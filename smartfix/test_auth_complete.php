<?php
// Complete Authentication System Test
include('includes/db.php');

echo "<h2>SmartFix Authentication System Test</h2>";

try {
    // Test 1: Check if all required tables exist
    echo "<h3>1. Database Tables Check</h3>";
    
    $required_tables = ['users', 'admins', 'service_requests', 'notifications', 'products', 'orders', 'order_items', 'messages', 'reviews'];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    }
    
    // Test 2: Check admin accounts
    echo "<h3>2. Admin Accounts Check</h3>";
    $stmt = $pdo->query("SELECT username, email, role FROM admins");
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "<p>✅ Found " . count($admins) . " admin account(s):</p>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>Username: {$admin['username']}, Email: {$admin['email']}, Role: {$admin['role']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No admin accounts found</p>";
    }
    
    // Test 3: Check user accounts
    echo "<h3>3. User Accounts Check</h3>";
    $stmt = $pdo->query("SELECT name, email, user_type, is_verified FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<p>✅ Found " . count($users) . " user account(s):</p>";
        echo "<ul>";
        foreach ($users as $user) {
            $verified = $user['is_verified'] ? 'Verified' : 'Not Verified';
            echo "<li>Name: {$user['name']}, Email: {$user['email']}, Type: {$user['user_type']}, Status: $verified</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ No user accounts found</p>";
    }
    
    // Test 4: Check security tables
    echo "<h3>4. Security Tables Check</h3>";
    $security_tables = ['rate_limits', 'audit_logs', 'csrf_tokens'];
    
    foreach ($security_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Security table '$table' exists</p>";
        } else {
            echo "<p>❌ Security table '$table' missing</p>";
        }
    }
    
    // Test 5: Check file permissions
    echo "<h3>5. File Permissions Check</h3>";
    
    $critical_files = [
        'auth.php' => 'Main authentication file',
        'includes/db.php' => 'Database connection',
        'includes/SecurityManager.php' => 'Security manager',
        'includes/EmailVerification.php' => 'Email verification',
        'user/dashboard.php' => 'User dashboard',
        'admin/admin_dashboard_new.php' => 'Admin dashboard',
        'logout.php' => 'Logout handler'
    ];
    
    foreach ($critical_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ $description ($file) exists</p>";
        } else {
            echo "<p>❌ $description ($file) missing</p>";
        }
    }
    
    // Test 6: Test login URLs
    echo "<h3>6. Authentication URLs</h3>";
    echo "<p>✅ <a href='auth.php?form=login' target='_blank'>User Login</a></p>";
    echo "<p>✅ <a href='auth.php?form=register' target='_blank'>User Registration</a></p>";
    echo "<p>✅ <a href='auth.php?form=admin' target='_blank'>Admin Login</a></p>";
    echo "<p>✅ <a href='admin/admin_register.php' target='_blank'>Admin Registration</a></p>";
    
    // Test 7: Sample credentials
    echo "<h3>7. Test Credentials</h3>";
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Admin Login:</h4>";
    echo "<p>URL: <a href='auth.php?form=admin'>auth.php?form=admin</a><br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong></p>";
    echo "</div>";
    
    echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Test User Login:</h4>";
    echo "<p>URL: <a href='auth.php?form=login'>auth.php?form=login</a><br>";
    echo "Username: <strong>john_doe</strong><br>";
    echo "Password: <strong>password123</strong></p>";
    echo "</div>";
    
    echo "<div style='background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Admin Registration Code:</h4>";
    echo "<p>For new admin registration: <strong>SMARTFIX2023</strong></p>";
    echo "</div>";
    
    echo "<h3>🎉 Authentication System Test Complete!</h3>";
    echo "<p><a href='auth.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Login</a>";
    echo "<a href='setup_complete_auth_system.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Setup Again</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please run the setup script first: <a href='setup_complete_auth_system.php'>setup_complete_auth_system.php</a></p>";
}
?>