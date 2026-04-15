<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/clients') ?>" style="color: var(--text-muted); font-size: 13px;"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    <div class="btn-group">
        <a href="<?= url('/clients/edit/' . $client['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> <?= __('common.edit') ?></a>
        <a href="<?= url('/invoices/create?customer_id=' . $client['id']) ?>" class="btn btn-sm btn-primary"><i class="fas fa-file-invoice"></i> <?= __('clients.new_invoice') ?></a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3><?= __('clients.info') ?></h3>
            <span class="badge <?= $client['category'] === 'vip' ? 'badge-vip' : ($client['category'] === 'regular' ? 'badge-primary' : 'badge-secondary') ?>">
                <?= strtoupper($client['category']) ?>
            </span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('clients.full_name') ?></span><span class="value"><?= e(($client['first_name'] ? $client['first_name'] . ' ' : '') . $client['last_name']) ?></span></li>
                <li><span class="label"><?= __('clients.type') ?></span><span class="value"><?= $client['type'] === 'company' ? __('clients.type.company') : __('clients.type.individual') ?></span></li>
                <li><span class="label"><?= __('clients.phone') ?></span><span class="value"><?= e($client['phone'] ?? '-') ?></span></li>
                <li><span class="label"><?= __('clients.email') ?></span><span class="value"><?= e($client['email'] ?? '-') ?></span></li>
                <li><span class="label"><?= __('clients.address') ?></span><span class="value"><?= e($client['address'] ?? '-') ?></span></li>
                <?php if ($client['tax_id']): ?>
                <li><span class="label"><?= __('clients.tax_id') ?></span><span class="value"><?= e($client['tax_id']) ?></span></li>
                <?php endif; ?>
                <li><span class="label"><?= __('clients.loyalty_points') ?></span><span class="value"><?= $client['loyalty_points'] ?></span></li>
                <li><span class="label"><?= __('clients.credit_limit') ?></span><span class="value"><?= formatMoney($client['credit_limit']) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= __('clients.stats') ?></h3></div>
        <div class="card-body">
            <div class="kpi-grid" style="grid-template-columns: 1fr 1fr;">
                <div style="text-align:center;padding:16px;border-radius:var(--radius);background:var(--bg);">
                    <div style="font-size:24px;font-weight:800;font-family:var(--font-mono);color:var(--primary);"><?= formatMoney($stats['total_purchases']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= __('clients.total_purchases') ?></div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:var(--radius);background:var(--bg);">
                    <div style="font-size:24px;font-weight:800;font-family:var(--font-mono);"><?= $stats['invoice_count'] ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= __('clients.invoice_count') ?></div>
                </div>
            </div>
            <div style="text-align:center;padding:20px;margin-top:16px;border-radius:var(--radius);background:<?= $client['balance'] < 0 ? 'rgba(231,76,60,0.08)' : 'rgba(39,174,96,0.08)' ?>;">
                <div style="font-size:13px;color:var(--text-secondary);"><?= __('clients.current_balance') ?></div>
                <div style="font-size:28px;font-weight:800;font-family:var(--font-mono);color:var(--<?= $client['balance'] < 0 ? 'danger' : 'success' ?>);">
                    <?= formatMoney($client['balance']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debts -->
<?php if (!empty($debts)): ?>
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-circle" style="color: var(--danger); margin-right: 8px;"></i><?= __('clients.debts_title') ?></h3>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th><?= __('debts.due_date') ?></th><th class="text-right"><?= __('debts.amount') ?></th><th class="text-right"><?= __('debts.paid') ?></th><th class="text-right"><?= __('debts.remaining') ?></th><th><?= __('common.status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($debts as $d): ?>
            <tr>
                <td><?= formatDate($d['due_date']) ?></td>
                <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                <td class="text-right text-mono"><?= formatMoney($d['paid_amount']) ?></td>
                <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Invoices -->
<div class="card mt-3">
    <div class="card-header"><h3><?= __('dash.recent_invoices') ?></h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($invoices)): ?>
        <div class="empty-state" style="padding:30px;"><p><?= __('dash.no_invoices') ?></p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th><?= __('common.number') ?></th><th><?= __('common.type') ?></th><th><?= __('common.date') ?></th><th class="text-right"><?= __('common.total') ?></th><th><?= __('common.status') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td class="text-mono" style="font-size:12px;"><?= e($inv['number']) ?></td>
                <td><span class="badge badge-<?= $inv['type'] === 'invoice' ? 'primary' : ($inv['type'] === 'quote' ? 'info' : 'warning') ?>"><?= $inv['type'] === 'invoice' ? __('invoices.invoice') : ($inv['type'] === 'quote' ? __('invoices.quote') : __('invoices.credit_note')) ?></span></td>
                <td><?= formatDate($inv['issue_date']) ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($inv['total']) ?></td>
                <td><span class="badge badge-<?= invoiceStatusClass($inv['status']) ?>"><?= invoiceStatusLabel($inv['status']) ?></span></td>
                <td><a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
