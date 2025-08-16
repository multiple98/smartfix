<?php
session_start();
include('../includes/db.php');
require_once('../includes/EmailNotification.php');

// Get service type from URL parameter
$service_type = isset($_GET['type']) ? $_GET['type'] : '';

// Define service types and their details
$service_types = [
    'phone' => [
        'title' => 'Phone Repair Service',
        'icon' => 'fas fa-mobile-alt',
        'description' => 'Professional repair services for all smartphone brands and models.',
        'options' => [
            'Screen Replacement',
            'Battery Replacement',
            'Water Damage Repair',
            'Camera Repair',
            'Charging Port Repair',
            'Software Issues',
            'Other'
        ]
    ],
    'computer' => [
        'title' => 'Computer Repair Service',
        'icon' => 'fas fa-laptop',
        'description' => 'Expert solutions for desktop and laptop computer issues.',
        'options' => [
            'Hardware Repair',
            'Software Installation',
            'Virus Removal',
            'Data Recovery',
            'Upgrades',
            'Network Setup',
            'Other'
        ]
    ],
    'car' => [
        'title' => 'Vehicle Repair Service',
        'icon' => 'fas fa-car',
        'description' => 'Professional automotive repair and maintenance services.',
        'options' => [
            'Engine Diagnostics',
            'Electrical System Repair',
            'Brake Service',
            'Oil Change',
            'Tire Service',
            'AC Repair',
            'Other'
        ]
    ],
    'house' => [
        'title' => 'Find a House',
        'icon' => 'fas fa-home',
        'description' => 'Connect with real estate agents to find your perfect home.',
        'options' => [
            'Rental Property',
            'Property for Sale',
            'Commercial Property',
            'Land',
            'Apartment',
            'House',
            'Other'
        ]
    ],
    'plumber' => [
        'title' => 'Find a Plumber',
        'icon' => 'fas fa-wrench',
        'description' => 'Connect with qualified plumbers for all your plumbing needs.',
        'options' => [
            'Leak Repair',
            'Pipe Installation',
            'Drain Cleaning',
            'Water Heater Service',
            'Fixture Installation',
            'Emergency Plumbing',
            'Other'
        ]
    ],
    'electrician' => [
        'title' => 'Find an Electrician',
        'icon' => 'fas fa-bolt',
        'description' => 'Connect with certified electricians for safe electrical work.',
        'options' => [
            'Wiring Installation',
            'Electrical Repairs',
            'Lighting Installation',
            'Panel Upgrades',
            'Safety Inspection',
            'Emergency Service',
            'Other'
        ]
    ]
];

// Set default if service type is not valid
if (!array_key_exists($service_type, $service_types)) {
    $service_type = 'phone';
}

