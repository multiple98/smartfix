<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Function to check if a column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // Fallback method for older MySQL versions or compatibility issues
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Field'] === $column) {
                    return true;
                }
            }
            return false;
        } catch (PDOException $e2) {
            return false;
        }
    }
}

// Check if products table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create products table
        $createTable = "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            category VARCHAR(50) NOT NULL,
            image VARCHAR(255),
            stock INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
        $message = "Products table created successfully!";
    } else {
        $updates = [];
        
        // Check for stock column
        if (!columnExists($pdo, 'products', 'stock')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN stock INT DEFAULT 1");
            $updates[] = "Added 'stock' column";
        }
        
        // Check for created_at column
        if (!columnExists($pdo, 'products', 'created_at')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            $updates[] = "Added 'created_at' column";
        }
        
        // Check for updated_at column
        if (!columnExists($pdo, 'products', 'updated_at')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            $updates[] = "Added 'updated_at' column";
        }
        
        // Check for status column
        if (!columnExists($pdo, 'products', 'status')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
            $updates[] = "Added 'status' column";
        }
        
        // Check for is_deleted column
        if (!columnExists($pdo, 'products', 'is_deleted')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0");
            $updates[] = "Added 'is_deleted' column";
        }
        
        // Check for is_featured column
        if (!columnExists($pdo, 'products', 'is_featured')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0");
            $updates[] = "Added 'is_featured' column";
        }
        
        // Check for is_new column
        if (!columnExists($pdo, 'products', 'is_new')) {
            $pdo->exec("ALTER TABLE products ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0");
            $updates[] = "Added 'is_new' column";
        }
        
        if (count($updates) > 0) {
            $message = "Products table updated: " . implode(", ", $updates);
        } else {
            $message = "Products table is already up to date.";
        }
    }
    
    $_SESSION['success_message'] = $message;
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to manage products page
header("Location: manage_products.php");
exit();
?>