<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Transport Quotes Table - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fix Transport Quotes Table</h1>";

try {
    // Check if transport_quotes table exists
    echo "<div class='info'>🔍 Checking transport_quotes table structure...</div>";
    
    $check_table = "SHOW TABLES LIKE 'transport_quotes'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='error'>❌ transport_quotes table does not exist. Creating it now...</div>";
        
        // Create the complete transport_quotes table
        $create_quotes = "CREATE TABLE transport_quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            transport_provider_id INT NOT NULL,
            pickup_address TEXT NOT NULL,
            delivery_address TEXT NOT NULL,
            distance_km DECIMAL(8,2),
            estimated_cost DECIMAL(10,2) NOT NULL,
            estimated_delivery_time INT,
            quote_valid_until DATETIME,
            status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (transport_provider_id) REFERENCES transport_providers(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id),
            INDEX idx_provider_id (transport_provider_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_quotes);
        echo "<div class='success'>✅ transport_quotes table created successfully!</div>";
    } else {
        echo "<div class='success'>✅ transport_quotes table exists.</div>";
        
        // Check if status column exists
        echo "<div class='info'>🔍 Checking for status column...</div>";
        
        $check_status = "SHOW COLUMNS FROM transport_quotes LIKE 'status'";
        $status_result = $pdo->query($check_status);
        
        if ($status_result->rowCount() == 0) {
            echo "<div class='error'>❌ status column is missing. Adding it now...</div>";
            
            $add_status = "ALTER TABLE transport_quotes 
                          ADD COLUMN status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending' 
                          AFTER quote_valid_until";
            $pdo->exec($add_status);
            
            echo "<div class='success'>✅ status column added successfully!</div>";
        } else {
            echo "<div class='success'>✅ status column exists.</div>";
        }
        
        // Check other important columns
        $required_columns = [
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $alter_sql) {
            $check_column = "SHOW COLUMNS FROM transport_quotes LIKE '$column'";
            $column_result = $pdo->query($check_column);
            
            if ($column_result->rowCount() == 0) {
                echo "<div class='error'>❌ $column column is missing. Adding it now...</div>";
                $pdo->exec("ALTER TABLE transport_quotes $alter_sql");
                echo "<div class='success'>✅ $column column added successfully!</div>";
            } else {
                echo "<div class='success'>✅ $column column exists.</div>";
            }
        }
    }
    
    // Show current table structure
    echo "<div class='info'>📋 Current transport_quotes table structure:</div>";
    echo "<pre>";
    $describe = $pdo->query("DESCRIBE transport_quotes");
    $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo sprintf("%-20s %-20s %-10s %-10s %-10s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'], 
            $column['Extra']
        );
    }
    echo "</pre>";
    
    // Test the status column
    echo "<div class='info'>🧪 Testing status column functionality...</div>";
    
    try {
        $test_query = "SELECT COUNT(*) as count FROM transport_quotes WHERE status = 'pending'";
        $test_result = $pdo->query($test_query);
        $count = $test_result->fetchColumn();
        echo "<div class='success'>✅ Status column test passed! Found $count pending quotes.</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Status column test failed: " . $e->getMessage() . "</div>";
    }
    
    // Check if transport_providers table exists (required for foreign key)
    echo "<div class='info'>🔍 Checking transport_providers table...</div>";
    $check_providers = "SHOW TABLES LIKE 'transport_providers'";
    $providers_result = $pdo->query($check_providers);
    
    if ($providers_result->rowCount() == 0) {
        echo "<div class='error'>❌ transport_providers table is missing. This is required for the transport system to work.</div>";
        echo "<div class='info'>💡 Please run the enhanced_transport_system.php script to create all required tables.</div>";
    } else {
        echo "<div class='success'>✅ transport_providers table exists.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ General Error: " . $e->getMessage() . "</div>";
}

echo "
        <h2>🎯 Next Steps</h2>
        <div class='info'>
            <p>If the transport_quotes table has been fixed, you can now:</p>
            <ul>
                <li>Test the transport quote system</li>
                <li>Generate quotes for orders</li>
                <li>Use the admin transport dashboard</li>
            </ul>
        </div>
        
        <a href='transport_quotes.php' class='btn'>🧪 Test Transport Quotes</a>
        <a href='admin/transport_dashboard.php' class='btn'>📊 Transport Dashboard</a>
        <a href='enhanced_transport_system.php' class='btn'>🔧 Full System Setup</a>
        <a href='admin/test_transport_integration.php' class='btn'>🔍 Integration Test</a>
        
    </div>
</body>
</html>";
?>