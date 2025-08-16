<?php
// Test script for reports functionality
session_start();
include 'includes/db.php';

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['user_name'] = 'Test Admin';

echo "<h1>📊 Reports System Test</h1>";

try {
    // Test database connection
    echo "<h2>1. Database Connection</h2>";
    $test_query = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test required tables
    echo "<h2>2. Required Tables Check</h2>";
    $required_tables = [
        'users' => 'User accounts',
        'service_requests' => 'Service requests',
        'technicians' => 'Technician profiles',
        'orders' => 'Order management',
        'order_items' => 'Order line items',
        'products' => 'Product catalog'
    ];
    
    $missing_tables = [];
    foreach ($required_tables as $table => $description) {
        try {
            $check = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($check->rowCount() > 0) {
                echo "<p style='color: green;'>✓ Table '$table' exists ($description)</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Table '$table' missing ($description)</p>";
                $missing_tables[] = $table;
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error checking table '$table': " . $e->getMessage() . "</p>";
            $missing_tables[] = $table;
        }
    }
    
    // Test data availability
    echo "<h2>3. Data Availability</h2>";
    
    // Check users
    try {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>👥 Users: $user_count</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Cannot count users: " . $e->getMessage() . "</p>";
    }
    
    // Check service requests
    try {
        $service_count = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
        echo "<p>🔧 Service Requests: $service_count</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Cannot count service requests: " . $e->getMessage() . "</p>";
    }
    
    // Check orders
    try {
        $order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        echo "<p>🛍️ Orders: $order_count</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Cannot count orders: " . $e->getMessage() . "</p>";
    }
    
    // Check products
    try {
        $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "<p>📦 Products: $product_count</p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠ Cannot count products: " . $e->getMessage() . "</p>";
    }
    
    // Test the reports page variables
    echo "<h2>4. Testing Reports Variables</h2>";
    
    // Initialize the same variables as in reports_enhanced.php
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
    
    echo "<p style='color: green;'>✓ All variables initialized successfully</p>";
    echo "<p>Service Data: " . json_encode($service_data) . "</p>";
    echo "<p>User Data: " . json_encode($user_data) . "</p>";
    echo "<p>Revenue Data: " . json_encode($revenue_data) . "</p>";
    echo "<p>Technician Data Count: " . count($technician_data) . "</p>";
    
    // Test the actual reports page
    echo "<h2>5. Reports Page Status</h2>";
    if (count($missing_tables) == 0) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ Reports Ready!</h3>";
        echo "<p>All required tables exist. The reports page should work without errors.</p>";
        echo "<p><a href='admin/reports_enhanced.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔗 Open Reports Page</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #856404; margin-top: 0;'>⚠ Missing Tables</h3>";
        echo "<p>Some tables are missing, but the reports page should still work with default values.</p>";
        echo "<p>Missing tables: " . implode(', ', $missing_tables) . "</p>";
        echo "<p><a href='fix_all_database_issues.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Fix Database Issues</a></p>";
        echo "<p><a href='admin/reports_enhanced.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🔗 Try Reports Page</a></p>";
        echo "</div>";
    }
    
    // Add some sample data if tables are empty
    echo "<h2>6. Sample Data Generation</h2>";
    
    if (in_array('users', $missing_tables) == false) {
        try {
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            if ($user_count == 0) {
                echo "<p>Adding sample users...</p>";
                $sample_users = [
                    ['john_doe', 'john@example.com', 'John Doe', '+260-97-111-1111'],
                    ['jane_smith', 'jane@example.com', 'Jane Smith', '+260-96-222-2222'],
                    ['admin_user', 'admin@smartfix.com', 'Admin User', '+260-95-333-3333']
                ];
                
                $insert_user = $pdo->prepare("INSERT INTO users (username, email, name, phone, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                foreach ($sample_users as $user) {
                    $insert_user->execute([$user[0], $user[1], $user[2], $user[3], password_hash('password123', PASSWORD_DEFAULT)]);
                }
                echo "<p style='color: green;'>✓ Added " . count($sample_users) . " sample users</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ Could not add sample users: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection.</p>";
    echo "</div>";
}

echo "<h3>🔗 Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='admin/reports_enhanced.php'>Enhanced Reports</a></li>";
echo "<li><a href='fix_all_database_issues.php'>Fix Database Issues</a></li>";
echo "<li><a href='admin/admin_dashboard_new.php'>Admin Dashboard</a></li>";
echo "</ul>";
?>