<?php
session_start();
include('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: ../dashboard.php');
    exit;
}

// Get order details
$order = null;
$order_items = [];
$tracking_updates = [];
$transport_providers = [];
$show_transport_selection = false;

try {
    // Get order
    $stmt = $pdo->prepare("SELECT o.*, t.name as transport_name, t.contact as transport_contact, t.estimated_days 
                          FROM orders o 
                          LEFT JOIN transport_providers t ON o.transport_id = t.id 
                          WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ../dashboard.php');
        exit;
    }
    
    // Check if transport selection is needed
    if (empty($order['transport_id']) && $order['status'] === 'processing') {
        $show_transport_selection = true;
        
        // Get available transport providers
        try {
            $transport_stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE status = 'active' ORDER BY rating DESC, name ASC");
            $transport_stmt->execute();
            $transport_providers = $transport_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Transport providers table might not exist
            $transport_providers = [];
        }
    }
    
    // Get order items
    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image as product_image 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tracking updates
    try {
        // First check which timestamp column exists
        $check_columns = $pdo->query("SHOW COLUMNS FROM order_tracking");
        $columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
        
        $timestamp_column = 'id'; // Default fallback
        if (in_array('timestamp', $columns)) {
            $timestamp_column = 'timestamp';
        } elseif (in_array('created_at', $columns)) {
            $timestamp_column = 'created_at';
        } elseif (in_array('updated_at', $columns)) {
            $timestamp_column = 'updated_at';
        }
        
        $stmt = $pdo->prepare("SELECT * FROM order_tracking 
                              WHERE order_id = ? 
                              ORDER BY {$timestamp_column} DESC");
        $stmt->execute([$order_id]);
        $tracking_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If order_tracking table doesn't exist or has issues, continue without tracking
        $tracking_updates = [];
    }
} catch (PDOException $e) {
    die("Error fetching order details: " . $e->getMessage());
}

// Handle transport selection
$transport_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_transport'])) {
    $selected_transport_id = intval($_POST['transport_id']);
    
    if ($selected_transport_id > 0) {
        try {
            // Get transport provider details
            $transport_stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE id = ? AND status = 'active'");
            $transport_stmt->execute([$selected_transport_id]);
            $selected_transport = $transport_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($selected_transport) {
                // Calculate transport cost (basic calculation)
                $base_cost = $selected_transport['base_cost'] ?? 15.00;
                $cost_per_km = $selected_transport['cost_per_km'] ?? 2.50;
                $estimated_distance = 25; // Default distance in km
                $transport_cost = $base_cost + ($cost_per_km * $estimated_distance);
                
                // Update order with transport information
                $update_stmt = $pdo->prepare("UPDATE orders SET transport_id = ?, transport_cost = ? WHERE id = ? AND user_id = ?");
                $update_stmt->execute([$selected_transport_id, $transport_cost, $order_id, $_SESSION['user_id']]);
                
                // Create transport quote record
                try {
                    $quote_stmt = $pdo->prepare("INSERT INTO transport_quotes (order_id, transport_provider_id, pickup_address, delivery_address, distance_km, estimated_cost, estimated_delivery_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'accepted', NOW())");
                    $quote_stmt->execute([
                        $order_id,
                        $selected_transport_id,
                        'SmartFix Warehouse',
                        $order['shipping_address'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_province'],
                        $estimated_distance,
                        $transport_cost,
                        $selected_transport['estimated_days'] ?? 2
                    ]);
                } catch (PDOException $e) {
                    // Transport quotes table might not exist, continue without it
                }
                
                // Add tracking update
                try {
                    // Check which timestamp column exists in order_tracking
                    $check_tracking_columns = $pdo->query("SHOW COLUMNS FROM order_tracking");
                    $tracking_columns = $check_tracking_columns->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('timestamp', $tracking_columns)) {
                        $tracking_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location, timestamp) VALUES (?, 'Transport Selected', ?, 'SmartFix Warehouse', NOW())");
                    } elseif (in_array('created_at', $tracking_columns)) {
                        $tracking_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location, created_at) VALUES (?, 'Transport Selected', ?, 'SmartFix Warehouse', NOW())");
                    } else {
                        $tracking_stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location) VALUES (?, 'Transport Selected', ?, 'SmartFix Warehouse')");
                    }
                    
                    $tracking_stmt->execute([
                        $order_id,
                        "Transport provider selected: {$selected_transport['name']}. Estimated delivery: {$selected_transport['estimated_days']} days."
                    ]);
                } catch (PDOException $e) {
                    // Continue without tracking if table doesn't exist
                }
                
                $transport_message = "Transport provider selected successfully! Your order will be delivered by {$selected_transport['name']}.";
                
                // Refresh order data
                $stmt = $pdo->prepare("SELECT o.*, t.name as transport_name, t.contact as transport_contact, t.estimated_days 
                                      FROM orders o 
                                      LEFT JOIN transport_providers t ON o.transport_id = t.id 
                                      WHERE o.id = ? AND o.user_id = ?");
                $stmt->execute([$order_id, $_SESSION['user_id']]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                $show_transport_selection = false;
                
            } else {
                $transport_message = "Selected transport provider is not available. Please choose another option.";
            }
        } catch (PDOException $e) {
            $transport_message = "Error selecting transport provider. Please try again.";
        }
    } else {
        $transport_message = "Please select a transport provider.";
    }
}

