<?php
session_start();
include('includes/db.php');

// Get parameters
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

// Get user's location if available
$user_lat = isset($_SESSION['user_latitude']) ? floatval($_SESSION['user_latitude']) : null;
$user_lng = isset($_SESSION['user_longitude']) ? floatval($_SESSION['user_longitude']) : null;

// Function to calculate distance between two points using Haversine formula
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    if ($lat1 == null || $lng1 == null || $lat2 == null || $lng2 == null) {
        return 10; // Default 10km if no GPS data
    }
    
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLng/2) * sin($dLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

// Get order details if order_id provided
$order = null;
$total_weight = 5; // Default weight in kg
$order_value = 0;
$delivery_address = '';

if ($order_id > 0) {
    try {
        // First check what columns exist in orders table
        $columns_check = $pdo->query("SHOW COLUMNS FROM orders");
        $available_columns = [];
        while ($col = $columns_check->fetch(PDO::FETCH_ASSOC)) {
            $available_columns[] = $col['Field'];
        }
        
        // Build query based on available columns
        $select_fields = "o.*";
        if (in_array('shipping_address', $available_columns)) {
            $select_fields .= ", o.shipping_address, o.shipping_city, o.shipping_name";
        }
        if (in_array('shipping_province', $available_columns)) {
            $select_fields .= ", o.shipping_province";
        }
        
        $stmt = $pdo->prepare("SELECT $select_fields,
                              (SELECT SUM(oi.quantity * p.price) 
                               FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = o.id) as calculated_total
                              FROM orders o WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $order_value = $order['total_amount'];
            
            // Build delivery address from available fields
            if (isset($order['shipping_address']) && !empty($order['shipping_address'])) {
                $city = $order['shipping_city'] ?? 'Lusaka';
                $province = isset($order['shipping_province']) ? ', ' . $order['shipping_province'] : '';
                $delivery_address = $order['shipping_address'] . ', ' . $city . $province;
            } elseif (isset($order['customer_address']) && !empty($order['customer_address'])) {
                $city = $order['customer_city'] ?? 'Lusaka';
                $province = isset($order['customer_province']) ? ', ' . $order['customer_province'] : '';
                $delivery_address = $order['customer_address'] . ', ' . $city . $province;
            } else {
                $delivery_address = 'Customer Address, Lusaka';
            }
            
            // Estimate weight based on order items
            $weight_stmt = $pdo->prepare("SELECT SUM(oi.quantity) as total_items 
                                         FROM order_items oi 
                                         WHERE oi.order_id = ?");
            $weight_stmt->execute([$order_id]);
            $weight_result = $weight_stmt->fetch();
            $total_weight = ($weight_result['total_items'] ?? 1) * 2; // Estimate 2kg per item
        }
    } catch (PDOException $e) {
        // Handle error - set defaults
        $delivery_address = 'Customer Address, Lusaka';
        $order_value = 100; // Default order value
    }
} elseif ($product_id > 0) {
    // Get product details for single product order
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $order_value = $product['price'] * $quantity;
            $total_weight = $quantity * 1.5; // Estimate 1.5kg per product
        }
    } catch (PDOException $e) {
        // Handle error
    }
}

