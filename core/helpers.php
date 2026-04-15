<?php

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string {
    // Use Router base if set (includes tenant slug), otherwise APP_BASE
    if (class_exists('Router') && Router::getBase()) {
        return Router::getBase() . $path;
    }
    return APP_BASE . $path;
}

function asset(string $path): string {
    return APP_BASE . '/public/' . $path;
}

function tenantUrl(string $slug, string $path = ''): string {
    return APP_BASE . '/' . $slug . $path;
}

function adminUrl(string $path = ''): string {
    return APP_BASE . '/admin' . $path;
}

function formatMoney($amount): string {
    return number_format((float)$amount, 2, ',', ' ') . ' ' . CURRENCY;
}

function formatDate($date): string {
    if (!$date) return '-';
    return date(DATE_FORMAT, strtotime($date));
}

function formatDateTime($datetime): string {
    if (!$datetime) return '-';
    return date(DATETIME_FORMAT, strtotime($datetime));
}

function isActive(string $path): string {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = url($path);
    return str_starts_with($current, $base) ? 'active' : '';
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_name'],
        'full_name' => $_SESSION['user_full_name'],
        'role' => $_SESSION['user_role'],
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

function hasRole(string ...$roles): bool {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function stockStatusClass(int $current, int $min): string {
    if ($current <= 0) return 'danger';
    if ($current <= $min) return 'warning';
    return 'success';
}

function stockStatusLabel(int $current, int $min): string {
    if ($current <= 0) return 'Rupture';
    if ($current <= $min) return 'Stock bas';
    return 'OK';
}

function debtStatusClass(string $status): string {
    return match($status) {
        'paid' => 'success',
        'partial' => 'warning',
        'overdue' => 'danger',
        default => 'info'
    };
}

function invoiceStatusClass(string $status): string {
    return match($status) {
        'paid' => 'success',
        'partial' => 'warning',
        'overdue', 'cancelled' => 'danger',
        'sent', 'accepted' => 'info',
        'draft' => 'secondary',
        default => 'secondary'
    };
}

function invoiceStatusLabel(string $status): string {
    return match($status) {
        'draft' => 'Brouillon',
        'sent' => 'Envoyé',
        'accepted' => 'Accepté',
        'refused' => 'Refusé',
        'expired' => 'Expiré',
        'partial' => 'Partiel',
        'paid' => 'Payé',
        'overdue' => 'En retard',
        'cancelled' => 'Annulé',
        default => $status
    };
}
