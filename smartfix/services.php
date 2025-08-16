<?php
session_start();
include('includes/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Services | SmartFix</title>
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

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    }

    .service-card {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .service-image {
      height: 200px;
      overflow: hidden;
      position: relative;
    }

    .service-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .service-card:hover .service-image img {
      transform: scale(1.1);
    }

    .service-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.6));
    }

    .service-category {
      position: absolute;
      top: 15px;
      right: 15px;
      background: #007BFF;
      color: white;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .service-content {
      padding: 25px;
    }

    .service-content h3 {
      font-size: 22px;
      margin: 0 0 15px;
      color: #004080;
    }

    .service-content p {
      color: #666;
      margin-bottom: 20px;
      font-size: 15px;
    }

    .service-features {
      display: flex;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .feature {
      display: flex;
      align-items: center;
      margin-right: 15px;
      margin-bottom: 10px;
      font-size: 14px;
      color: #555;
    }

    .feature i {
      color: #007BFF;
      margin-right: 5px;
    }

    .service-btn {
      display: inline-block;
      background: #007BFF;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .service-btn:hover {
      background: #0056b3;
      transform: translateY(-3px);
    }

    .emergency-btn {
      background: #dc3545;
    }

    .emergency-btn:hover {
      background: #c82333;
    }

    .service-categories {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
      margin-bottom: 40px;
    }

    .category-btn {
      padding: 10px 20px;
      background: #f0f0f0;
      border: none;
      border-radius: 30px;
      cursor: pointer;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .category-btn:hover, .category-btn.active {
      background: #007BFF;
      color: white;
    }

    .category-btn i {
      margin-right: 8px;
    }

    .process-section {
      background: #f0f8ff;
      padding: 60px 0;
      margin: 60px 0;
    }

    .process-steps {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .process-step {
      flex: 1;
      min-width: 200px;
      max-width: 250px;
      text-align: center;
      position: relative;
    }

    .process-step:not(:last-child):after {
      content: '';
      position: absolute;
      top: 50px;
      right: -15px;
      width: 30px;
      height: 2px;
      background: #007BFF;
      display: none;
    }

    @media (min-width: 768px) {
      .process-step:not(:last-child):after {
        display: block;
      }
    }

    .step-number {
      width: 60px;
      height: 60px;
      background: #007BFF;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      margin: 0 auto 20px;
    }

    .process-step h3 {
      font-size: 18px;
      color: #004080;
      margin-bottom: 10px;
    }

    .process-step p {
      font-size: 14px;
      color: #666;
    }

    .testimonials-section {
      padding: 60px 0;
    }

    .testimonials-container {
      max-width: 1000px;
      margin: 0 auto;
      position: relative;
    }

    .testimonial-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      margin: 20px;
      text-align: center;
    }

    .testimonial-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      overflow: hidden;
      margin: 0 auto 20px;
      border: 3px solid #007BFF;
    }

    .testimonial-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .testimonial-text {
      font-style: italic;
      color: #555;
      margin-bottom: 20px;
      position: relative;
    }

    .testimonial-text:before, .testimonial-text:after {
      content: '"';
      font-size: 50px;
      color: #007BFF;
      opacity: 0.2;
      position: absolute;
    }

    .testimonial-text:before {
      top: -20px;
      left: -15px;
    }

    .testimonial-text:after {
      bottom: -40px;
      right: -15px;
    }

    .testimonial-name {
      font-weight: bold;
      color: #004080;
      margin-bottom: 5px;
    }

    .testimonial-role {
      font-size: 14px;
      color: #666;
    }

    .cta-section {
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      text-align: center;
      padding: 80px 20px;
    }

    .cta-content {
      max-width: 800px;
      margin: 0 auto;
    }

    .cta-content h2 {
      font-size: 36px;
      margin-bottom: 20px;
    }

    .cta-content p {
      font-size: 18px;
      margin-bottom: 30px;
      opacity: 0.9;
    }

    .cta-buttons {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 15px;
    }

    .cta-btn {
      display: inline-block;
      padding: 15px 30px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .cta-btn.primary {
      background: #007BFF;
      color: white;
    }

    .cta-btn.primary:hover {
      background: #0056b3;
      transform: translateY(-3px);
    }

    .cta-btn.secondary {
      background: transparent;
      color: white;
      border: 2px solid white;
    }

    .cta-btn.secondary:hover {
      background: rgba(255,255,255,0.1);
      transform: translateY(-3px);
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

    @media (max-width: 768px) {
      .page-header h1 {
        font-size: 36px;
      }
      
      .page-header p {
        font-size: 16px;
      }
      
      .section-title h2 {
        font-size: 28px;
      }
      
      .service-card {
        max-width: 400px;
        margin: 0 auto;
      }
    }
  </style>
</head>
<body>

<header> 
  <div class="logo">SmartFixZed</div>
  <nav>
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
    <a href="services.php"><i class="fas fa-tools"></i> Services</a>
    <a href="shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
    <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
    <a href="contact.php"><i class="fas fa-phone"></i> Contact</a>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="dashboard.php"><i class="fas fa-user"></i> My Account</a>
    <?php else: ?>
      <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
  </nav>
</header>

<div class="page-header">
  <h1>Our Services</h1>
  <p>Professional repair and maintenance services for all your needs</p>
</div>

<div class="container">
  <div class="section-title">
    <h2>What We Offer</h2>
    <p>From device repairs to finding professionals, we've got you covered</p>
  </div>
  
  <div class="service-categories">
    <button class="category-btn active" data-category="all"><i class="fas fa-th-large"></i> All Services</button>
    <button class="category-btn" data-category="repair"><i class="fas fa-tools"></i> Repairs</button>
    <button class="category-btn" data-category="find"><i class="fas fa-search"></i> Find Services</button>
    <button class="category-btn" data-category="other"><i class="fas fa-plus-circle"></i> Other Services</button>
  </div>
  
  <div class="services-grid">
    <!-- Repair Services -->
    <div class="service-card" data-category="repair">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1556656793-08538906a9f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Phone Repair">
        <div class="service-overlay"></div>
        <div class="service-category">Repair</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-mobile-alt"></i> Phone Repair</h3>
        <p>Professional repair services for all smartphone brands and models.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Screen Replacement</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Battery Issues</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Water Damage</div>
        </div>
        <a href="services/request_service.php?type=phone" class="service-btn">Request Service <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="service-card" data-category="repair">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1588702547919-26089e690ecc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Computer Repair">
        <div class="service-overlay"></div>
        <div class="service-category">Repair</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-laptop"></i> Computer Repair</h3>
        <p>Expert solutions for desktop and laptop computer issues.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Hardware Upgrades</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Virus Removal</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Data Recovery</div>
        </div>
        <a href="services/request_service.php?type=computer" class="service-btn">Request Service <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="service-card" data-category="repair">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1332&q=80" alt="Car Repair">
        <div class="service-overlay"></div>
        <div class="service-category">Repair</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-car"></i> Vehicle Repair</h3>
        <p>Professional automotive repair and maintenance services.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Engine Diagnostics</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Electrical Systems</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Regular Maintenance</div>
        </div>
        <a href="services/request_service.php?type=car" class="service-btn">Request Service <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <!-- Find Services -->
    <div class="service-card" data-category="find">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1073&q=80" alt="Find a House">
        <div class="service-overlay"></div>
        <div class="service-category">Find</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-home"></i> Find a House</h3>
        <p>Connect with real estate agents to find your perfect home.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Rental Properties</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Properties for Sale</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Agent Matching</div>
        </div>
        <a href="services/request_service.php?type=house" class="service-btn">Find Now <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="service-card" data-category="find">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1169&q=80" alt="Find a Plumber">
        <div class="service-overlay"></div>
        <div class="service-category">Find</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-wrench"></i> Find a Plumber</h3>
        <p>Connect with qualified plumbers for all your plumbing needs.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Leak Repairs</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Installation Services</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Emergency Plumbing</div>
        </div>
        <a href="services/request_service.php?type=plumber" class="service-btn">Find Now <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="service-card" data-category="find">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1621905252507-b35492cc74b4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1169&q=80" alt="Find an Electrician">
        <div class="service-overlay"></div>
        <div class="service-category">Find</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-bolt"></i> Find an Electrician</h3>
        <p>Connect with certified electricians for safe electrical work.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Wiring & Repairs</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Installation Services</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Safety Inspections</div>
        </div>
        <a href="services/request_service.php?type=electrician" class="service-btn">Find Now <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <!-- Other Services -->
    <div class="service-card" data-category="other">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Emergency Service">
        <div class="service-overlay"></div>
        <div class="service-category">Emergency</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-exclamation-triangle"></i> Emergency Service</h3>
        <p>Urgent repair services available 24/7 for critical situations.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> 24/7 Availability</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Priority Response</div>
          <div class="feature"><i class="fas fa-check-circle"></i> All Service Types</div>
        </div>
        <a href="services/emergency.php" class="service-btn emergency-btn"><i class="fas fa-bolt"></i> Emergency Help</a>
      </div>
    </div>
    
    <div class="service-card" data-category="other">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Track Service">
        <div class="service-overlay"></div>
        <div class="service-category">Track</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-search-location"></i> Track Repair Status</h3>
        <p>Check the current status of your ongoing repair service.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Real-time Updates</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Repair Timeline</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Technician Details</div>
        </div>
        <a href="services/track_service.php" class="service-btn">Track Now <i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
    
    <div class="service-card" data-category="other">
      <div class="service-image">
        <img src="https://images.unsplash.com/photo-1607082350899-7e105aa886ae?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="SmartFix Shop">
        <div class="service-overlay"></div>
        <div class="service-category">Shop</div>
      </div>
      <div class="service-content">
        <h3><i class="fas fa-shopping-cart"></i> SmartFix Shop</h3>
        <p>Quality replacement parts and accessories for your devices.</p>
        <div class="service-features">
          <div class="feature"><i class="fas fa-check-circle"></i> Genuine Parts</div>
          <div class="feature"><i class="fas fa-check-circle"></i> Accessories</div>
          <div class="feature"><i class="fas fa-check-circle"></i> DIY Kits</div>
        </div>
        <a href="shop.php" class="service-btn">Shop Now <i class="fas fa-shopping-cart"></i></a>
      </div>
    </div>
  </div>
</div>

<div class="process-section">
  <div class="container">
    <div class="section-title">
      <h2>How It Works</h2>
      <p>Our simple process to get your repair or service request fulfilled</p>
    </div>
    
    <div class="process-steps">
      <div class="process-step">
        <div class="step-number">1</div>
        <h3>Request Service</h3>
        <p>Choose your service and submit your request through our platform</p>
      </div>
      
      <div class="process-step">
        <div class="step-number">2</div>
        <h3>Get a Quote</h3>
        <p>Receive a detailed quote and timeline for your service</p>
      </div>
      
      <div class="process-step">
        <div class="step-number">3</div>
        <h3>Service Delivery</h3>
        <p>Our professionals will complete your service to the highest standard</p>
      </div>
      
      <div class="process-step">
        <div class="step-number">4</div>
        <h3>Satisfaction</h3>
        <p>Enjoy your repaired device or professional service with our guarantee</p>
      </div>
    </div>
  </div>
</div>

<div class="container testimonials-section">
  <div class="section-title">
    <h2>What Our Customers Say</h2>
    <p>Hear from people who have used our services</p>
  </div>
  
  <div class="testimonials-container">
    <div class="testimonial-card">
      <div class="testimonial-avatar">
        <img src="" alt="ABRAHAM KATONGO.">
      </div>
      <div class="testimonial-text">
        SmartFix repaired my phone screen in just 2 hours. The service was fast, professional, and the price was very reasonable. I'll definitely use them again!
      </div>
      <div class="testimonial-name">KATONGO ABRAHAM</div>
      <div class="testimonial-role">Chinsali, Zambia</div>
    </div>
  </div>
</div>

<div class="cta-section">
  <div class="cta-content">
    <h2>Ready to Get Started?</h2>
    <p>Request a service today and experience the SmartFix difference</p>
    <div class="cta-buttons">
      <a href="services/request_service.php" class="cta-btn primary"><i class="fas fa-tools"></i> Request Service</a>
      <a href="contact.php" class="cta-btn secondary"><i class="fas fa-phone"></i> Contact Us</a>
    </div>
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
      <a href="index.php">Home</a>
      <a href="services.php">Services</a>
      <a href="shop.php">Shop</a>
      <a href="about.php">About Us</a>
      <a href="contact.php">Contact Us</a>
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
  // Category filtering
  const categoryButtons = document.querySelectorAll('.category-btn');
  const serviceCards = document.querySelectorAll('.service-card');
  
  categoryButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Remove active class from all buttons
      categoryButtons.forEach(btn => btn.classList.remove('active'));
      
      // Add active class to clicked button
      button.classList.add('active');
      
      const category = button.getAttribute('data-category');
      
      // Show/hide cards based on category
      serviceCards.forEach(card => {
        if (category === 'all' || card.getAttribute('data-category') === category) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
</script>

</body>
</html>
