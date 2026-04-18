<?php
/**
 * Create product_features singleton-per-product table in master DB.
 * Stores the bullet list shown on the landing page, per Polar product UUID.
 *
 * HTTP GET /database/migrate_product_features.php?key=gestionpro-migrate
 */

if (($_GET['key'] ?? '') !== 'gestionpro-migrate') {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = new PDO(db_dsn('gestionpro_master'), DB_USER, DB_PASS, db_pdo_options());

    $db->exec("
        CREATE TABLE IF NOT EXISTS product_features (
            product_id VARCHAR(64) NOT NULL PRIMARY KEY,
            features TEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[ok] table product_features ready\n\nDone.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
