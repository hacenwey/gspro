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
        if (in_array($_SESSION['user_role'], $roles, true)) {
            return;
        }
        // Access is denied either way; bounce to the role's own home rather than
        // leaving a dead-end page (a cashier opening a bookmarked /dashboard).
        $home = roleHome();
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if ($current === url($home)) {
            http_response_code(403); // would redirect to itself — deny outright
            die('Accès non autorisé');
        }
        $this->flash('error', __('common.forbidden', 'Acces non autorise.'));
        $this->redirect($home);
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

    // ===================== XLSX IMPORT HELPERS =====================

    /**
     * Parse the uploaded `file` field as an .xlsx sheet.
     *
     * On any problem it sets a flash message, redirects to $redirectPath and
     * (because redirect() exits) never returns.
     *
     * @return array<int, array<int, string>>|null Rows, or null after redirecting.
     */
    protected function readUploadedSheet(string $redirectPath): ?array {
        $file = $_FILES['file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Aucun fichier valide reçu.');
            $this->redirect($redirectPath);
        }
        if (!preg_match('/\.xlsx$/i', (string)$file['name'])) {
            $this->flash('error', 'Format non supporté. Utilisez un fichier .xlsx.');
            $this->redirect($redirectPath);
        }
        try {
            $rows = (new \App\Services\XlsxReader())->read($file['tmp_name']);
        } catch (\Throwable $e) {
            $this->flash('error', 'Lecture du fichier échouée: ' . $e->getMessage());
            $this->redirect($redirectPath);
            return null;
        }
        if (count($rows) < 2) {
            $this->flash('error', 'Le fichier ne contient aucune ligne de données.');
            $this->redirect($redirectPath);
            return null;
        }
        return $rows;
    }

    /**
     * Build a lowercased header-name => column-index map from the header row.
     * @return array<string, int>
     */
    protected function headerMap(array $headerRow): array {
        $map = [];
        foreach ($headerRow as $i => $label) {
            $key = strtolower(trim((string)$label));
            if ($key !== '' && !isset($map[$key])) { $map[$key] = $i; }
        }
        return $map;
    }

    /** Read a cell from $row by trying each candidate header name against the map. */
    protected function cell(array $row, array $col, array $names): string {
        foreach ($names as $name) {
            if (isset($col[$name]) && isset($row[$col[$name]])) {
                return (string)$row[$col[$name]];
            }
        }
        return '';
    }

    /** Parse a possibly locale-formatted number (comma decimals, thin/regular spaces). */
    protected function num(string $value, float $default = 0): float {
        $value = trim($value);
        if ($value === '') { return (float)$default; }
        $value = str_replace([' ', "\xC2\xA0"], '', $value);
        $value = str_replace(',', '.', $value);
        return is_numeric($value) ? (float)$value : (float)$default;
    }
}
