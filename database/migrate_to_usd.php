<?php
/**
 * One-off migration: switch every existing tenant's operational currency to USD.
 *
 * Run once after deploying the USD-only / internationalization refactor.
 * HTTP GET /database/migrate_to_usd.php?key=gestionpro-migrate
 *
 * Idempotent: settings are updated in-place, safe to re-run.
 */

if (($_GET['key'] ?? '') !== 'gestionpro-migrate') {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

try {
    $master = new PDO(db_dsn('gestionpro_master'), DB_USER, DB_PASS, db_pdo_options());
    $tenants = $master->query("SELECT slug, db_name FROM tenants")->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($tenants as $t) {
        try {
            $pdo = new PDO(db_dsn($t['db_name']), DB_USER, DB_PASS, db_pdo_options());
            $upd = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $upd->execute(['USD', 'currency']);
            $upd->execute(['$',   'currency_symbol']);
            $updated++;
            echo "[ok]   {$t['slug']}\n";
        } catch (PDOException $e) {
            echo "[skip] {$t['slug']}: {$e->getMessage()}\n";
        }
    }

    echo "\nDone. Updated $updated tenant(s) to USD.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
