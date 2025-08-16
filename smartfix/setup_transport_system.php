<?php
// Enhanced Transport System Setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

echo "<h1>SmartFix Enhanced Transport System Setup</h1>";
echo "<style>
body{font-family:Arial;margin:40px;background:#f5f5f5;} 
.success{color:green;background:#f0fff0;padding:15px;margin:10px 0;border-left:4px solid green;border-radius:5px;} 
.error{color:red;background:#fff0f0;padding:15px;margin:10px 0;border-left:4px solid red;border-radius:5px;} 
.info{color:blue;background:#f0f8ff;padding:15px;margin:10px 0;border-left:4px solid blue;border-radius:5px;}
.warning{color:orange;background:#fffaf0;padding:15px;margin:10px 0;border-left:4px solid orange;border-radius:5px;}
h2{color:#004080;border-bottom:2px solid #007BFF;padding-bottom:10px;margin-top:30px;}
.container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.table{width:100%;border-collapse:collapse;margin:20px 0;}
.table th,.table td{padding:12px;border:1px solid #ddd;text-align:left;}
.table th{background:#f8f9fa;}
</style>";

echo "<div class='container'>";

try {
    echo "<h2>1. Setting Up Enhanced Transport Providers Table</h2>";
    
    // Check if transport_providers table exists and get its structure
    $table_exists = false;
    try {
        $check_table = $pdo->query("SHOW TABLES LIKE 'transport_providers'");
        $table_exists = $check_table->rowCount() > 0;
        
        if ($table_exists) {
            echo "<div class='warning'>⚠ Transport providers table already exists. Checking structure...</div>";
            
            // Check if table has user_id column (which might be causing the error)
            $columns = $pdo->query("SHOW COLUMNS FROM transport_providers")->fetchAll(PDO::FETCH_ASSOC);
            $has_user_id = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'user_id') {
                    $has_user_id = true;
                    break;
                }
            }
            
            if ($has_user_id) {
                echo "<div class='warning'>⚠ Found user_id column in existing table. This might cause issues. Dropping and recreating...</div>";
                $pdo->exec("DROP TABLE IF EXISTS transport_providers");
                $table_exists = false;
            }
        }
    } catch (PDOException $e) {
        echo "<div class='info'>✓ Transport providers table doesn't exist yet</div>";
    }
    
    if (!$table_exists) {
        // Create enhanced transport providers table WITHOUT user_id column
        $create_transport_providers = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            regions TEXT NOT NULL,
            estimated_days INT NOT NULL DEFAULT 3,
            cost_per_km DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            base_cost DECIMAL(10,2) NOT NULL DEFAULT 20.00,
            max_weight_kg INT NOT NULL DEFAULT 50,
            service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard',
            vehicle_type VARCHAR(50) DEFAULT 'Van',
            rating DECIMAL(3,2) DEFAULT 4.0,
            operating_hours VARCHAR(100) DEFAULT '8:00 AM - 6:00 PM',
            latitude DECIMAL(10,8) DEFAULT -15.3875,
            longitude DECIMAL(11,8) DEFAULT 28.3228,
            status ENUM('active', 'inactive') DEFAULT 'active',
            description TEXT,
            insurance_valid TINYINT(1) DEFAULT 1,
            license_number VARCHAR(50),
            established_year INT,
            website VARCHAR(100),
            social_media JSON,
            specialties JSON,
            coverage_area_km INT DEFAULT 50,
            min_order_value DECIMAL(10,2) DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_regions (regions(100)),
            INDEX idx_rating (rating),
            INDEX idx_service_type (service_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_transport_providers);
        echo "<div class='success'>✓ Enhanced transport_providers table created successfully!</div>";
    } else {
        echo "<div class='info'>✓ Transport providers table structure is compatible</div>";
    }
    
    echo "<h2>2. Creating Transport Quotes Table</h2>";
    
    // Check if transport_quotes table exists and has user_id column
    try {
        $check_quotes_table = $pdo->query("SHOW TABLES LIKE 'transport_quotes'");
        if ($check_quotes_table->rowCount() > 0) {
            $quotes_columns = $pdo->query("SHOW COLUMNS FROM transport_quotes")->fetchAll(PDO::FETCH_ASSOC);
            $quotes_has_user_id = false;
            foreach ($quotes_columns as $column) {
                if ($column['Field'] === 'user_id') {
                    $quotes_has_user_id = true;
                    break;
                }
            }
            
            if ($quotes_has_user_id) {
                echo "<div class='warning'>⚠ Found user_id column in transport_quotes table. Dropping and recreating...</div>";
                $pdo->exec("DROP TABLE IF EXISTS transport_quotes");
            }
        }
    } catch (PDOException $e) {
        // Table doesn't exist, continue
    }
    
    // Create transport quotes table WITHOUT user_id column
    $create_transport_quotes = "CREATE TABLE IF NOT EXISTS transport_quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        transport_provider_id INT NOT NULL,
        pickup_address TEXT NOT NULL,
        delivery_address TEXT NOT NULL,
        distance_km DECIMAL(8,2) NOT NULL,
        estimated_cost DECIMAL(10,2) NOT NULL,
        actual_cost DECIMAL(10,2) DEFAULT NULL,
        estimated_delivery_time INT NOT NULL,
        actual_delivery_time INT DEFAULT NULL,
        quote_valid_until DATETIME NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (transport_provider_id) REFERENCES transport_providers(id) ON DELETE CASCADE,
        INDEX idx_order (order_id),
        INDEX idx_provider (transport_provider_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_transport_quotes);
    echo "<div class='success'>✓ Transport quotes table created successfully!</div>";
    
    echo "<h2>3. Creating Transport Tracking Table</h2>";
    
    // Create transport tracking table
    $create_transport_tracking = "CREATE TABLE IF NOT EXISTS transport_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quote_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        location VARCHAR(200),
        description TEXT NOT NULL,
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        estimated_arrival DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (quote_id) REFERENCES transport_quotes(id) ON DELETE CASCADE,
        INDEX idx_quote (quote_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_transport_tracking);
    echo "<div class='success'>✓ Transport tracking table created successfully!</div>";
    
    echo "<h2>4. Inserting Sample Transport Providers</h2>";
    
    // Insert enhanced sample providers
    $sample_providers = [
        [
            'name' => 'FastTrack Delivery Pro',
            'contact' => '+260-97-123-4567',
            'email' => 'info@fasttrack.zm',
            'regions' => 'Lusaka,Copperbelt,Central',
            'estimated_days' => 2,
            'cost_per_km' => 3.50,
            'base_cost' => 25.00,
            'max_weight_kg' => 100,
            'service_type' => 'express',
            'vehicle_type' => 'Refrigerated Truck',
            'rating' => 4.8,
            'operating_hours' => '6:00 AM - 10:00 PM',
            'latitude' => -15.3875,
            'longitude' => 28.3228,
            'status' => 'active',
            'description' => 'Premium express delivery service with temperature-controlled vehicles for sensitive items',
            'insurance_valid' => 1,
            'license_number' => 'ZM-TRANS-001',
            'established_year' => 2018,
            'website' => 'https://fasttrack.zm',
            'social_media' => '{"facebook": "FastTrackZM", "twitter": "@FastTrackZM"}',
            'specialties' => '["Electronics", "Fragile Items", "Cold Chain", "Medical Supplies"]',
            'coverage_area_km' => 100,
            'min_order_value' => 50.00
        ],
        [
            'name' => 'City Express Standard',
            'contact' => '+260-96-987-6543',
            'email' => 'orders@cityexpress.zm',
            'regions' => 'Lusaka,Eastern,Southern',
            'estimated_days' => 3,
            'cost_per_km' => 2.80,
            'base_cost' => 20.00,
            'max_weight_kg' => 75,
            'service_type' => 'standard',
            'vehicle_type' => 'Van',
            'rating' => 4.2,
            'operating_hours' => '8:00 AM - 6:00 PM',
            'latitude' => -15.4067,
            'longitude' => 28.2871,
            'status' => 'active',
            'description' => 'Reliable and affordable standard delivery service for everyday needs',
            'insurance_valid' => 1,
            'license_number' => 'ZM-TRANS-002',
            'established_year' => 2020,
            'website' => 'https://cityexpress.zm',
            'social_media' => '{"facebook": "CityExpressZM"}',
            'specialties' => '["General Cargo", "Documents", "Small Packages"]',
            'coverage_area_km' => 75,
            'min_order_value' => 25.00
        ],
        [
            'name' => 'QuickMove Same-Day',
            'contact' => '+260-95-555-0123',
            'email' => 'support@quickmove.zm',
            'regions' => 'Lusaka,Copperbelt,Northern',
            'estimated_days' => 1,
            'cost_per_km' => 5.00,
            'base_cost' => 40.00,
            'max_weight_kg' => 30,
            'service_type' => 'same_day',
            'vehicle_type' => 'Motorcycle',
            'rating' => 4.6,
            'operating_hours' => '24/7',
            'latitude' => -15.3794,
            'longitude' => 28.3069,
            'status' => 'active',
            'description' => 'Ultra-fast same-day delivery for urgent orders and time-sensitive deliveries',
            'insurance_valid' => 1,
            'license_number' => 'ZM-TRANS-003',
            'established_year' => 2021,
            'website' => 'https://quickmove.zm',
            'social_media' => '{"facebook": "QuickMoveZM", "twitter": "@QuickMoveZM", "instagram": "quickmovezm"}',
            'specialties' => '["Urgent Delivery", "Medical Supplies", "Legal Documents", "Emergency Services"]',
            'coverage_area_km' => 50,
            'min_order_value' => 100.00
        ],
        [
            'name' => 'Copperbelt Logistics',
            'contact' => '+260-97-888-9999',
            'email' => 'dispatch@copperbelt-logistics.zm',
            'regions' => 'Copperbelt,Northern,Luapula',
            'estimated_days' => 4,
            'cost_per_km' => 2.50,
            'base_cost' => 18.00,
            'max_weight_kg' => 200,
            'service_type' => 'standard',
            'vehicle_type' => 'Truck',
            'rating' => 4.0,
            'operating_hours' => '7:00 AM - 7:00 PM',
            'latitude' => -12.8389,
            'longitude' => 28.2069,
            'status' => 'active',
            'description' => 'Heavy-duty logistics for mining equipment and industrial supplies',
            'insurance_valid' => 1,
            'license_number' => 'ZM-TRANS-004',
            'established_year' => 2015,
            'website' => 'https://copperbelt-logistics.zm',
            'social_media' => '{"facebook": "CopperbeltLogistics"}',
            'specialties' => '["Heavy Items", "Industrial Equipment", "Mining Supplies", "Bulk Cargo"]',
            'coverage_area_km' => 150,
            'min_order_value' => 200.00
        ],
        [
            'name' => 'Green Delivery Solutions',
            'contact' => '+260-96-777-8888',
            'email' => 'eco@greendelivery.zm',
            'regions' => 'Lusaka,Central,Southern',
            'estimated_days' => 3,
            'cost_per_km' => 3.20,
            'base_cost' => 22.00,
            'max_weight_kg' => 40,
            'service_type' => 'standard',
            'vehicle_type' => 'Electric Van',
            'rating' => 4.4,
            'operating_hours' => '8:00 AM - 5:00 PM',
            'latitude' => -15.3875,
            'longitude' => 28.3228,
            'status' => 'active',
            'description' => 'Eco-friendly delivery service using electric vehicles for sustainable logistics',
            'insurance_valid' => 1,
            'license_number' => 'ZM-TRANS-005',
            'established_year' => 2022,
            'website' => 'https://greendelivery.zm',
            'social_media' => '{"facebook": "GreenDeliveryZM", "instagram": "greendeliveryzm"}',
            'specialties' => '["Eco-Friendly", "Small Packages", "Local Delivery", "Sustainable Transport"]',
            'coverage_area_km' => 60,
            'min_order_value' => 30.00
        ]
    ];
    
    $insert_stmt = $pdo->prepare("INSERT INTO transport_providers 
        (name, contact, email, regions, estimated_days, cost_per_km, base_cost, max_weight_kg, 
         service_type, vehicle_type, rating, operating_hours, latitude, longitude, status, 
         description, insurance_valid, license_number, established_year, website, social_media, 
         specialties, coverage_area_km, min_order_value) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sample_providers as $provider) {
        $insert_stmt->execute([
            $provider['name'], $provider['contact'], $provider['email'], $provider['regions'],
            $provider['estimated_days'], $provider['cost_per_km'], $provider['base_cost'], $provider['max_weight_kg'],
            $provider['service_type'], $provider['vehicle_type'], $provider['rating'], $provider['operating_hours'],
            $provider['latitude'], $provider['longitude'], $provider['status'], $provider['description'],
            $provider['insurance_valid'], $provider['license_number'], $provider['established_year'], $provider['website'],
            $provider['social_media'], $provider['specialties'], $provider['coverage_area_km'], $provider['min_order_value']
        ]);
        echo "<div class='success'>✓ Added: {$provider['name']}</div>";
    }
    
    echo "<h2>5. Creating Sample Transport Quotes</h2>";
    
    // Insert sample quotes
    $sample_quotes = [
        [1, 1, 'SmartFix Warehouse, Lusaka', 'Customer Address, Lusaka Central', 15.5, 79.25, 2, 'accepted'],
        [2, 2, 'SmartFix Warehouse, Lusaka', 'Customer Address, Chilanga', 25.0, 90.00, 3, 'pending'],
        [3, 3, 'SmartFix Warehouse, Lusaka', 'Customer Address, Kabulonga', 8.2, 81.00, 1, 'completed']
    ];
    
    $quote_stmt = $pdo->prepare("INSERT INTO transport_quotes 
        (order_id, transport_provider_id, pickup_address, delivery_address, distance_km, 
         estimated_cost, estimated_delivery_time, status, quote_valid_until) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
    
    foreach ($sample_quotes as $quote) {
        $quote_stmt->execute($quote);
    }
    echo "<div class='success'>✓ Added sample transport quotes</div>";
    
    echo "<h2>6. Database Structure Summary</h2>";
    
    // Show table structures
    $tables = ['transport_providers', 'transport_quotes', 'transport_tracking'];
    
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table class='table'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>7. Statistics</h2>";
    
    // Show statistics
    $provider_count = $pdo->query("SELECT COUNT(*) FROM transport_providers")->fetchColumn();
    $quote_count = $pdo->query("SELECT COUNT(*) FROM transport_quotes")->fetchColumn();
    $active_providers = $pdo->query("SELECT COUNT(*) FROM transport_providers WHERE status = 'active'")->fetchColumn();
    
    echo "<div class='info'>";
    echo "<strong>Setup Complete!</strong><br>";
    echo "• Transport Providers: $provider_count<br>";
    echo "• Active Providers: $active_providers<br>";
    echo "• Sample Quotes: $quote_count<br>";
    echo "• All tables created with proper indexes and foreign keys<br>";
    echo "</div>";
    
    echo "<h2>8. Next Steps</h2>";
    echo "<div class='info'>";
    echo "<strong>Your enhanced transport system is ready!</strong><br><br>";
    echo "Features now available:<br>";
    echo "• Enhanced provider management with ratings, specialties, and social media<br>";
    echo "• GPS coordinates for accurate distance calculations<br>";
    echo "• Service type differentiation (standard, express, overnight, same-day)<br>";
    echo "• Vehicle type tracking and weight limits<br>";
    echo "• Insurance and licensing information<br>";
    echo "• Coverage area and minimum order value settings<br>";
    echo "• Transport quotes and tracking system<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error during setup: " . $e->getMessage() . "</div>";
}

echo "</div>";

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<a href='admin/transport_providers_enhanced.php' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Manage Providers</a> ";
echo "<a href='smart_transport_selector.php?order_id=1' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Test Transport Selector</a> ";
echo "<a href='admin/admin_dashboard_new.php' style='background:#17a2b8;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Admin Dashboard</a>";
?>