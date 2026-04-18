<div class="admin-header page-header">
    <h1><i class="fas fa-database" style="color:#DC2626;margin-right:8px;"></i> Migrations</h1>
    <div class="page-header-actions">
        <a href="<?= adminUrl('/migrations/run?scope=master') ?>" class="btn btn-sm btn-primary"><i class="fas fa-play"></i> Master</a>
        <a href="<?= adminUrl('/migrations/run?scope=all_tenants') ?>" class="btn btn-sm btn-primary" onclick="return confirm('Executer sur tous les tenants actifs ?')"><i class="fas fa-play-circle"></i> Tous les tenants</a>
        <a href="<?= adminUrl('/migrations/baseline?scope=master') ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Marquer toutes les migrations master comme appliquees sans les executer ?')">Baseline master</a>
        <a href="<?= adminUrl('/migrations/baseline?scope=all_tenants') ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Baseline tous les tenants ?')">Baseline tenants</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3>Master DB</h3></div>
    <div class="card-body">
        <?php if (empty($masterPending)): ?>
            <p class="text-muted mb-0"><i class="fas fa-check-circle text-success"></i> A jour.</p>
        <?php else: ?>
            <p><strong><?= count($masterPending) ?> migration(s) en attente :</strong></p>
            <ul class="mb-0">
                <?php foreach ($masterPending as $v): ?>
                    <li class="text-mono text-sm"><?= e($v) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Tenants (<?= count($tenants) ?>)</h3></div>
    <div class="card-body p-0">
        <table class="table">
            <thead><tr><th>Slug</th><th>Entreprise</th><th>En attente</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            <?php foreach ($tenants as $t): ?>
            <tr>
                <td class="text-mono fw-bold"><?= e($t['slug']) ?></td>
                <td><?= e($t['company_name']) ?></td>
                <td>
                    <?php if (!empty($t['error'])): ?>
                        <span class="badge badge-danger">Erreur: <?= e($t['error']) ?></span>
                    <?php elseif (empty($t['pending'])): ?>
                        <span class="badge badge-success">A jour</span>
                    <?php else: ?>
                        <span class="badge badge-warning"><?= count($t['pending']) ?> en attente</span>
                        <div class="text-xs text-muted mt-1"><?= e(implode(', ', $t['pending'])) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <?php if (!empty($t['pending']) && empty($t['error'])): ?>
                        <a href="<?= adminUrl('/migrations/run?scope=tenant&slug=' . urlencode($t['slug'])) ?>" class="btn btn-sm btn-primary"><i class="fas fa-play"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
