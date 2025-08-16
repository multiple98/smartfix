<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Get admin information
$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get counts for dashboard
// Users count - count all users from users table
try {
    $users_query = "SELECT COUNT(*) as count FROM users";
    $users_stmt = $pdo->prepare($users_query);
    $users_stmt->execute();
    $users_count = $users_stmt->fetchColumn();
} catch (PDOException $e) {
    $users_count = 0;
}

// Technicians count - try technicians table first, then fallback to users with user_type
try {
    // First try to count from separate technicians table
    $techs_query = "SELECT COUNT(*) as count FROM technicians";
    $techs_stmt = $pdo->prepare($techs_query);
    $techs_stmt->execute();
    $techs_count = $techs_stmt->fetchColumn();
} catch (PDOException $e) {
    // Fallback: try to find user_type column in users table
    try {
        $techs_query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'technician'";
        $techs_stmt = $pdo->prepare($techs_query);
        $techs_stmt->execute();
        $techs_count = $techs_stmt->fetchColumn();
    } catch (PDOException $e2) {
        // If neither works, set to 0
        $techs_count = 0;
    }
}

// Service requests count
try {
    $requests_query = "SELECT COUNT(*) as count FROM service_requests";
    $requests_stmt = $pdo->prepare($requests_query);
    $requests_stmt->execute();
    $requests_count = $requests_stmt->fetchColumn();
} catch (PDOException $e) {
    $requests_count = 0;
}

// Pending requests count
try {
    $pending_query = "SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'";
    $pending_stmt = $pdo->prepare($pending_query);
    $pending_stmt->execute();
    $pending_count = $pending_stmt->fetchColumn();
} catch (PDOException $e) {
    $pending_count = 0;
}

// Completed requests count
try {
    $completed_query = "SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed'";
    $completed_stmt = $pdo->prepare($completed_query);
    $completed_stmt->execute();
    $completed_count = $completed_stmt->fetchColumn();
} catch (PDOException $e) {
    $completed_count = 0;
}

// Check if technician_id column exists in service_requests table
$check_column_query = "SHOW COLUMNS FROM service_requests LIKE 'technician_id'";
$check_column_stmt = $pdo->prepare($check_column_query);
$check_column_stmt->execute();
$technician_id_exists = $check_column_stmt->rowCount() > 0;

// Check if request_date column exists in service_requests table
$check_date_query = "SHOW COLUMNS FROM service_requests LIKE 'request_date'";
$check_date_stmt = $pdo->prepare($check_date_query);
$check_date_stmt->execute();
$request_date_exists = $check_date_stmt->rowCount() > 0;

// Check if created_at column exists in service_requests table
$check_created_query = "SHOW COLUMNS FROM service_requests LIKE 'created_at'";
$check_created_stmt = $pdo->prepare($check_created_query);
$check_created_stmt->execute();
$created_at_exists = $check_created_stmt->rowCount() > 0;

// Get recent service requests
$order_by = "";
if ($request_date_exists) {
    $order_by = "ORDER BY sr.request_date DESC";
} elseif ($created_at_exists) {
    $order_by = "ORDER BY sr.created_at DESC";
} else {
    $order_by = "ORDER BY sr.id DESC"; // Fallback to ID if no date columns exist
}

if ($technician_id_exists) {
    $recent_query = "SELECT sr.*, u.name as technician_name 
                    FROM service_requests sr
                    LEFT JOIN users u ON sr.technician_id = u.id
                    $order_by LIMIT 5";
} else {
    // If technician_id doesn't exist, just get the service requests without joining
    $recent_query = "SELECT sr.* 
                    FROM service_requests sr
                    $order_by LIMIT 5";
}
$recent_stmt = $pdo->prepare($recent_query);
$recent_stmt->execute();
$recent_requests = $recent_stmt->fetchAll();

// Get unread notifications count
try {
    $notifications_query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
    $notifications_stmt = $pdo->prepare($notifications_query);
    $notifications_stmt->execute();
    $notifications_count = $notifications_stmt->fetchColumn();
} catch (PDOException $e) {
    // If notifications table doesn't exist, set count to 0
    $notifications_count = 0;
}

// Get recent notifications for display
try {
    $recent_notifications_query = "SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5";
    $recent_notifications_stmt = $pdo->prepare($recent_notifications_query);
    $recent_notifications_stmt->execute();
    $recent_notifications = $recent_notifications_stmt->fetchAll();
} catch (PDOException $e) {
    $recent_notifications = [];
}

