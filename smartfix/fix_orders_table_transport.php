<?php
include('includes/db.php');

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
echo "<h1>🔧 Fix Orders Table for Transport System</h1>";

try {
    // First, check current orders table structure
    echo "<div class='info'>📋 Checking current orders table structure...</div>";
    
    $result = $pdo->query("DESCRIBE orders");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<pre>Current columns in orders table:\n" . implode(', ', $existing_columns) . "</pre>";
    
    // Define required columns for transport system
    $required_columns = [
        'shipping_name' => 'VARCHAR(100)',
        'shipping_address' => 'TEXT',
        'shipping_city' => 'VARCHAR(50)',
        'shipping_province' => 'VARCHAR(50) DEFAULT "Lusaka"',
        'shipping_phone' => 'VARCHAR(20)',
        'transport_id' => 'INT',
        'transport_cost' => 'DECIMAL(10,2) DEFAULT 0.00',
        'delivery_notes' => 'TEXT',
        'notes' => 'TEXT'
    ];
    
    $missing_columns = [];
    $added_columns = [];
    
    // Check which columns are missing
    foreach ($required_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            $missing_columns[$column] = $definition;
        }
    }
    
    if (!empty($missing_columns)) {
        echo "<div class='info'>🔧 Missing columns detected. Adding required columns...</div>";
        
        foreach ($missing_columns as $column => $definition) {
            try {
                $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
                $pdo->exec($sql);
                $added_columns[] = $column;
                echo "<div class='success'>✅ Added column: $column ($definition)</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error adding column $column: " . $e->getMessage() . "</div>";
            }
        }
        
        // Add foreign key constraint for transport_id if it was added
        if (in_array('transport_id', $added_columns)) {
            try {
                // First check if transport_providers table exists
                $check_table = "SHOW TABLES LIKE 'transport_providers'";
                $stmt = $pdo->query($check_table);
                if ($stmt->rowCount() > 0) {
                    $fk_sql = "ALTER TABLE orders ADD CONSTRAINT fk_orders_transport 
                              FOREIGN KEY (transport_id) REFERENCES transport_providers(id) ON DELETE SET NULL";
                    $pdo->exec($fk_sql);
                    echo "<div class='success'>✅ Added foreign key constraint for transport_id</div>";
                } else {
                    echo "<div class='info'>ℹ️ transport_providers table not found. Foreign key will be added after running enhanced_transport_system.php</div>";
                }
            } catch (PDOException $e) {
                // Foreign key might already exist or transport_providers table might not exist yet
                echo "<div class='info'>ℹ️ Foreign key constraint: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        echo "<div class='success'>✅ All required columns already exist in orders table!</div>";
    }
    
    // Check if we need to update existing records with default shipping information
    echo "<div class='info'>📋 Checking existing orders for missing shipping data...</div>";
    
    $check_orders = "SELECT COUNT(*) as count FROM orders WHERE (shipping_name IS NULL OR shipping_name = '') AND customer_name IS NOT NULL";
    $stmt = $pdo->query($check_orders);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<div class='info'>🔧 Found {$result['count']} orders with missing shipping data. Updating...</div>";
        
        // Update orders to copy customer data to shipping data where missing
        $update_sql = "UPDATE orders SET 
                       shipping_name = COALESCE(NULLIF(shipping_name, ''), customer_name, 'Customer'),
                       shipping_address = COALESCE(NULLIF(shipping_address, ''), customer_address, 'Address not provided'),
                       shipping_city = COALESCE(NULLIF(shipping_city, ''), customer_city, 'Lusaka'),
                       shipping_province = COALESCE(NULLIF(shipping_province, ''), customer_province, 'Lusaka'),
                       shipping_phone = COALESCE(NULLIF(shipping_phone, ''), customer_phone, customer_email)
                       WHERE (shipping_name IS NULL OR shipping_name = '')";
        
        $pdo->exec($update_sql);
        echo "<div class='success'>✅ Updated existing orders with shipping information</div>";
    } else {
        echo "<div class='success'>✅ All orders have proper shipping data</div>";
    }
    
    // Show final table structure
    echo "<div class='info'>📋 Final orders table structure:</div>";
    $result = $pdo->query("DESCRIBE orders");
    echo "<pre>";
    printf("%-20s %-20s %-10s %-5s %-10s %s\n", 'Column', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat("-", 80) . "\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        printf("%-20s %-20s %-10s %-5s %-10s %s\n", 
               $row['Field'], 
               $row['Type'], 
               $row['Null'], 
               $row['Key'], 
               $row['Default'], 
               $row['Extra']);
    }
    echo "</pre>";
    
    echo "<div class='success'>🎉 Orders table is now ready for the Enhanced Transport System!</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}

echo "<h2>🎯 Next Steps</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Run Transport System Setup:</strong> <a href='enhanced_transport_system.php' target='_blank'>enhanced_transport_system.php</a></li>";
echo "<li><strong>Test Smart Transport Selector:</strong> <a href='smart_transport_selector.php' target='_blank'>smart_transport_selector.php</a></li>";
echo "<li><strong>Try Transport Quotes:</strong> <a href='transport_quotes.php' target='_blank'>transport_quotes.php</a></li>";
echo "<li><strong>Check Admin Dashboard:</strong> <a href='admin/transport_dashboard.php' target='_blank'>admin/transport_dashboard.php</a></li>";
echo "</ol>";
echo "</div>";

echo "</div>";
?>