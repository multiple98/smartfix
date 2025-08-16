<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Get admin information
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['admin_id'] ?? 1;

// Process form submission for adding/editing technicians
$success_message = '';
$error_message = '';

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_technicians'])) {
    $action = $_POST['bulk_action'];
    $selected_ids = $_POST['selected_technicians'];
    
    try {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE technicians SET status = 'available' WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                $stmt->execute($selected_ids);
                $success_message = count($selected_ids) . " technician(s) activated successfully.";
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE technicians SET status = 'offline' WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                $stmt->execute($selected_ids);
                $success_message = count($selected_ids) . " technician(s) deactivated successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM technicians WHERE id IN (" . str_repeat('?,', count($selected_ids) - 1) . "?)");
                $stmt->execute($selected_ids);
                $success_message = count($selected_ids) . " technician(s) deleted successfully.";
                break;
        }
    } catch (PDOException $e) {
        $error_message = "Error performing bulk action: " . $e->getMessage();
    }
}

// Handle status update via AJAX
if (isset($_POST['update_status']) && isset($_POST['technician_id']) && isset($_POST['new_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE technicians SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['new_status'], $_POST['technician_id']]);
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $e->getMessage()]);
        exit;
    }
}

// Handle profile photo upload
if (isset($_FILES['profile_photo']) && isset($_POST['technician_id_photo'])) {
    $technician_id = $_POST['technician_id_photo'];
    $upload_dir = '../uploads/technicians/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $file_name = 'technician_' . $technician_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE technicians SET profile_photo = ? WHERE id = ?");
                $stmt->execute(['uploads/technicians/' . $file_name, $technician_id]);
                $success_message = "Profile photo updated successfully.";
            } catch (PDOException $e) {
                $error_message = "Error updating profile photo: " . $e->getMessage();
            }
        } else {
            $error_message = "Error uploading file.";
        }
    } else {
        $error_message = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 5MB.";
    }
}

// Delete technician
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM technicians WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Technician deleted successfully.";
    } catch (PDOException $e) {
        $error_message = "Error deleting technician: " . $e->getMessage();
    }
}

// Add or edit technician
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_technician'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $specialization = trim($_POST['specialization']);
    $regions = isset($_POST['regions']) ? implode(',', $_POST['regions']) : '';
    $address = trim($_POST['address']);
    $latitude = !empty($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? trim($_POST['longitude']) : null;
    $status = trim($_POST['status']);
    $bio = trim($_POST['bio']);
    $experience_years = intval($_POST['experience_years'] ?? 0);
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $skills = trim($_POST['skills'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender = trim($_POST['gender'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    
    // Simple validation
    if (empty($name) || empty($phone) || empty($specialization) || empty($regions)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if we're editing or adding
            if (isset($_POST['technician_id']) && !empty($_POST['technician_id'])) {
                // Update existing technician
                $query = "UPDATE technicians SET 
                          name = :name, 
                          phone = :phone, 
                          email = :email, 
                          specialization = :specialization, 
                          regions = :regions, 
                          address = :address, 
                          latitude = :latitude, 
                          longitude = :longitude, 
                          status = :status, 
                          bio = :bio,
                          experience_years = :experience_years,
                          hourly_rate = :hourly_rate,
                          skills = :skills,
                          certifications = :certifications,
                          emergency_contact = :emergency_contact,
                          date_of_birth = :date_of_birth,
                          gender = :gender,
                          national_id = :national_id,
                          is_verified = :is_verified,
                          updated_at = NOW()
                          WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'specialization' => $specialization,
                    'regions' => $regions,
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status' => $status,
                    'bio' => $bio,
                    'experience_years' => $experience_years,
                    'hourly_rate' => $hourly_rate,
                    'skills' => $skills,
                    'certifications' => $certifications,
                    'emergency_contact' => $emergency_contact,
                    'date_of_birth' => $date_of_birth,
                    'gender' => $gender,
                    'national_id' => $national_id,
                    'is_verified' => $is_verified,
                    'id' => $_POST['technician_id']
                ]);
                
                $success_message = "Technician updated successfully.";
            } else {
                // Add new technician
                $query = "INSERT INTO technicians (name, phone, email, specialization, regions, address, latitude, longitude, status, bio, experience_years, hourly_rate, skills, certifications, emergency_contact, date_of_birth, gender, national_id, is_verified, created_at) 
                          VALUES (:name, :phone, :email, :specialization, :regions, :address, :latitude, :longitude, :status, :bio, :experience_years, :hourly_rate, :skills, :certifications, :emergency_contact, :date_of_birth, :gender, :national_id, :is_verified, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'specialization' => $specialization,
                    'regions' => $regions,
                    'address' => $address,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status' => $status,
                    'bio' => $bio,
                    'experience_years' => $experience_years,
                    'hourly_rate' => $hourly_rate,
                    'skills' => $skills,
                    'certifications' => $certifications,
                    'emergency_contact' => $emergency_contact,
                    'date_of_birth' => $date_of_birth,
                    'gender' => $gender,
                    'national_id' => $national_id,
                    'is_verified' => $is_verified
                ]);
                
                $success_message = "Technician added successfully.";
            }
        } catch (PDOException $e) {
            // If table doesn't exist, create it
            if ($e->getCode() == '42S02') {
                try {
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
                        rating DECIMAL(3,2) DEFAULT 0,
                        total_jobs INT DEFAULT 0,
                        completed_jobs INT DEFAULT 0,
                        profile_photo VARCHAR(255),
                        experience_years INT DEFAULT 0,
                        hourly_rate DECIMAL(10,2) DEFAULT 0,
                        availability_schedule JSON,
                        skills TEXT,
                        certifications TEXT,
                        emergency_contact VARCHAR(20),
                        date_of_birth DATE,
                        gender ENUM('male', 'female', 'other'),
                        national_id VARCHAR(20),
                        is_verified BOOLEAN DEFAULT FALSE,
                        last_active DATETIME,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_table);
                    
                    // Try inserting again
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'specialization' => $specialization,
                        'regions' => $regions,
                        'address' => $address,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'status' => $status,
                        'bio' => $bio
                    ]);
                    
                    $success_message = "Technician added successfully.";
                } catch (PDOException $e2) {
                    $error_message = "Error creating technicians table: " . $e2->getMessage();
                }
            } else {
                $error_message = "Error adding technician: " . $e->getMessage();
            }
        }
    }
}

