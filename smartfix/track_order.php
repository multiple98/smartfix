<?php
session_start();
include('includes/db.php');

$tracking_number = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';
$order = null;
$tracking_history = [];
$error_message = '';

if (!empty($tracking_number)) {
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.tracking_number = ?
            GROUP BY o.id
        ");
        $stmt->execute([$tracking_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get tracking history
            try {
                $tracking_stmt = $pdo->prepare("
                    SELECT status, description, location, created_at
                    FROM order_tracking 
                    WHERE order_id = ? 
                    ORDER BY created_at DESC
                ");
                $tracking_stmt->execute([$order['id']]);
                $tracking_history = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Tracking table might not exist or have different structure
                $tracking_history = [
                    [
                        'status' => 'Order Placed',
                        'description' => 'Your order has been received and is being processed.',
                        'location' => 'SmartFix Warehouse',
                        'created_at' => $order['created_at']
                    ]
                ];
            }
        } else {
            $error_message = "Order not found. Please check your tracking number and try again.";
        }
    } catch (PDOException $e) {
        $error_message = "Error retrieving order information. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Order - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            max-width: 800px;
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
        
        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 40px;
            margin-bottom: 30px;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .order-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .order-status {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .status-processing {
            background: #fff3cd;
            color: #856404;
            border: 2px solid var(--warning-color);
        }
        
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
            border: 2px solid #17a2b8;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
            border: 2px solid var(--success-color);
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            background: var(--light-color);
            padding: 20px;
            border-radius: 12px;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-value {
            color: var(--dark-color);
            font-size: 1.1rem;
        }
        
        .tracking-timeline {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            padding: 40px;
        }
        
        .timeline-header {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -37px;
            top: 25px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--primary-color);
        }
        
        .timeline-item:first-child::before {
            background: var(--success-color);
            box-shadow: 0 0 0 3px var(--success-color);
        }
        
        .timeline-status {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        
        .timeline-description {
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .timeline-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .timeline-location {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
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
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .order-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .timeline-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        
        <div class="page-header">
            <h1>
                <i class="fas fa-search"></i>
                Track Your Order
            </h1>
            <p>Enter your tracking number to see the current status of your order</p>
        </div>
        
        <div class="search-card">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="tracking">
                        <i class="fas fa-barcode"></i>
                        Tracking Number
                    </label>
                    <input type="text" 
                           id="tracking" 
                           name="tracking" 
                           class="form-control" 
                           placeholder="Enter your tracking number (e.g., SF-ORD-000001)"
                           value="<?= htmlspecialchars($tracking_number) ?>"
                           required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    <span>Track Order</span>
                </button>
            </form>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            <div class="order-card">
                <div class="order-header">
                    <h2 class="order-title">Order #<?= htmlspecialchars($order['tracking_number']) ?></h2>
                    <div class="order-status status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </div>
                </div>
                
                <div class="order-details">
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-user"></i>
                            Customer Name
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($order['shipping_name']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($order['shipping_phone']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Delivery Address
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($order['shipping_address']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-calendar"></i>
                            Order Date
                        </div>
                        <div class="detail-value"><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></div>
                    </div>
                    
                    <?php if (!empty($order['items'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-shopping-bag"></i>
                            Items Ordered
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($order['items']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-dollar-sign"></i>
                            Total Amount
                        </div>
                        <div class="detail-value">K<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($tracking_history)): ?>
            <div class="tracking-timeline">
                <h3 class="timeline-header">
                    <i class="fas fa-route"></i>
                    Order Timeline
                </h3>
                
                <div class="timeline">
                    <?php foreach ($tracking_history as $track): ?>
                    <div class="timeline-item">
                        <div class="timeline-status"><?= htmlspecialchars($track['status']) ?></div>
                        <div class="timeline-description"><?= htmlspecialchars($track['description']) ?></div>
                        <div class="timeline-meta">
                            <div class="timeline-location">
                                <i class="fas fa-map-pin"></i>
                                <span><?= htmlspecialchars($track['location'] ?? 'Processing Center') ?></span>
                            </div>
                            <div class="timeline-date">
                                <i class="fas fa-clock"></i>
                                <span><?= date('M d, Y g:i A', strtotime($track['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-focus on tracking input
        document.getElementById('tracking').focus();
        
        // Format tracking number input
        document.getElementById('tracking').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            // Remove any non-alphanumeric characters except hyphens
            value = value.replace(/[^A-Z0-9-]/g, '');
            e.target.value = value;
        });
        
        // Add loading state to search button
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.querySelector('.btn-primary');
            btn.innerHTML = '<div class="loading"></div> <span>Searching...</span>';
            btn.disabled = true;
        });
    </script>
</body>
</html>