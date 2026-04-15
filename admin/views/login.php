<!DOCTYPE html>
<html lang="fr" dir="ltr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - GestionPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #7F1D1D 0%, #DC2626 50%, #EF4444 100%);
        }
        .login-container { width: 100%; max-width: 420px; padding: 20px; }
        .login-card { background: var(--surface); border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); padding: 44px 40px; }
        .login-brand { text-align: center; margin-bottom: 36px; }
        .login-brand .logo {
            width: 68px; height: 68px; background: linear-gradient(135deg, #DC2626, #EF4444);
            border-radius: 18px; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 22px; color: #fff; margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(220,38,38,0.35);
        }
        .login-brand h1 { font-size: 24px; font-weight: 800; }
        .login-brand p { color: var(--text-muted); font-size: 13px; margin-top: 6px; }
        .login-error {
            background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
            color: #DC2626; padding: 10px 14px; border-radius: var(--radius);
            font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
        }
        .login-card .form-control { padding: 12px 16px; font-size: 15px; }
        .btn-login { width: 100%; padding: 14px; font-size: 15px; justify-content: center; font-weight: 600; background: #DC2626; border-color: #DC2626; }
        .btn-login:hover { background: #B91C1C; border-color: #B91C1C; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-brand">
            <div class="logo"><i class="fas fa-shield-halved"></i></div>
            <h1>Super Admin</h1>
            <p>Panel d'administration multi-client</p>
        </div>
        <?php if (!empty($error)): ?>
        <div class="login-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= adminUrl('/login') ?>">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-control" placeholder="superadmin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
        </form>
    </div>
</div>
</body>
</html>
