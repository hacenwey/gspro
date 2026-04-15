<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?= $product ? 'Modifier le produit' : 'Nouveau produit' ?></h3>
        <a href="<?= url('/products') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url($product ? '/products/update/' . $product['id'] : '/products/store') ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Reference *</label>
                    <input type="text" name="reference" class="form-control" value="<?= e($product['reference'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Code-barres</label>
                    <input type="text" name="barcode" class="form-control" value="<?= e($product['barcode'] ?? '') ?>" placeholder="EAN-13, Code128...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Designation *</label>
                <input type="text" name="name" class="form-control" value="<?= e($product['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Categorie</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Unite de mesure</label>
                    <select name="unit" class="form-control">
                        <?php foreach (['piece' => 'Piece', 'kg' => 'Kilogramme', 'litre' => 'Litre', 'metre' => 'Metre', 'carton' => 'Carton', 'paquet' => 'Paquet'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($product['unit'] ?? 'piece') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prix d'achat HT *</label>
                    <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="<?= $product['purchase_price'] ?? '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Prix de vente HT *</label>
                    <input type="number" name="selling_price" class="form-control" step="0.01" min="0" value="<?= $product['selling_price'] ?? '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">TVA (%)</label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" value="<?= $product['tax_rate'] ?? '19.00' ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Seuil stock minimum</label>
                    <input type="number" name="min_stock" class="form-control" min="0" value="<?= $product['min_stock'] ?? '0' ?>">
                </div>
                <?php if (!$product): ?>
                <div class="form-group">
                    <label class="form-label">Stock initial</label>
                    <input type="number" name="current_stock" class="form-control" min="0" value="0">
                </div>
                <?php endif; ?>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $product ? 'Mettre a jour' : 'Enregistrer' ?></button>
                <a href="<?= url('/products') ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
