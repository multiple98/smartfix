<?php
session_start();
include('includes/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SmartFix | Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  
  <!-- PWA Meta Tags -->
  <meta name="theme-color" content="#007BFF">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="SmartFix">
  <meta name="description" content="SmartFix - Your trusted service management platform for repair services, technician bookings, and product sales">
  
  <!-- PWA Icons -->
  <link rel="icon" href="favicon.ico" sizes="32x32">
  <link rel="icon" href="img/icon-192x192.png" sizes="192x192">
  <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
  <link rel="manifest" href="manifest.json">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/mobile-responsive.css">
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
      --emergency-color: #e60000;
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 12px;
    }
    
    ::-webkit-scrollbar-track {
      background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--primary-light);
      border-radius: 6px;
      border: 3px solid #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-color);
    }
    
    .dark-mode {
      --primary-color: #1a5b9c;
      --primary-light: #2a6db3;
      --primary-dark: #0d4a8a;
      --accent-color: #ffcc00;
      --accent-hover: #e6b800;
      --text-color: #e0e0e0;
      --text-light: #b0b0b0;
      --bg-color: #121212;
      --bg-light: #1e1e1e;
      --bg-dark: #0a0a0a;
      --shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg-color);
      color: var(--text-color);
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    body.menu-open {
      overflow: hidden;
    }

    /* Old header styles removed to prevent conflicts with new mobile header */

    .notification {
      position: relative;
      display: inline-block;
    }

    .bell {
      font-size: 20px;
      margin-left: 10px;
    }

    .dot {
      position: absolute;
      top: -5px;
      right: -8px;
      height: 12px;
      width: 12px;
      background: red;
      border-radius: 50%;
    }

    .hero {
      padding: 60px 20px;
      text-align: center;
      background: #e6f0ff;
      animation: fadeIn 2s;
    }

    .hero h1 {
      font-size: 36px;
      margin-bottom: 10px;
    }

    .hero p {
      font-size: 18px;
      color: #333;
    }

    .btn-emergency {
      background: #e60000;
      color: white;
      padding: 10px 20px;
      border: none;
      font-weight: bold;
      border-radius: 6px;
      margin-top: 20px;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn-emergency:hover {
      background: #cc0000;
    }

    .services {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      padding: 40px 20px;
    }

    .card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
      transition: 0.4s;
    }

    .card:hover {
      transform: scale(1.03);
    }

    .card h3 {
      margin: 10px 0;
      color: #004080;
    }

    .card a {
      text-decoration: none;
      color: #0066cc;
      font-weight: bold;
    }

    .stats {
      background: #fff;
      text-align: center;
      padding: 40px 20px;
    }

    .stat {
      font-size: 28px;
      color: #004080;
      font-weight: bold;
    }

    .stat-label {
      color: #777;
      font-size: 14px;
    }

    .testimonials {
      background: #f0f8ff;
      padding: 40px 20px;
      text-align: center;
    }

    .testimonial-card {
      display: inline-block;
      max-width: 300px;
      margin: 10px;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      font-size: 14px;
    }

    .testimonial-card strong {
      display: block;
      margin-top: 10px;
      color: #004080;
    }

    .contact-info {
      background: #f2f2f2;
      padding: 20px;
      text-align: center;
    }

    .contact-info p {
      margin: 5px;
      font-size: 16px;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #004080;
      color: white;
    }

    @keyframes fadeIn {
      from {opacity: 0;}
      to {opacity: 1;}
    }
    .stats {
  background: #f0f8ff;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 40px;
  padding: 40px 20px;
  flex-wrap: wrap;
}

.stat-box {
  background: white;
  border-radius: 10px;
  padding: 20px 30px;
  text-align: center;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  transition: transform 0.3s;
}

.stat-box:hover {
  transform: scale(1.05);
}

.stat-icon {
  font-size: 40px;
  margin-bottom: 10px;
  color: #004080;
}

.stat-number {
  font-size: 28px;
  color: #004080;
  font-weight: bold;
}

.stat-label {
  color: #555;
  font-size: 16px;
}
.hero-slider {
  position: relative;
  height: 400px;
  overflow: hidden;
  color: white;
}

.slides {
  position: relative;
  height: 100%;
}

.slide {
  position: absolute;
  height: 100%;
  width: 100%;
  background-size: cover;
  background-position: center;
  opacity: 0;
  transition: opacity 1.5s ease-in-out;
}

.slide.active {
  opacity: 1;
  z-index: 1;
}

.hero-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 2;
  color: white;
  background: rgba(0, 0, 0, 0.4);
  padding: 20px 30px;
  border-radius: 10px;
  max-width: 600px;
}

.hero-content h1 {
  font-size: 36px;
  margin-bottom: 10px;
}

.hero-content p {
  font-size: 18px;
}

.hero-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}

.btn-emergency, .btn-primary, .btn-secondary {
  display: inline-block;
  padding: 12px 24px;
  font-weight: bold;
  border-radius: 30px;
  text-decoration: none;
  transition: all 0.3s;
  text-align: center;
}

.btn-emergency {
  background: #e60000;
  color: white;
}

