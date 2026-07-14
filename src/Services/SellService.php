<?php
namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Encapsulates the POS "record a sale" use case.
 *
 * Responsibilities:
 *  - Lock requested stock rows (deterministic order, single SQL round-trip).
 *  - Validate availability against aggregated demand.
 *  - Compute line totals, subtotal, tax.
 *  - Decrement stock atomically with a guard, rolling back on race.
 *
 * Does NOT own the transaction: the caller opens/commits so the invoice
 * creation, payments and debt writes stay in the same unit of work.
 */
final class SellService
{
    public function __construct(private PDO $db) {}

    /**
     * @param array<int, array{id:string, qty:int|float}> $items
     * @return array{
     *   subtotal: float,
     *   tax: float,
     *   total: float,
     *   lines: array<int, array{product_id:string, description:string, quantity:int, unit_price:float, tax_rate:float, line_total:float}>
     * }
     */
    public function priceAndLock(array $items, bool $allowNegativeStock = false): array
    {
        if (empty($items)) {
            throw new RuntimeException('Panier vide');
        }

        $requested = [];
        foreach ($items as $it) {
            $pid = (string)($it['id'] ?? '');
            $qty = (int)($it['qty'] ?? 0);
            if ($pid === '' || $qty <= 0) {
                throw new RuntimeException('Ligne panier invalide.');
            }
            $requested[$pid] = ($requested[$pid] ?? 0) + $qty;
        }

        $ids = array_keys($requested);
        sort($ids, SORT_STRING);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // FOR UPDATE is a MySQL/Postgres concept. SQLite serializes writes via
        // its transaction lock, so we omit it there (tests run on SQLite).
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lockClause = in_array($driver, ['mysql', 'pgsql'], true) ? 'FOR UPDATE' : '';

        $stmt = $this->db->prepare("
            SELECT id, name, selling_price, tax_rate, current_stock
            FROM products
            WHERE id IN ($placeholders) AND is_active = 1
            $lockClause
        ");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $byId = [];
        foreach ($rows as $r) { $byId[$r['id']] = $r; }

        $subtotal = 0.0;
        $tax      = 0.0;
        $lines    = [];

        foreach ($items as $it) {
            $pid = (string)$it['id'];
            $qty = (int)$it['qty'];
            $product = $byId[$pid] ?? null;
            if (!$product) {
                throw new RuntimeException("Produit introuvable ou desactive.");
            }
            // Offline sales already left the shelf: record them even if the
            // server view of stock is behind (it may go negative, reconciled later).
            if (!$allowNegativeStock && (int)$product['current_stock'] < $requested[$pid]) {
                throw new RuntimeException("Stock insuffisant pour: " . $product['name']);
            }

            $unit  = (float)$product['selling_price'];
            $rate  = (float)$product['tax_rate'];
            $lineSub = $unit * $qty;
            $lineTax = $lineSub * ($rate / 100);

            $subtotal += $lineSub;
            $tax      += $lineTax;

            $lines[] = [
                'product_id' => $product['id'],
                'description'=> $product['name'],
                'quantity'   => $qty,
                'unit_price' => $unit,
                'tax_rate'   => $rate,
                'line_total' => $lineSub + $lineTax,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'total'    => $subtotal + $tax,
            'lines'    => $lines,
        ];
    }

    /**
     * Decrement stock with an atomic guard. Throws if a concurrent sale
     * slipped between the FOR UPDATE and this call.
     */
    public function decrementStock(string $productId, int $qty, bool $allowNegative = false): void
    {
        if ($allowNegative) {
            // Synced offline sale: force the decrement (stock may go negative).
            $this->db->prepare("UPDATE products SET current_stock = current_stock - ? WHERE id = ?")
                ->execute([$qty, $productId]);
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE products SET current_stock = current_stock - ?
            WHERE id = ? AND current_stock >= ?
        ");
        $stmt->execute([$qty, $productId, $qty]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("Stock insuffisant (conflit concurrent).");
        }
    }
}
