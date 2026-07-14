<?php
class ProductController extends Controller {

    public function index(): void {
        $this->requireRole(ROLES_STAFF);
        $search = $this->input('search', '');
        $category = $this->input('category', '');
        $stockFilter = $this->input('stock', '');
        $page = max(1, (int)$this->input('page', 1));

        $where = "WHERE p.is_active = 1";
        $params = [];

        if ($search) {
            $where .= " AND (p.name LIKE ? OR p.reference LIKE ? OR p.barcode LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        }
        if ($category) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }
        if ($stockFilter === 'low') {
            $where .= " AND p.current_stock <= p.min_stock AND p.current_stock > 0";
        } elseif ($stockFilter === 'out') {
            $where .= " AND p.current_stock <= 0";
        }

        $query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.name ASC";
        $result = $this->paginate($query, $params, $page);

        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

        $this->render('products/index', array_merge($result, [
            'pageTitle' => 'Produits & Stock',
            'search' => $search,
            'categoryFilter' => $category,
            'stockFilter' => $stockFilter,
            'categories' => $categories
        ]));
    }

    public function create(): void {
        $this->requireRole(ROLES_STAFF);
        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        $this->render('products/form', [
            'pageTitle' => 'Nouveau produit',
            'product' => null,
            'categories' => $categories
        ]);
    }

    public function store(): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/products'); }

