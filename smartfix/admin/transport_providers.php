<?php
session_start();
include('../includes/db.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Process form submission for adding/editing transport providers
$success_message = '';
$error_message = '';

// Delete transport provider
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM transport_providers WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Transport provider deleted successfully.";
    } catch (PDOException $e) {
        $error_message = "Error deleting transport provider: " . $e->getMessage();
    }
}

// Add or edit transport provider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_provider'])) {
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $regions = isset($_POST['regions']) ? implode(',', $_POST['regions']) : '';
    $estimated_days = (int)$_POST['estimated_days'];
    $cost_per_km = (float)$_POST['cost_per_km'];
    $description = trim($_POST['description']);
    
    // Simple validation
    if (empty($name) || empty($contact) || empty($regions)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if we're editing or adding
            if (isset($_POST['provider_id']) && !empty($_POST['provider_id'])) {
                // Update existing provider
                $query = "UPDATE transport_providers SET 
                          name = :name, 
                          contact = :contact, 
                          email = :email, 
                          regions = :regions, 
                          estimated_days = :estimated_days, 
                          cost_per_km = :cost_per_km, 
                          description = :description 
                          WHERE id = :id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'contact' => $contact,
                    'email' => $email,
                    'regions' => $regions,
                    'estimated_days' => $estimated_days,
                    'cost_per_km' => $cost_per_km,
                    'description' => $description,
                    'id' => $_POST['provider_id']
                ]);
                
                $success_message = "Transport provider updated successfully.";
            } else {
                // Add new provider
                $query = "INSERT INTO transport_providers (name, contact, email, regions, estimated_days, cost_per_km, description, created_at) 
                          VALUES (:name, :contact, :email, :regions, :estimated_days, :cost_per_km, :description, NOW())";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'name' => $name,
                    'contact' => $contact,
                    'email' => $email,
                    'regions' => $regions,
                    'estimated_days' => $estimated_days,
                    'cost_per_km' => $cost_per_km,
                    'description' => $description
                ]);
                
                $success_message = "Transport provider added successfully.";
            }
        } catch (PDOException $e) {
            // If table doesn't exist, create it
            if ($e->getCode() == '42S02') {
                try {
                    $create_table = "CREATE TABLE transport_providers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        contact VARCHAR(50) NOT NULL,
                        email VARCHAR(100),
                        regions TEXT NOT NULL,
                        estimated_days INT NOT NULL,
                        cost_per_km DECIMAL(10,2) NOT NULL,
                        description TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )";
                    $pdo->exec($create_table);
                    
                    // Try inserting again
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        'name' => $name,
                        'contact' => $contact,
                        'email' => $email,
                        'regions' => $regions,
                        'estimated_days' => $estimated_days,
                        'cost_per_km' => $cost_per_km,
                        'description' => $description
                    ]);
                    
                    $success_message = "Transport provider added successfully.";
                } catch (PDOException $e2) {
                    $error_message = "Error creating transport providers table: " . $e2->getMessage();
                }
            } else {
                $error_message = "Error adding transport provider: " . $e->getMessage();
            }
        }
    }
}

// Get transport provider for editing
$edit_provider = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = $_GET['edit'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE id = ?");
        $stmt->execute([$id]);
        $edit_provider = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Table might not exist yet
    }
}

// Get all transport providers
$providers = [];
try {
    $stmt = $pdo->query("SELECT * FROM transport_providers ORDER BY name");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Manage Transport Providers - SmartFix Admin</title>
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
                <li><a href="transport_providers.php" class="active"><i class="fas fa-truck"></i> Transport Providers</a></li>
                <li><a href="technicians.php"><i class="fas fa-user-hard-hat"></i> Technicians</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-truck"></i> Manage Transport Providers</h1>
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
                    <h2><?php echo $edit_provider ? 'Edit Transport Provider' : 'Add New Transport Provider'; ?></h2>
                </div>
                
                <form method="POST" action="transport_providers.php">
                    <?php if ($edit_provider): ?>
                        <input type="hidden" name="provider_id" value="<?php echo $edit_provider['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Provider Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo $edit_provider ? htmlspecialchars($edit_provider['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Contact Number *</label>
                        <input type="text" id="contact" name="contact" class="form-control" value="<?php echo $edit_provider ? htmlspecialchars($edit_provider['contact']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $edit_provider ? htmlspecialchars($edit_provider['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Regions Served *</label>
                        <div class="checkbox-group">
                            <?php 
                            $selected_regions = $edit_provider ? explode(',', $edit_provider['regions']) : [];
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
                        <label for="estimated_days">Estimated Delivery Days *</label>
                        <input type="number" id="estimated_days" name="estimated_days" class="form-control" min="1" max="30" value="<?php echo $edit_provider ? htmlspecialchars($edit_provider['estimated_days']) : '3'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_per_km">Cost per Kilometer (ZMW) *</label>
                        <input type="number" id="cost_per_km" name="cost_per_km" class="form-control" min="0" step="0.01" value="<?php echo $edit_provider ? htmlspecialchars($edit_provider['cost_per_km']) : '5.00'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"><?php echo $edit_provider ? htmlspecialchars($edit_provider['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="submit_provider" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $edit_provider ? 'Update Provider' : 'Add Provider'; ?>
                        </button>
                        
                        <?php if ($edit_provider): ?>
                            <a href="transport_providers.php" class="btn">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Transport Providers List</h2>
                </div>
                
                <?php if (count($providers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Regions</th>
                                <th>Est. Days</th>
                                <th>Cost/km</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($provider['name']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['regions']); ?></td>
                                    <td><?php echo htmlspecialchars($provider['estimated_days']); ?> days</td>
                                    <td>ZMW <?php echo number_format($provider['cost_per_km'], 2); ?></td>
                                    <td class="action-buttons">
                                        <a href="transport_providers.php?edit=<?php echo $provider['id']; ?>" class="btn"><i class="fas fa-edit"></i></a>
                                        <a href="transport_providers.php?delete=<?php echo $provider['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this provider?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No transport providers found. Add your first provider using the form above.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>