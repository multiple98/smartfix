<?php
// Complete Authentication System Setup
include('includes/db.php');

echo "<h2>SmartFix Authentication System Setup</h2>";

try {
    // 1. Create admins table if it doesn't exist
    $create_admins_table = "
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
    
    $pdo->exec($create_admins_table);
    echo "<p>✅ Admins table created/verified</p>";
    
    // 2. Check if default admin exists, if not create one
    $check_admin = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
    $check_admin->execute();
    
    if ($check_admin->fetchColumn() == 0) {
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $insert_admin = $pdo->prepare("
            INSERT INTO admins (username, email, password, full_name, role) 
            VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
        ");
        $insert_admin->execute([$default_password]);
        echo "<p>✅ Default admin created (username: admin, password: admin123)</p>";
    } else {
        echo "<p>ℹ️ Default admin already exists</p>";
    }
    
    // 3. Ensure users table has proper structure
    $create_users_table = "
    CREATE TABLE IF NOT EXISTS `users` (
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
    echo "<p>✅ Users table created/verified</p>";
    
    // 4. Create service_requests table if it doesn't exist
    $create_service_requests_table = "
    CREATE TABLE IF NOT EXISTS `service_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `technician_id` int(11) DEFAULT NULL,
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
        KEY `status` (`status`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_service_requests_table);
    echo "<p>✅ Service requests table created/verified</p>";
    
    // 5. Create notifications table
    $create_notifications_table = "
    CREATE TABLE IF NOT EXISTS `notifications` (
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
        KEY `is_read` (`is_read`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_notifications_table);
    echo "<p>✅ Notifications table created/verified</p>";
    
    // 6. Create products table for shop functionality
    $create_products_table = "
    CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `price` decimal(10,2) NOT NULL,
        `category` varchar(100) DEFAULT NULL,
        `image` varchar(255) DEFAULT NULL,
        `stock_quantity` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `category` (`category`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_products_table);
    echo "<p>✅ Products table created/verified</p>";
    
    // 7. Create orders table
    $create_orders_table = "
    CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        `shipping_address` text NOT NULL,
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
        `order_date` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_orders_table);
    echo "<p>✅ Orders table created/verified</p>";
    
    // 8. Create order_items table
    $create_order_items_table = "
    CREATE TABLE IF NOT EXISTS `order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`),
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_order_items_table);
    echo "<p>✅ Order items table created/verified</p>";
    
    // 9. Create messages table for communication
    $create_messages_table = "
    CREATE TABLE IF NOT EXISTS `messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `sender_type` enum('user','admin','technician') NOT NULL,
        `receiver_type` enum('user','admin','technician') NOT NULL,
        `subject` varchar(255) DEFAULT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) DEFAULT 0,
        `service_request_id` int(11) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`),
        KEY `service_request_id` (`service_request_id`),
        KEY `is_read` (`is_read`),
        FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_messages_table);
    echo "<p>✅ Messages table created/verified</p>";
    
    // 10. Create reviews table
    $create_reviews_table = "
    CREATE TABLE IF NOT EXISTS `reviews` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `technician_id` int(11) DEFAULT NULL,
        `service_request_id` int(11) DEFAULT NULL,
        `product_id` int(11) DEFAULT NULL,
        `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
        `review_text` text DEFAULT NULL,
        `is_approved` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `technician_id` (`technician_id`),
        KEY `service_request_id` (`service_request_id`),
        KEY `product_id` (`product_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`service_request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_reviews_table);
    echo "<p>✅ Reviews table created/verified</p>";
    
    // 11. Insert sample data if tables are empty
    
    // Sample users
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_count == 0) {
        $sample_users = [
            ['john_doe', 'john@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user', 1],
            ['jane_smith', 'jane@example.com', password_hash('password123', PASSWORD_DEFAULT), 'user', 1],
            ['tech_mike', 'mike@example.com', password_hash('password123', PASSWORD_DEFAULT), 'technician', 1],
            ['tech_sarah', 'sarah@example.com', password_hash('password123', PASSWORD_DEFAULT), 'technician', 1]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, user_type, is_verified) VALUES (?, ?, ?, ?, ?)");
        foreach ($sample_users as $user) {
            $stmt->execute($user);
        }
        echo "<p>✅ Sample users created</p>";
    }
    
    // Sample products
    $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($product_count == 0) {
        $sample_products = [
            ['iPhone Screen Protector', 'High-quality tempered glass screen protector', 15.99, 'Mobile Accessories', 'uploads/no-image.jpg', 50],
            ['Phone Repair Kit', 'Complete toolkit for phone repairs', 29.99, 'Tools', 'uploads/no-image.jpg', 25],
            ['Laptop Battery', 'Replacement battery for various laptop models', 89.99, 'Computer Parts', 'uploads/no-image.jpg', 15],
            ['USB Cable', 'High-speed USB charging cable', 12.99, 'Cables', 'uploads/no-image.jpg', 100]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_products as $product) {
            $stmt->execute($product);
        }
        echo "<p>✅ Sample products created</p>";
    }
    
    echo "<h3>🎉 Authentication System Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Login Credentials:</h4>";
    echo "<p><strong>Admin Login:</strong><br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    echo "URL: <a href='auth.php?form=admin'>Admin Login</a></p>";
    echo "<p><strong>Test User Login:</strong><br>";
    echo "Username: john_doe<br>";
    echo "Password: password123<br>";
    echo "URL: <a href='auth.php?form=login'>User Login</a></p>";
    echo "</div>";
    
    echo "<p><a href='auth.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>