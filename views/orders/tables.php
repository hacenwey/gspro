<div class="toolbar">
    <div></div>
    <button class="btn btn-primary" onclick="openModal('tableModal')"><i class="fas fa-plus"></i> <?= __('tables.new') ?></button>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($tables)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-chair"></i></div>
            <h4><?= __('tables.none') ?></h4>
            <p><?= __('tables.none_hint') ?></p>
            <button class="btn btn-primary" onclick="openModal('tableModal')"><i class="fas fa-plus"></i> <?= __('tables.new') ?></button>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr>
                <th><?= __('tables.name') ?></th><th><?= __('tables.zone') ?></th>
                <th class="text-center"><?= __('tables.seats') ?></th>
                <th class="text-center"><?= __('common.status') ?></th>
                <th class="text-right"><?= __('common.actions') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($tables as $t): ?>
            <tr style="<?= $t['is_active'] ? '' : 'opacity:.5;' ?>">
                <td style="font-weight:600;"><?= e($t['name']) ?></td>
                <td><?= e($t['zone'] ?? '-') ?></td>
                <td class="text-center"><?= (int)$t['seats'] ?: '-' ?></td>
                <td class="text-center">
                    <span class="badge badge-<?= $t['busy'] ? 'warning' : 'success' ?>"><?= $t['busy'] ? __('tables.busy') : __('tables.free') ?></span>
                </td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content:flex-end;">
                        <button class="btn btn-icon btn-sm btn-secondary"
                                onclick="editTable('<?= e($t['id']) ?>', <?= json_encode($t['name']) ?>, <?= json_encode($t['zone'] ?? '') ?>, <?= (int)$t['seats'] ?>, <?= $t['is_active'] ? 1 : 0 ?>)">
                            <i class="fas fa-pen"></i>
                        </button>
                        <form method="POST" action="<?= url('/tables/delete/' . $t['id']) ?>" style="display:inline;">
                            <?= csrf_field() ?>
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

<div class="modal-overlay" id="tableModal">
    <div class="modal">
        <div class="modal-header"><h3 id="tableModalTitle"><?= __('tables.new') ?></h3><button class="modal-close" onclick="closeModal('tableModal')">&times;</button></div>
        <form method="POST" id="tableForm" action="<?= url('/tables/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('tables.name') ?> *</label>
                    <input type="text" name="name" id="tName" class="form-control" required placeholder="Table 1">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('tables.zone') ?></label>
                    <input type="text" name="zone" id="tZone" class="form-control" placeholder="Terrasse">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('tables.seats') ?></label>
                    <input type="number" name="seats" id="tSeats" class="form-control" min="0" value="0">
                </div>
                <div class="form-group" id="tActiveGroup" style="display:none;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_active" id="tActive" value="1" checked> <?= __('common.active') ?: 'Actif' ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('tableModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= __('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
const T_STORE = <?= json_encode(url('/tables/store')) ?>;
const T_UPDATE = <?= json_encode(url('/tables/update/')) ?>;

function editTable(id, name, zone, seats, active) {
    document.getElementById('tableModalTitle').textContent = name;
    document.getElementById('tableForm').action = T_UPDATE + id;
    document.getElementById('tName').value = name;
    document.getElementById('tZone').value = zone;
    document.getElementById('tSeats').value = seats;
    document.getElementById('tActive').checked = !!active;
    document.getElementById('tActiveGroup').style.display = '';
    openModal('tableModal');
}
</script>
