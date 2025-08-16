<?php
/**
 * Update Service Request Status API
 * Updates service request status from technician interface
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST method allowed'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$request_id = $input['request_id'] ?? null;
$status = $input['status'] ?? null;
$technician_id = $input['technician_id'] ?? null;
$notes = $input['notes'] ?? '';

// Validate required parameters
if (!$request_id || !$status || !$technician_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid status'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verify technician has permission to update this request
    $verify_query = "SELECT id, status, technician_id FROM service_requests WHERE id = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$request_id]);
    $request = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Service request not found');
    }
    
    // Check if technician is assigned to this request or if it's unassigned
    if ($request['technician_id'] && $request['technician_id'] != $technician_id) {
        throw new Exception('You are not assigned to this service request');
    }
    
    // Update service request
    $update_fields = ['status = ?'];
    $params = [$status];
    
    // If starting work, assign technician
    if ($status === 'in_progress' && !$request['technician_id']) {
        $update_fields[] = 'technician_id = ?';
        $params[] = $technician_id;
    }
    
    // Add completion timestamp if completed
    if ($status === 'completed') {
        $update_fields[] = 'completed_at = NOW()';
    }
    
    // Add notes if provided
    if ($notes) {
        $update_fields[] = 'notes = CONCAT(COALESCE(notes, ""), ?)';
        $params[] = "\n[" . date('Y-m-d H:i:s') . "] " . $notes;
    }
    
    $params[] = $request_id;
    
    $update_query = "UPDATE service_requests SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $update_stmt = $pdo->prepare($update_query);
    $update_stmt->execute($params);
    
    // Log status change
    $log_query = "INSERT INTO service_request_status_log 
                 (request_id, technician_id, old_status, new_status, notes, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_query);
    $log_stmt->execute([$request_id, $technician_id, $request['status'], $status, $notes]);
    
    // Send notification to customer if status changed significantly
    if (in_array($status, ['in_progress', 'completed'])) {
        sendCustomerNotification($request_id, $status);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Service request status updated successfully',
        'new_status' => $status,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Send notification to customer about status change
 */
function sendCustomerNotification($request_id, $status) {
    global $pdo;
    
    try {
        // Get customer info
        $customer_query = "SELECT sr.user_id, sr.customer_name, sr.customer_email, sr.service_type, u.email
                          FROM service_requests sr
                          LEFT JOIN users u ON sr.user_id = u.id
                          WHERE sr.id = ?";
        $customer_stmt = $pdo->prepare($customer_query);
        $customer_stmt->execute([$request_id]);
        $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $status_messages = [
                'in_progress' => 'Your service request is now in progress. Our technician has started working on your request.',
                'completed' => 'Great news! Your service request has been completed successfully.'
            ];
            
            $message = $status_messages[$status] ?? "Your service request status has been updated to: " . ucfirst(str_replace('_', ' ', $status));
            
            // Insert notification
            $notification_query = "INSERT INTO notifications 
                                  (user_id, title, message, type, created_at) 
                                  VALUES (?, ?, ?, 'service_update', NOW())";
            $notification_stmt = $pdo->prepare($notification_query);
            $notification_stmt->execute([
                $customer['user_id'],
                'Service Request Update',
                $message
            ]);
        }
        
    } catch (PDOException $e) {
        // Log error but don't fail the main operation
        error_log("Failed to send customer notification: " . $e->getMessage());
    }
}

// Create status log table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_request_status_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        technician_id INT,
        old_status VARCHAR(50),
        new_status VARCHAR(50) NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_request_id (request_id),
        INDEX idx_created_at (created_at)
    )");
} catch (PDOException $e) {
    // Ignore table creation errors
}
?>