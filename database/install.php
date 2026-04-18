<?php
/**
 * Database installer - Run this once to set up the database.
 * Access: http://localhost/gestion_commerciale/database/install.php
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gestion_commerciale CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE gestion_commerciale");

    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');

    // Remove the CREATE DATABASE and USE lines (already done above)
    $schema = preg_replace('/CREATE DATABASE .+?;/s', '', $schema);
    $schema = preg_replace('/USE .+?;/', '', $schema);

    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $stmt) {
        if (!empty($stmt) && $stmt !== '--') {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Skip duplicate table/index errors
                if ($e->getCode() != '42S01' && $e->getCode() != '42000') {
                    echo "<p style='color:orange;'>Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }

    // Create admin user with proper bcrypt hash
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    $adminId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    // Check if admin exists
    $check = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (id, username, email, password_hash, full_name, role) VALUES (?, 'admin', 'admin@gestionpro.com', ?, 'Administrateur', 'admin')");
        $stmt->execute([$adminId, $adminHash]);
        echo "<p style='color:green;'>Admin user created (username: admin, password: admin123)</p>";
    } else {
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
        $stmt->execute([$adminHash]);
        echo "<p style='color:blue;'>Admin user already exists - password reset to admin123</p>";
    }

    echo "<h2 style='color:green; font-family: sans-serif;'>Base de donnees installee avec succes!</h2>";
    echo "<p style='font-family: sans-serif;'>Identifiants: <strong>admin</strong> / <strong>admin123</strong></p>";
    echo "<p style='font-family: sans-serif;'><a href='" . APP_BASE . "/login'>Se connecter</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red; font-family: sans-serif;'>Erreur d'installation</h2>";
    echo "<p style='color:red; font-family: sans-serif;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
