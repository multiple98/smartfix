<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Function to get database performance metrics
function getDatabaseMetrics($pdo) {
    $metrics = [];
    
    try {
        // Get database size
        $size_query = "SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
            FROM information_schema.tables 
            WHERE table_schema = 'smartfix'";
        $size_result = $pdo->query($size_query);
        $metrics['database_size'] = $size_result->fetchColumn() ?: 0;
        
        // Get table information
        $tables_query = "SELECT 
            table_name,
            table_rows,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
            ROUND((data_length / 1024 / 1024), 2) AS data_size_mb,
            ROUND((index_length / 1024 / 1024), 2) AS index_size_mb
            FROM information_schema.tables 
            WHERE table_schema = 'smartfix'
            ORDER BY (data_length + index_length) DESC";
        $tables_result = $pdo->query($tables_query);
        $metrics['tables'] = $tables_result->fetchAll();
        
        // Get slow queries information (if available)
        $metrics['slow_queries'] = [];
        try {
            $slow_query = "SHOW VARIABLES LIKE 'slow_query_log'";
            $slow_result = $pdo->query($slow_query);
            $slow_log_status = $slow_result->fetch();
            $metrics['slow_log_enabled'] = $slow_log_status['Value'] ?? 'OFF';
        } catch (Exception $e) {
            $metrics['slow_log_enabled'] = 'Unknown';
        }
        
        // Get connection information
        $connections_query = "SHOW STATUS LIKE 'Threads_connected'";
        $conn_result = $pdo->query($connections_query);
        $metrics['active_connections'] = $conn_result->fetch()['Value'] ?? 0;
        
        // Get query cache information
        try {
            $cache_query = "SHOW STATUS LIKE 'Qcache_%'";
            $cache_result = $pdo->query($cache_query);
            $cache_data = $cache_result->fetchAll();
            $metrics['query_cache'] = [];
            foreach ($cache_data as $row) {
                $metrics['query_cache'][$row['Variable_name']] = $row['Value'];
            }
        } catch (Exception $e) {
            $metrics['query_cache'] = [];
        }
        
    } catch (Exception $e) {
        $metrics['error'] = $e->getMessage();
    }
    
    return $metrics;
}

// Function to optimize database
function optimizeDatabase($pdo) {
    $results = [];
    
    try {
        // Get all tables
        $tables_query = "SHOW TABLES";
        $tables_result = $pdo->query($tables_query);
        $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            try {
                $optimize_query = "OPTIMIZE TABLE `$table`";
                $optimize_result = $pdo->query($optimize_query);
                $results[$table] = 'Optimized successfully';
            } catch (Exception $e) {
                $results[$table] = 'Error: ' . $e->getMessage();
            }
        }
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    return $results;
}

// Handle optimization request
$optimization_results = null;
if (isset($_POST['optimize_database'])) {
    $optimization_results = optimizeDatabase($pdo);
}

// Get current metrics
$metrics = getDatabaseMetrics($pdo);

// Performance recommendations
function getPerformanceRecommendations($metrics) {
    $recommendations = [];
    
    if ($metrics['database_size'] > 100) {
        $recommendations[] = [
            'type' => 'warning',
            'title' => 'Large Database Size',
            'message' => "Database size is {$metrics['database_size']} MB. Consider archiving old data.",
            'action' => 'Archive old records or implement data retention policies.'
        ];
    }
    
    if (isset($metrics['tables']) && count($metrics['tables']) > 0) {
        $largest_table = $metrics['tables'][0];
        if ($largest_table['size_mb'] > 50) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Large Table Detected',
                'message' => "Table '{$largest_table['table_name']}' is {$largest_table['size_mb']} MB.",
                'action' => 'Consider indexing or partitioning this table.'
            ];
        }
    }
    
    if ($metrics['slow_log_enabled'] === 'OFF') {
        $recommendations[] = [
            'type' => 'info',
            'title' => 'Slow Query Log Disabled',
            'message' => 'Slow query logging is disabled.',
            'action' => 'Enable slow query logging to identify performance bottlenecks.'
        ];
    }
    
    if ($metrics['active_connections'] > 20) {
        $recommendations[] = [
            'type' => 'warning',
            'title' => 'High Connection Count',
            'message' => "{$metrics['active_connections']} active connections detected.",
            'action' => 'Monitor for connection leaks or implement connection pooling.'
        ];
    }
    
    return $recommendations;
}

