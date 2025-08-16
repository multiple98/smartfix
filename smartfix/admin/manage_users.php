<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle user status update (activate/deactivate)
    if (isset($_POST['update_status'])) {
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        
        // Check if status column exists
        $check_column_query = "SHOW COLUMNS FROM users LIKE 'status'";
        $check_column_stmt = $pdo->prepare($check_column_query);
        $check_column_stmt->execute();
        $status_column_exists = $check_column_stmt->rowCount() > 0;
        
        if (!$status_column_exists) {
            // Add status column if it doesn't exist
            $add_column_query = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
            $pdo->exec($add_column_query);
        }
        
        // Now update the status
        $update_query = "UPDATE users SET status = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$status, $user_id]);
        
        $message = ($status == 'active') ? 'User activated successfully' : 'User deactivated successfully';
        $success_message = $message;
    }
    
    // Handle user type update
    if (isset($_POST['update_type'])) {
        $user_id = $_POST['user_id'];
        $user_type = $_POST['user_type'];
        
        // Check if user_type column exists first
        $check_type_column = "SHOW COLUMNS FROM users LIKE 'user_type'";
        $check_type_stmt = $pdo->prepare($check_type_column);
        $check_type_stmt->execute();
        
        if ($check_type_stmt->rowCount() == 0) {
            // Add user_type column if it doesn't exist
            $add_type_column = "ALTER TABLE users ADD COLUMN user_type ENUM('user', 'technician', 'admin') NOT NULL DEFAULT 'user'";
            $pdo->exec($add_type_column);
        }
        
        $update_query = "UPDATE users SET user_type = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$user_type, $user_id]);
        
        $success_message = 'User type updated successfully';
    }
    
    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        // Check if user has service requests
        $check_query = "SELECT COUNT(*) FROM service_requests WHERE user_id = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$user_id]);
        $request_count = $check_stmt->fetchColumn();
        
        if ($request_count > 0) {
            $error_message = 'Cannot delete user with active service requests. Deactivate the account instead.';
        } else {
            $delete_query = "DELETE FROM users WHERE id = ?";
            $delete_stmt = $pdo->prepare($delete_query);
            $delete_stmt->execute([$user_id]);
            
            $success_message = 'User deleted successfully';
        }
    }
}

// Check if status column exists first
$check_column_query = "SHOW COLUMNS FROM users LIKE 'status'";
$check_column_stmt = $pdo->prepare($check_column_query);
$check_column_stmt->execute();
$status_column_exists = $check_column_stmt->rowCount() > 0;

// Check if user_type column exists
$check_type_query = "SHOW COLUMNS FROM users LIKE 'user_type'";
$check_type_stmt = $pdo->prepare($check_type_query);
$check_type_stmt->execute();
$user_type_column_exists = $check_type_stmt->rowCount() > 0;

// Get filter parameters
$user_type_filter = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on filters
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($user_type_filter) && $user_type_column_exists) {
    $query .= " AND user_type = ?";
    $params[] = $user_type_filter;
}

if (!empty($status_filter) && $status_column_exists) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY created_at DESC";

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user counts for dashboard
$user_type_counts = [];
$total_users = 0;
$regular_users = 0;
$technicians = 0;
$admins = 0;

if ($user_type_column_exists) {
    // If user_type column exists, use it for counts
    $count_query = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute();
    
    while ($row = $count_stmt->fetch()) {
        $user_type_counts[$row['user_type']] = $row['count'];
    }
    
    $total_users = array_sum($user_type_counts);
    $regular_users = $user_type_counts['user'] ?? 0;
    $technicians = $user_type_counts['technician'] ?? 0;
    $admins = $user_type_counts['admin'] ?? 0;
} else {
    // Fallback: Count all users as regular users, try to get technicians from separate table
    try {
        $total_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
        $total_count_stmt->execute();
        $total_users = $total_count_stmt->fetchColumn();
        $regular_users = $total_users; // Assume all are regular users initially
        
        // Try to get technicians count from separate technicians table
        try {
            $tech_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM technicians");
            $tech_count_stmt->execute();
            $technicians = $tech_count_stmt->fetchColumn();
        } catch (PDOException $e) {
            $technicians = 0;
        }
        
        $admins = 0; // No way to count admins without user_type column
        
    } catch (PDOException $e) {
        $total_users = 0;
        $regular_users = 0;
        $technicians = 0;
        $admins = 0;
    }
}

// Get active/inactive counts if status column exists
$status_counts = [];
$active_users = 0;
$inactive_users = 0;

