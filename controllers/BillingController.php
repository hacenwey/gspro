<?php
/**
 * BillingController - SaaS subscription flow via Polar.sh.
 *
 * - startCheckout(plan) : creates a Polar checkout session and redirects the tenant owner to pay.
 * - success()           : post-payment landing inside the tenant (webhook is source of truth; this just shows a message).
 * - webhook()           : receives Polar events (subscription.active, subscription.canceled, order.paid) and
 *                         updates the master DB accordingly. Called at /webhook/polar (global, no tenant slug).
 *
 * NOTE: this controller does not extend Controller to avoid tenant-DB coupling — all state lives in the master DB.
 */
class BillingController {

    /** Create a Polar checkout for a given plan and redirect the user. */
    public function startCheckout(): void {
        $tenant = Tenant::current();
        if (!$tenant) {
            http_response_code(404);
            echo 'Tenant introuvable'; return;
        }

        $plan = strtolower(trim($_POST['plan'] ?? $_GET['plan'] ?? ''));
        if (!in_array($plan, ['starter', 'pro', 'enterprise'], true)) {
            http_response_code(400);
            echo 'Plan invalide'; return;
        }

        if (!Polar::isConfigured()) {
            http_response_code(503);
            echo 'Le paiement par carte n\'est pas encore active. Contactez-nous via WhatsApp.'; return;
        }

        $productId = Polar::productIdForPlan($plan);
        if (!$productId) {
            http_response_code(503);
            echo 'Produit Polar non configure pour ce plan.'; return;
        }

        $successUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . APP_BASE . '/' . $tenant['slug'] . '/pay/success?checkout_id={CHECKOUT_ID}';

        try {
            $checkout = Polar::createCheckout([
                'products'       => [$productId],
                'customer_email' => $tenant['owner_email'] ?? null,
                'customer_name'  => $tenant['owner_name']  ?? null,
                'success_url'    => $successUrl,
                'metadata'       => [
                    'tenant_id'   => (string)$tenant['id'],
                    'tenant_slug' => (string)$tenant['slug'],
                    'plan'        => $plan,
                ],
            ]);
        } catch (Throwable $e) {
            \App\Logging\Logger::exception($e, ['op' => 'polar_checkout_start', 'plan' => $plan, 'tenant_id' => $tenant['id'] ?? null]);
            http_response_code(502);
            echo 'Impossible de creer la session de paiement. Reessayez dans une minute.'; return;
        }

        header('Location: ' . $checkout['url']);
        exit;
    }