.btn-emergency:hover {
  background: #cc0000;
  transform: translateY(-3px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-primary {
  background: #007BFF;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
  transform: translateY(-3px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
  transform: translateY(-3px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Featured Services Section */
.featured-services {
  padding: 60px 20px;
  background: #f8f9fa;
}

.section-header {
  text-align: center;
  margin-bottom: 40px;
}

.section-header h2 {
  color: #343a40;
  font-size: 32px;
  margin-bottom: 10px;
}

.section-header p {
  color: #6c757d;
  font-size: 18px;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .featured-services {
    padding: 40px 15px;
  }
  
  .section-header h2 {
    font-size: 28px;
  }
  
  .section-header p {
    font-size: 16px;
  }
  
  .services-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }
  
  .service-card {
    padding: 20px;
  }
}

@media (max-width: 480px) {
  .services-grid {
    grid-template-columns: 1fr;
    max-width: 300px;
    margin: 0 auto;
  }
}

.service-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  padding: 30px;
  text-align: center;
  transition: transform 0.3s;
}

.service-card:hover {
  transform: translateY(-10px);
}

.service-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.service-card h3 {
  color: #343a40;
  margin-bottom: 15px;
}

.service-card p {
  color: #6c757d;
  margin-bottom: 20px;
}

.service-btn {
  display: inline-block;
  background: #007BFF;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  text-decoration: none;
  transition: background 0.3s;
}

.service-btn:hover {
  background: #0056b3;
}

.service-features {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 15px 0 20px 0;
  justify-content: center;
}

.feature-tag {
  background: #e8f4fd;
  color: #004080;
  padding: 4px 8px;
  border-radius: 15px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid rgba(0, 64, 128, 0.2);
}

@media (max-width: 768px) {
  .service-features {
    margin: 10px 0 15px 0;
  }
  
  .feature-tag {
    font-size: 11px;
    padding: 3px 6px;
  }
}

/* How It Works Section */
.how-it-works {
  padding: 60px 20px;
  background: white;
}

.steps-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.step {
  flex: 1;
  min-width: 250px;
  text-align: center;
  padding: 30px;
  background: #f8f9fa;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.step-number {
  width: 50px;
  height: 50px;
  background: #007BFF;
  color: white;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 24px;
  font-weight: bold;
  margin: 0 auto 20px;
}

.step h3 {
  color: #343a40;
  margin-bottom: 15px;
}

.step p {
  color: #6c757d;
}

/* Call to Action Section */
.cta-section {
  background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('img/cta-bg.jpg');
  background-size: cover;
  background-position: center;
  color: white;
  text-align: center;
  padding: 80px 20px;
}

@media (max-width: 768px) {
  .cta-section {
    padding: 60px 15px;
  }
  
  .cta-content h2 {
    font-size: 28px;
  }
  
  .cta-content p {
    font-size: 16px;
  }
  
  .cta-btn {
    padding: 12px 25px;
  }
}

@media (max-width: 480px) {
  .cta-section {
    padding: 50px 15px;
  }
  
  .cta-content h2 {
    font-size: 24px;
  }
  
  .cta-content p {
    font-size: 15px;
    margin-bottom: 20px;
  }
  
  .cta-buttons {
    flex-direction: column;
    max-width: 250px;
    margin: 0 auto;
  }
  
  .cta-btn {
    width: 100%;
    margin-bottom: 10px;
    text-align: center;
  }
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
  transition: all 0.3s;
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

/* Social Links */
.social-links {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 15px;
}

.social-icon {
  color: #007BFF;
  text-decoration: none;
  transition: color 0.3s;
}

.social-icon:hover {
  color: #0056b3;
}

/* Footer Styles */
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

.nav-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.4);
  color: white;
  font-size: 28px;
  padding: 10px;
  border: none;
  cursor: pointer;
  z-index: 10;
  border-radius: 50%;
}

.nav-arrow.prev {
  left: 15px;
}

.nav-arrow.next {
  right: 15px;
}

.nav-arrow:hover {
  background: rgba(0,0,0,0.6);
}

.dots-container {
  position: absolute;
  bottom: 20px;
  width: 100%;
  text-align: center;
  z-index: 5;
}

.dot {
  height: 12px;
  width: 12px;
  margin: 0 5px;
  background-color: #bbb;
  border-radius: 50%;
  display: inline-block;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.dot.active {
  background-color: #fff;
}

  </style>
</head>
<body>

<header class="main-header">
  <div class="header-container">
    <div class="logo-container">
      <a href="index.php" class="logo">
        <div class="logo-icon">
          <i class="fas fa-tools"></i>
        </div>
        <div class="logo-content">
          <span class="logo-text">SmartFix</span>
          <span class="logo-tagline">Professional Repair Services</span>
        </div>
      </a>
    </div>
    
    <div class="mobile-menu-toggle" id="menuToggle">
      <span></span>
      <span></span>
      <span></span>
    </div>
    
    <nav class="main-nav" id="mainNav">
      <ul class="nav-list">
        <li class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-home"></i> Home</a></li>
        
        <li class="nav-item dropdown">
          <a href="javascript:void(0)" class="nav-link dropdown-toggle">
            <i class="fas fa-tools"></i> Services <i class="fas fa-chevron-down dropdown-icon"></i>
          </a>
          <ul class="dropdown-menu">
            <li><a href="services.php?type=phone"><i class="fas fa-mobile-alt"></i> Phone Repair</a></li>
            <li><a href="services.php?type=computer"><i class="fas fa-laptop"></i> Computer Repair</a></li>
            <li><a href="services.php?type=auto"><i class="fas fa-car"></i> Vehicle Repair</a></li>
            <li><a href="services.php"><i class="fas fa-list"></i> All Services</a></li>
            <li><a href="emergency.php" class="emergency-link"><i class="fas fa-bolt"></i> Emergency Repair</a></li>
          </ul>
        </li>
        
        <li class="nav-item"><a href="shop.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Shop</a></li>
        <li class="nav-item"><a href="technicians.php" class="nav-link"><i class="fas fa-user-md"></i> Technicians</a></li>
        <li class="nav-item"><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> About</a></li>
        <li class="nav-item"><a href="contact.php" class="nav-link"><i class="fas fa-phone"></i> Contact</a></li>
        
        <?php if (!isset($_SESSION['user_id']) && !(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)): ?>
        <li class="nav-item dropdown get-started-nav">
          <a href="javascript:void(0)" class="nav-link dropdown-toggle get-started-link">
            <i class="fas fa-sign-in-alt"></i> Get Started <i class="fas fa-chevron-down dropdown-icon"></i>
          </a>
          <ul class="dropdown-menu get-started-menu">
            <li class="dropdown-header">
              <i class="fas fa-sign-in-alt"></i> Choose Your Login
            </li>
            <li class="dropdown-divider"></li>
            <li><a href="auth.php?form=login"><i class="fas fa-user"></i> User Login</a></li>
            <li><a href="admin/admin_login.php" class="admin-login-link"><i class="fas fa-shield-alt"></i> Admin Login</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
      
      <div class="nav-actions">
        <!-- Theme Toggle Only -->
        <div class="theme-toggle">
          <button id="themeToggle" aria-label="Toggle dark mode" class="theme-btn">
            <i class="fas fa-moon"></i>
          </button>
        </div>
        
        <!-- User Account (for logged in users only) -->
        <?php if (isset($_SESSION['user_id']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)): ?>
          <div class="user-account-minimal">
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="user/dashboard.php" class="account-link" title="My Dashboard">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
              </a>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
              <a href="admin/admin_dashboard_new.php" class="account-link admin-link" title="Admin Dashboard">
                <i class="fas fa-shield-alt"></i>
                <span>Admin</span>
              </a>
            <?php endif; ?>
            
            <a href="logout.php" class="logout-link" title="Logout">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<style>
  /* Modern Header Styles */
  .main-header {
    background: linear-gradient(to right, #004080, #0066cc);
    color: white;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    width: 100%;
  }
  
  .header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0.8rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .logo-container {
    display: flex;
    align-items: center;
    z-index: 1002; /* Above mobile menu */
  }
  
  .logo {
    text-decoration: none;
    color: white;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    padding: 8px 12px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
  }
  
  .logo:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  }
  
  .logo-icon {
    background: linear-gradient(135deg, #ffcc00, #ff9900);
    color: #004080;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 20px;
    box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
    transition: all 0.3s ease;
  }
  
  .logo:hover .logo-icon {
    transform: rotate(5deg) scale(1.1);
    box-shadow: 0 6px 20px rgba(255, 204, 0, 0.4);
  }
  
  .logo-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  
  .logo-text {
    font-size: 24px;
    font-weight: 700;
    color: white;
    line-height: 1;
    margin-bottom: 2px;
    letter-spacing: -0.5px;
  }
  
  .logo-tagline {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
  }
  
  .main-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-grow: 1;
    margin-left: 40px;
  }
  
  .nav-list {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
  }
  
  .nav-item {
    position: relative;
    margin: 0 5px;
  }
  
  .nav-link {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    font-weight: 500;
    border-radius: 5px;
    transition: all 0.3s ease;
  }
  
  .nav-link i {
    margin-right: 8px;
  }
  
  .nav-link:hover, .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
  }
  
  .dropdown-toggle {
    cursor: pointer;
  }
  
  .dropdown-icon {
    font-size: 12px;
    margin-left: 5px;
    transition: transform 0.3s ease;
  }
  
  .dropdown:hover .dropdown-icon {
    transform: rotate(180deg);
  }
  
  .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    min-width: 200px;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 100;
    list-style: none;
    padding: 10px 0;
    margin: 0;
  }
  
  .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }
  
  .dropdown-menu li {
    margin: 0;
  }
  
  .dropdown-menu a {
    color: #333;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    text-decoration: none;
    transition: all 0.3s ease;
  }
  
  .dropdown-menu a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
  }
  
  .dropdown-menu a:hover {
    background: #f5f5f5;
    color: #004080;
  }
  
  .emergency-link {
    color: #e60000 !important;
    font-weight: bold;
  }
  
  .nav-actions {
    display: flex;
    align-items: center;
  }
  
  .auth-link {
    margin-left: 10px;
  }
  
  .auth-link.highlight {
    background: #ffcc00;
    color: #004080;
    padding: 8px 15px;
    border-radius: 50px;
    font-weight: bold;
  }
  
  .auth-link.highlight:hover {
    background: #e6b800;
    transform: translateY(-2px);
  }
  
  .admin-link {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 5px;
    margin-left: 10px;
  }
  
  .notification {
    position: relative;
    margin-left: 15px;
  }
  
  .bell {
    font-size: 20px;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
  }
  
  .bell:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  
  .notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e60000;
    color: white;
    font-size: 12px;
    font-weight: bold;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #004080;
  }
  
  .theme-toggle {
    margin-left: 15px;
  }
  
  #themeToggle {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
  }
  
  #themeToggle:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  
  .mobile-menu-toggle {
    display: none;
    flex-direction: column;
    justify-content: space-between;
    width: 30px;
    height: 21px;
    cursor: pointer;
    z-index: 1002; /* Above mobile menu */
    position: relative;
  }
  
  .mobile-menu-toggle span {
    display: block;
    height: 3px;
    width: 100%;
    background-color: white;
    border-radius: 3px;
    transition: all 0.3s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
  }
  
  .mobile-menu-toggle span.active:nth-child(1) {
    transform: translateY(9px) rotate(45deg);
  }
  
  .mobile-menu-toggle span.active:nth-child(2) {
    opacity: 0;
  }
  
  .mobile-menu-toggle span.active:nth-child(3) {
    transform: translateY(-9px) rotate(-45deg);
  }
  
  /* Responsive Styles */
  @media (max-width: 1024px) {
    .main-nav {
      margin-left: 20px;
    }
    
    .nav-link {
      padding: 10px;
    }
  }
  
  @media (max-width: 992px) {
    .nav-link {
      padding: 10px 8px;
      font-size: 14px;
    }
    
    .nav-link i {
      margin-right: 5px;
      font-size: 14px;
    }
    
    .auth-link.highlight {
      padding: 6px 12px;
      font-size: 14px;
    }
  }
  
  @media (max-width: 768px) {
    .header-container {
      padding: 0.8rem 1.5rem;
    }
    
    .logo {
      padding: 6px 10px;
    }
    
    .logo-icon {
      width: 38px;
      height: 38px;
      margin-right: 10px;
      font-size: 18px;
    }
    
    .logo-text {
      font-size: 20px;
    }
    
    .logo-tagline {
      font-size: 10px;
    }
    
    .mobile-menu-toggle {
      display: flex;
    }
    
    .main-nav {
      position: fixed;
      top: 0;
      right: -300px;
      width: 300px;
      height: 100vh;
      background: linear-gradient(135deg, #004080, #003366);
      flex-direction: column;
      align-items: flex-start;
      padding: 80px 20px 20px;
      transition: right 0.3s ease;
      margin-left: 0;
      overflow-y: auto;
      box-shadow: -5px 0 15px rgba(0,0,0,0.2);
      z-index: 1001;
    }
    
    .main-nav.active {
      right: 0;
    }
    
    .nav-list {
      flex-direction: column;
      align-items: flex-start;
      width: 100%;
    }
    
    .nav-item {
      width: 100%;
      margin: 5px 0;
    }
    
    .nav-link {
      width: 100%;
      padding: 12px 15px;
      font-size: 16px;
      border-radius: 8px;
    }
    
    .dropdown-menu {
      position: static;
      opacity: 1;
      visibility: visible;
      transform: none;
      box-shadow: none;
      background: rgba(0, 0, 0, 0.15);
      width: 100%;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      border-top: none;
      border-radius: 0 0 8px 8px;
    }
    
    .dropdown.active .dropdown-menu {
      max-height: 500px;
    }
    
    .dropdown-toggle::after {
      content: '\f078';
      font-family: 'Font Awesome 5 Free';
      font-weight: 900;
      margin-left: auto;
    }
    
    .nav-actions {
      flex-direction: column;
      width: 100%;
      margin-top: 20px;
      align-items: flex-start;
    }
    
    .auth-link, .admin-link {
      margin: 5px 0;
      width: 100%;
    }
    
    .notification, .theme-toggle {
      margin: 10px 0;
    }
  }
  
  @media (max-width: 480px) {
    .header-container {
      padding: 0.6rem 1rem;
    }
    
    .logo {
      padding: 4px 8px;
    }
    
    .logo-icon {
      width: 32px;
      height: 32px;
      margin-right: 8px;
      font-size: 16px;
    }
    
    .logo-text {
      font-size: 18px;
    }
    
    .logo-tagline {
      font-size: 9px;
    }
  }

  /* Enhanced Navigation Actions Styles */
  .nav-actions {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  /* User Account Section */
  .user-account-section {
    position: relative;
  }

  .user-profile {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 25px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
  }

  .user-profile:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
    color: white;
    text-decoration: none;
  }

  .user-avatar i {
    font-size: 24px;
    color: #ffcc00;
  }

  .user-name {
    font-weight: 500;
    color: white;
  }

  .user-dropdown {
    min-width: 280px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border: none;
  }

  .dropdown-header .user-info {
    padding: 15px;
    background: linear-gradient(135deg, #007BFF, #0056b3);
    border-radius: 8px;
    margin-bottom: 10px;
  }

  .dropdown-header .user-info strong {
    color: white;
    display: block;
    font-size: 16px;
  }

  .dropdown-header .user-info small {
    color: rgba(255,255,255,0.8);
    font-size: 12px;
  }

  .logout-link {
    color: #dc3545 !important;
  }

  /* Guest Auth Section */
  .auth-section {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .auth-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
  }

  .login-btn {
    color: white;
    border-color: rgba(255,255,255,0.3);
  }

  .login-btn:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.5);
    color: white;
    text-decoration: none;
  }

  .register-btn {
    background: linear-gradient(135deg, #ffcc00, #e6b800);
    color: #004080;
    font-weight: 600;
  }

  .register-btn:hover {
    background: linear-gradient(135deg, #e6b800, #ccaa00);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255,204,0,0.3);
    color: #004080;
    text-decoration: none;
  }

  /* Admin Section */
  .admin-section {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .admin-dropdown-container {
    position: relative;
  }

  .admin-toggle {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 20px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
    color: white;
    text-decoration: none;
  }

  .admin-toggle:hover {
    background: linear-gradient(135deg, #c82333, #a02834);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
    color: white;
    text-decoration: none;
  }

  .admin-label {
    font-weight: 500;
    font-size: 13px;
  }

  .admin-dropdown {
    min-width: 250px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    border: none;
  }

  .admin-dropdown .dropdown-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    border-radius: 8px 8px 0 0;
    margin-bottom: 5px;
  }

  .admin-logout {
    color: #dc3545 !important;
    font-weight: 500;
  }

  .admin-access-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 20px;
    background: rgba(220,53,69,0.1);
    border: 1px solid rgba(220,53,69,0.3);
    color: #ffcc00;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.3s ease;
  }

  .admin-access-btn:hover {
    background: rgba(220,53,69,0.2);
    border-color: rgba(220,53,69,0.5);
    color: white;
    text-decoration: none;
  }

  .admin-text {
    font-weight: 500;
  }

  /* Notification Center */
  .notification-center {
    position: relative;
  }

  .notification-bell {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
  }

  .notification-bell:hover {
    background: rgba(255,255,255,0.2);
    color: #ffcc00;
    text-decoration: none;
  }

  .notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 2px solid #004080;
  }

  .notification-badge.pulse {
    animation: pulseNotification 2s infinite;
  }

  @keyframes pulseNotification {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }

  /* Theme Toggle */
  .theme-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .theme-btn:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
    color: #ffcc00;
  }

  /* Simplified User Account for Logged Users */
  .user-account-minimal {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .account-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 20px;
    background: rgba(255,255,255,0.1);
    transition: all 0.3s ease;
    font-size: 14px;
  }

  .account-link:hover {
    background: rgba(255,255,255,0.2);
    color: #ffcc00;
    text-decoration: none;
  }

  .account-link.admin-link {
    background: rgba(255,204,0,0.2);
    color: #ffcc00;
  }

  .account-link.admin-link:hover {
    background: rgba(255,204,0,0.3);
  }

  .logout-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(220,53,69,0.2);
    color: #dc3545;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .logout-link:hover {
    background: rgba(220,53,69,0.3);
    color: #ffffff;
    text-decoration: none;
  }

  /* Get Started Navbar Dropdown Styles */
  .get-started-nav .get-started-link {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white !important;
    border-radius: 25px;
    padding: 8px 16px !important;
    margin: 0 5px;
    font-weight: bold;
    transition: all 0.3s ease;
  }

  .get-started-nav .get-started-link:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
  }

  .get-started-menu {
    min-width: 220px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-radius: 10px;
    overflow: hidden;
    background: white;
  }

  .get-started-menu .dropdown-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 12px 15px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .get-started-menu .dropdown-divider {
    margin: 0;
    border-color: rgba(0,0,0,0.1);
  }

  .get-started-menu li a {
    padding: 12px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    transition: all 0.3s ease;
  }

  .get-started-menu li a:hover {
    background: rgba(40,167,69,0.1);
    color: #28a745;
  }

  .get-started-menu li a.admin-login-link:hover {
    background: rgba(255,193,7,0.1);
    color: #ffc107;
  }

  .get-started-menu li a i {
    width: 16px;
    text-align: center;
  }

  /* Dropdown Enhancements */
  .dropdown-divider {
    height: 1px;
    background: rgba(0,0,0,0.1);
    margin: 8px 0;
  }

  .dropdown-menu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    transition: all 0.2s ease;
  }

  .dropdown-menu li a:hover {
    background: rgba(0,123,255,0.1);
    color: #007BFF;
    text-decoration: none;
  }

  .dropdown-menu li a i {
    width: 16px;
    text-align: center;
    opacity: 0.7;
  }

  /* Mobile Responsive Auth Styles */
  @media (max-width: 768px) {
    .auth-section {
      flex-direction: column;
      gap: 8px;
      width: 100%;
    }

    .auth-btn {
      width: 100%;
      justify-content: center;
      padding: 10px 20px;
    }

    .admin-section {
      flex-direction: column;
      gap: 8px;
      width: 100%;
      margin-top: 10px;
    }

    .admin-toggle, .admin-access-btn {
      width: 100%;
      justify-content: center;
    }

    .user-profile {
      width: 100%;
      justify-content: center;
    }

    .notification-center {
      align-self: center;
      margin-top: 10px;
    }
  }

  /* Enhanced Mobile Navigation Styles */
  .admin-mobile {
    position: relative;
    background: rgba(220,53,69,0.1) !important;
    border-top: 2px solid #dc3545;
  }

  .admin-mobile:hover {
    background: rgba(220,53,69,0.2) !important;
  }

  .register-mobile {
    background: rgba(255,204,0,0.1) !important;
    border-top: 2px solid #ffcc00;
    color: #004080 !important;
  }

  .register-mobile:hover {
    background: rgba(255,204,0,0.2) !important;
  }

  .mobile-notification-badge {
    position: absolute;
    top: 5px;
    right: 10px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .hero-content {
      padding-top: 120px;
    }
    
    .user-account-minimal {
      gap: 8px;
    }
    
    .account-link {
      font-size: 12px;
      padding: 6px 10px;
    }
    
    .account-link span {
      display: none;
    }
    
    .get-started-nav .get-started-link {
      padding: 6px 12px !important;
      font-size: 14px;
    }
  }
</style>



<div class="hero-section">
  <div class="hero-background">
    <div class="slide active" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1581092921461-39b9d08a9b21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1332&q=80');"></div>
    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1588508065123-287b28e013da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
  </div>
  
  <div class="hero-content">
    <?php if (isset($_SESSION['user_id'])): ?>
      <h1>Welcome back, <span class="highlight"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?></span>!</h1>
    <?php else: ?>
      <h1>Smart Solutions for <span class="typing-text"></span></h1>
    <?php endif; ?>
    <p>Your trusted repair partner in Zambia</p>
    
    <div class="hero-search">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" placeholder="What needs fixing today?" class="search-input">
        <button class="search-button">Find Help</button>
      </div>
    </div>
    

    
    <div class="hero-buttons">
      <a href="emergency.php" class="btn-emergency pulse-animation"><i class="fas fa-bolt"></i> Emergency Repair</a>
      <a href="services.php" class="btn-primary"><i class="fas fa-tools"></i> Request Service</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="user/dashboard.php" class="btn-secondary"><i class="fas fa-user"></i> My Dashboard</a>
      <?php elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
        <a href="admin/admin_dashboard_new.php" class="btn-secondary"><i class="fas fa-shield-alt"></i> Admin Dashboard</a>
      <?php else: ?>
        <a href="shop.php" class="btn-secondary"><i class="fas fa-shopping-cart"></i> Browse Shop</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Navigation Arrows -->
  <button class="nav-arrow prev"><i class="fas fa-chevron-left"></i></button>
  <button class="nav-arrow next"><i class="fas fa-chevron-right"></i></button>

  <!-- Dots Indicators -->
  <div class="dots-container">
    <span class="dot active" data-index="0"></span>
    <span class="dot" data-index="1"></span>
    <span class="dot" data-index="2"></span>
  </div>
</div>

