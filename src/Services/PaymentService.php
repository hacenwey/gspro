<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;

/**
 * Records payments against debts/invoices and keeps derived state
 * (debt status, invoice amount_paid/status, party balance) consistent.
 */
final class PaymentService
{
    public function __construct(private PDO $db) {}

    /**
     * Apply a debt payment inside an already-open transaction.
     * Caller is responsible for begin/commit/rollBack.
     *
     * @param array $debt    row from debts (must contain id, type, total_amount, paid_amount, invoice_id, customer_id, supplier_id)
     * @param float $amount  payment amount (> 0, <= remaining)
     * @param string $method payment method
     * @param string|null $notes
     * @param string $userId actor recording the payment
     * @return string payment id
     */
    public function applyDebtPayment(array $debt, float $amount, string $method, ?string $notes, string $userId): string
    {
        if ($amount <= 0) {
            throw new RuntimeException('Montant invalide.');
        }
        $remaining = (float)$debt['total_amount'] - (float)$debt['paid_amount'];
        // Small epsilon to tolerate decimal rounding from frontend
        if ($amount > $remaining + 0.01) {
            throw new RuntimeException('Montant superieur au restant du.');
        }

        $newPaid = (float)$debt['paid_amount'] + $amount;
        $newStatus = $newPaid + 0.01 >= (float)$debt['total_amount'] ? 'paid' : 'partial';

        $this->db->prepare("UPDATE debts SET paid_amount = ?, status = ? WHERE id = ?")
            ->execute([$newPaid, $newStatus, $debt['id']]);

        $paymentId = $this->uuid();
        $paymentType = $debt['type'] === 'receivable' ? 'incoming' : 'outgoing';

        $this->db->prepare(
            "INSERT INTO payments (id, type, invoice_id, customer_id, supplier_id, amount, method, payment_date, notes, user_id)
             VALUES (?,?,?,?,?,?,?, " . $this->todayExpr() . ", ?, ?)"
        )->execute([
            $paymentId, $paymentType, $debt['invoice_id'] ?? null,
            $debt['customer_id'] ?? null, $debt['supplier_id'] ?? null,
            $amount, $method, $notes, $userId
        ]);

        if (!empty($debt['invoice_id'])) {
            $this->db->prepare(
                "UPDATE invoices
                 SET amount_paid = amount_paid + ?,
                     status = CASE WHEN amount_paid + ? >= total THEN 'paid' ELSE 'partial' END
                 WHERE id = ?"
            )->execute([$amount, $amount, $debt['invoice_id']]);
        }

        if (!empty($debt['customer_id'])) {
            $this->db->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?")
                ->execute([$amount, $debt['customer_id']]);
        }
        if (!empty($debt['supplier_id'])) {
            $this->db->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?")
                ->execute([$amount, $debt['supplier_id']]);
        }

        return $paymentId;
    }

    /**
     * Record a direct sale payment (no debt row yet). Used by POS.
     */
    public function recordSalePayment(string $invoiceId, ?string $customerId, ?string $cashSessionId, float $amount, string $method, string $userId): string
    {
        if ($amount <= 0) {
            throw new RuntimeException('Montant invalide.');
        }
        $paymentId = $this->uuid();
        $this->db->prepare(
            "INSERT INTO payments (id, type, invoice_id, customer_id, cash_session_id, amount, method, payment_date, user_id)
             VALUES (?,?,?,?,?,?,?, " . $this->todayExpr() . ", ?)"
        )->execute([
            $paymentId, 'incoming', $invoiceId, $customerId, $cashSessionId, $amount, $method, $userId
        ]);
        return $paymentId;
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

    private function todayExpr(): string
    {
        // CURDATE() on MySQL, DATE('now') on SQLite
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver === 'sqlite' ? "DATE('now')" : 'CURDATE()';
    }
}
