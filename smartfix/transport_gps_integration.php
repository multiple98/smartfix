<?php
/**
 * Transport & GPS Integration Dashboard
 * Complete overview and management of the transport and GPS tracking system
 */

session_start();
include 'includes/db.php';

// Check if admin is logged in (simple check for demo)
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'System Administrator';
}

// Get system statistics
$stats = [];

try {
    // Transport providers
    $stmt = $pdo->query("SELECT COUNT(*) as total, 
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active 
                        FROM transport_providers");
    $transport_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['transport_providers'] = $transport_stats;
    
    // Service requests with GPS
    $stmt = $pdo->query("SELECT COUNT(*) as total,
                        COUNT(CASE WHEN sl.latitude IS NOT NULL THEN 1 END) as with_gps
                        FROM service_requests sr
                        LEFT JOIN service_locations sl ON sr.id = sl.request_id");
    $service_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['service_requests'] = $service_stats;
    
    // Active technicians with location
    $stmt = $pdo->query("SELECT COUNT(*) as total,
                        COUNT(CASE WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as active_gps
                        FROM technicians t
                        LEFT JOIN technician_locations tl ON t.id = tl.technician_id
                        WHERE t.status = 'active'");
    $tech_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['technicians'] = $tech_stats;
    
    // Orders with transport
    $stmt = $pdo->query("SELECT COUNT(*) as total,
                        COUNT(CASE WHEN transport_id IS NOT NULL THEN 1 END) as with_transport
                        FROM orders");
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['orders'] = $order_stats;
    
    // Recent activity
    $stmt = $pdo->query("SELECT 'service_request' as type, created_at, 'New service request' as description
                        FROM service_requests 
                        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        UNION ALL
                        SELECT 'technician_location' as type, last_updated as created_at, 'Technician location update' as description
                        FROM technician_locations 
                        WHERE last_updated > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        UNION ALL
                        SELECT 'order' as type, created_at, 'New order placed' as description
                        FROM orders 
                        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY created_at DESC
                        LIMIT 10");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $stats = [
        'transport_providers' => ['total' => 0, 'active' => 0],
        'service_requests' => ['total' => 0, 'with_gps' => 0],
        'technicians' => ['total' => 0, 'active_gps' => 0],
        'orders' => ['total' => 0, 'with_transport' => 0]
    ];
    $recent_activity = [];
}

// System health checks
$health_checks = [
    'database' => checkDatabaseConnection(),
    'tables' => checkRequiredTables(),
    'api_endpoints' => checkApiEndpoints(),
    'config_files' => checkConfigFiles()
];

function checkDatabaseConnection() {
    global $pdo;
    try {
        $pdo->query("SELECT 1");
        return ['status' => 'healthy', 'message' => 'Database connection active'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database connection failed'];
    }
}

function checkRequiredTables() {
    global $pdo;
    $required_tables = [
        'transport_providers', 'transport_quotes', 'delivery_tracking',
        'service_locations', 'technician_locations', 'geocoding_cache'
    ];
    
    try {
        $existing_tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existing_tables[] = $row[0];
        }
        
        $missing_tables = array_diff($required_tables, $existing_tables);
        
        if (empty($missing_tables)) {
            return ['status' => 'healthy', 'message' => 'All required tables exist'];
        } else {
            return ['status' => 'warning', 'message' => 'Missing tables: ' . implode(', ', $missing_tables)];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Cannot check tables'];
    }
}

function checkApiEndpoints() {
    $api_files = [
        'api/transport-calculator.php',
        'api/delivery-tracking.php',
        'api/reverse-geocode.php',
        'api/update-technician-location.php'
    ];
    
    $missing_files = [];
    foreach ($api_files as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        return ['status' => 'healthy', 'message' => 'All API endpoints available'];
    } else {
        return ['status' => 'warning', 'message' => 'Missing API files: ' . implode(', ', $missing_files)];
    }
}

function checkConfigFiles() {
    $config_files = [
        'config/maps_config.php',
        'includes/GPSManager.php'
    ];
    
    $missing_files = [];
    foreach ($config_files as $file) {
        if (!file_exists($file)) {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        return ['status' => 'healthy', 'message' => 'All configuration files present'];
    } else {
        return ['status' => 'warning', 'message' => 'Missing config files: ' . implode(', ', $missing_files)];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport & GPS Integration Dashboard - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #007BFF;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
            font-size: 1.2rem;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .stat-sublabel {
            color: #999;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .health-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .health-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: bold;
            color: #007BFF;
        }

        .health-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .health-item.warning {
            border-left-color: #ffc107;
        }

        .health-item.error {
            border-left-color: #dc3545;
        }

        .health-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
        }

        .status-healthy { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }

        .features-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #007BFF;
        }

        .feature-card h4 {
            color: #007BFF;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 10px;
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.2);
            color: #0056b3;
        }

        .activity-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: 500px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .activity-item:hover {
            background: rgba(0, 123, 255, 0.05);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .activity-service { background: #007BFF; }
        .activity-technician { background: #28a745; }
        .activity-order { background: #ffc107; }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-truck"></i> <i class="fas fa-map-marked-alt"></i> Transport & GPS Integration</h1>
        <p>Complete delivery tracking and location management system</p>
    </div>

    <div class="container">
        <div class="dashboard-grid">
            <div class="main-content">
                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="color: #007BFF;">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['transport_providers']['active']; ?></div>
                        <div class="stat-label">Active Transport Providers</div>
                        <div class="stat-sublabel"><?php echo $stats['transport_providers']['total']; ?> total providers</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #28a745;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['service_requests']['with_gps']; ?></div>
                        <div class="stat-label">GPS-Enabled Requests</div>
                        <div class="stat-sublabel"><?php echo $stats['service_requests']['total']; ?> total requests</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #17a2b8;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['technicians']['active_gps']; ?></div>
                        <div class="stat-label">Active GPS Technicians</div>
                        <div class="stat-sublabel"><?php echo $stats['technicians']['total']; ?> total technicians</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="color: #ffc107;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['orders']['with_transport']; ?></div>
                        <div class="stat-label">Orders with Transport</div>
                        <div class="stat-sublabel"><?php echo $stats['orders']['total']; ?> total orders</div>
                    </div>
                </div>

                <!-- Features Overview -->
                <div class="features-section">
                    <h2><i class="fas fa-star"></i> System Features</h2>
                    <div class="features-grid">
                        <div class="feature-card">
                            <h4><i class="fas fa-truck"></i> Transport Management</h4>
                            <ul class="feature-list">
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Multiple transport providers</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Dynamic pricing calculation</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Real-time quotes</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Provider rating system</li>
                            </ul>
                        </div>

                        <div class="feature-card">
                            <h4><i class="fas fa-map-marked-alt"></i> GPS Tracking</h4>
                            <ul class="feature-list">
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Real-time location tracking</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Technician location history</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Service area mapping</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Distance calculations</li>
                            </ul>
                        </div>

                        <div class="feature-card">
                            <h4><i class="fas fa-shipping-fast"></i> Live Tracking</h4>
                            <ul class="feature-list">
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Real-time delivery status</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Driver information</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> ETA calculations</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Customer notifications</li>
                            </ul>
                        </div>

                        <div class="feature-card">
                            <h4><i class="fas fa-chart-line"></i> Analytics</h4>
                            <ul class="feature-list">
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Performance metrics</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Cost analysis</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Provider comparison</li>
                                <li><i class="fas fa-check" style="color: #28a745;"></i> Service coverage</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar">
                <!-- System Health -->
                <div class="health-panel">
                    <div class="health-header">
                        <i class="fas fa-heartbeat"></i>
                        System Health
                    </div>
                    
                    <?php foreach ($health_checks as $check_name => $check_result): ?>
                        <div class="health-item <?php echo $check_result['status']; ?>">
                            <div>
                                <strong><?php echo ucfirst(str_replace('_', ' ', $check_name)); ?></strong>
                                <div style="font-size: 0.9rem; color: #666;">
                                    <?php echo $check_result['message']; ?>
                                </div>
                            </div>
                            <div class="health-status status-<?php echo $check_result['status']; ?>">
                                <?php if ($check_result['status'] === 'healthy'): ?>
                                    <i class="fas fa-check-circle"></i> Healthy
                                <?php elseif ($check_result['status'] === 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle"></i> Warning
                                <?php else: ?>
                                    <i class="fas fa-times-circle"></i> Error
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Recent Activity -->
                <div class="activity-panel">
                    <div class="health-header">
                        <i class="fas fa-clock"></i>
                        Recent Activity
                    </div>
                    
                    <?php if (empty($recent_activity)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">No recent activity</p>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon activity-<?php echo explode('_', $activity['type'])[0]; ?>">
                                    <?php if ($activity['type'] === 'service_request'): ?>
                                        <i class="fas fa-clipboard-list"></i>
                                    <?php elseif ($activity['type'] === 'technician_location'): ?>
                                        <i class="fas fa-map-marker-alt"></i>
                                    <?php else: ?>
                                        <i class="fas fa-shopping-cart"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <div class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="admin/transport_dashboard.php" class="action-btn">
                <i class="fas fa-chart-line"></i>
                Transport Dashboard
            </a>
            <a href="admin/transport_providers_enhanced.php" class="action-btn">
                <i class="fas fa-truck"></i>
                Manage Providers
            </a>
            <a href="admin/gps_dashboard.php" class="action-btn">
                <i class="fas fa-map-marked-alt"></i>
                GPS Dashboard
            </a>
            <a href="technician/gps_tracker.php" class="action-btn">
                <i class="fas fa-mobile-alt"></i>
                Technician Tracker
            </a>
            <a href="transport_live_tracking.php?order_id=1" class="action-btn">
                <i class="fas fa-eye"></i>
                Live Tracking Demo
            </a>
            <a href="complete_transport_gps_system.php" class="action-btn">
                <i class="fas fa-cog"></i>
                System Setup
            </a>
        </div>
    </div>
</body>
</html>