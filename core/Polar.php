<?php
/**
 * Polar.sh API client + Standard Webhooks signature verifier.
 *
 * Config resolution order: master-DB polar_config row (id=1) first, then env vars.
 * Two profiles live side-by-side: `sandbox` and `live`. The active profile is
 * selected by the `mode` column.
 */
class Polar {

    private static ?array $cfg = null;

    private static function cfg(): array {
        if (self::$cfg !== null) return self::$cfg;

        $row = null;
        try {
            if (class_exists('Tenant')) {
                $db  = Tenant::getMasterDB();
                $row = $db->query("SELECT * FROM polar_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (Throwable $e) {
            $row = null;
        }

        if ($row) {
            $mode = ($row['mode'] === 'live') ? 'live' : 'sandbox';
            $p    = $mode . '_';
            $base = $mode === 'live' ? 'https://api.polar.sh' : 'https://sandbox-api.polar.sh';
            self::$cfg = [
                'mode'           => $mode,
                'api_base'       => $base,
                'access_token'   => (string)($row[$p.'access_token']   ?? ''),
                'webhook_secret' => (string)($row[$p.'webhook_secret'] ?? ''),
                'products' => [
                    'starter'    => (string)($row[$p.'starter_product_id']    ?? ''),
                    'pro'        => (string)($row[$p.'pro_product_id']        ?? ''),
                    'enterprise' => (string)($row[$p.'enterprise_product_id'] ?? ''),
                ],
            ];
        } else {
            $envBase = rtrim((string)(getenv('POLAR_API_BASE') ?: 'https://api.polar.sh'), '/');
            self::$cfg = [
                'mode'           => strpos($envBase, 'sandbox') !== false ? 'sandbox' : 'live',
                'api_base'       => $envBase,
                'access_token'   => (string)(getenv('POLAR_ACCESS_TOKEN')   ?: ''),
                'webhook_secret' => (string)(getenv('POLAR_WEBHOOK_SECRET') ?: ''),
                'products' => [
                    'starter'    => (string)(getenv('POLAR_STARTER_PRODUCT_ID')    ?: ''),
                    'pro'        => (string)(getenv('POLAR_PRO_PRODUCT_ID')        ?: ''),
                    'enterprise' => (string)(getenv('POLAR_ENTERPRISE_PRODUCT_ID') ?: ''),
                ],
            ];
        }
        return self::$cfg;
    }

    /** Force re-read on next access (call after saving config). */
    public static function resetCache(): void { self::$cfg = null; }

    public static function mode(): string        { return self::cfg()['mode']; }
    public static function apiBase(): string     { return self::cfg()['api_base']; }
    public static function accessToken(): string { return self::cfg()['access_token']; }
    public static function webhookSecret(): string { return self::cfg()['webhook_secret']; }

    /** Map our internal plan slug to a Polar product UUID for the active mode. */
    public static function productIdForPlan(string $plan): ?string {
        $id = self::cfg()['products'][strtolower($plan)] ?? '';
        return $id !== '' ? $id : null;
    }

    public static function isConfigured(): bool {
        return self::accessToken() !== '';
    }

    /**
     * Create a checkout session.
     * Returns ['id' => ..., 'url' => 'https://checkout.polar.sh/...'] on success.
     * Throws RuntimeException on failure.
     */
    public static function createCheckout(array $params): array {
        $body = array_filter([
            'products'        => $params['products']        ?? null,
            'customer_email'  => $params['customer_email']  ?? null,
            'customer_name'   => $params['customer_name']   ?? null,
            'success_url'     => $params['success_url']     ?? null,
            'metadata'        => $params['metadata']        ?? null,
        ], static fn($v) => $v !== null && $v !== '');

        $resp = self::request('POST', '/v1/checkouts/', $body);
        if (!isset($resp['id'], $resp['url'])) {
            throw new RuntimeException('Polar checkout response missing id/url');
        }
        return $resp;
    }

    /** Cancel a subscription. Defaults to cancel-at-period-end. */
    public static function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): array {
        if ($subscriptionId === '') {
            throw new RuntimeException('Empty subscription id');
        }
        return self::request('PATCH', '/v1/subscriptions/' . rawurlencode($subscriptionId), [
            'cancel_at_period_end' => $atPeriodEnd,
        ]);
    }

    /** List products for the active organization. Returns the `items` array. */
    public static function listProducts(int $limit = 50): array {
        $resp = self::request('GET', '/v1/products/?limit=' . (int)$limit);
        return $resp['items'] ?? [];
    }

    /**
     * Create a recurring product with a single fixed USD price.
     * $priceCents: amount in cents (e.g. 1200 for $12.00).
     */
    public static function createProduct(string $name, int $priceCents, string $interval = 'month', string $description = ''): array {
        $body = [
            'name'               => $name,
            'description'        => $description !== '' ? $description : $name,
            'recurring_interval' => $interval,
            'prices' => [[
                'amount_type'    => 'fixed',
                'price_amount'   => $priceCents,
                'price_currency' => 'usd',
            ]],
        ];
        return self::request('POST', '/v1/products/', $body);
    }

    /** Archive a product (Polar soft-delete). */
    public static function archiveProduct(string $productId): array {
        if ($productId === '') throw new RuntimeException('Empty product id');
        return self::request('PATCH', '/v1/products/' . rawurlencode($productId), ['is_archived' => true]);
    }

    /** Shared HTTP helper. Throws on non-2xx. */
    private static function request(string $method, string $path, ?array $body = null): array {
        if (!self::isConfigured()) {
            throw new RuntimeException('Polar access token is not configured');
        }

        $ch = curl_init(self::apiBase() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::accessToken(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException("Polar $method $path failed: $err");
        }
        $resp = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($resp) ? json_encode($resp) : $raw;
            throw new RuntimeException("Polar $method $path HTTP $code: $msg");
        }
        return is_array($resp) ? $resp : [];
    }

    /**
     * Verify a webhook payload using the Standard Webhooks spec.
     * Polar sends: webhook-id, webhook-timestamp, webhook-signature.
     */
    public static function verifyWebhook(string $rawBody, array $headers): bool {
        $secret = self::webhookSecret();
        if ($secret === '') return false;

        $id  = self::header($headers, 'webhook-id');
        $ts  = self::header($headers, 'webhook-timestamp');
        $sig = self::header($headers, 'webhook-signature');
        if ($id === '' || $ts === '' || $sig === '') return false;
        // Replay window: 5 minutes
        if (abs(time() - (int)$ts) > 300) return false;

        // Build candidate HMAC keys. Different Standard Webhooks implementations
        // interpret the prefixed secret differently, so we try all plausible forms.
        $keys = [];
        $stripped = $secret;
        foreach (['polar_whs_', 'whsec_'] as $prefix) {
            if (str_starts_with($stripped, $prefix)) {
                $stripped = substr($stripped, strlen($prefix));
                break;
            }
        }
        $keys[] = $stripped;                    // raw string (post-prefix)
        $keys[] = $secret;                      // raw string (full, with prefix)
        $decoded = base64_decode($stripped, true);
        if ($decoded !== false) $keys[] = $decoded;  // base64-decoded bytes

        $signed = $id . '.' . $ts . '.' . $rawBody;
        foreach ($keys as $k) {
            $expected = 'v1,' . base64_encode(hash_hmac('sha256', $signed, $k, true));
            foreach (explode(' ', $sig) as $candidate) {
                if (hash_equals($expected, $candidate)) return true;
            }
        }
        return false;
    }

    private static function header(array $headers, string $name): string {
        $name = strtolower($name);
        foreach ($headers as $k => $v) {
            if (strtolower($k) === $name) {
                return is_array($v) ? (string)$v[0] : (string)$v;
            }
        }
        return '';
    }

    public static function requestHeaders(): array {
        if (function_exists('getallheaders')) return getallheaders();
        $out = [];
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') === 0) {
                $h = str_replace('_', '-', strtolower(substr($k, 5)));
                $out[$h] = $v;
            }
        }
        return $out;
    }
}
