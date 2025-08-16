<?php
/**
 * Setup Zambian Technicians with GPS Locations
 * This script adds sample technicians with valid Zambian coordinates
 */

require_once 'includes/db.php';
require_once 'includes/ZambiaLocationValidator.php';
require_once 'includes/GPSManager.php';

$gps = new GPSManager($pdo);

try {
    echo "<h2>Setting up Zambian Technicians with GPS Locations</h2>";
    
    // Create technician_locations table if it doesn't exist
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
    
    echo "<p>✅ Created technician_locations table</p>";
    
    // Sample technicians with Zambian locations
    $zambian_technicians = [
        [
            'name' => 'John Mwanza',
            'email' => 'john.mwanza@smartfix.zm',
            'phone' => '+260 977 123 456',
            'specialization' => 'Phone Repair',
            'regions' => 'Lusaka,Central',
            'address' => '123 Cairo Road, Lusaka',
            'latitude' => -15.4167,
            'longitude' => 28.2833,
            'bio' => 'Experienced phone repair technician with over 8 years of experience in Lusaka. Specialized in screen replacements, battery issues, and water damage repair.',
            'rating' => 4.8,
            'total_jobs' => 156
        ],
        [
            'name' => 'Mary Banda',
            'email' => 'mary.banda@smartfix.zm',
            'phone' => '+260 966 789 012',
            'specialization' => 'Computer Repair',
            'regions' => 'Lusaka',
            'address' => '45 Great East Road, Lusaka',
            'latitude' => -15.3982,
            'longitude' => 28.3232,
            'bio' => 'Computer technician with expertise in hardware and software troubleshooting. Certified in Microsoft and Cisco technologies.',
            'rating' => 4.5,
            'total_jobs' => 89
        ],
        [
            'name' => 'David Phiri',
            'email' => 'david.phiri@smartfix.zm',
            'phone' => '+260 955 345 678',
            'specialization' => 'Vehicle Repair',
            'regions' => 'Copperbelt,North-Western',
            'address' => '78 Obote Avenue, Kitwe',
            'latitude' => -12.8058,
            'longitude' => 28.2132,
            'bio' => 'Certified automotive technician with 12 years of experience in the Copperbelt region.',
            'rating' => 4.9,
            'total_jobs' => 203
        ],
        [
            'name' => 'Sarah Tembo',
            'email' => 'sarah.tembo@smartfix.zm',
            'phone' => '+260 977 567 890',
            'specialization' => 'Electrical',
            'regions' => 'Lusaka,Southern',
            'address' => '12 Independence Avenue, Lusaka',
            'latitude' => -15.4265,
            'longitude' => 28.2868,
            'bio' => 'Licensed electrician with expertise in residential and commercial electrical systems.',
            'rating' => 4.7,
            'total_jobs' => 134
        ],
        [
            'name' => 'Michael Zulu',
            'email' => 'michael.zulu@smartfix.zm',
            'phone' => '+260 966 123 789',
            'specialization' => 'Plumbing',
            'regions' => 'Lusaka,Eastern',
            'address' => '34 Addis Ababa Drive, Lusaka',
            'latitude' => -15.4023,
            'longitude' => 28.3102,
            'bio' => 'Professional plumber with 10 years of experience in residential and commercial plumbing.',
            'rating' => 4.6,
            'total_jobs' => 98
        ],
        [
            'name' => 'Grace Mulenga',
            'email' => 'grace.mulenga@smartfix.zm',
            'phone' => '+260 955 987 654',
            'specialization' => 'Appliance Repair',
            'regions' => 'Copperbelt',
            'address' => '56 Independence Way, Ndola',
            'latitude' => -12.9587,
            'longitude' => 28.6366,
            'bio' => 'Appliance repair specialist covering washing machines, refrigerators, and air conditioners.',
            'rating' => 4.4,
            'total_jobs' => 76
        ],
        [
            'name' => 'Peter Lungu',
            'email' => 'peter.lungu@smartfix.zm',
            'phone' => '+260 977 456 123',
            'specialization' => 'HVAC',
            'regions' => 'Southern',
            'address' => '89 Mosi-oa-Tunya Road, Livingstone',
            'latitude' => -17.8419,
            'longitude' => 25.8544,
            'bio' => 'HVAC technician specializing in air conditioning and heating systems in Southern Province.',
            'rating' => 4.3,
            'total_jobs' => 67
        ],
        [
            'name' => 'Ruth Chanda',
            'email' => 'ruth.chanda@smartfix.zm',
            'phone' => '+260 966 654 321',
            'specialization' => 'Electronics',
            'regions' => 'Eastern',
            'address' => '23 Great North Road, Chipata',
            'latitude' => -13.6333,
            'longitude' => 32.6500,
            'bio' => 'Electronics repair specialist covering TVs, radios, and other consumer electronics.',
            'rating' => 4.2,
            'total_jobs' => 54
        ],
        [
            'name' => 'James Sakala',
            'email' => 'james.sakala@smartfix.zm',
            'phone' => '+260 955 789 456',
            'specialization' => 'Generator Repair',
            'regions' => 'Northern',
            'address' => '45 Lumumba Street, Kasama',
            'latitude' => -10.2167,
            'longitude' => 31.1833,
            'bio' => 'Generator and power equipment specialist serving Northern Province.',
            'rating' => 4.5,
            'total_jobs' => 43
        ],
        [
            'name' => 'Alice Mwale',
            'email' => 'alice.mwale@smartfix.zm',
            'phone' => '+260 977 321 654',
            'specialization' => 'Solar Systems',
            'regions' => 'Western',
            'address' => '67 Lealui Road, Mongu',
            'latitude' => -15.2500,
            'longitude' => 23.1333,
            'bio' => 'Solar panel installation and maintenance specialist in Western Province.',
            'rating' => 4.6,
            'total_jobs' => 38
        ]
    ];
    
    // Clear existing technicians and locations
    $pdo->exec("DELETE FROM technician_locations");
    $pdo->exec("DELETE FROM technicians");
    $pdo->exec("ALTER TABLE technicians AUTO_INCREMENT = 1");
    
    echo "<p>🗑️ Cleared existing technician data</p>";
    
    // Insert technicians
    $tech_query = "INSERT INTO technicians (name, email, phone, specialization, regions, address, bio, rating, total_jobs, status) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
    $tech_stmt = $pdo->prepare($tech_query);
    
    $inserted_count = 0;
    $location_count = 0;
    
    foreach ($zambian_technicians as $tech) {
        // Validate coordinates are within Zambia
        $validation = ZambiaLocationValidator::validateCoordinates($tech['latitude'], $tech['longitude']);
        
        if (!$validation['valid']) {
            echo "<p>❌ Skipping {$tech['name']}: {$validation['error']}</p>";
            continue;
        }
        
        // Insert technician
        $tech_stmt->execute([
            $tech['name'],
            $tech['email'],
            $tech['phone'],
            $tech['specialization'],
            $tech['regions'],
            $tech['address'],
            $tech['bio'],
            $tech['rating'],
            $tech['total_jobs']
        ]);
        
        $technician_id = $pdo->lastInsertId();
        $inserted_count++;
        
        // Update location using GPS Manager (with validation)
        $location_result = $gps->updateTechnicianLocation(
            $technician_id, 
            $tech['latitude'], 
            $tech['longitude'], 
            5.0 // 5 meter accuracy
        );
        
        if ($location_result['success']) {
            $location_count++;
            echo "<p>✅ Added {$tech['name']} in {$validation['province']} province, near {$validation['nearest_city']} ({$validation['distance_to_city']}km away)</p>";
        } else {
            echo "<p>⚠️ Added {$tech['name']} but failed to set location: {$location_result['error']}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Setup Complete!</h3>";
    echo "<p><strong>✅ {$inserted_count} technicians added</strong></p>";
    echo "<p><strong>📍 {$location_count} GPS locations set</strong></p>";
    
    // Show statistics
    $stats_query = "SELECT 
                        COUNT(*) as total,
                        COUNT(tl.technician_id) as with_location,
                        AVG(t.rating) as avg_rating
                    FROM technicians t 
                    LEFT JOIN technician_locations tl ON t.id = tl.technician_id 
                    WHERE t.status = 'available'";
    $stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>System Statistics:</h4>";
    echo "<ul>";
    echo "<li>Total Available Technicians: {$stats['total']}</li>";
    echo "<li>Technicians with GPS Location: {$stats['with_location']}</li>";
    echo "<li>Average Rating: " . number_format($stats['avg_rating'], 1) . "/5.0</li>";
    echo "</ul>";
    echo "</div>";
    
    // Show province distribution
    $province_query = "SELECT 
                          SUBSTRING_INDEX(regions, ',', 1) as province,
                          COUNT(*) as count
                       FROM technicians 
                       WHERE status = 'available'
                       GROUP BY province
                       ORDER BY count DESC";
    $provinces = $pdo->query($province_query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Technicians by Province:</h4>";
    echo "<ul>";
    foreach ($provinces as $province) {
        echo "<li>{$province['province']}: {$province['count']} technicians</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='technician_map.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>";
    echo "<i class='fas fa-map-marked-alt'></i> View Technician Map";
    echo "</a>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ Important Notes:</h4>";
    echo "<ul>";
    echo "<li>All coordinates have been validated to be within Zambia boundaries</li>";
    echo "<li>To use the map, configure your Google Maps API key in <code>config/maps_config.php</code></li>";
    echo "<li>Technicians can update their locations using the location tracker</li>";
    echo "<li>The system automatically validates all coordinates against Zambia boundaries</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h4>❌ Database Error:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<h4>❌ Error:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Zambian Technicians - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        h2, h3, h4 {
            color: #333;
        }
        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
</body>
</html>