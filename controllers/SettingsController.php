<?php
class SettingsController extends Controller {

    public function index(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);

        $settings = [];
        foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $users = $this->db->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM users ORDER BY created_at")->fetchAll();

        $this->render('settings/index', [
            'pageTitle' => 'Parametres',
            'settings' => $settings,
            'users' => $users
        ]);
    }

    public function update(): void {
        $this->requireRole([ROLE_ADMIN]);
        if (!verify_csrf()) { $this->flash('error', 'Token invalide'); $this->redirect('/settings'); }

        $keys = ['company_name', 'company_address', 'company_phone', 'company_email', 'company_tax_id', 'default_tax_rate', 'currency', 'currency_symbol', 'default_payment_terms', 'default_quote_validity'];

        foreach ($keys as $key) {
            $value = $this->input($key, '');
            $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$key, $value, $value]);
        }

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
