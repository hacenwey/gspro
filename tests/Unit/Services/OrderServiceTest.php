<?php
namespace Tests\Unit\Services;

use App\Services\OrderService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for OrderService using in-memory SQLite.
 *
 * The property that matters here is the derived order status: a ticket is only
 * as advanced as its least advanced dish. Everything the floor and the kitchen
 * display hangs off that rule, so it is pinned down case by case.
 */
final class OrderServiceTest extends TestCase
{
    private PDO $pdo;
    private OrderService $svc;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("
            CREATE TABLE products (
                id TEXT PRIMARY KEY, name TEXT NOT NULL,
                selling_price REAL NOT NULL, tax_rate REAL NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1
            )");
        $this->pdo->exec("
            INSERT INTO products (id, name, selling_price, tax_rate) VALUES
            ('p1', 'Thieboudienne', 100.0, 0),
            ('p2', 'Jus de bissap', 50.0, 10),
            ('p3', 'Plat retire', 30.0, 0)");
        $this->pdo->exec("UPDATE products SET is_active = 0 WHERE id = 'p3'");
        $this->pdo->exec("
            CREATE TABLE orders (
                id TEXT PRIMARY KEY, number TEXT, type TEXT, status TEXT DEFAULT 'open',
                table_id TEXT, customer_id TEXT, user_id TEXT, cash_session_id TEXT,
                invoice_id TEXT, merged_into_id TEXT, notes TEXT,
                sent_at TEXT, ready_at TEXT, closed_at TEXT
            )");
        $this->pdo->exec("
            CREATE TABLE order_items (
                id TEXT PRIMARY KEY, order_id TEXT NOT NULL, product_id TEXT,
                description TEXT, quantity INTEGER, unit_price REAL, tax_rate REAL,
                line_total REAL, status TEXT DEFAULT 'pending', notes TEXT,
                invoice_id TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )");
        $this->svc = new OrderService($this->pdo);
    }

    private function newOrder(string $type = 'takeaway', ?string $table = null): string
    {
        return $this->svc->create($type, $table, null, 'u1', null, 'CMD-1');
    }

    /** @param string[] $statuses */
    private function orderWithItemStatuses(array $statuses): string
    {
        $id = $this->newOrder();
        foreach ($statuses as $i => $s) {
            $this->pdo->prepare("INSERT INTO order_items (id, order_id, product_id, description, quantity, unit_price, tax_rate, line_total, status) VALUES (?,?,?,?,1,10,0,10,?)")
                ->execute(["i$i", $id, 'p1', 'x', $s]);
        }
        $this->svc->refreshStatus($id);
        return $id;
    }

    private function statusOf(string $id): string
    {
        $s = $this->pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $s->execute([$id]);
        return (string)$s->fetchColumn();
    }

    public function test_dine_in_requires_a_table(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->create('dine_in', null, null, 'u1', null, 'CMD-X');
    }

    public function test_rejects_unknown_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->svc->create('teleportation', null, null, 'u1', null, 'CMD-X');
    }

    public function test_add_item_prices_from_the_product_and_computes_tax(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p2', 2); // 50 x2 = 100, +10% = 110

        $row = $this->pdo->query("SELECT * FROM order_items")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Jus de bissap', $row['description']);
        $this->assertEquals(50.0, $row['unit_price']);
        $this->assertEquals(110.0, $row['line_total']);

        $t = $this->svc->totals($id);
        $this->assertEquals(100.0, $t['subtotal']);
        $this->assertEquals(10.0, $t['tax']);
        $this->assertEquals(110.0, $t['total']);
    }

    public function test_add_item_rejects_inactive_product_and_bad_quantity(): void
    {
        $id = $this->newOrder();
        try { $this->svc->addItem($id, 'p3', 1); $this->fail('inactive product accepted'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('introuvable', $e->getMessage()); }

        $this->expectException(RuntimeException::class);
        $this->svc->addItem($id, 'p1', 0);
    }

    public function test_same_dish_stacks_but_a_different_note_does_not(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->addItem($id, 'p1', 2);                 // merges -> qty 3
        $this->svc->addItem($id, 'p1', 1, 'sans piment');  // distinct note -> own line

        $rows = $this->pdo->query("SELECT quantity, notes, line_total FROM order_items ORDER BY notes IS NULL DESC")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertEquals(3, $rows[0]['quantity']);
        $this->assertEquals(300.0, $rows[0]['line_total']); // total recomputed on merge
        $this->assertSame('sans piment', $rows[1]['notes']);
    }

    public function test_dish_already_in_the_kitchen_is_not_merged_into(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->send($id);          // line becomes 'sent'
        $this->svc->addItem($id, 'p1', 1); // must open a new line, not touch the cooking one

        $this->assertSame(2, (int)$this->pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn());
    }

    public function test_send_marks_pending_lines_and_refuses_when_nothing_is_new(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->send($id);

        $this->assertSame('sent', $this->statusOf($id));
        $this->assertSame('sent', (string)$this->pdo->query("SELECT status FROM order_items")->fetchColumn());
        $this->assertNotNull($this->pdo->query("SELECT sent_at FROM orders")->fetchColumn());

        $this->expectException(RuntimeException::class);
        $this->svc->send($id); // nothing pending anymore
    }

    /**
     * The kitchen ticket is built from what send() returns, so a second round
     * must hand back only the new dishes — never reprint the first round.
     */
    public function test_send_returns_only_the_lines_it_just_sent(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 2, 'sans piment');

        $first = $this->svc->send($id);
        $this->assertCount(1, $first);
        $this->assertSame(2, $first[0]['qty']);
        $this->assertSame('Thieboudienne', $first[0]['label']);
        $this->assertSame('sans piment', $first[0]['notes']);

        $this->svc->addItem($id, 'p2', 1);
        $second = $this->svc->send($id);
        $this->assertCount(1, $second, 'second round must not reprint the first');
        $this->assertSame('Jus de bissap', $second[0]['label']);
        $this->assertNull($second[0]['notes']);
    }

    /**
     * The ticket is only as advanced as its least advanced dish.
     * @dataProvider statusCases
     */
    public function test_order_status_is_derived_from_its_lines(array $items, string $expected): void
    {
        $this->assertSame($expected, $this->statusOf($this->orderWithItemStatuses($items)));
    }

    public static function statusCases(): array
    {
        return [
            'all pending stays open'        => [['pending', 'pending'], 'open'],
            'all sent'                      => [['sent', 'sent'], 'sent'],
            'one cooking drags the ticket'  => [['preparing', 'sent'], 'preparing'],
            'one ready, one still sent'     => [['ready', 'sent'], 'sent'],
            'ready only when every dish is' => [['ready', 'ready'], 'ready'],
            'ready + served counts as ready'=> [['ready', 'served'], 'ready'],
            'all served'                    => [['served', 'served'], 'served'],
            'cancelled lines are ignored'   => [['ready', 'cancelled'], 'ready'],
            'no live line falls back'       => [['cancelled'], 'open'],
        ];
    }

    public function test_ready_at_is_stamped_once_the_whole_ticket_is_ready(): void
    {
        $id = $this->orderWithItemStatuses(['ready', 'ready']);
        $this->assertNotNull($this->pdo->query("SELECT ready_at FROM orders")->fetchColumn());
    }

    public function test_paid_order_is_terminal_and_frozen(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->markPaid($id, 'inv-1');

        $this->assertSame('paid', $this->statusOf($id));

        // A late kitchen tick must not resurrect a closed ticket.
        $this->svc->refreshStatus($id);
        $this->assertSame('paid', $this->statusOf($id));

        try { $this->svc->addItem($id, 'p1', 1); $this->fail('added to a paid order'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('cloturee', $e->getMessage()); }

        $this->expectException(RuntimeException::class);
        $this->svc->cancel($id); // a paid order cannot be cancelled
    }

    public function test_items_for_sale_groups_quantities_per_product(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1, 'note A'); // separate lines...
        $this->svc->addItem($id, 'p1', 2, 'note B');
        $this->svc->addItem($id, 'p2', 1);

        $sale = $this->svc->itemsForSale($id);
        $byId = [];
        foreach ($sale as $l) { $byId[$l['id']] = $l['qty']; }

        $this->assertSame(3, $byId['p1']); // ...but stock moves once, for 3
        $this->assertSame(1, $byId['p2']);
    }

    public function test_cancel_marks_lines_and_order(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->cancel($id);

        $this->assertSame('cancelled', $this->statusOf($id));
        $this->assertSame('cancelled', (string)$this->pdo->query("SELECT status FROM order_items")->fetchColumn());
        $this->assertSame([], $this->svc->itemsForSale($id)); // nothing to invoice
    }

    // ===================== Split & merge =====================

    /** @return string[] ids of the order's lines, in creation order */
    private function itemIds(string $orderId): array
    {
        $q = $this->pdo->prepare("SELECT id FROM order_items WHERE order_id = ? ORDER BY created_at, rowid");
        $q->execute([$orderId]);
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }

    public function test_settling_part_of_a_table_leaves_the_rest_owed(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);  // 100
        $this->svc->addItem($id, 'p2', 1);  // 50 + 10% = 55
        [$first] = $this->itemIds($id);

        $this->svc->settle($id, [$first], 'inv-1');

        // The order stays open — someone still owes for the juice.
        $this->assertSame('open', $this->statusOf($id));
        $this->assertEquals(55.0, $this->svc->totalsFor($id)['total']);
        $this->assertCount(1, $this->svc->unpaidItems($id));

        // ...and the settled dish is off the next bill, and off stock.
        $this->assertSame([['id' => 'p2', 'qty' => 1]], $this->svc->itemsForSale($id));
    }

    public function test_order_closes_once_the_last_line_is_settled(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->addItem($id, 'p2', 1);
        [$a, $b] = $this->itemIds($id);

        $this->svc->settle($id, [$a], 'inv-1');
        $this->assertSame('open', $this->statusOf($id));

        $this->svc->settle($id, [$b], 'inv-2');
        $this->assertSame('paid', $this->statusOf($id));
        $this->assertEquals(0.0, $this->svc->totalsFor($id)['total']);
        $this->assertSame('inv-2', $this->pdo->query("SELECT invoice_id FROM orders")->fetchColumn());
    }

    public function test_settling_without_a_selection_takes_everything_owed(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->addItem($id, 'p2', 2);

        $this->svc->markPaid($id, 'inv-1'); // null selection = all
        $this->assertSame('paid', $this->statusOf($id));
        $this->assertSame([], $this->svc->unpaidItems($id));
    }

    public function test_a_settled_line_is_never_resold_or_cancelled(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->addItem($id, 'p2', 1);
        [$paidLine] = $this->itemIds($id);
        $this->svc->settle($id, [$paidLine], 'inv-1');

        // Cancelling the table must not rewrite the bill someone already paid.
        $this->svc->cancel($id);
        $q = $this->pdo->prepare("SELECT status FROM order_items WHERE id = ?");
        $q->execute([$paidLine]);
        $this->assertSame('pending', $q->fetchColumn(), 'an invoiced line must keep its status');
    }

    public function test_merge_moves_unpaid_lines_and_leaves_a_trail(): void
    {
        $a = $this->newOrder();
        $b = $this->newOrder();
        $this->svc->addItem($a, 'p1', 1);
        $this->svc->addItem($b, 'p2', 2);

        $this->svc->merge($b, $a); // b joins a

        $this->assertCount(2, $this->svc->unpaidItems($a));
        $this->assertSame('cancelled', $this->statusOf($b));

        $q = $this->pdo->prepare("SELECT merged_into_id FROM orders WHERE id = ?");
        $q->execute([$b]);
        $this->assertSame($a, $q->fetchColumn(), 'the merged ticket must point at its host');
    }

    public function test_merge_leaves_already_invoiced_lines_behind(): void
    {
        $a = $this->newOrder();
        $b = $this->newOrder();
        $this->svc->addItem($b, 'p1', 1);
        $this->svc->addItem($b, 'p2', 1);
        [$paid] = $this->itemIds($b);
        $this->svc->settle($b, [$paid], 'inv-1'); // one dish already paid on b

        $this->svc->merge($b, $a);

        // Only the unpaid dish moves; the paid one stays with the bill that paid it.
        $this->assertCount(1, $this->svc->unpaidItems($a));
        $q = $this->pdo->prepare("SELECT order_id FROM order_items WHERE id = ?");
        $q->execute([$paid]);
        $this->assertSame($b, $q->fetchColumn());
    }

    public function test_merge_rejects_self_and_closed_orders(): void
    {
        $a = $this->newOrder();
        $this->svc->addItem($a, 'p1', 1);

        try { $this->svc->merge($a, $a); $this->fail('merged with itself'); }
        catch (RuntimeException $e) { $this->assertStringContainsString('elle-meme', $e->getMessage()); }

        $b = $this->newOrder();
        $this->svc->addItem($b, 'p1', 1);
        $this->svc->markPaid($b, 'inv-9');

        $this->expectException(RuntimeException::class);
        $this->svc->merge($a, $b); // target already closed
    }

    public function test_line_in_the_kitchen_cannot_be_removed(): void
    {
        $id = $this->newOrder();
        $this->svc->addItem($id, 'p1', 1);
        $this->svc->send($id);
        $itemId = (string)$this->pdo->query("SELECT id FROM order_items")->fetchColumn();

        $this->expectException(RuntimeException::class);
        $this->svc->removeItem($itemId);
    }
}
