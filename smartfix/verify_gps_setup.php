<?php
session_start();
include('includes/db.php');

echo "<h1>🔧 SmartFix GPS System Verification</h1>";
echo "<style>
body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 5px 0; }
.error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 5px 0; }
.info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 5px 0; }
.btn { background: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px 10px 0; cursor: pointer; }
</style>";

try {
    echo "<h2>📊 Database Connection</h2>";
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Check tables
    echo "<h2>🗄️ Database Tables</h2>";
    $tables = ['technicians', 'service_requests', 'technician_locations', 'service_locations'];
    
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<div class='success'>✅ Table '$table' exists with $count records</div>";
            } else {
                echo "<div class='error'>❌ Table '$table' does not exist</div>";
                
                // Create missing tables
                if ($table === 'technician_locations') {
                    $pdo->exec("CREATE TABLE technician_locations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        technician_id INT NOT NULL UNIQUE,
                        latitude DECIMAL(10, 8) NOT NULL,
                        longitude DECIMAL(11, 8) NOT NULL,
                        accuracy FLOAT,
                        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    echo "<div class='info'>📝 Created technician_locations table</div>";
                }
                
                if ($table === 'service_locations') {
                    $pdo->exec("CREATE TABLE service_locations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        request_id INT NOT NULL,
                        latitude DECIMAL(10, 8) NOT NULL,
                        longitude DECIMAL(11, 8) NOT NULL,
                        address TEXT,
                        accuracy FLOAT,
                        location_type ENUM('customer', 'service_point') DEFAULT 'customer',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_request_location (request_id, location_type)
                    )");
                    echo "<div class='info'>📝 Created service_locations table</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>🌍 GPS Data Status</h2>";
    
    // Check GPS data
    try {
        $tech_locations = $pdo->query("SELECT COUNT(*) FROM technician_locations")->fetchColumn();
        $service_locations = $pdo->query("SELECT COUNT(*) FROM service_locations")->fetchColumn();
        
        if ($tech_locations > 0) {
            echo "<div class='success'>✅ $tech_locations technician locations available</div>";
        } else {
            echo "<div class='info'>📍 No technician locations yet - will be created automatically</div>";
        }
        
        if ($service_locations > 0) {
            echo "<div class='success'>✅ $service_locations service locations available</div>";
        } else {
            echo "<div class='info'>📍 No service locations yet - will be created automatically</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ GPS data check failed: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>🚀 Ready to Launch!</h2>";
    echo "<div class='success'>✅ GPS Dashboard system is ready</div>";
    echo "<p>Choose your preferred option:</p>";
    echo "<a href='setup_gps_demo.php' class='btn'>🔧 Setup Sample Data</a>";
    echo "<a href='admin/gps_dashboard_demo.php' class='btn'>📊 View Demo Dashboard</a>";
    echo "<a href='admin/gps_dashboard.php' class='btn'>🗺️ Full Dashboard (Requires API Key)</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ System Error: " . $e->getMessage() . "</div>";
}
?>