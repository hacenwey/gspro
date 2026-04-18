<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/invoices') ?>" style="color:var(--text-muted);font-size:13px;"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    <div class="btn-group">
        <a href="<?= url('/invoices/pdf/' . $invoice['id']) ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-file-pdf"></i> <?= __('invoices.print') ?></a>
        <a href="<?= url('/invoices/pdf/' . $invoice['id'] . '?download=1') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-download"></i> PDF</a>
        <button type="button" class="btn btn-sm btn-primary" onclick="var p=document.getElementById('email-panel');p.style.display=p.style.display==='none'?'block':'none';"><i class="fas fa-envelope"></i> Email</button>
        <?php if ($invoice['type'] === 'quote' && !in_array($invoice['status'], ['accepted','cancelled'])): ?>
        <form method="POST" action="<?= url('/invoices/convert/' . $invoice['id']) ?>" style="display:inline;"><?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-exchange-alt"></i> <?= __('invoices.convert') ?></button>
        </form>
        <?php endif; ?>
        <?php if ($invoice['status'] === 'draft'): ?>
        <form method="POST" action="<?= url('/invoices/delete/' . $invoice['id']) ?>" style="display:inline;"><?= csrf_field() ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(this.parentNode)"><i class="fas fa-trash"></i> <?= __('common.delete') ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div id="email-panel" class="card mb-3" style="display:none;">
    <div class="card-header">
        <h3><i class="fas fa-paper-plane"></i> Envoyer le document par email</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/invoices/email/' . $invoice['id']) ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Destinataire</label>
                    <input type="email" name="to" class="form-control" required placeholder="client@example.com"
                           value="<?= e($invoice['email'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Envoyer</button>
                </div>
            </div>
            <div class="form-group">
                <label>Message (optionnel)</label>
                <textarea name="message" class="form-control" rows="3" placeholder="Bonjour, veuillez trouver ci-joint le document..."></textarea>
            </div>
            <p style="color:var(--text-muted);font-size:12px;margin:0;">Le PDF sera joint automatiquement.</p>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3><?= $invoice['type'] === 'invoice' ? __('invoices.invoice') : ($invoice['type'] === 'quote' ? __('invoices.quote') : __('invoices.credit_note')) ?> <?= e($invoice['number']) ?></h3>
            <span class="badge badge-<?= invoiceStatusClass($invoice['status']) ?>"><?= invoiceStatusLabel($invoice['status']) ?></span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('invoices.client') ?></span><span class="value"><?= e(($invoice['first_name'] ?? '') . ' ' . ($invoice['last_name'] ?? '')) ?></span></li>
                <li><span class="label"><?= __('invoices.issue_date') ?></span><span class="value"><?= formatDate($invoice['issue_date']) ?></span></li>
                <?php if ($invoice['due_date']): ?><li><span class="label"><?= __('invoices.due_date') ?></span><span class="value"><?= formatDate($invoice['due_date']) ?></span></li><?php endif; ?>
                <?php if ($invoice['validity_date']): ?><li><span class="label"><?= __('invoices.validity_date') ?></span><span class="value"><?= formatDate($invoice['validity_date']) ?></span></li><?php endif; ?>
                <li><span class="label"><?= __('invoices.created_by') ?></span><span class="value"><?= e($invoice['user_name'] ?? '') ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= __('invoices.amounts') ?></h3></div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('invoices.subtotal') ?></span><span class="value"><?= formatMoney($invoice['subtotal']) ?></span></li>
                <li><span class="label"><?= __('invoices.tax') ?></span><span class="value"><?= formatMoney($invoice['tax_amount']) ?></span></li>
                <?php if ($invoice['discount_amount'] > 0): ?><li><span class="label"><?= __('invoices.discount') ?></span><span class="value text-danger">-<?= formatMoney($invoice['discount_amount']) ?></span></li><?php endif; ?>
                <li style="font-size:16px;"><span class="label fw-bold"><?= __('invoices.total_ttc') ?></span><span class="value text-primary" style="font-size:20px;"><?= formatMoney($invoice['total']) ?></span></li>
                <li><span class="label"><?= __('invoices.amount_paid') ?></span><span class="value text-success"><?= formatMoney($invoice['amount_paid']) ?></span></li>
                <li><span class="label fw-bold"><?= __('invoices.remaining') ?></span><span class="value text-mono fw-bold <?= ($invoice['total'] - $invoice['amount_paid']) > 0 ? 'text-danger' : 'text-success' ?>"><?= formatMoney($invoice['total'] - $invoice['amount_paid']) ?></span></li>
            </ul>
        </div>
    </div>
</div>

<!-- Items -->
<div class="card mt-3">
    <div class="card-header"><h3><?= __('invoices.items') ?></h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th><?= __('common.reference') ?></th><th><?= __('common.description') ?></th><th class="text-right"><?= __('common.quantity') ?></th><th class="text-right"><?= __('common.price') ?></th><th class="text-right"><?= __('invoices.tax') ?></th><th class="text-right"><?= __('invoices.total_ttc') ?></th></tr></thead>
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
    <div class="card-header"><h3><?= __('invoices.payments_title') ?></h3></div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th><?= __('common.date') ?></th><th><?= __('common.method') ?></th><th><?= __('common.reference') ?></th><th class="text-right"><?= __('common.amount') ?></th></tr></thead>
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
    <div class="card-header"><h3><?= __('common.notes') ?></h3></div>
    <div class="card-body"><p style="white-space:pre-wrap;"><?= e($invoice['notes']) ?></p></div>
</div>
<?php endif; ?>
