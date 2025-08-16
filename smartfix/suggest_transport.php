<?php
session_start();
include('includes/db.php');

$order_id = intval($_GET['order_id'] ?? 0);
$success = isset($_GET['success']);

// Get order details
$order = null;
if ($order_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error
    }
}

// Get available transport providers
$transport_providers = [];
try {
    $stmt = $pdo->query("SELECT * FROM transport_providers ORDER BY name");
    $transport_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet, create it
    try {
        $create_transport = "CREATE TABLE IF NOT EXISTS transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            contact VARCHAR(50),
            estimated_days INT DEFAULT 3,
            price_per_km DECIMAL(10,2) DEFAULT 0.50,
            description TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_transport);
        
        // Add sample transport providers
        $sample_providers = [
            ['Zampost', '+260 211 228228', 7, 5.00, 'National postal service'],
            ['DHL Express', '+260 211 254254', 2, 25.00, 'International express courier'],
            ['FedEx', '+260 211 256789', 3, 20.00, 'Fast and reliable delivery'],
            ['Local Courier', '+260 977 123456', 1, 2.00, 'Same day delivery within Lusaka']
        ];
        
        foreach ($sample_providers as $provider) {
            $stmt = $pdo->prepare("INSERT INTO transport_providers (name, contact, estimated_days, price_per_km, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute($provider);
        }
        
        // Reload transport providers
        $stmt = $pdo->query("SELECT * FROM transport_providers ORDER BY name");
        $transport_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        // Continue without transport providers
    }
}

// Handle transport selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_transport']) && $order) {
    $transport_id = intval($_POST['transport_id']);
    
    try {
        // Update order with selected transport
        $stmt = $pdo->prepare("UPDATE orders SET transport_id = ? WHERE id = ?");
        $stmt->execute([$transport_id, $order_id]);
        
        // Add tracking update
        $transport_name = 'Selected Transport';
        foreach ($transport_providers as $provider) {
            if ($provider['id'] == $transport_id) {
                $transport_name = $provider['name'];
                break;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location, timestamp) 
                              VALUES (?, 'Transport Selected', ?, 'Processing Center', NOW())");
        $stmt->execute([$order_id, "Transport provider selected: {$transport_name}"]);
        
        // Redirect to order confirmation
        header("Location: shop/order_confirmation.php?id={$order_id}");
        exit;
    } catch (PDOException $e) {
        $error_message = "Error updating transport selection.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Suggestion - SmartFix</title>
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
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
        }
        
        nav a:hover {
            color: #ffcc00;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .success-message {
            background: #d4edda;
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
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            color: #004080;
            margin-top: 0;
        }
        
        .transport-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .transport-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .transport-option:hover {
            border-color: #007BFF;
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
        }
        
        .transport-option input[type="radio"] {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        
        .transport-name {
            font-size: 18px;
            font-weight: bold;
            color: #004080;
            margin-bottom: 10px;
        }
        
        .transport-contact {
            color: #666;
            margin-bottom: 8px;
        }
        
        .transport-time {
            color: #007BFF;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .transport-description {
            color: #666;
            font-size: 14px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn-primary {
            background: #007BFF;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-outline {
            background: transparent;
            color: #007BFF;
            border: 1px solid #007BFF;
        }
        
        .btn-outline:hover {
            background: #007BFF;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .transport-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-tools"></i> SmartFix
        </div>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
            <a href="services.php"><i class="fas fa-tools"></i> Services</a>
        </nav>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h2>Order Placed Successfully!</h2>
                <p>Your order has been received and is being processed.</p>
                <?php if ($order): ?>
                    <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
            <div class="card">
                <h2><i class="fas fa-truck"></i> Select Transport Option</h2>
                <p>Choose your preferred delivery method for your order.</p>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Order ID:</span>
                        <span><strong><?php echo htmlspecialchars($order['tracking_number']); ?></strong></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery to:</span>
                        <span><?php echo htmlspecialchars($order['shipping_name'] ?? $order['customer_name'] ?? 'Customer'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Address:</span>
                        <span><?php echo htmlspecialchars($order['shipping_address'] ?? $order['customer_address'] ?? 'Address not provided'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Total Amount:</span>
                        <span><strong>K<?php echo number_format($order['total_amount'], 2); ?></strong></span>
                    </div>
                </div>

                <?php if (!empty($transport_providers)): ?>
                    <form method="POST">
                        <div class="transport-grid">
                            <?php foreach ($transport_providers as $provider): ?>
                                <label class="transport-option">
                                    <input type="radio" name="transport_id" value="<?php echo $provider['id']; ?>" 
                                           <?php echo empty($selected_transport) ? 'required' : ''; ?>>
                                    
                                    <div class="transport-name">
                                        <i class="fas fa-truck"></i> <?php echo htmlspecialchars($provider['name']); ?>
                                    </div>
                                    
                                    <div class="transport-contact">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($provider['contact']); ?>
                                    </div>
                                    
                                    <div class="transport-time">
                                        <i class="fas fa-clock"></i> Estimated delivery: <?php echo $provider['estimated_days']; ?> day<?php echo $provider['estimated_days'] != 1 ? 's' : ''; ?>
                                    </div>
                                    
                                    <?php if ($provider['description']): ?>
                                        <div class="transport-description">
                                            <?php echo htmlspecialchars($provider['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="select_transport" class="btn btn-primary">
                                <i class="fas fa-check"></i> Confirm Transport Selection
                            </button>
                        </div>
                    </form>
                    
                    <div class="action-buttons">
                        <a href="shop/order_confirmation.php?id=<?php echo $order_id; ?>" class="btn btn-outline">
                            <i class="fas fa-skip-forward"></i> Skip - Use Default
                        </a>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-truck" style="font-size: 48px; color: #dee2e6; margin-bottom: 20px;"></i>
                        <h3>Transport Options Coming Soon</h3>
                        <p>We're setting up transport providers in your area.</p>
                        <a href="shop/order_confirmation.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Continue to Order Details
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card" style="text-align: center;">
                <h2>Order Not Found</h2>
                <p>The requested order could not be found.</p>
                <a href="shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>