<?php
// Comprehensive database fix for SmartFix system
include 'includes/db.php';

echo "<h1>🔧 SmartFix Database Repair Tool</h1>";
echo "<p>This tool will check and create all missing database tables and columns.</p>";

$tables_created = 0;
$columns_added = 0;
$errors = [];

try {
    // 1. System Settings Table
    echo "<h2>1. System Settings Table</h2>";
    $check_settings = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($check_settings->rowCount() == 0) {
        $create_settings = "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) NOT NULL DEFAULT 'SmartFix',
            contact_email VARCHAR(255) NOT NULL DEFAULT 'admin@smartfix.com',
            contact_phone VARCHAR(50) DEFAULT '+260-97-000-0000',
            address TEXT DEFAULT 'Lusaka, Zambia',
            enable_sms_alert TINYINT(1) DEFAULT 1,
            enable_email_alert TINYINT(1) DEFAULT 1,
            maintenance_mode TINYINT(1) DEFAULT 0,
            max_file_size INT DEFAULT 5242880,
            allowed_file_types VARCHAR(255) DEFAULT 'jpg,jpeg,png,gif,pdf,doc,docx',
            timezone VARCHAR(50) DEFAULT 'Africa/Lusaka',
            currency VARCHAR(10) DEFAULT 'ZMW',
            tax_rate DECIMAL(5,2) DEFAULT 16.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_settings);
        
        // Insert default settings
        $insert_defaults = "INSERT INTO system_settings (
            company_name, contact_email, contact_phone, address, 
            enable_sms_alert, enable_email_alert
        ) VALUES (
            'SmartFix', 'admin@smartfix.com', '+260-97-000-0000', 
            'Lusaka, Zambia', 1, 1
        )";
        $pdo->exec($insert_defaults);
        
        echo "<p style='color: green;'>✓ System settings table created with defaults</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ System settings table exists</p>";
    }

    // 2. Users Table (check for missing columns)
    echo "<h2>2. Users Table</h2>";
    $check_users = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($check_users->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Check for missing columns
        $users_columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $required_user_columns = [
            'role' => 'ENUM("user", "admin", "technician") DEFAULT "user"',
            'user_type' => 'ENUM("user", "admin", "technician") DEFAULT "user"',
            'phone' => 'VARCHAR(20)',
            'address' => 'TEXT',
            'profile_image' => 'VARCHAR(255)',
            'is_active' => 'TINYINT(1) DEFAULT 1',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ];
        
        foreach ($required_user_columns as $column => $definition) {
            if (!in_array($column, $users_columns)) {
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                    echo "<p style='color: green;'>✓ Added column '$column' to users table</p>";
                    $columns_added++;
                } catch (PDOException $e) {
                    echo "<p style='color: orange;'>⚠ Could not add column '$column': " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>✗ Users table missing - this is critical!</p>";
        $errors[] = "Users table is missing";
    }

    // 3. Products Table
    echo "<h2>3. Products Table</h2>";
    $check_products = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($check_products->rowCount() == 0) {
        $create_products = "CREATE TABLE products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category VARCHAR(100),
            image VARCHAR(255),
            stock INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_products);
        echo "<p style='color: green;'>✓ Products table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Products table exists</p>";
        
        // Check for missing columns
        $product_columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        $required_product_columns = [
            'stock' => 'INT DEFAULT 0',
            'is_active' => 'TINYINT(1) DEFAULT 1',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($required_product_columns as $column => $definition) {
            if (!in_array($column, $product_columns)) {
                try {
                    $pdo->exec("ALTER TABLE products ADD COLUMN $column $definition");
                    echo "<p style='color: green;'>✓ Added column '$column' to products table</p>";
                    $columns_added++;
                } catch (PDOException $e) {
                    echo "<p style='color: orange;'>⚠ Could not add column '$column': " . $e->getMessage() . "</p>";
                }
            }
        }
    }

    // 4. Orders Table
    echo "<h2>4. Orders Table</h2>";
    $check_orders = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($check_orders->rowCount() == 0) {
        $create_orders = "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            tracking_number VARCHAR(20) UNIQUE,
            shipping_name VARCHAR(100) NOT NULL,
            shipping_phone VARCHAR(20) NOT NULL,
            shipping_email VARCHAR(100),
            shipping_address TEXT NOT NULL,
            shipping_city VARCHAR(50) NOT NULL,
            shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
            payment_method VARCHAR(50) NOT NULL,
            transport_id INT,
            transport_cost DECIMAL(10,2) DEFAULT 0.00,
            notes TEXT,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_orders);
        echo "<p style='color: green;'>✓ Orders table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Orders table exists</p>";
    }

    // 5. Order Items Table
    echo "<h2>5. Order Items Table</h2>";
    $check_order_items = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($check_order_items->rowCount() == 0) {
        $create_order_items = "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        $pdo->exec($create_order_items);
        echo "<p style='color: green;'>✓ Order items table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Order items table exists</p>";
    }

    // 6. Transport Providers Table
    echo "<h2>6. Transport Providers Table</h2>";
    $check_transport = $pdo->query("SHOW TABLES LIKE 'transport_providers'");
    if ($check_transport->rowCount() == 0) {
        $create_transport = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            regions TEXT NOT NULL,
            estimated_days INT NOT NULL DEFAULT 3,
            cost_per_km DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            base_cost DECIMAL(10,2) NOT NULL DEFAULT 20.00,
            max_weight_kg INT NOT NULL DEFAULT 50,
            service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard',
            vehicle_type VARCHAR(50) DEFAULT 'Van',
            rating DECIMAL(3,2) DEFAULT 4.0,
            operating_hours VARCHAR(100) DEFAULT '8:00 AM - 6:00 PM',
            latitude DECIMAL(10,8) DEFAULT -15.3875,
            longitude DECIMAL(11,8) DEFAULT 28.3228,
            status ENUM('active', 'inactive') DEFAULT 'active',
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_transport);
        
        // Add sample providers
        $sample_providers = [
            ['FastTrack Delivery', '+260-97-123-4567', 'info@fasttrack.zm', 'Lusaka,Copperbelt,Central', 2, 3.50, 25.00, 'express', 'Fast and reliable delivery service'],
            ['City Express', '+260-96-987-6543', 'orders@cityexpress.zm', 'Lusaka,Eastern,Southern', 3, 2.80, 20.00, 'standard', 'Affordable delivery service'],
            ['QuickMove Logistics', '+260-95-555-0123', 'support@quickmove.zm', 'Lusaka,Copperbelt,Northern', 1, 5.00, 40.00, 'same_day', 'Same-day delivery for urgent orders']
        ];
        
        $insert_provider = $pdo->prepare("INSERT INTO transport_providers (name, contact, email, regions, estimated_days, cost_per_km, base_cost, service_type, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sample_providers as $provider) {
            $insert_provider->execute($provider);
        }
        
        echo "<p style='color: green;'>✓ Transport providers table created with sample data</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Transport providers table exists</p>";
    }

    // 7. Messages Table
    echo "<h2>7. Messages Table</h2>";
    $check_messages = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($check_messages->rowCount() == 0) {
        $create_messages = "CREATE TABLE messages (
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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_messages);
        echo "<p style='color: green;'>✓ Messages table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Messages table exists</p>";
    }

    // 8. Notifications Table
    echo "<h2>8. Notifications Table</h2>";
    $check_notifications = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($check_notifications->rowCount() == 0) {
        $create_notifications = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_notifications);
        echo "<p style='color: green;'>✓ Notifications table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Notifications table exists</p>";
    }

    // 9. Order Tracking Table
    echo "<h2>9. Order Tracking Table</h2>";
    $check_tracking = $pdo->query("SHOW TABLES LIKE 'order_tracking'");
    if ($check_tracking->rowCount() == 0) {
        $create_tracking = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        $pdo->exec($create_tracking);
        echo "<p style='color: green;'>✓ Order tracking table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Order tracking table exists</p>";
    }

    // 10. Service Requests Table
    echo "<h2>10. Service Requests Table</h2>";
    $check_service_requests = $pdo->query("SHOW TABLES LIKE 'service_requests'");
    if ($check_service_requests->rowCount() == 0) {
        $create_service_requests = "CREATE TABLE service_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            technician_id INT,
            service_type VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            scheduled_date DATE,
            scheduled_time TIME,
            address TEXT,
            phone VARCHAR(20),
            estimated_cost DECIMAL(10,2),
            actual_cost DECIMAL(10,2),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_service_requests);
        echo "<p style='color: green;'>✓ Service requests table created</p>";
        $tables_created++;
    } else {
        echo "<p style='color: green;'>✓ Service requests table exists</p>";
    }

    // Summary
    echo "<h2>🎉 Database Repair Complete!</h2>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Tables Created:</strong> $tables_created</li>";
    echo "<li>✅ <strong>Columns Added:</strong> $columns_added</li>";
    echo "<li>✅ <strong>Errors:</strong> " . count($errors) . "</li>";
    echo "</ul>";
    
    if (count($errors) > 0) {
        echo "<h4>Errors encountered:</h4>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color: #721c24;'>❌ $error</li>";
        }
        echo "</ul>";
    }
    echo "</div>";

    echo "<h3>🔗 Quick Links:</h3>";
    echo "<ul>";
    echo "<li><a href='admin/settings.php'>System Settings</a></li>";
    echo "<li><a href='admin/messages.php'>Messages</a></li>";
    echo "<li><a href='shop/checkout.php'>Test Checkout</a></li>";
    echo "<li><a href='admin/admin_dashboard_new.php'>Admin Dashboard</a></li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Critical Database Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<p>Please check your database connection settings in <code>includes/db.php</code></p>";
    echo "</div>";
}
?>