// Get available transport providers
$transport_options = [];
try {
    // First check if transport_providers table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'transport_providers'");
    if ($table_check->rowCount() == 0) {
        // Create the table if it doesn't exist
        $create_transport = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            regions TEXT NOT NULL,
            estimated_days INT NOT NULL DEFAULT 3,
            cost_per_km DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            base_cost DECIMAL(10,2) NOT NULL DEFAULT 20.00,
            max_weight_kg INT NOT NULL DEFAULT 50,
            service_type ENUM('standard', 'express', 'overnight', 'same_day') DEFAULT 'standard',
            vehicle_type VARCHAR(50) DEFAULT 'Van',
            rating DECIMAL(3,2) DEFAULT 4.0,
            operating_hours VARCHAR(100) DEFAULT '8:00 AM - 6:00 PM',
            latitude DECIMAL(10,8) DEFAULT -15.3875,
            longitude DECIMAL(11,8) DEFAULT 28.3228,
            status ENUM('active', 'inactive') DEFAULT 'active',
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_transport);
        
        // Add some default providers
        $default_providers = [
            ['FastTrack Delivery', '+260-97-123-4567', 'info@fasttrack.zm', 'Lusaka,Copperbelt,Central', 2, 3.50, 25.00, 'express', 'Fast and reliable delivery service'],
            ['City Express', '+260-96-987-6543', 'orders@cityexpress.zm', 'Lusaka,Eastern,Southern', 3, 2.80, 20.00, 'standard', 'Affordable delivery service'],
            ['QuickMove Logistics', '+260-95-555-0123', 'support@quickmove.zm', 'Lusaka,Copperbelt,Northern', 1, 5.00, 40.00, 'same_day', 'Same-day delivery for urgent orders']
        ];
        
        $insert_provider = $pdo->prepare("INSERT INTO transport_providers (name, contact, email, regions, estimated_days, cost_per_km, base_cost, service_type, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($default_providers as $provider) {
            $insert_provider->execute($provider);
        }
    }
    
    // Check what columns exist in the table
    $columns_result = $pdo->query("SHOW COLUMNS FROM transport_providers");
    $available_columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
    
    // Build query based on available columns
    $select_fields = "id, name, contact, regions, estimated_days, cost_per_km";
    if (in_array('description', $available_columns)) $select_fields .= ", description";
    if (in_array('base_cost', $available_columns)) $select_fields .= ", base_cost";
    if (in_array('max_weight_kg', $available_columns)) $select_fields .= ", max_weight_kg";
    if (in_array('service_type', $available_columns)) $select_fields .= ", service_type";
    if (in_array('vehicle_type', $available_columns)) $select_fields .= ", vehicle_type";
    if (in_array('rating', $available_columns)) $select_fields .= ", rating";
    if (in_array('operating_hours', $available_columns)) $select_fields .= ", operating_hours";
    if (in_array('latitude', $available_columns)) $select_fields .= ", latitude";
    if (in_array('longitude', $available_columns)) $select_fields .= ", longitude";
    if (in_array('status', $available_columns)) $select_fields .= ", status";
    
    $where_clause = "";
    if (in_array('status', $available_columns)) {
        $where_clause = "WHERE status = 'active'";
    }
    
    $order_clause = "ORDER BY ";
    if (in_array('rating', $available_columns)) {
        $order_clause .= "rating DESC, ";
    }
    $order_clause .= "cost_per_km ASC";
    
    $query = "SELECT $select_fields FROM transport_providers $where_clause $order_clause";
    $stmt = $pdo->query($query);
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($providers as $provider) {
        // Check if provider can handle the weight (use default if column doesn't exist)
        $max_weight = isset($provider['max_weight_kg']) ? $provider['max_weight_kg'] : 100;
        
        if ($total_weight <= $max_weight) {
            // Calculate distance and cost
            $provider_lat = isset($provider['latitude']) ? $provider['latitude'] : -15.3875; // Default Lusaka coordinates
            $provider_lng = isset($provider['longitude']) ? $provider['longitude'] : 28.3228;
            $distance = calculateDistance($user_lat, $user_lng, $provider_lat, $provider_lng);
            
            $base_cost = isset($provider['base_cost']) ? $provider['base_cost'] : 20.00;
            $delivery_cost = $base_cost + ($distance * $provider['cost_per_km']);
            
            // Add service type premium if service_type column exists
            $service_multiplier = 1.0;
            if (isset($provider['service_type'])) {
                switch ($provider['service_type']) {
                    case 'express':
                        $service_multiplier = 1.5;
                        break;
                    case 'overnight':
                        $service_multiplier = 1.8;
                        break;
                    case 'same_day':
                        $service_multiplier = 2.0;
                        break;
                }
            }
            
            $delivery_cost *= $service_multiplier;
            
            $transport_options[] = [
                'id' => $provider['id'],
                'name' => $provider['name'],
                'contact' => $provider['contact'],
                'description' => isset($provider['description']) ? $provider['description'] : 'Reliable delivery service',
                'service_type' => isset($provider['service_type']) ? $provider['service_type'] : 'standard',
                'vehicle_type' => isset($provider['vehicle_type']) ? $provider['vehicle_type'] : 'Van',
                'estimated_days' => $provider['estimated_days'],
                'distance_km' => round($distance, 2),
                'cost' => round($delivery_cost, 2),
                'rating' => isset($provider['rating']) ? $provider['rating'] : 4.0,
                'operating_hours' => isset($provider['operating_hours']) ? $provider['operating_hours'] : '8:00 AM - 6:00 PM'
            ];
        }
    }
    
    // Sort by cost
    usort($transport_options, function($a, $b) {
        return $a['cost'] <=> $b['cost'];
    });
    
} catch (PDOException $e) {
    // Handle error
}

