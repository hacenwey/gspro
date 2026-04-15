<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/caisse') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($sessions)): ?>
        <div class="empty-state"><div class="icon"><i class="fas fa-cash-register"></i></div><p><?= __('pos.no_sessions') ?></p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th><?= __('pos.cashier') ?></th><th><?= __('pos.opened') ?></th><th><?= __('pos.closed') ?></th><th class="text-right"><?= __('pos.initial_balance') ?></th><th class="text-right"><?= __('pos.closing_amount') ?></th><th class="text-right"><?= __('pos.difference') ?></th><th><?= __('common.status') ?></th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?= e($s['full_name']) ?></td>
                <td style="font-size:12px;"><?= formatDateTime($s['opened_at']) ?></td>
                <td style="font-size:12px;"><?= $s['closed_at'] ? formatDateTime($s['closed_at']) : '-' ?></td>
                <td class="text-right text-mono"><?= formatMoney($s['opening_balance']) ?></td>
                <td class="text-right text-mono"><?= $s['closing_balance'] !== null ? formatMoney($s['closing_balance']) : '-' ?></td>
                <td class="text-right text-mono fw-bold <?= ($s['difference'] ?? 0) < 0 ? 'text-danger' : (($s['difference'] ?? 0) > 0 ? 'text-success' : '') ?>">
                    <?= $s['difference'] !== null ? formatMoney($s['difference']) : '-' ?>
                </td>
                <td><span class="badge badge-<?= $s['status'] === 'open' ? 'success' : 'secondary' ?>"><?= $s['status'] === 'open' ? __('pos.open_status') : __('pos.closed_status') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
