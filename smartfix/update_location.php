<?php
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if request is POST and has JSON data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['latitude']) && isset($input['longitude'])) {
        $latitude = floatval($input['latitude']);
        $longitude = floatval($input['longitude']);
        
        // Validate coordinates (basic validation for Zambia region)
        if ($latitude >= -18.5 && $latitude <= -8.0 && $longitude >= 21.0 && $longitude <= 34.0) {
            $_SESSION['user_latitude'] = $latitude;
            $_SESSION['user_longitude'] = $longitude;
            $_SESSION['location_updated'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Location updated successfully',
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid coordinates for Zambia region'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Missing latitude or longitude'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>