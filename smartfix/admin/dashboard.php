<?php
session_start();
include('../includes/db.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?redirect=admin/dashboard.php');
    exit;
}

// Get counts for dashboard
try {
    // Total users
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Total technicians
    $total_technicians = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
    
    // Total products
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    // Total bookings
    $total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    
    // Pending bookings
    $pending_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    
    // Completed bookings
    $completed_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
    
    // Total revenue
    $total_revenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn();
    
    // Recent users
    $recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent bookings
    $recent_bookings = $pdo->query("
        SELECT b.*, u.name as client_name, t.name as technician_name 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN technicians t ON b.technician_id = t.id
        ORDER BY b.created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top technicians
    $top_technicians = $pdo->query("
        SELECT t.*, COUNT(b.id) as booking_count 
        FROM technicians t
        LEFT JOIN bookings b ON t.id = b.technician_id
        GROUP BY t.id
        ORDER BY t.rating DESC, booking_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top products
    $top_products = $pdo->query("
        SELECT p.*, COUNT(o.id) as order_count 
        FROM products p
        LEFT JOIN order_items o ON p.id = o.product_id
        GROUP BY p.id
        ORDER BY order_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Some tables might not exist yet
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #004080;
            --primary-light: #0066cc;
            --primary-dark: #00305f;
            --accent-color: #ffcc00;
            --accent-hover: #e6b800;
            --text-color: #333333;
            --text-light: #666666;
            --bg-color: #f5f5f5;
            --bg-light: #ffffff;
            --bg-dark: #f0f0f0;
            --shadow: 0 5px 15px rgba(0,0,0,0.1);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: var(--bg-color);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: var(--shadow);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .user-info span {
            font-weight: 500;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: var(--shadow);
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }
        
        .stat-card.users::before {
            background-color: var(--primary-color);
        }
        
        .stat-card.technicians::before {
            background-color: var(--success-color);
        }
        
        .stat-card.products::before {
            background-color: var(--info-color);
        }
        
        .stat-card.bookings::before {
            background-color: var(--warning-color);
        }
        
        .stat-card.revenue::before {
            background-color: var(--accent-color);
        }
        
        .stat-card i {
            font-size: 36px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .stat-card.users i {
            color: var(--primary-color);
        }
        
        .stat-card.technicians i {
            color: var(--success-color);
        }
        
        .stat-card.products i {
            color: var(--info-color);
        }
        
        .stat-card.bookings i {
            color: var(--warning-color);
        }
        
        .stat-card.revenue i {
            color: var(--accent-color);
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 24px;
            color: var(--text-color);
        }
        
        .stat-card p {
            margin: 5px 0 0;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item-info {
            flex: 1;
        }
        
        .list-item-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .list-item-subtitle {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .list-item-actions {
            margin-left: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .badge-confirmed {
            background-color: var(--info-color);
            color: white;
        }
        
        .badge-completed {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-cancelled {
            background-color: var(--danger-color);
            color: white;
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
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: var(--text-light);
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
        
        .rating {
            color: var(--accent-color);
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SmartFix</h2>
                <p>Admin Portal</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="technicians.php"><i class="fas fa-user-md"></i> Technicians</a></li>
                <li><a href="products.php"><i class="fas fa-shopping-cart"></i> Products</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Main Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="Admin">
                    <span><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-stats">
                <div class="stat-card users">
                    <i class="fas fa-users"></i>
                    <h3><?php echo isset($total_users) ? $total_users : 0; ?></h3>
                    <p>Total Users</p>
                </div>
                
                <div class="stat-card technicians">
                    <i class="fas fa-user-md"></i>
                    <h3><?php echo isset($total_technicians) ? $total_technicians : 0; ?></h3>
                    <p>Technicians</p>
                </div>
                
                <div class="stat-card products">
                    <i class="fas fa-box"></i>
                    <h3><?php echo isset($total_products) ? $total_products : 0; ?></h3>
                    <p>Products</p>
                </div>
                
                <div class="stat-card bookings">
                    <i class="fas fa-calendar-check"></i>
                    <h3><?php echo isset($total_bookings) ? $total_bookings : 0; ?></h3>
                    <p>Bookings</p>
                </div>
                
                <div class="stat-card revenue">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>K<?php echo isset($total_revenue) ? number_format($total_revenue, 2) : '0.00'; ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Users</h2>
                        <a href="users.php" class="btn btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_users)): ?>
                            <div class="no-data">
                                <i class="fas fa-users"></i>
                                <p>No users found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_users as $user): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <div class="list-item-title"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="list-item-subtitle">
                                            <?php echo htmlspecialchars($user['email']); ?> | 
                                            <?php echo isset($user['role']) ? ucfirst($user['role']) : 'User'; ?> | 
                                            Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-sm">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Bookings</h2>
                        <a href="bookings.php" class="btn btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_bookings)): ?>
                            <div class="no-data">
                                <i class="fas fa-calendar-check"></i>
                                <p>No bookings found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <div class="list-item-title"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                        <div class="list-item-subtitle">
                                            Client: <?php echo htmlspecialchars($booking['client_name']); ?> | 
                                            Technician: <?php echo htmlspecialchars($booking['technician_name']); ?> | 
                                            Date: <?php echo date('M d, Y', strtotime($booking['preferred_date'])); ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Top Technicians</h2>
                        <a href="technicians.php" class="btn btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_technicians)): ?>
                            <div class="no-data">
                                <i class="fas fa-user-md"></i>
                                <p>No technicians found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_technicians as $tech): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <div class="list-item-title"><?php echo htmlspecialchars($tech['name']); ?></div>
                                        <div class="list-item-subtitle">
                                            <?php echo htmlspecialchars($tech['specialization']); ?> | 
                                            <?php echo $tech['total_jobs']; ?> jobs completed
                                        </div>
                                        <div class="rating">
                                            <?php
                                            $rating = round($tech['rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                            <span>(<?php echo $tech['rating']; ?>/5)</span>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="technicians.php?edit=<?php echo $tech['id']; ?>" class="btn btn-sm">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Top Products</h2>
                        <a href="products.php" class="btn btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_products)): ?>
                            <div class="no-data">
                                <i class="fas fa-box"></i>
                                <p>No products found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                                <div class="list-item">
                                    <div class="list-item-info">
                                        <div class="list-item-title"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="list-item-subtitle">
                                            <?php echo htmlspecialchars($product['category']); ?> | 
                                            K<?php echo number_format($product['price'], 2); ?> | 
                                            <?php echo isset($product['order_count']) ? $product['order_count'] : 0; ?> orders
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm">Edit</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Booking Statistics</h2>
                </div>
                <div class="card-body">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <h3 style="color: var(--warning-color);"><?php echo isset($pending_bookings) ? $pending_bookings : 0; ?></h3>
                            <p>Pending</p>
                        </div>
                        <div>
                            <h3 style="color: var(--info-color);"><?php echo isset($total_bookings) && isset($pending_bookings) && isset($completed_bookings) ? ($total_bookings - $pending_bookings - $completed_bookings) : 0; ?></h3>
                            <p>In Progress</p>
                        </div>
                        <div>
                            <h3 style="color: var(--success-color);"><?php echo isset($completed_bookings) ? $completed_bookings : 0; ?></h3>
                            <p>Completed</p>
                        </div>
                        <div>
                            <h3 style="color: var(--primary-color);"><?php echo isset($total_bookings) ? $total_bookings : 0; ?></h3>
                            <p>Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