<style>
  .hero-section {
    position: relative;
    height: 650px;
    overflow: hidden;
    color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
  
  .hero-background::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.6) 100%);
    z-index: 2;
  }
  
  .hero-background .slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    opacity: 0;
    transition: opacity 1.5s ease-in-out;
    z-index: 1;
    transform: scale(1.05);
  }
  
  .hero-background .slide.active {
    opacity: 1;
    transform: scale(1);
    transition: opacity 1.5s ease-in-out, transform 8s ease-in-out;
  }
  
  .hero-content {
    position: relative;
    z-index: 10;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding-top: 180px;
    animation: fadeInUp 1s ease-out;
  }
  
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
  }
  
  .highlight {
    color: #ffcc00;
  }
  
  .typing-text {
    color: #ffcc00;
    border-right: 3px solid #ffcc00;
    padding-right: 5px;
    animation: blink 0.7s infinite;
  }
  
  @keyframes blink {
    0%, 100% { border-color: transparent; }
    50% { border-color: #ffcc00; }
  }
  
  .hero-content p {
    font-size: 1.5rem;
    margin-bottom: 30px;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
  }
  
  /* Mobile responsiveness for hero section */
  @media (max-width: 768px) {
    .hero-section {
      height: 550px;
    }
    
    .hero-content {
      padding-top: 120px;
      padding-left: 20px;
      padding-right: 20px;
    }
    
    .hero-content h1 {
      font-size: 2.2rem;
    }
    
    .hero-content p {
      font-size: 1.1rem;
    }
    
    .nav-arrow {
      width: 40px;
      height: 40px;
      font-size: 1rem;
    }
  }
  
  @media (max-width: 480px) {
    .hero-section {
      height: 500px;
    }
    
    .hero-content {
      padding-top: 100px;
    }
    
    .hero-content h1 {
      font-size: 1.8rem;
    }
    
    .hero-content p {
      font-size: 1rem;
      margin-bottom: 20px;
    }
  }
  
  .hero-search {
    margin-bottom: 30px;
  }
  
  .search-container {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 50px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    overflow: hidden;
  }
  
  .search-icon {
    position: absolute;
    left: 20px;
    color: #666;
    font-size: 18px;
  }
  
  .search-input {
    flex-grow: 1;
    padding: 15px 15px 15px 50px;
    border: none;
    font-size: 16px;
    outline: none;
  }
  
  .search-button {
    background: #007BFF;
    color: white;
    border: none;
    padding: 15px 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
  }
  
  .search-button:hover {
    background: #0056b3;
  }
  
  .hero-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    animation: fadeIn 1.5s ease-out;
    animation-delay: 0.5s;
    animation-fill-mode: both;
  }
  
  @media (max-width: 768px) {
    .hero-buttons {
      gap: 10px;
    }
    
    .btn-emergency, .btn-primary, .btn-secondary {
      padding: 10px 18px;
      font-size: 14px;
    }
  }
  
  @media (max-width: 480px) {
    .hero-buttons {
      flex-direction: column;
      width: 100%;
      max-width: 250px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .btn-emergency, .btn-primary, .btn-secondary {
      width: 100%;
      margin-bottom: 5px;
      text-align: center;
    }
  }
  
  .pulse-animation {
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 rgba(230, 0, 0, 0.4);
  }
  
  @keyframes pulse {
    0% { 
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(230, 0, 0, 0.4);
    }
    70% { 
      transform: scale(1.05);
      box-shadow: 0 0 0 15px rgba(230, 0, 0, 0);
    }
    100% { 
      transform: scale(1);
      box-shadow: 0 0 0 0 rgba(230, 0, 0, 0);
    }
  }
  
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
</style>
</head>

<body>
<!-- Mobile Navigation Overlay -->
<div class="nav-overlay" onclick="toggleMobileNav()"></div>

<!-- Header with Mobile Navigation -->
<header class="mobile-responsive-header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-tools"></i>
                <span>SmartFix</span>
            </a>
        </div>
        
        <div class="hamburger" onclick="toggleMobileNav()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <nav id="mainNavigation" class="main-navigation">
            <a href="index.php" class="active">
                <i class="fas fa-home"></i> Home
            </a>
            
            <a href="services.php">
                <i class="fas fa-tools"></i> Services
            </a>
            
            <a href="shop.php">
                <i class="fas fa-shopping-cart"></i> Shop
            </a>
            
            <a href="about.php">
                <i class="fas fa-info-circle"></i> About
            </a>
            
            <a href="contact.php">
                <i class="fas fa-phone"></i> Contact
            </a>
            
            <div class="nav-divider"></div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <a href="user/profile.php">
                    <i class="fas fa-user-edit"></i> Profile
                </a>
                
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="auth.php?form=login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                
                <a href="auth.php?form=register">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<style>
/* Mobile-Responsive Header Styles */
.mobile-responsive-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 100;
}

.mobile-responsive-header::before {
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

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
}

.logo {
    z-index: 1001;
}

.logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: white;
    font-size: 28px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.logo a:hover {
    transform: scale(1.05);
}

.logo i {
    margin-right: 10px;
    font-size: 32px;
}

.logo span {
    display: inline-block;
}

.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 8px;
    z-index: 1001;
    background: none;
    border: none;
    transition: all 0.3s ease;
}

.hamburger:hover {
    transform: scale(1.1);
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

.main-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 1;
}

.main-navigation a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 10px 16px;
    border-radius: 25px;
    position: relative;
    display: flex;
    align-items: center;
}

.main-navigation a:hover {
    color: #ffd700;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.main-navigation a.active {
    background: rgba(255,215,0,0.2);
    color: #ffd700;
    font-weight: 600;
}

.main-navigation a.logout-link {
    background: rgba(220,53,69,0.2);
    color: #ffcdd2;
    margin-left: 10px;
}

.main-navigation a.logout-link:hover {
    background: rgba(220,53,69,0.3);
    color: white;
}

.main-navigation a i {
    margin-right: 8px;
    font-size: 16px;
}

.nav-divider {
    width: 1px;
    height: 30px;
    background: rgba(255,255,255,0.2);
    margin: 0 10px;
}

.nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 999;
    opacity: 0;
    transition: all 0.3s ease;
}

.nav-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Styles */
@media (max-width: 968px) {
    .header-container {
        padding: 1rem;
    }
    
    .hamburger {
        display: flex;
    }
    
    .main-navigation {
        position: fixed;
        top: 0;
        left: -100%;
        width: 300px;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        flex-direction: column;
        align-items: flex-start;
        padding: 100px 30px 30px;
        transition: left 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        overflow-y: auto;
        gap: 5px;
    }

    .main-navigation.active {
        left: 0;
    }

    .main-navigation a {
        width: 100%;
        margin: 8px 0;
        padding: 15px 20px;
        border-radius: 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
        border: 1px solid transparent;
    }

    .main-navigation a:hover {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.2);
        transform: translateX(10px);
        color: white;
    }

    .main-navigation a.active {
        background: rgba(255,215,0,0.3);
        border-color: rgba(255,215,0,0.4);
        color: #ffd700;
    }

    .main-navigation a.logout-link {
        margin-top: 20px;
        background: rgba(220,53,69,0.3);
        border-color: rgba(220,53,69,0.4);
    }

    .main-navigation a i {
        width: 25px;
        margin-right: 15px;
        font-size: 18px;
        text-align: center;
    }

    .nav-divider {
        width: 80%;
        height: 1px;
        margin: 15px 0;
        background: rgba(255,255,255,0.2);
    }

    .logo a {
        font-size: 24px;
    }

    .logo i {
        font-size: 28px;
    }

    /* Hide text on very small screens */
    @media (max-width: 480px) {
        .logo span {
            display: none;
        }
        
        .logo i {
            margin-right: 0;
        }
        
        .main-navigation {
            width: 280px;
            padding: 80px 25px 25px;
        }
    }
}

/* Ensure body doesn't scroll when nav is open */
body.nav-open {
    overflow: hidden;
}
</style>

<script>
// Mobile Navigation JavaScript
function toggleMobileNav() {
    const nav = document.getElementById('mainNavigation');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.nav-overlay');
    const body = document.body;
    
    if (nav && hamburger) {
        const isActive = nav.classList.contains('active');
        
        nav.classList.toggle('active');
        hamburger.classList.toggle('active');
        
        if (overlay) {
            overlay.classList.toggle('active');
        }
        
        // Prevent body scroll when menu is open
        if (isActive) {
            body.classList.remove('nav-open');
        } else {
            body.classList.add('nav-open');
        }
    }
}

// Enhanced navigation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close menu when clicking on overlay
    const overlay = document.querySelector('.nav-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMobileNav);
    }
    
    // Close menu when clicking on navigation links
    const navLinks = document.querySelectorAll('.main-navigation a');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });
    
    // Close menu when pressing escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileNav();
        }
    });
    
    // Close menu on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 968) {
            closeMobileNav();
        }
    });
});

function closeMobileNav() {
    const nav = document.getElementById('mainNavigation');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.nav-overlay');
    const body = document.body;
    
    if (nav && nav.classList.contains('active')) {
        nav.classList.remove('active');
        hamburger.classList.remove('active');
        
        if (overlay) {
            overlay.classList.remove('active');
        }
        
        body.classList.remove('nav-open');
    }
}
</script>

<!-- Interactive Service Finder Section -->
<section id="service-finder" class="service-finder">
  <div class="container">
    <div class="section-header">
      <h2>Find Your Repair Solution</h2>
      <p>Tell us what needs fixing and we'll connect you with the right expert</p>
    </div>
    
    <div class="finder-options">
      <div class="finder-option" data-service="phone">
        <div class="option-icon"><i class="fas fa-mobile-alt"></i></div>
        <h3>Phone</h3>
      </div>
      <div class="finder-option" data-service="computer">
        <div class="option-icon"><i class="fas fa-laptop"></i></div>
        <h3>Computer</h3>
      </div>
      <div class="finder-option" data-service="vehicle">
        <div class="option-icon"><i class="fas fa-car"></i></div>
        <h3>Vehicle</h3>
      </div>
      <div class="finder-option" data-service="other">
        <div class="option-icon"><i class="fas fa-tools"></i></div>
        <h3>Other</h3>
      </div>
    </div>
    
    <div class="finder-details" id="finder-details">
      <!-- This will be populated dynamically based on selection -->
    </div>
  </div>
</section>

<style>
  .service-finder {
    padding: 100px 20px;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
  }
  
  .container {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .section-header {
    text-align: center;
    margin-bottom: 60px;
    position: relative;
  }
  
  .section-header::after {
    content: '';
    display: block;
    width: 80px;
    height: 4px;
    background: linear-gradient(to right, #004080, #0066cc);
    margin: 15px auto 0;
    border-radius: 2px;
  }
  
  .section-header h2 {
    font-size: 2.8rem;
    color: #333;
    margin-bottom: 15px;
    position: relative;
    display: inline-block;
  }
  
  .section-header h2::before {
    content: '';
    position: absolute;
    width: 30px;
    height: 30px;
    background-color: rgba(0, 64, 128, 0.1);
    border-radius: 50%;
    left: -15px;
    top: -5px;
    z-index: -1;
  }
  
  .section-header p {
    font-size: 1.3rem;
    color: #666;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
  }
  
  .finder-options {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 50px;
    perspective: 1000px;
  }
  
  .finder-option {
    background-color: white;
    border-radius: 15px;
    padding: 35px 25px;
    width: 200px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
  }
  
  .finder-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,64,128,0.1) 0%, rgba(0,64,128,0) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 1;
  }
  
  .finder-option:hover {
    transform: translateY(-15px) rotateX(5deg);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    border-color: rgba(0,64,128,0.3);
  }
  
  .finder-option:hover::before {
    opacity: 1;
  }
  
  .finder-option.active {
    background: linear-gradient(135deg, #004080, #003366);
    color: white;
    transform: translateY(-15px);
    border-color: #004080;
  }
  
  .option-icon {
    font-size: 3.5rem;
    margin-bottom: 20px;
    color: #004080;
    transition: all 0.4s ease;
    position: relative;
    z-index: 2;
  }
  
  .finder-option:hover .option-icon {
    transform: scale(1.1);
  }
  
  .finder-option.active .option-icon {
    color: white;
    text-shadow: 0 0 15px rgba(255,255,255,0.5);
  }
  
  .finder-option h3 {
    font-size: 1.3rem;
    font-weight: 600;
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
    margin: 0;
    margin: 0;
  }
  
  .finder-details {
    background-color: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    max-width: 800px;
    margin: 0 auto;
    display: none;
  }
  
  @media (max-width: 768px) {
    .service-finder {
      padding: 60px 15px;
    }
    
    .section-header h2 {
      font-size: 2.2rem;
    }
    
    .section-header p {
      font-size: 1.1rem;
    }
    
    .finder-options {
      gap: 15px;
    }
    
    .finder-option {
      width: calc(50% - 15px);
      padding: 25px 15px;
    }
    
    .finder-details {
      padding: 20px;
    }
  }
  
  @media (max-width: 480px) {
    .service-finder {
      padding: 50px 15px;
    }
    
    .section-header h2 {
      font-size: 1.8rem;
    }
    
    .finder-option {
      width: 100%;
      max-width: 250px;
      margin: 0 auto;
    }
    
    .option-icon {
      font-size: 3rem;
      margin-bottom: 15px;
    }
    
    .finder-option h3 {
      font-size: 1.2rem;
    }
  }
  
  .finder-details.active {
    display: block;
    animation: fadeIn 0.5s ease;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .service-detail-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .service-detail-icon {
    font-size: 2.5rem;
    margin-right: 20px;
    color: #004080;
    background-color: #f0f7ff;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .service-detail-title h3 {
    font-size: 1.8rem;
    margin: 0 0 5px 0;
    color: #333;
  }
  
  .service-detail-title p {
    font-size: 1rem;
    color: #666;
    margin: 0;
  }
  
  .common-issues {
    margin: 20px 0;
  }
  
  .common-issues h4 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: #333;
  }
  
  .issues-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
  }
  
  .issue-item {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .issue-item:hover {
    background-color: #e9ecef;
    transform: translateY(-3px);
  }
  
  .service-actions {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .action-btn {
    padding: 12px 25px;
    border-radius: 5px;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
  }
  
  .btn-request {
    background-color: #004080;
    color: white;
  }
  
  .btn-request:hover {
    background-color: #00305f;
  }
  
  .btn-shop {
    background-color: #28a745;
    color: white;
  }
  
  .btn-shop:hover {
    background-color: #218838;
  }
  
  .btn-back {
    background-color: transparent;
    color: #666;
    border: 1px solid #ccc;
  }
  
  .btn-back:hover {
    background-color: #f0f0f0;
  }

  /* Hero Section Styles */
  .hero-section {
    background: linear-gradient(135deg, #004080 0%, #0066cc 100%);
    color: white;
    padding: 80px 20px 60px;
    position: relative;
    overflow: hidden;
  }

  .hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
  }

  .hero-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 1;
  }

  .hero-text {
    text-align: left;
  }

  .hero-text h1 {
    font-size: 3.5rem;
    font-weight: bold;
    margin-bottom: 20px;
    line-height: 1.2;
    background: linear-gradient(45deg, #ffffff, #ffcc00);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .hero-text p {
    font-size: 1.3rem;
    line-height: 1.6;
    margin-bottom: 30px;
    opacity: 0.9;
  }

  .hero-stats {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .hero-stats .stat-box {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.2);
  }

  .hero-stats .stat-box:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.15);
  }

  .hero-stats .stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
    color: #ffcc00;
  }

  .hero-stats .stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 5px;
    color: white;
  }

  .hero-stats .stat-label {
    font-size: 1rem;
    opacity: 0.9;
    color: white;
  }

  @media (max-width: 1024px) {
    .hero-content {
      grid-template-columns: 1fr;
      gap: 40px;
      text-align: center;
    }
    
    .hero-text {
      text-align: center;
    }
    
    .hero-text h1 {
      font-size: 2.8rem;
    }
    
    .hero-stats {
      flex-direction: row;
      justify-content: center;
      gap: 15px;
    }
  }

  @media (max-width: 768px) {
    .hero-section {
      padding: 60px 15px 40px;
    }
    
    .hero-text h1 {
      font-size: 2.2rem;
    }
    
    .hero-text p {
      font-size: 1.1rem;
    }
    
    .hero-stats {
      flex-direction: column;
      max-width: 300px;
      margin: 0 auto;
    }
  }

  @media (max-width: 480px) {
    .hero-text h1 {
      font-size: 1.8rem;
    }
    
    .hero-text p {
      font-size: 1rem;
    }
  }

  /* Trust Indicators Section */
  .trust-indicators {
    background: #f8f9fa;
    padding: 60px 20px;
  }

  .trust-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
  }

  .trust-item {
    text-align: center;
    background: white;
    padding: 30px 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
  }

  .trust-item:hover {
    transform: translateY(-5px);
  }

  .trust-icon {
    font-size: 3rem;
    color: #007BFF;
    margin-bottom: 15px;
  }

  .trust-item h4 {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: #333;
  }

  .trust-item p {
    color: #666;
    font-size: 0.9rem;
    margin: 0;
  }

  /* Testimonials Section */
  .testimonials-section {
    background: white;
    padding: 80px 20px;
  }

  .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto 40px;
  }

  .testimonial-card {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
  }

  .testimonial-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
  }

  .testimonial-card::before {
    content: '"';
    position: absolute;
    top: -10px;
    left: 20px;
    font-size: 4rem;
    color: #007BFF;
    opacity: 0.3;
    font-family: serif;
  }

  .stars {
    color: #ffc107;
    font-size: 1.2rem;
    margin-bottom: 15px;
  }

  .testimonial-card p {
    font-size: 1rem;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
    font-style: italic;
  }

  .customer-info {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .customer-photo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #007BFF;
  }

  .customer-details strong {
    display: block;
    color: #333;
    font-size: 1rem;
    margin-bottom: 2px;
  }

  .customer-details span {
    color: #666;
    font-size: 0.9rem;
  }

  .testimonials-cta {
    text-align: center;
  }

  .testimonials-cta .btn-secondary {
    display: inline-block;
    background: #6c757d;
    color: white;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .testimonials-cta .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
  }

  @media (max-width: 768px) {
    .trust-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .trust-item {
      padding: 20px 15px;
    }

    .trust-icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    .testimonials-grid {
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .testimonial-card {
      padding: 20px;
    }

    .testimonials-section {
      padding: 60px 15px;
    }
  }

  @media (max-width: 480px) {
    .trust-grid {
      grid-template-columns: 1fr;
      max-width: 300px;
      margin: 0 auto;
    }
  }

  /* Call to Action Section */
  .cta-section {
    background: linear-gradient(135deg, #004080 0%, #0066cc 50%, #007BFF 100%);
    color: white;
    padding: 80px 20px;
    position: relative;
    overflow: hidden;
  }

  .cta-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="30" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="70" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
    animation: float 20s infinite linear;
  }

  @keyframes float {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  .cta-content {
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }

  .cta-content h2 {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
  }

  .cta-content p {
    font-size: 1.3rem;
    margin-bottom: 40px;
    opacity: 0.95;
    line-height: 1.6;
  }

  .cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 50px;
    flex-wrap: wrap;
  }

  .cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 15px 30px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
  }

  .cta-btn.primary {
    background: #ffcc00;
    color: #004080;
  }

  .cta-btn.primary:hover {
    background: #e6b800;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
  }

  .cta-btn.secondary {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
  }

  .cta-btn.secondary:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
  }

  .cta-features {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
  }

  .cta-feature {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
    opacity: 0.9;
  }

  .cta-feature i {
    font-size: 1.5rem;
    color: #ffcc00;
  }

  @media (max-width: 768px) {
    .cta-section {
      padding: 60px 15px;
    }

    .cta-content h2 {
      font-size: 2.2rem;
    }

    .cta-content p {
      font-size: 1.1rem;
      margin-bottom: 30px;
    }

    .cta-buttons {
      gap: 15px;
      margin-bottom: 40px;
    }

    .cta-btn {
      padding: 12px 25px;
      font-size: 1rem;
    }

    .cta-features {
      gap: 25px;
    }

    .cta-feature {
      font-size: 0.9rem;
    }

    .cta-feature i {
      font-size: 1.3rem;
    }
  }

  @media (max-width: 480px) {
    .cta-content h2 {
      font-size: 1.8rem;
    }

    .cta-content p {
      font-size: 1rem;
    }

    .cta-buttons {
      flex-direction: column;
      align-items: center;
    }

    .cta-btn {
      width: 100%;
      max-width: 280px;
      justify-content: center;
    }

    .cta-features {
      flex-direction: column;
      gap: 15px;
    }
  }
