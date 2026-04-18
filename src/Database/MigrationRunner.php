<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;
use App\Logging\Logger;

/**
 * Tiny forward-only SQL migration runner.
 *
 * Files under database/migrations/ named `YYYYMMDDHHMM_slug.sql` are applied in
 * lexicographic order. Applied versions are tracked in a `_migrations` table
 * on the target database (master or tenant).
 *
 * No rollback — fix-forward only. Keep migrations idempotent-friendly
 * (use IF NOT EXISTS, check column existence, etc.) since recovery is manual.
 */
final class MigrationRunner
{
    public function __construct(private PDO $db, private string $migrationsDir) {}

    public function run(): array
    {
        $this->ensureTable();
        $applied = $this->applied();
        $available = $this->available();

        $ran = [];
        foreach ($available as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException("Impossible de lire $file");
            }

            $this->db->beginTransaction();
            try {
                foreach ($this->splitStatements($sql) as $stmt) {
                    $trim = trim($stmt);
                    if ($trim === '' || str_starts_with($trim, '--')) continue;
                    $this->db->exec($stmt);
                }
                $this->db->prepare("INSERT INTO _migrations (version, applied_at) VALUES (?, " . $this->nowExpr() . ")")
                    ->execute([$version]);
                $this->db->commit();
                $ran[] = $version;
                Logger::info('migration.applied', ['version' => $version]);
            } catch (\Throwable $e) {
                $this->db->rollBack();
                Logger::exception($e, ['migration' => $version]);
                throw new RuntimeException("Migration $version echec: " . $e->getMessage(), 0, $e);
            }
        }
        return $ran;
    }

    /** Mark every discovered migration as already applied (used for baselining existing DBs). */
    public function baseline(): array
    {
        $this->ensureTable();
        $applied = $this->applied();
        $marked = [];
        foreach ($this->available() as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $applied, true)) continue;
            $this->db->prepare("INSERT INTO _migrations (version, applied_at) VALUES (?, " . $this->nowExpr() . ")")
                ->execute([$version]);
            $marked[] = $version;
        }
        return $marked;
    }

    public function pending(): array
    {
        $this->ensureTable();
        $applied = $this->applied();
        $out = [];
        foreach ($this->available() as $file) {
            $version = basename($file, '.sql');
            if (!in_array($version, $applied, true)) $out[] = $version;
        }
        return $out;
    }

    private function ensureTable(): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->db->exec("CREATE TABLE IF NOT EXISTS _migrations (
                version TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL
            )");
        } else {
            $this->db->exec("CREATE TABLE IF NOT EXISTS _migrations (
                version VARCHAR(191) PRIMARY KEY,
                applied_at DATETIME NOT NULL
            )");
        }
    }

    private function applied(): array
    {
        $stmt = $this->db->query("SELECT version FROM _migrations");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    private function available(): array
    {
        if (!is_dir($this->migrationsDir)) return [];
        $files = glob(rtrim($this->migrationsDir, '/\\') . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function splitStatements(string $sql): array
    {
        // Simple splitter: semicolon at end of line. Stored procs with internal ';'
        // are not supported — use a DELIMITER workaround if ever needed.
        $stripped = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*[\r\n]+/', $stripped) ?: [];
        return array_filter(array_map('trim', $parts), fn($s) => $s !== '');
    }

    private function nowExpr(): string
    {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()';
    }
}
