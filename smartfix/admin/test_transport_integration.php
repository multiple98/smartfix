<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Transport Integration Test - SmartFix Admin</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #007BFF; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; }
        .btn { display: inline-block; padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1><i class='fas fa-truck'></i> Transport System Integration Test</h1>
            <p>Testing transport system integration with admin dashboard</p>
        </div>";

// Test 1: Check if transport tables exist
echo "<div class='test-section'>
        <h2><i class='fas fa-database'></i> Database Tables Test</h2>";

$tables_to_check = ['transport_providers', 'transport_quotes', 'delivery_tracking'];
$tables_status = [];

foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $tables_status[$table] = 'exists';
            echo "<p class='success'><i class='fas fa-check'></i> Table '$table' exists</p>";
        } else {
            $tables_status[$table] = 'missing';
            echo "<p class='error'><i class='fas fa-times'></i> Table '$table' is missing</p>";
        }
    } catch (PDOException $e) {
        $tables_status[$table] = 'error';
        echo "<p class='error'><i class='fas fa-exclamation-triangle'></i> Error checking table '$table': " . $e->getMessage() . "</p>";
    }
}
echo "</div>";

// Test 2: Check transport providers data
if ($tables_status['transport_providers'] === 'exists') {
    echo "<div class='test-section'>
            <h2><i class='fas fa-truck'></i> Transport Providers Test</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_providers");
        $provider_count = $stmt->fetchColumn();
        
        if ($provider_count > 0) {
            echo "<p class='success'><i class='fas fa-check'></i> Found $provider_count transport providers</p>";
            
            // Show sample providers
            $stmt = $pdo->query("SELECT name, status, vehicle_type, service_type, rating FROM transport_providers LIMIT 5");
            $providers = $stmt->fetchAll();
            
            echo "<table>
                    <tr><th>Name</th><th>Status</th><th>Vehicle Type</th><th>Service Type</th><th>Rating</th></tr>";
            foreach ($providers as $provider) {
                echo "<tr>
                        <td>" . htmlspecialchars($provider['name']) . "</td>
                        <td><span class='" . ($provider['status'] === 'active' ? 'success' : 'warning') . "'>" . ucfirst($provider['status']) . "</span></td>
                        <td>" . ucfirst($provider['vehicle_type']) . "</td>
                        <td>" . ucfirst($provider['service_type']) . "</td>
                        <td>" . $provider['rating'] . "/5</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'><i class='fas fa-exclamation-triangle'></i> No transport providers found. Run the setup script first.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'><i class='fas fa-times'></i> Error checking providers: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

// Test 3: Check transport quotes
if ($tables_status['transport_quotes'] === 'exists') {
    echo "<div class='test-section'>
            <h2><i class='fas fa-quote-right'></i> Transport Quotes Test</h2>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_quotes");
        $quotes_count = $stmt->fetchColumn();
        
        echo "<p class='success'><i class='fas fa-check'></i> Found $quotes_count transport quotes</p>";
        
        if ($quotes_count > 0) {
            $stmt = $pdo->query("SELECT tq.*, tp.name as provider_name FROM transport_quotes tq 
                                LEFT JOIN transport_providers tp ON tq.transport_provider_id = tp.id 
                                ORDER BY tq.created_at DESC LIMIT 5");
            $quotes = $stmt->fetchAll();
            
            echo "<table>
                    <tr><th>Order ID</th><th>Provider</th><th>Distance</th><th>Cost</th><th>Status</th><th>Created</th></tr>";
            foreach ($quotes as $quote) {
                echo "<tr>
                        <td>#" . $quote['order_id'] . "</td>
                        <td>" . htmlspecialchars($quote['provider_name'] ?? 'N/A') . "</td>
                        <td>" . $quote['distance_km'] . " km</td>
                        <td>K" . number_format($quote['estimated_cost'], 2) . "</td>
                        <td><span class='" . ($quote['status'] === 'accepted' ? 'success' : 'warning') . "'>" . ucfirst($quote['status']) . "</span></td>
                        <td>" . date('M d, Y H:i', strtotime($quote['created_at'])) . "</td>
                      </tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'><i class='fas fa-times'></i> Error checking quotes: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

// Test 4: Check admin dashboard integration
echo "<div class='test-section'>
        <h2><i class='fas fa-tachometer-alt'></i> Admin Dashboard Integration Test</h2>";

try {
    // Test transport statistics queries
    $stats_queries = [
        'total_providers' => "SELECT COUNT(*) as count FROM transport_providers",
        'active_providers' => "SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'",
        'total_quotes' => "SELECT COUNT(*) as count FROM transport_quotes",
        'pending_deliveries' => "SELECT COUNT(*) as count FROM delivery_tracking WHERE status IN ('pickup_scheduled', 'in_transit', 'out_for_delivery')"
    ];
    
    $stats_results = [];
    foreach ($stats_queries as $stat_name => $query) {
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $stats_results[$stat_name] = $stmt->fetchColumn();
            echo "<p class='success'><i class='fas fa-check'></i> " . ucwords(str_replace('_', ' ', $stat_name)) . ": " . $stats_results[$stat_name] . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'><i class='fas fa-times'></i> Error getting $stat_name: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'><i class='fas fa-times'></i> Error testing dashboard integration: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Navigation links test
echo "<div class='test-section'>
        <h2><i class='fas fa-link'></i> Navigation Links Test</h2>";

$links_to_test = [
    'Transport Dashboard' => 'transport_dashboard.php',
    'Smart Transport Selector' => '../smart_transport_selector.php',
    'Transport Quotes' => '../transport_quotes.php',
    'Admin Dashboard' => 'admin_dashboard_new.php'
];

foreach ($links_to_test as $link_name => $link_path) {
    if (file_exists($link_path)) {
        echo "<p class='success'><i class='fas fa-check'></i> $link_name file exists</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times'></i> $link_name file missing: $link_path</p>";
    }
}
echo "</div>";

// Action buttons
echo "<div class='test-section'>
        <h2><i class='fas fa-tools'></i> Quick Actions</h2>
        <a href='admin_dashboard_new.php' class='btn'><i class='fas fa-tachometer-alt'></i> Go to Admin Dashboard</a>
        <a href='transport_dashboard.php' class='btn'><i class='fas fa-truck'></i> Open Transport Dashboard</a>
        <a href='../enhanced_transport_system.php' class='btn'><i class='fas fa-database'></i> Initialize Transport System</a>
        <a href='../test_transport_system.php' class='btn'><i class='fas fa-vial'></i> Test Transport System</a>
      </div>";

echo "    </div>
</body>
</html>";
?>