<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("INSERT INTO transport_providers (name, contact, email, regions, estimated_days, cost_per_km, base_cost, max_weight_kg, service_type, vehicle_type, rating, operating_hours, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact'],
                        $_POST['email'],
                        $_POST['regions'],
                        $_POST['estimated_days'],
                        $_POST['cost_per_km'],
                        $_POST['base_cost'],
                        $_POST['max_weight_kg'],
                        $_POST['service_type'],
                        $_POST['vehicle_type'],
                        $_POST['rating'],
                        $_POST['operating_hours'],
                        $_POST['description'],
                        $_POST['status']
                    ]);
                    $success_message = "Transport provider added successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error adding provider: " . $e->getMessage();
                }
                break;
                
            case 'edit':
                try {
                    $stmt = $pdo->prepare("UPDATE transport_providers SET name=?, contact=?, email=?, regions=?, estimated_days=?, cost_per_km=?, base_cost=?, max_weight_kg=?, service_type=?, vehicle_type=?, rating=?, operating_hours=?, description=?, status=? WHERE id=?");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['contact'],
                        $_POST['email'],
                        $_POST['regions'],
                        $_POST['estimated_days'],
                        $_POST['cost_per_km'],
                        $_POST['base_cost'],
                        $_POST['max_weight_kg'],
                        $_POST['service_type'],
                        $_POST['vehicle_type'],
                        $_POST['rating'],
                        $_POST['operating_hours'],
                        $_POST['description'],
                        $_POST['status'],
                        $_POST['id']
                    ]);
                    $success_message = "Transport provider updated successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error updating provider: " . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM transport_providers WHERE id=?");
                    $stmt->execute([$_POST['id']]);
                    $success_message = "Transport provider deleted successfully!";
                } catch (PDOException $e) {
                    $error_message = "Error deleting provider: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all transport providers
try {
    $stmt = $pdo->query("SELECT * FROM transport_providers ORDER BY name ASC");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $providers = [];
    $error_message = "Error fetching providers: " . $e->getMessage();
}

// Get provider for editing
$edit_provider = null;
if (isset($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $edit_provider = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching provider for editing: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transport Providers - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: var(--success-color);
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: var(--danger-color);
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .form-header i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 4px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .providers-table {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }
        
        tr:hover {
            background: rgba(0, 123, 255, 0.05);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .rating-stars {
            color: #ffc107;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .regions-list {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .table-responsive {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-truck"></i> Transport Providers Management</h1>
            <p>Manage delivery services and shipping providers</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-plus-circle"></i>
                <h2><?php echo $edit_provider ? 'Edit Transport Provider' : 'Add New Transport Provider'; ?></h2>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_provider ? 'edit' : 'add'; ?>">
                <?php if ($edit_provider): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_provider['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="required">Provider Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact" class="required">Contact Number</label>
                        <input type="text" id="contact" name="contact" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['contact'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="regions" class="required">Service Regions</label>
                        <input type="text" id="regions" name="regions" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['regions'] ?? ''); ?>" 
                               placeholder="e.g., Lusaka,Copperbelt,Central" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estimated_days" class="required">Estimated Delivery Days</label>
                        <input type="number" id="estimated_days" name="estimated_days" class="form-control" 
                               value="<?php echo $edit_provider['estimated_days'] ?? 3; ?>" min="1" max="30" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_per_km" class="required">Cost per KM (ZMW)</label>
                        <input type="number" id="cost_per_km" name="cost_per_km" class="form-control" 
                               value="<?php echo $edit_provider['cost_per_km'] ?? 5.00; ?>" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="base_cost" class="required">Base Cost (ZMW)</label>
                        <input type="number" id="base_cost" name="base_cost" class="form-control" 
                               value="<?php echo $edit_provider['base_cost'] ?? 20.00; ?>" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_weight_kg" class="required">Max Weight (KG)</label>
                        <input type="number" id="max_weight_kg" name="max_weight_kg" class="form-control" 
                               value="<?php echo $edit_provider['max_weight_kg'] ?? 50; ?>" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type" class="required">Service Type</label>
                        <select id="service_type" name="service_type" class="form-control" required>
                            <option value="standard" <?php echo ($edit_provider['service_type'] ?? '') == 'standard' ? 'selected' : ''; ?>>Standard</option>
                            <option value="express" <?php echo ($edit_provider['service_type'] ?? '') == 'express' ? 'selected' : ''; ?>>Express</option>
                            <option value="overnight" <?php echo ($edit_provider['service_type'] ?? '') == 'overnight' ? 'selected' : ''; ?>>Overnight</option>
                            <option value="same_day" <?php echo ($edit_provider['service_type'] ?? '') == 'same_day' ? 'selected' : ''; ?>>Same Day</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <input type="text" id="vehicle_type" name="vehicle_type" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['vehicle_type'] ?? 'Van'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">Rating (1-5)</label>
                        <input type="number" id="rating" name="rating" class="form-control" 
                               value="<?php echo $edit_provider['rating'] ?? 4.0; ?>" step="0.1" min="1" max="5">
                    </div>
                    
                    <div class="form-group">
                        <label for="operating_hours">Operating Hours</label>
                        <input type="text" id="operating_hours" name="operating_hours" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_provider['operating_hours'] ?? '8:00 AM - 6:00 PM'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="required">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo ($edit_provider['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_provider['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_provider['description'] ?? ''); ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_provider ? 'Update Provider' : 'Add Provider'; ?>
                    </button>
                    <?php if ($edit_provider): ?>
                        <a href="manage_transport_providers.php" class="btn btn-secondary" style="margin-left: 10px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Providers Table -->
        <div class="providers-table">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Transport Providers (<?php echo count($providers); ?>)</h2>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Contact</th>
                            <th>Regions</th>
                            <th>Service Type</th>
                            <th>Cost/KM</th>
                            <th>Base Cost</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($providers)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 50px;">
                                    <i class="fas fa-truck" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                                    <p>No transport providers found. Add your first provider above.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($providers as $provider): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($provider['name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($provider['vehicle_type'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($provider['contact']); ?><br>
                                        <?php if ($provider['email']): ?>
                                            <small><?php echo htmlspecialchars($provider['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="regions-list" title="<?php echo htmlspecialchars($provider['regions']); ?>">
                                            <?php echo htmlspecialchars($provider['regions']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo ucfirst($provider['service_type']); ?><br>
                                        <small><?php echo $provider['estimated_days']; ?> days</small>
                                    </td>
                                    <td>ZMW <?php echo number_format($provider['cost_per_km'], 2); ?></td>
                                    <td>ZMW <?php echo number_format($provider['base_cost'], 2); ?></td>
                                    <td>
                                        <span class="rating-stars">
                                            <?php
                                            $rating = floatval($provider['rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating ? '★' : '☆';
                                            }
                                            ?>
                                        </span><br>
                                        <small><?php echo number_format($rating, 1); ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $provider['status']; ?>">
                                            <?php echo ucfirst($provider['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $provider['id']; ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this provider?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $provider['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_dashboard_new.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>