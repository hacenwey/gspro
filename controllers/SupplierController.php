<?php
class SupplierController extends Controller {

    public function index(): void {
        $this->requireRole(ROLES_STAFF);
        $search = $this->input('search', '');
        $page = max(1, (int)$this->input('page', 1));
        $where = "WHERE is_active = 1";
        $params = [];
        if ($search) { $where .= " AND (company_name LIKE ? OR contact_name LIKE ? OR phone LIKE ?)"; $params = ["%$search%", "%$search%", "%$search%"]; }
        $result = $this->paginate("SELECT * FROM suppliers $where ORDER BY company_name", $params, $page);
        $this->render('suppliers/index', array_merge($result, ['pageTitle' => 'Fournisseurs', 'search' => $search]));
    }

    public function create(): void {
        $this->requireRole(ROLES_STAFF);
        $this->render('suppliers/form', ['pageTitle' => 'Nouveau fournisseur', 'supplier' => null]);
    }

    public function store(): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/suppliers'); }
        $stmt = $this->db->prepare("INSERT INTO suppliers (id, company_name, contact_name, phone, email, address, tax_id, payment_terms, notes) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$this->generateUUID(), trim($this->input('company_name')), $this->input('contact_name'), $this->input('phone'), $this->input('email'), $this->input('address'), $this->input('tax_id'), $this->input('payment_terms', 30), $this->input('notes')]);
        $this->flash('success', 'Fournisseur cree.'); $this->redirect('/suppliers');
    }

    public function edit(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?"); $stmt->execute([$id]);
        $supplier = $stmt->fetch(); if (!$supplier) $this->redirect('/suppliers');
        $this->render('suppliers/form', ['pageTitle' => 'Modifier fournisseur', 'supplier' => $supplier]);
    }

    public function update(string $id): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/suppliers'); }
        $stmt = $this->db->prepare("UPDATE suppliers SET company_name=?, contact_name=?, phone=?, email=?, address=?, tax_id=?, payment_terms=?, notes=? WHERE id=?");
        $stmt->execute([trim($this->input('company_name')), $this->input('contact_name'), $this->input('phone'), $this->input('email'), $this->input('address'), $this->input('tax_id'), $this->input('payment_terms', 30), $this->input('notes'), $id]);
        $this->flash('success', 'Fournisseur mis a jour.'); $this->redirect('/suppliers');
    }

    public function delete(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $this->db->prepare("UPDATE suppliers SET is_active = 0 WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Fournisseur supprime.'); $this->redirect('/suppliers');
    }

    public function view(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?"); $stmt->execute([$id]);
        $supplier = $stmt->fetch(); if (!$supplier) $this->redirect('/suppliers');
        $orders = $this->db->prepare("SELECT * FROM purchase_orders WHERE supplier_id = ? ORDER BY created_at DESC LIMIT 10"); $orders->execute([$id]);
        $debts = $this->db->prepare("SELECT * FROM debts WHERE supplier_id = ? AND status IN ('pending','partial','overdue') ORDER BY due_date ASC"); $debts->execute([$id]);
        $this->render('suppliers/view', ['pageTitle' => $supplier['company_name'], 'supplier' => $supplier, 'orders' => $orders->fetchAll(), 'debts' => $debts->fetchAll()]);
    }
}
