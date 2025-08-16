<?php
session_start();
include('../includes/db.php');
require_once('../includes/GPSManager.php');

// Check if technician is logged in
if (!isset($_SESSION['technician_id'])) {
    header("Location: login.php");
    exit();
}

$technician_id = $_SESSION['technician_id'];
$gps = new GPSManager($pdo);

// Get technician info
$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
$stmt->execute([$technician_id]);
$technician = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current location if available
$stmt = $pdo->prepare("SELECT * FROM technician_locations WHERE technician_id = ?");
$stmt->execute([$technician_id]);
$current_location = $stmt->fetch(PDO::FETCH_ASSOC);

// Get assigned service requests
$stmt = $pdo->prepare("
    SELECT sr.*, sl.latitude as customer_lat, sl.longitude as customer_lng, sl.address as customer_address
    FROM service_requests sr
    LEFT JOIN service_locations sl ON sr.id = sl.request_id AND sl.location_type = 'customer'
    WHERE sr.technician_id = ? AND sr.status IN ('assigned', 'in_progress')
    ORDER BY sr.created_at ASC
");
$stmt->execute([$technician_id]);
$service_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Tracker - SmartFix Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #007BFF;
            font-size: 1.5rem;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(40, 167, 69, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            border: 2px solid #28a745;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .map-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .map-header {
            background: #007BFF;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .location-controls {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary { background: #007BFF; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-light { background: rgba(255, 255, 255, 0.2); color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        #technician-map {
            height: 500px;
            width: 100%;
        }

        .info-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .panel-header {
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: bold;
        }

        .panel-content {
            padding: 1.5rem;
        }

        .location-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .service-requests {
            margin-top: 1.5rem;
        }

        .request-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .request-item:hover {
            background: rgba(0, 123, 255, 0.05);
            border-color: #007BFF;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .request-id {
            font-weight: bold;
            color: #007BFF;
        }

        .request-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-assigned { background: #ffc107; color: #333; }
        .status-in_progress { background: #17a2b8; color: white; }

        .request-details {
            font-size: 0.9rem;
            color: #666;
        }

        .request-actions {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }

        .tracking-controls {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: center;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .control-label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #28a745;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .accuracy-display {
            background: rgba(23, 162, 184, 0.1);
            border: 1px solid #17a2b8;
            border-radius: 6px;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: #17a2b8;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .controls-grid {
                grid-template-columns: 1fr;
            }
            
            #technician-map {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-map-marked-alt"></i> GPS Tracker</h1>
        <div class="status-indicator">
            <div class="status-dot"></div>
            <span id="tracking-status">Location Tracking Active</span>
        </div>
    </div>

    <div class="container">
        <!-- Tracking Controls -->
        <div class="tracking-controls">
            <div class="controls-grid">
                <div class="control-group">
                    <label class="control-label">Location Sharing</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="location-toggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Auto-Update</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="auto-update-toggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Current Accuracy</label>
                    <div class="accuracy-display" id="accuracy-display">
                        Detecting...
                    </div>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Manual Update</label>
                    <button class="btn btn-primary" onclick="updateLocation()">
                        <i class="fas fa-sync-alt"></i> Update Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="dashboard-grid">
            <!-- Map Section -->
            <div class="map-section">
                <div class="map-header">
                    <h3><i class="fas fa-map"></i> Your Location & Service Areas</h3>
                    <div class="location-controls">
                        <button class="btn btn-light" onclick="centerOnMe()">
                            <i class="fas fa-crosshairs"></i> Center on Me
                        </button>
                        <button class="btn btn-light" onclick="showAllRequests()">
                            <i class="fas fa-eye"></i> Show All
                        </button>
                    </div>
                </div>
                <div id="technician-map"></div>
            </div>

            <!-- Info Panel -->
            <div class="info-panel">
                <div class="panel-header">
                    <i class="fas fa-info-circle"></i> Location Information
                </div>
                <div class="panel-content">
                    <div class="location-info">
                        <div class="info-row">
                            <span class="info-label">Technician:</span>
                            <span class="info-value"><?php echo htmlspecialchars($technician['name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Specialization:</span>
                            <span class="info-value"><?php echo htmlspecialchars($technician['specialization']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value"><?php echo ucfirst($technician['status']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value" id="last-updated">
                                <?php echo $current_location ? date('M j, Y g:i A', strtotime($current_location['last_updated'])) : 'Never'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Coordinates:</span>
                            <span class="info-value" id="coordinates">
                                <?php 
                                if ($current_location) {
                                    echo number_format($current_location['latitude'], 6) . ', ' . number_format($current_location['longitude'], 6);
                                } else {
                                    echo 'Not available';
                                }
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="service-requests">
                        <h4><i class="fas fa-clipboard-list"></i> Assigned Service Requests</h4>
                        <?php if (empty($service_requests)): ?>
                            <p style="text-align: center; color: #666; margin: 1rem 0;">No active service requests</p>
                        <?php else: ?>
                            <?php foreach ($service_requests as $request): ?>
                                <div class="request-item" onclick="navigateToRequest(<?php echo $request['id']; ?>, <?php echo $request['customer_lat'] ?? 'null'; ?>, <?php echo $request['customer_lng'] ?? 'null'; ?>)">
                                    <div class="request-header">
                                        <span class="request-id">Request #<?php echo $request['id']; ?></span>
                                        <span class="request-status status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="request-details">
                                        <strong><?php echo htmlspecialchars($request['service_type']); ?></strong><br>
                                        <?php echo htmlspecialchars($request['customer_address'] ?? $request['address']); ?><br>
                                        <small>Created: <?php echo date('M j, g:i A', strtotime($request['created_at'])); ?></small>
                                    </div>
                                    <div class="request-actions">
                                        <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); navigateToRequest(<?php echo $request['id']; ?>, <?php echo $request['customer_lat'] ?? 'null'; ?>, <?php echo $request['customer_lng'] ?? 'null'; ?>)">
                                            <i class="fas fa-directions"></i> Navigate
                                        </button>
                                        <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); updateRequestStatus(<?php echo $request['id']; ?>, 'in_progress')">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBOti4mM-6x9WDnZIjIeyb7TlR-2K7_BDc&libraries=geometry"></script>
    
    <script>
        let map;
        let myLocationMarker;
        let serviceRequestMarkers = [];
        let watchId = null;
        let isTrackingEnabled = true;
        let autoUpdateEnabled = true;
        
        const technicianId = <?php echo $technician_id; ?>;
        const serviceRequests = <?php echo json_encode($service_requests); ?>;
        
        // Initialize map
        function initMap() {
            // Default center (Lusaka)
            const defaultCenter = { lat: -15.3875, lng: 28.3228 };
            
            // Use current location if available
            const currentLocation = <?php echo $current_location ? json_encode(['lat' => floatval($current_location['latitude']), 'lng' => floatval($current_location['longitude'])]) : 'null'; ?>;
            const center = currentLocation || defaultCenter;
            
            map = new google.maps.Map(document.getElementById('technician-map'), {
                zoom: 15,
                center: center,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });
            
            // Add service request markers
            addServiceRequestMarkers();
            
            // Start location tracking
            if (isTrackingEnabled) {
                startLocationTracking();
            }
        }
        
        // Start location tracking
        function startLocationTracking() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser.');
                return;
            }
            
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000 // 1 minute
            };
            
            // Get initial position
            navigator.geolocation.getCurrentPosition(
                updatePosition,
                handleLocationError,
                options
            );
            
            // Watch position changes
            if (autoUpdateEnabled) {
                watchId = navigator.geolocation.watchPosition(
                    updatePosition,
                    handleLocationError,
                    options
                );
            }
        }
        
        // Update position
        function updatePosition(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            // Update map marker
            updateMyLocationMarker(lat, lng, accuracy);
            
            // Update server
            if (isTrackingEnabled) {
                sendLocationUpdate(lat, lng, accuracy);
            }
            
            // Update UI
            updateLocationDisplay(lat, lng, accuracy);
        }
        
        // Handle location errors
        function handleLocationError(error) {
            let message = 'Unable to get location: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Location access denied by user.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    message += 'Location request timed out.';
                    break;
                default:
                    message += 'Unknown error occurred.';
                    break;
            }
            console.error(message);
            document.getElementById('tracking-status').textContent = 'Location Error';
        }
        
        // Update my location marker
        function updateMyLocationMarker(lat, lng, accuracy) {
            const position = { lat: lat, lng: lng };
            
            if (myLocationMarker) {
                myLocationMarker.setPosition(position);
            } else {
                myLocationMarker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: 'Your Location',
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                            <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="16" cy="16" r="12" fill="#007BFF" stroke="white" stroke-width="3"/>
                                <circle cx="16" cy="16" r="4" fill="white"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(32, 32),
                        anchor: new google.maps.Point(16, 16)
                    }
                });
                
                // Add accuracy circle
                const accuracyCircle = new google.maps.Circle({
                    strokeColor: '#007BFF',
                    strokeOpacity: 0.3,
                    strokeWeight: 1,
                    fillColor: '#007BFF',
                    fillOpacity: 0.1,
                    map: map,
                    center: position,
                    radius: accuracy
                });
                
                myLocationMarker.accuracyCircle = accuracyCircle;
            }
            
            // Update accuracy circle
            if (myLocationMarker.accuracyCircle) {
                myLocationMarker.accuracyCircle.setCenter(position);
                myLocationMarker.accuracyCircle.setRadius(accuracy);
            }
        }
        
        // Send location update to server
        async function sendLocationUpdate(lat, lng, accuracy) {
            try {
                const response = await fetch('/smartfix/api/update-technician-location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        technician_id: technicianId,
                        latitude: lat,
                        longitude: lng,
                        accuracy: accuracy
                    })
                });
                
                const data = await response.json();
                if (!data.success) {
                    console.error('Failed to update location:', data.error);
                }
            } catch (error) {
                console.error('Error updating location:', error);
            }
        }
        
        // Update location display
        function updateLocationDisplay(lat, lng, accuracy) {
            document.getElementById('coordinates').textContent = 
                lat.toFixed(6) + ', ' + lng.toFixed(6);
            document.getElementById('last-updated').textContent = 
                new Date().toLocaleString();
            document.getElementById('accuracy-display').textContent = 
                '±' + Math.round(accuracy) + 'm';
        }
        
        // Add service request markers
        function addServiceRequestMarkers() {
            serviceRequests.forEach(request => {
                if (request.customer_lat && request.customer_lng) {
                    const marker = new google.maps.Marker({
                        position: {
                            lat: parseFloat(request.customer_lat),
                            lng: parseFloat(request.customer_lng)
                        },
                        map: map,
                        title: `Service Request #${request.id}`,
                        icon: {
                            url: 'data:image/svg+xml;base64,' + btoa(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 2C10.48 2 6 6.48 6 12c0 8.25 10 18 10 18s10-9.75 10-18c0-5.52-4.48-10-10-10z" fill="#dc3545"/>
                                    <circle cx="16" cy="12" r="4" fill="white"/>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 32)
                        }
                    });
                    
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h4>Service Request #${request.id}</h4>
                                <p><strong>Service:</strong> ${request.service_type}</p>
                                <p><strong>Status:</strong> ${request.status.replace('_', ' ')}</p>
                                <p><strong>Address:</strong> ${request.customer_address || request.address}</p>
                                <button onclick="navigateToRequest(${request.id}, ${request.customer_lat}, ${request.customer_lng})" 
                                        style="background: #007BFF; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                    Navigate Here
                                </button>
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        // Close other info windows
                        serviceRequestMarkers.forEach(m => {
                            if (m.infoWindow) m.infoWindow.close();
                        });
                        infoWindow.open(map, marker);
                    });
                    
                    marker.infoWindow = infoWindow;
                    serviceRequestMarkers.push(marker);
                }
            });
        }
        
        // Center map on my location
        function centerOnMe() {
            if (myLocationMarker) {
                map.setCenter(myLocationMarker.getPosition());
                map.setZoom(16);
            } else {
                updateLocation();
            }
        }
        
        // Show all service requests
        function showAllRequests() {
            if (serviceRequestMarkers.length === 0) return;
            
            const bounds = new google.maps.LatLngBounds();
            
            // Include my location
            if (myLocationMarker) {
                bounds.extend(myLocationMarker.getPosition());
            }
            
            // Include all service request markers
            serviceRequestMarkers.forEach(marker => {
                bounds.extend(marker.getPosition());
            });
            
            map.fitBounds(bounds);
        }
        
        // Manual location update
        function updateLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    updatePosition,
                    handleLocationError,
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }
        }
        
        // Navigate to service request
        function navigateToRequest(requestId, lat, lng) {
            if (lat && lng) {
                // Open Google Maps navigation
                const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
                window.open(url, '_blank');
            } else {
                alert('Location not available for this request');
            }
        }
        
        // Update request status
        async function updateRequestStatus(requestId, status) {
            try {
                const response = await fetch('/smartfix/api/update-request-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId,
                        status: status,
                        technician_id: technicianId
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Failed to update request status: ' + data.error);
                }
            } catch (error) {
                console.error('Error updating request status:', error);
                alert('Error updating request status');
            }
        }
        
        // Toggle location tracking
        document.getElementById('location-toggle').addEventListener('change', function() {
            isTrackingEnabled = this.checked;
            
            if (isTrackingEnabled) {
                startLocationTracking();
                document.getElementById('tracking-status').textContent = 'Location Tracking Active';
            } else {
                if (watchId) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                document.getElementById('tracking-status').textContent = 'Location Tracking Disabled';
            }
        });
        
        // Toggle auto-update
        document.getElementById('auto-update-toggle').addEventListener('change', function() {
            autoUpdateEnabled = this.checked;
            
            if (autoUpdateEnabled && isTrackingEnabled) {
                startLocationTracking();
            } else if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
        });
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
        
        // Clean up when page unloads
        window.addEventListener('beforeunload', function() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
    </script>
</body>
</html>