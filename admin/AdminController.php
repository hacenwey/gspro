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

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['super_admin_id'] = $admin['id'];
            $_SESSION['super_admin_name'] = $admin['full_name'];

            $this->db->prepare("UPDATE super_admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            $this->redirect('/');
        } else {
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
}
