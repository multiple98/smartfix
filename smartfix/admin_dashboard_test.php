<?php
session_start();
include('includes/db.php');

echo "<h1>SmartFix Admin Dashboard Diagnostic Test</h1>";

// Test database connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $test_query = "SELECT 1";
    $stmt = $pdo->prepare($test_query);
    $stmt->execute();
    echo "✅ PDO Database connection: <strong>Working</strong><br>";
    
    $mysqli_test = mysqli_query($conn, "SELECT 1");
    if ($mysqli_test) {
        echo "✅ MySQLi Database connection: <strong>Working</strong><br>";
    } else {
        echo "❌ MySQLi Database connection: <strong>Failed</strong><br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection: <strong>Failed</strong> - " . $e->getMessage() . "<br>";
}

// Test database structure
echo "<h2>2. Database Structure Test</h2>";
$tables_to_check = ['users', 'technicians', 'service_requests', 'products', 'notifications', 'admins'];
foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table': <strong>Exists</strong><br>";
        } else {
            echo "❌ Table '$table': <strong>Missing</strong><br>";
        }
    } catch (Exception $e) {
        echo "❌ Table '$table': <strong>Error</strong> - " . $e->getMessage() . "<br>";
    }
}

// Test admin authentication system
echo "<h2>3. Admin Authentication Test</h2>";
try {
    $admin_check = $pdo->prepare("SHOW TABLES LIKE 'admins'");
    $admin_check->execute();
    if ($admin_check->rowCount() > 0) {
        echo "✅ Admin table: <strong>Exists</strong><br>";
        
        // Check for admin records
        $admin_count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        echo "📊 Admin records: <strong>$admin_count</strong><br>";
    } else {
        echo "❌ Admin table: <strong>Missing</strong><br>";
        echo "ℹ️ System will fall back to hardcoded admin (username: admin, password: 1234)<br>";
    }
} catch (Exception $e) {
    echo "❌ Admin authentication test: <strong>Failed</strong> - " . $e->getMessage() . "<br>";
}

// Test session status
echo "<h2>4. Session Status Test</h2>";
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo "✅ Admin session: <strong>Active</strong><br>";
    echo "👤 Admin name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
    echo "📧 Admin email: " . ($_SESSION['admin_email'] ?? 'Not set') . "<br>";
} else {
    echo "❌ Admin session: <strong>Not logged in</strong><br>";
    echo "<a href='auth.php?form=admin' style='color: #007BFF; text-decoration: none; padding: 8px 16px; background: #f8f9fa; border: 1px solid #007BFF; border-radius: 4px;'>Login as Admin</a><br>";
}

// Test file permissions
echo "<h2>5. File Permissions Test</h2>";
$dirs_to_check = ['uploads', 'cache'];
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir': <strong>Writable</strong><br>";
        } else {
            echo "❌ Directory '$dir': <strong>Not writable</strong><br>";
        }
    } else {
        echo "⚠️ Directory '$dir': <strong>Does not exist</strong><br>";
    }
}

// Test key admin files
echo "<h2>6. Admin Files Test</h2>";
$admin_files = [
    'admin/admin_dashboard_new.php',
    'admin/manage_products.php',
    'admin/manage_users.php',
    'admin/service_requests.php',
    'auth.php'
];

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        echo "✅ File '$file': <strong>Exists</strong><br>";
    } else {
        echo "❌ File '$file': <strong>Missing</strong><br>";
    }
}

// Test counts for dashboard
echo "<h2>7. Dashboard Data Test</h2>";
try {
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "👥 Total Users: <strong>$users_count</strong><br>";
    
    $requests_count = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
    echo "📋 Service Requests: <strong>$requests_count</strong><br>";
    
    try {
        $products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "🛍️ Products: <strong>$products_count</strong><br>";
    } catch (Exception $e) {
        echo "❌ Products table: <strong>Error</strong> - " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Dashboard data test: <strong>Failed</strong> - " . $e->getMessage() . "<br>";
}

// Conclusion
echo "<h2>8. Overall Status</h2>";
echo "<p>If all tests show ✅, your admin dashboard should be working properly.</p>";
echo "<p>If you see ❌ or ⚠️, please address those issues first.</p>";

echo "<hr>";
echo "<a href='admin/admin_dashboard_new.php' style='color: white; background: #007BFF; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Admin Dashboard</a>";
echo "<a href='auth.php?form=admin' style='color: #007BFF; background: #f8f9fa; padding: 10px 20px; text-decoration: none; border: 1px solid #007BFF; border-radius: 4px;'>Admin Login</a>";
?>