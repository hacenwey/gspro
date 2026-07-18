<?php
$tenant = Tenant::current();
$slug = $tenant['slug'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement reussi - GestionPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,'Segoe UI',Inter,sans-serif; background:#F9FAFB; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; color:#1F2937; }
        .card { background:#fff; max-width:520px; width:100%; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,.08); overflow:hidden; text-align:center; }
        .banner { padding:40px 32px; background:linear-gradient(135deg,#2E7D32,#1B5E20); color:#fff; }
        .banner i { font-size:52px; margin-bottom:12px; display:block; }
        .banner h1 { font-size:24px; font-weight:700; }
        .body { padding:32px; }
        .body h2 { font-size:18px; font-weight:600; margin-bottom:8px; }
        .body p { color:#6B7280; font-size:14px; line-height:1.6; margin-bottom:20px; }
        .btn { display:inline-block; padding:12px 28px; background:#DC2626; color:#fff; text-decoration:none; border-radius:10px; font-weight:600; transition:all .2s; }
        .btn:hover { background:#B91C1C; transform:translateY(-1px); }
        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; margin-bottom:8px; }
        .badge-ok { background:#D1FAE5; color:#1B5E20; }
        .badge-wait { background:#FEF3C7; color:#92400E; }
    </style>
</head>
<body>
<div class="card">
    <div class="banner">
        <i class="fas fa-circle-check"></i>
        <h1>Paiement recu !</h1>
    </div>
    <div class="body">
        <?php if ($isActive): ?>
            <span class="badge badge-ok"><i class="fas fa-check"></i> Abonnement actif</span>
            <h2>Merci, votre abonnement est en place.</h2>
            <p>Vous avez maintenant acces a toutes les fonctionnalites de votre plan. Votre prochaine facture sera automatique.</p>
        <?php else: ?>
            <span class="badge badge-wait"><i class="fas fa-clock"></i> Activation en cours</span>
            <h2>Votre paiement est en cours de traitement.</h2>
            <p>Votre abonnement sera active dans les prochaines secondes. Rafraichissez la page si ca ne se fait pas automatiquement dans 1 minute.</p>
        <?php endif; ?>
        <a href="<?= APP_BASE . '/' . htmlspecialchars($slug) . '/dashboard' ?>" class="btn">
            <i class="fas fa-arrow-right"></i> Aller au tableau de bord
        </a>
    </div>
</div>
<?php if (!$isActive): ?>
<script>setTimeout(() => location.reload(), 5000);</script>
<?php endif; ?>
</body>
</html>
