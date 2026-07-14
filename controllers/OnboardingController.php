<?php
class OnboardingController extends Controller {

    public function index(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);
        $done = $this->db->query("SELECT setting_value FROM settings WHERE setting_key = 'onboarding_done'")->fetchColumn();
        if ($done === '1') { $this->redirect('/dashboard'); }

        $settings = [];
        foreach ($this->db->query("SELECT * FROM settings")->fetchAll() as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }

        $this->render('onboarding/index', [
            'pageTitle' => __('onboarding.welcome'),
            'settings' => $settings
        ]);
    }

    public function saveCompany(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);
        if (!verify_csrf()) { $this->json(['error' => 'Token invalide'], 403); }

        $keys = ['company_name', 'company_address', 'company_phone', 'company_tax_id'];
        foreach ($keys as $key) {
            $value = $this->input($key, '');
            $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$key, $value, $value]);
        }

        // Mark onboarding as done
        $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('onboarding_done', '1') ON DUPLICATE KEY UPDATE setting_value = '1'")
            ->execute();

        $this->json(['success' => true]);
    }

    public function skip(): void {
        $this->requireRole([ROLE_ADMIN, ROLE_MANAGER]);
        $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('onboarding_done', '1') ON DUPLICATE KEY UPDATE setting_value = '1'")
            ->execute();
        $this->redirect('/dashboard');
    }
}
