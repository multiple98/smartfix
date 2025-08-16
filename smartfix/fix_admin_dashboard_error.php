<?php
// Fix Admin Dashboard Database Error
include('includes/db.php');

echo "<h2>Fixing Admin Dashboard Database Error</h2>";

try {
    // Check if users table exists
    echo "<h3>Step 1: Checking users table...</h3>";
    
    try {
        $stmt = $pdo->query('DESCRIBE users');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Users table exists with columns:</p><ul>";
        
        $existing_columns = [];
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
            $existing_columns[] = $column['Field'];
        }
        echo "</ul>";
        
        // Check if user_type column exists
        if (!in_array('user_type', $existing_columns)) {
            echo "<p style='color: red;'>❌ Missing 'user_type' column in users table</p>";
            echo "<p>Adding user_type column...</p>";
            
            $pdo->exec("ALTER TABLE users ADD COLUMN user_type ENUM('user','technician') DEFAULT 'user'");
            echo "<p style='color: green;'>✅ Added user_type column</p>";
        } else {
            echo "<p style='color: green;'>✅ user_type column exists</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Users table doesn't exist. Creating it...</p>";
        
        // Create users table with proper structure
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
        echo "<p style='color: green;'>✅ Users table created successfully</p>";
    }
    
    // Check if service_requests table exists
    echo "<h3>Step 2: Checking service_requests table...</h3>";
    
    try {
        $stmt = $pdo->query('DESCRIBE service_requests');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Service_requests table exists</p>";
        
        // Check for required columns
        $existing_columns = array_column($columns, 'Field');
        $required_columns = ['technician_id', 'request_date', 'created_at'];
        
        foreach ($required_columns as $req_col) {
            if (!in_array($req_col, $existing_columns)) {
                echo "<p style='color: orange;'>⚠️ Missing column: $req_col</p>";
                
                // Add missing columns
                switch ($req_col) {
                    case 'technician_id':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN technician_id INT(11) DEFAULT NULL");
                        echo "<p style='color: green;'>✅ Added technician_id column</p>";
                        break;
                    case 'request_date':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        echo "<p style='color: green;'>✅ Added request_date column</p>";
                        break;
                    case 'created_at':
                        $pdo->exec("ALTER TABLE service_requests ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        echo "<p style='color: green;'>✅ Added created_at column</p>";
                        break;
                }
            } else {
                echo "<p style='color: green;'>✅ Column $req_col exists</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Service_requests table doesn't exist. Creating it...</p>";
        
        // Create service_requests table
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
        echo "<p style='color: green;'>✅ Service_requests table created successfully</p>";
    }
    
    // Check if notifications table exists
    echo "<h3>Step 3: Checking notifications table...</h3>";
    
    try {
        $stmt = $pdo->query('DESCRIBE notifications');
        echo "<p>✅ Notifications table exists</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Notifications table doesn't exist. Creating it...</p>";
        
        // Create notifications table
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
        echo "<p style='color: green;'>✅ Notifications table created successfully</p>";
    }
    
    // Add some sample data if tables are empty
    echo "<h3>Step 4: Adding sample data if needed...</h3>";
    
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
        echo "<p style='color: green;'>✅ Sample users created</p>";
    } else {
        echo "<p>ℹ️ Users table already has data ($user_count users)</p>";
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
        echo "<p style='color: green;'>✅ Sample service requests created</p>";
    } else {
        echo "<p>ℹ️ Service_requests table already has data ($service_count requests)</p>";
    }
    
    // Check if admins table exists and create admin user
    echo "<h3>Step 5: Checking admins table...</h3>";
    
    try {
        $stmt = $pdo->query('DESCRIBE admins');
        echo "<p>✅ Admins table exists</p>";
        
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
            echo "<p style='color: green;'>✅ Default admin created (username: admin, password: admin123)</p>";
        } else {
            echo "<p>ℹ️ Default admin already exists</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Admins table doesn't exist. Creating it...</p>";
        
        // Create admins table
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
        echo "<p style='color: green;'>✅ Admins table created successfully</p>";
        
        // Create default admin
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = $pdo->prepare("
            INSERT INTO admins (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
        ");
        $insert_admin->execute([$default_password]);
        echo "<p style='color: green;'>✅ Default admin created (username: admin, password: admin123)</p>";
    }
    
    echo "<h3>🎉 Database Fix Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>The admin dashboard should now work properly.</strong></p>";
    echo "<h4>Login Credentials:</h4>";
    echo "<p><strong>Admin Login:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "URL: <a href='auth.php?form=admin'>Admin Login</a></p>";
    echo "<p><a href='admin/admin_dashboard_new.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Admin Dashboard</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>