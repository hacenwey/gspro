<!-- KPI -->
<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="kpi-card">
        <div class="kpi-icon <?= $typeFilter === 'receivable' ? 'orange' : 'red' ?>">
            <i class="fas fa-<?= $typeFilter === 'receivable' ? 'hand-holding-dollar' : 'file-invoice-dollar' ?>"></i>
        </div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('debts.total_due') ?> <?= $typeFilter === 'receivable' ? __('debts.receivable') : __('debts.payable') ?></div>
            <div class="kpi-value"><?= formatMoney($totalDue) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-exclamation-circle"></i></div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('debts.overdue') ?></div>
            <div class="kpi-value"><?= $overdueCount ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-list"></i></div>
        <div class="kpi-info">
            <div class="kpi-label"><?= __('debts.total_lines') ?></div>
            <div class="kpi-value"><?= $total ?></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="<?= url('/debts?type=receivable') ?>" class="tab <?= $typeFilter === 'receivable' ? 'active' : '' ?>"><i class="fas fa-arrow-down" style="color:var(--success);"></i> <?= __('debts.receivable') ?></a>
    <a href="<?= url('/debts?type=payable') ?>" class="tab <?= $typeFilter === 'payable' ? 'active' : '' ?>"><i class="fas fa-arrow-up" style="color:var(--danger);"></i> <?= __('debts.payable') ?></a>
</div>

<div class="toolbar">
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/debts') ?>?type=<?= $typeFilter ?>&status='+this.value">
            <option value=""><?= __('debts.all_statuses') ?></option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>><?= __('status.pending') ?></option>
            <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>><?= __('status.partial') ?></option>
            <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>><?= __('status.overdue') ?></option>
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>><?= __('status.paid') ?></option>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <h4><?= __('debts.no_debts') ?> <?= $typeFilter === 'receivable' ? __('debts.receivable') : __('debts.payable') ?></h4>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?= $typeFilter === 'receivable' ? __('invoices.client') : __('suppliers.title') ?></th>
                    <th><?= __('debts.due_date') ?></th>
                    <th class="text-right"><?= __('debts.amount') ?></th>
                    <th class="text-right"><?= __('debts.paid') ?></th>
                    <th class="text-right"><?= __('debts.remaining') ?></th>
                    <th><?= __('common.status') ?></th>
                    <th class="text-right"><?= __('common.actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $d): ?>
            <tr>
                <td style="font-weight:600;">
                    <?= $typeFilter === 'receivable'
                        ? e(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))
                        : e($d['company_name'] ?? '') ?>
                </td>
                <td>
                    <?= formatDate($d['due_date']) ?>
                    <?php if ($d['status'] === 'overdue'): ?>
                    <div style="font-size:11px;color:var(--danger);">
                        <?php $days = (int)((time() - strtotime($d['due_date'])) / 86400); ?>
                        <?= $days ?> <?= __('debts.days_overdue') ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                <td class="text-right text-mono text-success"><?= formatMoney($d['paid_amount']) ?></td>
                <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
                <td class="text-right">
                    <?php if ($d['status'] !== 'paid'): ?>
                    <button class="btn btn-sm btn-success" onclick="openPayModal('<?= $d['id'] ?>', <?= $d['remaining'] ?>)">
                        <i class="fas fa-money-bill"></i> <?= __('debts.pay') ?>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/debts') ?>?page=<?= $i ?>&type=<?= $typeFilter ?>&status=<?= $statusFilter ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="payDebtModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('debts.record_payment') ?></h3><button class="modal-close" onclick="closeModal('payDebtModal')">&times;</button></div>
        <form method="POST" id="payDebtForm" action="">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('debts.remaining_due') ?></label>
                    <div class="text-mono fw-bold text-danger" style="font-size:20px;" id="debtRemaining"></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('debts.payment_amount') ?> *</label>
                    <input type="number" name="amount" id="debtPayAmount" class="form-control" step="0.01" min="0.01" required style="font-size:18px;font-weight:700;font-family:var(--font-mono);text-align:center;">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('debts.payment_method') ?></label>
                    <select name="method" class="form-control">
                        <option value="cash"><?= __('payments.method.cash') ?></option>
                        <option value="card"><?= __('payments.method.card') ?></option>
                        <option value="bankily"><?= __('payments.method.bankily') ?></option>
                        <option value="masrivi"><?= __('payments.method.masrivi') ?></option>
                        <option value="sedad"><?= __('payments.method.sedad') ?></option>
                        <option value="check"><?= __('payments.method.check') ?></option>
                        <option value="transfer"><?= __('payments.method.transfer') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('common.notes') ?></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('payDebtModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?= __('common.validate') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(debtId, remaining) {
    document.getElementById('payDebtForm').action = '<?= url('/debts/payment/') ?>/' + debtId;
    document.getElementById('debtRemaining').textContent = formatMoney(remaining);
    document.getElementById('debtPayAmount').value = remaining.toFixed(2);
    document.getElementById('debtPayAmount').max = remaining;
    openModal('payDebtModal');
}
</script>
