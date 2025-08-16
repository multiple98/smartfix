<?php
include('includes/db.php');
require_once 'includes/EmailVerification.php';

echo "<h1>Email Verification System Test</h1>";

// Test 1: Check database structure
echo "<h2>1. Database Structure Check</h2>";
try {
    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll();
    
    $required_columns = ['is_verified', 'verification_token', 'verification_sent_at', 'email_verified_at'];
    $existing_columns = array_column($columns, 'Field');
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✓ All required columns exist in users table</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missing_columns) . "</p>";
        echo "<p><a href='check_users_table.php'>Fix Database Structure</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test 2: Check EmailVerification class
echo "<h2>2. EmailVerification Class Test</h2>";
try {
    $emailVerification = new EmailVerification($pdo);
    echo "<p style='color: green;'>✓ EmailVerification class loaded successfully</p>";
    
    // Test token generation
    $token = $emailVerification->generateToken();
    if (strlen($token) === 64) {
        echo "<p style='color: green;'>✓ Token generation working (length: " . strlen($token) . ")</p>";
    } else {
        echo "<p style='color: red;'>✗ Token generation issue (length: " . strlen($token) . ")</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ EmailVerification class error: " . $e->getMessage() . "</p>";
}

// Test 3: Check for unverified users
echo "<h2>3. Unverified Users Check</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_verified = 0");
    $result = $stmt->fetch();
    $unverified_count = $result['count'];
    
    if ($unverified_count > 0) {
        echo "<p style='color: orange;'>⚠ Found $unverified_count unverified user(s)</p>";
        echo "<p><a href='quick_verify.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Quick Verify Page</a></p>";
    } else {
        echo "<p style='color: green;'>✓ No unverified users found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking users: " . $e->getMessage() . "</p>";
}

// Test 4: System URLs
echo "<h2>4. System URLs Test</h2>";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "http://$host/smartfix";

$urls = [
    'Registration' => "$base_url/register.php",
    'Login' => "$base_url/login.php",
    'Quick Verify' => "$base_url/quick_verify.php",
    'Resend Verification' => "$base_url/resend_verification.php",
    'Email Verification' => "$base_url/verify_email.php"
];

echo "<ul>";
foreach ($urls as $name => $url) {
    echo "<li><a href='$url' target='_blank'>$name</a> - $url</li>";
}
echo "</ul>";

// Test 5: Debug mode status
echo "<h2>5. Debug Mode Status</h2>";
$emailVerification = new EmailVerification($pdo);
$reflection = new ReflectionClass($emailVerification);
$debugProperty = $reflection->getProperty('debug_mode');
$debugProperty->setAccessible(true);
$debugMode = $debugProperty->getValue($emailVerification);

if ($debugMode) {
    echo "<p style='color: green;'>✓ Debug mode is ENABLED - Perfect for development!</p>";
    echo "<p>This means:</p>";
    echo "<ul>";
    echo "<li>Email sending always returns success</li>";
    echo "<li>Verification tokens are always created</li>";
    echo "<li>Quick Verify Page will show all unverified accounts</li>";
    echo "<li>Debug logs are written to debug_emails.log</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠ Debug mode is DISABLED - Production mode</p>";
    echo "<p>For development, you might want to enable debug mode in EmailVerification.php</p>";
}

echo "<hr>";
echo "<h2>Quick Actions</h2>";
echo "<p>";
echo "<a href='register.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Registration</a>";
echo "<a href='quick_verify.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Quick Verify</a>";
echo "<a href='login.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Login</a>";
echo "</p>";

echo "<hr>";
echo "<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>