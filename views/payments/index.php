<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-arrow-down"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Total encaissements</div>
            <div class="kpi-value"><?= formatMoney($totalIn) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-arrow-up"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Total decaissements</div>
            <div class="kpi-value"><?= formatMoney($totalOut) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-balance-scale"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Solde net</div>
            <div class="kpi-value"><?= formatMoney($totalIn - $totalOut) ?></div>
        </div>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/payments') ?>?type='+this.value+'&method=<?= $methodFilter ?>'">
            <option value="">Tous types</option>
            <option value="incoming" <?= $typeFilter === 'incoming' ? 'selected' : '' ?>>Encaissements</option>
            <option value="outgoing" <?= $typeFilter === 'outgoing' ? 'selected' : '' ?>>Decaissements</option>
        </select>
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/payments') ?>?type=<?= $typeFilter ?>&method='+this.value">
            <option value="">Tous moyens</option>
            <?php foreach (['cash'=>'Especes','card'=>'Carte','check'=>'Cheque','transfer'=>'Virement','mobile'=>'Mobile'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $methodFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state"><div class="icon"><i class="fas fa-money-bill-wave"></i></div><h4>Aucun paiement</h4></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Facture</th><th>Tiers</th><th>Methode</th><th class="text-right">Montant</th><th>Par</th></tr></thead>
            <tbody>
            <?php foreach ($items as $p): ?>
            <tr>
                <td style="font-size:12px;"><?= formatDate($p['payment_date']) ?></td>
                <td>
                    <span class="badge badge-<?= $p['type'] === 'incoming' ? 'success' : 'danger' ?>">
                        <?= $p['type'] === 'incoming' ? 'Encaissement' : 'Decaissement' ?>
                    </span>
                </td>
                <td class="text-mono" style="font-size:12px;"><?= e($p['invoice_number'] ?? '-') ?></td>
                <td><?= e($p['client_name'] ?? $p['supplier_name'] ?? '-') ?></td>
                <td><span class="badge badge-info"><?= $p['method'] ?></span></td>
                <td class="text-right text-mono fw-bold <?= $p['type'] === 'incoming' ? 'text-success' : 'text-danger' ?>">
                    <?= $p['type'] === 'incoming' ? '+' : '-' ?><?= formatMoney($p['amount']) ?>
                </td>
                <td style="font-size:12px;"><?= e($p['user_name'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/payments') ?>?page=<?= $i ?>&type=<?= $typeFilter ?>&method=<?= $methodFilter ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
