<div class="toolbar">
    <div></div>
    <button class="btn btn-primary" onclick="openModal('catModal')"><i class="fas fa-plus"></i> Nouvelle categorie</button>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($categories)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <h4>Aucune categorie</h4>
            <p>Ajoutez des categories pour organiser vos produits.</p>
            <button class="btn btn-primary" onclick="openModal('catModal')"><i class="fas fa-plus"></i> Nouvelle categorie</button>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Nom</th><th>Parente</th><th>Description</th><th class="text-center">Produits</th><th class="text-right">Actions</th></tr></thead>
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
                            <button type="button" class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete(this.parentNode)"><i class="fas fa-trash"></i></button>
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
            <h3 id="catModalTitle">Nouvelle categorie</h3>
            <button class="modal-close" onclick="closeModal('catModal')">&times;</button>
        </div>
        <form method="POST" id="catForm" action="<?= url('/categories/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nom *</label>
                    <input type="text" name="name" id="catName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Categorie parente</label>
                    <select name="parent_id" id="catParent" class="form-control">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="catDesc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('catModal')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCat(id, name, parentId, desc) {
    document.getElementById('catModalTitle').textContent = 'Modifier categorie';
    document.getElementById('catForm').action = '<?= url('/categories/update/') ?>' + '/' + id;
    document.getElementById('catName').value = name;
    document.getElementById('catParent').value = parentId;
    document.getElementById('catDesc').value = desc;
    openModal('catModal');
}
</script>
