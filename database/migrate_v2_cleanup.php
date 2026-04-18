<?php
/**
 * V2 Lot 0 cleanup — remove residual Mauritania-era dbo schema bits.
 *
 *  - payments.method: drop `bankily`, `masrivi`, `sedad` from ENUM. Existing rows
 *    using those values are remapped to `mobile` before the ENUM is rewritten
 *    so the ALTER does not lose data.
 *  - products.tax_rate DEFAULT 16.00 -> DEFAULT 0.00 (USD tenants expect 0 by default).
 *
 * Idempotent. HTTP GET /database/migrate_v2_cleanup.php?key=gestionpro-migrate
 */

if (($_GET['key'] ?? '') !== 'gestionpro-migrate') {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$NEW_ENUM = "ENUM('cash','card','check','transfer','mobile')";
$LEGACY_METHODS = ['bankily', 'masrivi', 'sedad'];

try {
    $master = new PDO(db_dsn('gestionpro_master'), DB_USER, DB_PASS, db_pdo_options());
    $tenants = $master->query("SELECT slug, db_name FROM tenants")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tenants as $t) {
        echo "\n=== {$t['slug']} ({$t['db_name']}) ===\n";
        try {
            $pdo = new PDO(db_dsn($t['db_name']), DB_USER, DB_PASS, db_pdo_options());

            // 1) Remap legacy methods -> 'mobile'
            $in = "'" . implode("','", $LEGACY_METHODS) . "'";
            $remapped = $pdo->exec("UPDATE payments SET method = 'mobile' WHERE method IN ($in)");
            echo "  remapped legacy payment methods: " . (int)$remapped . "\n";

            // 2) Rewrite ENUM (only if needed)
            $col = $pdo->query("
                SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'method'
            ")->fetchColumn();
            if ($col && strpos($col, 'bankily') !== false) {
                $pdo->exec("ALTER TABLE payments MODIFY method $NEW_ENUM NOT NULL DEFAULT 'cash'");
                echo "  ENUM payments.method rewritten\n";
            } else {
                echo "  ENUM already clean\n";
            }

            // 3) tax_rate default
            $def = $pdo->query("
                SELECT COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'tax_rate'
            ")->fetchColumn();
            if ($def !== null && (float)$def != 0.0) {
                $pdo->exec("ALTER TABLE products MODIFY tax_rate DECIMAL(5,2) DEFAULT 0.00");
                echo "  products.tax_rate default -> 0.00\n";
            } else {
                echo "  tax_rate default already 0\n";
            }

            echo "  OK\n";
        } catch (PDOException $e) {
            echo "  SKIP: " . $e->getMessage() . "\n";
        }
    }

    echo "\nDone.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