if ($status_column_exists) {
    $status_query = "SELECT status, COUNT(*) as count FROM users GROUP BY status";
    $status_stmt = $pdo->prepare($status_query);
    $status_stmt->execute();
    
    while ($row = $status_stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    $active_users = $status_counts['active'] ?? 0;
    $inactive_users = $status_counts['inactive'] ?? 0;
} else {
    // If status column doesn't exist, assume all users are active
    $active_users = $total_users;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SmartFix Admin</title>
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
        
        .page-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
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
        
        /* Filter Section */
        .filter-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
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
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .user-type-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .type-admin {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .type-technician {
            background-color: rgba(23, 162, 184, 0.15);
            color: var(--info-color);
        }
        
        .type-user {
            background-color: rgba(0, 123, 255, 0.15);
            color: var(--primary-color);
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
        
        .edit-btn {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .edit-btn:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .delete-btn {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .delete-btn:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1050;
            overflow: auto;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: slideIn 0.3s;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary-color);
        }
        
        .modal-body {
            padding: 1.25rem;
        }
        
        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        /* Alert Messages */
        .alert {
            padding: 0.75rem 1.25rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
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
            .main-content {
                padding: 1.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-actions {
                margin-top: 1rem;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard.php" class="sidebar-logo">
                    <i class="fas fa-tools"></i>
                    <span>SmartFix Admin</span>
                </a>
            </div>
            
            <div class="sidebar-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="manage_users.php" class="menu-item active">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                
                <a href="technicians.php" class="menu-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Technicians</span>
                </a>
                
                <a href="service_requests.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Service Requests</span>
                </a>
                
                <a href="product_listings.php" class="menu-item">
                    <i class="fas fa-box-open"></i>
                    <span>Product Listings</span>
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
                
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
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
                    <h1>User Management</h1>
                </div>
                
                <div class="page-actions">
                    <button class="btn btn-outline" onclick="exportToCSV()">
                        <i class="fas fa-download"></i> Export Users
                    </button>
                    <button class="btn btn-primary" onclick="openAddUserModal()">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $regular_users; ?></h3>
                        <p>Regular Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $technicians; ?></h3>
                        <p>Technicians</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $admins; ?></h3>
                        <p>Administrators</p>
                    </div>
                </div>
                
                <?php if ($status_column_exists): ?>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $active_users; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form action="manage_users.php" method="GET" class="filter-form">
                    <?php if ($user_type_column_exists): ?>
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select name="user_type" id="user_type" class="form-control">
                            <option value="">All Types</option>
                            <option value="user" <?php echo $user_type_filter == 'user' ? 'selected' : ''; ?>>Regular Users</option>
                            <option value="technician" <?php echo $user_type_filter == 'technician' ? 'selected' : ''; ?>>Technicians</option>
                            <option value="admin" <?php echo $user_type_filter == 'admin' ? 'selected' : ''; ?>>Administrators</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($status_column_exists): ?>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Name, Email, Phone..." value="<?php echo $search; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 0 0 auto;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="manage_users.php" class="btn btn-outline">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                        if ($user_type_column_exists && isset($user['user_type'])) {
                                            $user_type = $user['user_type'];
                                            $type_class = 'type-' . $user_type;
                                            echo "<span class='user-type-badge $type_class'>" . ucfirst($user_type) . "</span>";
                                        } else {
                                            // Default to 'user' if no user_type column exists
                                            echo "<span class='user-type-badge type-user'>User</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($status_column_exists) {
                                            $status = $user['status'] ?? 'active';
                                            $status_class = 'status-' . $status;
                                            echo "<span class='status-badge $status_class'>" . ucfirst($status) . "</span>";
                                        } else {
                                            echo "<span class='status-badge status-active'>Active</span>";
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="#" class="action-btn view-btn" onclick="viewUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="#" class="action-btn edit-btn" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($status_column_exists): ?>
                                            <?php if ($user['status'] == 'active' || !isset($user['status'])): ?>
                                                <a href="#" class="action-btn delete-btn" onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="action-btn edit-btn" onclick="activateUser(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-check"></i> Activate
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="#" class="action-btn delete-btn" onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <div style="color: var(--secondary-color);">
                                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                        <p>No users found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- View User Modal -->
    <div class="modal" id="viewUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">User Details</h2>
                <button class="modal-close" onclick="closeModal('viewUserModal')">&times;</button>
            </div>
            <div class="modal-body" id="userDetails">
                <!-- User details will be loaded here -->
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                    <p>Loading user details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('viewUserModal')">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit User</h2>
                <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    
                    <?php if ($user_type_column_exists): ?>
                    <div class="form-group">
                        <label for="edit_user_type">User Type</label>
                        <select name="user_type" id="edit_user_type" class="form-control">
                            <option value="user">Regular User</option>
                            <option value="technician">Technician</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitEditUser()">Save Changes</button>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New User</h2>
                <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addUserForm" method="POST" action="manage_users.php">
                    <div class="form-group">
                        <label for="add_name">Name</label>
                        <input type="text" name="name" id="add_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_email">Email</label>
                        <input type="email" name="email" id="add_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_phone">Phone</label>
                        <input type="text" name="phone" id="add_phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="add_password">Password</label>
                        <input type="password" name="password" id="add_password" class="form-control" required>
                    </div>
                    
                    <?php if ($user_type_column_exists): ?>
                    <div class="form-group">
                        <label for="add_user_type">User Type</label>
                        <select name="user_type" id="add_user_type" class="form-control">
                            <option value="user">Regular User</option>
                            <option value="technician">Technician</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitAddUser()">Add User</button>
            </div>
        </div>
    </div>
    
    <!-- Deactivate User Form (Hidden) -->
    <form id="deactivateUserForm" method="POST" action="manage_users.php" style="display: none;">
        <input type="hidden" name="user_id" id="deactivate_user_id">
        <input type="hidden" name="status" value="inactive">
        <input type="hidden" name="update_status" value="1">
    </form>
    
    <!-- Activate User Form (Hidden) -->
    <form id="activateUserForm" method="POST" action="manage_users.php" style="display: none;">
        <input type="hidden" name="user_id" id="activate_user_id">
        <input type="hidden" name="status" value="active">
        <input type="hidden" name="update_status" value="1">
    </form>
    
    <script>
        // View user details
        function viewUser(userId) {
            document.getElementById('viewUserModal').classList.add('show');
            
            // In a real implementation, you would fetch the user details via AJAX
            // For now, we'll simulate loading the details
            setTimeout(() => {
                document.getElementById('userDetails').innerHTML = `
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 0.5rem;">Personal Information</h3>
                        <p><strong>Name:</strong> John Doe</p>
                        <p><strong>Email:</strong> john.doe@example.com</p>
                        <p><strong>Phone:</strong> (555) 123-4567</p>
                        <p><strong>Address:</strong> 123 Main St, Anytown, USA</p>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 0.5rem;">Account Information</h3>
                        <p><strong>User Type:</strong> <span class="user-type-badge type-user">User</span></p>
                        <p><strong>Status:</strong> <span class="status-badge status-active">Active</span></p>
                        <p><strong>Registered:</strong> Jan 15, 2023</p>
                        <p><strong>Last Login:</strong> Today at 10:45 AM</p>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="margin-bottom: 0.5rem;">Activity Summary</h3>
                        <p><strong>Service Requests:</strong> 5</p>
                        <p><strong>Completed Services:</strong> 3</p>
                        <p><strong>Pending Services:</strong> 2</p>
                        <p><strong>Total Spent:</strong> $350.00</p>
                    </div>
                `;
            }, 1000);
        }
        
        // Edit user
        function editUser(userId) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('editUserModal').classList.add('show');
            
            // In a real implementation, you would fetch the user data via AJAX
            // For now, we'll use placeholder data
            document.getElementById('edit_name').value = 'John Doe';
            document.getElementById('edit_email').value = 'john.doe@example.com';
            document.getElementById('edit_phone').value = '(555) 123-4567';
            document.getElementById('edit_user_type').value = 'user';
        }
        
        // Add new user
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('show');
        }
        
        // Deactivate user
        function deactivateUser(userId) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                document.getElementById('deactivate_user_id').value = userId;
                document.getElementById('deactivateUserForm').submit();
            }
        }
        
        // Activate user
        function activateUser(userId) {
            document.getElementById('activate_user_id').value = userId;
            document.getElementById('activateUserForm').submit();
        }
        
        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        // Submit edit user form
        function submitEditUser() {
            document.getElementById('editUserForm').submit();
        }
        
        // Submit add user form
        function submitAddUser() {
            document.getElementById('addUserForm').submit();
        }
        
        // Export to CSV
        function exportToCSV() {
            alert('Export functionality will be implemented here.');
            // In a real implementation, this would generate and download a CSV file
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>