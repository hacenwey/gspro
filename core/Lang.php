<?php
class Lang {
    private static string $locale = 'fr';
    private static array $translations = [];

    public static function init(): void {
        if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        self::$locale = $_SESSION['lang'] ?? DEFAULT_LANG;
        self::load();
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
