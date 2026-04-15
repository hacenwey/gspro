<div class="admin-header">
    <h1><i class="fas fa-chart-pie" style="color:#DC2626;margin-right:8px;"></i> Tableau de bord</h1>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-building"></i></div>
        <div>
            <div class="stat-value"><?= $totalTenants ?></div>
            <div class="stat-label">Clients total</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="stat-value"><?= $activeTenants ?></div>
            <div class="stat-label">Clients actifs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-ban"></i></div>
        <div>
            <div class="stat-value"><?= $totalTenants - $activeTenants ?></div>
            <div class="stat-label">Clients inactifs</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Recent Tenants -->
    <div class="card">
        <div class="card-header">
            <h3>Derniers clients ajoutes</h3>
            <a href="<?= adminUrl('/tenants/create') ?>" class="btn btn-sm btn-primary" style="background:#DC2626;border-color:#DC2626;"><i class="fas fa-plus"></i> Nouveau</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($recentTenants)): ?>
            <div class="empty-state" style="padding:30px;"><p>Aucun client</p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Slug</th><th>Entreprise</th><th>Plan</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recentTenants as $t): ?>
                <tr>
                    <td>
                        <a href="<?= APP_BASE . '/' . $t['slug'] . '/login' ?>" target="_blank" class="text-mono" style="font-weight:600;color:var(--primary);">
                            <?= e($t['slug']) ?> <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                        </a>
                    </td>
                    <td><?= e($t['company_name']) ?></td>
                    <td><span class="badge badge-info"><?= strtoupper($t['plan']) ?></span></td>
                    <td>
                        <span class="badge badge-<?= $t['is_active'] ? 'success' : 'danger' ?>">
                            <?= $t['is_active'] ? 'Actif' : 'Inactif' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="card">
        <div class="card-header"><h3>Activite recente</h3></div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($recentLog)): ?>
            <div class="empty-state" style="padding:30px;"><p>Aucune activite</p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Action</th><th>Client</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentLog as $log): ?>
                <tr>
                    <td style="font-size:12px;"><?= e($log['action']) ?></td>
                    <td style="font-weight:600;"><?= e($log['slug'] ?? $log['company_name'] ?? '-') ?></td>
                    <td style="font-size:12px;"><?= formatDateTime($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
