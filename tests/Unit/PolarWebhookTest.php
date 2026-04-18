<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the Standard Webhooks HMAC logic that lives in core/Polar.php.
 *
 * The Polar class reads its secret through a static cfg() which hits the
 * master DB on first access. We bypass that by (1) setting an env secret
 * and (2) pre-seeding Polar::$cfg via reflection in setUp.
 */
final class PolarWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('Polar')) {
            require_once __DIR__ . '/../../core/Polar.php';
        }
        // Seed config through reflection so cfg() doesn't touch the DB.
        $ref = new \ReflectionClass('Polar');
        $prop = $ref->getProperty('cfg');
        $prop->setValue(null, [
            'mode'           => 'sandbox',
            'api_base'       => 'https://sandbox-api.polar.sh',
            'access_token'   => 'test',
            'webhook_secret' => 'polar_whs_testsecret',
            'products'       => ['starter' => '', 'pro' => '', 'enterprise' => ''],
        ]);
    }

    private function signWith(string $secretPostPrefix, string $id, string $ts, string $body): string
    {
        return 'v1,' . base64_encode(hash_hmac('sha256', "$id.$ts.$body", $secretPostPrefix, true));
    }

    public function test_valid_signature_post_prefix_accepted(): void
    {
        $id = 'evt_1'; $ts = (string)time(); $body = '{"type":"ping"}';
        $sig = $this->signWith('testsecret', $id, $ts, $body);

        $ok = \Polar::verifyWebhook($body, [
            'webhook-id' => $id, 'webhook-timestamp' => $ts, 'webhook-signature' => $sig,
        ]);
        $this->assertTrue($ok);
    }

    public function test_valid_signature_full_secret_accepted(): void
    {
        $id = 'evt_2'; $ts = (string)time(); $body = '{}';
        $sig = $this->signWith('polar_whs_testsecret', $id, $ts, $body);

        $ok = \Polar::verifyWebhook($body, [
            'webhook-id' => $id, 'webhook-timestamp' => $ts, 'webhook-signature' => $sig,
        ]);
        $this->assertTrue($ok);
    }

    public function test_replay_window_rejects_old_timestamp(): void
    {
        $id = 'evt_3'; $ts = (string)(time() - 400); $body = '{}';
        $sig = $this->signWith('testsecret', $id, $ts, $body);

        $ok = \Polar::verifyWebhook($body, [
            'webhook-id' => $id, 'webhook-timestamp' => $ts, 'webhook-signature' => $sig,
        ]);
        $this->assertFalse($ok);
    }

    public function test_bad_signature_rejected(): void
    {
        $id = 'evt_4'; $ts = (string)time(); $body = '{}';
        $sig = $this->signWith('wrongsecret', $id, $ts, $body);

        $ok = \Polar::verifyWebhook($body, [
            'webhook-id' => $id, 'webhook-timestamp' => $ts, 'webhook-signature' => $sig,
        ]);
        $this->assertFalse($ok);
    }

    public function test_missing_headers_rejected(): void
    {
        $this->assertFalse(\Polar::verifyWebhook('{}', []));
    }
}
