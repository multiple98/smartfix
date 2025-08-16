<?php
include('includes/db.php');
require_once('includes/GPSManager.php');

echo "<h2>GPS Dashboard Data Test</h2>\n";

try {
    $gps = new GPSManager($pdo);
    
    // Check if tables exist
    echo "<h3>Database Tables:</h3>\n";
    $tables = $pdo->query("SHOW TABLES LIKE '%location%' OR SHOW TABLES LIKE 'technicians' OR SHOW TABLES LIKE 'service_requests'")->fetchAll();
    foreach ($tables as $table) {
        echo "- " . array_values($table)[0] . "<br>\n";
    }
    
    // Check technicians data
    echo "<h3>Technicians Data:</h3>\n";
    $techs = $pdo->query("SELECT COUNT(*) as count FROM technicians")->fetch();
    echo "Total technicians: " . $techs['count'] . "<br>\n";
    
    if ($techs['count'] > 0) {
        $sample_techs = $pdo->query("SELECT id, name, phone, specialization FROM technicians LIMIT 5")->fetchAll();
        foreach ($sample_techs as $tech) {
            echo "- ID: {$tech['id']}, Name: {$tech['name']}, Specialization: {$tech['specialization']}<br>\n";
        }
    }
    
    // Check service requests
    echo "<h3>Service Requests Data:</h3>\n";
    $requests = $pdo->query("SELECT COUNT(*) as count FROM service_requests")->fetch();
    echo "Total service requests: " . $requests['count'] . "<br>\n";
    
    if ($requests['count'] > 0) {
        $sample_requests = $pdo->query("SELECT id, name, service_type, status FROM service_requests LIMIT 5")->fetchAll();
        foreach ($sample_requests as $req) {
            echo "- ID: {$req['id']}, Customer: {$req['name']}, Service: {$req['service_type']}, Status: {$req['status']}<br>\n";
        }
    }
    
    // Check location tables
    echo "<h3>Location Tables:</h3>\n";
    try {
        $tech_locations = $pdo->query("SELECT COUNT(*) as count FROM technician_locations")->fetch();
        echo "Technician locations: " . $tech_locations['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "technician_locations table doesn't exist yet<br>\n";
    }
    
    try {
        $service_locations = $pdo->query("SELECT COUNT(*) as count FROM service_locations")->fetch();
        echo "Service locations: " . $service_locations['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "service_locations table doesn't exist yet<br>\n";
    }
    
    // Create sample GPS data
    echo "<h3>Creating Sample GPS Data:</h3>\n";
    
    // Sample Kigali coordinates for technicians
    $sample_coords = [
        [-1.9441, 30.0619], // Kigali City Center
        [-1.9506, 30.1044], // Kacyiru
        [-1.9378, 30.0851], // Kimisagara  
        [-1.9702, 30.1044], // Gisozi
        [-1.9219, 30.0877]  // Nyamirambo
    ];
    
    // Get some technicians
    $technicians = $pdo->query("SELECT id FROM technicians LIMIT 5")->fetchAll();
    
    if (count($technicians) > 0) {
        foreach ($technicians as $index => $tech) {
            if (isset($sample_coords[$index])) {
                $lat = $sample_coords[$index][0];
                $lng = $sample_coords[$index][1];
                $success = $gps->updateTechnicianLocation($tech['id'], $lat, $lng, 10);
                echo "Updated technician {$tech['id']} location: " . ($success ? 'SUCCESS' : 'FAILED') . "<br>\n";
            }
        }
    }
    
    // Add some service requests with GPS
    $service_requests = $pdo->query("SELECT id FROM service_requests LIMIT 5")->fetchAll();
    if (count($service_requests) > 0) {
        foreach ($service_requests as $index => $req) {
            if (isset($sample_coords[$index])) {
                $lat = $sample_coords[$index][0] + (rand(-50, 50) * 0.0001); // Add some variation
                $lng = $sample_coords[$index][1] + (rand(-50, 50) * 0.0001);
                $success = $gps->saveCustomerLocation($req['id'], $lat, $lng, "Kigali Area " . ($index + 1), 15);
                echo "Updated service request {$req['id']} location: " . ($success ? 'SUCCESS' : 'FAILED') . "<br>\n";
            }
        }
    }
    
    echo "<br><strong>Database setup complete!</strong><br>\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>