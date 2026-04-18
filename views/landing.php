<?php
// Allow manual currency override via ?currency=MRU|USD (stored in session)
if (isset($_GET['currency']) && in_array($_GET['currency'], ['MRU', 'USD'], true)) {
    $_SESSION['geo_currency'] = [
        'country'  => $_GET['currency'] === 'MRU' ? 'MR' : 'XX',
        'currency' => $_GET['currency'],
        'symbol'   => $_GET['currency'] === 'MRU' ? 'UM' : '$',
    ];
}

$geo = GeoCurrency::detect();
$cur = $geo['currency'];
$sym = $geo['symbol'];

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

$priceStarter = GeoCurrency::formatPrice('starter', $cur, $sym);
$pricePro     = GeoCurrency::formatPrice('pro',     $cur, $sym);
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionPro — Logiciel de gestion commerciale tout-en-un</title>
    <meta name="description" content="Gerez stocks, factures, caisse POS et clients. 7 jours d'essai gratuit, annulable a tout moment. Adapte a la Mauritanie et l'Afrique francophone.">
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
        .currency-switch{display:inline-flex;padding:3px;background:var(--primary-50);border:1px solid var(--primary-100);border-radius:100px;font-size:11px;font-weight:700;margin-right:4px}
        .currency-switch a{padding:5px 11px;border-radius:100px;color:var(--text-muted);transition:all .2s}
        .currency-switch a.on{background:var(--primary);color:#fff;box-shadow:0 1px 3px rgba(79,70,229,.3)}

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

        /* ===== SOCIAL PROOF ROW ===== */
        .logos-row{padding:40px 24px;background:#fff;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
        .logos-row .lbl{text-align:center;font-size:13px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:20px}
        .logos-grid{display:flex;justify-content:center;align-items:center;gap:40px;flex-wrap:wrap;opacity:.7}
        .logo-chip{display:flex;align-items:center;gap:10px;font-weight:700;font-size:15px;color:var(--text-secondary)}
        .logo-chip i{font-size:20px;color:var(--primary)}

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
        .pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:48px;text-align:left;max-width:1100px;margin-left:auto;margin-right:auto}
        .price-card{padding:32px 28px;border-radius:18px;border:1px solid var(--border);background:var(--surface);position:relative;transition:all .25s}
        .price-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
        .price-card.popular{border-color:var(--primary);box-shadow:0 8px 32px rgba(79,70,229,.15);border-width:1.5px}
        .price-card.popular::before{content:'⭐ Plus choisi';position:absolute;top:-12px;left:50%;transform:translateX(-50%);padding:5px 14px;background:var(--primary);color:#fff;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap}
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

        /* ===== TESTIMONIALS ===== */
        .testimonials-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:48px}
        .testimonial{padding:28px;background:#fff;border:1px solid var(--border);border-radius:16px;position:relative}
        .testimonial .quote{font-size:15px;color:var(--text);line-height:1.65;margin-bottom:18px}
        .testimonial .author{display:flex;align-items:center;gap:12px}
        .testimonial .avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px}
        .testimonial .info{font-size:13px}
        .testimonial .info strong{display:block;font-weight:700}
        .testimonial .info span{color:var(--text-muted)}
        .stars{color:#F59E0B;margin-bottom:12px;font-size:13px}

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

        /* ===== CLIENTS ===== */
        .clients-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-top:24px}
        .client-card{display:flex;align-items:center;gap:12px;padding:16px 18px;border:1px solid var(--border);border-radius:12px;background:var(--surface);transition:all .2s}
        .client-card:hover{border-color:var(--primary);transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .client-avatar{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:#fff;flex-shrink:0}
        .client-card h4{font-size:14px;font-weight:600}
        .client-card p{font-size:12px;color:var(--text-muted)}

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
        .register-success{text-align:center;padding:40px 28px}
        .register-success .check{width:72px;height:72px;border-radius:50%;background:rgba(16,185,129,.1);display:inline-flex;align-items:center;justify-content:center;font-size:32px;color:var(--success);margin-bottom:18px}
        .register-success h3{font-size:22px;font-weight:800;margin-bottom:8px}
        .register-success p{font-size:14px;color:var(--text-secondary);margin-bottom:22px;line-height:1.65}
        .register-success .creds{background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:14px 18px;text-align:left;margin-bottom:20px;font-size:14px}
        .register-success .creds>div{margin-bottom:6px}
        .register-success .creds>div:last-child{margin-bottom:0}
        .register-success .creds strong{color:var(--primary)}
        .btn-go{display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:var(--primary);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;text-decoration:none}
        .btn-go:hover{background:var(--primary-dark)}

        /* ===== RESPONSIVE ===== */
        @media (max-width:900px){
            .features-grid,.pricing-grid,.steps,.testimonials-grid,.trust-grid{grid-template-columns:1fr}
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
            <a href="#features" class="hide-mobile">Fonctionnalites</a>
            <a href="#pricing" class="hide-mobile">Tarifs</a>
            <a href="#faq" class="hide-mobile">FAQ</a>
            <span class="currency-switch hide-mobile" title="Devise d'affichage">
                <a href="?currency=MRU#pricing" class="<?= $cur === 'MRU' ? 'on' : '' ?>">MRU</a>
                <a href="?currency=USD#pricing" class="<?= $cur === 'USD' ? 'on' : '' ?>">USD</a>
            </span>
            <a href="<?= APP_BASE ?>/admin/login" class="hide-mobile" style="font-size:13px;color:var(--text-muted);" title="Administration"><i class="fas fa-shield-halved"></i></a>
            <a href="javascript:void(0)" class="btn-nav" onclick="openRegister()"><i class="fas fa-rocket"></i> Essayer gratuit</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <i class="fas fa-gift"></i> 7 jours d'essai — annulable a tout moment
        </div>
        <h1>Gerez votre commerce.<br>Simplifiez votre journee.</h1>
        <p class="hero-sub">La solution tout-en-un pour commerces, PME et boutiques. Stocks, caisse POS, factures, clients et finances — dans un seul outil, pret en 30 secondes.</p>

        <div class="hero-trust">
            <span><i class="fas fa-check-circle"></i> 7 jours gratuits a l'essai</span>
            <span><i class="fas fa-check-circle"></i> Annulez avant J+7 et rien ne sera debite</span>
            <span><i class="fas fa-check-circle"></i> Support bilingue FR/AR</span>
        </div>

        <div class="hero-cta">
            <button class="btn-cta btn-primary-cta" onclick="openRegister()"><i class="fas fa-rocket"></i> Creer mon espace gratuitement</button>
            <a href="#features" class="btn-cta btn-secondary-cta"><i class="fas fa-play-circle"></i> Voir la demo</a>
        </div>

        <!-- Stats -->
        <div class="hero-stats">
            <div class="hero-stat"><div class="v"><?= max($totalTenants, 50) ?>+</div><div class="l">Entreprises actives</div></div>
            <div class="hero-stat"><div class="v">30<span style="font-size:.7em;">s</span></div><div class="l">Inscription</div></div>
            <div class="hero-stat"><div class="v">99.9%</div><div class="l">Disponibilite</div></div>
            <div class="hero-stat"><div class="v">24/7</div><div class="l">Acces</div></div>
        </div>

        <!-- Mockup -->
        <div class="hero-mockup">
            <div class="mockup-toolbar">
                <div class="mockup-dot r"></div><div class="mockup-dot y"></div><div class="mockup-dot g"></div>
                <div style="flex:1;text-align:center;font-size:12px;color:var(--text-muted);">gestionpro.it.com/mon-entreprise/dashboard</div>
            </div>
            <div class="mockup-body">
                <div class="mockup-sidebar">
                    <div class="m-item active"><i class="fas fa-chart-line"></i> Tableau de bord</div>
                    <div class="m-item"><i class="fas fa-cash-register"></i> Caisse POS</div>
                    <div class="m-item"><i class="fas fa-boxes-stacked"></i> Produits</div>
                    <div class="m-item"><i class="fas fa-users"></i> Clients</div>
                    <div class="m-item"><i class="fas fa-file-invoice-dollar"></i> Factures</div>
                    <div class="m-item"><i class="fas fa-hand-holding-dollar"></i> Dettes</div>
                    <div class="m-item"><i class="fas fa-money-bill-wave"></i> Paiements</div>
                </div>
                <div class="mockup-main">
                    <div class="mockup-kpi">
                        <div class="m-kpi"><div class="val green">847 500 <?= $sym ?></div><div class="lbl">CA du jour</div></div>
                        <div class="m-kpi"><div class="val blue">2 340</div><div class="lbl">Produits en stock</div></div>
                        <div class="m-kpi"><div class="val orange">12</div><div class="lbl">Alertes stock</div></div>
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
        <div class="section-label"><i class="fas fa-bolt"></i> Fonctionnalites</div>
        <div class="section-title">Tout pour gerer votre commerce</div>
        <div class="section-desc">Une solution complete, conçue pour les commerces et PME de Mauritanie et d'Afrique francophone. Paiements mobiles, bilingue, TVA locale.</div>

        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon i1"><i class="fas fa-cash-register"></i></div>
                <h3>Caisse POS rapide</h3>
                <p>Interface de vente intuitive avec scan code-barres, recherche produit instantanee et paiement en un clic.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i2"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Gestion de stock</h3>
                <p>Suivi en temps reel, alertes stock bas, entrees/sorties automatiques a chaque vente.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i3"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3>Devis & Factures</h3>
                <p>Creez des devis, convertissez-les en factures, generez des PDF professionnels en quelques clics.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i4"><i class="fas fa-mobile-screen"></i></div>
                <h3>Paiements mobiles</h3>
                <p>Bankily, Masrivi, Sedad : acceptez tous les moyens de paiement mauritaniens.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i5"><i class="fas fa-hand-holding-dollar"></i></div>
                <h3>Suivi des credits</h3>
                <p>Gerez dettes clients et fournisseurs. Sachez qui vous doit combien, a tout moment.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i6"><i class="fas fa-language"></i></div>
                <h3>Bilingue FR / عربي</h3>
                <p>Interface complete en francais et arabe avec support RTL. Adaptee au marche local.</p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="how-inner">
        <div class="section-label"><i class="fas fa-wand-magic-sparkles"></i> Demarrage</div>
        <div class="section-title" style="margin-bottom:0;">Pret en 30 secondes</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Creez votre espace</h3>
                <p>Choisissez un nom pour votre entreprise. C'est gratuit, sans carte bancaire.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Ajoutez vos produits</h3>
                <p>Importez votre catalogue, definissez vos prix et stocks initiaux.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Commencez a vendre</h3>
                <p>Utilisez la caisse POS, facturez vos clients, suivez vos finances en temps reel.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pad pricing-center" id="pricing">
    <div class="container">
        <div class="section-label"><i class="fas fa-tags"></i> Tarifs</div>
        <div class="section-title">Un plan pour chaque entreprise</div>
        <p class="section-desc center">Commencez avec <strong>7 jours gratuits</strong> sur n'importe quel plan. Annulez avant la fin de l'essai et rien ne sera debite.</p>

        <div class="pricing-grid" style="grid-template-columns:repeat(2,1fr);max-width:760px;">
            <div class="price-card popular">
                <div class="trial-badge"><i class="fas fa-gift" style="font-size:10px;"></i> 7 jours d'essai</div>
                <div class="price-name">Starter</div>
                <div class="price-desc">Pour les PME en croissance</div>
                <div class="price-amount"><?= $priceStarter ?><span>/ mois</span></div>
                <div class="price-note">Facture mensuellement</div>
                <ul class="price-list">
                    <li><i class="fas fa-check"></i> 5 utilisateurs</li>
                    <li><i class="fas fa-check"></i> 500 produits</li>
                    <li><i class="fas fa-check"></i> Caisse POS + factures illimitees</li>
                    <li><i class="fas fa-check"></i> Support prioritaire</li>
                    <li><i class="fas fa-check"></i> Annulable a tout moment durant l'essai</li>
                </ul>
                <button class="btn-price filled" onclick="openRegister('starter')">Demarrer mes 7 jours</button>
            </div>
            <div class="price-card">
                <div class="trial-badge"><i class="fas fa-gift" style="font-size:10px;"></i> 7 jours d'essai</div>
                <div class="price-name">Pro</div>
                <div class="price-desc">Pour les grandes equipes</div>
                <div class="price-amount"><?= $pricePro ?><span>/ mois</span></div>
                <div class="price-note">Facture mensuellement</div>
                <ul class="price-list">
                    <li><i class="fas fa-check"></i> 15 utilisateurs</li>
                    <li><i class="fas fa-check"></i> 5 000 produits</li>
                    <li><i class="fas fa-check"></i> Tout le plan Starter</li>
                    <li><i class="fas fa-check"></i> Support 24/7</li>
                    <li><i class="fas fa-check"></i> Rapports avances</li>
                </ul>
                <button class="btn-price outline" onclick="openRegister('pro')">Demarrer mes 7 jours</button>
            </div>
        </div>

        <p class="pricing-note">
            <i class="fas fa-circle-info"></i>
            Devise : <strong><?= $cur ?></strong> (<?= $sym ?>) — <a href="?currency=<?= $cur === 'MRU' ? 'USD' : 'MRU' ?>#pricing" style="color:var(--primary);">afficher en <?= $cur === 'MRU' ? 'USD' : 'MRU' ?></a>
        </p>
    </div>
</section>

<!-- TRUST / SECURITY -->
<section class="trust-section">
    <div class="trust-inner">
        <div class="section-label"><i class="fas fa-shield-halved"></i> Securite</div>
        <div class="section-title">Vos donnees sont protegees</div>
        <div class="section-desc">Chiffrement, sauvegardes et isolation des donnees — votre entreprise merite une infrastructure solide.</div>
        <div class="trust-grid">
            <div class="trust-item">
                <i class="fas fa-lock"></i>
                <h4>HTTPS partout</h4>
                <p>Connexion chiffree TLS 1.3 sur toutes les pages et API.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-database"></i>
                <h4>Base dediee</h4>
                <p>Chaque client a sa propre base de donnees isolee.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-hard-drive"></i>
                <h4>Sauvegardes</h4>
                <p>Sauvegarde quotidienne automatique de vos donnees.</p>
            </div>
            <div class="trust-item">
                <i class="fas fa-user-shield"></i>
                <h4>Mots de passe</h4>
                <p>Hachage bcrypt, aucun mot de passe en clair.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="pad" style="background:var(--bg);">
    <div class="container">
        <div class="section-label"><i class="fas fa-quote-right"></i> Temoignages</div>
        <div class="section-title">Ils ont simplifie leur gestion</div>
        <div class="testimonials-grid">
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <div class="quote">"Avant je notais les ventes sur un cahier. Maintenant je vois mon CA en temps reel et je sais exactement ce qui se vend."</div>
                <div class="author">
                    <div class="avatar">AM</div>
                    <div class="info">
                        <strong>Ahmed Mohamed</strong>
                        <span>Boutique Salam, Nouakchott</span>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <div class="quote">"Le plus utile : la gestion des dettes clients. Je sais qui doit me payer, sans me melanger."</div>
                <div class="author">
                    <div class="avatar">FB</div>
                    <div class="info">
                        <strong>Fatimetou Brahim</strong>
                        <span>Epicerie El-Houda</span>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="stars">★★★★★</div>
                <div class="quote">"Installation en 30 secondes, interface claire. Mes caissiers ont appris en 15 minutes."</div>
                <div class="author">
                    <div class="avatar">MK</div>
                    <div class="info">
                        <strong>Moustapha Kamara</strong>
                        <span>Supermarche El-Medina</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="pad" id="faq" style="background:#fff;">
    <div class="container" style="text-align:center;">
        <div class="section-label"><i class="fas fa-circle-question"></i> Questions</div>
        <div class="section-title">Tout ce que vous voulez savoir</div>

        <div class="faq-wrap" style="text-align:left;">
            <details class="faq-item">
                <summary>Comment fonctionne l'essai de 7 jours ?</summary>
                <div class="faq-body">Pour les paiements par carte (USD), nous enregistrons votre moyen de paiement mais ne prelevons rien pendant 7 jours. Vous pouvez annuler a tout moment durant ces 7 jours et rien ne sera debite. Pour les paiements locaux (MRU), vous accedez directement a votre espace pour 7 jours, puis nous contactez pour activer l'abonnement.</div>
            </details>
            <details class="faq-item">
                <summary>Que se passe-t-il apres les 7 jours d'essai ?</summary>
                <div class="faq-body">Si vous n'avez pas annule, votre abonnement mensuel est automatiquement active et la carte est prelevee. Sinon, votre espace est suspendu mais vos donnees sont conservees et vous pouvez reactiver quand vous voulez.</div>
            </details>
            <details class="faq-item">
                <summary>Puis-je annuler a tout moment ?</summary>
                <div class="faq-body">Oui, sans engagement. Vous pouvez annuler en un clic depuis votre espace (Parametres > Abonnement). Si vous annulez pendant l'essai, rien n'est debite. Apres, vous continuez a avoir acces jusqu'a la fin de la periode deja payee.</div>
            </details>
            <details class="faq-item">
                <summary>Mes donnees sont-elles en securite ?</summary>
                <div class="faq-body">Absolument. HTTPS chiffre, chaque client a sa propre base de donnees isolee, sauvegardes quotidiennes automatiques. Vos donnees vous appartiennent et sont exportables en tout temps.</div>
            </details>
            <details class="faq-item">
                <summary>Puis-je utiliser GestionPro depuis un telephone ?</summary>
                <div class="faq-body">Oui, l'interface est 100% responsive et fonctionne parfaitement sur telephone, tablette et ordinateur.</div>
            </details>
            <details class="faq-item">
                <summary>Comment puis-je payer mon abonnement ?</summary>
                <div class="faq-body">Nous acceptons Bankily, Masrivi, Sedad et le virement bancaire. Contactez-nous et nous vous guidons — l'activation est immediate des reception du paiement.</div>
            </details>
            <details class="faq-item">
                <summary>Y a-t-il une version en arabe ?</summary>
                <div class="faq-body">Oui, l'interface complete est disponible en francais et en arabe avec support RTL natif. Changement de langue en un clic depuis votre espace.</div>
            </details>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="cta-banner">
    <div class="container">
        <h2>Pret a simplifier votre gestion ?</h2>
        <p>Creez votre espace en 30 secondes. 7 jours gratuits, annulable a tout moment.</p>
        <button class="btn-cta btn-primary-cta" onclick="openRegister()"><i class="fas fa-rocket"></i> Creer mon espace maintenant</button>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-brand">GestionPro</div>
    <p>&copy; <?= date('Y') ?> GestionPro — Solution de gestion commerciale</p>
    <div class="footer-links">
        <a href="#features">Fonctionnalites</a>
        <a href="#pricing">Tarifs</a>
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
                    <div class="trial-modal-badge"><i class="fas fa-gift" style="font-size:11px;"></i> 7 jours gratuits</div>
                    <h2>Creer votre espace</h2>
                    <p>Pret en 30 secondes, annulable a tout moment</p>
                </div>
                <button class="modal-close" onclick="closeRegister()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="register-error" id="regError"></div>

                <div class="fg">
                    <label>Nom de votre entreprise *</label>
                    <input type="text" id="regCompany" placeholder="Ex: Boutique Salam" required>
                </div>
                <div class="fg">
                    <label>Adresse de votre espace *</label>
                    <div class="slug-preview">
                        <span class="slug-prefix">gestionpro.it.com/</span>
                        <input type="text" id="regSlug" placeholder="boutique-salam" pattern="[a-z0-9_-]+" required>
                    </div>
                </div>
                <div class="fg-row">
                    <div class="fg">
                        <label>Votre nom *</label>
                        <input type="text" id="regName" placeholder="Ahmed Mohamed">
                    </div>
                    <div class="fg">
                        <label>Telephone</label>
                        <input type="text" id="regPhone" placeholder="+222 XX XX XX XX">
                    </div>
                </div>
                <div class="fg">
                    <label>Email *</label>
                    <input type="email" id="regEmail" placeholder="ahmed@exemple.com">
                </div>
                <div class="fg">
                    <label>Mot de passe *</label>
                    <input type="password" id="regPassword" placeholder="Minimum 6 caracteres">
                </div>
                <input type="hidden" id="regPlan" value="starter">
                <button class="btn-register" id="regSubmit" onclick="submitRegister()">
                    <i class="fas fa-rocket"></i> Demarrer mon essai de 7 jours
                </button>
                <p style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
                    7 jours gratuits. Annulez avant J+7 et rien ne sera debite.
                </p>
            </div>
        </div>

        <div id="registerSuccess" style="display:none;">
            <div class="register-success">
                <div class="check"><i class="fas fa-check"></i></div>
                <h3>Votre espace est pret !</h3>
                <p>Vos 7 jours d'essai gratuit commencent maintenant.<br>Connectez-vous pour decouvrir GestionPro.</p>
                <div class="creds">
                    <div><strong>URL :</strong> <span id="successUrl"></span></div>
                    <div><strong>Identifiant :</strong> <span id="successUser">admin</span></div>
                    <div><strong>Mot de passe :</strong> le mot de passe que vous avez choisi</div>
                </div>
                <a href="#" class="btn-go" id="successLink"><i class="fas fa-sign-in-alt"></i> Se connecter</a>
            </div>
        </div>
    </div>
</div>

<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior:'smooth', block:'start' }); }
    });
});

