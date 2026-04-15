<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/invoices') ?>" style="color:var(--text-muted);font-size:13px;"><i class="fas fa-arrow-left"></i> Retour</a>
    <div class="btn-group">
        <a href="<?= url('/invoices/pdf/' . $invoice['id']) ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-file-pdf"></i> PDF</a>
        <?php if ($invoice['type'] === 'quote' && !in_array($invoice['status'], ['accepted','cancelled'])): ?>
        <form method="POST" action="<?= url('/invoices/convert/' . $invoice['id']) ?>" style="display:inline;"><?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-exchange-alt"></i> Convertir en facture</button>
        </form>
        <?php endif; ?>
        <?php if ($invoice['status'] === 'draft'): ?>
        <form method="POST" action="<?= url('/invoices/delete/' . $invoice['id']) ?>" style="display:inline;"><?= csrf_field() ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(this.parentNode)"><i class="fas fa-trash"></i> Supprimer</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3><?= $invoice['type'] === 'invoice' ? 'Facture' : ($invoice['type'] === 'quote' ? 'Devis' : 'Avoir') ?> <?= e($invoice['number']) ?></h3>
            <span class="badge badge-<?= invoiceStatusClass($invoice['status']) ?>"><?= invoiceStatusLabel($invoice['status']) ?></span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label">Client</span><span class="value"><?= e(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')) ?></span></li>
                <li><span class="label">Date emission</span><span class="value"><?= formatDate($invoice['issue_date']) ?></span></li>
                <?php if ($invoice['due_date']): ?><li><span class="label">Echeance</span><span class="value"><?= formatDate($invoice['due_date']) ?></span></li><?php endif; ?>
                <?php if ($invoice['validity_date']): ?><li><span class="label">Validite</span><span class="value"><?= formatDate($invoice['validity_date']) ?></span></li><?php endif; ?>
                <li><span class="label">Cree par</span><span class="value"><?= e($invoice['user_name'] ?? '') ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Montants</h3></div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label">Sous-total HT</span><span class="value"><?= formatMoney($invoice['subtotal']) ?></span></li>
                <li><span class="label">TVA</span><span class="value"><?= formatMoney($invoice['tax_amount']) ?></span></li>
                <?php if ($invoice['discount_amount'] > 0): ?><li><span class="label">Remise</span><span class="value text-danger">-<?= formatMoney($invoice['discount_amount']) ?></span></li><?php endif; ?>
                <li style="font-size:16px;"><span class="label fw-bold">Total TTC</span><span class="value text-primary" style="font-size:20px;"><?= formatMoney($invoice['total']) ?></span></li>
                <li><span class="label">Montant paye</span><span class="value text-success"><?= formatMoney($invoice['amount_paid']) ?></span></li>
                <li><span class="label fw-bold">Reste du</span><span class="value text-mono fw-bold <?= ($invoice['total'] - $invoice['amount_paid']) > 0 ? 'text-danger' : 'text-success' ?>"><?= formatMoney($invoice['total'] - $invoice['amount_paid']) ?></span></li>
            </ul>
        </div>
    </div>
</div>

<!-- Items -->
<div class="card mt-3">
    <div class="card-header"><h3>Lignes</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Ref</th><th>Description</th><th class="text-right">Qte</th><th class="text-right">P.U. HT</th><th class="text-right">TVA</th><th class="text-right">Total TTC</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="text-mono" style="font-size:12px;"><?= e($item['product_ref'] ?? '-') ?></td>
                <td><?= e($item['description']) ?></td>
                <td class="text-right text-mono"><?= $item['quantity'] ?></td>
                <td class="text-right text-mono"><?= formatMoney($item['unit_price']) ?></td>
                <td class="text-right"><?= $item['tax_rate'] ?>%</td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($item['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payments -->
<?php if (!empty($payments)): ?>
<div class="card mt-3">
    <div class="card-header"><h3>Paiements</h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Date</th><th>Methode</th><th>Reference</th><th class="text-right">Montant</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= formatDate($p['payment_date']) ?></td>
                <td><span class="badge badge-info"><?= $p['method'] ?></span></td>
                <td style="font-size:12px;"><?= e($p['reference'] ?? '-') ?></td>
                <td class="text-right text-mono fw-bold text-success"><?= formatMoney($p['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($invoice['notes']): ?>
<div class="card mt-3">
    <div class="card-header"><h3>Notes</h3></div>
    <div class="card-body"><p style="white-space:pre-wrap;"><?= e($invoice['notes']) ?></p></div>
</div>
<?php endif; ?>
