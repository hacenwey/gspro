<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $invoice['type'] === 'invoice' ? 'Facture' : ($invoice['type'] === 'quote' ? 'Devis' : 'Avoir') ?> <?= e($invoice['number']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #333; padding: 40px; max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 3px solid #1B4F72; }
        .company { font-size: 20px; font-weight: 700; color: #1B4F72; }
        .company-info { font-size: 12px; color: #666; margin-top: 4px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 28px; color: #1B4F72; text-transform: uppercase; }
        .doc-title .number { font-size: 16px; color: #666; margin-top: 4px; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .party { width: 48%; }
        .party-label { font-size: 11px; text-transform: uppercase; color: #999; margin-bottom: 6px; font-weight: 600; }
        .party-name { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .party-detail { font-size: 12px; color: #666; line-height: 1.6; }
        .meta { display: flex; gap: 30px; margin-bottom: 24px; padding: 12px 16px; background: #f8f9fa; border-radius: 6px; }
        .meta-item { font-size: 12px; }
        .meta-label { color: #999; }
        .meta-value { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead th { background: #1B4F72; color: #fff; padding: 10px 12px; font-size: 11px; text-transform: uppercase; text-align: left; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 12px; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-mono { font-family: 'Courier New', monospace; }
        .totals { width: 300px; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .total-row.grand { font-size: 18px; font-weight: 700; color: #1B4F72; border-top: 2px solid #1B4F72; padding-top: 10px; margin-top: 6px; }
        .notes { margin-top: 30px; padding: 16px; background: #f8f9fa; border-radius: 6px; font-size: 12px; }
        .footer { margin-top: 40px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 16px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-draft { background: #e2e3e5; color: #383d41; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        @media print { body { padding: 20px; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px;text-align:right;">
        <button onclick="window.print()" style="padding:10px 20px;background:#1B4F72;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Imprimer / PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="company"><?= e($settings['company_name'] ?? 'Mon Entreprise') ?></div>
            <div class="company-info">
                <?php if (!empty($settings['company_address'])): ?><?= e($settings['company_address']) ?><br><?php endif; ?>
                <?php if (!empty($settings['company_phone'])): ?>Tel: <?= e($settings['company_phone']) ?><br><?php endif; ?>
                <?php if (!empty($settings['company_email'])): ?><?= e($settings['company_email']) ?><br><?php endif; ?>
                <?php if (!empty($settings['company_tax_id'])): ?>NIF: <?= e($settings['company_tax_id']) ?><?php endif; ?>
            </div>
        </div>
        <div class="doc-title">
            <h1><?= $invoice['type'] === 'invoice' ? 'Facture' : ($invoice['type'] === 'quote' ? 'Devis' : 'Avoir') ?></h1>
            <div class="number"><?= e($invoice['number']) ?></div>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <div class="party-label">Client</div>
            <div class="party-name"><?= e(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')) ?></div>
            <div class="party-detail">
                <?php if ($invoice['address']): ?><?= e($invoice['address']) ?><br><?php endif; ?>
                <?php if ($invoice['phone']): ?>Tel: <?= e($invoice['phone']) ?><br><?php endif; ?>
                <?php if ($invoice['email']): ?><?= e($invoice['email']) ?><br><?php endif; ?>
                <?php if ($invoice['client_tax_id']): ?>NIF: <?= e($invoice['client_tax_id']) ?><?php endif; ?>
            </div>
        </div>
        <div class="party" style="text-align:right;">
            <div class="party-label">Details</div>
            <div class="party-detail">
                Date: <?= formatDate($invoice['issue_date']) ?><br>
                <?php if ($invoice['due_date']): ?>Echeance: <?= formatDate($invoice['due_date']) ?><br><?php endif; ?>
                <?php if ($invoice['validity_date']): ?>Validite: <?= formatDate($invoice['validity_date']) ?><br><?php endif; ?>
                <span class="status status-<?= $invoice['status'] ?>"><?= invoiceStatusLabel($invoice['status']) ?></span>
            </div>
        </div>
    </div>

    <table>
        <thead><tr><th>Description</th><th class="text-right">Qte</th><th class="text-right">P.U. HT</th><th class="text-right">TVA</th><th class="text-right">Total TTC</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= e($item['description']) ?></td>
            <td class="text-right text-mono"><?= $item['quantity'] ?></td>
            <td class="text-right text-mono"><?= number_format($item['unit_price'], 2, ',', ' ') ?></td>
            <td class="text-right"><?= $item['tax_rate'] ?>%</td>
            <td class="text-right text-mono" style="font-weight:600;"><?= number_format($item['line_total'], 2, ',', ' ') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row"><span>Sous-total HT</span><span class="text-mono"><?= number_format($invoice['subtotal'], 2, ',', ' ') ?> DA</span></div>
        <div class="total-row"><span>TVA</span><span class="text-mono"><?= number_format($invoice['tax_amount'], 2, ',', ' ') ?> DA</span></div>
        <?php if ($invoice['discount_amount'] > 0): ?>
        <div class="total-row"><span>Remise</span><span class="text-mono" style="color:#c00;">-<?= number_format($invoice['discount_amount'], 2, ',', ' ') ?> DA</span></div>
        <?php endif; ?>
        <div class="total-row grand"><span>Total TTC</span><span class="text-mono"><?= number_format($invoice['total'], 2, ',', ' ') ?> DA</span></div>
    </div>

    <?php if ($invoice['notes']): ?>
    <div class="notes"><strong>Notes:</strong> <?= e($invoice['notes']) ?></div>
    <?php endif; ?>

    <div class="footer">
        Document genere par GestionPro | <?= e($settings['company_name'] ?? '') ?>
    </div>
</body>
</html>
