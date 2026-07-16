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

// ===================== LANDING PAGE I18N =====================
// The landing page is served before Lang::init() (see index.php), so it
// handles locale selection on its own. It follows DEFAULT_LANG rather than
// hardcoding a locale, so the app default stays defined in one place;
// the other languages remain available via ?lang=.
$landingLangs = ['ar', 'fr', 'en'];
$landingDefault = in_array(DEFAULT_LANG, $landingLangs, true) ? DEFAULT_LANG : 'fr';
if (isset($_GET['lang']) && in_array($_GET['lang'], $landingLangs, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? $landingDefault;
if (!in_array($lang, $landingLangs, true)) { $lang = $landingDefault; }
$dir     = $lang === 'ar' ? 'rtl' : 'ltr';
$isRtl   = $lang === 'ar';

// Display currency: MRU (Mauritanian ouguiya). Subscription payments are
// processed by Polar in USD; amounts are converted for display only.
$USD_TO_MRU = 40;
$fmtMru = static function ($usd) use ($USD_TO_MRU) {
    return number_format((float)$usd * $USD_TO_MRU, 0, ',', ' ');
};

$TR = [
    'ar' => [
        'cur' => 'أوقية',
        'page_title' => 'GestionPro — برنامج إدارة الأعمال المتكامل',
        'page_desc' => 'أدِر المخزون والفواتير ونقاط البيع والعملاء. أداة واحدة لتجارتك — اطلب عبر واتساب، دون التزام.',
        'nav_features' => 'المميزات', 'nav_faq' => 'الأسئلة الشائعة',
        'nav_admin_title' => 'الإدارة', 'nav_cta' => 'تواصل معنا',
        'hero_h1_1' => 'أدِر تجارتك.', 'hero_h1_2' => 'بسّط يومك.',
        'hero_sub' => 'المنصة المتكاملة للمتاجر والشركات الصغيرة والمتوسطة وأصحاب الخدمات. المخزون، نقاط البيع، الفواتير، العملاء والمالية — أداة واحدة، جاهزة في 30 ثانية.',
        'hero_trust1' => 'يعمل حتى بدون إنترنت', 'hero_trust2' => 'بدون بطاقة بنكية مطلوبة',
        'hero_trust3' => 'متوفر بالعربية والفرنسية والإنجليزية',
        'hero_cta1' => 'أنشئ مساحة عملي', 'hero_cta2' => 'شاهد العرض',
        'stat_businesses' => 'شركات نشطة', 'stat_signup' => 'للتسجيل', 'stat_uptime' => 'التشغيل', 'stat_access' => 'الوصول',
        'm_dashboard' => 'لوحة التحكم', 'm_pos' => 'نقاط البيع', 'm_products' => 'المنتجات', 'm_clients' => 'العملاء',
        'm_invoices' => 'الفواتير', 'm_debts' => 'الديون', 'm_payments' => 'المدفوعات',
        'kpi_revenue' => 'إيرادات اليوم', 'kpi_stock' => 'منتجات في المخزون', 'kpi_alerts' => 'تنبيهات المخزون',
        'feat_label' => 'المميزات', 'feat_title' => 'كل ما تحتاجه لإدارة تجارتك',
        'feat_desc' => 'مجموعة أدوات متكاملة للمتاجر والشركات الصغيرة والمتوسطة في أي مكان. متعدد المستخدمين واللغات، جاهز للاستخدام فوراً.',
        'feat1_t' => 'نقاط بيع سريعة', 'feat1_d' => 'واجهة بيع سهلة مع قارئ الباركود، بحث فوري عن المنتجات ودفع بنقرة واحدة.',
        'feat2_t' => 'إدارة المخزون', 'feat2_d' => 'تتبّع فوري، تنبيهات نفاد المخزون، إدخال وإخراج تلقائي مع كل عملية بيع.',
        'feat3_t' => 'عروض الأسعار والفواتير', 'feat3_d' => 'أنشئ عروض أسعار، حوّلها إلى فواتير، وأصدر ملفات PDF احترافية في ثوانٍ.',
        'feat4_t' => 'متابعة الديون', 'feat4_d' => 'أدِر مستحقات العملاء والموردين. اعرف من يدين لك وبماذا في أي وقت.',
        'feat5_t' => 'لوحات معلومات وتقارير', 'feat5_d' => 'اتجاهات الإيرادات، أفضل المنتجات، وضع الخزينة — رؤى واضحة بنظرة واحدة.',
        // Slot 6 sells offline rather than languages: it is the real differentiator,
        // and the language mention already lives in the hero and the FAQ.
        'feat6_t' => 'يعمل دون اتصال', 'feat6_d' => 'الصندوق يواصل البيع حتى بدون إنترنت. تتم مزامنة المبيعات تلقائياً بمجرد عودة الشبكة.',
        // Problem-first section — each pain maps to a feature above it.
        'prob_label' => 'هل يبدو مألوفاً؟', 'prob_title' => 'الصداع الذي يعرفه كل تاجر',
        'prob_desc' => 'إذا وجدت نفسك في واحدة فقط من هذه النقاط، فإن GestionPro صُنع لك.',
        'prob1_t' => 'البيع بالأجل دون رؤية واضحة',
        'prob1_d' => 'دفتر وقصاصات، وفي آخر الشهر لم تعد تعرف من يدين لك وبكم — ولا منذ متى.',
        'prob2_t' => 'نفاد المخزون يكتشفه الزبون',
        'prob2_d' => 'تعرف أن صنفاً ما نفد عندما يطلبه الزبون. مخزونك في ذاكرتك، لا في جدول.',
        'prob3_t' => 'أرقام غامضة عند الإغلاق',
        'prob3_d' => 'كم بعت فعلاً اليوم؟ ما الذي ينجح؟ هل تربح؟ أنت تخمّن.',
        'prob4_t' => 'الاتصال الذي ينقطع',
        'prob4_d' => 'ينقطع الإنترنت فيتوقف الصندوق. زبائنك ينتظرون بينما تسجّل على ورقة.',
        'how_label' => 'البدء', 'how_title' => 'جاهز في 30 ثانية',
        'step1_t' => 'أنشئ مساحة عملك', 'step1_d' => 'تواصل معنا عبر واتساب: يُنشئ فريقنا مساحة عملك ويرافقك. لا حاجة لبطاقة بنكية.',
        'step2_t' => 'أضف منتجاتك', 'step2_d' => 'استورد كتالوجك، حدّد الأسعار ومستويات المخزون الافتتاحية.',
        'step3_t' => 'ابدأ البيع', 'step3_d' => 'استخدم نقاط البيع، أصدر فواتير للعملاء، وتابع أموالك في الوقت الفعلي.',
        // Bullets shown on the live Polar plan cards when no custom list is set in /admin/polar.
        'trust_label' => 'الأمان', 'trust_title' => 'بياناتك محميّة',
        'trust_desc' => 'تشفير ونسخ احتياطي وعزل للبيانات — نشاطك يستحق بنية تحتية متينة.',
        'trust1_t' => 'HTTPS في كل مكان', 'trust1_d' => 'اتصالات مشفّرة بـ TLS 1.3 على كل صفحة وكل طلب API.',
        'trust2_t' => 'قاعدة بيانات مخصّصة', 'trust2_d' => 'كل عميل يحصل على قاعدة بيانات معزولة خاصة به.',
        'trust3_t' => 'نسخ احتياطي يومي', 'trust3_d' => 'نسخ احتياطي تلقائي يومي لبياناتك.',
        'trust4_t' => 'كلمات مرور آمنة', 'trust4_d' => 'تشفير Bcrypt — لا كلمات مرور نصية أبداً.',
        'faq_label' => 'الأسئلة', 'faq_title' => 'كل ما تريد معرفته',
        'faq1_q' => 'كيف أبدأ مع GestionPro؟',
        'faq1_a' => 'راسلنا عبر واتساب مع اختيار باقتك. ينشئ فريقنا مساحة عملك، ويستورد كتالوجك، ويرافقك في الانطلاق — مساحتك جاهزة في نفس اليوم.',
        'faq2_q' => 'كيف تتم عملية الدفع؟',
        'faq2_a' => 'يتفق معك فريقنا على الباقة وموعد الدفع عبر واتساب، ثم يفعّل اشتراكك. لا تُسجَّل أي بطاقة بنكية على الموقع.',
        'faq3_q' => 'هل يمكنني التوقف في أي وقت؟',
        'faq3_a' => 'نعم، دون أي التزام. لا نسجّل أي بطاقة بنكية مسبقاً: أنت من يقرر مواصلة الاشتراك أو التوقف، ويمكنك التواصل مع فريقنا عبر واتساب في أي وقت.',
        'faq4_q' => 'هل بياناتي آمنة؟',
        'faq4_a' => 'بالتأكيد. تشفير HTTPS، قاعدة بيانات معزولة مخصّصة لكل عميل، نسخ احتياطي تلقائي يومي. بياناتك ملكك ويمكن تصديرها في أي وقت.',
        'faq5_q' => 'هل يمكنني استخدام GestionPro من الهاتف؟',
        'faq5_a' => 'نعم، الواجهة متجاوبة بالكامل وتعمل بشكل مثالي على الهاتف والجهاز اللوحي والحاسوب.',
        'faq6_q' => 'ما اللغات المدعومة؟',
        'faq6_a' => 'الفرنسية (افتراضياً) والعربية والإنجليزية — مع دعم أصلي للكتابة من اليمين لليسار في العربية. يمكنك تبديل اللغة بنقرة واحدة من مساحة عملك.',
        'cta_h2' => 'جاهز لتبسيط تجارتك؟', 'cta_p' => 'راسلنا عبر واتساب: مساحة عملك جاهزة في نفس اليوم، دون أي التزام.',
        'footer_tagline' => 'GestionPro — برنامج إدارة الأعمال',
        'f_company' => 'اسم النشاط التجاري *', 'f_company_ph' => 'مثال: متجر النور',
        'f_slug' => 'عنوان مساحة العمل *', 'f_slug_ph' => 'acme-shop',
        'f_name' => 'اسمك *', 'f_name_ph' => 'محمد الأمين',
        'f_phone' => 'الهاتف', 'f_phone_ph' => '+222 12 34 56 78',
        'f_email' => 'البريد الإلكتروني *', 'f_email_ph' => 'you@example.com',
        'f_password' => 'كلمة المرور *', 'f_password_ph' => '6 أحرف على الأقل',
        'f_submit' => 'المتابعة إلى الدفع الآمن',
        'js_creating' => 'جارٍ إنشاء مساحة عملك...', 'js_redirect' => 'جارٍ التحويل إلى الدفع...', 'js_opening' => 'جارٍ فتح مساحة عملك...',
        'js_created_err' => 'تم إنشاء مساحة العمل لكن تعذّر المتابعة تلقائياً. يرجى تسجيل الدخول.',
        'js_conn_err' => 'خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
        'popular' => '\2B50 الأكثر شيوعاً',
        'wa_start' => 'ابدأ عبر واتساب', 'wa_contact' => 'تواصل معنا عبر واتساب',
        'wa_msg_general' => 'مرحباً، أرغب في معرفة المزيد عن GestionPro.',
        'wa_note' => 'اطلب عبر واتساب وسيتواصل معك فريقنا لإتمام طلبك.',
        // Use cases per business
        'nav_usecases' => 'لمن؟',
        'uc_label' => 'لمن؟', 'uc_title' => 'لكل مهنة قواعدها',
        'uc_desc' => 'يتكيف GestionPro مع نشاطك: فعّل ما تحتاجه فقط.',
        'uc1_t' => 'متجر وتجارة', 'uc1_sub' => 'مواد غذائية، أدوات، ملابس، تجميل...',
        'uc1_b1' => 'صندوق سريع مع قارئ الباركود',
        'uc1_b2' => 'مخزون لحظي وتنبيهات النفاد',
        'uc1_b3' => 'ديون الزبائن: من يدين وبكم ومنذ متى',
        'uc1_b4' => 'إيصالات مطبوعة وفواتير PDF',
        'uc1_b5' => 'يواصل البيع حتى لو انقطع الإنترنت',
        'uc2_t' => 'مطعم ومقهى وحلويات', 'uc2_sub' => 'قاعة، وجبات سريعة، عصائر، تموين...',
        'uc2_b1' => 'طلبات حسب الطاولة أو سفري أو توصيل',
        'uc2_b2' => 'شاشة مطبخ وبون يطبع عند الإرسال',
        'uc2_b3' => 'ملاحظات لكل صنف: بدون بصل، ناضج جيدا...',
        'uc2_b4' => 'تقسيم أو دمج الفواتير',
        'uc2_b5' => 'حساب طاه محصور في شاشته',
        'uc3_t' => 'خدمات وحرفيون', 'uc3_sub' => 'ورشات، مقدمو خدمات، مهن حرة...',
        'uc3_b1' => 'عرض سعر يتحول إلى فاتورة بنقرة',
        'uc3_b2' => 'فواتير PDF بترويسة مؤسستك',
        'uc3_b3' => 'متابعة المدفوعات والمتأخرات',
        'uc3_b4' => 'ملف الزبائن والسجل الكامل',
        'uc3_b5' => 'تصدير CSV لمحاسبك',
    ],
    'fr' => [
        'cur' => 'MRU',
        'page_title' => 'GestionPro — Logiciel de gestion d\'entreprise tout-en-un',
        'page_desc' => 'Gérez stock, factures, caisse et clients. Un seul outil pour votre activité — commandez sur WhatsApp, sans engagement.',
        'nav_features' => 'Fonctionnalités', 'nav_faq' => 'FAQ',
        'nav_admin_title' => 'Administration', 'nav_cta' => 'Nous contacter',
        'hero_h1_1' => 'Gérez votre activité.', 'hero_h1_2' => 'Simplifiez votre journée.',
        'hero_sub' => 'La plateforme tout-en-un pour boutiques, PME et prestataires de services. Stock, caisse, factures, clients et finances — un seul outil, prêt en 30 secondes.',
        'hero_trust1' => 'Fonctionne même sans internet', 'hero_trust2' => 'Aucune carte bancaire requise',
        'hero_trust3' => 'Disponible en arabe, français et anglais',
        'hero_cta1' => 'Créer mon espace', 'hero_cta2' => 'Voir la démo',
        'stat_businesses' => 'Entreprises actives', 'stat_signup' => 'Inscription', 'stat_uptime' => 'Disponibilité', 'stat_access' => 'Accès',
        'm_dashboard' => 'Tableau de bord', 'm_pos' => 'Caisse', 'm_products' => 'Produits', 'm_clients' => 'Clients',
        'm_invoices' => 'Factures', 'm_debts' => 'Dettes', 'm_payments' => 'Paiements',
        'kpi_revenue' => 'Revenu du jour', 'kpi_stock' => 'Produits en stock', 'kpi_alerts' => 'Alertes de stock',
        'feat_label' => 'Fonctionnalités', 'feat_title' => 'Tout pour gérer votre activité',
        'feat_desc' => 'Une boîte à outils complète pour boutiques et PME partout dans le monde. Multi-utilisateurs, multilingue, prêt à l\'emploi.',
        'feat1_t' => 'Caisse rapide', 'feat1_d' => 'Interface de vente intuitive avec scan de code-barres, recherche instantanée et encaissement en un clic.',
        'feat2_t' => 'Gestion du stock', 'feat2_d' => 'Suivi en temps réel, alertes de stock bas, entrées et sorties automatiques à chaque vente.',
        'feat3_t' => 'Devis & Factures', 'feat3_d' => 'Créez des devis, convertissez-les en factures, générez des PDF professionnels en quelques secondes.',
        'feat4_t' => 'Suivi des crédits', 'feat4_d' => 'Gérez les créances clients et les dettes fournisseurs. Sachez qui vous doit quoi, à tout moment.',
        'feat5_t' => 'Tableaux de bord & rapports', 'feat5_d' => 'Tendances du chiffre d\'affaires, meilleurs produits, situation de trésorerie — des analyses claires en un coup d\'œil.',
        // Slot 6 sells offline rather than languages: it is the real differentiator,
        // and the language mention already lives in the hero and the FAQ.
        'feat6_t' => 'Fonctionne hors ligne', 'feat6_d' => 'La caisse continue d\'encaisser même sans internet. Les ventes se synchronisent toutes seules dès que le réseau revient.',
        // Problem-first section — each pain maps to a feature above it.
        'prob_label' => 'Ça vous parle ?', 'prob_title' => 'Les casse-têtes que tout commerçant connaît',
        'prob_desc' => 'Si vous vous reconnaissez dans un seul de ces points, GestionPro est fait pour vous.',
        'prob1_t' => 'Vendre à crédit, à l\'aveugle',
        'prob1_d' => 'Un carnet, des ardoises, et en fin de mois vous ne savez plus qui vous doit quoi — ni depuis quand.',
        'prob2_t' => 'La rupture découverte par le client',
        'prob2_d' => 'Vous apprenez qu\'un article manque quand on vous le demande. Votre stock est dans votre tête, pas dans un tableau.',
        'prob3_t' => 'Des chiffres flous à la fermeture',
        'prob3_d' => 'Combien avez-vous vraiment vendu aujourd\'hui ? Qu\'est-ce qui marche ? Gagnez-vous de l\'argent ? Vous devinez.',
        'prob4_t' => 'La connexion qui lâche',
        'prob4_d' => 'Internet saute et la caisse s\'arrête. Vos clients attendent pendant que vous notez sur un bout de papier.',
        'how_label' => 'Prise en main', 'how_title' => 'Prêt en 30 secondes',
        'step1_t' => 'Créez votre espace', 'step1_d' => 'Contactez-nous sur WhatsApp : notre équipe crée votre espace et vous accompagne. Aucune carte bancaire requise.',
        'step2_t' => 'Ajoutez vos produits', 'step2_d' => 'Importez votre catalogue, définissez les prix et les stocks de départ.',
        'step3_t' => 'Commencez à vendre', 'step3_d' => 'Utilisez la caisse, facturez vos clients, suivez vos finances en temps réel.',
        // Bullets shown on the live Polar plan cards when no custom list is set in /admin/polar.
        'trust_label' => 'Sécurité', 'trust_title' => 'Vos données sont protégées',
        'trust_desc' => 'Chiffrement, sauvegardes et isolation des données — votre activité mérite une infrastructure solide.',
        'trust1_t' => 'HTTPS partout', 'trust1_d' => 'Connexions chiffrées TLS 1.3 sur chaque page et appel API.',
        'trust2_t' => 'Base de données dédiée', 'trust2_d' => 'Chaque client dispose de sa propre base de données isolée.',
        'trust3_t' => 'Sauvegardes quotidiennes', 'trust3_d' => 'Sauvegardes automatiques quotidiennes de vos données.',
        'trust4_t' => 'Mots de passe sécurisés', 'trust4_d' => 'Hachage Bcrypt — jamais de mots de passe en clair.',
        'faq_label' => 'Questions', 'faq_title' => 'Tout ce que vous voulez savoir',
        'faq1_q' => 'Comment démarrer avec GestionPro ?',
        'faq1_a' => 'Écrivez-nous sur WhatsApp en choisissant votre formule. Notre équipe crée votre espace, importe votre catalogue et vous accompagne pour la mise en route — votre espace est prêt le jour même.',
        'faq2_q' => 'Comment se passe le paiement ?',
        'faq2_a' => 'Notre équipe convient avec vous de la formule et de l\'échéance sur WhatsApp, puis active votre abonnement. Aucune carte bancaire n\'est enregistrée sur le site.',
        'faq3_q' => 'Puis-je arrêter à tout moment ?',
        'faq3_a' => 'Oui, sans aucun engagement. Aucune carte n\'est enregistrée à l\'avance : c\'est vous qui décidez de continuer ou d\'arrêter, et vous pouvez joindre notre équipe sur WhatsApp à tout moment.',
        'faq4_q' => 'Mes données sont-elles sécurisées ?',
        'faq4_a' => 'Absolument. Chiffrement HTTPS, une base de données isolée dédiée par client, sauvegardes automatiques quotidiennes. Vos données vous appartiennent et sont exportables à tout moment.',
        'faq5_q' => 'Puis-je utiliser GestionPro depuis un téléphone ?',
        'faq5_a' => 'Oui, l\'interface est entièrement responsive et fonctionne parfaitement sur téléphone, tablette et ordinateur.',
        'faq6_q' => 'Quelles langues sont prises en charge ?',
        'faq6_a' => 'Le français (par défaut), l\'arabe et l\'anglais — avec support RTL natif pour l\'arabe. Vous pouvez changer de langue en un clic depuis votre espace.',
        'cta_h2' => 'Prêt à simplifier votre activité ?', 'cta_p' => 'Écrivez-nous sur WhatsApp : votre espace est prêt le jour même, sans engagement.',
        'footer_tagline' => 'GestionPro — Logiciel de gestion d\'entreprise',
        'f_company' => 'Nom de l\'entreprise *', 'f_company_ph' => 'ex. Boutique Acme',
        'f_slug' => 'Adresse de l\'espace *', 'f_slug_ph' => 'acme-shop',
        'f_name' => 'Votre nom *', 'f_name_ph' => 'Jean Dupont',
        'f_phone' => 'Téléphone', 'f_phone_ph' => '+222 12 34 56 78',
        'f_email' => 'E-mail *', 'f_email_ph' => 'vous@exemple.com',
        'f_password' => 'Mot de passe *', 'f_password_ph' => 'Au moins 6 caractères',
        'f_submit' => 'Continuer vers le paiement sécurisé',
        'js_creating' => 'Création de votre espace...', 'js_redirect' => 'Redirection vers le paiement...', 'js_opening' => 'Ouverture de votre espace...',
        'js_created_err' => 'Espace créé mais impossible de continuer automatiquement. Veuillez vous connecter.',
        'js_conn_err' => 'Erreur de connexion. Veuillez réessayer.',
        'popular' => '\2B50 Le plus populaire',
        'wa_start' => 'Démarrer sur WhatsApp', 'wa_contact' => 'Nous contacter sur WhatsApp',
        'wa_msg_general' => 'Bonjour, je souhaite en savoir plus sur GestionPro.',
        'wa_note' => 'Commandez sur WhatsApp et notre équipe vous recontacte pour finaliser votre commande.',
        // Use cases per business
        'nav_usecases' => 'Pour qui',
        'uc_label' => 'Pour qui', 'uc_title' => 'Chaque metier a ses regles',
        'uc_desc' => 'GestionPro s\'adapte a votre activite : vous n\'activez que ce dont vous avez besoin.',
        'uc1_t' => 'Boutique & commerce', 'uc1_sub' => 'Alimentation, quincaillerie, vetements, cosmetiques...',
        'uc1_b1' => 'Caisse rapide avec scan de code-barres',
        'uc1_b2' => 'Stock en temps reel et alertes de rupture',
        'uc1_b3' => 'Credits clients : qui doit quoi, depuis quand',
        'uc1_b4' => 'Tickets imprimes et factures PDF',
        'uc1_b5' => 'Encaisse meme quand internet tombe',
        'uc2_t' => 'Restaurant, cafe & patisserie', 'uc2_sub' => 'Salle, snack, jus, traiteur...',
        'uc2_b1' => 'Commandes par table, a emporter ou en livraison',
        'uc2_b2' => 'Ecran cuisine + bon imprime a l\'envoi',
        'uc2_b3' => 'Notes par plat : sans oignon, bien cuit...',
        'uc2_b4' => 'Separer ou fusionner les additions',
        'uc2_b5' => 'Compte cuisinier limite a son ecran',
        'uc3_t' => 'Services & artisans', 'uc3_sub' => 'Ateliers, prestataires, professions liberales...',
        'uc3_b1' => 'Devis converti en facture en un clic',
        'uc3_b2' => 'Factures PDF a votre en-tete',
        'uc3_b3' => 'Suivi des paiements et des impayes',
        'uc3_b4' => 'Fichier clients et historique complet',
        'uc3_b5' => 'Exports CSV pour votre comptable',
    ],
    'en' => [
        'cur' => 'MRU',
        'page_title' => 'GestionPro — All-in-one business management software',
        'page_desc' => 'Manage stock, invoices, POS and clients. One tool for your business — order on WhatsApp, no commitment.',
        'nav_features' => 'Features', 'nav_faq' => 'FAQ',
        'nav_admin_title' => 'Administration', 'nav_cta' => 'Contact us',
        'hero_h1_1' => 'Run your business.', 'hero_h1_2' => 'Simplify your day.',
        'hero_sub' => 'The all-in-one platform for shops, SMBs and service businesses. Stock, POS, invoices, clients and finance — one tool, ready in 30 seconds.',
        'hero_trust1' => 'Works even without internet', 'hero_trust2' => 'No bank card required',
        'hero_trust3' => 'Available in Arabic, French and English',
        'hero_cta1' => 'Create my workspace', 'hero_cta2' => 'See the demo',
        'stat_businesses' => 'Active businesses', 'stat_signup' => 'Sign-up', 'stat_uptime' => 'Uptime', 'stat_access' => 'Access',
        'm_dashboard' => 'Dashboard', 'm_pos' => 'POS', 'm_products' => 'Products', 'm_clients' => 'Clients',
        'm_invoices' => 'Invoices', 'm_debts' => 'Debts', 'm_payments' => 'Payments',
        'kpi_revenue' => 'Today\'s revenue', 'kpi_stock' => 'Products in stock', 'kpi_alerts' => 'Stock alerts',
        'feat_label' => 'Features', 'feat_title' => 'Everything to run your business',
        'feat_desc' => 'A complete toolkit for shops and SMBs anywhere in the world. Multi-user, multilingual, ready out of the box.',
        'feat1_t' => 'Fast POS', 'feat1_d' => 'Intuitive sales interface with barcode scan, instant product search and one-click checkout.',
        'feat2_t' => 'Stock management', 'feat2_d' => 'Real-time tracking, low-stock alerts, automatic ins and outs with every sale.',
        'feat3_t' => 'Quotes & Invoices', 'feat3_d' => 'Create quotes, convert them to invoices, generate professional PDFs in seconds.',
        'feat4_t' => 'Credit tracking', 'feat4_d' => 'Manage customer receivables and supplier payables. Know who owes you what, anytime.',
        'feat5_t' => 'Dashboards & reports', 'feat5_d' => 'Revenue trends, top products, cash positions — clear insights at a glance.',
        // Slot 6 sells offline rather than languages: it is the real differentiator,
        // and the language mention already lives in the hero and the FAQ.
        'feat6_t' => 'Works offline', 'feat6_d' => 'The till keeps selling with no internet. Sales sync on their own the moment the connection is back.',
        // Problem-first section — each pain maps to a feature above it.
        'prob_label' => 'Sound familiar?', 'prob_title' => 'The headaches every shop owner knows',
        'prob_desc' => 'If even one of these rings true, GestionPro was built for you.',
        'prob1_t' => 'Selling on credit, blind',
        'prob1_d' => 'A notebook, scraps of paper — and by month end you no longer know who owes you what, or since when.',
        'prob2_t' => 'Stock-outs your customer finds first',
        'prob2_d' => 'You learn an item ran out when someone asks for it. Your stock lives in your head, not in a table.',
        'prob3_t' => 'Fuzzy numbers at closing',
        'prob3_d' => 'How much did you really sell today? What works? Are you making money? You guess.',
        'prob4_t' => 'The connection that drops',
        'prob4_d' => 'The internet cuts out and the till stops. Your customers wait while you scribble on paper.',
        'how_label' => 'Getting started', 'how_title' => 'Ready in 30 seconds',
        'step1_t' => 'Create your workspace', 'step1_d' => 'Message us on WhatsApp: our team sets up your workspace and guides you. No bank card required.',
        'step2_t' => 'Add your products', 'step2_d' => 'Import your catalogue, set prices and opening stock levels.',
        'step3_t' => 'Start selling', 'step3_d' => 'Use the POS, invoice clients, track your finances in real time.',
        // Bullets shown on the live Polar plan cards when no custom list is set in /admin/polar.
        'trust_label' => 'Security', 'trust_title' => 'Your data is protected',
        'trust_desc' => 'Encryption, backups and data isolation — your business deserves solid infrastructure.',
        'trust1_t' => 'HTTPS everywhere', 'trust1_d' => 'TLS 1.3 encrypted connections on every page and API call.',
        'trust2_t' => 'Dedicated database', 'trust2_d' => 'Every customer gets its own isolated database.',
        'trust3_t' => 'Daily backups', 'trust3_d' => 'Automatic daily backups of your data.',
        'trust4_t' => 'Secure passwords', 'trust4_d' => 'Bcrypt hashing — no plain-text passwords ever.',
        'faq_label' => 'Questions', 'faq_title' => 'Everything you want to know',
        'faq1_q' => 'How do I get started with GestionPro?',
        'faq1_a' => 'Message us on WhatsApp with the plan you want. Our team creates your workspace, imports your catalogue and walks you through setup — your workspace is ready the same day.',
        'faq2_q' => 'How does payment work?',
        'faq2_a' => 'Our team agrees the plan and billing date with you on WhatsApp, then activates your subscription. No bank card is stored on the site.',
        'faq3_q' => 'Can I stop at any time?',
        'faq3_a' => 'Yes, with no commitment at all. No card is stored upfront: you decide whether to continue or stop, and you can reach our team on WhatsApp at any time.',
        'faq4_q' => 'Is my data secure?',
        'faq4_a' => 'Absolutely. HTTPS encryption, a dedicated isolated database per customer, automatic daily backups. Your data belongs to you and is exportable at any time.',
        'faq5_q' => 'Can I use GestionPro from a phone?',
        'faq5_a' => 'Yes, the interface is fully responsive and works perfectly on phone, tablet and desktop.',
        'faq6_q' => 'Which languages are supported?',
        'faq6_a' => 'French (default), Arabic and English — with native RTL support for Arabic. You can switch languages in one click from your workspace.',
        'cta_h2' => 'Ready to simplify your business?', 'cta_p' => 'Message us on WhatsApp: your workspace is ready the same day, with no commitment.',
        'footer_tagline' => 'GestionPro — Business management software',
        'f_company' => 'Business name *', 'f_company_ph' => 'e.g. Acme Shop',
        'f_slug' => 'Workspace address *', 'f_slug_ph' => 'acme-shop',
        'f_name' => 'Your name *', 'f_name_ph' => 'Jane Doe',
        'f_phone' => 'Phone', 'f_phone_ph' => '+222 12 34 56 78',
        'f_email' => 'Email *', 'f_email_ph' => 'you@example.com',
        'f_password' => 'Password *', 'f_password_ph' => 'At least 6 characters',
        'f_submit' => 'Continue to secure checkout',
        'js_creating' => 'Creating your workspace...', 'js_redirect' => 'Redirecting to checkout...', 'js_opening' => 'Opening your workspace...',
        'js_created_err' => 'Workspace created but we could not continue automatically. Please sign in.',
        'js_conn_err' => 'Connection error. Please try again.',
        'popular' => '\2B50 Most popular',
        'wa_start' => 'Get started on WhatsApp', 'wa_contact' => 'Contact us on WhatsApp',
        'wa_msg_general' => 'Hello, I\'d like to know more about GestionPro.',
        'wa_note' => 'Order on WhatsApp and our team will get back to you to finalise your order.',
        // Use cases per business
        'nav_usecases' => 'Who it\'s for',
        'uc_label' => 'Who it\'s for', 'uc_title' => 'Every trade has its own rules',
        'uc_desc' => 'GestionPro fits your business: switch on only what you need.',
        'uc1_t' => 'Shops & retail', 'uc1_sub' => 'Groceries, hardware, clothing, cosmetics...',
        'uc1_b1' => 'Fast till with barcode scanning',
        'uc1_b2' => 'Live stock and out-of-stock alerts',
        'uc1_b3' => 'Customer credit: who owes what, since when',
        'uc1_b4' => 'Printed receipts and PDF invoices',
        'uc1_b5' => 'Keeps selling when the internet drops',
        'uc2_t' => 'Restaurant, cafe & pastry', 'uc2_sub' => 'Dining room, snack bar, juice, catering...',
        'uc2_b1' => 'Orders by table, takeaway or delivery',
        'uc2_b2' => 'Kitchen screen + ticket printed on send',
        'uc2_b3' => 'Notes per dish: no onion, well done...',
        'uc2_b4' => 'Split or merge the bills',
        'uc2_b5' => 'Kitchen account limited to its own screen',
        'uc3_t' => 'Services & trades', 'uc3_sub' => 'Workshops, contractors, professionals...',
        'uc3_b1' => 'Quote turned into an invoice in one click',
        'uc3_b2' => 'PDF invoices with your letterhead',
        'uc3_b3' => 'Payment and overdue tracking',
        'uc3_b4' => 'Client file and full history',
        'uc3_b5' => 'CSV exports for your accountant',
    ],
];
$t = $TR[$lang];
$cur = $t['cur'];

// WhatsApp ordering links. No self-serve checkout on the landing page:
// every call-to-action opens WhatsApp with a prefilled message.
$waBase = 'https://wa.me/' . WHATSAPP_ORDER;
$waLink = static function (string $message) use ($waBase) {
    return $waBase . '?text=' . rawurlencode($message);
};
$waGeneral = $waLink($t['wa_msg_general']);

// No pricing grid: plans and prices are agreed on WhatsApp, so the landing no
// longer fetches Polar products nor prints amounts. $fmtMru stays — the hero
// mockup still formats its demo revenue with it.
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['page_title']) ?></title>
    <meta name="description" content="<?= e($t['page_desc']) ?>">
    <?php if (defined('GOOGLE_SITE_VERIFICATION') && GOOGLE_SITE_VERIFICATION !== ''): ?>
    <meta name="google-site-verification" content="<?= e(GOOGLE_SITE_VERIFICATION) ?>">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
        :root{
            --primary:#4F46E5;--primary-light:#6366F1;--primary-dark:#3730A3;
            --primary-50:#EEF2FF;--primary-100:#E0E7FF;--primary-200:#C7D2FE;
            --text:#0F172A;--text-secondary:#475569;--text-muted:#94A3B8;
            --bg:#F8FAFC;--surface:#FFFFFF;--border:#E2E8F0;--border-light:#F1F5F9;
            --success:#10B981;--danger:#EF4444;--warning:#F59E0B;
            /* WhatsApp brand — every WhatsApp CTA uses it so the action reads instantly */
            --wa:#25D366;--wa-dark:#1DBF5C;--wa-deep:#128C7E;--wa-glow:rgba(37,211,102,.35);
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
        .nav-links .btn-nav{background:var(--wa);color:#fff;font-weight:700;padding:9px 18px;border-radius:10px;display:inline-flex;align-items:center;gap:7px;box-shadow:0 3px 10px var(--wa-glow);transition:all .2s}
        .nav-links .btn-nav:hover{background:var(--wa-dark);color:#fff;box-shadow:0 6px 16px rgba(37,211,102,.45)}
        .nav-links .btn-nav i{font-size:16px}

        /* ===== HERO ===== */
        .hero{padding:130px 24px 80px;position:relative;overflow:hidden;background:linear-gradient(180deg,#fff 0%,var(--primary-50) 100%)}
        .hero::before{content:'';position:absolute;top:-200px;left:50%;transform:translateX(-50%);width:900px;height:900px;border-radius:50%;background:radial-gradient(circle,rgba(79,70,229,.08) 0%,transparent 70%);pointer-events:none}
        .hero-inner{max-width:1100px;margin:0 auto;text-align:center;position:relative}
        .hero h1{font-size:clamp(36px,5.5vw,68px);font-weight:900;line-height:1.05;letter-spacing:-2px;max-width:860px;margin:0 auto 20px;background:linear-gradient(135deg,var(--text) 0%,var(--primary-dark) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .hero-sub{font-size:clamp(16px,2vw,19px);color:var(--text-secondary);max-width:620px;margin:0 auto 36px;line-height:1.65}
        .hero-trust{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;margin-bottom:28px;font-size:13px;color:var(--text-secondary)}
        .hero-trust span{display:inline-flex;align-items:center;gap:6px}
        .hero-trust i{color:var(--success)}
        .hero-cta{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:52px}
        .btn-cta{display:inline-flex;align-items:center;gap:10px;padding:16px 30px;border-radius:14px;font-weight:700;font-size:15px;transition:all .25s;cursor:pointer;border:none;font-family:inherit}
        /* Both hero and closing CTAs are WhatsApp actions — brand them as such. */
        .btn-primary-cta{background:var(--wa);color:#fff;box-shadow:0 8px 24px var(--wa-glow)}
        .btn-primary-cta:hover{background:var(--wa-dark);transform:translateY(-2px);box-shadow:0 14px 34px rgba(37,211,102,.45)}
        .btn-cta i{font-size:19px}
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

        /* Problem-first section: name the pain before selling the cure. */
        .prob-section{background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
        .prob-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
        .prob-card{display:flex;gap:16px;align-items:flex-start;padding:24px;border-radius:16px;background:var(--bg);border:1px solid var(--border);transition:all .25s}
        .prob-card:hover{border-color:var(--primary-200);box-shadow:var(--shadow-md);transform:translateY(-2px)}
        .prob-card .ic{flex:0 0 44px;height:44px;border-radius:12px;background:rgba(239,68,68,.09);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:17px}
        /* Use cases: one panel per trade, so a visitor sees their own shop. */
        .uc-section{background:var(--bg)}
        .uc-tabs{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px}
        .uc-tab{display:inline-flex;align-items:center;gap:9px;padding:12px 20px;border-radius:100px;border:1.5px solid var(--border);background:var(--surface);font-family:inherit;font-size:14.5px;font-weight:600;color:var(--text-secondary);cursor:pointer;transition:all .2s}
        .uc-tab:hover{border-color:var(--primary);color:var(--primary)}
        .uc-tab.active{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 6px 18px rgba(79,70,229,.3)}
        .uc-tab i{font-size:16px}
        .uc-panel{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:36px;box-shadow:var(--shadow-md);animation:uc-in .25s ease}
        @keyframes uc-in{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
        .uc-head{display:flex;align-items:center;gap:16px;margin-bottom:24px}
        .uc-ic{width:56px;height:56px;flex-shrink:0;border-radius:16px;background:var(--primary-50);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:24px}
        .uc-head h3{font-size:22px;font-weight:800;letter-spacing:-.5px}
        .uc-head p{font-size:14px;color:var(--text-muted);margin-top:2px}
        .uc-list{list-style:none;display:grid;grid-template-columns:repeat(2,1fr);gap:14px 28px;margin-bottom:28px}
        .uc-list li{display:flex;align-items:flex-start;gap:11px;font-size:15px;color:var(--text-secondary);line-height:1.5}
        .uc-list li i{color:var(--success);font-size:15px;margin-top:3px;flex-shrink:0}
        .uc-cta{padding:13px 24px;font-size:14.5px}
        @media (max-width:768px){.uc-panel{padding:24px}.uc-list{grid-template-columns:1fr}.uc-tab{flex:1;justify-content:center;padding:11px 14px;font-size:13px}}

        .prob-card h3{font-size:16px;font-weight:700;margin-bottom:6px;letter-spacing:-.2px}
        .prob-card p{font-size:14px;color:var(--text-secondary);line-height:1.65}
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
        /* WhatsApp order buttons: inline-flex (was block, so the icon sat off-baseline). */

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
            .features-grid,.steps,.trust-grid,.prob-grid{grid-template-columns:1fr}
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
    <style>
        /* Locale-specific: Arabic font + RTL adjustments */
        <?php if ($isRtl): ?>
        body{font-family:'Cairo','Inter',sans-serif}
        .hero h1{letter-spacing:0}
        .section-title,.hero-sub{letter-spacing:0}
        .slug-prefix{border-radius:0 10px 10px 0;border-right:1.5px solid var(--border);border-left:0}
        .slug-preview input{border-radius:10px 0 0 10px}
        .step-arrow{right:auto;left:-20px;transform:scaleX(-1)}
        <?php endif; ?>
        /* Language switcher */
        .lang-switch{display:inline-flex;align-items:center;gap:2px;margin-<?= $isRtl ? 'right' : 'left' ?>:6px}
        .lang-switch a{padding:5px 8px;border-radius:7px;font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase}
        .lang-switch a.active{background:var(--primary-50);color:var(--primary)}
        .lang-switch a:hover{color:var(--text)}
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
            <a href="#features" class="hide-mobile"><?= e($t['nav_features']) ?></a>
            <a href="#usecases" class="hide-mobile"><?= e($t['nav_usecases']) ?></a>
            <a href="#faq" class="hide-mobile"><?= e($t['nav_faq']) ?></a>
            <a href="<?= APP_BASE ?>/admin/login" class="hide-mobile" style="font-size:13px;color:var(--text-muted);" title="<?= e($t['nav_admin_title']) ?>"><i class="fas fa-shield-halved"></i></a>
            <span class="lang-switch">
                <a href="?lang=ar" class="<?= $lang === 'ar' ? 'active' : '' ?>">ع</a>
                <a href="?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>
                <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
            </span>
            <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" class="btn-nav"><i class="fab fa-whatsapp"></i> <?= e($t['nav_cta']) ?></a>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <h1><?= e($t['hero_h1_1']) ?><br><?= e($t['hero_h1_2']) ?></h1>
        <p class="hero-sub"><?= e($t['hero_sub']) ?></p>

        <div class="hero-trust">
            <span><i class="fas fa-check-circle"></i> <?= e($t['hero_trust1']) ?></span>
            <span><i class="fas fa-check-circle"></i> <?= e($t['hero_trust2']) ?></span>
            <span><i class="fas fa-check-circle"></i> <?= e($t['hero_trust3']) ?></span>
        </div>

        <div class="hero-cta">
            <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" class="btn-cta btn-primary-cta"><i class="fab fa-whatsapp"></i> <?= e($t['wa_start']) ?></a>
            <a href="#features" class="btn-cta btn-secondary-cta"><i class="fas fa-play-circle"></i> <?= e($t['hero_cta2']) ?></a>
        </div>

        <div class="hero-stats">
            <div class="hero-stat"><div class="v"><?= max($totalTenants, 50) ?>+</div><div class="l"><?= e($t['stat_businesses']) ?></div></div>
            <div class="hero-stat"><div class="v">30<span style="font-size:.7em;">s</span></div><div class="l"><?= e($t['stat_signup']) ?></div></div>
            <div class="hero-stat"><div class="v">99.9%</div><div class="l"><?= e($t['stat_uptime']) ?></div></div>
            <div class="hero-stat"><div class="v">24/7</div><div class="l"><?= e($t['stat_access']) ?></div></div>
        </div>

        <div class="hero-mockup">
            <div class="mockup-toolbar">
                <div class="mockup-dot r"></div><div class="mockup-dot y"></div><div class="mockup-dot g"></div>
                <div style="flex:1;text-align:center;font-size:12px;color:var(--text-muted);">gestionpro.it.com/my-business/dashboard</div>
            </div>
            <div class="mockup-body">
                <div class="mockup-sidebar">
                    <div class="m-item active"><i class="fas fa-chart-line"></i> <?= e($t['m_dashboard']) ?></div>
                    <div class="m-item"><i class="fas fa-cash-register"></i> <?= e($t['m_pos']) ?></div>
                    <div class="m-item"><i class="fas fa-boxes-stacked"></i> <?= e($t['m_products']) ?></div>
                    <div class="m-item"><i class="fas fa-users"></i> <?= e($t['m_clients']) ?></div>
                    <div class="m-item"><i class="fas fa-file-invoice-dollar"></i> <?= e($t['m_invoices']) ?></div>
                    <div class="m-item"><i class="fas fa-hand-holding-dollar"></i> <?= e($t['m_debts']) ?></div>
                    <div class="m-item"><i class="fas fa-money-bill-wave"></i> <?= e($t['m_payments']) ?></div>
                </div>
                <div class="mockup-main">
                    <div class="mockup-kpi">
                        <div class="m-kpi"><div class="val green"><?= $fmtMru(8475) ?> <?= e($cur) ?></div><div class="lbl"><?= e($t['kpi_revenue']) ?></div></div>
                        <div class="m-kpi"><div class="val blue">2 340</div><div class="lbl"><?= e($t['kpi_stock']) ?></div></div>
                        <div class="m-kpi"><div class="val orange">12</div><div class="lbl"><?= e($t['kpi_alerts']) ?></div></div>
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

<!-- PROBLEMS — name the pain first, then sell the cure -->
<section class="pad prob-section">
    <div class="container">
        <div class="section-label"><i class="fas fa-circle-question"></i> <?= e($t['prob_label']) ?></div>
        <div class="section-title"><?= e($t['prob_title']) ?></div>
        <div class="section-desc"><?= e($t['prob_desc']) ?></div>

        <div class="prob-grid">
            <div class="prob-card">
                <div class="ic"><i class="fas fa-hand-holding-dollar"></i></div>
                <div><h3><?= e($t['prob1_t']) ?></h3><p><?= e($t['prob1_d']) ?></p></div>
            </div>
            <div class="prob-card">
                <div class="ic"><i class="fas fa-boxes-stacked"></i></div>
                <div><h3><?= e($t['prob2_t']) ?></h3><p><?= e($t['prob2_d']) ?></p></div>
            </div>
            <div class="prob-card">
                <div class="ic"><i class="fas fa-chart-line"></i></div>
                <div><h3><?= e($t['prob3_t']) ?></h3><p><?= e($t['prob3_d']) ?></p></div>
            </div>
            <div class="prob-card">
                <div class="ic"><i class="fas fa-wifi"></i></div>
                <div><h3><?= e($t['prob4_t']) ?></h3><p><?= e($t['prob4_d']) ?></p></div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="pad" id="features">
    <div class="container">
        <div class="section-label"><i class="fas fa-bolt"></i> <?= e($t['feat_label']) ?></div>
        <div class="section-title"><?= e($t['feat_title']) ?></div>
        <div class="section-desc"><?= e($t['feat_desc']) ?></div>

        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon i1"><i class="fas fa-cash-register"></i></div>
                <h3><?= e($t['feat1_t']) ?></h3>
                <p><?= e($t['feat1_d']) ?></p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i6"><i class="fas fa-wifi"></i></div>
                <h3><?= e($t['feat6_t']) ?></h3>
                <p><?= e($t['feat6_d']) ?></p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i2"><i class="fas fa-boxes-stacked"></i></div>
                <h3><?= e($t['feat2_t']) ?></h3>
                <p><?= e($t['feat2_d']) ?></p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i3"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3><?= e($t['feat3_t']) ?></h3>
                <p><?= e($t['feat3_d']) ?></p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i4"><i class="fas fa-hand-holding-dollar"></i></div>
                <h3><?= e($t['feat4_t']) ?></h3>
                <p><?= e($t['feat4_d']) ?></p>
            </div>
            <div class="feat-card">
                <div class="feat-icon i5"><i class="fas fa-chart-line"></i></div>
                <h3><?= e($t['feat5_t']) ?></h3>
                <p><?= e($t['feat5_d']) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="how-inner">
        <div class="section-label"><i class="fas fa-wand-magic-sparkles"></i> <?= e($t['how_label']) ?></div>
        <div class="section-title" style="margin-bottom:0;"><?= e($t['how_title']) ?></div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3><?= e($t['step1_t']) ?></h3>
                <p><?= e($t['step1_d']) ?></p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3><?= e($t['step2_t']) ?></h3>
                <p><?= e($t['step2_d']) ?></p>
                <span class="step-arrow"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3><?= e($t['step3_t']) ?></h3>
                <p><?= e($t['step3_d']) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- USE CASES — what the product does for *your* trade, not in general -->
<section class="pad uc-section" id="usecases">
    <div class="container">
        <div class="section-label"><i class="fas fa-store"></i> <?= e($t['uc_label']) ?></div>
        <div class="section-title"><?= e($t['uc_title']) ?></div>
        <div class="section-desc"><?= e($t['uc_desc']) ?></div>

        <div class="uc-tabs" role="tablist">
            <?php
            $ucs = [
                1 => ['icon' => 'fa-store',       'key' => 'uc1'],
                2 => ['icon' => 'fa-utensils',    'key' => 'uc2'],
                3 => ['icon' => 'fa-screwdriver-wrench', 'key' => 'uc3'],
            ];
            foreach ($ucs as $i => $uc): ?>
            <button class="uc-tab <?= $i === 1 ? 'active' : '' ?>" data-uc="<?= $i ?>" role="tab"
                    aria-selected="<?= $i === 1 ? 'true' : 'false' ?>" onclick="showUc(<?= $i ?>)">
                <i class="fas <?= $uc['icon'] ?>"></i>
                <span><?= e($t[$uc['key'] . '_t']) ?></span>
            </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($ucs as $i => $uc): ?>
        <div class="uc-panel <?= $i === 1 ? '' : 'hidden' ?>" id="uc-<?= $i ?>" role="tabpanel">
            <div class="uc-head">
                <div class="uc-ic"><i class="fas <?= $uc['icon'] ?>"></i></div>
                <div>
                    <h3><?= e($t[$uc['key'] . '_t']) ?></h3>
                    <p><?= e($t[$uc['key'] . '_sub']) ?></p>
                </div>
            </div>
            <ul class="uc-list">
                <?php for ($b = 1; $b <= 5; $b++): ?>
                <li><i class="fas fa-circle-check"></i> <?= e($t[$uc['key'] . '_b' . $b]) ?></li>
                <?php endfor; ?>
            </ul>
            <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" class="btn-cta btn-primary-cta uc-cta">
                <i class="fab fa-whatsapp"></i> <?= e($t['wa_start']) ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TRUST / SECURITY -->
<section class="trust-section">
    <div class="trust-inner">
        <div class="section-label"><i class="fas fa-shield-halved"></i> <?= e($t['trust_label']) ?></div>
        <div class="section-title"><?= e($t['trust_title']) ?></div>
        <div class="section-desc"><?= e($t['trust_desc']) ?></div>
        <div class="trust-grid">
            <div class="trust-item">
                <i class="fas fa-lock"></i>
                <h4><?= e($t['trust1_t']) ?></h4>
                <p><?= e($t['trust1_d']) ?></p>
            </div>
            <div class="trust-item">
                <i class="fas fa-database"></i>
                <h4><?= e($t['trust2_t']) ?></h4>
                <p><?= e($t['trust2_d']) ?></p>
            </div>
            <div class="trust-item">
                <i class="fas fa-hard-drive"></i>
                <h4><?= e($t['trust3_t']) ?></h4>
                <p><?= e($t['trust3_d']) ?></p>
            </div>
            <div class="trust-item">
                <i class="fas fa-user-shield"></i>
                <h4><?= e($t['trust4_t']) ?></h4>
                <p><?= e($t['trust4_d']) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="pad" id="faq" style="background:#fff;">
    <div class="container" style="text-align:center;">
        <div class="section-label"><i class="fas fa-circle-question"></i> <?= e($t['faq_label']) ?></div>
        <div class="section-title"><?= e($t['faq_title']) ?></div>

        <div class="faq-wrap" style="text-align:<?= $isRtl ? 'right' : 'left' ?>;">
            <details class="faq-item">
                <summary><?= e($t['faq1_q']) ?></summary>
                <div class="faq-body"><?= e($t['faq1_a']) ?></div>
            </details>
            <details class="faq-item">
                <summary><?= e($t['faq2_q']) ?></summary>
                <div class="faq-body"><?= e($t['faq2_a']) ?></div>
            </details>
            <details class="faq-item">
                <summary><?= e($t['faq3_q']) ?></summary>
                <div class="faq-body"><?= $t['faq3_a'] ?></div>
            </details>
            <details class="faq-item">
                <summary><?= e($t['faq4_q']) ?></summary>
                <div class="faq-body"><?= e($t['faq4_a']) ?></div>
            </details>
            <details class="faq-item">
                <summary><?= e($t['faq5_q']) ?></summary>
                <div class="faq-body"><?= e($t['faq5_a']) ?></div>
            </details>
            <details class="faq-item">
                <summary><?= e($t['faq6_q']) ?></summary>
                <div class="faq-body"><?= e($t['faq6_a']) ?></div>
            </details>
        </div>
    </div>
</section>

<!-- CTA BANNER -->
<section class="cta-banner">
    <div class="container">
        <h2><?= e($t['cta_h2']) ?></h2>
        <p><?= e($t['cta_p']) ?></p>
        <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener" class="btn-cta btn-primary-cta"><i class="fab fa-whatsapp"></i> <?= e($t['wa_contact']) ?></a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-brand">GestionPro</div>
    <p>&copy; <?= date('Y') ?> <?= e($t['footer_tagline']) ?></p>
    <div class="footer-links">
        <a href="#features"><?= e($t['nav_features']) ?></a>
        <a href="#usecases"><?= e($t['nav_usecases']) ?></a>
        <a href="#faq"><?= e($t['nav_faq']) ?></a>
        <a href="<?= e($waGeneral) ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        <a href="<?= APP_BASE ?>/admin/login"><?= e($t['nav_admin_title']) ?></a>
    </div>
</footer>

<script>
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// Smooth-scroll for same-page anchors only (skip external/WhatsApp links).
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior:'smooth', block:'start' }); }
    });
});

// Use-case tabs. Panels are rendered server-side and merely toggled, so the
// content is in the HTML for search engines even though only one shows.
function showUc(n) {
    document.querySelectorAll('.uc-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('uc-' + n).classList.remove('hidden');
    document.querySelectorAll('.uc-tab').forEach(t => {
        const on = t.dataset.uc === String(n);
        t.classList.toggle('active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
    });
}
</script>

</body>
</html>
