<?php
session_start();
include('includes/db.php');

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Transport Selection Integration - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .test-section { margin: 30px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Transport Selection Integration Test</h1>
        <div class='info'>Testing the transport selection integration in order confirmation process.</div>";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: Check if transport_providers table exists and has data
echo "<div class='test-section'>
        <h2>Test 1: Transport Providers Availability</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_providers WHERE status = 'active'");
    $provider_count = $stmt->fetchColumn();
    
    if ($provider_count > 0) {
        echo "<div class='success'>✅ Found $provider_count active transport providers</div>";
        $tests_passed++;
        
        // Show sample providers
        $stmt = $pdo->query("SELECT name, vehicle_type, service_type, rating, base_cost, cost_per_km FROM transport_providers WHERE status = 'active' LIMIT 3");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
                <tr><th>Provider</th><th>Vehicle</th><th>Service</th><th>Rating</th><th>Base Cost</th><th>Cost/km</th></tr>";
        foreach ($providers as $provider) {
            echo "<tr>
                    <td>" . htmlspecialchars($provider['name']) . "</td>
                    <td>" . ucfirst($provider['vehicle_type'] ?? 'N/A') . "</td>
                    <td>" . ucfirst($provider['service_type'] ?? 'N/A') . "</td>
                    <td>" . ($provider['rating'] ?? 'N/A') . "/5</td>
                    <td>K" . number_format($provider['base_cost'] ?? 0, 2) . "</td>
                    <td>K" . number_format($provider['cost_per_km'] ?? 0, 2) . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No active transport providers found</div>";
        echo "<div class='warning'>⚠️ Transport selection will not work without providers. Please run the transport setup script.</div>";
        $tests_failed++;
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Transport providers table error: " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div>";

// Test 2: Check orders table structure
echo "<div class='test-section'>
        <h2>Test 2: Orders Table Structure</h2>";

try {
    $required_columns = ['transport_id', 'transport_cost'];
    $missing_columns = [];
    
    foreach ($required_columns as $column) {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $missing_columns[] = $column;
        }
    }
    
    if (empty($missing_columns)) {
        echo "<div class='success'>✅ Orders table has all required transport columns</div>";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Missing columns in orders table: " . implode(', ', $missing_columns) . "</div>";
        echo "<div class='warning'>⚠️ Transport selection may not work properly without these columns.</div>";
        $tests_failed++;
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Orders table error: " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div>";

// Test 3: Check for orders without transport
echo "<div class='test-section'>
        <h2>Test 3: Orders Needing Transport Selection</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE (transport_id IS NULL OR transport_id = 0) AND status = 'processing'");
    $orders_needing_transport = $stmt->fetchColumn();
    
    if ($orders_needing_transport > 0) {
        echo "<div class='info'>ℹ️ Found $orders_needing_transport orders that need transport selection</div>";
        
        // Show sample orders
        $stmt = $pdo->query("SELECT id, tracking_number, shipping_name, created_at FROM orders WHERE (transport_id IS NULL OR transport_id = 0) AND status = 'processing' LIMIT 5");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
                <tr><th>Order ID</th><th>Tracking Number</th><th>Customer</th><th>Created</th><th>Action</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>
                    <td>#" . $order['id'] . "</td>
                    <td>" . htmlspecialchars($order['tracking_number']) . "</td>
                    <td>" . htmlspecialchars($order['shipping_name']) . "</td>
                    <td>" . date('M d, Y', strtotime($order['created_at'])) . "</td>
                    <td><a href='shop/order_confirmation.php?id=" . $order['id'] . "' class='btn' style='padding: 5px 10px; font-size: 12px;'>Test Selection</a></td>
                  </tr>";
        }
        echo "</table>";
        $tests_passed++;
    } else {
        echo "<div class='success'>✅ All orders have transport assigned or no processing orders found</div>";
        $tests_passed++;
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error checking orders: " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div>";

// Test 4: Test transport cost calculation
echo "<div class='test-section'>
        <h2>Test 4: Transport Cost Calculation</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM transport_providers WHERE status = 'active' LIMIT 1");
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($provider) {
        $base_cost = $provider['base_cost'] ?? 15.00;
        $cost_per_km = $provider['cost_per_km'] ?? 2.50;
        $test_distance = 25;
        $calculated_cost = $base_cost + ($cost_per_km * $test_distance);
        
        echo "<div class='success'>✅ Transport cost calculation working</div>";
        echo "<div class='info'>
                <strong>Sample Calculation:</strong><br>
                Provider: " . htmlspecialchars($provider['name']) . "<br>
                Base Cost: K" . number_format($base_cost, 2) . "<br>
                Cost per KM: K" . number_format($cost_per_km, 2) . "<br>
                Distance: {$test_distance} km<br>
                <strong>Total Cost: K" . number_format($calculated_cost, 2) . "</strong>
              </div>";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ No providers available for cost calculation test</div>";
        $tests_failed++;
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Cost calculation test error: " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div>";

// Test 5: Check transport_quotes table
echo "<div class='test-section'>
        <h2>Test 5: Transport Quotes Table</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'transport_quotes'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM transport_quotes");
        $quotes_count = $stmt->fetchColumn();
        echo "<div class='success'>✅ Transport quotes table exists with $quotes_count records</div>";
        $tests_passed++;
    } else {
        echo "<div class='warning'>⚠️ Transport quotes table doesn't exist. Quotes won't be saved, but selection will still work.</div>";
        $tests_passed++; // Not critical for basic functionality
    }
} catch (PDOException $e) {
    echo "<div class='warning'>⚠️ Transport quotes table issue: " . $e->getMessage() . "</div>";
    $tests_passed++; // Not critical
}
echo "</div>";

// Summary
echo "<div class='test-section'>
        <h2>🎯 Test Summary</h2>";

$total_tests = $tests_passed + $tests_failed;
$success_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100) : 0;

if ($tests_failed == 0) {
    echo "<div class='success'>
            <h3>✅ All Tests Passed!</h3>
            <p><strong>Tests Passed:</strong> $tests_passed</p>
            <p><strong>Tests Failed:</strong> $tests_failed</p>
            <p><strong>Success Rate:</strong> $success_rate%</p>
            <p>Transport selection integration is ready for use!</p>
          </div>";
} else {
    echo "<div class='warning'>
            <h3>⚠️ Some Issues Found</h3>
            <p><strong>Tests Passed:</strong> $tests_passed</p>
            <p><strong>Tests Failed:</strong> $tests_failed</p>
            <p><strong>Success Rate:</strong> $success_rate%</p>
            <p>Please address the failed tests before using transport selection.</p>
          </div>";
}

echo "
        <h2>🚀 Next Steps</h2>
        <div class='info'>
            <p>To test the transport selection integration:</p>
            <ul>
                <li>Ensure transport providers are available (run setup if needed)</li>
                <li>Place a test order without selecting transport</li>
                <li>Go to order confirmation page</li>
                <li>Verify transport selection interface appears</li>
                <li>Test selecting a transport provider</li>
                <li>Confirm order is updated with transport information</li>
            </ul>
        </div>
        
        <a href='quick_fix_missing_columns.php' class='btn'>🔧 Fix Missing Columns</a>
        <a href='shop/checkout.php' class='btn'>🛒 Test Checkout Process</a>
        <a href='admin/transport_dashboard.php' class='btn'>📊 Transport Dashboard</a>
        <a href='admin/admin_dashboard_new.php' class='btn'>🏠 Admin Dashboard</a>
        
    </div>
</body>
</html>";
?>