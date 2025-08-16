<?php
/**
 * Find Nearest Technicians API
 * Returns technicians near a given location
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in API response

require_once '../includes/db.php';
require_once '../includes/GPSManager.php';

try {
    // Validate required parameters
    $latitude = floatval($_GET['lat'] ?? 0);
    $longitude = floatval($_GET['lng'] ?? 0);
    $service_type = $_GET['service_type'] ?? '';
    $radius = intval($_GET['radius'] ?? 50);
    $limit = min(intval($_GET['limit'] ?? 10), 20); // Max 20 results
    
    if ($latitude == 0 || $longitude == 0) {
        throw new Exception('Invalid coordinates provided');
    }
    
    // Validate service area (optional)
    $gps = new GPSManager($pdo);
    if (!$gps->isInServiceArea($latitude, $longitude)) {
        echo json_encode([
            'success' => false,
            'error' => 'Location is outside our service area',
            'service_area' => 'Currently serving Kigali and surrounding areas'
        ]);
        exit;
    }
    
    // Ensure technicians table exists
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS technicians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            specialization TEXT,
            rating DECIMAL(3,2) DEFAULT 5.00,
            status ENUM('active', 'inactive', 'busy') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Check if we have technicians, if not create sample data
        $techCount = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'active'")->fetchColumn();
        if ($techCount == 0) {
            $sampleTechnicians = [
                ['John Mugisha', 'john.mugisha@smartfix.com', '+250781234567', 'phone,computer', 4.8],
                ['Sarah Uwimana', 'sarah.uwimana@smartfix.com', '+250782345678', 'phone,electronics', 4.9],
                ['Peter Nkurunziza', 'peter.nkuru@smartfix.com', '+250783456789', 'car,automotive', 4.7],
                ['Grace Mukamana', 'grace.mukam@smartfix.com', '+250784567890', 'house,plumbing', 4.6],
                ['David Kayitare', 'david.kayit@smartfix.com', '+250785678901', 'electrician,electronics', 4.9],
                ['Alice Nyirahabimana', 'alice.nyira@smartfix.com', '+250786789012', 'computer,phone', 4.8]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO technicians (name, email, phone, specialization, rating) VALUES (?, ?, ?, ?, ?)");
            foreach ($sampleTechnicians as $tech) {
                $stmt->execute($tech);
            }
            
            // Add sample locations for technicians
            $techIds = $pdo->query("SELECT id FROM technicians ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
            $locations = [
                [-1.9441, 30.0619], // Kigali center
                [-1.9506, 30.0588], // Near city center
                [-1.9366, 30.0606], // Kimisagara
                [-1.9456, 30.0735], // Remera
                [-1.9706, 30.0384], // Nyamirambo
                [-1.9195, 30.0904]  // Kimironko
            ];
            
            foreach ($techIds as $index => $techId) {
                if (isset($locations[$index])) {
                    $gps->updateTechnicianLocation($techId, $locations[$index][0], $locations[$index][1], 50);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Technician table creation error: " . $e->getMessage());
    }
    
    // Find nearest technicians
    $technicians = $gps->findNearestTechnicians($latitude, $longitude, $service_type, $radius, $limit);
    
    if (empty($technicians)) {
        echo json_encode([
            'success' => true,
            'technicians' => [],
            'message' => 'No technicians found in your area. Try expanding your search radius.',
            'search_params' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'service_type' => $service_type,
                'radius_km' => $radius
            ]
        ]);
        exit;
    }
    
    // Format technicians data for frontend
    $formatted_technicians = array_map(function($tech) {
        return [
            'id' => (int)$tech['id'],
            'name' => $tech['name'],
            'email' => $tech['email'],
            'phone' => $tech['phone'],
            'specialization' => $tech['specialization'],
            'rating' => (float)$tech['rating'],
            'latitude' => (float)$tech['latitude'],
            'longitude' => (float)$tech['longitude'],
            'distance_km' => round((float)$tech['distance_km'], 2),
            'status' => $tech['status'],
            'last_updated' => $tech['last_updated']
        ];
    }, $technicians);
    
    echo json_encode([
        'success' => true,
        'technicians' => $formatted_technicians,
        'total_found' => count($formatted_technicians),
        'search_params' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'service_type' => $service_type,
            'radius_km' => $radius
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Find technicians API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'request_params' => $_GET,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>