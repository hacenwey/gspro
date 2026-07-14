<div class="toolbar">
    <div></div>
    <div class="toolbar-filters">
        <button class="btn btn-secondary" onclick="openModal('catImportModal')" title="Importer depuis Excel"><i class="fas fa-file-excel"></i> Importer</button>
        <button class="btn btn-primary" onclick="openModal('catModal')"><i class="fas fa-plus"></i> <?= __('categories.new') ?></button>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($categories)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <h4><?= __('categories.no_categories') ?></h4>
            <p><?= __('categories.add_desc') ?></p>
            <button class="btn btn-primary" onclick="openModal('catModal')"><i class="fas fa-plus"></i> <?= __('categories.new') ?></button>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th><?= __('categories.name') ?></th><th><?= __('categories.parent') ?></th><th><?= __('common.description') ?></th><th class="text-center"><?= __('common.products_count') ?></th><th class="text-right"><?= __('common.actions') ?></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td style="font-weight: 600;"><?= e($cat['name']) ?></td>
                <td><?= e($cat['parent_name'] ?? '-') ?></td>
                <td style="font-size:12px; color: var(--text-secondary);"><?= e($cat['description'] ?? '-') ?></td>
                <td class="text-center"><span class="badge badge-info"><?= $cat['product_count'] ?></span></td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content: flex-end;">
                        <button class="btn btn-icon btn-sm btn-secondary" onclick="editCat('<?= $cat['id'] ?>', '<?= e($cat['name']) ?>', '<?= $cat['parent_id'] ?? '' ?>', '<?= e($cat['description'] ?? '') ?>')"><i class="fas fa-pen"></i></button>
                        <form method="POST" action="<?= url('/categories/delete/' . $cat['id']) ?>" style="display:inline;">
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

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="catModalTitle"><?= __('categories.new') ?></h3>
            <button class="modal-close" onclick="closeModal('catModal')">&times;</button>
        </div>
        <form method="POST" id="catForm" action="<?= url('/categories/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('categories.name') ?> *</label>
                    <input type="text" name="name" id="catName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('categories.parent') ?></label>
                    <select name="parent_id" id="catParent" class="form-control">
                        <option value=""><?= __('categories.no_parent') ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('common.description') ?></label>
                    <textarea name="description" id="catDesc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('catModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= __('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal-overlay" id="catImportModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-file-excel"></i> Importer des catégories (Excel)</h3>
            <button class="modal-close" onclick="closeModal('catImportModal')">&times;</button>
        </div>
        <form method="POST" action="<?= url('/categories/import') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body">
                <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 12px;">
                    Fichier <strong>.xlsx</strong> avec une ligne d'en-tête. Les catégories sont ajoutées ou mises à jour
                    selon leur <strong>nom</strong>. La colonne <strong>parent</strong> (facultative) référence une autre catégorie par son nom.
                </p>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                    Colonnes : <code>name, parent, description</code>
                </p>
                <a href="<?= url('/categories/import/template') ?>" class="btn btn-secondary btn-sm" style="margin-bottom: 14px;">
                    <i class="fas fa-download"></i> Télécharger le modèle
                </a>
                <div class="form-group">
                    <label class="form-label">Fichier Excel (.xlsx) *</label>
                    <input type="file" name="file" accept=".xlsx" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('catImportModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Importer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(id, name, parentId, desc) {
    document.getElementById('catModalTitle').textContent = '<?= __('categories.edit') ?>';
    document.getElementById('catForm').action = '<?= url('/categories/update/') ?>' + '/' + id;
    document.getElementById('catName').value = name;
    document.getElementById('catParent').value = parentId;
    document.getElementById('catDesc').value = desc;
    openModal('catModal');
}
</script>
