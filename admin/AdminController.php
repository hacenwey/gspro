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

        $flash = $this->getFlash();
        $this->render('dashboard', compact('totalTenants', 'activeTenants', 'recentTenants', 'recentLog', 'flash'));
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
