<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GestionPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1B4F72 0%, #2E86C1 50%, #1B4F72 100%);
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand .logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1B4F72, #2E86C1);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            color: #fff;
            margin-bottom: 16px;
        }
        .login-brand h1 { font-size: 24px; font-weight: 800; color: var(--text); }
        .login-brand p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .login-error {
            background: rgba(231,76,60,0.1);
            border: 1px solid rgba(231,76,60,0.2);
            color: var(--danger);
            padding: 10px 14px;
            border-radius: var(--radius);
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-control { padding: 12px 16px; font-size: 15px; }
        .btn-login { width: 100%; padding: 14px; font-size: 15px; justify-content: center; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-brand">
            <div class="logo">GP</div>
            <h1>GestionPro</h1>
            <p>Gestion Commerciale Integree</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="login-error">
            <i class="fas fa-circle-exclamation"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/login') ?>">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur ou email</label>
                <input type="text" name="username" class="form-control" placeholder="admin" value="<?= e($username ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>
    </div>
</div>
</body>
</html>
