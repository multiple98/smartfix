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
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   (SELECT SUM(oi.quantity * p.price) 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = o.id) as calculated_total
            FROM orders o WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $order_value = $order['total_amount'];
            $delivery_address = $order['shipping_address'] . ', ' . ($order['shipping_city'] ?? 'Lusaka');
            
            // Estimate weight based on order items
            $weight_stmt = $pdo->prepare("SELECT SUM(oi.quantity) as total_items FROM order_items oi WHERE oi.order_id = ?");
            $weight_stmt->execute([$order_id]);
            $weight_result = $weight_stmt->fetch();
            $total_weight = ($weight_result['total_items'] ?? 1) * 2; // Estimate 2kg per item
        }
    } catch (PDOException $e) {
        $delivery_address = 'Customer Address, Lusaka';
        $order_value = 100;
    }
} elseif ($product_id > 0) {
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
$success_message = '';
$error_message = '';

try {
    // Get active providers that can handle the order
    $stmt = $pdo->prepare("
        SELECT * FROM transport_providers 
        WHERE status = 'active' 
        AND max_weight_kg >= ? 
        AND min_order_value <= ?
        ORDER BY rating DESC, cost_per_km ASC
    ");
    $stmt->execute([$total_weight, $order_value]);
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($providers as $provider) {
        // Check if provider serves the delivery region
        $provider_regions = explode(',', $provider['regions']);
        $serves_region = false;
        
        // For now, assume Lusaka region (in real app, would determine from delivery address)
        foreach ($provider_regions as $region) {
            if (stripos(trim($region), 'Lusaka') !== false) {
                $serves_region = true;
                break;
            }
        }
        
        if ($serves_region) {
            // Calculate distance and cost
            $distance = calculateDistance($user_lat, $user_lng, $provider['latitude'], $provider['longitude']);
            $delivery_cost = $provider['base_cost'] + ($distance * $provider['cost_per_km']);
            
            // Apply service type multiplier
            $service_multiplier = 1.0;
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
            
            $delivery_cost *= $service_multiplier;
            
            // Parse specialties and social media
            $specialties = json_decode($provider['specialties'], true) ?: [];
            $social_media = json_decode($provider['social_media'], true) ?: [];
            
            $transport_options[] = [
                'id' => $provider['id'],
                'name' => $provider['name'],
                'contact' => $provider['contact'],
                'email' => $provider['email'],
                'description' => $provider['description'],
                'service_type' => $provider['service_type'],
                'vehicle_type' => $provider['vehicle_type'],
                'estimated_days' => $provider['estimated_days'],
                'distance_km' => round($distance, 2),
                'cost' => round($delivery_cost, 2),
                'rating' => $provider['rating'],
                'operating_hours' => $provider['operating_hours'],
                'specialties' => $specialties,
                'social_media' => $social_media,
                'insurance_valid' => $provider['insurance_valid'],
                'license_number' => $provider['license_number'],
                'established_year' => $provider['established_year'],
                'website' => $provider['website'],
                'coverage_area_km' => $provider['coverage_area_km'],
                'max_weight_kg' => $provider['max_weight_kg']
            ];
        }
    }
    
    // Sort by cost
    usort($transport_options, function($a, $b) {
        return $a['cost'] <=> $b['cost'];
    });
    
} catch (PDOException $e) {
    $error_message = "Error loading transport providers: " . $e->getMessage();
}

// Handle transport selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_transport'])) {
    $selected_transport_id = intval($_POST['transport_id']);
    
    try {
        // Find selected transport option
        $selected_option = null;
        foreach ($transport_options as $option) {
            if ($option['id'] == $selected_transport_id) {
                $selected_option = $option;
                break;
            }
        }
        
        if ($selected_option && $order_id > 0) {
            // Check if transport_quotes table exists and get its structure
            $table_exists = false;
            $has_user_id = false;
            
            try {
                $check_table = $pdo->query("SHOW TABLES LIKE 'transport_quotes'");
                $table_exists = $check_table->rowCount() > 0;
                
                if ($table_exists) {
                    $columns = $pdo->query("SHOW COLUMNS FROM transport_quotes")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($columns as $column) {
                        if ($column['Field'] === 'user_id') {
                            $has_user_id = true;
                            break;
                        }
                    }
                }
            } catch (PDOException $e) {
                // Table doesn't exist, we'll create it
            }
            
            // Create transport quote with or without user_id based on table structure
            if ($has_user_id) {
                // If table has user_id, include it in the insert (use session user_id or default to 1)
                $user_id = $_SESSION['user_id'] ?? 1;
                $quote_stmt = $pdo->prepare("
                    INSERT INTO transport_quotes 
                    (order_id, transport_provider_id, pickup_address, delivery_address, 
                     distance_km, estimated_cost, estimated_delivery_time, quote_valid_until, status, user_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 'accepted', ?)
                ");
                
                $quote_stmt->execute([
                    $order_id,
                    $selected_transport_id,
                    'SmartFix Warehouse, Lusaka',
                    $delivery_address,
                    $selected_option['distance_km'],
                    $selected_option['cost'],
                    $selected_option['estimated_days'],
                    $user_id
                ]);
            } else {
                // Standard insert without user_id
                $quote_stmt = $pdo->prepare("
                    INSERT INTO transport_quotes 
                    (order_id, transport_provider_id, pickup_address, delivery_address, 
                     distance_km, estimated_cost, estimated_delivery_time, quote_valid_until, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 'accepted')
                ");
                
                $quote_stmt->execute([
                    $order_id,
                    $selected_transport_id,
                    'SmartFix Warehouse, Lusaka',
                    $delivery_address,
                    $selected_option['distance_km'],
                    $selected_option['cost'],
                    $selected_option['estimated_days']
                ]);
            }
            
            $quote_id = $pdo->lastInsertId();
            
            // Update order with transport details
            $update_order = "UPDATE orders SET transport_id = ?, transport_cost = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_order);
            $update_stmt->execute([$selected_transport_id, $selected_option['cost'], $order_id]);
            
            // Add initial tracking entry
            $tracking_stmt = $pdo->prepare("
                INSERT INTO transport_tracking (quote_id, status, description, location) 
                VALUES (?, 'Transport Selected', ?, 'Processing Center')
            ");
            $tracking_stmt->execute([
                $quote_id, 
                "Selected {$selected_option['name']} for delivery (K{$selected_option['cost']})"
            ]);
            
            // Add order tracking entry
            try {
                $order_tracking_stmt = $pdo->prepare("
                    INSERT INTO order_tracking (order_id, status, description, location) 
                    VALUES (?, 'Transport Assigned', ?, 'Processing Center')
                ");
                $order_tracking_stmt->execute([
                    $order_id, 
                    "Transport assigned: {$selected_option['name']} - Estimated delivery: {$selected_option['estimated_days']} days"
                ]);
            } catch (PDOException $e) {
                // Order tracking table might not exist
            }
            
            $success_message = "Transport provider selected successfully! Your order will be delivered by {$selected_option['name']} in approximately {$selected_option['estimated_days']} days. Total delivery cost: K{$selected_option['cost']}";
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
    <title>Enhanced Smart Transport Selector - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --body-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--body-bg);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: #ffcc00;
            transform: translateX(-5px);
        }
        
        .order-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .order-info h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: var(--light-color);
            border-radius: 10px;
        }
        
        .detail-item i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .provider-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
            position: relative;
        }
        
        .provider-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .provider-header {
            padding: 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            position: relative;
        }
        
        .service-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .provider-name {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .provider-description {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .provider-body {
            padding: 25px;
        }
        
        .provider-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .stat-item i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .provider-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stars {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .rating-text {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .provider-features {
            margin-bottom: 20px;
        }
        
        .features-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .feature-tag {
            background: var(--light-color);
            color: var(--dark-color);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid #e9ecef;
        }
        
        .cost-breakdown {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid var(--info-color);
        }
        
        .cost-title {
            font-weight: 600;
            color: var(--info-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cost-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
        }
        
        .total-cost {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            text-align: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid var(--info-color);
        }
        
        .provider-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--light-color);
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .no-providers {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .no-providers i {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .no-providers h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .insurance-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .providers-grid {
                grid-template-columns: 1fr;
            }
            
            .provider-stats {
                grid-template-columns: 1fr;
            }
            
            .cost-details {
                grid-template-columns: 1fr;
            }
            
            .provider-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="order_confirmation.php?order_id=<?= $order_id ?>" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Order</span>
        </a>
        
        <div class="page-header">
            <h1>
                <i class="fas fa-truck-moving"></i>
                Smart Transport Selector
            </h1>
            <p>Choose the best delivery option for your order</p>
        </div>
        
        <?php if ($order): ?>
        <div class="order-info">
            <h2>
                <i class="fas fa-box"></i>
                Order Information
            </h2>
            <div class="order-details">
                <div class="detail-item">
                    <i class="fas fa-hashtag"></i>
                    <span><strong>Order ID:</strong> #<?= $order['id'] ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-weight-hanging"></i>
                    <span><strong>Estimated Weight:</strong> <?= $total_weight ?>kg</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span><strong>Order Value:</strong> K<?= number_format($order_value, 2) ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><strong>Delivery To:</strong> <?= htmlspecialchars($delivery_address) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($transport_options)): ?>
            <div class="providers-grid">
                <?php foreach ($transport_options as $option): ?>
                    <div class="provider-card">
                        <?php if ($option['insurance_valid']): ?>
                            <div class="insurance-badge">
                                <i class="fas fa-shield-alt"></i> Insured
                            </div>
                        <?php endif; ?>
                        
                        <div class="provider-header">
                            <div class="service-badge"><?= ucfirst($option['service_type']) ?></div>
                            <div class="provider-name"><?= htmlspecialchars($option['name']) ?></div>
                            <div class="provider-description"><?= htmlspecialchars($option['description']) ?></div>
                        </div>
                        
                        <div class="provider-body">
                            <div class="provider-rating">
                                <div class="stars">
                                    <?php
                                    $rating = $option['rating'];
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star"></i>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="rating-text"><?= number_format($rating, 1) ?>/5.0</span>
                                <?php if ($option['established_year']): ?>
                                    <span style="color: var(--secondary-color); font-size: 0.9rem;">
                                        (Est. <?= $option['established_year'] ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="provider-stats">
                                <div class="stat-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($option['contact']) ?></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-truck"></i>
                                    <span><?= htmlspecialchars($option['vehicle_type']) ?></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= $option['estimated_days'] ?> day<?= $option['estimated_days'] > 1 ? 's' : '' ?></span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-route"></i>
                                    <span><?= $option['distance_km'] ?>km away</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-weight-hanging"></i>
                                    <span>Max <?= $option['max_weight_kg'] ?>kg</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-business-time"></i>
                                    <span><?= htmlspecialchars($option['operating_hours']) ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($option['specialties'])): ?>
                            <div class="provider-features">
                                <div class="features-title">
                                    <i class="fas fa-star"></i> Specialties
                                </div>
                                <div class="features-list">
                                    <?php foreach ($option['specialties'] as $specialty): ?>
                                        <span class="feature-tag"><?= htmlspecialchars($specialty) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="cost-breakdown">
                                <div class="cost-title">
                                    <i class="fas fa-calculator"></i>
                                    Cost Breakdown
                                </div>
                                <div class="cost-details">
                                    <div class="cost-item">
                                        <span>Base Cost:</span>
                                        <span>K<?= number_format($option['cost'] / (1 + ($option['distance_km'] * $option['cost'] / 100)), 2) ?></span>
                                    </div>
                                    <div class="cost-item">
                                        <span>Distance (<?= $option['distance_km'] ?>km):</span>
                                        <span>K<?= number_format($option['distance_km'] * ($option['cost'] / 100), 2) ?></span>
                                    </div>
                                    <div class="cost-item">
                                        <span>Service Premium:</span>
                                        <span><?= ucfirst($option['service_type']) ?></span>
                                    </div>
                                </div>
                                <div class="total-cost">
                                    Total: K<?= number_format($option['cost'], 2) ?>
                                </div>
                            </div>
                            
                            <div class="provider-actions">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="transport_id" value="<?= $option['id'] ?>">
                                    <button type="submit" name="select_transport" class="btn btn-primary">
                                        <i class="fas fa-check"></i>
                                        Select Provider
                                    </button>
                                </form>
                                
                                <?php if ($option['website']): ?>
                                    <a href="<?= htmlspecialchars($option['website']) ?>" target="_blank" class="btn btn-outline" style="flex: 0 0 auto;">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($option['social_media'])): ?>
                            <div class="social-links">
                                <?php if (isset($option['social_media']['facebook'])): ?>
                                    <a href="https://facebook.com/<?= htmlspecialchars($option['social_media']['facebook']) ?>" target="_blank" class="social-link">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (isset($option['social_media']['twitter'])): ?>
                                    <a href="https://twitter.com/<?= htmlspecialchars($option['social_media']['twitter']) ?>" target="_blank" class="social-link">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (isset($option['social_media']['instagram'])): ?>
                                    <a href="https://instagram.com/<?= htmlspecialchars($option['social_media']['instagram']) ?>" target="_blank" class="social-link">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-providers">
                <i class="fas fa-truck-loading"></i>
                <h3>No Transport Providers Available</h3>
                <p>Sorry, no transport providers are currently available for your order requirements.</p>
                <p>Please contact customer service for assistance.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Add loading state to selection buttons
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Selecting...';
                btn.disabled = true;
            });
        });
        
        // Auto-refresh location if available
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                // Store location in session for better distance calculations
                fetch('update_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    })
                });
            });
        }
    </script>
</body>
</html>