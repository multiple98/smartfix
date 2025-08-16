<?php
session_start();
include('../includes/db.php');

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../login.php?redirect=technician/profile.php');
    exit;
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

// Initialize variables
$success_message = '';
$error_message = '';
$technician = null;

// Get technician information
try {
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
    $error_message = "Error retrieving technician profile: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $regions = isset($_POST['regions']) ? $_POST['regions'] : [];
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    $status = trim($_POST['status']);
    
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
            
            if ($technician) {
                // Update existing technician
                $query = "UPDATE technicians SET 
                          name = :name, 
                          phone = :phone, 
                          email = :email, 
                          specialization = :specialization, 
                          regions = :regions, 
                          address = :address, 
                          bio = :bio, 
                          status = :status 
                          WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'specialization' => $specialization,
                    'regions' => $regions_str,
                    'address' => $address,
                    'bio' => $bio,
                    'status' => $status,
                    'user_id' => $_SESSION['user_id']
                ]);
                
                $success_message = "Your profile has been updated successfully.";
            } else {
                // Create new technician profile
                $query = "INSERT INTO technicians (name, phone, email, specialization, regions, address, bio, status, user_id, created_at) 
                          VALUES (:name, :phone, :email, :specialization, :regions, :address, :bio, :status, :user_id, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'specialization' => $specialization,
                    'regions' => $regions_str,
                    'address' => $address,
                    'bio' => $bio,
                    'status' => $status,
                    'user_id' => $_SESSION['user_id']
                ]);
                
                $success_message = "Your technician profile has been created successfully.";
            }
            
            // Refresh technician data
            $stmt = $pdo->prepare("SELECT * FROM technicians WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $technician = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Process profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (in_array(strtolower($filetype), $allowed)) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('../uploads/technicians')) {
                mkdir('../uploads/technicians', 0777, true);
            }
            
            // Create unique filename
            $new_filename = 'technician_' . $_SESSION['user_id'] . '_' . time() . '.' . $filetype;
            $upload_path = '../uploads/technicians/' . $new_filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                try {
                    // Update profile picture in database
                    $stmt = $pdo->prepare("UPDATE technicians SET profile_picture = ? WHERE user_id = ?");
                    $stmt->execute([$new_filename, $_SESSION['user_id']]);
                    
                    $success_message = "Profile picture updated successfully.";
                    
                    // Refresh technician data
                    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $technician = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                } catch (PDOException $e) {
                    $error_message = "Error updating profile picture in database: " . $e->getMessage();
                }
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SmartFix Technician</title>
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
            --bg-color: #f5f5f5;
            --bg-light: #ffffff;
            --bg-dark: #f0f0f0;
            --shadow: 0 5px 15px rgba(0,0,0,0.1);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: var(--bg-color);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: var(--shadow);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .user-info span {
            font-weight: 500;
        }
        
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .profile-sidebar {
            text-align: center;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            overflow: hidden;
            border: 5px solid var(--primary-light);
        }
        
        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-stats {
            margin-top: 20px;
            text-align: left;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--text-light);
        }
        
        .stat-value {
            font-weight: 600;
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
            padding: 10px;
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
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .rating {
            color: var(--accent-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .checkbox-group {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SmartFix</h2>
                <p>Technician Portal</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="earnings.php"><i class="fas fa-money-bill-wave"></i> Earnings</a></li>
                <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Main Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Profile</h1>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="Profile">
                    <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$technician): ?>
                <div class="alert alert-warning">
                    <p>You haven't set up your technician profile yet. Please complete the form below to start receiving bookings.</p>
                </div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h2>Profile Picture</h2>
                        </div>
                        <div class="card-body profile-sidebar">
                            <div class="profile-picture">
                                <?php if (isset($technician['profile_picture']) && !empty($technician['profile_picture'])): ?>
                                    <img src="../uploads/technicians/<?php echo htmlspecialchars($technician['profile_picture']); ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/150?text=Upload+Photo" alt="Default Profile">
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="profile_picture">Upload New Picture</label>
                                    <input type="file" name="profile_picture" id="profile_picture" class="form-control">
                                </div>
                                <button type="submit" name="upload_photo" class="btn">Upload Photo</button>
                            </form>
                            
                            <?php if ($technician): ?>
                                <div class="profile-stats">
                                    <div class="rating">
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
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">Jobs Completed</div>
                                        <div class="stat-value"><?php echo $technician['total_jobs']; ?></div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">Member Since</div>
                                        <div class="stat-value"><?php echo date('M d, Y', strtotime($technician['created_at'])); ?></div>
                                    </div>
                                    
                                    <div class="stat-item">
                                        <div class="stat-label">Current Status</div>
                                        <div class="stat-value">
                                            <?php if ($technician['status'] === 'available'): ?>
                                                <span style="color: var(--success-color);">Available</span>
                                            <?php elseif ($technician['status'] === 'busy'): ?>
                                                <span style="color: var(--warning-color);">Busy</span>
                                            <?php else: ?>
                                                <span style="color: var(--danger-color);">Offline</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="name">Full Name*</label>
                                <input type="text" name="name" id="name" class="form-control" required value="<?php echo $technician ? htmlspecialchars($technician['name']) : htmlspecialchars($_SESSION['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number*</label>
                                <input type="tel" name="phone" id="phone" class="form-control" required value="<?php echo $technician ? htmlspecialchars($technician['phone']) : ''; ?>" placeholder="e.g., +260 977123456">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?php echo $technician ? htmlspecialchars($technician['email']) : ''; ?>" placeholder="e.g., yourname@example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="specialization">Primary Specialization*</label>
                                <select name="specialization" id="specialization" class="form-control" required>
                                    <option value="">Select Your Specialization</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo htmlspecialchars($spec); ?>" <?php if ($technician && $technician['specialization'] === $spec) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($spec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Regions You Serve*</label>
                                <div class="checkbox-group">
                                    <?php 
                                    $selected_regions = [];
                                    if ($technician && !empty($technician['regions'])) {
                                        $selected_regions = explode(',', $technician['regions']);
                                    }
                                    
                                    foreach ($zambian_regions as $region): 
                                    ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox" name="regions[]" id="region_<?php echo htmlspecialchars($region); ?>" value="<?php echo htmlspecialchars($region); ?>" <?php if (in_array($region, $selected_regions)) echo 'checked'; ?>>
                                            <label for="region_<?php echo htmlspecialchars($region); ?>"><?php echo htmlspecialchars($region); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Business Address</label>
                                <textarea name="address" id="address" class="form-control" placeholder="Enter your business address..."><?php echo $technician ? htmlspecialchars($technician['address']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">About Yourself & Your Experience*</label>
                                <textarea name="bio" id="bio" class="form-control" required placeholder="Describe your skills, experience, and the services you offer..."><?php echo $technician ? htmlspecialchars($technician['bio']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Availability Status*</label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="available" <?php if ($technician && $technician['status'] === 'available') echo 'selected'; ?>>Available for Jobs</option>
                                    <option value="busy" <?php if ($technician && $technician['status'] === 'busy') echo 'selected'; ?>>Busy (Limited Availability)</option>
                                    <option value="offline" <?php if ($technician && $technician['status'] === 'offline') echo 'selected'; ?>>Offline (Not Available)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-block">Save Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>