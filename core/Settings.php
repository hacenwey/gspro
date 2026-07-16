<?php
/**
 * Read-through accessor for the tenant's `settings` table.
 *
 * Controllers each ran their own `SELECT * FROM settings` loop, which is fine for
 * a page that already needs the whole set. This exists for the cases that need a
 * single value from anywhere — the layout, helpers — without paying for a query
 * per lookup. Loaded once per request, then served from memory.
 *
 * Values are whatever is stored: always treat them as strings.
 */
class Settings {
    private static ?array $cache = null;

    /** @return array<string, string> */
    public static function all(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }
        self::$cache = [];
        try {
            $db = Tenant::getDB();
            foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
                self::$cache[$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (\Throwable $e) {
            // No tenant / no DB (landing page, installer): fall back to defaults
            // rather than taking the page down over a settings lookup.
            self::$cache = [];
        }
        return self::$cache;
    }

    public static function get(string $key, ?string $default = null): ?string {
        $all = self::all();
        return isset($all[$key]) && $all[$key] !== '' ? $all[$key] : $default;
    }

    /** Drop the cache after a write so the next read sees the new value. */
    public static function flush(): void {
        self::$cache = null;
    }
}
