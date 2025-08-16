<?php
session_start();
include('includes/db.php');

// Get all technicians
$technicians = [];
try {
    $stmt = $pdo->query("SELECT * FROM technicians WHERE status = 'available' ORDER BY rating DESC");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
    $error = $e->getMessage();
}

// Get filter parameters
$specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';
$region = isset($_GET['region']) ? $_GET['region'] : '';

// Apply filters if set
if (!empty($specialization) || !empty($region)) {
    try {
        $query = "SELECT * FROM technicians WHERE status = 'available'";
        $params = [];
        
        if (!empty($specialization)) {
            $query .= " AND specialization = :specialization";
            $params['specialization'] = $specialization;
        }
        
        if (!empty($region)) {
            $query .= " AND regions LIKE :region";
            $params['region'] = '%' . $region . '%';
        }
        
        $query .= " ORDER BY rating DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error
        $error = $e->getMessage();
    }
}

// Get all specializations for filter
$specializations = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT specialization FROM technicians");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $specializations[] = $row['specialization'];
    }
} catch (PDOException $e) {
    // Table might not exist yet
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Technicians - SmartFix</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .filter-section {
            background-color: var(--bg-light);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-reset {
            background-color: #6c757d;
        }
        
        .btn-reset:hover {
            background-color: #5a6268;
        }
        
        .technicians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .technician-card {
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .technician-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        .technician-header {
            background-color: var(--primary-light);
            color: white;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        
        .technician-header h3 {
            margin: 0;
            font-size: 20px;
        }
        
        .technician-header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .technician-rating {
            margin-top: 10px;
            color: var(--accent-color);
        }
        
        .technician-body {
            padding: 20px;
        }
        
        .technician-info {
            margin-bottom: 15px;
        }
        
        .technician-info p {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .technician-info i {
            width: 25px;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .technician-regions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 15px;
        }
        
        .region-badge {
            background-color: var(--primary-light);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .technician-footer {
            padding: 15px 20px;
            background-color: var(--bg-dark);
            text-align: center;
        }
        
        .btn-contact {
            display: inline-block;
            padding: 8px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .btn-contact:hover {
            background-color: var(--primary-dark);
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            background-color: var(--bg-light);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .no-results h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .no-results p {
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
            .filter-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-group {
                width: 100%;
            }
            
            .technicians-grid {
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
            <a href="technicians.php" class="active">Technicians</a>
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
        <h1>Our Expert Technicians</h1>
        <p>Find skilled professionals for all your repair needs across Zambia</p>
    </div>
    
    <div class="container">
        <div class="filter-section">
            <h2>Find a Technician</h2>
            <form class="filter-form" method="GET">
                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <select name="specialization" id="specialization" class="form-control">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>" <?php if ($specialization === $spec) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($spec); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="region">Region</label>
                    <select name="region" id="region" class="form-control">
                        <option value="">All Regions</option>
                        <?php foreach ($zambian_regions as $reg): ?>
                            <option value="<?php echo htmlspecialchars($reg); ?>" <?php if ($region === $reg) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($reg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 0 0 auto;">
                    <button type="submit" class="btn">Filter Results</button>
                    <a href="technicians.php" class="btn btn-reset">Reset</a>
                </div>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="no-results">
                <h3>Oops! Something went wrong</h3>
                <p>We're having trouble loading our technicians data. Please try again later or contact support.</p>
                <p>Error details: <?php echo htmlspecialchars($error); ?></p>
                <a href="reset_technicians_table.php" class="btn">Reset Technicians Table</a>
            </div>
        <?php elseif (empty($technicians)): ?>
            <div class="no-results">
                <h3>No Technicians Found</h3>
                <p>We couldn't find any technicians matching your criteria. Please try different filters or check back later.</p>
                <a href="technicians.php" class="btn">View All Technicians</a>
            </div>
        <?php else: ?>
            <div class="technicians-grid">
                <?php foreach ($technicians as $tech): ?>
                    <div class="technician-card">
                        <div class="technician-header">
                            <h3><?php echo htmlspecialchars($tech['name']); ?></h3>
                            <p><?php echo htmlspecialchars($tech['specialization']); ?></p>
                            <div class="technician-rating">
                                <?php
                                $rating = round($tech['rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span>(<?php echo $tech['rating']; ?>/5)</span>
                            </div>
                        </div>
                        
                        <div class="technician-body">
                            <div class="technician-info">
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($tech['phone']); ?></p>
                                <?php if (!empty($tech['email'])): ?>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($tech['email']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($tech['address'])): ?>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($tech['address']); ?></p>
                                <?php endif; ?>
                                <p><i class="fas fa-briefcase"></i> <?php echo $tech['total_jobs']; ?> jobs completed</p>
                            </div>
                            
                            <?php if (!empty($tech['regions'])): ?>
                                <div class="technician-regions">
                                    <?php
                                    $regions = explode(',', $tech['regions']);
                                    foreach ($regions as $reg) {
                                        echo '<span class="region-badge">' . htmlspecialchars(trim($reg)) . '</span>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="technician-footer">
                            <a href="book_technician.php?id=<?php echo $tech['id']; ?>" class="btn-contact">Book This Technician</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; text-align: center;">
            <h2>Join Our Team of Technicians</h2>
            <p>Are you a skilled technician looking to expand your client base? Join our platform today!</p>
            <a href="register_technician.php" class="btn" style="margin-top: 15px;">Apply Now</a>
        </div>
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