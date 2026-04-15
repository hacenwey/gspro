<!-- KPI -->
<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
    <div class="kpi-card">
        <div class="kpi-icon <?= $typeFilter === 'receivable' ? 'orange' : 'red' ?>">
            <i class="fas fa-<?= $typeFilter === 'receivable' ? 'hand-holding-dollar' : 'file-invoice-dollar' ?>"></i>
        </div>
        <div class="kpi-info">
            <div class="kpi-label">Total <?= $typeFilter === 'receivable' ? 'creances' : 'dettes' ?></div>
            <div class="kpi-value"><?= formatMoney($totalDue) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-exclamation-circle"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">En retard</div>
            <div class="kpi-value"><?= $overdueCount ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-list"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Total lignes</div>
            <div class="kpi-value"><?= $total ?></div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="<?= url('/debts?type=receivable') ?>" class="tab <?= $typeFilter === 'receivable' ? 'active' : '' ?>"><i class="fas fa-arrow-down" style="margin-right:6px;color:var(--success);"></i>Creances clients</a>
    <a href="<?= url('/debts?type=payable') ?>" class="tab <?= $typeFilter === 'payable' ? 'active' : '' ?>"><i class="fas fa-arrow-up" style="margin-right:6px;color:var(--danger);"></i>Dettes fournisseurs</a>
</div>

<div class="toolbar">
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/debts') ?>?type=<?= $typeFilter ?>&status='+this.value">
            <option value="">Tous statuts</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>En attente</option>
            <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>>Partiel</option>
            <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>En retard</option>
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paye</option>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <h4>Aucune <?= $typeFilter === 'receivable' ? 'creance' : 'dette' ?></h4>
        </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?= $typeFilter === 'receivable' ? 'Client' : 'Fournisseur' ?></th>
                    <th>Echeance</th>
                    <th class="text-right">Montant</th>
                    <th class="text-right">Paye</th>
                    <th class="text-right">Reste du</th>
                    <th>Statut</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $d): ?>
            <tr>
                <td style="font-weight:600;">
                    <?= $typeFilter === 'receivable'
                        ? e(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))
                        : e($d['company_name'] ?? '') ?>
                </td>
                <td>
                    <?= formatDate($d['due_date']) ?>
                    <?php if ($d['status'] === 'overdue'): ?>
                    <div style="font-size:11px;color:var(--danger);">
                        <?php $days = (int)((time() - strtotime($d['due_date'])) / 86400); ?>
                        <?= $days ?> jour(s) de retard
                    </div>
                    <?php endif; ?>
                </td>
                <td class="text-right text-mono"><?= formatMoney($d['total_amount']) ?></td>
                <td class="text-right text-mono text-success"><?= formatMoney($d['paid_amount']) ?></td>
                <td class="text-right text-mono fw-bold text-danger"><?= formatMoney($d['remaining']) ?></td>
                <td><span class="badge badge-<?= debtStatusClass($d['status']) ?>"><?= $d['status'] ?></span></td>
                <td class="text-right">
                    <?php if ($d['status'] !== 'paid'): ?>
                    <button class="btn btn-sm btn-success" onclick="openPayModal('<?= $d['id'] ?>', <?= $d['remaining'] ?>)">
                        <i class="fas fa-money-bill"></i> Payer
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/debts') ?>?page=<?= $i ?>&type=<?= $typeFilter ?>&status=<?= $statusFilter ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="payDebtModal">
    <div class="modal">
        <div class="modal-header"><h3>Enregistrer un paiement</h3><button class="modal-close" onclick="closeModal('payDebtModal')">&times;</button></div>
        <form method="POST" id="payDebtForm" action="">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Montant restant du</label>
                    <div class="text-mono fw-bold text-danger" style="font-size:20px;" id="debtRemaining"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Montant du paiement *</label>
                    <input type="number" name="amount" id="debtPayAmount" class="form-control" step="0.01" min="0.01" required style="font-size:18px;font-weight:700;font-family:var(--font-mono);text-align:center;">
                </div>
                <div class="form-group">
                    <label class="form-label">Mode de paiement</label>
                    <select name="method" class="form-control">
                        <option value="cash">Especes</option>
                        <option value="card">Carte</option>
                        <option value="check">Cheque</option>
                        <option value="transfer">Virement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('payDebtModal')">Annuler</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Valider</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(debtId, remaining) {
    document.getElementById('payDebtForm').action = '<?= url('/debts/payment/') ?>/' + debtId;
    document.getElementById('debtRemaining').textContent = formatMoney(remaining);
    document.getElementById('debtPayAmount').value = remaining.toFixed(2);
    document.getElementById('debtPayAmount').max = remaining;
    openModal('payDebtModal');
}
</script>
