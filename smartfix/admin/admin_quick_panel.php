<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Get quick stats
try {
    $pending_requests = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
    $urgent_requests = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE priority = 'high' OR service_type LIKE '%emergency%'")->fetchColumn();
    $unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn() ?? 0;
    $new_users_today = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?? 0;
} catch (Exception $e) {
    $pending_requests = 0;
    $urgent_requests = 0;
    $unread_messages = 0;
    $new_users_today = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Quick Panel - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-bg: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .quick-panel {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .panel-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .panel-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .panel-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
        }
        
        .stat-card.urgent::before {
            background: var(--danger-color);
        }
        
        .stat-card.messages::before {
            background: var(--info-color);
        }
        
        .stat-card.users::before {
            background: var(--success-color);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .stat-card.urgent .stat-icon {
            color: var(--danger-color);
        }
        
        .stat-card.messages .stat-icon {
            color: var(--info-color);
        }
        
        .stat-card.users .stat-icon {
            color: var(--success-color);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .action-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .action-section h3 {
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
        
        .action-section h3 i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            background: var(--light-bg);
            color: var(--dark-color);
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .action-btn i {
            margin-right: 0.5rem;
            width: 20px;
        }
        
        .urgent-btn {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-color: rgba(220, 53, 69, 0.3);
        }
        
        .urgent-btn:hover {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }
        
        .footer-actions {
            text-align: center;
            margin-top: 3rem;
        }
        
        .dashboard-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .dashboard-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .quick-search {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        @media (max-width: 768px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .panel-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="quick-panel">
        <div class="panel-header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Quick Panel</h1>
            <p>SmartFix Management Center - Quick Access to Everything You Need</p>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $pending_requests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            
            <div class="stat-card urgent">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?php echo $urgent_requests; ?></div>
                <div class="stat-label">Urgent Requests</div>
            </div>
            
            <div class="stat-card messages">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-number"><?php echo $unread_messages; ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-number"><?php echo $new_users_today; ?></div>
                <div class="stat-label">New Users Today</div>
            </div>
        </div>
        
        <!-- Quick Search -->
        <div class="quick-search">
            <input type="text" class="search-input" placeholder="Quick search: user ID, email, service request ID..." id="quickSearch" onkeypress="handleSearch(event)">
        </div>
        
        <!-- Quick Actions -->
        <div class="actions-grid">
            <div class="action-section">
                <h3><i class="fas fa-clipboard-list"></i> Service Management</h3>
                <a href="service_requests_enhanced.php" class="action-btn">
                    <i class="fas fa-list"></i> View All Service Requests
                </a>
                <?php if ($pending_requests > 0): ?>
                <a href="service_requests_enhanced.php?status=pending" class="action-btn urgent-btn">
                    <i class="fas fa-clock"></i> Pending Requests (<?php echo $pending_requests; ?>)
                </a>
                <?php endif; ?>
                <?php if ($urgent_requests > 0): ?>
                <a href="emergency_dashboard.php" class="action-btn urgent-btn">
                    <i class="fas fa-ambulance"></i> Emergency Dashboard (<?php echo $urgent_requests; ?>)
                </a>
                <?php endif; ?>
                <a href="assign_technician.php" class="action-btn">
                    <i class="fas fa-user-cog"></i> Assign Technicians
                </a>
            </div>
            
            <div class="action-section">
                <h3><i class="fas fa-users"></i> User Management</h3>
                <a href="manage_users.php" class="action-btn">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="technicians.php" class="action-btn">
                    <i class="fas fa-user-cog"></i> Manage Technicians
                </a>
                <a href="manage_users.php?action=add" class="action-btn">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <a href="register_technician.php" class="action-btn">
                    <i class="fas fa-plus-circle"></i> Register Technician
                </a>
            </div>
            
            <div class="action-section">
                <h3><i class="fas fa-shopping-cart"></i> Shop Management</h3>
                <a href="manage_products.php" class="action-btn">
                    <i class="fas fa-box"></i> Manage Products
                </a>
                <a href="add_product.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <a href="orders.php" class="action-btn">
                    <i class="fas fa-shopping-bag"></i> View Orders
                </a>
                <a href="inventory.php" class="action-btn">
                    <i class="fas fa-warehouse"></i> Inventory Management
                </a>
            </div>
            
            <div class="action-section">
                <h3><i class="fas fa-chart-line"></i> Analytics & Reports</h3>
                <a href="reports_enhanced.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
                <a href="admin_performance_dashboard.php" class="action-btn">
                    <i class="fas fa-tachometer-alt"></i> Performance Dashboard
                </a>
                <a href="gps_dashboard.php" class="action-btn">
                    <i class="fas fa-map-marked-alt"></i> GPS Tracking
                </a>
                <a href="export_data.php" class="action-btn">
                    <i class="fas fa-download"></i> Export Data
                </a>
            </div>
            
            <div class="action-section">
                <h3><i class="fas fa-cog"></i> System Settings</h3>
                <a href="settings.php" class="action-btn">
                    <i class="fas fa-cog"></i> General Settings
                </a>
                <?php if ($unread_messages > 0): ?>
                <a href="messages.php" class="action-btn urgent-btn">
                    <i class="fas fa-envelope"></i> Messages (<?php echo $unread_messages; ?>)
                </a>
                <?php else: ?>
                <a href="messages.php" class="action-btn">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <?php endif; ?>
                <a href="notifications.php" class="action-btn">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <a href="backup.php" class="action-btn">
                    <i class="fas fa-shield-alt"></i> Backup System
                </a>
            </div>
            
            <div class="action-section">
                <h3><i class="fas fa-tools"></i> Quick Tools</h3>
                <a href="database_maintenance.php" class="action-btn">
                    <i class="fas fa-database"></i> Database Maintenance
                </a>
                <a href="clear_cache.php" class="action-btn">
                    <i class="fas fa-broom"></i> Clear Cache
                </a>
                <a href="system_health.php" class="action-btn">
                    <i class="fas fa-heartbeat"></i> System Health Check
                </a>
                <a href="../admin_dashboard_test.php" class="action-btn">
                    <i class="fas fa-bug"></i> System Diagnostics
                </a>
            </div>
        </div>
        
        <!-- Footer Actions -->
        <div class="footer-actions">
            <a href="admin_dashboard_new.php" class="dashboard-btn">
                <i class="fas fa-tachometer-alt"></i> Go to Full Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function handleSearch(event) {
            if (event.key === 'Enter') {
                const query = document.getElementById('quickSearch').value.trim();
                if (query) {
                    // Determine search type and redirect appropriately
                    if (/^\d+$/.test(query)) {
                        // Numeric: could be user ID or service request ID
                        if (confirm(`Search for User/Request ID: ${query}?`)) {
                            window.location.href = `search_results.php?q=${query}&type=id`;
                        }
                    } else if (query.includes('@')) {
                        // Email search
                        window.location.href = `search_results.php?q=${encodeURIComponent(query)}&type=email`;
                    } else {
                        // General search
                        window.location.href = `search_results.php?q=${encodeURIComponent(query)}&type=general`;
                    }
                }
            }
        }
        
        // Add some dynamic updates
        setInterval(function() {
            // In a real implementation, you could fetch updated counts via AJAX
            console.log('Quick panel stats could be updated here');
        }, 60000); // Every minute
    </script>
</body>
</html>