// Function to get status label and class
function getStatusInfo($status) {
    switch ($status) {
        case 'processing':
            return [
                'label' => 'Processing',
                'class' => 'status-processing',
                'icon' => 'fas fa-cog',
                'description' => 'Your order has been received and is being processed.'
            ];
        case 'shipped':
            return [
                'label' => 'Shipped',
                'class' => 'status-shipped',
                'icon' => 'fas fa-truck',
                'description' => 'Your order has been shipped and is on its way to you.'
            ];
        case 'in_transit':
            return [
                'label' => 'In Transit',
                'class' => 'status-transit',
                'icon' => 'fas fa-shipping-fast',
                'description' => 'Your order is currently in transit to your location.'
            ];
        case 'out_for_delivery':
            return [
                'label' => 'Out for Delivery',
                'class' => 'status-delivery',
                'icon' => 'fas fa-truck-loading',
                'description' => 'Your order is out for delivery and will arrive today.'
            ];
        case 'delivered':
            return [
                'label' => 'Delivered',
                'class' => 'status-delivered',
                'icon' => 'fas fa-check-circle',
                'description' => 'Your order has been delivered successfully.'
            ];
        case 'cancelled':
            return [
                'label' => 'Cancelled',
                'class' => 'status-cancelled',
                'icon' => 'fas fa-times-circle',
                'description' => 'This order has been cancelled.'
            ];
        default:
            return [
                'label' => 'Unknown',
                'class' => 'status-unknown',
                'icon' => 'fas fa-question-circle',
                'description' => 'Status information is not available.'
            ];
    }
}

