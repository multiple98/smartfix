<?php
session_start();
include('includes/db.php');

// Get parameters
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$pickup_address = isset($_GET['pickup']) ? trim($_GET['pickup']) : 'SmartFix Warehouse, Lusaka';
$delivery_address = isset($_GET['delivery']) ? trim($_GET['delivery']) : '';
$weight_kg = isset($_GET['weight']) ? floatval($_GET['weight']) : 5.0;

// Handle quote request
$quotes = [];
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_quotes'])) {
    $pickup_address = trim($_POST['pickup_address']);
    $delivery_address = trim($_POST['delivery_address']);
    $weight_kg = floatval($_POST['weight_kg']);
    $customer_lat = floatval($_POST['customer_lat'] ?? -15.4067);
    $customer_lng = floatval($_POST['customer_lng'] ?? 28.2871);
    
    if (empty($pickup_address) || empty($delivery_address) || $weight_kg <= 0) {
        $error_message = "Please fill in all required fields with valid values.";
    } else {
        try {
            // Get available transport providers
            $providers_stmt = $pdo->prepare("SELECT * FROM transport_providers 
                                           WHERE status = 'active' AND max_weight_kg >= ? 
                                           ORDER BY rating DESC");
            $providers_stmt->execute([$weight_kg]);
            $providers = $providers_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($providers as $provider) {
                // Calculate distance from customer to pickup, then to delivery
                $pickup_distance = calculateDistance($customer_lat, $customer_lng, 
                                                   $provider['latitude'], $provider['longitude']);
                $delivery_distance = 10; // Estimate delivery distance
                $total_distance = $pickup_distance + $delivery_distance;
                
                // Calculate base cost
                $base_cost = $provider['base_cost'];
                $distance_cost = $total_distance * $provider['cost_per_km'];
                
                // Apply service type multiplier
                $service_multiplier = 1.0;
                switch ($provider['service_type']) {
                    case 'express': $service_multiplier = 1.5; break;
                    case 'overnight': $service_multiplier = 1.8; break;
                    case 'same_day': $service_multiplier = 2.0; break;
                }
                
                // Weight-based pricing
                $weight_multiplier = 1.0;
                if ($weight_kg > 20) {
                    $weight_multiplier = 1.2;
                } elseif ($weight_kg > 50) {
                    $weight_multiplier = 1.5;
                }
                
                $total_cost = ($base_cost + $distance_cost) * $service_multiplier * $weight_multiplier;
                
                // Create quote
                $quote = [
                    'provider_id' => $provider['id'],
                    'provider_name' => $provider['name'],
                    'contact' => $provider['contact'],
                    'email' => $provider['email'],
                    'description' => $provider['description'],
                    'service_type' => $provider['service_type'],
                    'vehicle_type' => $provider['vehicle_type'],
                    'estimated_days' => $provider['estimated_days'],
                    'total_distance' => round($total_distance, 2),
                    'total_cost' => round($total_cost, 2),
                    'base_cost' => $base_cost,
                    'distance_cost' => round($distance_cost, 2),
                    'service_multiplier' => $service_multiplier,
                    'weight_multiplier' => $weight_multiplier,
                    'rating' => $provider['rating'],
                    'operating_hours' => $provider['operating_hours'],
                    'max_weight' => $provider['max_weight_kg']
                ];
                
                $quotes[] = $quote;
            }
            
            // Sort quotes by total cost
            usort($quotes, function($a, $b) {
                return $a['total_cost'] <=> $b['total_cost'];
            });
            
            $success_message = "Found " . count($quotes) . " available transport quotes.";
            
        } catch (PDOException $e) {
            $error_message = "Error generating quotes: " . $e->getMessage();
        }
    }
}

