<?php
// This script is meant to be called via AJAX or as a background process
// It automatically assigns technicians to emergency service requests based on location and availability

include('../includes/db.php');

// Function to calculate distance between two points using Haversine formula
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the earth in km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c; // Distance in km
    
    return $distance;
}

// Get pending emergency service requests
try {
    $stmt = $pdo->query("SELECT * FROM service_requests WHERE status = 'pending' AND is_emergency = 1 AND technician_id IS NULL");
    $emergency_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching emergency requests: " . $e->getMessage());
}

// Process each emergency request
foreach ($emergency_requests as $request) {
    // Skip if no location data
    if (empty($request['latitude']) || empty($request['longitude'])) {
        continue;
    }
    
    // Get available technicians
    try {
        // Map service types to specializations
        $specialization_map = [
            'phone' => 'Phone Repair',
            'computer' => 'Computer Repair',
            'car' => 'Vehicle Repair',
            'plumber' => 'Plumbing',
            'electrician' => 'Electrical',
            'house' => 'General Maintenance'
        ];
        
        $specialization = isset($specialization_map[$request['service_type']]) ? $specialization_map[$request['service_type']] : null;
        
        // Query to find technicians
        $query = "SELECT * FROM technicians WHERE status = 'available'";
        $params = [];
        
        // Filter by specialization if available
        if ($specialization) {
            $query .= " AND specialization = ?";
            $params[] = $specialization;
        }
        
        // Extract region from address (simple approach)
        $region = '';
        foreach (['Lusaka', 'Copperbelt', 'Central', 'Eastern', 'Luapula', 'Muchinga', 'Northern', 'North-Western', 'Southern', 'Western'] as $r) {
            if (stripos($request['address'], $r) !== false) {
                $region = $r;
                break;
            }
        }
        
        // Filter by region if we could determine it
        if (!empty($region)) {
            $query .= " AND (regions LIKE ? OR regions LIKE ? OR regions LIKE ?)";
            $params[] = $region;
            $params[] = $region . ',%';
            $params[] = '%,' . $region . ',%';
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $available_technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Find the closest technician
        $closest_technician = null;
        $min_distance = PHP_FLOAT_MAX;
        
        foreach ($available_technicians as $technician) {
            // Skip if technician has no location data
            if (empty($technician['latitude']) || empty($technician['longitude'])) {
                continue;
            }
            
            // Calculate distance
            $distance = calculateDistance(
                $request['latitude'], 
                $request['longitude'], 
                $technician['latitude'], 
                $technician['longitude']
            );
            
            // Update closest technician if this one is closer
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $closest_technician = $technician;
            }
        }
        
        // Assign the closest technician if found and within 50km
        if ($closest_technician && $min_distance <= 50) {
            // Update service request with technician ID and change status to 'assigned'
            $stmt = $pdo->prepare("UPDATE service_requests SET technician_id = ?, status = 'assigned', assigned_at = NOW() WHERE id = ?");
            $stmt->execute([$closest_technician['id'], $request['id']]);
            
            // Update technician status to busy
            $stmt = $pdo->prepare("UPDATE technicians SET status = 'busy' WHERE id = ?");
            $stmt->execute([$closest_technician['id']]);
            
            // Create notification for customer
            try {
                $notification_query = "INSERT INTO notifications (type, message, is_read, created_at) 
                                      VALUES ('technician_assigned', :message, 0, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'message' => "Technician {$closest_technician['name']} has been automatically assigned to your emergency request ({$request['reference_number']}). They will contact you shortly."
                ]);
            } catch (PDOException $e) {
                // Notifications table might not exist yet, ignore error
            }
            
            // Create notification for technician
            try {
                $notification_query = "INSERT INTO notifications (type, message, is_read, created_at, user_id) 
                                      VALUES ('emergency_assignment', :message, 0, NOW(), :user_id)";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'message' => "EMERGENCY: You have been assigned to service request {$request['reference_number']} at {$request['address']}. Customer: {$request['name']} - {$request['phone']}",
                    'user_id' => $closest_technician['id']
                ]);
            } catch (PDOException $e) {
                // Notifications table might not exist yet, ignore error
            }
            
            // Log the assignment
            error_log("Auto-assigned technician {$closest_technician['name']} (ID: {$closest_technician['id']}) to emergency request {$request['reference_number']} (ID: {$request['id']}). Distance: {$min_distance}km");
        } else {
            // Log that no suitable technician was found
            error_log("No suitable technician found for emergency request {$request['reference_number']} (ID: {$request['id']})");
        }
    } catch (PDOException $e) {
        error_log("Error processing emergency request {$request['id']}: " . $e->getMessage());
    }
}

// Return success response if called via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'processed' => count($emergency_requests)]);
}
?>