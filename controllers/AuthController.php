<?php
class AuthController extends Controller {

    public function loginForm(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
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

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Update last login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            $this->redirect('/dashboard');
        } else {
            $error = "Identifiants incorrects.";
            require APP_ROOT . '/views/auth/login.php';
        }
    }

    public function logout(): void {
        session_destroy();
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}
