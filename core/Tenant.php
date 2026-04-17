<?php
/**
 * Multi-Tenant Manager
 * Detects tenant from URL slug and provides tenant-scoped database connections.
 *
 * URL format: /gestion_commerciale/{tenant_slug}/dashboard
 * Admin URL:  /gestion_commerciale/admin/...
 */
class Tenant {
    private static ?array $current = null;
    private static ?PDO $masterDb = null;
    private static ?PDO $tenantDb = null;
    private static ?string $slug = null;

    /**
     * Extract tenant slug from the current request URI.
     * Returns null if we're on the admin panel or no slug found.
     */
    public static function extractSlug(): ?string {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = '/gestion_commerciale';
        $path = str_replace($base, '', $uri);
        $path = '/' . trim($path, '/');

        // /admin/... → super-admin panel, no tenant
        if (str_starts_with($path, '/admin')) {
            return null;
        }

        // /{slug}/... → tenant
        $segments = explode('/', trim($path, '/'));
        if (!empty($segments[0]) && $segments[0] !== 'database') {
            return $segments[0];
        }

        return null;
    }

    /**
     * Get the path AFTER the tenant slug.
     * e.g. /gestion_commerciale/ahmed/products → /products
     */
    public static function extractPath(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = '/gestion_commerciale';
        $path = str_replace($base, '', $uri);
        $path = '/' . trim($path, '/');

        $slug = self::extractSlug();
        if ($slug) {
            $path = substr($path, strlen('/' . $slug));
            if ($path === '' || $path === false) $path = '/';
        }

        return $path;
    }

    /**
     * Extract path for admin panel.
     * e.g. /gestion_commerciale/admin/tenants → /tenants
     */
    public static function extractAdminPath(): string {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = '/gestion_commerciale/admin';
        $path = str_replace($base, '', $uri);
        $path = '/' . trim($path, '/');
        return $path;
    }

    /**
     * Get master database connection.
     */
    public static function getMasterDB(): PDO {
        if (self::$masterDb === null) {
            self::$masterDb = new PDO(db_dsn('gestionpro_master'), DB_USER, DB_PASS, db_pdo_options());
        }
        return self::$masterDb;
    }

    /**
     * Load and validate tenant by slug.
     */
    public static function load(string $slug): bool {
        self::$slug = $slug;

        try {
            $db = self::getMasterDB();
            $stmt = $db->prepare("SELECT * FROM tenants WHERE slug = ? AND is_active = 1");
            $stmt->execute([$slug]);
            $tenant = $stmt->fetch();

            if (!$tenant) return false;

            // Check expiration
            if ($tenant['expires_at'] && strtotime($tenant['expires_at']) < time()) {
                return false;
            }

            self::$current = $tenant;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get current tenant info.
     */
    public static function current(): ?array {
        return self::$current;
    }

    /**
     * Get current tenant slug.
     */
    public static function slug(): ?string {
        return self::$slug;
    }

    /**
     * Get tenant-scoped database connection.
     */
    public static function getDB(): PDO {
        if (self::$tenantDb === null) {
            if (!self::$current) {
                throw new RuntimeException('No tenant loaded');
            }
            $dbName = self::$current['db_name'];
            self::$tenantDb = new PDO(db_dsn($dbName), DB_USER, DB_PASS, db_pdo_options());
        }
        return self::$tenantDb;
    }

    /**
     * Provision a new tenant: create database, run schema, create admin user.
     */
    public static function provision(array $data): array {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($data['slug']));
        if (strlen($slug) < 2) {
            throw new RuntimeException('Slug trop court (min 2 caracteres)');
        }

        $dbName = 'gestionpro_' . str_replace('-', '_', $slug);
        $masterDb = self::getMasterDB();

        // Check slug uniqueness
        $check = $masterDb->prepare("SELECT COUNT(*) FROM tenants WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetchColumn() > 0) {
            throw new RuntimeException('Ce slug est deja utilise');
        }

        // Generate tenant ID
        $tenantId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Create tenant database
        $pdo = new PDO(db_dsn(), DB_USER, DB_PASS, db_pdo_options());
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");

        // Run tenant schema (reuse existing schema.sql)
        $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
        $schema = preg_replace('/CREATE DATABASE .+?;/s', '', $schema);
        $schema = preg_replace('/USE .+?;/', '', $schema);

        // Remove seed data - we'll insert our own
        $schemaOnly = preg_replace('/-- =+ SEED DATA =+.*/s', '', $schema);

        $statements = array_filter(array_map('trim', explode(';', $schemaOnly)));
        foreach ($statements as $stmt) {
            if (!empty($stmt) && $stmt !== '--') {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    if ($e->getCode() != '42S01' && $e->getCode() != '42000') {
                        // Non-critical, continue
                    }
                }
            }
        }

        // Create admin user for this tenant
        $adminPassword = $data['admin_password'] ?? 'admin123';
        $adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $adminUserId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $adminUsername = $data['admin_username'] ?? 'admin';
        $adminEmail = $data['owner_email'] ?? 'admin@' . $slug . '.local';
        $adminFullName = $data['owner_name'] ?? 'Administrateur';

        $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?, 'admin')");
        $stmt->execute([$adminUserId, $adminUsername, $adminEmail, $adminHash, $adminFullName]);

