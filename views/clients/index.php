<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/clients') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="<?= __('clients.search') ?>" value="<?= e($search) ?>">
        </form>
    </div>
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/clients') ?>?search=<?= urlencode($search) ?>&category='+this.value">
            <option value=""><?= __('clients.all_categories') ?></option>
            <option value="vip" <?= $categoryFilter === 'vip' ? 'selected' : '' ?>><?= __('clients.cat.vip') ?></option>
            <option value="regular" <?= $categoryFilter === 'regular' ? 'selected' : '' ?>><?= __('clients.cat.regular') ?></option>
            <option value="occasional" <?= $categoryFilter === 'occasional' ? 'selected' : '' ?>><?= __('clients.cat.occasional') ?></option>
        </select>
        <a href="<?= url('/clients/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('clients.new') ?></a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-users"></i></div>
            <h4><?= __('clients.no_clients') ?></h4>
            <p><?= __('clients.start_add') ?></p>
            <a href="<?= url('/clients/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('clients.new') ?></a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th><?= __('clients.title') ?></th><th><?= __('clients.phone') ?></th><th><?= __('clients.email') ?></th><th><?= __('clients.category') ?></th><th class="text-right"><?= __('clients.balance') ?></th><th class="text-right"><?= __('common.actions') ?></th></tr></thead>
                <tbody>
                <?php foreach ($items as $c): ?>
                <tr>
                    <td>
                        <div class="d-flex align-center gap-1">
                            <div style="width:36px;height:36px;border-radius:var(--radius);background:<?= $c['category'] === 'vip' ? 'linear-gradient(135deg,#F59E0B,#D97706)' : ($c['category'] === 'regular' ? 'var(--primary)' : 'var(--text-muted)') ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px;flex-shrink:0;">
                                <?= strtoupper(substr($c['last_name'], 0, 2)) ?>
                            </div>
                            <div>
                                <a href="<?= url('/clients/view/' . $c['id']) ?>" style="font-weight:600;color:var(--text);">
                                    <?= e(($c['first_name'] ? $c['first_name'] . ' ' : '') . $c['last_name']) ?>
                                </a>
                                <div style="font-size:11px;color:var(--text-muted);"><?= $c['type'] === 'company' ? __('clients.type.company') : __('clients.type.individual') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($c['phone'] ?? '-') ?></td>
                    <td style="font-size:12px;"><?= e($c['email'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= $c['category'] === 'vip' ? 'badge-vip' : ($c['category'] === 'regular' ? 'badge-primary' : 'badge-secondary') ?>">
                            <?= strtoupper($c['category']) ?>
                        </span>
                    </td>
                    <td class="text-right text-mono fw-bold <?= $c['balance'] < 0 ? 'text-danger' : '' ?>">
                        <?= formatMoney($c['balance']) ?>
                    </td>
                    <td class="text-right">
                        <div class="btn-group" style="justify-content: flex-end;">
                            <a href="<?= url('/clients/view/' . $c['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                            <a href="<?= url('/clients/edit/' . $c['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
                            <form method="POST" action="<?= url('/clients/delete/' . $c['id']) ?>" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="button" class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete(this.parentNode, '<?= __('common.confirm_delete') ?>')"><i class="fas fa-trash"></i></button>
                            </form>
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
            <a href="<?= url('/clients') ?>?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
