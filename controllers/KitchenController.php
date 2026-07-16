<?php
/**
 * Kitchen display. Cooks see the queue and tick dishes off; they reach nothing
 * else in the app (see ROLES_KITCHEN and roleHome()).
 *
 * The screen polls feed() rather than holding a socket open: an 8s refresh is
 * plenty for a pass, and it survives the flaky connections this runs on.
 */
class KitchenController extends Controller {

    private function guard(): void {
        $this->requireRole(ROLES_KITCHEN);
        if (!isRestaurant()) {
            $this->flash('error', __('orders.retail_mode', 'Module restaurant desactive.'));
            $this->redirect(roleHome());
        }
    }

    public function index(): void {
        $this->guard();
        $this->render('kitchen/index', ['pageTitle' => __('nav.kitchen', 'Cuisine')]);
    }

    /** Live queue: every order with at least one dish still to cook or to pass. */
    public function feed(): void {
        $this->guard();

        $rows = $this->db->query("
            SELECT o.id, o.number, o.type, o.status, o.sent_at,
                   t.name AS table_name,
                   TIMESTAMPDIFF(MINUTE, o.sent_at, NOW()) AS waiting_min,
                   oi.id AS item_id, oi.description, oi.quantity, oi.notes, oi.status AS item_status
            FROM orders o
            LEFT JOIN service_tables t ON o.table_id = t.id
            JOIN order_items oi ON oi.order_id = o.id
            WHERE o.status IN ('sent','preparing','ready')
              AND oi.status IN ('sent','preparing','ready')
            ORDER BY o.sent_at ASC, oi.created_at ASC
        ")->fetchAll();

        // Flat rows -> one entry per ticket, so the screen renders cards not lines.
        $orders = [];
        foreach ($rows as $r) {
            $id = $r['id'];
            if (!isset($orders[$id])) {
                $orders[$id] = [
                    'id'          => $id,
                    'number'      => $r['number'],
                    'type'        => $r['type'],
                    'status'      => $r['status'],
                    'table_name'  => $r['table_name'],
                    'waiting_min' => (int)$r['waiting_min'],
                    'items'       => [],
                ];
            }
            $orders[$id]['items'][] = [
                'id'       => $r['item_id'],
                'label'    => $r['description'],
                'qty'      => (int)$r['quantity'],
                'notes'    => $r['notes'],
                'status'   => $r['item_status'],
            ];
        }

        $this->json(['orders' => array_values($orders), 'at' => date('H:i:s')]);
    }

    /** Cook ticks a dish: preparing -> ready. */
    public function itemStatus(string $itemId): void {
        $this->guard();
        if (!verify_csrf()) { $this->json(['error' => 'Token invalide'], 403); }
        try {
            (new \App\Services\OrderService($this->db))->setItemStatus($itemId, (string)$this->input('status', 'ready'));
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
