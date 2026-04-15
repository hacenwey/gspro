<?php
class Controller {
    protected PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    protected function render(string $view, array $data = []): void {
        extract($data);
        $content = APP_ROOT . '/views/' . $view . '.php';
        if (!file_exists($content)) {
            die("View not found: $view");
        }
        require APP_ROOT . '/views/layout/main.php';
    }

    protected function viewPartial(string $view, array $data = []): void {
        extract($data);
        require APP_ROOT . '/views/' . $view . '.php';
    }

    protected function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void {
        header('Location: ' . url($path));
        exit;
    }

    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        // In multi-tenant mode, verify the session belongs to this tenant
        if (class_exists('Tenant') && Tenant::slug()) {
            if (($_SESSION['tenant_slug'] ?? '') !== Tenant::slug()) {
                $this->redirect('/login');
            }
        }
    }

    protected function requireRole(array $roles): void {
        $this->requireAuth();
        if (!in_array($_SESSION['user_role'], $roles)) {
            http_response_code(403);
            die('Accès non autorisé');
        }
    }

    protected function input(string $key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function generateUUID(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    protected function generateNumber(string $prefix, string $table, string $column = 'number'): string {
        $year = date('Y');
        $pattern = $prefix . '-' . $year . '-%';
        $stmt = $this->db->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY $column DESC LIMIT 1");
        $stmt->execute([$pattern]);
        $last = $stmt->fetchColumn();
        if ($last) {
            $parts = explode('-', $last);
            $counter = intval(end($parts)) + 1;
        } else {
            $counter = 1;
        }
        return $prefix . '-' . $year . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    protected function paginate(string $query, array $params, int $page, int $perPage = ITEMS_PER_PAGE): array {
        $countQuery = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) FROM', $query);
        $countQuery = preg_replace('/ORDER BY .+$/i', '', $countQuery);
        $stmt = $this->db->prepare($countQuery);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        $totalPages = max(1, ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare($query . " LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage
        ];
    }

    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}
