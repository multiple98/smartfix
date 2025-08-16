<?php
session_start();
include('../includes/db.php');
include('transport_calculator.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=shop/checkout_enhanced.php');
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Initialize transport calculator
$transport_calc = new TransportCalculator($pdo);

// Create sample providers if none exist
$transport_calc->createSampleProviders();

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

// Get cart items and calculate totals
$cart_items = [];
$total_amount = 0;
$total_weight = 0;

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $product['weight'] = $product['weight'] ?? 1; // Default 1kg per item
            $product['total_weight'] = $product['weight'] * $quantity;
            
            $cart_items[] = $product;
            $total_amount += $product['subtotal'];
            $total_weight += $product['total_weight'];
        }
    } catch (PDOException $e) {
        continue;
    }
}

// Zambian provinces
$zambian_provinces = [
    'Central', 'Copperbelt', 'Eastern', 'Luapula', 'Lusaka', 
    'Muchinga', 'Northern', 'Northwestern', 'Southern', 'Western'
];

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $shipping_name = trim($_POST['shipping_name']);
    $shipping_phone = trim($_POST['shipping_phone']);
    $shipping_email = trim($_POST['shipping_email']);
    $shipping_address = trim($_POST['shipping_address']);
    $shipping_city = trim($_POST['shipping_city']);
    $shipping_province = trim($_POST['shipping_province']);
    $transport_id = intval($_POST['transport_id'] ?? 0);
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || 
        empty($shipping_city) || empty($shipping_province) || empty($payment_method)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($shipping_email) && !filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($transport_id == 0) {
        $error_message = "Please select a transport provider.";
    } else {
        try {
            // Calculate shipping cost
            $shipping_cost_data = $transport_calc->calculateShippingCost($transport_id, $shipping_province, $shipping_city, $total_weight);
            
            if (!$shipping_cost_data) {
                $error_message = "Error calculating shipping cost. Please try again.";
            } else {
                $shipping_cost = $shipping_cost_data['total_cost'];
                $final_total = $total_amount + $shipping_cost;
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate tracking number
                $tracking_number = 'SF-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Create order
                $order_query = "INSERT INTO orders (
                    user_id, tracking_number, shipping_name, shipping_phone, shipping_email, 
                    shipping_address, shipping_city, shipping_province, payment_method, 
                    transport_id, transport_cost, notes, total_amount, status, created_at
                ) VALUES (
                    :user_id, :tracking_number, :shipping_name, :shipping_phone, :shipping_email,
                    :shipping_address, :shipping_city, :shipping_province, :payment_method,
                    :transport_id, :transport_cost, :notes, :total_amount, 'processing', NOW()
                )";
                
                $order_stmt = $pdo->prepare($order_query);
                $order_result = $order_stmt->execute([
                    'user_id' => $user_id,
                    'tracking_number' => $tracking_number,
                    'shipping_name' => $shipping_name,
                    'shipping_phone' => $shipping_phone,
                    'shipping_email' => $shipping_email,
                    'shipping_address' => $shipping_address,
                    'shipping_city' => $shipping_city,
                    'shipping_province' => $shipping_province,
                    'payment_method' => $payment_method,
                    'transport_id' => $transport_id,
                    'transport_cost' => $shipping_cost,
                    'notes' => $notes,
                    'total_amount' => $final_total
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $item_stmt = $pdo->prepare($item_query);
                
                foreach ($cart_items as $item) {
                    $item_stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
                }
                
                // Add initial tracking entry
                $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location) VALUES (?, ?, ?, ?)";
                $tracking_stmt = $pdo->prepare($tracking_query);
                $tracking_stmt->execute([
                    $order_id,
                    'processing',
                    'Order received and being processed',
                    'SmartFix Warehouse, Lusaka'
                ]);
                
                // Get delivery estimate
                $delivery_estimate = $transport_calc->getDeliveryEstimate($transport_id, $shipping_province);
                
                // Commit transaction
                $pdo->commit();
                
                // Clear cart
                unset($_SESSION['cart']);
                
                // Redirect to success page
                header("Location: order_success.php?order_id=$order_id&tracking=$tracking_number");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollback();
            $error_message = "Error processing order: " . $e->getMessage();
        }
    }
}

