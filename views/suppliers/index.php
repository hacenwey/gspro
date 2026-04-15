<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/suppliers') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="<?= __('suppliers.search') ?>" value="<?= e($search) ?>">
        </form>
    </div>
    <a href="<?= url('/suppliers/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('suppliers.new') ?></a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-truck"></i></div>
            <h4><?= __('suppliers.no_suppliers') ?></h4>
            <a href="<?= url('/suppliers/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('suppliers.new') ?></a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th><?= __('suppliers.company_name') ?></th><th><?= __('suppliers.contact_name') ?></th><th><?= __('suppliers.phone') ?></th><th><?= __('suppliers.payment_terms_short') ?></th><th class="text-right"><?= __('clients.balance') ?></th><th class="text-right"><?= __('common.actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($items as $s): ?>
            <tr>
                <td><a href="<?= url('/suppliers/view/' . $s['id']) ?>" style="font-weight:600;color:var(--text);"><?= e($s['company_name']) ?></a></td>
                <td><?= e($s['contact_name'] ?? '-') ?></td>
                <td><?= e($s['phone'] ?? '-') ?></td>
                <td><?= $s['payment_terms'] ?> <?= __('suppliers.days') ?></td>
                <td class="text-right text-mono fw-bold <?= $s['balance'] < 0 ? 'text-danger' : '' ?>"><?= formatMoney($s['balance']) ?></td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content: flex-end;">
                        <a href="<?= url('/suppliers/view/' . $s['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                        <a href="<?= url('/suppliers/edit/' . $s['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="<?= url('/suppliers/delete/' . $s['id']) ?>" style="display:inline;"><?= csrf_field() ?>
                            <button type="button" class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete(this.parentNode, '<?= __('common.confirm_delete') ?>')"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
