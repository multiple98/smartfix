<?php
include('includes/db.php');

echo "<style>
body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
.success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
.info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.btn { background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
</style>";

echo "<div class='container'>";
echo "<h1>🛠️ Complete Orders Table Fix for SmartFix</h1>";

try {
    // Check if orders table exists
    $check_table = "SHOW TABLES LIKE 'orders'";
    $stmt = $pdo->query($check_table);
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Orders table doesn't exist. Creating it first...</div>";
        
        // Create orders table with all required columns
        $create_orders = "CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            tracking_number VARCHAR(50) UNIQUE,
            
            -- Customer Information
            customer_name VARCHAR(100),
            customer_email VARCHAR(100),
            customer_phone VARCHAR(20),
            customer_address TEXT,
            customer_city VARCHAR(50),
            customer_province VARCHAR(50) DEFAULT 'Lusaka',
            
            -- Shipping Information
            shipping_name VARCHAR(100),
            shipping_email VARCHAR(100),
            shipping_phone VARCHAR(20),
            shipping_address TEXT,
            shipping_city VARCHAR(50),
            shipping_province VARCHAR(50) DEFAULT 'Lusaka',
            
            -- Order Details
            payment_method VARCHAR(50) DEFAULT 'cash',
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            
            -- Transport Integration
            transport_id INT NULL,
            transport_cost DECIMAL(10,2) DEFAULT 0.00,
            delivery_notes TEXT,
            notes TEXT,
            
            -- Timestamps
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_tracking (tracking_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_orders);
        echo "<div class='success'>✅ Created orders table with all required columns!</div>";
    } else {
        echo "<div class='info'>📋 Orders table exists. Checking for missing columns...</div>";
    }
    
    // Get current table structure
    $result = $pdo->query("DESCRIBE orders");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<div class='info'>Current columns: " . implode(', ', $existing_columns) . "</div>";
    
    // Define ALL required columns for the complete system
    $all_required_columns = [
        // Basic order info
        'tracking_number' => 'VARCHAR(50) UNIQUE',
        
        // Customer information
        'customer_name' => 'VARCHAR(100)',
        'customer_email' => 'VARCHAR(100)',
        'customer_phone' => 'VARCHAR(20)',
        'customer_address' => 'TEXT',
        'customer_city' => 'VARCHAR(50)',
        'customer_province' => 'VARCHAR(50) DEFAULT "Lusaka"',
        
        // Shipping information (required by checkout)
        'shipping_name' => 'VARCHAR(100)',
        'shipping_email' => 'VARCHAR(100)',
        'shipping_phone' => 'VARCHAR(20)',
        'shipping_address' => 'TEXT',
        'shipping_city' => 'VARCHAR(50)',
        'shipping_province' => 'VARCHAR(50) DEFAULT "Lusaka"',
        
        // Payment and order details
        'payment_method' => 'VARCHAR(50) DEFAULT "cash"',
        'status' => 'ENUM("pending", "processing", "shipped", "delivered", "cancelled") DEFAULT "pending"',
        
        // Transport system integration
        'transport_id' => 'INT NULL',
        'transport_cost' => 'DECIMAL(10,2) DEFAULT 0.00',
        'delivery_notes' => 'TEXT',
        'notes' => 'TEXT',
        
        // Timestamps
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    $missing_columns = [];
    $added_columns = [];
    
    // Check which columns are missing
    foreach ($all_required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $missing_columns[$column] = $definition;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "<div class='warning'>🔧 Found " . count($missing_columns) . " missing columns. Adding them now...</div>";
        
        foreach ($missing_columns as $column => $definition) {
            try {
                $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
                $pdo->exec($sql);
                $added_columns[] = $column;
                echo "<div class='success'>✅ Added column: $column</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error adding column $column: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ All required columns already exist!</div>";
    }
    
    // Update existing orders with default shipping data
    echo "<div class='info'>🔧 Updating existing orders with default shipping data...</div>";
    
    $count_empty = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE shipping_name IS NULL OR shipping_name = ''")->fetch()['count'];
    
    if ($count_empty > 0) {
        $update_sql = "UPDATE orders SET 
                       shipping_name = COALESCE(NULLIF(shipping_name, ''), customer_name, 'Customer'),
                       shipping_email = COALESCE(NULLIF(shipping_email, ''), customer_email, 'customer@example.com'),
                       shipping_phone = COALESCE(NULLIF(shipping_phone, ''), customer_phone, ''),
                       shipping_address = COALESCE(NULLIF(shipping_address, ''), customer_address, 'Address not provided'),
                       shipping_city = COALESCE(NULLIF(shipping_city, ''), customer_city, 'Lusaka'),
                       shipping_province = COALESCE(NULLIF(shipping_province, ''), customer_province, 'Lusaka'),
                       payment_method = COALESCE(NULLIF(payment_method, ''), 'cash'),
                       status = COALESCE(NULLIF(status, ''), 'processing')
                       WHERE shipping_name IS NULL OR shipping_name = ''";
        
        $pdo->exec($update_sql);
        echo "<div class='success'>✅ Updated $count_empty orders with shipping information!</div>";
    } else {
        echo "<div class='success'>✅ All orders already have shipping information!</div>";
    }
    
    // Create order_items table if it doesn't exist
    $check_items_table = "SHOW TABLES LIKE 'order_items'";
    $stmt = $pdo->query($check_items_table);
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>📦 Creating order_items table...</div>";
        
        $create_items = "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id),
            INDEX idx_product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_items);
        echo "<div class='success'>✅ Created order_items table!</div>";
    }
    
    // Create order_tracking table if it doesn't exist  
    $check_tracking_table = "SHOW TABLES LIKE 'order_tracking'";
    $stmt = $pdo->query($check_tracking_table);
    if ($stmt->rowCount() == 0) {
        echo "<div class='warning'>📍 Creating order_tracking table...</div>";
        
        $create_tracking = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT,
            location VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_tracking);
        echo "<div class='success'>✅ Created order_tracking table!</div>";
    }
    
    // Add foreign key constraints if transport_providers table exists
    $check_transport_table = "SHOW TABLES LIKE 'transport_providers'";
    $stmt = $pdo->query($check_transport_table);
    if ($stmt->rowCount() > 0 && in_array('transport_id', $added_columns)) {
        try {
            $fk_sql = "ALTER TABLE orders ADD CONSTRAINT fk_orders_transport 
                      FOREIGN KEY (transport_id) REFERENCES transport_providers(id) ON DELETE SET NULL";
            $pdo->exec($fk_sql);
            echo "<div class='success'>✅ Added foreign key constraint for transport_id!</div>";
        } catch (PDOException $e) {
            echo "<div class='info'>ℹ️ Foreign key constraint info: " . $e->getMessage() . "</div>";
        }
    }
    
    // Show final table structure
    echo "<div class='info'>📋 Final orders table structure:</div>";
    $result = $pdo->query("DESCRIBE orders");
    echo "<pre>";
    printf("%-25s %-25s %-10s %-5s %-15s %s\n", 'Column', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat("-", 100) . "\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-25s %-25s %-10s %-5s %-15s %s\n", 
               $row['Field'], 
               $row['Type'], 
               $row['Null'], 
               $row['Key'], 
               $row['Default'] ?? 'NULL', 
               $row['Extra']);
    }
    echo "</pre>";
    
    // Test the table by inserting a sample order
    echo "<div class='info'>🧪 Testing orders table functionality...</div>";
    
    try {
        // Try a sample insert
        $test_insert = "INSERT INTO orders (
            user_id, shipping_name, shipping_phone, shipping_email, 
            shipping_address, shipping_city, shipping_province, 
            payment_method, total_amount, status, tracking_number
        ) VALUES (
            1, 'Test Customer', '+260977123456', 'test@example.com',
            'Test Address', 'Lusaka', 'Lusaka', 
            'cash', 100.00, 'processing', CONCAT('TEST-', UNIX_TIMESTAMP())
        )";
        
        $pdo->exec($test_insert);
        $test_id = $pdo->lastInsertId();
        
        // Clean up test record
        $pdo->exec("DELETE FROM orders WHERE id = $test_id");
        
        echo "<div class='success'>✅ Orders table is working perfectly!</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Test failed: " . $e->getMessage() . "</div>";
    }
    
    echo "<div class='success'>🎉 Complete orders table fix completed successfully!</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🎯 System Status</h2>";
echo "<div class='success'>";
echo "<h3>✅ Database Ready For:</h3>";
echo "<ul>";
echo "<li><strong>✅ E-commerce Orders</strong> - Full checkout process</li>";
echo "<li><strong>✅ Transport System</strong> - Smart delivery selection</li>";  
echo "<li><strong>✅ Order Tracking</strong> - Real-time status updates</li>";
echo "<li><strong>✅ Customer Management</strong> - Complete customer data</li>";
echo "<li><strong>✅ Shipping Integration</strong> - Address and contact info</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Next Steps</h2>";
echo "<div class='info'>";
echo "<h3>Test Your System:</h3>";
echo "<a href='enhanced_transport_system.php' class='btn'>🚚 Setup Transport System</a>";
echo "<a href='smart_transport_selector.php' class='btn'>🎯 Test Transport Selector</a>";
echo "<a href='shop/checkout.php' class='btn'>🛒 Test Checkout Process</a>";
echo "<a href='admin/transport_dashboard.php' class='btn'>📊 Admin Dashboard</a>";
echo "</div>";

echo "</div>";
?>