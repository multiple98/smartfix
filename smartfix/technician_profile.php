<?php
session_start();
include('includes/db.php');

// Get technician ID from URL
$technician_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize variables
$technician = null;
$reviews = [];
$error = '';

// Get technician details
if ($technician_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ? AND status = 'available'");
        $stmt->execute([$technician_id]);
        $technician = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$technician) {
            $error = "Technician not found or not available.";
        } else {
            // Get reviews for this technician
            try {
                $stmt = $pdo->prepare("
                    SELECT r.*, u.name as user_name, b.service_type 
                    FROM reviews r
                    JOIN users u ON r.user_id = u.id
                    JOIN bookings b ON r.booking_id = b.id
                    WHERE r.technician_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 10
                ");
                $stmt->execute([$technician_id]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Reviews table might not exist yet
            }
        }
    } catch (PDOException $e) {
        $error = "Error retrieving technician details: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $technician ? htmlspecialchars($technician['name']) : 'Technician Profile'; ?> - SmartFix</title>
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .profile-sidebar {
            flex: 1;
            min-width: 300px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .profile-header {
            background-color: var(--primary-light);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            margin: 0 auto 15px;
            overflow: hidden;
            background-color: #f0f0f0;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-name {
            font-size: 24px;
            margin: 0 0 5px;
        }
        
        .profile-specialization {
            font-size: 16px;
            opacity: 0.9;
            margin: 0 0 15px;
        }
        
        .profile-rating {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stars {
            color: var(--accent-color);
            margin-right: 5px;
        }
        
        .profile-status {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .profile-status.busy {
            background-color: #ffc107;
            color: #212529;
        }
        
        .profile-status.offline {
            background-color: #6c757d;
        }
        
        .profile-details {
            padding: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .detail-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-light);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .detail-content {
            flex-grow: 1;
        }
        
        .detail-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-light);
        }
        
        .detail-value {
            color: var(--text-color);
        }
        
        .regions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        
        .region-badge {
            background-color: var(--primary-light);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .profile-actions {
            padding: 20px;
            background-color: var(--bg-dark);
            text-align: center;
        }
        
        .btn-book {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
            box-sizing: border-box;
        }
        
        .btn-book:hover {
            background-color: var(--primary-dark);
        }
        
        .profile-content {
            flex: 2;
            min-width: 300px;
        }
        
        .content-section {
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .section-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .section-body {
            padding: 20px;
        }
        
        .bio-text {
            line-height: 1.6;
            color: var(--text-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }
        
        .stat-item {
            background-color: var(--bg-dark);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .reviews-list {
            margin-top: 10px;
        }
        
        .review-item {
            border-bottom: 1px solid var(--bg-dark);
            padding: 15px 0;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer-info {
            font-weight: 500;
        }
        
        .review-date {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .review-rating {
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .review-service {
            font-style: italic;
            color: var(--text-light);
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .review-comment {
            line-height: 1.5;
        }
        
        .no-reviews {
            text-align: center;
            padding: 20px;
            color: var(--text-light);
        }
        
        .error-container {
            text-align: center;
            padding: 40px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .error-container h2 {
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .error-container p {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            
            .profile-sidebar, .profile-content {
                width: 100%;
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
        <?php if (!empty($error)): ?>
            <div class="error-container">
                <h2>Technician Not Found</h2>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="technicians.php" class="btn-book">Browse All Technicians</a>
            </div>
        <?php elseif ($technician): ?>
            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <div class="profile-image">
                            <?php 
                            $profile_img = !empty($technician['profile_picture']) ? $technician['profile_picture'] : 'https://via.placeholder.com/150';
                            ?>
                            <img src="<?php echo $profile_img; ?>" alt="<?php echo htmlspecialchars($technician['name']); ?>">
                        </div>
                        <h1 class="profile-name"><?php echo htmlspecialchars($technician['name']); ?></h1>
                        <p class="profile-specialization"><?php echo htmlspecialchars($technician['specialization']); ?></p>
                        <div class="profile-rating">
                            <div class="stars">
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
                            </div>
                            <span><?php echo number_format($technician['rating'], 1); ?>/5</span>
                        </div>
                        <div class="profile-status <?php echo strtolower($technician['status']); ?>">
                            <?php echo ucfirst($technician['status']); ?>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value"><?php echo htmlspecialchars($technician['phone']); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($technician['email'])): ?>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Email</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($technician['email']); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($technician['address'])): ?>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Address</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($technician['address']); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-map"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Service Areas</div>
                                <div class="detail-value">
                                    <div class="regions-list">
                                        <?php
                                        $regions = explode(',', $technician['regions']);
                                        foreach ($regions as $region) {
                                            $region = trim($region);
                                            if (!empty($region)) {
                                                echo '<span class="region-badge">' . htmlspecialchars($region) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Experience</div>
                                <div class="detail-value"><?php echo $technician['total_jobs']; ?> jobs completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="book_technician.php?id=<?php echo $technician['id']; ?>" class="btn-book">Book This Technician</a>
                    </div>
                </div>
                
                <div class="profile-content">
                    <div class="content-section">
                        <div class="section-header">
                            <h2>About</h2>
                        </div>
                        <div class="section-body">
                            <?php if (!empty($technician['bio'])): ?>
                                <div class="bio-text">
                                    <?php echo nl2br(htmlspecialchars($technician['bio'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="bio-text">
                                    <p>No detailed information available for this technician yet.</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $technician['total_jobs']; ?></div>
                                    <div class="stat-label">Jobs Completed</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($technician['rating'], 1); ?></div>
                                    <div class="stat-label">Average Rating</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo count($reviews); ?></div>
                                    <div class="stat-label">Reviews</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-section">
                        <div class="section-header">
                            <h2>Customer Reviews</h2>
                        </div>
                        <div class="section-body">
                            <?php if (empty($reviews)): ?>
                                <div class="no-reviews">
                                    <p>No reviews yet for this technician.</p>
                                </div>
                            <?php else: ?>
                                <div class="reviews-list">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item">
                                            <div class="review-header">
                                                <div class="reviewer-info"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                                <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                            </div>
                                            <div class="review-rating">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <div class="review-service">
                                                Service: <?php echo htmlspecialchars($review['service_type']); ?>
                                            </div>
                                            <?php if (!empty($review['comment'])): ?>
                                                <div class="review-comment">
                                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> SmartFix. All rights reserved.</p>
    </footer>
</body>
</html>