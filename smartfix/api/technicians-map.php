<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/db.php';

// Zambia geographical boundaries
$zambia_bounds = [
    'north' => -8.224,
    'south' => -18.079,
    'east' => 33.706,
    'west' => 21.999
];

try {
    // Get filter parameters
    $specialization = $_GET['specialization'] ?? '';
    $status = $_GET['status'] ?? '';
    $min_rating = floatval($_GET['min_rating'] ?? 0);
    $include_offline = $_GET['include_offline'] ?? 'true';
    
    // Build query with filters
    $where_conditions = ["t.status = 'available'"];
    $params = [];
    
    if ($specialization) {
        $where_conditions[] = "t.specialization LIKE ?";
        $params[] = "%{$specialization}%";
    }
    
    if ($status) {
        if ($status === 'online') {
            $where_conditions[] = "tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        } elseif ($status === 'recently_active') {
            $where_conditions[] = "tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) AND tl.last_updated <= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        } elseif ($status === 'offline') {
            $where_conditions[] = "(tl.last_updated IS NULL OR tl.last_updated <= DATE_SUB(NOW(), INTERVAL 2 HOUR))";
        }
    }
    
    if ($min_rating > 0) {
        $where_conditions[] = "t.rating >= ?";
        $params[] = $min_rating;
    }
    
    // Add Zambia boundary restrictions
    $where_conditions[] = "(tl.latitude IS NULL OR (tl.latitude BETWEEN ? AND ? AND tl.longitude BETWEEN ? AND ?))";
    $params = array_merge($params, [
        $zambia_bounds['south'], 
        $zambia_bounds['north'], 
        $zambia_bounds['west'], 
        $zambia_bounds['east']
    ]);
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $query = "SELECT 
                t.id,
                t.name,
                t.email,
                t.phone,
                t.specialization,
                t.regions,
                t.address,
                t.bio,
                t.rating,
                t.total_jobs,
                t.status,
                t.created_at,
                tl.latitude,
                tl.longitude,
                tl.accuracy,
                tl.last_updated,
                CASE 
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                    ELSE 'offline'
                END AS location_status,
                CASE 
                    WHEN tl.latitude IS NOT NULL AND tl.longitude IS NOT NULL THEN
                        CASE
                            WHEN tl.latitude BETWEEN ? AND ? AND tl.longitude BETWEEN ? AND ? THEN 'within_zambia'
                            ELSE 'outside_zambia'
                        END
                    ELSE 'no_location'
                END AS location_validity
              FROM technicians t
              LEFT JOIN technician_locations tl ON t.id = tl.technician_id
              WHERE {$where_clause}
              ORDER BY 
                CASE 
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 2
                    ELSE 3
                END,
                t.rating DESC, 
                t.total_jobs DESC";
    
    // Add boundary parameters again for the location_validity CASE statement
    $params = array_merge($params, [
        $zambia_bounds['south'], 
        $zambia_bounds['north'], 
        $zambia_bounds['west'], 
        $zambia_bounds['east']
    ]);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process and validate coordinates
    $processed_technicians = [];
    foreach ($technicians as $tech) {
        // Validate coordinates are within Zambia if they exist
        if ($tech['latitude'] && $tech['longitude']) {
            $lat = floatval($tech['latitude']);
            $lng = floatval($tech['longitude']);
            
            // Double-check coordinates are within Zambia boundaries
            if ($lat >= $zambia_bounds['south'] && $lat <= $zambia_bounds['north'] &&
                $lng >= $zambia_bounds['west'] && $lng <= $zambia_bounds['east']) {
                
                $tech['coordinates_valid'] = true;
                $tech['latitude'] = $lat;
                $tech['longitude'] = $lng;
            } else {
                // Coordinates outside Zambia - remove them
                $tech['coordinates_valid'] = false;
                $tech['latitude'] = null;
                $tech['longitude'] = null;
                $tech['location_status'] = 'offline';
            }
        } else {
            $tech['coordinates_valid'] = false;
        }
        
        // Format data for frontend
        $tech['rating'] = floatval($tech['rating']);
        $tech['total_jobs'] = intval($tech['total_jobs']);
        $tech['specialization_list'] = explode(',', $tech['specialization']);
        $tech['regions_list'] = $tech['regions'] ? explode(',', $tech['regions']) : [];
        
        // Add distance calculation helper (will be calculated on frontend)
        $tech['distance_km'] = null;
        
        $processed_technicians[] = $tech;
    }
    
    // Get summary statistics
    $total_technicians = count($processed_technicians);
    $with_location = count(array_filter($processed_technicians, function($t) { 
        return $t['coordinates_valid']; 
    }));
    $online = count(array_filter($processed_technicians, function($t) { 
        return $t['location_status'] === 'online'; 
    }));
    $recently_active = count(array_filter($processed_technicians, function($t) { 
        return $t['location_status'] === 'recently_active'; 
    }));
    
    // Get specialization breakdown
    $specializations = [];
    foreach ($processed_technicians as $tech) {
        foreach ($tech['specialization_list'] as $spec) {
            $spec = trim($spec);
            if ($spec) {
                $specializations[$spec] = ($specializations[$spec] ?? 0) + 1;
            }
        }
    }
    arsort($specializations);
    
    $response = [
        'success' => true,
        'data' => [
            'technicians' => $processed_technicians,
            'statistics' => [
                'total_technicians' => $total_technicians,
                'with_location' => $with_location,
                'online' => $online,
                'recently_active' => $recently_active,
                'offline' => $total_technicians - $online - $recently_active
            ],
            'specializations' => $specializations,
            'zambia_bounds' => $zambia_bounds,
            'filters_applied' => [
                'specialization' => $specialization,
                'status' => $status,
                'min_rating' => $min_rating
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Technicians Map API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => 'Unable to fetch technician data',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Technicians Map API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',
        'message' => 'Unable to process request',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>