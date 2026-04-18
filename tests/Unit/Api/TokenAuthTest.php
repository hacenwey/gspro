<?php

declare(strict_types=1);

namespace Tests\Unit\Api;

use App\Api\TokenAuth;
use PDO;
use PHPUnit\Framework\TestCase;

final class TokenAuthTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE users (id TEXT PRIMARY KEY, full_name TEXT, role TEXT, is_active INTEGER DEFAULT 1)");
        $this->db->exec("CREATE TABLE api_tokens (
            id TEXT PRIMARY KEY, user_id TEXT, name TEXT, token_hash TEXT,
            last_used_at TEXT, expires_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, revoked_at TEXT
        )");
        $this->db->exec("INSERT INTO users (id, full_name, role, is_active) VALUES ('u1', 'Alice', 'admin', 1)");
        $this->db->exec("INSERT INTO users (id, full_name, role, is_active) VALUES ('u2', 'Bob', 'user', 0)");
    }

    public function testIssueReturns64HexToken(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $raw);
    }

    public function testAuthenticateValidToken(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test');

        $user = $auth->authenticate("Bearer $raw");
        $this->assertIsArray($user);
        $this->assertSame('u1', $user['user_id']);
        $this->assertSame('Alice', $user['name']);
        $this->assertSame('admin', $user['role']);
    }

    public function testAuthenticateRejectsBadToken(): void
    {
        $auth = new TokenAuth($this->db);
        $this->assertNull($auth->authenticate('Bearer ' . str_repeat('a', 64)));
    }

    public function testAuthenticateRejectsMissingHeader(): void
    {
        $auth = new TokenAuth($this->db);
        $this->assertNull($auth->authenticate(null));
        $this->assertNull($auth->authenticate(''));
    }

    public function testAuthenticateRejectsMalformedHeader(): void
    {
        $auth = new TokenAuth($this->db);
        $this->assertNull($auth->authenticate('Basic abc'));
        $this->assertNull($auth->authenticate('Bearer short'));
    }

    public function testAuthenticateRejectsInactiveUser(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u2', 'test'); // Bob inactive
        $this->assertNull($auth->authenticate("Bearer $raw"));
    }

    public function testAuthenticateRejectsRevokedToken(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test');
        $tokenId = $this->db->query("SELECT id FROM api_tokens WHERE user_id='u1'")->fetchColumn();
        $auth->revoke($tokenId);
        $this->assertNull($auth->authenticate("Bearer $raw"));
    }

    public function testAuthenticateRejectsExpiredToken(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test', date('Y-m-d H:i:s', strtotime('-1 day')));
        $this->assertNull($auth->authenticate("Bearer $raw"));
    }

    public function testAuthenticateUpdatesLastUsedAt(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test');
        $before = $this->db->query("SELECT last_used_at FROM api_tokens WHERE user_id='u1'")->fetchColumn();
        $this->assertNull($before);
        $auth->authenticate("Bearer $raw");
        $after = $this->db->query("SELECT last_used_at FROM api_tokens WHERE user_id='u1'")->fetchColumn();
        $this->assertNotNull($after);
    }

    public function testTokenHashIsSha256(): void
    {
        $auth = new TokenAuth($this->db);
        $raw = $auth->issue('u1', 'test');
        $stored = $this->db->query("SELECT token_hash FROM api_tokens WHERE user_id='u1'")->fetchColumn();
        $this->assertSame(hash('sha256', $raw), $stored);
    }
}
