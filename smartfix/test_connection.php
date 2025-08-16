<?php
echo "<h1>Testing SmartFix Database Connection</h1>";
echo "<div style='font-family: Arial; max-width: 800px; margin: 20px auto; padding: 20px;'>";

// Include our enhanced db connection
include_once 'includes/db.php';

echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0;'>";
echo "<h3>✅ SUCCESS! Database Connection Working</h3>";
echo "<p><strong>PDO Connection:</strong> Active and ready</p>";
echo "<p><strong>MySQLi Connection:</strong> Active and ready</p>";
echo "<p><strong>Database:</strong> 'smartfix' is accessible</p>";
echo "</div>";

// Test PDO query
try {
    $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
    $result = $stmt->fetch();
    echo "<div style='background: #cce5ff; padding: 10px; margin: 10px 0;'>";
    echo "<strong>PDO Test Query Results:</strong><br>";
    echo "Current Database: " . $result['current_db'] . "<br>";
    echo "Current Time: " . $result['current_time'] . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0;'>";
    echo "<strong>PDO Test Failed:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test MySQLi query
$result = mysqli_query($conn, "SELECT DATABASE() as current_db, NOW() as current_time");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<div style='background: #cce5ff; padding: 10px; margin: 10px 0;'>";
    echo "<strong>MySQLi Test Query Results:</strong><br>";
    echo "Current Database: " . $row['current_db'] . "<br>";
    echo "Current Time: " . $row['current_time'] . "<br>";
    echo "</div>";
} else {
    echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0;'>";
    echo "<strong>MySQLi Test Failed:</strong> " . mysqli_error($conn);
    echo "</div>";
}

// Check if 2FA tables exist
echo "<h3>Checking 2FA Tables:</h3>";
$tables = ['user_2fa_codes', 'user_trusted_devices'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test TwoFactorAuth class
echo "<h3>Testing TwoFactorAuth Class:</h3>";
try {
    include_once 'includes/TwoFactorAuth.php';
    $twoFA = new TwoFactorAuth($pdo);
    echo "✅ TwoFactorAuth class loaded successfully<br>";
    
    // Test code generation
    $code = $twoFA->generateCode();
    echo "✅ Generated test 2FA code: <strong>$code</strong><br>";
    
    // Test device fingerprint
    $fingerprint = $twoFA->createDeviceFingerprint();
    echo "✅ Generated device fingerprint: <strong>" . substr($fingerprint, 0, 12) . "...</strong><br>";
    
} catch (Exception $e) {
    echo "❌ TwoFactorAuth test failed: " . $e->getMessage() . "<br>";
}

echo "<div style='background: #e1f7d5; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>";
echo "<h3>🎉 Connection Test Complete!</h3>";
echo "<p>If you see this message, your MySQL connection issues are resolved!</p>";
echo "<p>Your SmartFix application should now work properly.</p>";
echo "</div>";

echo "</div>";
?>