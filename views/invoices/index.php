<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/invoices') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="Rechercher par numero, client..." value="<?= e($search) ?>">
            <?php if ($typeFilter): ?><input type="hidden" name="type" value="<?= e($typeFilter) ?>"><?php endif; ?>
        </form>
    </div>
    <div class="toolbar-filters">
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/invoices') ?>?type='+this.value+'&search=<?= urlencode($search) ?>'">
            <option value="">Tous types</option>
            <option value="invoice" <?= $typeFilter === 'invoice' ? 'selected' : '' ?>>Factures</option>
            <option value="quote" <?= $typeFilter === 'quote' ? 'selected' : '' ?>>Devis</option>
            <option value="credit_note" <?= $typeFilter === 'credit_note' ? 'selected' : '' ?>>Avoirs</option>
        </select>
        <select class="form-control" style="width:auto;" onchange="window.location.href='<?= url('/invoices') ?>?type=<?= urlencode($typeFilter) ?>&status='+this.value+'&search=<?= urlencode($search) ?>'">
            <option value="">Tous statuts</option>
            <?php foreach (['draft','sent','partial','paid','overdue','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= invoiceStatusLabel($s) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="btn-group">
            <a href="<?= url('/invoices/create?type=quote') ?>" class="btn btn-secondary"><i class="fas fa-plus"></i> Devis</a>
            <a href="<?= url('/invoices/create?type=invoice') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Facture</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
            <h4>Aucun document</h4>
            <div class="btn-group" style="justify-content:center;">
                <a href="<?= url('/invoices/create?type=quote') ?>" class="btn btn-secondary"><i class="fas fa-plus"></i> Devis</a>
                <a href="<?= url('/invoices/create?type=invoice') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Facture</a>
            </div>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>N°</th><th>Type</th><th>Client</th><th>Date</th><th class="text-right">Total</th><th class="text-right">Paye</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $inv): ?>
            <tr>
                <td><a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="text-mono" style="font-size:12px;font-weight:600;"><?= e($inv['number']) ?></a></td>
                <td><span class="badge badge-<?= $inv['type'] === 'invoice' ? 'primary' : ($inv['type'] === 'quote' ? 'info' : 'warning') ?>"><?= $inv['type'] === 'invoice' ? 'Facture' : ($inv['type'] === 'quote' ? 'Devis' : 'Avoir') ?></span></td>
                <td><?= e(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? '')) ?></td>
                <td style="font-size:12px;"><?= formatDate($inv['issue_date']) ?></td>
                <td class="text-right text-mono fw-bold"><?= formatMoney($inv['total']) ?></td>
                <td class="text-right text-mono"><?= formatMoney($inv['amount_paid']) ?></td>
                <td><span class="badge badge-<?= invoiceStatusClass($inv['status']) ?>"><?= invoiceStatusLabel($inv['status']) ?></span></td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content:flex-end;">
                        <a href="<?= url('/invoices/view/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                        <a href="<?= url('/invoices/pdf/' . $inv['id']) ?>" class="btn btn-icon btn-sm btn-secondary" target="_blank"><i class="fas fa-file-pdf"></i></a>
                        <?php if ($inv['type'] === 'quote' && $inv['status'] !== 'accepted'): ?>
                        <form method="POST" action="<?= url('/invoices/convert/' . $inv['id']) ?>" style="display:inline;"><?= csrf_field() ?>
                            <button type="submit" class="btn btn-icon btn-sm btn-success" title="Convertir en facture"><i class="fas fa-exchange-alt"></i></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="card-footer"><div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= url('/invoices') ?>?page=<?= $i ?>&type=<?= urlencode($typeFilter) ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
