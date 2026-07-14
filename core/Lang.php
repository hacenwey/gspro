<?php
class Lang {
    private static string $locale = 'fr';
    private static array $translations = [];

    public static function init(): void {
        if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        // Resolution order:
        //   1. session   — the header switcher, a temporary per-session override
        //   2. settings  — the tenant's saved choice; survives logout (session is wiped)
        //   3. DEFAULT_LANG
        self::$locale = $_SESSION['lang'] ?? self::storedLocale() ?? DEFAULT_LANG;
        self::load();
    }

    /**
     * The tenant's persisted language, or null when unset/invalid/unreachable.
     * Only hit when the session carries no override, and it's a primary-key lookup.
     */
    private static function storedLocale(): ?string {
        if (!class_exists('Tenant') || !Tenant::current()) {
            return null;
        }
        try {
            $stmt = Tenant::getDB()->query("SELECT setting_value FROM settings WHERE setting_key = 'language' LIMIT 1");
            $value = $stmt ? $stmt->fetchColumn() : null;
            return ($value && in_array($value, SUPPORTED_LANGS, true)) ? $value : null;
        } catch (\Throwable $e) {
            // Settings table missing or DB down — fall back to the default silently.
            return null;
        }
    }

    public static function load(): void {
        $file = APP_ROOT . '/lang/' . self::$locale . '.php';
        if (file_exists($file)) {
            self::$translations = require $file;
        }
    }

    public static function get(string $key, string $default = ''): string {
        return self::$translations[$key] ?? ($default ?: $key);
    }

    public static function locale(): string {
        return self::$locale;
    }

    public static function isRtl(): bool {
        return self::$locale === 'ar';
    }

    public static function dir(): string {
        return self::isRtl() ? 'rtl' : 'ltr';
    }
}

function __($key, $default = ''): string {
    return Lang::get($key, $default);
}
