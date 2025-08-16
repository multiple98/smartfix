<?php
session_start();
include('includes/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us | SmartFix</title>
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
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1581092921461-39b9d08a9b21?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
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

    .about-content {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 40px;
      margin-bottom: 60px;
    }

    .about-image {
      flex: 1;
      min-width: 300px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .about-image img {
      width: 100%;
      height: auto;
      display: block;
      transition: transform 0.5s ease;
    }

    .about-image:hover img {
      transform: scale(1.05);
    }

    .about-text {
      flex: 1;
      min-width: 300px;
    }

    .about-text h3 {
      font-size: 28px;
      color: #004080;
      margin-bottom: 20px;
    }

    .about-text p {
      margin-bottom: 20px;
      font-size: 16px;
    }

    .mission-vision {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      margin-bottom: 60px;
    }

    .mission, .vision {
      flex: 1;
      min-width: 300px;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .mission:hover, .vision:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .mission h3, .vision h3 {
      font-size: 24px;
      color: #004080;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }

    .mission h3 i, .vision h3 i {
      margin-right: 10px;
      color: #007BFF;
    }

    .team-section {
      text-align: center;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      margin-top: 40px;
    }

    .team-member {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
    }

    .team-member:hover {
      transform: translateY(-10px);
    }

    .member-image {
      height: 250px;
      overflow: hidden;
    }

    .member-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .team-member:hover .member-image img {
      transform: scale(1.1);
    }

    .member-info {
      padding: 20px;
    }

    .member-info h3 {
      font-size: 20px;
      color: #004080;
      margin-bottom: 5px;
    }

    .member-info p {
      color: #666;
      font-size: 14px;
      margin-bottom: 15px;
    }

    .social-links {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .social-links a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: #f0f0f0;
      color: #333;
      border-radius: 50%;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .social-links a:hover {
      background: #007BFF;
      color: white;
      transform: translateY(-3px);
    }

    .values-section {
      background: #f0f8ff;
      padding: 60px 20px;
      margin: 60px 0;
    }

    .values-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 40px auto 0;
    }

    .value-card {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      text-align: center;
      transition: transform 0.3s ease;
    }

    .value-card:hover {
      transform: translateY(-10px);
    }

    .value-icon {
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

    .value-card h3 {
      font-size: 20px;
      color: #004080;
      margin-bottom: 15px;
    }

    .timeline-section {
      padding: 60px 20px;
    }

    .timeline {
      position: relative;
      max-width: 1000px;
      margin: 40px auto 0;
    }

    .timeline::after {
      content: '';
      position: absolute;
      width: 4px;
      background-color: #007BFF;
      top: 0;
      bottom: 0;
      left: 50%;
      margin-left: -2px;
    }

    .timeline-item {
      padding: 10px 40px;
      position: relative;
      width: 50%;
      box-sizing: border-box;
    }

    .timeline-item:nth-child(odd) {
      left: 0;
    }

    .timeline-item:nth-child(even) {
      left: 50%;
    }

    .timeline-item::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      background-color: white;
      border: 4px solid #007BFF;
      border-radius: 50%;
      top: 15px;
      z-index: 1;
    }

    .timeline-item:nth-child(odd)::after {
      right: -12px;
    }

    .timeline-item:nth-child(even)::after {
      left: -12px;
    }

    .timeline-content {
      padding: 20px;
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .timeline-content h3 {
      font-size: 20px;
      color: #004080;
      margin-bottom: 10px;
    }

    .timeline-content p {
      margin: 0;
      color: #666;
    }

    .timeline-year {
      position: absolute;
      top: 15px;
      font-weight: bold;
      color: #007BFF;
    }

    .timeline-item:nth-child(odd) .timeline-year {
      right: 20px;
    }

    .timeline-item:nth-child(even) .timeline-year {
      left: 20px;
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
      .timeline::after {
        left: 31px;
      }
      
      .timeline-item {
        width: 100%;
        padding-left: 70px;
        padding-right: 25px;
      }
      
      .timeline-item:nth-child(even) {
        left: 0;
      }
      
      .timeline-item:nth-child(odd)::after,
      .timeline-item:nth-child(even)::after {
        left: 18px;
      }
      
      .timeline-item:nth-child(odd) .timeline-year,
      .timeline-item:nth-child(even) .timeline-year {
        left: 0;
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
      <a href="auth.php?form=login"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="auth.php?form=register"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
  </nav>
</header>

<div class="page-header">
  <h1>About SmartFix</h1>
  <p>Your trusted partner for all repair services in Zambia</p>
</div>

<div class="container">
  <div class="section-title">
    <h2>Our Story</h2>
    <p>Learn about our journey, mission, and the team behind SmartFix</p>
  </div>
  
  <div class="about-content">
    <div class="about-image">
      <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="SmartFix Workshop">
    </div>
    <div class="about-text">
      <h3>Who We Are</h3>
      <p>SmartFix was founded in 2020 with a simple mission: to provide reliable, affordable, and professional repair services to the people of Zambia. What started as a small phone repair shop in Mpika has now grown into a comprehensive repair service center catering to phones, computers, vehicles, and more.</p>
      <p>Our team of skilled technicians brings years of experience and a passion for problem-solving to every repair job. We believe in transparency, quality workmanship, and customer satisfaction above all else.</p>
      <p>Today, SmartFix is proud to be Zambia's leading repair service provider, with a reputation built on trust, expertise, and exceptional customer service.</p>
    </div>
  </div>
  
  <div class="mission-vision">
    <div class="mission">
      <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
      <p>To provide accessible, high-quality repair services that extend the life of your devices and vehicles, reducing waste and saving you money.</p>
      <p>We aim to be the most trusted repair service in Zambia, known for our technical expertise, honest pricing, and exceptional customer care.</p>
    </div>
    <div class="vision">
      <h3><i class="fas fa-eye"></i> Our Vision</h3>
      <p>To create a world where repair is the first option, not the last resort. We envision a future where devices and vehicles are repaired rather than replaced, reducing electronic waste and promoting sustainability.</p>
      <p>We strive to be at the forefront of repair technology and techniques, continuously improving our services to meet the evolving needs of our customers.</p>
    </div>
  </div>
</div>

<div class="values-section">
  <div class="section-title">
    <h2>Our Core Values</h2>
    <p>The principles that guide everything we do at SmartFix</p>
  </div>
  
  <div class="values-grid">
    <div class="value-card">
      <div class="value-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h3>Quality</h3>
      <p>We never compromise on the quality of our repairs. We use genuine parts and follow industry best practices to ensure your devices and vehicles perform like new.</p>
    </div>
    
    <div class="value-card">
      <div class="value-icon">
        <i class="fas fa-handshake"></i>
      </div>
      <h3>Integrity</h3>
      <p>We believe in honest pricing, transparent communication, and doing what's right for our customers, even when it's not the most profitable option.</p>
    </div>
    
    <div class="value-card">
      <div class="value-icon">
        <i class="fas fa-users"></i>
      </div>
      <h3>Customer Focus</h3>
      <p>Our customers are at the heart of everything we do. We listen to your needs, respect your time, and strive to exceed your expectations with every interaction.</p>
    </div>
    
    <div class="value-card">
      <div class="value-icon">
        <i class="fas fa-lightbulb"></i>
      </div>
      <h3>Innovation</h3>
      <p>We continuously invest in the latest tools, techniques, and training to stay at the cutting edge of repair technology and provide the best possible service.</p>
    </div>
  </div>
</div>

<div class="container">
  <div class="section-title">
    <h2>Meet Our Team</h2>
    <p>The skilled professionals behind SmartFix's success</p>
  </div>
  
  <div class="team-grid">
    <div class="team-member">
      <div class="member-image">
        <img src="img/CEO" alt="KATONGO ABRAHAM">
      </div>
      <div class="member-info">
        <h3>KATONGO ABRAHAM</h3>
        <p>Founder & CEO</p>
        <p>With over 10 years of experience in electronics repair, John founded SmartFix with a vision to provide quality repair services to all Zambians.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-linkedin"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fas fa-envelope"></i></a>
        </div>
      </div>
    </div>
    
    <div class="team-member">
      <div class="member-image">
        <img src="" alt="danny muwowo">
      </div>
      <div class="member-info">
        <h3>AUSTINE MUWOWO</h3>
        <p>Operations Manager</p>
        <p>Austine ensures that all operations run smoothly, from customer service to repair workflows, maintaining our high standards of quality.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-linkedin"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fas fa-envelope"></i></a>
        </div>
      </div>
    </div>
    
    <div class="team-member">
      <div class="member-image">
        <img src="" alt="hatyoka">
      </div>
      <div class="member-info">
        <h3>Luckson Hatyoka</h3>
        <p>tecnician</p>
        <p>With certifications in electronics and automotive repair, Chishala leads our technical team, ensuring all repairs meet our exacting standards.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-linkedin"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fas fa-envelope"></i></a>
        </div>
      </div>
    </div>
    
    <div class="team-member">
      <div class="member-image">
        <img src="" alt="soko">
      </div>
      <div class="member-info">
        <h3>Danny Muwowo</h3>
        <p>Customer Relations</p>
        <p>Danny ensures that every customer has a positive experience with SmartFix, handling inquiries, feedback, and ensuring customer satisfaction.</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-linkedin"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fas fa-envelope"></i></a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container timeline-section">
  <div class="section-title">
    <h2>Our Journey</h2>
    <p>Key milestones in SmartFix's growth and development</p>
  </div>
  
  <div class="timeline">
    <div class="timeline-item">
      <div class="timeline-year">2021</div>
      <div class="timeline-content">
        <h3>The Beginning</h3>
        <p>SmartFix was founded as a small phone repair shop in nakonde with just two technicians.</p>
      </div>
    </div>
    
    <div class="timeline-item">
      <div class="timeline-year">2022</div>
      <div class="timeline-content">
        <h3>Expansion of Services</h3>
        <p>Added computer repair services and expanded our team to five technicians.</p>
      </div>
    </div>
    
    <div class="timeline-item">
      <div class="timeline-year">2023</div>
      <div class="timeline-content">
        <h3>New Location</h3>
        <p>Moved to a larger facility and added automotive electronics repair to our service offerings.</p>
      </div>
    </div>
    
    <div class="timeline-item">
      <div class="timeline-year">2025</div>
      <div class="timeline-content">
        <h3>Online Presence</h3>
        <p>Launched our website and online booking system to better serve our growing customer base.</p>
      </div>
    </div>
    
    <div class="timeline-item">
      <div class="timeline-year">2025</div>
      <div class="timeline-content">
        <h3>Spare Parts Shop</h3>
        <p>Opened our spare parts shop to provide quality components for DIY repairs and other repair shops.</p>
      </div>
    </div>
    
    <div class="timeline-item">
      <div class="timeline-year">2025</div>
      <div class="timeline-content">
        <h3>Today</h3>
        <p>Now serving thousands of customers across Zambia with a team of 15+ skilled technicians.</p>
      </div>
    </div>
  </div>
</div>

<div class="cta-section">
  <div class="cta-content">
    <h2>Ready to Experience SmartFix?</h2>
    <p>Whether you need a repair, want to shop for parts, or just have questions, we're here to help.</p>
    <div class="cta-buttons">
      <a href="services.php" class="cta-btn primary">Our Services</a>
      <a href="contact.php" class="cta-btn secondary">Contact Us</a>
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
      <p><i class="fas fa-map-marker-alt"></i> Kapasa Makasa along great north Road, Lusaka, Zambia</p>
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
    <p>&copy; <?php echo date('Y'); ?> SmartFixzed. All Rights Reserved.</p>
  </div>
</footer>

</body>
</html>
