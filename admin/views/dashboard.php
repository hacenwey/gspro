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
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#F59E0B;"><i class="fas fa-headset"></i></div>
        <div>
            <div class="stat-value"><?= $openTickets ?><span style="font-size:14px;color:var(--text-muted);font-weight:400;"> / <?= $totalTickets ?></span></div>
            <div class="stat-label">Tickets ouverts</div>
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

    <!-- Recent Tickets -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-headset" style="color:#F59E0B;margin-right:4px;"></i> Tickets recents</h3>
            <a href="<?= adminUrl('/tickets') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-right"></i> Voir tous</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($recentTickets)): ?>
            <div class="empty-state" style="padding:30px;"><p>Aucun ticket</p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Ticket</th><th>Client</th><th>Sujet</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recentTickets as $tk): ?>
                <?php
                    $tkStatLabel = match($tk['status']) { 'open'=>'Ouvert','in_progress'=>'En cours','waiting'=>'Attente','resolved'=>'Resolu','closed'=>'Ferme',default=>$tk['status'] };
                    $tkStatClass = match($tk['status']) { 'open'=>'success','in_progress'=>'primary','waiting'=>'warning','resolved'=>'info','closed'=>'secondary',default=>'secondary' };
                ?>
                <tr>
                    <td>
                        <a href="<?= adminUrl('/tickets/view/' . $tk['id']) ?>" class="text-mono" style="font-weight:700;font-size:11px;color:var(--primary);">
                            #<?= substr($tk['id'], 0, 8) ?>
                        </a>
                    </td>
                    <td style="font-size:12px;"><?= e($tk['company_name'] ?? '-') ?></td>
                    <td style="font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <a href="<?= adminUrl('/tickets/view/' . $tk['id']) ?>" style="color:var(--text);"><?= e($tk['subject']) ?></a>
                    </td>
                    <td><span class="badge badge-<?= $tkStatClass ?>" style="font-size:10px;"><?= $tkStatLabel ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Activity Log -->
<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3><i class="fas fa-clock-rotate-left" style="color:#0EA5E9;margin-right:4px;"></i> Activite recente</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($recentLog)): ?>
        <div class="empty-state" style="padding:30px;"><p>Aucune activite</p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Action</th><th>Client</th><th>Details</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentLog as $log): ?>
            <tr>
                <td><span class="badge badge-secondary" style="font-size:11px;"><?= e($log['action']) ?></span></td>
                <td style="font-weight:600;"><?= e($log['slug'] ?? $log['company_name'] ?? '-') ?></td>
                <td style="font-size:12px;color:var(--text-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($log['details'] ?? '') ?></td>
                <td style="font-size:12px;"><?= formatDateTime($log['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