// Handle quote acceptance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_quote'])) {
    $provider_id = intval($_POST['provider_id']);
    $quote_cost = floatval($_POST['quote_cost']);
    $estimated_days = intval($_POST['estimated_days']);
    
    try {
        // Save accepted quote
        $quote_stmt = $pdo->prepare("INSERT INTO transport_quotes 
                                    (order_id, transport_provider_id, pickup_address, delivery_address, 
                                     distance_km, estimated_cost, estimated_delivery_time, quote_valid_until, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 'accepted')");
        
        $quote_stmt->execute([
            $order_id, $provider_id, $pickup_address, $delivery_address,
            $_POST['total_distance'], $quote_cost, $estimated_days
        ]);
        
        // Update order if order_id exists
        if ($order_id > 0) {
            $update_stmt = $pdo->prepare("UPDATE orders SET transport_id = ?, transport_cost = ? WHERE id = ?");
            $update_stmt->execute([$provider_id, $quote_cost, $order_id]);
        }
        
        $success_message = "Quote accepted successfully! Transport cost: K" . number_format($quote_cost, 2);
        
    } catch (PDOException $e) {
        $error_message = "Error accepting quote: " . $e->getMessage();
    }
}

// Function to calculate distance
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    if ($lat1 == null || $lng1 == null || $lat2 == null || $lng2 == null) {
        return 15; // Default distance
    }
    
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Quotes - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .quote-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #004080;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #007BFF;
            outline: none;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007BFF;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .quotes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .quote-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .quote-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .quote-card.best-value {
            border: 3px solid #28a745;
        }
        
        .quote-card.best-value::before {
            content: "BEST VALUE";
            position: absolute;
            top: -10px;
            left: 20px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .quote-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .provider-name {
            font-size: 20px;
            font-weight: bold;
            color: #004080;
            margin-bottom: 5px;
        }
        
        .quote-price {
            font-size: 28px;
            font-weight: bold;
            color: #007BFF;
            text-align: right;
        }
        
        .quote-details {
            margin: 15px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .service-badge {
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
        
        .cost-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .cost-breakdown h4 {
            margin: 0 0 10px 0;
            color: #004080;
            font-size: 14px;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
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
        
        .rating {
            color: #ffc107;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .quotes-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-calculator"></i> Transport Quotes</h1>
        <p>Get competitive delivery quotes from multiple transport providers</p>
    </div>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="quote-form">
            <h2><i class="fas fa-search"></i> Get Transport Quotes</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="pickup_address">
                            <i class="fas fa-map-marker-alt"></i> Pickup Address *
                        </label>
                        <input type="text" name="pickup_address" id="pickup_address" class="form-control"
                               value="<?php echo htmlspecialchars($pickup_address); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_address">
                            <i class="fas fa-map-marker-alt"></i> Delivery Address *
                        </label>
                        <input type="text" name="delivery_address" id="delivery_address" class="form-control"
                               value="<?php echo htmlspecialchars($delivery_address); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight_kg">
                            <i class="fas fa-weight-hanging"></i> Package Weight (kg) *
                        </label>
                        <input type="number" name="weight_kg" id="weight_kg" class="form-control"
                               value="<?php echo $weight_kg; ?>" min="0.1" step="0.1" required>
                    </div>
                </div>
                
                <input type="hidden" name="customer_lat" value="<?php echo $_SESSION['user_latitude'] ?? -15.4067; ?>">
                <input type="hidden" name="customer_lng" value="<?php echo $_SESSION['user_longitude'] ?? 28.2871; ?>">
                
                <button type="submit" name="get_quotes" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Get Quotes
                </button>
            </form>
        </div>

        <?php if (!empty($quotes)): ?>
            <div class="quotes-grid">
                <?php foreach ($quotes as $index => $quote): ?>
                    <div class="quote-card <?php echo $index === 0 ? 'best-value' : ''; ?>">
                        <div class="quote-header">
                            <div>
                                <div class="provider-name">
                                    <i class="fas fa-<?php 
                                        echo $quote['vehicle_type'] === 'motorbike' ? 'motorcycle' : 
                                            ($quote['vehicle_type'] === 'truck' ? 'truck' : 'car'); 
                                    ?>"></i>
                                    <?php echo htmlspecialchars($quote['provider_name']); ?>
                                </div>
                                <div class="service-badge service-<?php echo $quote['service_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $quote['service_type'])); ?>
                                </div>
                            </div>
                            <div class="quote-price">K<?php echo number_format($quote['total_cost'], 2); ?></div>
                        </div>

                        <div class="quote-details">
                            <div class="detail-row">
                                <span>Delivery Time:</span>
                                <span><?php echo $quote['estimated_days']; ?> day<?php echo $quote['estimated_days'] != 1 ? 's' : ''; ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Distance:</span>
                                <span><?php echo $quote['total_distance']; ?> km</span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Vehicle:</span>
                                <span><?php echo ucfirst($quote['vehicle_type']); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Max Weight:</span>
                                <span><?php echo $quote['max_weight']; ?> kg</span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Contact:</span>
                                <span><?php echo htmlspecialchars($quote['contact']); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span>Rating:</span>
                                <span class="rating">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $quote['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                    (<?php echo $quote['rating']; ?>)
                                </span>
                            </div>
                        </div>

                        <div class="cost-breakdown">
                            <h4><i class="fas fa-receipt"></i> Cost Breakdown</h4>
                            <div class="cost-item">
                                <span>Base Cost:</span>
                                <span>K<?php echo number_format($quote['base_cost'], 2); ?></span>
                            </div>
                            <div class="cost-item">
                                <span>Distance (<?php echo $quote['total_distance']; ?> km):</span>
                                <span>K<?php echo number_format($quote['distance_cost'], 2); ?></span>
                            </div>
                            <?php if ($quote['service_multiplier'] != 1.0): ?>
                                <div class="cost-item">
                                    <span>Service Premium (<?php echo ($quote['service_multiplier'] - 1) * 100; ?>%):</span>
                                    <span>K<?php echo number_format(($quote['base_cost'] + $quote['distance_cost']) * ($quote['service_multiplier'] - 1), 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($quote['weight_multiplier'] != 1.0): ?>
                                <div class="cost-item">
                                    <span>Weight Surcharge (<?php echo ($quote['weight_multiplier'] - 1) * 100; ?>%):</span>
                                    <span>K<?php echo number_format($quote['total_cost'] - (($quote['base_cost'] + $quote['distance_cost']) * $quote['service_multiplier']), 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($quote['description']): ?>
                            <p style="color: #666; font-size: 14px; margin: 15px 0;">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($quote['description']); ?>
                            </p>
                        <?php endif; ?>

                        <form method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="provider_id" value="<?php echo $quote['provider_id']; ?>">
                            <input type="hidden" name="quote_cost" value="<?php echo $quote['total_cost']; ?>">
                            <input type="hidden" name="estimated_days" value="<?php echo $quote['estimated_days']; ?>">
                            <input type="hidden" name="total_distance" value="<?php echo $quote['total_distance']; ?>">
                            <button type="submit" name="accept_quote" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-check"></i> Accept This Quote
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 40px;">
            <a href="shop.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Shop
            </a>
            <a href="smart_transport_selector.php" class="btn btn-primary">
                <i class="fas fa-truck"></i> Transport Selector
            </a>
        </div>
    </div>

    <script>
        // Auto-fill address from order if available
        <?php if ($order): ?>
            <?php 
            $delivery_addr = '';
            if (!empty($order['shipping_address'])) {
                $city = $order['shipping_city'] ?? 'Lusaka';
                $province = isset($order['shipping_province']) ? ', ' . $order['shipping_province'] : '';
                $delivery_addr = $order['shipping_address'] . ', ' . $city . $province;
            } elseif (!empty($order['customer_address'])) {
                $city = $order['customer_city'] ?? 'Lusaka';
                $province = isset($order['customer_province']) ? ', ' . $order['customer_province'] : '';
                $delivery_addr = $order['customer_address'] . ', ' . $city . $province;
            }
            if ($delivery_addr): ?>
            document.getElementById('delivery_address').value = "<?php echo htmlspecialchars($delivery_addr); ?>";
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>