<?php
/**
 * Migration: Polar.sh payment integration columns
 * Adds per-tenant references to the Polar customer and subscription.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=gestionpro_master;charset=" . DB_CHARSET;
$db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2 style='font-family:Inter,sans-serif;'>Migration: Polar.sh</h2>";

$statements = [
    'add polar_customer_id'       => "ALTER TABLE tenants ADD COLUMN polar_customer_id VARCHAR(64) NULL AFTER subscription_paid_until",
    'add polar_subscription_id'   => "ALTER TABLE tenants ADD COLUMN polar_subscription_id VARCHAR(64) NULL AFTER polar_customer_id",
    'add plan column'             => "ALTER TABLE tenants ADD COLUMN plan ENUM('free','starter','pro','enterprise') NOT NULL DEFAULT 'free' AFTER polar_subscription_id",
    'add last_invoice_at'         => "ALTER TABLE tenants ADD COLUMN last_invoice_at DATETIME NULL AFTER plan",
    'index polar_subscription_id' => "CREATE INDEX idx_polar_subscription_id ON tenants (polar_subscription_id)",
    'index polar_customer_id'     => "CREATE INDEX idx_polar_customer_id ON tenants (polar_customer_id)",
];

foreach ($statements as $label => $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color:green;font-family:monospace;font-size:13px;'>OK: $label</p>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'duplicate') !== false || stripos($msg, 'already exists') !== false) {
            echo "<p style='color:#888;font-family:monospace;font-size:13px;'>SKIP: $label (already applied)</p>";
        } else {
            echo "<p style='color:red;font-family:monospace;font-size:13px;'>ERROR: $label - " . htmlspecialchars($msg) . "</p>";
        }
    }
}

echo "<h3 style='color:green;font-family:Inter,sans-serif;'>Migration terminee!</h3>";
echo "<p><a href='/admin/tenants'>Voir les clients</a></p>";
