<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-arrow-down"></i></div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('payments.total_in') ?></div>
            <div class="kpi-value"><?= formatMoney($totalIn) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-arrow-up"></i></div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('payments.total_out') ?></div>
            <div class="kpi-value"><?= formatMoney($totalOut) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-balance-scale"></i></div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('payments.net_balance') ?></div>
            <div class="kpi-value"><?= formatMoney($totalIn - $totalOut) ?></div>
        </div>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/payments') ?>?type='+this.value+'&method=<?= $methodFilter ?>'">
            <option value=""><?= __('payments.all_types') ?></option>
            <option value="incoming" <?= $typeFilter === 'incoming' ? 'selected' : '' ?>><?= __('payments.incoming') ?></option>
            <option value="outgoing" <?= $typeFilter === 'outgoing' ? 'selected' : '' ?>><?= __('payments.outgoing') ?></option>
        </select>
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/payments') ?>?type=<?= $typeFilter ?>&method='+this.value">
            <option value=""><?= __('payments.all_methods') ?></option>
            <?php foreach (['cash'=>__('payments.method.cash'),'card'=>__('payments.method.card'),'bankily'=>__('payments.method.bankily'),'masrivi'=>__('payments.method.masrivi'),'sedad'=>__('payments.method.sedad'),'check'=>__('payments.method.check'),'transfer'=>__('payments.method.transfer'),'mobile'=>__('payments.method.mobile')] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $methodFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon"><i class="fas fa-money-bill-wave"></i></div><h4><?= __('payments.no_payments') ?></h4></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th><?= __('common.date') ?></th><th><?= __('common.type') ?></th><th><?= __('invoices.invoice') ?></th><th><?= __('debts.party') ?></th><th><?= __('common.method') ?></th><th class="text-right"><?= __('common.amount') ?></th><th><?= __('common.by') ?></th></tr></thead>
                <tbody>
                <?php foreach ($items as $p): ?>
                <tr>
                    <td style="font-size:12px;"><?= formatDate($p['payment_date']) ?></td>
                    <td>
                        <span class="badge badge-<?= $p['type'] === 'incoming' ? 'success' : 'danger' ?>">
                            <?= $p['type'] === 'incoming' ? __('payments.incoming') : __('payments.outgoing') ?>
                        </span>
                    </td>
                    <td class="text-mono" style="font-size:12px;"><?= e($p['invoice_number'] ?? '-') ?></td>
                    <td><?= e($p['client_name'] ?? $p['supplier_name'] ?? '-') ?></td>
                    <td><span class="badge badge-info"><?= $p['method'] ?></span></td>
                    <td class="text-right text-mono fw-bold <?= $p['type'] === 'incoming' ? 'text-success' : 'text-danger' ?>">
                        <?= $p['type'] === 'incoming' ? '+' : '-' ?><?= formatMoney($p['amount']) ?>
                    </td>
                    <td style="font-size:12px;"><?= e($p['user_name'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/payments') ?>?page=<?= $i ?>&type=<?= $typeFilter ?>&method=<?= $methodFilter ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