        // Insert default settings
        $companyName = $data['company_name'] ?? 'Mon Entreprise';
        $defaultSettings = [
            ['company_name', $companyName],
            ['company_address', $data['address'] ?? 'Nouakchott, Mauritanie'],
            ['company_phone', $data['owner_phone'] ?? ''],
            ['company_email', $adminEmail],
            ['company_tax_id', ''],
            ['company_logo', ''],
            ['invoice_prefix', 'FAC'],
            ['quote_prefix', 'DEV'],
            ['credit_note_prefix', 'AVO'],
            ['po_prefix', 'BC'],
            ['default_tax_rate', '16'],
            ['currency', 'MRU'],
            ['currency_symbol', 'UM'],
            ['default_payment_terms', '30'],
            ['default_quote_validity', '30'],
            ['low_stock_alert', '1'],
            ['onboarding_done', '0'],
        ];

        $settingStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as [$key, $value]) {
            $settingStmt->execute([$key, $value]);
        }

        // Insert default categories
        $catStmt = $pdo->prepare("INSERT INTO categories (id, name, description) VALUES (UUID(), ?, ?)");
        $catStmt->execute(['Alimentation', 'Produits alimentaires']);
        $catStmt->execute(['Boissons', 'Boissons et rafraichissements']);
        $catStmt->execute(['Divers', 'Articles divers']);

        // Register tenant in master database
        $plan = $data['plan'] ?? 'starter';
        $maxUsers = match($plan) {
            'free' => 2,
            'starter' => 5,
            'pro' => 15,
            'enterprise' => 100,
            default => 5
        };
        $maxProducts = match($plan) {
            'free' => 100,
            'starter' => 500,
            'pro' => 5000,
            'enterprise' => 999999,
            default => 500
        };

        $stmt = $masterDb->prepare("INSERT INTO tenants (id, slug, company_name, owner_name, owner_email, owner_phone, db_name, plan, max_users, max_products) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tenantId,
            $slug,
            $companyName,
            $data['owner_name'] ?? '',
            $adminEmail,
            $data['owner_phone'] ?? '',
            $dbName,
            $plan,
            $maxUsers,
            $maxProducts
        ]);

        // Log
        $logStmt = $masterDb->prepare("INSERT INTO tenant_log (tenant_id, action, details) VALUES (?, 'tenant_created', ?)");
        $logStmt->execute([$tenantId, json_encode(['slug' => $slug, 'db' => $dbName, 'plan' => $plan])]);

        return [
            'tenant_id' => $tenantId,
            'slug' => $slug,
            'db_name' => $dbName,
            'admin_username' => $adminUsername,
            'admin_password' => $adminPassword,
            'url' => '/gestion_commerciale/' . $slug . '/login'
        ];
    }

    /**
     * List all tenants.
     */
    public static function listAll(): array {
        $db = self::getMasterDB();
        return $db->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll();
    }

    /**
     * Toggle tenant active status.
     */
    public static function toggleActive(string $tenantId): void {
        $db = self::getMasterDB();
        $db->prepare("UPDATE tenants SET is_active = NOT is_active WHERE id = ?")->execute([$tenantId]);
    }

    /**
     * Delete a tenant and its database.
     */
    public static function delete(string $tenantId): void {
        $db = self::getMasterDB();

        // Get db name first
        $stmt = $db->prepare("SELECT db_name, slug FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch();

        if ($tenant) {
            // Drop tenant database
            $pdo = new PDO(db_dsn(), DB_USER, DB_PASS, db_pdo_options());
            $pdo->exec("DROP DATABASE IF EXISTS `{$tenant['db_name']}`");

            // Remove from master
            $db->prepare("DELETE FROM tenant_log WHERE tenant_id = ?")->execute([$tenantId]);
            $db->prepare("DELETE FROM tenants WHERE id = ?")->execute([$tenantId]);
        }
    }

    /**
     * Get a tenant by ID.
     */
    public static function getById(string $id): ?array {
        $db = self::getMasterDB();
        $stmt = $db->prepare("SELECT * FROM tenants WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update tenant info.
     */
    public static function update(string $id, array $data): void {
        $db = self::getMasterDB();
        $stmt = $db->prepare("UPDATE tenants SET company_name = ?, owner_name = ?, owner_email = ?, owner_phone = ?, plan = ?, max_users = ?, max_products = ?, is_active = ?, expires_at = ?, notes = ? WHERE id = ?");
        $stmt->execute([
            $data['company_name'],
            $data['owner_name'],
            $data['owner_email'],
            $data['owner_phone'] ?? '',
            $data['plan'] ?? 'starter',
            $data['max_users'] ?? 5,
            $data['max_products'] ?? 500,
            $data['is_active'] ?? 1,
            $data['expires_at'] ?: null,
            $data['notes'] ?? '',
            $id
        ]);
    }
}
