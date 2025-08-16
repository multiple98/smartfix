<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Quick Fix Missing Columns - SmartFix</title>
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>⚡ Quick Fix: Missing Columns</h1>
        <div class='info'>Adding all missing columns to transport_providers table...</div>";

$fixes_applied = 0;
$errors = 0;

// Define the missing columns that need to be added
$missing_columns = [
    'address' => "ALTER TABLE transport_providers ADD COLUMN address TEXT",
    'base_cost' => "ALTER TABLE transport_providers ADD COLUMN base_cost DECIMAL(8,2) DEFAULT 15.00",
    'max_weight_kg' => "ALTER TABLE transport_providers ADD COLUMN max_weight_kg DECIMAL(8,2) DEFAULT 50.00",
    'vehicle_type' => "ALTER TABLE transport_providers ADD COLUMN vehicle_type ENUM('motorbike', 'car', 'van', 'truck') DEFAULT 'car'",
    'service_type' => "ALTER TABLE transport_providers ADD COLUMN service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard'",
    'status' => "ALTER TABLE transport_providers ADD COLUMN status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active'",
    'rating' => "ALTER TABLE transport_providers ADD COLUMN rating DECIMAL(3,2) DEFAULT 4.00",
    'latitude' => "ALTER TABLE transport_providers ADD COLUMN latitude DECIMAL(10,8)",
    'longitude' => "ALTER TABLE transport_providers ADD COLUMN longitude DECIMAL(11,8)",
    'operating_hours' => "ALTER TABLE transport_providers ADD COLUMN operating_hours VARCHAR(100) DEFAULT '08:00-18:00'",
    'updated_at' => "ALTER TABLE transport_providers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($missing_columns as $column_name => $sql) {
    try {
        echo "<div class='info'>Adding column: $column_name...</div>";
        $pdo->exec($sql);
        echo "<div class='success'>✅ Successfully added $column_name column!</div>";
        $fixes_applied++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<div class='warning'>⚠️ Column $column_name already exists - skipping.</div>";
        } else {
            echo "<div class='error'>❌ Failed to add $column_name: " . $e->getMessage() . "</div>";
            $errors++;
        }
    }
}

// Now add sample data if the table is empty
try {
    echo "<div class='info'>Checking for existing data...</div>";
    $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_providers");
    $provider_count = $count_stmt->fetchColumn();
    
    if ($provider_count == 0) {
        echo "<div class='warning'>No transport providers found. Adding sample data...</div>";
        
        // Sample providers with all required fields
        $sample_providers = [
            [
                'name' => 'Zampost Premium',
                'contact' => '+260-211-123456',
                'email' => 'info@zampost.zm',
                'description' => 'National postal service with reliable delivery',
                'regions' => 'All Provinces',
                'address' => 'Cairo Road, Lusaka',
                'cost_per_km' => 2.00,
                'base_cost' => 10.00,
                'estimated_days' => 3,
                'max_weight_kg' => 25.00,
                'vehicle_type' => 'van',
                'service_type' => 'standard',
                'status' => 'active',
                'rating' => 4.20,
                'latitude' => -15.3875,
                'longitude' => 28.3228,
                'operating_hours' => '08:00-17:00'
            ],
            [
                'name' => 'DHL Express Zambia',
                'contact' => '+260-211-789012',
                'email' => 'lusaka@dhl.com',
                'description' => 'International courier with same-day service',
                'regions' => 'Lusaka, Copperbelt',
                'address' => 'Manda Hill, Lusaka',
                'cost_per_km' => 5.00,
                'base_cost' => 25.00,
                'estimated_days' => 1,
                'max_weight_kg' => 30.00,
                'vehicle_type' => 'van',
                'service_type' => 'express',
                'status' => 'active',
                'rating' => 4.80,
                'latitude' => -15.3982,
                'longitude' => 28.3228,
                'operating_hours' => '08:00-18:00'
            ],
            [
                'name' => 'Local Riders Co-op',
                'contact' => '+260-977-123456',
                'email' => 'riders@localcoop.zm',
                'description' => 'Community motorcycle delivery network',
                'regions' => 'Lusaka Province',
                'address' => 'Kamwala Market, Lusaka',
                'cost_per_km' => 1.50,
                'base_cost' => 8.00,
                'estimated_days' => 1,
                'max_weight_kg' => 10.00,
                'vehicle_type' => 'motorbike',
                'service_type' => 'same_day',
                'status' => 'active',
                'rating' => 4.00,
                'latitude' => -15.4067,
                'longitude' => 28.2871,
                'operating_hours' => '07:00-19:00'
            ]
        ];
        
        $insert_sql = "INSERT INTO transport_providers (name, contact, email, description, regions, address, cost_per_km, base_cost, estimated_days, max_weight_kg, vehicle_type, service_type, status, rating, latitude, longitude, operating_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_sql);
        
        foreach ($sample_providers as $provider) {
            try {
                $stmt->execute([
                    $provider['name'],
                    $provider['contact'],
                    $provider['email'],
                    $provider['description'],
                    $provider['regions'],
                    $provider['address'],
                    $provider['cost_per_km'],
                    $provider['base_cost'],
                    $provider['estimated_days'],
                    $provider['max_weight_kg'],
                    $provider['vehicle_type'],
                    $provider['service_type'],
                    $provider['status'],
                    $provider['rating'],
                    $provider['latitude'],
                    $provider['longitude'],
                    $provider['operating_hours']
                ]);
                echo "<div class='success'>✅ Added provider: " . $provider['name'] . "</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Failed to add provider " . $provider['name'] . ": " . $e->getMessage() . "</div>";
                $errors++;
            }
        }
    } else {
        echo "<div class='success'>✅ Found $provider_count existing transport providers.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error adding sample data: " . $e->getMessage() . "</div>";
    $errors++;
}

// Test the fix
echo "<div class='info'>🧪 Testing the database fix...</div>";

$test_queries = [
    'Address Column' => "SELECT name, address FROM transport_providers LIMIT 1",
    'Status Column' => "SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'",
    'Vehicle Type' => "SELECT COUNT(*) as count FROM transport_providers WHERE vehicle_type IS NOT NULL",
    'All Columns' => "SELECT name, address, status, vehicle_type, service_type, rating FROM transport_providers LIMIT 1"
];

foreach ($test_queries as $test_name => $query) {
    try {
        $result = $pdo->query($query);
        if ($result) {
            echo "<div class='success'>✅ $test_name test PASSED!</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ $test_name test FAILED: " . $e->getMessage() . "</div>";
        $errors++;
    }
}

// Summary
if ($errors == 0) {
    echo "<div class='success'>
            <h2>🎉 All Missing Columns Fixed Successfully!</h2>
            <p><strong>Columns Added:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p>Your transport system should now work without database errors!</p>
          </div>";
} else {
    echo "<div class='warning'>
            <h2>⚠️ Fix Completed with Some Issues</h2>
            <p><strong>Columns Added:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p>Most issues have been resolved, but please check the errors above.</p>
          </div>";
}

echo "
        <h2>🎯 Next Steps</h2>
        <div class='info'>
            <p>Now that the missing columns have been added:</p>
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
        <a href='diagnose_transport_tables.php' class='btn'>🔍 Re-run Diagnostic</a>
        
    </div>
</body>
</html>";
?>