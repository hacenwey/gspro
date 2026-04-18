<?php
/**
 * Records login attempts (success and failure) for every client and super-admin.
 * Stored in the master database so stats can span all tenants.
 */
class LoginLog {

    public static function record(array $data): void {
        try {
            $db = Tenant::getMasterDB();
            $stmt = $db->prepare(
                "INSERT INTO login_logs
                    (tenant_id, tenant_slug, actor_type, user_id, username, ip_address, user_agent, success)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['tenant_id']   ?? null,
                $data['tenant_slug'] ?? null,
                $data['actor_type']  ?? 'tenant_user',
                $data['user_id']     ?? null,
                substr((string)($data['username'] ?? ''), 0, 100),
                self::clientIp(),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                !empty($data['success']) ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            // Logging must never break the login flow.
            error_log('LoginLog failed: ' . $e->getMessage());
        }
    }

    /**
     * Best-effort real client IP behind Caddy / other proxies.
     */
    public static function clientIp(): string {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return substr($ip, 0, 45);
            }
        }
        return '';
    }
}
