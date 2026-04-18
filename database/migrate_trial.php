<?php
/**
 * Migration: Add 7-day trial + subscription columns to tenants table.
 * Run once: https://gestionpro.it.com/database/migrate_trial.php
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=gestionpro_master;charset=" . DB_CHARSET;
$db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2 style='font-family:Inter,sans-serif;'>Migration: Trial &amp; Subscription</h2>";

$steps = [
    // Columns
    ["ALTER TABLE tenants ADD COLUMN trial_ends_at DATETIME NULL AFTER expires_at", "add trial_ends_at"],
    ["ALTER TABLE tenants ADD COLUMN subscription_status ENUM('trial','active','expired','cancelled') NOT NULL DEFAULT 'trial' AFTER trial_ends_at", "add subscription_status"],
    ["ALTER TABLE tenants ADD COLUMN paid_at DATETIME NULL AFTER subscription_status", "add paid_at"],
    ["ALTER TABLE tenants ADD COLUMN subscription_paid_until DATETIME NULL AFTER paid_at", "add subscription_paid_until"],

    // Backfill existing tenants: assume they are grandfathered to 'active' so they don't get blocked
    ["UPDATE tenants SET subscription_status = 'active', paid_at = created_at, subscription_paid_until = DATE_ADD(created_at, INTERVAL 10 YEAR) WHERE subscription_status = 'trial' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)", "grandfather existing tenants"],

    // Indexes
    ["CREATE INDEX idx_tenants_status ON tenants(subscription_status)", "index on subscription_status"],
    ["CREATE INDEX idx_tenants_trial_ends ON tenants(trial_ends_at)", "index on trial_ends_at"],
];

foreach ($steps as [$sql, $label]) {
    try {
        $db->exec($sql);
        echo "<p style='color:green;font-family:monospace;font-size:13px;'>OK: $label</p>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Duplicate') || str_contains($msg, 'already exists') || str_contains($msg, 'exists')) {
            echo "<p style='color:orange;font-family:monospace;font-size:13px;'>SKIP ($label): already exists</p>";
        } else {
            echo "<p style='color:red;font-family:monospace;font-size:13px;'>ERROR ($label): " . htmlspecialchars($msg) . "</p>";
        }
    }
}

echo "<h3 style='color:green;font-family:Inter,sans-serif;'>Migration terminee!</h3>";
echo "<p><a href='" . APP_BASE . "/admin/tenants'>Voir les clients</a></p>";
