<?php
class ClientController extends Controller {

    public function index(): void {
        $this->requireRole(ROLES_STAFF);
        $search = $this->input('search', '');
        $category = $this->input('category', '');
        $page = max(1, (int)$this->input('page', 1));

        $where = "WHERE 1=1";
        $params = [];
        if ($search) {
            $where .= " AND (last_name LIKE ? OR first_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        if ($category) { $where .= " AND category = ?"; $params[] = $category; }

        $query = "SELECT * FROM customers $where ORDER BY last_name ASC";
        $result = $this->paginate($query, $params, $page);

        $this->render('clients/index', array_merge($result, [
            'pageTitle' => 'Clients',
            'search' => $search,
            'categoryFilter' => $category
        ]));
    }

    public function create(): void {
        $this->requireRole(ROLES_STAFF);
        $this->render('clients/form', ['pageTitle' => 'Nouveau client', 'client' => null]);
    }

    public function store(): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/clients'); }

        $stmt = $this->db->prepare("INSERT INTO customers (id, type, first_name, last_name, phone, email, address, tax_id, category, credit_limit, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $this->generateUUID(),
            $this->input('type', 'individual'),
            $this->input('first_name'),
            trim($this->input('last_name')),
            $this->input('phone'),
            $this->input('email'),
            $this->input('address'),
            $this->input('tax_id'),
            $this->input('category', 'regular'),
            $this->input('credit_limit', 0),
            $this->input('notes')
        ]);

        $this->flash('success', 'Client cree avec succes.');
        $this->redirect('/clients');
    }

    public function edit(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        if (!$client) { $this->redirect('/clients'); }
        $this->render('clients/form', ['pageTitle' => 'Modifier client', 'client' => $client]);
    }

    public function update(string $id): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/clients'); }

        $stmt = $this->db->prepare("UPDATE customers SET type=?, first_name=?, last_name=?, phone=?, email=?, address=?, tax_id=?, category=?, credit_limit=?, notes=? WHERE id=?");
        $stmt->execute([
            $this->input('type', 'individual'),
            $this->input('first_name'),
            trim($this->input('last_name')),
            $this->input('phone'),
            $this->input('email'),
            $this->input('address'),
            $this->input('tax_id'),
            $this->input('category', 'regular'),
            $this->input('credit_limit', 0),
            $this->input('notes'),
            $id
        ]);

        $this->flash('success', 'Client mis a jour.');
        $this->redirect('/clients');
    }

    public function delete(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $this->db->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Client supprime.');
        $this->redirect('/clients');
    }

    public function view(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        if (!$client) { $this->redirect('/clients'); }

        $invoices = $this->db->prepare("SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
        $invoices->execute([$id]);

        $debts = $this->db->prepare("SELECT * FROM debts WHERE customer_id = ? AND status IN ('pending','partial','overdue') ORDER BY due_date ASC");
        $debts->execute([$id]);

        $stats = [];
        $stats['total_purchases'] = $this->db->prepare("SELECT COALESCE(SUM(total), 0) FROM invoices WHERE customer_id = ? AND type = 'invoice' AND status IN ('paid','partial')");
        $stats['total_purchases']->execute([$id]);
        $stats['total_purchases'] = $stats['total_purchases']->fetchColumn();

        $stats['invoice_count'] = $this->db->prepare("SELECT COUNT(*) FROM invoices WHERE customer_id = ? AND type = 'invoice'");
        $stats['invoice_count']->execute([$id]);
        $stats['invoice_count'] = $stats['invoice_count']->fetchColumn();

        $this->render('clients/view', [
            'pageTitle' => ($client['first_name'] ? $client['first_name'] . ' ' : '') . $client['last_name'],
            'client' => $client,
            'invoices' => $invoices->fetchAll(),
            'debts' => $debts->fetchAll(),
            'stats' => $stats
        ]);
    }

    /**
     * Client lookup for the POS — the one client endpoint a cashier may call.
     * Read-only and returns no back-office data beyond what the till needs.
     */
    public function search(): void {
        $this->requireAuth();
        $q = $this->input('q', '');
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, phone, balance, credit_limit FROM customers WHERE last_name LIKE ? OR first_name LIKE ? OR phone LIKE ? ORDER BY last_name LIMIT 20");
        $stmt->execute(["%$q%", "%$q%", "%$q%"]);
        $this->json($stmt->fetchAll());
    }
}
