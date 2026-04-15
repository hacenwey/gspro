<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/suppliers') ?>" style="color: var(--text-muted); font-size: 13px;"><i class="fas fa-arrow-left"></i> Retour</a>
    <a href="<?= url('/suppliers/edit/' . $supplier['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> Modifier</a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Informations fournisseur</h3></div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label">Raison sociale</span><span class="value"><?= e($supplier['company_name']) ?></span></li>
                <li><span class="label">Contact</span><span class="value"><?= e($supplier['contact_name'] ?? '-') ?></span></li>
                <li><span class="label">Telephone</span><span class="value"><?= e($supplier['phone'] ?? '-') ?></span></li>
                <li><span class="label">Email</span><span class="value"><?= e($supplier['email'] ?? '-') ?></span></li>
                <li><span class="label">Delai paiement</span><span class="value"><?= $supplier['payment_terms'] ?> jours</span></li>
                <li><span class="label">Solde</span><span class="value text-mono fw-bold <?= $supplier['balance'] < 0 ? 'text-danger' : '' ?>"><?= formatMoney($supplier['balance']) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Dettes en cours</h3></div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($debts)): ?>
            <div class="empty-state" style="padding:30px;"><p>Aucune dette</p></div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Echeance</th><th class="text-right">Montant</th><th class="text-right">Reste</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($debts as $d): ?>
                <tr>
                    <td><?= formatDate($d['due_date']) ?></td>
                    <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                    <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                    <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3>Bons de commande</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($orders)): ?>
        <div class="empty-state" style="padding:30px;"><p>Aucun bon de commande</p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>N°</th><th>Date</th><th class="text-right">Total</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="text-mono"><?= e($o['number']) ?></td>
                <td><?= formatDate($o['order_date']) ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($o['total']) ?></td>
                <td><span class="badge badge-<?= $o['status'] === 'received' ? 'success' : ($o['status'] === 'sent' ? 'info' : 'secondary') ?>"><?= $o['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
