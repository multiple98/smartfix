<?php
session_start();
include('includes/db.php');

// Check if user is logged in
$logged_in = isset($_SESSION['user_id']);

// Get technician ID from URL
$technician_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$technician = null;
$success_message = '';
$error_message = '';

// Get technician details
if ($technician_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ? AND status = 'available'");
        $stmt->execute([$technician_id]);
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$technician) {
            $error_message = "Technician not found or not available.";
        }
    } catch (PDOException $e) {
        $error_message = "Error retrieving technician details.";
    }
}

// Process booking form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Check if user is logged in
    if (!$logged_in) {
        $error_message = "Please log in to book a technician.";
    } else {
        $service_type = trim($_POST['service_type']);
        $description = trim($_POST['description']);
        $address = trim($_POST['address']);
        $preferred_date = trim($_POST['preferred_date']);
        $preferred_time = trim($_POST['preferred_time']);
        $contact_phone = trim($_POST['contact_phone']);
        
        // Simple validation
        if (empty($service_type) || empty($description) || empty($address) || empty($preferred_date) || empty($contact_phone)) {
            $error_message = "Please fill in all required fields.";
        } else {
            try {
                // Check if bookings table exists, if not create it
                $check_table = $pdo->query("SHOW TABLES LIKE 'bookings'");
                if ($check_table->rowCount() == 0) {
                    $create_table = "CREATE TABLE bookings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        technician_id INT NOT NULL,
                        service_type VARCHAR(100) NOT NULL,
                        description TEXT NOT NULL,
                        address TEXT NOT NULL,
                        preferred_date DATE NOT NULL,
                        preferred_time VARCHAR(20) NOT NULL,
                        contact_phone VARCHAR(20) NOT NULL,
                        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_table);
                }
                
                // Insert booking
                $query = "INSERT INTO bookings (user_id, technician_id, service_type, description, address, preferred_date, preferred_time, contact_phone, status) 
                          VALUES (:user_id, :technician_id, :service_type, :description, :address, :preferred_date, :preferred_time, :contact_phone, 'pending')";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'technician_id' => $technician_id,
                    'service_type' => $service_type,
                    'description' => $description,
                    'address' => $address,
                    'preferred_date' => $preferred_date,
                    'preferred_time' => $preferred_time,
                    'contact_phone' => $contact_phone
                ]);
                
                $success_message = "Your booking has been submitted successfully! The technician will contact you to confirm the appointment.";
            } catch (PDOException $e) {
                $error_message = "Error submitting booking: " . $e->getMessage();
            }
        }
    }
}

