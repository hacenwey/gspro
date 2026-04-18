# Audit Technique Interne — GestionPro V2

**Date :** 18 avril 2026
**Portée :** code source `gestion_commerciale` (master branch, commit `3606d15`)
**Déploiement actuel :** `gestionpro.it.com` sur AWS EC2 (Docker Compose)
**Objectif :** fournir un état des lieux objectif avant la phase *product discovery* V2.

---

## 1. Synthèse exécutive

GestionPro est un SaaS multi-tenant (PHP 8.2) de gestion commerciale pour PME : POS, stock, clients/fournisseurs, factures/devis, dettes/créances, trésorerie, tickets support, abonnement Polar.sh. Le produit est **fonctionnel en production**, avec 4 tenants actifs, un onboarding, un paywall, un panneau super-admin et l'intégration paiement complète (sandbox + live).

La codebase est **compacte** (~4 500 lignes hors views/CSS/JS), **lisible**, et suit un pattern MVC minimal. Elle souffre cependant de plusieurs dettes structurelles qui limitent la capacité à scaler côté produit (nouvelles features rapides) et côté technique (volumétrie, équipe, qualité).

**Les 5 chantiers critiques pour la V2 :**

1. **Concurrence & intégrité stock** — les ventes POS peuvent sur-vendre en charge parallèle.
2. **Rendu PDF des factures** — actuellement HTML, pas de vrai PDF.
3. **Absence totale de tests** — aucun filet de sécurité pour refactor.
4. **Dette résiduelle Mauritanie** — enums paiement, `tax_rate=16`, "DA" en dur, dans une app devenue USD-only i18n.
5. **Couche frontend monolithique** — un `app.css` de 1 278 lignes, aucun design system réutilisable.

---

## 2. Inventaire des features

### 2.1 Features tenant (app métier)
| Module | État | Controller | Remarque |
|---|---|---|---|
| Authentification (login / logout / reset) | ✅ prod | AuthController | multi-tenant, session par slug |
| Onboarding entreprise | ✅ prod | OnboardingController | 1 seule étape, UX light |
| Dashboard (KPIs + charts) | ✅ prod | DashboardController | 7–30 requêtes SQL pour le chart |
| Produits + variantes + stock | ✅ prod | ProductController | variants déclarés en schema mais pas exposés UI |
| Catégories | ✅ prod | CategoryController | CRUD basique |
| Clients | ✅ prod | ClientController | balance calculée séparément |
| Fournisseurs | ✅ prod | SupplierController | CRUD basique |
| Caisse / POS | ✅ prod | CaisseController | session ouverte/fermée, credit, tickets |
| Factures & devis | ⚠️ partiel | InvoiceController | pas de vrai PDF ; devis→facture OK |
| Dettes & créances | ✅ prod | DebtController | alimenté par ventes à crédit |
| Paiements | ✅ prod | PaymentController | index seulement |
| Tickets support | ✅ prod | TicketController | intégré à la sidebar admin |
| Paramètres + utilisateurs | ✅ prod | SettingsController | création user minimaliste |
| Facturation SaaS (Polar) | ✅ prod | BillingController | webhook + 6 events + cancel |

### 2.2 Tables déclarées mais non exposées UI
- `product_variants` — schema prêt, aucun controller ne l'utilise
- `purchase_orders` / `purchase_order_items` — schema prêt, pas de vue, pas de controller → **feature zombie**
- `audit_log` — écrite ponctuellement, jamais affichée

### 2.3 Features admin (super-admin)
| Module | État |
|---|---|
| Dashboard global | ✅ |
| Liste tenants + création / édition / toggle / reset password / trial / activation | ✅ |
| Historique connexions | ✅ |
| Configuration Polar.sh (mode, credentials, produits) | ✅ UI moderne livrée |
| Tickets support (vue admin) | ✅ |

---

## 3. Architecture actuelle

