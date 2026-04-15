<div class="d-flex justify-between align-center mb-3">
    <div>
        <a href="<?= url('/products') ?>" style="color: var(--text-muted); font-size: 13px;"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    </div>
    <div class="btn-group">
        <a href="<?= url('/products/edit/' . $product['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> <?= __('common.edit') ?></a>
        <button class="btn btn-sm btn-success" onclick="openModal('adjustModal')"><i class="fas fa-sliders"></i> <?= __('products.adjust_stock') ?></button>
    </div>
</div>

<div class="grid-2">
    <!-- Product Info -->
    <div class="card">
        <div class="card-header">
            <h3><?= __('products.info') ?></h3>
            <span class="badge badge-<?= stockStatusClass($product['current_stock'], $product['min_stock']) ?>">
                <?= stockStatusLabel($product['current_stock'], $product['min_stock']) ?>
            </span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label"><?= __('products.reference') ?></span><span class="value"><?= e($product['reference']) ?></span></li>
                <?php if ($product['barcode']): ?>
                <li><span class="label"><?= __('products.barcode') ?></span><span class="value"><?= e($product['barcode']) ?></span></li>
                <?php endif; ?>
                <li><span class="label"><?= __('products.category') ?></span><span class="value"><?= e($product['category_name'] ?? 'N/A') ?></span></li>
                <li><span class="label"><?= __('products.unit') ?></span><span class="value"><?= e($product['unit']) ?></span></li>
                <li><span class="label"><?= __('products.purchase_price') ?></span><span class="value"><?= formatMoney($product['purchase_price']) ?></span></li>
                <li><span class="label"><?= __('products.selling_price') ?></span><span class="value" style="color: var(--primary);"><?= formatMoney($product['selling_price']) ?></span></li>
                <li><span class="label"><?= __('products.tax_rate') ?></span><span class="value"><?= $product['tax_rate'] ?>%</span></li>
                <li>
                    <span class="label"><?= __('products.margin') ?></span>
                    <?php $margin = $product['selling_price'] - $product['purchase_price']; $marginPct = $product['purchase_price'] > 0 ? ($margin / $product['purchase_price']) * 100 : 0; ?>
                    <span class="value text-<?= $margin >= 0 ? 'success' : 'danger' ?>"><?= formatMoney($margin) ?> (<?= number_format($marginPct, 1) ?>%)</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Stock Info -->
    <div class="card">
        <div class="card-header">
            <h3><?= __('common.stock') ?></h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 48px; font-weight: 800; font-family: var(--font-mono); color: var(--<?= stockStatusClass($product['current_stock'], $product['min_stock']) ?>);">
                    <?= $product['current_stock'] ?>
                </div>
                <div style="color: var(--text-muted); font-size: 14px;"><?= e($product['unit']) ?>(s) <?= __('products.in_stock') ?></div>
                <div style="margin-top: 8px; font-size: 13px; color: var(--text-secondary);"><?= __('products.min_threshold') ?>: <?= $product['min_stock'] ?></div>
            </div>
            <div style="display: flex; gap: 8px; justify-content: center; margin-top: 16px;">
                <button class="btn btn-success btn-sm" onclick="openModal('adjustModal');document.getElementById('adjType').value='in';">
                    <i class="fas fa-plus"></i> <?= __('products.stock_in') ?>
                </button>
                <button class="btn btn-danger btn-sm" onclick="openModal('adjustModal');document.getElementById('adjType').value='out';">
                    <i class="fas fa-minus"></i> <?= __('products.stock_out') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Movement History -->
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-history" style="color: var(--info);"></i> <?= __('products.movements') ?></h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($movements)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            <p><?= __('products.no_movements') ?></p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th><?= __('common.date') ?></th><th><?= __('common.type') ?></th><th><?= __('products.reason') ?></th><th class="text-right"><?= __('common.quantity') ?></th><th><?= __('common.notes') ?></th><th><?= __('common.by') ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($movements as $m): ?>
                <tr>
                    <td style="font-size:12px;"><?= formatDateTime($m['created_at']) ?></td>
                    <td>
                        <span class="badge badge-<?= $m['type'] === 'in' ? 'success' : ($m['type'] === 'out' ? 'danger' : 'warning') ?>">
                            <?= $m['type'] === 'in' ? __('products.stock_in') : ($m['type'] === 'out' ? __('products.stock_out') : __('products.adjustment')) ?>
                        </span>
                    </td>
                    <td><?= e($m['reason']) ?></td>
                    <td class="text-right text-mono fw-bold"><?= $m['type'] === 'in' ? '+' : '-' ?><?= $m['quantity'] ?></td>
                    <td style="font-size:12px;"><?= e($m['notes'] ?? '') ?></td>
                    <td style="font-size:12px;"><?= e($m['user_name'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal-overlay" id="adjustModal">
    <div class="modal">
        <div class="modal-header">
            <h3><?= __('products.adjust_stock') ?></h3>
            <button class="modal-close" onclick="closeModal('adjustModal')">&times;</button>
        </div>
        <form method="POST" action="<?= url('/products/adjust-stock/' . $product['id']) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('products.movement_type') ?></label>
                    <select name="type" id="adjType" class="form-control" required>
                        <option value="in"><?= __('products.stock_in') ?> (+)</option>
                        <option value="out"><?= __('products.stock_out') ?> (-)</option>
                        <option value="adjustment"><?= __('products.adjustment') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.quantity') ?></label>
                    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('products.reason') ?></label>
                    <select name="reason" class="form-control">
                        <option value="purchase"><?= __('products.reason.purchase') ?></option>
                        <option value="sale"><?= __('products.reason.sale') ?></option>
                        <option value="return_client"><?= __('products.reason.return_client') ?></option>
                        <option value="return_supplier"><?= __('products.reason.return_supplier') ?></option>
                        <option value="loss"><?= __('products.reason.loss') ?></option>
                        <option value="inventory"><?= __('products.reason.inventory') ?></option>
                        <option value="internal"><?= __('products.reason.internal') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('common.notes') ?></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('adjustModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> <?= __('common.validate') ?></button>
            </div>
        </form>
    </div>
</div>
