<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?= $product ? __('products.edit') : __('products.new') ?></h3>
        <a href="<?= url('/products') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url($product ? '/products/update/' . $product['id'] : '/products/store') ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('products.reference') ?> *</label>
                    <input type="text" name="reference" class="form-control" value="<?= e($product['reference'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.barcode') ?></label>
                    <input type="text" name="barcode" class="form-control" value="<?= e($product['barcode'] ?? '') ?>" placeholder="EAN-13, Code128...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('products.designation') ?> *</label>
                <input type="text" name="name" class="form-control" value="<?= e($product['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('products.description') ?></label>
                <textarea name="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('products.category') ?></label>
                    <select name="category_id" class="form-control">
                        <option value=""><?= __('products.no_category') ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.unit') ?></label>
                    <select name="unit" class="form-control">
                        <?php foreach (['piece' => __('products.units.piece'), 'kg' => __('products.units.kg'), 'litre' => __('products.units.litre'), 'metre' => __('products.units.metre'), 'carton' => __('products.units.carton'), 'paquet' => __('products.units.paquet')] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($product['unit'] ?? 'piece') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('products.purchase_price') ?> *</label>
                    <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="<?= $product['purchase_price'] ?? '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.selling_price') ?> *</label>
                    <input type="number" name="selling_price" class="form-control" step="0.01" min="0" value="<?= $product['selling_price'] ?? '0.00' ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.tax_rate') ?></label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" value="<?= $product['tax_rate'] ?? TAX_RATE_DEFAULT ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('products.min_stock') ?></label>
                    <input type="number" name="min_stock" class="form-control" min="0" value="<?= $product['min_stock'] ?? '0' ?>">
                </div>
                <?php if (!$product): ?>
                <div class="form-group">
                    <label class="form-label"><?= __('products.initial_stock') ?></label>
                    <input type="number" name="current_stock" class="form-control" min="0" value="0">
                </div>
                <?php endif; ?>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $product ? __('common.update') : __('common.save') ?></button>
                <a href="<?= url('/products') ?>" class="btn btn-secondary"><?= __('common.cancel') ?></a>
            </div>
        </form>
    </div>
</div>