// Handle transport selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_transport'])) {
    $selected_transport_id = intval($_POST['transport_id']);
    $customer_lat = floatval($_POST['customer_lat'] ?? 0);
    $customer_lng = floatval($_POST['customer_lng'] ?? 0);
    
    try {
        // Create transport quote
        $quote_stmt = $pdo->prepare("INSERT INTO transport_quotes 
                                    (order_id, transport_provider_id, pickup_address, delivery_address, 
                                     distance_km, estimated_cost, estimated_delivery_time, quote_valid_until, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 'accepted')");
        
        // Find selected transport option
        $selected_option = null;
        foreach ($transport_options as $option) {
            if ($option['id'] == $selected_transport_id) {
                $selected_option = $option;
                break;
            }
        }
        
        if ($selected_option && $order_id > 0) {
            $quote_stmt->execute([
                $order_id,
                $selected_transport_id,
                'SmartFix Warehouse, Lusaka',
                $delivery_address,
                $selected_option['distance_km'],
                $selected_option['cost'],
                $selected_option['estimated_days']
            ]);
            
            // Update order with transport details
            $update_order = "UPDATE orders SET transport_id = ?, transport_cost = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_order);
            $update_stmt->execute([$selected_transport_id, $selected_option['cost'], $order_id]);
            
            // Add tracking entry
            $tracking_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location) 
                                           VALUES (?, 'Transport Selected', ?, 'Processing Center')");
            $tracking_stmt->execute([$order_id, "Selected {$selected_option['name']} for delivery (K{$selected_option['cost']})"]);
            
            $success_message = "Transport option selected successfully! Estimated cost: K{$selected_option['cost']}";
        }
        
    } catch (PDOException $e) {
        $error_message = "Error selecting transport: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Transport Selector - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            color: #004080;
            font-size: 28px;
        }
        
        .header p {
            color: #666;
            margin: 10px 0 0 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .order-summary {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        
        .order-summary h2 {
            margin-top: 0;
            color: #004080;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .summary-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
        }
        
        .summary-item .label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .transport-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .transport-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 3px solid transparent;
            position: relative;
        }
        
        .transport-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-color: #007BFF;
        }
        
        .transport-card.recommended {
            border-color: #28a745;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(255, 255, 255, 0.95) 50%);
        }
        
        .transport-card.recommended::before {
            content: "RECOMMENDED";
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .transport-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .transport-name {
            font-size: 20px;
            font-weight: bold;
            color: #004080;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .transport-cost {
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
        }
        
        .transport-info {
            margin: 15px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .service-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .service-standard { background: #17a2b8; color: white; }
        .service-express { background: #ffc107; color: #333; }
        .service-overnight { background: #fd7e14; color: white; }
        .service-same_day { background: #dc3545; color: white; }
        
        .rating {
            color: #ffc107;
        }
        
        .select-btn {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .select-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .location-prompt {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-location {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .transport-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-truck"></i> Smart Transport Selector</h1>
        <p>Choose the best delivery option for your purchase</p>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <div style="margin-top: 15px;">
                    <a href="shop/order_confirmation.php?id=<?php echo $order_id; ?>" class="btn-location">
                        View Order Details
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$user_lat || !$user_lng): ?>
            <div class="location-prompt">
                <i class="fas fa-map-marker-alt" style="font-size: 24px; margin-bottom: 10px;"></i>
                <h3>Enable Location Services</h3>
                <p>For accurate distance calculations and better transport suggestions, please share your location.</p>
                <button class="btn-location" onclick="getLocation()">
                    <i class="fas fa-crosshairs"></i> Share My Location
                </button>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <h2><i class="fas fa-box"></i> Order Summary</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="value">K<?php echo number_format($order_value, 2); ?></div>
                    <div class="label">Order Value</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?php echo $total_weight; ?> kg</div>
                    <div class="label">Estimated Weight</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?php echo count($transport_options); ?></div>
                    <div class="label">Available Options</div>
                </div>
                <div class="summary-item">
                    <div class="value"><?php 
                        if ($order_id > 0 && isset($order)) {
                            echo $order['tracking_number'] ?? 'ORD-' . $order_id;
                        } else {
                            echo 'New Order';
                        }
                    ?></div>
                    <div class="label">Order Reference</div>
                </div>
            </div>
        </div>

        <?php if (!empty($transport_options)): ?>
            <form method="POST" id="transportForm">
                <input type="hidden" name="customer_lat" id="customerLat" value="<?php echo $user_lat; ?>">
                <input type="hidden" name="customer_lng" id="customerLng" value="<?php echo $user_lng; ?>">
                
                <div class="transport-grid">
                    <?php foreach ($transport_options as $index => $option): ?>
                        <div class="transport-card <?php echo $index === 0 ? 'recommended' : ''; ?>">
                            <div class="transport-header">
                                <div class="transport-name">
                                    <i class="fas fa-<?php 
                                        echo $option['vehicle_type'] === 'motorbike' ? 'motorcycle' : 
                                            ($option['vehicle_type'] === 'truck' ? 'truck' : 'car'); 
                                    ?>"></i>
                                    <?php echo htmlspecialchars($option['name']); ?>
                                </div>
                                <div class="transport-cost">K<?php echo number_format($option['cost'], 2); ?></div>
                            </div>

                            <div class="transport-info">
                                <div class="info-row">
                                    <span>Service Type:</span>
                                    <span class="service-type service-<?php echo $option['service_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $option['service_type'])); ?>
                                    </span>
                                </div>
                                
                                <div class="info-row">
                                    <span>Delivery Time:</span>
                                    <span><?php echo $option['estimated_days']; ?> day<?php echo $option['estimated_days'] != 1 ? 's' : ''; ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span>Distance:</span>
                                    <span><?php echo $option['distance_km']; ?> km</span>
                                </div>
                                
                                <div class="info-row">
                                    <span>Vehicle:</span>
                                    <span><?php echo ucfirst($option['vehicle_type']); ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span>Contact:</span>
                                    <span><?php echo htmlspecialchars($option['contact']); ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span>Rating:</span>
                                    <span class="rating">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $option['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                        (<?php echo $option['rating']; ?>)
                                    </span>
                                </div>
                            </div>

                            <?php if ($option['description']): ?>
                                <p style="color: #666; font-size: 14px; margin: 10px 0;">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($option['description']); ?>
                                </p>
                            <?php endif; ?>

                            <button type="submit" name="select_transport" value="1" 
                                    onclick="document.querySelector('input[name=transport_id]').value = <?php echo $option['id']; ?>"
                                    class="select-btn">
                                <i class="fas fa-check"></i> Select This Option
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="transport_id" id="transportId">
            </form>
        <?php else: ?>
            <div class="transport-card" style="text-align: center; padding: 60px;">
                <i class="fas fa-truck" style="font-size: 64px; color: #dee2e6; margin-bottom: 20px;"></i>
                <h3>No Transport Options Available</h3>
                <p>Unfortunately, no transport providers can handle this delivery at the moment.</p>
                <p>Please contact customer service at <strong>+260 777 041 357</strong> for assistance.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    document.getElementById('customerLat').value = lat;
                    document.getElementById('customerLng').value = lng;
                    
                    // Store in session via AJAX
                    fetch('update_location.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'lat=' + lat + '&lng=' + lng
                    }).then(() => {
                        location.reload(); // Refresh to recalculate distances
                    });
                }, function(error) {
                    alert('Location access denied or unavailable. Distance calculations will use estimated values.');
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Auto-get location on page load if not available
        if (!<?php echo $user_lat ? 'true' : 'false'; ?> && navigator.geolocation) {
            setTimeout(getLocation, 1000);
        }
    </script>
</body>
</html>