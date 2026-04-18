<?php
/**
 * Public self-registration controller.
 *
 * Card required up-front for every signup. Tenant is created with
 * subscription_status = 'pending_payment' (no app access yet). A Polar
 * checkout is created and the client JSON response carries a
 * `checkout_url` so the browser redirects into Polar. The 7-day trial +
 * recurring charge are managed by Polar; the webhook flips the tenant to
 * 'active' once Polar confirms.
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
        // $_POST['plan'] can be either a legacy slug (starter/pro/enterprise)
        // or a Polar product UUID coming from the dynamic landing cards.
        $rawPlan = trim($_POST['plan'] ?? 'starter');
        $isUuid = (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $rawPlan);

        $productName = null;
        if ($isUuid) {
            // UUID path: validate against live Polar products and pick a sane
            // tenants.plan tier (enum column) based on monthly-equivalent price.
            $productId = null;
            foreach (Polar::listProductsCached() as $p) {
                if (($p['id'] ?? '') === $rawPlan) {
                    $productId   = $rawPlan;
                    $productName = (string)($p['name'] ?? '');
                    $price = ($p['prices'][0] ?? [])['price_amount'] ?? 0;
                    $interval = ($p['recurring_interval'] ?? 'month');
                    $monthly = $interval === 'year' ? ($price / 12) : $price;
                    $plan = $monthly <= 2000 ? 'starter' : ($monthly <= 5000 ? 'pro' : 'enterprise');
                    break;
                }
            }
            if (!$productId) $plan = 'starter';
        } else {
            $plan = strtolower($rawPlan);
            if (!in_array($plan, ['starter', 'pro', 'enterprise'], true)) {
                $plan = 'starter';
            }
            $productId = Polar::productIdForPlan($plan);
        }

        $errors = [];
        if (strlen($slug) < 3) $errors[] = 'Slug must be at least 3 characters.';
        if (strlen($slug) > 30) $errors[] = 'Slug must be at most 30 characters.';
        if (in_array($slug, ['admin', 'api', 'public', 'database', 'register', 'www', 'app', 'mail', 'webhook', 'pay'])) {
            $errors[] = 'This name is reserved.';
        }
        if (empty($companyName)) $errors[] = 'Company name is required.';
        if (empty($ownerName)) $errors[] = 'Your name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';

        if (!Polar::isConfigured() || !$productId) {
            $errors[] = 'Payment provider is not available. Please try again later.';
        }

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
                'plan' => $plan,
                'subscription_status' => 'pending_payment',
            ]);
        } catch (RuntimeException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        try {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'gestionpro.it.com';
            $successUrl = $scheme . '://' . $host . APP_BASE . '/' . $result['slug'] . '/pay/success?checkout_id={CHECKOUT_ID}';

            $checkout = Polar::createCheckout([
                'products'       => [$productId],
                'customer_email' => $email,
                'customer_name'  => $ownerName,
                'success_url'    => $successUrl,
                'metadata'       => array_filter([
                    'tenant_id'   => (string)$result['tenant_id'],
                    'tenant_slug' => (string)$result['slug'],
                    'plan'        => $plan,
                    'product_id'  => $productId,
                    'product_name' => $productName,
                ], static fn($v) => $v !== null && $v !== ''),
            ]);
        } catch (Throwable $e) {
            error_log('Polar register checkout failed: ' . $e->getMessage());
            echo json_encode([
                'success'      => true,
                'mode'         => 'pending_payment',
                'slug'         => $result['slug'],
                'url'          => APP_BASE . '/' . $result['slug'] . '/pay',
                'username'     => $result['admin_username'],
                'warning'      => 'Your workspace was created but the checkout could not be opened. Retry from your workspace.',
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
