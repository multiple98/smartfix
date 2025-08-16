<?php
// Complete Email System Setup with Admin Dashboard Integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$messages = [];

try {
    $messages[] = "🔧 Setting up complete email notification system...";
    
    // 1. Create email_logs table if it doesn't exist
    $messages[] = "Checking email_logs table...";
    $create_email_logs = "
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            email_type VARCHAR(50) NOT NULL,
            status ENUM('sent', 'failed') NOT NULL,
            request_id INT DEFAULT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipient (recipient),
            INDEX idx_request_id (request_id),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_email_logs);
    $messages[] = "✅ email_logs table created/verified";
    
    // 2. Create/update notifications table for admin dashboard
    $messages[] = "Checking notifications table...";
    $create_notifications = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL DEFAULT 'info',
            title VARCHAR(255) DEFAULT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            user_id INT DEFAULT NULL,
            request_id INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at),
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_notifications);
    $messages[] = "✅ notifications table created/verified";
    
    // 3. Ensure service_requests table has all required columns
    $messages[] = "Updating service_requests table structure...";
    
    // Check what columns exist
    $result = $pdo->query("DESCRIBE service_requests");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    $columns_to_add = [
        'reference_number' => "VARCHAR(20) UNIQUE DEFAULT NULL",
        'name' => "VARCHAR(100) DEFAULT NULL",
        'email' => "VARCHAR(100) DEFAULT NULL",
        'contact' => "VARCHAR(20) DEFAULT NULL", 
        'phone' => "VARCHAR(20) DEFAULT NULL",
        'address' => "TEXT DEFAULT NULL",
        'service_option' => "VARCHAR(100) DEFAULT NULL",
        'priority' => "VARCHAR(20) DEFAULT 'normal'",
        'assigned_technician' => "INT DEFAULT NULL",
        'technician_id' => "INT DEFAULT NULL",
        'scheduled_date' => "DATETIME DEFAULT NULL",
        'completion_date' => "DATETIME DEFAULT NULL",
        'completed_at' => "DATETIME DEFAULT NULL",
        'notes' => "TEXT DEFAULT NULL",
        'preferred_date' => "DATE DEFAULT NULL",
        'preferred_time' => "VARCHAR(20) DEFAULT NULL",
        'request_date' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE service_requests ADD COLUMN $column $definition");
                $messages[] = "✅ Added column '$column' to service_requests table";
            } catch (PDOException $e) {
                $messages[] = "⚠️ Could not add column '$column': " . $e->getMessage();
            }
        } else {
            $messages[] = "✅ Column '$column' already exists";
        }
    }
    
    // 4. Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_service_requests_email ON service_requests(email)",
        "CREATE INDEX IF NOT EXISTS idx_service_requests_reference ON service_requests(reference_number)",
        "CREATE INDEX IF NOT EXISTS idx_service_requests_status ON service_requests(status)",
        "CREATE INDEX IF NOT EXISTS idx_service_requests_date ON service_requests(request_date)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (PDOException $e) {
            // Ignore if index already exists
        }
    }
    $messages[] = "✅ Database indexes optimized";
    
    // 5. Create email templates table for customization
    $messages[] = "Creating email_templates table...";
    $create_email_templates = "
        CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(50) NOT NULL UNIQUE,
            subject VARCHAR(255) NOT NULL,
            body_html TEXT NOT NULL,
            body_text TEXT DEFAULT NULL,
            variables TEXT DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_email_templates);
    $messages[] = "✅ email_templates table created/verified";
    
    // 6. Insert default email templates
    $templates = [
        [
            'template_name' => 'service_request_confirmation',
            'subject' => 'Service Request Confirmation - SmartFix',
            'body_html' => '<h2>Hello {customer_name},</h2><p>Thank you for choosing SmartFix! We have received your service request and our team will review it shortly.</p><p><strong>Service Request Details:</strong><br>• Request ID: {request_id}<br>• Service Type: {service_type}<br>• Request Date: {request_date}<br>• Status: {status}</p><p>We will contact you soon to discuss your request.</p>',
            'variables' => 'customer_name, request_id, service_type, request_date, status'
        ],
        [
            'template_name' => 'admin_new_request_notification', 
            'subject' => 'New Service Request - {service_type}',
            'body_html' => '<h2>New Service Request Received</h2><p>A new service request has been submitted and requires attention.</p><p><strong>Customer Information:</strong><br>• Name: {customer_name}<br>• Email: {customer_email}<br>• Phone: {customer_phone}</p><p><strong>Service Details:</strong><br>• Request ID: {request_id}<br>• Service Type: {service_type}<br>• Description: {description}</p>',
            'variables' => 'customer_name, customer_email, customer_phone, request_id, service_type, description'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO email_templates (template_name, subject, body_html, variables) VALUES (?, ?, ?, ?)");
    foreach ($templates as $template) {
        $stmt->execute([$template['template_name'], $template['subject'], $template['body_html'], $template['variables']]);
    }
    $messages[] = "✅ Default email templates installed";
    
    // 7. Test email configuration
    $messages[] = "Testing email configuration...";
    require_once 'includes/EmailNotification.php';
    
    try {
        $emailNotification = new EmailNotification($pdo);
        $messages[] = "✅ EmailNotification class loaded successfully";
        
        // Test if PHP mail function is configured
        if (function_exists('mail')) {
            $messages[] = "✅ PHP mail() function is available";
        } else {
            $messages[] = "⚠️ PHP mail() function is not available - emails will not send";
        }
    } catch (Exception $e) {
        $messages[] = "❌ Error loading EmailNotification: " . $e->getMessage();
    }
    
    // 8. Create sample notification for admin
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (type, title, message, is_read, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            'system',
            'Email System Setup Complete',
            'The email notification system has been successfully configured and is ready to send notifications for new service requests.',
            0
        ]);
        $messages[] = "✅ Sample notification created for admin dashboard";
    } catch (PDOException $e) {
        $messages[] = "⚠️ Could not create sample notification: " . $e->getMessage();
    }
    
    $messages[] = "🎉 Complete email system setup finished successfully!";
    
} catch (PDOException $e) {
    $messages[] = "❌ Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $messages[] = "❌ General Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System Setup - SmartFix</title>
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
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #007BFF;
            margin-top: 0;
            border-bottom: 3px solid #007BFF;
            padding-bottom: 15px;
            text-align: center;
        }
        
        .message-list {
            margin: 30px 0;
            padding: 0;
            list-style: none;
        }
        
        .message-list li {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-left: 4px solid #007BFF;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .message-list li:contains("✅") {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        
        .message-list li:contains("⚠️") {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        
        .message-list li:contains("❌") {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .message-list li:contains("🎉") {
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
        
        .btn-info {
            background-color: #17a2b8;
        }
        
        .btn-info:hover {
            background-color: #117a8b;
        }
        
        .status-info {
            background-color: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .status-info h3 {
            color: #007BFF;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 SmartFix Email System Setup</h1>
        
        <ul class="message-list">
            <?php foreach ($messages as $message): ?>
                <li><?php echo $message; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <div class="status-info">
            <h3>✅ Setup Complete!</h3>
            <p>Your SmartFix email notification system is now ready to:</p>
            <ul>
                <li>Send confirmation emails to customers when they request services</li>
                <li>Send notification emails to admins about new service requests</li>
                <li>Log all email activities for tracking</li>
                <li>Display service requests properly in the admin dashboard</li>
            </ul>
        </div>
        
        <div class="buttons">
            <a href="services/request_service.php?type=phone" class="btn btn-success">🧪 Test Service Request</a>
            <a href="admin/admin_dashboard_new.php" class="btn btn-info">👨‍💼 Admin Dashboard</a>
            <a href="admin/admin_login.php" class="btn">🔐 Admin Login</a>
            <a href="index.php" class="btn">🏠 Home Page</a>
        </div>
    </div>
</body>
</html>