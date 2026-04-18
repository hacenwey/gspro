<!DOCTYPE html>
<html lang="fr" dir="ltr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - GestionPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 240px; background: var(--surface); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; padding: 24px 0;
        }
        .admin-sidebar .brand {
            padding: 0 20px 24px; border-bottom: 1px solid var(--border); margin-bottom: 16px;
            display: flex; align-items: center; gap: 12px;
        }
        .admin-sidebar .brand .logo {
            width: 40px; height: 40px; background: linear-gradient(135deg, #DC2626, #EF4444);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 14px; color: #fff;
        }
        .admin-sidebar .brand h2 { font-size: 16px; font-weight: 700; }
        .admin-sidebar .brand small { font-size: 11px; color: var(--text-muted); display: block; }
        .admin-nav { flex: 1; padding: 0 12px; }
        .admin-nav a {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius);
            color: var(--text-secondary); font-size: 14px; font-weight: 500; transition: var(--transition);
            text-decoration: none; margin-bottom: 2px;
        }
        .admin-nav a:hover { background: var(--bg-subtle); color: var(--text); }
        .admin-nav a.active { background: rgba(220,38,38,0.08); color: #DC2626; font-weight: 600; }
        .admin-nav a i { width: 20px; text-align: center; }
        .admin-main { flex: 1; padding: 32px; background: var(--bg); overflow-y: auto; }
        .admin-header { margin-bottom: 28px; }
        .admin-header h1 { font-size: 24px; font-weight: 800; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg);
            padding: 20px; display: flex; align-items: center; gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center;
            justify-content: center; font-size: 20px;
        }
        .stat-icon.red { background: rgba(220,38,38,0.1); color: #DC2626; }
        .stat-icon.green { background: rgba(16,185,129,0.1); color: #10B981; }
        .stat-icon.blue { background: rgba(14,165,233,0.1); color: #0EA5E9; }
        .stat-value { font-size: 28px; font-weight: 800; font-family: var(--font-mono); }
        .stat-label { font-size: 13px; color: var(--text-muted); }
        .admin-sidebar-footer { padding: 16px 20px; border-top: 1px solid var(--border); font-size: 13px; color: var(--text-muted); }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="brand">
            <div class="logo">SA</div>
            <div>
                <h2>GestionPro</h2>
                <small>Super Admin</small>
            </div>
        </div>
        <nav class="admin-nav">
            <a href="<?= adminUrl('/') ?>" class="<?= Tenant::extractAdminPath() === '/' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Tableau de bord
            </a>
            <a href="<?= adminUrl('/tenants') ?>" class="<?= str_starts_with(Tenant::extractAdminPath(), '/tenants') ? 'active' : '' ?>">
                <i class="fas fa-building"></i> Clients
            </a>
            <a href="<?= adminUrl('/connections') ?>" class="<?= str_starts_with(Tenant::extractAdminPath(), '/connections') ? 'active' : '' ?>">
                <i class="fas fa-right-to-bracket"></i> Connexions
            </a>
            <a href="<?= adminUrl('/tickets') ?>" class="<?= str_starts_with(Tenant::extractAdminPath(), '/tickets') ? 'active' : '' ?>" style="position:relative;">
                <i class="fas fa-headset"></i> Tickets
                <?php
                try {
                    $__openCount = Tenant::getMasterDB()->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')")->fetchColumn();
                    if ($__openCount > 0): ?>
                    <span style="position:absolute;right:12px;background:#DC2626;color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px;"><?= $__openCount ?></span>
                    <?php endif;
                } catch (Exception $e) {} ?>
            </a>
        </nav>
        <div class="admin-sidebar-footer">
            <div style="margin-bottom: 8px;">
                <i class="fas fa-user-shield"></i> <?= e($_SESSION['super_admin_name'] ?? 'Admin') ?>
            </div>
            <a href="<?= adminUrl('/logout') ?>" style="color: var(--danger); font-size: 12px;">
                <i class="fas fa-sign-out-alt"></i> Deconnexion
            </a>
        </div>
    </aside>
    <main class="admin-main">
        <?php if (!empty($flash)): ?>
        <div class="toast-container" style="position:relative;top:0;right:0;margin-bottom:16px;">
            <div class="toast toast-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>" style="position:relative;animation:none;">
                <i class="fas fa-<?= $flash['type'] === 'error' ? 'circle-exclamation' : 'circle-check' ?>"></i>
                <?= e($flash['message']) ?>
            </div>
        </div>
        <?php endif; ?>
        <?php require $content; ?>
    </main>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
