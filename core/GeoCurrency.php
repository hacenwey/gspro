<?php
/**
 * Currency helper. The app is USD-only — this class is kept as a thin
 * adapter so existing callers (landing page, trial_expired view, register
 * controller) keep working without branching logic.
 */
class GeoCurrency {

    public const PLANS = [
        'starter' => ['USD' => 12],
        'pro'     => ['USD' => 35],
    ];

    public static function detect(): array {
        return ['country' => '', 'currency' => 'USD', 'symbol' => '$'];
    }

    public static function formatPrice(string $plan, string $currency = 'USD', string $symbol = '$'): string {
        $amount = self::PLANS[$plan]['USD'] ?? 0;
        if ($amount == 0) return '0 ' . $symbol;
        return $symbol . number_format($amount, 0, ',', ' ');
    }
}
