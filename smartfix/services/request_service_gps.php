<?php
session_start();
include('../includes/db.php');
require_once('../includes/EmailNotification.php');
require_once('../includes/GPSManager.php');

// Get service type from URL parameter
$service_type = isset($_GET['type']) ? $_GET['type'] : 'phone';

// Initialize GPS Manager
$gps = new GPSManager($pdo);

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
    
    // GPS data
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $location_accuracy = floatval($_POST['location_accuracy'] ?? 0);
    $preferred_technician_id = intval($_POST['preferred_technician_id'] ?? 0);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($description)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Create service_requests table if needed
            $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (
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
                preferred_technician_id INT,
                user_id INT,
                notes TEXT,
                completed_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Insert service request
            $query = "INSERT INTO service_requests (name, email, phone, service_type, service_option, description, address, preferred_date, preferred_time, preferred_technician_id, status, created_at) 
                      VALUES (:name, :email, :phone, :service_type, :service_option, :description, :address, :preferred_date, :preferred_time, :preferred_technician_id, 'pending', NOW())";
            
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
                'preferred_time' => $preferred_time ?: null,
                'preferred_technician_id' => $preferred_technician_id ?: null
            ]);
            
            if (!$success) {
                throw new PDOException("Failed to insert service request");
            }
            
            $request_id = $pdo->lastInsertId();
            $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
            
            // Update reference number
            $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute(['reference_number' => $reference_number, 'id' => $request_id]);
            
            // Save GPS location if available
            if ($latitude != 0 && $longitude != 0) {
                $gps->saveCustomerLocation($request_id, $latitude, $longitude, $address, $location_accuracy);
            }
            
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
                $adminEmailSent = $emailNotification->sendServiceRequestNotificationToAdmin($serviceDetails);
                
            } catch (Exception $emailError) {
                error_log("Email notification error: " . $emailError->getMessage());
            }
            
            $success_message = "✅ Thank you! Your service request has been submitted successfully.<br>
                              <strong>Reference Number:</strong> {$reference_number}<br>";
            
            if ($latitude != 0 && $longitude != 0) {
                $success_message .= "<strong>📍 Location:</strong> Saved with GPS coordinates<br>";
            }
            
            if ($preferred_technician_id > 0) {
                $success_message .= "<strong>👨‍🔧 Preferred Technician:</strong> Your preference has been noted<br>";
            }
            
            if (isset($emailSent) && $emailSent) {
                $success_message .= "<small>📧 A confirmation email has been sent to your email address.</small>";
            }
            
            // Clear form data
            $name = $email = $phone = $service_option = $description = $address = $preferred_date = $preferred_time = '';
            
        } catch (Exception $e) {
            error_log("GPS Service Request Error: " . $e->getMessage());
            $error_message = "Sorry, there was an error submitting your request. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $current_service['title']; ?> with GPS | SmartFix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .service-header {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            padding: 40px 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .service-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .service-header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .map-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007BFF;
        }

        .btn {
            background: #007BFF;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
        }

        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }

        /* GPS specific styles */
        .location-controls {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
        }

        .location-status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }

        .location-status.loading {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .location-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .location-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        #location-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007BFF;
            margin: 15px 0;
            display: none;
        }

        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        #nearest-technicians {
            margin-top: 20px;
            display: none;
        }

        .technicians-grid {
            display: grid;
            gap: 15px;
            margin-top: 15px;
        }

        .technician-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .technician-card:hover {
            border-color: #007BFF;
            background: #e7f3ff;
        }

        .technician-card.online {
            border-left: 4px solid #28a745;
        }

        .technician-card.recently_active {
            border-left: 4px solid #ffc107;
        }

        .technician-card.offline {
            border-left: 4px solid #6c757d;
        }

        .technician-card h5 {
            margin: 0 0 10px 0;
            color: #007BFF;
        }

        .technician-card .specialization {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .technician-card .rating {
            margin-bottom: 5px;
        }

        .technician-card .distance {
            font-weight: 600;
            color: #007BFF;
            margin-bottom: 5px;
        }

        .status-online { color: #28a745; font-weight: 600; }
        .status-recently_active { color: #ffc107; font-weight: 600; }
        .status-offline { color: #6c757d; font-weight: 600; }

        .required {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="service-header">
            <h1><i class="<?php echo $current_service['icon']; ?>"></i> <?php echo $current_service['title']; ?></h1>
            <p><?php echo $current_service['description']; ?></p>
            <p><i class="fas fa-map-marker-alt"></i> <strong>GPS-Enhanced Service</strong> - We'll find the nearest technician for you!</p>
        </div>

        <div class="main-content">
            <div class="form-section">
                <h2>📋 Service Request Details</h2>
                
                <?php if ($success_message): ?>
                    <div class="success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- GPS Location Controls -->
                    <div class="location-controls">
                        <h3><i class="fas fa-map-marker-alt"></i> Your Location</h3>
                        <p>We'll use your location to find the nearest available technicians and provide accurate service estimates.</p>
                        
                        <button type="button" id="detect-location-btn" class="btn">
                            <i class="fas fa-crosshairs"></i> Detect My Location
                        </button>
                        
                        <div id="location-status" class="location-status"></div>
                        <div id="location-info"></div>
                        
                        <!-- Hidden GPS fields -->
                        <input type="hidden" id="latitude" name="latitude" value="">
                        <input type="hidden" id="longitude" name="longitude" value="">
                        <input type="hidden" id="location_accuracy" name="location_accuracy" value="">
                        <input type="hidden" id="preferred_technician_id" name="preferred_technician_id" value="">
                    </div>

                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="service_option">Service Option <span class="required">*</span></label>
                        <select id="service_option" name="service_option" required>
                            <option value="">Select Service Option</option>
                            <?php foreach ($current_service['options'] as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo ($service_option ?? '') === $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Problem Description <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="4" required placeholder="Please describe the issue in detail..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="address">Address Details</label>
                        <textarea id="address" name="address" rows="3" placeholder="Your address will be auto-filled when you detect location, but you can add more details here..."><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="preferred_date">Preferred Date</label>
                        <input type="date" id="preferred_date" name="preferred_date" value="<?php echo htmlspecialchars($preferred_date ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="preferred_time">Preferred Time</label>
                        <select id="preferred_time" name="preferred_time">
                            <option value="">Any time</option>
                            <option value="morning">Morning (8AM - 12PM)</option>
                            <option value="afternoon">Afternoon (12PM - 5PM)</option>
                            <option value="evening">Evening (5PM - 8PM)</option>
                        </select>
                    </div>

                    <button type="submit" name="service_submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </form>
            </div>

            <div class="map-section">
                <h3><i class="fas fa-map"></i> Service Area Map</h3>
                <div id="map"></div>
                
                <div id="nearest-technicians"></div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <h4><i class="fas fa-info-circle"></i> GPS Features</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>📍 Auto-detect your location</li>
                        <li>🔍 Find nearest technicians</li>
                        <li>📏 Calculate distance & travel time</li>
                        <li>🗺️ Visual map showing service area</li>
                        <li>👨‍🔧 Choose preferred technician</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Google Maps API -->
    <?php 
    require_once('../config/maps_config.php');
    if (isGoogleMapsConfigured()): 
    ?>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo getGoogleMapsApiKey(); ?>&callback=initMap&libraries=geometry"></script>
    <?php else: ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('map').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #666; text-align: center; flex-direction: column;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></i>
                        <h3>Google Maps API Not Configured</h3>
                        <p>GPS features are not available. You can still submit your request with a manual address.</p>
                        <a href="../test_gps_setup.php" style="background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">Setup GPS</a>
                    </div>
                `;
                
                // Hide GPS-specific controls
                document.querySelector('.location-controls').style.display = 'none';
                document.getElementById('nearest-technicians').style.display = 'none';
            });
        </script>
    <?php endif; ?>
    
    <!-- Load GPS Location Manager -->
    <script src="../js/gps-location.js"></script>

    <script>
        // Initialize map when Google Maps loads
        function initMap() {
            if (window.gpsManager) {
                window.gpsManager.initializeMap('map', -1.9441, 30.0619); // Kigali center
            }
        }

        // Service type specific handling
        document.addEventListener('DOMContentLoaded', function() {
            const serviceTypeField = document.getElementById('service_option');
            
            // Update nearest technicians when service type changes
            serviceTypeField.addEventListener('change', function() {
                if (window.gpsManager && window.gpsManager.isLocationDetected) {
                    showNearestTechnicians(this.value);
                }
            });
        });
    </script>
</body>
</html>