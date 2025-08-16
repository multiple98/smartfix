<?php
session_start();
include('../includes/db.php');

$tracking_number = $_GET['tracking'] ?? '';
$order = null;
$tracking_history = [];

if ($tracking_number) {
    try {
        // Get order details
        $order_query = "SELECT o.*, tp.name as transport_name, tp.contact as transport_contact 
                        FROM orders o 
                        LEFT JOIN transport_providers tp ON o.transport_id = tp.id 
                        WHERE o.tracking_number = ?";
        $stmt = $pdo->prepare($order_query);
        $stmt->execute([$tracking_number]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Get tracking history
            $tracking_query = "SELECT * FROM order_tracking WHERE order_id = ? ORDER BY timestamp DESC";
            $tracking_stmt = $pdo->prepare($tracking_query);
            $tracking_stmt->execute([$order['id']]);
            $tracking_history = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Handle error silently
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }
        
        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .search-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .btn {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .order-details {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .order-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .tracking-number {
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .order-info {
            padding: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            text-align: center;
            padding: 20px;
            background: var(--light-color);
            border-radius: 10px;
        }
        
        .info-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #666;
        }
        
        .tracking-timeline {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 30px;
        }
        
        .timeline-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
        }
        
        .timeline {
            position: relative;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        
        .timeline-item {
            position: relative;
            padding-left: 80px;
            margin-bottom: 30px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .timeline-content {
            background: var(--light-color);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--success-color);
        }
        
        .timeline-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .timeline-description {
            color: #666;
            margin-bottom: 10px;
        }
        
        .timeline-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #888;
        }
        
        .no-order {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }
        
        .no-order i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline-item {
                padding-left: 60px;
            }
            
            .timeline-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .timeline::before {
                left: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Track Your Order</h1>
            <p>Enter your tracking number to see the current status of your order</p>
        </div>
        
        <div class="search-form">
            <form method="GET">
                <div class="form-group">
                    <label for="tracking">Tracking Number</label>
                    <input type="text" id="tracking" name="tracking" class="form-control" 
                           value="<?php echo htmlspecialchars($tracking_number); ?>" 
                           placeholder="Enter your tracking number (e.g., SF-20241201-1234)" required>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Track Order
                </button>
            </form>
        </div>
        
        <?php if ($tracking_number && $order): ?>
            <div class="order-details">
                <div class="order-header">
                    <div class="tracking-number"><?php echo htmlspecialchars($tracking_number); ?></div>
                    <p>Order Status: <?php echo ucfirst($order['status']); ?></p>
                </div>
                
                <div class="order-info">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="info-label">Order Date</div>
                            <div class="info-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div class="info-label">Total Amount</div>
                            <div class="info-value">ZMW <?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></div>
                        </div>
                        
                        <?php if ($order['transport_name']): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="info-label">Transport Provider</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['transport_name']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($tracking_history)): ?>
            <div class="tracking-timeline">
                <h2 class="timeline-header">
                    <i class="fas fa-route"></i> Order Timeline
                </h2>
                
                <div class="timeline">
                    <?php foreach ($tracking_history as $track): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <?php
                            $icon = 'fas fa-circle';
                            switch ($track['status']) {
                                case 'processing':
                                    $icon = 'fas fa-cog';
                                    break;
                                case 'shipped':
                                    $icon = 'fas fa-shipping-fast';
                                    break;
                                case 'in_transit':
                                    $icon = 'fas fa-truck';
                                    break;
                                case 'out_for_delivery':
                                    $icon = 'fas fa-route';
                                    break;
                                case 'delivered':
                                    $icon = 'fas fa-check';
                                    break;
                                default:
                                    $icon = 'fas fa-info';
                            }
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title"><?php echo ucwords(str_replace('_', ' ', $track['status'])); ?></div>
                            <div class="timeline-description"><?php echo htmlspecialchars($track['description']); ?></div>
                            <div class="timeline-meta">
                                <span><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($track['timestamp'])); ?></span>
                                <?php if ($track['location']): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($track['location']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php elseif ($tracking_number && !$order): ?>
            <div class="no-order">
                <i class="fas fa-search"></i>
                <h2>Order Not Found</h2>
                <p>We couldn't find an order with tracking number: <strong><?php echo htmlspecialchars($tracking_number); ?></strong></p>
                <p>Please check your tracking number and try again.</p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="../shop.php" class="btn" style="display: inline-block; width: auto; padding: 15px 30px;">
                <i class="fas fa-shopping-cart"></i> Continue Shopping
            </a>
        </div>
    </div>
</body>
</html>