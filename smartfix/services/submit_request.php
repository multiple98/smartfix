
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/db.php';
require_once '../includes/EmailNotification.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_type = $_POST['service_type'] ?? '';
    $service_option = $_POST['service_option'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'normal';

    // Generate unique reference number
    $reference_number = 'SF' . date('Ymd') . sprintf('%04d', rand(1000, 9999));

    // Insert into database with comprehensive data
    try {
        // First ensure table exists with proper structure
        $checkTable = $pdo->query("SHOW TABLES LIKE 'service_requests'");
        if ($checkTable->rowCount() == 0) {
            // Create table if it doesn't exist
            $createTable = "CREATE TABLE service_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reference_number VARCHAR(20) UNIQUE,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                service_type VARCHAR(50) NOT NULL,
                service_option VARCHAR(100),
                description TEXT NOT NULL,
                address TEXT,
                preferred_date DATE,
                preferred_time VARCHAR(20),
                priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
                technician_id INT,
                user_id INT,
                notes TEXT,
                completed_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($createTable);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO service_requests (
                reference_number, name, email, phone, address, service_type, 
                service_option, description, priority, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $success = $stmt->execute([
            $reference_number, $fullname, $email, $phone, $address, 
            $service_type, $service_option, $description, $priority
        ]);
        
        if (!$success) {
            throw new PDOException("Failed to insert service request");
        }
        
        $request_id = $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Error inserting service request: " . $e->getMessage());
        
        // Handle missing columns or table structure issues
        if (strpos($e->getMessage(), "Unknown column") !== false) {
            // Try with basic columns only
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO service_requests (name, email, phone, service_type, description, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$fullname, $email, $phone, $service_type, $description]);
                $request_id = $pdo->lastInsertId();
                $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
                
                // Try to update reference number if column exists
                try {
                    $updateRef = $pdo->prepare("UPDATE service_requests SET reference_number = ? WHERE id = ?");
                    $updateRef->execute([$reference_number, $request_id]);
                } catch (PDOException $refError) {
                    // Reference number column might not exist
                    error_log("Could not update reference number: " . $refError->getMessage());
                }
                
            } catch (PDOException $e2) {
                die("<div style='background: #ffebee; color: #c62828; padding: 20px; border-radius: 5px; margin: 20px; text-align: center;'>
                     <h3>❌ Database Error</h3>
                     <p>Unable to submit your service request due to database issues.</p>
                     <p>Please <a href='../fix_service_requests_system.php' style='color: #1976d2;'>run the database repair tool</a> first, then try again.</p>
                     <p style='font-size: 12px; margin-top: 20px;'>Error details: " . htmlspecialchars($e2->getMessage()) . "</p>
                     <a href='javascript:history.back()' style='display: inline-block; background: #1976d2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px;'>← Go Back</a>
                     </div>");
            }
        } else {
            die("<div style='background: #ffebee; color: #c62828; padding: 20px; border-radius: 5px; margin: 20px; text-align: center;'>
                 <h3>❌ Service Request Error</h3>
                 <p>Unable to process your service request at this time.</p>
                 <p>Please try again later or contact support.</p>
                 <a href='javascript:history.back()' style='display: inline-block; background: #1976d2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px;'>← Go Back</a>
                 </div>");
        }
    }

    // Initialize email notification system
    $emailNotification = new EmailNotification($pdo);
    
    // Prepare service details for email
    $serviceDetails = [
        'request_id' => $reference_number,
        'service_type' => $service_type,
        'service_option' => $service_option,
        'description' => $description,
        'status' => 'pending',
        'priority' => $priority,
        'request_date' => date('Y-m-d H:i:s'),
        'customer_name' => $fullname,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'customer_address' => $address
    ];

    // Send confirmation email to customer
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailSent = $emailNotification->sendServiceRequestConfirmation($email, $fullname, $serviceDetails);
        $emailNotification->logEmailActivity($email, 'Service Request Confirmation', 'confirmation', $emailSent ? 'sent' : 'failed', $request_id);
        
        // Send notification to admin
        $adminEmailSent = $emailNotification->sendServiceRequestNotificationToAdmin($serviceDetails);
        $emailNotification->logEmailActivity('admin@smartfix.com', 'New Service Request', 'admin_notification', $adminEmailSent ? 'sent' : 'failed', $request_id);
        
        // Create notification for admin dashboard
        try {
            $notification_query = "INSERT INTO notifications (type, title, message, is_read, request_id, created_at) 
                                  VALUES ('service_request', :title, :message, 0, :request_id, NOW())";
            $notification_stmt = $pdo->prepare($notification_query);
            $notification_stmt->execute([
                'title' => "New Service Request - {$service_type}",
                'message' => "New {$service_type} service request ({$reference_number}) from {$fullname}. Priority: {$priority}",
                'request_id' => $request_id
            ]);
        } catch (PDOException $e) {
            error_log("Could not create admin notification: " . $e->getMessage());
        }
    }
    
    // Redirect to a confirmation page or show confirmation
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Request Submitted - SmartFix</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                margin: 0;
                padding: 0;
                background: #f0f2f5;
                text-align: center;
            }
            .container {
                max-width: 700px;
                margin: 40px auto;
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h2 {
                color: #007BFF;
            }
            .success-icon {
                font-size: 60px;
                color: #28a745;
                margin: 20px 0;
            }
            .request-id {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                font-weight: bold;
                margin: 20px 0;
                display: inline-block;
            }
            .buttons {
                margin-top: 30px;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                margin: 0 10px;
                background: #007BFF;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success-icon">✅</div>
            <h2>Thanks, <?php echo htmlspecialchars($fullname); ?>!</h2>
            <p>Your request for <strong><?php echo htmlspecialchars($service_type); ?></strong> has been received and recorded.</p>
            
            <div class="request-id">
                Reference Number: <?php echo htmlspecialchars($reference_number); ?>
            </div>
            
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                <h3 style="margin-top: 0; color: #007BFF;">📧 Email Confirmation</h3>
                <?php if (filter_var($email, FILTER_VALIDATE_EMAIL)): ?>
                    <p>✅ A confirmation email has been sent to <strong><?php echo htmlspecialchars($email); ?></strong></p>
                    <p>The email contains your request details and tracking information.</p>
                <?php else: ?>
                    <p>⚠️ No valid email provided. Please save your reference number for tracking.</p>
                <?php endif; ?>
            </div>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                <h3 style="margin-top: 0; color: #28a745;">🔄 What Happens Next?</h3>
                <ol style="text-align: left; padding-left: 20px;">
                    <li>Our team will review your request within 2-4 hours</li>
                    <li>We'll assign a qualified technician to your case</li>
                    <li>You'll receive a call or email to schedule the service</li>
                    <li>Our technician will arrive at your specified location</li>
                </ol>
            </div>
            
            <p>We'll contact you soon at <?php echo htmlspecialchars($phone); ?> to discuss your request.</p>
            
            <div class="buttons">
                <a href="../services/track_service.php?id=<?php echo urlencode($reference_number); ?>" class="btn">Track Request</a>
                <a href="../index.php" class="btn">Return to Home</a>
                <a href="../services.php" class="btn">More Services</a>
            </div>
        </div>
    </body>
    </html>
<?php
}
?>
