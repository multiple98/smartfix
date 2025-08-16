<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/ZambiaLocationValidator.php';

// Simple admin check (you can enhance this with proper admin authentication)
if (!isset($_SESSION['admin_logged_in'])) {
    // For demo purposes, we'll allow access
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Demo Admin';
}

try {
    // Get GPS system statistics
    $stats_query = "SELECT 
                        COUNT(*) as total_technicians,
                        COUNT(tl.technician_id) as with_location,
                        COUNT(CASE WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 END) as online,
                        COUNT(CASE WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) AND tl.last_updated <= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 END) as recently_active,
                        AVG(t.rating) as avg_rating
                    FROM technicians t 
                    LEFT JOIN technician_locations tl ON t.id = tl.technician_id 
                    WHERE t.status = 'available'";
    $stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);
    
    // Get province distribution
    $province_query = "SELECT 
                          SUBSTRING_INDEX(t.regions, ',', 1) as province,
                          COUNT(*) as technician_count,
                          COUNT(tl.technician_id) as with_location,
                          COUNT(CASE WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 END) as online_count
                       FROM technicians t
                       LEFT JOIN technician_locations tl ON t.id = tl.technician_id
                       WHERE t.status = 'available'
                       GROUP BY province
                       ORDER BY technician_count DESC";
    $provinces = $pdo->query($province_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get specialization distribution
    $spec_query = "SELECT 
                      t.specialization,
                      COUNT(*) as count,
                      COUNT(tl.technician_id) as with_location,
                      AVG(t.rating) as avg_rating
                   FROM technicians t
                   LEFT JOIN technician_locations tl ON t.id = tl.technician_id
                   WHERE t.status = 'available'
                   GROUP BY t.specialization
                   ORDER BY count DESC";
    $specializations = $pdo->query($spec_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent location updates
    $recent_query = "SELECT 
                        t.name,
                        t.specialization,
                        tl.latitude,
                        tl.longitude,
                        tl.last_updated,
                        CASE 
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                            ELSE 'offline'
                        END AS status
                     FROM technicians t
                     JOIN technician_locations tl ON t.id = tl.technician_id
                     WHERE t.status = 'available'
                     ORDER BY tl.last_updated DESC
                     LIMIT 10";
    $recent_updates = $pdo->query($recent_query)->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS System Overview - SmartFix Admin</title>
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
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 1rem 0;
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

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-content {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-online {
            background: #d4edda;
            color: #155724;
        }

        .status-recently_active {
            background: #fff3cd;
            color: #856404;
        }

        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            background: #28a745;
            height: 100%;
            transition: width 0.3s;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #1e7e34;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-map-marked-alt"></i>
                GPS System Overview
            </h1>
            <div class="nav-links">
                <a href="../technician_map.php"><i class="fas fa-map"></i> View Map</a>
                <a href="../setup_zambia_technicians.php"><i class="fas fa-database"></i> Setup Data</a>
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #007bff;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number" style="color: #007bff;">
                        <?php echo $stats['total_technicians']; ?>
                    </div>
                    <div class="stat-label">Total Technicians</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #28a745;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-number" style="color: #28a745;">
                        <?php echo $stats['with_location']; ?>
                    </div>
                    <div class="stat-label">With GPS Location</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #17a2b8;">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-number" style="color: #17a2b8;">
                        <?php echo $stats['online']; ?>
                    </div>
                    <div class="stat-label">Online Now</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #ffc107;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number" style="color: #ffc107;">
                        <?php echo number_format($stats['avg_rating'], 1); ?>
                    </div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>

            <!-- Coverage Information -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>System Status:</strong> 
                GPS coverage is active across <?php echo count($provinces); ?> Zambian provinces. 
                <?php echo round(($stats['with_location'] / $stats['total_technicians']) * 100); ?>% of technicians have GPS locations enabled.
            </div>

            <div class="grid-2">
                <!-- Province Distribution -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-map"></i>
                        Coverage by Province
                    </div>
                    <div class="card-content">
                        <?php foreach ($provinces as $province): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                    <strong><?php echo htmlspecialchars($province['province']); ?></strong>
                                    <span><?php echo $province['technician_count']; ?> technicians</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($province['with_location'] / max($province['technician_count'], 1)) * 100; ?>%;"></div>
                                </div>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo $province['with_location']; ?> with GPS • <?php echo $province['online_count']; ?> online
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Service Types -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tools"></i>
                        Service Specializations
                    </div>
                    <div class="card-content">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Service Type</th>
                                    <th>Count</th>
                                    <th>GPS</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($specializations as $spec): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(ucfirst($spec['specialization'])); ?></td>
                                        <td><?php echo $spec['count']; ?></td>
                                        <td><?php echo $spec['with_location']; ?></td>
                                        <td>
                                            <div style="color: #ffc107;">
                                                <?php 
                                                $rating = floatval($spec['avg_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '<i class="fas fa-star-half-alt"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                                <span style="color: #333; margin-left: 5px;"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Location Updates -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock"></i>
                    Recent Location Updates
                </div>
                <div class="card-content">
                    <?php if (empty($recent_updates)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">
                            <i class="fas fa-info-circle"></i>
                            No recent location updates. Run the setup script to populate sample data.
                        </p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Technician</th>
                                    <th>Service</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Last Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_updates as $update): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($update['name']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($update['specialization'])); ?></td>
                                        <td>
                                            <?php 
                                            $validation = ZambiaLocationValidator::validateCoordinates($update['latitude'], $update['longitude']);
                                            if ($validation['valid']) {
                                                echo htmlspecialchars($validation['province'] . ' Province');
                                                if ($validation['nearest_city']) {
                                                    echo '<br><small style="color: #666;">Near ' . htmlspecialchars($validation['nearest_city']) . '</small>';
                                                }
                                            } else {
                                                echo 'Location unavailable';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $update['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $update['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y H:i', strtotime($update['last_updated'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="../technician_map.php" class="btn btn-success">
                    <i class="fas fa-map-marked-alt"></i>
                    View Live Map
                </a>
                <a href="../setup_zambia_technicians.php" class="btn btn-warning">
                    <i class="fas fa-database"></i>
                    Setup Sample Data
                </a>
                <a href="../api/technicians-map.php" class="btn" target="_blank">
                    <i class="fas fa-code"></i>
                    View API Data
                </a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>