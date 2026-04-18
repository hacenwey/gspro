<?php
class DebtController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $type = $this->input('type', 'receivable');
        $status = $this->input('status', '');
        $page = max(1, (int)$this->input('page', 1));

        $where = "WHERE d.type = ?";
        $params = [$type];
        if ($status) { $where .= " AND d.status = ?"; $params[] = $status; }

        if ($type === 'receivable') {
            $query = "SELECT d.*, c.first_name, c.last_name FROM debts d LEFT JOIN customers c ON d.customer_id = c.id $where ORDER BY d.due_date ASC";
        } else {
            $query = "SELECT d.*, s.company_name FROM debts d LEFT JOIN suppliers s ON d.supplier_id = s.id $where ORDER BY d.due_date ASC";
        }

        $result = $this->paginate($query, $params, $page);

        // Stats
        $totalDue = $this->db->prepare("SELECT COALESCE(SUM(remaining), 0) FROM debts WHERE type = ? AND status IN ('pending','partial','overdue')");
        $totalDue->execute([$type]);
        $totalDue = $totalDue->fetchColumn();

        $overdueCount = $this->db->prepare("SELECT COUNT(*) FROM debts WHERE type = ? AND status IN ('pending','partial') AND due_date < CURDATE()");
        $overdueCount->execute([$type]);
        $overdueCount = $overdueCount->fetchColumn();

        // Auto-update overdue statuses
        $this->db->prepare("UPDATE debts SET status = 'overdue' WHERE type = ? AND status IN ('pending','partial') AND due_date < CURDATE()")->execute([$type]);

        $this->render('debts/index', array_merge($result, [
            'pageTitle' => 'Dettes & Credits',
            'typeFilter' => $type,
            'statusFilter' => $status,
            'totalDue' => $totalDue,
            'overdueCount' => $overdueCount
        ]));
    }

    public function recordPayment(string $id): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/debts'); }

        $debt = $this->db->prepare("SELECT * FROM debts WHERE id = ?");
        $debt->execute([$id]);
        $debt = $debt->fetch();
        if (!$debt) { $this->redirect('/debts'); }

        $amount = (float)$this->input('amount');
        $method = $this->input('method', 'cash');

        if ($amount <= 0 || $amount > $debt['remaining']) {
            $this->flash('error', 'Montant invalide.');
            $this->redirect('/debts');
        }

        $this->db->beginTransaction();
        try {
            $svc = new \App\Services\PaymentService($this->db);
            $svc->applyDebtPayment($debt, $amount, $method, $this->input('notes'), $_SESSION['user_id']);
            $this->db->commit();
            $this->flash('success', 'Paiement de ' . formatMoney($amount) . ' enregistre.');
        } catch (Exception $e) {
            $this->db->rollBack();
            \App\Logging\Logger::exception($e, ['action' => 'debt.pay', 'debt_id' => $id]);
            $this->flash('error', 'Erreur: ' . $e->getMessage());
        }

        $this->redirect('/debts?type=' . $debt['type']);
    }

    public function view(string $id): void {
        $this->requireAuth();
        $debt = $this->db->prepare("SELECT d.*, c.first_name, c.last_name, s.company_name FROM debts d LEFT JOIN customers c ON d.customer_id = c.id LEFT JOIN suppliers s ON d.supplier_id = s.id WHERE d.id = ?");
        $debt->execute([$id]);
        $debt = $debt->fetch();
        if (!$debt) { $this->redirect('/debts'); }

        $payments = $this->db->prepare("SELECT * FROM payments WHERE (customer_id = ? OR supplier_id = ?) AND invoice_id = ? ORDER BY payment_date DESC");
        $payments->execute([$debt['customer_id'], $debt['supplier_id'], $debt['invoice_id']]);

        $this->render('debts/view', [
            'pageTitle' => 'Detail dette/creance',
            'debt' => $debt,
            'payments' => $payments->fetchAll()
        ]);
    }
}
