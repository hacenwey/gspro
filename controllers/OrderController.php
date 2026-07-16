<?php
/**
 * Restaurant order taking: the waiter's screen.
 *
 * Only reachable when the tenant runs in restaurant mode (settings.business_type)
 * — a clothing shop has no tables and no kitchen.
 */
class OrderController extends Controller {

    /** Restaurant-only, and never for kitchen staff (they only get the pass). */
    private function guard(): void {
        $this->requireRole(ROLES_ORDERS);
        if (!isRestaurant()) {
            $this->flash('error', __('orders.retail_mode', 'Module restaurant desactive.'));
            $this->redirect(roleHome());
        }
    }

    private function svc(): \App\Services\OrderService {
        return new \App\Services\OrderService($this->db);
    }

    public function index(): void {
        $this->guard();

        $orders = $this->db->query("
            SELECT o.*, t.name AS table_name, u.full_name AS waiter,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id AND oi.status <> 'cancelled') AS item_count,
                   (SELECT COALESCE(SUM(oi.line_total),0) FROM order_items oi WHERE oi.order_id = o.id AND oi.status <> 'cancelled') AS total
            FROM orders o
            LEFT JOIN service_tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.status NOT IN ('paid','cancelled')
            ORDER BY FIELD(o.status,'ready','preparing','sent','open','served'), o.created_at ASC
        ")->fetchAll();

        $tables = $this->db->query("
            SELECT t.*, o.id AS order_id, o.status AS order_status
            FROM service_tables t
            LEFT JOIN orders o ON o.table_id = t.id AND o.status NOT IN ('paid','cancelled')
            WHERE t.is_active = 1 ORDER BY t.zone, t.name
        ")->fetchAll();

        $this->render('orders/index', [
            'pageTitle' => __('nav.orders', 'Commandes'),
            'orders'    => $orders,
            'tables'    => $tables,
        ]);
    }

    public function store(): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/orders'); }

        $session = $this->openSessionId();
        try {
            $id = $this->svc()->create(
                (string)$this->input('type', 'dine_in'),
                $this->input('table_id') ?: null,
                $this->input('customer_id') ?: null,
                $_SESSION['user_id'],
                $session,
                $this->generateNumber(ORDER_PREFIX, 'orders')
            );
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/orders');
            return;
        }
        $this->redirect('/orders/view/' . $id);
    }

