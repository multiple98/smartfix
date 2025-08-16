<?php
/**
 * Reverse Geocoding API Endpoint
 * Converts coordinates to human-readable addresses
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';
require_once '../config/maps_config.php';

// Get coordinates from request
$latitude = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$longitude = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if (!$latitude || !$longitude) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid coordinates provided'
    ]);
    exit;
}

// Get Google Maps API key
$api_key = getGoogleMapsApiKey();

if (!$api_key || $api_key === 'YOUR_GOOGLE_MAPS_API_KEY') {
    // Fallback to basic location description
    $address = getBasicLocationDescription($latitude, $longitude);
    echo json_encode([
        'success' => true,
        'address' => $address,
        'source' => 'fallback'
    ]);
    exit;
}

// Use Google Maps Geocoding API
$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$api_key}";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response) {
    $data = json_decode($response, true);
    
    if ($data['status'] === 'OK' && !empty($data['results'])) {
        $formatted_address = $data['results'][0]['formatted_address'];
        
        // Store the geocoding result for future reference
        try {
            $stmt = $pdo->prepare("INSERT INTO geocoding_cache (latitude, longitude, address, created_at) 
                                  VALUES (?, ?, ?, NOW()) 
                                  ON DUPLICATE KEY UPDATE address = VALUES(address), updated_at = NOW()");
            $stmt->execute([$latitude, $longitude, $formatted_address]);
        } catch (PDOException $e) {
            // Ignore cache errors
        }
        
        echo json_encode([
            'success' => true,
            'address' => $formatted_address,
            'source' => 'google_maps'
        ]);
    } else {
        // Fallback to basic description
        $address = getBasicLocationDescription($latitude, $longitude);
        echo json_encode([
            'success' => true,
            'address' => $address,
            'source' => 'fallback',
            'google_error' => $data['status'] ?? 'Unknown error'
        ]);
    }
} else {
    // Check cache first
    try {
        $stmt = $pdo->prepare("SELECT address FROM geocoding_cache 
                              WHERE ABS(latitude - ?) < 0.001 AND ABS(longitude - ?) < 0.001 
                              ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$latitude, $longitude]);
        $cached = $stmt->fetch();
        
        if ($cached) {
            echo json_encode([
                'success' => true,
                'address' => $cached['address'],
                'source' => 'cache'
            ]);
            exit;
        }
    } catch (PDOException $e) {
        // Ignore cache errors
    }
    
    // Final fallback
    $address = getBasicLocationDescription($latitude, $longitude);
    echo json_encode([
        'success' => true,
        'address' => $address,
        'source' => 'fallback'
    ]);
}

/**
 * Generate basic location description based on coordinates
 */
function getBasicLocationDescription($lat, $lng) {
    // Lusaka area landmarks and districts
    $landmarks = [
        ['name' => 'Cairo Road Area', 'lat' => -15.4067, 'lng' => 28.2871, 'radius' => 0.01],
        ['name' => 'University of Zambia', 'lat' => -15.3928, 'lng' => 28.3228, 'radius' => 0.015],
        ['name' => 'Kabulonga', 'lat' => -15.3692, 'lng' => 28.3728, 'radius' => 0.02],
        ['name' => 'Woodlands', 'lat' => -15.3500, 'lng' => 28.3200, 'radius' => 0.02],
        ['name' => 'Chilanga', 'lat' => -15.5989, 'lng' => 28.2731, 'radius' => 0.03],
        ['name' => 'Kafue', 'lat' => -15.7694, 'lng' => 28.1814, 'radius' => 0.05],
        ['name' => 'Mazabuka', 'lat' => -15.8560, 'lng' => 27.7480, 'radius' => 0.05]
    ];
    
    // Find nearest landmark
    $nearest = null;
    $min_distance = PHP_FLOAT_MAX;
    
    foreach ($landmarks as $landmark) {
        $distance = sqrt(pow($lat - $landmark['lat'], 2) + pow($lng - $landmark['lng'], 2));
        if ($distance < $landmark['radius'] && $distance < $min_distance) {
            $nearest = $landmark;
            $min_distance = $distance;
        }
    }
    
    if ($nearest) {
        return "Near " . $nearest['name'] . ", Lusaka, Zambia";
    }
    
    // General area description
    if ($lat >= -15.6 && $lat <= -15.2 && $lng >= 28.1 && $lng <= 28.5) {
        return "Lusaka, Zambia";
    } elseif ($lat >= -16.0 && $lat <= -15.6 && $lng >= 27.5 && $lng <= 28.5) {
        return "Southern Lusaka Province, Zambia";
    } else {
        return "Lusaka Province, Zambia";
    }
}

// Create geocoding cache table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS geocoding_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_coords (latitude, longitude)
    )");
} catch (PDOException $e) {
    // Ignore table creation errors
}
?>