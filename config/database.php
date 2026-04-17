<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'gestion_commerciale'); // fallback default
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');
define('DB_SSL', filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN));

/**
 * PDO options, shared across every connection (main, master, tenant).
 */
function db_pdo_options(): array {
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (DB_SSL) {
        // TiDB Cloud / Aiven etc. require TLS. We trust the system CA bundle.
        $opts[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
    return $opts;
}

/**
 * Build a DSN, optionally scoped to a specific database.
 */
function db_dsn(?string $dbName = null): string {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT;
    if ($dbName !== null && $dbName !== '') {
        $dsn .= ';dbname=' . $dbName;
    }
    $dsn .= ';charset=' . DB_CHARSET;
    return $dsn;
}

/**
 * Get the current database connection.
 * In multi-tenant mode, returns the tenant-scoped DB.
 * Falls back to the default DB for legacy/direct access.
 */
function getDB(): PDO {
    if (class_exists('Tenant') && Tenant::current()) {
        return Tenant::getDB();
    }

    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(db_dsn(DB_NAME), DB_USER, DB_PASS, db_pdo_options());
    }
    return $pdo;
}
