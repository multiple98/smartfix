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

// Get user's service requests with reference numbers
$service_query = "SELECT * FROM service_requests WHERE email = ? OR contact = ? ORDER BY request_date DESC";
$stmt = $pdo->prepare($service_query);
$stmt->execute([$user_email, $user_email]);
$service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the latest update for each service request
$service_updates = [];
if (count($service_requests) > 0) {
    try {
        // Create a comma-separated list of request IDs
        $request_ids = array_map(function($req) { return $req['id']; }, $service_requests);
        $id_list = implode(',', $request_ids);
        
        if (!empty($id_list)) {
            // Get the latest update for each service request
            $updates_query = "SELECT su.* FROM service_updates su
                             INNER JOIN (
                                 SELECT service_request_id, MAX(created_at) as latest_date
                                 FROM service_updates
                                 WHERE service_request_id IN ($id_list)
                                 GROUP BY service_request_id
                             ) latest ON su.service_request_id = latest.service_request_id AND su.created_at = latest.latest_date";
            $updates_stmt = $pdo->query($updates_query);
            
            if ($updates_stmt) {
                $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Index updates by service_request_id for easy access
                foreach ($updates as $update) {
                    $service_updates[$update['service_request_id']] = $update;
                }
            }
        }
    } catch (PDOException $e) {
        // Silently fail if the service_updates table doesn't exist yet
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
            color: var(--secondary-color);
            opacity: 0.5;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
        }
        
        .empty-state p {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-actions {
                margin-top: 1rem;
                width: 100%;
            }
            
            .dashboard-actions .btn {
                flex: 1;
                text-align: center;
            }
            
            .section-title {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-title .view-all {
                margin-top: 0.5rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                min-width: 650px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <i class="fas fa-tools"></i>
            <span>SmartFix</span>
        </a>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="services.php" class="nav-link">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
            </li>
            
            <li class="nav-item">
                <a href="shop.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Shop
                </a>
            </li>
            
            <li class="nav-item">
                <a href="contact.php" class="nav-link">
                    <i class="fas fa-envelope"></i> Contact
                </a>
            </li>
            
            <li class="nav-item user-dropdown">
                <div class="user-dropdown-toggle">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <span><?php echo $user_name; ?></span>
                    <i class="fas fa-chevron-down" style="margin-left: 0.5rem; font-size: 0.8rem;"></i>
                </div>
                
                <div class="user-dropdown-menu">
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
    
    <div class="main-content">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>Welcome, <?php echo $user_name; ?>!</h1>
                    <p>Manage your service requests and account settings</p>
                </div>
                
                <div class="dashboard-actions">
                    <a href="services.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> New Service Request
                    </a>
                    
                    <a href="services/track_service.php" class="btn btn-outline">
                        <i class="fas fa-search"></i> Track Service
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
            
            <!-- Popular Services -->
            <div class="section-title">
                <h2>Popular Services</h2>
                <a href="services.php" class="view-all">View All Services <i class="fas fa-arrow-right"></i></a>
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
                    <h3>Computer Repair</h3>
                    <p>Hardware fixes, software issues, and performance upgrades</p>
                    <a href="services.php?type=computer" class="btn btn-outline">Learn More</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Car Diagnostics</h3>
                    <p>Engine diagnostics, electrical issues, and general maintenance</p>
                    <a href="services.php?type=car" class="btn btn-outline">Learn More</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3>Home Services</h3>
                    <p>Plumbing, electrical work, and general home maintenance</p>
                    <a href="services.php?type=home" class="btn btn-outline">Learn More</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle user dropdown menu
        document.querySelector('.user-dropdown-toggle').addEventListener('click', function() {
            document.querySelector('.user-dropdown-menu').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                const dropdown = document.querySelector('.user-dropdown-menu');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>