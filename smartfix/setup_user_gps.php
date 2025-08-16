<?php
/**
 * Setup script for User GPS Dashboard
 * Creates necessary database tables and sample data
 */

include('includes/db.php');

echo "<h2>Setting up User GPS Dashboard...</h2>";

try {
    // Create order_locations table for tracking order delivery locations
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address TEXT,
        location_type ENUM('pickup', 'delivery') DEFAULT 'delivery',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_order_location (order_id, location_type),
        INDEX idx_order_id (order_id),
        INDEX idx_coordinates (latitude, longitude)
    )");
    echo "<p>✅ Created order_locations table</p>";

    // Ensure service_locations table exists (from GPSManager)
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address TEXT,
        accuracy FLOAT,
        location_type ENUM('customer', 'service_point') DEFAULT 'customer',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_request_location (request_id, location_type),
        INDEX idx_coordinates (latitude, longitude),
        INDEX idx_request_id (request_id)
    )");
    echo "<p>✅ Created/verified service_locations table</p>";

    // Ensure technician_locations table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS technician_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        technician_id INT NOT NULL UNIQUE,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        accuracy FLOAT,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_technician_id (technician_id),
        INDEX idx_coordinates (latitude, longitude),
        INDEX idx_last_updated (last_updated)
    )");
    echo "<p>✅ Created/verified technician_locations table</p>";

    // Add some sample data if tables are empty
    
    // Check if we have any users
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    if ($user_count > 0) {
        // Get a sample user
        $sample_user = $pdo->query("SELECT id FROM users LIMIT 1")->fetch();
        $user_id = $sample_user['id'];
        
        // Add sample service request with location if none exist
        $service_count = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE user_id = $user_id")->fetchColumn();
        
        if ($service_count == 0) {
            // Create sample service request
            $pdo->exec("INSERT INTO service_requests (user_id, service_type, description, status, created_at) 
                       VALUES ($user_id, 'Phone Repair', 'Sample service request for GPS demo', 'pending', NOW())");
            
            $request_id = $pdo->lastInsertId();
            
            // Add location for the service request (Kigali city center)
            $pdo->exec("INSERT INTO service_locations (request_id, latitude, longitude, address, location_type) 
                       VALUES ($request_id, -1.9441, 30.0619, 'Kigali City Center, Rwanda', 'customer')");
            
            echo "<p>✅ Added sample service request with location</p>";
        }
        
        // Add sample order with location if none exist
        $order_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetchColumn();
        
        if ($order_count == 0) {
            // Create sample order
            $pdo->exec("INSERT INTO orders (user_id, total_amount, status, created_at) 
                       VALUES ($user_id, 25000, 'processing', NOW())");
            
            $order_id = $pdo->lastInsertId();
            
            // Add delivery location for the order
            $pdo->exec("INSERT INTO order_locations (order_id, latitude, longitude, address, location_type) 
                       VALUES ($order_id, -1.9506, 30.0588, 'Nyarugenge District, Kigali, Rwanda', 'delivery')");
            
            echo "<p>✅ Added sample order with delivery location</p>";
        }
    }
    
    // Add sample technicians with locations if none exist
    $tech_location_count = $pdo->query("SELECT COUNT(*) FROM technician_locations")->fetchColumn();
    
    if ($tech_location_count == 0) {
        // Check if we have technicians
        $tech_count = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
        
        if ($tech_count == 0) {
            // Create sample technicians
            $technicians = [
                ['John Doe', 'john@smartfix.com', '+250788123456', 'Phone Repair, Computer Repair', -1.9441, 30.0619],
                ['Jane Smith', 'jane@smartfix.com', '+250788123457', 'Electrical, Plumbing', -1.9506, 30.0588],
                ['Mike Johnson', 'mike@smartfix.com', '+250788123458', 'Car Repair, Mechanical', -1.9370, 30.0644],
                ['Sarah Wilson', 'sarah@smartfix.com', '+250788123459', 'Phone Repair, Electronics', -1.9512, 30.0675],
                ['David Brown', 'david@smartfix.com', '+250788123460', 'Computer Repair, IT Support', -1.9400, 30.0550]
            ];
            
            foreach ($technicians as $tech) {
                // Insert technician
                $stmt = $pdo->prepare("INSERT INTO technicians (name, email, phone, specialization, status, rating, created_at) 
                                     VALUES (?, ?, ?, ?, 'active', ?, NOW())");
                $stmt->execute([$tech[0], $tech[1], $tech[2], $tech[3], rand(35, 50) / 10]);
                
                $tech_id = $pdo->lastInsertId();
                
                // Insert technician location
                $stmt = $pdo->prepare("INSERT INTO technician_locations (technician_id, latitude, longitude, last_updated) 
                                     VALUES (?, ?, ?, NOW())");
                $stmt->execute([$tech_id, $tech[4], $tech[5]]);
            }
            
            echo "<p>✅ Added sample technicians with locations</p>";
        } else {
            // Add locations for existing technicians
            $technicians = $pdo->query("SELECT id FROM technicians LIMIT 5")->fetchAll();
            $locations = [
                [-1.9441, 30.0619],
                [-1.9506, 30.0588],
                [-1.9370, 30.0644],
                [-1.9512, 30.0675],
                [-1.9400, 30.0550]
            ];
            
            foreach ($technicians as $index => $tech) {
                if (isset($locations[$index])) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO technician_locations (technician_id, latitude, longitude, last_updated) 
                                         VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$tech['id'], $locations[$index][0], $locations[$index][1]]);
                }
            }
            
            echo "<p>✅ Added locations for existing technicians</p>";
        }
    }
    
    echo "<h3>✅ User GPS Dashboard setup completed successfully!</h3>";
    echo "<p><strong>What's been set up:</strong></p>";
    echo "<ul>";
    echo "<li>Database tables for GPS tracking</li>";
    echo "<li>Sample service requests with locations</li>";
    echo "<li>Sample orders with delivery locations</li>";
    echo "<li>Sample technicians with GPS coordinates</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Configure Google Maps API key in <code>config/maps_config.php</code></li>";
    echo "<li>Users can now access GPS Dashboard from their main dashboard</li>";
    echo "<li>Create service requests with location data</li>";
    echo "<li>Track technicians and orders on the map</li>";
    echo "</ul>";
    
    echo "<br><a href='user/dashboard.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to User Dashboard</a>";
    echo " <a href='user/gps_dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>View GPS Dashboard</a>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>