</style>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-content">
    <div class="hero-text">
      <h1>SmartFix - Your Trusted Repair Partner</h1>
      <p>Professional repair services for phones, computers, vehicles and more. Fast, reliable, and affordable solutions with expert technicians at your service.</p>
      
      <div class="hero-search">
        <div class="search-container">
          <i class="fas fa-search search-icon"></i>
          <input type="text" class="search-input" placeholder="What needs fixing today? (Phone, computer, car...)">
          <button class="search-button">Find Service</button>
        </div>
      </div>
      
      <div class="hero-buttons">
        <a href="services.php" class="btn-primary">
          <i class="fas fa-tools"></i> Request Service
        </a>
        <a href="services/emergency.php" class="btn-emergency pulse-animation">
          <i class="fas fa-bolt"></i> Emergency Repair
        </a>
        <a href="shop.php" class="btn-secondary">
          <i class="fas fa-shopping-cart"></i> Shop Parts
        </a>
      </div>
    </div>
    
    <div class="hero-stats">
      <div class="stat-box">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-number">500+</div>
        <div class="stat-label">Happy Customers</div>
      </div>
      <div class="stat-box">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-number">1000+</div>
        <div class="stat-label">Repairs Completed</div>
      </div>
      <div class="stat-box">
        <div class="stat-icon"><i class="fas fa-user-md"></i></div>
        <div class="stat-number">15+</div>
        <div class="stat-label">Expert Technicians</div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Services Section -->
<section class="featured-services">
  <div class="section-header">
    <h2>Our Professional Services</h2>
    <p>Comprehensive repair and maintenance services for all your technology and vehicle needs</p>
  </div>
  
  <div class="services-grid">
    <div class="service-card">
      <div class="service-icon" style="color: #007BFF;"><i class="fas fa-mobile-alt"></i></div>
      <h3>Phone Repair</h3>
      <p>Expert screen replacement, battery issues, water damage recovery, charging port fixes, and software troubleshooting for all phone models</p>
      <div class="service-features">
        <span class="feature-tag">✓ Same Day Service</span>
        <span class="feature-tag">✓ 90 Day Warranty</span>
      </div>
      <a href="services.php?type=phone" class="service-btn">Book Repair <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="service-card">
      <div class="service-icon" style="color: #28a745;"><i class="fas fa-laptop"></i></div>
      <h3>Computer Repair</h3>
      <p>Hardware upgrades, virus removal, data recovery, performance optimization, and complete system diagnostics for PC and Mac</p>
      <div class="service-features">
        <span class="feature-tag">✓ Free Diagnosis</span>
        <span class="feature-tag">✓ Data Protection</span>
      </div>
      <a href="services.php?type=computer" class="service-btn">Get Quote <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="service-card">
      <div class="service-icon" style="color: #fd7e14;"><i class="fas fa-car"></i></div>
      <h3>Vehicle Repair</h3>
      <p>Professional engine diagnostics, brake service, electrical system repairs, oil changes, and comprehensive vehicle maintenance</p>
      <div class="service-features">
        <span class="feature-tag">✓ Mobile Service</span>
        <span class="feature-tag">✓ Licensed Mechanics</span>
      </div>
      <a href="services.php?type=auto" class="service-btn">Schedule Service <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="service-card">
      <div class="service-icon" style="color: #6f42c1;"><i class="fas fa-tools"></i></div>
      <h3>Quality Parts Store</h3>
      <p>Genuine replacement parts for phones, computers, and vehicles. OEM and aftermarket options with competitive pricing and fast delivery</p>
      <div class="service-features">
        <span class="feature-tag">✓ Genuine Parts</span>
        <span class="feature-tag">✓ Fast Shipping</span>
      </div>
      <a href="shop.php" class="service-btn">Shop Now <i class="fas fa-shopping-cart"></i></a>
    </div>
  </div>
</section>

<!-- Trust Indicators Section -->
<section class="trust-indicators">
  <div class="container">
    <div class="trust-grid">
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-shield-alt"></i></div>
        <h4>Warranty Guarantee</h4>
        <p>90-day warranty on all repairs</p>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-user-check"></i></div>
        <h4>Certified Technicians</h4>
        <p>Licensed & experienced professionals</p>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-clock"></i></div>
        <h4>Quick Turnaround</h4>
        <p>Most repairs completed same day</p>
      </div>
      <div class="trust-item">
        <div class="trust-icon"><i class="fas fa-thumbs-up"></i></div>
        <h4>Customer Satisfaction</h4>
        <p>98% customer satisfaction rate</p>
      </div>
    </div>
  </div>
</section>

