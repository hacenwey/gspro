<?php
namespace App\Logging;

use Throwable;

/**
 * Structured JSON-lines logger.
 *
 * One file per day at logs/app-YYYY-MM-DD.log. Each line is a JSON object:
 *   { ts, level, msg, ctx, request_id, tenant, user, ... }
 *
 * Deliberately minimalist so we don't pull in Monolog for a SaaS that isn't
 * yet doing log aggregation. When we wire Sentry/Loki later, the structured
 * format makes the migration trivial.
 */
final class Logger
{
    private static ?string $requestId = null;

    public static function info(string $msg, array $ctx = []): void     { self::write('info',  $msg, $ctx); }
    public static function warn(string $msg, array $ctx = []): void     { self::write('warn',  $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void    { self::write('error', $msg, $ctx); }

    public static function exception(Throwable $e, array $ctx = []): void
    {
        self::write('error', $e->getMessage(), array_merge($ctx, [
            'exception' => [
                'class' => get_class($e),
                'file'  => $e->getFile() . ':' . $e->getLine(),
                'trace' => self::shortTrace($e),
            ],
        ]));
    }

    private static function write(string $level, string $msg, array $ctx): void
    {
        $dir = defined('APP_ROOT') ? APP_ROOT . '/logs' : __DIR__ . '/../../logs';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

        $record = [
            'ts'         => date('c'),
            'level'      => $level,
            'msg'        => $msg,
            'request_id' => self::requestId(),
            'tenant'     => class_exists('Tenant') && \Tenant::slug() ? \Tenant::slug() : null,
            'user'       => $_SESSION['user_id'] ?? null,
            'ctx'        => $ctx,
        ];
        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($line === false) { return; }

        @file_put_contents(
            $dir . '/app-' . date('Y-m-d') . '.log',
            $line . "\n",
            FILE_APPEND | LOCK_EX
        );

        // Also route to PHP's error_log so existing monitoring still sees things.
        // Silenced in PHPUnit runs to keep test output clean.
        if (($level === 'error' || $level === 'warn') && !defined('PHPUNIT_RUNNING')) {
            error_log('[' . $level . '] ' . $msg . ' ' . ($ctx ? json_encode($ctx) : ''));
        }
    }

    private static function requestId(): string
    {
        if (self::$requestId !== null) return self::$requestId;
        self::$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
        return self::$requestId;
    }

    private static function shortTrace(Throwable $e, int $max = 8): array
    {
        $out = [];
        foreach (array_slice($e->getTrace(), 0, $max) as $frame) {
            $out[] = ($frame['file'] ?? '?') . ':' . ($frame['line'] ?? '?')
                   . ' ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
        }
        return $out;
    }
}
