<?php
$tenant = Tenant::current();
$slug = Tenant::slug();
$state = Tenant::trialState();
$geo = GeoCurrency::detect();
$plan = $tenant['plan'] ?? 'starter';
$price = GeoCurrency::formatPrice($plan, $geo['currency'], $geo['symbol']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Essai expire - <?= e($tenant['company_name'] ?? 'GestionPro') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',-apple-system,sans-serif;background:linear-gradient(135deg,#0F172A 0%,#1E293B 100%);color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;-webkit-font-smoothing:antialiased}
        .card{max-width:560px;width:100%;background:#fff;color:#0F172A;border-radius:24px;box-shadow:0 40px 80px rgba(0,0,0,.4);overflow:hidden}
        .card-top{background:linear-gradient(135deg,#DC2626,#F59E0B);padding:40px 32px;text-align:center;color:#fff}
        .icon-ring{width:80px;height:80px;margin:0 auto 16px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:34px}
        .card-top h1{font-size:28px;font-weight:900;letter-spacing:-1px;margin-bottom:8px}
        .card-top p{font-size:15px;opacity:.9}
        .card-body{padding:32px}
        .tenant-box{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:18px 20px;margin-bottom:24px}
        .tenant-box .lbl{font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
        .tenant-box .name{font-size:18px;font-weight:700;margin-top:4px}
        .tenant-box .slug{font-size:13px;color:#475569;font-family:'Inter',monospace}
        .plan-box{background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:14px;padding:20px;text-align:center;margin-bottom:24px}
        .plan-box .pname{font-size:14px;font-weight:700;color:#4F46E5;text-transform:uppercase;letter-spacing:1px}
        .plan-box .pprice{font-size:34px;font-weight:900;color:#0F172A;margin-top:4px;letter-spacing:-1px}
        .plan-box .pper{font-size:13px;color:#64748B}
        h2.cta-title{font-size:17px;font-weight:700;margin-bottom:12px}
        .pay-list{list-style:none;margin-bottom:24px}
        .pay-list li{padding:10px 0;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;gap:12px;font-size:14px}
        .pay-list li:last-child{border-bottom:0}
        .pay-list i{width:28px;height:28px;border-radius:8px;background:#EEF2FF;color:#4F46E5;display:flex;align-items:center;justify-content:center;font-size:13px}
        .contact-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:15px;background:#0F172A;color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s;margin-bottom:10px}
        .contact-btn:hover{background:#1E293B;transform:translateY(-1px)}
        .contact-btn.wa{background:#25D366}
        .contact-btn.wa:hover{background:#1EBE57}
        .logout-link{text-align:center;display:block;margin-top:16px;font-size:13px;color:#64748B;text-decoration:none}
        .logout-link:hover{color:#0F172A}
    </style>
</head>
<body>
    <div class="card">
        <div class="card-top">
            <div class="icon-ring"><i class="fas fa-lock"></i></div>
            <h1><?= $state['subscription_status'] === 'trial' ? 'Essai termine' : 'Abonnement requis' ?></h1>
            <p>Pour continuer a utiliser GestionPro, activez votre abonnement.</p>
        </div>
        <div class="card-body">
            <div class="tenant-box">
                <div class="lbl">Votre espace</div>
                <div class="name"><?= e($tenant['company_name'] ?? '') ?></div>
                <div class="slug">gestionpro.it.com/<?= e($slug) ?></div>
            </div>

            <div class="plan-box">
                <div class="pname">Plan <?= strtoupper(e($plan)) ?></div>
                <div class="pprice"><?= e($price) ?></div>
                <div class="pper">par mois</div>
            </div>

            <h2 class="cta-title"><i class="fas fa-circle-info" style="color:#4F46E5;"></i> Comment activer ?</h2>
            <ul class="pay-list">
                <li><i class="fas fa-mobile-screen"></i> Paiement par Bankily, Masrivi ou Sedad</li>
                <li><i class="fas fa-university"></i> Virement bancaire</li>
                <li><i class="fas fa-headset"></i> Support pour vous guider</li>
            </ul>

            <a href="https://wa.me/22248586074?text=<?= urlencode('Bonjour, je souhaite activer mon abonnement GestionPro pour ' . ($tenant['company_name'] ?? '') . ' (' . $slug . ')') ?>" class="contact-btn wa" target="_blank">
                <i class="fab fa-whatsapp"></i> Contacter via WhatsApp
            </a>
            <a href="mailto:contact@gestionpro.it.com?subject=<?= urlencode('Activation abonnement ' . $slug) ?>" class="contact-btn">
                <i class="fas fa-envelope"></i> Envoyer un email
            </a>

            <a href="<?= APP_BASE . '/' . e($slug) . '/logout' ?>" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Se deconnecter
            </a>
        </div>
    </div>
</body>
</html>
