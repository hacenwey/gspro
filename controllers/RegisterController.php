<?php
/**
 * Public self-registration controller.
 * Allows clients to create their own account from the landing page.
 */
class RegisterController {

    public function register(): void {
        header('Content-Type: application/json');

        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_POST['slug'] ?? '')));
        $companyName = trim($_POST['company_name'] ?? '');
        $ownerName = trim($_POST['owner_name'] ?? '');
        $email = trim($_POST['owner_email'] ?? '');
        $phone = trim($_POST['owner_phone'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        $errors = [];
        if (strlen($slug) < 3) $errors[] = 'Le slug doit contenir au moins 3 caracteres.';
        if (strlen($slug) > 30) $errors[] = 'Le slug ne doit pas depasser 30 caracteres.';
        if (in_array($slug, ['admin', 'api', 'public', 'database', 'register', 'www', 'app', 'mail'])) {
            $errors[] = 'Ce nom est reserve.';
        }
        if (empty($companyName)) $errors[] = 'Le nom de l\'entreprise est requis.';
        if (empty($ownerName)) $errors[] = 'Votre nom est requis.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caracteres.';

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
            return;
        }

        try {
            $result = Tenant::provision([
                'slug' => $slug,
                'company_name' => $companyName,
                'owner_name' => $ownerName,
                'owner_email' => $email,
                'owner_phone' => $phone,
                'admin_username' => 'admin',
                'admin_password' => $password,
                'plan' => 'starter',
            ]);

            echo json_encode([
                'success' => true,
                'url' => $result['url'],
                'slug' => $result['slug'],
                'username' => $result['admin_username'],
            ]);
        } catch (RuntimeException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