$recommendations = getPerformanceRecommendations($metrics);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Performance - SmartFix Admin</title>
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
            padding: 2rem;
        }
        
        .performance-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h1 {
            color: var(--dark-color);
        }
        
        .optimize-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .optimize-btn:hover {
            background: #218838;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            text-align: center;
        }
        
        .metric-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: var(--secondary-color);
        }
        
        .recommendations-section, .tables-section {
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            background: var(--light-color);
        }
        
        .section-header h2 {
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .section-header h2 i {
            margin-right: 0.75rem;
        }
        
        .recommendation {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
        }
        
        .recommendation:last-child {
            border-bottom: none;
        }
        
        .recommendation-icon {
            margin-right: 1rem;
            font-size: 1.5rem;
            margin-top: 0.25rem;
        }
        
        .recommendation-icon.warning {
            color: var(--warning-color);
        }
        
        .recommendation-icon.info {
            color: var(--info-color);
        }
        
        .recommendation-icon.danger {
            color: var(--danger-color);
        }
        
        .recommendation-content h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .recommendation-content p {
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .recommendation-content .action {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .tables-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tables-table th,
        .tables-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tables-table th {
            background: var(--light-color);
            font-weight: 600;
        }
        
        .tables-table tbody tr:hover {
            background: rgba(0, 123, 255, 0.03);
        }
        
        .size-bar {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .size-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success-color), var(--warning-color));
            transition: var(--transition);
        }
        
        .optimization-results {
            background: var(--success-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .optimization-results h3 {
            margin-bottom: 1rem;
        }
        
        .optimization-results ul {
            list-style: none;
        }
        
        .optimization-results li {
            padding: 0.25rem 0;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            background: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="performance-container">
        <a href="admin_dashboard_new.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="page-header">
            <div>
                <h1><i class="fas fa-tachometer-alt"></i> Database Performance Monitor</h1>
                <p>Monitor and optimize your SmartFix database performance</p>
            </div>
            <form method="POST">
                <button type="submit" name="optimize_database" class="optimize-btn" onclick="return confirm('This will optimize all database tables. Continue?')">
                    <i class="fas fa-wrench"></i> Optimize Database
                </button>
            </form>
        </div>
        
        <?php if ($optimization_results): ?>
        <div class="optimization-results">
            <h3><i class="fas fa-check-circle"></i> Database Optimization Results</h3>
            <ul>
                <?php foreach ($optimization_results as $table => $result): ?>
                    <li><strong><?php echo $table; ?>:</strong> <?php echo $result; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Performance Metrics -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="metric-value"><?php echo $metrics['database_size']; ?> MB</div>
                <div class="metric-label">Database Size</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-table"></i>
                </div>
                <div class="metric-value"><?php echo count($metrics['tables'] ?? []); ?></div>
                <div class="metric-label">Tables</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <div class="metric-value"><?php echo $metrics['active_connections']; ?></div>
                <div class="metric-label">Active Connections</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-value"><?php echo $metrics['slow_log_enabled']; ?></div>
                <div class="metric-label">Slow Query Log</div>
            </div>
        </div>
        
        <!-- Recommendations -->
        <?php if (!empty($recommendations)): ?>
        <div class="recommendations-section">
            <div class="section-header">
                <h2><i class="fas fa-lightbulb"></i> Performance Recommendations</h2>
            </div>
            <?php foreach ($recommendations as $rec): ?>
                <div class="recommendation">
                    <div class="recommendation-icon <?php echo $rec['type']; ?>">
                        <?php if ($rec['type'] === 'warning'): ?>
                            <i class="fas fa-exclamation-triangle"></i>
                        <?php elseif ($rec['type'] === 'info'): ?>
                            <i class="fas fa-info-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="recommendation-content">
                        <h3><?php echo $rec['title']; ?></h3>
                        <p><?php echo $rec['message']; ?></p>
                        <div class="action"><?php echo $rec['action']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Table Information -->
        <?php if (!empty($metrics['tables'])): ?>
        <div class="tables-section">
            <div class="section-header">
                <h2><i class="fas fa-table"></i> Database Tables</h2>
            </div>
            <table class="tables-table">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Rows</th>
                        <th>Size (MB)</th>
                        <th>Data (MB)</th>
                        <th>Index (MB)</th>
                        <th>Size Visualization</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $max_size = max(array_column($metrics['tables'], 'size_mb'));
                    foreach ($metrics['tables'] as $table): 
                        $size_percentage = $max_size > 0 ? ($table['size_mb'] / $max_size) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo $table['table_name']; ?></strong></td>
                            <td><?php echo number_format($table['table_rows']); ?></td>
                            <td><?php echo $table['size_mb']; ?></td>
                            <td><?php echo $table['data_size_mb']; ?></td>
                            <td><?php echo $table['index_size_mb']; ?></td>
                            <td>
                                <div class="size-bar">
                                    <div class="size-bar-fill" style="width: <?php echo $size_percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (isset($metrics['error'])): ?>
        <div class="recommendations-section">
            <div class="section-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Error</h2>
            </div>
            <div class="recommendation">
                <div class="recommendation-icon danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="recommendation-content">
                    <h3>Database Error</h3>
                    <p><?php echo $metrics['error']; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
        
        // Add some interactivity
        document.querySelectorAll('.metric-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 200);
            });
        });
    </script>
</body>
</html>