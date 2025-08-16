<?php
// Quick fix for shipping_province column
include('includes/db.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Fix - Shipping Province Column</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quick Fix: Shipping Province Column</h1>

<?php
try {
    echo "<div class='info'>Checking and fixing shipping_province column...</div>";
    
    // Check if column exists
    $check = "SHOW COLUMNS FROM orders LIKE 'shipping_province'";
    $result = $pdo->query($check);
    
    if ($result->rowCount() == 0) {
        // Column doesn't exist, add it
        $add_column = "ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka' AFTER shipping_city";
        $pdo->exec($add_column);
        echo "<div class='success'>✅ Successfully added shipping_province column to orders table!</div>";
    } else {
        echo "<div class='success'>✅ shipping_province column already exists!</div>";
    }
    
    // Verify the fix worked
    $verify = "SHOW COLUMNS FROM orders LIKE 'shipping_province'";
    $verify_result = $pdo->query($verify);
    
    if ($verify_result->rowCount() > 0) {
        echo "<div class='success'>🎉 Fix confirmed! The shipping_province column is now present in the orders table.</div>";
        echo "<div class='info'>Your checkout system should now work properly without the column error.</div>";
    }
    
} catch (PDOException $e) {
    if ($e->getCode() == '42S02') {
        // Table doesn't exist, create it
        try {
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
            echo "<div class='success'>✅ Created orders table with shipping_province column!</div>";
            
        } catch (PDOException $e2) {
            echo "<div class='error'>❌ Error creating table: " . $e2->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
    }
}
?>

        <div style="margin-top: 20px;">
            <a href="shop/checkout.php" class="btn">Test Checkout</a>
            <a href="index.php" class="btn">Back to Home</a>
        </div>
    </div>
</body>
</html>