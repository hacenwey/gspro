<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PaymentService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PaymentServiceTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec("CREATE TABLE debts (
            id TEXT PRIMARY KEY,
            type TEXT,
            customer_id TEXT,
            supplier_id TEXT,
            invoice_id TEXT,
            total_amount REAL,
            paid_amount REAL DEFAULT 0,
            status TEXT DEFAULT 'pending'
        )");

        $this->db->exec("CREATE TABLE invoices (
            id TEXT PRIMARY KEY,
            total REAL,
            amount_paid REAL DEFAULT 0,
            status TEXT DEFAULT 'sent'
        )");

        $this->db->exec("CREATE TABLE customers (
            id TEXT PRIMARY KEY,
            balance REAL DEFAULT 0
        )");

        $this->db->exec("CREATE TABLE suppliers (
            id TEXT PRIMARY KEY,
            balance REAL DEFAULT 0
        )");

        $this->db->exec("CREATE TABLE payments (
            id TEXT PRIMARY KEY,
            type TEXT,
            invoice_id TEXT,
            customer_id TEXT,
            supplier_id TEXT,
            cash_session_id TEXT,
            amount REAL,
            method TEXT,
            payment_date TEXT,
            notes TEXT,
            user_id TEXT
        )");
    }

    private function seedDebtScenario(float $total, float $paid = 0, string $type = 'receivable'): array
    {
        $this->db->exec("INSERT INTO invoices (id, total, amount_paid, status) VALUES ('inv-1', $total, $paid, 'partial')");
        $this->db->exec("INSERT INTO customers (id, balance) VALUES ('cust-1', -$total)");
        $this->db->exec("INSERT INTO debts (id, type, customer_id, invoice_id, total_amount, paid_amount, status)
                         VALUES ('debt-1', '$type', 'cust-1', 'inv-1', $total, $paid, 'pending')");
        return [
            'id' => 'debt-1',
            'type' => $type,
            'customer_id' => 'cust-1',
            'supplier_id' => null,
            'invoice_id' => 'inv-1',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'status' => 'pending',
        ];
    }

    public function testPartialPaymentMarksPartial(): void
    {
        $debt = $this->seedDebtScenario(1000.00);

        $this->db->beginTransaction();
        $svc = new PaymentService($this->db);
        $svc->applyDebtPayment($debt, 400.00, 'cash', null, 'user-1');
        $this->db->commit();

        $row = $this->db->query("SELECT paid_amount, status FROM debts WHERE id = 'debt-1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(400.00, $row['paid_amount']);
        $this->assertSame('partial', $row['status']);

        $inv = $this->db->query("SELECT amount_paid, status FROM invoices WHERE id = 'inv-1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(400.00, $inv['amount_paid']);
        $this->assertSame('partial', $inv['status']);

        $cust = $this->db->query("SELECT balance FROM customers WHERE id = 'cust-1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(-600.00, $cust['balance']); // -1000 + 400
    }

    public function testFullPaymentMarksPaid(): void
    {
        $debt = $this->seedDebtScenario(500.00);

        $this->db->beginTransaction();
        $svc = new PaymentService($this->db);
        $svc->applyDebtPayment($debt, 500.00, 'card', 'final', 'user-1');
        $this->db->commit();

        $row = $this->db->query("SELECT status FROM debts WHERE id = 'debt-1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('paid', $row['status']);

        $inv = $this->db->query("SELECT status FROM invoices WHERE id = 'inv-1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('paid', $inv['status']);
    }

    public function testRejectsOverpayment(): void
    {
        $debt = $this->seedDebtScenario(200.00, 100.00); // remaining = 100

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('superieur');

        $svc = new PaymentService($this->db);
        $svc->applyDebtPayment($debt, 150.00, 'cash', null, 'user-1');
    }

    public function testRejectsNonPositiveAmount(): void
    {
        $debt = $this->seedDebtScenario(200.00);

        $this->expectException(RuntimeException::class);
        $svc = new PaymentService($this->db);
        $svc->applyDebtPayment($debt, 0.0, 'cash', null, 'user-1');
    }

    public function testPayableDebtRecordedAsOutgoing(): void
    {
        $debt = $this->seedDebtScenario(300.00, 0, 'payable');

        $this->db->beginTransaction();
        $svc = new PaymentService($this->db);
        $svc->applyDebtPayment($debt, 100.00, 'transfer', null, 'user-1');
        $this->db->commit();

        $type = $this->db->query("SELECT type FROM payments WHERE invoice_id = 'inv-1'")->fetchColumn();
        $this->assertSame('outgoing', $type);
    }

    public function testRecordSalePaymentInserts(): void
    {
        $this->db->exec("INSERT INTO invoices (id, total, amount_paid, status) VALUES ('inv-2', 500, 0, 'draft')");

        $svc = new PaymentService($this->db);
        $paymentId = $svc->recordSalePayment('inv-2', 'cust-1', null, 500.0, 'cash', 'user-1');

        $row = $this->db->query("SELECT type, amount, method FROM payments WHERE id = '$paymentId'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('incoming', $row['type']);
        $this->assertEquals(500.0, $row['amount']);
        $this->assertSame('cash', $row['method']);
    }

    public function testRecordSalePaymentRejectsZero(): void
    {
        $this->expectException(RuntimeException::class);
        $svc = new PaymentService($this->db);
        $svc->recordSalePayment('inv-2', null, null, 0.0, 'cash', 'user-1');
    }
}