    public function view(string $id): void {
        $this->guard();

        $stmt = $this->db->prepare("
            SELECT o.*, t.name AS table_name, u.full_name AS waiter,
                   CONCAT_WS(' ', c.first_name, c.last_name) AS customer_name
            FROM orders o
            LEFT JOIN service_tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) { $this->redirect('/orders'); }

        $items = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY created_at");
        $items->execute([$id]);

        $products = $this->db->query("
            SELECT p.id, p.name, p.selling_price, p.tax_rate, p.category_id
            FROM products p WHERE p.is_active = 1 ORDER BY p.name LIMIT 300
        ")->fetchAll();
        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

        // Other open tickets, so this one can be merged into a table that joined up.
        $mergeable = $this->db->prepare("
            SELECT o.id, o.number, t.name AS table_name
            FROM orders o LEFT JOIN service_tables t ON o.table_id = t.id
            WHERE o.status NOT IN ('paid','cancelled') AND o.id <> ?
            ORDER BY o.created_at");
        $mergeable->execute([$id]);

        $this->render('orders/view', [
            'pageTitle'  => $order['number'],
            'order'      => $order,
            'items'      => $items->fetchAll(),
            'products'   => $products,
            'categories' => $categories,
            'totals'     => $this->svc()->totals($id),
            'due'        => $this->svc()->totalsFor($id),   // what is still owed
            'mergeable'  => $mergeable->fetchAll(),
        ]);
    }

    public function addItem(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->json(['error' => 'Token invalide'], 403); }
        try {
            $this->svc()->addItem($id, (string)$this->input('product_id'), (int)$this->input('qty', 1), $this->input('notes'));
            $this->json(['success' => true] + $this->svc()->totals($id));
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function removeItem(string $itemId): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/orders'); }
        $row = $this->db->prepare("SELECT order_id FROM order_items WHERE id = ?");
        $row->execute([$itemId]);
        $orderId = $row->fetchColumn();
        try {
            $this->svc()->removeItem($itemId);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect($orderId ? '/orders/view/' . $orderId : '/orders');
    }

    /**
     * Send to the kitchen and hand back the ticket to print.
     *
     * Answers JSON rather than redirecting: the browser has to render and print
     * the kitchen ticket, and only the lines just sent belong on it.
     */
    public function send(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->json(['error' => 'Token invalide'], 403); }

        try {
            $sent = $this->svc()->send($id);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
            return;
        }

        $stmt = $this->db->prepare("
            SELECT o.number, o.type, t.name AS table_name, u.full_name AS waiter
            FROM orders o
            LEFT JOIN service_tables t ON o.table_id = t.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?");
        $stmt->execute([$id]);
        $o = $stmt->fetch() ?: [];

        $this->json(['success' => true, 'ticket' => [
            'number' => $o['number'] ?? '',
            'type'   => __('orders.type.' . ($o['type'] ?? 'dine_in')),
            'table'  => $o['table_name'] ?? '',
            'waiter' => $o['waiter'] ?? '',
            'at'     => date('d/m/Y H:i'),
            'items'  => $sent,
        ]]);
    }

    public function cancel(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/orders/view/' . $id); }
        try {
            $this->svc()->cancel($id);
            $this->flash('success', __('orders.cancelled_ok', 'Commande annulee.'));
            $this->redirect('/orders');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/orders/view/' . $id);
        }
    }

    /**
     * Turn the order into a paid invoice. Reuses the POS path, so stock movements,
     * payments and debts behave exactly as a counter sale.
     */
    public function pay(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/orders/view/' . $id); }

        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) { $this->redirect('/orders'); }
        if ($order['status'] === 'paid') {
            $this->flash('error', __('orders.already_paid', 'Commande deja encaissee.'));
            $this->redirect('/orders/view/' . $id);
        }

        $svc = $this->svc();

        // A split bill posts the lines it covers; no selection means "all that's left".
        $itemIds = $_POST['item_ids'] ?? null;
        $itemIds = is_array($itemIds) && $itemIds ? array_map('strval', $itemIds) : null;

        $items = $svc->itemsForSale($id, $itemIds);
        if (!$items) {
            $this->flash('error', __('orders.empty', 'Commande vide.'));
            $this->redirect('/orders/view/' . $id);
        }

        $method     = (string)$this->input('payment_method', 'cash');
        $amountPaid = (float)$this->input('amount_paid', 0);

        $this->db->beginTransaction();
        try {
            $sell   = new \App\Services\SellService($this->db);
            $priced = $sell->priceAndLock($items);

            $invoiceId = $this->generateUUID();
            $number    = $this->generateNumber(INVOICE_PREFIX, 'invoices');

            $this->db->prepare("INSERT INTO invoices (id, number, type, status, customer_id, user_id, issue_date, due_date, subtotal, tax_amount, total, amount_paid) VALUES (?,?,?,'paid',?,?,CURDATE(),CURDATE(),?,?,?,?)")
                ->execute([$invoiceId, $number, 'invoice', $order['customer_id'], $_SESSION['user_id'],
                           $priced['subtotal'], $priced['tax'], $priced['total'], $priced['total']]);

            foreach ($priced['lines'] as $line) {
                $this->db->prepare("INSERT INTO invoice_items (id, invoice_id, product_id, description, quantity, unit_price, tax_rate, line_total) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$this->generateUUID(), $invoiceId, $line['product_id'], $line['description'],
                               $line['quantity'], $line['unit_price'], $line['tax_rate'], $line['line_total']]);
                $sell->decrementStock($line['product_id'], (int)$line['quantity']);
                $this->db->prepare("INSERT INTO stock_movements (id, product_id, type, reason, quantity, unit_cost, reference_type, reference_id, user_id) VALUES (?,?,'out','sale',?,?,'invoice',?,?)")
                    ->execute([$this->generateUUID(), $line['product_id'], $line['quantity'], $line['unit_price'], $invoiceId, $_SESSION['user_id']]);
            }

            (new \App\Services\PaymentService($this->db))->recordSalePayment(
                $invoiceId, $order['customer_id'], $order['cash_session_id'] ?: $this->openSessionId(),
                $priced['total'], $method, $_SESSION['user_id']
            );

            $svc->settle($id, $itemIds, $invoiceId);
            $this->db->commit();

            $change    = max(0, $amountPaid - $priced['total']);
            $remaining = $svc->totalsFor($id)['total'];
            $this->flash('success', sprintf('%s %s — %s%s%s',
                __('orders.paid_ok', 'Encaissee.'), $number, formatMoney($priced['total']),
                $change > 0 ? ' | ' . __('pos.change') . ': ' . formatMoney($change) : '',
                $remaining > 0 ? ' | ' . __('orders.remaining', 'Reste') . ': ' . formatMoney($remaining) : ''));

            // A partly-settled table stays open on its own ticket.
            $this->redirect($remaining > 0 ? '/orders/view/' . $id : '/orders');
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->flash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('/orders/view/' . $id);
        }
    }

    /** Merge this ticket into another open one (two tables joining up). */
    public function merge(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/orders/view/' . $id); }

        $target = (string)$this->input('target_id', '');
        try {
            $this->svc()->merge($id, $target);
            $this->flash('success', __('orders.merged_ok', 'Commandes fusionnees.'));
            $this->redirect('/orders/view/' . $target);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/orders/view/' . $id);
        }
    }

    /** Current user's open till, if any — lets restaurant takings land in the session. */
    private function openSessionId(): ?string {
        $s = $this->db->prepare("SELECT id FROM cash_sessions WHERE user_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
        $s->execute([$_SESSION['user_id']]);
        return $s->fetchColumn() ?: null;
    }
}
