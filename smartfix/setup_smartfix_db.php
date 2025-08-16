<?php
$host = "localhost";
$user = "root";
$pass = "";

try {
    // Connect to MySQL server (without specifying database)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create the smartfix database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS smartfix");
    echo "✅ Database 'smartfix' created successfully!<br>";
    
    // Select the database
    $pdo->exec("USE smartfix");
    
    // Create essential tables for 2FA
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_2fa_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_trusted_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_fingerprint VARCHAR(64) NOT NULL,
        device_name VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_device (user_id, device_fingerprint)
    )");
    
    echo "✅ Essential 2FA tables created!<br>";
    echo "✅ SmartFix database setup complete!<br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>