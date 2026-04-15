<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/debts?type=' . $debt['type']) ?>" style="color:var(--text-muted);font-size:13px;"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3><?= $debt['type'] === 'receivable' ? __('debts.client_debt') : __('debts.supplier_debt') ?></h3>
            <span class="badge badge-<?= debtStatusClass($debt['status']) ?>"><?= $debt['status'] ?></span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('debts.party') ?></span><span class="value"><?= $debt['type'] === 'receivable' ? e(($debt['first_name']??'').' '.$debt['last_name']) : e($debt['company_name']??'') ?></span></li>
                <li><span class="label"><?= __('debts.due_date') ?></span><span class="value"><?= formatDate($debt['due_date']) ?></span></li>
                <li><span class="label"><?= __('debts.amount') ?></span><span class="value text-mono"><?= formatMoney($debt['total_amount']) ?></span></li>
                <li><span class="label"><?= __('debts.paid') ?></span><span class="value text-mono text-success"><?= formatMoney($debt['paid_amount']) ?></span></li>
                <li><span class="label fw-bold"><?= __('debts.remaining') ?></span><span class="value text-mono fw-bold text-danger" style="font-size:18px;"><?= formatMoney($debt['remaining']) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3><?= __('debts.payment_history') ?></h3></div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($payments)): ?>
            <div class="empty-state" style="padding:30px;"><p><?= __('debts.no_payments') ?></p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th><?= __('common.date') ?></th><th><?= __('common.method') ?></th><th class="text-right"><?= __('common.amount') ?></th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= formatDate($p['payment_date']) ?></td>
                    <td><span class="badge badge-info"><?= $p['method'] ?></span></td>
                    <td class="text-right text-mono fw-bold text-success"><?= formatMoney($p['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
