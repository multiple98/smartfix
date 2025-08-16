<?php
// Run Database Fix Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

echo "Starting database fix...\n";

try {
    // Step 1: Check and fix users table
    echo "Step 1: Checking users table...\n";
    
    try {
        $stmt = $pdo->query('DESCRIBE users');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Users table exists\n";
        
        $existing_columns = array_column($columns, 'Field');
        
        // Check if user_type column exists
        if (!in_array('user_type', $existing_columns)) {
            echo "❌ Missing 'user_type' column. Adding it...\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN user_type ENUM('user','technician') DEFAULT 'user'");
            echo "✅ Added user_type column\n";
        } else {
            echo "✅ user_type column exists\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Users table doesn't exist. Creating it...\n";
        
        $create_users_table = "
        CREATE TABLE `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `user_type` enum('user','technician') DEFAULT 'user',
            `is_verified` tinyint(1) DEFAULT 0,
            `verification_token` varchar(255) DEFAULT NULL,
            `reset_token` varchar(255) DEFAULT NULL,
            `reset_expires` timestamp NULL DEFAULT NULL,
            `profile_image` varchar(255) DEFAULT NULL,
            `status` enum('active','inactive','suspended') DEFAULT 'active',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_users_table);
        echo "✅ Users table created successfully\n";
    }
    
    // Step 2: Check and fix service_requests table
    echo "Step 2: Checking service_requests table...\n";
    
    try {
        $stmt = $pdo->query('DESCRIBE service_requests');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Service_requests table exists\n";
        
        $existing_columns = array_column($columns, 'Field');
        $required_columns = ['technician_id', 'request_date', 'created_at'];
        
        foreach ($required_columns as $req_col) {
            if (!in_array($req_col, $existing_columns)) {
                echo "⚠️ Missing column: $req_col. Adding it...\n";
                
                switch ($req_col) {
                    case 'technician_id':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN technician_id INT(11) DEFAULT NULL");
                        echo "✅ Added technician_id column\n";
                        break;
                    case 'request_date':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        echo "✅ Added request_date column\n";
                        break;
                    case 'created_at':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        echo "✅ Added created_at column\n";
                        break;
                }
            } else {
                echo "✅ Column $req_col exists\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Service_requests table doesn't exist. Creating it...\n";
        
        $create_service_requests_table = "
        CREATE TABLE `service_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `technician_id` int(11) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `service_type` varchar(100) NOT NULL,
            `description` text NOT NULL,
            `location` varchar(255) DEFAULT NULL,
            `latitude` decimal(10,8) DEFAULT NULL,
            `longitude` decimal(11,8) DEFAULT NULL,
            `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
            `priority` enum('low','medium','high','emergency') DEFAULT 'medium',
            `estimated_cost` decimal(10,2) DEFAULT NULL,
            `actual_cost` decimal(10,2) DEFAULT NULL,
            `scheduled_date` datetime DEFAULT NULL,
            `completed_date` datetime DEFAULT NULL,
            `rating` int(1) DEFAULT NULL,
            `review` text DEFAULT NULL,
            `request_date` timestamp DEFAULT CURRENT_TIMESTAMP,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `technician_id` (`technician_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_service_requests_table);
        echo "✅ Service_requests table created successfully\n";
    }
    
    // Step 3: Check and fix notifications table
    echo "Step 3: Checking notifications table...\n";
    
    try {
        $stmt = $pdo->query('DESCRIBE notifications');
        echo "✅ Notifications table exists\n";
    } catch (PDOException $e) {
        echo "❌ Notifications table doesn't exist. Creating it...\n";
        
        $create_notifications_table = "
        CREATE TABLE `notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `admin_id` int(11) DEFAULT NULL,
            `type` enum('info','success','warning','error','service_update','payment','system') DEFAULT 'info',
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `action_url` varchar(255) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `admin_id` (`admin_id`),
            KEY `is_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_notifications_table);
        echo "✅ Notifications table created successfully\n";
    }
    
    // Step 4: Check and fix admins table
    echo "Step 4: Checking admins table...\n";
    
    try {
        $stmt = $pdo->query('DESCRIBE admins');
        echo "✅ Admins table exists\n";
        
        // Check if default admin exists
        $check_admin = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
        $check_admin->execute();
        
        if ($check_admin->fetchColumn() == 0) {
            $default_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin = $pdo->prepare("
                INSERT INTO admins (username, email, password, full_name, role) 
                VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
            ");
            $insert_admin->execute([$default_password]);
            echo "✅ Default admin created (username: admin, password: admin123)\n";
        } else {
            echo "ℹ️ Default admin already exists\n";
        }
        
    } catch (PDOException $e) {
        echo "❌ Admins table doesn't exist. Creating it...\n";
        
        $create_admins_table = "
        CREATE TABLE `admins` (
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
        
        $pdo->exec($create_admins_table);
        echo "✅ Admins table created successfully\n";
        
        // Create default admin
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = $pdo->prepare("
            INSERT INTO admins (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
        ");
        $insert_admin->execute([$default_password]);
        echo "✅ Default admin created (username: admin, password: admin123)\n";
    }
    
    // Step 5: Add sample data if needed
    echo "Step 5: Adding sample data if needed...\n";
    
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_count == 0) {
        $sample_users = [
            ['John Doe', 'john@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user'],
            ['Jane Smith', 'jane@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user'],
            ['Mike Tech', 'mike@example.com', password_hash('password123', PASSWORD_DEFAULT), 'technician'],
            ['Sarah Tech', 'sarah@example.com', password_hash('password123', PASSWORD_DEFAULT), 'technician']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
        foreach ($sample_users as $user) {
            $stmt->execute($user);
        }
        echo "✅ Sample users created\n";
    } else {
        echo "ℹ️ Users table already has data ($user_count users)\n";
    }
    
    $service_count = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
    if ($service_count == 0) {
        $sample_requests = [
            ['Test User', 'test@example.com', '1234567890', 'phone', 'Screen replacement needed', 'pending'],
            ['Demo Customer', 'demo@example.com', '0987654321', 'laptop', 'Laptop not starting', 'pending']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO service_requests (name, email, phone, service_type, description, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_requests as $request) {
            $stmt->execute($request);
        }
        echo "✅ Sample service requests created\n";
    } else {
        echo "ℹ️ Service_requests table already has data ($service_count requests)\n";
    }
    
    echo "\n🎉 Database Fix Complete!\n";
    echo "Admin Login Credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "URL: http://localhost/smartfix/auth.php?form=admin\n";
    echo "\nAdmin Dashboard: http://localhost/smartfix/admin/admin_dashboard_new.php\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>