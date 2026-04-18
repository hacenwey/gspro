<?php
/**
 * Migration: drop 'free' plan + add 'pending_payment' subscription status.
 *
 * - Any tenant still on plan='free' is upgraded to 'starter' (no data loss, just pricing bucket).
 * - plan ENUM becomes ('starter','pro','enterprise') default 'starter'.
 * - subscription_status gains 'pending_payment' for USD users who registered but haven't
 *   completed their Polar checkout yet.
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=gestionpro_master;charset=" . DB_CHARSET;
$db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2 style='font-family:Inter,sans-serif;'>Migration: no free plan + pending_payment</h2>";

$steps = [
    'upgrade existing free tenants'  => "UPDATE tenants SET plan = 'starter' WHERE plan = 'free'",
    'drop free from plan enum'       => "ALTER TABLE tenants MODIFY plan ENUM('starter','pro','enterprise') NOT NULL DEFAULT 'starter'",
    'add pending_payment status'     => "ALTER TABLE tenants MODIFY subscription_status ENUM('trial','active','expired','cancelled','pending_payment') NOT NULL DEFAULT 'trial'",
];

foreach ($steps as $label => $sql) {
    try {
        $affected = $db->exec($sql);
        $info = is_int($affected) ? " ($affected rows)" : '';
        echo "<p style='color:green;font-family:monospace;font-size:13px;'>OK: $label$info</p>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'duplicate') !== false) {
            echo "<p style='color:#888;font-family:monospace;font-size:13px;'>SKIP: $label (already applied)</p>";
        } else {
            echo "<p style='color:red;font-family:monospace;font-size:13px;'>ERROR: $label - " . htmlspecialchars($msg) . "</p>";
        }
    }
}

echo "<h3 style='color:green;font-family:Inter,sans-serif;'>Migration terminee!</h3>";
echo "<p><a href='/admin/tenants'>Voir les clients</a></p>";
