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

// Date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Initialize default data arrays to prevent undefined variable errors
$service_data = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'assigned_requests' => 0,
    'in_progress_requests' => 0,
    'completed_requests' => 0,
    'cancelled_requests' => 0,
    'emergency_requests' => 0,
    'avg_completion_days' => 0
];

$user_data = [
    'total_users' => 0,
    'verified_users' => 0,
    'unverified_users' => 0,
    'new_users' => 0
];

$revenue_data = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order_value' => 0,
    'completed_revenue' => 0,
    'pending_revenue' => 0
];

$technician_data = [];
$product_data = [];

// Generate comprehensive reports
try {
    // Service Requests Analytics
    $service_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
            SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_requests,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
            SUM(CASE WHEN priority = 'emergency' THEN 1 ELSE 0 END) as emergency_requests,
            AVG(CASE WHEN status = 'completed' THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_completion_days
        FROM service_requests 
        WHERE DATE(COALESCE(created_at, request_date, NOW())) BETWEEN ? AND ?
    ");
    $service_stats->execute([$start_date, $end_date]);
    $result = $service_stats->fetch();
    if ($result) {
        $service_data = $result;
    }
} catch (PDOException $e) {
    // Service requests table might not exist, keep default values
}

try {
    // User Statistics
    $user_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_users,
            SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as unverified_users,
            SUM(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_users
        FROM users
    ");
    $user_stats->execute([$start_date, $end_date]);
    $result = $user_stats->fetch();
    if ($result) {
        $user_data = $result;
    }
} catch (PDOException $e) {
    // Users table might not exist or have different structure, keep default values
}

try {
    // Technician Performance
    $tech_performance = $pdo->prepare("
        SELECT 
            t.id,
            t.name,
            t.specialization,
            t.rating,
            COUNT(sr.id) as total_jobs,
            SUM(CASE WHEN sr.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
            SUM(CASE WHEN sr.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_jobs,
            AVG(CASE WHEN sr.status = 'completed' THEN DATEDIFF(sr.updated_at, sr.assigned_at) ELSE NULL END) as avg_completion_days
        FROM technicians t
        LEFT JOIN service_requests sr ON t.id = sr.technician_id 
            AND DATE(COALESCE(sr.created_at, sr.request_date)) BETWEEN ? AND ?
        GROUP BY t.id, t.name, t.specialization, t.rating
        ORDER BY completed_jobs DESC, t.rating DESC
    ");
    $tech_performance->execute([$start_date, $end_date]);
    $technician_data = $tech_performance->fetchAll();
} catch (PDOException $e) {
    // Technicians table might not exist, keep default empty array
    $technician_data = [];
}

// Revenue Analytics (if orders table exists)
try {
    $revenue_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue,
            SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END) as pending_revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $revenue_stats->execute([$start_date, $end_date]);
    $result = $revenue_stats->fetch();
    if ($result) {
        $revenue_data = $result;
    }
} catch (PDOException $e) {
    // Orders table might not exist, keep default values
}

// Product Performance (if products and order_items tables exist)
try {
    $product_performance = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.category,
            p.price,
            COUNT(oi.id) as times_ordered,
            SUM(oi.quantity) as total_quantity_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id 
            AND DATE(o.created_at) BETWEEN ? AND ?
        WHERE p.is_active = 1
        GROUP BY p.id, p.name, p.category, p.price
        ORDER BY total_revenue DESC, times_ordered DESC
        LIMIT 10
    ");
    $product_performance->execute([$start_date, $end_date]);
    $product_data = $product_performance->fetchAll();
} catch (PDOException $e) {
    // Products or order_items tables might not exist, keep default empty array
    $product_data = [];
}

// Initialize additional default arrays
$daily_data = [];
$service_types = [];

// Daily Activity Trends
try {
    $daily_trends = $pdo->prepare("
        SELECT 
            DATE(COALESCE(created_at, request_date)) as activity_date,
            COUNT(*) as daily_requests
        FROM service_requests 
        WHERE DATE(COALESCE(created_at, request_date)) BETWEEN ? AND ?
        GROUP BY DATE(COALESCE(created_at, request_date))
        ORDER BY activity_date DESC
        LIMIT 30
    ");
    $daily_trends->execute([$start_date, $end_date]);
    $daily_data = $daily_trends->fetchAll();
} catch (PDOException $e) {
    // Service requests table might not exist, keep default empty array
    $daily_data = [];
}

// Service Type Distribution
try {
    $service_distribution = $pdo->prepare("
        SELECT 
            service_type,
            COUNT(*) as request_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM service_requests 
        WHERE DATE(COALESCE(created_at, request_date)) BETWEEN ? AND ?
        GROUP BY service_type
        ORDER BY request_count DESC
        LIMIT 10
    ");
    $service_distribution->execute([$start_date, $end_date]);
    $service_types = $service_distribution->fetchAll();
} catch (PDOException $e) {
    // Service requests table might not exist, keep default empty array
    $service_types = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }
        
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: auto auto auto 1fr;
            gap: 20px;
            align-items: end;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #28a745;
        }
        
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.requests::before { background: #007BFF; }
        .stat-card.users::before { background: #28a745; }
        .stat-card.revenue::before { background: #ffc107; }
        .stat-card.performance::before { background: #17a2b8; }
        
        .stat-card .icon {
            font-size: 48px;
            opacity: 0.1;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .stat-card h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .requests .stat-value { color: #007BFF; }
        .users .stat-value { color: #28a745; }
        .revenue .stat-value { color: #ffc107; }
        .performance .stat-value { color: #17a2b8; }
        
        .stat-details {
            font-size: 14px;
            color: #666;
            display: grid;
            gap: 5px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            font-size: 20px;
            color: #333;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .table-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-header h3 {
            font-size: 18px;
            color: #333;
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .performance-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .performance-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-chart-line"></i> Analytics & Reports</h1>
            <div class="nav-links">
                <a href="admin_dashboard_new.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="service_requests_enhanced.php"><i class="fas fa-clipboard-list"></i> Requests</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Users</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Date Range Filters -->
        <div class="filters-section">
            <form method="GET">
                <div class="filters-grid">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: #666; font-size: 14px;">
                            Report Period: <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>
                        </span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <!-- Service Requests Stats -->
            <div class="stat-card requests">
                <i class="fas fa-clipboard-list icon"></i>
                <h3>Service Requests</h3>
                <div class="stat-value"><?php echo number_format($service_data['total_requests']); ?></div>
                <div class="stat-details">
                    <div>✅ Completed: <?php echo $service_data['completed_requests']; ?></div>
                    <div>⏳ Pending: <?php echo $service_data['pending_requests']; ?></div>
                    <div>🔴 Emergency: <?php echo $service_data['emergency_requests']; ?></div>
                    <?php if ($service_data['avg_completion_days']): ?>
                        <div>📊 Avg Completion: <?php echo round($service_data['avg_completion_days'], 1); ?> days</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Stats -->
            <div class="stat-card users">
                <i class="fas fa-users icon"></i>
                <h3>User Management</h3>
                <div class="stat-value"><?php echo number_format($user_data['total_users']); ?></div>
                <div class="stat-details">
                    <div>✅ Verified: <?php echo $user_data['verified_users']; ?></div>
                    <div>⚠️ Unverified: <?php echo $user_data['unverified_users']; ?></div>
                    <div>🆕 New Users: <?php echo $user_data['new_users']; ?></div>
                </div>
            </div>

            <!-- Revenue Stats -->
            <div class="stat-card revenue">
                <i class="fas fa-dollar-sign icon"></i>
                <h3>Revenue Analytics</h3>
                <div class="stat-value">$<?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-details">
                    <div>🛍️ Total Orders: <?php echo $revenue_data['total_orders'] ?? 0; ?></div>
                    <div>💰 Avg Order: $<?php echo number_format($revenue_data['avg_order_value'] ?? 0, 2); ?></div>
                    <div>✅ Completed: $<?php echo number_format($revenue_data['completed_revenue'] ?? 0, 2); ?></div>
                    <div>⏳ Pending: $<?php echo number_format($revenue_data['pending_revenue'] ?? 0, 2); ?></div>
                </div>
            </div>

            <!-- System Performance -->
            <div class="stat-card performance">
                <i class="fas fa-tachometer-alt icon"></i>
                <h3>System Performance</h3>
                <div class="stat-value"><?php echo is_array($technician_data) ? count($technician_data) : 0; ?></div>
                <div class="stat-details">
                    <div>👨‍🔧 Active Technicians</div>
                    <div>📈 Completion Rate: <?php echo ($service_data['total_requests'] ?? 0) > 0 ? round((($service_data['completed_requests'] ?? 0) / $service_data['total_requests']) * 100, 1) : 0; ?>%</div>
                    <div>🎯 Success Rate: <?php 
                        $total = ($service_data['total_requests'] ?? 0);
                        $cancelled = ($service_data['cancelled_requests'] ?? 0);
                        $completed = ($service_data['completed_requests'] ?? 0);
                        $active_requests = $total - $cancelled;
                        echo $active_requests > 0 ? round(($completed / $active_requests) * 100, 1) : 0; 
                    ?>%</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Service Status Distribution -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Service Status Distribution</h3>
                </div>
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>

            <!-- Daily Activity Trends -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Daily Activity Trends</h3>
                </div>
                <canvas id="trendsChart" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Service Type Distribution -->
        <?php if (!empty($service_types)): ?>
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-bar"></i> Service Type Performance</h3>
            </div>
            <canvas id="serviceTypesChart" width="400" height="200"></canvas>
        </div>
        <?php endif; ?>

        <!-- Technician Performance Table -->
        <?php if (!empty($technician_data)): ?>
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-user-cog"></i> Technician Performance Report</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Technician</th>
                        <th>Specialization</th>
                        <th>Rating</th>
                        <th>Total Jobs</th>
                        <th>Completed</th>
                        <th>Success Rate</th>
                        <th>Avg Days</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($technician_data as $tech): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($tech['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                            <td>
                                <span class="rating-stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $tech['rating'] ? '★' : '☆';
                                    }
                                    ?>
                                </span>
                                (<?php echo $tech['rating']; ?>)
                            </td>
                            <td><?php echo $tech['total_jobs']; ?></td>
                            <td><?php echo $tech['completed_jobs']; ?></td>
                            <td>
                                <?php 
                                $success_rate = $tech['total_jobs'] > 0 ? ($tech['completed_jobs'] / $tech['total_jobs']) * 100 : 0;
                                echo round($success_rate, 1) . '%';
                                ?>
                            </td>
                            <td><?php echo $tech['avg_completion_days'] ? round($tech['avg_completion_days'], 1) : 'N/A'; ?></td>
                            <td>
                                <div class="performance-bar">
                                    <div class="performance-fill" style="width: <?php echo min($success_rate, 100); ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Top Products Performance -->
        <?php if (!empty($product_data)): ?>
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-shopping-cart"></i> Top Products Performance</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Orders</th>
                        <th>Quantity Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($product_data as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['times_ordered']; ?></td>
                            <td><?php echo $product['total_quantity_sold']; ?></td>
                            <td><strong>$<?php echo number_format($product['total_revenue'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Service Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $service_data['pending_requests']; ?>,
                        <?php echo $service_data['assigned_requests']; ?>,
                        <?php echo $service_data['in_progress_requests']; ?>,
                        <?php echo $service_data['completed_requests']; ?>,
                        <?php echo $service_data['cancelled_requests']; ?>
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#17a2b8',
                        '#007BFF',
                        '#28a745',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Daily Activity Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach (array_reverse($daily_data) as $day): ?>
                        '<?php echo date('M j', strtotime($day['activity_date'])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Daily Requests',
                    data: [
                        <?php foreach (array_reverse($daily_data) as $day): ?>
                            <?php echo $day['daily_requests']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Service Types Chart
        <?php if (!empty($service_types)): ?>
        const serviceTypesCtx = document.getElementById('serviceTypesChart').getContext('2d');
        new Chart(serviceTypesCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($service_types as $type): ?>
                        '<?php echo htmlspecialchars($type['service_type']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Total Requests',
                    data: [
                        <?php foreach ($service_types as $type): ?>
                            <?php echo $type['request_count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(0, 123, 255, 0.8)',
                    borderColor: '#007BFF',
                    borderWidth: 1
                }, {
                    label: 'Completed',
                    data: [
                        <?php foreach ($service_types as $type): ?>
                            <?php echo $type['completed_count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>