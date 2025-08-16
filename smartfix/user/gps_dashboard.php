<?php
session_start();
include('../includes/db.php');
require_once('../includes/GPSManager.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php?form=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$gps = new GPSManager($pdo);

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

// Get user's service requests with locations
$user_requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT sr.*, sl.latitude, sl.longitude, sl.address as gps_address,
               t.name as technician_name, t.phone as technician_phone,
               tl.latitude as tech_lat, tl.longitude as tech_lng,
               tl.last_updated as tech_last_update
        FROM service_requests sr
        LEFT JOIN service_locations sl ON sr.id = sl.request_id AND sl.location_type = 'customer'
        LEFT JOIN technicians t ON sr.technician_id = t.id
        LEFT JOIN technician_locations tl ON t.id = tl.technician_id
        WHERE sr.user_id = ?
        ORDER BY sr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $user_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Service requests table might not exist
    $user_requests = [];
}

// Get user's orders with delivery locations (if available)
$user_orders = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.*, ol.latitude, ol.longitude, ol.address as delivery_address
        FROM orders o
        LEFT JOIN order_locations ol ON o.id = ol.order_id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $user_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Orders table might not exist
    $user_orders = [];
}

// Get nearby technicians (if user has a location)
$nearby_technicians = [];
$user_location = null;

// Try to get user's last known location from their most recent service request
if (!empty($user_requests)) {
    foreach ($user_requests as $request) {
        if ($request['latitude'] && $request['longitude']) {
            $user_location = [
                'lat' => $request['latitude'],
                'lng' => $request['longitude']
            ];
            break;
        }
    }
}

