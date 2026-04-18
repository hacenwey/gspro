<?php
/**
 * One-off migration: create polar_config singleton table in master DB
 * and seed it from current .env Polar variables.
 *
 * HTTP GET /database/migrate_polar_config.php?key=gestionpro-migrate
 *
 * Idempotent: re-running only fills blank columns, never overwrites.
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
        CREATE TABLE IF NOT EXISTS polar_config (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            mode ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
            sandbox_access_token   VARCHAR(255) NOT NULL DEFAULT '',
            sandbox_webhook_secret VARCHAR(255) NOT NULL DEFAULT '',
            sandbox_starter_product_id    VARCHAR(64) NOT NULL DEFAULT '',
            sandbox_pro_product_id        VARCHAR(64) NOT NULL DEFAULT '',
            sandbox_enterprise_product_id VARCHAR(64) NOT NULL DEFAULT '',
            live_access_token   VARCHAR(255) NOT NULL DEFAULT '',
            live_webhook_secret VARCHAR(255) NOT NULL DEFAULT '',
            live_starter_product_id    VARCHAR(64) NOT NULL DEFAULT '',
            live_pro_product_id        VARCHAR(64) NOT NULL DEFAULT '',
            live_enterprise_product_id VARCHAR(64) NOT NULL DEFAULT '',
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[ok] table polar_config ready\n";

    $exists = (int)$db->query("SELECT COUNT(*) FROM polar_config WHERE id = 1")->fetchColumn();

    $envBase    = rtrim(getenv('POLAR_API_BASE') ?: '', '/');
    $envToken   = (string)(getenv('POLAR_ACCESS_TOKEN') ?: '');
    $envSecret  = (string)(getenv('POLAR_WEBHOOK_SECRET') ?: '');
    $envStarter = (string)(getenv('POLAR_STARTER_PRODUCT_ID') ?: '');
    $envPro     = (string)(getenv('POLAR_PRO_PRODUCT_ID') ?: '');
    $envEnt     = (string)(getenv('POLAR_ENTERPRISE_PRODUCT_ID') ?: '');

    $isSandbox = strpos($envBase, 'sandbox') !== false;
    $bucket    = $isSandbox ? 'sandbox' : 'live';

    if ($exists === 0) {
        $sql = "INSERT INTO polar_config
            (id, mode, {$bucket}_access_token, {$bucket}_webhook_secret,
             {$bucket}_starter_product_id, {$bucket}_pro_product_id, {$bucket}_enterprise_product_id)
            VALUES (1, ?, ?, ?, ?, ?, ?)";
        $db->prepare($sql)->execute([$bucket, $envToken, $envSecret, $envStarter, $envPro, $envEnt]);
        echo "[ok] seeded row id=1 (mode={$bucket})\n";
    } else {
        $fields = [
            "{$bucket}_access_token"          => $envToken,
            "{$bucket}_webhook_secret"        => $envSecret,
            "{$bucket}_starter_product_id"    => $envStarter,
            "{$bucket}_pro_product_id"        => $envPro,
            "{$bucket}_enterprise_product_id" => $envEnt,
        ];
        $parts = [];
        $vals  = [];
        foreach ($fields as $col => $val) {
            if ($val === '') continue;
            $parts[] = "$col = CASE WHEN $col = '' THEN ? ELSE $col END";
            $vals[]  = $val;
        }
        if ($parts) {
            $vals[] = 1;
            $db->prepare("UPDATE polar_config SET " . implode(', ', $parts) . " WHERE id = ?")->execute($vals);
            echo "[ok] filled blank columns for mode={$bucket}\n";
        } else {
            echo "[skip] nothing to seed\n";
        }
    }

    echo "\nDone.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
