<div class="page-header">
    <h1><i class="fas fa-key"></i> Jetons API</h1>
    <div class="page-header-actions">
        <a href="<?= url('/settings') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Parametres</a>
    </div>
</div>

<?php if (!empty($newToken)): ?>
<div class="card mb-3" style="border-left: 4px solid var(--success);">
    <div class="card-body">
        <strong><i class="fas fa-circle-check text-success"></i> Nouveau token genere — copiez-le maintenant :</strong>
        <div class="mt-2" style="display:flex;gap:8px;">
            <input type="text" class="form-control text-mono" value="<?= e($newToken) ?>" readonly onclick="this.select()" style="font-size:12px;">
            <button type="button" class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText('<?= e($newToken) ?>'); this.innerText='Copie !';">Copier</button>
        </div>
        <p class="text-xs text-muted mt-1 mb-0">Ce token ne sera plus jamais reaffiche. Stockez-le de maniere securisee.</p>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><h3>Nouveau token</h3></div>
    <div class="card-body">
        <form method="POST" action="<?= url('/settings/api-tokens/create') ?>">
            <?= csrf_field() ?>
            <div class="d-flex gap-2" style="flex-wrap:wrap;align-items:flex-end;">
                <div class="form-group mb-0" style="flex:1;min-width:200px;">
                    <label class="form-label">Nom / usage</label>
                    <input type="text" name="name" class="form-control" placeholder="ex: Integration Zapier" required>
                </div>
                <div class="form-group mb-0" style="width:140px;">
                    <label class="form-label">Expire dans (jours)</label>
                    <input type="number" name="expires_days" class="form-control" value="365" min="0">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Generer</button>
            </div>
            <p class="text-xs text-muted mt-1 mb-0">Laisser 0 pour un token sans expiration.</p>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Tokens actifs (<?= count($tokens) ?>)</h3></div>
    <div class="card-body p-0">
        <?php if (empty($tokens)): ?>
            <div class="empty-state" style="padding:40px;">
                <p class="text-muted">Aucun token cree.</p>
            </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Nom</th><th>Cree par</th><th>Cree le</th><th>Derniere utilisation</th><th>Expire</th><th>Etat</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            <?php foreach ($tokens as $t): ?>
            <tr>
                <td class="fw-bold"><?= e($t['name']) ?></td>
                <td class="text-sm"><?= e($t['issued_to']) ?></td>
                <td class="text-sm"><?= formatDateTime($t['created_at']) ?></td>
                <td class="text-sm"><?= $t['last_used_at'] ? formatDateTime($t['last_used_at']) : '<span class="text-muted">jamais</span>' ?></td>
                <td class="text-sm"><?= $t['expires_at'] ? formatDate($t['expires_at']) : '<span class="text-muted">illimite</span>' ?></td>
                <td>
                    <?php if ($t['revoked_at']): ?>
                        <span class="badge badge-secondary">Revoque</span>
                    <?php elseif ($t['expires_at'] && strtotime($t['expires_at']) < time()): ?>
                        <span class="badge badge-warning">Expire</span>
                    <?php else: ?>
                        <span class="badge badge-success">Actif</span>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if (!$t['revoked_at']): ?>
                    <form method="POST" action="<?= url('/settings/api-tokens/revoke/' . $t['id']) ?>" style="display:inline;" onsubmit="return confirm('Revoquer ce token ? Les integrations l\'utilisant cesseront de fonctionner.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Revoquer</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3><i class="fas fa-book"></i> Utilisation de l'API</h3></div>
    <div class="card-body">
        <p>Base URL : <code><?= url('/api/v1') ?></code></p>
        <p>Auth : <code>Authorization: Bearer &lt;votre-token&gt;</code></p>
        <h4 class="mt-3">Endpoints</h4>
        <ul class="text-sm">
            <li><code>GET /api/v1/health</code> — statut</li>
            <li><code>GET /api/v1/products?q=&amp;page=&amp;per_page=</code></li>
            <li><code>GET /api/v1/invoices?type=&amp;status=&amp;page=</code></li>
            <li><code>GET /api/v1/invoices/{id}</code> — detail + lignes</li>
            <li><code>GET /api/v1/customers?q=&amp;page=</code></li>
        </ul>
        <h4 class="mt-3">Exemple curl</h4>
        <pre class="text-mono text-sm" style="background:var(--bg-subtle);padding:12px;border-radius:8px;overflow-x:auto;">curl -H "Authorization: Bearer YOUR_TOKEN" \
     "<?= url('/api/v1/products?q=pain&per_page=10') ?>"</pre>
    </div>
</div>
