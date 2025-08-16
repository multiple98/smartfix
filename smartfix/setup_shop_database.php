<?php
// Comprehensive database setup for SmartFix Shop System
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('includes/db.php');

echo "<h1>SmartFix Shop Database Setup</h1>";
echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Test database connection
    echo "<div class='info'>Testing database connection...</div>";
    $test = $pdo->query("SELECT 1")->fetchColumn();
    echo "<div class='success'>✓ Database connection successful</div><br>";
    
    // Start transaction for all table creations
    $pdo->beginTransaction();
    
    // 1. Create/Update products table
    echo "<div class='info'>Setting up products table...</div>";
    try {
        $products_sql = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            image VARCHAR(255),
            category VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            is_deleted TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($products_sql);
        
        // Check if products exist, if not add sample data
        $count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_deleted = 0")->fetchColumn();
        if ($count == 0) {
            $sample_products = [
                ['iPhone Screen Protector', 'Premium tempered glass screen protector for iPhone', 25.00, 50, 'product_680e7adfa833f.jpg', 'Phone'],
                ['Samsung Battery', 'Original replacement battery for Samsung Galaxy series', 45.00, 30, 'product_680cf579dd01e.jpg', 'Phone'],
                ['Laptop Charger', 'Universal laptop charger 90W', 85.00, 20, 'product_680befd5abe56.jpg', 'Computer'],
                ['USB Cable', 'High-speed USB-C to USB-A cable', 15.00, 100, 'product_680cd28b5be22.png', 'Electronics'],
                ['Phone Case', 'Protective phone case for various models', 20.00, 75, 'product_680cf5a51ddc5.jpg', 'Phone'],
                ['Bluetooth Headphones', 'Wireless Bluetooth headphones with noise cancellation', 120.00, 25, 'product_680e022a33d64.jpg', 'Electronics']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image, category) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($sample_products as $product) {
                $stmt->execute($product);
            }
            echo "<div class='success'>✓ Added sample products</div>";
        }
        echo "<div class='success'>✓ Products table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with products table: " . $e->getMessage() . "</div>";
    }
    
    // 2. Create orders table
    echo "<div class='info'>Setting up orders table...</div>";
    try {
        $orders_sql = "CREATE TABLE IF NOT EXISTS orders (
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
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tracking (tracking_number),
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        )";
        $pdo->exec($orders_sql);
        echo "<div class='success'>✓ Orders table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with orders table: " . $e->getMessage() . "</div>";
    }
    
    // 3. Create order_items table
    echo "<div class='info'>Setting up order_items table...</div>";
    try {
        $order_items_sql = "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_product (product_id)
        )";
        $pdo->exec($order_items_sql);
        echo "<div class='success'>✓ Order items table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with order_items table: " . $e->getMessage() . "</div>";
    }
    
    // 4. Create order_tracking table
    echo "<div class='info'>Setting up order_tracking table...</div>";
    try {
        $order_tracking_sql = "CREATE TABLE IF NOT EXISTS order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_timestamp (timestamp)
        )";
        $pdo->exec($order_tracking_sql);
        echo "<div class='success'>✓ Order tracking table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with order_tracking table: " . $e->getMessage() . "</div>";
    }
    
    // 5. Create transport_providers table
    echo "<div class='info'>Setting up transport_providers table...</div>";
    try {
        $transport_sql = "CREATE TABLE IF NOT EXISTS transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50),
            estimated_days INT DEFAULT 3,
            price_per_km DECIMAL(10,2) DEFAULT 0.50,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($transport_sql);
        
        // Check if providers exist, if not add sample data
        $count = $pdo->query("SELECT COUNT(*) FROM transport_providers WHERE status = 'active'")->fetchColumn();
        if ($count == 0) {
            $providers = [
                ['Zampost', '+260 211 228228', 7, 5.00, 'National postal service - Reliable and affordable'],
                ['DHL Express', '+260 211 254254', 2, 25.00, 'International express courier - Fast delivery'],
                ['FedEx', '+260 211 256789', 3, 20.00, 'Fast and reliable international delivery'],
                ['Local Courier', '+260 977 123456', 1, 2.00, 'Same day delivery within Lusaka'],
                ['PostNet', '+260 211 123789', 4, 8.00, 'Reliable countrywide delivery service']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO transport_providers (name, contact, estimated_days, price_per_km, description) VALUES (?, ?, ?, ?, ?)");
            foreach ($providers as $provider) {
                $stmt->execute($provider);
            }
            echo "<div class='success'>✓ Added sample transport providers</div>";
        }
        echo "<div class='success'>✓ Transport providers table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with transport_providers table: " . $e->getMessage() . "</div>";
    }
    
    // 6. Create/Update notifications table
    echo "<div class='info'>Setting up notifications table...</div>";
    try {
        $notifications_sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_read (is_read),
            INDEX idx_created (created_at)
        )";
        $pdo->exec($notifications_sql);
        echo "<div class='success'>✓ Notifications table ready</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Error with notifications table: " . $e->getMessage() . "</div>";
    }
    
    // 7. Ensure users table exists for authentication
    echo "<div class='info'>Checking users table...</div>";
    try {
        $users_check = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
        if (!$users_check) {
            $users_sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($users_sql);
            echo "<div class='success'>✓ Users table created</div>";
        } else {
            echo "<div class='success'>✓ Users table exists</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error with users table: " . $e->getMessage() . "</div>";
    }
    
    // Commit all changes
    $pdo->commit();
    echo "<br><div class='success'><h2>✓ Database setup completed successfully!</h2></div>";
    
    // Show summary
    echo "<h3>Tables Summary:</h3>";
    $tables = ['products', 'orders', 'order_items', 'order_tracking', 'transport_providers', 'notifications', 'users'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<div>$table: $count records</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>$table: Error counting records</div>";
        }
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div class='error'><h2>Database setup failed!</h2>";
    echo "Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Please check your database connection settings in includes/db.php</div>";
} catch (Exception $e) {
    echo "<div class='error'><h2>Setup failed!</h2>";
    echo "Error: " . $e->getMessage() . "</div>";
}

echo "<br><hr><a href='shop.php'>Go to Shop</a> | <a href='admin/admin_dashboard_new.php'>Admin Dashboard</a>";
?>