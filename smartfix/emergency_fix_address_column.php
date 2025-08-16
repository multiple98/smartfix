<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Emergency Fix: Address Column - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚨 Emergency Fix: Address Column</h1>
        <div class='error'>
            <strong>Error:</strong> SQLSTATE[42S22]: Column not found: 1054 Unknown column 'address' in 'field list'
        </div>";

try {
    // Check if transport_providers table exists
    echo "<div class='info'>🔍 Checking transport_providers table...</div>";
    
    $check_table = "SHOW TABLES LIKE 'transport_providers'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='error'>❌ transport_providers table does not exist!</div>";
        echo "<div class='warning'>⚠️ Please run the complete transport system setup first.</div>";
        echo "<a href='enhanced_transport_system.php' class='btn'>🔧 Setup Transport System</a>";
    } else {
        echo "<div class='success'>✅ transport_providers table exists.</div>";
        
        // Check current table structure
        echo "<div class='info'>📋 Current table structure:</div>";
        echo "<pre>";
        $describe = $pdo->query("DESCRIBE transport_providers");
        $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
        $existing_columns = [];
        
        foreach ($columns as $column) {
            $existing_columns[] = $column['Field'];
            echo sprintf("%-20s %-20s %-10s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null']
            );
        }
        echo "</pre>";
        
        // Check for missing critical columns and add them
        $critical_columns = [
            'address' => "ADD COLUMN address TEXT",
            'contact' => "ADD COLUMN contact VARCHAR(50)",
            'email' => "ADD COLUMN email VARCHAR(100)",
            'description' => "ADD COLUMN description TEXT",
            'regions' => "ADD COLUMN regions TEXT",
            'cost_per_km' => "ADD COLUMN cost_per_km DECIMAL(8,2) DEFAULT 2.50",
            'base_cost' => "ADD COLUMN base_cost DECIMAL(8,2) DEFAULT 15.00",
            'estimated_days' => "ADD COLUMN estimated_days INT DEFAULT 2",
            'max_weight_kg' => "ADD COLUMN max_weight_kg DECIMAL(8,2) DEFAULT 50.00",
            'vehicle_type' => "ADD COLUMN vehicle_type ENUM('motorbike', 'car', 'van', 'truck') DEFAULT 'car'",
            'service_type' => "ADD COLUMN service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard'",
            'status' => "ADD COLUMN status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'",
            'rating' => "ADD COLUMN rating DECIMAL(3,2) DEFAULT 4.00",
            'latitude' => "ADD COLUMN latitude DECIMAL(10,8)",
            'longitude' => "ADD COLUMN longitude DECIMAL(11,8)",
            'operating_hours' => "ADD COLUMN operating_hours VARCHAR(100) DEFAULT '08:00-18:00'"
        ];
        
        $fixes_applied = 0;
        
        foreach ($critical_columns as $column_name => $alter_sql) {
            if (!in_array($column_name, $existing_columns)) {
                echo "<div class='warning'>❌ Missing column: $column_name - Adding it now...</div>";
                try {
                    $pdo->exec("ALTER TABLE transport_providers $alter_sql");
                    echo "<div class='success'>✅ Added $column_name column successfully!</div>";
                    $fixes_applied++;
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to add $column_name: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='success'>✅ Column $column_name exists.</div>";
            }
        }
        
        // Add sample data if table is empty
        echo "<div class='info'>🔍 Checking for sample data...</div>";
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_providers");
        $provider_count = $count_stmt->fetchColumn();
        
        if ($provider_count == 0) {
            echo "<div class='warning'>⚠️ No transport providers found. Adding sample data...</div>";
            
            $sample_providers = [
                ['Zampost Premium', '+260-211-123456', 'info@zampost.zm', 'National postal service with reliable delivery', 'All Provinces', 'Cairo Road, Lusaka', 2.00, 10.00, 3, 25.00, 'van', 'standard', 'active', 4.20, -15.3875, 28.3228, '08:00-17:00'],
                ['DHL Express Zambia', '+260-211-789012', 'lusaka@dhl.com', 'International courier with same-day service', 'Lusaka, Copperbelt', 'Manda Hill, Lusaka', 5.00, 25.00, 1, 30.00, 'van', 'express', 'active', 4.80, -15.3982, 28.3228, '08:00-18:00'],
                ['Local Riders Co-op', '+260-977-123456', 'riders@localcoop.zm', 'Community motorcycle delivery network', 'Lusaka Province', 'Kamwala Market, Lusaka', 1.50, 8.00, 1, 10.00, 'motorbike', 'same_day', 'active', 4.00, -15.4067, 28.2871, '07:00-19:00'],
                ['QuickDelivery Express', '+260-966-789012', 'quick@delivery.zm', 'Fast urban delivery service', 'Lusaka, Ndola', 'Levy Junction, Lusaka', 3.00, 15.00, 1, 20.00, 'car', 'express', 'active', 4.50, -15.3928, 28.3228, '08:00-20:00'],
                ['TransAfrica Logistics', '+260-211-345678', 'logistics@transafrica.zm', 'Heavy freight and long-distance transport', 'All Provinces', 'Industrial Area, Lusaka', 4.00, 30.00, 5, 1000.00, 'truck', 'standard', 'active', 4.30, -15.4314, 28.2923, '06:00-18:00']
            ];
            
            $insert_sql = "INSERT INTO transport_providers (name, contact, email, description, regions, address, cost_per_km, base_cost, estimated_days, max_weight_kg, vehicle_type, service_type, status, rating, latitude, longitude, operating_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insert_sql);
            
            foreach ($sample_providers as $provider) {
                try {
                    $stmt->execute($provider);
                    echo "<div class='success'>✅ Added provider: " . $provider[0] . "</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to add provider " . $provider[0] . ": " . $e->getMessage() . "</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ Found $provider_count transport providers.</div>";
        }
        
        // Test the fix
        echo "<div class='info'>🧪 Testing the address column fix...</div>";
        try {
            $test_query = "SELECT name, address FROM transport_providers LIMIT 1";
            $test_result = $pdo->query($test_query);
            $test_row = $test_result->fetch(PDO::FETCH_ASSOC);
            
            if ($test_row) {
                echo "<div class='success'>✅ Address column test PASSED!</div>";
                echo "<div class='info'>Sample: " . $test_row['name'] . " - " . ($test_row['address'] ?? 'No address') . "</div>";
            } else {
                echo "<div class='warning'>⚠️ No data to test with, but column exists.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Address column test FAILED: " . $e->getMessage() . "</div>";
        }
        
        // Summary
        if ($fixes_applied > 0) {
            echo "<div class='success'>
                    <h3>✅ Emergency Fix Complete!</h3>
                    <p><strong>Columns Added:</strong> $fixes_applied</p>
                    <p>The address column error should now be resolved.</p>
                  </div>";
        } else {
            echo "<div class='info'>
                    <h3>ℹ️ No Fixes Needed</h3>
                    <p>All required columns already exist.</p>
                  </div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ General Error: " . $e->getMessage() . "</div>";
}

echo "
        <h2>🎯 Next Steps</h2>
        <div class='info'>
            <p>After running this fix:</p>
            <ul>
                <li>Test the transport dashboard</li>
                <li>Try generating transport quotes</li>
                <li>Check the admin dashboard statistics</li>
                <li>Run the integration test</li>
            </ul>
        </div>
        
        <a href='admin/transport_dashboard.php' class='btn'>📊 Test Transport Dashboard</a>
        <a href='transport_quotes.php' class='btn'>🧪 Test Transport Quotes</a>
        <a href='admin/admin_dashboard_new.php' class='btn'>🏠 Admin Dashboard</a>
        <a href='admin/test_transport_integration.php' class='btn'>🔍 Integration Test</a>
        
    </div>
</body>
</html>";
?>