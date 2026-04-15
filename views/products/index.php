<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/products') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="Rechercher produit, reference, code-barres..." value="<?= e($search) ?>">
            <?php if ($categoryFilter): ?><input type="hidden" name="category" value="<?= e($categoryFilter) ?>"><?php endif; ?>
            <?php if ($stockFilter): ?><input type="hidden" name="stock" value="<?= e($stockFilter) ?>"><?php endif; ?>
        </form>
    </div>
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/products') ?>?search=<?= urlencode($search) ?>&stock=<?= urlencode($stockFilter) ?>&category='+this.value">
            <option value="">Toutes categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoryFilter === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/products') ?>?search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&stock='+this.value">
            <option value="">Tout le stock</option>
            <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Stock bas</option>
            <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Rupture</option>
        </select>
        <a href="<?= url('/products/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nouveau produit
        </a>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-box-open"></i></div>
            <h4>Aucun produit</h4>
            <p>Commencez par ajouter votre premier produit.</p>
            <a href="<?= url('/products/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau produit</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Designation</th>
                        <th>Categorie</th>
                        <th class="text-right">P. Achat</th>
                        <th class="text-right">P. Vente</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Statut</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $p): ?>
                    <tr>
                        <td class="text-mono" style="font-size:12px;"><?= e($p['reference']) ?></td>
                        <td>
                            <a href="<?= url('/products/view/' . $p['id']) ?>" style="font-weight: 600; color: var(--text);">
                                <?= e($p['name']) ?>
                            </a>
                            <?php if ($p['barcode']): ?>
                            <div style="font-size:11px; color: var(--text-muted);"><i class="fas fa-barcode"></i> <?= e($p['barcode']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-info"><?= e($p['category_name'] ?? 'N/A') ?></span></td>
                        <td class="text-right text-mono"><?= formatMoney($p['purchase_price']) ?></td>
                        <td class="text-right text-mono fw-bold"><?= formatMoney($p['selling_price']) ?></td>
                        <td class="text-center text-mono fw-bold"><?= $p['current_stock'] ?> <?= e($p['unit']) ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?= stockStatusClass($p['current_stock'], $p['min_stock']) ?>">
                                <?= stockStatusLabel($p['current_stock'], $p['min_stock']) ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="btn-group" style="justify-content: flex-end;">
                                <a href="<?= url('/products/view/' . $p['id']) ?>" class="btn btn-icon btn-sm btn-secondary" title="Voir"><i class="fas fa-eye"></i></a>
                                <a href="<?= url('/products/edit/' . $p['id']) ?>" class="btn btn-icon btn-sm btn-secondary" title="Modifier"><i class="fas fa-pen"></i></a>
                                <form method="POST" action="<?= url('/products/delete/' . $p['id']) ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="button" class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete(this.parentNode)" title="Supprimer"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer">
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= url('/products') ?>?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&stock=<?= urlencode($stockFilter) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top: 12px; color: var(--text-muted); font-size: 13px;">
    Total: <?= $total ?> produit(s)
</div>
