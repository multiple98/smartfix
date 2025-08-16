<?php
session_start();
include('../includes/db.php');

$reference_number = '';
$service_details = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_submit'])) {
    $reference_number = trim($_POST['reference_number']);
    
    if (empty($reference_number)) {
        $error_message = "Please enter a reference number.";
    } else {
        try {
            $query = "SELECT * FROM service_requests WHERE reference_number = :reference_number";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['reference_number' => $reference_number]);
            
            if ($stmt->rowCount() > 0) {
                $service_details = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "No service request found with this reference number. Please check and try again.";
            }
        } catch (PDOException $e) {
            $error_message = "Sorry, there was an error retrieving your service details. Please try again later.";
        }
    }
}

// Check if we're searching by ID from GET parameter (for backward compatibility)
if (isset($_GET['id']) && !empty($_GET['id']) && empty($service_details)) {
    $search_id = trim($_GET['id']);
    
    try {
        $query = "SELECT * FROM service_requests WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $search_id]);
        
        if ($stmt->rowCount() > 0) {
            $service_details = $stmt->fetch(PDO::FETCH_ASSOC);
            $reference_number = $service_details['reference_number'];
        }
    } catch (PDOException $e) {
        // Ignore errors for backward compatibility
    }
}

// Function to get status label and class
function getStatusInfo($status) {
    switch ($status) {
        case 'pending':
            return [
                'label' => 'Pending',
                'class' => 'status-pending',
                'icon' => 'fas fa-clock',
                'description' => 'Your request has been received and is awaiting assignment to a technician.'
            ];
        case 'assigned':
            return [
                'label' => 'Assigned',
                'class' => 'status-assigned',
                'icon' => 'fas fa-user-check',
                'description' => 'A technician has been assigned to your request and will contact you shortly.'
            ];
        case 'in_progress':
            return [
                'label' => 'In Progress',
                'class' => 'status-progress',
                'icon' => 'fas fa-tools',
                'description' => 'Work on your request is currently in progress.'
            ];
        case 'completed':
            return [
                'label' => 'Completed',
                'class' => 'status-completed',
                'icon' => 'fas fa-check-circle',
                'description' => 'Your service request has been completed successfully.'
            ];
        case 'cancelled':
            return [
                'label' => 'Cancelled',
                'class' => 'status-cancelled',
                'icon' => 'fas fa-times-circle',
                'description' => 'This service request has been cancelled.'
            ];
        default:
            return [
                'label' => 'Unknown',
                'class' => 'status-unknown',
                'icon' => 'fas fa-question-circle',
                'description' => 'Status information is not available.'
            ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Track Service | SmartFix</title>
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
      background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1556656793-08538906a9f8?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      text-align: center;
      padding: 60px 20px;
      position: relative;
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

    .track-form {
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 30px;
      text-align: center;
      margin-bottom: 40px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-control {
      width: 100%;
      max-width: 400px;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      transition: border-color 0.3s;
      box-sizing: border-box;
      margin: 0 auto;
    }

    .form-control:focus {
      border-color: #007BFF;
      outline: none;
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
      display: inline-block;
    }

    .btn-submit:hover {
      background: #0056b3;
      transform: translateY(-3px);
    }

    .alert {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .service-details {
      background: white;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 30px;
      margin-bottom: 40px;
    }

    .service-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 20px;
    }

    .service-title h3 {
      font-size: 24px;
      color: #004080;
      margin: 0 0 10px;
    }

    .service-reference {
      color: #666;
      font-size: 16px;
    }

    .service-status {
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: bold;
      display: inline-flex;
      align-items: center;
    }

    .service-status i {
      margin-right: 8px;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-assigned {
      background: #cce5ff;
      color: #004085;
    }

    .status-progress {
      background: #d1ecf1;
      color: #0c5460;
    }

    .status-completed {
      background: #d4edda;
      color: #155724;
    }

    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }

    .status-unknown {
      background: #e2e3e5;
      color: #383d41;
    }

    .service-info {
      margin-top: 30px;
    }

    .info-row {
      display: flex;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }

    .info-label {
      width: 150px;
      font-weight: bold;
      color: #004080;
    }

    .info-value {
      flex: 1;
      min-width: 200px;
    }

    .status-description {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-top: 20px;
      border-left: 4px solid #007BFF;
    }

    .status-timeline {
      margin-top: 40px;
    }

    .timeline {
      position: relative;
      max-width: 1000px;
      margin: 0 auto;
    }

    .timeline::after {
      content: '';
      position: absolute;
      width: 4px;
      background-color: #e9ecef;
      top: 0;
      bottom: 0;
      left: 20px;
      margin-left: -2px;
    }

    .timeline-item {
      padding: 10px 40px;
      position: relative;
      background-color: inherit;
      width: 100%;
      box-sizing: border-box;
      margin-bottom: 20px;
    }

    .timeline-item::after {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
      left: 18px;
      background-color: white;
      border: 4px solid #007BFF;
      top: 15px;
      border-radius: 50%;
      z-index: 1;
    }

    .timeline-content {
      padding: 20px;
      background-color: white;
      position: relative;
      border-radius: 6px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .timeline-date {
      color: #666;
      font-size: 14px;
    }

    .timeline-title {
      margin: 5px 0;
      color: #004080;
    }

    .timeline-text {
      margin: 0;
    }

    .no-results {
      text-align: center;
      padding: 40px 20px;
    }

    .no-results i {
      font-size: 48px;
      color: #ccc;
      margin-bottom: 20px;
    }

    .no-results h3 {
      color: #004080;
      margin-bottom: 10px;
    }

    .message-form {
      margin-top: 30px;
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
    }

    .message-form h3 {
      color: #004080;
      margin-top: 0;
      margin-bottom: 15px;
    }

    .message-form textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 16px;
      min-height: 100px;
      resize: vertical;
      margin-bottom: 15px;
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
      
      .service-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .info-row {
        flex-direction: column;
      }
      
      .info-label {
        margin-bottom: 5px;
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

<div class="page-header">
  <h1><i class="fas fa-search-location"></i> Track Your Service</h1>
  <p>Check the status of your repair or service request</p>
</div>

<div class="container">
  <div class="section-title">
    <h2>Service Tracking</h2>
    <p>Enter your reference number to track the status of your service request</p>
  </div>
  
  <div class="track-form">
    <form action="track_service.php" method="POST">
      <div class="form-group">
        <input type="text" id="reference_number" name="reference_number" class="form-control" placeholder="Enter your reference number (e.g., SF000001)" value="<?php echo htmlspecialchars($reference_number); ?>" required>
      </div>
      
      <button type="submit" name="track_submit" class="btn-submit"><i class="fas fa-search"></i> Track Service</button>
    </form>
  </div>
  
  <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
      <?php echo $error_message; ?>
    </div>
  <?php endif; ?>
  
  <?php if ($service_details): ?>
    <?php 
      $status_info = getStatusInfo($service_details['status']);
      $service_date = new DateTime($service_details['created_at']);
      $completed_date = !empty($service_details['completed_at']) ? new DateTime($service_details['completed_at']) : null;
    ?>
    
    <div class="service-details">
      <div class="service-header">
        <div class="service-title">
          <h3><?php echo ucfirst($service_details['service_type']); ?> Service</h3>
          <div class="service-reference">Reference: <?php echo htmlspecialchars($service_details['reference_number']); ?></div>
        </div>
        
        <div class="service-status <?php echo $status_info['class']; ?>">
          <i class="<?php echo $status_info['icon']; ?>"></i> <?php echo $status_info['label']; ?>
        </div>
      </div>
      
      <div class="status-description">
        <p><i class="fas fa-info-circle"></i> <?php echo $status_info['description']; ?></p>
      </div>
      
      <div class="service-info">
        <div class="info-row">
          <div class="info-label">Service Type:</div>
          <div class="info-value"><?php echo ucfirst($service_details['service_type']); ?></div>
        </div>
        
        <div class="info-row">
          <div class="info-label">Service Option:</div>
          <div class="info-value"><?php echo htmlspecialchars($service_details['service_option']); ?></div>
        </div>
        
        <div class="info-row">
          <div class="info-label">Description:</div>
          <div class="info-value"><?php echo htmlspecialchars($service_details['description']); ?></div>
        </div>
        
        <div class="info-row">
          <div class="info-label">Submitted On:</div>
          <div class="info-value"><?php echo $service_date->format('F j, Y, g:i a'); ?></div>
        </div>
        
        <?php if ($completed_date): ?>
        <div class="info-row">
          <div class="info-label">Completed On:</div>
          <div class="info-value"><?php echo $completed_date->format('F j, Y, g:i a'); ?></div>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="status-timeline">
        <h3>Service Timeline</h3>
        
        <div class="timeline">
          <?php
          // Fetch service updates from the database
          try {
            $updates_query = "SELECT * FROM service_updates WHERE service_request_id = :request_id ORDER BY created_at ASC";
            $updates_stmt = $pdo->prepare($updates_query);
            $updates_stmt->execute(['request_id' => $service_details['id']]);
            $service_updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Always show the initial submission as the first timeline item
            ?>
            <div class="timeline-item">
              <div class="timeline-content">
                <div class="timeline-date"><?php echo $service_date->format('F j, Y, g:i a'); ?></div>
                <h4 class="timeline-title">Service Request Received</h4>
                <p class="timeline-text">Your service request has been successfully submitted.</p>
              </div>
            </div>
            <?php
            
            // Display all service updates from the database
            if (count($service_updates) > 0) {
              foreach ($service_updates as $update) {
                $update_date = new DateTime($update['created_at']);
                $update_status_info = getStatusInfo($update['status']);
                ?>
                <div class="timeline-item">
                  <div class="timeline-content">
                    <div class="timeline-date"><?php echo $update_date->format('F j, Y, g:i a'); ?></div>
                    <h4 class="timeline-title">Status Updated: <?php echo $update_status_info['label']; ?></h4>
                    <p class="timeline-text"><?php echo nl2br(htmlspecialchars($update['update_text'])); ?></p>
                  </div>
                </div>
                <?php
              }
            } else {
              // If no updates exist in the database, show the default timeline based on status
              if ($service_details['status'] != 'pending') {
                ?>
                <div class="timeline-item">
                  <div class="timeline-content">
                    <div class="timeline-date"><?php echo (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+1 hour')->format('F j, Y, g:i a'); ?></div>
                    <h4 class="timeline-title">Service Request Assigned</h4>
                    <p class="timeline-text">Your request has been assigned to a technician.</p>
                  </div>
                </div>
                <?php
              }
              
              if (in_array($service_details['status'], ['in_progress', 'completed'])) {
                ?>
                <div class="timeline-item">
                  <div class="timeline-content">
                    <div class="timeline-date"><?php echo (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+3 hours')->format('F j, Y, g:i a'); ?></div>
                    <h4 class="timeline-title">Service In Progress</h4>
                    <p class="timeline-text">Work on your service request has begun.</p>
                  </div>
                </div>
                <?php
              }
              
              if ($service_details['status'] == 'completed') {
                ?>
                <div class="timeline-item">
                  <div class="timeline-content">
                    <div class="timeline-date"><?php echo $completed_date ? $completed_date->format('F j, Y, g:i a') : (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+1 day')->format('F j, Y, g:i a'); ?></div>
                    <h4 class="timeline-title">Service Completed</h4>
                    <p class="timeline-text">Your service request has been successfully completed.</p>
                  </div>
                </div>
                <?php
              }
            }
          } catch (PDOException $e) {
            // If there's an error (like table doesn't exist), fall back to the default timeline
            ?>
            <div class="timeline-item">
              <div class="timeline-content">
                <div class="timeline-date"><?php echo $service_date->format('F j, Y, g:i a'); ?></div>
                <h4 class="timeline-title">Service Request Received</h4>
                <p class="timeline-text">Your service request has been successfully submitted.</p>
              </div>
            </div>
            
            <?php if ($service_details['status'] != 'pending'): ?>
            <div class="timeline-item">
              <div class="timeline-content">
                <div class="timeline-date"><?php echo (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+1 hour')->format('F j, Y, g:i a'); ?></div>
                <h4 class="timeline-title">Service Request Assigned</h4>
                <p class="timeline-text">Your request has been assigned to a technician.</p>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($service_details['status'], ['in_progress', 'completed'])): ?>
            <div class="timeline-item">
              <div class="timeline-content">
                <div class="timeline-date"><?php echo (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+3 hours')->format('F j, Y, g:i a'); ?></div>
                <h4 class="timeline-title">Service In Progress</h4>
                <p class="timeline-text">Work on your service request has begun.</p>
              </div>
            </div>
            <?php endif; ?>
            
            <?php if ($service_details['status'] == 'completed'): ?>
            <div class="timeline-item">
              <div class="timeline-content">
                <div class="timeline-date"><?php echo $completed_date ? $completed_date->format('F j, Y, g:i a') : (new DateTime($service_date->format('Y-m-d H:i:s')))->modify('+1 day')->format('F j, Y, g:i a'); ?></div>
                <h4 class="timeline-title">Service Completed</h4>
                <p class="timeline-text">Your service request has been successfully completed.</p>
              </div>
            </div>
            <?php endif; ?>
            <?php
          }
          ?>
        </div>
      </div>
      
      <?php if (isset($_SESSION['user_id']) && $service_details['status'] != 'completed' && $service_details['status'] != 'cancelled'): ?>
      <div class="message-form">
        <h3>Send a Message</h3>
        <form action="send_message.php" method="POST">
          <input type="hidden" name="request_id" value="<?php echo $service_details['id']; ?>">
          <textarea name="message" placeholder="Write a message to our team about this service request..." required></textarea>
          <button type="submit" class="btn-submit">Send Message</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)): ?>
    <div class="no-results">
      <i class="fas fa-search"></i>
      <h3>No Service Found</h3>
      <p>We couldn't find any service with the reference number you provided. Please check the number and try again.</p>
    </div>
  <?php endif; ?>
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

</body>
</html>