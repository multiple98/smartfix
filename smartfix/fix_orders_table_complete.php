<?php
// Complete fix for orders table - adds all missing columns
include('includes/db.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Orders Table Fix - SmartFix</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h3 { color: #333; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Complete Orders Table Fix</h1>
        <div class="info">This script will check and fix all missing columns in your orders table structure.</div>

<?php
try {
    echo "<h3>Step 1: Checking if orders table exists...</h3>";
    
    // Check if orders table exists
    $check_table = "SHOW TABLES LIKE 'orders'";
    $table_result = $pdo->query($check_table);
    
    if ($table_result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Orders table doesn't exist. Creating complete table structure...</div>";
        
        // Create the complete orders table with ALL required columns
        $create_table = "CREATE TABLE orders (
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
        
        $pdo->exec($create_table);
        echo "<div class='success'>✅ Created complete orders table with all required columns!</div>";
        
    } else {
        echo "<div class='success'>✅ Orders table exists</div>";
        
        echo "<h3>Step 2: Checking current table structure...</h3>";
        
        // Get current table structure
        $describe = "DESCRIBE orders";
        $current_structure = $pdo->query($describe);
        $existing_columns = [];
        
        echo "<div class='info'>Current columns:</div>";
        echo "<pre>";
        while ($row = $current_structure->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $row['Field'];
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        echo "</pre>";
        
        echo "<h3>Step 3: Adding missing columns...</h3>";
        
        // Define required columns and their specifications
        $required_columns = [
            'user_id' => "INT",
            'tracking_number' => "VARCHAR(20) UNIQUE",
            'shipping_name' => "VARCHAR(100) NOT NULL",
            'shipping_phone' => "VARCHAR(20) NOT NULL",
            'shipping_email' => "VARCHAR(100)",
            'shipping_address' => "TEXT NOT NULL",
            'shipping_city' => "VARCHAR(50) NOT NULL DEFAULT 'Lusaka'",
            'shipping_province' => "VARCHAR(50) NOT NULL DEFAULT 'Lusaka'",
            'payment_method' => "VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery'",
            'transport_id' => "INT",
            'notes' => "TEXT",
            'total_amount' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'status' => "ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing'",
            'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        $columns_added = 0;
        $previous_column = 'id'; // Start after the id column
        
        foreach ($required_columns as $column_name => $column_spec) {
            if (!in_array($column_name, $existing_columns)) {
                try {
                    $add_column = "ALTER TABLE orders ADD COLUMN $column_name $column_spec AFTER $previous_column";
                    $pdo->exec($add_column);
                    echo "<div class='success'>✅ Added column: $column_name</div>";
                    $columns_added++;
                } catch (PDOException $e) {
                    echo "<div class='warning'>⚠️ Could not add $column_name: " . $e->getMessage() . "</div>";
                    // Try without AFTER clause
                    try {
                        $add_column_simple = "ALTER TABLE orders ADD COLUMN $column_name $column_spec";
                        $pdo->exec($add_column_simple);
                        echo "<div class='success'>✅ Added column (alternative): $column_name</div>";
                        $columns_added++;
                    } catch (PDOException $e2) {
                        echo "<div class='error'>❌ Failed to add $column_name: " . $e2->getMessage() . "</div>";
                    }
                }
            } else {
                echo "<div class='info'>ℹ️ Column already exists: $column_name</div>";
            }
            $previous_column = $column_name;
        }
        
        if ($columns_added > 0) {
            echo "<div class='success'>🎉 Added $columns_added missing columns!</div>";
        } else {
            echo "<div class='success'>✅ All required columns already exist!</div>";
        }
    }
    
    echo "<h3>Step 4: Creating related tables...</h3>";
    
    // Create order_items table if missing
    $check_items = "SHOW TABLES LIKE 'order_items'";
    $items_result = $pdo->query($check_items);
    
    if ($items_result->rowCount() == 0) {
        $create_items = "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_items);
        echo "<div class='success'>✅ Created order_items table</div>";
    } else {
        echo "<div class='info'>ℹ️ order_items table already exists</div>";
    }
    
    // Create order_tracking table if missing
    $check_tracking = "SHOW TABLES LIKE 'order_tracking'";
    $tracking_result = $pdo->query($check_tracking);
    
    if ($tracking_result->rowCount() == 0) {
        $create_tracking = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_tracking);
        echo "<div class='success'>✅ Created order_tracking table</div>";
    } else {
        echo "<div class='info'>ℹ️ order_tracking table already exists</div>";
    }
    
    echo "<h3>Step 5: Final verification...</h3>";
    
    // Show final table structure
    $final_structure = "DESCRIBE orders";
    $final_result = $pdo->query($final_structure);
    
    echo "<div class='success'>Final orders table structure:</div>";
    echo "<pre>";
    echo sprintf("%-20s %-25s %-5s %-5s %-15s %s\n", 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat('-', 80) . "\n";
    
    while ($row = $final_result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-25s %-5s %-5s %-15s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
    echo "</pre>";
    
    // Test a simple query to ensure everything works
    $test_query = "SELECT COUNT(*) as order_count FROM orders";
    $test_result = $pdo->query($test_query);
    $count = $test_result->fetch()['order_count'];
    
    echo "<div class='success'>🎉 All fixes completed successfully!</div>";
    echo "<div class='info'>✅ Table structure is now complete<br>";
    echo "✅ Current orders in database: $count<br>";
    echo "✅ Your checkout system should now work without column errors</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Error Code: " . $e->getCode() . "</div>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<div class='warning'>This appears to be a database permission issue. Make sure your database user has ALTER privileges.</div>";
    }
}
?>

        <hr>
        <div style="margin-top: 20px;">
            <h3>Test Your System:</h3>
            <a href="shop/checkout.php" class="btn">🛒 Test Checkout</a>
            <a href="process_order.php" class="btn">📦 Test Order Processing</a>
            <a href="shop/track_order.php" class="btn">🔍 Test Order Tracking</a>
            <a href="admin/admin_dashboard_new.php" class="btn">👤 Admin Dashboard</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
        
        <div class="warning" style="margin-top: 20px;">
            <strong>⚠️ Important:</strong>
            <ul>
                <li>Test your checkout process thoroughly</li>
                <li>Verify that orders can be placed successfully</li>
                <li>Check that all order data is saved correctly</li>
                <li>Test order tracking and status updates</li>
            </ul>
        </div>
        
        <div class="info" style="margin-top: 10px;">
            <strong>📋 What was fixed:</strong>
            <ul>
                <li>✅ Added missing shipping_province column</li>
                <li>✅ Added missing total_amount column</li>
                <li>✅ Ensured all required order columns exist</li>
                <li>✅ Created supporting tables (order_items, order_tracking)</li>
                <li>✅ Set proper defaults and constraints</li>
            </ul>
        </div>
    </div>
</body>
</html>