// Calculate estimated delivery date
function getEstimatedDelivery($order) {
    if (!isset($order['created_at']) || !isset($order['estimated_days'])) {
        return 'Not available';
    }
    
    $created = new DateTime($order['created_at']);
    $estimated_days = intval($order['estimated_days']);
    $delivery_date = clone $created;
    $delivery_date->modify("+{$estimated_days} days");
    
    return $delivery_date->format('F j, Y');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SmartFix Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background: #004080;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        nav a:hover {
            color: #ffcc00;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0;
        }
        
        .page-title h1 {
            font-size: 32px;
            color: #004080;
            margin-bottom: 10px;
        }
        
        .page-title p {
            color: #666;
            font-size: 16px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .success-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #28a745;
        }
        
        .success-message h2 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 20px;
            color: #004080;
        }
        
        .order-details {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .order-info {
            flex: 1;
            min-width: 300px;
        }
        
        .shipping-info {
            flex: 1;
            min-width: 300px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .info-label {
            width: 150px;
            font-weight: bold;
            color: #004080;
        }
        
        .info-value {
            flex: 1;
        }
        
        .order-status {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .order-status i {
            margin-right: 8px;
        }
        
        .status-processing {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-shipped, .status-transit {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-delivery {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-unknown {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-description {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007BFF;
        }
        
        .order-items {
            margin-top: 30px;
        }
        
        .item-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .item-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #004080;
            border-bottom: 1px solid #eee;
        }
        
        .item-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-name {
            font-weight: 500;
        }
        
        .order-summary {
            margin-top: 20px;
            text-align: right;
        }
        
        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        
        .summary-label {
            width: 150px;
            text-align: right;
            margin-right: 20px;
        }
        
        .summary-value {
            width: 100px;
            text-align: right;
            font-weight: 500;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .tracking-timeline {
            margin-top: 40px;
        }
        
        .timeline {
            position: relative;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: #e9ecef;
            top: 0;
            bottom: 0;
            left: 20px;
            margin-left: -2px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 18px;
            background-color: white;
            border: 4px solid #007BFF;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-content {
            padding: 20px;
            background-color: white;
            position: relative;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .timeline-date {
            color: #666;
            font-size: 14px;
        }
        
        .timeline-title {
            margin: 5px 0;
            color: #004080;
        }
        
        .timeline-text {
            margin: 0;
        }
        
        .timeline-location {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .delivery-progress {
            margin-top: 30px;
        }
        
        .progress-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .progress-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e9ecef;
            transform: translateY(-50%);
            z-index: 0;
        }
        
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            background: #007BFF;
            transform: translateY(-50%);
            z-index: 1;
            transition: width 0.5s ease;
        }
        
        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        
        .progress-step.active {
            border-color: #007BFF;
            background: #007BFF;
            color: white;
        }
        
        .progress-step.completed {
            border-color: #28a745;
            background: #28a745;
            color: white;
        }
        
        .progress-label {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            white-space: nowrap;
            color: #666;
        }
        
        .transport-info {
            margin-top: 30px;
            background: #f0f8ff;
            padding: 20px;
            border-radius: 10px;
        }
        
        .transport-info h3 {
            color: #004080;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .transport-logo {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .transport-logo i {
            font-size: 30px;
            color: #007BFF;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #007BFF;
            color: #007BFF;
        }
        
        .btn-outline:hover {
            background-color: #f0f7ff;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        footer {
            background: #004080;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 40px;
        }
        
        .footer-column {
            flex: 1;
            min-width: 200px;
            text-align: left;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3:after {
            content: '';
            position: absolute;
            width: 30px;
            height: 2px;
            background: #007BFF;
            bottom: 0;
            left: 0;
        }
        
        .footer-column p {
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .footer-column a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .footer-column a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 10px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 0;
        }
        
        .social-links a:hover {
            background: #007BFF;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .order-details {
                flex-direction: column;
            }
            
            .item-table {
                display: block;
                overflow-x: auto;
            }
            
            .progress-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 40px;
            }
            
            .progress-container::before {
                width: 4px;
                height: 100%;
                left: 15px;
                top: 0;
                transform: none;
            }
            
            .progress-bar {
                width: 4px !important;
                height: var(--progress-height);
                left: 15px;
                top: 0;
                transform: none;
            }
            
            .progress-step {
                margin-left: 0;
            }
            
            .progress-label {
                top: 0;
                left: 40px;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">SmartFixZed</div>
        <nav>
            <a href="../index.php"><i class="fas fa-home"></i> Home</a>
            <a href="../services.php"><i class="fas fa-tools"></i> Services</a>
            <a href="../shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
            <a href="../about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="../contact.php"><i class="fas fa-phone"></i> Contact</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../dashboard.php"><i class="fas fa-user"></i> My Account</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="../register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <div class="container">
        <div class="page-title">
            <h1>Order Confirmation</h1>
            <p>Thank you for your purchase!</p>
        </div>
        
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <h2>Your Order Has Been Placed Successfully</h2>
            <p>We've received your order and are processing it now. You'll receive updates as your order progresses.</p>
            <p>Your tracking number is: <strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong></p>
        </div>
        
        <?php if (!empty($transport_message)): ?>
        <div class="success-message" style="background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb;">
            <i class="fas fa-truck"></i>
            <h2>Transport Update</h2>
            <p><?php echo htmlspecialchars($transport_message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($show_transport_selection && !empty($transport_providers)): ?>
        <div class="card" style="border: 2px solid #ffc107; background: #fff8e1;">
            <div class="card-header">
                <h2 style="color: #856404;"><i class="fas fa-truck"></i> Choose Your Transport Provider</h2>
                <p style="margin: 0; color: #856404;">Please select a transport provider to complete your order delivery setup.</p>
            </div>
            
            <form method="POST" action="">
                <div style="display: grid; gap: 15px; margin-bottom: 20px;">
                    <?php foreach ($transport_providers as $provider): ?>
                    <div class="transport-option" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;" onclick="selectTransport(<?php echo $provider['id']; ?>)">
                        <input type="radio" name="transport_id" value="<?php echo $provider['id']; ?>" id="transport_<?php echo $provider['id']; ?>" style="margin-right: 10px;">
                        <label for="transport_<?php echo $provider['id']; ?>" style="cursor: pointer; width: 100%; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; color: #004080;"><?php echo htmlspecialchars($provider['name']); ?></h4>
                                <p style="margin: 0 0 5px 0; color: #666; font-size: 14px;"><?php echo htmlspecialchars($provider['description'] ?? 'Reliable delivery service'); ?></p>
                                <div style="display: flex; gap: 15px; font-size: 13px; color: #666;">
                                    <span><i class="fas fa-truck"></i> <?php echo ucfirst($provider['vehicle_type'] ?? 'Vehicle'); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo ($provider['estimated_days'] ?? 2); ?> days</span>
                                    <span><i class="fas fa-star"></i> <?php echo number_format($provider['rating'] ?? 4.0, 1); ?>/5</span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 18px; font-weight: bold; color: #28a745;">
                                    K<?php echo number_format(($provider['base_cost'] ?? 15) + (($provider['cost_per_km'] ?? 2.5) * 25), 2); ?>
                                </div>
                                <div style="font-size: 12px; color: #666;">Est. cost</div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" name="select_transport" style="background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background 0.3s;">
                        <i class="fas fa-check"></i> Confirm Transport Selection
                    </button>
                </div>
            </form>
            
            <script>
            function selectTransport(id) {
                // Remove previous selections
                document.querySelectorAll('.transport-option').forEach(option => {
                    option.style.borderColor = '#e9ecef';
                    option.style.backgroundColor = 'white';
                });
                
                // Select current option
                const selectedOption = document.querySelector(`#transport_${id}`).closest('.transport-option');
                selectedOption.style.borderColor = '#007BFF';
                selectedOption.style.backgroundColor = '#f8f9ff';
                
                // Check the radio button
                document.querySelector(`#transport_${id}`).checked = true;
            }
            </script>
        </div>
        <?php elseif ($show_transport_selection && empty($transport_providers)): ?>
        <div class="card" style="border: 2px solid #dc3545; background: #fff5f5;">
            <div class="card-header">
                <h2 style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Transport Setup Required</h2>
                <p style="margin: 0; color: #dc3545;">No transport providers are currently available. Please contact support or try again later.</p>
            </div>
            <div style="text-align: center; padding: 20px;">
                <a href="../smart_transport_selector.php?order_id=<?php echo $order_id; ?>" class="btn" style="background: #007BFF; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;">
                    <i class="fas fa-truck"></i> Advanced Transport Selection
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Order Status</h2>
                <?php $status_info = getStatusInfo($order['status']); ?>
                <div class="order-status <?php echo $status_info['class']; ?>">
                    <i class="<?php echo $status_info['icon']; ?>"></i> <?php echo $status_info['label']; ?>
                </div>
            </div>
            
            <div class="status-description">
                <p><i class="fas fa-info-circle"></i> <?php echo $status_info['description']; ?></p>
            </div>
            
            <div class="delivery-progress">
                <h3>Delivery Progress</h3>
                
                <?php 
                // Calculate progress percentage based on status
                $progress_percentage = 0;
                switch($order['status']) {
                    case 'processing': $progress_percentage = 20; break;
                    case 'shipped': $progress_percentage = 40; break;
                    case 'in_transit': $progress_percentage = 60; break;
                    case 'out_for_delivery': $progress_percentage = 80; break;
                    case 'delivered': $progress_percentage = 100; break;
                }
                ?>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                    
                    <div class="progress-step <?php echo $progress_percentage >= 20 ? 'completed' : ''; ?>">
                        <i class="fas fa-check"></i>
                        <div class="progress-label">Order Placed</div>
                    </div>
                    
                    <div class="progress-step <?php echo $progress_percentage >= 40 ? 'completed' : ($progress_percentage >= 20 ? 'active' : ''); ?>">
                        <i class="fas <?php echo $progress_percentage >= 40 ? 'fa-check' : 'fa-box'; ?>"></i>
                        <div class="progress-label">Shipped</div>
                    </div>
                    
                    <div class="progress-step <?php echo $progress_percentage >= 60 ? 'completed' : ($progress_percentage >= 40 ? 'active' : ''); ?>">
                        <i class="fas <?php echo $progress_percentage >= 60 ? 'fa-check' : 'fa-truck'; ?>"></i>
                        <div class="progress-label">In Transit</div>
                    </div>
                    
                    <div class="progress-step <?php echo $progress_percentage >= 80 ? 'completed' : ($progress_percentage >= 60 ? 'active' : ''); ?>">
                        <i class="fas <?php echo $progress_percentage >= 80 ? 'fa-check' : 'fa-truck-loading'; ?>"></i>
                        <div class="progress-label">Out for Delivery</div>
                    </div>
                    
                    <div class="progress-step <?php echo $progress_percentage >= 100 ? 'completed' : ($progress_percentage >= 80 ? 'active' : ''); ?>">
                        <i class="fas <?php echo $progress_percentage >= 100 ? 'fa-check' : 'fa-home'; ?>"></i>
                        <div class="progress-label">Delivered</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="order-details">
            <div class="order-info card">
                <div class="card-header">
                    <h2>Order Information</h2>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Order Number:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['tracking_number']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Order Date:</div>
                    <div class="info-value"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Payment Method:</div>
                    <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></div>
                </div>
                
                <?php if (isset($order['transport_name'])): ?>
                <div class="info-row">
                    <div class="info-label">Transport:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['transport_name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Est. Delivery:</div>
                    <div class="info-value"><?php echo getEstimatedDelivery($order); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="info-row">
                    <div class="info-label">Notes:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['notes']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="shipping-info card">
                <div class="card-header">
                    <h2>Shipping Information</h2>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_phone']); ?></div>
                </div>
                
                <?php if (!empty($order['shipping_email'])): ?>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_email']); ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">City:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_city']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Province:</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['shipping_province']); ?></div>
                </div>
            </div>
        </div>
        
        <?php if (isset($order['transport_name'])): ?>
        <div class="transport-info">
            <h3>Transport Provider</h3>
            <div class="transport-logo">
                <i class="fas fa-truck"></i>
            </div>
            <div class="info-row">
                <div class="info-label">Provider:</div>
                <div class="info-value"><?php echo htmlspecialchars($order['transport_name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact:</div>
                <div class="info-value"><?php echo htmlspecialchars($order['transport_contact']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Estimated Delivery Time:</div>
                <div class="info-value"><?php echo $order['estimated_days']; ?> days</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>Order Items</h2>
            </div>
            
            <table class="item-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <div class="item-image">
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    </div>
                                    <div class="item-name" style="margin-left: 15px;">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>ZMW <?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>ZMW <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="order-summary">
                <div class="summary-row">
                    <div class="summary-label">Subtotal:</div>
                    <div class="summary-value">ZMW <?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Shipping:</div>
                    <div class="summary-value">Included</div>
                </div>
                <div class="summary-row total">
                    <div class="summary-label">Total:</div>
                    <div class="summary-value">ZMW <?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
            </div>
        </div>
        
        <?php if (count($tracking_updates) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h2>Tracking Updates</h2>
            </div>
            
            <div class="tracking-timeline">
                <div class="timeline">
                    <?php foreach ($tracking_updates as $update): ?>
                        <?php 
                        // Handle different timestamp column names
                        $timestamp_value = null;
                        if (isset($update['timestamp'])) {
                            $timestamp_value = $update['timestamp'];
                        } elseif (isset($update['created_at'])) {
                            $timestamp_value = $update['created_at'];
                        } elseif (isset($update['updated_at'])) {
                            $timestamp_value = $update['updated_at'];
                        }
                        
                        if ($timestamp_value) {
                            $update_date = new DateTime($timestamp_value);
                            $formatted_date = $update_date->format('F j, Y, g:i a');
                        } else {
                            $formatted_date = 'Date not available';
                        }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-date"><?php echo $formatted_date; ?></div>
                                <h4 class="timeline-title"><?php echo htmlspecialchars($update['status']); ?></h4>
                                <p class="timeline-text"><?php echo htmlspecialchars($update['description']); ?></p>
                                <?php if (!empty($update['location'])): ?>
                                <p class="timeline-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($update['location']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="track_order.php?tracking=<?php echo urlencode($order['tracking_number']); ?>" class="btn">
                <i class="fas fa-search"></i> Track Order
            </a>
            <a href="../shop.php" class="btn btn-outline">
                <i class="fas fa-shopping-cart"></i> Continue Shopping
            </a>
        </div>
    </div>
    
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>SmartFix</h3>
                <p>Your trusted partner for all repair services in Zambia. Quality repairs, genuine parts, and exceptional service.</p>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <a href="../index.php">Home</a>
                <a href="../services.php">Services</a>
                <a href="../shop.php">Shop</a>
                <a href="../about.php">About Us</a>
                <a href="../contact.php">Contact Us</a>
            </div>
            
            <div class="footer-column">
                <h3>Contact Info</h3>
                <p><i class="fas fa-map-marker-alt"></i> Great North Road, Chinsali at Kapasa Makasa University, Zambia</p>
                <p><i class="fas fa-phone"></i> +260 777041357</p>
                <p><i class="fas fa-phone"></i> +260 776992688</p>
                <p><i class="fas fa-envelope"></i> info@smartfix.co.zm</p>
            </div>
            
            <div class="footer-column">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SmartFix. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>