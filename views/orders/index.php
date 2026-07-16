<?php
/** Active service board: open tickets first, plus the floor at a glance. */
$statusClass = [
    'open' => 'secondary', 'sent' => 'info', 'preparing' => 'warning',
    'ready' => 'success', 'served' => 'primary',
];
?>
<div class="toolbar">
    <div></div>
    <button class="btn btn-primary" onclick="openModal('newOrderModal')"><i class="fas fa-plus"></i> <?= __('orders.new') ?></button>
</div>

<?php if (!empty($tables)): ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="floor-grid">
            <?php foreach ($tables as $t): ?>
            <?php $busy = !empty($t['order_id']); ?>
            <a class="floor-tile <?= $busy ? 'busy' : '' ?>"
               href="<?= $busy ? url('/orders/view/' . $t['order_id']) : '#' ?>"
               <?= $busy ? '' : 'onclick="prefillTable(\'' . e($t['id']) . '\');return false;"' ?>>
                <div class="ft-name"><?= e($t['name']) ?></div>
                <div class="ft-state"><?= $busy ? __('tables.busy') : __('tables.free') ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-receipt"></i></div>
            <h4><?= __('orders.none') ?></h4>
            <p><?= __('orders.none_hint') ?></p>
            <button class="btn btn-primary" onclick="openModal('newOrderModal')"><i class="fas fa-plus"></i> <?= __('orders.new') ?></button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table">
            <thead><tr>
                <th>#</th><th><?= __('orders.type') ?></th><th><?= __('orders.table') ?></th>
                <th><?= __('orders.waiter') ?></th><th class="text-center"><?= __('orders.items') ?></th>
                <th class="text-right"><?= __('invoices.total_ttc') ?></th>
                <th class="text-center"><?= __('common.status') ?></th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="text-mono" style="font-size:12px;"><?= e($o['number']) ?></td>
                <td><?= __('orders.type.' . $o['type']) ?></td>
                <td><?= e($o['table_name'] ?? '-') ?></td>
                <td style="font-size:12px;color:var(--text-secondary);"><?= e($o['waiter'] ?? '-') ?></td>
                <td class="text-center"><?= (int)$o['item_count'] ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($o['total']) ?></td>
                <td class="text-center">
                    <span class="badge badge-<?= $statusClass[$o['status']] ?? 'secondary' ?>"><?= __('orders.status.' . $o['status']) ?></span>
                </td>
                <td class="text-right">
                    <a href="<?= url('/orders/view/' . $o['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- New order -->
<div class="modal-overlay" id="newOrderModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('orders.new') ?></h3><button class="modal-close" onclick="closeModal('newOrderModal')">&times;</button></div>
        <form method="POST" action="<?= url('/orders/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('orders.type') ?></label>
                    <select name="type" id="orderType" class="form-control" onchange="toggleTable()">
                        <option value="dine_in"><?= __('orders.type.dine_in') ?></option>
                        <option value="takeaway"><?= __('orders.type.takeaway') ?></option>
                        <option value="delivery"><?= __('orders.type.delivery') ?></option>
                    </select>
                </div>
                <div class="form-group" id="tableGroup">
                    <label class="form-label"><?= __('orders.table') ?></label>
                    <select name="table_id" id="tableSelect" class="form-control">
                        <option value=""><?= __('orders.no_table') ?></option>
                        <?php foreach ($tables as $t): if (!empty($t['order_id'])) continue; ?>
                        <option value="<?= e($t['id']) ?>"><?= e($t['name']) ?><?= $t['zone'] ? ' — ' . e($t['zone']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('newOrderModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> <?= __('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTable() {
    const isDineIn = document.getElementById('orderType').value === 'dine_in';
    document.getElementById('tableGroup').style.display = isDineIn ? '' : 'none';
}
// Tapping a free table opens the new-order dialog with it preselected.
function prefillTable(id) {
    document.getElementById('orderType').value = 'dine_in';
    toggleTable();
    document.getElementById('tableSelect').value = id;
    openModal('newOrderModal');
}
toggleTable();
</script>
