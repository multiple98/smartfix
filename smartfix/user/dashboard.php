<?php
session_start();
include('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php?form=login');
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

// Get user's orders
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                          FROM orders o 
                          LEFT JOIN order_items oi ON o.id = oi.order_id 
                          WHERE o.user_id = ? 
                          GROUP BY o.id 
                          ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Orders table might not exist yet
}

// Get user's service requests (if table exists)
$service_requests = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Service requests table might not exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-welcome {
            text-align: right;
        }

        .user-welcome h2 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .user-welcome p {
            font-size: 14px;
            opacity: 0.9;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #667eea;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .widget {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .widget h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-processing { background: #fff3cd; color: #856404; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .action-btn i {
            font-size: 20px;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }

        .no-data i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
        }

        .profile-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-summary h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .info-item i {
            color: #667eea;
            width: 16px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1><i class="fas fa-tools"></i> SmartFix</h1>
            </div>
            <div class="user-info">
                <div class="user-welcome">
                    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['name']); ?>!</h2>
                    <p>Manage your orders, services, and account</p>
                </div>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="value"><?php echo count($orders); ?></div>
                <div class="label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="value"><?php echo count($service_requests); ?></div>
                <div class="label">Service Requests</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="value">
                    <?php 
                    $active_orders = array_filter($orders, function($order) {
                        return in_array($order['status'], ['processing', 'shipped', 'in_transit']);
                    });
                    echo count($active_orders);
                    ?>
                </div>
                <div class="label">Active Orders</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="value">Member</div>
                <div class="label">Account Status</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="../shop.php" class="action-btn">
                <i class="fas fa-shopping-cart"></i>
                <span>Shop Products</span>
            </a>
            
            <a href="../services/request_service.php" class="action-btn">
                <i class="fas fa-wrench"></i>
                <span>Request Service</span>
            </a>
            
            <a href="../shop/cart.php" class="action-btn">
                <i class="fas fa-shopping-bag"></i>
                <span>View Cart</span>
            </a>
            
            <a href="../shop/track_order.php" class="action-btn">
                <i class="fas fa-truck"></i>
                <span>Track Order</span>
            </a>
            
            <a href="gps_dashboard.php" class="action-btn">
                <i class="fas fa-map-marked-alt"></i>
                <span>GPS Dashboard</span>
            </a>
        </div>

        <div class="content-grid">
            <div class="main-content">
                <h3>Recent Orders</h3>
                
                <?php if (!empty($orders)): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($orders, 0, 10) as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $order['tracking_number'] ?? '#' . $order['id']; ?></strong>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td>K<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-shopping-cart"></i>
                        <h4>No Orders Yet</h4>
                        <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                        <a href="../shop.php" class="action-btn" style="margin-top: 20px; display: inline-flex;">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Start Shopping</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <!-- Profile Summary -->
                <div class="widget">
                    <h3>Profile Summary</h3>
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($user['full_name'] ?? $user['name']); ?></h4>
                        <div class="profile-info">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <?php if (!empty($user['phone'])): ?>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($user['city'])): ?>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($user['city']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="widget">
                    <h3>Quick Links</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="../shop.php" class="action-btn">
                            <i class="fas fa-store"></i>
                            <span>Browse Shop</span>
                        </a>
                        <a href="../shop/track_order.php" class="action-btn">
                            <i class="fas fa-search"></i>
                            <span>Track Order</span>
                        </a>
                        <a href="../contact.php" class="action-btn">
                            <i class="fas fa-envelope"></i>
                            <span>Contact Support</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>