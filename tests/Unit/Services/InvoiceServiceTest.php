<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\InvoiceService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InvoiceServiceTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE invoices (
            id TEXT PRIMARY KEY,
            number TEXT,
            type TEXT,
            status TEXT,
            customer_id TEXT,
            user_id TEXT,
            issue_date TEXT,
            due_date TEXT,
            validity_date TEXT,
            subtotal REAL,
            tax_amount REAL,
            discount_amount REAL,
            total REAL,
            notes TEXT
        )");
        $this->db->exec("CREATE TABLE invoice_items (
            id TEXT PRIMARY KEY,
            invoice_id TEXT,
            product_id TEXT,
            description TEXT,
            quantity REAL,
            unit_price REAL,
            tax_rate REAL,
            discount_pct REAL,
            line_total REAL
        )");
    }

    public function testTotalizeSingleLine(): void
    {
        $svc = new InvoiceService($this->db);
        $totals = $svc->totalize([
            ['quantity' => 2, 'unit_price' => 100, 'tax_rate' => 20, 'description' => 'X'],
        ]);
        $this->assertEquals(200.00, $totals['subtotal']);
        $this->assertEquals(40.00, $totals['tax']);
        $this->assertEquals(240.00, $totals['total']);
    }

    public function testTotalizeAppliesLineDiscount(): void
    {
        $svc = new InvoiceService($this->db);
        $totals = $svc->totalize([
            ['quantity' => 10, 'unit_price' => 100, 'tax_rate' => 0, 'discount_pct' => 10, 'description' => 'X'],
        ]);
        $this->assertEquals(900.00, $totals['subtotal']);
        $this->assertEquals(900.00, $totals['total']);
    }

    public function testTotalizeAppliesInvoiceDiscount(): void
    {
        $svc = new InvoiceService($this->db);
        $totals = $svc->totalize([
            ['quantity' => 1, 'unit_price' => 1000, 'tax_rate' => 0, 'description' => 'X'],
        ], 50.0);
        $this->assertEquals(1000.00, $totals['subtotal']);
        $this->assertEquals(50.00, $totals['discount']);
        $this->assertEquals(950.00, $totals['total']);
    }

    public function testTotalizeRejectsEmpty(): void
    {
        $this->expectException(RuntimeException::class);
        (new InvoiceService($this->db))->totalize([]);
    }

    public function testTotalizeRejectsBadQty(): void
    {
        $this->expectException(RuntimeException::class);
        (new InvoiceService($this->db))->totalize([
            ['quantity' => 0, 'unit_price' => 10, 'tax_rate' => 0, 'description' => 'X'],
        ]);
    }

    public function testNextNumberSequence(): void
    {
        $year = date('Y');
        $this->db->exec("INSERT INTO invoices (id, number) VALUES ('a', 'INV-$year-0001')");
        $this->db->exec("INSERT INTO invoices (id, number) VALUES ('b', 'INV-$year-0002')");

        $svc = new InvoiceService($this->db);
        $this->assertSame("INV-$year-0003", $svc->nextNumber('INV'));
    }

    public function testNextNumberBootstraps(): void
    {
        $svc = new InvoiceService($this->db);
        $year = date('Y');
        $this->assertSame("INV-$year-0001", $svc->nextNumber('INV'));
    }

    public function testCreateDraftPersistsHeaderAndItems(): void
    {
        $svc = new InvoiceService($this->db);
        $totals = $svc->totalize([
            ['quantity' => 3, 'unit_price' => 50, 'tax_rate' => 20, 'description' => 'Widget'],
            ['quantity' => 1, 'unit_price' => 20, 'tax_rate' => 0, 'description' => 'Misc'],
        ]);

        $this->db->beginTransaction();
        $id = $svc->createDraft([
            'id'     => 'inv-test',
            'number' => 'INV-2026-0001',
            'type'   => 'invoice',
        ], $totals['lines'], $totals);
        $this->db->commit();

        $this->assertSame('inv-test', $id);

        $row = $this->db->query("SELECT subtotal, tax_amount, total FROM invoices WHERE id = 'inv-test'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(170.00, $row['subtotal']);
        $this->assertEquals(30.00, $row['tax_amount']);
        $this->assertEquals(200.00, $row['total']);

        $count = $this->db->query("SELECT COUNT(*) FROM invoice_items WHERE invoice_id = 'inv-test'")->fetchColumn();
        $this->assertEquals(2, $count);
    }
}
