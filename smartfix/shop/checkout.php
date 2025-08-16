<?php
session_start();
include('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=shop/checkout.php');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Get cart items
$cart_items = [];
$total_amount = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $cart_items[] = $product;
            $total_amount += $product['subtotal'];
        }
    } catch (PDOException $e) {
        // Skip this product if there's an error
        continue;
    }
}

// Get available transport providers
$transport_providers = [];
try {
    $stmt = $pdo->query("SELECT * FROM transport_providers ORDER BY name");
    $transport_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Process checkout
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $shipping_name = trim($_POST['shipping_name']);
    $shipping_phone = trim($_POST['shipping_phone']);
    $shipping_email = trim($_POST['shipping_email']);
    $shipping_address = trim($_POST['shipping_address']);
    $shipping_city = trim($_POST['shipping_city']);
    $shipping_province = trim($_POST['shipping_province']);
    $payment_method = trim($_POST['payment_method']);
    $transport_id = isset($_POST['transport_id']) ? intval($_POST['transport_id']) : 0;
    $notes = trim($_POST['notes']);
    
    // Simple validation
    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($shipping_city) || empty($shipping_province)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($shipping_email) && !filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Create order
            $order_query = "INSERT INTO orders (user_id, shipping_name, shipping_phone, shipping_email, shipping_address, 
                           shipping_city, shipping_province, payment_method, transport_id, notes, total_amount, status, created_at) 
                           VALUES (:user_id, :shipping_name, :shipping_phone, :shipping_email, :shipping_address, 
                           :shipping_city, :shipping_province, :payment_method, :transport_id, :notes, :total_amount, 'processing', NOW())";
            $order_stmt = $pdo->prepare($order_query);
            $order_stmt->execute([
                'user_id' => $user_id,
                'shipping_name' => $shipping_name,
                'shipping_phone' => $shipping_phone,
                'shipping_email' => $shipping_email,
                'shipping_address' => $shipping_address,
                'shipping_city' => $shipping_city,
                'shipping_province' => $shipping_province,
                'payment_method' => $payment_method,
                'transport_id' => $transport_id > 0 ? $transport_id : null,
                'notes' => $notes,
                'total_amount' => $total_amount
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Generate tracking number
            $tracking_number = 'SF-ORD-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            
            // Update order with tracking number
            $update_query = "UPDATE orders SET tracking_number = :tracking_number WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'tracking_number' => $tracking_number,
                'id' => $order_id
            ]);
            
            // Add order items
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                          VALUES (:order_id, :product_id, :quantity, :price)";
            $item_stmt = $pdo->prepare($item_query);
            
            foreach ($cart_items as $item) {
                $item_stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
                
                // Update product stock (only if stock column exists)
                try {
                    $stock_query = "UPDATE products SET stock = GREATEST(0, stock - :quantity) WHERE id = :id AND stock IS NOT NULL";
                    $stock_stmt = $pdo->prepare($stock_query);
                    $stock_stmt->execute([
                        'quantity' => $item['quantity'],
                        'id' => $item['id']
                    ]);
                } catch (PDOException $e) {
                    // Stock column might not exist, continue without stock update
                }
            }
            
            // Add initial tracking entry
            $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location, timestamp) 
                              VALUES (:order_id, 'Order Placed', 'Your order has been received and is being processed.', :location, NOW())";
            $tracking_stmt = $pdo->prepare($tracking_query);
            $tracking_stmt->execute([
                'order_id' => $order_id,
                'location' => 'SmartFix Warehouse, ' . $shipping_province
            ]);
            
            // Create notification for admin
            $notification_query = "INSERT INTO notifications (type, message, is_read, created_at) 
                                  VALUES ('new_order', :message, 0, NOW())";
            $notification_stmt = $pdo->prepare($notification_query);
            $notification_stmt->execute([
                'message' => "New order ({$tracking_number}) from {$shipping_name} - {$shipping_phone}"
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Redirect to smart transport selector if no transport was selected
            if ($transport_id == 0) {
                header("Location: ../smart_transport_selector.php?order_id={$order_id}");
            } else {
                header("Location: order_confirmation.php?id={$order_id}");
            }
            exit;
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            
            // Check if tables need to be created
            if ($e->getCode() == '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
                try {
                    // Create orders table with all necessary columns
                    $create_orders = "CREATE TABLE orders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        tracking_number VARCHAR(20) UNIQUE,
                        shipping_name VARCHAR(100) NOT NULL,
                        shipping_phone VARCHAR(20) NOT NULL,
                        shipping_email VARCHAR(100),
                        shipping_address TEXT NOT NULL,
                        shipping_city VARCHAR(50) NOT NULL,
                        shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
                        payment_method VARCHAR(50) NOT NULL,
                        transport_id INT,
                        transport_cost DECIMAL(10,2) DEFAULT 0.00,
                        notes TEXT,
                        total_amount DECIMAL(10,2) NOT NULL,
                        status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_orders);
                    
                    // Create order_items table
                    $create_items = "CREATE TABLE order_items (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        product_id INT NOT NULL,
                        quantity INT NOT NULL,
                        price DECIMAL(10,2) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                    )";
                    $pdo->exec($create_items);
                    
                    // Create order_tracking table
                    $create_tracking = "CREATE TABLE order_tracking (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        status VARCHAR(50) NOT NULL,
                        description TEXT NOT NULL,
                        location VARCHAR(100),
                        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                    )";
                    $pdo->exec($create_tracking);
                    
                    // Create transport_providers table if it doesn't exist
                    $create_transport = "CREATE TABLE IF NOT EXISTS transport_providers (
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
                    
                    // Create notifications table if it doesn't exist
                    $create_notifications = "CREATE TABLE IF NOT EXISTS notifications (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        type VARCHAR(50) NOT NULL,
                        message TEXT NOT NULL,
                        is_read TINYINT(1) DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_notifications);
                    
                    $error_message = "Database tables have been created. Please try submitting your order again.";
                } catch (PDOException $e2) {
                    $error_message = "Error setting up order system: " . $e2->getMessage() . " Please run the database fix script.";
                }
            } else {
                $error_message = "Error processing your order: " . $e->getMessage();
                
                // Log the error for debugging
                error_log("Checkout error: " . $e->getMessage());
                
                // Provide helpful information based on the error
                if (strpos($e->getMessage(), "Unknown column") !== false) {
                    $error_message .= " - Some database columns may be missing. Please run the database fix script.";
                }
            }
        }
    }
}

