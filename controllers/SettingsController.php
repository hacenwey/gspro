<?php
class SettingsController extends Controller {

    public function index(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);

        $settings = [];
        foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $users = $this->db->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY created_at")->fetchAll();

        // Subscription info (master DB, per-tenant row)
        $tenant = Tenant::current();
        $subscription = [
            'plan'       => $tenant['plan'] ?? 'starter',
            'status'     => $tenant['subscription_status'] ?? 'trial',
            'paid_until' => $tenant['subscription_paid_until'] ?? null,
            'trial_ends' => $tenant['trial_ends_at'] ?? null,
            'polar_sub'  => $tenant['polar_subscription_id'] ?? null,
        ];

        $this->render('settings/index', [
            'pageTitle'    => 'Parametres',
            'settings'     => $settings,
            'users'        => $users,
            'subscription' => $subscription,
        ]);
    }

    // ==================== API Tokens ====================

    public function apiTokens(): void {
        $this->requireRole([ROLE_ADMIN]);
        $tokens = $this->db->query("
            SELECT t.id, t.name, t.last_used_at, t.expires_at, t.created_at, t.revoked_at, u.full_name AS issued_to
            FROM api_tokens t JOIN users u ON u.id = t.user_id
            ORDER BY t.created_at DESC
        ")->fetchAll();

        $newToken = $_SESSION['new_api_token'] ?? null;
        unset($_SESSION['new_api_token']);

        $this->render('settings/api_tokens', [
            'pageTitle' => 'Jetons API',
            'tokens'    => $tokens,
            'newToken'  => $newToken,
        ]);
    }

    public function createApiToken(): void {
        $this->requireRole([ROLE_ADMIN]);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/settings/api-tokens'); }
        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->flash('error', 'Nom requis.');
            $this->redirect('/settings/api-tokens');
        }
        $expiresDays = (int)$this->input('expires_days', 365);
        $expiresAt = $expiresDays > 0 ? date('Y-m-d H:i:s', strtotime("+$expiresDays days")) : null;

        $auth = new \App\Api\TokenAuth($this->db);
        $raw = $auth->issue($_SESSION['user_id'], $name, $expiresAt);
        $_SESSION['new_api_token'] = $raw;
        $this->flash('success', 'Token cree. Copiez-le maintenant — il ne sera plus affiche.');
        $this->redirect('/settings/api-tokens');
    }

    public function revokeApiToken(string $id): void {
        $this->requireRole([ROLE_ADMIN]);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/settings/api-tokens'); }
        (new \App\Api\TokenAuth($this->db))->revoke($id);
        $this->flash('success', 'Token revoque.');
        $this->redirect('/settings/api-tokens');
    }

    public function update(): void {
        $this->requireRole([ROLE_ADMIN]);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/settings'); }

        $keys = ['company_name', 'company_address', 'company_phone', 'company_email', 'company_tax_id', 'default_tax_rate', 'currency', 'currency_symbol', 'default_payment_terms', 'default_quote_validity'];

        foreach ($keys as $key) {
            $value = $this->input($key, '');
            $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$key, $value, $value]);
        }

        // Language is whitelisted separately: an unknown code would leave the app
        // with no translations. Also applied to the current session so the change
        // is visible right away rather than only after the next login.
        $lang = $this->input('language', '');
        if (in_array($lang, SUPPORTED_LANGS, true)) {
            $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('language', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$lang, $lang]);
            $_SESSION['lang'] = $lang;
        }

        // Business vertical: whitelisted like the language — an unknown value would
        // silently disable the module it gates.
        $biz = $this->input('business_type', '');
        if (in_array($biz, BUSINESS_TYPES, true)) {
            $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('business_type', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$biz, $biz]);
        }

        Settings::flush(); // cached for the request — drop it so the redirect reads fresh values
        $this->flash('success', 'Parametres mis a jour.');
        $this->redirect('/settings');
    }

    public function storeUser(): void {
        $this->requireRole([ROLE_ADMIN]);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/settings'); }

        $password = $this->input('password');
        if (strlen($password) < 6) {
            $this->flash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');
            $this->redirect('/settings');
        }

        $stmt = $this->db->prepare("INSERT INTO users (id, username, email, password_hash, full_name, role) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $this->generateUUID(),
            trim($this->input('username')),
            trim($this->input('email')),
            password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            trim($this->input('full_name')),
            $this->input('role', 'cashier')
        ]);

        $this->flash('success', 'Utilisateur cree.');
        $this->redirect('/settings');
    }
}
