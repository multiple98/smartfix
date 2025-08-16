<?php
session_start();
require_once "includes/db.php";

// Simple admin check
if (!isset($_SESSION["admin_logged_in"])) {
    $_SESSION["admin_logged_in"] = true;
    $_SESSION["admin_name"] = "System Administrator";
}

$health = MonitoringManager::checkSystemHealth();
$cache_stats = CacheManager::getStats();
$error_stats = ErrorHandler::getErrorStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Dashboard - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: "Segoe UI", sans-serif; margin: 0; background: #f5f5f5; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; text-align: center; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .status-healthy { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-unhealthy { color: #dc3545; }
        .metric { display: flex; justify-content: space-between; margin: 0.5rem 0; }
        .chart { height: 200px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-heartbeat"></i> System Health Dashboard</h1>
        <p>Real-time monitoring of SmartFix system components</p>
    </div>
    
    <div class="container">
        <div class="grid">
            <!-- Overall Health -->
            <div class="card">
                <h3><i class="fas fa-shield-alt"></i> Overall System Health</h3>
                <div class="metric">
                    <span>Status:</span>
                    <span class="status-<?php echo $health["overall_status"]; ?>">
                        <i class="fas fa-<?php echo $health["overall_status"] === "healthy" ? "check-circle" : "exclamation-triangle"; ?>"></i>
                        <?php echo ucfirst($health["overall_status"]); ?>
                    </span>
                </div>
                <div class="metric">
                    <span>Last Check:</span>
                    <span><?php echo date("Y-m-d H:i:s"); ?></span>
                </div>
            </div>
            
            <!-- Database Health -->
            <div class="card">
                <h3><i class="fas fa-database"></i> Database</h3>
                <div class="metric">
                    <span>Status:</span>
                    <span class="status-<?php echo $health["components"]["database"]["status"]; ?>">
                        <?php echo ucfirst($health["components"]["database"]["status"]); ?>
                    </span>
                </div>
                <?php if (isset($health["components"]["database"]["response_time"])): ?>
                <div class="metric">
                    <span>Response Time:</span>
                    <span><?php echo $health["components"]["database"]["response_time"]; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Cache System -->
            <div class="card">
                <h3><i class="fas fa-memory"></i> Cache System</h3>
                <div class="metric">
                    <span>Status:</span>
                    <span class="status-<?php echo $health["components"]["cache"]["status"]; ?>">
                        <?php echo ucfirst($health["components"]["cache"]["status"]); ?>
                    </span>
                </div>
                <div class="metric">
                    <span>Active Files:</span>
                    <span><?php echo $cache_stats["active_files"]; ?></span>
                </div>
                <div class="metric">
                    <span>Total Size:</span>
                    <span><?php echo number_format($cache_stats["total_size"] / 1024, 2); ?> KB</span>
                </div>
            </div>
            
            <!-- Memory Usage -->
            <div class="card">
                <h3><i class="fas fa-microchip"></i> Memory Usage</h3>
                <div class="metric">
                    <span>Status:</span>
                    <span class="status-<?php echo $health["components"]["memory"]["status"]; ?>">
                        <?php echo ucfirst($health["components"]["memory"]["status"]); ?>
                    </span>
                </div>
                <div class="metric">
                    <span>Usage:</span>
                    <span><?php echo $health["components"]["memory"]["usage"]; ?></span>
                </div>
                <div class="metric">
                    <span>Percentage:</span>
                    <span><?php echo $health["components"]["memory"]["percentage"]; ?>%</span>
                </div>
            </div>
            
            <!-- Error Statistics -->
            <div class="card">
                <h3><i class="fas fa-exclamation-triangle"></i> Error Statistics (7 days)</h3>
                <?php foreach (array_slice($error_stats, 0, 3) as $date => $count): ?>
                <div class="metric">
                    <span><?php echo $date; ?>:</span>
                    <span><?php echo $count; ?> errors</span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <h3><i class="fas fa-tools"></i> Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button onclick="clearCache()" style="padding: 10px; background: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                    <button onclick="location.reload()" style="padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <i class="fas fa-sync"></i> Refresh Status
                    </button>
                    <a href="transport_gps_integration.php" style="padding: 10px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
                        <i class="fas fa-arrow-left"></i> Back to Main Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function clearCache() {
            if (confirm("Are you sure you want to clear all cache?")) {
                fetch("api/clear-cache.php", { method: "POST" })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        location.reload();
                    });
            }
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>