<?php
session_start();
require_once('../includes/db.php');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFix - Admin System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #007BFF; text-align: center; margin-bottom: 30px; }
        .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007BFF; border-radius: 5px; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .info { background: #d1ecf1; border-left-color: #17a2b8; color: #0c5460; }
        .code { background: #f1f3f4; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
        .btn { background: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 SmartFix Admin System Setup</h1>";

$setup_steps = [];
$errors = [];

// Step 1: Create admins table
try {
    $create_admins = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'super_admin') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($create_admins);
    $setup_steps[] = ["success", "✅ Admins table created/verified successfully"];
} catch (PDOException $e) {
    $errors[] = "❌ Failed to create admins table: " . $e->getMessage();
    $setup_steps[] = ["error", "❌ Failed to create admins table: " . $e->getMessage()];
}

// Step 2: Create default admin user
try {
    $check_admin = "SELECT COUNT(*) FROM admins WHERE username = 'admin'";
    $stmt = $pdo->prepare($check_admin);
    $stmt->execute();
    $admin_exists = $stmt->fetchColumn();
    
    if (!$admin_exists) {
        $default_password = password_hash('1234', PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO admins (username, email, password, name, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_admin);
        $stmt->execute(['admin', 'admin@smartfix.com', $default_password, 'Administrator', 'super_admin']);
        $setup_steps[] = ["success", "✅ Default admin user created (username: admin, password: 1234)"];
    } else {
        $setup_steps[] = ["info", "ℹ️ Default admin user already exists"];
    }
} catch (PDOException $e) {
    $errors[] = "❌ Failed to create default admin: " . $e->getMessage();
    $setup_steps[] = ["error", "❌ Failed to create default admin: " . $e->getMessage()];
}

// Step 3: Ensure service_requests table has all needed columns
try {
    $check_columns = [
        'technician_id' => "ALTER TABLE service_requests ADD COLUMN technician_id INT DEFAULT NULL",
        'assigned_at' => "ALTER TABLE service_requests ADD COLUMN assigned_at TIMESTAMP NULL",
        'status' => "ALTER TABLE service_requests ADD COLUMN status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'",
        'priority' => "ALTER TABLE service_requests ADD COLUMN priority ENUM('low', 'normal', 'high', 'emergency') DEFAULT 'normal'",
        'notes' => "ALTER TABLE service_requests ADD COLUMN notes TEXT",
        'created_at' => "ALTER TABLE service_requests ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE service_requests ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($check_columns as $column => $sql) {
        try {
            $check_column_query = "SHOW COLUMNS FROM service_requests LIKE '$column'";
            $stmt = $pdo->prepare($check_column_query);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $pdo->exec($sql);
                $setup_steps[] = ["success", "✅ Added '$column' column to service_requests table"];
            }
        } catch (PDOException $e) {
            // Column might already exist, continue
        }
    }
    $setup_steps[] = ["success", "✅ Service requests table structure verified"];
} catch (PDOException $e) {
    $errors[] = "⚠️ Issue with service_requests table: " . $e->getMessage();
    $setup_steps[] = ["warning", "⚠️ Issue with service_requests table: " . $e->getMessage()];
}

// Step 4: Ensure technicians table exists and has proper structure
try {
    $create_technicians = "CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) UNIQUE,
        specialization TEXT NOT NULL,
        regions TEXT NOT NULL,
        address TEXT,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        status ENUM('available', 'busy', 'offline') DEFAULT 'available',
        rating DECIMAL(3,2) DEFAULT 0.00,
        bio TEXT,
        avatar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($create_technicians);
    $setup_steps[] = ["success", "✅ Technicians table created/verified successfully"];
} catch (PDOException $e) {
    $errors[] = "❌ Failed to create technicians table: " . $e->getMessage();
    $setup_steps[] = ["error", "❌ Failed to create technicians table: " . $e->getMessage()];
}

// Step 5: Ensure products table has admin management columns
try {
    $product_columns = [
        'status' => "ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active'",
        'is_deleted' => "ALTER TABLE products ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE",
        'created_by' => "ALTER TABLE products ADD COLUMN created_by INT",
        'updated_by' => "ALTER TABLE products ADD COLUMN updated_by INT",
        'created_at' => "ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ];
    
    foreach ($product_columns as $column => $sql) {
        try {
            $check_column_query = "SHOW COLUMNS FROM products LIKE '$column'";
            $stmt = $pdo->prepare($check_column_query);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                $pdo->exec($sql);
                $setup_steps[] = ["success", "✅ Added '$column' column to products table"];
            }
        } catch (PDOException $e) {
            // Column might already exist, continue
        }
    }
    $setup_steps[] = ["success", "✅ Products table structure verified for admin management"];
} catch (PDOException $e) {
    $errors[] = "⚠️ Issue with products table: " . $e->getMessage();
    $setup_steps[] = ["warning", "⚠️ Issue with products table: " . $e->getMessage()];
}

// Step 6: Ensure notifications table exists
try {
    $create_notifications = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'service', 'order', 'system') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    )";
    $pdo->exec($create_notifications);
    $setup_steps[] = ["success", "✅ Notifications table created/verified successfully"];
} catch (PDOException $e) {
    $errors[] = "❌ Failed to create notifications table: " . $e->getMessage();
    $setup_steps[] = ["error", "❌ Failed to create notifications table: " . $e->getMessage()];
}

