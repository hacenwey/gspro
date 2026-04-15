<?php
class PaymentController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $type = $this->input('type', '');
        $method = $this->input('method', '');
        $page = max(1, (int)$this->input('page', 1));

        $where = "WHERE 1=1"; $params = [];
        if ($type) { $where .= " AND p.type = ?"; $params[] = $type; }
        if ($method) { $where .= " AND p.method = ?"; $params[] = $method; }

        $query = "SELECT p.*, u.full_name as user_name, c.last_name as client_name, s.company_name as supplier_name, i.number as invoice_number
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN customers c ON p.customer_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN invoices i ON p.invoice_id = i.id
            $where ORDER BY p.created_at DESC";

        $result = $this->paginate($query, $params, $page);

        $totalIn = $this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE type='incoming'")->fetchColumn();
        $totalOut = $this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE type='outgoing'")->fetchColumn();

        $this->render('payments/index', array_merge($result, [
            'pageTitle' => 'Paiements',
            'typeFilter' => $type,
            'methodFilter' => $method,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut
        ]));
    }
}