// Define Zambian provinces
$zambian_provinces = [
    'Lusaka',
    'Copperbelt',
    'Central',
    'Eastern',
    'Luapula',
    'Muchinga',
    'Northern',
    'North-Western',
    'Southern',
    'Western'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SmartFix Shop</title>
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
        
        .checkout-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .checkout-form {
            flex: 1;
            min-width: 300px;
        }
        
        .order-summary {
            width: 350px;
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
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 20px;
            color: #004080;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #007BFF;
            outline: none;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .required-field::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
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
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #666;
            font-size: 14px;
        }
        
        .cart-item-quantity {
            font-size: 14px;
            color: #666;
        }
        
        .cart-summary {
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .payment-methods {
            margin-top: 20px;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #007BFF;
            background-color: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: #007BFF;
            background-color: #f0f7ff;
        }
        
        .payment-method input {
            margin-right: 10px;
        }
        
        .payment-method-icon {
            margin-right: 15px;
            font-size: 24px;
            color: #007BFF;
            width: 30px;
            text-align: center;
        }
        
        .payment-method-details {
            flex: 1;
        }
        
        .payment-method-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .payment-method-description {
            font-size: 14px;
            color: #666;
        }
        
        .transport-providers {
            margin-top: 20px;
        }
        
        .transport-provider {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .transport-provider:hover {
            border-color: #007BFF;
            background-color: #f8f9fa;
        }
        
        .transport-provider.selected {
            border-color: #007BFF;
            background-color: #f0f7ff;
        }
        
        .transport-provider input {
            margin-right: 10px;
        }
        
        .transport-provider-icon {
            margin-right: 15px;
            font-size: 24px;
            color: #007BFF;
            width: 30px;
            text-align: center;
        }
        
        .transport-provider-details {
            flex: 1;
        }
        
        .transport-provider-title {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .transport-provider-description {
            font-size: 14px;
            color: #666;
        }
        
        .transport-provider-info {
            display: flex;
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .transport-provider-info div {
            margin-right: 15px;
        }
        
        .transport-provider-info i {
            margin-right: 5px;
            color: #007BFF;
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
            .checkout-container {
                flex-direction: column;
            }
            
            .order-summary {
                width: 100%;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
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
            <h1>Checkout</h1>
            <p>Complete your purchase by providing shipping and payment information</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <form method="POST" action="checkout.php">
                    <div class="card">
                        <div class="card-header">
                            <h2>Shipping Information</h2>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_name" class="required-field">Full Name</label>
                            <input type="text" id="shipping_name" name="shipping_name" class="form-control" value="<?php echo isset($user) ? htmlspecialchars($user['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="shipping_phone" class="required-field">Phone Number</label>
                                    <input type="text" id="shipping_phone" name="shipping_phone" class="form-control" value="<?php echo isset($user) ? htmlspecialchars($user['phone']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="shipping_email">Email Address</label>
                                    <input type="email" id="shipping_email" name="shipping_email" class="form-control" value="<?php echo isset($user) ? htmlspecialchars($user['email']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_address" class="required-field">Delivery Address</label>
                            <input type="text" id="shipping_address" name="shipping_address" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="shipping_city" class="required-field">City/Town</label>
                                    <input type="text" id="shipping_city" name="shipping_city" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="shipping_province" class="required-field">Province</label>
                                    <select id="shipping_province" name="shipping_province" class="form-control" required>
                                        <option value="">Select Province</option>
                                        <?php foreach ($zambian_provinces as $province): ?>
                                            <option value="<?php echo $province; ?>"><?php echo $province; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Transport Provider</h2>
                        </div>
                        
                        <?php if (count($transport_providers) > 0): ?>
                            <p>Select a transport provider to deliver your order:</p>
                            
                            <div class="transport-providers">
                                <?php foreach ($transport_providers as $provider): ?>
                                    <?php 
                                    // Check if this provider serves the selected province
                                    $regions = explode(',', $provider['regions']);
                                    ?>
                                    <div class="transport-provider" data-regions="<?php echo htmlspecialchars($provider['regions']); ?>" onclick="selectTransport(this, <?php echo $provider['id']; ?>)">
                                        <input type="radio" name="transport_id" value="<?php echo $provider['id']; ?>" id="transport_<?php echo $provider['id']; ?>">
                                        <div class="transport-provider-icon">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <div class="transport-provider-details">
                                            <div class="transport-provider-title"><?php echo htmlspecialchars($provider['name']); ?></div>
                                            <div class="transport-provider-description"><?php echo htmlspecialchars($provider['description']); ?></div>
                                            <div class="transport-provider-info">
                                                <div><i class="fas fa-clock"></i> <?php echo $provider['estimated_days']; ?> days</div>
                                                <div><i class="fas fa-money-bill"></i> ZMW <?php echo number_format($provider['cost_per_km'], 2); ?>/km</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div id="no-transport-message" style="display: none;" class="alert alert-danger">
                                No transport providers available for the selected province. Please choose a different province or contact us for assistance.
                            </div>
                        <?php else: ?>
                            <p>No transport providers available at this time. Your order will be processed, but you'll need to arrange pickup or contact us for delivery options.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Payment Method</h2>
                        </div>
                        
                        <div class="payment-methods">
                            <div class="payment-method selected" onclick="selectPayment(this, 'cash_on_delivery')">
                                <input type="radio" name="payment_method" value="cash_on_delivery" id="payment_cash" checked>
                                <div class="payment-method-icon">
                                    <i class="fas fa-money-bill"></i>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Cash on Delivery</div>
                                    <div class="payment-method-description">Pay when you receive your order</div>
                                </div>
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment(this, 'mobile_money')">
                                <input type="radio" name="payment_method" value="mobile_money" id="payment_mobile">
                                <div class="payment-method-icon">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Mobile Money</div>
                                    <div class="payment-method-description">Pay using MTN Money, Airtel Money, or Zamtel Money</div>
                                </div>
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment(this, 'bank_transfer')">
                                <input type="radio" name="payment_method" value="bank_transfer" id="payment_bank">
                                <div class="payment-method-icon">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-title">Bank Transfer</div>
                                    <div class="payment-method-description">Pay directly to our bank account</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Additional Information</h2>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Special instructions for delivery or any other notes"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="order-summary">
                    <div class="card">
                        <div class="card-header">
                            <h2>Order Summary</h2>
                        </div>
                        
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item">
                                    <div class="cart-item-image">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                    <div class="cart-item-details">
                                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="cart-item-price">ZMW <?php echo number_format($item['price'], 2); ?></div>
                                        <div class="cart-item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-summary">
                            <div class="summary-row">
                                <div>Subtotal</div>
                                <div>ZMW <?php echo number_format($total_amount, 2); ?></div>
                            </div>
                            <div class="summary-row">
                                <div>Shipping</div>
                                <div>Calculated at next step</div>
                            </div>
                            <div class="summary-row total">
                                <div>Total</div>
                                <div>ZMW <?php echo number_format($total_amount, 2); ?></div>
                            </div>
                        </div>
                        
                        <button type="submit" name="checkout" class="btn btn-block">
                            <i class="fas fa-lock"></i> Complete Order
                        </button>
                    </div>
                </div>
            </form>
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
    
    <script>
        // Function to select payment method
        function selectPayment(element, method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById('payment_' + method.split('_')[1]).checked = true;
        }
        
        // Function to select transport provider
        function selectTransport(element, id) {
            // Remove selected class from all transport providers
            document.querySelectorAll('.transport-provider').forEach(provider => {
                provider.classList.remove('selected');
            });
            
            // Add selected class to clicked provider
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById('transport_' + id).checked = true;
        }
        
        // Function to filter transport providers based on province
        document.getElementById('shipping_province').addEventListener('change', function() {
            const selectedProvince = this.value;
            let providersAvailable = false;
            
            // Show/hide transport providers based on regions they serve
            document.querySelectorAll('.transport-provider').forEach(provider => {
                const regions = provider.getAttribute('data-regions').split(',');
                
                if (selectedProvince === '' || regions.includes(selectedProvince)) {
                    provider.style.display = 'flex';
                    providersAvailable = true;
                } else {
                    provider.style.display = 'none';
                    // Uncheck if it was selected
                    const radioBtn = provider.querySelector('input[type="radio"]');
                    if (radioBtn.checked) {
                        radioBtn.checked = false;
                        provider.classList.remove('selected');
                    }
                }
            });
            
            // Show/hide no transport message
            const noTransportMsg = document.getElementById('no-transport-message');
            if (noTransportMsg) {
                noTransportMsg.style.display = selectedProvince !== '' && !providersAvailable ? 'block' : 'none';
            }
        });
    </script>
</body>
</html>