// Define service types
$service_types = [
    'Phone Repair',
    'Computer Repair',
    'Vehicle Repair',
    'Plumbing',
    'Electrical',
    'Home Appliance Repair',
    'General Maintenance',
    'HVAC',
    'Carpentry',
    'Other'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Technician - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #004080;
            --primary-light: #0066cc;
            --primary-dark: #00305f;
            --accent-color: #ffcc00;
            --accent-hover: #e6b800;
            --text-color: #333333;
            --text-light: #666666;
            --bg-color: #f9f9f9;
            --bg-light: #ffffff;
            --bg-dark: #f0f0f0;
            --shadow: 0 5px 15px rgba(0,0,0,0.1);
            --success-color: #28a745;
            --error-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        nav a:hover {
            color: var(--accent-color);
        }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .booking-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .technician-info {
            flex: 1;
            min-width: 300px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .technician-header {
            background-color: var(--primary-light);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .technician-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .technician-header p {
            margin: 5px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .technician-rating {
            margin-top: 10px;
            color: var(--accent-color);
        }
        
        .technician-details {
            padding: 20px;
        }
        
        .technician-details p {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        
        .technician-details i {
            width: 25px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .booking-form {
            flex: 2;
            min-width: 300px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 30px;
        }
        
        .booking-form h2 {
            margin-top: 0;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .date-time-container {
            display: flex;
            gap: 15px;
        }
        
        .date-time-container .form-group {
            flex: 1;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .login-message {
            text-align: center;
            padding: 30px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .login-message h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .login-message p {
            margin-bottom: 20px;
        }
        
        .error-container {
            text-align: center;
            padding: 40px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .error-container h2 {
            color: var(--error-color);
            margin-bottom: 15px;
        }
        
        .error-container p {
            margin-bottom: 20px;
        }
        
        footer {
            background: var(--primary-color);
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
            background: var(--accent-color);
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
            background: var(--accent-color);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .date-time-container {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">SmartFix</div>
        <nav>
            <a href="index.php">Home</a>
            <a href="services.php">Services</a>
            <a href="shop.php">Shop</a>
            <a href="technicians.php">Technicians</a>
            <a href="contact.php">Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>
    
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <p style="margin-top: 15px;">
                    <a href="technicians.php" class="btn">Back to Technicians</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn" style="margin-left: 10px;">View Your Bookings</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php elseif (!$technician): ?>
            <div class="error-container">
                <h2>Technician Not Found</h2>
                <p>The technician you're looking for is not available or doesn't exist.</p>
                <a href="technicians.php" class="btn">Browse All Technicians</a>
            </div>
        <?php elseif (!$logged_in): ?>
            <div class="login-message">
                <h2>Login Required</h2>
                <p>You need to be logged in to book a technician.</p>
                <a href="login.php?redirect=book_technician.php?id=<?php echo $technician_id; ?>" class="btn">Login Now</a>
                <p style="margin-top: 15px;">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        <?php else: ?>
            <div class="booking-container">
                <div class="technician-info">
                    <div class="technician-header">
                        <h2><?php echo htmlspecialchars($technician['name']); ?></h2>
                        <p><?php echo htmlspecialchars($technician['specialization']); ?></p>
                        <div class="technician-rating">
                            <?php
                            $rating = round($technician['rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                            <span>(<?php echo $technician['rating']; ?>/5)</span>
                        </div>
                    </div>
                    
                    <div class="technician-details">
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($technician['phone']); ?></p>
                        <?php if (!empty($technician['email'])): ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($technician['email']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($technician['address'])): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($technician['address']); ?></p>
                        <?php endif; ?>
                        <p><i class="fas fa-briefcase"></i> <?php echo $technician['total_jobs']; ?> jobs completed</p>
                        
                        <?php if (!empty($technician['regions'])): ?>
                            <p><i class="fas fa-map"></i> Service Areas:</p>
                            <div style="margin-left: 35px;">
                                <?php
                                $regions = explode(',', $technician['regions']);
                                foreach ($regions as $reg) {
                                    echo '<span style="display: inline-block; background-color: var(--primary-light); color: white; padding: 3px 8px; border-radius: 20px; font-size: 12px; margin: 2px;">' . htmlspecialchars(trim($reg)) . '</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="booking-form">
                    <h2>Book This Technician</h2>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="service_type">Service Type*</label>
                            <select name="service_type" id="service_type" class="form-control" required>
                                <option value="">Select Service Type</option>
                                <?php foreach ($service_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php if ($type === $technician['specialization']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Describe Your Issue*</label>
                            <textarea name="description" id="description" class="form-control" required placeholder="Please provide details about the problem you're experiencing..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Service Address*</label>
                            <textarea name="address" id="address" class="form-control" required placeholder="Enter the full address where the service is needed..."></textarea>
                        </div>
                        
                        <div class="date-time-container">
                            <div class="form-group">
                                <label for="preferred_date">Preferred Date*</label>
                                <?php
                                // Set minimum date to tomorrow
                                $min_date = date('Y-m-d', strtotime('+1 day'));
                                // Set maximum date to 30 days from now
                                $max_date = date('Y-m-d', strtotime('+30 days'));
                                ?>
                                <input type="date" name="preferred_date" id="preferred_date" class="form-control" required min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="preferred_time">Preferred Time*</label>
                                <select name="preferred_time" id="preferred_time" class="form-control" required>
                                    <option value="">Select Time</option>
                                    <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
                                    <option value="Afternoon (12PM-4PM)">Afternoon (12PM-4PM)</option>
                                    <option value="Evening (4PM-8PM)">Evening (4PM-8PM)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_phone">Contact Phone*</label>
                            <input type="tel" name="contact_phone" id="contact_phone" class="form-control" required placeholder="Enter your phone number...">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="submit_booking" class="btn btn-block">Submit Booking Request</button>
                        </div>
                        
                        <p style="margin-top: 15px; font-size: 14px; color: var(--text-light);">
                            * Required fields<br>
                            Note: This is a booking request. The technician will contact you to confirm availability and provide a quote.
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>About SmartFix</h3>
                <p>SmartFix is Zambia's leading repair service platform, connecting customers with skilled technicians for all repair needs.</p>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <a href="index.php">Home</a>
                <a href="services.php">Services</a>
                <a href="shop.php">Shop</a>
                <a href="technicians.php">Technicians</a>
                <a href="contact.php">Contact Us</a>
            </div>
            
            <div class="footer-column">
                <h3>Contact Us</h3>
                <p><i class="fas fa-map-marker-alt"></i> 123 Cairo Road, Lusaka, Zambia</p>
                <p><i class="fas fa-phone"></i> +260 977 123 456</p>
                <p><i class="fas fa-envelope"></i> info@smartfix.co.zm</p>
                
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SmartFix. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>