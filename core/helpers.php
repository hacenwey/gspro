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

/**
 * Brand mark for the sidebar / login / landing.
 *
 * Renders public/img/logo-mark.png when it is there, and falls back to the "GP"
 * monogram otherwise — so dropping the file in is all it takes to switch, and a
 * missing file never leaves a broken image in the header.
 */
function brandMark(string $class = 'logo'): string {
    $file = APP_ROOT . '/public/img/logo-mark.png';
    if (is_file($file)) {
        // Cache-bust on mtime: the logo is long-cached but must update on replace.
        $src = asset('img/logo-mark.png') . '?v=' . filemtime($file);
        return '<div class="' . e($class) . ' has-mark"><img src="' . e($src) . '" alt="GestionPro"></div>';
    }
    return '<div class="' . e($class) . '">GP</div>';
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

/**
 * Landing route for the current role. Cashiers are POS-only, so sending them to
 * the dashboard would bounce them straight into an access denial after login.
 */
function roleHome(): string {
    if (hasRole(ROLE_KITCHEN)) return '/kitchen';
    if (hasRole(ROLE_CASHIER)) return isRestaurant() ? '/orders' : '/caisse';
    return '/dashboard';
}

/** Tenant's business vertical. Unknown/empty values fall back to retail. */
function businessType(): string {
    $t = class_exists('Settings') ? Settings::get('business_type', BUSINESS_TYPE_DEFAULT) : BUSINESS_TYPE_DEFAULT;
    return in_array($t, BUSINESS_TYPES, true) ? $t : BUSINESS_TYPE_DEFAULT;
}

/** Gate for the restaurant module (orders, tables, kitchen screen). */
function isRestaurant(): bool {
    return businessType() === BUSINESS_RESTAURANT;
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
