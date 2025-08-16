<?php
session_start();
include('includes/db.php');

// Process contact form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $phone = trim($_POST['phone']);
  $subject = trim($_POST['subject']);
  $message = trim($_POST['message']);
  
  // Simple validation
  if (empty($name) || empty($email) || empty($message)) {
    $error_message = "Please fill in all required fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Please enter a valid email address.";
  } else {
    try {
      // Insert into database
      $query = "INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                VALUES (:name, :email, :phone, :subject, :message, NOW())";
      $stmt = $pdo->prepare($query);
      $stmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message
      ]);
      
      $success_message = "Thank you for your message! We'll get back to you soon.";
      
      // Clear form data after successful submission
      $name = $email = $phone = $subject = $message = '';
    } catch (PDOException $e) {
      // If table doesn't exist, create it
      if ($e->getCode() == '42S02') {
        try {
          $create_table = "CREATE TABLE contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(200),
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
          )";
          $pdo->exec($create_table);
          
          // Try inserting again
          $stmt = $pdo->prepare($query);
          $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
          ]);
          
          $success_message = "Thank you for your message! We'll get back to you soon.";
          
          // Clear form data after successful submission
          $name = $email = $phone = $subject = $message = '';
        } catch (PDOException $e2) {
          $error_message = "Sorry, there was an error sending your message. Please try again later.";
        }
      } else {
        $error_message = "Sorry, there was an error sending your message. Please try again later.";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us | SmartFix</title>
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

    .page-header {
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1174&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      text-align: center;
      padding: 100px 20px;
      position: relative;
    }

    .page-header h1 {
      font-size: 48px;
      margin-bottom: 20px;
      animation: fadeInUp 1s ease;
    }

    .page-header p {
      font-size: 20px;
      max-width: 800px;
      margin: 0 auto;
      animation: fadeInUp 1.2s ease;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px 20px;
    }

    .section-title {
      text-align: center;
      margin-bottom: 50px;
    }

    .section-title h2 {
      font-size: 36px;
      color: #004080;
      position: relative;
      display: inline-block;
      margin-bottom: 15px;
    }

    .section-title h2:after {
      content: '';
      position: absolute;
      width: 50px;
      height: 3px;
      background: #007BFF;
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

    .contact-info-section {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-bottom: 60px;
    }

    .contact-info-card {
      flex: 1;
      min-width: 250px;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .contact-info-card:hover {
      transform: translateY(-10px);
    }

    .contact-icon {
      width: 70px;
      height: 70px;
      background: #f0f8ff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 30px;
      color: #007BFF;
    }

    .contact-info-card h3 {
      font-size: 20px;
      color: #004080;
      margin-bottom: 15px;
    }

    .contact-info-card p {
      color: #666;
      margin-bottom: 10px;
    }

    .contact-info-card a {
      color: #007BFF;
      text-decoration: none;
      transition: color 0.3s;
    }

    .contact-info-card a:hover {
      color: #0056b3;
    }

    .contact-form-section {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      margin-bottom: 60px;
    }

    .contact-form {
      flex: 1;
      min-width: 300px;
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
    }

    .form-control:focus {
      border-color: #007BFF;
      outline: none;
    }

    textarea.form-control {
      min-height: 150px;
      resize: vertical;
    }

    .btn-submit {
      background: #007BFF;
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 5px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-submit:hover {
      background: #0056b3;
      transform: translateY(-3px);
    }

    .contact-image {
      flex: 1;
      min-width: 300px;
    }

    .contact-image img {
      width: 100%;
      height: auto;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .map-section {
      margin-bottom: 60px;
    }

    .map-container {
      height: 450px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .map-container iframe {
      width: 100%;
      height: 100%;
      border: 0;
    }

    .business-hours {
      background: #f0f8ff;
      padding: 60px 20px;
      margin-bottom: 60px;
    }

    .hours-container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .hours-table {
      width: 100%;
      border-collapse: collapse;
    }

    .hours-table tr {
      border-bottom: 1px solid #eee;
    }

    .hours-table tr:last-child {
      border-bottom: none;
    }

    .hours-table td {
      padding: 15px 10px;
    }

    .hours-table td:first-child {
      font-weight: bold;
      color: #004080;
    }

    .hours-table td:last-child {
      text-align: right;
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

    .faq-section {
      margin-bottom: 60px;
    }

    .faq-container {
      max-width: 800px;
      margin: 0 auto;
    }

    .faq-item {
      margin-bottom: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      overflow: hidden;
    }

    .faq-question {
      padding: 20px;
      background: #f8f9fa;
      font-weight: bold;
      color: #004080;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .faq-question:hover {
      background: #e9ecef;
    }

    .faq-answer {
      padding: 0 20px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease;
    }

    .faq-item.active .faq-answer {
      padding: 20px;
      max-height: 500px;
    }

    .faq-toggle {
      transition: transform 0.3s ease;
    }

    .faq-item.active .faq-toggle {
      transform: rotate(180deg);
    }

    footer {
      background: #004080;
      color: white;
      padding: 40px 20px;
      text-align: center;
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
      .contact-form-section {
        flex-direction: column-reverse;
      }
      
      .contact-image {
        margin-bottom: 30px;
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
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
    <a href="services.php"><i class="fas fa-tools"></i> Services</a>
    <a href="shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
    <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
    <a href="contact.php"><i class="fas fa-phone"></i> Contact</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php"><i class="fas fa-user"></i> My Account</a>
    <?php else: ?>
      <a href="auth.php?form=login"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="auth.php?form=register"><i class="fas fa-user-plus"></i> Register</a>
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
  <h1>Contact Us</h1>
  <p>We're here to help with all your repair needs</p>
</div>

<div class="container">
  <div class="section-title">
    <h2>Get In Touch</h2>
    <p>Have questions or need assistance? Reach out to us through any of these channels</p>
  </div>
  
  <div class="contact-info-section">
    <div class="contact-info-card">
      <div class="contact-icon">
        <i class="fas fa-map-marker-alt"></i>
      </div>
      <h3>Our Location</h3>
      <p>Kapasa Makasa University</p>
      <p>Chinsali, Zambia</p>
    </div>
    
    <div class="contact-info-card">
      <div class="contact-icon">
        <i class="fas fa-phone-alt"></i>
      </div>
      <h3>Phone Number</h3>
      <p><a href="tel:+260 777041357">+260 777041357</a></p>
      <p><a href="tel:+260 776992688">+260 776992688</a></p>
    </div>
    
    <div class="contact-info-card">
      <div class="contact-icon">
        <i class="fas fa-envelope"></i>
      </div>
      <h3>Email Address</h3>
      <p><a href="mailto:info@smartfixzed.com">info@smartfixzed.com</a></p>
      <p><a href="mailto:support@smartfix.co.zm">support@smartfixzed.com</a></p>
    </div>
    
    <div class="contact-info-card">
      <div class="contact-icon">
        <i class="fas fa-clock"></i>
      </div>
      <h3>Business Hours</h3>
      <p>Monday - Friday: 8:00 AM - 6:00 PM</p>
      <p>Saturday: 9:00 AM - 4:00 PM</p>
    </div>
  </div>
  
  <div class="contact-form-section">
    <div class="contact-form">
      <h3>Send Us a Message</h3>
      
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          <div><?php echo $success_message; ?></div>
        </div>
      <?php endif; ?>
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle"></i>
          <div><?php echo $error_message; ?></div>
        </div>
      <?php endif; ?>
      
      <form action="contact.php" method="POST">
        <div class="form-group">
          <label for="name"><i class="fas fa-user"></i>Your Name *</label>
          <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required placeholder="Enter your full name">
        </div>
        
        <div class="form-group">
          <label for="email"><i class="fas fa-envelope"></i>Email Address *</label>
          <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required placeholder="your.email@example.com">
        </div>
        
        <div class="form-group">
          <label for="phone"><i class="fas fa-phone"></i>Phone Number</label>
          <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" placeholder="+260 XXX XXX XXX">
        </div>
        
        <div class="form-group">
          <label for="subject"><i class="fas fa-tag"></i>Subject</label>
          <input type="text" id="subject" name="subject" class="form-control" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" placeholder="Brief description of your inquiry">
        </div>
        
        <div class="form-group">
          <label for="message"><i class="fas fa-edit"></i>Your Message *</label>
          <textarea id="message" name="message" class="form-control" required placeholder="Please provide details about your inquiry or feedback..."><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
          <button type="submit" name="contact_submit" class="btn-submit">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </div>
      </form>
    </div>
    
    <div class="contact-image">
      <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Contact SmartFix">
    </div>
  </div>
</div>

<div class="map-section">
  <div class="container">
    <div class="section-title">
      <h2>Find Us</h2>
      <p>Visit our repair center in Chinsali</p>
    </div>
    
    <div class="map-container">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d61293.30566999788!2d28.266886241796875!3d-15.416786499999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19408b6f8ed79c7d%3A0x7d5d1d93dc172a89!2sLusaka%2C%20Zambia!5e0!3m2!1sen!2sus!4v1623345678901!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
    </div>
  </div>
</div>

<div class="business-hours">
  <div class="container">
    <div class="section-title">
      <h2>Business Hours</h2>
      <p>When you can visit us or call for service</p>
    </div>
    
    <div class="hours-container">
      <table class="hours-table">
        <tr>
          <td>Monday</td>
          <td>8:00 AM - 6:00 PM</td>
        </tr>
        <tr>
          <td>Tuesday</td>
          <td>8:00 AM - 6:00 PM</td>
        </tr>
        <tr>
          <td>Wednesday</td>
          <td>8:00 AM - 6:00 PM</td>
        </tr>
        <tr>
          <td>Thursday</td>
          <td>8:00 AM - 6:00 PM</td>
        </tr>
        <tr>
          <td>Friday</td>
          <td>8:00 AM - 6:00 PM</td>
        </tr>
        <tr>
          <td>Saturday</td>
          <td>9:00 AM - 4:00 PM</td>
        </tr>
        <tr>
          <td>Sunday</td>
          <td>Closed</td>
        </tr>
      </table>
    </div>
  </div>
</div>

<div class="container faq-section">
  <div class="section-title">
    <h2>Frequently Asked Questions</h2>
    <p>Quick answers to common questions</p>
  </div>
  
  <div class="faq-container">
    <div class="faq-item">
      <div class="faq-question">
        How long does a typical repair take?
        <i class="fas fa-chevron-down faq-toggle"></i>
      </div>
      <div class="faq-answer">
        <p>Most phone and computer repairs are completed within 24-48 hours. Vehicle repairs may take 2-3 days depending on the complexity and parts availability. We'll provide you with a specific timeframe when you bring in your device or vehicle.</p>
      </div>
    </div>
    
    <div class="faq-item">
      <div class="faq-question">
        Do you offer warranty on repairs?
        <i class="fas fa-chevron-down faq-toggle"></i>
      </div>
      <div class="faq-answer">
        <p>Yes, all our repairs come with a 30-day warranty. If you experience any issues related to the repair within this period, we'll fix it at no additional cost.</p>
      </div>
    </div>
    
    <div class="faq-item">
      <div class="faq-question">
        Do I need an appointment?
        <i class="fas fa-chevron-down faq-toggle"></i>
      </div>
      <div class="faq-answer">
        <p>While walk-ins are welcome, we recommend scheduling an appointment to minimize wait times. You can book an appointment through our website, by phone, or via email.</p>
      </div>
    </div>
    
    <div class="faq-item">
      <div class="faq-question">
        What payment methods do you accept?
        <i class="fas fa-chevron-down faq-toggle"></i>
      </div>
      <div class="faq-answer">
        <p>We accept cash, credit/debit cards, mobile money transfers (MTN, Airtel), and bank transfers. Payment is typically required upon completion of the repair.</p>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-content">
    <div class="footer-column">
      <h3>SmartFixZed</h3>
      <p>Your trusted partner for all repair services in Zambia. Quality repairs, genuine parts, and exceptional service.</p>
    </div>
    
    <div class="footer-column">
      <h3>Quick Links</h3>
      <a href="index.php">Home</a>
      <a href="services.php">Services</a>
      <a href="shop.php">Shop</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact Us</a>
    </div>
    
    <div class="footer-column">
      <h3>Contact Info</h3>
      <p><i class="fas fa-map-marker-alt"></i> kapasa makasa university, chinsali, Zambia</p>
      <p><i class="fas fa-phone"></i> +260 777041357</p>
      <p><i class="fas fa-envelope"></i> info@smartfixzed.com</p>
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
    <p>&copy; <?php echo date('Y'); ?> SmartFix. All Rights Reserved.2025</p>
  </div>
</footer>

<script>
  // FAQ Accordion
  document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
      const faqItem = question.parentElement;
      faqItem.classList.toggle('active');
      
      // Close other open FAQs
      document.querySelectorAll('.faq-item').forEach(item => {
        if (item !== faqItem && item.classList.contains('active')) {
          item.classList.remove('active');
        }
      });
    });
  });
</script>

</body>
</html>