<?php
class CaisseController extends Controller {

    public function index(): void {
        $this->requireAuth();

        // Check for open session
        $session = $this->db->prepare("SELECT * FROM cash_sessions WHERE user_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
        $session->execute([$_SESSION['user_id']]);
        $session = $session->fetch();

        $categories = $this->db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        // Pagination POS : ne charge que les 120 produits les plus "utilises" (ventes recentes),
        // puis fallback alphabetique. Le reste est accessible via recherche serveur (/caisse/search).
        $products = $this->db->query("
            SELECT p.id, p.name, p.reference, p.barcode, p.selling_price, p.tax_rate, p.current_stock, p.unit, p.category_id,
                   COALESCE(SUM(CASE WHEN sm.reason = 'sale' AND sm.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) THEN sm.quantity ELSE 0 END), 0) AS recent_sold
            FROM products p
            LEFT JOIN stock_movements sm ON sm.product_id = p.id AND sm.type = 'out'
            WHERE p.is_active = 1 AND p.current_stock > 0
            GROUP BY p.id
            ORDER BY recent_sold DESC, p.name ASC
            LIMIT 120
        ")->fetchAll();
        $totalProducts = (int)$this->db->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND current_stock > 0")->fetchColumn();

        // Company info for the printed receipt header.
        $settings = [];
        foreach ($this->db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $this->render('caisse/index', [
            'pageTitle' => 'Caisse (POS)',
            'session' => $session,
            'categories' => $categories,
            'products' => $products,
            'totalProducts' => $totalProducts,
            'productsShown' => count($products),
            'shop' => [
                'name'    => $settings['company_name'] ?? 'GestionPro',
                'address' => $settings['company_address'] ?? '',
                'phone'   => $settings['company_phone'] ?? '',
                'tax_id'  => $settings['company_tax_id'] ?? '',
            ],
        ]);
    }

    public function open(): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/caisse'); }

        $stmt = $this->db->prepare("INSERT INTO cash_sessions (id, user_id, opening_balance) VALUES (?, ?, ?)");
        $stmt->execute([$this->generateUUID(), $_SESSION['user_id'], $this->input('opening_balance', 0)]);

        $this->flash('success', 'Caisse ouverte.');
        $this->redirect('/caisse');
    }

    public function close(): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/caisse'); }

        $sessionId = $this->input('session_id');
        $closingBalance = $this->input('closing_balance', 0);

        // Calculate expected balance
        $session = $this->db->prepare("SELECT * FROM cash_sessions WHERE id = ?");
        $session->execute([$sessionId]);
        $session = $session->fetch();

        $cashPayments = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN type='incoming' THEN amount ELSE -amount END), 0) FROM payments WHERE cash_session_id = ? AND method = 'cash'");
        $cashPayments->execute([$sessionId]);
        $cashTotal = $cashPayments->fetchColumn();

        $expectedBalance = $session['opening_balance'] + $cashTotal;
        $difference = $closingBalance - $expectedBalance;

        $stmt = $this->db->prepare("UPDATE cash_sessions SET closed_at = NOW(), closing_balance = ?, expected_balance = ?, difference = ?, status = 'closed', notes = ? WHERE id = ?");
        $stmt->execute([$closingBalance, $expectedBalance, $difference, $this->input('notes'), $sessionId]);

        $this->flash('success', 'Caisse cloturee. Ecart: ' . formatMoney($difference));
        $this->redirect('/caisse');
    }

    public function sell(): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->json(['error' => 'Token invalide'], 403); }

        $items = json_decode($this->input('items', '[]'), true);
        $customerId = $this->input('customer_id') ?: null;
        $paymentMethod = $this->input('payment_method', 'cash');
        $amountPaid = (float)$this->input('amount_paid', 0);
        $sessionId = $this->input('session_id');
        $isCredit = $this->input('is_credit') === '1';
        // Offline POS: client-generated idempotency key + "sale already happened" flag.
        $clientUid = trim((string)$this->input('client_uid', '')) ?: null;
        $offline = $this->input('offline') === '1';

        if (empty($items)) {
            $this->json(['error' => 'Panier vide'], 400);
        }

        if ($isCredit && !$customerId) {
            $this->json(['error' => 'Un client est requis pour une vente a credit'], 400);
        }

        // Idempotency: if this sale was already synced, return the existing invoice
        // instead of creating a duplicate (network retries, double flush, etc.).
        if ($clientUid !== null) {
            $existing = $this->db->prepare("SELECT number, total, amount_paid FROM invoices WHERE client_uid = ?");
            $existing->execute([$clientUid]);
            if ($row = $existing->fetch()) {
                $this->json([
                    'success' => true,
                    'duplicate' => true,
                    'invoice_number' => $row['number'],
                    'total' => (float)$row['total'],
                    'change' => max(0, $amountPaid - (float)$row['total']),
                ]);
            }
        }

        $this->db->beginTransaction();
        try {
            $svc = new \App\Services\SellService($this->db);
            $priced = $svc->priceAndLock($items, $offline);
            $subtotal     = $priced['subtotal'];
            $taxAmount    = $priced['tax'];
            $total        = $priced['total'];
            $invoiceItems = $priced['lines'];

            $invoiceId = $this->generateUUID();
            $invoiceNumber = $this->generateNumber(INVOICE_PREFIX, 'invoices');

            $status = $isCredit ? 'partial' : 'paid';
            $paid = $isCredit ? $amountPaid : $total;

            // Create invoice
            $stmt = $this->db->prepare("INSERT INTO invoices (id, number, type, status, customer_id, user_id, issue_date, due_date, subtotal, tax_amount, total, amount_paid, client_uid) VALUES (?,?,?,?,?,?,CURDATE(),?,?,?,?,?,?)");
            $dueDate = $isCredit ? date('Y-m-d', strtotime('+30 days')) : date('Y-m-d');
            $stmt->execute([$invoiceId, $invoiceNumber, 'invoice', $status, $customerId, $_SESSION['user_id'], $dueDate, $subtotal, $taxAmount, $total, $paid, $clientUid]);

            // Create invoice items & update stock
            foreach ($invoiceItems as $item) {
                $this->db->prepare("INSERT INTO invoice_items (id, invoice_id, product_id, description, quantity, unit_price, tax_rate, line_total) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$this->generateUUID(), $invoiceId, $item['product_id'], $item['description'], $item['quantity'], $item['unit_price'], $item['tax_rate'], $item['line_total']]);

                // Atomic stock decrement with guard (defense in depth vs the FOR UPDATE above).
                // Offline sales force the decrement (stock may go negative — reconciled later).
                $svc->decrementStock($item['product_id'], (int)$item['quantity'], $offline);

                // Stock movement
                $this->db->prepare("INSERT INTO stock_movements (id, product_id, type, reason, quantity, unit_cost, reference_type, reference_id, user_id) VALUES (?,?,'out','sale',?,?,'invoice',?,?)")
                    ->execute([$this->generateUUID(), $item['product_id'], $item['quantity'], $item['unit_price'], $invoiceId, $_SESSION['user_id']]);
            }

            // Record payment
            if ($paid > 0) {
                $paySvc = new \App\Services\PaymentService($this->db);
                $paySvc->recordSalePayment($invoiceId, $customerId, $sessionId, (float)$paid, $paymentMethod, $_SESSION['user_id']);
            }

            // Create debt if credit sale
            if ($isCredit && $total > $paid) {
                $this->db->prepare("INSERT INTO debts (id, type, customer_id, invoice_id, total_amount, paid_amount, due_date, status) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$this->generateUUID(), 'receivable', $customerId, $invoiceId, $total, $paid, $dueDate, $paid > 0 ? 'partial' : 'pending']);

                // Update customer balance
                if ($customerId) {
                    $remaining = $total - $paid;
                    $this->db->prepare("UPDATE customers SET balance = balance - ? WHERE id = ?")->execute([$remaining, $customerId]);
                }
            }

            $this->db->commit();
            $this->json(['success' => true, 'invoice_number' => $invoiceNumber, 'total' => $total, 'change' => max(0, $amountPaid - $total)]);

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function history(): void {
        $this->requireAuth();
        $sessions = $this->db->query("SELECT cs.*, u.full_name FROM cash_sessions cs JOIN users u ON cs.user_id = u.id ORDER BY cs.opened_at DESC LIMIT 30")->fetchAll();
        $this->render('caisse/history', ['pageTitle' => 'Historique Caisse', 'sessions' => $sessions]);
    }

    public function searchProducts(): void {
        $this->requireAuth();
        $q = $this->input('q', '');
        $stmt = $this->db->prepare("SELECT id, name, reference, barcode, selling_price, tax_rate, current_stock, unit FROM products WHERE is_active = 1 AND current_stock > 0 AND (name LIKE ? OR reference LIKE ? OR barcode = ?) ORDER BY name LIMIT 20");
        $stmt->execute(["%$q%", "%$q%", $q]);
        $this->json($stmt->fetchAll());
    }
}