<!-- Customer Reviews Section -->
<section class="testimonials-section">
  <div class="container">
    <div class="section-header">
      <h2>What Our Customers Say</h2>
      <p>Real reviews from satisfied customers who trust SmartFix with their repairs</p>
    </div>
    
    <div class="testimonials-grid">
      <div class="testimonial-card">
        <div class="stars">★★★★★</div>
        <p>"Amazing service! My phone screen was replaced in just 30 minutes and looks brand new. The staff was professional and the price was very reasonable."</p>
        <div class="customer-info">
          <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=60&h=60&fit=crop&crop=face&auto=format" alt="Customer" class="customer-photo">
          <div class="customer-details">
            <strong>Michael Johnson</strong>
            <span>iPhone 14 Screen Repair</span>
          </div>
        </div>
      </div>
      
      <div class="testimonial-card">
        <div class="stars">★★★★★</div>
        <p>"Excellent computer repair service. They recovered all my important files and fixed my laptop's performance issues. Highly recommended!"</p>
        <div class="customer-info">
          <img src="https://images.unsplash.com/photo-1494790108755-2616c96c6aec?w=60&h=60&fit=crop&crop=face&auto=format" alt="Customer" class="customer-photo">
          <div class="customer-details">
            <strong>Sarah Williams</strong>
            <span>Laptop Repair & Data Recovery</span>
          </div>
        </div>
      </div>
      
      <div class="testimonial-card">
        <div class="stars">★★★★★</div>
        <p>"Fast and reliable car repair service. They diagnosed my engine problem quickly and fixed it at a fair price. Great mobile service too!"</p>
        <div class="customer-info">
          <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=60&h=60&fit=crop&crop=face&auto=format" alt="Customer" class="customer-photo">
          <div class="customer-details">
            <strong>David Chen</strong>
            <span>Engine Diagnostics</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="testimonials-cta">
      <a href="about.php#reviews" class="btn-secondary">Read More Reviews</a>
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works">
  <div class="container">
    <div class="section-header">
      <h2>How SmartFix Works</h2>
      <p>Your journey to a perfectly working device in 4 simple steps</p>
    </div>
    
    <div class="journey-timeline">
      <div class="timeline-track"></div>
      
      <div class="timeline-step active" data-step="1">
        <div class="step-icon">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="step-content">
          <h3>Request</h3>
          <p>Submit your repair request online or via our mobile app</p>
          <a href="#" class="step-btn" onclick="showStepDetail(1); return false;">Learn More</a>
        </div>
      </div>
      
      <div class="timeline-step" data-step="2">
        <div class="step-icon">
          <i class="fas fa-search"></i>
        </div>
        <div class="step-content">
          <h3>Diagnosis</h3>
          <p>Our experts assess your device and provide a detailed quote</p>
          <a href="#" class="step-btn" onclick="showStepDetail(2); return false;">Learn More</a>
        </div>
      </div>
      
      <div class="timeline-step" data-step="3">
        <div class="step-icon">
          <i class="fas fa-tools"></i>
        </div>
        <div class="step-content">
          <h3>Repair</h3>
          <p>Skilled technicians fix your device with quality parts</p>
          <a href="#" class="step-btn" onclick="showStepDetail(3); return false;">Learn More</a>
        </div>
      </div>
      
      <div class="timeline-step" data-step="4">
        <div class="step-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="step-content">
          <h3>Return</h3>
          <p>Get your device back, working like new with warranty</p>
          <a href="#" class="step-btn" onclick="showStepDetail(4); return false;">Learn More</a>
        </div>
      </div>
    </div>
    
    <div class="step-details-container">
      <div class="step-detail active" data-detail="1">
        <div class="detail-close" onclick="hideStepDetails()"><i class="fas fa-times"></i></div>
        <div class="detail-content">
          <div class="detail-image">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Submit Request">
          </div>
          <div class="detail-text">
            <h3><span class="detail-step">Step 1:</span> Submit Your Request</h3>
            <p>Fill out our simple form with details about your device and the issues you're experiencing. You can also upload photos to help us diagnose the problem faster.</p>
            <ul class="detail-features">
              <li><i class="fas fa-check-circle"></i> Easy online form takes just 2 minutes</li>
              <li><i class="fas fa-check-circle"></i> Upload photos of the damage</li>
              <li><i class="fas fa-check-circle"></i> Choose drop-off or pickup options</li>
              <li><i class="fas fa-check-circle"></i> Receive instant confirmation</li>
            </ul>
            <a href="services.php" class="detail-cta">Start Your Repair Request</a>
          </div>
        </div>
      </div>
      
      <div class="step-detail" data-detail="2">
        <div class="detail-close" onclick="hideStepDetails()"><i class="fas fa-times"></i></div>
        <div class="detail-content">
          <div class="detail-image">
            <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Diagnosis">
          </div>
          <div class="detail-text">
            <h3><span class="detail-step">Step 2:</span> Expert Diagnosis</h3>
            <p>Our certified technicians will carefully examine your device to identify all issues. We'll provide a detailed assessment and transparent pricing with no hidden fees.</p>
            <ul class="detail-features">
              <li><i class="fas fa-check-circle"></i> Thorough inspection by certified experts</li>
              <li><i class="fas fa-check-circle"></i> Detailed report of all issues found</li>
              <li><i class="fas fa-check-circle"></i> Upfront pricing with no surprises</li>
              <li><i class="fas fa-check-circle"></i> Options for different quality parts</li>
            </ul>
            <a href="about.php#technicians" class="detail-cta">Meet Our Technicians</a>
          </div>
        </div>
      </div>
      
      <div class="step-detail" data-detail="3">
        <div class="detail-close" onclick="hideStepDetails()"><i class="fas fa-times"></i></div>
        <div class="detail-content">
          <div class="detail-image">
            <img src="https://images.unsplash.com/photo-1588508065123-287b28e013da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Repair">
          </div>
          <div class="detail-text">
            <h3><span class="detail-step">Step 3:</span> Professional Repair</h3>
            <p>Once you approve the quote, our skilled technicians get to work using industry-standard tools and quality replacement parts to fix your device.</p>
            <ul class="detail-features">
              <li><i class="fas fa-check-circle"></i> Repairs by certified professionals</li>
              <li><i class="fas fa-check-circle"></i> Genuine or high-quality compatible parts</li>
              <li><i class="fas fa-check-circle"></i> Clean room environment for sensitive repairs</li>
              <li><i class="fas fa-check-circle"></i> Regular updates on repair progress</li>
            </ul>
            <a href="services.php#repair-types" class="detail-cta">View Our Repair Services</a>
          </div>
        </div>
      </div>
      
      <div class="step-detail" data-detail="4">
        <div class="detail-close" onclick="hideStepDetails()"><i class="fas fa-times"></i></div>
        <div class="detail-content">
          <div class="detail-image">
            <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Return">
          </div>
          <div class="detail-text">
            <h3><span class="detail-step">Step 4:</span> Device Return</h3>
            <p>Receive your fully repaired device with our quality guarantee. We'll walk you through the repairs done and provide care instructions for the future.</p>
            <ul class="detail-features">
              <li><i class="fas fa-check-circle"></i> Quality testing before return</li>
              <li><i class="fas fa-check-circle"></i> 90-day repair warranty</li>
              <li><i class="fas fa-check-circle"></i> Convenient pickup or delivery options</li>
              <li><i class="fas fa-check-circle"></i> Maintenance tips to prevent future issues</li>
            </ul>
            <a href="warranty.php" class="detail-cta">Learn About Our Warranty</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .how-it-works {
    padding: 80px 0;
    background-color: var(--bg-light);
    position: relative;
    overflow: hidden;
  }
  
  @media (max-width: 768px) {
    .how-it-works {
      padding: 50px 15px;
    }
    
    .journey-timeline {
      flex-direction: column;
      margin-left: 20px;
    }
    
    .timeline-track {
      left: 25px;
      width: 4px;
      height: 100%;
      top: 0;
    }
    
    .timeline-step {
      flex-direction: row;
      width: 100%;
      margin-bottom: 30px;
      align-items: flex-start;
    }
    
    .step-icon {
      margin-right: 20px;
      margin-bottom: 0;
    }
    
    .step-content {
      text-align: left;
    }
  }
  
  @media (max-width: 480px) {
    .how-it-works {
      padding: 40px 15px;
    }
    
    .step-detail {
      width: 90%;
      max-width: none;
      height: auto;
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .detail-content {
      flex-direction: column;
    }
    
    .detail-image {
      width: 100%;
      height: 180px;
    }
    
    .detail-text {
      padding: 15px;
    }
  }
  
  .journey-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    max-width: 1000px;
    margin: 60px auto 40px;
    padding: 0 20px;
  }
  
  .timeline-track {
    position: absolute;
    top: 40px;
    left: 60px;
    right: 60px;
    height: 4px;
    background: linear-gradient(to right, var(--primary-color), var(--accent-color));
    z-index: 1;
  }
  
  .timeline-step {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 22%;
    transition: all 0.3s ease;
  }
  
  .step-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: var(--primary-color);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    position: relative;
    transition: all 0.3s ease;
    border: 3px solid transparent;
  }
  
  .timeline-step.active .step-icon {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
    border-color: var(--accent-color);
  }
  
  .timeline-step:hover .step-icon {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
  }
  
  .step-content {
    text-align: center;
    padding: 0 10px;
  }
  
  .step-content h3 {
    margin: 0 0 10px;
    color: var(--primary-color);
    font-size: 1.3rem;
  }
  
  .step-content p {
    margin: 0 0 15px;
    color: var(--text-light);
    font-size: 0.9rem;
    line-height: 1.4;
  }
  
  .step-btn {
    display: inline-block;
    padding: 8px 15px;
    background: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    border-radius: 20px;
    font-size: 0.8rem;
    text-decoration: none;
    transition: all 0.3s ease;
  }
  
  .step-btn:hover {
    background: var(--primary-color);
    color: white;
  }
  
  .step-details-container {
    max-width: 900px;
    margin: 40px auto 0;
    position: relative;
  }
  
  .step-detail {
    background: white;
    border-radius: 10px;
    box-shadow: var(--shadow);
    overflow: hidden;
    display: none;
    animation: fadeIn 0.5s ease;
  }
  
  .step-detail.active {
    display: block;
  }
  
  .detail-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 30px;
    height: 30px;
    background: rgba(0,0,0,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
  }
  
  .detail-close:hover {
    background: rgba(0,0,0,0.2);
    transform: rotate(90deg);
  }
  
  .detail-content {
    display: flex;
    flex-wrap: wrap;
  }
  
  .detail-image {
    flex: 1;
    min-width: 300px;
  }
  
  .detail-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  .detail-text {
    flex: 1;
    min-width: 300px;
    padding: 30px;
  }
  
  .detail-text h3 {
    margin: 0 0 15px;
    font-size: 1.5rem;
    color: var(--primary-color);
  }
  
  .detail-step {
    color: var(--accent-color);
  }
  
  .detail-text p {
    margin: 0 0 20px;
    line-height: 1.6;
    color: var(--text-color);
  }
  
  .detail-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px;
  }
  
  .detail-features li {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
  }
  
  .detail-features li i {
    color: #28a745;
    margin-right: 10px;
  }
  
  .detail-cta {
    display: inline-block;
    padding: 12px 25px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
  }
  
  .detail-cta:hover {
    background: var(--primary-dark);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  @media (max-width: 768px) {
    .journey-timeline {
      flex-direction: column;
      align-items: flex-start;
      margin-left: 30px;
    }
    
    .timeline-track {
      top: 40px;
      bottom: 40px;
      left: 40px;
      right: auto;
      width: 4px;
      height: auto;
    }
    
    .timeline-step {
      flex-direction: row;
      width: 100%;
      margin-bottom: 30px;
    }
    
    .step-icon {
      margin-right: 20px;
      margin-bottom: 0;
    }
    
    .step-content {
      text-align: left;
    }
    
    .detail-content {
      flex-direction: column;
    }
    
    .detail-image {
      height: 200px;
    }
  }
</style>

<!-- Suggestion Modal -->
<div id="serviceModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
  <div style="background:white; padding:20px; border-radius:10px; width:90%; max-width:400px; text-align:center; box-shadow:0 0 20px rgba(0,0,0,0.2);">
    <h2 id="modalTitle"></h2>
    <p id="modalMessage"></p>
    <button id="modalContinue" style="padding:10px 20px; background:#004080; color:white; border:none; border-radius:6px; cursor:pointer;">Continue</button>
    <button onclick="closeModal()" style="padding:10px 20px; background:#ccc; color:#333; border:none; border-radius:6px; margin-left:10px; cursor:pointer;">Cancel</button>
  </div>
</div>

<script>
  function suggestService(serviceName, serviceType) {
    document.getElementById('modalTitle').innerText = `${serviceName}`;
    let message = "";
    if (serviceType === "phone") {
      message = "You selected Phone Repair. Our technicians will assess and repair your phone in under 24 hours.";
    } else if (serviceType === "computer") {
      message = "Need Computer Repair? We handle both hardware and software issues for laptops and desktops.";
    } else if (serviceType === "auto") {
      message = "Auto Repair selected. We connect you with trusted local mechanics for your car issues.";
    } else {
      message = "Looking for Spare Parts? Browse our marketplace for second-hand and genuine parts.";
    }

    document.getElementById('modalMessage').innerText = message;
    document.getElementById('modalContinue').onclick = function () {
      if (serviceType === "shop") {
        window.location.href = 'shop.php';
      } else {
        window.location.href = `services/request_service.php?type=${serviceType}`;
      }
    };
    document.getElementById('serviceModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('serviceModal').style.display = 'none';
  }
</script>


<!-- Stats Counter Section -->
<section class="stats-counter">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-tools"></i></div>
        <div class="stat-number" data-count="1500">0</div>
        <div class="stat-label">Repairs Completed</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-smile"></i></div>
        <div class="stat-number" data-count="850">0</div>
        <div class="stat-label">Happy Customers</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
        <div class="stat-number" data-count="45">0</div>
        <div class="stat-label">Expert Technicians</div>
      </div>
      <div class="stat-item">
        <div class="stat-icon"><i class="fas fa-city"></i></div>
        <div class="stat-number" data-count="12">0</div>
        <div class="stat-label">Locations</div>
      </div>
    </div>
  </div>
</section>

<style>
  .stats-counter {
    padding: 80px 20px;
    background-color: #004080;
    color: white;
    position: relative;
    overflow: hidden;
  }
  
  .stats-counter::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80') center/cover;
    opacity: 0.15;
    z-index: 1;
  }
  
  .stats-counter .container {
    position: relative;
    z-index: 2;
  }
  
  .stats-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .stat-item {
    text-align: center;
    flex: 1;
    min-width: 200px;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    backdrop-filter: blur(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .stat-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  }
  
  .stat-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #ffcc00;
  }
  
  .stat-number {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 10px;
    background: linear-gradient(45deg, #ffffff, #ffcc00);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  
  .stat-label {
    font-size: 1.2rem;
    color: #ffffff;
  }
  
  @media (max-width: 992px) {
    .stats-counter {
      padding: 60px 20px;
    }
    
    .stats-grid {
      gap: 25px;
    }
    
    .stat-item {
      min-width: 180px;
    }
    
    .stat-icon {
      font-size: 2.5rem;
    }
    
    .stat-number {
      font-size: 2.5rem;
    }
    
    .stat-label {
      font-size: 1.1rem;
    }
  }
  
  @media (max-width: 768px) {
    .stats-counter {
      padding: 50px 15px;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    
    .stat-item {
      width: 100%;
      min-width: 0;
      padding: 15px;
    }
    
    .stat-icon {
      font-size: 2.2rem;
      margin-bottom: 10px;
    }
    
    .stat-number {
      font-size: 2.2rem;
    }
    
    .stat-label {
      font-size: 1rem;
    }
  }
  
  @media (max-width: 480px) {
    .stats-counter {
      padding: 40px 10px;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
    }
    
    .stat-item {
      max-width: 250px;
      margin: 0 auto;
    }
  }
</style>

<!-- Featured Technicians Section -->
<section class="featured-technicians">
  <div class="container">
    <div class="section-header">
      <h2>Meet Our Expert Technicians</h2>
      <p>Skilled professionals ready to solve your technical problems</p>
    </div>
    
    <div class="technicians-slider">
      <?php
      // Get featured technicians from database
      try {
        $stmt = $pdo->query("SELECT * FROM technicians WHERE status = 'available' ORDER BY rating DESC LIMIT 5");
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($technicians) > 0) {
          foreach ($technicians as $tech) {
            // Default image if none is set
            $profile_img = !empty($tech['profile_picture']) ? $tech['profile_picture'] : 'https://via.placeholder.com/150';
            
            // Format rating with stars
            $rating_stars = '';
            $rating = round($tech['rating']);
            for ($i = 1; $i <= 5; $i++) {
              if ($i <= $rating) {
                $rating_stars .= '<i class="fas fa-star"></i>';
              } else {
                $rating_stars .= '<i class="far fa-star"></i>';
              }
            }
            
            echo '<div class="technician-card">
                    <div class="technician-image">
                      <img src="' . $profile_img . '" alt="' . htmlspecialchars($tech['name']) . '">
                    </div>
                    <div class="technician-info">
                      <h3>' . htmlspecialchars($tech['name']) . '</h3>
                      <p class="technician-specialty">' . htmlspecialchars($tech['specialization']) . '</p>
                      <div class="technician-rating">' . $rating_stars . ' <span>(' . $tech['rating'] . ')</span></div>
                      <p class="technician-jobs">' . $tech['total_jobs'] . ' jobs completed</p>
                      <a href="book_technician.php?id=' . $tech['id'] . '" class="book-btn">Book Now</a>
                    </div>
                  </div>';
          }
        } else {
          echo '<div class="no-technicians">
                  <p>No technicians available at the moment. Please check back later.</p>
                </div>';
        }
      } catch (PDOException $e) {
        echo '<div class="error-message">
                <p>Unable to load technicians. Please try again later.</p>
              </div>';
      }
      ?>
    </div>
    
    <div class="view-all-container">
      <a href="technicians.php" class="view-all-btn">View All Technicians <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<style>
  .featured-technicians {
    padding: 80px 20px;
    background-color: #f8f9fa;
  }
  
  .technicians-slider {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    justify-content: center;
    margin: 40px 0;
  }
  
  .technician-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    width: 280px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  
  .technician-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
  }
  
  .technician-image {
    height: 200px;
    overflow: hidden;
  }
  
  .technician-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }
  
  .technician-card:hover .technician-image img {
    transform: scale(1.1);
  }
  
  .technician-info {
    padding: 20px;
    text-align: center;
  }
  
  .technician-info h3 {
    margin: 0 0 5px;
    color: #333;
    font-size: 1.2rem;
  }
  
  .technician-specialty {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 10px;
  }
  
  .technician-rating {
    color: #ffcc00;
    margin-bottom: 10px;
  }
  
  .technician-rating span {
    color: #666;
    font-size: 0.9rem;
  }
  
  .technician-jobs {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 15px;
  }
  
  .book-btn {
    display: inline-block;
    background: #004080;
    color: white;
    padding: 8px 20px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 500;
    transition: background 0.3s ease;
  }
  
  .book-btn:hover {
    background: #00305f;
  }
  
  .view-all-container {
    text-align: center;
    margin-top: 20px;
  }
  
  .view-all-btn {
    display: inline-block;
    background: transparent;
    color: #004080;
    border: 2px solid #004080;
    padding: 10px 25px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .view-all-btn:hover {
    background: #004080;
    color: white;
    transform: translateY(-3px);
  }
  
  .no-technicians, .error-message {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
  }
  
  .error-message {
    border-left: 4px solid #dc3545;
  }
  
  @media (max-width: 768px) {
    .technicians-slider {
      flex-direction: column;
      align-items: center;
    }
    
    .technician-card {
      width: 100%;
      max-width: 320px;
    }
  }
</style>

<!-- Recent Repairs Showcase -->
<section class="recent-repairs">
  <div class="container">
    <div class="section-header">
      <h2>Recent Successful Repairs</h2>
      <p>See the transformation in our latest repair projects</p>
    </div>
    
    <div class="repairs-grid">
      <div class="repair-card">
        <div class="repair-images">
          <div class="before-image">
            <img src="https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=329&q=80" alt="Broken Phone Screen">
            <span class="label">Before</span>
          </div>
          <div class="after-image">
            <img src="https://images.unsplash.com/photo-1598327105666-5b89351aff97?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=327&q=80" alt="Fixed Phone Screen">
            <span class="label">After</span>
          </div>
        </div>
        <div class="repair-info">
          <h3>iPhone Screen Replacement</h3>
          <p>Completed in 2 hours</p>
          <div class="repair-meta">
            <span><i class="fas fa-map-marker-alt"></i> Lusaka</span>
            <span><i class="fas fa-calendar-alt"></i> 2 days ago</span>
          </div>
        </div>
      </div>
      
      <div class="repair-card">
        <div class="repair-images">
          <div class="before-image">
            <img src="https://images.unsplash.com/photo-1517077304055-6e89abbf09b0?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1169&q=80" alt="Damaged Laptop">
            <span class="label">Before</span>
          </div>
          <div class="after-image">
            <img src="https://images.unsplash.com/photo-1531297484001-80022131f5a1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1120&q=80" alt="Fixed Laptop">
            <span class="label">After</span>
          </div>
        </div>
        <div class="repair-info">
          <h3>Laptop Water Damage Repair</h3>
          <p>Completed in 24 hours</p>
          <div class="repair-meta">
            <span><i class="fas fa-map-marker-alt"></i> Kitwe</span>
            <span><i class="fas fa-calendar-alt"></i> 1 week ago</span>
          </div>
        </div>
      </div>
      
      <div class="repair-card">
        <div class="repair-images">
          <div class="before-image">
            <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1332&q=80" alt="Car Engine Problem">
            <span class="label">Before</span>
          </div>
          <div class="after-image">
            <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1283&q=80" alt="Fixed Car Engine">
            <span class="label">After</span>
          </div>
        </div>
        <div class="repair-info">
          <h3>Vehicle Engine Repair</h3>
          <p>Completed in 3 days</p>
          <div class="repair-meta">
            <span><i class="fas fa-map-marker-alt"></i> Ndola</span>
            <span><i class="fas fa-calendar-alt"></i> 2 weeks ago</span>
          </div>
        </div>
      </div>
    </div>
    
    <div class="view-more-container">
      <a href="gallery.php" class="view-more-btn">View More Repairs <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<style>
  .recent-repairs {
    padding: 80px 20px;
    background-color: var(--bg-color);
  }
  
  .repairs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
  }
  
  .repair-card {
    background: var(--bg-light);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
  }
  
  .repair-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
  }
  
  .repair-images {
    position: relative;
    height: 200px;
    display: flex;
  }
  
  .before-image, .after-image {
    position: relative;
    width: 50%;
    overflow: hidden;
  }
  
  .before-image img, .after-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s ease;
  }
  
  .repair-card:hover .before-image img {
    transform: scale(1.1);
  }
  
  .repair-card:hover .after-image img {
    transform: scale(1.1);
  }
  
  .label {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
  }
  
  .repair-info {
    padding: 20px;
  }
  
  .repair-info h3 {
    margin: 0 0 10px;
    color: var(--primary-color);
    font-size: 1.2rem;
  }
  
  .repair-info p {
    margin: 0 0 15px;
    color: var(--text-light);
    font-size: 0.9rem;
  }
  
  .repair-meta {
    display: flex;
    justify-content: space-between;
    color: var(--text-light);
    font-size: 0.8rem;
  }
  
  .repair-meta span {
    display: flex;
    align-items: center;
  }
  
  .repair-meta i {
    margin-right: 5px;
    color: var(--primary-color);
  }
  
  .view-more-container {
    text-align: center;
    margin-top: 40px;
  }
  
  .view-more-btn {
    display: inline-block;
    padding: 12px 25px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
  }
  
  .view-more-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .view-more-btn i {
    margin-left: 8px;
    transition: transform 0.3s ease;
  }
  
  .view-more-btn:hover i {
    transform: translateX(5px);
  }
  
  @media (max-width: 768px) {
    .repairs-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<!-- Testimonials Carousel Section -->
<section class="testimonials-section">
  <div class="container">
    <div class="section-header">
      <h2>What Our Customers Say</h2>
      <p>Hear from people who have experienced our services</p>
    </div>
    
    <div class="testimonial-carousel">
      <div class="testimonial-track" id="testimonialTrack">
        <div class="testimonial-slide">
          <div class="testimonial-content">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p>"SmartFix saved my iPhone after water damage! Their technician was fast, friendly, and professional. My phone works like new again!"</p>
            <div class="testimonial-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>
          <div class="testimonial-author">
            <img src="" alt="Abraham Katongo">
            <div class="author-info">
              <h4>Abraham Katongo</h4>
              <p>Lusaka, Zambia</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-slide">
          <div class="testimonial-content">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p>"I posted my old car battery on SmartFix marketplace and it sold in just 3 days! Great platform for selling used parts and finding what you need."</p>
            <div class="testimonial-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star-half-alt"></i>
            </div>
          </div>
          <div class="testimonial-author">
            <img src="" alt="Danny Muwowo">
            <div class="author-info">
              <h4>Danny Muwowo</h4>
              <p>Kitwe, Zambia</p>
            </div>
          </div>
        </div>
        
        <div class="testimonial-slide">
          <div class="testimonial-content">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p>"The emergency repair service saved my travel plans! My laptop crashed the night before an important presentation, and SmartFix had it working by morning. Amazing response time!"</p>
            <div class="testimonial-rating">
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
              <i class="fas fa-star"></i>
            </div>
          </div>
          <div class="testimonial-author">
            <img src="" alt="Austine Muwowo">
            <div class="author-info">
              <h4>Austine Muwowo</h4>
              <p>Ndola, Zambia</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="carousel-controls">
        <button class="prev-testimonial"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel-dots" id="testimonialDots">
          <span class="dot active" data-index="0"></span>
          <span class="dot" data-index="1"></span>
          <span class="dot" data-index="2"></span>
        </div>
        <button class="next-testimonial"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
</section>

<style>
  .testimonials-section {
    padding: 80px 20px;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
  }
  
  .testimonial-carousel {
    position: relative;
    max-width: 900px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  }
  
  .testimonial-track {
    display: flex;
    transition: transform 0.5s ease;
    height: 100%;
  }
  
  .testimonial-slide {
    min-width: 100%;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  
  @media (max-width: 768px) {
    .testimonials-section {
      padding: 60px 15px;
    }
    
    .testimonial-slide {
      padding: 15px;
    }
  }
  
  @media (max-width: 480px) {
    .testimonials-section {
      padding: 40px 10px;
    }
    
    .testimonial-slide {
      padding: 10px;
    }
  }
  
  .testimonial-content {
    background-color: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    position: relative;
    transition: all 0.3s ease;
  }
  
  @media (max-width: 768px) {
    .testimonial-content {
      padding: 25px;
      border-radius: 12px;
    }
  }
  
  @media (max-width: 480px) {
    .testimonial-content {
      padding: 20px;
      margin-bottom: 15px;
    }
  }
  
  .testimonial-content::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50px;
    width: 20px;
    height: 20px;
    background-color: white;
    transform: rotate(45deg);
    box-shadow: 5px 5px 5px rgba(0,0,0,0.05);
  }
  
  .quote-icon {
    font-size: 2rem;
    color: #004080;
    margin-bottom: 15px;
    opacity: 0.5;
  }
  
  .testimonial-content p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #333;
    font-style: italic;
  }
  
  .testimonial-rating {
    margin-top: 15px;
    color: #ffcc00;
    font-size: 1.2rem;
  }
  
  .testimonial-author {
    display: flex;
    align-items: center;
    padding-left: 30px;
  }
  
  .testimonial-author img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid white;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    margin-right: 15px;
  }
  
  @media (max-width: 768px) {
    .testimonial-author {
      padding-left: 20px;
    }
    
    .testimonial-author img {
      width: 50px;
      height: 50px;
      margin-right: 12px;
    }
  }
  
  @media (max-width: 480px) {
    .testimonial-author {
      padding-left: 15px;
    }
    
    .testimonial-author img {
      width: 45px;
      height: 45px;
      margin-right: 10px;
      border-width: 2px;
    }
  }
  
  .author-info h4 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
  }
  
  .author-info p {
    margin: 5px 0 0 0;
    font-size: 0.9rem;
    color: #666;
  }
  
  .carousel-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 30px;
  }
  
  .prev-testimonial, .next-testimonial {
    background-color: #004080;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 15px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.2);
  }
  
  .prev-testimonial:hover, .next-testimonial:hover {
    background-color: #00305f;
    transform: scale(1.1);
  }
  
  @media (max-width: 768px) {
    .carousel-controls {
      margin-top: 20px;
    }
    
    .prev-testimonial, .next-testimonial {
      width: 36px;
      height: 36px;
      margin: 0 10px;
    }
  }
  
  @media (max-width: 480px) {
    .carousel-controls {
      margin-top: 15px;
    }
    
    .prev-testimonial, .next-testimonial {
      width: 32px;
      height: 32px;
      margin: 0 8px;
    }
  }
  
  .carousel-dots {
    display: flex;
    gap: 8px;
  }
  
  .carousel-dots .dot {
    width: 12px;
    height: 12px;
    background-color: #ccc;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .carousel-dots .dot.active {
    background-color: #004080;
    transform: scale(1.2);
  }
