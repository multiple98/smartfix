<?php
// Debug version of service request - shows actual errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('includes/db.php');
require_once('includes/EmailNotification.php');

// Get service type from URL parameter
$service_type = isset($_GET['type']) ? $_GET['type'] : 'phone';

$success_message = '';
$error_message = '';
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $service_option = trim($_POST['service_option']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $preferred_date = trim($_POST['preferred_date']);
    $preferred_time = trim($_POST['preferred_time']);
    
    $debug_info[] = "Form submitted with data: name=$name, email=$email, phone=$phone, service_type=$service_type";
    
    // Simple validation
    if (empty($name) || empty($email) || empty($phone) || empty($description)) {
        $error_message = "Please fill in all required fields.";
        $debug_info[] = "Validation failed: missing required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
        $debug_info[] = "Validation failed: invalid email address";
    } else {
        try {
            $debug_info[] = "Attempting to insert into database...";
            
            // Check if service_requests table exists
            try {
                $check_table = $pdo->query("SELECT 1 FROM service_requests LIMIT 1");
                $debug_info[] = "✅ service_requests table exists";
            } catch (PDOException $e) {
                $debug_info[] = "❌ service_requests table missing: " . $e->getMessage();
                throw new Exception("service_requests table does not exist");
            }
            
            // Insert into database
            $query = "INSERT INTO service_requests (name, email, phone, service_type, service_option, description, address, preferred_date, preferred_time, status, created_at) 
                      VALUES (:name, :email, :phone, :service_type, :service_option, :description, :address, :preferred_date, :preferred_time, 'pending', NOW())";
            
            $debug_info[] = "Preparing query: $query";
            
            $stmt = $pdo->prepare($query);
            
            $params = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service_type' => $service_type,
                'service_option' => $service_option,
                'description' => $description,
                'address' => $address,
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time
            ];
            
            $debug_info[] = "Executing with params: " . json_encode($params);
            
            $stmt->execute($params);
            
            // Generate reference number
            $request_id = $pdo->lastInsertId();
            $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
            
            $debug_info[] = "✅ Record inserted successfully. ID: $request_id, Reference: $reference_number";
            
            // Update reference number
            $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'reference_number' => $reference_number,
                'id' => $request_id
            ]);
            
            $debug_info[] = "✅ Reference number updated";
            
            // Send email notifications
            try {
                $debug_info[] = "Attempting to send email notifications...";
                
                $emailNotification = new EmailNotification($pdo);
                
                $serviceDetails = [
                    'request_id' => $reference_number,
                    'service_type' => $service_type,
                    'service_option' => $service_option,
                    'description' => $description,
                    'status' => 'pending',
                    'priority' => 'normal',
                    'request_date' => date('Y-m-d H:i:s'),
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'customer_address' => $address
                ];
                
                // Send confirmation email to customer
                $emailSent = $emailNotification->sendServiceRequestConfirmation($email, $name, $serviceDetails);
                $debug_info[] = $emailSent ? "✅ Customer email sent" : "⚠️ Customer email failed";
                
                // Log email activity
                $emailNotification->logEmailActivity($email, 'Service Request Confirmation', 'confirmation', $emailSent ? 'sent' : 'failed', $request_id);
                
                // Send notification to admin
                $adminEmailSent = $emailNotification->sendServiceRequestNotificationToAdmin($serviceDetails);
                $debug_info[] = $adminEmailSent ? "✅ Admin email sent" : "⚠️ Admin email failed";
                
                // Log admin email activity
                $emailNotification->logEmailActivity('admin@smartfix.com', 'New Service Request', 'admin_notification', $adminEmailSent ? 'sent' : 'failed', $request_id);
                
            } catch (Exception $email_error) {
                $debug_info[] = "⚠️ Email error: " . $email_error->getMessage();
            }
            
            // Create notification for admin dashboard
            try {
                $debug_info[] = "Creating admin notification...";
                
                $notification_query = "INSERT INTO notifications (type, title, message, is_read, request_id, created_at) 
                                      VALUES ('service_request', :title, :message, 0, :request_id, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'title' => "New Service Request - {$service_type}",
                    'message' => "New {$service_type} service request ({$reference_number}) from {$name}. Contact: {$phone}",
                    'request_id' => $request_id
                ]);
                
                $debug_info[] = "✅ Admin notification created";
                
            } catch (PDOException $notif_error) {
                $debug_info[] = "⚠️ Notification error: " . $notif_error->getMessage();
            }
            
            $success_message = "Thank you! Your service request has been submitted successfully. Your reference number is: <strong>{$reference_number}</strong>";
            if ($emailSent) {
                $success_message .= "<br><small>📧 A confirmation email has been sent to your email address.</small>";
            }
            
            // Clear form data after successful submission
            $name = $email = $phone = $service_option = $description = $address = $preferred_date = $preferred_time = '';
            
        } catch (PDOException $e) {
            $error_message = "Database Error: " . $e->getMessage();
            $debug_info[] = "❌ Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $error_message = "General Error: " . $e->getMessage();
            $debug_info[] = "❌ General error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Service Request - SmartFix</title>
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: #007BFF;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .debug-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
        }
        
        .debug-info h3 {
            margin-top: 0;
            color: #495057;
        }
        
        .debug-info ul {
            list-style: none;
            padding: 0;
        }
        
        .debug-info li {
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐛 Debug Service Request Form</h1>
        
        <?php if ($success_message): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info)): ?>
            <div class="debug-info">
                <h3>Debug Information:</h3>
                <ul>
                    <?php foreach ($debug_info as $info): ?>
                        <li><?php echo htmlspecialchars($info); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="service_option">Service Option *</label>
                <select id="service_option" name="service_option" required>
                    <option value="">Select Service Option</option>
                    <option value="Screen Replacement">Screen Replacement</option>
                    <option value="Battery Replacement">Battery Replacement</option>
                    <option value="Water Damage Repair">Water Damage Repair</option>
                    <option value="Camera Repair">Camera Repair</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Problem Description *</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="preferred_date">Preferred Date</label>
                <input type="date" id="preferred_date" name="preferred_date" value="<?php echo htmlspecialchars($preferred_date ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="preferred_time">Preferred Time</label>
                <select id="preferred_time" name="preferred_time">
                    <option value="">Select Time</option>
                    <option value="morning">Morning (8AM - 12PM)</option>
                    <option value="afternoon">Afternoon (12PM - 6PM)</option>
                    <option value="evening">Evening (6PM - 8PM)</option>
                </select>
            </div>
            
            <button type="submit" name="service_submit" class="btn">Submit Service Request</button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <p><a href="../services/request_service.php?type=<?php echo $service_type; ?>">← Back to Regular Form</a></p>
            <p><a href="setup_complete_email_system.php">🔧 Run Database Setup</a></p>
        </div>
    </div>
</body>
</html>