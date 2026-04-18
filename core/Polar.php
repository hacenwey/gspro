<?php
/**
 * Polar.sh API client + Standard Webhooks signature verifier.
 *
 * Two ways to authenticate API calls:
 *   POLAR_ACCESS_TOKEN   - Organization Access Token (server-side only, never ship to browsers)
 *
 * Three environment-driven product IDs:
 *   POLAR_STARTER_PRODUCT_ID
 *   POLAR_PRO_PRODUCT_ID
 *   POLAR_ENTERPRISE_PRODUCT_ID (optional)
 *
 * POLAR_API_BASE lets you point at sandbox.polar.sh while testing; defaults to prod.
 */
class Polar {

    public static function apiBase(): string {
        return rtrim(getenv('POLAR_API_BASE') ?: 'https://api.polar.sh', '/');
    }

    public static function accessToken(): string {
        return (string)(getenv('POLAR_ACCESS_TOKEN') ?: '');
    }

    public static function webhookSecret(): string {
        return (string)(getenv('POLAR_WEBHOOK_SECRET') ?: '');
    }

    /** Map our internal plan slug to a Polar product UUID (from env). */
    public static function productIdForPlan(string $plan): ?string {
        $envKey = 'POLAR_' . strtoupper($plan) . '_PRODUCT_ID';
        $id = getenv($envKey);
        return $id ? (string)$id : null;
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
        if (!self::isConfigured()) {
            throw new RuntimeException('Polar access token is not configured');
        }

        $body = array_filter([
            'products'        => $params['products']        ?? null,
            'customer_email'  => $params['customer_email']  ?? null,
            'customer_name'   => $params['customer_name']   ?? null,
            'success_url'     => $params['success_url']     ?? null,
            'metadata'        => $params['metadata']        ?? null,
        ], static fn($v) => $v !== null && $v !== '');

        $ch = curl_init(self::apiBase() . '/v1/checkouts/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::accessToken(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Polar checkout request failed: ' . $err);
        }
        $resp = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($resp) ? json_encode($resp) : $raw;
            throw new RuntimeException("Polar checkout HTTP $code: $msg");
        }
        if (!isset($resp['id'], $resp['url'])) {
            throw new RuntimeException('Polar checkout response missing id/url');
        }
        return $resp;
    }

    /**
     * Verify a webhook payload using the Standard Webhooks spec.
     * Polar sends these headers:
     *   webhook-id
     *   webhook-timestamp (unix seconds)
     *   webhook-signature (space-separated list of "v1,<base64>")
     *
     * Returns true on valid signature, false otherwise.
     */
    public static function verifyWebhook(string $rawBody, array $headers): bool {
        $secret = self::webhookSecret();
        if ($secret === '') return false;

        $id   = self::header($headers, 'webhook-id');
        $ts   = self::header($headers, 'webhook-timestamp');
        $sig  = self::header($headers, 'webhook-signature');
        if ($id === '' || $ts === '' || $sig === '') return false;

        // Reject replays older than 5 min
        if (abs(time() - (int)$ts) > 300) return false;

        // Secrets can be prefixed: Polar uses "polar_whs_...", Standard Webhooks spec uses "whsec_...".
        // After stripping prefix, the body is usually base64-encoded bytes; fall back to raw if not.
        $key = $secret;
        foreach (['polar_whs_', 'whsec_'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                $key = substr($key, strlen($prefix));
                break;
            }
        }
        $decoded = base64_decode($key, true);
        if ($decoded === false) {
            $decoded = $key;
        }

        $signed = $id . '.' . $ts . '.' . $rawBody;
        $expected = 'v1,' . base64_encode(hash_hmac('sha256', $signed, $decoded, true));

        foreach (explode(' ', $sig) as $candidate) {
            if (hash_equals($expected, $candidate)) return true;
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

    /** Fetch incoming request headers (case-insensitive). */
    public static function requestHeaders(): array {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }
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
