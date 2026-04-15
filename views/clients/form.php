<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?= $client ? __('clients.edit') : __('clients.new') ?></h3>
        <a href="<?= url('/clients') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> <?= __('common.back') ?></a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url($client ? '/clients/update/' . $client['id'] : '/clients/store') ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('clients.type') ?> *</label>
                    <select name="type" class="form-control">
                        <option value="individual" <?= ($client['type'] ?? '') === 'individual' ? 'selected' : '' ?>><?= __('clients.type.individual') ?></option>
                        <option value="company" <?= ($client['type'] ?? '') === 'company' ? 'selected' : '' ?>><?= __('clients.type.company') ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('clients.category') ?></label>
                    <select name="category" class="form-control">
                        <option value="regular" <?= ($client['category'] ?? 'regular') === 'regular' ? 'selected' : '' ?>><?= __('clients.cat.regular') ?></option>
                        <option value="vip" <?= ($client['category'] ?? '') === 'vip' ? 'selected' : '' ?>><?= __('clients.cat.vip') ?></option>
                        <option value="occasional" <?= ($client['category'] ?? '') === 'occasional' ? 'selected' : '' ?>><?= __('clients.cat.occasional') ?></option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('clients.first_name') ?></label>
                    <input type="text" name="first_name" class="form-control" value="<?= e($client['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('clients.last_name') ?> *</label>
                    <input type="text" name="last_name" class="form-control" value="<?= e($client['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('clients.phone') ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= e($client['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('clients.email') ?></label>
                    <input type="email" name="email" class="form-control" value="<?= e($client['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('clients.address') ?></label>
                <textarea name="address" class="form-control" rows="2"><?= e($client['address'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= __('clients.tax_id') ?></label>
                    <input type="text" name="tax_id" class="form-control" value="<?= e($client['tax_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('clients.credit_limit') ?></label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="<?= $client['credit_limit'] ?? '0.00' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label"><?= __('common.notes') ?></label>
                <textarea name="notes" class="form-control" rows="2"><?= e($client['notes'] ?? '') ?></textarea>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $client ? __('common.update') : __('common.save') ?></button>
                <a href="<?= url('/clients') ?>" class="btn btn-secondary"><?= __('common.cancel') ?></a>
            </div>
        </form>
    </div>
</div>
