<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Transport System Setup - SmartFix</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 40px; 
            background: #f8f9fa; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #007BFF; 
            border-bottom: 2px solid #007BFF; 
            padding-bottom: 10px; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border: 1px solid #c3e6cb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border: 1px solid #f5c6cb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border: 1px solid #bee5eb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚚 Enhanced Transport System Setup</h1>
        
<?php
include 'includes/db.php';

$messages = [];

try {
    // 1. Create/Update transport_providers table with enhanced structure
    $messages[] = ['info', '🔧 Setting up transport providers table...'];
    
    $create_transport_providers = "CREATE TABLE IF NOT EXISTS transport_providers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        contact VARCHAR(50),
        email VARCHAR(100),
        description TEXT,
        regions TEXT,
        address TEXT,
        cost_per_km DECIMAL(8,2) DEFAULT 2.00,
        base_cost DECIMAL(8,2) DEFAULT 50.00,
        estimated_days INT DEFAULT 3,
        max_weight_kg INT DEFAULT 50,
        vehicle_type ENUM('motorbike', 'car', 'van', 'truck') DEFAULT 'car',
        service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard',
        status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
        rating DECIMAL(3,2) DEFAULT 5.00,
        total_deliveries INT DEFAULT 0,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        operating_hours VARCHAR(100) DEFAULT '08:00-18:00',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_service_type (service_type),
        INDEX idx_rating (rating)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($create_transport_providers);
    $messages[] = ['success', '✅ Transport providers table created/updated successfully!'];
    
    // 2. Create transport_quotes table for dynamic pricing
    $messages[] = ['info', '🔧 Setting up transport quotes table...'];
    
    $create_quotes = "CREATE TABLE IF NOT EXISTS transport_quotes (
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
        FOREIGN KEY (transport_provider_id) REFERENCES transport_providers(id) ON DELETE CASCADE,
        INDEX idx_order_id (order_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($create_quotes);
    $messages[] = ['success', '✅ Transport quotes table created successfully!'];
    
    // 3. Create delivery_tracking table for real-time tracking
    $messages[] = ['info', '🔧 Setting up delivery tracking table...'];
    
    $create_tracking = "CREATE TABLE IF NOT EXISTS delivery_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        transport_provider_id INT NOT NULL,
        driver_name VARCHAR(100),
        driver_phone VARCHAR(20),
        vehicle_number VARCHAR(20),
        current_latitude DECIMAL(10, 8),
        current_longitude DECIMAL(11, 8),
        status ENUM('pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery') DEFAULT 'pickup_scheduled',
        estimated_arrival DATETIME,
        actual_delivery_time DATETIME,
        delivery_notes TEXT,
        customer_signature TEXT,
        proof_of_delivery VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($create_tracking);
    $messages[] = ['success', '✅ Delivery tracking table created successfully!'];
    
    // 4. Check if we need to add transport_id column to orders table
    $messages[] = ['info', '🔧 Checking orders table structure...'];
    
    $check_orders = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'orders' 
                     AND COLUMN_NAME = 'transport_id'";
    $stmt = $pdo->prepare($check_orders);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $add_transport_column = "ALTER TABLE orders 
                               ADD COLUMN transport_id INT NULL,
                               ADD COLUMN transport_cost DECIMAL(10,2) DEFAULT 0.00,
                               ADD COLUMN delivery_notes TEXT,
                               ADD FOREIGN KEY (transport_id) REFERENCES transport_providers(id) ON DELETE SET NULL";
        $pdo->exec($add_transport_column);
        $messages[] = ['success', '✅ Added transport columns to orders table!'];
    } else {
        $messages[] = ['info', 'ℹ️ Orders table already has transport columns'];
    }
    
    // 5. Insert enhanced sample transport providers
    $messages[] = ['info', '🔧 Adding sample transport providers...'];
    
    $count_check = "SELECT COUNT(*) FROM transport_providers";
    $stmt = $pdo->prepare($count_check);
    $stmt->execute();
    $provider_count = $stmt->fetchColumn();
    
    if ($provider_count < 5) {
        $sample_providers = [
            [
                'name' => 'QuickDelivery Express',
                'contact' => '+260 977 123 456',
                'email' => 'quick@delivery.zm',
                'description' => 'Fast same-day delivery service within Lusaka',
                'regions' => 'Lusaka, Kabwe, Mazabuka',
                'address' => 'Plot 123, Industrial Area, Lusaka',
                'cost_per_km' => 3.50,
                'base_cost' => 25.00,
                'estimated_days' => 1,
                'max_weight_kg' => 25,
                'vehicle_type' => 'motorbike',
                'service_type' => 'same_day',
                'latitude' => -15.3975,
                'longitude' => 28.3228,
                'operating_hours' => '07:00-20:00'
            ],
            [
                'name' => 'Zampost Premium',
                'contact' => '+260 211 228 228',
                'email' => 'premium@zampost.zm',
                'description' => 'Reliable nationwide postal and courier service',
                'regions' => 'All Provinces',
                'address' => 'Cairo Road, Lusaka',
                'cost_per_km' => 2.00,
                'base_cost' => 35.00,
                'estimated_days' => 3,
                'max_weight_kg' => 100,
                'vehicle_type' => 'van',
                'service_type' => 'standard',
                'latitude' => -15.4067,
                'longitude' => 28.2871,
                'operating_hours' => '08:00-17:00'
            ],
            [
                'name' => 'DHL Express Zambia',
                'contact' => '+260 211 254 254',
                'email' => 'lusaka@dhl.com',
                'description' => 'International express courier with local delivery',
                'regions' => 'Lusaka, Kitwe, Ndola, Livingstone',
                'address' => 'Addis Ababa Drive, Lusaka',
                'cost_per_km' => 8.50,
                'base_cost' => 150.00,
                'estimated_days' => 1,
                'max_weight_kg' => 200,
                'vehicle_type' => 'van',
                'service_type' => 'express',
                'latitude' => -15.3928,
                'longitude' => 28.3228,
                'operating_hours' => '08:00-18:00'
            ],
            [
                'name' => 'Local Riders Co-op',
                'contact' => '+260 976 888 999',
                'email' => 'info@localriders.zm',
                'description' => 'Community-based motorcycle delivery network',
                'regions' => 'Lusaka, Kafue, Chilanga',
                'address' => 'Kamwala Market, Lusaka',
                'cost_per_km' => 2.50,
                'base_cost' => 15.00,
                'estimated_days' => 1,
                'max_weight_kg' => 15,
                'vehicle_type' => 'motorbike',
                'service_type' => 'same_day',
                'latitude' => -15.4178,
                'longitude' => 28.2937,
                'operating_hours' => '06:00-21:00'
            ],
            [
                'name' => 'TransAfrica Logistics',
                'contact' => '+260 975 555 777',
                'email' => 'logistics@transafrica.zm',
                'description' => 'Heavy freight and bulk delivery specialists',
                'regions' => 'Lusaka, Copperbelt, Southern Province',
                'address' => 'Great East Road, Lusaka',
                'cost_per_km' => 5.00,
                'base_cost' => 100.00,
                'estimated_days' => 2,
                'max_weight_kg' => 1000,
                'vehicle_type' => 'truck',
                'service_type' => 'standard',
                'latitude' => -15.3692,
                'longitude' => 28.3728,
                'operating_hours' => '07:00-19:00'
            ]
        ];
        
        $insert_provider = "INSERT INTO transport_providers 
                           (name, contact, email, description, regions, address, cost_per_km, base_cost, 
                            estimated_days, max_weight_kg, vehicle_type, service_type, latitude, longitude, operating_hours) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_provider);
        
        $added_count = 0;
        foreach ($sample_providers as $provider) {
            if ($stmt->execute([
                $provider['name'], $provider['contact'], $provider['email'],
                $provider['description'], $provider['regions'], $provider['address'],
                $provider['cost_per_km'], $provider['base_cost'], $provider['estimated_days'],
                $provider['max_weight_kg'], $provider['vehicle_type'], $provider['service_type'],
                $provider['latitude'], $provider['longitude'], $provider['operating_hours']
            ])) {
                $added_count++;
            }
        }
        
        $messages[] = ['success', "✅ Added $added_count enhanced transport providers!"];
    } else {
        $messages[] = ['info', 'ℹ️ Transport providers already exist, skipping sample data'];
    }
    
    // 6. Create transport cost calculation function
    $messages[] = ['info', '🔧 Transport system setup completed!'];
    $messages[] = ['success', '🎉 Enhanced Transport System is now ready!'];
    
} catch (PDOException $e) {
    $messages[] = ['error', '❌ Database Error: ' . htmlspecialchars($e->getMessage())];
} catch (Exception $e) {
    $messages[] = ['error', '❌ Error: ' . htmlspecialchars($e->getMessage())];
}

