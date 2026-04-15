<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/suppliers') ?>" style="color: var(--text-muted); font-size: 13px;"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    <a href="<?= url('/suppliers/edit/' . $supplier['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> <?= __('common.edit') ?></a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3><?= __('suppliers.info') ?></h3></div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('suppliers.company_name') ?></span><span class="value"><?= e($supplier['company_name']) ?></span></li>
                <li><span class="label"><?= __('suppliers.contact_name') ?></span><span class="value"><?= e($supplier['contact_name'] ?? '-') ?></span></li>
                <li><span class="label"><?= __('suppliers.phone') ?></span><span class="value"><?= e($supplier['phone'] ?? '-') ?></span></li>
                <li><span class="label"><?= __('suppliers.email') ?></span><span class="value"><?= e($supplier['email'] ?? '-') ?></span></li>
                <li><span class="label"><?= __('suppliers.payment_terms_short') ?></span><span class="value"><?= $supplier['payment_terms'] ?> <?= __('suppliers.days') ?></span></li>
                <li><span class="label"><?= __('clients.balance') ?></span><span class="value text-mono fw-bold <?= $supplier['balance'] < 0 ? 'text-danger' : '' ?>"><?= formatMoney($supplier['balance']) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= __('suppliers.debts_title') ?></h3></div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($debts)): ?>
            <div class="empty-state" style="padding:30px;"><p><?= __('suppliers.no_debts') ?></p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th><?= __('debts.due_date') ?></th><th class="text-right"><?= __('debts.amount') ?></th><th class="text-right"><?= __('debts.remaining') ?></th><th><?= __('common.status') ?></th></tr></thead>
                <tbody>
                <?php foreach ($debts as $d): ?>
                <tr>
                    <td><?= formatDate($d['due_date']) ?></td>
                    <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                    <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                    <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3><?= __('suppliers.orders') ?></h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($orders)): ?>
        <div class="empty-state" style="padding:30px;"><p><?= __('suppliers.no_orders') ?></p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th><?= __('common.number') ?></th><th><?= __('common.date') ?></th><th class="text-right"><?= __('common.total') ?></th><th><?= __('common.status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="text-mono"><?= e($o['number']) ?></td>
                <td><?= formatDate($o['order_date']) ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($o['total']) ?></td>
                <td><span class="badge badge-<?= $o['status'] === 'received' ? 'success' : ($o['status'] === 'sent' ? 'info' : 'secondary') ?>"><?= $o['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
