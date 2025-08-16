<?php
// Simple script to create admin table and user
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$dbname = "smartfix";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creating Admin Table and User</h2>";
    
    // Create admins table
    $createTable = "
    CREATE TABLE IF NOT EXISTS `admins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL UNIQUE,
        `email` varchar(100) NOT NULL UNIQUE,
        `password` varchar(255) NOT NULL,
        `full_name` varchar(100) DEFAULT NULL,
        `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
        `is_active` tinyint(1) DEFAULT 1,
        `last_login` timestamp NULL DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createTable);
    echo "<p>✅ Admins table created successfully</p>";
    
    // Check if admin user already exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $checkAdmin->execute();
    
    if ($checkAdmin->fetchColumn() == 0) {
        // Create admin user with properly hashed password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insertAdmin = $pdo->prepare("
            INSERT INTO admins (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
        ");
        
        $insertAdmin->execute([$hashedPassword]);
        echo "<p>✅ Admin user created successfully</p>";
        echo "<p><strong>Login credentials:</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123</p>";
    } else {
        echo "<p>ℹ️ Admin user already exists</p>";
    }
    
    // Test the login
    $testLogin = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = 'admin'");
    $testLogin->execute();
    $admin = $testLogin->fetch();
    
    if ($admin && password_verify('admin123', $admin['password'])) {
        echo "<p>✅ Password verification test successful</p>";
    } else {
        echo "<p>❌ Password verification test failed</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='auth.php?form=admin' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Admin Login</a></p>";
    echo "<p><a href='admin/admin_dashboard_new.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    // If database doesn't exist, try to create it
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<p>Trying to create database...</p>";
        try {
            $pdo_create = new PDO("mysql:host=$host", $username, $password);
            $pdo_create->exec("CREATE DATABASE IF NOT EXISTS smartfix");
            echo "<p>✅ Database 'smartfix' created. Please refresh this page.</p>";
        } catch (PDOException $e2) {
            echo "<p style='color: red;'>❌ Could not create database: " . $e2->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin User - SmartFix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h2 { color: #007BFF; }
        p { margin: 10px 0; }
        a { margin-right: 10px; }
    </style>
</head>
<body>
</body>
</html>