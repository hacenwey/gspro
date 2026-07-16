<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Restaurant orders: an order that lives over time, unlike the retail POS sale
 * (cart -> paid invoice, instantly).
 *
 *   open -> sent -> preparing -> ready -> served -> paid
 *
 * The order-level status is *derived* from its lines, so the kitchen can tick
 * dish by dish and the floor still sees one meaningful state per ticket. Stock
 * is only moved at payment, by SellService — an order is a promise, not a sale,
 * and cancelling one must not have to put stock back.
 */
final class OrderService
{
    public function __construct(private PDO $db) {}

    /** Lines a cook still has to act on. */
    public const KITCHEN_STATUSES = ['sent', 'preparing', 'ready'];

    public function create(string $type, ?string $tableId, ?string $customerId, string $userId, ?string $sessionId, string $number): string
    {
        if (!in_array($type, ['dine_in', 'takeaway', 'delivery'], true)) {
            throw new RuntimeException('Type de commande invalide.');
        }
        if ($type === 'dine_in' && !$tableId) {
            throw new RuntimeException('Une table est requise pour une commande sur place.');
        }
        $id = $this->uuid();
        $this->db->prepare(
            "INSERT INTO orders (id, number, type, status, table_id, customer_id, user_id, cash_session_id)
             VALUES (?,?,?,'open',?,?,?,?)"
        )->execute([$id, $number, $type, $tableId ?: null, $customerId ?: null, $userId, $sessionId ?: null]);
        return $id;
    }

