<?php
session_start();
include('../includes/db.php');
require_once('../includes/GPSManager.php');

// For demo purposes, we'll create a simple technician session
if (!isset($_SESSION['technician_id'])) {
    $_SESSION['technician_id'] = 1; // Demo technician ID
    $_SESSION['technician_name'] = 'Demo Technician';
}

$technician_id = $_SESSION['technician_id'];
$gps = new GPSManager($pdo);

// Handle location update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_location'])) {
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $accuracy = floatval($_POST['accuracy']);
    
    if ($latitude && $longitude) {
        $success = $gps->updateTechnicianLocation($technician_id, $latitude, $longitude, $accuracy);
        
        if ($success) {
            $response = ['success' => true, 'message' => 'Location updated successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to update location'];
        }
        
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

// Get technician info and current location
try {
    $techInfo = $pdo->prepare("
        SELECT t.*, tl.latitude, tl.longitude, tl.last_updated,
               CASE 
                   WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                   WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                   ELSE 'offline'
               END AS location_status
        FROM technicians t
        LEFT JOIN technician_locations tl ON t.id = tl.technician_id
        WHERE t.id = ?
    ");
    $techInfo->execute([$technician_id]);
    $technician = $techInfo->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Create sample technician if doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        specialization TEXT,
        rating DECIMAL(3,2) DEFAULT 5.00,
        status ENUM('active', 'inactive', 'busy') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO technicians (id, name, email, phone, specialization) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Demo Technician', 'demo@smartfix.com', '+250123456789', 'phone,computer']);
    
    $technician = [
        'id' => 1,
        'name' => 'Demo Technician',
        'email' => 'demo@smartfix.com',
        'phone' => '+250123456789',
        'specialization' => 'phone,computer',
        'rating' => 5.0,
        'status' => 'active',
        'latitude' => null,
        'longitude' => null,
        'last_updated' => null,
        'location_status' => 'offline'
    ];
}

// Get assigned service requests
try {
    $assignedRequests = $pdo->prepare("
        SELECT sr.*, sl.latitude as customer_lat, sl.longitude as customer_lng
        FROM service_requests sr
        LEFT JOIN service_locations sl ON sr.id = sl.request_id AND sl.location_type = 'customer'
        WHERE sr.technician_id = ? AND sr.status IN ('assigned', 'in_progress')
        ORDER BY sr.created_at DESC
    ");
    $assignedRequests->execute([$technician_id]);
    $requests = $assignedRequests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Tracker - SmartFix Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: #28a745;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 20px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-online { background: #28a745; }
        .status-recently_active { background: #ffc107; }
        .status-offline { background: #6c757d; }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
        }

        .card-content {
            padding: 20px;
        }

        .location-controls {
            text-align: center;
        }

        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }

        .btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }

        .location-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007BFF;
            margin: 15px 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .requests-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .request-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .request-card:hover {
            background: #e9ecef;
            border-color: #28a745;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .request-ref {
            font-weight: bold;
            color: #007BFF;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-assigned {
            background: #fff3cd;
            color: #856404;
        }

        .status-in_progress {
            background: #d4edda;
            color: #155724;
        }

        .request-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .distance-info {
            color: #28a745;
            font-weight: 600;
            margin-top: 10px;
        }

        .auto-tracking {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
            text-align: center;
        }

        .tracking-status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }

        .tracking-status.active {
            background: #d4edda;
            color: #155724;
        }

        .tracking-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-map-marker-alt"></i> Location Tracker</h1>
        <div class="status-indicator">
            <div class="status-dot status-<?php echo $technician['location_status']; ?>"></div>
            <span id="connection-status"><?php echo ucfirst(str_replace('_', ' ', $technician['location_status'])); ?></span>
        </div>
    </div>

    <div class="container">
        <div class="auto-tracking">
            <h3><i class="fas fa-satellite-dish"></i> Auto Location Tracking</h3>
            <p>Keep your location updated automatically to receive nearby service requests.</p>
            <div class="tracking-status" id="tracking-status">
                <i class="fas fa-circle"></i> Tracking Inactive
            </div>
            <button id="start-tracking" class="btn" onclick="startAutoTracking()">
                <i class="fas fa-play"></i> Start Auto Tracking
            </button>
            <button id="stop-tracking" class="btn btn-danger" onclick="stopAutoTracking()" disabled>
                <i class="fas fa-stop"></i> Stop Tracking
            </button>
        </div>

        <div class="main-grid">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-map"></i> Your Location & Service Area
                </div>
                <div class="card-content">
                    <div class="location-controls">
                        <button class="btn" onclick="updateLocation()">
                            <i class="fas fa-crosshairs"></i> Update Current Location
                        </button>
                        <button class="btn btn-secondary" onclick="centerMap()">
                            <i class="fas fa-home"></i> Center Map
                        </button>
                    </div>

                    <div class="location-info" id="location-info" style="display: none;">
                        <h4><i class="fas fa-info-circle"></i> Location Information</h4>
                        <div id="location-details"></div>
                    </div>

                    <div id="map"></div>

                    <div style="margin-top: 20px;">
                        <h4>Technician Information</h4>
                        <div class="info-item">
                            <span><strong>Name:</strong></span>
                            <span><?php echo htmlspecialchars($technician['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span><strong>Specialization:</strong></span>
                            <span><?php echo htmlspecialchars($technician['specialization']); ?></span>
                        </div>
                        <div class="info-item">
                            <span><strong>Rating:</strong></span>
                            <span><?php echo str_repeat('⭐', round($technician['rating'])); ?> (<?php echo $technician['rating']; ?>)</span>
                        </div>
                        <div class="info-item">
                            <span><strong>Last Update:</strong></span>
                            <span id="last-update"><?php echo $technician['last_updated'] ? date('Y-m-d H:i:s', strtotime($technician['last_updated'])) : 'Never'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clipboard-list"></i> Assigned Requests
                    <span style="float: right; background: #007BFF; color: white; padding: 2px 8px; border-radius: 10px;">
                        <?php echo count($requests); ?>
                    </span>
                </div>
                <div class="card-content">
                    <div class="requests-list">
                        <?php if (empty($requests)): ?>
                            <div style="text-align: center; color: #666; padding: 20px;">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i>
                                <p>No assigned requests at the moment.</p>
                                <p>Keep your location updated to receive nearby service requests!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <div class="request-card" onclick="navigateToCustomer(<?php echo $request['customer_lat'] ?: 0; ?>, <?php echo $request['customer_lng'] ?: 0; ?>, '<?php echo addslashes($request['name']); ?>')">
                                    <div class="request-header">
                                        <span class="request-ref"><?php echo $request['reference_number'] ?: 'REQ-' . $request['id']; ?></span>
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="request-info">
                                        <p><strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($request['name']); ?></strong></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?></p>
                                        <p><i class="fas fa-wrench"></i> <?php echo ucfirst($request['service_type']); ?> - <?php echo htmlspecialchars($request['service_option'] ?: 'General'); ?></p>
                                        <p><i class="fas fa-calendar"></i> <?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></p>
                                        
                                        <?php if ($request['customer_lat'] && $request['customer_lng'] && $technician['latitude'] && $technician['longitude']): ?>
                                            <?php 
                                            $distance = $gps->calculateDistance(
                                                $technician['latitude'], $technician['longitude'],
                                                $request['customer_lat'], $request['customer_lng']
                                            );
                                            ?>
                                            <div class="distance-info">
                                                <i class="fas fa-route"></i> ~<?php echo number_format($distance, 1); ?> km away
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Google Maps API -->
    <?php 
    require_once('../config/maps_config.php');
    if (isGoogleMapsConfigured()): 
    ?>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo getGoogleMapsApiKey(); ?>&callback=initMap"></script>
    <?php else: ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('map').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #666; text-align: center; flex-direction: column;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></i>
                        <h3>Google Maps API Not Configured</h3>
                        <p>Please contact your administrator to set up GPS features.</p>
                        <a href="../test_gps_setup.php" style="background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">Check GPS Setup</a>
                    </div>
                `;
            });
        </script>
    <?php endif; ?>

    <script>
        let map;
        let currentLocationMarker;
        let trackingInterval;
        let isTracking = false;
        let currentPosition = null;
        
        // Initialize map
        function initMap() {
            const kigali = { lat: -1.9441, lng: 30.0619 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: kigali,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            // Load current technician location if available
            <?php if ($technician['latitude'] && $technician['longitude']): ?>
                const currentLat = <?php echo $technician['latitude']; ?>;
                const currentLng = <?php echo $technician['longitude']; ?>;
                showCurrentLocation(currentLat, currentLng, true);
            <?php endif; ?>

            // Load customer locations
            loadCustomerLocations();
        }

        // Update location manually
        function updateLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        currentPosition = { lat, lng, accuracy };
                        showCurrentLocation(lat, lng);
                        sendLocationUpdate(lat, lng, accuracy);
                    },
                    (error) => {
                        alert('Error getting location: ' + error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser');
            }
        }

        // Show current location on map
        function showCurrentLocation(lat, lng, fromDatabase = false) {
            const position = { lat: lat, lng: lng };
            
            if (currentLocationMarker) {
                currentLocationMarker.setMap(null);
            }
            
            currentLocationMarker = new google.maps.Marker({
                position: position,
                map: map,
                title: 'Your Current Location',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: '#28a745',
                    fillOpacity: 1,
                    strokeColor: 'white',
                    strokeWeight: 3
                },
                animation: google.maps.Animation.DROP
            });
            
            map.setCenter(position);
            map.setZoom(15);
            
            // Update location info
            const locationInfo = document.getElementById('location-info');
            const locationDetails = document.getElementById('location-details');
            
            locationDetails.innerHTML = `
                <div class="info-item">
                    <span><strong>Latitude:</strong></span>
                    <span>${lat.toFixed(6)}</span>
                </div>
                <div class="info-item">
                    <span><strong>Longitude:</strong></span>
                    <span>${lng.toFixed(6)}</span>
                </div>
                <div class="info-item">
                    <span><strong>Source:</strong></span>
                    <span>${fromDatabase ? 'Database' : 'GPS Device'}</span>
                </div>
                <div class="info-item">
                    <span><strong>Updated:</strong></span>
                    <span>${new Date().toLocaleTimeString()}</span>
                </div>
            `;
            
            locationInfo.style.display = 'block';
        }

        // Send location update to server
        async function sendLocationUpdate(lat, lng, accuracy) {
            try {
                const formData = new FormData();
                formData.append('update_location', '1');
                formData.append('latitude', lat);
                formData.append('longitude', lng);
                formData.append('accuracy', accuracy);
                formData.append('ajax', '1');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('last-update').textContent = new Date().toLocaleString();
                    updateConnectionStatus('online');
                } else {
                    console.error('Failed to update location:', result.message);
                }
                
            } catch (error) {
                console.error('Error updating location:', error);
            }
        }

        // Auto tracking functions
        function startAutoTracking() {
            if (isTracking) return;
            
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser');
                return;
            }
            
            isTracking = true;
            document.getElementById('start-tracking').disabled = true;
            document.getElementById('stop-tracking').disabled = false;
            
            const trackingStatus = document.getElementById('tracking-status');
            trackingStatus.innerHTML = '<i class="fas fa-satellite"></i> Tracking Active';
            trackingStatus.className = 'tracking-status active';
            
            // Update location immediately
            updateLocation();
            
            // Set up interval for continuous tracking (every 2 minutes)
            trackingInterval = setInterval(() => {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        currentPosition = { lat, lng, accuracy };
                        showCurrentLocation(lat, lng);
                        sendLocationUpdate(lat, lng, accuracy);
                    },
                    (error) => {
                        console.error('Auto tracking error:', error);
                        updateConnectionStatus('offline');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }, 120000); // 2 minutes
        }

        function stopAutoTracking() {
            if (!isTracking) return;
            
            isTracking = false;
            clearInterval(trackingInterval);
            
            document.getElementById('start-tracking').disabled = false;
            document.getElementById('stop-tracking').disabled = true;
            
            const trackingStatus = document.getElementById('tracking-status');
            trackingStatus.innerHTML = '<i class="fas fa-circle"></i> Tracking Inactive';
            trackingStatus.className = 'tracking-status inactive';
            
            updateConnectionStatus('recently_active');
        }

        // Update connection status
        function updateConnectionStatus(status) {
            const statusElement = document.getElementById('connection-status');
            const statusDot = document.querySelector('.status-dot');
            
            statusElement.textContent = status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1);
            statusDot.className = `status-dot status-${status}`;
        }

        // Center map on current location
        function centerMap() {
            if (currentPosition) {
                map.setCenter({ lat: currentPosition.lat, lng: currentPosition.lng });
                map.setZoom(15);
            } else {
                map.setCenter({ lat: -1.9441, lng: 30.0619 }); // Kigali
                map.setZoom(13);
            }
        }

        // Navigate to customer location
        function navigateToCustomer(lat, lng, customerName) {
            if (lat && lng) {
                const customerPosition = { lat: parseFloat(lat), lng: parseFloat(lng) };
                
                // Add customer marker
                const customerMarker = new google.maps.Marker({
                    position: customerPosition,
                    map: map,
                    title: `Customer: ${customerName}`,
                    icon: {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 8,
                        fillColor: '#dc3545',
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 2
                    }
                });
                
                // Center map between technician and customer
                if (currentPosition) {
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(customerPosition);
                    bounds.extend(currentPosition);
                    map.fitBounds(bounds);
                } else {
                    map.setCenter(customerPosition);
                    map.setZoom(15);
                }
                
                // Open navigation in external app
                const navigationUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
                if (confirm(`Navigate to ${customerName}?\nThis will open Google Maps.`)) {
                    window.open(navigationUrl, '_blank');
                }
            }
        }

        // Load customer locations on map
        function loadCustomerLocations() {
            const requests = <?php echo json_encode($requests); ?>;
            
            requests.forEach(request => {
                if (request.customer_lat && request.customer_lng) {
                    const marker = new google.maps.Marker({
                        position: { 
                            lat: parseFloat(request.customer_lat), 
                            lng: parseFloat(request.customer_lng) 
                        },
                        map: map,
                        title: `Customer: ${request.name}`,
                        icon: {
                            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                            scale: 6,
                            fillColor: '#007BFF',
                            fillOpacity: 1,
                            strokeColor: 'white',
                            strokeWeight: 2
                        }
                    });
                    
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h4>${request.reference_number || 'REQ-' + request.id}</h4>
                                <p><strong>Customer:</strong> ${request.name}</p>
                                <p><strong>Service:</strong> ${request.service_type}</p>
                                <p><strong>Status:</strong> ${request.status}</p>
                                <button onclick="navigateToCustomer(${request.customer_lat}, ${request.customer_lng}, '${request.name}')" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-top: 5px;">Navigate</button>
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });
                }
            });
        }

        // Auto-start tracking on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user previously had tracking enabled
            if (localStorage.getItem('autoTrackingEnabled') === 'true') {
                setTimeout(startAutoTracking, 2000);
            }
        });

        // Save tracking preference
        function startAutoTracking() {
            localStorage.setItem('autoTrackingEnabled', 'true');
            // ... rest of the startAutoTracking function
        }

        function stopAutoTracking() {
            localStorage.setItem('autoTrackingEnabled', 'false');
            // ... rest of the stopAutoTracking function
        }
    </script>
</body>
</html>