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
                <!-- Language Toggle -->
                <div class="lang-toggle">
                    <?php $otherLang = Lang::locale() === 'fr' ? 'ar' : 'fr'; ?>
                    <a href="?lang=<?= $otherLang ?>" class="btn btn-sm btn-secondary" title="<?= $otherLang === 'ar' ? 'العربية' : 'Francais' ?>">
                        <i class="fas fa-language"></i>
                        <?= $otherLang === 'ar' ? 'عربي' : 'FR' ?>
                    </a>
                </div>
                <button class="btn btn-sm btn-secondary theme-toggle" onclick="toggleTheme()" title="Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <div class="page-content">
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
    const APP_LANG = '<?= Lang::locale() ?>';
    const APP_RTL = <?= Lang::isRtl() ? 'true' : 'false' ?>;
</script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
