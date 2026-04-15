<div class="d-flex justify-between align-center mb-3">
    <a href="<?= url('/clients') ?>" style="color: var(--text-muted); font-size: 13px;"><i class="fas fa-arrow-left"></i> Retour aux clients</a>
    <div class="btn-group">
        <a href="<?= url('/clients/edit/' . $client['id']) ?>" class="btn btn-sm btn-secondary"><i class="fas fa-pen"></i> Modifier</a>
        <a href="<?= url('/invoices/create?customer_id=' . $client['id']) ?>" class="btn btn-sm btn-primary"><i class="fas fa-file-invoice"></i> Nouvelle facture</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3>Informations client</h3>
            <span class="badge <?= $client['category'] === 'vip' ? 'badge-vip' : ($client['category'] === 'regular' ? 'badge-primary' : 'badge-secondary') ?>">
                <?= strtoupper($client['category']) ?>
            </span>
        </div>
        <div class="card-body">
            <ul class="stats-list">
                <li><span class="label">Nom complet</span><span class="value"><?= e(($client['first_name'] ? $client['first_name'] . ' ' : '') . $client['last_name']) ?></span></li>
                <li><span class="label">Type</span><span class="value"><?= $client['type'] === 'company' ? 'Entreprise' : 'Particulier' ?></span></li>
                <li><span class="label">Telephone</span><span class="value"><?= e($client['phone'] ?? '-') ?></span></li>
                <li><span class="label">Email</span><span class="value"><?= e($client['email'] ?? '-') ?></span></li>
                <li><span class="label">Adresse</span><span class="value"><?= e($client['address'] ?? '-') ?></span></li>
                <?php if ($client['tax_id']): ?>
                <li><span class="label">NIF/RC</span><span class="value"><?= e($client['tax_id']) ?></span></li>
                <?php endif; ?>
                <li><span class="label">Points fidelite</span><span class="value"><?= $client['loyalty_points'] ?></span></li>
                <li><span class="label">Plafond credit</span><span class="value"><?= formatMoney($client['credit_limit']) ?></span></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Statistiques</h3></div>
        <div class="card-body">
            <div class="kpi-grid" style="grid-template-columns: 1fr 1fr;">
                <div style="text-align:center;padding:16px;border-radius:var(--radius);background:var(--bg);">
                    <div style="font-size:24px;font-weight:800;font-family:var(--font-mono);color:var(--primary);"><?= formatMoney($stats['total_purchases']) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Total achats</div>
                </div>
                <div style="text-align:center;padding:16px;border-radius:var(--radius);background:var(--bg);">
                    <div style="font-size:24px;font-weight:800;font-family:var(--font-mono);"><?= $stats['invoice_count'] ?></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Factures</div>
                </div>
            </div>
            <div style="text-align:center;padding:20px;margin-top:16px;border-radius:var(--radius);background:<?= $client['balance'] < 0 ? 'rgba(231,76,60,0.08)' : 'rgba(39,174,96,0.08)' ?>;">
                <div style="font-size:13px;color:var(--text-secondary);">Solde courant</div>
                <div style="font-size:28px;font-weight:800;font-family:var(--font-mono);color:var(--<?= $client['balance'] < 0 ? 'danger' : 'success' ?>);">
                    <?= formatMoney($client['balance']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debts -->
<?php if (!empty($debts)): ?>
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-exclamation-circle" style="color: var(--danger); margin-right: 8px;"></i>Creances en cours</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Echeance</th><th class="text-right">Montant</th><th class="text-right">Paye</th><th class="text-right">Reste</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($debts as $d): ?>
            <tr>
                <td><?= formatDate($d['due_date']) ?></td>
                <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                <td class="text-right text-mono"><?= formatMoney($d['paid_amount']) ?></td>
                <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Invoices -->
<div class="card mt-3">
    <div class="card-header"><h3>Dernieres factures</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($invoices)): ?>
        <div class="empty-state" style="padding:30px;"><p>Aucune facture</p></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>N°</th><th>Type</th><th>Date</th><th class="text-right">Total</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
                <td class="text-mono" style="font-size:12px;"><?= e($inv['number']) ?></td>
                <td><span class="badge badge-<?= $inv['type'] === 'invoice' ? 'primary' : ($inv['type'] === 'quote' ? 'info' : 'warning') ?>"><?= $inv['type'] === 'invoice' ? 'Facture' : ($inv['type'] === 'quote' ? 'Devis' : 'Avoir') ?></span></td>
                <td><?= formatDate($inv['issue_date']) ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($inv['total']) ?></td>
                <td><span class="badge badge-<?= invoiceStatusClass($inv['status']) ?>"><?= invoiceStatusLabel($inv['status']) ?></span></td>
                <td><a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
