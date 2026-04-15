<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-building" style="color:#DC2626;margin-right:8px;"></i> Clients</h1>
    <a href="<?= adminUrl('/tenants/create') ?>" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
        <i class="fas fa-plus"></i> Nouveau client
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($tenants)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-building"></i></div>
            <h4>Aucun client</h4>
            <p>Creez votre premier client pour commencer.</p>
            <a href="<?= adminUrl('/tenants/create') ?>" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
                <i class="fas fa-plus"></i> Creer un client
            </a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>Entreprise</th>
                    <th>Proprietaire</th>
                    <th>Plan</th>
                    <th>Base de donnees</th>
                    <th>Statut</th>
                    <th>Cree le</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td>
                    <a href="<?= APP_BASE . '/' . $t['slug'] . '/login' ?>" target="_blank" class="text-mono" style="font-weight:700;color:var(--primary);">
                        <?= e($t['slug']) ?>
                    </a>
                </td>
                <td style="font-weight:600;"><?= e($t['company_name']) ?></td>
                <td>
                    <div><?= e($t['owner_name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= e($t['owner_email']) ?></div>
                </td>
                <td>
                    <span class="badge badge-<?= match($t['plan']) { 'free' => 'secondary', 'starter' => 'info', 'pro' => 'primary', 'enterprise' => 'warning', default => 'secondary' } ?>">
                        <?= strtoupper($t['plan']) ?>
                    </span>
                </td>
                <td class="text-mono" style="font-size:11px;"><?= e($t['db_name']) ?></td>
                <td>
                    <span class="badge badge-<?= $t['is_active'] ? 'success' : 'danger' ?>">
                        <?= $t['is_active'] ? 'Actif' : 'Inactif' ?>
                    </span>
                    <?php if ($t['expires_at']): ?>
                    <div style="font-size:10px;color:var(--text-muted);">Exp: <?= formatDate($t['expires_at']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;"><?= formatDate($t['created_at']) ?></td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content:flex-end;">
                        <a href="<?= APP_BASE . '/' . $t['slug'] . '/login' ?>" target="_blank" class="btn btn-icon btn-sm btn-secondary" title="Acceder"><i class="fas fa-external-link-alt"></i></a>
                        <a href="<?= adminUrl('/tenants/edit/' . $t['id']) ?>" class="btn btn-icon btn-sm btn-secondary" title="Modifier"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="<?= adminUrl('/tenants/toggle/' . $t['id']) ?>" style="display:inline;">
                            <button type="submit" class="btn btn-icon btn-sm btn-<?= $t['is_active'] ? 'warning' : 'success' ?>" title="<?= $t['is_active'] ? 'Desactiver' : 'Activer' ?>">
                                <i class="fas fa-<?= $t['is_active'] ? 'pause' : 'play' ?>"></i>
                            </button>
                        </form>
                        <form method="POST" action="<?= adminUrl('/tenants/delete/' . $t['id']) ?>" style="display:inline;">
                            <button type="button" class="btn btn-icon btn-sm btn-danger" title="Supprimer"
                                onclick="if(confirm('ATTENTION: Ceci supprimera definitivement le client <?= e($t['slug']) ?> et sa base de donnees. Continuer?')) this.parentNode.submit();">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
