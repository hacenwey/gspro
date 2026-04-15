<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'GestionPro') ?> - GestionPro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
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
            <h1>GestionPro</h1>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="<?= url('/dashboard') ?>" class="nav-item <?= isActive('/dashboard') ?>">
                    <span class="icon"><i class="fas fa-chart-line"></i></span>
                    Tableau de bord
                </a>
                <a href="<?= url('/caisse') ?>" class="nav-item <?= isActive('/caisse') ?>">
                    <span class="icon"><i class="fas fa-cash-register"></i></span>
                    Caisse (POS)
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Gestion</div>
                <a href="<?= url('/products') ?>" class="nav-item <?= isActive('/products') ?>">
                    <span class="icon"><i class="fas fa-boxes-stacked"></i></span>
                    Produits & Stock
                </a>
                <a href="<?= url('/categories') ?>" class="nav-item <?= isActive('/categories') ?>">
                    <span class="icon"><i class="fas fa-tags"></i></span>
                    Categories
                </a>
                <a href="<?= url('/clients') ?>" class="nav-item <?= isActive('/clients') ?>">
                    <span class="icon"><i class="fas fa-users"></i></span>
                    Clients
                </a>
                <a href="<?= url('/suppliers') ?>" class="nav-item <?= isActive('/suppliers') ?>">
                    <span class="icon"><i class="fas fa-truck"></i></span>
                    Fournisseurs
                </a>
            </div>
            <div class="nav-section">
                <div class="nav-section-title">Finances</div>
                <a href="<?= url('/invoices') ?>" class="nav-item <?= isActive('/invoices') ?>">
                    <span class="icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    Devis & Factures
                </a>
                <a href="<?= url('/debts') ?>" class="nav-item <?= isActive('/debts') ?>">
                    <span class="icon"><i class="fas fa-hand-holding-dollar"></i></span>
                    Dettes & Credits
                </a>
                <a href="<?= url('/payments') ?>" class="nav-item <?= isActive('/payments') ?>">
                    <span class="icon"><i class="fas fa-money-bill-wave"></i></span>
                    Paiements
                </a>
            </div>
            <?php if (hasRole('admin', 'manager')): ?>
            <div class="nav-section">
                <div class="nav-section-title">Administration</div>
                <a href="<?= url('/settings') ?>" class="nav-item <?= isActive('/settings') ?>">
                    <span class="icon"><i class="fas fa-cog"></i></span>
                    Parametres
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
                <a href="<?= url('/logout') ?>" title="Deconnexion" style="color: rgba(255,255,255,0.5); font-size: 16px;">
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
                <button class="btn-menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?= e($pageTitle ?? 'Tableau de bord') ?></h2>
            </div>
            <div class="header-right">
                <button class="btn btn-sm btn-secondary" onclick="toggleTheme()" title="Theme">
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

<script src="<?= asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): ?>
<script><?= $extraJs ?></script>
<?php endif; ?>
</body>
</html>
