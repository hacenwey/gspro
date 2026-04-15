<?php
class DashboardController extends Controller {

    public function index(): void {
        $this->requireAuth();

        // Check onboarding
        $onboardingDone = $this->db->query("SELECT setting_value FROM settings WHERE setting_key = 'onboarding_done'")->fetchColumn();

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // KPIs
        $data['todaySales'] = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE type = 'invoice' AND status IN ('paid','partial') AND issue_date = '$today'")->fetchColumn();

        $data['todayCount'] = $this->db->query("SELECT COUNT(*) FROM invoices WHERE type = 'invoice' AND issue_date = '$today'")->fetchColumn();

        $data['monthSales'] = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE type = 'invoice' AND status IN ('paid','partial') AND issue_date >= '$monthStart'")->fetchColumn();

        $data['totalReceivables'] = $this->db->query("SELECT COALESCE(SUM(remaining), 0) FROM debts WHERE type = 'receivable' AND status IN ('pending','partial','overdue')")->fetchColumn();

        $data['totalPayables'] = $this->db->query("SELECT COALESCE(SUM(remaining), 0) FROM debts WHERE type = 'payable' AND status IN ('pending','partial','overdue')")->fetchColumn();

        $data['lowStockCount'] = $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND current_stock <= min_stock")->fetchColumn();

        $data['totalProducts'] = $this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

        $data['totalClients'] = $this->db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

        // Low stock products
        $data['lowStock'] = $this->db->query("SELECT id, name, reference, current_stock, min_stock FROM products WHERE is_active = 1 AND current_stock <= min_stock ORDER BY current_stock ASC LIMIT 8")->fetchAll();

        // Recent invoices
        $data['recentInvoices'] = $this->db->query("
            SELECT i.*, c.last_name, c.first_name
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.type = 'invoice'
            ORDER BY i.created_at DESC LIMIT 5
        ")->fetchAll();

        // Overdue debts
        $data['overdueDebts'] = $this->db->query("
            SELECT d.*, c.last_name, c.first_name
            FROM debts d
            LEFT JOIN customers c ON d.customer_id = c.id
            WHERE d.type = 'receivable' AND d.status IN ('pending','partial','overdue') AND d.due_date <= '$today'
            ORDER BY d.due_date ASC LIMIT 5
        ")->fetchAll();

        // Top selling products (this month)
        $data['topProducts'] = $this->db->query("
            SELECT p.name, SUM(ii.quantity) as total_qty, SUM(ii.line_total) as total_amount
            FROM invoice_items ii
            JOIN invoices i ON ii.invoice_id = i.id
            JOIN products p ON ii.product_id = p.id
            WHERE i.type = 'invoice' AND i.issue_date >= '$monthStart'
            GROUP BY p.id, p.name
            ORDER BY total_amount DESC LIMIT 5
        ")->fetchAll();

        $data['pageTitle'] = __('dash.title');
        $data['showOnboarding'] = ($onboardingDone !== '1');
        if ($data['showOnboarding']) {
            $data['companySettings'] = [];
            foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) {
                $data['companySettings'][$s['setting_key']] = $s['setting_value'];
            }
        }
        $this->render('dashboard/index', $data);
    }

    public function chartData(): void {
        $this->requireAuth();
        $period = $_GET['period'] ?? '7d';

        $data = [];
        if ($period === '7d') {
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = date('d/m', strtotime($date));
                $amount = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE type = 'invoice' AND status IN ('paid','partial') AND issue_date = '$date'")->fetchColumn();
                $data[] = ['label' => $label, 'amount' => (float)$amount];
            }
        } elseif ($period === '30d') {
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = date('d/m', strtotime($date));
                $amount = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE type = 'invoice' AND status IN ('paid','partial') AND issue_date = '$date'")->fetchColumn();
                $data[] = ['label' => $label, 'amount' => (float)$amount];
            }
        } else {
            for ($i = 11; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $label = date('M Y', strtotime($month . '-01'));
                $amount = $this->db->query("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE type = 'invoice' AND status IN ('paid','partial') AND issue_date LIKE '$month%'")->fetchColumn();
                $data[] = ['label' => $label, 'amount' => (float)$amount];
            }
        }

        $this->json($data);
    }
}
