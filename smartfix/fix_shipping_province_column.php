<?php
session_start();
include('includes/db.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Shipping Province Column - SmartFix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1 { color: #333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin: 10px 5px;
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Shipping Province Column</h1>
        <div class="info">This script will add the missing shipping_province column to the orders table.</div>

<?php

try {
    echo "<h3>Checking orders table structure...</h3>";
    
    // Check if orders table exists
    $check_table = "SHOW TABLES LIKE 'orders'";
    $table_result = $pdo->query($check_table);
    
    if ($table_result->rowCount() == 0) {
        echo "<div class='error'>❌ Orders table doesn't exist. Creating complete table structure...</div>";
        
        // Create the complete orders table with all required columns
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
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($create_table);
        echo "<div class='success'>✅ Created orders table with shipping_province column</div>";
        
    } else {
        echo "<div class='success'>✅ Orders table exists</div>";
        
        // Check if shipping_province column exists
        $check_column = "SHOW COLUMNS FROM orders LIKE 'shipping_province'";
        $column_result = $pdo->query($check_column);
        
        if ($column_result->rowCount() == 0) {
            echo "<div class='warning'>⚠️ shipping_province column is missing. Adding it now...</div>";
            
            // Add the shipping_province column
            $add_column = "ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka' AFTER shipping_city";
            $pdo->exec($add_column);
            
            echo "<div class='success'>✅ Added shipping_province column to orders table</div>";
        } else {
            echo "<div class='success'>✅ shipping_province column already exists</div>";
        }
    }
    
    // Check if order_items table exists
    echo "<h3>Checking order_items table...</h3>";
    $check_items_table = "SHOW TABLES LIKE 'order_items'";
    $items_result = $pdo->query($check_items_table);
    
    if ($items_result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ order_items table missing. Creating it...</div>";
        
        $create_items = "CREATE TABLE order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_items);
        echo "<div class='success'>✅ Created order_items table</div>";
    } else {
        echo "<div class='success'>✅ order_items table exists</div>";
    }
    
    // Check if order_tracking table exists
    echo "<h3>Checking order_tracking table...</h3>";
    $check_tracking_table = "SHOW TABLES LIKE 'order_tracking'";
    $tracking_result = $pdo->query($check_tracking_table);
    
    if ($tracking_result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ order_tracking table missing. Creating it...</div>";
        
        $create_tracking = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_tracking);
        echo "<div class='success'>✅ Created order_tracking table</div>";
    } else {
        echo "<div class='success'>✅ order_tracking table exists</div>";
    }
    
    // Show final table structure
    echo "<h3>Final orders table structure:</h3>";
    $describe = "DESCRIBE orders";
    $structure = $pdo->query($describe);
    
    echo "<pre>";
    echo "Field\t\t\tType\t\t\tNull\tKey\tDefault\tExtra\n";
    echo "-----------------------------------------------------------------------\n";
    while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s\t%-20s\t%s\t%s\t%s\t%s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
    echo "</pre>";
    
    echo "<div class='success'><strong>🎉 All fixes completed successfully!</strong></div>";
    echo "<div class='info'>The shipping_province column has been added and your checkout system should now work properly.</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Error Code: " . $e->getCode() . "</div>";
    
    // If it's a foreign key constraint error, provide guidance
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo "<div class='warning'>This appears to be a foreign key constraint issue. You may need to:</div>";
        echo "<ul>";
        echo "<li>Remove existing foreign keys first</li>";
        echo "<li>Clean up orphaned records</li>";
        echo "<li>Then re-add the constraints</li>";
        echo "</ul>";
    }
}

?>

        <hr>
        <div style="margin-top: 20px;">
            <a href="shop/checkout.php" class="btn">Test Checkout</a>
            <a href="admin/admin_dashboard_new.php" class="btn">Admin Dashboard</a>
            <a href="index.php" class="btn">Home</a>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <strong>Next Steps:</strong>
            <ul>
                <li>Test the checkout process to ensure it works</li>
                <li>Check that orders can be placed successfully</li>
                <li>Verify that the shipping province field is saved correctly</li>
            </ul>
        </div>
    </div>
</body>
</html>