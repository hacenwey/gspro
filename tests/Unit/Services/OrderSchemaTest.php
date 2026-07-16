<?php
namespace Tests\Unit\Services;

use App\Services\OrderService;
use PHPUnit\Framework\TestCase;

/**
 * Guards the order enums in the SQL schema against the statuses the code writes.
 *
 * Why this exists: OrderServiceTest runs on SQLite, which has no ENUM — it stores
 * the column as TEXT and accepts anything. So a status the service writes but the
 * MySQL enum does not list passes every test and then fails in production with
 * "Data truncated for column 'status'". That is exactly what happened: 'sent' was
 * missing from the order_items enum.
 *
 * Parsing the DDL is crude, but it is the only place where the two definitions can
 * be compared without a MySQL server.
 */
final class OrderSchemaTest extends TestCase
{
    private const SCHEMA    = __DIR__ . '/../../../database/schema.sql';
    private const MIGRATION = __DIR__ . '/../../../database/migrations/tenant/202607161000_restaurant_orders.sql';

    /** Pull the ENUM values of `status` from a named CREATE TABLE block. */
    private function enumOf(string $file, string $table): array
    {
        $sql = file_get_contents($file);
        $this->assertNotFalse($sql, "unreadable: $file");

        // Isolate the table block, then the status column inside it.
        $start = stripos($sql, "CREATE TABLE IF NOT EXISTS $table (");
        if ($start === false) {
            $start = stripos($sql, "CREATE TABLE $table (");
        }
        $this->assertNotFalse($start, "table $table not found in " . basename($file));

        $block = substr($sql, $start, strpos($sql, ') ENGINE', $start) - $start);
        $this->assertSame(1, preg_match("/^\s*status ENUM\(([^)]+)\)/mi", $block, $m),
            "no status ENUM on $table in " . basename($file));

        preg_match_all("/'([^']+)'/", $m[1], $vals);
        return $vals[1];
    }

    public function test_order_items_enum_lists_every_status_the_service_writes(): void
    {
        foreach ([self::SCHEMA, self::MIGRATION] as $file) {
            $this->assertSame(
                OrderService::ITEM_STATUSES,
                $this->enumOf($file, 'order_items'),
                basename($file) . ': order_items.status must match OrderService::ITEM_STATUSES'
            );
        }
    }

    public function test_orders_enum_lists_every_status_the_service_writes(): void
    {
        foreach ([self::SCHEMA, self::MIGRATION] as $file) {
            $this->assertSame(
                OrderService::ORDER_STATUSES,
                $this->enumOf($file, 'orders'),
                basename($file) . ': orders.status must match OrderService::ORDER_STATUSES'
            );
        }
    }

    /** The kitchen queue can only ask for line statuses that exist. */
    public function test_kitchen_statuses_are_a_subset_of_line_statuses(): void
    {
        $this->assertSame(
            [],
            array_diff(OrderService::KITCHEN_STATUSES, OrderService::ITEM_STATUSES)
        );
    }
}