// Get today's service requests count with error handling
try {
    $today_requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE DATE(COALESCE(request_date, created_at, NOW())) = CURDATE()";
    $today_requests_stmt = $pdo->prepare($today_requests_query);
    $today_requests_stmt->execute();
    $today_requests_count = $today_requests_stmt->fetchColumn();
} catch (PDOException $e) {
    // Fallback query if columns don't exist
    try {
        $today_requests_count = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
    } catch (PDOException $e2) {
        $today_requests_count = 0;
    }
}

// Get analytics data for charts
// Service requests trend (last 7 days)
try {
    $trend_query = "SELECT DATE(COALESCE(request_date, created_at, NOW())) as request_day, COUNT(*) as count 
                   FROM service_requests 
                   WHERE DATE(COALESCE(request_date, created_at, NOW())) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                   GROUP BY DATE(COALESCE(request_date, created_at, NOW()))
                   ORDER BY request_day ASC";
    $trend_stmt = $pdo->prepare($trend_query);
    $trend_stmt->execute();
    $trend_data = $trend_stmt->fetchAll();
} catch (PDOException $e) {
    $trend_data = [];
}

// Service types distribution
try {
    $types_query = "SELECT service_type, COUNT(*) as count FROM service_requests GROUP BY service_type ORDER BY count DESC LIMIT 10";
    $types_stmt = $pdo->prepare($types_query);
    $types_stmt->execute();
    $service_types_data = $types_stmt->fetchAll();
} catch (PDOException $e) {
    $service_types_data = [];
}

// Status distribution
try {
    $status_query = "SELECT status, COUNT(*) as count FROM service_requests GROUP BY status";
    $status_stmt = $pdo->prepare($status_query);
    $status_stmt->execute();
    $status_data = $status_stmt->fetchAll();
} catch (PDOException $e) {
    $status_data = [];
}

// Transport statistics
$transport_stats = [];
try {
    // Total transport providers
    $transport_providers_query = "SELECT COUNT(*) as count FROM transport_providers";
    $transport_providers_stmt = $pdo->prepare($transport_providers_query);
    $transport_providers_stmt->execute();
    $transport_stats['total_providers'] = $transport_providers_stmt->fetchColumn();
} catch (PDOException $e) {
    $transport_stats['total_providers'] = 0;
}

try {
    // Check if status column exists in transport_providers
    $check_status_column = "SHOW COLUMNS FROM transport_providers LIKE 'status'";
    $status_check = $pdo->prepare($check_status_column);
    $status_check->execute();
    
    if ($status_check->rowCount() > 0) {
        // Status column exists, use it
        $active_providers_query = "SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'";
    } else {
        // Status column doesn't exist, count all providers
        $active_providers_query = "SELECT COUNT(*) as count FROM transport_providers";
    }
    
    $active_providers_stmt = $pdo->prepare($active_providers_query);
    $active_providers_stmt->execute();
    $transport_stats['active_providers'] = $active_providers_stmt->fetchColumn();
} catch (PDOException $e) {
    $transport_stats['active_providers'] = 0;
}

try {
    // Total transport quotes
    $transport_quotes_query = "SELECT COUNT(*) as count FROM transport_quotes";
    $transport_quotes_stmt = $pdo->prepare($transport_quotes_query);
    $transport_quotes_stmt->execute();
    $transport_stats['total_quotes'] = $transport_quotes_stmt->fetchColumn();
} catch (PDOException $e) {
    $transport_stats['total_quotes'] = 0;
}

