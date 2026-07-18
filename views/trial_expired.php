<?php
$tenant = Tenant::current();
$slug = Tenant::slug();
$state = Tenant::trialState();
$plan = $tenant['plan'] ?? 'starter';
if ($plan === 'free') $plan = 'starter';
$price = GeoCurrency::formatPrice($plan);
$polarReady = Polar::isConfigured() && Polar::productIdForPlan($plan);
$subStatus = $tenant['subscription_status'] ?? 'trial';
$pageTitle = match($subStatus) {
    'pending_payment' => 'Complete your payment',
    'cancelled'       => 'Subscription cancelled',
    'expired'         => 'Subscription expired',
    default           => ($state['subscription_status'] === 'trial' ? 'Trial ended' : 'Subscription required'),
};
$pageIntro = match($subStatus) {
    'pending_payment' => 'Finish your checkout to activate your workspace. 7 days free, cancel anytime.',
    'cancelled'       => 'Your subscription was cancelled. Reactivate it to regain access to your workspace.',
    default           => 'Activate your subscription to keep using GestionPro.',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription required - <?= e($tenant['company_name'] ?? 'GestionPro') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',-apple-system,sans-serif;background:linear-gradient(135deg,#0F172A 0%,#1E293B 100%);color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;-webkit-font-smoothing:antialiased}
        .card{max-width:520px;width:100%;background:#fff;color:#0F172A;border-radius:24px;box-shadow:0 40px 80px rgba(0,0,0,.4);overflow:hidden}
        .card-top{background:linear-gradient(135deg,#1B3A5C,#2C5A8A);padding:40px 32px;text-align:center;color:#fff}
        .icon-ring{width:80px;height:80px;margin:0 auto 16px;border-radius:50%;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:34px}
        .card-top h1{font-size:28px;font-weight:900;letter-spacing:-1px;margin-bottom:8px}
        .card-top p{font-size:15px;opacity:.9}
        .card-body{padding:32px}
        .tenant-box{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:18px 20px;margin-bottom:20px}
        .tenant-box .lbl{font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
        .tenant-box .name{font-size:18px;font-weight:700;margin-top:4px}
        .tenant-box .slug{font-size:13px;color:#475569;font-family:'Inter',monospace}
        .plan-box{background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:14px;padding:24px;text-align:center;margin-bottom:24px}
        .plan-box .pname{font-size:14px;font-weight:700;color:#1B3A5C;text-transform:uppercase;letter-spacing:1px}
        .plan-box .pprice{font-size:40px;font-weight:900;color:#0F172A;margin-top:6px;letter-spacing:-1px}
        .plan-box .pper{font-size:13px;color:#64748B}
        .pay-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;background:linear-gradient(135deg,#1B3A5C,#2C5A8A);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .2s}
        .pay-btn:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(27,58,92,.4)}
        .trust-row{display:flex;justify-content:center;gap:18px;flex-wrap:wrap;margin-top:16px;font-size:12px;color:#64748B}
        .trust-row span{display:inline-flex;align-items:center;gap:6px}
        .trust-row i{color:#10B981}
        .warn{background:#FEF3C7;border:1px solid #FCD34D;color:#78350F;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;line-height:1.55}
        .logout-link{text-align:center;display:block;margin-top:20px;font-size:13px;color:#64748B;text-decoration:none}
        .logout-link:hover{color:#0F172A}
    </style>
</head>
<body>
    <div class="card">
        <div class="card-top">
            <div class="icon-ring"><i class="fas fa-credit-card"></i></div>
            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($pageIntro) ?></p>
        </div>
        <div class="card-body">
            <div class="tenant-box">
                <div class="lbl">Your workspace</div>
                <div class="name"><?= e($tenant['company_name'] ?? '') ?></div>
                <div class="slug">gestionpro.it.com/<?= e($slug) ?></div>
            </div>

            <div class="plan-box">
                <div class="pname">Plan <?= strtoupper(e($plan)) ?></div>
                <div class="pprice"><?= e($price) ?></div>
                <div class="pper">per month</div>
            </div>

            <?php if ($polarReady): ?>
                <form method="POST" action="<?= APP_BASE . '/' . e($slug) . '/pay/start' ?>">
                    <input type="hidden" name="plan" value="<?= e($plan) ?>">
                    <button type="submit" class="pay-btn">
                        <i class="fas fa-credit-card"></i> Pay <?= e($price) ?> / month
                    </button>
                </form>
                <div class="trust-row">
                    <span><i class="fas fa-check-circle"></i> Secure checkout</span>
                    <span><i class="fas fa-check-circle"></i> Cancel anytime</span>
                </div>
            <?php else: ?>
                <div class="warn">
                    <i class="fas fa-triangle-exclamation"></i>
                    The payment provider is temporarily unavailable. Please try again in a few minutes or contact support.
                </div>
                <a href="mailto:contact@gestionpro.it.com?subject=<?= urlencode('Subscription activation - ' . $slug) ?>" class="pay-btn">
                    <i class="fas fa-envelope"></i> Contact support
                </a>
            <?php endif; ?>

            <a href="<?= APP_BASE . '/' . e($slug) . '/logout' ?>" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Sign out
            </a>
        </div>
    </div>
</body>
</html>
