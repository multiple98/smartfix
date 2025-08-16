<?php
// Fix order tracking table structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

echo "<h1>SmartFix Order Tracking Table Fix</h1>";
echo "<style>
body{font-family:Arial;margin:40px;background:#f5f5f5;} 
.success{color:green;background:#f0fff0;padding:15px;margin:10px 0;border-left:4px solid green;border-radius:5px;} 
.error{color:red;background:#fff0f0;padding:15px;margin:10px 0;border-left:4px solid red;border-radius:5px;} 
.info{color:blue;background:#f0f8ff;padding:15px;margin:10px 0;border-left:4px solid blue;border-radius:5px;}
.warning{color:orange;background:#fffaf0;padding:15px;margin:10px 0;border-left:4px solid orange;border-radius:5px;}
h2{color:#004080;border-bottom:2px solid #007BFF;padding-bottom:10px;margin-top:30px;}
.container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
</style>";

echo "<div class='container'>";

try {
    echo "<h2>1. Checking Order Tracking Table</h2>";
    
    // Check if order_tracking table exists
    $table_exists = $pdo->query("SHOW TABLES LIKE 'order_tracking'")->fetchColumn();
    
    if (!$table_exists) {
        echo "<div class='warning'>Order tracking table does not exist. Creating it...</div>";
        
        $create_table_sql = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order (order_id),
            INDEX idx_created_at (created_at)
        )";
        
        $pdo->exec($create_table_sql);
        echo "<div class='success'>✓ Order tracking table created successfully!</div>";
    } else {
        echo "<div class='info'>✓ Order tracking table exists</div>";
        
        // Check table structure
        echo "<h2>2. Checking Table Structure</h2>";
        $columns = $pdo->query("SHOW COLUMNS FROM order_tracking")->fetchAll(PDO::FETCH_ASSOC);
        
        $existing_columns = array_column($columns, 'Field');
        echo "<div class='info'>Current columns: " . implode(', ', $existing_columns) . "</div>";
        
        // Check for timestamp column (old structure)
        if (in_array('timestamp', $existing_columns)) {
            echo "<div class='warning'>Found old 'timestamp' column. Renaming to 'created_at'...</div>";
            
            try {
                $pdo->exec("ALTER TABLE order_tracking CHANGE timestamp created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                echo "<div class='success'>✓ Renamed 'timestamp' column to 'created_at'</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>Error renaming column: " . $e->getMessage() . "</div>";
            }
        }
        
        // Add missing columns
        $required_columns = [
            'created_at' => "ALTER TABLE order_tracking ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE order_tracking ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $sql) {
            if (!in_array($column, $existing_columns)) {
                try {
                    $pdo->exec($sql);
                    echo "<div class='success'>✓ Added '$column' column</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>Error adding '$column' column: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='info'>✓ Column '$column' already exists</div>";
            }
        }
        
        // Add indexes if they don't exist
        echo "<h2>3. Checking Indexes</h2>";
        try {
            $indexes = $pdo->query("SHOW INDEX FROM order_tracking")->fetchAll(PDO::FETCH_ASSOC);
            $index_names = array_column($indexes, 'Key_name');
            
            if (!in_array('idx_order', $index_names)) {
                $pdo->exec("ALTER TABLE order_tracking ADD INDEX idx_order (order_id)");
                echo "<div class='success'>✓ Added order_id index</div>";
            } else {
                echo "<div class='info'>✓ Order ID index exists</div>";
            }
            
            if (!in_array('idx_created_at', $index_names)) {
                $pdo->exec("ALTER TABLE order_tracking ADD INDEX idx_created_at (created_at)");
                echo "<div class='success'>✓ Added created_at index</div>";
            } else {
                echo "<div class='info'>✓ Created_at index exists</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='warning'>Could not check/add indexes: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>4. Testing Table Functionality</h2>";
    
    // Test insert
    try {
        $test_query = "INSERT INTO order_tracking (order_id, status, description, location) VALUES (0, 'TEST', 'Test entry', 'Test Location')";
        $pdo->exec($test_query);
        
        // Get the inserted ID and delete the test record
        $test_id = $pdo->lastInsertId();
        $pdo->exec("DELETE FROM order_tracking WHERE id = $test_id");
        
        echo "<div class='success'>✓ Table insert/delete functionality working</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>Table functionality test failed: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>5. Checking Other Required Tables</h2>";
    
    // Check other required tables
    $required_tables = [
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
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
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        'notifications' => "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($required_tables as $table_name => $sql) {
        $exists = $pdo->query("SHOW TABLES LIKE '$table_name'")->fetchColumn();
        if (!$exists) {
            try {
                $pdo->exec($sql);
                echo "<div class='success'>✓ Created table: $table_name</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>Error creating table $table_name: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='info'>✓ Table exists: $table_name</div>";
        }
    }
    
    echo "<h2>6. Final Structure Check</h2>";
    
    // Show final table structure
    $final_columns = $pdo->query("SHOW COLUMNS FROM order_tracking")->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='info'><strong>Final order_tracking table structure:</strong><br>";
    foreach ($final_columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Default']}<br>";
    }
    echo "</div>";
    
    echo "<div class='success'><h3>✓ Order tracking table fix completed successfully!</h3></div>";
    
    echo "<div class='info'>
    <strong>What was fixed:</strong><br>
    - Ensured order_tracking table exists<br>
    - Fixed column structure (timestamp → created_at)<br>
    - Added missing columns and indexes<br>
    - Verified all related tables exist<br>
    - Tested table functionality<br>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error during fix process: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='order.php?product_id=1' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Test Order Page</a> ";
echo "<a href='shop.php' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Visit Shop</a> ";
echo "<a href='admin/admin_dashboard_new.php' style='background:#17a2b8;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Admin Panel</a>";
?>