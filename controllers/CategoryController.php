<?php
class CategoryController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $categories = $this->db->query("
            SELECT c.*, pc.name as parent_name,
            (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1) as product_count
            FROM categories c
            LEFT JOIN categories pc ON c.parent_id = pc.id
            ORDER BY c.name
        ")->fetchAll();

        $this->render('products/categories', [
            'pageTitle' => 'Categories',
            'categories' => $categories
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/categories'); }

        $stmt = $this->db->prepare("INSERT INTO categories (id, name, parent_id, description) VALUES (?,?,?,?)");
        $stmt->execute([
            $this->generateUUID(),
            trim($this->input('name')),
            $this->input('parent_id') ?: null,
            $this->input('description')
        ]);

        $this->flash('success', 'Categorie creee.');
        $this->redirect('/categories');
    }

    public function update(string $id): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/categories'); }

        $stmt = $this->db->prepare("UPDATE categories SET name=?, parent_id=?, description=? WHERE id=?");
        $stmt->execute([
            trim($this->input('name')),
            $this->input('parent_id') ?: null,
            $this->input('description'),
            $id
        ]);

        $this->flash('success', 'Categorie mise a jour.');
        $this->redirect('/categories');
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->db->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $this->db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Categorie supprimee.');
        $this->redirect('/categories');
    }
}
