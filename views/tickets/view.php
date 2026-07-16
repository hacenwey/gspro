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
$isClosed = in_array($ticket['status'], ['closed', 'resolved']);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-ticket"></i>
            Ticket #<?= substr($ticket['id'], 0, 8) ?>
        </h1>
        <p class="page-subtitle"><?= e($ticket['subject']) ?></p>
    </div>
    <a href="<?= url('/tickets') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<!-- Ticket info bar -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
        <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
            <div>
                <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Statut</span><br>
                <span class="badge badge-<?= $statClass ?>" style="font-size:13px;padding:5px 12px;margin-top:4px;"><?= $statLabel ?></span>
            </div>
            <div style="width:1px;height:32px;background:var(--border);"></div>
            <div>
                <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Categorie</span><br>
                <span style="font-size:14px;font-weight:600;margin-top:4px;display:inline-block;"><?= $catLabel ?></span>
            </div>
            <div style="width:1px;height:32px;background:var(--border);"></div>
            <div>
                <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Priorite</span><br>
                <span class="badge badge-<?= $prioClass ?>" style="font-size:13px;padding:5px 12px;margin-top:4px;"><?= $prioLabel ?></span>
            </div>
            <div style="width:1px;height:32px;background:var(--border);"></div>
            <div>
                <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Cree le</span><br>
                <span style="font-size:13px;margin-top:4px;display:inline-block;"><?= formatDateTime($ticket['created_at']) ?></span>
            </div>
            <div>
                <span style="font-size:11px;text-transform:uppercase;color:var(--text-muted);font-weight:600;">Par</span><br>
                <span style="font-size:13px;font-weight:600;margin-top:4px;display:inline-block;"><?= e($ticket['user_name']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Messages thread -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><i class="fas fa-comments"></i> Conversation (<?= count($messages) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <?php foreach ($messages as $i => $msg): ?>
        <?php $isAdmin = $msg['sender_type'] === 'admin'; ?>
        <div style="padding:20px 24px;<?= $i > 0 ? 'border-top:1px solid var(--border);' : '' ?><?= $isAdmin ? 'background:rgba(79,70,229,0.03);' : '' ?>">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;
                    background:<?= $isAdmin ? 'linear-gradient(135deg,#4F46E5,#4338CA)' : 'linear-gradient(135deg,#047857,#065F46)' ?>;">
                    <?= $isAdmin ? '<i class="fas fa-headset" style="font-size:14px;"></i>' : strtoupper(substr($msg['sender_name'], 0, 2)) ?>
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">
                        <?= e($msg['sender_name']) ?>
                        <?php if ($isAdmin): ?>
                        <span style="font-size:11px;background:var(--primary);color:#fff;padding:2px 8px;border-radius:100px;margin-left:4px;">Support</span>
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

<!-- Reply form -->
<?php if (!$isClosed): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-reply"></i> Repondre</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/tickets/reply/' . $ticket['id']) ?>">
            <div class="form-group">
                <textarea name="message" class="form-control" rows="4" required
                    placeholder="Ecrivez votre reponse..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:24px;color:var(--text-muted);">
        <i class="fas fa-lock" style="font-size:20px;margin-bottom:8px;display:block;"></i>
        Ce ticket est <?= $ticket['status'] === 'resolved' ? 'resolu' : 'ferme' ?>. Vous ne pouvez plus y repondre.
        <br><a href="<?= url('/tickets/create') ?>" class="btn btn-sm btn-primary" style="margin-top:12px;">
            <i class="fas fa-plus"></i> Creer un nouveau ticket
        </a>
    </div>
</div>
<?php endif; ?>