</style>

<!-- Live Technician Availability Section -->
<section class="technician-availability">
  <div class="container">
    <div class="section-header">
      <h2>Available Technicians Near You</h2>
      <p>Connect with our experts ready to help you right now</p>
    </div>
    
    <div class="technicians-grid">
      <div class="technician-card">
        <div class="tech-status online"></div>
        <img src="" alt="ABRAHAM KATONGO">
        <h3>KATONGO ABRAHAM</h3>
        <p class="tech-specialty">Phone Specialist</p>
        <div class="tech-rating">
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <span>(42)</span>
        </div>
        <a href="book_technician.php?id=1" class="btn-book">Book Now</a>
      </div>
      
      <div class="technician-card">
        <div class="tech-status online"></div>
        <img src="" alt="Sarah NAMFUKWE">
        <h3>Sarah NAMFUKWE</h3>
        <p class="tech-specialty">Computer Repair Expert</p>
        <div class="tech-rating">
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star-half-alt"></i>
          <span>(38)</span>
        </div>
        <a href="book_technician.php?id=2" class="btn-book">Book Now</a>
      </div>
      
      <div class="technician-card">
        <div class="tech-status online"></div>
        <img src="SIWALE MUSENGA" alt="MUSENGA">
        <h3>MUSENGA SIWALE</h3>
        <p class="tech-specialty">Auto Mechanic</p>
        <div class="tech-rating">
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star"></i>
          <span>(27)</span>
        </div>
        <a href="book_technician.php?id=3" class="btn-book">Book Now</a>
      </div>
      
      <div class="technician-card">
        <div class="tech-status online"></div>
        <img src="" alt="AUSTINE MUWOWO">
        <h3>AUSTINE MUWOWO</h3>
        <p class="tech-specialty">Electronics Technician</p>
        <div class="tech-rating">
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star filled"></i>
          <i class="fas fa-star"></i>
          <span>(31)</span>
        </div>
        <a href="book_technician.php?id=4" class="btn-book">Book Now</a>
      </div>
    </div>
    
    <div class="view-all-container">
      <a href="technicians.php" class="view-all-btn">View All Technicians <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<style>
  .technician-availability {
    padding: 80px 20px;
    background-color: var(--bg-light);
  }
  
  .technicians-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-top: 40px;
  }
  
  .technician-card {
    background: white;
    border-radius: 10px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: var(--shadow);
    position: relative;
    transition: all 0.3s ease;
  }
  
  .technician-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
  }
  
  .tech-status {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
  }
  
  .tech-status.online {
    background: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    animation: pulse-green 2s infinite;
  }
  
  @keyframes pulse-green {
    0% {
      box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    70% {
      box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
      box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
  }
  
  .technician-card img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
    padding: 3px;
    margin-bottom: 15px;
  }
  
  .technician-card h3 {
    margin: 0 0 5px;
    color: var(--primary-color);
    font-size: 1.2rem;
  }
  
  .tech-specialty {
    color: var(--text-light);
    font-size: 0.9rem;
    margin: 0 0 15px;
  }
  
  .tech-rating {
    margin-bottom: 20px;
    color: #ccc;
  }
  
  .tech-rating .filled {
    color: #ffcc00;
  }
  
  .tech-rating span {
    color: var(--text-light);
    font-size: 0.8rem;
    margin-left: 5px;
  }
  
  .btn-book {
    display: inline-block;
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
  }
  
  .btn-book:hover {
    background: var(--primary-dark);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .view-all-container {
    text-align: center;
    margin-top: 40px;
  }
  
  .view-all-btn {
    display: inline-block;
    padding: 12px 25px;
    background: transparent;
    color: var(--primary-color);
    text-decoration: none;
    border: 2px solid var(--primary-color);
    border-radius: 5px;
    font-weight: bold;
    transition: all 0.3s ease;
  }
  
  .view-all-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .view-all-btn i {
    margin-left: 8px;
    transition: transform 0.3s ease;
  }
  
  .view-all-btn:hover i {
    transform: translateX(5px);
  }
  
  @media (max-width: 768px) {
    .technicians-grid {
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
  }
</style>

<!-- Emergency Repair Call-Out Section -->
<section class="emergency-callout">
  <div class="container">
    <div class="emergency-content">
      <div class="emergency-icon">
        <i class="fas fa-bolt"></i>
      </div>
      <div class="emergency-text">
        <h2>Need Urgent Repairs?</h2>
        <p>Our emergency team is available 24/7 for critical situations</p>
      </div>
      <div class="emergency-action">
        <a href="tel:+260777041357" class="btn-emergency-call">
          <i class="fas fa-phone"></i> Call Now
        </a>
        <span class="or-divider">OR</span>
        <a href="emergency.php" class="btn-emergency-online">
          <i class="fas fa-laptop"></i> Request Online
        </a>
      </div>
    </div>
  </div>
</section>

<style>
  .emergency-callout {
    padding: 60px 20px;
    background: linear-gradient(135deg, #e60000, #ff5722);
    color: white;
    position: relative;
    overflow: hidden;
  }
  
  .emergency-callout::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('https://images.unsplash.com/photo-1584438784894-089d6a62b8fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80') center/cover;
    opacity: 0.15;
    z-index: 1;
  }
  
  .emergency-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    gap: 30px;
  }
  
  .emergency-icon {
    font-size: 4rem;
    background-color: rgba(255, 255, 255, 0.2);
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    animation: pulse 2s infinite;
  }
  
  .emergency-text {
    flex: 1;
    min-width: 300px;
  }
  
  .emergency-text h2 {
    font-size: 2.5rem;
    margin: 0 0 10px 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
  }
  
  .emergency-text p {
    font-size: 1.2rem;
    margin: 0;
    opacity: 0.9;
  }
  
  .emergency-action {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
  }
  
  .btn-emergency-call, .btn-emergency-online {
    display: inline-block;
    padding: 15px 25px;
    border-radius: 50px;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.1rem;
  }
  
  .btn-emergency-call {
    background-color: white;
    color: #e60000;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }
  
  .btn-emergency-call:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
  }
  
  .btn-emergency-online {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid white;
  }
  
  .btn-emergency-online:hover {
    background-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-5px);
  }
  
  .or-divider {
    font-weight: bold;
    font-size: 1.2rem;
    opacity: 0.8;
  }
  
  @media (max-width: 768px) {
    .emergency-content {
      flex-direction: column;
      text-align: center;
    }
    
    .emergency-action {
      flex-direction: column;
      width: 100%;
    }
    
    .btn-emergency-call, .btn-emergency-online {
      width: 100%;
      text-align: center;
    }
  }
</style>

<!-- Call to Action Section -->
<section class="cta-section">
  <div class="cta-content">
    <h2>Ready to Get Your Device Fixed?</h2>
    <p>Join thousands of satisfied customers who trust SmartFix for their repair needs</p>
    <div class="cta-buttons">
      <a href="register.php" class="cta-btn primary">Create an Account</a>
      <a href="services.php" class="cta-btn secondary">Browse Services</a>
    </div>
  </div>
</section>

<div class="contact-info">
  <h2>Need Help?</h2>
  <p>Email: support@smartfix.com</p>
  <p>Call: +260-777 041357</p>
  <p>Location: Kapasa Makasa Campus, Zambia</p>
  
  <div class="social-links">
    <a href="#" class="social-icon">Facebook</a>
    <a href="#" class="social-icon">Twitter</a>
    <a href="#" class="social-icon">Instagram</a>
    <a href="#" class="social-icon">WhatsApp</a>
  </div>
</div>

<footer>
  &copy; 2025 SmartFix. All rights reserved.
</footer>

<script>
  // Show suggestion after user clicks on a request link
  const links = document.querySelectorAll('.card a');

  links.forEach(link => {
    link.addEventListener('click', function (event) {
      event.preventDefault();
      const serviceType = this.href.split('type=')[1] || 'spare_parts';

      let message = "Let’s help you get started!";
      switch (serviceType) {
        case 'phone':
          message = "Looking for phone repair? We'll match you with a nearby technician.";
          break;
        case 'computer':
          message = "Great! We'll connect you to a qualified computer repair expert.";
          break;
        case 'auto':
          message = "Need auto repair? Finding the best mechanic near you.";
          break;
        default:
          window.location.href = this.href; // Allow redirect for shop or others
          return;
      }

      if (confirm(message + "\n\nDo you want to proceed to the service request page?")) {
        window.location.href = this.href;
      }
    });
  });
</script>
<script>
  // Hero section background slider
  const slides = document.querySelectorAll('.hero-background .slide');
  const dots = document.querySelectorAll('.dot');
  const nextBtn = document.querySelector('.next');
  const prevBtn = document.querySelector('.prev');
  const heroSection = document.querySelector('.hero-section');
  
  let currentIndex = 0;
  let slideInterval = setInterval(nextSlide, 5000);

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove('active');
      dots[i].classList.remove('active');
      if (i === index) {
        slide.classList.add('active');
        dots[i].classList.add('active');
      }
    });
  }

  function nextSlide() {
    currentIndex = (currentIndex + 1) % slides.length;
    showSlide(currentIndex);
  }

  function prevSlide() {
    currentIndex = (currentIndex - 1 + slides.length) % slides.length;
    showSlide(currentIndex);
  }

  // Event Listeners
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      resetInterval();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      resetInterval();
    });
  }

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      currentIndex = parseInt(dot.getAttribute('data-index'));
      showSlide(currentIndex);
      resetInterval();
    });
  });

  // Pause on hover
  heroSection.addEventListener('mouseenter', () => clearInterval(slideInterval));
  heroSection.addEventListener('mouseleave', () => slideInterval = setInterval(nextSlide, 5000));

  function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
  }

  // Typing effect for hero section
  const typingElement = document.querySelector('.typing-text');
  if (typingElement) {
    const words = ['Phone Repairs', 'Computer Issues', 'Vehicle Problems', 'Any Device'];
    let wordIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typingSpeed = 100;

    function typeEffect() {
      const currentWord = words[wordIndex];
      
      if (isDeleting) {
        typingElement.textContent = currentWord.substring(0, charIndex - 1);
        charIndex--;
        typingSpeed = 50;
      } else {
        typingElement.textContent = currentWord.substring(0, charIndex + 1);
        charIndex++;
        typingSpeed = 150;
      }
      
      if (!isDeleting && charIndex === currentWord.length) {
        isDeleting = true;
        typingSpeed = 1000; // Pause at end of word
      } else if (isDeleting && charIndex === 0) {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        typingSpeed = 500; // Pause before typing next word
      }
      
      setTimeout(typeEffect, typingSpeed);
    }

    // Start the typing effect
    setTimeout(typeEffect, 1000);
  }

  // Search functionality
  const searchInput = document.querySelector('.search-input');
  const searchButton = document.querySelector('.search-button');
  
  if (searchButton && searchInput) {
    searchButton.addEventListener('click', () => {
      const query = searchInput.value.trim();
      if (query) {
        window.location.href = `services.php?search=${encodeURIComponent(query)}`;
      }
    });
    
    // Also trigger search on Enter key
    searchInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const query = searchInput.value.trim();
        if (query) {
          window.location.href = `services.php?search=${encodeURIComponent(query)}`;
        }
      }
    });
  }

  showSlide(currentIndex); // initial display
  
  // Service Finder Functionality
  const finderOptions = document.querySelectorAll('.finder-option');
  const finderDetails = document.getElementById('finder-details');
  
  finderOptions.forEach(option => {
    option.addEventListener('click', () => {
      // Remove active class from all options
      finderOptions.forEach(opt => opt.classList.remove('active'));
      
      // Add active class to clicked option
      option.classList.add('active');
      
      // Get service type
      const serviceType = option.getAttribute('data-service');
      
      // Show service details
      showServiceDetails(serviceType);
    });
  });
  
  function showServiceDetails(serviceType) {
    let detailsHTML = '';
    let serviceTitle = '';
    let serviceDesc = '';
    let serviceIcon = '';
    let commonIssues = [];
    
    // Set content based on service type
    switch(serviceType) {
      case 'phone':
        serviceTitle = 'Phone Repair';
        serviceDesc = 'Fast and reliable repairs for all phone brands';
        serviceIcon = 'fa-mobile-alt';
        commonIssues = [
          'Cracked Screen', 
          'Battery Replacement', 
          'Water Damage', 
          'Charging Port Issues',
          'Camera Problems',
          'Software Issues'
        ];
        break;
        
      case 'computer':
        serviceTitle = 'Computer Repair';
        serviceDesc = 'Expert solutions for laptops and desktops';
        serviceIcon = 'fa-laptop';
        commonIssues = [
          'Slow Performance', 
          'Virus Removal', 
          'Hardware Upgrades', 
          'Data Recovery',
          'Screen Replacement',
          'Operating System Issues'
        ];
        break;
        
      case 'vehicle':
        serviceTitle = 'Vehicle Repair';
        serviceDesc = 'Professional automotive maintenance and repair';
        serviceIcon = 'fa-car';
        commonIssues = [
          'Engine Problems', 
          'Brake Service', 
          'Electrical Issues', 
          'Oil Change',
          'Transmission Repair',
          'AC/Heating Service'
        ];
        break;
        
      case 'other':
        serviceTitle = 'Other Repairs';
        serviceDesc = 'Solutions for all your other repair needs';
        serviceIcon = 'fa-tools';
        commonIssues = [
          'Home Appliances', 
          'Gaming Consoles', 
          'Audio Equipment', 
          'Smart Devices',
          'Drones',
          'Custom Projects'
        ];
        break;
    }
    
    // Build the HTML
    detailsHTML = `
      <div class="service-detail-header">
        <div class="service-detail-icon">
          <i class="fas ${serviceIcon}"></i>
        </div>
        <div class="service-detail-title">
          <h3>${serviceTitle}</h3>
          <p>${serviceDesc}</p>
        </div>
      </div>
      
      <div class="common-issues">
        <h4>Common Issues We Fix:</h4>
        <div class="issues-grid">
          ${commonIssues.map(issue => `
            <div class="issue-item" onclick="selectIssue('${serviceType}', '${issue}')">
              ${issue}
            </div>
          `).join('')}
        </div>
      </div>
      
      <div class="service-actions">
        <a href="#" class="action-btn btn-back" onclick="hideServiceDetails(event)">Back</a>
        <div>
          <a href="services.php?type=${serviceType}" class="action-btn btn-request">Request Service</a>
          <a href="shop.php?category=${serviceType}" class="action-btn btn-shop">Shop Parts</a>
        </div>
      </div>
    `;
    
    // Update the details container
    finderDetails.innerHTML = detailsHTML;
    finderDetails.classList.add('active');
  }
  
  function hideServiceDetails(event) {
    event.preventDefault();
    finderDetails.classList.remove('active');
    finderOptions.forEach(opt => opt.classList.remove('active'));
  }
  
  function selectIssue(serviceType, issue) {
    window.location.href = `services.php?type=${serviceType}&issue=${encodeURIComponent(issue)}`;
  }
  
  // Animate stat counters when they come into view
  const statNumbers = document.querySelectorAll('.stat-number');
  let counted = false;

  function animateCounters() {
    if (counted) return;
    
    const windowHeight = window.innerHeight;
    const statsSection = document.querySelector('.stats-counter');
    
    if (!statsSection) return;
    
    const statsSectionTop = statsSection.getBoundingClientRect().top;
    
    if (statsSectionTop < windowHeight - 100) {
      statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-count'));
        let count = 0;
        const duration = 2000; // 2 seconds
        const increment = Math.ceil(target / (duration / 20)); // Update every 20ms
        
        const timer = setInterval(() => {
          count += increment;
          if (count >= target) {
            stat.textContent = target.toLocaleString();
            clearInterval(timer);
          } else {
            stat.textContent = count.toLocaleString();
          }
        }, 20);
      });
      
      counted = true;
    }
  }

  window.addEventListener('scroll', animateCounters);
  
  // Also check on page load
  document.addEventListener('DOMContentLoaded', () => {
    animateCounters();
    initNavigation();
  });
  
  // Navigation functionality
  function initNavigation() {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const themeToggle = document.getElementById('themeToggle');
    
    // Mobile menu toggle
    if (menuToggle && mainNav) {
      menuToggle.addEventListener('click', () => {
        mainNav.classList.toggle('active');
        
        // Animate hamburger to X
        const spans = menuToggle.querySelectorAll('span');
        spans.forEach(span => span.classList.toggle('active'));
        
        // Prevent body scrolling when menu is open
        document.body.classList.toggle('menu-open');
      });
    }
    
    // Mobile dropdown toggles
    dropdownToggles.forEach(toggle => {
      toggle.addEventListener('click', (e) => {
        // Only for mobile view
        if (window.innerWidth <= 768) {
          e.preventDefault();
          const parent = toggle.parentElement;
          
          // Close all other dropdowns
          dropdownToggles.forEach(otherToggle => {
            const otherParent = otherToggle.parentElement;
            if (otherParent !== parent) {
              otherParent.classList.remove('active');
            }
          });
          
          // Toggle current dropdown
          parent.classList.toggle('active');
        }
      });
    });
    
    // Theme toggle functionality
    if (themeToggle) {
      // Check for saved theme preference
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
      }
      
      themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        
        if (document.body.classList.contains('dark-mode')) {
          themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
          localStorage.setItem('theme', 'dark');
        } else {
          themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
          localStorage.setItem('theme', 'light');
        }
      });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (mainNav && mainNav.classList.contains('active')) {
        if (!mainNav.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
          mainNav.classList.remove('active');
          document.body.classList.remove('menu-open');
          
          // Reset hamburger icon
          const spans = menuToggle.querySelectorAll('span');
          spans.forEach(span => span.classList.remove('active'));
        }
      }
    });
  }
  
  // How It Works functionality
  function showStepDetail(stepNumber) {
    // Hide all step details
    const stepDetails = document.querySelectorAll('.step-detail');
    stepDetails.forEach(detail => {
      detail.classList.remove('active');
    });
    
    // Show the selected step detail
    const selectedDetail = document.querySelector(`.step-detail[data-detail="${stepNumber}"]`);
    if (selectedDetail) {
      selectedDetail.classList.add('active');
      
      // Scroll to the detail if needed
      selectedDetail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    // Update active step in timeline
    const timelineSteps = document.querySelectorAll('.timeline-step');
    timelineSteps.forEach(step => {
      step.classList.remove('active');
      if (step.getAttribute('data-step') == stepNumber) {
        step.classList.add('active');
      }
    });
  }
  
  function hideStepDetails() {
    const stepDetails = document.querySelectorAll('.step-detail');
    stepDetails.forEach(detail => {
      detail.classList.remove('active');
    });
    
    // Set first step as active by default
    const timelineSteps = document.querySelectorAll('.timeline-step');
    timelineSteps.forEach((step, index) => {
      step.classList.toggle('active', index === 0);
    });
  }
  
  // Initialize timeline steps click events
  document.addEventListener('DOMContentLoaded', function() {
    const timelineSteps = document.querySelectorAll('.timeline-step');
    timelineSteps.forEach(step => {
      step.addEventListener('click', function() {
        const stepNumber = this.getAttribute('data-step');
        showStepDetail(stepNumber);
      });
    });
  });
  
  // Testimonials Carousel
  const testimonialTrack = document.getElementById('testimonialTrack');
  const testimonialDots = document.querySelectorAll('#testimonialDots .dot');
  const prevTestimonial = document.querySelector('.prev-testimonial');
  const nextTestimonial = document.querySelector('.next-testimonial');
  let testimonialIndex = 0;
  
  if (testimonialTrack && testimonialDots.length > 0) {
    // Initialize testimonial carousel
    function showTestimonial(index) {
      testimonialTrack.style.transform = `translateX(-${index * 100}%)`;
      
      testimonialDots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
      });
    }
    
    // Event listeners for testimonial navigation
    if (prevTestimonial) {
      prevTestimonial.addEventListener('click', () => {
        testimonialIndex = (testimonialIndex - 1 + testimonialDots.length) % testimonialDots.length;
        showTestimonial(testimonialIndex);
      });
    }
    
    if (nextTestimonial) {
      nextTestimonial.addEventListener('click', () => {
        testimonialIndex = (testimonialIndex + 1) % testimonialDots.length;
        showTestimonial(testimonialIndex);
      });
    }
    
    testimonialDots.forEach((dot, i) => {
      dot.addEventListener('click', () => {
        testimonialIndex = i;
        showTestimonial(testimonialIndex);
      });
    });
    
    // Auto-advance testimonials
    let testimonialInterval = setInterval(() => {
      testimonialIndex = (testimonialIndex + 1) % testimonialDots.length;
      showTestimonial(testimonialIndex);
    }, 5000);
    
    // Pause on hover
    testimonialTrack.addEventListener('mouseenter', () => {
      clearInterval(testimonialInterval);
    });
    
    testimonialTrack.addEventListener('mouseleave', () => {
      testimonialInterval = setInterval(() => {
        testimonialIndex = (testimonialIndex + 1) % testimonialDots.length;
        showTestimonial(testimonialIndex);
      }, 5000);
    });
  }
