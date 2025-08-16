<?php
session_start();
include('includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information with fallbacks for missing session variables
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
$user_type = $_SESSION['user_type'] ?? 'user';

// Ensure required tables exist
try {
    // Check if service_updates table exists, create if not
    $checkTable = $pdo->query("SHOW TABLES LIKE 'service_updates'");
    if ($checkTable->rowCount() == 0) {
        $createTable = "
            CREATE TABLE service_updates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service_request_id INT NOT NULL,
                update_text TEXT NOT NULL,
                status VARCHAR(50) DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_by_type ENUM('admin', 'technician', 'user') DEFAULT 'technician',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_service_request_id (service_request_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($createTable);
    }
} catch (PDOException $e) {
    // If table creation fails, we'll handle it in PerformanceManager
    error_log("Could not create service_updates table: " . $e->getMessage());
}

// Initialize performance manager for optimized queries
$performance_file = __DIR__ . '/includes/PerformanceManager.php';
if (!file_exists($performance_file)) {
    die("PerformanceManager.php file not found at: " . $performance_file);
}

require_once $performance_file;

if (!class_exists('PerformanceManager')) {
    die("PerformanceManager class not found after including file");
}

$performance = new PerformanceManager($pdo);

// Get optimized dashboard data
$service_requests_with_updates = $performance->getDashboardData($user_id, $user_email);

// Prepare data for display
$service_updates = [];
$service_requests = [];

foreach ($service_requests_with_updates as $request) {
    $service_requests[] = [
        'id' => $request['id'],
        'reference_number' => $request['reference_number'],
        'service_type' => $request['service_type'],
        'status' => $request['current_status'],
        'request_date' => $request['request_date']
    ];
    
    if ($request['latest_update']) {
        $service_updates[$request['id']] = [
            'message' => $request['latest_update'],
            'status' => $request['current_status'],
            'created_at' => $request['update_date']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFix Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        
        /* Navigation */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 2rem;
            position: fixed;
            top: 0;
            width: 100%;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .navbar-brand span {
            margin-left: 0.5rem;
        }
        
        .navbar-nav {
            display: flex;
            list-style: none;
        }
        
        .nav-item {
            margin-left: 1.5rem;
        }
        
        .nav-link {
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .nav-link:hover {
            color: var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 0.5rem;
        }
        
        .user-dropdown {
            position: relative;
            cursor: pointer;
        }
        
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            color: var(--dark-color);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-weight: bold;
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            box-shadow: var(--card-shadow);
            border-radius: 8px;
            width: 200px;
            display: none;
            z-index: 1001;
        }
        
        .user-dropdown-menu.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .dropdown-item:hover {
            background-color: var(--light-color);
        }
        
        .dropdown-item i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 0.5rem 0;
        }
        
        .logout-item {
            color: var(--danger-color);
        }
        
        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-title h1 {
            font-size: 2rem;
            color: var(--dark-color);
        }
        
        .dashboard-title p {
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.2);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
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
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
        }
        
        .stat-info p {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        /* Service Cards */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .service-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            text-align: center;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .service-card h3 {
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }
        
        .service-card p {
            color: var(--secondary-color);
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }
        
        .service-card .btn {
            width: 100%;
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
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .view-all:hover {
            color: var(--primary-dark);
        }
        
        /* Tables */
        .table-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
        }
        
        th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
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
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
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
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .view-btn {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
        }
        
        .view-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2.5rem;
        }
        
        .empty-icon {
            font-size: 3.5rem;
            color: #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .empty-state p {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Footer */
        .dashboard-footer {
            background-color: white;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: 3rem;
            border-top: 1px solid #e9ecef;
        }
        
        .dashboard-footer p {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.7rem 1rem;
            }
            
            .nav-item {
                margin-left: 1rem;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-actions {
                margin-top: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            th, td {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-tools"></i>
            <span>SmartFix</span>
        </a>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="services.php" class="nav-link">
                    <i class="fas fa-wrench"></i> Services
                </a>
            </li>
            <li class="nav-item">
                <a href="shop.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Shop
                </a>
            </li>
            <li class="nav-item">
                <a href="emergency.php" class="nav-link">
                    <i class="fas fa-ambulance"></i> Emergency
                </a>
            </li>
            <li class="nav-item user-dropdown">
                <div class="user-dropdown-toggle" onclick="toggleDropdown()">
                    <div class="user-avatar">
                        <?php echo substr(htmlspecialchars($user_name), 0, 1); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down" style="margin-left: 5px;"></i>
                </div>
                
                <div class="user-dropdown-menu" id="userDropdown">
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="account_settings.php" class="dropdown-item">
                        <i class="fas fa-user-cog"></i> Account Settings
                    </a>
                    <a href="services/track_service.php" class="dropdown-item">
                        <i class="fas fa-clipboard-list"></i> My Services
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p>Here's an overview of your activity and services</p>
                </div>
                
                <div class="dashboard-actions">
                    <a href="services.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Service Request
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($service_requests); ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                            $completed = 0;
                            foreach($service_requests as $request) {
                                if(($request['status'] ?? '') == 'completed') $completed++;
                            }
                            echo $completed;
                        ?></h3>
                        <p>Completed Services</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                            $pending = 0;
                            foreach($service_requests as $request) {
                                if(($request['status'] ?? '') == 'pending') $pending++;
                            }
                            echo $pending;
                        ?></h3>
                        <p>Pending Services</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                            $in_progress = 0;
                            foreach($service_requests as $request) {
                                if(($request['status'] ?? '') == 'in_progress') $in_progress++;
                            }
                            echo $in_progress;
                        ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="section-title">
                <h2>Quick Actions</h2>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-wrench"></i>
                    </div>
                    <h3>Request Repair</h3>
                    <p>Submit a new repair request for your device or vehicle</p>
                    <a href="services.php" class="btn btn-primary">Get Started</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Shop Parts</h3>
                    <p>Browse our collection of quality spare parts and accessories</p>
                    <a href="shop.php" class="btn btn-primary">Shop Now</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-ambulance"></i>
                    </div>
                    <h3>Emergency Service</h3>
                    <p>Need urgent help? Request our priority emergency service</p>
                    <a href="emergency.php" class="btn btn-primary">Emergency Help</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Account Settings</h3>
                    <p>Update your profile information and preferences</p>
                    <a href="account_settings.php" class="btn btn-primary">Manage Account</a>
                </div>
            </div>
        
            <!-- Service Tracking Widget -->
            <?php include('includes/service_tracking_widget.php'); ?>
            
            <!-- Recommended Services -->
            <div class="section-title">
                <h2>Recommended for You</h2>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Phone Screen Repair</h3>
                    <p>Fix cracked screens, touch issues, and display problems</p>
                    <a href="services.php?type=phone&service=screen" class="btn btn-outline">Learn More</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-laptop"></i>
                    </div>
                    <h3>Computer Tune-Up</h3>
                    <p>Speed up your slow computer with our optimization service</p>
                    <a href="services.php?type=computer&service=tuneup" class="btn btn-outline">Learn More</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Vehicle Maintenance</h3>
                    <p>Regular maintenance to keep your vehicle running smoothly</p>
                    <a href="services.php?type=auto&service=maintenance" class="btn btn-outline">Learn More</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> SmartFix. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Toggle user dropdown menu
        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.user-dropdown-toggle') && 
                !event.target.matches('.user-dropdown-toggle *')) {
                var dropdown = document.getElementById('userDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
</body>
</html>