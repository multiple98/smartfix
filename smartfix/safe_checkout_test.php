<?php
session_start();
include('includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Test the orders table structure
echo "<style>
body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
.success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
.info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<div class='container'>";
echo "<h1>🧪 Safe Checkout Test</h1>";

try {
    // Check orders table structure
    echo "<div class='info'>📋 Checking orders table structure...</div>";
    
    $result = $pdo->query("DESCRIBE orders");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<pre>Available columns: " . implode(', ', $existing_columns) . "</pre>";
    
    // Required columns for checkout
    $required_for_checkout = [
        'user_id', 'total_amount', 'status', 'created_at',
        'shipping_name', 'shipping_phone', 'shipping_address', 
        'shipping_city', 'shipping_province', 'payment_method'
    ];
    
    $missing_required = [];
    foreach ($required_for_checkout as $col) {
        if (!in_array($col, $existing_columns)) {
            $missing_required[] = $col;
        }
    }
    
    if (empty($missing_required)) {
        echo "<div class='success'>✅ All required columns for checkout exist!</div>";
        
        // Test a safe insert
        echo "<div class='info'>🧪 Testing safe order creation...</div>";
        
        try {
            // Build dynamic insert query based on available columns
            $insert_columns = [];
            $insert_placeholders = [];
            $insert_values = [];
            
            // Always required
            $insert_columns[] = 'user_id';
            $insert_placeholders[] = '?';
            $insert_values[] = $_SESSION['user_id'];
            
            $insert_columns[] = 'total_amount';
            $insert_placeholders[] = '?';
            $insert_values[] = 99.99; // Test amount
            
            $insert_columns[] = 'status';
            $insert_placeholders[] = '?';
            $insert_values[] = 'processing';
            
            // Add shipping columns if they exist
            $shipping_fields = [
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '+260977123456',
                'shipping_address' => 'Test Address',
                'shipping_city' => 'Lusaka',
                'shipping_province' => 'Lusaka',
                'payment_method' => 'cash'
            ];
            
            foreach ($shipping_fields as $field => $value) {
                if (in_array($field, $existing_columns)) {
                    $insert_columns[] = $field;
                    $insert_placeholders[] = '?';
                    $insert_values[] = $value;
                }
            }
            
            // Add optional fields if they exist
            if (in_array('tracking_number', $existing_columns)) {
                $insert_columns[] = 'tracking_number';
                $insert_placeholders[] = '?';
                $insert_values[] = 'TEST-' . time();
            }
            
            if (in_array('transport_id', $existing_columns)) {
                $insert_columns[] = 'transport_id';
                $insert_placeholders[] = '?';
                $insert_values[] = null;
            }
            
            if (in_array('notes', $existing_columns)) {
                $insert_columns[] = 'notes';
                $insert_placeholders[] = '?';
                $insert_values[] = 'Test order - safe checkout';
            }
            
            $query = "INSERT INTO orders (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $insert_placeholders) . ")";
            
            echo "<pre>Test Query: $query</pre>";
            echo "<pre>Values: " . implode(', ', $insert_values) . "</pre>";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($insert_values);
            $test_order_id = $pdo->lastInsertId();
            
            echo "<div class='success'>✅ Successfully created test order #$test_order_id</div>";
            
            // Clean up test order
            $pdo->exec("DELETE FROM orders WHERE id = $test_order_id");
            echo "<div class='info'>🧹 Cleaned up test order</div>";
            
            echo "<div class='success'>🎉 Checkout system is ready to work!</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Test order creation failed: " . $e->getMessage() . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Missing required columns: " . implode(', ', $missing_required) . "</div>";
        echo "<div class='info'>Please run <a href='complete_orders_table_fix.php'>complete_orders_table_fix.php</a> first!</div>";
    }
    
    // Show available transport providers
    echo "<div class='info'>🚚 Checking transport providers...</div>";
    
    $check_transport = "SHOW TABLES LIKE 'transport_providers'";
    $stmt = $pdo->query($check_transport);
    if ($stmt->rowCount() > 0) {
        $providers = $pdo->query("SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'")->fetch();
        echo "<div class='success'>✅ Found {$providers['count']} active transport providers</div>";
    } else {
        echo "<div class='info'>ℹ️ Transport system not set up yet. Run <a href='enhanced_transport_system.php'>enhanced_transport_system.php</a></div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🎯 Recommended Actions</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Fix Database:</strong> <a href='complete_orders_table_fix.php'>Run Complete Orders Table Fix</a></li>";
echo "<li><strong>Setup Transport:</strong> <a href='enhanced_transport_system.php'>Initialize Transport System</a></li>";
echo "<li><strong>Test Checkout:</strong> <a href='shop/checkout.php'>Try Full Checkout Process</a></li>";
echo "<li><strong>Test Transport:</strong> <a href='smart_transport_selector.php'>Test Transport Selector</a></li>";
echo "</ol>";
echo "</div>";

echo "</div>";
?>