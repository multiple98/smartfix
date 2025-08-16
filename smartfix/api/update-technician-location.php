<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

require_once '../includes/db.php';
require_once '../includes/GPSManager.php';
require_once '../includes/ZambiaLocationValidator.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['technician_id', 'latitude', 'longitude'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required field',
                'message' => "Field '{$field}' is required",
                'required_fields' => $required_fields
            ]);
            exit;
        }
    }
    
    $technician_id = intval($input['technician_id']);
    $latitude = floatval($input['latitude']);
    $longitude = floatval($input['longitude']);
    $accuracy = isset($input['accuracy']) ? floatval($input['accuracy']) : null;
    
    // Validate technician exists
    $tech_check = $pdo->prepare("SELECT id, name, status FROM technicians WHERE id = ?");
    $tech_check->execute([$technician_id]);
    $technician = $tech_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$technician) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Technician not found',
            'message' => "No technician found with ID {$technician_id}"
        ]);
        exit;
    }
    
    // Check if technician is active
    if ($technician['status'] !== 'available') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Technician not available',
            'message' => 'Only available technicians can update their location',
            'technician_status' => $technician['status']
        ]);
        exit;
    }
    
    // Validate coordinates are within Zambia
    $validation = ZambiaLocationValidator::validateCoordinates($latitude, $longitude);
    
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid location',
            'message' => $validation['error'],
            'suggestion' => $validation['suggestion'] ?? 'Please ensure your location is within Zambia',
            'zambia_bounds' => ZambiaLocationValidator::getZambiaBounds(),
            'provided_coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ]
        ]);
        exit;
    }
    
    // Update location using GPS Manager
    $gps = new GPSManager($pdo);
    $result = $gps->updateTechnicianLocation($technician_id, $latitude, $longitude, $accuracy);
    
    if ($result['success']) {
        // Get updated location status
        $status_query = $pdo->prepare("
            SELECT 
                tl.latitude,
                tl.longitude,
                tl.accuracy,
                tl.last_updated,
                CASE 
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                    ELSE 'offline'
                END AS location_status
            FROM technician_locations tl
            WHERE tl.technician_id = ?
        ");
        $status_query->execute([$technician_id]);
        $location_info = $status_query->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'technician_id' => $technician_id,
                'technician_name' => $technician['name'],
                'coordinates' => [
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'accuracy' => $accuracy,
                    'formatted' => ZambiaLocationValidator::formatCoordinates($result['latitude'], $result['longitude'])
                ],
                'location_info' => [
                    'province' => $result['province'],
                    'nearest_city' => $result['nearest_city'],
                    'distance_to_city' => $result['distance_to_city'],
                    'accuracy_description' => ZambiaLocationValidator::getAccuracyDescription($accuracy)
                ],
                'status' => [
                    'location_status' => $location_info['location_status'],
                    'last_updated' => $location_info['last_updated']
                ]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Update failed',
            'message' => $result['error'],
            'suggestion' => $result['suggestion'] ?? 'Please try again later'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Update Technician Location API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => 'Unable to update location due to database error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Update Technician Location API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'Unable to process location update',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>