try {
    // Check if delivery_tracking table exists
    $check_delivery_table = "SHOW TABLES LIKE 'delivery_tracking'";
    $delivery_check = $pdo->prepare($check_delivery_table);
    $delivery_check->execute();
    
    if ($delivery_check->rowCount() > 0) {
        // Table exists, check for status column
        $check_delivery_status = "SHOW COLUMNS FROM delivery_tracking LIKE 'status'";
        $delivery_status_check = $pdo->prepare($check_delivery_status);
        $delivery_status_check->execute();
        
        if ($delivery_status_check->rowCount() > 0) {
            $pending_deliveries_query = "SELECT COUNT(*) as count FROM delivery_tracking WHERE status IN ('pickup_scheduled', 'in_transit', 'out_for_delivery')";
        } else {
            $pending_deliveries_query = "SELECT COUNT(*) as count FROM delivery_tracking";
        }
        
        $pending_deliveries_stmt = $pdo->prepare($pending_deliveries_query);
        $pending_deliveries_stmt->execute();
        $transport_stats['pending_deliveries'] = $pending_deliveries_stmt->fetchColumn();
    } else {
        $transport_stats['pending_deliveries'] = 0;
    }
} catch (PDOException $e) {
    $transport_stats['pending_deliveries'] = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #007BFF;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --body-bg: #f0f2f5;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--body-bg);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        /* Admin Sidebar */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .sidebar-logo i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .menu-divider {
            height: 1px;
            background-color: rgba(255,255,255,0.1);
            margin: 0.5rem 0;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title h1 {
            font-size: 1.75rem;
            color: var(--dark-color);
        }
        
        .page-title p {
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }
        
        .notification-badge {
            position: relative;
            display: inline-block;
        }
        
        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
        }
        
        .stat-icon.blue {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
        }
        
        .stat-icon.green {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .stat-icon.orange {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .stat-icon.red {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .stat-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .stat-info p {
            color: var(--secondary-color);
            font-size: 0.85rem;
        }
        
        /* Quick Access Grid */
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .quick-access-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }
        
        .quick-access-card:hover {
            transform: translateY(-5px);
        }
        
        .quick-access-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .quick-access-card h3 {
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }
        
        .quick-access-card p {
            color: var(--secondary-color);
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }
        
        .quick-access-card a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .quick-access-card a:hover {
            color: var(--primary-dark);
        }
        
        /* Section Titles */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 2.5rem 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-title h2 {
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .view-all:hover {
            color: var(--primary-dark);
        }
        
        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        
        th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:last-child {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: rgba(0, 123, 255, 0.03);
        }
        
        .status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .status-in_progress {
            background-color: rgba(23, 162, 184, 0.15);
            color: var(--info-color);
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .action-btn {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: var(--transition);
            margin-right: 0.25rem;
        }
        
        .view-btn {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
        }
        
        .view-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Chart Container */
        .chart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .chart-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-placeholder {
            width: 100%;
            height: 300px;
            background-color: var(--light-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .btn-outline:hover, .btn-outline.active-period {
            background-color: var(--primary-color);
            color: white;
        }
        
        canvas {
            max-height: 300px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
                overflow: visible;
            }
            
            .sidebar-header {
                padding: 1rem;
            }
            
            .sidebar-logo span {
                display: none;
            }
            
            .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
                padding: 0.75rem;
            }
            
            .menu-item i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 9999;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
                position: relative;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 10000;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(0,123,255,0.3);
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .quick-access-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .page-title {
                margin-left: 60px;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9998;
            }
            
            .overlay.active {
                display: block;
            }
        }
        
        .mobile-menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Mobile Overlay -->
        <div class="overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="sidebar-logo">
                    <i class="fas fa-tools"></i>
                    <span>SmartFix Admin</span>
                </a>
            </div>
            
            <div class="sidebar-menu">
                <a href="admin_dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="manage_users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                
                <a href="technicians.php" class="menu-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Technicians</span>
                </a>
                
                <a href="service_requests_enhanced.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Service Requests</span>
                </a>
                
                <a href="manage_products.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Manage Products</span>
                </a>
                
                <a href="transport_dashboard.php" class="menu-item">
                    <i class="fas fa-truck"></i>
                    <span>Transport Management</span>
                </a>
                
                <a href="emergency_dashboard.php" class="menu-item">
                    <i class="fas fa-ambulance"></i>
                    <span>Emergency Services</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
                
                <a href="reports_enhanced.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics & Reports</span>
                </a>
                
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h1>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h1>
                    <p>Here's what's happening with SmartFix today</p>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="admin_quick_panel.php" class="btn btn-outline" style="text-decoration: none; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem;">
                        <i class="fas fa-bolt"></i> Quick Panel
                    </a>
                    <div class="notification-badge">
                        <a href="admin_notifications.php" style="color: var(--dark-color); font-size: 1.25rem;">
                            <i class="fas fa-bell"></i>
                            <?php if ($notifications_count > 0): ?>
                                <span class="badge"><?php echo $notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $users_count; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $techs_count; ?></h3>
                        <p>Technicians</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $requests_count; ?></h3>
                        <p>Service Requests</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_count; ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $completed_count; ?></h3>
                        <p>Completed Services</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today_requests_count; ?></h3>
                        <p>Today's Requests</p>
                    </div>
                </div>
                
                <?php if ($notifications_count > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $notifications_count; ?></h3>
                        <p>New Notifications</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Transport Statistics -->
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $transport_stats['total_providers']; ?></h3>
                        <p>Transport Providers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $transport_stats['active_providers']; ?></h3>
                        <p>Active Providers</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $transport_stats['total_quotes']; ?></h3>
                        <p>Transport Quotes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $transport_stats['pending_deliveries']; ?></h3>
                        <p>Pending Deliveries</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Access -->
            <div class="section-title">
                <h2>Quick Access</h2>
            </div>
            
            <div class="quick-access-grid">
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Add New User</h3>
                    <p>Create a new user account</p>
                    <a href="manage_users.php">Manage Users <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Assign Technician</h3>
                    <p>Assign technicians to service requests</p>
                    <a href="service_requests_enhanced.php">View Requests <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Manage Products</h3>
                    <p>Add or edit products in the shop</p>
                    <a href="manage_products.php">Manage Shop <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-ambulance"></i>
                    </div>
                    <h3>Emergency Services</h3>
                    <p>Manage urgent service requests</p>
                    <a href="emergency_dashboard.php">View Emergencies <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>View Reports</h3>
                    <p>Access analytics and reports</p>
                    <a href="reports_enhanced.php">View Reports <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Transport Management</h3>
                    <p>Manage delivery providers and quotes</p>
                    <a href="transport_dashboard.php">Manage Transport <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            
            <!-- System Tools -->
            <div class="section-title">
                <h2>System Tools</h2>
            </div>
            
            <div class="quick-access-grid">
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-vial"></i>
                    </div>
                    <h3>Transport Integration Test</h3>
                    <p>Test transport system integration</p>
                    <a href="test_transport_integration.php">Run Tests <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Initialize Transport System</h3>
                    <p>Set up transport database tables</p>
                    <a href="../enhanced_transport_system.php">Initialize System <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h3>Database Fixes</h3>
                    <p>Fix missing database columns</p>
                    <a href="../complete_orders_table_fix.php">Fix Database <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-truck-loading"></i>
                    </div>
                    <h3>Fix Transport Database</h3>
                    <p>Fix transport system database issues</p>
                    <a href="../fix_transport_database_complete.php">Fix Transport DB <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>Emergency Column Fix</h3>
                    <p>Quick fix for missing address column</p>
                    <a href="../emergency_fix_address_column.php">Emergency Fix <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Diagnose Transport Tables</h3>
                    <p>Check transport database structure</p>
                    <a href="../diagnose_transport_tables.php">Diagnose Tables <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Quick Column Fix</h3>
                    <p>Add missing columns immediately</p>
                    <a href="../quick_fix_missing_columns.php">Quick Fix <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Test Transport Selection</h3>
                    <p>Test transport selection integration</p>
                    <a href="../test_transport_selection.php">Test Integration <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>Fix Order Tracking</h3>
                    <p>Fix timestamp column issues</p>
                    <a href="../fix_order_tracking_table.php">Fix Tracking <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="quick-access-card">
                    <div class="quick-access-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Fix All Timestamp Issues</h3>
                    <p>Comprehensive timestamp fix</p>
                    <a href="../fix_all_timestamp_issues.php">Fix All Timestamps <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            
            <!-- Recent Notifications -->
            <?php if (!empty($recent_notifications)): ?>
            <div class="section-title">
                <h2>Recent Notifications</h2>
                <a href="notifications.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="table-container" style="margin-bottom: 2rem;">
                <div style="padding: 1rem;">
                    <?php foreach ($recent_notifications as $notification): ?>
                    <div style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e9ecef;">
                        <div style="margin-right: 1rem;">
                            <?php if ($notification['type'] == 'service_request'): ?>
                                <i class="fas fa-clipboard-list" style="color: #007BFF;"></i>
                            <?php elseif ($notification['type'] == 'system'): ?>
                                <i class="fas fa-cog" style="color: #6c757d;"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle" style="color: #17a2b8;"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1;">
                            <?php if ($notification['title']): ?>
                                <h5 style="margin: 0 0 0.25rem; font-size: 0.9rem;"><?php echo htmlspecialchars($notification['title']); ?></h5>
                            <?php endif; ?>
                            <p style="margin: 0; color: #666; font-size: 0.85rem;"><?php echo htmlspecialchars($notification['message']); ?></p>
                        </div>
                        <div style="margin-left: 1rem; color: #999; font-size: 0.8rem;">
                            <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recent Service Requests -->
            <div class="section-title">
                <h2>Recent Service Requests</h2>
                <a href="service_requests.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Service Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Technician</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_requests) > 0): ?>
                            <?php foreach ($recent_requests as $request): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($request['name']); ?></div>
                                        <div style="font-size: 0.8rem; color: var(--secondary-color);">
                                            <?php echo htmlspecialchars($request['email']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['service_type']); ?></td>
                                    <td>
                                        <?php 
                                        if ($request_date_exists && isset($request['request_date'])) {
                                            echo date('M d, Y', strtotime($request['request_date']));
                                        } elseif ($created_at_exists && isset($request['created_at'])) {
                                            echo date('M d, Y', strtotime($request['created_at']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $request['status'] ?? 'pending';
                                        $status_class = 'status-' . $status;
                                        echo "<span class='status $status_class'>" . ucfirst($status) . "</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($technician_id_exists && isset($request['technician_name'])) {
                                            echo htmlspecialchars($request['technician_name']);
                                        } else {
                                            echo 'Not Assigned';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="service_requests.php?id=<?php echo $request['id']; ?>" class="action-btn view-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <div style="color: var(--secondary-color);">
                                        <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                        <p>No service requests found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Analytics Charts -->
            <div class="section-title">
                <h2>Analytics Overview</h2>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Service Requests Trend (Last 7 Days)</div>
                    <div class="chart-actions">
                        <button class="btn btn-outline active-period" onclick="changeChartPeriod('week')">Week</button>
                        <button class="btn btn-outline" onclick="changeChartPeriod('month')">Month</button>
                        <button class="btn btn-outline" onclick="changeChartPeriod('year')">Year</button>
                    </div>
                </div>
                <canvas id="requestsTrendChart" width="400" height="200"></canvas>
            </div>
            
            <div class="stats-grid">
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Service Types Distribution</div>
                    </div>
                    <canvas id="serviceTypesChart" width="300" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Request Status Overview</div>
                    </div>
                    <canvas id="statusChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Chart data from PHP
        const trendData = <?php echo json_encode($trend_data); ?>;
        const serviceTypesData = <?php echo json_encode($service_types_data); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        
        let requestsTrendChart, serviceTypesChart, statusChart;
        
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
        });
        
        function initializeCharts() {
            // Requests Trend Chart
            const trendCtx = document.getElementById('requestsTrendChart').getContext('2d');
            
            // Prepare trend data
            const last7Days = [];
            const trendCounts = [];
            
            // Fill last 7 days
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                last7Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                // Find count for this date
                const found = trendData.find(item => item.request_day === dateStr);
                trendCounts.push(found ? parseInt(found.count) : 0);
            }
            
            requestsTrendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [{
                        label: 'Service Requests',
                        data: trendCounts,
                        borderColor: '#007BFF',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Service Types Chart
            if (serviceTypesData.length > 0) {
                const typesCtx = document.getElementById('serviceTypesChart').getContext('2d');
                const colors = ['#007BFF', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d', '#e83e8c', '#20c997'];
                
                serviceTypesChart = new Chart(typesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: serviceTypesData.map(item => item.service_type || 'Unknown'),
                        datasets: [{
                            data: serviceTypesData.map(item => parseInt(item.count)),
                            backgroundColor: colors.slice(0, serviceTypesData.length),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }
            
            // Status Chart
            if (statusData.length > 0) {
                const statusCtx = document.getElementById('statusChart').getContext('2d');
                const statusColors = {
                    'pending': '#ffc107',
                    'in_progress': '#17a2b8',
                    'completed': '#28a745',
                    'cancelled': '#dc3545'
                };
                
                statusChart = new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: statusData.map(item => {
                            const status = item.status || 'unknown';
                            return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
                        }),
                        datasets: [{
                            data: statusData.map(item => parseInt(item.count)),
                            backgroundColor: statusData.map(item => statusColors[item.status] || '#6c757d'),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Change chart period
        function changeChartPeriod(period) {
            // Update button states
            document.querySelectorAll('.chart-actions .btn-outline').forEach(btn => {
                btn.classList.remove('active-period');
            });
            event.target.classList.add('active-period');
            
            // Update chart title
            const chartTitle = document.querySelector('.chart-title');
            const periodText = {
                'week': 'Last 7 Days',
                'month': 'Last 30 Days', 
                'year': 'Last 12 Months'
            };
            chartTitle.textContent = `Service Requests Trend (${periodText[period]})`;
            
            // In a real implementation, you would fetch new data here
            // For now, we'll show an alert
            console.log(`Chart period changed to ${period}`);
        }
        
        // Add some interactivity
        function refreshDashboard() {
            location.reload();
        }
        
        // Auto-refresh every 5 minutes (optional)
        setInterval(refreshDashboard, 300000);
        
        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.querySelector('.overlay');
            const toggle = document.querySelector('.mobile-menu-toggle i');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Change icon
            if (sidebar.classList.contains('active')) {
                toggle.className = 'fas fa-times';
            } else {
                toggle.className = 'fas fa-bars';
            }
        }
        
        // Close mobile menu when clicking on menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(toggleMobileMenu, 100);
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('adminSidebar');
                const overlay = document.querySelector('.overlay');
                const toggle = document.querySelector('.mobile-menu-toggle i');
                
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggle.className = 'fas fa-bars';
            }
        });
    </script>
</body>
</html>