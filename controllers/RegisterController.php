<?php
/**
 * Public self-registration controller.
 *
 * Two signup flows depending on geo-detected currency:
 *   - USD tenants: card required up-front. Tenant is created with subscription_status
 *                  'pending_payment' (no app access yet), a Polar checkout is created
 *                  with customer_email/metadata, and the client JSON response carries
 *                  a `checkout_url` so the browser redirects into Polar. The 7-day free
 *                  trial + charge are managed by Polar; webhook flips the tenant to
 *                  'active' once Polar confirms.
 *   - MRU tenants: 7-day in-app trial, no card (no automated rail). After the trial
 *                  they see the paywall with manual-pay contact buttons.
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
        $plan = strtolower(trim($_POST['plan'] ?? 'starter'));
        if (!in_array($plan, ['starter', 'pro', 'enterprise'], true)) {
            $plan = 'starter';
        }

        // Validation
        $errors = [];
        if (strlen($slug) < 3) $errors[] = 'Le slug doit contenir au moins 3 caracteres.';
        if (strlen($slug) > 30) $errors[] = 'Le slug ne doit pas depasser 30 caracteres.';
        if (in_array($slug, ['admin', 'api', 'public', 'database', 'register', 'www', 'app', 'mail', 'webhook', 'pay'])) {
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

        // Decide billing rail from geo currency (USD -> Polar, MRU -> manual).
        $geo = GeoCurrency::detect();
        $usePolar = ($geo['currency'] === 'USD')
            && Polar::isConfigured()
            && Polar::productIdForPlan($plan);

        try {
            $result = Tenant::provision([
                'slug' => $slug,
                'company_name' => $companyName,
                'owner_name' => $ownerName,
                'owner_email' => $email,
                'owner_phone' => $phone,
                'admin_username' => 'admin',
                'admin_password' => $password,
                'plan' => $plan,
                // USD signups must pay before getting access.
                'subscription_status' => $usePolar ? 'pending_payment' : 'trial',
            ]);
        } catch (RuntimeException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        if (!$usePolar) {
            echo json_encode([
                'success'  => true,
                'url'      => $result['url'],
                'slug'     => $result['slug'],
                'username' => $result['admin_username'],
                'mode'     => 'trial',
            ]);
            return;
        }

        // USD path: create Polar checkout and hand the URL back so the browser redirects in.
        try {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'gestionpro.it.com';
            $successUrl = $scheme . '://' . $host . APP_BASE . '/' . $result['slug'] . '/pay/success?checkout_id={CHECKOUT_ID}';

            $checkout = Polar::createCheckout([
                'products'       => [Polar::productIdForPlan($plan)],
                'customer_email' => $email,
                'customer_name'  => $ownerName,
                'success_url'    => $successUrl,
                'metadata'       => [
                    'tenant_id'   => (string)$result['tenant_id'],
                    'tenant_slug' => (string)$result['slug'],
                    'plan'        => $plan,
                ],
            ]);
        } catch (Throwable $e) {
            // Checkout creation failed — tenant is already provisioned as pending_payment.
            // User can retry from /{slug}/pay/start.
            error_log('Polar register checkout failed: ' . $e->getMessage());
            echo json_encode([
                'success'      => true,
                'mode'         => 'pending_payment',
                'slug'         => $result['slug'],
                'url'          => APP_BASE . '/' . $result['slug'] . '/pay',
                'username'     => $result['admin_username'],
                'warning'      => 'Votre espace est cree. Impossible d\'ouvrir le paiement maintenant, reessayez depuis votre espace.',
            ]);
            return;
        }

        echo json_encode([
            'success'      => true,
            'mode'         => 'polar_checkout',
            'slug'         => $result['slug'],
            'username'     => $result['admin_username'],
            'checkout_url' => $checkout['url'],
            'url'          => APP_BASE . '/' . $result['slug'] . '/pay/success',
        ]);
    }
}
