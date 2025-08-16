<?php
/**
 * SmartFix Security & Performance Setup Script
 * This script initializes all security and performance enhancements
 */

require_once 'includes/db.php';
require_once 'includes/SecurityManager.php';
require_once 'includes/PerformanceManager.php';

// Initialize managers
$security = new SecurityManager($pdo);
$performance = new PerformanceManager($pdo);

$results = [];
$errors = [];

try {
    echo "<!DOCTYPE html><html><head><title>SmartFix Setup</title><style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success { color: #28a745; margin: 10px 0; }
        .error { color: #dc3545; margin: 10px 0; }
        .warning { color: #ffc107; margin: 10px 0; }
        .info { color: #17a2b8; margin: 10px 0; }
        h1 { color: #343a40; text-align: center; margin-bottom: 30px; }
        h2 { color: #495057; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 30px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .btn:hover { background: #0056b3; }
    </style></head><body>";
    
    echo "<div class='container'>";
    echo "<h1>🔒 SmartFix Security & Performance Setup</h1>";
    
    // Step 1: Create cache directory
    echo "<h2>Step 1: Creating Cache Directory</h2>";
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        if (mkdir($cacheDir, 0755, true)) {
            echo "<div class='success'>✅ Cache directory created successfully</div>";
        } else {
            echo "<div class='error'>❌ Failed to create cache directory</div>";
            $errors[] = "Cache directory creation failed";
        }
    } else {
        echo "<div class='info'>ℹ️ Cache directory already exists</div>";
    }
    
    // Step 2: Create .htaccess for cache directory
    $htaccessContent = "Order Deny,Allow\nDeny from all";
    $htaccessPath = $cacheDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        if (file_put_contents($htaccessPath, $htaccessContent)) {
            echo "<div class='success'>✅ Cache directory protected with .htaccess</div>";
        } else {
            echo "<div class='warning'>⚠️ Could not create .htaccess for cache directory</div>";
        }
    }
    
    // Step 3: Initialize security tables
    echo "<h2>Step 2: Initializing Security Tables</h2>";
    try {
        // This will create all security tables
        new SecurityManager($pdo);
        echo "<div class='success'>✅ Security tables created successfully</div>";
        
        // Check if tables exist
        $tables = ['rate_limits', 'audit_logs', 'csrf_tokens'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✅ Table '$table' created</div>";
            } else {
                echo "<div class='error'>❌ Table '$table' creation failed</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Security table creation failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $errors[] = "Security tables: " . $e->getMessage();
    }
    
    // Step 4: Create required tables if missing
    echo "<h2>Step 3: Creating Missing Application Tables</h2>";
    
    $requiredTables = [
        'user_trusted_devices' => "CREATE TABLE IF NOT EXISTS user_trusted_devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            device_fingerprint VARCHAR(64) NOT NULL,
            device_name VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_device (user_id, device_fingerprint),
            INDEX idx_user_id (user_id),
            INDEX idx_fingerprint (device_fingerprint)
        )",
        
        'user_2fa_codes' => "CREATE TABLE IF NOT EXISTS user_2fa_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(6) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_code (code),
            INDEX idx_expires (expires_at)
        )"
    ];
    
    foreach ($requiredTables as $tableName => $createSQL) {
        try {
            $pdo->exec($createSQL);
            echo "<div class='success'>✅ Table '$tableName' created/verified</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Failed to create table '$tableName': " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors[] = "Table $tableName: " . $e->getMessage();
        }
    }
    
    // Step 5: Database optimization
    echo "<h2>Step 4: Database Performance Optimization</h2>";
    try {
        $optimizations = $performance->optimizeDatabase();
        foreach ($optimizations as $optimization) {
            if (strpos($optimization, '✅') !== false) {
                echo "<div class='success'>$optimization</div>";
            } elseif (strpos($optimization, '⚠️') !== false) {
                echo "<div class='warning'>$optimization</div>";
            } else {
                echo "<div class='error'>$optimization</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Database optimization failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $errors[] = "Database optimization: " . $e->getMessage();
    }
    
    // Step 6: Create admin user if doesn't exist
    echo "<h2>Step 5: Admin User Setup</h2>";
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins");
        $stmt->execute();
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount == 0) {
            $adminPassword = $security->hashPassword('admin123');
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute(['admin', $adminPassword, 'admin@smartfix.com']);
            echo "<div class='success'>✅ Default admin user created (username: admin, password: admin123)</div>";
            echo "<div class='warning'>⚠️ Please change the default password immediately!</div>";
        } else {
            echo "<div class='info'>ℹ️ Admin users already exist ($adminCount found)</div>";
        }
    } catch (PDOException $e) {
        // Admin table might not exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $adminPassword = $security->hashPassword('admin123');
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $adminPassword, 'admin@smartfix.com']);
            echo "<div class='success'>✅ Admin table created and default admin user added</div>";
            echo "<div class='warning'>⚠️ Default login: admin / admin123 - CHANGE THIS PASSWORD!</div>";
        } catch (PDOException $e2) {
            echo "<div class='error'>❌ Admin setup failed: " . htmlspecialchars($e2->getMessage()) . "</div>";
        }
    }
    
    // Step 7: Security configuration
    echo "<h2>Step 6: Security Configuration</h2>";
    
    // Create security config file
    $securityConfig = "<?php