</script>

<!-- Call to Action Section -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content">
      <h2>Ready to Fix Your Device?</h2>
      <p>Join thousands of satisfied customers who trust SmartFix with their repair needs. Get started today!</p>
      <div class="cta-buttons">
        <a href="services.php" class="cta-btn primary">
          <i class="fas fa-tools"></i> Request Service Now
        </a>
        <a href="tel:+260777041357" class="cta-btn secondary">
          <i class="fas fa-phone"></i> Call: +260 777 041357
        </a>
      </div>
      <div class="cta-features">
        <div class="cta-feature">
          <i class="fas fa-shipping-fast"></i>
          <span>Free Pickup & Delivery</span>
        </div>
        <div class="cta-feature">
          <i class="fas fa-shield-alt"></i>
          <span>90-Day Warranty</span>
        </div>
        <div class="cta-feature">
          <i class="fas fa-clock"></i>
          <span>Same-Day Service Available</span>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="modern-footer">
  <div class="footer-top">
    <div class="container">
      <div class="footer-newsletter">
        <h3>Stay Updated</h3>
        <p>Subscribe to our newsletter for repair tips and exclusive offers</p>
        <form class="newsletter-form" action="subscribe.php" method="post">
          <div class="form-group">
            <input type="email" name="email" placeholder="Your email address" required>
            <button type="submit">Subscribe</button>
          </div>
          <div class="form-check">
            <input type="checkbox" id="consent" name="consent" required>
            <label for="consent">I agree to receive emails from SmartFix</label>
          </div>
        </form>
      </div>
      
      <div class="footer-grid">
        <div class="footer-column">
          <div class="footer-logo">
            <span class="logo-text">SmartFix</span>
            <span class="logo-highlight">Zed</span>
          </div>
          <p>Your trusted partner for all repair services in Zambia. Quality repairs, genuine parts, and exceptional service since 2020.</p>
          <div class="footer-badges">
            <span class="badge"><i class="fas fa-certificate"></i> Certified</span>
            <span class="badge"><i class="fas fa-shield-alt"></i> Secure</span>
            <span class="badge"><i class="fas fa-check-circle"></i> Trusted</span>
          </div>
        </div>
        
        <div class="footer-column">
          <h3>Services</h3>
          <ul class="footer-links">
            <li><a href="services.php?type=phone"><i class="fas fa-mobile-alt"></i> Phone Repair</a></li>
            <li><a href="services.php?type=computer"><i class="fas fa-laptop"></i> Computer Repair</a></li>
            <li><a href="services.php?type=auto"><i class="fas fa-car"></i> Vehicle Repair</a></li>
            <li><a href="emergency.php"><i class="fas fa-bolt"></i> Emergency Service</a></li>
            <li><a href="shop.php"><i class="fas fa-shopping-cart"></i> Spare Parts Shop</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Company</h3>
          <ul class="footer-links">
            <li><a href="about.php"><i class="fas fa-info-circle"></i> About Us</a></li>
            <li><a href="team.php"><i class="fas fa-users"></i> Our Team</a></li>
            <li><a href="careers.php"><i class="fas fa-briefcase"></i> Careers</a></li>
            <li><a href="testimonials.php"><i class="fas fa-quote-left"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-newspaper"></i> Blog</a></li>
          </ul>
        </div>
        
        <div class="footer-column">
          <h3>Contact Us</h3>
          <ul class="contact-info">
            <li>
              <i class="fas fa-map-marker-alt"></i>
              <div>
                <span>Headquarters:</span>
                Kapasa Makasa University, Great North Road, Chinsali, Zambia
              </div>
            </li>
            <li>
              <i class="fas fa-phone-alt"></i>
              <div>
                <span>Phone:</span>
                <a href="tel:+260777041357">+260 777 041357</a>
              </div>
            </li>
            <li>
              <i class="fas fa-envelope"></i>
              <div>
                <span>Email:</span>
                <a href="mailto:info@smartfixzed.com">info@smartfixzed.com</a>
              </div>
            </li>
            <li>
              <i class="fas fa-clock"></i>
              <div>
                <span>Hours:</span>
                Mon-Sat: 8:00 AM - 6:00 PM
              </div>
            </li>
          </ul>
        </div>
      </div>
      
      <div class="footer-social">
        <h3>Connect With Us</h3>
        <div class="social-links">
          <a href="#" class="social-link facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-link instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link linkedin"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="social-link youtube"><i class="fab fa-youtube"></i></a>
          <a href="#" class="social-link whatsapp"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?php echo date('Y'); ?> SmartFix. All Rights Reserved.</p>
      <div class="footer-legal">
        <a href="privacy.php">Privacy Policy</a>
        <a href="terms.php">Terms of Service</a>
        <a href="warranty.php">Warranty Information</a>
        <a href="sitemap.php">Sitemap</a>
      </div>
      <div class="payment-methods">
        <i class="fab fa-cc-visa"></i>
        <i class="fab fa-cc-mastercard"></i>
        <i class="fab fa-cc-paypal"></i>
        <i class="fab fa-cc-apple-pay"></i>
      </div>
    </div>
  </div>
