<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gestion_commerciale'); // fallback default
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get the current database connection.
 * In multi-tenant mode, returns the tenant-scoped DB.
 * Falls back to the default DB for legacy/direct access.
 */
function getDB(): PDO {
    // If a tenant is loaded, use tenant DB
    if (class_exists('Tenant') && Tenant::current()) {
        return Tenant::getDB();
    }

    // Fallback to default DB (for install scripts, etc.)
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
