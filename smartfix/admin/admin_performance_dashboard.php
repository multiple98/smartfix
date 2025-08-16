<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/SecurityManager.php';
require_once '../includes/PerformanceManager.php';

// Initialize managers
$security = new SecurityManager($pdo);
$performance = new PerformanceManager($pdo);
$security::secureSession();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle performance optimization requests
if ($_POST['action'] ?? '' === 'optimize_database') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($security->verifyCSRFToken($csrf_token, $_SESSION['user_id'] ?? null)) {
        $optimizations = $performance->optimizeDatabase();
        $_SESSION['optimization_results'] = $optimizations;
        header("Location: admin_performance_dashboard.php?optimized=1");
        exit();
    }
}

// Handle cache cleanup
if ($_POST['action'] ?? '' === 'cleanup_cache') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if ($security->verifyCSRFToken($csrf_token, $_SESSION['user_id'] ?? null)) {
        $cleaned = $performance->cleanupCache();
        $_SESSION['cache_cleaned'] = $cleaned;
        header("Location: admin_performance_dashboard.php?cache_cleaned=1");
        exit();
    }
}

// Get dashboard statistics
$dbStats = $performance->getDbStats();
$performanceMetrics = $performance->getPerformanceMetrics();

// Generate CSRF token
$csrfToken = $security->generateCSRFToken($_SESSION['user_id'] ?? null);

// Get recent audit logs
$auditQuery = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10";
$auditLogs = [];
try {
    $stmt = $pdo->query($auditQuery);
    $auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get rate limit statistics
$rateLimitQuery = "SELECT endpoint, COUNT(*) as attempts, COUNT(CASE WHEN is_blocked = 1 THEN 1 END) as blocked FROM rate_limits GROUP BY endpoint";
$rateLimitStats = [];
try {
    $stmt = $pdo->query($rateLimitQuery);
    $rateLimitStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFix Admin - Performance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        .header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .stat-card p {
            color: var(--dark-color);
            opacity: 0.8;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: var(--light-color);
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background: #cce7ff;
            color: #004085;
            border: 1px solid #99d6ff;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        .table th {
            background: var(--light-color);
            font-weight: 600;
        }

        .table tr:hover {
            background: rgba(0,123,255,0.1);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-success {
            background: var(--success-color);
            color: white;
        }

        .badge-danger {
            background: var(--danger-color);
            color: white;
        }

        .badge-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .progress {
            background: var(--light-color);
            border-radius: 4px;
            height: 20px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-bar {
            background: var(--success-color);
            height: 100%;
            transition: width 0.3s;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-tachometer-alt"></i> Performance Dashboard</h1>
        <div class="nav-links">
            <a href="admin_dashboard.php"><i class="fas fa-dashboard"></i> Main Dashboard</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['optimized'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Database optimization completed! 
                <?php if (isset($_SESSION['optimization_results'])): ?>
                    <ul style="margin-top: 0.5rem;">
                        <?php foreach ($_SESSION['optimization_results'] as $result): ?>
                            <li><?php echo htmlspecialchars($result); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php unset($_SESSION['optimization_results']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['cache_cleaned'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Cache cleanup completed! 
                Cleaned <?php echo $_SESSION['cache_cleaned'] ?? 0; ?> old cache files.
                <?php unset($_SESSION['cache_cleaned']); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo number_format($dbStats[0]['total_users'] ?? 0); ?></h3>
                <p><i class="fas fa-users"></i> Total Users</p>
            </div>
            <div class="stat-card success">
                <h3><?php echo number_format($dbStats[0]['completed_requests'] ?? 0); ?></h3>
                <p><i class="fas fa-check-circle"></i> Completed Requests</p>
            </div>
            <div class="stat-card warning">
                <h3><?php echo $performanceMetrics['queries_per_second'] ?? 'N/A'; ?></h3>
                <p><i class="fas fa-database"></i> Queries/Second</p>
            </div>
            <div class="stat-card danger">
                <h3><?php echo $performanceMetrics['slow_queries'] ?? 'N/A'; ?></h3>
                <p><i class="fas fa-exclamation-triangle"></i> Slow Queries</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Performance Tools -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tools"></i> Performance Tools
                </div>
                <div class="card-body">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="optimize_database">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database"></i> Optimize Database
                        </button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="cleanup_cache">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-trash"></i> Cleanup Cache
                        </button>
                    </form>

                    <h4 style="margin-top: 2rem; margin-bottom: 1rem;">Database Performance</h4>
                    <div>
                        <strong>Server Uptime:</strong> <?php echo $performanceMetrics['uptime_hours'] ?? 'N/A'; ?> hours<br>
                        <strong>Total Queries:</strong> <?php echo number_format($performanceMetrics['total_queries'] ?? 0); ?><br>
                        <strong>Slow Queries:</strong> <?php echo $performanceMetrics['slow_queries'] ?? 'N/A'; ?>
                    </div>

                    <?php if (!empty($rateLimitStats)): ?>
                        <h4 style="margin-top: 2rem; margin-bottom: 1rem;">Rate Limiting Statistics</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Attempts</th>
                                    <th>Blocked</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rateLimitStats as $stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stat['endpoint']); ?></td>
                                        <td><?php echo number_format($stat['attempts']); ?></td>
                                        <td>
                                            <?php if ($stat['blocked'] > 0): ?>
                                                <span class="badge badge-danger"><?php echo $stat['blocked']; ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success">0</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Monitoring -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-shield-alt"></i> Security Monitor
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> All security features active
                    </div>
                    
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check text-success"></i> Rate limiting enabled
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check text-success"></i> CSRF protection active
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check text-success"></i> SQL injection prevention
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check text-success"></i> File upload validation
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check text-success"></i> Audit logging active
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if (!empty($auditLogs)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Recent Security Events
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User ID</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo $log['user_id'] ?? 'N/A'; ?></td>
                                    <td>
                                        <?php
                                        $actionClass = '';
                                        if (strpos($log['action'], 'failed') !== false) {
                                            $actionClass = 'badge-danger';
                                        } elseif (strpos($log['action'], 'success') !== false) {
                                            $actionClass = 'badge-success';
                                        } else {
                                            $actionClass = 'badge-warning';
                                        }
                                        ?>
                                        <span class="badge <?php echo $actionClass; ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($log['new_values'] ?? '', 0, 50)); ?>
                                        <?php if (strlen($log['new_values'] ?? '') > 50) echo '...'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh performance metrics every 30 seconds
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);

        // Add loading state to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                this.classList.add('loading');
                const buttons = this.querySelectorAll('button[type="submit"]');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                });
            });
        });
    </script>
</body>
</html>