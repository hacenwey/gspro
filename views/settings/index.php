<div class="grid-2">
    <!-- Company Settings -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-building" style="margin-right:8px;color:var(--primary);"></i>Informations entreprise</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= url('/settings/update') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label">Nom de l'entreprise</label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($settings['company_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <textarea name="company_address" class="form-control" rows="2"><?= e($settings['company_address'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telephone</label>
                        <input type="text" name="company_phone" class="form-control" value="<?= e($settings['company_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?= e($settings['company_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">NIF / Registre de commerce</label>
                    <input type="text" name="company_tax_id" class="form-control" value="<?= e($settings['company_tax_id'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">TVA par defaut (%)</label>
                        <input type="number" name="default_tax_rate" class="form-control" step="0.01" value="<?= $settings['default_tax_rate'] ?? '19' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Devise</label>
                        <input type="text" name="currency" class="form-control" value="<?= e($settings['currency'] ?? 'DA') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Delai paiement (jours)</label>
                        <input type="number" name="default_payment_terms" class="form-control" value="<?= $settings['default_payment_terms'] ?? '30' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Validite devis (jours)</label>
                        <input type="number" name="default_quote_validity" class="form-control" value="<?= $settings['default_quote_validity'] ?? '30' ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>

    <!-- Users Management -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users-cog" style="margin-right:8px;color:var(--primary);"></i>Utilisateurs</h3>
            <button class="btn btn-sm btn-primary" onclick="openModal('userModal')"><i class="fas fa-plus"></i> Ajouter</button>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead><tr><th>Utilisateur</th><th>Role</th><th>Statut</th><th>Derniere connexion</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;"><?= e($u['full_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= e($u['username']) ?> | <?= e($u['email']) ?></div>
                    </td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'manager' ? 'primary' : 'info') ?>"><?= $u['role'] ?></span></td>
                    <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? 'Actif' : 'Inactif' ?></span></td>
                    <td style="font-size:12px;"><?= $u['last_login'] ? formatDateTime($u['last_login']) : 'Jamais' ?></td>
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
        <div class="modal-header"><h3>Nouvel utilisateur</h3><button class="modal-close" onclick="closeModal('userModal')">&times;</button></div>
        <form method="POST" action="<?= url('/settings/users/store') ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nom complet *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control">
                            <option value="cashier">Caissier</option>
                            <option value="manager">Manager</option>
                            <option value="accountant">Comptable</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Creer</button>
            </div>
        </form>
    </div>
</div>
