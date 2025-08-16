<?php
session_start();
include('includes/db.php');

// Initialize variables
$success_message = '';
$error_message = '';

// Define Zambian regions/provinces
$zambian_regions = [
    'Lusaka',
    'Copperbelt',
    'Central',
    'Eastern',
    'Luapula',
    'Muchinga',
    'Northern',
    'North-Western',
    'Southern',
    'Western'
];

// Define specializations
$specializations = [
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_technician'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $regions = isset($_POST['regions']) ? $_POST['regions'] : [];
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    
    // Simple validation
    if (empty($name) || empty($phone) || empty($specialization) || empty($regions)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if technicians table exists
            $check_table = $pdo->query("SHOW TABLES LIKE 'technicians'");
            if ($check_table->rowCount() == 0) {
                // Create technicians table
                $create_table = "CREATE TABLE technicians (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    email VARCHAR(100),
                    specialization VARCHAR(100) NOT NULL,
                    regions TEXT NOT NULL,
                    address TEXT,
                    latitude VARCHAR(20),
                    longitude VARCHAR(20),
                    status ENUM('available', 'busy', 'offline') DEFAULT 'available',
                    bio TEXT,
                    rating DECIMAL(3,1) DEFAULT 0,
                    total_jobs INT DEFAULT 0,
                    user_id INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )";
                $pdo->exec($create_table);
            }
            
            // Convert regions array to comma-separated string
            $regions_str = implode(',', $regions);
            
            // Insert new technician
            $query = "INSERT INTO technicians (name, phone, email, specialization, regions, address, bio, status) 
                      VALUES (:name, :phone, :email, :specialization, :regions, :address, :bio, 'available')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'specialization' => $specialization,
                'regions' => $regions_str,
                'address' => $address,
                'bio' => $bio
            ]);
            
            $success_message = "Thank you for registering as a technician! Your application has been submitted successfully. Our team will review your information and contact you shortly.";
        } catch (PDOException $e) {
            // If there's an error with the bio column, redirect to reset script
            if (strpos($e->getMessage(), "Unknown column 'bio'") !== false) {
                header("Location: reset_technicians_table.php");
                exit;
            } else {
                $error_message = "Error registering technician: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as a Technician - SmartFix</title>
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
        
        .page-title {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('img/technician-banner.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 60px 20px;
        }
        
        .page-title h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .page-title p {
            font-size: 18px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .registration-form {
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 30px;
        }
        
        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: var(--text-light);
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
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
        }
        
        .checkbox-item input {
            margin-right: 8px;
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
        
        .required-note {
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-light);
            text-align: center;
        }
        
        .benefits-section {
            margin-top: 40px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 30px;
        }
        
        .benefits-section h3 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .benefit-item {
            text-align: center;
            padding: 15px;
        }
        
        .benefit-icon {
            font-size: 36px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .benefit-title {
            font-weight: 600;
            margin-bottom: 8px;
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
            .checkbox-group {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
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
    
    <div class="page-title">
        <h1>Join Our Network of Technicians</h1>
        <p>Expand your client base and grow your business with SmartFix</p>
    </div>
    
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <p style="margin-top: 15px;">
                    <a href="index.php" class="btn">Back to Home</a>
                </p>
            </div>
        <?php else: ?>
            <div class="registration-form">
                <div class="form-header">
                    <h2>Technician Registration</h2>
                    <p>Fill out the form below to join our network of skilled technicians</p>
                </div>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name*</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number*</label>
                        <input type="tel" name="phone" id="phone" class="form-control" required placeholder="e.g., +260 977123456">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="e.g., yourname@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="specialization">Primary Specialization*</label>
                        <select name="specialization" id="specialization" class="form-control" required>
                            <option value="">Select Your Specialization</option>
                            <?php foreach ($specializations as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec); ?>"><?php echo htmlspecialchars($spec); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Regions You Serve*</label>
                        <div class="checkbox-group">
                            <?php foreach ($zambian_regions as $region): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="regions[]" id="region_<?php echo htmlspecialchars($region); ?>" value="<?php echo htmlspecialchars($region); ?>">
                                    <label for="region_<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Business Address</label>
                        <textarea name="address" id="address" class="form-control" placeholder="Enter your business address..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">About Yourself & Your Experience*</label>
                        <textarea name="bio" id="bio" class="form-control" required placeholder="Describe your skills, experience, and the services you offer..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="register_technician" class="btn btn-block">Submit Application</button>
                    </div>
                    
                    <p class="required-note">* Required fields</p>
                </form>
            </div>
            
            <div class="benefits-section">
                <h3>Benefits of Joining SmartFix</h3>
                
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="benefit-title">Expand Your Client Base</div>
                        <p>Reach more customers looking for your specific skills and services.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="benefit-title">Flexible Schedule</div>
                        <p>Work on your own terms and accept jobs that fit your availability.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="benefit-title">Build Your Reputation</div>
                        <p>Earn reviews and ratings to showcase your quality work.</p>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="benefit-title">Increase Your Income</div>
                        <p>Access a steady stream of job opportunities to boost your earnings.</p>
                    </div>
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