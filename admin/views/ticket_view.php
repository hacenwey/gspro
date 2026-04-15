<?php
$statLabel = match($ticket['status']) {
    'open' => 'Ouvert', 'in_progress' => 'En cours',
    'waiting' => 'En attente', 'resolved' => 'Resolu',
    'closed' => 'Ferme', default => $ticket['status']
};
$statClass = match($ticket['status']) {
    'open' => 'success', 'in_progress' => 'primary',
    'waiting' => 'warning', 'resolved' => 'info',
    'closed' => 'secondary', default => 'secondary'
};
$catLabel = match($ticket['category']) {
    'bug' => 'Bug', 'feature' => 'Fonctionnalite',
    'billing' => 'Facturation', 'urgent' => 'Urgent', default => 'General'
};
$prioLabel = match($ticket['priority']) {
    'low' => 'Basse', 'high' => 'Haute', 'critical' => 'Critique', default => 'Moyenne'
};
$prioClass = match($ticket['priority']) {
    'low' => 'secondary', 'high' => 'warning', 'critical' => 'danger', default => 'info'
};
$isClosed = in_array($ticket['status'], ['closed']);
?>

<div class="admin-header" style="display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h1>
            <i class="fas fa-ticket" style="color:#DC2626;margin-right:8px;"></i>
            Ticket #<?= substr($ticket['id'], 0, 8) ?>
        </h1>
        <p style="color:var(--text-muted);font-size:14px;margin-top:4px;"><?= e($ticket['subject']) ?></p>
    </div>
    <a href="<?= adminUrl('/tickets') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
</div>

<!-- Info bar -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px;">
    <div class="stat-card" style="padding:16px;">
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Client</div>
            <div style="font-weight:700;font-size:15px;"><?= e($ticket['company_name'] ?? '-') ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= e($ticket['slug'] ?? '') ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Auteur</div>
            <div style="font-weight:700;font-size:15px;"><?= e($ticket['user_name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= e($ticket['user_email'] ?? '') ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Statut / Priorite</div>
            <div style="display:flex;gap:6px;margin-top:4px;">
                <span class="badge badge-<?= $statClass ?>"><?= $statLabel ?></span>
                <span class="badge badge-<?= $prioClass ?>"><?= $prioLabel ?></span>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= $catLabel ?></div>
        </div>
    </div>
    <div class="stat-card" style="padding:16px;">
        <div>
            <div style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Dates</div>
            <div style="font-size:12px;">Cree: <?= formatDateTime($ticket['created_at']) ?></div>
            <?php if ($ticket['closed_at']): ?>
            <div style="font-size:12px;color:var(--text-muted);">Ferme: <?= formatDateTime($ticket['closed_at']) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick status change -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:12px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:13px;font-weight:600;color:var(--text-secondary);">Changer le statut:</span>
        <?php
        $statuses = [
            'open' => ['Ouvert', 'success'],
            'in_progress' => ['En cours', 'primary'],
            'waiting' => ['En attente', 'warning'],
            'resolved' => ['Resolu', 'info'],
            'closed' => ['Ferme', 'secondary'],
        ];
        foreach ($statuses as $sKey => $sVal):
            $isCurrentStatus = $ticket['status'] === $sKey;
        ?>
        <form method="POST" action="<?= adminUrl('/tickets/status/' . $ticket['id']) ?>" style="display:inline;">
            <input type="hidden" name="status" value="<?= $sKey ?>">
            <button type="submit" class="btn btn-sm btn-<?= $sVal[1] ?>"
                style="<?= $isCurrentStatus ? 'opacity:1;box-shadow:0 0 0 2px var(--primary);' : 'opacity:0.6;' ?>"
                <?= $isCurrentStatus ? 'disabled' : '' ?>>
                <?= $sVal[0] ?>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
</div>

<!-- Conversation -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fas fa-comments" style="color:#DC2626;"></i> Conversation (<?= count($messages) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php foreach ($messages as $i => $msg): ?>
        <?php $isAdmin = $msg['sender_type'] === 'admin'; ?>
        <div style="padding:20px 24px;<?= $i > 0 ? 'border-top:1px solid var(--border);' : '' ?><?= $isAdmin ? 'background:rgba(220,38,38,0.03);' : '' ?>">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;
                    background:<?= $isAdmin ? 'linear-gradient(135deg,#DC2626,#EF4444)' : 'linear-gradient(135deg,#10B981,#34D399)' ?>;">
                    <?= $isAdmin ? '<i class="fas fa-headset" style="font-size:14px;"></i>' : strtoupper(substr($msg['sender_name'], 0, 2)) ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:14px;">
                        <?= e($msg['sender_name']) ?>
                        <?php if ($isAdmin): ?>
                        <span style="font-size:11px;background:#DC2626;color:#fff;padding:2px 8px;border-radius:100px;margin-left:4px;">Admin</span>
                        <?php else: ?>
                        <span style="font-size:11px;background:var(--bg-subtle);color:var(--text-muted);padding:2px 8px;border-radius:100px;margin-left:4px;">Client</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);"><?= formatDateTime($msg['created_at']) ?></div>
                </div>
            </div>
            <div style="padding-left:46px;font-size:14px;line-height:1.7;color:var(--text-secondary);white-space:pre-wrap;"><?= e($msg['message']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Admin reply form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-reply" style="color:#DC2626;"></i> Repondre au client</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= adminUrl('/tickets/reply/' . $ticket['id']) ?>">
            <div class="form-group">
                <textarea name="message" class="form-control" rows="5" required
                    placeholder="Ecrivez votre reponse au client..."></textarea>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div class="form-group" style="margin-bottom:0;flex:0 0 auto;">
                    <label class="form-label" style="margin-bottom:4px;">Changer le statut apres envoi:</label>
                    <select name="status" class="form-control" style="width:auto;min-width:160px;">
                        <option value="">-- Ne pas changer --</option>
                        <option value="in_progress" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>En cours</option>
                        <option value="waiting">En attente (du client)</option>
                        <option value="resolved">Resolu</option>
                        <option value="closed">Ferme</option>
                    </select>
                </div>
                <div style="flex:1;"></div>
                <button type="submit" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
                    <i class="fas fa-paper-plane"></i> Envoyer la reponse
                </button>
            </div>
        </form>
    </div>
</div>
