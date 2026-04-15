<?php
class ProductController extends Controller {

    public function index(): void {
        $this->requireAuth();
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
        $this->requireAuth();
        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        $this->render('products/form', [
            'pageTitle' => 'Nouveau produit',
            'product' => null,
            'categories' => $categories
        ]);
    }

    public function store(): void {
        $this->requireAuth();
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
        $this->requireAuth();
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
        $this->requireAuth();
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
        $this->requireAuth();
        $stmt = $this->db->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $this->flash('success', 'Produit supprime.');
        $this->redirect('/products');
    }

    public function view(string $id): void {
        $this->requireAuth();
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
        $this->requireAuth();
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

    public function apiList(): void {
        $this->requireAuth();
        $search = $this->input('q', '');
        $stmt = $this->db->prepare("SELECT id, name, reference, barcode, selling_price, tax_rate, current_stock, unit FROM products WHERE is_active = 1 AND (name LIKE ? OR reference LIKE ? OR barcode LIKE ?) ORDER BY name LIMIT 50");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        $this->json($stmt->fetchAll());
    }
}
