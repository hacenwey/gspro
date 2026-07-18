<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>" dir="<?= Lang::dir() ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('auth.login') ?> - GestionPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php if (Lang::isRtl()): ?>
    <link rel="stylesheet" href="<?= asset('css/rtl.css') ?>">
    <?php endif; ?>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #12283F 0%, #1B3A5C 50%, #2C5A8A 100%);
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(99,102,241,0.3) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(27,58,92,0.2) 0%, transparent 50%);
            pointer-events: none;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        .login-card {
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.05);
            padding: 44px 40px;
            backdrop-filter: blur(10px);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 36px;
        }
        .login-brand .logo {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #1B3A5C, #2C5A8A);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 26px;
            color: #fff;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(27,58,92,0.35);
        }
        .login-brand h1 { font-size: 26px; font-weight: 800; color: var(--text); letter-spacing: -0.5px; }
        .login-brand p { color: var(--text-muted); font-size: 14px; margin-top: 6px; }
        .login-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            color: var(--danger);
            padding: 10px 14px;
            border-radius: var(--radius);
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .login-card .form-control { padding: 12px 16px; font-size: 15px; }
        .btn-login { width: 100%; padding: 14px; font-size: 15px; justify-content: center; font-weight: 600; letter-spacing: 0.3px; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-brand">
            <?= brandMark() ?>
            <h1>GestionPro</h1>
            <?php if (class_exists('Tenant') && Tenant::current()): ?>
            <p style="font-weight:600;color:var(--text);font-size:15px;margin-bottom:4px;"><?= e(Tenant::current()['company_name']) ?></p>
            <?php endif; ?>
            <p><?= __('auth.tagline') ?></p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="login-error">
            <i class="fas fa-circle-exclamation"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login') ?>">
            <div class="form-group">
                <label class="form-label"><?= __('auth.username') ?></label>
                <input type="text" name="username" class="form-control" placeholder="admin" value="<?= e($username ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('auth.password') ?></label>
                <input type="password" name="password" class="form-control" placeholder="<?= __('auth.password') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <?= __('auth.submit') ?>
            </button>
            <div style="text-align:center;margin-top:16px;">
                <?php $otherLang = Lang::locale() === 'fr' ? 'ar' : 'fr'; ?>
                <a href="?lang=<?= $otherLang ?>" style="color:var(--text-muted);font-size:13px;">
                    <i class="fas fa-language"></i> <?= $otherLang === 'ar' ? 'العربية' : 'Francais' ?>
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
