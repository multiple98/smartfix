<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Complete Transport Database Fix - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
        h2 { color: #28a745; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .step { margin: 20px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Complete Transport Database Fix</h1>
        <div class='info'>This script will create and fix all transport-related database tables and columns.</div>";

$fixes_applied = 0;
$errors_encountered = 0;

// Step 1: Create/Fix transport_providers table
echo "<div class='step'>
        <h2>Step 1: Transport Providers Table</h2>";

try {
    // Check if table exists
    $check_table = "SHOW TABLES LIKE 'transport_providers'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='warning'>❌ transport_providers table does not exist. Creating it...</div>";
        
        $create_providers = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50),
            email VARCHAR(100),
            description TEXT,
            regions TEXT,
            address TEXT,
            cost_per_km DECIMAL(8,2) DEFAULT 2.50,
            base_cost DECIMAL(8,2) DEFAULT 15.00,
            estimated_days INT DEFAULT 2,
            max_weight_kg DECIMAL(8,2) DEFAULT 50.00,
            vehicle_type ENUM('motorbike', 'car', 'van', 'truck') DEFAULT 'car',
            service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard',
            status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            rating DECIMAL(3,2) DEFAULT 4.00,
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            operating_hours VARCHAR(100) DEFAULT '08:00-18:00',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_service_type (service_type),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_providers);
        echo "<div class='success'>✅ transport_providers table created successfully!</div>";
        $fixes_applied++;
        
        // Add sample data
        $sample_providers = [
            ['Zampost Premium', '+260-211-123456', 'info@zampost.zm', 'National postal service with reliable delivery', 'All Provinces', 'Cairo Road, Lusaka', 2.00, 10.00, 3, 25.00, 'van', 'standard', 'active', 4.20, -15.3875, 28.3228, '08:00-17:00'],
            ['DHL Express Zambia', '+260-211-789012', 'lusaka@dhl.com', 'International courier with same-day service', 'Lusaka, Copperbelt', 'Manda Hill, Lusaka', 5.00, 25.00, 1, 30.00, 'van', 'express', 'active', 4.80, -15.3982, 28.3228, '08:00-18:00'],
            ['Local Riders Co-op', '+260-977-123456', 'riders@localcoop.zm', 'Community motorcycle delivery network', 'Lusaka Province', 'Kamwala Market, Lusaka', 1.50, 8.00, 1, 10.00, 'motorbike', 'same_day', 'active', 4.00, -15.4067, 28.2871, '07:00-19:00'],
            ['QuickDelivery Express', '+260-966-789012', 'quick@delivery.zm', 'Fast urban delivery service', 'Lusaka, Ndola', 'Levy Junction, Lusaka', 3.00, 15.00, 1, 20.00, 'car', 'express', 'active', 4.50, -15.3928, 28.3228, '08:00-20:00'],
            ['TransAfrica Logistics', '+260-211-345678', 'logistics@transafrica.zm', 'Heavy freight and long-distance transport', 'All Provinces', 'Industrial Area, Lusaka', 4.00, 30.00, 5, 1000.00, 'truck', 'standard', 'active', 4.30, -15.4314, 28.2923, '06:00-18:00']
        ];
        
        $insert_provider = "INSERT INTO transport_providers (name, contact, email, description, regions, address, cost_per_km, base_cost, estimated_days, max_weight_kg, vehicle_type, service_type, status, rating, latitude, longitude, operating_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_provider);
        
        foreach ($sample_providers as $provider) {
            $stmt->execute($provider);
        }
        
        echo "<div class='success'>✅ Sample transport providers added!</div>";
        
    } else {
        echo "<div class='success'>✅ transport_providers table exists.</div>";
        
        // Check for missing columns
        $required_columns = [
            'contact' => "ADD COLUMN contact VARCHAR(50)",
            'email' => "ADD COLUMN email VARCHAR(100)",
            'description' => "ADD COLUMN description TEXT",
            'regions' => "ADD COLUMN regions TEXT",
            'address' => "ADD COLUMN address TEXT",
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
            'operating_hours' => "ADD COLUMN operating_hours VARCHAR(100) DEFAULT '08:00-18:00'",
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $alter_sql) {
            $check_column = "SHOW COLUMNS FROM transport_providers LIKE '$column'";
            $column_result = $pdo->query($check_column);
            
            if ($column_result->rowCount() == 0) {
                echo "<div class='warning'>❌ $column column is missing. Adding it...</div>";
                $pdo->exec("ALTER TABLE transport_providers $alter_sql");
                echo "<div class='success'>✅ $column column added successfully!</div>";
                $fixes_applied++;
            }
        }
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error with transport_providers table: " . $e->getMessage() . "</div>";
    $errors_encountered++;
}
echo "</div>";

// Step 2: Create/Fix transport_quotes table
echo "<div class='step'>
        <h2>Step 2: Transport Quotes Table</h2>";

