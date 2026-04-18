<?php
/**
 * Migration: Add login_logs table to master database.
 * Run once: http://localhost/gestion_commerciale/database/migrate_login_logs.php
 * Or in prod: https://gestionpro.it.com/database/migrate_login_logs.php
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=gestionpro_master;charset=" . DB_CHARSET;
$db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$queries = [
    "CREATE TABLE IF NOT EXISTS login_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        tenant_id CHAR(36) NULL,
        tenant_slug VARCHAR(50) NULL,
        actor_type ENUM('tenant_user','super_admin') NOT NULL DEFAULT 'tenant_user',
        user_id CHAR(36) NULL,
        username VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "CREATE INDEX idx_login_logs_tenant ON login_logs(tenant_id)",
    "CREATE INDEX idx_login_logs_created ON login_logs(created_at)",
    "CREATE INDEX idx_login_logs_success ON login_logs(success)",
    "CREATE INDEX idx_login_logs_actor ON login_logs(actor_type)",
];

echo "<h2 style='font-family:Inter,sans-serif;'>Migration: Login Logs</h2>";
foreach ($queries as $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color:green;font-family:monospace;font-size:13px;'>OK: " . substr($sql, 0, 70) . "...</p>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate') || str_contains($msg, 'already exists')) {
            echo "<p style='color:orange;font-family:monospace;font-size:13px;'>SKIP (exists): " . substr($sql, 0, 70) . "...</p>";
        } else {
            echo "<p style='color:red;font-family:monospace;font-size:13px;'>ERROR: " . htmlspecialchars($msg) . "</p>";
        }
    }
}
echo "<h3 style='color:green;font-family:Inter,sans-serif;'>Migration terminee!</h3>";
echo "<p><a href='" . APP_BASE . "/admin/connections'>Voir les connexions</a></p>";
