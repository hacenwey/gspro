<?php
/**
 * Migration: Add support tickets tables to master database.
 * Run once: http://localhost:8888/gestion_commerciale/database/migrate_tickets.php
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=gestionpro_master;charset=" . DB_CHARSET;
$db = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$queries = [
    "CREATE TABLE IF NOT EXISTS tickets (
        id CHAR(36) PRIMARY KEY,
        tenant_id CHAR(36) NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        user_email VARCHAR(100) NULL,
        subject VARCHAR(255) NOT NULL,
        category ENUM('bug','feature','billing','general','urgent') NOT NULL DEFAULT 'general',
        priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
        status ENUM('open','in_progress','waiting','resolved','closed') NOT NULL DEFAULT 'open',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        closed_at DATETIME NULL,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE INDEX IF NOT EXISTS idx_tickets_tenant ON tickets(tenant_id)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status)",
    "CREATE INDEX IF NOT EXISTS idx_tickets_created ON tickets(created_at)",

    "CREATE TABLE IF NOT EXISTS ticket_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticket_id CHAR(36) NOT NULL,
        sender_type ENUM('client','admin') NOT NULL,
        sender_name VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE INDEX IF NOT EXISTS idx_ticket_messages_ticket ON ticket_messages(ticket_id)",
];

echo "<h2>Migration: Support Tickets</h2>";
foreach ($queries as $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color:green;'>OK: " . substr($sql, 0, 60) . "...</p>";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            echo "<p style='color:orange;'>SKIP (exists): " . substr($sql, 0, 60) . "...</p>";
        } else {
            echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
        }
    }
}
echo "<h3 style='color:green;'>Migration complete!</h3>";
echo "<p><a href='/gestion_commerciale/admin/'>Retour admin</a></p>";
