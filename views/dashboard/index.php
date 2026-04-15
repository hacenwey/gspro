<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-coins"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">CA du jour</div>
            <div class="kpi-value"><?= formatMoney($todaySales) ?></div>
            <div class="kpi-change"><?= $todayCount ?> vente(s)</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-chart-bar"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">CA du mois</div>
            <div class="kpi-value"><?= formatMoney($monthSales) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Creances clients</div>
            <div class="kpi-value"><?= formatMoney($totalReceivables) ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-file-invoice"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Dettes fournisseurs</div>
            <div class="kpi-value"><?= formatMoney($totalPayables) ?></div>
        </div>
    </div>
</div>

<div class="grid-2">
    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-area" style="color: var(--primary); margin-right: 8px;"></i>Evolution du CA</h3>
            <div class="btn-group">
                <button class="btn btn-sm btn-secondary chart-period active" data-period="7d">7j</button>
                <button class="btn btn-sm btn-secondary chart-period" data-period="30d">30j</button>
                <button class="btn btn-sm btn-secondary chart-period" data-period="12m">12m</button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="260"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-trophy" style="color: var(--accent); margin-right: 8px;"></i>Top produits (mois)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($topProducts)): ?>
            <div class="empty-state" style="padding: 40px 20px;">
                <div class="icon"><i class="fas fa-chart-pie"></i></div>
                <p>Aucune vente ce mois-ci</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Produit</th><th class="text-right">Qte</th><th class="text-right">Montant</th></tr></thead>
                <tbody>
                <?php foreach ($topProducts as $i => $p): ?>
                <tr>
                    <td>
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--primary);color:#fff;font-size:10px;font-weight:700;margin-right:8px;"><?= $i + 1 ?></span>
                        <?= e($p['name']) ?>
                    </td>
                    <td class="text-right text-mono"><?= number_format($p['total_qty'], 0) ?></td>
                    <td class="text-right text-mono"><?= formatMoney($p['total_amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid-2 mt-3">
    <!-- Low Stock Alerts -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-triangle-exclamation" style="color: var(--danger); margin-right: 8px;"></i>Alertes stock bas (<?= $lowStockCount ?>)</h3>
            <a href="<?= url('/products') ?>" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($lowStock)): ?>
            <div class="empty-state" style="padding: 40px 20px;">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <p>Tous les stocks sont OK</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>Produit</th><th class="text-center">Stock</th><th class="text-center">Min</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $p): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600;"><?= e($p['name']) ?></div>
                        <div style="font-size:11px; color: var(--text-muted);"><?= e($p['reference']) ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-<?= stockStatusClass($p['current_stock'], $p['min_stock']) ?>">
                            <?= $p['current_stock'] ?>
                        </span>
                    </td>
                    <td class="text-center text-mono"><?= $p['min_stock'] ?></td>
                    <td class="text-right">
                        <a href="<?= url('/products/view/' . $p['id']) ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Invoices & Overdue Debts -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock" style="color: var(--info); margin-right: 8px;"></i>Dernieres factures</h3>
            <a href="<?= url('/invoices') ?>" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentInvoices)): ?>
            <div class="empty-state" style="padding: 40px 20px;">
                <div class="icon"><i class="fas fa-file-invoice"></i></div>
                <p>Aucune facture</p>
            </div>
            <?php else: ?>
            <table class="table">
                <thead><tr><th>N°</th><th>Client</th><th class="text-right">Total</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recentInvoices as $inv): ?>
                <tr>
                    <td class="text-mono" style="font-size:12px;"><?= e($inv['number']) ?></td>
                    <td><?= e(($inv['first_name'] ?? '') . ' ' . $inv['last_name']) ?></td>
                    <td class="text-right text-mono"><?= formatMoney($inv['total']) ?></td>
                    <td><span class="badge badge-<?= invoiceStatusClass($inv['status']) ?>"><?= invoiceStatusLabel($inv['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Stats Bar -->
<div class="kpi-grid mt-3">
    <div class="kpi-card">
        <div class="kpi-icon purple"><i class="fas fa-box"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Produits actifs</div>
            <div class="kpi-value"><?= $totalProducts ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Clients</div>
            <div class="kpi-value"><?= $totalClients ?></div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Stock bas</div>
            <div class="kpi-value"><?= $lowStockCount ?></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let revenueChart;

function loadChart(period) {
    fetch('<?= url('/api/dashboard/chart') ?>?period=' + period)
        .then(r => r.json())
        .then(data => {
            const labels = data.map(d => d.label);
            const values = data.map(d => d.amount);

            if (revenueChart) revenueChart.destroy();

            const ctx = document.getElementById('revenueChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(46,134,193,0.15)');
            gradient.addColorStop(1, 'rgba(46,134,193,0)');

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Chiffre d\'affaires',
                        data: values,
                        borderColor: '#2E86C1',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#2E86C1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: { callback: v => v.toLocaleString('fr-FR') + ' DA' }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        });
}

document.querySelectorAll('.chart-period').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.chart-period').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        this.classList.add('btn-primary');
        document.querySelectorAll('.chart-period:not(.active)').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
        });
        loadChart(this.dataset.period);
    });
});

loadChart('7d');
</script>