// Current service details
$current_service = $service_types[$service_type];

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $service_option = trim($_POST['service_option']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $preferred_date = trim($_POST['preferred_date']);
    $preferred_time = trim($_POST['preferred_time']);
    
    // Simple validation
    if (empty($name) || empty($email) || empty($phone) || empty($description)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // First, ensure the service_requests table has the correct structure
            try {
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
            } catch (PDOException $createError) {
                // Table might already exist, continue
            }
            
            // Insert into database with proper error handling
            $query = "INSERT INTO service_requests (name, email, phone, service_type, service_option, description, address, preferred_date, preferred_time, status, created_at) 
                      VALUES (:name, :email, :phone, :service_type, :service_option, :description, :address, :preferred_date, :preferred_time, 'pending', NOW())";
            $stmt = $pdo->prepare($query);
            $success = $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service_type' => $service_type,
                'service_option' => $service_option,
                'description' => $description,
                'address' => $address,
                'preferred_date' => $preferred_date ?: null,
                'preferred_time' => $preferred_time ?: null
            ]);
            
            if (!$success) {
                throw new PDOException("Failed to insert service request");
            }
            
            // Generate reference number
            $request_id = $pdo->lastInsertId();
            $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
            
            // Update reference number
            $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'reference_number' => $reference_number,
                'id' => $request_id
            ]);
            
            // Send email notifications
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
            $emailNotification->logEmailActivity($email, 'Service Request Confirmation', 'confirmation', $emailSent ? 'sent' : 'failed', $request_id);
            
            // Send notification to admin
            $adminEmailSent = $emailNotification->sendServiceRequestNotificationToAdmin($serviceDetails);
            $emailNotification->logEmailActivity('admin@smartfix.com', 'New Service Request', 'admin_notification', $adminEmailSent ? 'sent' : 'failed', $request_id);
            
            $success_message = "Thank you! Your service request has been submitted successfully. Your reference number is: <strong>{$reference_number}</strong>";
            if ($emailSent) {
                $success_message .= "<br><small>📧 A confirmation email has been sent to your email address.</small>";
            }
            
            // Clear form data after successful submission
            $name = $email = $phone = $service_option = $description = $address = $preferred_date = $preferred_time = '';
            
            // Create notification for admin dashboard
            try {
                $notification_query = "INSERT INTO notifications (type, title, message, is_read, request_id, created_at) 
                                      VALUES ('service_request', :title, :message, 0, :request_id, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'title' => "New Service Request - {$service_type}",
                    'message' => "New {$service_type} service request ({$reference_number}) from {$name}. Contact: {$phone}",
                    'request_id' => $request_id
                ]);
                
                // Also log the admin email notification attempt
                if (isset($adminEmailSent) && $adminEmailSent) {
                    $emailNotification->logEmailActivity('admin@smartfix.com', 'New Service Request Notification', 'admin_notification', 'sent', $request_id);
                }
            } catch (PDOException $e) {
                // Notifications table might not exist yet, log the error
                error_log("Could not create admin notification: " . $e->getMessage());
            }
            
        } catch (PDOException $e) {
            // Log the actual error for debugging
            error_log("Service Request Error: " . $e->getMessage());
            
            // Handle specific database errors
            if (strpos($e->getMessage(), "doesn't exist") !== false || $e->getCode() == '42S02') {
                // Table doesn't exist - try to create it
                try {
                    $create_table = "CREATE TABLE service_requests (
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
                    $pdo->exec($create_table);
                    
                    // Try inserting again with the new table
                    $stmt = $pdo->prepare($query);
                    $success = $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'service_type' => $service_type,
                        'service_option' => $service_option,
                        'description' => $description,
                        'address' => $address,
                        'preferred_date' => $preferred_date ?: null,
                        'preferred_time' => $preferred_time ?: null
                    ]);
                    
                    if (!$success) {
                        throw new PDOException("Failed to insert after table creation");
                    }
                    
                    // Generate reference number
                    $request_id = $pdo->lastInsertId();
                    $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
                    
                    // Update reference number
                    $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->execute([
                        'reference_number' => $reference_number,
                        'id' => $request_id
                    ]);
                    
                    $success_message = "Thank you! Your service request has been submitted successfully. Your reference number is: <strong>{$reference_number}</strong>";
                    
                    // Send email notifications
                    try {
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
                        
                        $emailSent = $emailNotification->sendServiceRequestConfirmation($email, $name, $serviceDetails);
                        if ($emailSent) {
                            $success_message .= "<br><small>📧 A confirmation email has been sent to your email address.</small>";
                        }
                    } catch (Exception $emailError) {
                        // Email error - don't fail the whole process
                        error_log("Email notification error: " . $emailError->getMessage());
                    }
                    
                    // Clear form data after successful submission
                    $name = $email = $phone = $service_option = $description = $address = $preferred_date = $preferred_time = '';
                    
                } catch (PDOException $e2) {
                    error_log("Failed to create table or insert: " . $e2->getMessage());
                    $error_message = "Database error: Unable to process your request. Please contact support or try again later.";
                }
            } else if (strpos($e->getMessage(), "Unknown column") !== false) {
                // Missing columns - user should run the fix script
                $error_message = "Database structure issue detected. Please run the <a href='../fix_service_requests_system.php' target='_blank'>database repair tool</a> first.";
            } else {
                // Other database errors
                $error_message = "Sorry, there was an error submitting your request. Please try again later. (Error: " . $e->getCode() . ")";
            }
        } catch (Exception $e) {
            // General errors
            error_log("General error in service request: " . $e->getMessage());
            $error_message = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $current_service['title']; ?> | SmartFix</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      color: #333;
      line-height: 1.6;
    }

    /* Professional Header */
    header {
      background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
      color: white;
      padding: 1.5rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      position: relative;
      overflow: hidden;
    }

    header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    .logo {
      font-size: 28px;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      z-index: 1;
    }

    nav {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      z-index: 1;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin: 0 15px;
      font-weight: 500;
      transition: all 0.3s ease;
      padding: 8px 16px;
      border-radius: 25px;
      position: relative;
    }

    nav a:hover {
      color: #ffd700;
      background: rgba(255,255,255,0.1);
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    nav a i {
      margin-right: 8px;
    }

    /* Mobile Menu Hamburger */
    .hamburger {
      display: none;
      flex-direction: column;
      cursor: pointer;
      padding: 8px;
      z-index: 1001;
    }

    .hamburger span {
      width: 25px;
      height: 3px;
      background: white;
      margin: 3px 0;
      transition: all 0.3s ease;
      border-radius: 3px;
    }

    .hamburger.active span:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }

    .hamburger.active span:nth-child(2) {
      opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
      transform: rotate(-45deg) translate(7px, -6px);
    }

    /* Mobile Navigation Overlay */
    .nav-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }

    .nav-overlay.active {
      display: block;
    }

    /* Professional Page Header */
    .page-header {
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: white;
      text-align: center;
      padding: 120px 20px 80px;
      position: relative;
      overflow: hidden;
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(102, 126, 234, 0.3), rgba(118, 75, 162, 0.3));
    }

    .page-header h1 {
      font-size: 48px;
      margin-bottom: 20px;
      animation: fadeInUp 1s ease;
      text-shadow: 0 4px 8px rgba(0,0,0,0.3);
      position: relative;
      z-index: 1;
    }

    .page-header p {
      font-size: 20px;
      max-width: 800px;
      margin: 0 auto;
      animation: fadeInUp 1.2s ease;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      position: relative;
      z-index: 1;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 60px 20px;
    }

    /* Professional Section Title */
    .section-title {
      text-align: center;
      margin-bottom: 50px;
      position: relative;
    }

    .section-title h2 {
      font-size: 36px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      position: relative;
      display: inline-block;
      margin-bottom: 20px;
      font-weight: 700;
    }

    .section-title h2:after {
      content: '';
      position: absolute;
      width: 60px;
      height: 4px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      border-radius: 2px;
    }

    .section-title p {
      color: #666;
      font-size: 18px;
      max-width: 700px;
      margin: 0 auto;
    }

    /* Professional Service Details */
    .service-details {
      background: linear-gradient(135deg, #f8fdff 0%, #e8f4fd 100%);
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      border: 1px solid rgba(102, 126, 234, 0.1);
      position: relative;
      overflow: hidden;
    }

    .service-details::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .service-details h3 {
      color: #1e3c72;
      margin-top: 0;
      margin-bottom: 20px;
      font-size: 24px;
      font-weight: 600;
    }

    .service-details p {
      margin-bottom: 25px;
      color: #555;
      font-size: 16px;
    }

    .service-features {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }

    .feature-tag {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      font-weight: 500;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      transition: all 0.3s ease;
    }

    .feature-tag:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .feature-tag i {
      margin-right: 8px;
      font-size: 16px;
    }

    /* Professional Form Container */
    .form-container {
      background: linear-gradient(135deg, #ffffff 0%, #f8fdff 100%);
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      padding: 50px;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(102, 126, 234, 0.1);
    }

    .form-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #667eea 100%);
    }

    .form-container::after {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
      pointer-events: none;
    }

    /* Professional Form Elements */
    .form-group {
      margin-bottom: 30px;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 12px;
      font-weight: 600;
      color: #1e3c72;
      font-size: 16px;
      display: flex;
      align-items: center;
      transition: all 0.3s ease;
    }

    .form-group label i {
      margin-right: 10px;
      width: 20px;
      color: #667eea;
      font-size: 16px;
    }

    .form-control {
      width: 100%;
      padding: 18px 24px;
      border: 2px solid transparent;
      border-radius: 15px;
      font-size: 16px;
      transition: all 0.3s ease;
      box-sizing: border-box;
      background: linear-gradient(135deg, #f8fdff 0%, #ffffff 100%);
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
      position: relative;
    }

    .form-control:focus {
      border-color: #667eea;
      outline: none;
      background: white;
      box-shadow: 0 0 20px rgba(102, 126, 234, 0.2), inset 0 2px 4px rgba(0,0,0,0.05);
      transform: translateY(-2px);
    }

    .form-control:valid {
      border-color: #28a745;
      box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
    }

    select.form-control {
      background: linear-gradient(135deg, #f8fdff 0%, #ffffff 100%);
      cursor: pointer;
    }

    textarea.form-control {
      min-height: 140px;
      resize: vertical;
      font-family: inherit;
    }

    /* Professional Button */
    .btn-submit {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 18px 40px;
      border-radius: 50px;
      font-size: 18px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
      position: relative;
      overflow: hidden;
      min-width: 200px;
    }

    .btn-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.6s;
    }

    .btn-submit:hover::before {
      left: 100%;
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
      background: linear-gradient(135deg, #7b8bf0 0%, #8a63c7 100%);
    }

    .btn-submit:active {
      transform: translateY(-1px);
    }

    .btn-submit i {
      margin-left: 12px;
      font-size: 16px;
      transition: transform 0.3s ease;
    }

    .btn-submit:hover i {
      transform: translateX(3px);
    }

    /* Location Services Styles */
    .location-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      border: 2px dashed #dee2e6;
      text-align: center;
      margin-top: 10px;
    }

    .btn-location {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-location:hover {
      background: linear-gradient(135deg, #218838, #1ea080);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }

    .btn-location:disabled {
      background: #6c757d;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .btn-location i {
      margin-right: 8px;
    }

    .location-status {
      margin-top: 15px;
      font-size: 14px;
      font-weight: 500;
    }

    .location-status.loading {
      color: #007bff;
    }

    .location-status.success {
      color: #28a745;
    }

    .location-status.error {
      color: #dc3545;
    }

    .location-info {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
      border-left: 4px solid #28a745;
      text-align: left;
    }

    .location-info p {
      margin: 5px 0;
    }

    .location-info small {
      color: #6c757d;
      font-style: italic;
    }

    /* Professional Alerts */
    .alert {
      padding: 20px 25px;
      border-radius: 15px;
      margin-bottom: 30px;
      font-size: 16px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      display: flex;
      align-items: flex-start;
      position: relative;
      overflow: hidden;
    }

    .alert::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
    }

    .alert i {
      margin-right: 15px;
      font-size: 20px;
      margin-top: 2px;
    }

    .alert-success {
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: #155724;
    }

    .alert-success::before {
      background: #28a745;
    }

    .alert-success i {
      color: #28a745;
    }

    .alert-danger {
      background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
      color: #721c24;
    }

    .alert-danger::before {
      background: #dc3545;
    }

    .alert-danger i {
      color: #dc3545;
    }

    /* Professional Form Layout */
    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }

    .form-col {
      flex: 1;
      min-width: 280px;
    }

    .required-field::after {
      content: '*';
      color: #dc3545;
      margin-left: 6px;
      font-weight: bold;
    }

    /* Professional Input Focus Animation */
    .form-group {
      position: relative;
    }

    .form-group input:focus + .floating-label,
    .form-group input:not(:placeholder-shown) + .floating-label {
      top: -10px;
      left: 15px;
      font-size: 12px;
      color: #667eea;
      background: white;
      padding: 0 8px;
    }

    .floating-label {
      position: absolute;
      top: 50%;
      left: 24px;
      transform: translateY(-50%);
      color: #999;
      pointer-events: none;
      transition: all 0.3s ease;
      background: transparent;
    }

    footer {
      background: #004080;
      color: white;
      padding: 40px 20px;
      text-align: center;
      margin-top: 60px;
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 40px;
    }

    .footer-column {
      flex: 1;
      min-width: 200px;
      text-align: left;
    }

    .footer-column h3 {
      font-size: 18px;
      margin-bottom: 20px;
      position: relative;
      padding-bottom: 10px;
    }

    .footer-column h3:after {
      content: '';
      position: absolute;
      width: 30px;
      height: 2px;
      background: #007BFF;
      bottom: 0;
      left: 0;
    }

    .footer-column p {
      margin-bottom: 10px;
      font-size: 14px;
    }

    .footer-column a {
      color: #ccc;
      text-decoration: none;
      transition: color 0.3s;
      display: block;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .footer-column a:hover {
      color: white;
    }

    .social-links {
      display: flex;
      gap: 10px;
    }

    .social-links a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: rgba(255,255,255,0.1);
      color: white;
      border-radius: 50%;
      text-decoration: none;
      transition: all 0.3s ease;
      margin-bottom: 0;
    }

    .social-links a:hover {
      background: #007BFF;
      transform: translateY(-3px);
    }

    .footer-bottom {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid rgba(255,255,255,0.1);
      text-align: center;
      font-size: 14px;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 968px) {
      header {
        padding: 1rem;
      }
      
      nav {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        flex-direction: column;
        align-items: flex-start;
        padding: 100px 30px 30px;
        transition: left 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      }

      nav.active {
        left: 0;
      }

      nav a {
        width: 100%;
        margin: 10px 0;
        padding: 15px 20px;
        border-radius: 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
      }

      nav a i {
        width: 25px;
        margin-right: 15px;
        font-size: 18px;
      }

      .hamburger {
        display: flex;
      }

      .logo {
        font-size: 22px;
      }
    }

    @media (max-width: 768px) {
      .page-header h1 {
        font-size: 28px;
      }
      
      .page-header p {
        font-size: 16px;
      }
      
      .section-title h2 {
        font-size: 26px;
      }
      
      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>
<body>

<div class="nav-overlay" onclick="toggleMobileNav()"></div>

<header> 
  <div class="logo">SmartFixZed</div>
  
  <div class="hamburger" onclick="toggleMobileNav()">
    <span></span>
    <span></span>
    <span></span>
  </div>
  
  <nav id="mobileNav">
    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
    <a href="../services.php"><i class="fas fa-tools"></i> Services</a>
    <a href="../shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
    <a href="../about.php"><i class="fas fa-info-circle"></i> About</a>
    <a href="../contact.php"><i class="fas fa-phone"></i> Contact</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="../dashboard.php"><i class="fas fa-user"></i> My Account</a>
    <?php else: ?>
      <a href="../auth.php?form=login"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="../auth.php?form=register"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
  </nav>
</header>

<script>
function toggleMobileNav() {
    const nav = document.getElementById('mobileNav');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.nav-overlay');
    
    nav.classList.toggle('active');
    hamburger.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : 'auto';
}

// Close menu when clicking on a link
document.querySelectorAll('nav a').forEach(link => {
    link.addEventListener('click', () => {
        const nav = document.getElementById('mobileNav');
        const hamburger = document.querySelector('.hamburger');
        const overlay = document.querySelector('.nav-overlay');
        
        nav.classList.remove('active');
        hamburger.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
});
</script>

<div class="page-header">
  <h1><i class="<?php echo $current_service['icon']; ?>"></i> <?php echo $current_service['title']; ?></h1>
  <p><?php echo $current_service['description']; ?></p>
</div>

<div class="container">
  <div class="service-details">
    <h3>About This Service</h3>
    <p><?php echo $current_service['description']; ?> Our team of professionals is ready to assist you with all your <?php echo strtolower(str_replace(['Find a ', 'Find an '], '', $current_service['title'])); ?> needs.</p>
    
    <h3>What We Offer</h3>
    <div class="service-features">
      <?php foreach ($current_service['options'] as $option): ?>
        <div class="feature-tag"><i class="fas fa-check-circle"></i> <?php echo $option; ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="section-title">
    <h2>Request Service</h2>
    <p>Fill out the form below to request this service</p>
  </div>
  
  <?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
      <?php echo $success_message; ?>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
      <?php echo $error_message; ?>
    </div>
  <?php endif; ?>
  
  <div class="form-container">
    <form action="request_service.php?type=<?php echo $service_type; ?>" method="POST">
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="name" class="required-field">Your Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
          </div>
        </div>
        
        <div class="form-col">
          <div class="form-group">
            <label for="email" class="required-field">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
          </div>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="phone" class="required-field">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
          </div>
        </div>
        
        <div class="form-col">
          <div class="form-group">
            <label for="service_option" class="required-field">Service Option</label>
            <select id="service_option" name="service_option" class="form-control" required>
              <option value="">Select an option</option>
              <?php foreach ($current_service['options'] as $option): ?>
                <option value="<?php echo $option; ?>" <?php echo (isset($service_option) && $service_option === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      
      <div class="form-group">
        <label for="description" class="required-field">Describe Your Needs</label>
        <textarea id="description" name="description" class="form-control" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
      </div>
      
      <div class="form-group">
        <label for="address">Your Address</label>
        <textarea id="address" name="address" class="form-control"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
      </div>
      
      <!-- GPS Location Section -->
      <div class="form-group">
        <label>Location Services</label>
        <div class="location-section">
          <button type="button" id="get-location-btn" class="btn-location">
            <i class="fas fa-map-marker-alt"></i> Share My Location
          </button>
          <div id="location-status" class="location-status"></div>
          <div id="location-info" class="location-info" style="display: none;">
            <p><strong>Location captured:</strong></p>
            <p id="location-address"></p>
            <small>This helps us assign the nearest technician to you.</small>
          </div>
        </div>
        <input type="hidden" id="latitude" name="latitude" value="">
        <input type="hidden" id="longitude" name="longitude" value="">
        <input type="hidden" id="location_accuracy" name="location_accuracy" value="">
      </div>
      
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="preferred_date">Preferred Date</label>
            <input type="date" id="preferred_date" name="preferred_date" class="form-control" value="<?php echo isset($preferred_date) ? htmlspecialchars($preferred_date) : ''; ?>">
          </div>
        </div>
        
        <div class="form-col">
          <div class="form-group">
            <label for="preferred_time">Preferred Time</label>
            <select id="preferred_time" name="preferred_time" class="form-control">
              <option value="">Select a time</option>
              <option value="Morning (8AM-12PM)" <?php echo (isset($preferred_time) && $preferred_time === 'Morning (8AM-12PM)') ? 'selected' : ''; ?>>Morning (8AM-12PM)</option>
              <option value="Afternoon (12PM-4PM)" <?php echo (isset($preferred_time) && $preferred_time === 'Afternoon (12PM-4PM)') ? 'selected' : ''; ?>>Afternoon (12PM-4PM)</option>
              <option value="Evening (4PM-8PM)" <?php echo (isset($preferred_time) && $preferred_time === 'Evening (4PM-8PM)') ? 'selected' : ''; ?>>Evening (4PM-8PM)</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="form-group">
        <button type="submit" name="service_submit" class="btn-submit">Submit Request <i class="fas fa-paper-plane"></i></button>
      </div>
    </form>
  </div>
</div>

<footer>
  <div class="footer-content">
    <div class="footer-column">
      <h3>SmartFix</h3>
      <p>Your trusted partner for all repair services in Zambia. Quality repairs, genuine parts, and exceptional service.</p>
    </div>
    
    <div class="footer-column">
      <h3>Quick Links</h3>
      <a href="../index.php">Home</a>
      <a href="../services.php">Services</a>
      <a href="../shop.php">Shop</a>
      <a href="../about.php">About Us</a>
      <a href="../contact.php">Contact Us</a>
    </div>
    
    <div class="footer-column">
      <h3>Contact Info</h3>
      <p><i class="fas fa-map-marker-alt"></i> Great North Road, Chinsali at Kapasa Makasa University, Zambia</p>
      <p><i class="fas fa-phone"></i> +260 777041357</p>
      <p><i class="fas fa-phone"></i> +260 776992688</p>
      <p><i class="fas fa-envelope"></i> info@smartfix.co.zm</p>
    </div>
    
    <div class="footer-column">
      <h3>Follow Us</h3>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
  </div>
  
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> SmartFix. All Rights Reserved.</p>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const getLocationBtn = document.getElementById('get-location-btn');
    const locationStatus = document.getElementById('location-status');
    const locationInfo = document.getElementById('location-info');
    const locationAddress = document.getElementById('location-address');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const accuracyInput = document.getElementById('location_accuracy');

    if (getLocationBtn) {
        getLocationBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                showLocationStatus('Geolocation is not supported by this browser.', 'error');
                return;
            }

            // Disable button and show loading
            getLocationBtn.disabled = true;
            getLocationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
            showLocationStatus('Requesting your location...', 'loading');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    // Store coordinates
                    latitudeInput.value = latitude;
                    longitudeInput.value = longitude;
                    accuracyInput.value = accuracy;

                    // Get address from coordinates
                    reverseGeocode(latitude, longitude);

                    // Update button
                    getLocationBtn.innerHTML = '<i class="fas fa-check"></i> Location Captured';
                    getLocationBtn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                    
                    showLocationStatus('Location captured successfully!', 'success');
                },
                function(error) {
                    let errorMessage = 'Unable to get your location. ';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Please allow location access and try again.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                            break;
                    }
                    
                    showLocationStatus(errorMessage, 'error');
                    
                    // Reset button
                    getLocationBtn.disabled = false;
                    getLocationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Try Again';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutes
                }
            );
        });
    }

    function showLocationStatus(message, type) {
        locationStatus.textContent = message;
        locationStatus.className = 'location-status ' + type;
    }

    function reverseGeocode(lat, lng) {
        // Simple reverse geocoding using a free service
        // In production, you might want to use Google Maps API or another service
        fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=en`)
            .then(response => response.json())
            .then(data => {
                let address = 'Location coordinates captured';
                
                if (data && data.locality) {
                    address = data.locality;
                    if (data.city && data.city !== data.locality) {
                        address += ', ' + data.city;
                    }
                    if (data.countryName) {
                        address += ', ' + data.countryName;
                    }
                }
                
                locationAddress.textContent = address;
                locationInfo.style.display = 'block';
            })
            .catch(error => {
                console.log('Reverse geocoding failed:', error);
                locationAddress.textContent = `Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                locationInfo.style.display = 'block';
            });
    }
});
</script>

</body>
</html>
