<?php
class CategoryController extends Controller {

    public function index(): void {
        $this->requireRole(ROLES_STAFF);
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
        $this->requireRole(ROLES_STAFF);
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
        $this->requireRole(ROLES_STAFF);
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
        $this->requireRole(ROLES_STAFF);
        $this->db->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
        $this->db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Categorie supprimee.');
        $this->redirect('/categories');
    }

    /**
     * Download an .xlsx template showing the expected columns for category import.
     */
    public function importTemplate(): void {
        $this->requireRole(ROLES_STAFF);
        (new \App\Services\XlsxWriter())->download('modele-categories.xlsx', [
            ['name', 'parent', 'description'],
            ['Boissons', '', 'Catégorie parente'],
            ['Sodas', 'Boissons', 'Sous-catégorie de Boissons'],
        ]);
        exit;
    }

    /**
     * Bulk import categories from an uploaded .xlsx file.
     *
     * Columns are matched by header name. Rows are upserted by `name`. The
     * optional `parent` column references another category by name; parents are
     * resolved after all rows are read so order in the file does not matter.
     */
    public function import(): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/categories'); }

        $rows = $this->readUploadedSheet('/categories');
        if ($rows === null) { return; }

        $col = $this->headerMap($rows[0]);
        if (!isset($col['name']) && !isset($col['nom'])) {
            $this->flash('error', 'Colonne "name" introuvable dans le fichier.');
            $this->redirect('/categories');
        }

        // Existing categories: lowercase name => id.
        $byName = [];
        foreach ($this->db->query("SELECT id, name FROM categories")->fetchAll() as $c) {
            $byName[mb_strtolower(trim($c['name']))] = $c['id'];
        }

        $inserted = 0; $updated = 0; $skipped = 0;
        $pendingParents = []; // childId => parent name (lowercased)

        $this->db->beginTransaction();
        try {
            for ($i = 1, $n = count($rows); $i < $n; $i++) {
                $row  = $rows[$i];
                $name = trim($this->cell($row, $col, ['name', 'nom']));
                if ($name === '') { $skipped++; continue; }
                $description = $this->cell($row, $col, ['description']);
                $parentName  = trim($this->cell($row, $col, ['parent', 'parent_id', 'categorie_parente']));

                $key = mb_strtolower($name);
                if (isset($byName[$key])) {
                    $id = $byName[$key];
                    $this->db->prepare("UPDATE categories SET description=? WHERE id=?")->execute([$description, $id]);
                    $updated++;
                } else {
                    $id = $this->generateUUID();
                    $this->db->prepare("INSERT INTO categories (id, name, description) VALUES (?,?,?)")->execute([$id, $name, $description]);
                    $byName[$key] = $id;
                    $inserted++;
                }
                if ($parentName !== '') {
                    $pendingParents[$id] = mb_strtolower($parentName);
                }
            }

            // Resolve parents now that every category name is known.
            foreach ($pendingParents as $childId => $parentKey) {
                $parentId = $byName[$parentKey] ?? null;
                if ($parentId && $parentId !== $childId) {
                    $this->db->prepare("UPDATE categories SET parent_id=? WHERE id=?")->execute([$parentId, $childId]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->flash('error', 'Import échoué: ' . $e->getMessage());
            $this->redirect('/categories');
        }

        $this->flash('success', "Import terminé : $inserted ajoutée(s), $updated mise(s) à jour, $skipped ignorée(s).");
        $this->redirect('/categories');
    }
}
