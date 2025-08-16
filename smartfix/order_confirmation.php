<?php
session_start();
include('includes/db.php');

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$success = isset($_GET['success']) ? true : false;

if ($order_id <= 0) {
    header("Location: shop.php");
    exit();
}

// Get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: shop.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: shop.php");
    exit();
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .confirmation-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 50px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 30px;
            animation: bounce 1s ease-in-out;
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
        
        h1 {
            color: var(--success-color);
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 40px;
            opacity: 0.8;
        }
        
        .order-details {
            background: var(--light-color);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            text-align: left;
        }
        
        .order-details h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .detail-value {
            color: var(--dark-color);
        }
        
        .tracking-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            border: 2px solid var(--primary-color);
        }
        
        .tracking-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            min-height: 55px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 5px 15px rgba(108,117,125,0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108,117,125,0.4);
        }
        
        .next-steps {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }
        
        .next-steps h4 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .next-steps ul {
            list-style: none;
            padding: 0;
        }
        
        .next-steps li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #856404;
        }
        
        .next-steps li i {
            color: #ffc107;
            width: 20px;
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                padding: 30px 25px;
                margin: 10px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .success-icon {
                font-size: 4rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        .contact-info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .contact-info h4 {
            color: #0c5460;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-info p {
            color: #0c5460;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>Order Confirmed!</h1>
        <p class="subtitle">Thank you for your order. We've received your request and will process it shortly.</p>
        
        <div class="order-details">
            <h3>
                <i class="fas fa-receipt"></i>
                Order Details
            </h3>
            
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value">#<?= $order['id'] ?></span>
            </div>
            
            <?php if (!empty($order['tracking_number'])): ?>
            <div class="detail-row">
                <span class="detail-label">Tracking Number:</span>
                <span class="detail-value"><?= htmlspecialchars($order['tracking_number']) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value"><?= htmlspecialchars($order['shipping_name']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?= htmlspecialchars($order['shipping_phone']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Delivery Address:</span>
                <span class="detail-value"><?= htmlspecialchars($order['shipping_address']) ?></span>
            </div>
            
            <?php if (!empty($order['items'])): ?>
            <div class="detail-row">
                <span class="detail-label">Items:</span>
                <span class="detail-value"><?= htmlspecialchars($order['items']) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <span class="detail-label">Order Status:</span>
                <span class="detail-value"><?= ucfirst($order['status']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">K<?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>
        
        <?php if (!empty($order['tracking_number'])): ?>
        <div class="tracking-info">
            <h4><i class="fas fa-truck"></i> Track Your Order</h4>
            <p>Your tracking number is:</p>
            <div class="tracking-number"><?= htmlspecialchars($order['tracking_number']) ?></div>
            <p>Use this number to track your order status and delivery progress.</p>
        </div>
        <?php endif; ?>
        
        <div class="next-steps">
            <h4><i class="fas fa-list-check"></i> What Happens Next?</h4>
            <ul>
                <li><i class="fas fa-check"></i> We'll process your order within 24 hours</li>
                <li><i class="fas fa-phone"></i> Our team will contact you to confirm delivery details</li>
                <li><i class="fas fa-truck"></i> Your order will be prepared and shipped</li>
                <li><i class="fas fa-bell"></i> You'll receive updates via SMS/phone calls</li>
                <li><i class="fas fa-home"></i> Delivery will be made to your specified address</li>
            </ul>
        </div>
        
        <div class="contact-info">
            <h4><i class="fas fa-headset"></i> Need Help?</h4>
            <p><i class="fas fa-phone"></i> Call us: +260 977 123456</p>
            <p><i class="fas fa-envelope"></i> Email: orders@smartfix.zm</p>
            <p><i class="fas fa-clock"></i> Support Hours: 8:00 AM - 6:00 PM (Mon-Sat)</p>
        </div>
        
        <div class="action-buttons">
            <a href="shop.php" class="btn btn-secondary">
                <i class="fas fa-shopping-cart"></i>
                <span>Continue Shopping</span>
            </a>
            
            <?php if (!empty($order['tracking_number'])): ?>
            <a href="track_order.php?tracking=<?= urlencode($order['tracking_number']) ?>" class="btn btn-primary">
                <i class="fas fa-search"></i>
                <span>Track Order</span>
            </a>
            <?php endif; ?>
            
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                <span>Back to Home</span>
            </a>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 30 seconds if no interaction
        let redirectTimer = setTimeout(function() {
            window.location.href = 'shop.php';
        }, 30000);
        
        // Cancel redirect if user interacts with page
        document.addEventListener('click', function() {
            clearTimeout(redirectTimer);
        });
        
        document.addEventListener('keypress', function() {
            clearTimeout(redirectTimer);
        });
        
        // Show success animation
        window.addEventListener('load', function() {
            const container = document.querySelector('.confirmation-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(function() {
                container.style.transition = 'all 0.6s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>