<?php
/**
 * Super Admin Controller
 * Manages tenants (client installations).
 */
class AdminController {
    private PDO $db;

    public function __construct() {
        $this->db = Tenant::getMasterDB();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        $content = APP_ROOT . '/admin/views/' . $view . '.php';
        if (!file_exists($content)) {
            die("Admin view not found: $view");
        }
        require APP_ROOT . '/admin/views/layout.php';
    }

    private function redirect(string $path): void {
        header('Location: ' . adminUrl($path));
        exit;
    }

    private function flash(string $type, string $message): void {
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
    }

    private function getFlash(): ?array {
        if (isset($_SESSION['admin_flash'])) {
            $f = $_SESSION['admin_flash'];
            unset($_SESSION['admin_flash']);
            return $f;
        }
        return null;
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['super_admin_id'])) {
            $this->redirect('/login');
        }
    }

    // ===================== AUTH =====================

    public function loginForm(): void {
        if (isset($_SESSION['super_admin_id'])) {
            $this->redirect('/');
        }
        $error = '';
        require APP_ROOT . '/admin/views/login.php';
    }

    public function login(): void {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM super_admins WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        $logBase = [
            'actor_type' => 'super_admin',
            'username'   => $username,
        ];

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['super_admin_id'] = $admin['id'];
            $_SESSION['super_admin_name'] = $admin['full_name'];

            $this->db->prepare("UPDATE super_admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

            LoginLog::record($logBase + ['user_id' => $admin['id'], 'success' => true]);

            $this->redirect('/');
        } else {
            LoginLog::record($logBase + ['user_id' => $admin['id'] ?? null, 'success' => false]);

            $error = 'Identifiants incorrects';
            require APP_ROOT . '/admin/views/login.php';
        }
    }

    public function logout(): void {
        unset($_SESSION['super_admin_id'], $_SESSION['super_admin_name']);
        $this->redirect('/login');
    }

    // ===================== DASHBOARD =====================

    public function dashboard(): void {
        $this->requireAuth();

        $totalTenants = $this->db->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
        $activeTenants = $this->db->query("SELECT COUNT(*) FROM tenants WHERE is_active = 1")->fetchColumn();
        $recentTenants = $this->db->query("SELECT * FROM tenants ORDER BY created_at DESC LIMIT 5")->fetchAll();
        $recentLog = $this->db->query("SELECT tl.*, t.slug, t.company_name FROM tenant_log tl LEFT JOIN tenants t ON t.id = tl.tenant_id ORDER BY tl.created_at DESC LIMIT 10")->fetchAll();

        // Ticket stats
        $openTickets = 0;
        $totalTickets = 0;
        $recentTickets = [];
        try {
            $openTickets = (int)$this->db->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')")->fetchColumn();
            $totalTickets = (int)$this->db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
            $recentTickets = $this->db->query(
                "SELECT t.*, tn.slug, tn.company_name FROM tickets t LEFT JOIN tenants tn ON tn.id = t.tenant_id ORDER BY t.created_at DESC LIMIT 5"
            )->fetchAll();
        } catch (Exception $e) {}

        $flash = $this->getFlash();
        $this->render('dashboard', compact('totalTenants', 'activeTenants', 'recentTenants', 'recentLog', 'openTickets', 'totalTickets', 'recentTickets', 'flash'));
    }

    // ===================== TENANTS CRUD =====================

    public function tenants(): void {
        $this->requireAuth();

        $tenants = Tenant::listAll();
        $flash = $this->getFlash();
        $this->render('tenants', compact('tenants', 'flash'));
    }

    public function createTenant(): void {
        $this->requireAuth();
        $flash = $this->getFlash();
        $this->render('tenant_form', ['tenant' => null, 'flash' => $flash]);
    }

    public function storeTenant(): void {
        $this->requireAuth();

        try {
            $result = Tenant::provision([
                'slug' => $_POST['slug'] ?? '',
                'company_name' => $_POST['company_name'] ?? '',
                'owner_name' => $_POST['owner_name'] ?? '',
                'owner_email' => $_POST['owner_email'] ?? '',
                'owner_phone' => $_POST['owner_phone'] ?? '',
                'admin_username' => $_POST['admin_username'] ?? 'admin',
                'admin_password' => $_POST['admin_password'] ?? 'admin123',
                'plan' => $_POST['plan'] ?? 'starter',
                'address' => $_POST['address'] ?? '',
            ]);

            $this->flash('success', 'Client "' . $result['slug'] . '" cree avec succes! URL: ' . $result['url'] . ' | Identifiants: ' . $result['admin_username'] . ' / ' . $result['admin_password']);
            $this->redirect('/tenants');
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/tenants/create');
        }
    }

    public function editTenant(string $id): void {
        $this->requireAuth();
        $tenant = Tenant::getById($id);
        if (!$tenant) {
            $this->flash('error', 'Client introuvable');
            $this->redirect('/tenants');
        }
        $flash = $this->getFlash();
        $this->render('tenant_form', compact('tenant', 'flash'));
    }

    public function updateTenant(string $id): void {
        $this->requireAuth();

        Tenant::update($id, [
            'company_name' => $_POST['company_name'] ?? '',
            'owner_name' => $_POST['owner_name'] ?? '',
            'owner_email' => $_POST['owner_email'] ?? '',
            'owner_phone' => $_POST['owner_phone'] ?? '',
            'plan' => $_POST['plan'] ?? 'starter',
            'max_users' => (int)($_POST['max_users'] ?? 5),
            'max_products' => (int)($_POST['max_products'] ?? 500),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'expires_at' => $_POST['expires_at'] ?? '',
            'notes' => $_POST['notes'] ?? '',
        ]);

        $this->flash('success', 'Client mis a jour');
        $this->redirect('/tenants');
    }

    public function toggleTenant(string $id): void {
        $this->requireAuth();
        Tenant::toggleActive($id);
        $this->flash('success', 'Statut modifie');
        $this->redirect('/tenants');
    }

    public function deleteTenant(string $id): void {
        $this->requireAuth();
        $tenant = Tenant::getById($id);
        Tenant::delete($id);
        $this->flash('success', 'Client "' . ($tenant['slug'] ?? '') . '" supprime (base de donnees supprimee)');
        $this->redirect('/tenants');
    }

    // ===================== SUPPORT TICKETS =====================

    public function tickets(): void {
        $this->requireAuth();

        $status = $_GET['status'] ?? '';
        $sql = "SELECT t.*, tn.slug, tn.company_name,
                (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as msg_count,
                (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_at
                FROM tickets t
                LEFT JOIN tenants tn ON tn.id = t.tenant_id";
        $params = [];

        if ($status && in_array($status, ['open', 'in_progress', 'waiting', 'resolved', 'closed'])) {
            $sql .= " WHERE t.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY FIELD(t.status, 'open', 'in_progress', 'waiting', 'resolved', 'closed'), t.updated_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        // Counts
        $countRows = $this->db->query("SELECT status, COUNT(*) as cnt FROM tickets GROUP BY status")->fetchAll();
        $counts = ['all' => 0, 'open' => 0, 'in_progress' => 0, 'waiting' => 0, 'resolved' => 0, 'closed' => 0];
        foreach ($countRows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
            $counts['all'] += (int)$row['cnt'];
        }

        $flash = $this->getFlash();
        $this->render('tickets', compact('tickets', 'counts', 'status', 'flash'));
    }

    public function viewTicket(string $id): void {
        $this->requireAuth();

        $stmt = $this->db->prepare(
            "SELECT t.*, tn.slug, tn.company_name, tn.owner_email
             FROM tickets t LEFT JOIN tenants tn ON tn.id = t.tenant_id WHERE t.id = ?"
        );
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            $this->flash('error', 'Ticket introuvable');
            $this->redirect('/tickets');
            return;
        }

        $msgStmt = $this->db->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$id]);
        $messages = $msgStmt->fetchAll();

        $flash = $this->getFlash();
        $this->render('ticket_view', compact('ticket', 'messages', 'flash'));
    }

    public function replyTicket(string $id): void {
        $this->requireAuth();

        $message = trim($_POST['message'] ?? '');
        $newStatus = $_POST['status'] ?? '';

        if (empty($message)) {
            $this->flash('error', 'Le message est requis.');
            $this->redirect('/tickets/view/' . $id);
            return;
        }

        $adminName = $_SESSION['super_admin_name'] ?? 'Admin';

        $msgStmt = $this->db->prepare(
            "INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message) VALUES (?, 'admin', ?, ?)"
        );
        $msgStmt->execute([$id, $adminName, $message]);

        // Update status if provided
        if ($newStatus && in_array($newStatus, ['open', 'in_progress', 'waiting', 'resolved', 'closed'])) {
            $updateSql = "UPDATE tickets SET status = ?";
            $updateParams = [$newStatus];
            if (in_array($newStatus, ['resolved', 'closed'])) {
                $updateSql .= ", closed_at = NOW()";
            } else {
                $updateSql .= ", closed_at = NULL";
            }
            $updateSql .= " WHERE id = ?";
            $updateParams[] = $id;
            $this->db->prepare($updateSql)->execute($updateParams);
        }

        $this->flash('success', 'Reponse envoyee.');
        $this->redirect('/tickets/view/' . $id);
    }

    public function updateTicketStatus(string $id): void {
        $this->requireAuth();

        $newStatus = $_POST['status'] ?? '';
        if (!in_array($newStatus, ['open', 'in_progress', 'waiting', 'resolved', 'closed'])) {
            $this->flash('error', 'Statut invalide.');
            $this->redirect('/tickets');
            return;
        }

        $updateSql = "UPDATE tickets SET status = ?";
        $updateParams = [$newStatus];
        if (in_array($newStatus, ['resolved', 'closed'])) {
            $updateSql .= ", closed_at = NOW()";
        } else {
            $updateSql .= ", closed_at = NULL";
        }
        $updateSql .= " WHERE id = ?";
        $updateParams[] = $id;
        $this->db->prepare($updateSql)->execute($updateParams);

        $statLabel = match($newStatus) {
            'open' => 'Ouvert', 'in_progress' => 'En cours',
            'waiting' => 'En attente', 'resolved' => 'Resolu',
            'closed' => 'Ferme', default => $newStatus
        };
        $this->flash('success', 'Statut mis a jour: ' . $statLabel);
        $this->redirect('/tickets/view/' . $id);
    }

    // ===================== SUBSCRIPTION =====================

    public function activateSubscription(string $id): void {
        $this->requireAuth();
        $months = max(1, (int)($_POST['months'] ?? 1));

        $tenant = Tenant::getById($id);
        if (!$tenant) {
            $this->flash('error', 'Client introuvable');
            $this->redirect('/tenants');
            return;
        }

        Tenant::activateSubscription($id, $months);
        $this->flash('success', 'Abonnement active pour ' . $tenant['slug'] . ' (' . $months . ' mois)');
        $this->redirect('/tenants/edit/' . $id);
    }

    public function extendTrial(string $id): void {
        $this->requireAuth();
        $days = max(1, (int)($_POST['days'] ?? 7));

        $tenant = Tenant::getById($id);
        if (!$tenant) {
            $this->flash('error', 'Client introuvable');
            $this->redirect('/tenants');
            return;
        }

        Tenant::extendTrial($id, $days);
        $this->flash('success', 'Essai prolonge de ' . $days . ' jours pour ' . $tenant['slug']);
        $this->redirect('/tenants/edit/' . $id);
    }

    // ===================== CONNECTIONS =====================

    public function connections(): void {
        $this->requireAuth();

        $tenantFilter = $_GET['tenant'] ?? '';
        $successFilter = $_GET['success'] ?? '';
        $limit = min(500, max(20, (int)($_GET['limit'] ?? 100)));

        $where = [];
        $params = [];
        if ($tenantFilter !== '') {
            $where[] = "l.tenant_id = ?";
            $params[] = $tenantFilter;
        }
        if ($successFilter === '1') {
            $where[] = "l.success = 1";
        } elseif ($successFilter === '0') {
            $where[] = "l.success = 0";
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $logs = $this->db->prepare(
                "SELECT l.*, t.company_name, t.slug AS current_slug
                 FROM login_logs l
                 LEFT JOIN tenants t ON t.id = l.tenant_id
                 $whereSql
                 ORDER BY l.created_at DESC
                 LIMIT $limit"
            );
            $logs->execute($params);
            $logs = $logs->fetchAll();

            $stats = [
                'total'      => (int)$this->db->query("SELECT COUNT(*) FROM login_logs")->fetchColumn(),
                'success'    => (int)$this->db->query("SELECT COUNT(*) FROM login_logs WHERE success = 1")->fetchColumn(),
                'failed'     => (int)$this->db->query("SELECT COUNT(*) FROM login_logs WHERE success = 0")->fetchColumn(),
                'last_24h'   => (int)$this->db->query("SELECT COUNT(*) FROM login_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn(),
                'last_7d'    => (int)$this->db->query("SELECT COUNT(*) FROM login_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
                'unique_ips' => (int)$this->db->query("SELECT COUNT(DISTINCT ip_address) FROM login_logs WHERE ip_address IS NOT NULL AND ip_address != ''")->fetchColumn(),
            ];

            $topTenants = $this->db->query(
                "SELECT l.tenant_id, l.tenant_slug, t.company_name,
                        COUNT(*) AS total_conn,
                        SUM(CASE WHEN l.success = 1 THEN 1 ELSE 0 END) AS ok_conn,
                        MAX(l.created_at) AS last_conn
                 FROM login_logs l
                 LEFT JOIN tenants t ON t.id = l.tenant_id
                 WHERE l.actor_type = 'tenant_user'
                   AND l.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY l.tenant_id, l.tenant_slug, t.company_name
                 ORDER BY total_conn DESC
                 LIMIT 10"
            )->fetchAll();

            $tenantsList = $this->db->query("SELECT id, slug, company_name FROM tenants ORDER BY company_name")->fetchAll();
        } catch (PDOException $e) {
            $this->flash('error', 'Table login_logs introuvable. Executez database/migrate_login_logs.php');
            $logs = [];
            $stats = ['total'=>0,'success'=>0,'failed'=>0,'last_24h'=>0,'last_7d'=>0,'unique_ips'=>0];
            $topTenants = [];
            $tenantsList = [];
        }

        $flash = $this->getFlash();
        $this->render('connections', compact('logs', 'stats', 'topTenants', 'tenantsList', 'tenantFilter', 'successFilter', 'limit', 'flash'));
    }

    // ===================== RESET PASSWORD =====================

    public function resetPassword(string $id): void {
        $this->requireAuth();

        $tenant = Tenant::getById($id);
        if (!$tenant) {
            $this->flash('error', 'Client introuvable');
            $this->redirect('/tenants');
            return;
        }

        $newPassword = $_POST['new_password'] ?? '';
        $userId = $_POST['user_id'] ?? '';

        if (strlen($newPassword) < 6) {
            $this->flash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');
            $this->redirect('/tenants/edit/' . $id);
            return;
        }

        try {
            // Connect to tenant's database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . $tenant['db_name'] . ";charset=" . DB_CHARSET;
            $tenantDb = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            if ($userId) {
                // Reset specific user
                $stmt = $tenantDb->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                $user = $tenantDb->prepare("SELECT username FROM users WHERE id = ?");
                $user->execute([$userId]);
                $username = $user->fetchColumn() ?: 'inconnu';
            } else {
                // Reset first admin user
                $stmt = $tenantDb->prepare("UPDATE users SET password_hash = ? WHERE role = 'admin' ORDER BY created_at ASC LIMIT 1");
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT)]);
                $username = 'admin';
            }

            // Log the action
            $logStmt = $this->db->prepare("INSERT INTO tenant_log (tenant_id, admin_id, action, details) VALUES (?, ?, 'reset_password', ?)");
            $logStmt->execute([$id, $_SESSION['super_admin_id'], "Mot de passe reinitialise pour: $username"]);

            $this->flash('success', 'Mot de passe de "' . $username . '" reinitialise avec succes pour ' . $tenant['slug']);
            $this->redirect('/tenants/edit/' . $id);
        } catch (Exception $e) {
            $this->flash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('/tenants/edit/' . $id);
        }
    }

    // ===================== POLAR =====================

    public function polar(): void {
        $this->requireAuth();
        $this->db->exec("INSERT IGNORE INTO polar_config (id) VALUES (1)");
        $cfg = $this->db->query("SELECT * FROM polar_config WHERE id = 1")->fetch() ?: [];

        $products = [];
        $productsError = '';
        if (Polar::isConfigured()) {
            try {
                $products = Polar::listProducts(100);
            } catch (Throwable $e) {
                $productsError = $e->getMessage();
            }
        }

        $flash = $this->getFlash();
        $this->render('polar', compact('cfg', 'products', 'productsError', 'flash'));
    }

    public function savePolar(): void {
        $this->requireAuth();
        $mode = ($_POST['mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';

        $fields = [
            'sandbox_access_token', 'sandbox_webhook_secret',
            'sandbox_starter_product_id', 'sandbox_pro_product_id', 'sandbox_enterprise_product_id',
            'live_access_token', 'live_webhook_secret',
            'live_starter_product_id', 'live_pro_product_id', 'live_enterprise_product_id',
        ];
        $set  = ['mode = ?'];
        $vals = [$mode];
        foreach ($fields as $f) {
            if (array_key_exists($f, $_POST)) {
                $v = trim((string)$_POST[$f]);
                // empty secret/token submission = keep existing value (masked display)
                if ($v === '' && (str_ends_with($f, '_access_token') || str_ends_with($f, '_webhook_secret'))) {
                    continue;
                }
                $set[]  = "$f = ?";
                $vals[] = $v;
            }
        }
        $vals[] = 1;
        $this->db->prepare("UPDATE polar_config SET " . implode(', ', $set) . " WHERE id = ?")->execute($vals);

        Polar::resetCache();
        $this->flash('success', 'Configuration Polar enregistree (mode = ' . $mode . ').');
        $this->redirect('/polar');
    }

    public function createPolarProduct(): void {
        $this->requireAuth();
        $name     = trim((string)($_POST['name'] ?? ''));
        $price    = (float)($_POST['price_usd'] ?? 0);
        $interval = ($_POST['interval'] ?? 'month') === 'year' ? 'year' : 'month';

        if ($name === '' || $price <= 0) {
            $this->flash('error', 'Nom et prix requis.');
            $this->redirect('/polar');
        }
        try {
            $p = Polar::createProduct($name, (int)round($price * 100), $interval);
            $this->flash('success', 'Produit cree : ' . $p['name'] . ' (' . $p['id'] . ')');
        } catch (Throwable $e) {
            $this->flash('error', 'Erreur Polar : ' . $e->getMessage());
        }
        $this->redirect('/polar');
    }

    public function archivePolarProduct(string $id): void {
        $this->requireAuth();
        try {
            Polar::archiveProduct($id);
            $this->flash('success', 'Produit archive.');
        } catch (Throwable $e) {
            $this->flash('error', 'Erreur Polar : ' . $e->getMessage());
        }
        $this->redirect('/polar');
    }

    public function assignPolarProduct(): void {
        $this->requireAuth();
        $plan      = ($_POST['plan'] ?? '') === 'pro' ? 'pro' : (($_POST['plan'] ?? '') === 'enterprise' ? 'enterprise' : 'starter');
        $productId = trim((string)($_POST['product_id'] ?? ''));
        $mode      = ($_POST['mode'] ?? 'sandbox') === 'live' ? 'live' : 'sandbox';

        $col = $mode . '_' . $plan . '_product_id';
        $this->db->prepare("UPDATE polar_config SET $col = ? WHERE id = 1")->execute([$productId]);
        Polar::resetCache();
        $this->flash('success', strtoupper($plan) . ' assigne a ' . $productId . ' (' . $mode . ').');
        $this->redirect('/polar');
    }
}
