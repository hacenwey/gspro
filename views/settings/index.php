<?php
$sub = $subscription ?? [];
$subPlan = strtoupper($sub['plan'] ?? 'starter');
$subStatus = $sub['status'] ?? 'trial';
$paidUntil = !empty($sub['paid_until']) ? strtotime($sub['paid_until']) : null;
$statusLabel = match($subStatus) {
    'active'  => ['Actif',    'success'],
    'trial'   => ['Essai',    'warning'],
    'cancelled' => ['Annule', 'danger'],
    'expired'   => ['Expire', 'danger'],
    'pending_payment' => ['En attente de paiement', 'warning'],
    default => [$subStatus, 'secondary'],
};
?>
<!-- Subscription card -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3><i class="fas fa-credit-card" style="color:var(--primary);"></i> Abonnement</h3></div>
    <div class="card-body">
        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Plan</div>
                <div style="font-size:20px;font-weight:800;color:var(--primary);"><?= e($subPlan) ?></div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Statut</div>
                <span class="badge badge-<?= $statusLabel[1] ?>" style="font-size:13px;margin-top:4px;display:inline-block;"><?= e($statusLabel[0]) ?></span>
            </div>
            <?php if ($paidUntil): ?>
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Prochaine echeance</div>
                <div style="font-size:15px;font-weight:600;"><?= date('d/m/Y', $paidUntil) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($sub['polar_sub']) && $subStatus === 'active'): ?>
            <form method="POST" action="<?= url('/pay/cancel') ?>" onsubmit="return confirm('Confirmer l\'annulation de l\'abonnement ? Vous garderez l\'acces jusqu\'au <?= $paidUntil ? date('d/m/Y', $paidUntil) : 'fin de la periode' ?>.');" style="margin-top:16px;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-ban"></i> Annuler mon abonnement
                </button>
                <div style="font-size:12px;color:var(--text-muted);margin-top:6px;">
                    Aucun prelevement ne sera effectue. Vous gardez l'acces jusqu'a la fin de la periode en cours.
                </div>
            </form>
        <?php elseif ($subStatus === 'cancelled'): ?>
            <form method="POST" action="<?= url('/pay/start') ?>" style="margin-top:16px;">
                <input type="hidden" name="plan" value="<?= e($sub['plan'] ?? 'starter') ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-credit-card"></i> Reactiver mon abonnement
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <!-- Company Settings -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-building" style="color:var(--primary);"></i> <?= __('settings.company') ?></h3></div>
        <div class="card-body">
            <form method="POST" action="<?= url('/settings/update') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label"><?= __('settings.company_name') ?></label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($settings['company_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('settings.address') ?></label>
                    <textarea name="company_address" class="form-control" rows="2"><?= e($settings['company_address'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.phone') ?></label>
                        <input type="text" name="company_phone" class="form-control" value="<?= e($settings['company_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.email') ?></label>
                        <input type="email" name="company_email" class="form-control" value="<?= e($settings['company_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('settings.tax_id') ?></label>
                    <input type="text" name="company_tax_id" class="form-control" value="<?= e($settings['company_tax_id'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.default_tax') ?></label>
                        <input type="number" name="default_tax_rate" class="form-control" step="0.01" value="<?= $settings['default_tax_rate'] ?? TAX_RATE_DEFAULT ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.currency') ?></label>
                        <input type="text" name="currency" class="form-control" value="<?= e($settings['currency'] ?? CURRENCY) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.payment_terms') ?></label>
                        <input type="number" name="default_payment_terms" class="form-control" value="<?= $settings['default_payment_terms'] ?? '30' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.quote_validity') ?></label>
                        <input type="number" name="default_quote_validity" class="form-control" value="<?= $settings['default_quote_validity'] ?? '30' ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-save"></i> <?= __('common.save') ?></button>
            </form>
        </div>
    </div>

    <!-- Users Management -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users-cog" style="color:var(--primary);"></i> <?= __('settings.users') ?></h3>
            <button class="btn btn-sm btn-primary" onclick="openModal('userModal')"><i class="fas fa-plus"></i> <?= __('settings.add_user') ?></button>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead><tr><th><?= __('settings.users') ?></th><th><?= __('settings.role') ?></th><th><?= __('common.status') ?></th><th><?= __('settings.last_login') ?></th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= e($u['full_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= e($u['username']) ?> | <?= e($u['email']) ?></div>
                    </td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'manager' ? 'primary' : 'info') ?>"><?= $u['role'] ?></span></td>
                    <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? __('settings.active') : __('settings.inactive') ?></span></td>
                    <td style="font-size:12px;"><?= $u['last_login'] ? formatDateTime($u['last_login']) : __('settings.never') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header"><h3><?= __('settings.new_user') ?></h3><button class="modal-close" onclick="closeModal('userModal')">&times;</button></div>
        <form method="POST" action="<?= url('/settings/users/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label"><?= __('settings.full_name') ?> *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.username') ?> *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.email') ?> *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.password') ?> *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('settings.role') ?> *</label>
                        <select name="role" class="form-control">
                            <option value="cashier"><?= __('settings.role.cashier') ?></option>
                            <option value="manager"><?= __('settings.role.manager') ?></option>
                            <option value="accountant"><?= __('settings.role.accountant') ?></option>
                            <option value="admin"><?= __('settings.role.admin') ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= __('settings.create_user') ?></button>
            </div>
        </form>
    </div>
</div>
