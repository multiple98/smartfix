<?php
// Final test of 2FA system
echo "Testing SmartFix 2FA System\n";
echo "============================\n";

try {
    // Test connection
    include_once __DIR__ . '/includes/db.php';
    echo "✓ Database connection working\n";
    
    // Test 2FA class
    include_once __DIR__ . '/includes/TwoFactorAuth.php';
    $twoFA = new TwoFactorAuth($pdo);
    echo "✓ TwoFactorAuth class loaded\n";
    
    // Test code generation
    $code = $twoFA->generateCode();
    echo "✓ Generated code: $code\n";
    
    // Test code storage
    $userId = 123;
    $storedCode = $twoFA->generateAndStore2FACode($userId);
    if ($storedCode) {
        echo "✓ Code stored: $storedCode\n";
        
        // Test verification
        if ($twoFA->verifyCode($userId, $storedCode)) {
            echo "✓ Code verification: PASSED\n";
            echo "\n=== ALL TESTS PASSED ===\n";
            echo "Your 2FA system is working correctly!\n";
        } else {
            echo "✗ Code verification failed\n";
        }
    } else {
        echo "✗ Failed to store code\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nIf you see this error, run fix_mysql_complete.bat first.\n";
}
?>