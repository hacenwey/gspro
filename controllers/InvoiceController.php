<?php
class InvoiceController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $type = $this->input('type', '');
        $status = $this->input('status', '');
        $search = $this->input('search', '');
        $page = max(1, (int)$this->input('page', 1));

        $where = "WHERE 1=1"; $params = [];
        if ($type) { $where .= " AND i.type = ?"; $params[] = $type; }
        if ($status) { $where .= " AND i.status = ?"; $params[] = $status; }
        if ($search) { $where .= " AND (i.number LIKE ? OR c.last_name LIKE ? OR c.first_name LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }

        $query = "SELECT i.*, c.first_name, c.last_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id $where ORDER BY i.created_at DESC";
        $result = $this->paginate($query, $params, $page);

        $this->render('invoices/index', array_merge($result, [
            'pageTitle' => 'Devis & Factures',
            'typeFilter' => $type,
            'statusFilter' => $status,
            'search' => $search
        ]));
    }

    public function create(): void {
        $this->requireAuth();
        $customers = $this->db->query("SELECT id, first_name, last_name FROM customers ORDER BY last_name")->fetchAll();
        $products = $this->db->query("SELECT id, name, reference, selling_price, tax_rate, current_stock FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
        $customerId = $this->input('customer_id', '');
        $type = $this->input('type', 'invoice');
        $this->render('invoices/form', ['pageTitle' => 'Nouveau document', 'invoice' => null, 'customers' => $customers, 'products' => $products, 'selectedCustomer' => $customerId, 'selectedType' => $type]);
    }

    public function store(): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/invoices'); }

        $type = $this->input('type', 'invoice');
        $prefix = match($type) { 'quote' => QUOTE_PREFIX, 'credit_note' => CREDIT_NOTE_PREFIX, default => INVOICE_PREFIX };

        $this->db->beginTransaction();
        try {
            $svc = new \App\Services\InvoiceService($this->db);
            $rawItems = json_decode($this->input('items_json', '[]'), true) ?: [];
            $discountAmount = (float)$this->input('discount_amount', 0);
            $totals = $svc->totalize($rawItems, $discountAmount);

            $id = $svc->createDraft([
                'id'            => $this->generateUUID(),
                'number'        => $svc->nextNumber($prefix),
                'type'          => $type,
                'status'        => 'draft',
                'customer_id'   => $this->input('customer_id'),
                'user_id'       => $_SESSION['user_id'],
                'issue_date'    => $this->input('issue_date', date('Y-m-d')),
                'due_date'      => $this->input('due_date') ?: null,
                'validity_date' => $this->input('validity_date') ?: null,
                'notes'         => $this->input('notes'),
            ], $totals['lines'], $totals);

            $this->db->commit();
            $this->flash('success', $type === 'quote' ? 'Devis cree.' : 'Facture creee.');
            $this->redirect('/invoices/view/' . $id);
        } catch (Exception $e) {
            $this->db->rollBack();
            \App\Logging\Logger::exception($e, ['action' => 'invoice.store', 'type' => $type]);
            $this->flash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('/invoices/create');
        }
    }

    public function view(string $id): void {
        $this->requireAuth();
        $stmt = $this->db->prepare("SELECT i.*, c.first_name, c.last_name, c.phone, c.email, c.address, c.tax_id as client_tax_id, u.full_name as user_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN users u ON i.user_id = u.id WHERE i.id = ?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if (!$invoice) $this->redirect('/invoices');

        $items = $this->db->prepare("SELECT ii.*, p.reference as product_ref FROM invoice_items ii LEFT JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = ?");
        $items->execute([$id]);

        $payments = $this->db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date");
        $payments->execute([$id]);

        $this->render('invoices/view', [
            'pageTitle' => $invoice['number'],
            'invoice' => $invoice,
            'items' => $items->fetchAll(),
            'payments' => $payments->fetchAll()
        ]);
    }

    public function convertToInvoice(string $id): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/invoices'); }

        $quote = $this->db->prepare("SELECT * FROM invoices WHERE id = ? AND type = 'quote'");
        $quote->execute([$id]); $quote = $quote->fetch();
        if (!$quote) { $this->redirect('/invoices'); }

        $this->db->beginTransaction();
        try {
            $newId = $this->generateUUID();
            $number = $this->generateNumber(INVOICE_PREFIX, 'invoices');

            $this->db->prepare("INSERT INTO invoices (id, number, type, status, customer_id, user_id, issue_date, due_date, subtotal, tax_amount, discount_amount, total, notes, quote_id) VALUES (?,?,'invoice','draft',?,?,CURDATE(),?,?,?,?,?,?,?)")
                ->execute([$newId, $number, $quote['customer_id'], $_SESSION['user_id'], date('Y-m-d', strtotime('+30 days')), $quote['subtotal'], $quote['tax_amount'], $quote['discount_amount'], $quote['total'], $quote['notes'], $id]);

            // Copy items
            $items = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?"); $items->execute([$id]);
            foreach ($items as $item) {
                $this->db->prepare("INSERT INTO invoice_items (id, invoice_id, product_id, description, quantity, unit_price, tax_rate, discount_pct, line_total) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$this->generateUUID(), $newId, $item['product_id'], $item['description'], $item['quantity'], $item['unit_price'], $item['tax_rate'], $item['discount_pct'], $item['line_total']]);
            }

            // Update quote status
            $this->db->prepare("UPDATE invoices SET status = 'accepted' WHERE id = ?")->execute([$id]);

            $this->db->commit();
            $this->flash('success', 'Devis converti en facture ' . $number);
            $this->redirect('/invoices/view/' . $newId);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->flash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('/invoices/view/' . $id);
        }
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Document supprime.');
        $this->redirect('/invoices');
    }

    public function email(string $id): void {
        $this->requireAuth();
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/invoices/view/' . $id); }

        $stmt = $this->db->prepare("SELECT i.*, c.first_name, c.last_name, c.email FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?");
        $stmt->execute([$id]); $invoice = $stmt->fetch();
        if (!$invoice) { $this->redirect('/invoices'); }

        $to = trim((string)($this->input('to') ?: $invoice['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email destinataire invalide.');
            $this->redirect('/invoices/view/' . $id);
        }

        $items = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $items->execute([$id]); $items = $items->fetchAll();

        $settings = [];
        foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) { $settings[$s['setting_key']] = $s['setting_value']; }

        try {
            $pdfSvc = new \App\Services\InvoicePdfService();
            $pdfBinary = $pdfSvc->toBinary($invoice, $items, $settings);

            $docType = match($invoice['type']) { 'quote' => 'devis', 'credit_note' => 'avoir', default => 'facture' };
            $companyName = $settings['company_name'] ?? 'GestionPro';
            $subject = ucfirst($docType) . ' ' . $invoice['number'] . ' - ' . $companyName;
            $note = trim((string)$this->input('message', ''));

            $html = '<p>Bonjour ' . e(trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? ''))) . ',</p>'
                  . '<p>Veuillez trouver ci-joint votre ' . $docType . ' <strong>' . e($invoice['number']) . '</strong> d\'un montant de <strong>' . e(formatMoney($invoice['total'])) . '</strong>.</p>'
                  . ($note !== '' ? '<p>' . nl2br(e($note)) . '</p>' : '')
                  . '<p>Cordialement,<br>' . e($companyName) . '</p>';

            $mailer = new \App\Services\Mailer();
            $mailer->send(
                $to, $subject, $html, '',
                [['content' => $pdfBinary, 'filename' => $pdfSvc->filename($invoice), 'mime' => 'application/pdf']],
                trim(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')) ?: null
            );

            // Mark as sent if it was draft
            if (($invoice['status'] ?? '') === 'draft') {
                $next = $invoice['type'] === 'invoice' ? 'sent' : 'sent';
                $this->db->prepare("UPDATE invoices SET status = ? WHERE id = ?")->execute([$next, $id]);
            }

            $this->flash('success', 'Document envoye a ' . $to);
        } catch (\Throwable $e) {
            \App\Logging\Logger::exception($e, ['op' => 'invoice_email', 'invoice_id' => $id]);
            $this->flash('error', 'Envoi impossible: ' . $e->getMessage());
        }
        $this->redirect('/invoices/view/' . $id);
    }

    public function pdf(string $id): void {
        $this->requireAuth();
        $stmt = $this->db->prepare("SELECT i.*, c.first_name, c.last_name, c.phone, c.email, c.address, c.tax_id as client_tax_id FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id WHERE i.id = ?");
        $stmt->execute([$id]); $invoice = $stmt->fetch();
        if (!$invoice) { $this->redirect('/invoices'); }
        $items = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?"); $items->execute([$id]);
        $items = $items->fetchAll();

        $settings = [];
        foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) { $settings[$s['setting_key']] = $s['setting_value']; }

        // ?format=html  -> preview in browser (legacy behavior)
        // ?download=1   -> attachment
        // default       -> inline PDF (opens in browser reader)
        $format = $this->input('format', 'pdf');
        if ($format === 'html') {
            header('Content-Type: text/html; charset=utf-8');
            require APP_ROOT . '/views/invoices/pdf.php';
            exit;
        }

        $attachment = $this->input('download') === '1';
        $svc = new \App\Services\InvoicePdfService();
        $svc->stream($invoice, $items, $settings, $attachment);
        exit;
    }
}
