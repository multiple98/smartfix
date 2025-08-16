<?php
session_start();
include('../includes/db.php');

// Process emergency request form
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emergency_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description']);
    $address = trim($_POST['address']);
    $lat = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $lng = isset($_POST['longitude']) ? $_POST['longitude'] : null;
    
    // Simple validation
    if (empty($name) || empty($phone) || empty($description) || empty($address)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Insert into database
            $query = "INSERT INTO service_requests (name, email, phone, service_type, service_option, description, address, status, is_emergency, latitude, longitude, created_at) 
                      VALUES (:name, :email, :phone, :service_type, 'Emergency Service', :description, :address, 'pending', 1, :latitude, :longitude, NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service_type' => $service_type,
                'description' => $description,
                'address' => $address,
                'latitude' => $lat,
                'longitude' => $lng
            ]);
            
            // Generate reference number
            $request_id = $pdo->lastInsertId();
            $reference_number = 'EM' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
            
            // Update reference number
            $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'reference_number' => $reference_number,
                'id' => $request_id
            ]);
            
            $success_message = "Your emergency request has been submitted! Our team will contact you shortly. Your reference number is: <strong>{$reference_number}</strong>";
            
            // Clear form data after successful submission
            $name_copy = $name; // Save a copy for the auto-assign feature
            $name = $email = $phone = $service_type = $description = $address = '';
            
            // Create notification for admin
            try {
                $notification_query = "INSERT INTO notifications (type, message, is_read, created_at) 
                                      VALUES ('emergency_request', :message, 0, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'message' => "EMERGENCY REQUEST ({$reference_number}) from {$name_copy} - {$phone}"
                ]);
            } catch (PDOException $e) {
                // Notifications table might not exist yet, ignore error
            }
            
            // Attempt to auto-assign a technician
            if (!empty($lat) && !empty($lng)) {
                // Call the auto-assign script in the background
                $auto_assign_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/auto_assign.php';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $auto_assign_url);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_exec($ch);
                curl_close($ch);
            }
            
        } catch (PDOException $e) {
            // If table doesn't exist, create it
            if ($e->getCode() == '42S02') {
                try {
                    $create_table = "CREATE TABLE service_requests (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        reference_number VARCHAR(20),
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(100),
                        phone VARCHAR(20) NOT NULL,
                        service_type VARCHAR(50) NOT NULL,
                        service_option VARCHAR(100) NOT NULL,
                        description TEXT NOT NULL,
                        address TEXT NOT NULL,
                        preferred_date DATE,
                        preferred_time VARCHAR(20),
                        status VARCHAR(20) DEFAULT 'pending',
                        is_emergency BOOLEAN DEFAULT 0,
                        latitude VARCHAR(20),
                        longitude VARCHAR(20),
                        technician_id INT,
                        completed_at DATETIME,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_table);
                    
                    // Try inserting again
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'service_type' => $service_type,
                        'description' => $description,
                        'address' => $address,
                        'latitude' => $lat,
                        'longitude' => $lng
                    ]);
                    
                    // Generate reference number
                    $request_id = $pdo->lastInsertId();
                    $reference_number = 'EM' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
                    
                    // Update reference number
                    $update_query = "UPDATE service_requests SET reference_number = :reference_number WHERE id = :id";
                    $update_stmt = $pdo->prepare($update_query);
                    $update_stmt->execute([
                        'reference_number' => $reference_number,
                        'id' => $request_id
                    ]);
                    
                    $success_message = "Your emergency request has been submitted! Our team will contact you shortly. Your reference number is: <strong>{$reference_number}</strong>";
                    
                    // Clear form data after successful submission
                    $name = $email = $phone = $service_type = $description = $address = '';
                    
                } catch (PDOException $e2) {
                    $error_message = "Sorry, there was an error submitting your request. Please call our emergency hotline at +260 777041357 or +260 776992688.";
                }
            } else {
                $error_message = "Sorry, there was an error submitting your request. Please call our emergency hotline at +260 777041357 or +260 776992688.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Emergency Service | SmartFix</title>
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

    header {
      background: #004080;
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
      color: #ffcc00;
    }

    .page-header {
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      text-align: center;
      padding: 60px 20px;
      position: relative;
    }

    .emergency-header {
      background: linear-gradient(rgba(220,53,69,0.8), rgba(220,53,69,0.8)), url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
    }

    .page-header h1 {
      font-size: 36px;
      margin-bottom: 15px;
      animation: fadeInUp 1s ease;
    }

    .page-header p {
      font-size: 18px;
      max-width: 800px;
      margin: 0 auto;
      animation: fadeInUp 1.2s ease;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .section-title {
      text-align: center;
      margin-bottom: 40px;
    }

    .section-title h2 {
      font-size: 32px;
      color: #dc3545;
      position: relative;
      display: inline-block;
      margin-bottom: 15px;
    }

    .section-title h2:after {
      content: '';
      position: absolute;
      width: 50px;
      height: 3px;
      background: #dc3545;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
    }

    .section-title p {
      color: #666;
      font-size: 18px;
      max-width: 700px;
      margin: 0 auto;
    }

    .emergency-info {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 30px;
      text-align: center;
    }

    .emergency-info h3 {
      color: #721c24;
      margin-top: 0;
    }

    .emergency-info .phone {
      font-size: 24px;
      font-weight: bold;
      margin: 15px 0;
    }

    .emergency-info .phone i {
      margin-right: 10px;
      animation: pulse 1.5s infinite;
    }

    .form-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #333;
    }

    .form-control {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      transition: border-color 0.3s;
      box-sizing: border-box;
    }

    .form-control:focus {
      border-color: #dc3545;
      outline: none;
    }

    textarea.form-control {
      min-height: 120px;
      resize: vertical;
    }

    .btn-submit {
      background: #dc3545;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
      display: inline-block;
    }

    .btn-submit:hover {
      background: #c82333;
      transform: translateY(-3px);
    }

    .alert {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
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

    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .form-col {
      flex: 1;
      min-width: 250px;
    }

    .required-field::after {
      content: '*';
      color: #dc3545;
      margin-left: 4px;
    }

    .emergency-steps {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      margin: 40px 0;
    }

    .step-card {
      flex: 1;
      min-width: 250px;
      max-width: 300px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 20px;
      text-align: center;
    }

    .step-number {
      width: 40px;
      height: 40px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: bold;
      margin: 0 auto 15px;
    }

    .step-card h3 {
      color: #333;
      margin-top: 0;
      margin-bottom: 10px;
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

    @keyframes pulse {
      0% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.2);
      }
      100% {
        transform: scale(1);
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

<header> 
  <div class="logo">SmartFixZed</div>
  <nav>
    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
    <a href="../services.php"><i class="fas fa-tools"></i> Services</a>
    <a href="../shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
    <a href="../about.php"><i class="fas fa-info-circle"></i> About</a>
    <a href="../contact.php"><i class="fas fa-phone"></i> Contact</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="../dashboard.php"><i class="fas fa-user"></i> My Account</a>
    <?php else: ?>
      <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="../register.php"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
  </nav>
</header>

<div class="page-header emergency-header">
  <h1><i class="fas fa-exclamation-triangle"></i> Emergency Service</h1>
  <p>Fast response for urgent repair needs</p>
</div>

<div class="container">
  <div class="emergency-info">
    <h3>Need immediate assistance?</h3>
    <p>Call our 24/7 emergency hotline:</p>
    <div class="phone"><i class="fas fa-phone-alt"></i> +260 777041357</div>
    <div class="phone"><i class="fas fa-phone-alt"></i> +260 776992688</div>
    <p>Our emergency team is available 24/7 to assist you with urgent repair needs along Great North Road, Chinsali at Kapasa Makasa University.</p>
  </div>

  <div class="section-title">
    <h2>Emergency Service Request</h2>
    <p>Fill out the form below for urgent assistance</p>
  </div>
  
  <div class="emergency-steps">
    <div class="step-card">
      <div class="step-number">1</div>
      <h3>Submit Request</h3>
      <p>Fill out the emergency form with your details and issue</p>
    </div>
    
    <div class="step-card">
      <div class="step-number">2</div>
      <h3>Immediate Response</h3>
      <p>Our team will contact you within 15 minutes</p>
    </div>
    
    <div class="step-card">
      <div class="step-number">3</div>
      <h3>Quick Resolution</h3>
      <p>A technician will be dispatched to your location ASAP</p>
    </div>
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
    <form action="emergency.php" method="POST">
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="name" class="required-field">Your Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
          </div>
        </div>
        
        <div class="form-col">
          <div class="form-group">
            <label for="phone" class="required-field">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required>
          </div>
        </div>
      </div>
      
      <div class="form-row">
        <div class="form-col">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
          </div>
        </div>
        
        <div class="form-col">
          <div class="form-group">
            <label for="service_type" class="required-field">Emergency Type</label>
            <select id="service_type" name="service_type" class="form-control" required>
              <option value="">Select emergency type</option>
              <option value="phone" <?php echo (isset($service_type) && $service_type === 'phone') ? 'selected' : ''; ?>>Phone Emergency</option>
              <option value="computer" <?php echo (isset($service_type) && $service_type === 'computer') ? 'selected' : ''; ?>>Computer Emergency</option>
              <option value="car" <?php echo (isset($service_type) && $service_type === 'car') ? 'selected' : ''; ?>>Vehicle Emergency</option>
              <option value="plumbing" <?php echo (isset($service_type) && $service_type === 'plumbing') ? 'selected' : ''; ?>>Plumbing Emergency</option>
              <option value="electrical" <?php echo (isset($service_type) && $service_type === 'electrical') ? 'selected' : ''; ?>>Electrical Emergency</option>
              <option value="other" <?php echo (isset($service_type) && $service_type === 'other') ? 'selected' : ''; ?>>Other Emergency</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="form-group">
        <label for="description" class="required-field">Describe Your Emergency</label>
        <textarea id="description" name="description" class="form-control" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
      </div>
      
      <div class="form-group">
        <label for="address" class="required-field">Your Address</label>
        <textarea id="address" name="address" class="form-control" required><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
      </div>
      
      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">
      
      <div class="form-group">
        <button type="submit" name="emergency_submit" class="btn-submit"><i class="fas fa-bolt"></i> Submit Emergency Request</button>
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
  // Get user's location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      document.getElementById('latitude').value = position.coords.latitude;
      document.getElementById('longitude').value = position.coords.longitude;
    }, function(error) {
      console.log("Location access denied or unavailable.");
    });
  }
</script>

</body>
</html>