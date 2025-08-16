<?php
session_start();
include('../includes/db.php');

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../login.php?redirect=technician/dashboard.php');
    exit;
}

// Get technician information
$technician = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$technician) {
        // Technician profile doesn't exist yet
        $create_profile = true;
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}

// Get pending bookings
$pending_bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.technician_id = ? AND b.status = 'pending'
        ORDER BY b.preferred_date ASC
    ");
    $stmt->execute([$technician['id']]);
    $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get active bookings
$active_bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.technician_id = ? AND b.status = 'confirmed'
        ORDER BY b.preferred_date ASC
    ");
    $stmt->execute([$technician['id']]);
    $active_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get completed bookings
$completed_bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email,
               r.rating, r.comment
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN reviews r ON r.booking_id = b.id
        WHERE b.technician_id = ? AND b.status = 'completed'
        ORDER BY b.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute([$technician['id']]);
    $completed_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Process booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ? AND technician_id = ?");
        $stmt->execute([$status, $booking_id, $technician['id']]);
        
        // If completing a job, update technician stats
        if ($status === 'completed') {
            $stmt = $pdo->prepare("UPDATE technicians SET total_jobs = total_jobs + 1 WHERE id = ?");
            $stmt->execute([$technician['id']]);
        }
        
        // Redirect to refresh the page
        header('Location: dashboard.php?success=Booking updated successfully');
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

// Calculate earnings (simplified example)
$earnings = [
    'today' => 0,
    'week' => 0,
    'month' => 0,
    'total' => 0
];

try {
    // Today's earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE technician_id = ? AND DATE(payment_date) = CURDATE()
    ");
    $stmt->execute([$technician['id']]);
    $earnings['today'] = $stmt->fetchColumn();
    
    // This week's earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE technician_id = ? AND YEARWEEK(payment_date, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $stmt->execute([$technician['id']]);
    $earnings['week'] = $stmt->fetchColumn();
    
    // This month's earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE technician_id = ? AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())
    ");
    $stmt->execute([$technician['id']]);
    $earnings['month'] = $stmt->fetchColumn();
    
    // Total earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE technician_id = ?
    ");
    $stmt->execute([$technician['id']]);
    $earnings['total'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Payments table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - SmartFix</title>
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
        }
        
        .stat-card i {
            font-size: 36px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 24px;
            color: var(--primary-color);
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
        
        .booking-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .booking-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .booking-title {
            font-weight: 600;
            font-size: 16px;
        }
        
        .booking-date {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .booking-details {
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .booking-details p {
            margin: 5px 0;
        }
        
        .booking-actions {
            display: flex;
            gap: 10px;
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
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
            margin-top: 5px;
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
                <p>Technician Portal</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="earnings.php"><i class="fas fa-money-bill-wave"></i> Earnings</a></li>
                <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Main Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Technician Dashboard</h1>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="Profile">
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($create_profile)): ?>
                <div class="alert alert-warning">
                    <p>Your technician profile is not complete. Please <a href="profile.php">complete your profile</a> to start receiving bookings.</p>
                </div>
            <?php else: ?>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <i class="fas fa-tools"></i>
                        <h3><?php echo $technician['total_jobs']; ?></h3>
                        <p>Jobs Completed</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-star"></i>
                        <h3><?php echo number_format($technician['rating'], 1); ?></h3>
                        <p>Average Rating</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt"></i>
                        <h3><?php echo count($pending_bookings); ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                    
                    <div class="stat-card">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>K<?php echo number_format($earnings['month'], 2); ?></h3>
                        <p>This Month's Earnings</p>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <h2>Pending Bookings</h2>
                            <a href="bookings.php?status=pending" class="btn">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_bookings)): ?>
                                <div class="no-data">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No pending bookings at the moment.</p>
                                </div>
                            <?php else: ?>
                                <ul class="booking-list">
                                    <?php foreach (array_slice($pending_bookings, 0, 3) as $booking): ?>
                                        <li class="booking-item">
                                            <div class="booking-header">
                                                <div class="booking-title"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                                <div class="booking-date">
                                                    <span class="badge badge-pending">Pending</span>
                                                </div>
                                            </div>
                                            <div class="booking-details">
                                                <p><strong>Client:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['preferred_date'])); ?> (<?php echo htmlspecialchars($booking['preferred_time']); ?>)</p>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['address']); ?></p>
                                            </div>
                                            <div class="booking-actions">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" name="update_booking" class="btn btn-success">Accept</button>
                                                </form>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" name="update_booking" class="btn btn-danger">Decline</button>
                                                </form>
                                                <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn">Details</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2>Active Jobs</h2>
                            <a href="bookings.php?status=confirmed" class="btn">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($active_bookings)): ?>
                                <div class="no-data">
                                    <i class="fas fa-briefcase"></i>
                                    <p>No active jobs at the moment.</p>
                                </div>
                            <?php else: ?>
                                <ul class="booking-list">
                                    <?php foreach (array_slice($active_bookings, 0, 3) as $booking): ?>
                                        <li class="booking-item">
                                            <div class="booking-header">
                                                <div class="booking-title"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                                <div class="booking-date">
                                                    <span class="badge badge-confirmed">Confirmed</span>
                                                </div>
                                            </div>
                                            <div class="booking-details">
                                                <p><strong>Client:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($booking['preferred_date'])); ?> (<?php echo htmlspecialchars($booking['preferred_time']); ?>)</p>
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['address']); ?></p>
                                            </div>
                                            <div class="booking-actions">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" name="update_booking" class="btn btn-success">Mark Complete</button>
                                                </form>
                                                <a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn">Details</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Completed Jobs</h2>
                        <a href="bookings.php?status=completed" class="btn">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($completed_bookings)): ?>
                            <div class="no-data">
                                <i class="fas fa-clipboard-check"></i>
                                <p>No completed jobs yet.</p>
                            </div>
                        <?php else: ?>
                            <ul class="booking-list">
                                <?php foreach (array_slice($completed_bookings, 0, 5) as $booking): ?>
                                    <li class="booking-item">
                                        <div class="booking-header">
                                            <div class="booking-title"><?php echo htmlspecialchars($booking['service_type']); ?></div>
                                            <div class="booking-date">
                                                <span class="badge badge-completed">Completed</span>
                                                <?php echo date('M d, Y', strtotime($booking['updated_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="booking-details">
                                            <p><strong>Client:</strong> <?php echo htmlspecialchars($booking['client_name']); ?></p>
                                            <?php if (isset($booking['rating'])): ?>
                                                <div class="rating">
                                                    <?php
                                                    $rating = round($booking['rating']);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star"></i>';
                                                        }
                                                    }
                                                    ?>
                                                    <span>(<?php echo $booking['rating']; ?>/5)</span>
                                                </div>
                                                <?php if (!empty($booking['comment'])): ?>
                                                    <p><em>"<?php echo htmlspecialchars($booking['comment']); ?>"</em></p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p><em>No rating yet</em></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>