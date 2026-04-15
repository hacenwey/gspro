<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3><?= $client ? 'Modifier le client' : 'Nouveau client' ?></h3>
        <a href="<?= url('/clients') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url($client ? '/clients/update/' . $client['id'] : '/clients/store') ?>">
            <?= csrf_field() ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-control">
                        <option value="individual" <?= ($client['type'] ?? '') === 'individual' ? 'selected' : '' ?>>Particulier</option>
                        <option value="company" <?= ($client['type'] ?? '') === 'company' ? 'selected' : '' ?>>Entreprise</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Categorie</label>
                    <select name="category" class="form-control">
                        <option value="regular" <?= ($client['category'] ?? 'regular') === 'regular' ? 'selected' : '' ?>>Regulier</option>
                        <option value="vip" <?= ($client['category'] ?? '') === 'vip' ? 'selected' : '' ?>>VIP</option>
                        <option value="occasional" <?= ($client['category'] ?? '') === 'occasional' ? 'selected' : '' ?>>Occasionnel</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Prenom</label>
                    <input type="text" name="first_name" class="form-control" value="<?= e($client['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom / Raison sociale *</label>
                    <input type="text" name="last_name" class="form-control" value="<?= e($client['last_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Telephone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($client['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($client['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Adresse</label>
                <textarea name="address" class="form-control" rows="2"><?= e($client['address'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">NIF / RC</label>
                    <input type="text" name="tax_id" class="form-control" value="<?= e($client['tax_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Plafond credit</label>
                    <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="<?= $client['credit_limit'] ?? '0.00' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= e($client['notes'] ?? '') ?></textarea>
            </div>

            <div class="btn-group mt-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $client ? 'Mettre a jour' : 'Enregistrer' ?></button>
                <a href="<?= url('/clients') ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
