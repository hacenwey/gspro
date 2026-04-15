<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GestionPro - Gerez votre commerce en ligne</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        :root {
            --primary: #4F46E5; --primary-light: #6366F1; --primary-dark: #3730A3;
            --primary-50: #EEF2FF; --primary-100: #E0E7FF;
            --text: #0F172A; --text-secondary: #475569; --text-muted: #94A3B8;
            --bg: #F8FAFC; --surface: #FFFFFF; --border: #E2E8F0;
            --success: #10B981; --danger: #EF4444; --warning: #F59E0B;
        }
        body { font-family:'Inter',-apple-system,sans-serif; color:var(--text); background:var(--bg); -webkit-font-smoothing:antialiased; }
        a { text-decoration:none; color:inherit; }

        /* ===== NAVBAR ===== */
        .navbar {
            position:fixed; top:0; left:0; right:0; z-index:100;
            background:rgba(255,255,255,0.85); backdrop-filter:blur(20px) saturate(180%);
            border-bottom:1px solid rgba(226,232,240,0.6);
            padding:0 24px; height:64px; display:flex; align-items:center; justify-content:space-between;
            transition: all 0.3s;
        }
        .navbar.scrolled { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .nav-brand { display:flex; align-items:center; gap:10px; }
        .nav-brand .logo {
            width:36px; height:36px; background:linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:14px; color:#fff;
        }
        .nav-brand span { font-size:18px; font-weight:800; letter-spacing:-0.5px; }
        .nav-links { display:flex; align-items:center; gap:8px; }
        .nav-links a {
            padding:8px 16px; border-radius:8px; font-size:14px; font-weight:500;
            color:var(--text-secondary); transition:all 0.2s;
        }
        .nav-links a:hover { color:var(--text); background:var(--primary-50); }
        .nav-links .btn-nav {
            background:var(--primary); color:#fff; font-weight:600;
            padding:8px 20px; border-radius:8px;
        }
        .nav-links .btn-nav:hover { background:var(--primary-dark); color:#fff; }

        /* ===== HERO ===== */
        .hero {
            padding:140px 24px 100px; text-align:center; position:relative; overflow:hidden;
            background:linear-gradient(180deg, #fff 0%, var(--primary-50) 100%);
        }
        .hero::before {
            content:''; position:absolute; top:-200px; left:50%; transform:translateX(-50%);
            width:800px; height:800px; border-radius:50%;
            background:radial-gradient(circle, rgba(79,70,229,0.07) 0%, transparent 70%);
            pointer-events:none;
        }
        .hero-badge {
            display:inline-flex; align-items:center; gap:8px;
            padding:6px 16px 6px 8px; background:var(--primary-50); border:1px solid var(--primary-100);
            border-radius:100px; font-size:13px; font-weight:600; color:var(--primary);
            margin-bottom:24px;
        }
        .hero-badge i { font-size:16px; }
        .hero h1 {
            font-size:clamp(36px, 5vw, 64px); font-weight:900; line-height:1.1;
            letter-spacing:-2px; max-width:800px; margin:0 auto 20px;
            background:linear-gradient(135deg, var(--text) 0%, var(--primary-dark) 100%);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
        }
        .hero p {
            font-size:clamp(16px, 2vw, 20px); color:var(--text-secondary); max-width:560px;
            margin:0 auto 40px; line-height:1.7; font-weight:400;
        }
        .hero-cta { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; margin-bottom:60px; }
        .btn-cta {
            display:inline-flex; align-items:center; gap:10px; padding:16px 32px;
            border-radius:14px; font-weight:700; font-size:16px; transition:all 0.25s;
            cursor:pointer; border:none;
        }
        .btn-primary-cta {
            background:var(--primary); color:#fff;
            box-shadow:0 4px 14px rgba(79,70,229,0.35), 0 0 0 0 rgba(79,70,229,0.2);
        }
        .btn-primary-cta:hover { background:var(--primary-dark); transform:translateY(-2px); box-shadow:0 8px 24px rgba(79,70,229,0.4); }
        .btn-secondary-cta {
            background:var(--surface); color:var(--text); border:1.5px solid var(--border);
        }
        .btn-secondary-cta:hover { border-color:var(--primary); color:var(--primary); transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,0.06); }

        /* Hero mockup */
        .hero-mockup {
            max-width:1000px; margin:0 auto; position:relative;
            background:var(--surface); border-radius:16px; border:1px solid var(--border);
            box-shadow:0 24px 80px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.02);
            overflow:hidden;
        }
        .mockup-toolbar {
            display:flex; align-items:center; gap:8px; padding:12px 16px;
            border-bottom:1px solid var(--border); background:#FAFBFC;
        }
        .mockup-dot { width:10px; height:10px; border-radius:50%; }
        .mockup-dot.r { background:#EF4444; } .mockup-dot.y { background:#F59E0B; } .mockup-dot.g { background:#10B981; }
        .mockup-body { display:flex; min-height:320px; }
        .mockup-sidebar { width:200px; border-right:1px solid var(--border); padding:16px 12px; background:#FAFBFC; }
        .mockup-sidebar .m-item {
            padding:8px 12px; border-radius:8px; font-size:12px; color:var(--text-muted);
            display:flex; align-items:center; gap:8px; margin-bottom:2px;
        }
        .mockup-sidebar .m-item.active { background:var(--primary-50); color:var(--primary); font-weight:600; }
        .mockup-sidebar .m-item i { width:16px; text-align:center; font-size:11px; }
        .mockup-main { flex:1; padding:20px; }
        .mockup-kpi { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
        .m-kpi {
            padding:14px; border-radius:10px; border:1px solid var(--border); background:var(--surface);
        }
        .m-kpi .val { font-size:20px; font-weight:800; font-family:'Inter',monospace; }
        .m-kpi .lbl { font-size:10px; color:var(--text-muted); margin-top:2px; }
        .m-kpi .val.green { color:var(--success); } .m-kpi .val.blue { color:var(--primary); } .m-kpi .val.orange { color:var(--warning); }
        .mockup-chart {
            height:120px; background:linear-gradient(180deg, rgba(79,70,229,0.06) 0%, transparent 100%);
            border-radius:10px; border:1px solid var(--border); position:relative; overflow:hidden;
        }
        .mockup-chart svg { position:absolute; bottom:0; left:0; width:100%; }

        /* ===== FEATURES ===== */
        .features { padding:100px 24px; max-width:1100px; margin:0 auto; }
        .section-label {
            display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:700;
            color:var(--primary); text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;
        }
        .section-title { font-size:clamp(28px, 3.5vw, 40px); font-weight:800; letter-spacing:-1px; margin-bottom:16px; }
        .section-desc { font-size:17px; color:var(--text-secondary); max-width:550px; line-height:1.7; margin-bottom:48px; }

        .features-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; }
        .feat-card {
            padding:28px; border-radius:16px; border:1px solid var(--border); background:var(--surface);
            transition:all 0.25s; position:relative; overflow:hidden;
        }
        .feat-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background:linear-gradient(90deg, var(--primary), var(--primary-light));
            transform:scaleX(0); transform-origin:left; transition:transform 0.3s;
        }
        .feat-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.06); }
        .feat-card:hover::before { transform:scaleX(1); }
        .feat-icon {
            width:48px; height:48px; border-radius:12px; display:flex; align-items:center;
            justify-content:center; font-size:20px; margin-bottom:16px;
        }
        .feat-icon.i1 { background:rgba(79,70,229,0.1); color:#4F46E5; }
        .feat-icon.i2 { background:rgba(16,185,129,0.1); color:#10B981; }
        .feat-icon.i3 { background:rgba(245,158,11,0.1); color:#F59E0B; }
        .feat-icon.i4 { background:rgba(14,165,233,0.1); color:#0EA5E9; }
        .feat-icon.i5 { background:rgba(239,68,68,0.1); color:#EF4444; }
        .feat-icon.i6 { background:rgba(168,85,247,0.1); color:#A855F7; }
        .feat-card h3 { font-size:16px; font-weight:700; margin-bottom:8px; }
        .feat-card p { font-size:14px; color:var(--text-secondary); line-height:1.6; }

        /* ===== HOW IT WORKS ===== */
        .how-section { padding:80px 24px; background:var(--surface); border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
        .how-inner { max-width:900px; margin:0 auto; text-align:center; }
        .steps { display:grid; grid-template-columns:repeat(3,1fr); gap:32px; margin-top:48px; }
        .step { position:relative; }
        .step-num {
            width:56px; height:56px; border-radius:16px; margin:0 auto 16px;
            display:flex; align-items:center; justify-content:center;
            font-size:24px; font-weight:900; color:var(--primary);
            background:var(--primary-50); border:2px solid var(--primary-100);
        }
        .step h3 { font-size:16px; font-weight:700; margin-bottom:8px; }
        .step p { font-size:14px; color:var(--text-secondary); line-height:1.6; }
        .step-arrow {
            position:absolute; top:28px; right:-20px; color:var(--border); font-size:20px;
        }

        /* ===== PRICING ===== */
        .pricing { padding:100px 24px; max-width:1100px; margin:0 auto; text-align:center; }
        .pricing-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:48px; }
        .price-card {
            padding:32px 28px; border-radius:16px; border:1px solid var(--border);
            background:var(--surface); text-align:left; position:relative; transition:all 0.25s;
        }
        .price-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.06); }
        .price-card.popular {
            border-color:var(--primary); box-shadow:0 8px 32px rgba(79,70,229,0.15);
        }
        .price-card.popular::before {
            content:'Populaire'; position:absolute; top:-12px; left:50%; transform:translateX(-50%);
            padding:4px 14px; background:var(--primary); color:#fff; border-radius:100px;
            font-size:12px; font-weight:700;
        }
        .price-name { font-size:18px; font-weight:700; margin-bottom:4px; }
        .price-desc { font-size:13px; color:var(--text-muted); margin-bottom:20px; }
        .price-amount { font-size:40px; font-weight:900; letter-spacing:-1px; }
        .price-amount span { font-size:15px; font-weight:500; color:var(--text-muted); }
        .price-list { list-style:none; margin:24px 0; }
        .price-list li {
            padding:8px 0; font-size:14px; color:var(--text-secondary);
            display:flex; align-items:center; gap:10px;
        }
        .price-list li i { color:var(--success); font-size:13px; width:16px; }
        .btn-price {
            display:block; width:100%; padding:12px; border:none; border-radius:10px;
            font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s;
            text-align:center;
        }
        .btn-price.outline { background:transparent; border:1.5px solid var(--border); color:var(--text); }
        .btn-price.outline:hover { border-color:var(--primary); color:var(--primary); }
        .btn-price.filled { background:var(--primary); color:#fff; }
        .btn-price.filled:hover { background:var(--primary-dark); }

        /* ===== REGISTER MODAL ===== */
        .modal-overlay {
            display:none; position:fixed; inset:0; z-index:200;
            background:rgba(15,23,42,0.6); backdrop-filter:blur(6px);
            align-items:center; justify-content:center; padding:20px;
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:var(--surface); border-radius:20px; width:100%; max-width:480px;
            box-shadow:0 32px 80px rgba(0,0,0,0.2); animation:modalIn 0.3s ease;
            max-height:90vh; overflow-y:auto;
        }
        @keyframes modalIn { from { opacity:0; transform:translateY(20px) scale(0.97); } to { opacity:1; transform:none; } }
        .modal-header {
            padding:24px 28px 0; display:flex; justify-content:space-between; align-items:flex-start;
        }
        .modal-header h2 { font-size:22px; font-weight:800; }
        .modal-header p { font-size:14px; color:var(--text-muted); margin-top:4px; }
        .modal-close { background:none; border:none; font-size:22px; color:var(--text-muted); cursor:pointer; padding:4px; }
        .modal-close:hover { color:var(--text); }
        .modal-body { padding:24px 28px 28px; }
        .fg { margin-bottom:16px; }
        .fg label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text-secondary); }
        .fg input {
            width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:10px;
            font-size:14px; font-family:inherit; transition:border 0.2s; background:var(--bg);
        }
        .fg input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(79,70,229,0.1); }
        .slug-preview {
            display:flex; align-items:center; gap:0; margin-top:6px;
        }
        .slug-prefix {
            padding:8px 12px; background:var(--bg); border:1.5px solid var(--border);
            border-right:0; border-radius:10px 0 0 10px; font-size:12px; color:var(--text-muted);
            white-space:nowrap;
        }
        .slug-preview input { border-radius:0 10px 10px 0; }
        .fg-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .btn-register {
            width:100%; padding:14px; border:none; border-radius:12px; background:var(--primary);
            color:#fff; font-size:15px; font-weight:700; cursor:pointer; transition:all 0.2s;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-register:hover { background:var(--primary-dark); }
        .btn-register:disabled { opacity:0.6; cursor:not-allowed; }
        .register-error {
            background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2);
            color:var(--danger); padding:10px 14px; border-radius:8px; font-size:13px;
            margin-bottom:16px; display:none;
        }
        .register-success {
            text-align:center; padding:40px 28px;
        }
        .register-success .check {
            width:64px; height:64px; border-radius:50%; background:rgba(16,185,129,0.1);
            display:inline-flex; align-items:center; justify-content:center;
            font-size:28px; color:var(--success); margin-bottom:16px;
        }
        .register-success h3 { font-size:20px; font-weight:700; margin-bottom:8px; }
        .register-success p { font-size:14px; color:var(--text-secondary); margin-bottom:20px; line-height:1.6; }
        .register-success .creds {
            background:var(--bg); border:1px solid var(--border); border-radius:10px;
            padding:14px 18px; text-align:left; margin-bottom:20px; font-size:14px;
        }
        .register-success .creds strong { color:var(--primary); }
        .btn-go {
            display:inline-flex; align-items:center; gap:8px; padding:14px 32px;
            background:var(--primary); color:#fff; border:none; border-radius:12px;
            font-size:15px; font-weight:700; cursor:pointer; transition:all 0.2s; text-decoration:none;
        }
        .btn-go:hover { background:var(--primary-dark); }

        /* ===== CLIENTS SECTION ===== */
        .clients-section { padding:60px 24px; max-width:900px; margin:0 auto; }
        .clients-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:12px; margin-top:24px; }
        .client-card {
            display:flex; align-items:center; gap:12px; padding:16px 18px;
            border:1px solid var(--border); border-radius:12px; background:var(--surface);
            transition:all 0.2s;
        }
        .client-card:hover { border-color:var(--primary); transform:translateY(-2px); box-shadow:0 4px 16px rgba(79,70,229,0.08); }
        .client-avatar {
            width:40px; height:40px; border-radius:10px;
            background:linear-gradient(135deg, var(--primary), var(--primary-light));
            display:flex; align-items:center; justify-content:center;
            font-weight:800; font-size:15px; color:#fff; flex-shrink:0;
        }
        .client-card h4 { font-size:14px; font-weight:600; }
        .client-card p { font-size:12px; color:var(--text-muted); }

        /* ===== FOOTER ===== */
        .footer { padding:48px 24px; text-align:center; border-top:1px solid var(--border); }
        .footer-brand { font-size:18px; font-weight:800; margin-bottom:8px; }
        .footer p { font-size:13px; color:var(--text-muted); }
        .footer-links { margin-top:12px; display:flex; gap:20px; justify-content:center; }
        .footer-links a { font-size:13px; color:var(--text-secondary); transition:color 0.2s; }
        .footer-links a:hover { color:var(--primary); }

        /* ===== RESPONSIVE ===== */
        @media (max-width:768px) {
            .features-grid, .pricing-grid, .steps { grid-template-columns:1fr; }
            .mockup-sidebar { display:none; }
            .mockup-kpi { grid-template-columns:1fr; }
            .hero { padding:120px 20px 60px; }
            .step-arrow { display:none; }
            .nav-links .hide-mobile { display:none; }
            .fg-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="nav-brand">
        <div class="logo">GP</div>
        <span>GestionPro</span>
    </div>
    <div class="nav-links">
        <a href="#features" class="hide-mobile">Fonctionnalites</a>
        <a href="#pricing" class="hide-mobile">Tarifs</a>
        <a href="#clients-section" class="hide-mobile">Clients</a>
        <a href="<?= APP_BASE ?>/admin/login" class="hide-mobile" style="font-size:13px;"><i class="fas fa-shield-halved"></i></a>
        <a href="javascript:void(0)" class="btn-nav" onclick="openRegister()"><i class="fas fa-rocket"></i> Commencer</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge"><i class="fas fa-sparkles"></i> Nouveau : Paiements Bankily, Masrivi & Sedad</div>
    <h1>Gerez votre commerce.<br>Simplifiez votre vie.</h1>
    <p>Stocks, factures, clients, caisse POS — tout ce dont votre entreprise a besoin, pret en 30 secondes. Gratuit pour commencer.</p>
    <div class="hero-cta">
        <button class="btn-cta btn-primary-cta" onclick="openRegister()"><i class="fas fa-rocket"></i> Creer mon espace gratuit</button>
        <a href="#features" class="btn-cta btn-secondary-cta"><i class="fas fa-play-circle"></i> Decouvrir</a>
    </div>

    <!-- Mockup -->
    <div class="hero-mockup">
        <div class="mockup-toolbar">
            <div class="mockup-dot r"></div><div class="mockup-dot y"></div><div class="mockup-dot g"></div>
            <div style="flex:1;text-align:center;font-size:12px;color:var(--text-muted);">gestionpro.com/mon-entreprise/dashboard</div>
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
                    <div class="m-kpi"><div class="val green">847 500 UM</div><div class="lbl">CA du jour</div></div>
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
</section>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-label"><i class="fas fa-bolt"></i> Fonctionnalites</div>
    <div class="section-title">Tout pour gerer votre commerce</div>
    <div class="section-desc">Une solution complete adaptee au marche mauritanien. Paiements mobiles, bilingue francais/arabe, TVA locale.</div>

    <div class="features-grid">
        <div class="feat-card">
            <div class="feat-icon i1"><i class="fas fa-cash-register"></i></div>
            <h3>Caisse POS rapide</h3>
            <p>Interface de vente intuitive avec scan code-barres, recherche produit et paiement en un clic.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon i2"><i class="fas fa-boxes-stacked"></i></div>
            <h3>Gestion de stock</h3>
            <p>Suivi en temps reel, alertes stock bas, mouvements d'entree/sortie automatiques.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon i3"><i class="fas fa-file-invoice-dollar"></i></div>
            <h3>Devis & Factures</h3>
            <p>Creez des devis, convertissez-les en factures, generez des PDF professionnels.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon i4"><i class="fas fa-mobile-screen"></i></div>
            <h3>Paiements mobiles</h3>
            <p>Bankily, Masrivi, Sedad — acceptez tous les moyens de paiement mauritaniens.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon i5"><i class="fas fa-hand-holding-dollar"></i></div>
            <h3>Suivi des credits</h3>
            <p>Gerez les dettes clients et fournisseurs. Sachez qui vous doit combien, en temps reel.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon i6"><i class="fas fa-language"></i></div>
            <h3>Bilingue FR / عربي</h3>
            <p>Interface complete en francais et arabe avec support RTL. Adaptee a la Mauritanie.</p>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="how-inner">
        <div class="section-label"><i class="fas fa-wand-magic-sparkles"></i> Comment ca marche</div>
        <div class="section-title" style="margin-bottom:0;">Pret en 30 secondes</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Creez votre compte</h3>
                <p>Choisissez un nom pour votre espace. C'est gratuit, sans carte bancaire.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>Ajoutez vos produits</h3>
                <p>Importez votre catalogue, definissez vos prix et vos stocks initiaux.</p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Commencez a vendre</h3>
                <p>Utilisez la caisse POS, facturez vos clients, suivez vos finances.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing" id="pricing">
    <div class="section-label"><i class="fas fa-tags"></i> Tarifs</div>
    <div class="section-title">Un plan pour chaque entreprise</div>
    <p style="color:var(--text-secondary);max-width:500px;margin:0 auto 0;font-size:16px;">Commencez gratuitement. Evoluez quand vous grandissez.</p>

    <div class="pricing-grid">
        <div class="price-card">
            <div class="price-name">Gratuit</div>
            <div class="price-desc">Pour demarrer</div>
            <div class="price-amount">0 <span>UM / mois</span></div>
            <ul class="price-list">
                <li><i class="fas fa-check"></i> 2 utilisateurs</li>
                <li><i class="fas fa-check"></i> 100 produits</li>
                <li><i class="fas fa-check"></i> Caisse POS</li>
                <li><i class="fas fa-check"></i> Factures illimitees</li>
            </ul>
            <button class="btn-price outline" onclick="openRegister('free')">Commencer gratuitement</button>
        </div>
        <div class="price-card popular">
            <div class="price-name">Starter</div>
            <div class="price-desc">Pour les PME</div>
            <div class="price-amount">5 000 <span>UM / mois</span></div>
            <ul class="price-list">
                <li><i class="fas fa-check"></i> 5 utilisateurs</li>
                <li><i class="fas fa-check"></i> 500 produits</li>
                <li><i class="fas fa-check"></i> Tout le plan Gratuit</li>
                <li><i class="fas fa-check"></i> Support prioritaire</li>
            </ul>
            <button class="btn-price filled" onclick="openRegister('starter')">Choisir Starter</button>
        </div>
        <div class="price-card">
            <div class="price-name">Pro</div>
            <div class="price-desc">Pour les grandes entreprises</div>
            <div class="price-amount">15 000 <span>UM / mois</span></div>
            <ul class="price-list">
                <li><i class="fas fa-check"></i> 15 utilisateurs</li>
                <li><i class="fas fa-check"></i> 5 000 produits</li>
                <li><i class="fas fa-check"></i> Tout le plan Starter</li>
                <li><i class="fas fa-check"></i> Support 24/7</li>
            </ul>
            <button class="btn-price outline" onclick="openRegister('pro')">Choisir Pro</button>
        </div>
    </div>
</section>

<!-- EXISTING CLIENTS -->
<?php
try {
    $masterDb = Tenant::getMasterDB();
    $tenants = $masterDb->query("SELECT slug, company_name FROM tenants WHERE is_active = 1 ORDER BY company_name")->fetchAll();
} catch (Exception $e) {
    $tenants = [];
}
?>
<?php if (!empty($tenants)): ?>
<section class="clients-section" id="clients-section">
    <div style="text-align:center;">
        <div class="section-label"><i class="fas fa-building"></i> Nos clients</div>
        <div class="section-title" style="font-size:28px;">Ils nous font deja confiance</div>
    </div>
    <div class="clients-grid">
        <?php foreach ($tenants as $t): ?>
        <a href="<?= APP_BASE . '/' . $t['slug'] . '/login' ?>" class="client-card">
            <div class="client-avatar"><?= strtoupper(mb_substr($t['company_name'], 0, 2)) ?></div>
            <div>
                <h4><?= e($t['company_name']) ?></h4>
                <p><i class="fas fa-arrow-right" style="font-size:10px;"></i> <?= e($t['slug']) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-brand">GestionPro</div>
    <p>&copy; <?= date('Y') ?> GestionPro — Solution de gestion commerciale pour la Mauritanie</p>
    <div class="footer-links">
        <a href="#features">Fonctionnalites</a>
        <a href="#pricing">Tarifs</a>
        <a href="<?= APP_BASE ?>/admin/login">Administration</a>
    </div>
</footer>

<!-- REGISTER MODAL -->
<div class="modal-overlay" id="registerModal">
    <div class="modal">
        <!-- Form -->
        <div id="registerForm">
            <div class="modal-header">
                <div>
                    <h2>Creer votre espace</h2>
                    <p>Gratuit, pret en 30 secondes</p>
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
                    <label>Adresse de votre espace (slug) *</label>
                    <div class="slug-preview">
                        <span class="slug-prefix">gestionpro.com/</span>
                        <input type="text" id="regSlug" placeholder="boutique-salam" pattern="[a-z0-9_-]+" required>
                    </div>
                </div>
                <div class="fg-row">
                    <div class="fg">
                        <label>Votre nom complet *</label>
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
                    <i class="fas fa-rocket"></i> Creer mon espace
                </button>
                <p style="text-align:center;margin-top:12px;font-size:12px;color:var(--text-muted);">
                    En creant un compte, vous acceptez nos conditions d'utilisation.
                </p>
            </div>
        </div>

        <!-- Success -->
        <div id="registerSuccess" style="display:none;">
            <div class="register-success">
                <div class="check"><i class="fas fa-check"></i></div>
                <h3>Votre espace est pret !</h3>
                <p>Votre espace de gestion a ete cree avec succes. Connectez-vous pour commencer.</p>
                <div class="creds">
                    <div><strong>URL :</strong> <span id="successUrl"></span></div>
                    <div style="margin-top:6px;"><strong>Identifiant :</strong> <span id="successUser">admin</span></div>
                    <div style="margin-top:6px;"><strong>Mot de passe :</strong> le mot de passe que vous avez choisi</div>
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

// Open/close register modal
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

// Close on overlay click
document.getElementById('registerModal').addEventListener('click', function(e) {
    if (e.target === this) closeRegister();
});

// Submit registration
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
            btn.innerHTML = '<i class="fas fa-rocket"></i> Creer mon espace';

            if (data.success) {
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
            btn.innerHTML = '<i class="fas fa-rocket"></i> Creer mon espace';
            errEl.textContent = 'Erreur de connexion. Reessayez.';
            errEl.style.display = 'block';
        });
}

// Keyboard submit
document.querySelectorAll('#registerModal input').forEach(input => {
    input.addEventListener('keydown', e => { if (e.key === 'Enter') submitRegister(); });
});
</script>

</body>
</html>
