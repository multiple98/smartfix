<?php
session_start();
include('../includes/db.php');
include('transport_calculator.php');

// Check if order ID and tracking number are provided
if (!isset($_GET['order_id']) || !isset($_GET['tracking'])) {
    header('Location: ../index.php');
    exit;
}

$order_id = intval($_GET['order_id']);
$tracking_number = $_GET['tracking'];

// Get order details
try {
    $order_query = "SELECT o.*, tp.name as transport_name, tp.contact as transport_contact, 
                           tp.estimated_days, tp.service_type
                    FROM orders o 
                    LEFT JOIN transport_providers tp ON o.transport_id = tp.id 
                    WHERE o.id = ? AND o.tracking_number = ?";
    $stmt = $pdo->prepare($order_query);
    $stmt->execute([$order_id, $tracking_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ../index.php');
        exit;
    }
    
    // Get order items
    $items_query = "SELECT oi.*, p.name as product_name 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?";
    $items_stmt = $pdo->prepare($items_query);
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate delivery estimate
    $transport_calc = new TransportCalculator($pdo);
    $delivery_estimate = null;
    if ($order['transport_id']) {
        $delivery_estimate = $transport_calc->getDeliveryEstimate($order['transport_id'], $order['shipping_province']);
    }
    
} catch (PDOException $e) {
    die("Error fetching order details: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .success-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .success-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .order-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .tracking-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        
        .card-content {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section {
            background: var(--light-color);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .info-value {
            color: #666;
        }
        
        .order-items {
            margin-bottom: 30px;
        }
        
        .items-header {
            background: var(--light-color);
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item:last-child {
            border-bottom: none;
            border-radius: 0 0 10px 10px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .item-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .item-price {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .order-total {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .delivery-timeline {
            background: var(--light-color);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .timeline-header {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timeline-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }
        
        .timeline-step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        
        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
        }
        
        .step-icon.completed {
            background: var(--success-color);
        }
        
        .step-icon.pending {
            background: #ccc;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .step-date {
            font-size: 0.9rem;
            color: #666;
        }
        
        .timeline-line {
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ccc;
            z-index: -1;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 25px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .contact-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .contact-info h4 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline-steps {
                flex-direction: column;
                gap: 20px;
            }
            
            .timeline-line {
                display: none;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order has been successfully placed.</p>
        </div>
        
        <div class="order-card">
            <div class="card-header">
                <div class="tracking-number"><?php echo htmlspecialchars($tracking_number); ?></div>
                <p>Keep this tracking number for your records</p>
            </div>
            
            <div class="card-content">
                <div class="info-grid">
                    <!-- Order Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-shopping-bag"></i> Order Details</h3>
                        <div class="info-item">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Method:</span>
                            <span class="info-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span class="info-value"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <div class="info-section">
                        <h3><i class="fas fa-shipping-fast"></i> Shipping Details</h3>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['shipping_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['shipping_phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">City:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['shipping_city']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Province:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['shipping_province']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Transport Information -->
                    <?php if ($order['transport_name']): ?>
                    <div class="info-section">
                        <h3><i class="fas fa-truck"></i> Delivery Service</h3>
                        <div class="info-item">
                            <span class="info-label">Provider:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['transport_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contact:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['transport_contact']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Service Type:</span>
                            <span class="info-value"><?php echo ucfirst($order['service_type']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Shipping Cost:</span>
                            <span class="info-value">ZMW <?php echo number_format($order['transport_cost'], 2); ?></span>
                        </div>
                        <?php if ($delivery_estimate): ?>
                        <div class="info-item">
                            <span class="info-label">Expected Delivery:</span>
                            <span class="info-value"><?php echo $delivery_estimate['delivery_date_formatted']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Order Items -->
                <div class="order-items">
                    <div class="items-header">
                        <i class="fas fa-list"></i> Order Items
                    </div>
                    <?php foreach ($order_items as $item): ?>
                    <div class="item">
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="item-meta">Quantity: <?php echo $item['quantity']; ?> × ZMW <?php echo number_format($item['price'], 2); ?></div>
                        </div>
                        <div class="item-price">
                            ZMW <?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Total -->
                <div class="order-total">
                    <i class="fas fa-receipt"></i> Total Amount: ZMW <?php echo number_format($order['total_amount'], 2); ?>
                </div>
                
                <!-- Delivery Timeline -->
                <?php if ($delivery_estimate): ?>
                <div class="delivery-timeline">
                    <h3 class="timeline-header">
                        <i class="fas fa-clock"></i> Estimated Delivery Timeline
                    </h3>
                    <div class="timeline-steps">
                        <div class="timeline-line"></div>
                        <div class="timeline-step">
                            <div class="step-icon completed">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="step-title">Order Placed</div>
                            <div class="step-date"><?php echo date('M j', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon pending">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="step-title">Processing</div>
                            <div class="step-date"><?php echo date('M j', strtotime('+1 day', strtotime($order['created_at']))); ?></div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon pending">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="step-title">In Transit</div>
                            <div class="step-date"><?php echo date('M j', strtotime('+2 days', strtotime($order['created_at']))); ?></div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon pending">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="step-title">Delivered</div>
                            <div class="step-date"><?php echo date('M j', strtotime($delivery_estimate['delivery_date'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="track_order.php?tracking=<?php echo urlencode($tracking_number); ?>" class="btn btn-primary">
                        <i class="fas fa-search"></i> Track Order
                    </a>
                    <a href="../shop.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Continue Shopping
                    </a>
                    <a href="../user/dashboard.php" class="btn btn-info">
                        <i class="fas fa-user"></i> My Account
                    </a>
                </div>
                
                <!-- Contact Information -->
                <div class="contact-info">
                    <h4><i class="fas fa-headset"></i> Need Help?</h4>
                    <p>If you have any questions about your order, please contact us:</p>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>📞 Phone: +260-97-000-0000</li>
                        <li>📧 Email: support@smartfix.com</li>
                        <li>💬 WhatsApp: +260-97-000-0000</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>