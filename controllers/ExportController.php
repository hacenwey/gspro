<?php
/**
 * CSV exports for tenant-scoped data.
 *
 * Uses streaming via CsvWriter so large catalogs don't buffer in memory.
 */
class ExportController extends Controller {

    public function products(): void {
        $this->requireAuth();
        $rows = $this->db->query("
            SELECT p.reference, p.barcode, p.name, c.name AS category, p.unit,
                   p.purchase_price, p.selling_price, p.tax_rate,
                   p.current_stock, p.min_stock, p.is_active
            FROM products p LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.name
        ");
        $this->emitCsv(
            'products-' . date('Y-m-d') . '.csv',
            ['Ref', 'Barcode', 'Name', 'Category', 'Unit', 'Purchase price', 'Selling price', 'Tax %', 'Stock', 'Min stock', 'Active'],
            $rows
        );
    }

    public function clients(): void {
        $this->requireAuth();
        $rows = $this->db->query("
            SELECT type, last_name, first_name, phone, email, address, tax_id,
                   category, loyalty_points, credit_limit, balance, created_at
            FROM customers ORDER BY last_name, first_name
        ");
        $this->emitCsv(
            'clients-' . date('Y-m-d') . '.csv',
            ['Type', 'Last name', 'First name', 'Phone', 'Email', 'Address', 'Tax ID', 'Category', 'Loyalty', 'Credit limit', 'Balance', 'Created'],
            $rows
        );
    }

    public function sales(): void {
        $this->requireAuth();
        $from = $this->input('from', date('Y-m-01'));
        $to   = $this->input('to',   date('Y-m-d'));
        $stmt = $this->db->prepare("
            SELECT i.number, i.issue_date, i.type, i.status,
                   COALESCE(CONCAT_WS(' ', c.first_name, c.last_name), '-') AS customer,
                   i.subtotal, i.tax_amount, i.discount_amount, i.total, i.amount_paid,
                   (i.total - i.amount_paid) AS remaining
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            WHERE i.issue_date BETWEEN ? AND ?
            ORDER BY i.issue_date DESC, i.number DESC
        ");
        $stmt->execute([$from, $to]);
        $this->emitCsv(
            "sales-$from-to-$to.csv",
            ['Number', 'Date', 'Type', 'Status', 'Customer', 'Subtotal', 'Tax', 'Discount', 'Total', 'Paid', 'Remaining'],
            $stmt
        );
    }

    /**
     * @param \PDOStatement $stmt a PDO statement ready to fetch (associative or numeric)
     */
    private function emitCsv(string $filename, array $headers, \PDOStatement $stmt): void {
        $gen = (function() use ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                yield $row;
            }
        })();
        (new \App\Services\CsvWriter())->stream($filename, $headers, $gen);
        exit;
    }
}