    /** Post-checkout landing. Shows pending-activation message until the webhook arrives. */
    public function success(): void {
        $tenant = Tenant::current();
        if (!$tenant) { http_response_code(404); echo 'Tenant introuvable'; return; }

        // Re-fetch tenant with subscription state (may already be active if webhook raced ahead)
        $db = Tenant::getMasterDB();
        $stmt = $db->prepare("SELECT subscription_status, subscription_paid_until, plan FROM tenants WHERE id = ?");
        $stmt->execute([$tenant['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $isActive = ($row['subscription_status'] ?? '') === 'active';

        require APP_ROOT . '/views/billing_success.php';
    }

    /**
     * POST /{slug}/pay/cancel - tenant-initiated subscription cancellation.
     * Calls Polar to cancel_at_period_end so the user keeps access until the current
     * period (trial or paid) wraps up. Webhook will land later and flip us to 'cancelled'.
     */
    public function cancel(): void {
        $tenant = Tenant::current();
        if (!$tenant) { http_response_code(404); echo 'Tenant introuvable'; return; }

        // Only a logged-in admin of this tenant can cancel.
        if (empty($_SESSION['user_id']) || ($_SESSION['tenant_slug'] ?? null) !== $tenant['slug']) {
            http_response_code(403); echo 'Non autorise'; return;
        }
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            http_response_code(403); echo 'Reserve aux administrateurs'; return;
        }

        $subId = trim((string)($tenant['polar_subscription_id'] ?? ''));
        if ($subId === '') {
            $this->redirectFlash($tenant['slug'], 'Aucun abonnement Polar a annuler.');
            return;
        }

        try {
            Polar::cancelSubscription($subId, true);
            $this->logEvent($tenant['id'], 'polar_subscription_cancel_requested', [
                'polar_sub_id' => $subId,
            ]);
            $this->redirectFlash($tenant['slug'], 'Abonnement annule. Aucun prelevement ne sera effectue. Vous gardez l\'acces jusqu\'a la fin de la periode en cours.', 'success');
        } catch (Throwable $e) {
            \App\Logging\Logger::exception($e, ['op' => 'polar_cancel', 'tenant_id' => $tenant['id'], 'sub_id' => $subId]);
            $this->redirectFlash($tenant['slug'], 'Impossible d\'annuler l\'abonnement. Contactez le support.');
        }
    }

    private function redirectFlash(string $slug, string $msg, string $type = 'error'): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
        header('Location: ' . APP_BASE . '/' . $slug . '/settings');
        exit;
    }

    /**
     * POST /webhook/polar - Polar.sh event receiver.
     * Events handled:
     *   subscription.active   -> activate tenant (paid_at=now, paid_until=current_period_end, plan=...)
     *   subscription.canceled -> mark expired (keep paid_until as-is)
     *   order.paid            -> bump last_invoice_at
     */
    public function webhook(): void {
        $raw = file_get_contents('php://input') ?: '';
        $headers = Polar::requestHeaders();

        if (!Polar::verifyWebhook($raw, $headers)) {
            \App\Logging\Logger::warn('Polar webhook: invalid signature', ['headers' => array_intersect_key($headers, array_flip(['webhook-id','webhook-timestamp']))]);
            http_response_code(401);
            echo 'invalid signature'; return;
        }

        $payload = json_decode($raw, true);
        $type    = $payload['type'] ?? '';
        $data    = $payload['data'] ?? [];

        try {
            switch ($type) {
                case 'subscription.active':
                case 'subscription.created':
                case 'subscription.updated':
                    $this->handleSubscriptionActive($data);
                    break;
                case 'subscription.canceled':
                    // User asked to cancel - Polar keeps access until current_period_end.
                    // We just log it and update paid_until (still honored by the gate).
                    $this->handleSubscriptionCancelRequested($data);
                    break;
                case 'subscription.revoked':
                    // Access actually ends now (trial lapsed without paying, charge failed, etc).
                    $this->handleSubscriptionCanceled($data);
                    break;
                case 'order.paid':
                    $this->handleOrderPaid($data);
                    break;
                default:
                    // Acknowledge but ignore unknown events
                    break;
            }
        } catch (Throwable $e) {
            \App\Logging\Logger::exception($e, ['op' => 'polar_webhook', 'type' => $type]);
            http_response_code(500);
            echo 'handler error'; return;
        }

        http_response_code(200);
        echo 'ok';
    }

    private function handleSubscriptionActive(array $sub): void {
        $tenantId = $sub['metadata']['tenant_id'] ?? null;
        $plan     = strtolower($sub['metadata']['plan'] ?? 'starter');

        // Fallback: match by customer_email if metadata is missing (manual Polar subs)
        if (!$tenantId) {
            $email = $sub['customer']['email'] ?? null;
            if ($email) {
                $db = Tenant::getMasterDB();
                $stmt = $db->prepare("SELECT id FROM tenants WHERE owner_email = ? LIMIT 1");
                $stmt->execute([$email]);
                $tenantId = $stmt->fetchColumn() ?: null;
            }
        }
        if (!$tenantId) {
            error_log('Polar webhook: subscription.active without tenant_id');
            return;
        }

        $status = strtolower($sub['status'] ?? '');
        if ($status !== 'active' && $status !== 'trialing' && $status !== '') {
            // Subscription is not actually active — skip
            return;
        }

        $paidUntil = $sub['current_period_end'] ?? null; // ISO-8601
        $polarSubId  = (string)($sub['id'] ?? '');
        $polarCustId = (string)($sub['customer_id'] ?? $sub['customer']['id'] ?? '');

        $db = Tenant::getMasterDB();
        $stmt = $db->prepare("
            UPDATE tenants SET
                subscription_status     = 'active',
                plan                    = :plan,
                paid_at                 = NOW(),
                subscription_paid_until = :paid_until,
                polar_subscription_id   = :sub_id,
                polar_customer_id       = :cust_id
            WHERE id = :tenant_id
        ");
        $stmt->execute([
            'plan'        => $plan,
            'paid_until'  => $paidUntil ? date('Y-m-d H:i:s', strtotime($paidUntil)) : date('Y-m-d H:i:s', strtotime('+1 month')),
            'sub_id'      => $polarSubId,
            'cust_id'     => $polarCustId,
            'tenant_id'   => $tenantId,
        ]);
        $this->logEvent($tenantId, 'polar_subscription_active', [
            'plan' => $plan, 'polar_sub_id' => $polarSubId, 'paid_until' => $paidUntil,
        ]);
    }

    /** subscription.canceled - user requested cancel, access continues until period end. */
    private function handleSubscriptionCancelRequested(array $sub): void {
        $tenantId = $this->resolveTenantIdFromSub($sub);
        if (!$tenantId) return;

        $paidUntil = $sub['current_period_end'] ?? null;
        $db = Tenant::getMasterDB();
        // Keep subscription_status='active' until period end; just update paid_until.
        if ($paidUntil) {
            $stmt = $db->prepare("UPDATE tenants SET subscription_paid_until = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s', strtotime($paidUntil)), $tenantId]);
        }
        $this->logEvent($tenantId, 'polar_subscription_cancel_pending', [
            'polar_sub_id' => $sub['id'] ?? '', 'ends_at' => $paidUntil,
        ]);
    }

    /** subscription.revoked - access actually ends now. */
    private function handleSubscriptionCanceled(array $sub): void {
        $tenantId = $this->resolveTenantIdFromSub($sub);
        if (!$tenantId) return;

        $db = Tenant::getMasterDB();
        $db->prepare("UPDATE tenants SET subscription_status = 'cancelled' WHERE id = ?")
           ->execute([$tenantId]);
        $this->logEvent($tenantId, 'polar_subscription_revoked', ['polar_sub_id' => $sub['id'] ?? '']);
    }

    private function resolveTenantIdFromSub(array $sub): ?string {
        $subId = (string)($sub['id'] ?? '');
        if ($subId === '') return null;
        $db = Tenant::getMasterDB();
        $stmt = $db->prepare("SELECT id FROM tenants WHERE polar_subscription_id = ? LIMIT 1");
        $stmt->execute([$subId]);
        $id = $stmt->fetchColumn();
        return $id ?: null;
    }

    private function logEvent($tenantId, string $action, array $details): void {
        if (!$tenantId) return;
        try {
            $db = Tenant::getMasterDB();
            $db->prepare("INSERT INTO tenant_log (tenant_id, action, details) VALUES (?, ?, ?)")
               ->execute([$tenantId, $action, json_encode($details)]);
        } catch (Throwable $e) {
            error_log("tenant_log insert failed: " . $e->getMessage());
        }
    }

    private function handleOrderPaid(array $order): void {
        $custId = (string)($order['customer_id'] ?? $order['customer']['id'] ?? '');
        if ($custId === '') return;

        $db = Tenant::getMasterDB();
        $stmt = $db->prepare("UPDATE tenants SET last_invoice_at = NOW() WHERE polar_customer_id = :cust_id");
        $stmt->execute(['cust_id' => $custId]);
    }
}
