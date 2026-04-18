<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-right-to-bracket" style="color:#0EA5E9;margin-right:8px;"></i> Connexions</h1>
    <div style="font-size:12px;color:var(--text-muted);">
        Journal des connexions de tous les clients
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-right-to-bracket"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
            <div class="stat-label">Total connexions</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['success']) ?></div>
            <div class="stat-label">Reussies</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-circle-xmark"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['failed']) ?></div>
            <div class="stat-label">Echouees</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(139,92,246,0.1);color:#8B5CF6;"><i class="fas fa-clock"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['last_24h']) ?></div>
            <div class="stat-label">Dernieres 24h</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#F59E0B;"><i class="fas fa-calendar-week"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['last_7d']) ?></div>
            <div class="stat-label">7 derniers jours</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(6,182,212,0.1);color:#06B6D4;"><i class="fas fa-globe"></i></div>
        <div>
            <div class="stat-value"><?= number_format($stats['unique_ips']) ?></div>
            <div class="stat-label">IPs uniques</div>
        </div>
    </div>
</div>

<!-- Top tenants -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3><i class="fas fa-trophy" style="color:#F59E0B;margin-right:4px;"></i> Clients les plus actifs (30 jours)</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($topTenants)): ?>
        <div class="empty-state" style="padding:30px;"><p>Aucune connexion enregistree</p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Client</th><th>Slug</th><th class="text-right">Total</th><th class="text-right">Reussies</th><th>Derniere connexion</th></tr></thead>
            <tbody>
            <?php foreach ($topTenants as $tt): ?>
            <tr>
                <td style="font-weight:600;"><?= e($tt['company_name'] ?? '(supprime)') ?></td>
                <td class="text-mono" style="font-size:12px;"><?= e($tt['tenant_slug'] ?? '-') ?></td>
                <td class="text-right text-mono" style="font-weight:700;"><?= number_format((int)$tt['total_conn']) ?></td>
                <td class="text-right text-mono" style="color:#10B981;"><?= number_format((int)$tt['ok_conn']) ?></td>
                <td style="font-size:12px;"><?= formatDateTime($tt['last_conn']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Filters + log -->
<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <h3><i class="fas fa-list" style="color:#0EA5E9;margin-right:4px;"></i> Historique des connexions</h3>
        <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <select name="tenant" class="form-control" style="font-size:13px;padding:6px 10px;">
                <option value="">Tous les clients</option>
                <?php foreach ($tenantsList as $tn): ?>
                <option value="<?= e($tn['id']) ?>" <?= $tenantFilter === $tn['id'] ? 'selected' : '' ?>>
                    <?= e($tn['company_name']) ?> (<?= e($tn['slug']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <select name="success" class="form-control" style="font-size:13px;padding:6px 10px;">
                <option value="">Tous</option>
                <option value="1" <?= $successFilter === '1' ? 'selected' : '' ?>>Reussies</option>
                <option value="0" <?= $successFilter === '0' ? 'selected' : '' ?>>Echouees</option>
            </select>
            <select name="limit" class="form-control" style="font-size:13px;padding:6px 10px;">
                <?php foreach ([50, 100, 200, 500] as $n): ?>
                <option value="<?= $n ?>" <?= $limit === $n ? 'selected' : '' ?>><?= $n ?> lignes</option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary" style="background:#DC2626;border-color:#DC2626;">
                <i class="fas fa-filter"></i> Filtrer
            </button>
            <?php if ($tenantFilter !== '' || $successFilter !== ''): ?>
            <a href="<?= adminUrl('/connections') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-xmark"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding:40px;">
            <div class="icon"><i class="fas fa-ghost"></i></div>
            <p>Aucune connexion trouvee</p>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Utilisateur</th>
                    <th>IP</th>
                    <th>Navigateur</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <?php
                $ua = $log['user_agent'] ?? '';
                $shortUa = '-';
                if ($ua) {
                    if (stripos($ua, 'firefox') !== false)      $shortUa = 'Firefox';
                    elseif (stripos($ua, 'edg') !== false)      $shortUa = 'Edge';
                    elseif (stripos($ua, 'chrome') !== false)   $shortUa = 'Chrome';
                    elseif (stripos($ua, 'safari') !== false)   $shortUa = 'Safari';
                    elseif (stripos($ua, 'curl') !== false)     $shortUa = 'curl';
                    else $shortUa = substr($ua, 0, 20);
                    if (stripos($ua, 'mobile') !== false) $shortUa .= ' (Mobile)';
                }
            ?>
            <tr>
                <td style="font-size:12px;white-space:nowrap;"><?= formatDateTime($log['created_at']) ?></td>
                <td>
                    <?php if ($log['actor_type'] === 'super_admin'): ?>
                        <span class="badge badge-warning" style="font-size:10px;">SUPER ADMIN</span>
                    <?php elseif ($log['company_name']): ?>
                        <div style="font-weight:600;font-size:13px;"><?= e($log['company_name']) ?></div>
                        <div class="text-mono" style="font-size:11px;color:var(--text-muted);"><?= e($log['tenant_slug']) ?></div>
                    <?php else: ?>
                        <span class="text-mono" style="font-size:11px;color:var(--text-muted);"><?= e($log['tenant_slug'] ?? '?') ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($log['actor_type'] === 'super_admin'): ?>
                        <span class="badge badge-danger" style="font-size:10px;">ADMIN</span>
                    <?php else: ?>
                        <span class="badge badge-info" style="font-size:10px;">UTILISATEUR</span>
                    <?php endif; ?>
                </td>
                <td class="text-mono" style="font-size:12px;"><?= e($log['username']) ?></td>
                <td class="text-mono" style="font-size:12px;"><?= e($log['ip_address'] ?? '-') ?></td>
                <td style="font-size:12px;color:var(--text-muted);" title="<?= e($ua) ?>"><?= e($shortUa) ?></td>
                <td>
                    <?php if ($log['success']): ?>
                    <span class="badge badge-success" style="font-size:10px;"><i class="fas fa-check"></i> OK</span>
                    <?php else: ?>
                    <span class="badge badge-danger" style="font-size:10px;"><i class="fas fa-xmark"></i> ECHEC</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
