<?php
// Stats for social proof
try {
    $masterDb = Tenant::getMasterDB();
    $tenants = $masterDb->query("SELECT slug, company_name FROM tenants WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();
    $totalTenants = count($tenants);
    $activeTenants = (int)$masterDb->query("SELECT COUNT(*) FROM tenants WHERE subscription_status = 'active' OR subscription_status = 'trial'")->fetchColumn();
} catch (Exception $e) {
    $tenants = [];
    $totalTenants = 0;
    $activeTenants = 0;
}

$priceStarter = GeoCurrency::formatPrice('starter');
$pricePro     = GeoCurrency::formatPrice('pro');

// Pull live Polar products for the dynamic pricing grid. On failure or empty
// response the view falls back to the hardcoded Starter + Pro cards below.
$polarProducts = [];
if (class_exists('Polar') && Polar::isConfigured()) {
    $featuresMap     = class_exists('ProductFeatures') ? ProductFeatures::all() : [];
    $defaultFeatures = class_exists('ProductFeatures') ? ProductFeatures::defaults() : [];
    foreach (Polar::listProductsCached() as $p) {
        if (($p['is_archived'] ?? false) === true) continue;
        $price = ($p['prices'][0] ?? null);
        if (!$price || ($price['amount_type'] ?? '') !== 'fixed') continue;
        $pid = (string)($p['id'] ?? '');
        $polarProducts[] = [
            'id'       => $pid,
            'name'     => (string)($p['name'] ?? ''),
            'desc'     => (string)($p['description'] ?? ''),
            'amount'   => (int)($price['price_amount'] ?? 0), // cents
            'currency' => strtoupper((string)($price['price_currency'] ?? 'USD')),
            'interval' => (string)($p['recurring_interval'] ?? 'month'),
            'features' => !empty($featuresMap[$pid]) ? $featuresMap[$pid] : $defaultFeatures,
        ];
    }
    // Cheapest first so the layout reads naturally.
    usort($polarProducts, static fn($a, $b) => $a['amount'] <=> $b['amount']);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionPro — All-in-one business management software</title>
    <meta name="description" content="Manage stock, invoices, POS and clients. 7-day free trial, cancel anytime.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        :root{
            --primary:#4F46E5;--primary-light:#6366F1;--primary-dark:#3730A3;
            --primary-50:#EEF2FF;--primary-100:#E0E7FF;--primary-200:#C7D2FE;
            --text:#0F172A;--text-secondary:#475569;--text-muted:#94A3B8;
            --bg:#F8FAFC;--surface:#FFFFFF;--border:#E2E8F0;--border-light:#F1F5F9;
            --success:#10B981;--danger:#EF4444;--warning:#F59E0B;
            --shadow-sm:0 1px 2px rgba(0,0,0,.04);
            --shadow-md:0 4px 12px rgba(0,0,0,.06);
            --shadow-lg:0 12px 40px rgba(0,0,0,.08);
            --shadow-xl:0 24px 80px rgba(0,0,0,.12);
        }
        html{scroll-behavior:smooth}
        body{font-family:'Inter',-apple-system,sans-serif;color:var(--text);background:var(--bg);-webkit-font-smoothing:antialiased;line-height:1.5}
        a{text-decoration:none;color:inherit}
        .container{max-width:1200px;margin:0 auto;padding:0 24px}

        /* ===== NAVBAR ===== */
        .navbar{position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.85);backdrop-filter:blur(20px) saturate(180%);border-bottom:1px solid rgba(226,232,240,.6);height:64px;display:flex;align-items:center;transition:all .3s}
        .navbar.scrolled{box-shadow:0 4px 20px rgba(0,0,0,.06)}
        .nav-inner{width:100%;display:flex;align-items:center;justify-content:space-between}
        .nav-brand{display:flex;align-items:center;gap:10px}
        .nav-brand .logo{width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#fff}
        .nav-brand span{font-size:18px;font-weight:800;letter-spacing:-.5px}
        .nav-links{display:flex;align-items:center;gap:4px}
        .nav-links a{padding:8px 14px;border-radius:8px;font-size:14px;font-weight:500;color:var(--text-secondary);transition:all .2s}
        .nav-links a:hover{color:var(--text);background:var(--primary-50)}
        .nav-links .btn-nav{background:var(--primary);color:#fff;font-weight:600;padding:9px 18px;border-radius:10px;display:inline-flex;align-items:center;gap:6px}
        .nav-links .btn-nav:hover{background:var(--primary-dark);color:#fff}

        /* ===== HERO ===== */
        .hero{padding:130px 24px 80px;position:relative;overflow:hidden;background:linear-gradient(180deg,#fff 0%,var(--primary-50) 100%)}
        .hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:900px;height:900px;border-radius:50%;background:radial-gradient(circle,rgba(79,70,229,.08) 0%,transparent 70%);pointer-events:none}
        .hero-inner{max-width:1100px;margin:0 auto;text-align:center;position:relative}
        .hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 16px 6px 8px;background:#fff;border:1px solid var(--primary-100);border-radius:100px;font-size:13px;font-weight:600;color:var(--primary);margin-bottom:28px;box-shadow:var(--shadow-sm)}
        .hero-badge i{width:20px;height:20px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px}
        .hero h1{font-size:clamp(36px,5.5vw,68px);font-weight:900;line-height:1.05;letter-spacing:-2px;max-width:860px;margin:0 auto 20px;background:linear-gradient(135deg,var(--text) 0%,var(--primary-dark) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-sub{font-size:clamp(16px,2vw,19px);color:var(--text-secondary);max-width:620px;margin:0 auto 36px;line-height:1.65}
        .hero-trust{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:28px;font-size:13px;color:var(--text-secondary)}
        .hero-trust span{display:inline-flex;align-items:center;gap:6px}
        .hero-trust i{color:var(--success)}
        .hero-cta{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:52px}
        .btn-cta{display:inline-flex;align-items:center;gap:10px;padding:16px 30px;border-radius:14px;font-weight:700;font-size:15px;transition:all .25s;cursor:pointer;border:none;font-family:inherit}
        .btn-primary-cta{background:var(--primary);color:#fff;box-shadow:0 8px 24px rgba(79,70,229,.35)}
        .btn-primary-cta:hover{background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 14px 32px rgba(79,70,229,.4)}
        .btn-secondary-cta{background:var(--surface);color:var(--text);border:1.5px solid var(--border)}
        .btn-secondary-cta:hover{border-color:var(--primary);color:var(--primary);transform:translateY(-2px);box-shadow:var(--shadow-md)}

        /* Stats row */
        .hero-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;max-width:880px;margin:0 auto 60px;padding:24px;background:#fff;border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-md)}
        .hero-stat{text-align:center}
        .hero-stat .v{font-size:clamp(22px,3vw,32px);font-weight:900;letter-spacing:-1px;color:var(--primary);font-variant-numeric:tabular-nums}
        .hero-stat .l{font-size:12px;color:var(--text-muted);margin-top:4px;font-weight:500}

        /* Hero mockup */
        .hero-mockup{max-width:1000px;margin:0 auto;position:relative;background:var(--surface);border-radius:16px;border:1px solid var(--border);box-shadow:var(--shadow-xl),0 0 0 1px rgba(0,0,0,.02);overflow:hidden}
        .mockup-toolbar{display:flex;align-items:center;gap:8px;padding:12px 16px;border-bottom:1px solid var(--border);background:#FAFBFC}
        .mockup-dot{width:10px;height:10px;border-radius:50%}
        .mockup-dot.r{background:#EF4444}.mockup-dot.y{background:#F59E0B}.mockup-dot.g{background:#10B981}
        .mockup-body{display:flex;min-height:340px}
        .mockup-sidebar{width:200px;border-right:1px solid var(--border);padding:16px 12px;background:#FAFBFC}
        .mockup-sidebar .m-item{padding:8px 12px;border-radius:8px;font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:8px;margin-bottom:2px}
        .mockup-sidebar .m-item.active{background:var(--primary-50);color:var(--primary);font-weight:600}
        .mockup-sidebar .m-item i{width:16px;text-align:center;font-size:11px}
        .mockup-main{flex:1;padding:20px}
        .mockup-kpi{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
        .m-kpi{padding:14px;border-radius:10px;border:1px solid var(--border);background:var(--surface)}
        .m-kpi .val{font-size:20px;font-weight:800}
        .m-kpi .lbl{font-size:10px;color:var(--text-muted);margin-top:2px}
        .m-kpi .val.green{color:var(--success)}.m-kpi .val.blue{color:var(--primary)}.m-kpi .val.orange{color:var(--warning)}
        .mockup-chart{height:140px;background:linear-gradient(180deg,rgba(79,70,229,.06) 0%,transparent 100%);border-radius:10px;border:1px solid var(--border);position:relative;overflow:hidden}
        .mockup-chart svg{position:absolute;bottom:0;left:0;width:100%}

        /* ===== SECTION ===== */
        section.pad{padding:100px 24px}
        .section-label{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:12px}
        .section-title{font-size:clamp(28px,3.5vw,44px);font-weight:800;letter-spacing:-1.5px;margin-bottom:16px;line-height:1.15}
        .section-desc{font-size:17px;color:var(--text-secondary);max-width:600px;line-height:1.7;margin-bottom:48px}
        .section-desc.center{margin-left:auto;margin-right:auto}

        /* ===== FEATURES ===== */
        .features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
        .feat-card{padding:28px;border-radius:18px;border:1px solid var(--border);background:var(--surface);transition:all .25s;position:relative;overflow:hidden}
        .feat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--primary-light));transform:scaleX(0);transform-origin:left;transition:transform .3s}
        .feat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:var(--primary-200)}
        .feat-card:hover::before{transform:scaleX(1)}
        .feat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:18px}
        .feat-icon.i1{background:rgba(79,70,229,.1);color:#4F46E5}
        .feat-icon.i2{background:rgba(16,185,129,.1);color:#10B981}
        .feat-icon.i3{background:rgba(245,158,11,.1);color:#F59E0B}
        .feat-icon.i4{background:rgba(14,165,233,.1);color:#0EA5E9}
        .feat-icon.i5{background:rgba(239,68,68,.1);color:#EF4444}
        .feat-icon.i6{background:rgba(168,85,247,.1);color:#A855F7}
        .feat-card h3{font-size:17px;font-weight:700;margin-bottom:8px}
        .feat-card p{font-size:14px;color:var(--text-secondary);line-height:1.65}

        /* ===== STEPS ===== */
        .how-section{padding:80px 24px;background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
        .how-inner{max-width:1000px;margin:0 auto;text-align:center}
        .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;margin-top:48px}
        .step{position:relative}
        .step-num{width:56px;height:56px;border-radius:16px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:var(--primary);background:var(--primary-50);border:2px solid var(--primary-100)}
        .step h3{font-size:16px;font-weight:700;margin-bottom:8px}
        .step p{font-size:14px;color:var(--text-secondary);line-height:1.6}
        .step-arrow{position:absolute;top:28px;right:-20px;color:var(--border);font-size:20px}

        /* ===== PRICING ===== */
        .pricing-center{text-align:center}
        .pricing-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-top:48px;text-align:left;max-width:760px;margin-left:auto;margin-right:auto}
        .price-card{padding:32px 28px;border-radius:18px;border:1px solid var(--border);background:var(--surface);position:relative;transition:all .25s}
        .price-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
        .price-card.popular{border-color:var(--primary);box-shadow:0 8px 32px rgba(79,70,229,.15);border-width:1.5px}
        .price-card.popular::before{content:'\2B50 Most popular';position:absolute;top:-12px;left:50%;transform:translateX(-50%);padding:5px 14px;background:var(--primary);color:#fff;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap}
        .trial-badge{display:inline-block;padding:3px 10px;background:rgba(16,185,129,.1);color:var(--success);font-size:11px;font-weight:700;border-radius:100px;margin-bottom:10px;letter-spacing:.3px}
        .price-name{font-size:19px;font-weight:700;margin-bottom:4px}
        .price-desc{font-size:13px;color:var(--text-muted);margin-bottom:20px}
        .price-amount{font-size:42px;font-weight:900;letter-spacing:-1.5px;line-height:1}
        .price-amount span{font-size:14px;font-weight:500;color:var(--text-muted);margin-left:4px}
        .price-note{font-size:12px;color:var(--text-muted);margin-top:4px}
        .price-list{list-style:none;margin:24px 0}
        .price-list li{padding:8px 0;font-size:14px;color:var(--text-secondary);display:flex;align-items:flex-start;gap:10px}
        .price-list li i{color:var(--success);font-size:13px;width:16px;margin-top:3px}
        .btn-price{display:block;width:100%;padding:13px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all .2s;text-align:center;font-family:inherit}
        .btn-price.outline{background:transparent;border:1.5px solid var(--border);color:var(--text)}
        .btn-price.outline:hover{border-color:var(--primary);color:var(--primary)}
        .btn-price.filled{background:var(--primary);color:#fff}
        .btn-price.filled:hover{background:var(--primary-dark)}
        .pricing-note{text-align:center;margin-top:28px;font-size:13px;color:var(--text-muted)}

        /* ===== TRUST / SECURITY ===== */
        .trust-section{padding:80px 24px;background:linear-gradient(135deg,#0F172A 0%,#1E293B 100%);color:#fff}
        .trust-inner{max-width:1100px;margin:0 auto}
        .trust-inner .section-label{color:#818CF8}
        .trust-inner .section-title{color:#fff}
        .trust-inner .section-desc{color:#CBD5E1}
        .trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px}
        .trust-item{padding:22px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:14px}
        .trust-item i{font-size:22px;color:#818CF8;margin-bottom:10px}
        .trust-item h4{font-size:14px;font-weight:700;margin-bottom:6px}
        .trust-item p{font-size:12px;color:#94A3B8;line-height:1.55}

        /* ===== FAQ ===== */
        .faq-wrap{max-width:780px;margin:48px auto 0}
        .faq-item{background:#fff;border:1px solid var(--border);border-radius:12px;margin-bottom:12px;overflow:hidden;transition:all .2s}
        .faq-item[open]{border-color:var(--primary-200);box-shadow:var(--shadow-md)}
        .faq-item summary{padding:18px 22px;font-size:15px;font-weight:600;cursor:pointer;display:flex;justify-content:space-between;align-items:center;list-style:none;transition:color .2s}
        .faq-item summary::-webkit-details-marker{display:none}
        .faq-item summary::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;color:var(--text-muted);transition:transform .25s;font-size:12px}
        .faq-item[open] summary::after{transform:rotate(180deg);color:var(--primary)}
        .faq-item summary:hover{color:var(--primary)}
        .faq-item .faq-body{padding:0 22px 20px;font-size:14px;color:var(--text-secondary);line-height:1.7}

        /* ===== CTA BANNER ===== */
        .cta-banner{padding:80px 24px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;text-align:center}
        .cta-banner h2{font-size:clamp(28px,3.5vw,40px);font-weight:800;letter-spacing:-1px;margin-bottom:14px}
        .cta-banner p{font-size:16px;opacity:.9;max-width:560px;margin:0 auto 32px}
        .cta-banner .btn-cta{background:#fff;color:var(--primary)}
        .cta-banner .btn-cta:hover{background:#fff;transform:translateY(-2px);box-shadow:0 12px 32px rgba(0,0,0,.2)}

        /* ===== FOOTER ===== */
        .footer{padding:48px 24px 24px;text-align:center;border-top:1px solid var(--border);background:#fff}
        .footer-brand{font-size:18px;font-weight:800;margin-bottom:8px}
        .footer>p{font-size:13px;color:var(--text-muted)}
        .footer-links{margin-top:14px;display:flex;gap:22px;justify-content:center;flex-wrap:wrap}
        .footer-links a{font-size:13px;color:var(--text-secondary);transition:color .2s}
        .footer-links a:hover{color:var(--primary)}

        /* ===== MODAL ===== */
        .modal-overlay{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.6);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:20px}
        .modal-overlay.active{display:flex}
        .modal{background:var(--surface);border-radius:20px;width:100%;max-width:480px;box-shadow:var(--shadow-xl);animation:modalIn .3s ease;max-height:90vh;overflow-y:auto}
        @keyframes modalIn{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:none}}
        .modal-header{padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start}
        .modal-header h2{font-size:22px;font-weight:800}
        .modal-header p{font-size:14px;color:var(--text-muted);margin-top:4px}
        .modal-close{background:none;border:none;font-size:22px;color:var(--text-muted);cursor:pointer;padding:4px}
        .modal-close:hover{color:var(--text)}
        .modal-body{padding:24px 28px 28px}
        .trial-modal-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:rgba(16,185,129,.1);color:var(--success);font-size:12px;font-weight:700;border-radius:100px;margin-bottom:14px}
        .fg{margin-bottom:16px}
        .fg label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--text-secondary)}
        .fg input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:inherit;transition:border .2s;background:var(--bg)}
        .fg input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,.1)}
        .slug-preview{display:flex;align-items:center;gap:0;margin-top:6px}
        .slug-prefix{padding:11px 12px;background:var(--bg);border:1.5px solid var(--border);border-right:0;border-radius:10px 0 0 10px;font-size:12px;color:var(--text-muted);white-space:nowrap}
        .slug-preview input{border-radius:0 10px 10px 0}
        .fg-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .btn-register{width:100%;padding:14px;border:none;border-radius:12px;background:var(--primary);color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit}
        .btn-register:hover{background:var(--primary-dark)}
        .btn-register:disabled{opacity:.6;cursor:not-allowed}
        .register-error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:var(--danger);padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none}

        /* ===== RESPONSIVE ===== */
        @media (max-width:900px){
            .features-grid,.steps,.trust-grid{grid-template-columns:1fr}
            .pricing-grid{grid-template-columns:1fr}
            .trust-grid{grid-template-columns:repeat(2,1fr)}
            .hero-stats{grid-template-columns:repeat(2,1fr);gap:14px}
        }
        @media (max-width:768px){
            .mockup-sidebar{display:none}
            .mockup-kpi{grid-template-columns:1fr}
            .hero{padding:110px 20px 50px}
            .step-arrow{display:none}
            .nav-links .hide-mobile{display:none}
            .fg-row{grid-template-columns:1fr}
            section.pad{padding:70px 20px}
            .trust-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <div class="nav-brand">
            <div class="logo">GP</div>
            <span>GestionPro</span>
        </div>
        <div class="nav-links">
            <a href="#features" class="hide-mobile">Features</a>
            <a href="#pricing" class="hide-mobile">Pricing</a>
            <a href="#faq" class="hide-mobile">FAQ</a>
            <a href="<?= APP_BASE ?>/admin/login" class="hide-mobile" style="font-size:13px;color:var(--text-muted);" title="Administration"><i class="fas fa-shield-halved"></i></a>
            <a href="javascript:void(0)" class="btn-nav" onclick="openRegister()"><i class="fas fa-rocket"></i> Start free trial</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <i class="fas fa-gift"></i> 7-day trial — cancel anytime
        </div>
        <h1>Run your business.<br>Simplify your day.</h1>
        <p class="hero-sub">The all-in-one platform for shops, SMBs and service businesses. Stock, POS, invoices, clients and finance — one tool, ready in 30 seconds.</p>

        <div class="hero-trust">
            <span><i class="fas fa-check-circle"></i> 7 days free</span>
            <span><i class="fas fa-check-circle"></i> Cancel before day 7 — no charge</span>
            <span><i class="fas fa-check-circle"></i> Available in English, French and Arabic</span>
        </div>

        <div class="hero-cta">
            <button class="btn-cta btn-primary-cta" onclick="openRegister()"><i class="fas fa-rocket"></i> Create my workspace</button>
            <a href="#features" class="btn-cta btn-secondary-cta"><i class="fas fa-play-circle"></i> See the demo</a>
        </div>

        <div class="hero-stats">
            <div class="hero-stat"><div class="v"><?= max($totalTenants, 50) ?>+</div><div class="l">Active businesses</div></div>
            <div class="hero-stat"><div class="v">30<span style="font-size:.7em;">s</span></div><div class="l">Sign-up</div></div>
            <div class="hero-stat"><div class="v">99.9%</div><div class="l">Uptime</div></div>
            <div class="hero-stat"><div class="v">24/7</div><div class="l">Access</div></div>
        </div>

        <div class="hero-mockup">
            <div class="mockup-toolbar">
                <div class="mockup-dot r"></div><div class="mockup-dot y"></div><div class="mockup-dot g"></div>
                <div style="flex:1;text-align:center;font-size:12px;color:var(--text-muted);">gestionpro.it.com/my-business/dashboard</div>
            </div>
            <div class="mockup-body">
                <div class="mockup-sidebar">
                    <div class="m-item active"><i class="fas fa-chart-line"></i> Dashboard</div>
                    <div class="m-item"><i class="fas fa-cash-register"></i> POS</div>
                    <div class="m-item"><i class="fas fa-boxes-stacked"></i> Products</div>
                    <div class="m-item"><i class="fas fa-users"></i> Clients</div>
                    <div class="m-item"><i class="fas fa-file-invoice-dollar"></i> Invoices</div>
                    <div class="m-item"><i class="fas fa-hand-holding-dollar"></i> Debts</div>
                    <div class="m-item"><i class="fas fa-money-bill-wave"></i> Payments</div>
                </div>
                <div class="mockup-main">
                    <div class="mockup-kpi">
                        <div class="m-kpi"><div class="val green">$8,475</div><div class="lbl">Today's revenue</div></div>
                        <div class="m-kpi"><div class="val blue">2,340</div><div class="lbl">Products in stock</div></div>
                        <div class="m-kpi"><div class="val orange">12</div><div class="lbl">Stock alerts</div></div>
                    </div>
                    <div class="mockup-chart">
                        <svg viewBox="0 0 500 100" preserveAspectRatio="none" style="height:100%;width:100%;">
                            <defs><linearGradient id="cg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="rgba(79,70,229,0.15)"/><stop offset="100%" stop-color="rgba(79,70,229,0)"/></linearGradient></defs>
                            <path d="M0,80 C50,60 100,70 150,45 C200,20 250,50 300,30 C350,10 400,40 450,25 L500,20 L500,100 L0,100Z" fill="url(#cg)"/>
                            <path d="M0,80 C50,60 100,70 150,45 C200,20 250,50 300,30 C350,10 400,40 450,25 L500,20" fill="none" stroke="#4F46E5" stroke-width="2.5"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="pad" id="features">
    <div class="container">
        <div class="section-label"><i class="fas fa-bolt"></i> Features</div>
        <div class="section-title">Everything to run your business</div>
        <div class="section-desc">A complete toolkit for shops and SMBs anywhere in the world. Multi-user, multilingual, ready out of the box.</div>

        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon i1"><i class="fas fa-cash-register"></i></div>
                <h3>Fast POS</h3>
                <p>Intuitive sales interface with barcode scan, instant product search and one-click checkout.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i2"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Stock management</h3>
                <p>Real-time tracking, low-stock alerts, automatic ins and outs with every sale.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i3"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3>Quotes & Invoices</h3>
                <p>Create quotes, convert them to invoices, generate professional PDFs in seconds.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i4"><i class="fas fa-hand-holding-dollar"></i></div>
                <h3>Credit tracking</h3>
                <p>Manage customer receivables and supplier payables. Know who owes you what, anytime.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i5"><i class="fas fa-chart-line"></i></div>
                <h3>Dashboards & reports</h3>
                <p>Revenue trends, top products, cash positions — clear insights at a glance.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i6"><i class="fas fa-language"></i></div>
                <h3>3 languages</h3>
                <p>Full interface in English, French and Arabic with native RTL support. Switch in one click.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="how-inner">
        <div class="section-label"><i class="fas fa-wand-magic-sparkles"></i> Getting started</div>
        <div class="section-title" style="margin-bottom:0;">Ready in 30 seconds</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Create your workspace</h3>
                <p>Pick a name for your business. Enter your card to start the 7-day trial.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Add your products</h3>
                <p>Import your catalogue, set prices and opening stock levels.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Start selling</h3>
                <p>Use the POS, invoice clients, track your finances in real time.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pad pricing-center" id="pricing">
    <div class="container">
        <div class="section-label"><i class="fas fa-tags"></i> Pricing</div>
        <div class="section-title">A plan for every business</div>
        <p class="section-desc center">Start with a <strong>7-day free trial</strong> on any plan. Cancel before the trial ends and you won't be charged.</p>

        <div class="pricing-grid">
        <?php if (!empty($polarProducts)):
            $popularIdx = count($polarProducts) >= 2 ? 1 : 0; // second card, or the only one
            foreach ($polarProducts as $i => $prod):
                $priceStr  = '$' . number_format($prod['amount'] / 100, 0, ',', ' ');
                $intervalL = $prod['interval'] === 'year' ? '/ year' : '/ month';
                $billed    = $prod['interval'] === 'year' ? 'Billed yearly' : 'Billed monthly';
                $isPopular = ($i === $popularIdx);
                $btnClass  = $isPopular ? 'btn-price filled' : 'btn-price outline';
        ?>
            <div class="price-card<?= $isPopular ? ' popular' : '' ?>">
                <div class="trial-badge"><i class="fas fa-gift" style="font-size:10px;"></i> 7-day trial</div>
                <div class="price-name"><?= htmlspecialchars($prod['name'], ENT_QUOTES) ?></div>
                <div class="price-desc"><?= htmlspecialchars($prod['desc'] !== '' ? $prod['desc'] : 'Secure plan on Polar') ?></div>
                <div class="price-amount"><?= $priceStr ?><span><?= $intervalL ?></span></div>
                <div class="price-note"><?= $billed ?></div>
                <ul class="price-list">
                    <?php foreach ($prod['features'] as $bullet): ?>
                    <li><i class="fas fa-check"></i> <?= htmlspecialchars($bullet, ENT_QUOTES) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button class="<?= $btnClass ?>" onclick="openRegister('<?= htmlspecialchars($prod['id'], ENT_QUOTES) ?>')">Start 7-day trial</button>
            </div>
        <?php endforeach; else: ?>
            <div class="price-card popular">
                <div class="trial-badge"><i class="fas fa-gift" style="font-size:10px;"></i> 7-day trial</div>
                <div class="price-name">Starter</div>
                <div class="price-desc">For growing SMBs</div>
                <div class="price-amount"><?= $priceStarter ?><span>/ month</span></div>
                <div class="price-note">Billed monthly</div>
                <ul class="price-list">
                    <li><i class="fas fa-check"></i> 5 users</li>
                    <li><i class="fas fa-check"></i> 500 products</li>
                    <li><i class="fas fa-check"></i> POS + unlimited invoices</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                    <li><i class="fas fa-check"></i> Cancel anytime during the trial</li>
                </ul>
                <button class="btn-price filled" onclick="openRegister('starter')">Start 7-day trial</button>
            </div>
            <div class="price-card">
                <div class="trial-badge"><i class="fas fa-gift" style="font-size:10px;"></i> 7-day trial</div>
                <div class="price-name">Pro</div>
                <div class="price-desc">For larger teams</div>
                <div class="price-amount"><?= $pricePro ?><span>/ month</span></div>
                <div class="price-note">Billed monthly</div>
                <ul class="price-list">
                    <li><i class="fas fa-check"></i> 15 users</li>
                    <li><i class="fas fa-check"></i> 5,000 products</li>
                    <li><i class="fas fa-check"></i> Everything in Starter</li>
                    <li><i class="fas fa-check"></i> 24/7 support</li>
                    <li><i class="fas fa-check"></i> Advanced reports</li>
                </ul>
                <button class="btn-price outline" onclick="openRegister('pro')">Start 7-day trial</button>
            </div>
        <?php endif; ?>
        </div>

        <p class="pricing-note">
            <i class="fas fa-circle-info"></i>
            All prices in USD. Secure checkout by card — cancel anytime.
        </p>
    </div>
</section>

<!-- TRUST / SECURITY -->
<section class="trust-section">
    <div class="trust-inner">
        <div class="section-label"><i class="fas fa-shield-halved"></i> Security</div>
        <div class="section-title">Your data is protected</div>
        <div class="section-desc">Encryption, backups and data isolation — your business deserves solid infrastructure.</div>
        <div class="trust-grid">
            <div class="trust-item">
                <i class="fas fa-lock"></i>
                <h4>HTTPS everywhere</h4>
                <p>TLS 1.3 encrypted connections on every page and API call.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-database"></i>
                <h4>Dedicated database</h4>
                <p>Every customer gets its own isolated database.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-hard-drive"></i>
                <h4>Daily backups</h4>
                <p>Automatic daily backups of your data.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-user-shield"></i>
                <h4>Secure passwords</h4>
                <p>Bcrypt hashing — no plain-text passwords ever.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="pad" id="faq" style="background:#fff;">
    <div class="container" style="text-align:center;">
        <div class="section-label"><i class="fas fa-circle-question"></i> Questions</div>
        <div class="section-title">Everything you want to know</div>

        <div class="faq-wrap" style="text-align:left;">
            <details class="faq-item">
                <summary>How does the 7-day trial work?</summary>
                <div class="faq-body">We record your card at signup but do not charge anything for 7 days. You can cancel at any time during the trial and no charge will be made. Your card is only billed on day 8 if you haven't cancelled.</div>
            </details>
            <details class="faq-item">
                <summary>What happens after the 7-day trial?</summary>
                <div class="faq-body">If you haven't cancelled, your monthly subscription starts automatically and your card is charged. Otherwise, access is suspended, your data is kept and you can reactivate at any time.</div>
            </details>
            <details class="faq-item">
                <summary>Can I cancel at any time?</summary>
                <div class="faq-body">Yes, no commitment. You can cancel in one click from your workspace (Settings &gt; Subscription). If you cancel during the trial, nothing is charged. Afterwards you keep access until the end of the period you already paid for.</div>
            </details>
            <details class="faq-item">
                <summary>Is my data secure?</summary>
                <div class="faq-body">Absolutely. HTTPS encryption, a dedicated isolated database per customer, automatic daily backups. Your data belongs to you and is exportable at any time.</div>
            </details>
            <details class="faq-item">
                <summary>Can I use GestionPro from a phone?</summary>
                <div class="faq-body">Yes, the interface is fully responsive and works perfectly on phone, tablet and desktop.</div>
            </details>
            <details class="faq-item">
                <summary>Which languages are supported?</summary>
                <div class="faq-body">English (default), French and Arabic — with native RTL support. You can switch languages in one click from your workspace.</div>
            </details>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="cta-banner">
    <div class="container">
        <h2>Ready to simplify your business?</h2>
        <p>Create your workspace in 30 seconds. 7 days free, cancel anytime.</p>
        <button class="btn-cta btn-primary-cta" onclick="openRegister()"><i class="fas fa-rocket"></i> Create my workspace</button>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-brand">GestionPro</div>
    <p>&copy; <?= date('Y') ?> GestionPro — Business management software</p>
    <div class="footer-links">
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="#faq">FAQ</a>
        <a href="<?= APP_BASE ?>/admin/login">Administration</a>
    </div>
</footer>

<!-- REGISTER MODAL -->
<div class="modal-overlay" id="registerModal">
    <div class="modal">
        <div id="registerForm">
            <div class="modal-header">
                <div>
                    <div class="trial-modal-badge"><i class="fas fa-gift" style="font-size:11px;"></i> 7-day free trial</div>
                    <h2>Create your workspace</h2>
                    <p>Ready in 30 seconds, cancel anytime</p>
                </div>
                <button class="modal-close" onclick="closeRegister()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="register-error" id="regError"></div>

                <div class="fg">
                    <label>Business name *</label>
                    <input type="text" id="regCompany" placeholder="e.g. Acme Shop" required>
                </div>
                <div class="fg">
                    <label>Workspace address *</label>
                    <div class="slug-preview">
                        <span class="slug-prefix">gestionpro.it.com/</span>
                        <input type="text" id="regSlug" placeholder="acme-shop" pattern="[a-z0-9_-]+" required>
                    </div>
                </div>
                <div class="fg-row">
                    <div class="fg">
                        <label>Your name *</label>
                        <input type="text" id="regName" placeholder="Jane Doe">
                    </div>
                    <div class="fg">
                        <label>Phone</label>
                        <input type="text" id="regPhone" placeholder="+1 555 123 4567">
                    </div>
                </div>
                <div class="fg">
                    <label>Email *</label>
                    <input type="email" id="regEmail" placeholder="you@example.com">
                </div>
                <div class="fg">
                    <label>Password *</label>
                    <input type="password" id="regPassword" placeholder="At least 6 characters">
                </div>
                <input type="hidden" id="regPlan" value="starter">
                <button class="btn-register" id="regSubmit" onclick="submitRegister()">
                    <i class="fas fa-rocket"></i> Continue to secure checkout
                </button>
                <p style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
                    7 days free. Cancel before day 7 and you won't be charged.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior:'smooth', block:'start' }); }
    });
});

document.getElementById('regCompany').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .substring(0, 30);
    document.getElementById('regSlug').value = slug;
});

function openRegister(plan) {
    document.getElementById('regPlan').value = plan || 'starter';
    document.getElementById('registerModal').classList.add('active');
    document.getElementById('registerForm').style.display = '';
    document.getElementById('regError').style.display = 'none';
    setTimeout(() => document.getElementById('regCompany').focus(), 100);
}

function closeRegister() {
    document.getElementById('registerModal').classList.remove('active');
}

document.getElementById('registerModal').addEventListener('click', function(e) {
    if (e.target === this) closeRegister();
});

function submitRegister() {
    const btn = document.getElementById('regSubmit');
    const errEl = document.getElementById('regError');
    errEl.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating your workspace...';

    const formData = new FormData();
    formData.append('slug', document.getElementById('regSlug').value);
    formData.append('company_name', document.getElementById('regCompany').value);
    formData.append('owner_name', document.getElementById('regName').value);
    formData.append('owner_email', document.getElementById('regEmail').value);
    formData.append('owner_phone', document.getElementById('regPhone').value);
    formData.append('password', document.getElementById('regPassword').value);
    formData.append('plan', document.getElementById('regPlan').value);

    fetch('<?= APP_BASE ?>/register', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.mode === 'polar_checkout' && data.checkout_url) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting to checkout...';
                    window.location.href = data.checkout_url;
                    return;
                }
                if (data.url) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening your workspace...';
                    window.location.href = data.url;
                    return;
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket"></i> Continue to secure checkout';
                errEl.textContent = 'Workspace created but we could not continue automatically. Please sign in.';
                errEl.style.display = 'block';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket"></i> Continue to secure checkout';
                errEl.textContent = data.error;
                errEl.style.display = 'block';
            }
        })
        .catch(e => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> Continue to secure checkout';
            errEl.textContent = 'Connection error. Please try again.';
            errEl.style.display = 'block';
        });
}

document.querySelectorAll('#registerModal input').forEach(input => {
    input.addEventListener('keydown', e => { if (e.key === 'Enter') submitRegister(); });
});
</script>

</body>
</html>
