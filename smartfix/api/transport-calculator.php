<?php
/**
 * Transport Cost Calculator API
 * Calculates delivery costs based on distance, weight, and service type
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';
require_once '../config/maps_config.php';

// Get request parameters
$pickup_lat = isset($_GET['pickup_lat']) ? floatval($_GET['pickup_lat']) : null;
$pickup_lng = isset($_GET['pickup_lng']) ? floatval($_GET['pickup_lng']) : null;
$delivery_lat = isset($_GET['delivery_lat']) ? floatval($_GET['delivery_lat']) : null;
$delivery_lng = isset($_GET['delivery_lng']) ? floatval($_GET['delivery_lng']) : null;
$weight = isset($_GET['weight']) ? floatval($_GET['weight']) : 1.0;
$service_type = isset($_GET['service_type']) ? $_GET['service_type'] : 'standard';

// Validate required parameters
if (!$pickup_lat || !$pickup_lng || !$delivery_lat || !$delivery_lng) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required coordinates'
    ]);
    exit;
}

try {
    // Calculate distance using Haversine formula
    $distance_km = calculateDistance($pickup_lat, $pickup_lng, $delivery_lat, $delivery_lng);
    
    // Get available transport providers
    $providers = getAvailableProviders($distance_km, $weight, $service_type);
    
    // Calculate quotes for each provider
    $quotes = [];
    foreach ($providers as $provider) {
        $quote = calculateQuote($provider, $distance_km, $weight, $service_type);
        if ($quote) {
            $quotes[] = $quote;
        }
    }
    
    // Sort by cost (cheapest first)
    usort($quotes, function($a, $b) {
        return $a['total_cost'] <=> $b['total_cost'];
    });
    
    echo json_encode([
        'success' => true,
        'distance_km' => round($distance_km, 2),
        'weight_kg' => $weight,
        'service_type' => $service_type,
        'quotes' => $quotes,
        'pickup_coordinates' => ['lat' => $pickup_lat, 'lng' => $pickup_lng],
        'delivery_coordinates' => ['lat' => $delivery_lat, 'lng' => $delivery_lng]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Calculate distance between two points using Haversine formula
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

/**
 * Get available transport providers
 */
function getAvailableProviders($distance_km, $weight, $service_type) {
    global $pdo;
    
    $query = "SELECT * FROM transport_providers 
              WHERE status = 'active' 
              AND max_weight_kg >= ? 
              AND (service_type = ? OR service_type = 'standard')
              ORDER BY rating DESC, cost_per_km ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$weight, $service_type]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calculate quote for a specific provider
 */
function calculateQuote($provider, $distance_km, $weight, $service_type) {
    // Base cost calculation
    $base_cost = floatval($provider['base_cost']);
    $distance_cost = $distance_km * floatval($provider['cost_per_km']);
    
    // Weight surcharge (for items over 10kg)
    $weight_surcharge = 0;
    if ($weight > 10) {
        $weight_surcharge = ($weight - 10) * 2.00; // K2 per extra kg
    }
    
    // Service type multiplier
    $service_multiplier = 1.0;
    switch ($service_type) {
        case 'same_day':
            $service_multiplier = 1.5;
            break;
        case 'express':
            $service_multiplier = 1.3;
            break;
        case 'overnight':
            $service_multiplier = 1.2;
            break;
        case 'standard':
        default:
            $service_multiplier = 1.0;
            break;
    }
    
    // Distance surcharge for long distances
    $distance_surcharge = 0;
    if ($distance_km > 50) {
        $distance_surcharge = ($distance_km - 50) * 1.50; // K1.50 per km over 50km
    }
    
    // Calculate total cost
    $subtotal = ($base_cost + $distance_cost + $weight_surcharge + $distance_surcharge) * $service_multiplier;
    $total_cost = round($subtotal, 2);
    
    // Estimate delivery time
    $estimated_hours = calculateDeliveryTime($distance_km, $provider['vehicle_type'], $service_type);
    
    return [
        'provider_id' => $provider['id'],
        'provider_name' => $provider['name'],
        'vehicle_type' => $provider['vehicle_type'],
        'service_type' => $provider['service_type'],
        'rating' => floatval($provider['rating']),
        'base_cost' => $base_cost,
        'distance_cost' => round($distance_cost, 2),
        'weight_surcharge' => round($weight_surcharge, 2),
        'distance_surcharge' => round($distance_surcharge, 2),
        'service_multiplier' => $service_multiplier,
        'total_cost' => $total_cost,
        'estimated_delivery_hours' => $estimated_hours,
        'estimated_delivery_time' => date('Y-m-d H:i:s', strtotime("+{$estimated_hours} hours")),
        'contact' => $provider['contact'],
        'description' => $provider['description']
    ];
}

/**
 * Calculate estimated delivery time
 */
function calculateDeliveryTime($distance_km, $vehicle_type, $service_type) {
    // Base speed by vehicle type (km/h in city traffic)
    $speeds = [
        'motorbike' => 25,
        'car' => 30,
        'van' => 25,
        'truck' => 20
    ];
    
    $base_speed = $speeds[$vehicle_type] ?? 25;
    
    // Service type time adjustments
    $time_multiplier = 1.0;
    switch ($service_type) {
        case 'same_day':
            $time_multiplier = 0.5; // Rush delivery
            break;
        case 'express':
            $time_multiplier = 0.7;
            break;
        case 'overnight':
            $time_multiplier = 1.0;
            break;
        case 'standard':
        default:
            $time_multiplier = 1.5; // Allow for multiple stops
            break;
    }
    
    // Calculate travel time
    $travel_time = ($distance_km / $base_speed) * $time_multiplier;
    
    // Add processing time (pickup, sorting, etc.)
    $processing_time = 1.0; // 1 hour base processing
    
    // Add buffer for traffic and delays
    $buffer_time = $travel_time * 0.3; // 30% buffer
    
    $total_hours = $travel_time + $processing_time + $buffer_time;
    
    // Minimum delivery time based on service type
    $min_hours = [
        'same_day' => 2,
        'express' => 4,
        'overnight' => 12,
        'standard' => 24
    ];
    
    return max($total_hours, $min_hours[$service_type] ?? 24);
}

/**
 * Save quote to database for future reference
 */
function saveQuote($order_id, $provider_id, $pickup_address, $delivery_address, $distance_km, $estimated_cost, $estimated_hours) {
    global $pdo;
    
    try {
        $valid_until = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO transport_quotes 
                  (order_id, transport_provider_id, pickup_address, delivery_address, 
                   distance_km, estimated_cost, estimated_delivery_time, quote_valid_until, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $pdo->prepare($query);
        return $stmt->execute([
            $order_id, $provider_id, $pickup_address, $delivery_address,
            $distance_km, $estimated_cost, $estimated_hours, $valid_until
        ]);
        
    } catch (PDOException $e) {
        error_log("Save quote error: " . $e->getMessage());
        return false;
    }
}
?>