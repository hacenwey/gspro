<?php
/**
 * Master Database Installer
 * Creates the master database for multi-tenant management.
 * Run once: http://localhost/gestion_commerciale/database/install_master.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create master database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestionpro_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE gestionpro_master");

    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/master_schema.sql');
    $schema = preg_replace('/CREATE DATABASE .+?;/s', '', $schema);
    $schema = preg_replace('/USE .+?;/', '', $schema);

    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $stmt) {
        if (!empty($stmt) && $stmt !== '--') {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                if ($e->getCode() != '42S01' && $e->getCode() != '42000') {
                    echo "<p style='color:orange;'>Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }

    // Create default super admin
    $adminHash = password_hash('superadmin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $adminId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $check = $pdo->query("SELECT COUNT(*) FROM super_admins WHERE username = 'superadmin'")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("INSERT INTO super_admins (id, username, email, password_hash, full_name) VALUES (?, 'superadmin', 'admin@gestionpro.com', ?, 'Super Administrateur')");
        $stmt->execute([$adminId, $adminHash]);
        echo "<p style='color:green;'>&#10004; Super admin cree (username: <b>superadmin</b>, password: <b>superadmin123</b>)</p>";
    } else {
        $stmt = $pdo->prepare("UPDATE super_admins SET password_hash = ? WHERE username = 'superadmin'");
        $stmt->execute([$adminHash]);
        echo "<p style='color:blue;'>&#10004; Super admin existe deja - mot de passe reinitialise</p>";
    }

    echo "<h2 style='color:green; font-family: Inter, sans-serif;'>&#10004; Base master installee avec succes!</h2>";
    echo "<p style='font-family: Inter, sans-serif;'>Identifiants super-admin: <strong>superadmin</strong> / <strong>superadmin123</strong></p>";
    echo "<p style='font-family: Inter, sans-serif;'><a href='/gestion_commerciale/admin/login'>Acceder au panel admin</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red; font-family: Inter, sans-serif;'>Erreur d'installation</h2>";
    echo "<p style='color:red; font-family: Inter, sans-serif;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
