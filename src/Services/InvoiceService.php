<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Totals calculation + invoice persistence for the non-POS flow
 * (form-based invoice/quote/credit_note creation).
 *
 * POS sales keep going through SellService+PaymentService because
 * they need stock locking semantics that don't apply to drafts.
 */
final class InvoiceService
{
    public function __construct(private PDO $db) {}

    /**
     * Normalize + total a cart of line items coming from the invoice form.
     * Each input line requires: description, quantity, unit_price, tax_rate.
     * Optional: product_id, discount_pct.
     *
     * @param array<int, array<string, mixed>> $rawItems
     * @return array{subtotal: float, tax: float, discount: float, total: float, lines: array<int, array<string, mixed>>}
     */
    public function totalize(array $rawItems, float $invoiceDiscount = 0.0): array
    {
        if (empty($rawItems)) {
            throw new RuntimeException('Aucune ligne.');
        }

        $subtotal = 0.0;
        $taxAmount = 0.0;
        $lines = [];

        foreach ($rawItems as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $unit = (float)($item['unit_price'] ?? 0);
            $taxRate = (float)($item['tax_rate'] ?? 0);
            $discountPct = (float)($item['discount_pct'] ?? 0);

            if ($qty <= 0) {
                throw new RuntimeException('Quantite invalide.');
            }
            if ($unit < 0) {
                throw new RuntimeException('Prix unitaire invalide.');
            }

            $lineSubtotal = $unit * $qty * (1 - $discountPct / 100);
            $lineTax = $lineSubtotal * ($taxRate / 100);
            $lineTotal = $lineSubtotal + $lineTax;

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;

            $lines[] = [
                'product_id'   => $item['product_id'] ?? null,
                'description'  => $item['description'] ?? '',
                'quantity'     => $qty,
                'unit_price'   => $unit,
                'tax_rate'     => $taxRate,
                'discount_pct' => $discountPct,
                'line_total'   => $lineTotal,
            ];
        }

        $total = $subtotal + $taxAmount - $invoiceDiscount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax'      => round($taxAmount, 2),
            'discount' => round($invoiceDiscount, 2),
            'total'    => round($total, 2),
            'lines'    => $lines,
        ];
    }

    /**
     * Generate next invoice number for the given prefix & year.
     * Matches the legacy Controller::generateNumber() format.
     */
    public function nextNumber(string $prefix, string $table = 'invoices', string $column = 'number'): string
    {
        $year = date('Y');
        $pattern = $prefix . '-' . $year . '-%';
        $stmt = $this->db->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY $column DESC LIMIT 1");
        $stmt->execute([$pattern]);
        $last = $stmt->fetchColumn();
        $counter = 1;
        if ($last) {
            $parts = explode('-', $last);
            $counter = intval(end($parts)) + 1;
        }
        return $prefix . '-' . $year . '-' . str_pad((string)$counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Persist a draft invoice (+ its items) inside an already-open transaction.
     * Returns the invoice id.
     *
     * @param array<string, mixed> $header
     * @param array<int, array<string, mixed>> $lines output of totalize()['lines']
     * @param array<string, mixed> $totals subtotal/tax/discount/total
     */
    public function createDraft(array $header, array $lines, array $totals): string
    {
        $id = $header['id'] ?? $this->uuid();
        $stmt = $this->db->prepare(
            "INSERT INTO invoices (id, number, type, status, customer_id, user_id, issue_date, due_date, validity_date, subtotal, tax_amount, discount_amount, total, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $id,
            $header['number'],
            $header['type'] ?? 'invoice',
            $header['status'] ?? 'draft',
            $header['customer_id'] ?? null,
            $header['user_id'] ?? null,
            $header['issue_date'] ?? date('Y-m-d'),
            $header['due_date'] ?? null,
            $header['validity_date'] ?? null,
            $totals['subtotal'],
            $totals['tax'],
            $totals['discount'],
            $totals['total'],
            $header['notes'] ?? null,
        ]);

        foreach ($lines as $line) {
            $this->db->prepare(
                "INSERT INTO invoice_items (id, invoice_id, product_id, description, quantity, unit_price, tax_rate, discount_pct, line_total)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([
                $this->uuid(), $id, $line['product_id'],
                $line['description'], $line['quantity'], $line['unit_price'],
                $line['tax_rate'], $line['discount_pct'], $line['line_total']
            ]);
        }

        return $id;
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
