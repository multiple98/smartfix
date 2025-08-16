<?php
/**
 * Real-time Delivery Tracking API
 * Provides live tracking updates for deliveries
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

switch ($method) {
    case 'GET':
        if ($order_id) {
            getDeliveryTracking($order_id);
        } else {
            getAllActiveDeliveries();
        }
        break;
        
    case 'POST':
        updateDeliveryLocation();
        break;
        
    case 'PUT':
        updateDeliveryStatus();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

/**
 * Get delivery tracking information for a specific order
 */
function getDeliveryTracking($order_id) {
    global $pdo;
    
    try {
        $query = "SELECT dt.*, tp.name as provider_name, tp.contact as provider_contact,
                         o.tracking_number, o.customer_name, o.delivery_address,
                         TIMESTAMPDIFF(MINUTE, dt.updated_at, NOW()) as minutes_since_update
                  FROM delivery_tracking dt
                  LEFT JOIN transport_providers tp ON dt.transport_provider_id = tp.id
                  LEFT JOIN orders o ON dt.order_id = o.id
                  WHERE dt.order_id = ?
                  ORDER BY dt.updated_at DESC
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$order_id]);
        $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tracking) {
            // Get tracking history
            $history_query = "SELECT * FROM delivery_tracking_history 
                             WHERE order_id = ? 
                             ORDER BY created_at ASC";
            $history_stmt = $pdo->prepare($history_query);
            $history_stmt->execute([$order_id]);
            $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'tracking' => $tracking,
                'history' => $history,
                'is_live' => $tracking['minutes_since_update'] < 15
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No tracking information found for this order'
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get all active deliveries for admin dashboard
 */
function getAllActiveDeliveries() {
    global $pdo;
    
    try {
        $query = "SELECT dt.*, tp.name as provider_name, o.tracking_number, o.customer_name,
                         TIMESTAMPDIFF(MINUTE, dt.updated_at, NOW()) as minutes_since_update
                  FROM delivery_tracking dt
                  LEFT JOIN transport_providers tp ON dt.transport_provider_id = tp.id
                  LEFT JOIN orders o ON dt.order_id = o.id
                  WHERE dt.status NOT IN ('delivered', 'failed_delivery')
                  ORDER BY dt.updated_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'deliveries' => $deliveries,
            'count' => count($deliveries)
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update delivery location (from driver's mobile app)
 */
function updateDeliveryLocation() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $order_id = $input['order_id'] ?? null;
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $driver_notes = $input['notes'] ?? '';
    
    if (!$order_id || !$latitude || !$longitude) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
        return;
    }
    
    try {
        // Update current location
        $update_query = "UPDATE delivery_tracking 
                        SET current_latitude = ?, current_longitude = ?, 
                            delivery_notes = ?, updated_at = NOW()
                        WHERE order_id = ?";
        
        $stmt = $pdo->prepare($update_query);
        $stmt->execute([$latitude, $longitude, $driver_notes, $order_id]);
        
        // Add to tracking history
        $history_query = "INSERT INTO delivery_tracking_history 
                         (order_id, latitude, longitude, status, notes, created_at)
                         VALUES (?, ?, ?, 'location_update', ?, NOW())";
        
        $history_stmt = $pdo->prepare($history_query);
        $history_stmt->execute([$order_id, $latitude, $longitude, $driver_notes]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update delivery status
 */
function updateDeliveryStatus() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $order_id = $input['order_id'] ?? null;
    $status = $input['status'] ?? null;
    $notes = $input['notes'] ?? '';
    $proof_of_delivery = $input['proof_of_delivery'] ?? null;
    
    if (!$order_id || !$status) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
        return;
    }
    
    $valid_statuses = ['pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status'
        ]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update delivery tracking
        $update_fields = ['status = ?', 'delivery_notes = ?', 'updated_at = NOW()'];
        $params = [$status, $notes];
        
        if ($status === 'delivered') {
            $update_fields[] = 'actual_delivery_time = NOW()';
            if ($proof_of_delivery) {
                $update_fields[] = 'proof_of_delivery = ?';
                $params[] = $proof_of_delivery;
            }
        }
        
        $params[] = $order_id;
        
        $update_query = "UPDATE delivery_tracking SET " . implode(', ', $update_fields) . " WHERE order_id = ?";
        $stmt = $pdo->prepare($update_query);
        $stmt->execute($params);
        
        // Add to tracking history
        $history_query = "INSERT INTO delivery_tracking_history 
                         (order_id, status, notes, created_at)
                         VALUES (?, ?, ?, NOW())";
        
        $history_stmt = $pdo->prepare($history_query);
        $history_stmt->execute([$order_id, $status, $notes]);
        
        // Update order status if delivered
        if ($status === 'delivered') {
            $order_update = "UPDATE orders SET status = 'delivered', delivered_at = NOW() WHERE id = ?";
            $order_stmt = $pdo->prepare($order_update);
            $order_stmt->execute([$order_id]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Create tracking history table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_tracking_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        status VARCHAR(50) NOT NULL,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_created_at (created_at)
    )");
} catch (PDOException $e) {
    // Ignore table creation errors
}
?>