        $id = $this->generateUUID();
        $stmt = $this->db->prepare("INSERT INTO products (id, reference, barcode, name, description, category_id, unit, purchase_price, selling_price, tax_rate, min_stock, current_stock) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $id,
            trim($this->input('reference')),
            $this->input('barcode') ?: null,
            trim($this->input('name')),
            $this->input('description'),
            $this->input('category_id') ?: null,
            $this->input('unit', 'piece'),
            $this->input('purchase_price', 0),
            $this->input('selling_price', 0),
            $this->input('tax_rate', TAX_RATE_DEFAULT),
            $this->input('min_stock', 0),
            $this->input('current_stock', 0),
        ]);

        $this->flash('success', 'Produit cree avec succes.');
        $this->redirect('/products');
    }

    public function edit(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $product = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $product->execute([$id]);
        $product = $product->fetch();
        if (!$product) { $this->redirect('/products'); }

        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        $this->render('products/form', [
            'pageTitle' => 'Modifier produit',
            'product' => $product,
            'categories' => $categories
        ]);
    }

    public function update(string $id): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/products'); }

        $stmt = $this->db->prepare("UPDATE products SET reference=?, barcode=?, name=?, description=?, category_id=?, unit=?, purchase_price=?, selling_price=?, tax_rate=?, min_stock=? WHERE id=?");
        $stmt->execute([
            trim($this->input('reference')),
            $this->input('barcode') ?: null,
            trim($this->input('name')),
            $this->input('description'),
            $this->input('category_id') ?: null,
            $this->input('unit', 'piece'),
            $this->input('purchase_price', 0),
            $this->input('selling_price', 0),
            $this->input('tax_rate', TAX_RATE_DEFAULT),
            $this->input('min_stock', 0),
            $id
        ]);

        $this->flash('success', 'Produit mis a jour.');
        $this->redirect('/products');
    }

    public function delete(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $this->flash('success', 'Produit supprime.');
        $this->redirect('/products');
    }

    public function view(string $id): void {
        $this->requireRole(ROLES_STAFF);
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) { $this->redirect('/products'); }

        $movements = $this->db->prepare("SELECT sm.*, u.full_name as user_name FROM stock_movements sm LEFT JOIN users u ON sm.user_id = u.id WHERE sm.product_id = ? ORDER BY sm.created_at DESC LIMIT 20");
        $movements->execute([$id]);

        $this->render('products/view', [
            'pageTitle' => $product['name'],
            'product' => $product,
            'movements' => $movements->fetchAll()
        ]);
    }

    public function adjustStock(string $id): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/products/view/' . $id); }

        $type = $this->input('type');
        $quantity = abs((int)$this->input('quantity'));
        $reason = $this->input('reason', 'inventory');
        $notes = $this->input('notes', '');

        if ($quantity <= 0) {
            $this->flash('error', 'Quantite invalide.');
            $this->redirect('/products/view/' . $id);
        }

        $this->db->beginTransaction();
        try {
            // Create movement
            $stmt = $this->db->prepare("INSERT INTO stock_movements (id, product_id, type, reason, quantity, notes, user_id) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$this->generateUUID(), $id, $type, $reason, $quantity, $notes, $_SESSION['user_id']]);

            // Update stock
            $op = $type === 'in' ? '+' : '-';
            $this->db->prepare("UPDATE products SET current_stock = current_stock $op ? WHERE id = ?")->execute([$quantity, $id]);

            $this->db->commit();
            $this->flash('success', 'Stock ajuste.');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->flash('error', 'Erreur: ' . $e->getMessage());
        }

        $this->redirect('/products/view/' . $id);
    }

    /**
     * Download an .xlsx template showing the expected columns for product import.
     */
    public function importTemplate(): void {
        $this->requireRole(ROLES_STAFF);
        (new \App\Services\XlsxWriter())->download('modele-produits.xlsx', [
            ['reference', 'name', 'category', 'unit', 'purchase_price', 'selling_price', 'tax_rate', 'min_stock', 'current_stock', 'barcode', 'description'],
            ['P001', 'Exemple produit', 'Boissons', 'piece', '100', '150', '0', '5', '20', '6001234567890', 'Description optionnelle'],
        ]);
        exit;
    }

    /**
     * Bulk import products from an uploaded .xlsx file.
     *
     * Columns are matched by header name (first row), so column order is free.
     * Rows are upserted by `reference`: an existing reference is updated,
     * otherwise a new product is created. Categories are matched by name and
     * created on the fly when missing.
     */
    public function import(): void {
        $this->requireRole(ROLES_STAFF);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/products'); }

        $rows = $this->readUploadedSheet('/products');
        if ($rows === null) { return; }

        $col = $this->headerMap($rows[0]);
        if (!isset($col['reference']) && !isset($col['ref'])) {
            $this->flash('error', 'Colonne "reference" introuvable dans le fichier.');
            $this->redirect('/products');
        }

        $categoryCache = $this->loadCategoryCache();
        $inserted = 0; $updated = 0; $skipped = 0;

        $this->db->beginTransaction();
        try {
            for ($i = 1, $n = count($rows); $i < $n; $i++) {
                $row = $rows[$i];
                $reference = trim($this->cell($row, $col, ['reference', 'ref']));
                $name      = trim($this->cell($row, $col, ['name', 'nom', 'designation', 'désignation']));
                if ($reference === '' || $name === '') { $skipped++; continue; }

                $categoryId = $this->resolveCategory(
                    trim($this->cell($row, $col, ['category', 'categorie', 'catégorie'])),
                    $categoryCache
                );

                $barcode      = trim($this->cell($row, $col, ['barcode', 'code_barre', 'code-barres'])) ?: null;
                $description  = $this->cell($row, $col, ['description']);
                $unit         = trim($this->cell($row, $col, ['unit', 'unite', 'unité'])) ?: 'piece';
                $purchase     = $this->num($this->cell($row, $col, ['purchase_price', 'prix_achat', 'prix achat']));
                $selling      = $this->num($this->cell($row, $col, ['selling_price', 'prix_vente', 'prix vente']));
                $taxRate      = $this->num($this->cell($row, $col, ['tax_rate', 'tva', 'taxe']), TAX_RATE_DEFAULT);
                $minStock     = (int)$this->num($this->cell($row, $col, ['min_stock', 'stock_min', 'stock min']));
                $stockRaw     = $this->cell($row, $col, ['current_stock', 'stock', 'quantite', 'quantité']);

                $existing = $this->db->prepare("SELECT id FROM products WHERE reference = ?");
                $existing->execute([$reference]);
                $existingId = $existing->fetchColumn();

                if ($existingId) {
                    // Update descriptive fields; overwrite stock only when the column is filled.
                    $sql = "UPDATE products SET barcode=?, name=?, description=?, category_id=?, unit=?, purchase_price=?, selling_price=?, tax_rate=?, min_stock=?, is_active=1";
                    $params = [$barcode, $name, $description, $categoryId, $unit, $purchase, $selling, $taxRate, $minStock];
                    if (trim($stockRaw) !== '') {
                        $sql .= ", current_stock=?";
                        $params[] = (int)$this->num($stockRaw);
                    }
                    $sql .= " WHERE id=?";
                    $params[] = $existingId;
                    $this->db->prepare($sql)->execute($params);
                    $updated++;
                } else {
                    $this->db->prepare("INSERT INTO products (id, reference, barcode, name, description, category_id, unit, purchase_price, selling_price, tax_rate, min_stock, current_stock) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                        ->execute([
                            $this->generateUUID(), $reference, $barcode, $name, $description, $categoryId,
                            $unit, $purchase, $selling, $taxRate, $minStock, (int)$this->num($stockRaw),
                        ]);
                    $inserted++;
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->flash('error', 'Import échoué: ' . $e->getMessage());
            $this->redirect('/products');
        }

        $this->flash('success', "Import terminé : $inserted ajouté(s), $updated mis à jour, $skipped ignoré(s).");
        $this->redirect('/products');
    }

    /** @return array<string, string> lowercase name => category id */
    private function loadCategoryCache(): array {
        $cache = [];
        foreach ($this->db->query("SELECT id, name FROM categories")->fetchAll() as $c) {
            $cache[mb_strtolower(trim($c['name']))] = $c['id'];
        }
        return $cache;
    }

    /** Resolve a category by name, creating it when absent. Returns null for blank names. */
    private function resolveCategory(string $name, array &$cache): ?string {
        if ($name === '') { return null; }
        $key = mb_strtolower($name);
        if (isset($cache[$key])) { return $cache[$key]; }
        $id = $this->generateUUID();
        $this->db->prepare("INSERT INTO categories (id, name) VALUES (?, ?)")->execute([$id, $name]);
        $cache[$key] = $id;
        return $id;
    }

    public function apiList(): void {
        $this->requireRole(ROLES_STAFF);
        $search = $this->input('q', '');
        $stmt = $this->db->prepare("SELECT id, name, reference, barcode, selling_price, tax_rate, current_stock, unit FROM products WHERE is_active = 1 AND (name LIKE ? OR reference LIKE ? OR barcode LIKE ?) ORDER BY name LIMIT 50");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        $this->json($stmt->fetchAll());
    }
}
