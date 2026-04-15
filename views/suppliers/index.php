<div class="toolbar">
    <div class="toolbar-search">
        <span class="search-icon"><i class="fas fa-search"></i></span>
        <form method="GET" action="<?= url('/suppliers') ?>" style="display:contents;">
            <input type="text" name="search" class="form-control" placeholder="Rechercher fournisseur..." value="<?= e($search) ?>">
        </form>
    </div>
    <a href="<?= url('/suppliers/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau fournisseur</a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="icon"><i class="fas fa-truck"></i></div>
            <h4>Aucun fournisseur</h4>
            <a href="<?= url('/suppliers/create') ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau fournisseur</a>
        </div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Raison sociale</th><th>Contact</th><th>Telephone</th><th>Delai paiement</th><th class="text-right">Solde</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($items as $s): ?>
            <tr>
                <td><a href="<?= url('/suppliers/view/' . $s['id']) ?>" style="font-weight:600;color:var(--text);"><?= e($s['company_name']) ?></a></td>
                <td><?= e($s['contact_name'] ?? '-') ?></td>
                <td><?= e($s['phone'] ?? '-') ?></td>
                <td><?= $s['payment_terms'] ?> jours</td>
                <td class="text-right text-mono fw-bold <?= $s['balance'] < 0 ? 'text-danger' : '' ?>"><?= formatMoney($s['balance']) ?></td>
                <td class="text-right">
                    <div class="btn-group" style="justify-content: flex-end;">
                        <a href="<?= url('/suppliers/view/' . $s['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                        <a href="<?= url('/suppliers/edit/' . $s['id']) ?>" class="btn btn-icon btn-sm btn-secondary"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="<?= url('/suppliers/delete/' . $s['id']) ?>" style="display:inline;"><?= csrf_field() ?>
                            <button type="button" class="btn btn-icon btn-sm btn-danger" onclick="confirmDelete(this.parentNode)"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
