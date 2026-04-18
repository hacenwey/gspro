<?php
/**
 * Geo-based currency detection.
 * Mauritania (country=MR) -> MRU, all other countries -> USD.
 * Results cached per-IP for 24h on disk to avoid hammering the free geo API.
 */
class GeoCurrency {

    private const CACHE_DIR = APP_ROOT . '/storage/geo_cache';
    private const CACHE_TTL = 86400; // 24h

    public const PLANS = [
        'free'    => ['MRU' => 0,     'USD' => 0],
        'starter' => ['MRU' => 5000,  'USD' => 12],
        'pro'     => ['MRU' => 15000, 'USD' => 35],
    ];

    /** Detect currency for the current request. */
    public static function detect(): array {
        // Invalidate stale sessions that still carry an obsolete currency code
        if (isset($_SESSION['geo_currency']['currency'])
            && !in_array($_SESSION['geo_currency']['currency'], ['MRU', 'USD'], true)) {
            unset($_SESSION['geo_currency']);
        }
        if (isset($_SESSION['geo_currency'])) {
            return $_SESSION['geo_currency'];
        }

        $country = self::countryFromIp(self::clientIp());
        $currency = ($country === 'MR') ? 'MRU' : 'USD';
        $symbol   = ($currency === 'MRU') ? 'UM' : '$';

        $result = compact('country', 'currency', 'symbol');
        $_SESSION['geo_currency'] = $result;
        return $result;
    }

    public static function clientIp(): string {
        foreach (['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return '';
    }

    /**
     * Look up country code (ISO-2) from an IP address.
     * Uses free ipapi.co service with short HTTP timeout + on-disk cache.
     * Returns empty string on failure — caller should fall back to default.
     */
    public static function countryFromIp(string $ip): string {
        if ($ip === '' || self::isPrivateIp($ip)) {
            return ''; // local/dev request, default to non-MR
        }

        $cacheKey = md5($ip);
        $cacheFile = self::CACHE_DIR . '/' . $cacheKey . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['country'])) {
                return (string)$cached['country'];
            }
        }

        $country = self::fetchCountry($ip);

        if (!is_dir(self::CACHE_DIR)) {
            @mkdir(self::CACHE_DIR, 0775, true);
        }
        @file_put_contents($cacheFile, json_encode(['country' => $country, 'ip' => $ip]));

        return $country;
    }

    private static function fetchCountry(string $ip): string {
        $url = "https://ipapi.co/" . urlencode($ip) . "/country/";
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 2,
                'user_agent' => 'GestionPro/1.0',
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) return '';
        $code = trim(strtoupper($raw));
        if (strlen($code) !== 2 || !ctype_alpha($code)) return '';
        return $code;
    }

    private static function isPrivateIp(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** Format a plan price for display. */
    public static function formatPrice(string $plan, string $currency, string $symbol): string {
        $amount = self::PLANS[$plan][$currency] ?? 0;
        if ($amount == 0) return '0 ' . $symbol;
        return number_format($amount, 0, ',', ' ') . ' ' . $symbol;
    }
}
