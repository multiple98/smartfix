<?php
// Service Tracking Widget
// This file is included in the dashboard to display service tracking functionality

// Get user's service requests with reference numbers if not already fetched
if (!isset($service_requests)) {
    $service_query = "SELECT * FROM service_requests WHERE email = ? OR contact = ? ORDER BY request_date DESC";
    $stmt = $pdo->prepare($service_query);
    $stmt->execute([$user_email, $user_email]);
    $service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get the latest update for each service request if not already fetched
if (!isset($service_updates)) {
    $service_updates = [];
    if (count($service_requests) > 0) {
        try {
            // Create a comma-separated list of request IDs
            $request_ids = array_map(function($req) { return $req['id']; }, $service_requests);
            $id_list = implode(',', $request_ids);
            
            if (!empty($id_list)) {
                // Get the latest update for each service request
                $updates_query = "SELECT su.* FROM service_updates su
                                INNER JOIN (
                                    SELECT service_request_id, MAX(created_at) as latest_date
                                    FROM service_updates
                                    WHERE service_request_id IN ($id_list)
                                    GROUP BY service_request_id
                                ) latest ON su.service_request_id = latest.service_request_id AND su.created_at = latest.latest_date";
                $updates_stmt = $pdo->query($updates_query);
                
                if ($updates_stmt) {
                    $updates = $updates_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Index updates by service_request_id for easy access
                    foreach ($updates as $update) {
                        $service_updates[$update['service_request_id']] = $update;
                    }
                }
            }
        } catch (PDOException $e) {
            // Silently fail if the service_updates table doesn't exist yet
        }
    }
}
?>

<!-- Service Tracking Section -->
<div class="section-title">
    <h2>My Service Requests</h2>
    <a href="services/track_service.php" class="view-all">Track Any Service <i class="fas fa-arrow-right"></i></a>
</div>

<?php if (count($service_requests) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Reference #</th>
                    <th>Service Type</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Latest Update</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Display only the 5 most recent requests
                $recent_requests = array_slice($service_requests, 0, 5);
                foreach ($recent_requests as $request): 
                    // Get status info for styling
                    $status = $request['status'] ?? 'pending';
                    
                    // Get the latest update text if available
                    $latest_update = isset($service_updates[$request['id']]) 
                        ? $service_updates[$request['id']]['update_text'] 
                        : 'No updates yet';
                    
                    // Truncate update text if too long
                    if (strlen($latest_update) > 50) {
                        $latest_update = substr($latest_update, 0, 50) . '...';
                    }
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['reference_number'] ?? ('SF' . str_pad($request['id'], 6, '0', STR_PAD_LEFT))); ?></td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <?php 
                                // Display icon based on service type
                                $icon = 'fas fa-tools';
                                switch(strtolower($request['service_type'])) {
                                    case 'phone': $icon = 'fas fa-mobile-alt'; break;
                                    case 'computer': $icon = 'fas fa-laptop'; break;
                                    case 'car': $icon = 'fas fa-car'; break;
                                    case 'house': $icon = 'fas fa-home'; break;
                                    case 'plumber': $icon = 'fas fa-wrench'; break;
                                    case 'electrician': $icon = 'fas fa-bolt'; break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>" style="margin-right: 8px; color: var(--primary-color);"></i>
                                <?php echo htmlspecialchars(ucfirst($request['service_type'])); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?></td>
                        <td><span class="status status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                        <td>
                            <div style="max-width: 200px; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($latest_update); ?>
                            </div>
                        </td>
                        <td>
                            <a href="services/track_service.php?id=<?php echo $request['id']; ?>" class="action-btn view-btn">
                                <i class="fas fa-search-location"></i> Track
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Service Tracking Card -->
    <div style="background-color: #f8f9fa; border-radius: 12px; padding: 20px; margin-top: 20px; border-left: 4px solid var(--primary-color);">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <i class="fas fa-info-circle" style="font-size: 24px; color: var(--primary-color); margin-right: 15px;"></i>
            <h3 style="margin: 0; color: var(--dark-color);">Track Any Service Request</h3>
        </div>
        <p style="margin-bottom: 15px;">You can track the status of any service request using its reference number, even if you're not logged in.</p>
        <form action="services/track_service.php" method="POST" style="display: flex; gap: 10px;">
            <input type="text" name="reference_number" placeholder="Enter reference number (e.g., SF000001)" 
                   style="flex: 1; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px;">
            <button type="submit" name="track_submit" class="btn btn-primary" style="white-space: nowrap;">
                <i class="fas fa-search"></i> Track Service
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h3>No Service Requests Yet</h3>
        <p>You haven't submitted any service requests yet. Need help with something?</p>
        <a href="services.php" class="btn btn-primary">Request a Service</a>
        
        <!-- Service Tracking Card for users with no requests -->
        <div style="background-color: #f8f9fa; border-radius: 12px; padding: 20px; margin-top: 30px; border-left: 4px solid var(--primary-color); text-align: left;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <i class="fas fa-info-circle" style="font-size: 24px; color: var(--primary-color); margin-right: 15px;"></i>
                <h3 style="margin: 0; color: var(--dark-color);">Track Any Service Request</h3>
            </div>
            <p style="margin-bottom: 15px;">If you have a reference number, you can track the status of any service request, even if you're not logged in.</p>
            <form action="services/track_service.php" method="POST" style="display: flex; gap: 10px;">
                <input type="text" name="reference_number" placeholder="Enter reference number (e.g., SF000001)" 
                       style="flex: 1; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px;">
                <button type="submit" name="track_submit" class="btn btn-primary" style="white-space: nowrap;">
                    <i class="fas fa-search"></i> Track Service
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>