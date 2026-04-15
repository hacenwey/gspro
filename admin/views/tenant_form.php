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
                        /gestion_commerciale/
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
                    /gestion_commerciale/<?= e($tenant['slug']) ?>
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
