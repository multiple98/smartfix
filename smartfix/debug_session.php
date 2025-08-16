<?php
session_start();

echo "<h1>SmartFix Session Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
.session-info { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
.session-var { padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 4px solid #007BFF; }
.missing { border-left-color: #dc3545; background: #fff5f5; }
.present { border-left-color: #28a745; background: #f0fff0; }
h2 { color: #004080; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
.status { font-weight: bold; }
.status.good { color: #28a745; }
.status.warning { color: #ffc107; }
.status.error { color: #dc3545; }
</style>";

echo "<div class='session-info'>";
echo "<h2>Session Status</h2>";

if (session_status() == PHP_SESSION_ACTIVE) {
    echo "<p class='status good'>✓ Session is active</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} else {
    echo "<p class='status error'>✗ Session is not active</p>";
}

echo "</div>";

echo "<div class='session-info'>";
echo "<h2>All Session Variables</h2>";

if (empty($_SESSION)) {
    echo "<p class='status warning'>⚠ No session variables found</p>";
} else {
    echo "<p class='status good'>✓ Found " . count($_SESSION) . " session variables</p>";
    
    foreach ($_SESSION as $key => $value) {
        $displayValue = is_array($value) ? json_encode($value) : htmlspecialchars($value);
        echo "<div class='session-var present'><strong>$key:</strong> $displayValue</div>";
    }
}

echo "</div>";

echo "<div class='session-info'>";
echo "<h2>Required Session Variables Check</h2>";

$required_vars = [
    'user_id' => 'User ID',
    'user_name' => 'User Name',
    'user_email' => 'User Email',
    'user_type' => 'User Type'
];

$all_present = true;

foreach ($required_vars as $var => $description) {
    if (isset($_SESSION[$var]) && !empty($_SESSION[$var])) {
        echo "<div class='session-var present'>✓ <strong>$description ($var):</strong> " . htmlspecialchars($_SESSION[$var]) . "</div>";
    } else {
        echo "<div class='session-var missing'>✗ <strong>$description ($var):</strong> Missing or empty</div>";
        $all_present = false;
    }
}

if ($all_present) {
    echo "<p class='status good'>✓ All required session variables are present</p>";
} else {
    echo "<p class='status error'>✗ Some required session variables are missing</p>";
}

echo "</div>";

echo "<div class='session-info'>";
echo "<h2>Alternative Session Variables</h2>";

$alternative_vars = [
    'username' => 'Username (alternative to user_name)',
    'email' => 'Email (alternative to user_email)',
    'logged_in' => 'Logged In Status',
    'admin_logged_in' => 'Admin Logged In Status',
    'role' => 'User Role'
];

foreach ($alternative_vars as $var => $description) {
    if (isset($_SESSION[$var])) {
        $displayValue = is_bool($_SESSION[$var]) ? ($_SESSION[$var] ? 'true' : 'false') : htmlspecialchars($_SESSION[$var]);
        echo "<div class='session-var present'>✓ <strong>$description ($var):</strong> $displayValue</div>";
    } else {
        echo "<div class='session-var'>- <strong>$description ($var):</strong> Not set</div>";
    }
}

echo "</div>";

echo "<div class='session-info'>";
echo "<h2>Recommendations</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p class='status error'>⚠ You are not logged in. Please <a href='auth.php'>login</a> first.</p>";
} else {
    if (!isset($_SESSION['user_name']) && isset($_SESSION['username'])) {
        echo "<p class='status warning'>⚠ Using 'username' instead of 'user_name' - this is handled by fallback code.</p>";
    }
    
    if (!isset($_SESSION['user_email']) && isset($_SESSION['email'])) {
        echo "<p class='status warning'>⚠ Using 'email' instead of 'user_email' - this is handled by fallback code.</p>";
    }
    
    if (!isset($_SESSION['user_type'])) {
        echo "<p class='status warning'>⚠ 'user_type' not set - defaulting to 'user'.</p>";
    }
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_name']) && isset($_SESSION['user_email'])) {
        echo "<p class='status good'>✓ Session looks good! You should be able to access the dashboard.</p>";
    }
}

echo "</div>";

echo "<div class='session-info'>";
echo "<h2>Quick Actions</h2>";
echo "<a href='auth.php' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Login</a> ";
echo "<a href='dashboard.php' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Dashboard</a> ";
echo "<a href='logout.php' style='background:#dc3545;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Logout</a> ";
echo "<a href='index.php' style='background:#6c757d;color:white;padding:10px 15px;text-decoration:none;margin:5px;border-radius:5px;'>Home</a>";
echo "</div>";
?>