### 3.1 Pile technique
- **Langage :** PHP 8.2, procédural orienté objet, pas de framework (Laravel / Symfony)
- **DB :** MySQL 8, PDO brut (pas d'ORM, pas de query builder)
- **Front :** PHP templates server-rendered, 1 × `app.css` (1 278 L), 1 × `app.js` (178 L), pas de bundler, pas de framework JS
- **Deploy :** Docker Compose (prod = `docker-compose.prod.yml` + `.env.prod`), nginx + php-fpm + mysql
- **Paiement :** Polar.sh (Standard Webhooks)

### 3.2 Isolation multi-tenant
- **Base master** (`polar_config`, `tenants`, `tickets`, `tenant_log`, `login_logs`)
- **Base par tenant** (1 DB par slug : `gestion_commerciale_<slug>`), provisionnée via `schema.sql` au moment de l'inscription
- **Routing** : premier segment d'URL (`/<slug>/...`) sélectionne la DB
- **Risque :** pas de limite de tenants configurée — `Tenant::provision()` exécute `CREATE DATABASE` à l'inscription, aucune quota, aucun monitoring.

### 3.3 Couches (ou absence de)
```
config/         → app.php, database.php (constantes)
core/           → Tenant, Router, Controller, Polar, Lang, GeoCurrency, LoginLog, helpers
controllers/    → 15 controllers tenant + 1 admin
views/          → templates PHP par feature
models/         → ⚠️ VIDE
api/            → ⚠️ VIDE
database/       → schemas SQL + 9 scripts de migration standalone
```

**Constat :** pas de service layer, pas de repository, pas de DTO. La logique métier vit dans les controllers. Les migrations sont des scripts PHP autonomes gated par une clé URL (`?key=gestionpro-migrate`) — pas de framework de migration (Phinx / Doctrine).

---

## 4. Schéma de données

### 4.1 Master DB (polar_config, tenants, etc.)
- Singleton `polar_config` (id=1) : design correct, deux profils sandbox/live cohabitent.
- `tenants` : contient plan, trial dates, Polar IDs, `subscription_status`, `owner_email`. Solide.

### 4.2 Tenant DB (schema.sql, 16 tables)
**Points forts :**
- Clés primaires UUID string partout (portabilité)
- Index corrects sur `reference`, `barcode`, `customer_id`, `invoice_id`
- Moteur InnoDB (transactions OK)

**Points faibles :**
- `payment.method` ENUM contient **`bankily`, `masrivi`, `sedad`** — méthodes de paiement mauritaniennes, inutiles en USD-only
- `products.tax_rate DEFAULT 16` — ancien taux TVA Mauritanie
- `cash_sessions.notes` hardcodé "DA" (dinar) dans le contrôleur `CaisseController::close()` (L56)
- `debts.type ENUM('receivable','payable')` OK, mais pas d'index composite `(type, status)` → balaye la table pour les KPIs dashboard
- `audit_log` existe mais rarement écrite (scope incohérent)
- `purchase_orders` + `purchase_order_items` : tables mortes (aucun usage)
- Pas de contrainte `FOREIGN KEY` sur `invoice_items.product_id` → référentiel fragile si suppression produit

---

## 5. Dette technique — inventaire priorisé

Sévérité : 🔴 bloquant · 🟠 élevé · 🟡 moyen · 🟢 cosmétique

### 5.1 Correctness / intégrité

| # | Sévérité | Fichier | Problème |
|---|---|---|---|
| 1 | 🔴 | `controllers/CaisseController.php:87-93` | Vérif stock **sans `SELECT FOR UPDATE`** : en POS concurrent (2 caisses, même produit), les 2 ventes passent et le stock devient négatif. |
| 2 | 🟠 | `controllers/CaisseController.php:130` | `UPDATE products SET current_stock = current_stock - ?` sans check atomique : aucun garde-fou contre le sur-vente. |
| 3 | 🟠 | `controllers/InvoiceController.php:pdf()` | Rendu dit "PDF" est en réalité du HTML — impression client via navigateur. Pas de vrai PDF. |
| 4 | 🟠 | `core/Controller.php:84-97` | `paginate()` réécrit la requête via `preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) FROM', $query)` — cassé dès qu'il y a un sous-select ou `SELECT DISTINCT`. |
| 5 | 🟡 | `controllers/DashboardController.php:79-97` | Chart 30 jours = **30 requêtes SQL séparées**. Doit être 1 seule requête avec `GROUP BY DATE(...)`. |
| 6 | 🟡 | `core/Controller.php:60-67` | `generateUUID()` utilise `mt_rand` — non cryptographiquement sûr. OK pour IDs internes, mauvais si un UUID sert jamais de token. |
| 7 | 🟡 | `controllers/ProductController.php:14-17` | Recherche `LIKE %...%` sans index FULLTEXT — OK à petite échelle, deviendra lent à quelques dizaines de milliers de produits. |

### 5.2 Dette résiduelle pré-refonte

| # | Sévérité | Emplacement | Problème |
|---|---|---|---|
| 8 | 🟠 | `database/schema.sql` | ENUM `payment.method` contient `bankily`, `masrivi`, `sedad` — à supprimer. |
| 9 | 🟠 | `database/schema.sql` | `products.tax_rate DEFAULT 16` — devrait être 0 en USD-only. |
| 10 | 🟠 | `controllers/CaisseController.php:56` | `'Ecart: ... DA'` — devise hardcodée dinar algérien. |
| 11 | 🟡 | `admin/*` | Admin panel **entièrement en français** alors que l'app tenant est i18n (3 langues). |
| 12 | 🟡 | Divers | `die()` + texte brut sur erreur (ex. `Controller::requireRole` L52) — pas de page d'erreur stylée. |

### 5.3 Architecture

| # | Sévérité | Problème |
|---|---|---|
| 13 | 🟠 | **Aucun test automatisé.** Pas de PHPUnit, pas de test d'intégration, pas de CI. Tout refactor est manuel. |
| 14 | 🟠 | Logique métier dans les controllers — pas de service layer, impossible de tester sans HTTP. |
| 15 | 🟡 | Dossiers `models/` et `api/` **vides** → intention inachevée. |
| 16 | 🟡 | Pas de framework de migrations — 9 scripts PHP standalone, pas de registre, pas de rollback. |
| 17 | 🟡 | Pas de gestion de dépendances externe côté PHP (`composer.json` absent du projet ?). À vérifier. |
| 18 | 🟡 | Aucun logging structuré (`error_log` uniquement) — pas de Sentry, pas de tracing. |

### 5.4 Frontend

| # | Sévérité | Problème |
|---|---|---|
| 19 | 🟠 | Un seul `app.css` de 1 278 lignes — pas de design system, pas de tokens réutilisables (hors CSS vars), pas de composants. |
| 20 | 🟡 | Un seul `app.js` de 178 lignes — rien de modulaire. POS, dashboard, forms partagent 1 fichier. |
| 21 | 🟡 | Pas de minification, pas de hash pour cache busting (asset() renvoie un path statique). |
| 22 | 🟡 | Aucun framework JS — chaque écran réinvente les interactions (modals, toasts). |

### 5.5 Sécurité

| # | Sévérité | Problème |
|---|---|---|
| 23 | 🟠 | Pas de **rate limiting** sur `/login`, `/register`, `/pay/start`, webhook. |
| 24 | 🟡 | Scripts migration accessibles via URL gated par clé en clair (`gestionpro-migrate`) — OK mais ad-hoc. |
| 25 | 🟡 | `generateUUID` non crypto-sûr (cf. #6). |
| 26 | 🟢 | CSRF vérifié partout (bien). SQLi protégé par prepared statements partout (bien). |

---

## 6. Frictions UX identifiées (observations code / views)

- **Onboarding unique**, pas de tour guidé, pas de seed data. Le user arrive sur un dashboard vide sans pédagogie.
- **POS**, pas de raccourcis clavier, pas de scanner code-barres natif, fetch produit à chaque interaction.
- **Factures**, pas d'envoi email, pas de signature électronique, pas de templates multiples.
- **Dashboard**, 1 seule vue, pas de personnalisation, pas de drill-down.
- **Paramètres**, page unique monolithique, pas d'organisation par onglets.
- **Admin i18n/l10n absent**, un admin non-francophone ne peut pas utiliser le panneau.
- **Rapports/exports** : inexistants — pas d'export CSV/Excel, pas de rapports périodiques.
- **Mobile** : responsive CSS présent mais non testé système, pas de PWA.

---

## 7. Limites de scalabilité

### 7.1 Volumétrie par tenant
- Stock check via `SELECT WHERE is_active=1 AND current_stock > 0` (chargé intégralement dans la page POS) → **plantera dès quelques milliers de SKUs.**
- Recherche produits `LIKE %...%` → sans FULLTEXT devient lent.
- Pas de pagination côté POS — tous les produits sont envoyés au navigateur.

### 7.2 Nombre de tenants
- 1 DB MySQL par tenant → à 1 000 tenants, 1 000 bases = limite MySQL pratique (open_files, connexions).
- Pas de sharding prévu.
- `Tenant::getMasterDB()` et `getDB()` instancient des PDO à chaque requête HTTP, pas de pool.

### 7.3 Équipe / maintenabilité
- Pas de tests = chaque changement nécessite QA manuelle.
- Controllers qui cumulent HTTP + SQL + règles métier = difficile de paralléliser le travail.
- Pas de typage strict côté data (ni DTO, ni types PHP forts dans les rows PDO).

---

## 8. Ce qui fonctionne bien (à préserver)

- **Structure de routes claire** (Router.php minimaliste, lisible).
- **Séparation master / tenant DB** bien pensée.
- **Standard Webhooks** verify robuste (3 candidates keys après bug prod).
- **Intégration Polar complète** (checkout, cancel, webhook, admin UI).
- **Onboarding tenant automatique** (provision DB + schema à l'inscription).
- **Sessions propres** (`$_SESSION['tenant_slug']` vérifié dans `requireAuth`).
- **CSRF partout** + prepared statements.
- **Tickets support intégrés** avec badge de compteur dans l'admin.
- **Design admin** refondu récemment (cohérent, moderne).

---

## 9. Recommandations V2 — chantiers priorisés

### Lot 0 — Stabilisation (avant tout nouveau dev)
| Priorité | Chantier | Impact |
|---|---|---|
| P0 | Verrouillage stock avec `SELECT FOR UPDATE` sur POS | évite sur-vente |
| P0 | Mise en place PHPUnit + tests d'intégration minimum sur POS et facturation | filet refactor |
| P0 | Nettoyage dette MR (ENUMs paiement, tax_rate, "DA") | cohérence USD-only |
| P0 | Logging structuré (Sentry ou équivalent) | observabilité prod |

### Lot 1 — Valeur produit immédiate
| Priorité | Chantier | Impact |
|---|---|---|
| P1 | Vrai rendu PDF factures (dompdf / mpdf) + envoi email | feature demandée |
| P1 | Exports CSV / Excel (ventes, produits, clients) | rapport comptable |
| P1 | Traduction admin panel (i18n cohérent app+admin) | ouverture internationale |
| P1 | Dashboard : 1 requête `GROUP BY DATE` au lieu de 30 | performance |

### Lot 2 — Qualité architecture
| Priorité | Chantier | Impact |
|---|---|---|
| P2 | Introduire une service layer (UseCases testables) | vélocité feature |
| P2 | Framework de migrations (Phinx) + versioning | déploiements sûrs |
| P2 | Composer + autoload PSR-4 si absent | hygiène PHP |
| P2 | Split frontend : design system (tokens + composants) | capitalisation UI |

### Lot 3 — Scale & différenciation
| Priorité | Chantier | Impact |
|---|---|---|
| P3 | Activer `product_variants` en UI | différenciation POS |
| P3 | Achats / bons de commande (tables déjà prêtes) | feature payante |
| P3 | Pagination / lazy-load POS | scalabilité catalogue |
| P3 | API REST publique (dossier `api/` à activer) | intégrations, webhooks sortants |
| P3 | PWA mobile POS | terrain |

---

## 10. Questions à trancher avant la discovery

1. **Positionnement V2 :** on garde "gestion commerciale PME" généraliste, ou on niche (POS retail ? restauration ? B2B services ?) ?
2. **Stack :** on reste PHP vanilla ou on migre vers Laravel (beaucoup de Lot 2 viennent "gratuits" avec le framework) ?
3. **Multi-tenant :** on garde 1 DB/tenant ou on passe en schéma partagé avec `tenant_id` (meilleur scaling, plus simple à opérer) ?
4. **Pricing V2 :** mêmes tiers Starter/Pro/Enterprise ou restructuration ?
5. **Équipe :** quelle taille d'équipe tech V2 ? (détermine si on peut se permettre une refonte framework ou non)

---

## 11. Livrable suivant suggéré

À partir de cet audit, deux directions possibles :

- **Option A — Discovery produit** : benchmark concurrents (Odoo Community, Wafeq, Dolibarr, Zoho Books, Sage Online, Infinity POS, Loyverse) + entretiens utilisateurs actuels des 4 tenants, pour prioriser les Lots 1+3.
- **Option B — Plan de stabilisation technique** : exécuter Lot 0 en 2–3 sprints avant discovery, pour éviter que les incidents prod ne polluent la V2.

**Recommandation :** exécuter **Lot 0 en parallèle** de la Discovery — Lot 0 n'ajoute pas de features, ne bloque pas la Discovery, mais rend la V2 réalisable.
