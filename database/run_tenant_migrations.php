<?php
/**
 * CLI maintenance script: apply pending tenant migrations to every active
 * tenant database. Mirrors AdminController::runMigrations(scope=all_tenants)
 * so it can be run headlessly (e.g. `docker exec ... php database/run_tenant_migrations.php`).
 *
 * Forward-only, tracked in each tenant's `_migrations` table — safe to re-run.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Tenant.php';

use App\Database\MigrationRunner;

$master  = Tenant::getMasterDB();
$tenants = $master->query("SELECT slug FROM tenants WHERE is_active = 1")->fetchAll();

$total = 0;
$errors = [];

foreach ($tenants as $t) {
    $slug = $t['slug'];
    try {
        $tdb = Tenant::connectTo($slug);
        $ran = (new MigrationRunner($tdb, __DIR__ . '/migrations/tenant'))->run();
        echo sprintf("[%s] %s\n", $slug, $ran ? implode(', ', $ran) : 'deja a jour');
        $total += count($ran);
    } catch (\Throwable $e) {
        $errors[] = "$slug: " . $e->getMessage();
        echo sprintf("[%s] ERREUR: %s\n", $slug, $e->getMessage());
    }
}

echo "----\nTotal migrations appliquees: $total\n";
if ($errors) {
    fwrite(STDERR, "Erreurs:\n" . implode("\n", $errors) . "\n");
    exit(1);
}
