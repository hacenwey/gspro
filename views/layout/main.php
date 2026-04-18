<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>" dir="<?= Lang::dir() ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'GestionPro') ?> - GestionPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php if (Lang::isRtl()): ?>
    <link rel="stylesheet" href="<?= asset('css/rtl.css') ?>">
    <?php endif; ?>
    <?php if (isset($extraCss)): ?>
    <style><?= $extraCss ?></style>
    <?php endif; ?>
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo">GP</div>
            <div>
                <h1>GestionPro</h1>
                <?php if (class_exists('Tenant') && Tenant::current()): ?>
                <div style="font-size:11px;color:var(--text-muted);font-weight:500;margin-top:2px;"><?= e(Tenant::current()['company_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title"><?= __('nav.main') ?></div>
                <a href="<?= url('/dashboard') ?>" class="nav-item <?= isActive('/dashboard') ?>">
                    <span class="icon"><i class="fas fa-chart-line"></i></span>
                    <?= __('nav.dashboard') ?>
                </a>
                <a href="<?= url('/caisse') ?>" class="nav-item <?= isActive('/caisse') ?>">
                    <span class="icon"><i class="fas fa-cash-register"></i></span>
                    <?= __('nav.pos') ?>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title"><?= __('nav.management') ?></div>
                <a href="<?= url('/products') ?>" class="nav-item <?= isActive('/products') ?>">
                    <span class="icon"><i class="fas fa-boxes-stacked"></i></span>
                    <?= __('nav.products') ?>
                </a>
                <a href="<?= url('/categories') ?>" class="nav-item <?= isActive('/categories') ?>">
                    <span class="icon"><i class="fas fa-tags"></i></span>
                    <?= __('nav.categories') ?>
                </a>
                <a href="<?= url('/clients') ?>" class="nav-item <?= isActive('/clients') ?>">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    <?= __('nav.clients') ?>
                </a>
                <a href="<?= url('/suppliers') ?>" class="nav-item <?= isActive('/suppliers') ?>">
                    <span class="icon"><i class="fas fa-truck"></i></span>
                    <?= __('nav.suppliers') ?>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title"><?= __('nav.finances') ?></div>
                <a href="<?= url('/invoices') ?>" class="nav-item <?= isActive('/invoices') ?>">
                    <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <?= __('nav.invoices') ?>
                </a>
                <a href="<?= url('/debts') ?>" class="nav-item <?= isActive('/debts') ?>">
                    <span class="icon"><i class="fas fa-hand-holding-dollar"></i></span>
                    <?= __('nav.debts') ?>
                </a>
                <a href="<?= url('/payments') ?>" class="nav-item <?= isActive('/payments') ?>">
                    <span class="icon"><i class="fas fa-money-bill-wave"></i></span>
                    <?= __('nav.payments') ?>
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title"><?= __('nav.support') ?: 'Support' ?></div>
                <a href="<?= url('/tickets') ?>" class="nav-item <?= isActive('/tickets') ?>">
                    <span class="icon"><i class="fas fa-headset"></i></span>
                    <?= __('nav.tickets') ?: 'Support' ?>
                    <?php
                    // Show open ticket count badge
                    try {
                        if (class_exists('Tenant') && Tenant::current()) {
                            $__tCount = Tenant::getMasterDB()->prepare("SELECT COUNT(*) FROM tickets WHERE tenant_id = ? AND status IN ('open','in_progress','waiting')");
                            $__tCount->execute([Tenant::current()['id']]);
                            $__openTickets = (int)$__tCount->fetchColumn();
                            if ($__openTickets > 0): ?>
                                <span class="badge badge-danger" style="margin-left:auto;font-size:10px;padding:2px 7px;border-radius:100px;"><?= $__openTickets ?></span>
                            <?php endif;
                        }
                    } catch (Exception $e) {}
                    ?>
                </a>
            </div>
            <?php if (hasRole('admin', 'manager')): ?>
            <div class="nav-section">
                <div class="nav-section-title"><?= __('nav.admin') ?></div>
                <a href="<?= url('/settings') ?>" class="nav-item <?= isActive('/settings') ?>">
                    <span class="icon"><i class="fas fa-cog"></i></span>
                    <?= __('nav.settings') ?>
                </a>
            </div>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <?php $user = currentUser(); if ($user): ?>
            <div class="sidebar-user">
                <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 2)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= e($user['full_name']) ?></div>
                    <div class="user-role"><?= e($user['role']) ?></div>
                </div>
                <a href="<?= url('/logout') ?>" title="<?= __('auth.logout') ?>" style="color: var(--text-muted); font-size: 16px;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="header">
            <div class="header-left">
                <button class="btn-menu-toggle mobile-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?= e($pageTitle ?? __('dash.title')) ?></h2>
            </div>
            <div class="header-right">
                <div class="lang-toggle" style="display:inline-flex;gap:4px;align-items:center;background:var(--surface,#fff);border:1px solid var(--border,#E2E8F0);border-radius:8px;padding:2px;">
                    <?php
                    $__currentLang = Lang::locale();
                    $__langs = [
                        'en' => 'EN',
                        'fr' => 'FR',
                        'ar' => 'AR',
                    ];
                    foreach ($__langs as $__code => $__label):
                        $__active = $__code === $__currentLang;
                    ?>
                        <a href="?lang=<?= $__code ?>"
                           style="padding:5px 10px;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;<?= $__active ? 'background:var(--primary,#4F46E5);color:#fff;' : 'color:var(--text-muted,#64748B);' ?>"
                           title="<?= $__code ?>"><?= $__label ?></a>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-secondary theme-toggle" onclick="toggleTheme()" title="Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="page-content">
            <?php
            // Trial warning banner (shown in last 3 days of trial)
            $__trial = class_exists('Tenant') && Tenant::current() ? Tenant::trialState() : ['status' => 'ok'];
            if ($__trial['status'] === 'warning'):
                $daysLeft = $__trial['days_left'];
            ?>
            <div style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:1px solid #F59E0B;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:14px;">
                <i class="fas fa-triangle-exclamation" style="font-size:22px;color:#D97706;"></i>
                <div style="flex:1;">
                    <div style="font-weight:700;color:#92400E;font-size:14px;">
                        <?= str_replace('{n}', (string)$daysLeft, __('trial.banner.days_left')) ?>
                    </div>
                    <div style="font-size:12px;color:#78350F;margin-top:2px;">
                        <?= __('trial.banner.subtitle') ?>
                    </div>
                </div>
                <a href="<?= url('/trial-expired') ?>" style="background:#D97706;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;">
                    <i class="fas fa-credit-card"></i> <?= __('trial.banner.activate') ?>
                </a>
            </div>
            <?php endif; ?>

            <?php $flash = getFlash(); if ($flash): ?>
            <div class="toast-container">
                <div class="toast toast-<?= $flash['type'] === 'error' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?>">
                    <i class="fas fa-<?= $flash['type'] === 'error' ? 'circle-exclamation' : ($flash['type'] === 'warning' ? 'triangle-exclamation' : 'circle-check') ?>"></i>
                    <?= e($flash['message']) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php require $content; ?>
        </div>
    </main>
</div>

<script>
    const APP_CURRENCY = '<?= CURRENCY ?>';
    const APP_CURRENCY_SYMBOL = '<?= addslashes(CURRENCY_SYMBOL) ?>';
    const APP_LANG = '<?= Lang::locale() ?>';
    const APP_RTL = <?= Lang::isRtl() ? 'true' : 'false' ?>;
</script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