// Get available transport providers (for initial load)
$available_providers = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --primary-dark: #0056b3;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .checkout-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .checkout-form {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .form-section {
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .section-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .section-header h2 {
            color: var(--dark-color);
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .transport-providers {
            display: grid;
            gap: 15px;
        }
        
        .transport-provider {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }
        
        .transport-provider:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
        }
        
        .transport-provider.selected {
            border-color: var(--primary-color);
            background: rgba(0, 123, 255, 0.05);
        }
        
        .transport-provider input[type="radio"] {
            position: absolute;
            top: 15px;
            right: 15px;
            transform: scale(1.2);
        }
        
        .provider-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .provider-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .provider-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .provider-info h3 {
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .provider-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        
        .stars {
            color: #ffc107;
        }
        
        .provider-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .detail-item i {
            color: var(--primary-color);
            width: 16px;
        }
        
        .shipping-cost {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .cost-breakdown {
            display: grid;
            gap: 8px;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cost-total {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .payment-methods {
            display: grid;
            gap: 15px;
        }
        
        .payment-method {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .payment-method:hover {
            border-color: var(--primary-color);
        }
        
        .payment-method.selected {
            border-color: var(--primary-color);
            background: rgba(0, 123, 255, 0.05);
        }
        
        .payment-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-icon i {
            color: white;
            font-size: 1.2rem;
        }
        
        .order-summary {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .cart-items {
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-details {
            font-size: 0.9rem;
            color: #666;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .summary-totals {
            border-top: 2px solid var(--border-color);
            padding-top: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row.final {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            width: 100%;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: var(--success-color);
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: var(--danger-color);
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .provider-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-cart"></i> Secure Checkout</h1>
            <p>Complete your order with our professional delivery service</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-content">
            <form method="POST" class="checkout-form" id="checkoutForm">
                <!-- Shipping Information -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-shipping-fast"></i>
                        <h2>Shipping Information</h2>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_name" class="required">Full Name</label>
                        <input type="text" id="shipping_name" name="shipping_name" class="form-control" 
                               value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_phone" class="required">Phone Number</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_email">Email Address</label>
                            <input type="email" id="shipping_email" name="shipping_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address" class="required">Delivery Address</label>
                        <input type="text" id="shipping_address" name="shipping_address" class="form-control" 
                               placeholder="Enter your complete delivery address" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_city" class="required">City/Town</label>
                            <input type="text" id="shipping_city" name="shipping_city" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_province" class="required">Province</label>
                            <select id="shipping_province" name="shipping_province" class="form-control" required onchange="updateTransportProviders()">
                                <option value="">Select Province</option>
                                <?php foreach ($zambian_provinces as $province): ?>
                                    <option value="<?php echo $province; ?>"><?php echo $province; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Transport Provider Selection -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-truck"></i>
                        <h2>Delivery Service</h2>
                    </div>
                    
                    <div id="transport-loading" class="loading">
                        <div class="spinner"></div>
                        <p>Loading available transport providers...</p>
                    </div>
                    
                    <div id="transport-providers" class="transport-providers">
                        <p class="text-muted">Please select a province to see available transport providers.</p>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-credit-card"></i>
                        <h2>Payment Method</h2>
                    </div>
                    
                    <div class="payment-methods">
                        <div class="payment-method selected" onclick="selectPayment(this, 'cash_on_delivery')">
                            <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                            <div class="payment-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div>
                                <h4>Cash on Delivery</h4>
                                <p>Pay when your order is delivered</p>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment(this, 'mobile_money')">
                            <input type="radio" name="payment_method" value="mobile_money">
                            <div class="payment-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <h4>Mobile Money</h4>
                                <p>Pay via MTN Mobile Money or Airtel Money</p>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment(this, 'bank_transfer')">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <div class="payment-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div>
                                <h4>Bank Transfer</h4>
                                <p>Transfer to our bank account</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Notes -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="notes">Additional Notes (Optional)</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Any special delivery instructions or notes..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-lock"></i> Place Order Securely
                    </button>
                </div>
            </form>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-header">
                    <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                </div>
                
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-details">
                                    Qty: <?php echo $item['quantity']; ?> × ZMW <?php echo number_format($item['price'], 2); ?>
                                </div>
                            </div>
                            <div class="item-price">
                                ZMW <?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>ZMW <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Total Weight:</span>
                        <span><?php echo number_format($total_weight, 1); ?> kg</span>
                    </div>
                    <div class="total-row" id="shipping-cost-row" style="display: none;">
                        <span>Shipping:</span>
                        <span id="shipping-cost">ZMW 0.00</span>
                    </div>
                    <div class="total-row final">
                        <span>Total:</span>
                        <span id="final-total">ZMW <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="cart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedTransportId = 0;
        let baseTotal = <?php echo $total_amount; ?>;
        let totalWeight = <?php echo $total_weight; ?>;
        
        function selectPayment(element, method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(pm => pm.classList.remove('selected'));
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
        }
        
        function selectTransport(element, transportId, cost) {
            // Remove selected class from all providers
            document.querySelectorAll('.transport-provider').forEach(tp => tp.classList.remove('selected'));
            
            // Add selected class to clicked element
            element.classList.add('selected');
            
            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
            
            // Update selected transport ID
            selectedTransportId = transportId;
            
            // Update shipping cost display
            updateShippingCost(cost);
        }
        
        function updateShippingCost(cost) {
            const shippingCostRow = document.getElementById('shipping-cost-row');
            const shippingCostSpan = document.getElementById('shipping-cost');
            const finalTotalSpan = document.getElementById('final-total');
            
            if (cost > 0) {
                shippingCostRow.style.display = 'flex';
                shippingCostSpan.textContent = 'ZMW ' + cost.toFixed(2);
                
                const finalTotal = baseTotal + cost;
                finalTotalSpan.textContent = 'ZMW ' + finalTotal.toFixed(2);
            } else {
                shippingCostRow.style.display = 'none';
                finalTotalSpan.textContent = 'ZMW ' + baseTotal.toFixed(2);
            }
        }
        
        function updateTransportProviders() {
            const province = document.getElementById('shipping_province').value;
            const city = document.getElementById('shipping_city').value;
            const providersContainer = document.getElementById('transport-providers');
            const loadingDiv = document.getElementById('transport-loading');
            
            if (!province) {
                providersContainer.innerHTML = '<p class="text-muted">Please select a province to see available transport providers.</p>';
                return;
            }
            
            // Show loading
            loadingDiv.style.display = 'block';
            providersContainer.innerHTML = '';
            
            // Fetch transport providers
            fetch('get_transport_providers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    province: province,
                    city: city,
                    weight: totalWeight
                })
            })
            .then(response => response.json())
            .then(data => {
                loadingDiv.style.display = 'none';
                
                if (data.success && data.providers.length > 0) {
                    let html = '';
                    
                    data.providers.forEach(provider => {
                        const stars = '★'.repeat(Math.floor(provider.rating)) + '☆'.repeat(5 - Math.floor(provider.rating));
                        
                        html += `
                            <div class="transport-provider" onclick="selectTransport(this, ${provider.id}, ${provider.shipping_cost})">
                                <input type="radio" name="transport_id" value="${provider.id}">
                                <div class="provider-header">
                                    <div class="provider-icon">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="provider-info">
                                        <h3>${provider.name}</h3>
                                        <div class="provider-rating">
                                            <span class="stars">${stars}</span>
                                            <span>(${provider.rating})</span>
                                        </div>
                                    </div>
                                </div>
                                <p>${provider.description}</p>
                                <div class="provider-details">
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span>${provider.estimated_days} days</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-truck"></i>
                                        <span>${provider.vehicle_type}</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>${provider.service_type}</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <span>${provider.contact}</span>
                                    </div>
                                </div>
                                <div class="shipping-cost">
                                    <div class="cost-breakdown">
                                        <div class="cost-item">
                                            <span>Base Cost:</span>
                                            <span>ZMW ${provider.cost_breakdown.base_cost.toFixed(2)}</span>
                                        </div>
                                        <div class="cost-item">
                                            <span>Distance (${provider.cost_breakdown.estimated_distance}km):</span>
                                            <span>ZMW ${provider.cost_breakdown.distance_cost.toFixed(2)}</span>
                                        </div>
                                        ${provider.cost_breakdown.weight_surcharge > 0 ? `
                                        <div class="cost-item">
                                            <span>Weight Surcharge:</span>
                                            <span>ZMW ${provider.cost_breakdown.weight_surcharge.toFixed(2)}</span>
                                        </div>
                                        ` : ''}
                                        <div class="cost-item cost-total">
                                            <span>Total Shipping:</span>
                                            <span>ZMW ${provider.shipping_cost.toFixed(2)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    providersContainer.innerHTML = html;
                } else {
                    providersContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            No transport providers available for ${province}. Please contact us for alternative delivery options.
                        </div>
                    `;
                }
            })
            .catch(error => {
                loadingDiv.style.display = 'none';
                providersContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading transport providers. Please try again.
                    </div>
                `;
            });
        }
        
        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (selectedTransportId === 0) {
                e.preventDefault();
                alert('Please select a transport provider before placing your order.');
                return false;
            }
        });
    </script>
</body>
</html>