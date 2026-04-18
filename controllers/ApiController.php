<?php

/**
 * REST API v1 — tenant-scoped.
 *
 * Auth: Authorization: Bearer <64-hex-token>.
 * All responses JSON. Errors use { error: { code, message } }.
 */
class ApiController extends Controller
{
    private ?array $apiUser = null;

    private function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? getallheaders()['Authorization'] ?? null;
        $auth = new \App\Api\TokenAuth($this->db);
        $user = $auth->authenticate($header);
        if (!$user) {
            $this->apiError(401, 'unauthorized', 'Token manquant ou invalide.');
        }
        $this->apiUser = $user;
        return $user;
    }

    private function apiError(int $code, string $slug, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => ['code' => $slug, 'message' => $message]]);
        exit;
    }

    private function apiOk(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        header('X-API-Version: v1');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function health(): void
    {
        $this->apiOk(['status' => 'ok', 'version' => 'v1', 'tenant' => Tenant::slug()]);
    }

    public function listProducts(): void
    {
        $this->authenticate();
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = "is_active = 1";
        $params = [];
        if ($q !== '') {
            $where .= " AND (name LIKE ? OR reference LIKE ? OR barcode = ?)";
            $params = ["%$q%", "%$q%", $q];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT id, name, reference, barcode, selling_price, tax_rate, current_stock, unit, category_id
             FROM products WHERE $where ORDER BY name LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        $this->apiOk([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage,
                       'total_pages' => (int)ceil($total / $perPage)],
        ]);
    }

    public function listInvoices(): void
    {
        $this->authenticate();
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];
        if ($type)   { $where .= " AND type = ?";   $params[] = $type; }
        if ($status) { $where .= " AND status = ?"; $params[] = $status; }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM invoices WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT id, number, type, status, customer_id, issue_date, due_date,
                    subtotal, tax_amount, discount_amount, total, amount_paid
             FROM invoices WHERE $where ORDER BY issue_date DESC LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        $this->apiOk([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage,
                       'total_pages' => (int)ceil($total / $perPage)],
        ]);
    }

    public function getInvoice(string $id): void
    {
        $this->authenticate();
        $stmt = $this->db->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) $this->apiError(404, 'not_found', 'Facture introuvable.');

        $items = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $items->execute([$id]);
        $invoice['items'] = $items->fetchAll(PDO::FETCH_ASSOC);

        $this->apiOk(['data' => $invoice]);
    }

    public function listCustomers(): void
    {
        $this->authenticate();
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];
        if ($q !== '') {
            $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $params = ["%$q%", "%$q%", "%$q%", "%$q%"];
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM customers WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, phone, email, balance
             FROM customers WHERE $where ORDER BY last_name, first_name LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        $this->apiOk([
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage,
                       'total_pages' => (int)ceil($total / $perPage)],
        ]);
    }
}
