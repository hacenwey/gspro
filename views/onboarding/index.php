<style>
.onboarding-container {
    max-width: 700px;
    margin: 30px auto;
}
.onboarding-card {
    background: var(--surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}
.onboarding-header {
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
    padding: 40px 32px;
    text-align: center;
    color: #fff;
}
.onboarding-header h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}
.onboarding-header p {
    font-size: 15px;
    opacity: 0.85;
}
.onboarding-steps {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
}
.onboarding-step-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transition: var(--transition);
}
.onboarding-step-dot.active { background: var(--accent); transform: scale(1.3); }
.onboarding-step-dot.done { background: #fff; }

.step-panel {
    display: none;
    padding: 32px;
}
.step-panel.active { display: block; }

.step-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--text);
}
.step-desc {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 24px;
}
.step-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 20px;
}
.step-icon.blue { background: rgba(46,134,193,0.1); color: var(--secondary); }
.step-icon.green { background: rgba(39,174,96,0.1); color: var(--success); }
.step-icon.orange { background: rgba(243,156,18,0.1); color: var(--accent); }
.step-icon.purple { background: rgba(142,68,173,0.1); color: #8E44AD; }

.onboarding-footer {
    padding: 20px 32px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 16px;
}
.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border-radius: var(--radius);
    background: var(--bg);
}
.feature-item i {
    color: var(--success);
    margin-top: 2px;
    font-size: 16px;
}
.feature-item .info h4 { font-size: 14px; font-weight: 600; }
.feature-item .info p { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

.final-check {
    text-align: center;
    padding: 20px 0;
}
.final-check .big-icon {
    font-size: 64px;
    color: var(--success);
    margin-bottom: 16px;
}
</style>

<div class="onboarding-container">
    <div class="onboarding-card">
        <div class="onboarding-header">
            <h1><?= __('onboarding.welcome') ?></h1>
            <p><?= __('onboarding.welcome_desc') ?></p>
            <div class="onboarding-steps" id="stepDots">
                <div class="onboarding-step-dot active" data-step="1"></div>
                <div class="onboarding-step-dot" data-step="2"></div>
                <div class="onboarding-step-dot" data-step="3"></div>
                <div class="onboarding-step-dot" data-step="4"></div>
                <div class="onboarding-step-dot" data-step="5"></div>
            </div>
        </div>

        <!-- Step 1: Company Info -->
        <div class="step-panel active" data-step="1">
            <div class="step-icon blue"><i class="fas fa-building"></i></div>
            <div class="step-title" style="text-align:center;"><?= __('onboarding.step1_title') ?></div>
            <div class="step-desc" style="text-align:center;"><?= __('onboarding.step1_desc') ?></div>
            <form id="companyForm">
                <div class="form-group">
                    <label class="form-label"><?= __('onboarding.company_name') ?></label>
                    <input type="text" name="company_name" class="form-control" value="<?= e($settings['company_name'] ?? '') ?>" placeholder="Ex: Boutique Mohamed">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= __('onboarding.company_phone') ?></label>
                        <input type="text" name="company_phone" class="form-control" value="<?= e($settings['company_phone'] ?? '') ?>" placeholder="+222 XX XX XX XX">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('onboarding.company_tax_id') ?></label>
                        <input type="text" name="company_tax_id" class="form-control" value="<?= e($settings['company_tax_id'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('onboarding.company_address') ?></label>
                    <input type="text" name="company_address" class="form-control" value="<?= e($settings['company_address'] ?? '') ?>" placeholder="Nouakchott, Mauritanie">
                </div>
            </form>
        </div>

        <!-- Step 2: Products Intro -->
        <div class="step-panel" data-step="2">
            <div class="step-icon orange"><i class="fas fa-boxes-stacked"></i></div>
            <div class="step-title" style="text-align:center;"><?= __('onboarding.step2_title') ?></div>
            <div class="step-desc" style="text-align:center;"><?= __('onboarding.step2_desc') ?></div>
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'إدارة المخزون' : 'Gestion de stock' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'تتبع المدخلات والمخرجات تلقائياً' : 'Entrees/sorties automatiques' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'رمز البار' : 'Code-barres' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'مسح سريع للمنتجات' : 'Scan rapide des produits' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'تنبيهات المخزون' : 'Alertes stock' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'إشعار عند انخفاض المخزون' : 'Notification stock bas' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'الأصناف' : 'Categories' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'تنظيم المنتجات حسب الصنف' : 'Organisez vos produits' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Clients Intro -->
        <div class="step-panel" data-step="3">
            <div class="step-icon green"><i class="fas fa-users"></i></div>
            <div class="step-title" style="text-align:center;"><?= __('onboarding.step3_title') ?></div>
            <div class="step-desc" style="text-align:center;"><?= __('onboarding.step3_desc') ?></div>
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'إدارة الديون' : 'Suivi des credits' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'اعرف من يدين لك بالضبط' : 'Sachez qui vous doit combien' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'فئات الزبائن' : 'Categories VIP' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'عادي، مميز، عابر' : 'Regular, VIP, Occasionnel' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'سجل المشتريات' : 'Historique achats' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'كل عمليات الزبون' : 'Toutes les transactions' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'دفع موبايل' : 'Mobile Money' ?></h4>
                        <p>Bankily, Masrivi, Sedad</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: POS Intro -->
        <div class="step-panel" data-step="4">
            <div class="step-icon purple"><i class="fas fa-cash-register"></i></div>
            <div class="step-title" style="text-align:center;"><?= __('onboarding.step4_title') ?></div>
            <div class="step-desc" style="text-align:center;"><?= __('onboarding.step4_desc') ?></div>
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'بيع سريع' : 'Vente rapide' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'واجهة بسيطة وسريعة' : 'Interface simple et rapide' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'بيع بالدين' : 'Vente a credit' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'تتبع تلقائي للديون' : 'Suivi automatique des dettes' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'فواتير فورية' : 'Factures instantanees' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'فاتورة تلقائية عند كل بيع' : 'Facture auto a chaque vente' ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="info">
                        <h4><?= Lang::locale() === 'ar' ? 'تقرير الصندوق' : 'Rapport de caisse' ?></h4>
                        <p><?= Lang::locale() === 'ar' ? 'ملخص نهاية اليوم' : 'Bilan fin de journee' ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Ready -->
        <div class="step-panel" data-step="5">
            <div class="final-check">
                <div class="big-icon"><i class="fas fa-circle-check"></i></div>
                <div class="step-title"><?= __('onboarding.step5_title') ?></div>
                <div class="step-desc"><?= __('onboarding.step5_desc') ?></div>
            </div>
        </div>

        <div class="onboarding-footer">
            <button class="btn btn-secondary" id="btnPrev" style="visibility:hidden;" onclick="onboardingNav(-1)">
                <i class="fas fa-arrow-<?= Lang::isRtl() ? 'right' : 'left' ?>"></i> <?= __('onboarding.prev') ?>
            </button>
            <a href="<?= url('/onboarding/skip') ?>" class="btn btn-sm" style="color:var(--text-muted);" id="btnSkip"><?= __('onboarding.skip') ?></a>
            <button class="btn btn-primary" id="btnNext" onclick="onboardingNav(1)">
                <?= __('onboarding.next') ?> <i class="fas fa-arrow-<?= Lang::isRtl() ? 'left' : 'right' ?>"></i>
            </button>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 5;

function onboardingNav(dir) {
    const next = currentStep + dir;
    if (next < 1 || next > totalSteps) return;

    // On final step, submit
    if (currentStep === 5 && dir === 1) {
        submitOnboarding();
        return;
    }

    currentStep = next;
    updateStepUI();
}

function updateStepUI() {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.querySelector(`.step-panel[data-step="${currentStep}"]`).classList.add('active');

    document.querySelectorAll('.onboarding-step-dot').forEach(d => {
        const s = parseInt(d.dataset.step);
        d.classList.remove('active', 'done');
        if (s === currentStep) d.classList.add('active');
        else if (s < currentStep) d.classList.add('done');
    });

    document.getElementById('btnPrev').style.visibility = currentStep === 1 ? 'hidden' : 'visible';

    if (currentStep === totalSteps) {
        document.getElementById('btnNext').innerHTML = '<i class="fas fa-rocket"></i> <?= __("onboarding.finish") ?>';
        document.getElementById('btnSkip').style.display = 'none';
    } else {
        document.getElementById('btnNext').innerHTML = '<?= __("onboarding.next") ?> <i class="fas fa-arrow-<?= Lang::isRtl() ? "left" : "right" ?>"></i>';
        document.getElementById('btnSkip').style.display = '';
    }
}

function submitOnboarding() {
    const form = document.getElementById('companyForm');
    const formData = new FormData(form);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url("/onboarding/company") ?>', { method: 'POST', body: formData })
        .then(() => { window.location.href = '<?= url("/dashboard") ?>'; })
        .catch(() => { window.location.href = '<?= url("/dashboard") ?>'; });
}
</script>