// SmartFix Security Configuration
return [
    'session' => [
        'cookie_lifetime' => 3600, // 1 hour
        'cookie_httponly' => true,
        'cookie_secure' => " . (isset($_SERVER['HTTPS']) ? 'true' : 'false') . ",
        'use_strict_mode' => true,
    ],
    'rate_limiting' => [
        'login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
        'cleanup_interval' => 3600, // 1 hour
    ],
    'file_upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'upload_path' => 'uploads/',
    ],
    'csrf' => [
        'token_lifetime' => 3600, // 1 hour
        'regenerate_interval' => 300, // 5 minutes
    ],
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ],
];
";
    
    $configPath = __DIR__ . '/config/security.php';
    if (!is_dir(dirname($configPath))) {
        mkdir(dirname($configPath), 0755, true);
    }
    
    if (file_put_contents($configPath, $securityConfig)) {
        echo "<div class='success'>✅ Security configuration file created</div>";
    } else {
        echo "<div class='warning'>⚠️ Could not create security configuration file</div>";
    }
    
    // Step 8: Performance baseline
    echo "<h2>Step 7: Performance Baseline</h2>";
    try {
        $metrics = $performance->getPerformanceMetrics();
        echo "<pre>";
        echo "Database Performance Metrics:\n";
        echo "- Queries per second: " . ($metrics['queries_per_second'] ?? 'N/A') . "\n";
        echo "- Slow queries: " . ($metrics['slow_queries'] ?? 'N/A') . "\n";
        echo "- Server uptime: " . ($metrics['uptime_hours'] ?? 'N/A') . " hours\n";
        echo "</pre>";
        echo "<div class='success'>✅ Performance monitoring initialized</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ️ Performance metrics will be available after database activity</div>";
    }
    
    // Summary
    echo "<h2>Setup Summary</h2>";
    
    if (empty($errors)) {
        echo "<div class='success'>";
        echo "<h3>✅ Setup completed successfully!</h3>";
        echo "<p>Your SmartFix application now includes:</p>";
        echo "<ul>";
        echo "<li>🔒 Advanced security features (rate limiting, CSRF protection, audit logging)</li>";
        echo "<li>⚡ Performance optimizations (query caching, database indexing)</li>";
        echo "<li>🛡️ File upload security validation</li>";
        echo "<li>📊 Performance monitoring dashboard</li>";
        echo "<li>👤 Secure admin authentication</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ul>";
        echo "<li>📱 <a href='login.php' class='btn'>Test User Login</a></li>";
        echo "<li>👨‍💼 <a href='admin/admin_login.php' class='btn'>Test Admin Login (admin/admin123)</a></li>";
        echo "<li>📈 <a href='admin/admin_performance_dashboard.php' class='btn'>View Performance Dashboard</a></li>";
        echo "<li>🏠 <a href='index.php' class='btn'>Go to Homepage</a></li>";
        echo "</ul>";
        
        echo "<div class='warning'>";
        echo "<h3>⚠️ Important Security Notes:</h3>";
        echo "<ul>";
        echo "<li>Change the default admin password immediately</li>";
        echo "<li>Review and customize security settings in config/security.php</li>";
        echo "<li>Ensure your web server is configured with HTTPS in production</li>";
        echo "<li>Regularly monitor the audit logs for suspicious activity</li>";
        echo "<li>Keep the cache directory protected (it contains sensitive data)</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Setup completed with " . count($errors) . " error(s):</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "<p>Please review and fix these issues before proceeding.</p>";
        echo "</div>";
    }
    
    echo "<div style='margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;'>";
    echo "<h3>🛠️ Additional Tools:</h3>";
    echo "<p>You can re-run this setup script anytime to ensure all components are properly configured.</p>";
    echo "<p><strong>File Locations:</strong></p>";
    echo "<ul>";
    echo "<li>Security Manager: <code>includes/SecurityManager.php</code></li>";
    echo "<li>Performance Manager: <code>includes/PerformanceManager.php</code></li>";
    echo "<li>Cache Directory: <code>cache/</code></li>";
    echo "<li>Configuration: <code>config/security.php</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Setup Failed</h2>";
    echo "<p>Critical error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and file permissions.</p>";
    echo "</div>";
    echo "</div></body></html>";
}
?>