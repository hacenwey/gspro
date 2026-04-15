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
                            ticks: { callback: v => v.toLocaleString('fr-FR') + ' ' + APP_CURRENCY }
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

<?php if (!empty($showOnboarding)): ?>
<!-- Onboarding Popup Modal -->
<div class="modal-overlay active" id="onboardingModal" style="z-index:9999;">
    <div class="modal" style="max-width:720px;overflow:visible;">
        <style>
        .ob-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
            padding: 32px 32px 28px;
            text-align: center;
            color: #fff;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        .ob-header h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        .ob-header p { font-size: 14px; opacity: 0.85; }
        .ob-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .ob-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .ob-dot.active { background: var(--accent); transform: scale(1.4); }
        .ob-dot.done { background: #fff; }
        .ob-body { padding: 28px 32px; max-height: 420px; overflow-y: auto; }
        .ob-panel { display: none; }
        .ob-panel.active { display: block; animation: fadeIn 0.3s ease; }
        .ob-icon {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; margin: 0 auto 16px;
        }
        .ob-icon.blue { background: rgba(46,134,193,0.1); color: var(--secondary); }
        .ob-icon.green { background: rgba(39,174,96,0.1); color: var(--success); }
        .ob-icon.orange { background: rgba(243,156,18,0.1); color: var(--accent); }
        .ob-icon.purple { background: rgba(142,68,173,0.1); color: #8E44AD; }
        .ob-title { font-size: 18px; font-weight: 700; text-align: center; margin-bottom: 4px; }
        .ob-desc { font-size: 13px; color: var(--text-secondary); text-align: center; margin-bottom: 20px; }
        .ob-features {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;
        }
        .ob-feat {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px; border-radius: var(--radius); background: var(--bg);
        }
        .ob-feat i { color: var(--success); margin-top: 2px; }
        .ob-feat h4 { font-size: 13px; font-weight: 600; }
        .ob-feat p { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }
        .ob-footer {
            padding: 16px 32px;
            border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .ob-final { text-align: center; padding: 10px 0; }
        .ob-final .big-icon { font-size: 56px; color: var(--success); margin-bottom: 12px; }
        @media (max-width: 600px) {
            .ob-features { grid-template-columns: 1fr; }
            .modal { max-width: 95vw !important; }
        }
        </style>

        <div class="ob-header">
            <h1><?= __('onboarding.welcome') ?></h1>
            <p><?= __('onboarding.welcome_desc') ?></p>
            <div class="ob-dots" id="obDots">
                <div class="ob-dot active" data-s="1"></div>
                <div class="ob-dot" data-s="2"></div>
                <div class="ob-dot" data-s="3"></div>
                <div class="ob-dot" data-s="4"></div>
                <div class="ob-dot" data-s="5"></div>
            </div>
        </div>

        <div class="ob-body">
            <!-- Step 1 -->
            <div class="ob-panel active" data-s="1">
                <div class="ob-icon blue"><i class="fas fa-building"></i></div>
                <div class="ob-title"><?= __('onboarding.step1_title') ?></div>
                <div class="ob-desc"><?= __('onboarding.step1_desc') ?></div>
                <form id="obForm">
                    <div class="form-group">
                        <label class="form-label"><?= __('onboarding.company_name') ?></label>
                        <input type="text" name="company_name" class="form-control" value="<?= e($companySettings['company_name'] ?? '') ?>" placeholder="Ex: Boutique Mohamed">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= __('onboarding.company_phone') ?></label>
                            <input type="text" name="company_phone" class="form-control" value="<?= e($companySettings['company_phone'] ?? '') ?>" placeholder="+222 XX XX XX XX">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= __('onboarding.company_tax_id') ?></label>
                            <input type="text" name="company_tax_id" class="form-control" value="<?= e($companySettings['company_tax_id'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('onboarding.company_address') ?></label>
                        <input type="text" name="company_address" class="form-control" value="<?= e($companySettings['company_address'] ?? '') ?>" placeholder="Nouakchott, Mauritanie">
                    </div>
                </form>
            </div>

            <!-- Step 2 -->
            <div class="ob-panel" data-s="2">
                <div class="ob-icon orange"><i class="fas fa-boxes-stacked"></i></div>
                <div class="ob-title"><?= __('onboarding.step2_title') ?></div>
                <div class="ob-desc"><?= __('onboarding.step2_desc') ?></div>
                <div class="ob-features">
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'إدارة المخزون' : 'Gestion de stock' ?></h4><p><?= Lang::locale()==='ar' ? 'تتبع المدخلات والمخرجات تلقائياً' : 'Entrees/sorties automatiques' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'رمز البار' : 'Code-barres' ?></h4><p><?= Lang::locale()==='ar' ? 'مسح سريع للمنتجات' : 'Scan rapide des produits' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'تنبيهات المخزون' : 'Alertes stock' ?></h4><p><?= Lang::locale()==='ar' ? 'إشعار عند انخفاض المخزون' : 'Notification stock bas' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'الأصناف' : 'Categories' ?></h4><p><?= Lang::locale()==='ar' ? 'تنظيم المنتجات حسب الصنف' : 'Organisez vos produits' ?></p></div></div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="ob-panel" data-s="3">
                <div class="ob-icon green"><i class="fas fa-users"></i></div>
                <div class="ob-title"><?= __('onboarding.step3_title') ?></div>
                <div class="ob-desc"><?= __('onboarding.step3_desc') ?></div>
                <div class="ob-features">
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'إدارة الديون' : 'Suivi des credits' ?></h4><p><?= Lang::locale()==='ar' ? 'اعرف من يدين لك بالضبط' : 'Sachez qui vous doit combien' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'فئات الزبائن' : 'Categories VIP' ?></h4><p><?= Lang::locale()==='ar' ? 'عادي، مميز، عابر' : 'Regular, VIP, Occasionnel' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'سجل المشتريات' : 'Historique achats' ?></h4><p><?= Lang::locale()==='ar' ? 'كل عمليات الزبون' : 'Toutes les transactions' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4>Mobile Money</h4><p>Bankily, Masrivi, Sedad</p></div></div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="ob-panel" data-s="4">
                <div class="ob-icon purple"><i class="fas fa-cash-register"></i></div>
                <div class="ob-title"><?= __('onboarding.step4_title') ?></div>
                <div class="ob-desc"><?= __('onboarding.step4_desc') ?></div>
                <div class="ob-features">
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'بيع سريع' : 'Vente rapide' ?></h4><p><?= Lang::locale()==='ar' ? 'واجهة بسيطة وسريعة' : 'Interface simple et rapide' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'بيع بالدين' : 'Vente a credit' ?></h4><p><?= Lang::locale()==='ar' ? 'تتبع تلقائي للديون' : 'Suivi automatique des dettes' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'فواتير فورية' : 'Factures instantanees' ?></h4><p><?= Lang::locale()==='ar' ? 'فاتورة تلقائية عند كل بيع' : 'Facture auto a chaque vente' ?></p></div></div>
                    <div class="ob-feat"><i class="fas fa-check-circle"></i><div><h4><?= Lang::locale()==='ar' ? 'تقرير الصندوق' : 'Rapport de caisse' ?></h4><p><?= Lang::locale()==='ar' ? 'ملخص نهاية اليوم' : 'Bilan fin de journee' ?></p></div></div>
                </div>
            </div>

            <!-- Step 5 -->
            <div class="ob-panel" data-s="5">
                <div class="ob-final">
                    <div class="big-icon"><i class="fas fa-circle-check"></i></div>
                    <div class="ob-title"><?= __('onboarding.step5_title') ?></div>
                    <div class="ob-desc"><?= __('onboarding.step5_desc') ?></div>
                </div>
            </div>
        </div>

        <div class="ob-footer">
            <button class="btn btn-secondary btn-sm" id="obPrev" style="visibility:hidden;" onclick="obNav(-1)">
                <i class="fas fa-arrow-<?= Lang::isRtl() ? 'right' : 'left' ?>"></i> <?= __('onboarding.prev') ?>
            </button>
            <button class="btn btn-sm" style="color:var(--text-muted);" id="obSkip" onclick="obSkip()">
                <?= __('onboarding.skip') ?>
            </button>
            <button class="btn btn-primary btn-sm" id="obNext" onclick="obNav(1)">
                <?= __('onboarding.next') ?> <i class="fas fa-arrow-<?= Lang::isRtl() ? 'left' : 'right' ?>"></i>
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    let step = 1;
    const total = 5;

    window.obNav = function(dir) {
        if (step === total && dir === 1) { obFinish(); return; }
        const next = step + dir;
        if (next < 1 || next > total) return;
        step = next;
        obUpdate();
    };

    function obUpdate() {
        document.querySelectorAll('.ob-panel').forEach(p => p.classList.remove('active'));
        document.querySelector('.ob-panel[data-s="'+step+'"]').classList.add('active');

        document.querySelectorAll('.ob-dot').forEach(d => {
            const s = parseInt(d.dataset.s);
            d.classList.remove('active','done');
            if (s === step) d.classList.add('active');
            else if (s < step) d.classList.add('done');
        });

        document.getElementById('obPrev').style.visibility = step === 1 ? 'hidden' : 'visible';

        if (step === total) {
            document.getElementById('obNext').innerHTML = '<i class="fas fa-rocket"></i> <?= __("onboarding.finish") ?>';
            document.getElementById('obSkip').style.display = 'none';
        } else {
            document.getElementById('obNext').innerHTML = '<?= __("onboarding.next") ?> <i class="fas fa-arrow-<?= Lang::isRtl() ? "left" : "right" ?>"></i>';
            document.getElementById('obSkip').style.display = '';
        }
    }

    function obFinish() {
        const form = document.getElementById('obForm');
        const fd = new FormData(form);
        fd.append('csrf_token', '<?= csrf_token() ?>');

        fetch('<?= url("/onboarding/company") ?>', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(() => {
                document.getElementById('onboardingModal').classList.remove('active');
                document.body.style.overflow = '';
            })
            .catch(() => {
                document.getElementById('onboardingModal').classList.remove('active');
                document.body.style.overflow = '';
            });
    }

    window.obSkip = function() {
        fetch('<?= url("/onboarding/skip") ?>')
            .then(() => {
                document.getElementById('onboardingModal').classList.remove('active');
                document.body.style.overflow = '';
            });
    };

    // Prevent closing by clicking overlay
    document.getElementById('onboardingModal').addEventListener('click', function(e) {
        if (e.target === this) e.stopPropagation();
    });

    // Clickable dots
    document.querySelectorAll('.ob-dot').forEach(d => {
        d.addEventListener('click', function() {
            step = parseInt(this.dataset.s);
            obUpdate();
        });
    });
})();
</script>
<?php endif; ?>