// If we have user location, find nearby technicians
if ($user_location) {
    $nearby_technicians = $gps->findNearestTechnicians(
        $user_location['lat'], 
        $user_location['lng'], 
        null, 
        25, // 25km radius
        10  // limit to 10 technicians
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Dashboard - SmartFix</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: #667eea;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .map-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .map-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-controls {
            display: flex;
            gap: 10px;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #5a6fd8;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        #user-map {
            height: 600px;
            width: 100%;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .info-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
        }

        .info-content {
            max-height: 400px;
            overflow-y: auto;
        }

        .item {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f1f1;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #667eea;
        }

        .item p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-pending { background: #17a2b8; }
        .status-in_progress { background: #fd7e14; }
        .status-completed { background: #28a745; }
        .status-cancelled { background: #dc3545; }
        .status-online { background: #28a745; }
        .status-recently_active { background: #ffc107; }
        .status-offline { background: #6c757d; }

        .distance-badge {
            background: #e9ecef;
            color: #495057;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .no-data {
            text-align: center;
            color: #666;
            padding: 40px 20px;
        }

        .no-data i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .location-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 12px;
        }

        .technician-item {
            cursor: pointer;
            transition: background 0.3s;
        }

        .technician-item:hover {
            background: #f8f9fa;
        }

        .request-item {
            cursor: pointer;
            transition: background 0.3s;
        }

        .request-item:hover {
            background: #f8f9fa;
        }

        @media (max-width: 1024px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 10px;
            }
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            border-left: 4px solid;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1><i class="fas fa-map-marked-alt"></i> My GPS Dashboard</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="../services/request_service.php"><i class="fas fa-plus"></i> New Request</a>
                <a href="../shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="dashboard-header">
            <h2>Welcome, <?php echo htmlspecialchars($user['full_name'] ?? $user['name']); ?>!</h2>
            <div style="color: #666; font-size: 14px;">
                <i class="fas fa-clock"></i> Last updated: <span id="last-update"><?php echo date('H:i:s'); ?></span>
            </div>
        </div>

        <?php if (empty($user_requests) && empty($user_orders)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Welcome to your GPS Dashboard!</strong> 
            Once you create service requests or place orders, you'll be able to track them here on the map.
            <a href="../services/request_service.php" class="btn" style="margin-left: 15px;">Create Service Request</a>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="value"><?php echo count($user_requests); ?></div>
                <div class="label">Service Requests</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="value"><?php echo count($user_orders); ?></div>
                <div class="label">Orders</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="value"><?php echo count($nearby_technicians); ?></div>
                <div class="label">Nearby Technicians</div>
            </div>

            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="value">
                    <?php 
                    $active_requests = array_filter($user_requests, function($req) {
                        return in_array($req['status'], ['pending', 'in_progress']);
                    });
                    echo count($active_requests);
                    ?>
                </div>
                <div class="label">Active Requests</div>
            </div>
        </div>

        <div class="main-content">
            <!-- Map Section -->
            <div class="map-section">
                <div class="map-header">
                    <h3><i class="fas fa-map"></i> Your Service Map</h3>
                    <div class="map-controls">
                        <button class="btn btn-secondary" onclick="centerOnUser()">
                            <i class="fas fa-crosshairs"></i> Center on Me
                        </button>
                        <button class="btn" onclick="refreshMap()">
                            <i class="fas fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
                <div id="user-map"></div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Service Requests -->
                <div class="info-panel">
                    <div class="info-header">
                        <i class="fas fa-clipboard-list"></i> My Service Requests
                    </div>
                    <div class="info-content">
                        <?php if (!empty($user_requests)): ?>
                            <?php foreach ($user_requests as $request): ?>
                            <div class="item request-item" onclick="focusOnRequest(<?php echo $request['id']; ?>)">
                                <h4>
                                    <span class="status-indicator status-<?php echo $request['status']; ?>"></span>
                                    <?php echo htmlspecialchars($request['service_type'] ?? 'Service Request'); ?>
                                </h4>
                                <p><strong>Status:</strong> <?php echo ucfirst($request['status']); ?></p>
                                <?php if ($request['technician_name']): ?>
                                <p><strong>Technician:</strong> <?php echo htmlspecialchars($request['technician_name']); ?></p>
                                <?php endif; ?>
                                <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($request['created_at'])); ?></p>
                                <?php if ($request['gps_address']): ?>
                                <div class="location-info">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($request['gps_address']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-clipboard-list"></i>
                                <p>No service requests yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nearby Technicians -->
                <?php if (!empty($nearby_technicians)): ?>
                <div class="info-panel">
                    <div class="info-header">
                        <i class="fas fa-users"></i> Nearby Technicians
                    </div>
                    <div class="info-content">
                        <?php foreach ($nearby_technicians as $tech): ?>
                        <div class="item technician-item" onclick="focusOnTechnician(<?php echo $tech['id']; ?>)">
                            <h4>
                                <span class="status-indicator status-<?php echo $tech['status']; ?>"></span>
                                <?php echo htmlspecialchars($tech['name']); ?>
                                <span class="distance-badge"><?php echo number_format($tech['distance_km'], 1); ?>km</span>
                            </h4>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($tech['specialization']); ?></p>
                            <p><strong>Rating:</strong> 
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= $tech['rating'] ? '#ffc107' : '#e9ecef'; ?>"></i>
                                <?php endfor; ?>
                                (<?php echo $tech['rating']; ?>/5)
                            </p>
                            <p><strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $tech['status'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Orders -->
                <?php if (!empty($user_orders)): ?>
                <div class="info-panel">
                    <div class="info-header">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </div>
                    <div class="info-content">
                        <?php foreach ($user_orders as $order): ?>
                        <div class="item">
                            <h4>Order #<?php echo $order['id']; ?></h4>
                            <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                            <p><strong>Total:</strong> K<?php echo number_format($order['total_amount'], 2); ?></p>
                            <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            <?php if ($order['delivery_address']): ?>
                            <div class="location-info">
                                <i class="fas fa-truck"></i> <?php echo htmlspecialchars($order['delivery_address']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Google Maps JavaScript API -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo getGoogleMapsApiKey(); ?>&callback=initMap"></script>
    
    <script>
        let map;
        let markers = [];
        let infoWindows = [];

        // Map data from PHP
        const userRequests = <?php echo json_encode($user_requests); ?>;
        const nearbyTechnicians = <?php echo json_encode($nearby_technicians); ?>;
        const userOrders = <?php echo json_encode($user_orders); ?>;
        const userLocation = <?php echo json_encode($user_location); ?>;

        function initMap() {
            // Default center (Kigali, Rwanda)
            let center = { lat: -1.9441, lng: 30.0619 };
            
            // If user has a location, center on that
            if (userLocation) {
                center = { lat: parseFloat(userLocation.lat), lng: parseFloat(userLocation.lng) };
            }

            map = new google.maps.Map(document.getElementById('user-map'), {
                zoom: 12,
                center: center,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Add markers for service requests
            addServiceRequestMarkers();
            
            // Add markers for nearby technicians
            addTechnicianMarkers();
            
            // Add markers for orders
            addOrderMarkers();

            // Auto-refresh every 30 seconds
            setInterval(refreshMap, 30000);
        }

        function addServiceRequestMarkers() {
            userRequests.forEach(request => {
                if (request.latitude && request.longitude) {
                    const position = {
                        lat: parseFloat(request.latitude),
                        lng: parseFloat(request.longitude)
                    };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: request.service_type || 'Service Request',
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="12" fill="#667eea" stroke="white" stroke-width="2"/>
                                    <text x="16" y="20" text-anchor="middle" fill="white" font-size="12" font-family="Arial">S</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32)
                        }
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: #667eea;">
                                    ${request.service_type || 'Service Request'}
                                </h4>
                                <p style="margin: 4px 0;"><strong>Status:</strong> ${request.status}</p>
                                ${request.technician_name ? `<p style="margin: 4px 0;"><strong>Technician:</strong> ${request.technician_name}</p>` : ''}
                                <p style="margin: 4px 0;"><strong>Date:</strong> ${new Date(request.created_at).toLocaleDateString()}</p>
                                ${request.gps_address ? `<p style="margin: 4px 0; font-size: 12px; color: #666;"><i class="fas fa-map-marker-alt"></i> ${request.gps_address}</p>` : ''}
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        closeAllInfoWindows();
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    infoWindows.push(infoWindow);
                }
            });
        }

        function addTechnicianMarkers() {
            nearbyTechnicians.forEach(tech => {
                if (tech.latitude && tech.longitude) {
                    const position = {
                        lat: parseFloat(tech.latitude),
                        lng: parseFloat(tech.longitude)
                    };

                    const statusColor = tech.status === 'online' ? '#28a745' : 
                                       tech.status === 'recently_active' ? '#ffc107' : '#6c757d';

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: tech.name,
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="12" fill="${statusColor}" stroke="white" stroke-width="2"/>
                                    <text x="16" y="20" text-anchor="middle" fill="white" font-size="12" font-family="Arial">T</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32)
                        }
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: ${statusColor};">
                                    ${tech.name}
                                </h4>
                                <p style="margin: 4px 0;"><strong>Specialization:</strong> ${tech.specialization}</p>
                                <p style="margin: 4px 0;"><strong>Rating:</strong> ${tech.rating}/5 ⭐</p>
                                <p style="margin: 4px 0;"><strong>Distance:</strong> ${parseFloat(tech.distance_km).toFixed(1)}km away</p>
                                <p style="margin: 4px 0;"><strong>Status:</strong> ${tech.status.replace('_', ' ')}</p>
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        closeAllInfoWindows();
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    infoWindows.push(infoWindow);
                }
            });
        }

        function addOrderMarkers() {
            userOrders.forEach(order => {
                if (order.latitude && order.longitude) {
                    const position = {
                        lat: parseFloat(order.latitude),
                        lng: parseFloat(order.longitude)
                    };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: `Order #${order.id}`,
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="12" fill="#17a2b8" stroke="white" stroke-width="2"/>
                                    <text x="16" y="20" text-anchor="middle" fill="white" font-size="12" font-family="Arial">O</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32)
                        }
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: #17a2b8;">
                                    Order #${order.id}
                                </h4>
                                <p style="margin: 4px 0;"><strong>Status:</strong> ${order.status}</p>
                                <p style="margin: 4px 0;"><strong>Total:</strong> K${parseFloat(order.total_amount).toFixed(2)}</p>
                                <p style="margin: 4px 0;"><strong>Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                                ${order.delivery_address ? `<p style="margin: 4px 0; font-size: 12px; color: #666;"><i class="fas fa-truck"></i> ${order.delivery_address}</p>` : ''}
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        closeAllInfoWindows();
                        infoWindow.open(map, marker);
                    });

                    markers.push(marker);
                    infoWindows.push(infoWindow);
                }
            });
        }

        function closeAllInfoWindows() {
            infoWindows.forEach(infoWindow => {
                infoWindow.close();
            });
        }

        function clearMarkers() {
            markers.forEach(marker => {
                marker.setMap(null);
            });
            markers = [];
            infoWindows = [];
        }

        function centerOnUser() {
            if (userLocation) {
                map.setCenter({ lat: parseFloat(userLocation.lat), lng: parseFloat(userLocation.lng) });
                map.setZoom(15);
            } else {
                alert('No location data available. Please create a service request with location to use this feature.');
            }
        }

        function focusOnRequest(requestId) {
            const request = userRequests.find(r => r.id == requestId);
            if (request && request.latitude && request.longitude) {
                map.setCenter({ lat: parseFloat(request.latitude), lng: parseFloat(request.longitude) });
                map.setZoom(16);
            }
        }

        function focusOnTechnician(techId) {
            const tech = nearbyTechnicians.find(t => t.id == techId);
            if (tech && tech.latitude && tech.longitude) {
                map.setCenter({ lat: parseFloat(tech.latitude), lng: parseFloat(tech.longitude) });
                map.setZoom(16);
            }
        }

        function refreshMap() {
            // Update timestamp
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
            
            // In a real application, you would fetch new data via AJAX
            // For now, we'll just show a message
            console.log('Map refreshed at', new Date().toLocaleTimeString());
        }

        // Auto-update timestamp every second
        setInterval(() => {
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
        }, 1000);
    </script>
</body>
</html>