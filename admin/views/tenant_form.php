<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1>
        <i class="fas fa-<?= $tenant ? 'pen' : 'plus' ?>" style="color:#DC2626;margin-right:8px;"></i>
        <?= $tenant ? 'Modifier le client' : 'Nouveau client' ?>
    </h1>
    <a href="<?= adminUrl('/tenants') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="POST" action="<?= $tenant ? adminUrl('/tenants/update/' . $tenant['id']) : adminUrl('/tenants/store') ?>">

            <?php if (!$tenant): ?>
            <!-- Slug (only on creation) -->
            <div class="form-group">
                <label class="form-label">Slug URL *</label>
                <div style="display:flex;align-items:center;gap:0;">
                    <span style="padding:10px 14px;background:var(--bg-subtle);border:1px solid var(--border);border-right:0;border-radius:var(--radius) 0 0 var(--radius);font-size:13px;color:var(--text-muted);white-space:nowrap;">
                        <?= APP_BASE ?>/
                    </span>
                    <input type="text" name="slug" class="form-control" style="border-radius:0 var(--radius) var(--radius) 0;"
                        placeholder="nom-client" required pattern="[a-z0-9_-]+"
                        title="Lettres minuscules, chiffres, tirets et underscores uniquement">
                </div>
                <small style="color:var(--text-muted);font-size:11px;">Identifiant unique dans l'URL. Ex: ahmed, boutique-salam, entreprise-xyz</small>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label class="form-label">Slug URL</label>
                <div class="text-mono fw-bold" style="font-size:16px;color:var(--primary);">
                    <?= APP_BASE ?>/<?= e($tenant['slug']) ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Nom de l'entreprise *</label>
                <input type="text" name="company_name" class="form-control" value="<?= e($tenant['company_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom du proprietaire *</label>
                    <input type="text" name="owner_name" class="form-control" value="<?= e($tenant['owner_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="owner_email" class="form-control" value="<?= e($tenant['owner_email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Telephone</label>
                    <input type="text" name="owner_phone" class="form-control" value="<?= e($tenant['owner_phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Plan</label>
                    <select name="plan" class="form-control">
                        <?php $plan = $tenant['plan'] ?? 'starter'; ?>
                        <option value="free" <?= $plan === 'free' ? 'selected' : '' ?>>Free (2 users, 100 produits)</option>
                        <option value="starter" <?= $plan === 'starter' ? 'selected' : '' ?>>Starter (5 users, 500 produits)</option>
                        <option value="pro" <?= $plan === 'pro' ? 'selected' : '' ?>>Pro (15 users, 5000 produits)</option>
                        <option value="enterprise" <?= $plan === 'enterprise' ? 'selected' : '' ?>>Enterprise (100 users, illimite)</option>
                    </select>
                </div>
            </div>

            <?php if (!$tenant): ?>
            <div style="padding:16px;background:var(--bg-subtle);border-radius:var(--radius);margin-bottom:20px;">
                <h4 style="margin-bottom:12px;font-size:14px;color:var(--text-secondary);">
                    <i class="fas fa-key"></i> Identifiants administrateur
                </h4>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input type="text" name="admin_username" class="form-control" value="admin">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Mot de passe</label>
                        <input type="text" name="admin_password" class="form-control" value="admin123">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Adresse</label>
                <input type="text" name="address" class="form-control" placeholder="Nouakchott, Mauritanie">
            </div>
            <?php endif; ?>

            <?php if ($tenant): ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Max utilisateurs</label>
                    <input type="number" name="max_users" class="form-control" value="<?= $tenant['max_users'] ?? 5 ?>" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Max produits</label>
                    <input type="number" name="max_products" class="form-control" value="<?= $tenant['max_products'] ?? 500 ?>" min="1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date d'expiration</label>
                    <input type="date" name="expires_at" class="form-control" value="<?= $tenant['expires_at'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <div style="padding-top:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" <?= ($tenant['is_active'] ?? 1) ? 'checked' : '' ?>>
                            Actif
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($tenant['notes'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
                    <i class="fas fa-<?= $tenant ? 'save' : 'plus' ?>"></i>
                    <?= $tenant ? 'Mettre a jour' : 'Creer le client' ?>
                </button>
                <a href="<?= adminUrl('/tenants') ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php if ($tenant): ?>
<!-- ========== SUBSCRIPTION / TRIAL SECTION ========== -->
<?php $trState = Tenant::trialState($tenant); $subStatus = $tenant['subscription_status'] ?? 'active'; ?>
<div class="card" style="max-width:700px;margin-top:20px;">
    <div class="card-body">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-credit-card" style="color:#4F46E5;"></i> Abonnement
        </h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
            Gerer l'essai gratuit et l'abonnement de ce client.
        </p>

        <!-- Status summary -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
            <div style="padding:16px;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--bg);">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">Statut</div>
                <div style="font-size:17px;font-weight:700;margin-top:6px;color:<?= $trState['status'] === 'expired' ? '#DC2626' : ($trState['status'] === 'warning' ? '#F59E0B' : ($subStatus === 'active' ? '#10B981' : '#0EA5E9')) ?>;">
                    <?php if ($subStatus === 'trial' && $trState['status'] !== 'expired'): ?>
                        <i class="fas fa-clock"></i> Essai (<?= $trState['days_left'] ?>j)
                    <?php elseif ($subStatus === 'active'): ?>
                        <i class="fas fa-check-circle"></i> Abonne
                    <?php elseif ($trState['status'] === 'expired'): ?>
                        <i class="fas fa-circle-xmark"></i> Expire
                    <?php else: ?>
                        <?= ucfirst($subStatus) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div style="padding:16px;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--bg);">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">
                    <?= $subStatus === 'active' ? 'Valide jusqu\'au' : 'Fin essai' ?>
                </div>
                <div style="font-size:15px;font-weight:700;margin-top:6px;">
                    <?php if ($subStatus === 'active' && !empty($tenant['subscription_paid_until'])): ?>
                        <?= formatDate($tenant['subscription_paid_until']) ?>
                    <?php elseif (!empty($tenant['trial_ends_at'])): ?>
                        <?= formatDate($tenant['trial_ends_at']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <!-- Activate subscription -->
            <form method="POST" action="<?= adminUrl('/tenants/activate/' . $tenant['id']) ?>" style="padding:16px;border:1.5px solid #10B981;border-radius:var(--radius-lg);background:rgba(16,185,129,0.04);">
                <div style="font-weight:700;font-size:14px;color:#10B981;margin-bottom:8px;">
                    <i class="fas fa-check-circle"></i> Activer l'abonnement
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Le client a paye. Activer pour N mois.</p>
                <div style="display:flex;gap:8px;">
                    <select name="months" class="form-control" style="font-size:13px;padding:8px;">
                        <option value="1">1 mois</option>
                        <option value="3">3 mois</option>
                        <option value="6">6 mois</option>
                        <option value="12" selected>12 mois</option>
                    </select>
                    <button type="submit" class="btn btn-sm" style="background:#10B981;color:#fff;white-space:nowrap;">
                        <i class="fas fa-check"></i> Activer
                    </button>
                </div>
            </form>

            <!-- Extend trial -->
            <form method="POST" action="<?= adminUrl('/tenants/extend-trial/' . $tenant['id']) ?>" style="padding:16px;border:1.5px solid #F59E0B;border-radius:var(--radius-lg);background:rgba(245,158,11,0.04);">
                <div style="font-weight:700;font-size:14px;color:#F59E0B;margin-bottom:8px;">
                    <i class="fas fa-clock"></i> Prolonger l'essai
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Ajouter des jours gratuits a l'essai.</p>
                <div style="display:flex;gap:8px;">
                    <select name="days" class="form-control" style="font-size:13px;padding:8px;">
                        <option value="3">+3 jours</option>
                        <option value="7" selected>+7 jours</option>
                        <option value="14">+14 jours</option>
                        <option value="30">+30 jours</option>
                    </select>
                    <button type="submit" class="btn btn-sm" style="background:#F59E0B;color:#fff;white-space:nowrap;">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== RESET PASSWORD SECTION ========== -->
<?php
// Load users from tenant database
$tenantUsers = [];
try {
    $tDsn = "mysql:host=" . DB_HOST . ";dbname=" . $tenant['db_name'] . ";charset=" . DB_CHARSET;
    $tDb = new PDO($tDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $tenantUsers = $tDb->query("SELECT id, username, full_name, role, email, is_active, last_login FROM users ORDER BY role ASC, created_at ASC")->fetchAll();
} catch (Exception $e) {
    $tenantUsers = [];
}
?>
<div class="card" id="reset-section" style="max-width:700px;margin-top:20px;overflow:visible;">
    <div class="card-body" style="overflow:visible;">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-key" style="color:#DC2626;"></i> Reinitialiser un mot de passe
        </h3>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">
            Choisissez un utilisateur et definissez un nouveau mot de passe.
        </p>

        <?php if (empty($tenantUsers)): ?>
        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:14px;">
            <i class="fas fa-user-slash" style="font-size:24px;margin-bottom:8px;display:block;"></i>
            Aucun utilisateur trouve dans la base de donnees de ce client.
        </div>
        <?php else: ?>

        <!-- Users list -->
        <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($tenantUsers as $u): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--bg);transition:all 0.2s;">
                <!-- Avatar + Info -->
                <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:0;">
                    <div style="width:40px;height:40px;border-radius:10px;background:<?= match($u['role']) { 'admin' => 'rgba(220,38,38,0.1)', 'manager' => 'rgba(79,70,229,0.1)', 'cashier' => 'rgba(14,165,233,0.1)', default => 'rgba(245,158,11,0.1)' } ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-<?= match($u['role']) { 'admin' => 'user-shield', 'manager' => 'user-tie', 'cashier' => 'cash-register', default => 'calculator' } ?>"
                            style="font-size:15px;color:<?= match($u['role']) { 'admin' => '#DC2626', 'manager' => '#4F46E5', 'cashier' => '#0EA5E9', default => '#F59E0B' } ?>;"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-weight:700;font-size:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <?= e($u['username']) ?>
                            <span class="badge badge-<?= match($u['role']) { 'admin' => 'danger', 'manager' => 'primary', 'cashier' => 'info', 'accountant' => 'warning', default => 'secondary' } ?>" style="font-size:10px;">
                                <?= ucfirst($u['role']) ?>
                            </span>
                            <span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>" style="font-size:10px;">
                                <?= $u['is_active'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                            <?= e($u['full_name']) ?>
                            <?php if ($u['email']): ?> &middot; <?= e($u['email']) ?><?php endif; ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:1px;">
                            <i class="fas fa-clock" style="font-size:9px;"></i>
                            Derniere connexion: <?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Jamais' ?>
                        </div>
                    </div>
                </div>
                <!-- Reset button -->
                <button type="button" class="btn btn-sm"
                    style="background:rgba(220,38,38,0.08);color:#DC2626;border:1px solid rgba(220,38,38,0.2);white-space:nowrap;flex-shrink:0;"
                    onclick="openResetModal('<?= $u['id'] ?>', '<?= e($u['username']) ?>', '<?= e($u['full_name']) ?>')"
                    title="Reinitialiser le mot de passe">
                    <i class="fas fa-key"></i> Reset MDP
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:20px;">
    <div style="background:var(--surface);border-radius:16px;width:100%;max-width:420px;box-shadow:0 24px 60px rgba(0,0,0,0.2);animation:modalIn 0.25s ease;">
        <div style="padding:24px 24px 0;display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <h3 style="font-size:18px;font-weight:700;margin-bottom:4px;">
                    <i class="fas fa-key" style="color:#DC2626;"></i> Nouveau mot de passe
                </h3>
                <p style="font-size:13px;color:var(--text-muted);">
                    Pour: <strong id="resetUserDisplay" style="color:var(--text);"></strong>
                </p>
            </div>
            <button onclick="closeResetModal()" style="background:none;border:none;font-size:20px;color:var(--text-muted);cursor:pointer;">&times;</button>
        </div>
        <form method="POST" id="resetForm" style="padding:20px 24px 24px;">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Nouveau mot de passe *</label>
                <div style="position:relative;">
                    <input type="text" name="new_password" id="resetPasswordInput" class="form-control"
                        placeholder="Minimum 6 caracteres" required minlength="6"
                        style="padding-right:80px;">
                    <button type="button" onclick="generatePassword()" class="btn btn-sm"
                        style="position:absolute;right:4px;top:50%;transform:translateY(-50%);font-size:11px;padding:4px 10px;background:var(--bg-subtle);border:1px solid var(--border);">
                        <i class="fas fa-dice"></i> Generer
                    </button>
                </div>
                <small style="color:var(--text-muted);font-size:11px;">Le client devra utiliser ce nouveau mot de passe pour se connecter.</small>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
                    <i class="fas fa-check"></i> Reinitialiser
                </button>
                <button type="button" onclick="closeResetModal()" class="btn btn-secondary">Annuler</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalIn { from { opacity:0; transform:translateY(16px) scale(0.97); } to { opacity:1; transform:none; } }
</style>
<script>
function openResetModal(userId, username, fullName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserDisplay').textContent = username + (fullName ? ' (' + fullName + ')' : '');
    document.getElementById('resetForm').action = '<?= adminUrl('/tenants/reset-password/' . $tenant['id']) ?>';
    document.getElementById('resetPasswordInput').value = '';
    const modal = document.getElementById('resetModal');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('resetPasswordInput').focus(), 100);
}

function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
}

// Close on overlay click
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});

// Generate random password
function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
    let pass = '';
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('resetPasswordInput').value = pass;
}
</script>
<?php endif; ?>