</footer>

<style>
  .modern-footer {
    background-color: var(--primary-dark);
    color: white;
    position: relative;
    margin-top: 60px;
  }
  
  .footer-top {
    padding: 60px 20px 40px;
  }
  
  .footer-newsletter {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    text-align: center;
    max-width: 800px;
    margin: -80px auto 40px;
  }
  
  .footer-newsletter h3 {
    font-size: 1.5rem;
    margin: 0 0 10px;
  }
  
  .footer-newsletter p {
    margin: 0 0 20px;
    opacity: 0.9;
  }
  
  .newsletter-form .form-group {
    display: flex;
    max-width: 500px;
    margin: 0 auto 15px;
  }
  
  .newsletter-form input[type="email"] {
    flex-grow: 1;
    padding: 12px 15px;
    border: none;
    border-radius: 5px 0 0 5px;
    font-size: 1rem;
    outline: none;
  }
  
  .newsletter-form button {
    background: var(--accent-color);
    color: var(--primary-dark);
    border: none;
    padding: 0 25px;
    border-radius: 0 5px 5px 0;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .newsletter-form button:hover {
    background: var(--accent-hover);
  }
  
  .form-check {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    opacity: 0.9;
  }
  
  .form-check input {
    margin-right: 10px;
  }
  
  .footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    margin-bottom: 40px;
  }
  
  .footer-column h3 {
    font-size: 1.2rem;
    margin: 0 0 20px;
    position: relative;
    padding-bottom: 10px;
  }
  
  .footer-column h3:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--accent-color);
  }
  
  .footer-logo {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 15px;
    display: inline-block;
  }
  
  .logo-text {
    color: white;
  }
  
  .logo-highlight {
    color: var(--accent-color);
  }
  
  .footer-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
  }
  
  .badge {
    background: rgba(255,255,255,0.1);
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
  }
  
  .badge i {
    margin-right: 5px;
    color: var(--accent-color);
  }
  
  .footer-links, .contact-info {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .footer-links li {
    margin-bottom: 12px;
  }
  
  .footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
  }
  
  .footer-links a i {
    margin-right: 10px;
    color: var(--accent-color);
    width: 20px;
    text-align: center;
  }
  
  .footer-links a:hover {
    color: white;
    transform: translateX(5px);
  }
  
  .contact-info li {
    display: flex;
    margin-bottom: 15px;
  }
  
  .contact-info li i {
    color: var(--accent-color);
    margin-right: 15px;
    margin-top: 5px;
    width: 20px;
    text-align: center;
  }
  
  .contact-info li div {
    flex: 1;
  }
  
  .contact-info li span {
    display: block;
    font-weight: bold;
    margin-bottom: 3px;
    color: var(--accent-color);
  }
  
  .contact-info a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
  }
  
  .contact-info a:hover {
    color: white;
  }
  
  .footer-social {
    text-align: center;
    margin-top: 30px;
  }
  
  .footer-social h3 {
    margin-bottom: 20px;
  }
  
  .social-links {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
  }
  
  .social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.1);
  }
  
  .social-link:hover {
    transform: translateY(-5px);
  }
  
  .social-link.facebook:hover { background: #3b5998; }
  .social-link.twitter:hover { background: #1da1f2; }
  .social-link.instagram:hover { background: #e1306c; }
  .social-link.linkedin:hover { background: #0077b5; }
  .social-link.youtube:hover { background: #ff0000; }
  .social-link.whatsapp:hover { background: #25d366; }
  
  .footer-bottom {
    background: rgba(0,0,0,0.2);
    padding: 20px;
    text-align: center;
  }
  
  .footer-bottom .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
  }
  
  .footer-bottom p {
    margin: 0;
    font-size: 0.9rem;
  }
  
  .footer-legal {
    display: flex;
    gap: 20px;
  }
  
  .footer-legal a {
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
  }
  
  .footer-legal a:hover {
    color: white;
  }
  
  .payment-methods {
    display: flex;
    gap: 10px;
    font-size: 1.5rem;
    color: rgba(255,255,255,0.7);
  }
  
  @media (max-width: 768px) {
    .footer-newsletter {
      margin-top: -60px;
      padding: 20px;
    }
    
    .newsletter-form .form-group {
      flex-direction: column;
    }
    
    .newsletter-form input[type="email"] {
      border-radius: 5px;
      margin-bottom: 10px;
    }
    
    .newsletter-form button {
      border-radius: 5px;
      padding: 10px;
    }
    
    .footer-bottom .container {
      flex-direction: column;
      text-align: center;
    }
    
    .footer-legal, .payment-methods {
      justify-content: center;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    const toggleSpans = menuToggle.querySelectorAll('span');
    
    menuToggle.addEventListener('click', function() {
      mainNav.classList.toggle('active');
      document.body.classList.toggle('menu-open');
      toggleSpans.forEach(span => span.classList.toggle('active'));
    });
    
    // Handle dropdown menus in mobile view
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        // Only handle clicks in mobile view
        if (window.innerWidth <= 768) {
          e.preventDefault();
          const parent = this.parentElement;
          parent.classList.toggle('active');
          
          const dropdownMenu = parent.querySelector('.dropdown-menu');
          if (parent.classList.contains('active')) {
            dropdownMenu.style.maxHeight = dropdownMenu.scrollHeight + 'px';
          } else {
            dropdownMenu.style.maxHeight = '0';
          }
        }
      });
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 768 && mainNav.classList.contains('active')) {
        if (!mainNav.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
          mainNav.classList.remove('active');
          document.body.classList.remove('menu-open');
          toggleSpans.forEach(span => span.classList.remove('active'));
        }
      }
    });
    
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle.querySelector('i');
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark-mode');
      themeIcon.classList.remove('fa-moon');
      themeIcon.classList.add('fa-sun');
    }
    
    themeToggle.addEventListener('click', function() {
      document.body.classList.toggle('dark-mode');
      
      if (document.body.classList.contains('dark-mode')) {
        themeIcon.classList.remove('fa-moon');
        themeIcon.classList.add('fa-sun');
        localStorage.setItem('theme', 'dark');
      } else {
        themeIcon.classList.remove('fa-sun');
        themeIcon.classList.add('fa-moon');
        localStorage.setItem('theme', 'light');
      }
    });
    
    // Hero slider functionality
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.nav-arrow.prev');
    const nextBtn = document.querySelector('.nav-arrow.next');
    let currentSlide = 0;
    
    function showSlide(index) {
      // Hide all slides
      slides.forEach(slide => slide.classList.remove('active'));
      dots.forEach(dot => dot.classList.remove('active'));
      
      // Show the selected slide
      slides[index].classList.add('active');
      dots[index].classList.add('active');
      currentSlide = index;
    }
    
    // Initialize slider
    if (slides.length > 0) {
      showSlide(0);
      
      // Auto slide change
      let slideInterval = setInterval(() => {
        let nextSlide = (currentSlide + 1) % slides.length;
        showSlide(nextSlide);
      }, 5000);
      
      // Navigation arrows
      if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
          clearInterval(slideInterval);
          let prevSlide = (currentSlide - 1 + slides.length) % slides.length;
          showSlide(prevSlide);
        });
        
        nextBtn.addEventListener('click', () => {
          clearInterval(slideInterval);
          let nextSlide = (currentSlide + 1) % slides.length;
          showSlide(nextSlide);
        });
      }
      
      // Dot indicators
      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          clearInterval(slideInterval);
          showSlide(index);
        });
      });
    }
    
    // Stats counter animation
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    
    function animateStats() {
      statNumbers.forEach(stat => {
        const target = parseInt(stat.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const step = target / duration * 10; // Update every 10ms
        let current = 0;
        
        const updateCounter = () => {
          current += step;
          if (current < target) {
            stat.textContent = Math.floor(current);
            requestAnimationFrame(updateCounter);
          } else {
            stat.textContent = target;
          }
        };
        
        updateCounter();
      });
    }
    
    // Use Intersection Observer to trigger counter animation when visible
    if ('IntersectionObserver' in window && statNumbers.length > 0) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateStats();
            observer.disconnect(); // Only animate once
          }
        });
      }, { threshold: 0.1 });
      
      observer.observe(document.querySelector('.stats-counter'));
    }
  });
  
  // PWA Installation and Service Worker Registration
  let deferredPrompt;
  
  // Check if app is already installed
  if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('App is running in standalone mode');
    document.body.classList.add('pwa-installed');
  }
  
  // Listen for install prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install banner
    showInstallBanner();
  });
  
  function showInstallBanner() {
    const banner = document.createElement('div');
    banner.className = 'install-banner';
    banner.innerHTML = `
      <div class="install-content">
        <i class="fas fa-mobile-alt"></i>
        <div>
          <strong>Install SmartFix App</strong>
          <p>Get the full app experience with offline access</p>
        </div>
        <button onclick="installApp()" class="install-btn">
          Install
        </button>
        <button onclick="this.parentElement.parentElement.remove()" class="close-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
    
    document.body.insertBefore(banner, document.body.firstChild);
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
      if (document.querySelector('.install-banner')) {
        banner.remove();
      }
    }, 10000);
  }
  
  // Handle app installation
  window.installApp = async function() {
    if (!deferredPrompt) return;
    
    const result = await deferredPrompt.prompt();
    console.log('Install prompt result:', result.outcome);
    
    document.querySelector('.install-banner')?.remove();
    deferredPrompt = null;
  };
  
  // Register Service Worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/smartfix/sw.js')
      .then(registration => {
        console.log('ServiceWorker registered:', registration.scope);
      })
      .catch(error => {
        console.log('ServiceWorker registration failed:', error);
      });
  }
  
  // Network status management
  function updateNetworkStatus() {
    if (!navigator.onLine) {
      const offlineBanner = document.createElement('div');
      offlineBanner.className = 'offline-banner';
      offlineBanner.innerHTML = `
        <div class="offline-content">
          <i class="fas fa-wifi-slash"></i>
          <span>You're offline. Some features may be limited.</span>
        </div>
      `;
      
      document.body.insertBefore(offlineBanner, document.body.firstChild);
      
      // Remove when back online
      function removeOfflineBanner() {
        offlineBanner?.remove();
        window.removeEventListener('online', removeOfflineBanner);
      }
      window.addEventListener('online', removeOfflineBanner);
    }
  }
  
  window.addEventListener('offline', updateNetworkStatus);
  updateNetworkStatus();
</script>

<!-- PWA Manager -->
<script src="js/pwa.js"></script>

<!-- Mobile Navigation -->
<div class="mobile-nav">
  <div class="mobile-nav-items">
    <a href="index.php" class="mobile-nav-item active">
      <i class="fas fa-home"></i>
      <span>Home</span>
    </a>
    <a href="services.php" class="mobile-nav-item">
      <i class="fas fa-tools"></i>
      <span>Services</span>
    </a>
    <a href="shop.php" class="mobile-nav-item">
      <i class="fas fa-shopping-bag"></i>
      <span>Shop</span>
    </a>
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="user/dashboard.php" class="mobile-nav-item">
        <i class="fas fa-user-circle"></i>
        <span>Account</span>
      </a>
    <?php else: ?>
      <a href="auth.php?form=login" class="mobile-nav-item">
        <i class="fas fa-sign-in-alt"></i>
        <span>Login</span>
      </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
      <a href="admin/admin_dashboard_new.php" class="mobile-nav-item admin-mobile">
        <i class="fas fa-shield-alt"></i>
        <span>Admin</span>
        <?php
        // Check for unread notifications
        try {
          $notification_query = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
          $notification_stmt = $pdo->prepare($notification_query);
          $notification_stmt->execute();
          $notification_data = $notification_stmt->fetch();
          $notification_count = $notification_data['count'];
          
          if ($notification_count > 0): 
          ?>
          <span class="mobile-notification-badge"><?php echo $notification_count; ?></span>
          <?php endif;
        } catch (PDOException $e) {
          // Table might not exist yet
        }
        ?>
      </a>
    <?php elseif (!isset($_SESSION['user_id'])): ?>
      <a href="auth.php?form=register" class="mobile-nav-item register-mobile">
        <i class="fas fa-user-plus"></i>
        <span>Register</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Network Status Indicator -->
<div id="network-status" class="network-status"></div>

</body>
</html>
