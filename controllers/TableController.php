<?php
/**
 * Dining tables. Managed by the back office, consumed by the order screen.
 */
class TableController extends Controller {

    private function guard(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);
        if (!isRestaurant()) {
            $this->flash('error', __('orders.retail_mode', 'Module restaurant desactive.'));
            $this->redirect(roleHome());
        }
    }

    public function index(): void {
        $this->guard();
        $tables = $this->db->query("
            SELECT t.*,
                   (SELECT COUNT(*) FROM orders o WHERE o.table_id = t.id AND o.status NOT IN ('paid','cancelled')) AS busy
            FROM service_tables t ORDER BY t.zone, t.name
        ")->fetchAll();
        $this->render('orders/tables', ['pageTitle' => __('nav.tables', 'Salle'), 'tables' => $tables]);
    }

    public function store(): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/tables'); }
        $name = trim((string)$this->input('name'));
        if ($name === '') {
            $this->flash('error', __('tables.name_required', 'Le nom est requis.'));
            $this->redirect('/tables');
        }
        $this->db->prepare("INSERT INTO service_tables (id, name, zone, seats) VALUES (?,?,?,?)")
            ->execute([$this->generateUUID(), $name, trim((string)$this->input('zone')) ?: null, (int)$this->input('seats', 0)]);
        $this->flash('success', __('tables.created', 'Table ajoutee.'));
        $this->redirect('/tables');
    }

    public function update(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/tables'); }
        $this->db->prepare("UPDATE service_tables SET name=?, zone=?, seats=?, is_active=? WHERE id=?")
            ->execute([
                trim((string)$this->input('name')),
                trim((string)$this->input('zone')) ?: null,
                (int)$this->input('seats', 0),
                $this->input('is_active') === '1' ? 1 : 0,
                $id,
            ]);
        $this->flash('success', __('tables.updated', 'Table mise a jour.'));
        $this->redirect('/tables');
    }

    /**
     * Deactivate rather than delete: a table carries history through orders, and
     * dropping it would strip past tickets of where they were served.
     */
    public function delete(string $id): void {
        $this->guard();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/tables'); }
        $open = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE table_id = ? AND status NOT IN ('paid','cancelled')");
        $open->execute([$id]);
        if ((int)$open->fetchColumn() > 0) {
            $this->flash('error', __('tables.busy', 'Table occupee : cloturez la commande d\'abord.'));
            $this->redirect('/tables');
        }
        $this->db->prepare("UPDATE service_tables SET is_active = 0 WHERE id = ?")->execute([$id]);
        $this->flash('success', __('tables.removed', 'Table desactivee.'));
        $this->redirect('/tables');
    }
}
