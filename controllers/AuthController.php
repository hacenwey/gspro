<?php
class AuthController extends Controller {

    public function loginForm(): void {
        // Check if user is already logged in for THIS tenant
        if (isset($_SESSION['user_id']) && $this->isSameTenant()) {
            $this->redirect(roleHome()); // cashiers have no dashboard access
        }
        require APP_ROOT . '/views/auth/login.php';
    }

    public function login(): void {
        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($username) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
            require APP_ROOT . '/views/auth/login.php';
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        $tenantInfo = Tenant::current();
        $logBase = [
            'tenant_id'   => $tenantInfo['id'] ?? null,
            'tenant_slug' => Tenant::slug(),
            'actor_type'  => 'tenant_user',
            'username'    => $username,
        ];

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Store tenant slug in session to scope login
            if (class_exists('Tenant') && Tenant::slug()) {
                $_SESSION['tenant_slug'] = Tenant::slug();
            }

            // Update last login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            LoginLog::record($logBase + ['user_id' => $user['id'], 'success' => true]);

            $this->redirect(roleHome()); // cashiers have no dashboard access
        } else {
            LoginLog::record($logBase + ['user_id' => $user['id'] ?? null, 'success' => false]);

            $error = "Identifiants incorrects.";
            require APP_ROOT . '/views/auth/login.php';
        }
    }

    public function logout(): void {
        $slug = $_SESSION['tenant_slug'] ?? null;
        session_destroy();
        if ($slug) {
            header('Location: ' . APP_BASE . '/' . $slug . '/login');
        } else {
            header('Location: ' . APP_BASE . '/');
        }
        exit;
    }

    /**
     * Check if the session belongs to the current tenant.
     */
    private function isSameTenant(): bool {
        if (!class_exists('Tenant') || !Tenant::slug()) return true;
        return ($_SESSION['tenant_slug'] ?? '') === Tenant::slug();
    }
}