try {
    $check_table = "SHOW TABLES LIKE 'transport_quotes'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='warning'>❌ transport_quotes table does not exist. Creating it...</div>";
        
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
        $fixes_applied++;
        
    } else {
        echo "<div class='success'>✅ transport_quotes table exists.</div>";
        
        // Check for missing columns
        $required_columns = [
            'status' => "ADD COLUMN status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending'",
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $alter_sql) {
            $check_column = "SHOW COLUMNS FROM transport_quotes LIKE '$column'";
            $column_result = $pdo->query($check_column);
            
            if ($column_result->rowCount() == 0) {
                echo "<div class='warning'>❌ $column column is missing. Adding it...</div>";
                $pdo->exec("ALTER TABLE transport_quotes $alter_sql");
                echo "<div class='success'>✅ $column column added successfully!</div>";
                $fixes_applied++;
            }
        }
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error with transport_quotes table: " . $e->getMessage() . "</div>";
    $errors_encountered++;
}
echo "</div>";

// Step 3: Create/Fix delivery_tracking table
echo "<div class='step'>
        <h2>Step 3: Delivery Tracking Table</h2>";

try {
    $check_table = "SHOW TABLES LIKE 'delivery_tracking'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='warning'>❌ delivery_tracking table does not exist. Creating it...</div>";
        
        $create_tracking = "CREATE TABLE delivery_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            transport_provider_id INT NOT NULL,
            driver_name VARCHAR(100),
            driver_phone VARCHAR(20),
            vehicle_number VARCHAR(20),
            current_latitude DECIMAL(10,8),
            current_longitude DECIMAL(11,8),
            status ENUM('pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery') DEFAULT 'pickup_scheduled',
            estimated_arrival DATETIME,
            actual_delivery_time DATETIME,
            delivery_notes TEXT,
            customer_signature TEXT,
            proof_of_delivery VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (transport_provider_id) REFERENCES transport_providers(id) ON DELETE CASCADE,
            INDEX idx_order_id (order_id),
            INDEX idx_provider_id (transport_provider_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($create_tracking);
        echo "<div class='success'>✅ delivery_tracking table created successfully!</div>";
        $fixes_applied++;
        
    } else {
        echo "<div class='success'>✅ delivery_tracking table exists.</div>";
        
        // Check for missing columns
        $required_columns = [
            'status' => "ADD COLUMN status ENUM('pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery') DEFAULT 'pickup_scheduled'",
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $alter_sql) {
            $check_column = "SHOW COLUMNS FROM delivery_tracking LIKE '$column'";
            $column_result = $pdo->query($check_column);
            
            if ($column_result->rowCount() == 0) {
                echo "<div class='warning'>❌ $column column is missing. Adding it...</div>";
                $pdo->exec("ALTER TABLE delivery_tracking $alter_sql");
                echo "<div class='success'>✅ $column column added successfully!</div>";
                $fixes_applied++;
            }
        }
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error with delivery_tracking table: " . $e->getMessage() . "</div>";
    $errors_encountered++;
}
echo "</div>";

// Step 4: Test all queries
echo "<div class='step'>
        <h2>Step 4: Testing Database Queries</h2>";

$test_queries = [
    'Total Providers' => "SELECT COUNT(*) as count FROM transport_providers",
    'Active Providers' => "SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'",
    'Total Quotes' => "SELECT COUNT(*) as count FROM transport_quotes",
    'Pending Quotes' => "SELECT COUNT(*) as count FROM transport_quotes WHERE status = 'pending'",
    'Delivery Tracking' => "SELECT COUNT(*) as count FROM delivery_tracking",
    'Active Deliveries' => "SELECT COUNT(*) as count FROM delivery_tracking WHERE status IN ('pickup_scheduled', 'in_transit', 'out_for_delivery')"
];

foreach ($test_queries as $test_name => $query) {
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetchColumn();
        echo "<div class='success'>✅ $test_name: $result records</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ $test_name failed: " . $e->getMessage() . "</div>";
        $errors_encountered++;
    }
}
echo "</div>";

// Summary
echo "<div class='step'>
        <h2>🎯 Fix Summary</h2>";

if ($errors_encountered == 0) {
    echo "<div class='success'>
            <h3>✅ All Transport Database Issues Fixed!</h3>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors_encountered</p>
            <p>Your transport system database is now ready to use!</p>
          </div>";
} else {
    echo "<div class='warning'>
            <h3>⚠️ Some Issues Remain</h3>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors_encountered</p>
            <p>Please review the errors above and contact support if needed.</p>
          </div>";
}

echo "
        <h2>🚀 Next Steps</h2>
        <div class='info'>
            <p>Now that your database is fixed, you can:</p>
            <ul>
                <li>Test the transport quote system</li>
                <li>Use the admin transport dashboard</li>
                <li>Generate transport quotes for orders</li>
                <li>Track deliveries in real-time</li>
            </ul>
        </div>
        
        <a href='transport_quotes.php' class='btn'>🧪 Test Transport Quotes</a>
        <a href='admin/transport_dashboard.php' class='btn'>📊 Transport Dashboard</a>
        <a href='admin/test_transport_integration.php' class='btn'>🔍 Integration Test</a>
        <a href='admin/admin_dashboard_new.php' class='btn'>🏠 Admin Dashboard</a>
        
    </div>
</body>
</html>";
?>