// Auto-generate slug from company name
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
    document.getElementById('registerSuccess').style.display = 'none';
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creation en cours...';

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
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> Demarrer mon essai de 7 jours';

            if (data.success) {
                // USD path: redirect straight into Polar checkout (card + 7-day trial).
                if (data.mode === 'polar_checkout' && data.checkout_url) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirection vers le paiement...';
                    window.location.href = data.checkout_url;
                    return;
                }
                // MRU path (or Polar fallback): show success modal with login URL.
                const fullUrl = window.location.origin + data.url;
                document.getElementById('successUrl').textContent = fullUrl;
                document.getElementById('successUser').textContent = data.username;
                document.getElementById('successLink').href = data.url;
                document.getElementById('registerForm').style.display = 'none';
                document.getElementById('registerSuccess').style.display = '';
            } else {
                errEl.textContent = data.error;
                errEl.style.display = 'block';
            }
        })
        .catch(e => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket"></i> Demarrer mon essai de 7 jours';
            errEl.textContent = 'Erreur de connexion. Reessayez.';
            errEl.style.display = 'block';
        });
}

document.querySelectorAll('#registerModal input').forEach(input => {
    input.addEventListener('keydown', e => { if (e.key === 'Enter') submitRegister(); });
});
</script>

</body>
</html>
