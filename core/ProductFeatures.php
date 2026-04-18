<?php
/**
 * Per-product bullet lists for the landing page.
 *
 * Storage: master DB `product_features` (product_id PK, features TEXT).
 * Bullets are one per line in the TEXT column. Empty lines are ignored.
 * When a product has no row, callers should fall back to defaults().
 */
class ProductFeatures {

    /** Return map: product_id => array<string> (bullets). */
    public static function all(): array {
        try {
            $db   = Tenant::getMasterDB();
            $rows = $db->query("SELECT product_id, features FROM product_features")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[(string)$r['product_id']] = self::parse((string)$r['features']);
        }
        return $out;
    }

    /** Bullets for one product, or [] if not set. */
    public static function get(string $productId): array {
        if ($productId === '') return [];
        try {
            $db = Tenant::getMasterDB();
            $st = $db->prepare("SELECT features FROM product_features WHERE product_id = ?");
            $st->execute([$productId]);
            $raw = (string)($st->fetchColumn() ?: '');
        } catch (Throwable $e) {
            return [];
        }
        return self::parse($raw);
    }

    /**
     * Upsert bullets for a product. Empty list deletes the row so the
     * landing falls back to defaults().
     */
    public static function save(string $productId, string $raw): void {
        if ($productId === '') return;
        $bullets = self::parse($raw);
        $db = Tenant::getMasterDB();
        if (!$bullets) {
            $db->prepare("DELETE FROM product_features WHERE product_id = ?")->execute([$productId]);
            return;
        }
        $text = implode("\n", $bullets);
        $db->prepare("
            INSERT INTO product_features (product_id, features) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE features = VALUES(features)
        ")->execute([$productId, $text]);
    }

    /** Default bullets used when a product has no custom feature list. */
    public static function defaults(): array {
        return [
            'POS + unlimited invoices',
            'Products, stock, customers, suppliers',
            'PDF invoices + CSV exports',
            'Secure card checkout (Polar)',
            'Cancel anytime during the trial',
        ];
    }

    /** Split TEXT into clean bullets (trim, drop empties, cap at 10). */
    private static function parse(string $raw): array {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            // Allow users to paste with leading "- " or "* "
            $line = preg_replace('/^[-*]\s+/', '', $line);
            if ($line !== '') $out[] = $line;
            if (count($out) >= 10) break;
        }
        return $out;
    }
}