    /**
     * Add a line, pricing it from the product (never from the client).
     * Same line + same note stacks instead of piling up duplicate rows.
     */
    public function addItem(string $orderId, string $productId, int $qty, ?string $notes = null): void
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantite invalide.');
        }
        $this->assertEditable($orderId);

        $p = $this->db->prepare("SELECT id, name, selling_price, tax_rate FROM products WHERE id = ? AND is_active = 1");
        $p->execute([$productId]);
        $product = $p->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new RuntimeException('Produit introuvable ou desactive.');
        }

        $notes = $notes !== null && trim($notes) !== '' ? trim($notes) : null;

        // Only merge into a line the kitchen has not started yet.
        // Null-safe equality is spelled differently per driver: MySQL has <=>,
        // SQLite (used by the tests) has IS.
        $nullSafeEq = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'notes IS ?' : 'notes <=> ?';
        $existing = $this->db->prepare(
            "SELECT id, quantity FROM order_items
             WHERE order_id = ? AND product_id = ? AND status = 'pending'
               AND ($nullSafeEq) LIMIT 1"
        );
        $existing->execute([$orderId, $productId, $notes]);
        if ($row = $existing->fetch(PDO::FETCH_ASSOC)) {
            $newQty = (int)$row['quantity'] + $qty;
            $this->db->prepare("UPDATE order_items SET quantity = ?, line_total = ? WHERE id = ?")
                ->execute([$newQty, $this->lineTotal((float)$product['selling_price'], (float)$product['tax_rate'], $newQty), $row['id']]);
            return;
        }

        $this->db->prepare(
            "INSERT INTO order_items (id, order_id, product_id, description, quantity, unit_price, tax_rate, line_total, status, notes)
             VALUES (?,?,?,?,?,?,?,?, 'pending', ?)"
        )->execute([
            $this->uuid(), $orderId, $productId, $product['name'], $qty,
            (float)$product['selling_price'], (float)$product['tax_rate'],
            $this->lineTotal((float)$product['selling_price'], (float)$product['tax_rate'], $qty),
            $notes,
        ]);
    }

    public function removeItem(string $itemId): void
    {
        $row = $this->db->prepare("SELECT order_id, status FROM order_items WHERE id = ?");
        $row->execute([$itemId]);
        $item = $row->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            throw new RuntimeException('Ligne introuvable.');
        }
        if ($item['status'] !== 'pending') {
            throw new RuntimeException('Impossible de retirer une ligne deja en cuisine.');
        }
        $this->assertEditable($item['order_id']);
        $this->db->prepare("DELETE FROM order_items WHERE id = ?")->execute([$itemId]);
    }

    /**
     * Push every pending line to the kitchen queue.
     *
     * Returns the lines it just sent — the kitchen ticket must show only the new
     * dishes, not the whole order, or a second round would reprint the first.
     *
     * @return array<int, array{qty:int, label:string, notes:?string}>
     */
    public function send(string $orderId): array
    {
        $this->assertEditable($orderId);
        $q = $this->db->prepare("SELECT quantity, description, notes FROM order_items WHERE order_id = ? AND status = 'pending' ORDER BY created_at");
        $q->execute([$orderId]);
        $pending = $q->fetchAll(PDO::FETCH_ASSOC);
        if (!$pending) {
            throw new RuntimeException('Aucune nouvelle ligne a envoyer.');
        }
        $this->db->prepare("UPDATE order_items SET status = 'sent' WHERE order_id = ? AND status = 'pending'")->execute([$orderId]);
        $this->db->prepare("UPDATE orders SET status = 'sent', sent_at = COALESCE(sent_at, {$this->nowExpr()}) WHERE id = ?")->execute([$orderId]);
        $this->refreshStatus($orderId);

        return array_map(static fn(array $r): array => [
            'qty'   => (int)$r['quantity'],
            'label' => (string)$r['description'],
            'notes' => $r['notes'] !== null ? (string)$r['notes'] : null,
        ], $pending);
    }

    public function setItemStatus(string $itemId, string $status): string
    {
        if (!in_array($status, ['preparing', 'ready', 'served'], true)) {
            throw new RuntimeException('Statut invalide.');
        }
        $row = $this->db->prepare("SELECT order_id FROM order_items WHERE id = ?");
        $row->execute([$itemId]);
        $orderId = $row->fetchColumn();
        if (!$orderId) {
            throw new RuntimeException('Ligne introuvable.');
        }
        $this->db->prepare("UPDATE order_items SET status = ? WHERE id = ?")->execute([$status, $itemId]);
        $this->refreshStatus((string)$orderId);
        return (string)$orderId;
    }

    /**
     * Derive the order status from its lines: the ticket is only as advanced as
     * its least advanced dish.
     */
    public function refreshStatus(string $orderId): void
    {
        $cur = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
        $cur->execute([$orderId]);
        $orderStatus = $cur->fetchColumn();
        if (in_array($orderStatus, ['paid', 'cancelled'], true)) {
            return; // terminal
        }

        $q = $this->db->prepare(
            "SELECT
                SUM(status = 'pending')   AS pending,
                SUM(status = 'sent')      AS sent,
                SUM(status = 'preparing') AS preparing,
                SUM(status = 'ready')     AS ready,
                SUM(status = 'served')    AS served,
                COUNT(*)                  AS total
             FROM order_items WHERE order_id = ? AND status <> 'cancelled'"
        );
        $q->execute([$orderId]);
        $c = $q->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int)($c['total'] ?? 0);
        if ($total === 0) {
            $this->db->prepare("UPDATE orders SET status = 'open' WHERE id = ?")->execute([$orderId]);
            return;
        }

        if ((int)$c['served'] === $total)        $new = 'served';
        elseif ((int)$c['pending'] === $total)   $new = 'open';
        elseif ((int)$c['ready'] + (int)$c['served'] === $total) $new = 'ready';
        elseif ((int)$c['preparing'] > 0)        $new = 'preparing';
        else                                     $new = 'sent';

        $sql = "UPDATE orders SET status = ?";
        if ($new === 'ready')  $sql .= ", ready_at = COALESCE(ready_at, {$this->nowExpr()})";
        $sql .= " WHERE id = ?";
        $this->db->prepare($sql)->execute([$new, $orderId]);
    }

    /**
     * What is still owed, optionally for one bill's selection only.
     * @param string[]|null $itemIds null = every unpaid line
     * @return array{subtotal:float,tax:float,total:float}
     */
    public function totalsFor(string $orderId, ?array $itemIds = null): array
    {
        $sql = "SELECT COALESCE(SUM(unit_price * quantity), 0) AS sub,
                       COALESCE(SUM(unit_price * quantity * tax_rate / 100), 0) AS tax
                FROM order_items
                WHERE order_id = ? AND status <> 'cancelled' AND invoice_id IS NULL";
        $params = [$orderId];
        if ($itemIds !== null) {
            if (!$itemIds) {
                return ['subtotal' => 0.0, 'tax' => 0.0, 'total' => 0.0];
            }
            $sql .= " AND id IN (" . implode(',', array_fill(0, count($itemIds), '?')) . ")";
            $params = array_merge($params, array_values($itemIds));
        }
        $q = $this->db->prepare($sql);
        $q->execute($params);
        $r = $q->fetch(PDO::FETCH_ASSOC) ?: ['sub' => 0, 'tax' => 0];
        $sub = (float)$r['sub'];
        $tax = (float)$r['tax'];
        return ['subtotal' => $sub, 'tax' => $tax, 'total' => $sub + $tax];
    }

    /** Whole-order totals, paid lines included. @return array{subtotal:float,tax:float,total:float} */
    public function totals(string $orderId): array
    {
        $q = $this->db->prepare(
            "SELECT COALESCE(SUM(unit_price * quantity), 0) AS sub,
                    COALESCE(SUM(unit_price * quantity * tax_rate / 100), 0) AS tax
             FROM order_items WHERE order_id = ? AND status <> 'cancelled'"
        );
        $q->execute([$orderId]);
        $r = $q->fetch(PDO::FETCH_ASSOC) ?: ['sub' => 0, 'tax' => 0];
        $sub = (float)$r['sub'];
        $tax = (float)$r['tax'];
        return ['subtotal' => $sub, 'tax' => $tax, 'total' => $sub + $tax];
    }

    /**
     * Lines still to be settled: live, and not yet on an invoice.
     * @return array<int, array<string, mixed>>
     */
    public function unpaidItems(string $orderId): array
    {
        $q = $this->db->prepare(
            "SELECT * FROM order_items
             WHERE order_id = ? AND status <> 'cancelled' AND invoice_id IS NULL
             ORDER BY created_at"
        );
        $q->execute([$orderId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Quantities to move as stock, grouped per product — the shape SellService
     * expects. Restricted to $itemIds when a bill only covers part of the table.
     *
     * @param string[]|null $itemIds null = every unpaid line
     * @return array<int, array{id:string, qty:int}>
     */
    public function itemsForSale(string $orderId, ?array $itemIds = null): array
    {
        $sql = "SELECT product_id, SUM(quantity) AS qty FROM order_items
                WHERE order_id = ? AND status <> 'cancelled' AND product_id IS NOT NULL
                  AND invoice_id IS NULL";
        $params = [$orderId];
        if ($itemIds !== null) {
            if (!$itemIds) {
                return [];
            }
            $sql .= " AND id IN (" . implode(',', array_fill(0, count($itemIds), '?')) . ")";
            $params = array_merge($params, array_values($itemIds));
        }
        $sql .= " GROUP BY product_id";

        $q = $this->db->prepare($sql);
        $q->execute($params);
        $out = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = ['id' => (string)$r['product_id'], 'qty' => (int)$r['qty']];
        }
        return $out;
    }

    /**
     * Bind the settled lines to their invoice, then close the order if nothing
     * is left to pay. Splitting a bill means several invoices per order, so the
     * line — not the order — is what carries the payment link.
     *
     * @param string[]|null $itemIds null = every unpaid line
     */
    public function settle(string $orderId, ?array $itemIds, string $invoiceId): void
    {
        $sql = "UPDATE order_items SET invoice_id = ?
                WHERE order_id = ? AND status <> 'cancelled' AND invoice_id IS NULL";
        $params = [$invoiceId, $orderId];
        if ($itemIds !== null) {
            if (!$itemIds) {
                throw new RuntimeException('Aucune ligne selectionnee.');
            }
            $sql .= " AND id IN (" . implode(',', array_fill(0, count($itemIds), '?')) . ")";
            $params = array_merge($params, array_values($itemIds));
        }
        $this->db->prepare($sql)->execute($params);

        // orders.invoice_id keeps the *last* invoice, for a one-click link back;
        // the full truth of who paid what lives on the lines.
        if (!$this->unpaidItems($orderId)) {
            $this->db->prepare("UPDATE orders SET status = 'paid', invoice_id = ?, closed_at = {$this->nowExpr()} WHERE id = ?")
                ->execute([$invoiceId, $orderId]);
        } else {
            $this->db->prepare("UPDATE orders SET invoice_id = ? WHERE id = ?")->execute([$invoiceId, $orderId]);
        }
    }

    /** Settle the whole order in one invoice. */
    public function markPaid(string $orderId, string $invoiceId): void
    {
        $this->settle($orderId, null, $invoiceId);
    }

    /**
     * Move every line of $sourceId onto $targetId and close the source.
     *
     * Only unpaid lines move: a dish already invoiced belongs to the bill that
     * paid it. The source keeps its number and points at its host, so the merge
     * stays traceable instead of vanishing.
     */
    public function merge(string $sourceId, string $targetId): void
    {
        if ($sourceId === $targetId) {
            throw new RuntimeException('Impossible de fusionner une commande avec elle-meme.');
        }
        $this->assertEditable($sourceId);
        $this->assertEditable($targetId);

        $moved = $this->db->prepare("UPDATE order_items SET order_id = ? WHERE order_id = ? AND invoice_id IS NULL AND status <> 'cancelled'");
        $moved->execute([$targetId, $sourceId]);
        if ($moved->rowCount() === 0) {
            throw new RuntimeException('Aucune ligne a fusionner.');
        }

        $this->db->prepare("UPDATE orders SET status = 'cancelled', merged_into_id = ?, closed_at = {$this->nowExpr()} WHERE id = ?")
            ->execute([$targetId, $sourceId]);

        $this->refreshStatus($targetId);
    }

    public function cancel(string $orderId): void
    {
        $cur = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
        $cur->execute([$orderId]);
        if ($cur->fetchColumn() === 'paid') {
            throw new RuntimeException('Une commande encaissee ne peut pas etre annulee.');
        }
        // Never touch an invoiced line: cancelling it would rewrite the history of
        // a bill someone already paid. Only what is still owed gets cancelled.
        $this->db->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ? AND invoice_id IS NULL")->execute([$orderId]);
        $this->db->prepare("UPDATE orders SET status = 'cancelled', closed_at = {$this->nowExpr()} WHERE id = ?")->execute([$orderId]);
    }

    private function assertEditable(string $orderId): void
    {
        $cur = $this->db->prepare("SELECT status FROM orders WHERE id = ?");
        $cur->execute([$orderId]);
        $s = $cur->fetchColumn();
        if ($s === false) {
            throw new RuntimeException('Commande introuvable.');
        }
        if (in_array($s, ['paid', 'cancelled'], true)) {
            throw new RuntimeException('Cette commande est cloturee.');
        }
    }

    /** NOW() on MySQL, DATETIME('now') on SQLite (tests). Mirrors PaymentService. */
    private function nowExpr(): string
    {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "DATETIME('now')" : 'NOW()';
    }

    private function lineTotal(float $unit, float $taxRate, int $qty): float
    {
        $sub = $unit * $qty;
        return $sub + $sub * ($taxRate / 100);
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
