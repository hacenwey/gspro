<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?= $supplier ? __('suppliers.edit') : __('suppliers.new') ?></h3>
        <a href="<?= url('/suppliers') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url($supplier ? '/suppliers/update/' . $supplier['id'] : '/suppliers/store') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label"><?= __('suppliers.company_name') ?> *</label>
                <input type="text" name="company_name" class="form-control" value="<?= e($supplier['company_name'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('suppliers.contact_name') ?></label>
                    <input type="text" name="contact_name" class="form-control" value="<?= e($supplier['contact_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('suppliers.phone') ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= e($supplier['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('suppliers.email') ?></label>
                    <input type="email" name="email" class="form-control" value="<?= e($supplier['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('suppliers.tax_id') ?></label>
                    <input type="text" name="tax_id" class="form-control" value="<?= e($supplier['tax_id'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('suppliers.address') ?></label>
                <textarea name="address" class="form-control" rows="2"><?= e($supplier['address'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('suppliers.payment_terms') ?></label>
                    <input type="number" name="payment_terms" class="form-control" min="0" value="<?= $supplier['payment_terms'] ?? '30' ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('common.notes') ?></label>
                <textarea name="notes" class="form-control" rows="2"><?= e($supplier['notes'] ?? '') ?></textarea>
            </div>
            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $supplier ? __('common.update') : __('common.save') ?></button>
                <a href="<?= url('/suppliers') ?>" class="btn btn-secondary"><?= __('common.cancel') ?></a>
            </div>
        </form>
    </div>
</div>
