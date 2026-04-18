<?php
namespace Tests\Unit\Services;

use App\Services\SellService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for SellService using in-memory SQLite.
 *
 * SQLite does not implement FOR UPDATE (it's a no-op in shared-cache mode),
 * but the service's correctness properties we care about here are:
 *   - availability check vs. aggregated demand
 *   - line math (subtotal / tax / total)
 *   - atomic decrement rejects negative stock
 */
final class SellServiceTest extends TestCase
{
    private PDO $pdo;
    private SellService $svc;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("
            CREATE TABLE products (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                selling_price REAL NOT NULL,
                tax_rate REAL NOT NULL DEFAULT 0,
                current_stock INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1
            )
        ");
        $this->pdo->exec("
            INSERT INTO products (id, name, selling_price, tax_rate, current_stock) VALUES
            ('p1', 'Widget A', 10.00, 0,   5),
            ('p2', 'Widget B', 20.00, 10,  2),
            ('p3', 'Widget C', 15.50, 0,   0)
        ");
        $this->svc = new SellService($this->pdo);
    }

    public function test_prices_a_single_line_without_tax(): void
    {
        $r = $this->svc->priceAndLock([['id' => 'p1', 'qty' => 3]]);
        $this->assertSame(30.0, $r['subtotal']);
        $this->assertSame(0.0,  $r['tax']);
        $this->assertSame(30.0, $r['total']);
        $this->assertCount(1, $r['lines']);
        $this->assertSame('Widget A', $r['lines'][0]['description']);
    }

    public function test_prices_with_tax(): void
    {
        $r = $this->svc->priceAndLock([['id' => 'p2', 'qty' => 2]]);
        $this->assertSame(40.0, $r['subtotal']);
        $this->assertSame(4.0,  $r['tax']);
        $this->assertSame(44.0, $r['total']);
    }

    public function test_aggregates_duplicate_cart_lines_for_availability_check(): void
    {
        // p2 has stock=2. Two lines of 1 each -> allowed. Two lines of 2 each -> not.
        $r = $this->svc->priceAndLock([
            ['id' => 'p2', 'qty' => 1],
            ['id' => 'p2', 'qty' => 1],
        ]);
        $this->assertSame(44.0, $r['total']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuffisant/');
        $this->svc->priceAndLock([
            ['id' => 'p2', 'qty' => 2],
            ['id' => 'p2', 'qty' => 2],
        ]);
    }

    public function test_rejects_out_of_stock_product(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuffisant/');
        $this->svc->priceAndLock([['id' => 'p3', 'qty' => 1]]);
    }

    public function test_rejects_unknown_product(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->priceAndLock([['id' => 'ghost', 'qty' => 1]]);
    }

    public function test_rejects_invalid_cart(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/panier/i');
        $this->svc->priceAndLock([]);
    }

    public function test_rejects_zero_or_negative_qty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->priceAndLock([['id' => 'p1', 'qty' => 0]]);
    }

    public function test_decrement_succeeds_then_blocks_oversell(): void
    {
        $this->svc->decrementStock('p1', 3);
        $left = $this->pdo->query("SELECT current_stock FROM products WHERE id='p1'")->fetchColumn();
        $this->assertSame(2, (int)$left);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuffisant/');
        $this->svc->decrementStock('p1', 3); // only 2 left
    }
}
