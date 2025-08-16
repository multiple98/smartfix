<?php
session_start();
include '../includes/db.php';

// Check if user is logged in and is admin or technician
$user_type = $_SESSION['user_type'] ?? 'user';
if (!isset($_SESSION['user_id']) || ($user_type !== 'admin' && $user_type !== 'technician')) {
    header("Location: ../login.php");
    exit();
}

$error_message = '';
$success_message = '';
$service_request = null;

// Get service request ID from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $request_id = intval($_GET['id']);
    
    try {
        // Fetch service request details
        $query = "SELECT * FROM service_requests WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $request_id]);
        
        if ($stmt->rowCount() > 0) {
            $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_message = "Service request not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error retrieving service request: " . $e->getMessage();
    }
}

// Handle form submission for adding an update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_update'])) {
    $request_id = intval($_POST['request_id']);
    $update_text = trim($_POST['update_text']);
    $status = $_POST['status'];
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'] ?? 'admin';
    
    // Validate input
    if (empty($update_text)) {
        $error_message = "Please enter an update description.";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Insert update
            $insert_query = "INSERT INTO service_updates (service_request_id, update_text, status, created_by, created_by_type, created_at) 
                            VALUES (:service_request_id, :update_text, :status, :created_by, :created_by_type, NOW())";
            $insert_stmt = $pdo->prepare($insert_query);
            $insert_stmt->execute([
                'service_request_id' => $request_id,
                'update_text' => $update_text,
                'status' => $status,
                'created_by' => $user_id,
                'created_by_type' => $user_type
            ]);
            
            // Update service request status
            $update_query = "UPDATE service_requests SET status = :status";
            
            // If status is completed, set completed_at timestamp
            if ($status === 'completed') {
                $update_query .= ", completed_at = NOW()";
            }
            
            $update_query .= " WHERE id = :id";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([
                'status' => $status,
                'id' => $request_id
            ]);
            
            // Create notification for user if they have an account
            if (!empty($service_request['user_id'])) {
                $notification_query = "INSERT INTO notifications (user_id, type, message, is_read, created_at) 
                                      VALUES (:user_id, 'service_update', :message, 0, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'user_id' => $service_request['user_id'],
                    'message' => "Your service request ({$service_request['reference_number']}) has been updated to: " . ucfirst($status)
                ]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            $success_message = "Service update added successfully.";
            
            // Refresh service request data
            $query = "SELECT * FROM service_requests WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['id' => $request_id]);
            $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error_message = "Error adding update: " . $e->getMessage();
        }
    }
}

// Fetch previous updates for this service request
$updates = [];
if ($service_request) {
    try {
        $updates_query = "SELECT su.*, 
                         CASE 
                            WHEN su.created_by_type = 'admin' THEN (SELECT username FROM users WHERE id = su.created_by AND user_type = 'admin')
                            WHEN su.created_by_type = 'technician' THEN (SELECT name FROM technicians WHERE id = su.created_by)
                            ELSE 'System'
                         END as creator_name
                         FROM service_updates su
                         WHERE su.service_request_id = :request_id
                         ORDER BY su.created_at DESC";
        $updates_stmt = $pdo->prepare($updates_query);
        $updates_stmt->execute(['request_id' => $service_request['id']]);
        $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Just log the error, don't show to user
        error_log("Error fetching updates: " . $e->getMessage());
    }
}

