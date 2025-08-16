<?php
header('Content-Type: application/json');
include('../includes/db.php');
include('transport_calculator.php');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['province'])) {
    echo json_encode(['success' => false, 'error' => 'Province is required']);
    exit;
}

$province = $input['province'];
$city = $input['city'] ?? '';
$weight = floatval($input['weight'] ?? 0);

try {
    // Initialize transport calculator
    $transport_calc = new TransportCalculator($pdo);
    
    // Get available providers
    $providers = $transport_calc->getAvailableProviders($province, $city, $weight);
    
    $response_providers = [];
    
    foreach ($providers as $provider) {
        // Calculate shipping cost
        $cost_data = $transport_calc->calculateShippingCost($provider['id'], $province, $city, $weight);
        
        if ($cost_data) {
            // Get delivery estimate
            $delivery_estimate = $transport_calc->getDeliveryEstimate($provider['id'], $province);
            
            $response_providers[] = [
                'id' => $provider['id'],
                'name' => $provider['name'],
                'description' => $provider['description'] ?? 'Professional delivery service',
                'contact' => $provider['contact'],
                'email' => $provider['email'] ?? '',
                'service_type' => ucfirst($provider['service_type'] ?? 'standard'),
                'vehicle_type' => $provider['vehicle_type'] ?? 'Van',
                'estimated_days' => $provider['estimated_days'],
                'rating' => floatval($provider['rating'] ?? 4.0),
                'operating_hours' => $provider['operating_hours'] ?? '8:00 AM - 6:00 PM',
                'shipping_cost' => $cost_data['total_cost'],
                'cost_breakdown' => [
                    'base_cost' => $cost_data['base_cost'],
                    'distance_cost' => $cost_data['distance_cost'],
                    'weight_surcharge' => $cost_data['weight_surcharge'],
                    'service_multiplier' => $cost_data['service_multiplier'],
                    'estimated_distance' => $cost_data['estimated_distance']
                ],
                'delivery_estimate' => $delivery_estimate
            ];
        }
    }
    
    // Sort by total cost (cheapest first)
    usort($response_providers, function($a, $b) {
        return $a['shipping_cost'] <=> $b['shipping_cost'];
    });
    
    echo json_encode([
        'success' => true,
        'providers' => $response_providers,
        'total_providers' => count($response_providers),
        'province' => $province,
        'city' => $city,
        'weight' => $weight
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching transport providers: ' . $e->getMessage()
    ]);
}
?>