// Get technician for editing
$edit_technician = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = $_GET['edit'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
        $stmt->execute([$id]);
        $edit_technician = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist yet
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$specialization_filter = $_GET['specialization_filter'] ?? '';
$region_filter = $_GET['region_filter'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'name';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($specialization_filter)) {
    $where_conditions[] = "specialization = ?";
    $params[] = $specialization_filter;
}

if (!empty($region_filter)) {
    $where_conditions[] = "regions LIKE ?";
    $params[] = "%$region_filter%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Valid sort columns
$valid_sort_columns = ['name', 'specialization', 'status', 'rating', 'total_jobs', 'created_at'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'name';
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

// Get technicians with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$technicians = [];
$total_technicians = 0;

try {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM technicians $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_technicians = $count_stmt->fetchColumn();
    
    // Get technicians
    $query = "SELECT * FROM technicians $where_clause ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet
}

$total_pages = ceil($total_technicians / $per_page);

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Technicians - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #004080;
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
            background-color: #f5f5f5;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #004080;
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: #004080;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #004080;
        }
        
        table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
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
            border-color: #007BFF;
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .checkbox-item input {
            margin-right: 5px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-busy {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-offline {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .map-container {
            height: 300px;
            margin-bottom: 20px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 10px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SmartFix Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="service_requests.php"><i class="fas fa-tools"></i> Service Requests</a></li>
                <li><a href="products.php"><i class="fas fa-shopping-cart"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li><a href="transport_providers.php"><i class="fas fa-truck"></i> Transport Providers</a></li>
                <li><a href="technicians.php" class="active"><i class="fas fa-user-hard-hat"></i> Technicians</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-user-hard-hat"></i> Manage Technicians</h1>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="Admin">
                    <span>Admin</span>
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
            
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $edit_technician ? 'Edit Technician' : 'Add New Technician'; ?></h2>
                </div>
                
                <form method="POST" action="technicians.php">
                    <?php if ($edit_technician): ?>
                        <input type="hidden" name="technician_id" value="<?php echo $edit_technician['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['name']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['phone']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="specialization">Specialization *</label>
                                <select id="specialization" name="specialization" class="form-control" required>
                                    <option value="">Select Specialization</option>
                                    <?php foreach ($specializations as $spec): ?>
                                        <option value="<?php echo $spec; ?>" <?php echo ($edit_technician && $edit_technician['specialization'] == $spec) ? 'selected' : ''; ?>><?php echo $spec; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="experience_years">Experience (Years)</label>
                                <input type="number" id="experience_years" name="experience_years" class="form-control" min="0" max="50" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['experience_years'] ?? 0) : '0'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate (ZMW)</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" min="0" step="0.01" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['hourly_rate'] ?? 0) : '0'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['date_of_birth'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($edit_technician && ($edit_technician['gender'] ?? '') == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($edit_technician && ($edit_technician['gender'] ?? '') == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($edit_technician && ($edit_technician['gender'] ?? '') == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="national_id">National ID</label>
                                <input type="text" id="national_id" name="national_id" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['national_id'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="emergency_contact">Emergency Contact</label>
                                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['emergency_contact'] ?? '') : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="skills">Skills (comma-separated)</label>
                        <textarea id="skills" name="skills" class="form-control" rows="3" placeholder="e.g., Soldering, Circuit Repair, Software Installation"><?php echo $edit_technician ? htmlspecialchars($edit_technician['skills'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="certifications">Certifications</label>
                        <textarea id="certifications" name="certifications" class="form-control" rows="3" placeholder="List any relevant certifications"><?php echo $edit_technician ? htmlspecialchars($edit_technician['certifications'] ?? '') : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Regions Served *</label>
                        <div class="checkbox-group">
                            <?php 
                            $selected_regions = $edit_technician ? explode(',', $edit_technician['regions']) : [];
                            foreach ($zambian_regions as $region): 
                            ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="region_<?php echo $region; ?>" name="regions[]" value="<?php echo $region; ?>" <?php echo in_array($region, $selected_regions) ? 'checked' : ''; ?>>
                                    <label for="region_<?php echo $region; ?>"><?php echo $region; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <input type="text" id="address" name="address" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['address']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Location (Click on map to set location)</label>
                        <div id="map" class="map-container"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="latitude">Latitude</label>
                                <input type="text" id="latitude" name="latitude" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['latitude']) : ''; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="longitude">Longitude</label>
                                <input type="text" id="longitude" name="longitude" class="form-control" value="<?php echo $edit_technician ? htmlspecialchars($edit_technician['longitude']) : ''; ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="available" <?php echo ($edit_technician && $edit_technician['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="busy" <?php echo ($edit_technician && $edit_technician['status'] == 'busy') ? 'selected' : ''; ?>>Busy</option>
                            <option value="offline" <?php echo ($edit_technician && $edit_technician['status'] == 'offline') ? 'selected' : ''; ?>>Offline</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Bio/Description</label>
                        <textarea id="bio" name="bio" class="form-control"><?php echo $edit_technician ? htmlspecialchars($edit_technician['bio']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="is_verified" name="is_verified" <?php echo ($edit_technician && ($edit_technician['is_verified'] ?? 0)) ? 'checked' : ''; ?>>
                            <label for="is_verified">Verified Technician</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="submit_technician" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $edit_technician ? 'Update Technician' : 'Add Technician'; ?>
                        </button>
                        
                        <?php if ($edit_technician): ?>
                            <a href="technicians.php" class="btn">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Technicians List</h2>
                </div>
                
                <?php if (count($technicians) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Phone</th>
                                <th>Regions</th>
                                <th>Status</th>
                                <th>Rating</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicians as $tech): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tech['name']); ?></td>
                                    <td><?php echo htmlspecialchars($tech['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($tech['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($tech['regions']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $tech['status']; ?>">
                                            <?php echo ucfirst($tech['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $rating = $tech['rating'] > 0 ? $tech['rating'] : 'N/A';
                                        echo is_numeric($rating) ? number_format($rating, 1) . '/5.0' : $rating;
                                        ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="technicians.php?edit=<?php echo $tech['id']; ?>" class="btn"><i class="fas fa-edit"></i></a>
                                        <a href="technicians.php?delete=<?php echo $tech['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this technician?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No technicians found. Add your first technician using the form above.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize map when the page loads
        function initMap() {
            // Default location (Lusaka, Zambia)
            var defaultLat = -15.4167;
            var defaultLng = 28.2833;
            
            // If editing and coordinates exist, use those
            <?php if ($edit_technician && !empty($edit_technician['latitude']) && !empty($edit_technician['longitude'])): ?>
                defaultLat = <?php echo $edit_technician['latitude']; ?>;
                defaultLng = <?php echo $edit_technician['longitude']; ?>;
            <?php endif; ?>
            
            // Create map
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: defaultLat, lng: defaultLng},
                zoom: 12
            });
            
            // Create marker
            var marker = new google.maps.Marker({
                position: {lat: defaultLat, lng: defaultLng},
                map: map,
                draggable: true
            });
            
            // Update coordinates when marker is dragged
            google.maps.event.addListener(marker, 'dragend', function() {
                document.getElementById('latitude').value = marker.getPosition().lat();
                document.getElementById('longitude').value = marker.getPosition().lng();
            });
            
            // Add marker when map is clicked
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                document.getElementById('latitude').value = event.latLng.lat();
                document.getElementById('longitude').value = event.latLng.lng();
            });
            
            // Set initial coordinates
            document.getElementById('latitude').value = defaultLat;
            document.getElementById('longitude').value = defaultLng;
        }
    </script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>