// Step 7: Add sample data if tables are empty
try {
    // Add sample technicians if none exist
    $tech_count = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
    if ($tech_count == 0) {
        $sample_technicians = [
            ['John Smith', '+1234567890', 'john@smartfix.com', 'Electronics, Smartphones', 'Downtown,Midtown', '123 Tech Street', 40.7128, -74.0060, 'available', 4.8, 'Experienced electronics technician with 10+ years experience'],
            ['Sarah Johnson', '+1234567891', 'sarah@smartfix.com', 'Computer Repair, Laptops', 'Uptown,Downtown', '456 Repair Ave', 40.7589, -73.9851, 'available', 4.6, 'Computer repair specialist focused on laptops and desktops'],
            ['Mike Wilson', '+1234567892', 'mike@smartfix.com', 'Home Appliances, HVAC', 'Suburbs,Midtown', '789 Service Blvd', 40.7282, -73.7949, 'available', 4.9, 'Home appliance and HVAC systems expert'],
        ];
        
        $insert_tech = "INSERT INTO technicians (name, phone, email, specialization, regions, address, latitude, longitude, status, rating, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_tech);
        
        foreach ($sample_technicians as $tech) {
            $stmt->execute($tech);
        }
        $setup_steps[] = ["success", "✅ Added sample technicians for testing"];
    }
    
    // Add sample products if none exist
    $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($product_count == 0) {
        $sample_products = [
            ['Smartphone Screen Protector', 'Premium tempered glass screen protector', 15.99, 'uploads/no-image.jpg', 'Electronics', 'active', 0],
            ['Phone Case', 'Durable protective phone case', 25.99, 'uploads/no-image.jpg', 'Electronics', 'active', 0],
            ['Laptop Cooling Pad', 'High-performance laptop cooling solution', 45.99, 'uploads/no-image.jpg', 'Computer Accessories', 'active', 0],
            ['USB-C Cable', 'Fast charging USB-C cable 6ft', 12.99, 'uploads/no-image.jpg', 'Electronics', 'active', 0]
        ];
        
        $insert_product = "INSERT INTO products (name, description, price, image, category, status, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_product);
        
        foreach ($sample_products as $product) {
            $stmt->execute($product);
        }
        $setup_steps[] = ["success", "✅ Added sample products for testing"];
    }
} catch (PDOException $e) {
    $setup_steps[] = ["warning", "⚠️ Could not add sample data: " . $e->getMessage()];
}

// Display all setup steps
foreach ($setup_steps as $step) {
    echo "<div class='step {$step[0]}'>{$step[1]}</div>";
}

if (empty($errors)) {
    echo "
    <div class='step success'>
        <h3>🎉 Admin System Setup Complete!</h3>
        <p>The SmartFix admin system has been successfully configured. Here's what you can do now:</p>
        <ul>
            <li><strong>Login as Admin:</strong> Go to <a href='../auth.php?form=admin'>Admin Login</a></li>
            <li><strong>Default Credentials:</strong> Username: <code>admin</code>, Password: <code>1234</code></li>
            <li><strong>Admin Dashboard:</strong> Access full platform management</li>
            <li><strong>Manage Users:</strong> View and manage all registered users</li>
            <li><strong>Manage Technicians:</strong> Add, edit, and assign technicians</li>
            <li><strong>Manage Service Requests:</strong> View and assign service requests</li>
            <li><strong>Manage Products:</strong> Add, edit, and manage shop products</li>
            <li><strong>Emergency Services:</strong> Handle urgent service requests</li>
            <li><strong>Reports & Analytics:</strong> View platform statistics</li>
        </ul>
    </div>
    
    <div class='step info'>
        <h3>🔐 Security Recommendations</h3>
        <ul>
            <li><strong>Change Default Password:</strong> Please change the default admin password immediately</li>
            <li><strong>Create Additional Admins:</strong> Add more admin users as needed</li>
            <li><strong>Regular Backups:</strong> Ensure database backups are configured</li>
            <li><strong>Monitor Access:</strong> Review admin access logs regularly</li>
        </ul>
    </div>
    
    <div style='text-align: center; margin-top: 30px;'>
        <a href='../auth.php?form=admin' class='btn'>🔐 Go to Admin Login</a>
        <a href='admin_dashboard_new.php' class='btn' style='background: #28a745; margin-left: 10px;'>📊 Go to Dashboard</a>
    </div>";
} else {
    echo "<div class='step error'><h3>❌ Setup encountered some issues</h3><p>Please review the errors above and try running the setup again.</p></div>";
}

echo "</div></body></html>";
?>