<?php
session_start();
include('includes/db.php');

// Test PWA and Mobile Features
$tests = [
    'PWA Manifest' => [
        'file' => 'manifest.json',
        'exists' => file_exists('manifest.json')
    ],
    'Service Worker' => [
        'file' => 'sw.js',
        'exists' => file_exists('sw.js')
    ],
    'Offline Page' => [
        'file' => 'offline.html',
        'exists' => file_exists('offline.html')
    ],
    'PWA JavaScript' => [
        'file' => 'js/pwa.js',
        'exists' => file_exists('js/pwa.js')
    ],
    'Mobile CSS' => [
        'file' => 'css/mobile-responsive.css',
        'exists' => file_exists('css/mobile-responsive.css')
    ],
    'Payment Manager' => [
        'file' => 'includes/PaymentManager.php',
        'exists' => file_exists('includes/PaymentManager.php')
    ],
    'Enhanced Checkout' => [
        'file' => 'shop/checkout_enhanced.php',
        'exists' => file_exists('shop/checkout_enhanced.php')
    ]
];

// Check database tables
$db_tests = [
    'payment_transactions' => false,
    'payment_methods' => false,
    'orders' => false,
    'users' => false
];

try {
    foreach ($db_tests as $table => $exists) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $db_tests[$table] = $stmt->fetch() !== false;
    }
} catch (PDOException $e) {
    // Database error
}

// Test PaymentManager
$payment_manager_working = false;
if (file_exists('includes/PaymentManager.php')) {
    try {
        require_once('includes/PaymentManager.php');
        $paymentManager = new PaymentManager($pdo);
        $payment_methods = $paymentManager->getPaymentMethods();
        $payment_manager_working = !empty($payment_methods);
    } catch (Exception $e) {
        // PaymentManager error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile & PWA Test - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .test-section {
            margin-bottom: 30px;
        }
        
        .test-section h2 {
            color: #333;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        
        .test-item.pass {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .test-item.fail {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .test-name {
            font-weight: 600;
        }
        
        .test-status {
            font-weight: bold;
        }
        
        .pass .test-status::before {
            content: "✓ ";
            color: #28a745;
        }
        
        .fail .test-status::before {
            content: "✗ ";
            color: #dc3545;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        
        .btn.secondary {
            background: #6c757d;
        }
        
        .btn.secondary:hover {
            background: #545b62;
        }
        
        .pwa-features {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
        }
        
        .pwa-features h3 {
            margin-top: 0;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        
        .feature-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #28a745;
            font-weight: bold;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 20px;
            }
            
            .test-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-mobile-alt"></i> Mobile & PWA Implementation Test</h1>
        
        <div class="test-section">
            <h2><i class="fas fa-file-code"></i> Core Files</h2>
            <?php foreach ($tests as $name => $test): ?>
                <div class="test-item <?php echo $test['exists'] ? 'pass' : 'fail'; ?>">
                    <div class="test-name"><?php echo $name; ?> (<?php echo $test['file']; ?>)</div>
                    <div class="test-status"><?php echo $test['exists'] ? 'INSTALLED' : 'MISSING'; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="test-section">
            <h2><i class="fas fa-database"></i> Database Tables</h2>
            <?php foreach ($db_tests as $table => $exists): ?>
                <div class="test-item <?php echo $exists ? 'pass' : 'fail'; ?>">
                    <div class="test-name">Table: <?php echo $table; ?></div>
                    <div class="test-status"><?php echo $exists ? 'EXISTS' : 'MISSING'; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="test-section">
            <h2><i class="fas fa-credit-card"></i> Payment System</h2>
            <div class="test-item <?php echo $payment_manager_working ? 'pass' : 'fail'; ?>">
                <div class="test-name">PaymentManager Class</div>
                <div class="test-status"><?php echo $payment_manager_working ? 'WORKING' : 'ERROR'; ?></div>
            </div>
            
            <?php if ($payment_manager_working): ?>
                <div class="test-item pass">
                    <div class="test-name">Available Payment Methods</div>
                    <div class="test-status"><?php echo count($payment_methods); ?> METHODS</div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="pwa-features">
            <h3><i class="fas fa-rocket"></i> PWA Features Ready</h3>
            <ul class="feature-list">
                <li>Progressive Web App installation</li>
                <li>Offline functionality with Service Worker</li>
                <li>Mobile-optimized responsive design</li>
                <li>Enhanced payment processing</li>
                <li>Push notification framework</li>
                <li>Background sync capabilities</li>
                <li>Native app-like experience</li>
                <li>Fast loading with caching</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <a href="login.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Test Login PWA
            </a>
            <a href="shop/checkout_enhanced.php" class="btn">
                <i class="fas fa-shopping-cart"></i> Test Enhanced Checkout
            </a>
            <a href="create_simple_icons.html" class="btn secondary">
                <i class="fas fa-images"></i> Generate PWA Icons
            </a>
            <a href="MOBILE_PWA_IMPLEMENTATION.md" class="btn secondary">
                <i class="fas fa-book"></i> View Documentation
            </a>
        </div>
    </div>
    
    <script>
        // Test PWA features
        console.log('🚀 Testing PWA Features...');
        
        // Test Service Worker support
        if ('serviceWorker' in navigator) {
            console.log('✅ Service Worker supported');
            
            navigator.serviceWorker.register('/smartfix/sw.js')
                .then(registration => {
                    console.log('✅ Service Worker registered:', registration.scope);
                })
                .catch(error => {
                    console.log('❌ Service Worker registration failed:', error);
                });
        } else {
            console.log('❌ Service Worker not supported');
        }
        
        // Test PWA install capability
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('✅ PWA install prompt available');
            e.preventDefault();
        });
        
        // Test network status
        console.log('📶 Network status:', navigator.onLine ? 'Online' : 'Offline');
        
        // Test responsive design
        console.log('📱 Screen width:', window.innerWidth + 'px');
        
        // Test local storage
        try {
            localStorage.setItem('smartfix-test', 'working');
            localStorage.removeItem('smartfix-test');
            console.log('✅ Local Storage working');
        } catch (e) {
            console.log('❌ Local Storage failed');
        }
        
        console.log('🎉 PWA test complete!');
    </script>
</body>
</html>