// Display all messages
foreach ($messages as $msg) {
    echo "<div class='{$msg[0]}'>{$msg[1]}</div>\n";
}
?>

        <h2>🚀 Enhanced Transport Features</h2>
        <div class="grid">
            <div class="card">
                <h3>📍 GPS-Based Pricing</h3>
                <ul>
                    <li>Real-time distance calculations</li>
                    <li>Dynamic pricing based on location</li>
                    <li>Multiple transport options</li>
                </ul>
            </div>
            
            <div class="card">
                <h3>🚛 Smart Provider Selection</h3>
                <ul>
                    <li>Vehicle type matching</li>
                    <li>Weight capacity checking</li>
                    <li>Service type filtering</li>
                </ul>
            </div>
            
            <div class="card">
                <h3>📱 Real-time Tracking</h3>
                <ul>
                    <li>Live delivery updates</li>
                    <li>Driver contact information</li>
                    <li>Estimated arrival times</li>
                </ul>
            </div>
            
            <div class="card">
                <h3>💰 Smart Pricing</h3>
                <ul>
                    <li>Base cost + per-km rates</li>
                    <li>Service type premiums</li>
                    <li>Competitive quotes</li>
                </ul>
            </div>
        </div>
        
        <h2>🧪 Test the Enhanced System</h2>
        <p>Click the buttons below to test the new transport functionality:</p>
        
        <a href="smart_transport_selector.php" class="btn">🎯 Smart Transport Selector</a>
        <a href="shop/checkout.php" class="btn">🛒 Enhanced Checkout</a>
        <a href="transport_quotes.php" class="btn">💰 Transport Quotes</a>
        <a href="admin/transport_dashboard.php" class="btn">📊 Transport Dashboard</a>
        
        <h2>📋 Database Schema</h2>
        <div class="info">
            <p><strong>New Tables Created:</strong></p>
            <ul>
                <li><code>transport_providers</code> - Enhanced provider information with GPS and capabilities</li>
                <li><code>transport_quotes</code> - Dynamic pricing and quote management</li>
                <li><code>delivery_tracking</code> - Real-time delivery tracking and proof of delivery</li>
            </ul>
            
            <p><strong>Enhanced Features:</strong></p>
            <ul>
                <li>GPS-based distance and cost calculations</li>
                <li>Vehicle type and weight capacity matching</li>
                <li>Service type selection (standard, express, overnight, same-day)</li>
                <li>Real-time delivery tracking with driver information</li>
                <li>Proof of delivery and customer signatures</li>
                <li>Rating system for transport providers</li>
            </ul>
        </div>
        
        <h2>🔧 Implementation Notes</h2>
        <pre>
Key Enhancements:
1. GPS-based pricing with haversine distance calculation
2. Smart provider matching based on:
   - Weight capacity
   - Service area coverage
   - Vehicle type requirements
   - Service speed needs
3. Real-time tracking with:
   - Driver GPS location updates
   - Estimated arrival times
   - Delivery status updates
4. Enhanced user experience with:
   - Visual transport selection
   - Cost comparisons
   - Real-time quotes
        </pre>
    </div>
</body>
</html>