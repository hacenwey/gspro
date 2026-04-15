<!DOCTYPE html>
<html lang="fr" dir="ltr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionPro - Gestion Commerciale Multi-Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            background: #F8FAFC;
            color: #0F172A;
        }
        .hero {
            background: linear-gradient(135deg, #312E81 0%, #4F46E5 50%, #6366F1 100%);
            color: #fff;
            padding: 60px 20px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(99,102,241,0.3) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(79,70,229,0.2) 0%, transparent 50%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 700px; margin: 0 auto; }
        .hero .logo {
            width: 72px; height: 72px; background: rgba(255,255,255,0.15);
            border-radius: 18px; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 28px; margin-bottom: 20px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);
        }
        .hero h1 { font-size: 36px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.5px; }
        .hero p { font-size: 16px; opacity: 0.85; max-width: 500px; margin: 0 auto 32px; line-height: 1.6; }
        .hero-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .hero-actions a {
            display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px;
            border-radius: 12px; font-weight: 600; font-size: 15px; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-hero-primary {
            background: #fff; color: #4F46E5;
        }
        .btn-hero-primary:hover { background: #EEF2FF; transform: translateY(-1px); }
        .btn-hero-secondary {
            background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
        }
        .btn-hero-secondary:hover { background: rgba(255,255,255,0.25); }

        .container { max-width: 900px; margin: 0 auto; padding: 0 20px; }

        .features {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
            margin-top: -40px; position: relative; z-index: 2; padding: 0 20px;
            max-width: 900px; margin-left: auto; margin-right: auto;
        }
        .feature-card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
            padding: 24px; text-align: center; transition: all 0.2s;
        }
        .feature-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .feature-icon {
            width: 48px; height: 48px; border-radius: 12px; display: inline-flex;
            align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px;
        }
        .feature-icon.indigo { background: rgba(79,70,229,0.1); color: #4F46E5; }
        .feature-icon.green { background: rgba(16,185,129,0.1); color: #10B981; }
        .feature-icon.orange { background: rgba(245,158,11,0.1); color: #F59E0B; }
        .feature-card h3 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .feature-card p { font-size: 13px; color: #64748B; line-height: 1.5; }

        .tenants-section { padding: 48px 20px; max-width: 900px; margin: 0 auto; }
        .tenants-section h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; text-align: center; }
        .tenant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; }
        .tenant-card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px;
            display: flex; align-items: center; gap: 14px; text-decoration: none; color: inherit;
            transition: all 0.2s;
        }
        .tenant-card:hover { border-color: #4F46E5; box-shadow: 0 4px 16px rgba(79,70,229,0.1); transform: translateY(-1px); }
        .tenant-avatar {
            width: 44px; height: 44px; background: linear-gradient(135deg, #4F46E5, #6366F1);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 16px; color: #fff; flex-shrink: 0;
        }
        .tenant-info h4 { font-size: 14px; font-weight: 700; }
        .tenant-info p { font-size: 12px; color: #94A3B8; }

        .footer { text-align: center; padding: 32px 20px; color: #94A3B8; font-size: 13px; }

        @media (max-width: 640px) {
            .features { grid-template-columns: 1fr; margin-top: -30px; }
            .hero h1 { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <div class="logo">GP</div>
            <h1>GestionPro</h1>
            <p>Solution de gestion commerciale multi-client. Chaque entreprise dispose de son propre espace securise avec base de donnees independante.</p>
            <div class="hero-actions">
                <a href="<?= APP_BASE ?>/admin/login" class="btn-hero-secondary">
                    <i class="fas fa-shield-halved"></i> Panel Admin
                </a>
            </div>
        </div>
    </div>

    <div class="features">
        <div class="feature-card">
            <div class="feature-icon indigo"><i class="fas fa-database"></i></div>
            <h3>Isolation totale</h3>
            <p>Chaque client a sa propre base de donnees. Aucun risque de melange de donnees.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon green"><i class="fas fa-rocket"></i></div>
            <h3>Installation rapide</h3>
            <p>Creez un nouveau client en 30 secondes depuis le panel admin.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon orange"><i class="fas fa-sliders"></i></div>
            <h3>Gestion centralisee</h3>
            <p>Activez, desactivez ou supprimez des clients depuis un seul tableau de bord.</p>
        </div>
    </div>

    <?php
    // Show active tenants
    try {
        $masterDb = Tenant::getMasterDB();
        $tenants = $masterDb->query("SELECT slug, company_name FROM tenants WHERE is_active = 1 ORDER BY company_name")->fetchAll();
    } catch (Exception $e) {
        $tenants = [];
    }
    ?>

    <?php if (!empty($tenants)): ?>
    <div class="tenants-section">
        <h2><i class="fas fa-building" style="color:#4F46E5;margin-right:8px;"></i> Espaces clients</h2>
        <div class="tenant-grid">
            <?php foreach ($tenants as $t): ?>
            <a href="<?= APP_BASE . '/' . $t['slug'] . '/login' ?>" class="tenant-card">
                <div class="tenant-avatar"><?= strtoupper(substr($t['slug'], 0, 2)) ?></div>
                <div class="tenant-info">
                    <h4><?= e($t['company_name']) ?></h4>
                    <p><?= e($t['slug']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>&copy; <?= date('Y') ?> GestionPro - Gestion commerciale multi-client</p>
    </div>
</body>
</html>
