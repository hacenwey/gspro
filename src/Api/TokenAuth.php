<?php

declare(strict_types=1);

namespace App\Api;

use PDO;

/**
 * Bearer-token auth for /api/v1/*. Tokens are hashed (SHA-256) at rest.
 * Token format: 64 hex chars. We hand out the cleartext once on creation,
 * store only the hash. Client sends `Authorization: Bearer <token>`.
 */
final class TokenAuth
{
    public function __construct(private PDO $db) {}

    public static function generate(): array
    {
        $raw = bin2hex(random_bytes(32));
        return ['raw' => $raw, 'hash' => hash('sha256', $raw)];
    }

    public function authenticate(?string $header): ?array
    {
        if (!$header || !preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($header), $m)) {
            return null;
        }
        $hash = hash('sha256', $m[1]);
        $stmt = $this->db->prepare(
            "SELECT t.id, t.user_id, t.expires_at, u.full_name, u.role, u.is_active
             FROM api_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.revoked_at IS NULL LIMIT 1"
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        if (!$row['is_active']) return null;
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) return null;

        $this->db->prepare("UPDATE api_tokens SET last_used_at = " .
            ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()') .
            " WHERE id = ?")
            ->execute([$row['id']]);

        return [
            'user_id' => $row['user_id'],
            'name'    => $row['full_name'],
            'role'    => $row['role'],
            'token_id' => $row['id'],
        ];
    }

    public function issue(string $userId, string $name, ?string $expiresAt = null): string
    {
        $t = self::generate();
        $this->db->prepare(
            "INSERT INTO api_tokens (id, user_id, name, token_hash, expires_at) VALUES (?,?,?,?,?)"
        )->execute([$this->uuid(), $userId, $name, $t['hash'], $expiresAt]);
        return $t['raw'];
    }

    public function revoke(string $tokenId): void
    {
        $this->db->prepare("UPDATE api_tokens SET revoked_at = " .
            ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? "datetime('now')" : 'NOW()') .
            " WHERE id = ?")
            ->execute([$tokenId]);
    }

    private function uuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
