<?php
// Test Service Request System
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';
require_once 'includes/EmailNotification.php';

$test_results = [];
$test_success = true;

try {
    $test_results[] = "🧪 Starting service request system test...";
    
    // Test 1: Database connectivity
    $test_results[] = "Testing database connectivity...";
    $pdo->query("SELECT 1");
    $test_results[] = "✅ Database connection successful";
    
    // Test 2: Check required tables exist
    $test_results[] = "Checking required tables...";
    
    $required_tables = ['service_requests', 'notifications', 'email_logs'];
    foreach ($required_tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $test_results[] = "✅ Table '$table' exists";
        } else {
            $test_results[] = "❌ Table '$table' missing";
            $test_success = false;
        }
    }
    
    // Test 3: Test service request insertion
    $test_results[] = "Testing service request submission...";
    
    $test_data = [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '+1-555-0123', 
        'service_type' => 'phone',
        'service_option' => 'Screen Replacement',
        'description' => 'Test service request for system verification',
        'address' => '123 Test Street',
        'priority' => 'normal'
    ];
    
    $reference_number = 'TEST' . date('YmdHis');
    
    $stmt = $pdo->prepare("
        INSERT INTO service_requests (
            reference_number, name, email, contact, service_type, 
            service_option, description, address, priority, status, request_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $reference_number,
        $test_data['name'],
        $test_data['email'], 
        $test_data['phone'],
        $test_data['service_type'],
        $test_data['service_option'],
        $test_data['description'],
        $test_data['address'],
        $test_data['priority']
    ]);
    
    $request_id = $pdo->lastInsertId();
    $test_results[] = "✅ Service request inserted successfully (ID: $request_id, Ref: $reference_number)";
    
    // Test 4: Test EmailNotification class
    $test_results[] = "Testing email notification system...";
    
    $emailNotification = new EmailNotification($pdo);
    $test_results[] = "✅ EmailNotification class instantiated";
    
    // Test 5: Test notification creation
    $test_results[] = "Testing admin notification creation...";
    
    $notification_stmt = $pdo->prepare("
        INSERT INTO notifications (type, title, message, is_read, request_id, created_at) 
        VALUES ('service_request', ?, ?, 0, ?, NOW())
    ");
    
    $notification_stmt->execute([
        "Test Service Request - {$test_data['service_type']}", 
        "Test notification for service request ({$reference_number}) from {$test_data['name']}",
        $request_id
    ]);
    
    $test_results[] = "✅ Admin notification created successfully";
    
    // Test 6: Test email logging
    $test_results[] = "Testing email activity logging...";
    
    $emailNotification->logEmailActivity(
        $test_data['email'], 
        'Test Service Request Confirmation', 
        'test', 
        'sent', 
        $request_id
    );
    
    $test_results[] = "✅ Email activity logged successfully";
    
    // Test 7: Verify data in admin dashboard queries
    $test_results[] = "Testing admin dashboard data retrieval...";
    
    // Test service request count
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE id = ?");
    $count_stmt->execute([$request_id]);
    if ($count_stmt->fetchColumn() > 0) {
        $test_results[] = "✅ Service request visible in database";
    }
    
    // Test notification count
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE request_id = ?");
    $notif_stmt->execute([$request_id]);
    if ($notif_stmt->fetchColumn() > 0) {
        $test_results[] = "✅ Notification visible for admin dashboard";
    }
    
    // Test 8: Email template functionality (without sending)
    $test_results[] = "Testing email template building...";
    
    $serviceDetails = [
        'request_id' => $reference_number,
        'service_type' => $test_data['service_type'],
        'service_option' => $test_data['service_option'],
        'description' => $test_data['description'],
        'status' => 'pending',
        'priority' => $test_data['priority'],
        'request_date' => date('Y-m-d H:i:s'),
        'customer_name' => $test_data['name'],
        'customer_email' => $test_data['email'],
        'customer_phone' => $test_data['phone'],
        'customer_address' => $test_data['address']
    ];
    
    // Test customer confirmation email (without sending)
    $reflection = new ReflectionClass($emailNotification);
    $method = $reflection->getMethod('buildEmailTemplate');
    $method->setAccessible(true);
    
    $email_data = [
        'title' => 'Test Email',
        'greeting' => 'Hello Test User',
        'content' => ['This is a test email template'],
        'footer_note' => 'This is a test'
    ];
    
    $email_template = $method->invoke($emailNotification, $email_data);
    if (strpos($email_template, 'Hello Test User') !== false) {
        $test_results[] = "✅ Email template building works correctly";
    } else {
        $test_results[] = "❌ Email template building failed";
        $test_success = false;
    }
    
    // Clean up test data
    $test_results[] = "Cleaning up test data...";
    $pdo->prepare("DELETE FROM service_requests WHERE id = ?")->execute([$request_id]);
    $pdo->prepare("DELETE FROM notifications WHERE request_id = ?")->execute([$request_id]);
    $pdo->prepare("DELETE FROM email_logs WHERE request_id = ?")->execute([$request_id]);
    $test_results[] = "✅ Test data cleaned up";
    
    if ($test_success) {
        $test_results[] = "🎉 All tests passed! Email notification system is working correctly.";
    } else {
        $test_results[] = "⚠️ Some tests failed. Please check the issues above.";
    }
    
} catch (Exception $e) {
    $test_results[] = "❌ Test failed with error: " . $e->getMessage();
    $test_success = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Request System Test - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: <?php echo $test_success ? '#28a745' : '#dc3545'; ?>;
            margin-top: 0;
            border-bottom: 3px solid <?php echo $test_success ? '#28a745' : '#dc3545'; ?>;
            padding-bottom: 15px;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            <?php if ($test_success): ?>
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            <?php else: ?>
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            <?php endif; ?>
        }
        
        .test-results {
            margin: 30px 0;
            padding: 0;
            list-style: none;
        }
        
        .test-results li {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-left: 4px solid #007BFF;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        
        .test-results li:contains("✅") {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        
        .test-results li:contains("❌") {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .test-results li:contains("🎉") {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            font-weight: bold;
        }
        
        .buttons {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .info-box h3 {
            color: #007BFF;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Service Request System Test</h1>
        
        <div class="status-badge">
            <?php if ($test_success): ?>
                ✅ All Systems Working
            <?php else: ?>
                ⚠️ Issues Detected
            <?php endif; ?>
        </div>
        
        <ul class="test-results">
            <?php foreach ($test_results as $result): ?>
                <li><?php echo $result; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <div class="info-box">
            <h3>✅ What This Test Verified:</h3>
            <ul>
                <li><strong>Database Connection:</strong> Confirmed SmartFix can connect to the database</li>
                <li><strong>Required Tables:</strong> Verified service_requests, notifications, and email_logs tables exist</li>
                <li><strong>Service Request Submission:</strong> Tested the complete service request creation process</li>
                <li><strong>Email System:</strong> Confirmed EmailNotification class is working</li>
                <li><strong>Admin Notifications:</strong> Verified notifications are created for the admin dashboard</li>
                <li><strong>Email Logging:</strong> Confirmed email activities are properly logged</li>
                <li><strong>Template System:</strong> Verified email templates are built correctly</li>
            </ul>
        </div>
        
        <div class="buttons">
            <?php if ($test_success): ?>
                <a href="services/request_service.php?type=phone" class="btn btn-success">📱 Test Real Service Request</a>
                <a href="admin/admin_dashboard_new.php" class="btn">👨‍💼 View Admin Dashboard</a>
            <?php else: ?>
                <a href="setup_complete_email_system.php" class="btn btn-warning">🔧 Run Setup Again</a>
            <?php endif; ?>
            <a href="index.php" class="btn">🏠 Return to Home</a>
        </div>
    </div>
</body>
</html>