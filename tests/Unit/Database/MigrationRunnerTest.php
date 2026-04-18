<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use App\Database\MigrationRunner;
use PDO;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migrunner_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    private function mkDb(): PDO
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    }

    private function writeMigration(string $name, string $sql): void
    {
        file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . $name . '.sql', $sql);
    }

    public function testRunsPendingMigrationsInOrder(): void
    {
        $db = $this->mkDb();
        $this->writeMigration('202604180001_create_foo', 'CREATE TABLE foo (id INTEGER PRIMARY KEY)');
        $this->writeMigration('202604180002_add_bar', 'ALTER TABLE foo ADD COLUMN bar TEXT');

        $ran = (new MigrationRunner($db, $this->tmpDir))->run();

        $this->assertCount(2, $ran);
        $this->assertSame('202604180001_create_foo', $ran[0]);
        $cols = $db->query("PRAGMA table_info(foo)")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $cols);
    }

    public function testSkipsAlreadyApplied(): void
    {
        $db = $this->mkDb();
        $this->writeMigration('202604180001_a', 'CREATE TABLE a (id INTEGER PRIMARY KEY)');

        $runner = new MigrationRunner($db, $this->tmpDir);
        $runner->run();
        $second = $runner->run();
        $this->assertSame([], $second);
    }

    public function testBaselineMarksWithoutRunning(): void
    {
        $db = $this->mkDb();
        $this->writeMigration('202604180001_never_executed', 'CREATE TABLE should_not_exist (id INT)');

        $runner = new MigrationRunner($db, $this->tmpDir);
        $marked = $runner->baseline();

        $this->assertSame(['202604180001_never_executed'], $marked);
        $tbl = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='should_not_exist'")->fetchColumn();
        $this->assertFalse($tbl);

        $this->assertSame([], $runner->pending());
    }

    public function testRollbackOnFailure(): void
    {
        $db = $this->mkDb();
        $this->writeMigration('202604180001_bad', 'CREATE TABLE good (id INT); CREATE TABLE good (id INT)'); // duplicate

        $this->expectException(\RuntimeException::class);
        (new MigrationRunner($db, $this->tmpDir))->run();
    }
}
