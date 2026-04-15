<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/caisse') ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Retour a la caisse</a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($sessions)): ?>
        <div class="empty-state"><div class="icon"><i class="fas fa-cash-register"></i></div><p>Aucune session de caisse</p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Caissier</th><th>Ouverture</th><th>Cloture</th><th class="text-right">Fond initial</th><th class="text-right">Solde cloture</th><th class="text-right">Ecart</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?= e($s['full_name']) ?></td>
                <td style="font-size:12px;"><?= formatDateTime($s['opened_at']) ?></td>
                <td style="font-size:12px;"><?= $s['closed_at'] ? formatDateTime($s['closed_at']) : '-' ?></td>
                <td class="text-right text-mono"><?= formatMoney($s['opening_balance']) ?></td>
                <td class="text-right text-mono"><?= $s['closing_balance'] !== null ? formatMoney($s['closing_balance']) : '-' ?></td>
                <td class="text-right text-mono fw-bold <?= ($s['difference'] ?? 0) < 0 ? 'text-danger' : (($s['difference'] ?? 0) > 0 ? 'text-success' : '') ?>">
                    <?= $s['difference'] !== null ? formatMoney($s['difference']) : '-' ?>
                </td>
                <td><span class="badge badge-<?= $s['status'] === 'open' ? 'success' : 'secondary' ?>"><?= $s['status'] === 'open' ? 'Ouverte' : 'Cloturee' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
