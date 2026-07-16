<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/invoices') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="<?= __('invoices.search') ?>" value="<?= e($search) ?>">
            <?php if ($typeFilter): ?><input type="hidden" name="type" value="<?= e($typeFilter) ?>"><?php endif; ?>
        </form>
    </div>
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/invoices') ?>?type='+this.value+'&search=<?= urlencode($search) ?>'">
            <option value=""><?= __('invoices.all_types') ?></option>
            <option value="invoice" <?= $typeFilter === 'invoice' ? 'selected' : '' ?>><?= __('invoices.invoice') ?></option>
            <option value="quote" <?= $typeFilter === 'quote' ? 'selected' : '' ?>><?= __('invoices.quote') ?></option>
            <option value="credit_note" <?= $typeFilter === 'credit_note' ? 'selected' : '' ?>><?= __('invoices.credit_note') ?></option>
        </select>
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/invoices') ?>?type=<?= urlencode($typeFilter) ?>&status='+this.value+'&search=<?= urlencode($search) ?>'">
            <option value=""><?= __('invoices.all_statuses') ?></option>
            <?php foreach (['draft','sent','partial','paid','overdue','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= invoiceStatusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="btn-group">
            <a href="<?= url('/export/sales') ?>" class="btn btn-secondary" title="Export CSV"><i class="fas fa-file-csv"></i> CSV</a>
            <a href="<?= url('/invoices/create?type=quote') ?>" class="btn btn-secondary"><i class="fas fa-plus"></i> <?= __('invoices.new_quote') ?></a>
            <a href="<?= url('/invoices/create?type=invoice') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('invoices.new_invoice') ?></a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
            <h4><?= __('invoices.no_documents') ?></h4>
            <div class="btn-group" style="justify-content:center;">
                <a href="<?= url('/invoices/create?type=quote') ?>" class="btn btn-secondary"><i class="fas fa-plus"></i> <?= __('invoices.new_quote') ?></a>
                <a href="<?= url('/invoices/create?type=invoice') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('invoices.new_invoice') ?></a>
            </div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th><?= __('common.number') ?></th><th><?= __('common.type') ?></th><th><?= __('invoices.client') ?></th><th><?= __('common.date') ?></th><th class="text-right"><?= __('common.total') ?></th><th class="text-right"><?= __('debts.paid') ?></th><th><?= __('common.status') ?></th><th class="text-right"><?= __('common.actions') ?></th></tr></thead>
                <tbody>
                <?php foreach ($items as $inv): ?>
                <tr>
                    <td><a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="text-mono" style="font-size:12px;font-weight:600;"><?= e($inv['number']) ?></a></td>
                    <td><span class="badge badge-<?= $inv['type'] === 'invoice' ? 'primary' : ($inv['type'] === 'quote' ? 'info' : 'warning') ?>"><?= $inv['type'] === 'invoice' ? __('invoices.invoice') : ($inv['type'] === 'quote' ? __('invoices.quote') : __('invoices.credit_note')) ?></span></td>
                    <td><?= e(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? '')) ?></td>
                    <td style="font-size:12px;"><?= formatDate($inv['issue_date']) ?></td>
                    <td class="text-right text-mono fw-bold"><?= formatMoney($inv['total']) ?></td>
                    <td class="text-right text-mono"><?= formatMoney($inv['amount_paid']) ?></td>
                    <td><span class="badge badge-<?= invoiceStatusClass($inv['status']) ?>"><?= invoiceStatusLabel($inv['status']) ?></span></td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content:flex-end;">
                            <a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('/invoices/pdf/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary" target="_blank"><i class="fas fa-file-pdf"></i></a>
                            <?php if ($inv['type'] === 'quote' && $inv['status'] !== 'accepted'): ?>
                            <form method="POST" action="<?= url('/invoices/convert/' . $inv['id']) ?>" style="display:inline;"><?= csrf_field() ?>
                                <button type="submit" class="btn btn-icon btn-sm btn-success" title="<?= __('invoices.convert') ?>"><i class="fas fa-exchange-alt"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/invoices') ?>?page=<?= $i ?>&type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