// Function to get status label and class
function getStatusInfo($status) {
    switch ($status) {
        case 'pending':
            return [
                'label' => 'Pending',
                'class' => 'status-pending',
                'color' => '#856404',
                'bg' => '#fff3cd'
            ];
        case 'assigned':
            return [
                'label' => 'Assigned',
                'class' => 'status-assigned',
                'color' => '#004085',
                'bg' => '#cce5ff'
            ];
        case 'in_progress':
            return [
                'label' => 'In Progress',
                'class' => 'status-progress',
                'color' => '#0c5460',
                'bg' => '#d1ecf1'
            ];
        case 'completed':
            return [
                'label' => 'Completed',
                'class' => 'status-completed',
                'color' => '#155724',
                'bg' => '#d4edda'
            ];
        case 'cancelled':
            return [
                'label' => 'Cancelled',
                'class' => 'status-cancelled',
                'color' => '#721c24',
                'bg' => '#f8d7da'
            ];
        default:
            return [
                'label' => ucfirst($status),
                'class' => 'status-unknown',
                'color' => '#383d41',
                'bg' => '#e2e3e5'
            ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Service Request | SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header {
            background: #343a40;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .header a {
            color: white;
            text-decoration: none;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 10px 20px;
            font-size: 16px;
            line-height: 1.5;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        .service-info {
            margin-bottom: 20px;
        }
        
        .service-info-item {
            display: flex;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .service-info-label {
            font-weight: bold;
            width: 150px;
            flex-shrink: 0;
        }
        
        .service-info-value {
            flex-grow: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .updates-list {
            margin-top: 20px;
        }
        
        .update-item {
            border-left: 3px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
            border-radius: 0 5px 5px 0;
        }
        
        .update-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .update-content {
            margin-bottom: 10px;
        }
        
        .update-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .service-info-item {
                flex-direction: column;
            }
            
            .service-info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-tools"></i> Update Service Request</h1>
        <a href="service_requests.php"><i class="fas fa-arrow-left"></i> Back to Service Requests</a>
    </div>
    
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($service_request): ?>
            <?php $status_info = getStatusInfo($service_request['status']); ?>
            
            <div class="card">
                <div class="card-header">
                    <span>Service Request Details</span>
                    <span class="status-badge" style="background-color: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['color']; ?>;">
                        <?php echo $status_info['label']; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="service-info">
                        <div class="service-info-item">
                            <div class="service-info-label">Reference Number:</div>
                            <div class="service-info-value"><?php echo htmlspecialchars($service_request['reference_number']); ?></div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Customer:</div>
                            <div class="service-info-value"><?php echo htmlspecialchars($service_request['name']); ?></div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Contact:</div>
                            <div class="service-info-value">
                                <?php echo htmlspecialchars($service_request['email']); ?><br>
                                <?php echo htmlspecialchars($service_request['phone']); ?>
                            </div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Service Type:</div>
                            <div class="service-info-value"><?php echo htmlspecialchars($service_request['service_type']); ?></div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Service Option:</div>
                            <div class="service-info-value"><?php echo htmlspecialchars($service_request['service_option']); ?></div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Description:</div>
                            <div class="service-info-value"><?php echo nl2br(htmlspecialchars($service_request['description'])); ?></div>
                        </div>
                        
                        <div class="service-info-item">
                            <div class="service-info-label">Submitted On:</div>
                            <div class="service-info-value"><?php echo date('F j, Y, g:i a', strtotime($service_request['created_at'])); ?></div>
                        </div>
                        
                        <?php if (!empty($service_request['completed_at'])): ?>
                        <div class="service-info-item">
                            <div class="service-info-label">Completed On:</div>
                            <div class="service-info-value"><?php echo date('F j, Y, g:i a', strtotime($service_request['completed_at'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Add Service Update</div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="request_id" value="<?php echo $service_request['id']; ?>">
                        
                        <div class="form-group">
                            <label for="update_text">Update Description:</label>
                            <textarea class="form-control" id="update_text" name="update_text" required placeholder="Describe the work done, parts replaced, or current status of the service..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Update Status:</label>
                            <select class="form-control" id="status" name="status">
                                <option value="pending" <?php echo ($service_request['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="assigned" <?php echo ($service_request['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in_progress" <?php echo ($service_request['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo ($service_request['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($service_request['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_update" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Update
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Service History</div>
                <div class="card-body">
                    <?php if (count($updates) > 0): ?>
                        <div class="updates-list">
                            <?php foreach ($updates as $update): ?>
                                <?php $update_status_info = getStatusInfo($update['status']); ?>
                                <div class="update-item">
                                    <div class="update-header">
                                        <span><strong><?php echo htmlspecialchars($update['creator_name']); ?></strong> • <?php echo date('F j, Y, g:i a', strtotime($update['created_at'])); ?></span>
                                        <span class="update-status" style="background-color: <?php echo $update_status_info['bg']; ?>; color: <?php echo $update_status_info['color']; ?>;">
                                            <?php echo $update_status_info['label']; ?>
                                        </span>
                                    </div>
                                    <div class="update-content">
                                        <?php echo nl2br(htmlspecialchars($update['update_text'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No updates have been added to this service request yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <p>Service request not found or you don't have permission to view it.</p>
                    <a href="service_requests.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Service Requests
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>