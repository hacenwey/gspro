<div class="page-header" style="margin-bottom:12px;">
    <div>
        <h1 class="page-title"><i class="fas fa-headset"></i> <?= __('tickets.title') ?></h1>
        <p class="page-subtitle"><?= __('tickets.subtitle') ?></p>
    </div>
    <a href="<?= url('/tickets/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?= __('tickets.new') ?>
    </a>
</div>

<!-- Status filter tabs -->
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:4px;padding:8px 12px;overflow-x:auto;">
        <?php
        $filters = [
            ''             => ['label' => 'Tous',       'icon' => '',       'color' => ''],
            'open'         => ['label' => 'Ouverts',    'icon' => 'circle', 'color' => '#10B981'],
            'in_progress'  => ['label' => 'En cours',   'icon' => 'circle', 'color' => '#3B82F6'],
            'waiting'      => ['label' => 'En attente', 'icon' => 'circle', 'color' => '#F59E0B'],
            'resolved'     => ['label' => 'Resolus',    'icon' => 'circle', 'color' => '#8B5CF6'],
            'closed'       => ['label' => 'Fermes',     'icon' => 'circle', 'color' => '#94A3B8'],
        ];
        foreach ($filters as $key => $f):
            $isActive = ($status === $key) || ($key === '' && !$status);
            $count = $key === '' ? $counts['all'] : ($counts[$key] ?? 0);
        ?>
        <a href="<?= url('/tickets' . ($key ? '?status=' . $key : '')) ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:<?= $isActive ? '700' : '500' ?>;white-space:nowrap;transition:all 0.2s;text-decoration:none;
           <?= $isActive
               ? 'background:var(--primary);color:#fff;'
               : 'color:var(--text-secondary);background:transparent;' ?>">
            <?php if ($f['icon']): ?>
                <i class="fas fa-<?= $f['icon'] ?>" style="font-size:7px;<?= $isActive ? 'color:#fff;' : 'color:' . $f['color'] . ';' ?>"></i>
            <?php endif; ?>
            <?= $f['label'] ?>
            <span style="font-size:11px;font-weight:600;padding:1px 6px;border-radius:100px;
                <?= $isActive
                    ? 'background:rgba(255,255,255,0.25);color:#fff;'
                    : 'background:var(--bg-subtle);color:var(--text-muted);' ?>">
                <?= $count ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($tickets)): ?>
<div class="card">
    <div class="empty-state" style="padding:60px 20px;">
        <div class="icon" style="width:64px;height:64px;font-size:28px;"><i class="fas fa-headset"></i></div>
        <h4 style="margin-top:16px;font-size:18px;"><?= __('tickets.empty') ?></h4>
        <p style="color:var(--text-muted);margin:8px 0 20px;">Vous n'avez pas encore de demande de support.</p>
        <a href="<?= url('/tickets/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Creer un ticket
        </a>
    </div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px;">
    <?php foreach ($tickets as $t): ?>
    <?php
        $catLabel = match($t['category']) {
            'bug' => 'Bug', 'feature' => 'Fonctionnalite',
            'billing' => 'Facturation', 'urgent' => 'Urgent',
            default => 'General'
        };
        $catIcon = match($t['category']) {
            'bug' => 'bug', 'feature' => 'lightbulb',
            'billing' => 'receipt', 'urgent' => 'exclamation-triangle',
            default => 'circle-question'
        };
        $catClass = match($t['category']) {
            'bug' => 'danger', 'feature' => 'info',
            'billing' => 'warning', 'urgent' => 'danger',
            default => 'secondary'
        };
        $prioLabel = match($t['priority']) {
            'low' => 'Basse', 'high' => 'Haute', 'critical' => 'Critique', default => 'Moyenne'
        };
        $prioClass = match($t['priority']) {
            'low' => 'secondary', 'high' => 'warning', 'critical' => 'danger', default => 'info'
        };
        $statLabel = match($t['status']) {
            'open' => 'Ouvert', 'in_progress' => 'En cours',
            'waiting' => 'En attente', 'resolved' => 'Resolu',
            'closed' => 'Ferme', default => $t['status']
        };
        $statColor = match($t['status']) {
            'open' => '#10B981', 'in_progress' => '#3B82F6',
            'waiting' => '#F59E0B', 'resolved' => '#8B5CF6',
            'closed' => '#94A3B8', default => '#94A3B8'
        };
    ?>
    <a href="<?= url('/tickets/view/' . $t['id']) ?>" class="card" style="display:block;text-decoration:none;transition:all 0.2s;cursor:pointer;border-left:3px solid <?= $statColor ?>;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.06)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="padding:18px 20px;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;">
                <!-- Left: ticket info -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:12px;font-weight:700;color:var(--primary);font-family:var(--font-mono);">#<?= substr($t['id'], 0, 8) ?></span>
                        <span class="badge badge-<?= $catClass ?>" style="font-size:10px;">
                            <i class="fas fa-<?= $catIcon ?>" style="margin-right:3px;font-size:9px;"></i> <?= $catLabel ?>
                        </span>
                        <span class="badge badge-<?= $prioClass ?>" style="font-size:10px;"><?= $prioLabel ?></span>
                    </div>
                    <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= e($t['subject']) ?>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted);">
                        Par <strong><?= e($t['user_name']) ?></strong> &middot; <?= formatDateTime($t['created_at']) ?>
                    </div>
                </div>
                <!-- Right: status -->
                <div style="text-align:right;flex-shrink:0;">
                    <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:<?= $statColor ?>15;color:<?= $statColor ?>;">
                        <i class="fas fa-circle" style="font-size:6px;"></i> <?= $statLabel ?>
                    </div>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
