<?php
// Comprehensive database column fix for SmartFix
include('includes/db.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix All Database Columns - SmartFix</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h2, h3 { color: #333; }
        .table-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Comprehensive Database Column Fix</h1>
        <div class="info">This script will check and fix ALL common missing column issues in your SmartFix database.</div>

<?php
$fixes_applied = 0;
$errors_found = 0;

// Function to safely add columns
function addColumnSafely($pdo, $table, $column, $spec, &$fixes_applied, &$errors_found) {
    try {
        // Check if column exists
        $check = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'";
        $result = $pdo->query($check);
        $exists = $result->fetchColumn();
        
        if (!$exists) {
            $sql = "ALTER TABLE $table ADD COLUMN $column $spec";
            $pdo->exec($sql);
            echo "<div class='success'>✅ Added $column to $table table</div>";
            $fixes_applied++;
        } else {
            echo "<div class='info'>ℹ️ $table.$column already exists</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error adding $column to $table: " . $e->getMessage() . "</div>";
        $errors_found++;
    }
}

// Function to create table safely
function createTableSafely($pdo, $table, $sql, &$fixes_applied, &$errors_found) {
    try {
        $check = "SHOW TABLES LIKE '$table'";
        $result = $pdo->query($check);
        
        if ($result->rowCount() == 0) {
            $pdo->exec($sql);
            echo "<div class='success'>✅ Created $table table</div>";
            $fixes_applied++;
        } else {
            echo "<div class='info'>ℹ️ $table table already exists</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error creating $table table: " . $e->getMessage() . "</div>";
        $errors_found++;
    }
}

try {
    echo "<div class='table-section'>";
    echo "<h2>🛒 Orders Table</h2>";
    
    // Create orders table if missing
    $orders_sql = "CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        tracking_number VARCHAR(20) UNIQUE,
        shipping_name VARCHAR(100) NOT NULL,
        shipping_phone VARCHAR(20) NOT NULL,
        shipping_email VARCHAR(100),
        shipping_address TEXT NOT NULL,
        shipping_city VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
        shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
        payment_method VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery',
        transport_id INT,
        notes TEXT,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    createTableSafely($pdo, 'orders', $orders_sql, $fixes_applied, $errors_found);
    
    // Add missing columns to orders table
    addColumnSafely($pdo, 'orders', 'user_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'tracking_number', 'VARCHAR(20) UNIQUE', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'shipping_province', 'VARCHAR(50) NOT NULL DEFAULT "Lusaka"', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'total_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'transport_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'notes', 'TEXT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'orders', 'status', 'ENUM("processing", "shipped", "in_transit", "out_for_delivery", "delivered", "cancelled") DEFAULT "processing"', $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>📧 Messages Table</h2>";
    
    // Create messages table if missing
    $messages_sql = "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        user_id INT,
        receiver_id INT,
        sender_type ENUM('user','admin','technician') DEFAULT 'user',
        receiver_type ENUM('user','admin','technician') DEFAULT 'admin',
        request_id INT,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    createTableSafely($pdo, 'messages', $messages_sql, $fixes_applied, $errors_found);
    
    // Add missing columns to messages table
    addColumnSafely($pdo, 'messages', 'sender_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'user_id', 'INT', $fixes_applied, $errors_found); // Alternative column name used by some files
    addColumnSafely($pdo, 'messages', 'receiver_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'request_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'timestamp', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'is_read', 'TINYINT(1) DEFAULT 0', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'subject', 'VARCHAR(255)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'sender_type', 'ENUM("user","admin","technician") DEFAULT "user"', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'messages', 'receiver_type', 'ENUM("user","admin","technician") DEFAULT "admin"', $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>🛍️ Products Table</h2>";
    
    // Add missing columns to products table
    addColumnSafely($pdo, 'products', 'stock', 'INT DEFAULT 0', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'products', 'category', 'VARCHAR(100)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'products', 'image', 'VARCHAR(255)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'products', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'products', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>👥 Users Table</h2>";
    
    // Add missing columns to users table
    addColumnSafely($pdo, 'users', 'email_verified', 'TINYINT(1) DEFAULT 0', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'users', 'verification_token', 'VARCHAR(255)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'users', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'users', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>🔧 Service Requests Table</h2>";
    
    // Add missing columns to service_requests table
    addColumnSafely($pdo, 'service_requests', 'technician_id', 'INT', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'status', 'VARCHAR(50) DEFAULT "pending"', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'priority', 'ENUM("low","medium","high","urgent") DEFAULT "medium"', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'latitude', 'DECIMAL(10,8)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'longitude', 'DECIMAL(11,8)', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'created_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    addColumnSafely($pdo, 'service_requests', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>🏗️ Supporting Tables</h2>";
    
    // Create order_items table
    $order_items_sql = "CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    createTableSafely($pdo, 'order_items', $order_items_sql, $fixes_applied, $errors_found);
    
    // Create replies table
    $replies_sql = "CREATE TABLE replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        reply_message TEXT NOT NULL,
        sender_id INT,
        sender_type ENUM('user','admin','technician') DEFAULT 'admin',
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    createTableSafely($pdo, 'replies', $replies_sql, $fixes_applied, $errors_found);
    
    // Create order_tracking table
    $tracking_sql = "CREATE TABLE order_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(100),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    createTableSafely($pdo, 'order_tracking', $tracking_sql, $fixes_applied, $errors_found);
    
    echo "</div>";
    
    echo "<div class='table-section'>";
    echo "<h2>🔄 Data Updates</h2>";
    
    // Update existing data
    try {
        $update_count = $pdo->exec("UPDATE messages SET timestamp = created_at WHERE timestamp IS NULL AND created_at IS NOT NULL");
        if ($update_count > 0) {
            echo "<div class='success'>✅ Updated $update_count message timestamps</div>";
            $fixes_applied++;
        } else {
            echo "<div class='info'>ℹ️ No message timestamp updates needed</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='info'>ℹ️ Message timestamp update not applicable: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Final summary
    echo "<div class='table-section'>";
    echo "<h2>📊 Summary</h2>";
    
    if ($fixes_applied > 0) {
        echo "<div class='success'><strong>🎉 Fixes Applied: $fixes_applied</strong></div>";
    }
    
    if ($errors_found > 0) {
        echo "<div class='error'><strong>⚠️ Errors Found: $errors_found</strong></div>";
    }
    
    if ($fixes_applied > 0 || $errors_found == 0) {
        echo "<div class='success'><strong>✅ Your database structure should now be complete!</strong></div>";
        echo "<div class='info'>All common column issues have been addressed. Your SmartFix system should work without column errors.</div>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Critical Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>Please check your database connection and permissions.</div>";
}
?>

        <hr style="margin: 30px 0;">
        <div style="text-align: center;">
            <h3>Test Your System</h3>
            <a href="shop/checkout.php" class="btn">🛒 Test Checkout</a>
            <a href="view_messages.php" class="btn">📧 View Messages</a>
            <a href="admin/admin_messages.php" class="btn">👤 Admin Messages</a>
            <a href="process_order.php" class="btn">📦 Process Orders</a>
            <a href="admin/admin_dashboard_new.php" class="btn">🏠 Admin Dashboard</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <strong>🎯 What This Fix Covers:</strong>
            <ul>
                <li>✅ Orders table: shipping_province, total_amount, tracking_number, etc.</li>
                <li>✅ Messages table: timestamp, sender_id, request_id, is_read, etc.</li>
                <li>✅ Products table: stock, category, image, timestamps</li>
                <li>✅ Users table: email verification, timestamps</li>
                <li>✅ Service requests table: technician_id, status, priority, GPS</li>
                <li>✅ Supporting tables: order_items, replies, order_tracking</li>
                <li>✅ Data consistency updates</li>
            </ul>
        </div>
    </div>
</body>
</html>