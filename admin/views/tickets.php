<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h1><i class="fas fa-headset" style="color:#DC2626;margin-right:8px;"></i> Tickets Support</h1>
</div>

<!-- Status filters -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
    <?php
    $filters = [
        '' => ['label' => 'Tous', 'icon' => '', 'color' => ''],
        'open' => ['label' => 'Ouverts', 'icon' => 'circle', 'color' => '#10B981'],
        'in_progress' => ['label' => 'En cours', 'icon' => 'circle', 'color' => '#3B82F6'],
        'waiting' => ['label' => 'En attente', 'icon' => 'circle', 'color' => '#F59E0B'],
        'resolved' => ['label' => 'Resolus', 'icon' => 'circle', 'color' => '#8B5CF6'],
        'closed' => ['label' => 'Fermes', 'icon' => 'circle', 'color' => '#94A3B8'],
    ];
    foreach ($filters as $key => $f):
        $isActive = ($status === $key) || ($key === '' && !$status);
        $count = $key === '' ? $counts['all'] : ($counts[$key] ?? 0);
    ?>
    <a href="<?= adminUrl('/tickets' . ($key ? '?status=' . $key : '')) ?>"
        class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-secondary' ?>"
        style="<?= $isActive ? 'background:#DC2626;border-color:#DC2626;' : '' ?>">
        <?php if ($f['icon']): ?><i class="fas fa-<?= $f['icon'] ?>" style="font-size:8px;color:<?= $isActive ? '#fff' : $f['color'] ?>;"></i><?php endif; ?>
        <?= $f['label'] ?>
        <span style="margin-left:4px;opacity:0.7;"><?= $count ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($tickets)): ?>
<div class="card">
    <div class="empty-state">
        <div class="icon"><i class="fas fa-headset"></i></div>
        <h4>Aucun ticket</h4>
        <p>Aucune demande de support pour le moment.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Client</th>
                    <th>Sujet</th>
                    <th>Cat.</th>
                    <th>Priorite</th>
                    <th>Statut</th>
                    <th>Messages</th>
                    <th>Date</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
            <?php
                $catLabel = match($t['category']) { 'bug' => 'Bug', 'feature' => 'Feature', 'billing' => 'Billing', 'urgent' => 'Urgent', default => 'General' };
                $catClass = match($t['category']) { 'bug' => 'danger', 'feature' => 'info', 'billing' => 'warning', 'urgent' => 'danger', default => 'secondary' };
                $prioLabel = match($t['priority']) { 'low' => 'Basse', 'high' => 'Haute', 'critical' => 'Critique', default => 'Moyenne' };
                $prioClass = match($t['priority']) { 'low' => 'secondary', 'high' => 'warning', 'critical' => 'danger', default => 'info' };
                $statLabel = match($t['status']) { 'open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting' => 'En attente', 'resolved' => 'Resolu', 'closed' => 'Ferme', default => $t['status'] };
                $statClass = match($t['status']) { 'open' => 'success', 'in_progress' => 'primary', 'waiting' => 'warning', 'resolved' => 'info', 'closed' => 'secondary', default => 'secondary' };
            ?>
            <tr style="<?= $t['priority'] === 'critical' ? 'background:rgba(239,68,68,0.04);' : '' ?>">
                <td>
                    <a href="<?= adminUrl('/tickets/view/' . $t['id']) ?>" class="text-mono" style="font-weight:700;font-size:12px;color:var(--primary);">
                        #<?= substr($t['id'], 0, 8) ?>
                    </a>
                </td>
                <td>
                    <div style="font-weight:600;font-size:13px;"><?= e($t['company_name'] ?? '-') ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= e($t['slug'] ?? '') ?></div>
                </td>
                <td style="font-weight:500;max-width:200px;">
                    <a href="<?= adminUrl('/tickets/view/' . $t['id']) ?>" style="color:var(--text);">
                        <?= e($t['subject']) ?>
                    </a>
                    <div style="font-size:11px;color:var(--text-muted);"><?= e($t['user_name']) ?></div>
                </td>
                <td><span class="badge badge-<?= $catClass ?>" style="font-size:10px;"><?= $catLabel ?></span></td>
                <td>
                    <?php if ($t['priority'] === 'critical'): ?>
                    <span class="badge badge-danger" style="font-size:10px;animation:pulse 1.5s infinite;">
                        <i class="fas fa-exclamation-triangle"></i> <?= $prioLabel ?>
                    </span>
                    <?php else: ?>
                    <span class="badge badge-<?= $prioClass ?>" style="font-size:10px;"><?= $prioLabel ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $statClass ?>"><?= $statLabel ?></span></td>
                <td style="text-align:center;">
                    <span class="text-mono" style="font-weight:600;"><?= $t['msg_count'] ?></span>
                </td>
                <td style="font-size:11px;color:var(--text-muted);">
                    <?= formatDateTime($t['created_at']) ?>
                    <?php if ($t['last_message_at'] && $t['last_message_at'] !== $t['created_at']): ?>
                    <div style="font-size:10px;"><i class="fas fa-reply" style="font-size:8px;"></i> <?= formatDateTime($t['last_message_at']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-right">
                    <a href="<?= adminUrl('/tickets/view/' . $t['id']) ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity:1; }
    50% { opacity:0.6; }
}
</style>
<?php endif; ?>
