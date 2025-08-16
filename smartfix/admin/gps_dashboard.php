<?php
session_start();
include('../includes/db.php');
require_once('../includes/GPSManager.php');

// Simple admin authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    // For demo purposes, we'll create a simple session
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Administrator';
}

$gps = new GPSManager($pdo);

// Get statistics
$stats = $gps->getServiceAreaStats();

// Get recent service requests with locations
try {
    $recent_requests = $pdo->query("
        SELECT sr.*, sl.latitude, sl.longitude, sl.address as gps_address
        FROM service_requests sr
        LEFT JOIN service_locations sl ON sr.id = sl.request_id AND sl.location_type = 'customer'
        ORDER BY sr.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_requests = [];
}

// Get active technicians with locations
try {
    $active_technicians = $pdo->query("
        SELECT t.*, tl.latitude, tl.longitude, tl.last_updated,
               CASE 
                   WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                   WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                   ELSE 'offline'
               END AS location_status
        FROM technicians t
        LEFT JOIN technician_locations tl ON t.id = tl.technician_id
        WHERE t.status = 'active'
        ORDER BY tl.last_updated DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $active_technicians = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Dashboard - SmartFix Admin</title>
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
            background: #007BFF;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
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
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #007BFF;
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
            background: #007BFF;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        #admin-map {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #007BFF;
        }

        .item-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 10px;
        }

        .status-online { background: #28a745; }
        .status-recently_active { background: #ffc107; }
        .status-offline { background: #6c757d; }
        .status-pending { background: #17a2b8; }
        .status-in_progress { background: #fd7e14; }
        .status-completed { background: #28a745; }

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

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #28a745;
            font-size: 12px;
        }

        .pulse {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .filter-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        select, input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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
            
            .header {
                padding: 1rem;
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-map-marked-alt"></i> GPS Dashboard</h1>
        <div class="nav-links">
            <a href="admin_dashboard_new.php"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="service_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
            <a href="technicians.php"><i class="fas fa-users"></i> Technicians</a>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-header">
            <h2>Real-time Location Tracking</h2>
            <div class="live-indicator">
                <div class="pulse"></div>
                LIVE
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="color: #007BFF;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="value"><?php echo number_format($stats['total_locations'] ?? 0); ?></div>
                <div class="label">Service Locations</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="color: #28a745;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="value"><?php echo number_format($stats['active_technicians'] ?? 0); ?></div>
                <div class="label">Active Technicians</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="color: #17a2b8;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="value"><?php echo number_format(count($recent_requests)); ?></div>
                <div class="label">Recent Requests</div>
            </div>

            <div class="stat-card">
                <div class="icon" style="color: #ffc107;">
                    <i class="fas fa-route"></i>
                </div>
                <div class="value">98%</div>
                <div class="label">Coverage Area</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-controls">
            <div class="filter-group">
                <label>Show:</label>
                <select id="view-filter">
                    <option value="all">All Items</option>
                    <option value="technicians">Technicians Only</option>
                    <option value="requests">Requests Only</option>
                </select>
                
                <label>Status:</label>
                <select id="status-filter">
                    <option value="all">All Status</option>
                    <option value="online">Online</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                </select>
                
                <label>Service Type:</label>
                <select id="service-filter">
                    <option value="all">All Services</option>
                    <option value="phone">Phone Repair</option>
                    <option value="computer">Computer Repair</option>
                    <option value="car">Vehicle Repair</option>
                    <option value="plumber">Plumbing</option>
                    <option value="electrician">Electrical</option>
                </select>

                <button class="btn" onclick="refreshData()">
                    <i class="fas fa-refresh"></i> Refresh
                </button>
            </div>
        </div>

        <div class="main-content">
            <!-- Map Section -->
            <div class="map-section">
                <div class="map-header">
                    <h3><i class="fas fa-map"></i> Service Area Map</h3>
                    <div class="map-controls">
                        <button class="btn btn-secondary" onclick="centerOnKigali()">
                            <i class="fas fa-crosshairs"></i> Center on Kigali
                        </button>
                        <button class="btn" onclick="showTrafficLayer()">
                            <i class="fas fa-road"></i> Traffic
                        </button>
                    </div>
                </div>
                <div id="admin-map"></div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Active Technicians -->
                <div class="info-panel">
                    <div class="info-header">
                        <i class="fas fa-users"></i> Active Technicians
                        <span style="float: right; color: #28a745;"><?php echo count($active_technicians); ?> online</span>
                    </div>
                    <div class="info-content" id="technicians-list">
                        <?php foreach ($active_technicians as $tech): ?>
                        <div class="item technician-item" onclick="focusOnTechnician(<?php echo $tech['id']; ?>, <?php echo $tech['latitude'] ?? 0; ?>, <?php echo $tech['longitude'] ?? 0; ?>)">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($tech['name']); ?></h4>
                                <p><?php echo htmlspecialchars($tech['specialization']); ?></p>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($tech['phone']); ?></p>
                                <?php if ($tech['last_updated']): ?>
                                    <p><i class="fas fa-clock"></i> Last update: <?php echo date('H:i', strtotime($tech['last_updated'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="status-indicator status-<?php echo $tech['location_status']; ?>" 
                                 title="<?php echo ucfirst(str_replace('_', ' ', $tech['location_status'])); ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Service Requests -->
                <div class="info-panel">
                    <div class="info-header">
                        <i class="fas fa-clipboard-list"></i> Recent Requests
                        <span style="float: right; color: #17a2b8;"><?php echo count($recent_requests); ?> total</span>
                    </div>
                    <div class="info-content" id="requests-list">
                        <?php foreach (array_slice($recent_requests, 0, 20) as $request): ?>
                        <div class="item request-item" onclick="focusOnRequest(<?php echo $request['id']; ?>, <?php echo $request['latitude'] ?? 0; ?>, <?php echo $request['longitude'] ?? 0; ?>)">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($request['reference_number'] ?? 'REF-' . $request['id']); ?></h4>
                                <p><strong><?php echo htmlspecialchars($request['name']); ?></strong></p>
                                <p><?php echo ucfirst($request['service_type']); ?> - <?php echo htmlspecialchars($request['service_option'] ?? 'General'); ?></p>
                                <p><i class="fas fa-clock"></i> <?php echo date('M j, H:i', strtotime($request['created_at'])); ?></p>
                                <?php if ($request['latitude'] && $request['longitude']): ?>
                                    <p><i class="fas fa-map-marker-alt"></i> GPS Location Available</p>
                                <?php endif; ?>
                            </div>
                            <div class="status-indicator status-<?php echo $request['status']; ?>" 
                                 title="<?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>"></div>
                        </div>
                        <?php endforeach; ?>
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
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo getGoogleMapsApiKey(); ?>&callback=initAdminMap&libraries=geometry"></script>
    <?php else: ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('admin-map').innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #666; text-align: center; flex-direction: column;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></i>
                        <h3>Google Maps API Not Configured</h3>
                        <p>Please set up your Google Maps API key to use GPS features.</p>
                        <a href="../test_gps_setup.php" style="background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;">Setup GPS</a>
                    </div>
                `;
            });
        </script>
    <?php endif; ?>
    
    <script>
        let adminMap;
        let markers = [];
        let trafficLayer;
        
        // Initialize admin map
        function initAdminMap() {
            adminMap = new google.maps.Map(document.getElementById('admin-map'), {
                zoom: 12,
                center: { lat: -1.9441, lng: 30.0619 }, // Kigali
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Initialize traffic layer
            trafficLayer = new google.maps.TrafficLayer();

            // Load data on map
            loadTechnicians();
            loadServiceRequests();
            
            // Auto-refresh every 30 seconds
            setInterval(refreshData, 30000);
        }

        // Load technicians on map
        function loadTechnicians() {
            const technicians = <?php echo json_encode($active_technicians); ?>;
            
            technicians.forEach(tech => {
                if (tech.latitude && tech.longitude) {
                    const marker = new google.maps.Marker({
                        position: { lat: parseFloat(tech.latitude), lng: parseFloat(tech.longitude) },
                        map: adminMap,
                        title: tech.name,
                        icon: getTechnicianIcon(tech.location_status),
                        animation: google.maps.Animation.DROP
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px; max-width: 250px;">
                                <h4 style="margin: 0 0 10px 0; color: #007BFF;">${tech.name}</h4>
                                <p><strong>Specialization:</strong> ${tech.specialization}</p>
                                <p><strong>Status:</strong> <span style="color: ${getStatusColor(tech.location_status)}">${tech.location_status.replace('_', ' ')}</span></p>
                                <p><strong>Phone:</strong> <a href="tel:${tech.phone}">${tech.phone}</a></p>
                                <p><strong>Last Update:</strong> ${tech.last_updated ? new Date(tech.last_updated).toLocaleTimeString() : 'Unknown'}</p>
                                <div style="margin-top: 15px;">
                                    <button onclick="assignTechnicianToRequest(${tech.id})" style="background: #007BFF; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Assign to Request</button>
                                </div>
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        closeAllInfoWindows();
                        infoWindow.open(adminMap, marker);
                    });

                    marker.infoWindow = infoWindow;
                    marker.type = 'technician';
                    marker.technicianId = tech.id;
                    markers.push(marker);
                }
            });
        }

        // Load service requests on map
        function loadServiceRequests() {
            const requests = <?php echo json_encode($recent_requests); ?>;
            
            requests.forEach(request => {
                if (request.latitude && request.longitude) {
                    const marker = new google.maps.Marker({
                        position: { lat: parseFloat(request.latitude), lng: parseFloat(request.longitude) },
                        map: adminMap,
                        title: request.reference_number || `Request ${request.id}`,
                        icon: getRequestIcon(request.status),
                        animation: google.maps.Animation.DROP
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div style="padding: 10px; max-width: 250px;">
                                <h4 style="margin: 0 0 10px 0; color: #007BFF;">${request.reference_number || 'REQ-' + request.id}</h4>
                                <p><strong>Customer:</strong> ${request.name}</p>
                                <p><strong>Service:</strong> ${request.service_type} - ${request.service_option || 'General'}</p>
                                <p><strong>Status:</strong> <span style="color: ${getStatusColor(request.status)}">${request.status.replace('_', ' ')}</span></p>
                                <p><strong>Phone:</strong> <a href="tel:${request.phone}">${request.phone}</a></p>
                                <p><strong>Created:</strong> ${new Date(request.created_at).toLocaleString()}</p>
                                <p style="font-size: 12px; color: #666;"><strong>Description:</strong> ${request.description.substring(0, 100)}${request.description.length > 100 ? '...' : ''}</p>
                                <div style="margin-top: 15px;">
                                    <button onclick="viewRequestDetails(${request.id})" style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-right: 5px;">View Details</button>
                                    <button onclick="findNearestTechnicianFor(${request.id})" style="background: #ffc107; color: black; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">Find Technician</button>
                                </div>
                            </div>
                        `
                    });

                    marker.addListener('click', () => {
                        closeAllInfoWindows();
                        infoWindow.open(adminMap, marker);
                    });

                    marker.infoWindow = infoWindow;
                    marker.type = 'request';
                    marker.requestId = request.id;
                    markers.push(marker);
                }
            });
        }

        // Helper functions
        function getTechnicianIcon(status) {
            const colors = {
                online: '#28a745',
                recently_active: '#ffc107',
                offline: '#6c757d'
            };
            
            return {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: colors[status] || colors.offline,
                fillOpacity: 1,
                strokeColor: 'white',
                strokeWeight: 2
            };
        }

        function getRequestIcon(status) {
            const colors = {
                pending: '#17a2b8',
                assigned: '#fd7e14',
                in_progress: '#ffc107',
                completed: '#28a745',
                cancelled: '#dc3545'
            };
            
            return {
                path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                scale: 6,
                fillColor: colors[status] || colors.pending,
                fillOpacity: 1,
                strokeColor: 'white',
                strokeWeight: 2
            };
        }

        function getStatusColor(status) {
            const colors = {
                online: '#28a745',
                recently_active: '#ffc107',
                offline: '#6c757d',
                pending: '#17a2b8',
                assigned: '#fd7e14',
                in_progress: '#ffc107',
                completed: '#28a745',
                cancelled: '#dc3545'
            };
            return colors[status] || '#6c757d';
        }

        function closeAllInfoWindows() {
            markers.forEach(marker => {
                if (marker.infoWindow) {
                    marker.infoWindow.close();
                }
            });
        }

        function clearMarkers() {
            markers.forEach(marker => {
                marker.setMap(null);
            });
            markers = [];
        }

        // Action functions
        function focusOnTechnician(id, lat, lng) {
            if (lat && lng) {
                adminMap.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
                adminMap.setZoom(15);
                
                // Find and open the marker's info window
                const marker = markers.find(m => m.type === 'technician' && m.technicianId === id);
                if (marker && marker.infoWindow) {
                    closeAllInfoWindows();
                    marker.infoWindow.open(adminMap, marker);
                }
            }
        }

        function focusOnRequest(id, lat, lng) {
            if (lat && lng) {
                adminMap.setCenter({ lat: parseFloat(lat), lng: parseFloat(lng) });
                adminMap.setZoom(15);
                
                // Find and open the marker's info window
                const marker = markers.find(m => m.type === 'request' && m.requestId === id);
                if (marker && marker.infoWindow) {
                    closeAllInfoWindows();
                    marker.infoWindow.open(adminMap, marker);
                }
            }
        }

        function centerOnKigali() {
            adminMap.setCenter({ lat: -1.9441, lng: 30.0619 });
            adminMap.setZoom(12);
        }

        function showTrafficLayer() {
            if (trafficLayer.getMap()) {
                trafficLayer.setMap(null);
            } else {
                trafficLayer.setMap(adminMap);
            }
        }

        function refreshData() {
            console.log('Refreshing GPS data...');
            // In a real implementation, this would fetch fresh data via AJAX
            location.reload();
        }

        function assignTechnicianToRequest(technicianId) {
            // Implement technician assignment logic
            alert(`Assigning technician ${technicianId} to request. This would redirect to assignment page.`);
        }

        function viewRequestDetails(requestId) {
            // Open request details page
            window.open(`service_requests.php?id=${requestId}`, '_blank');
        }

        function findNearestTechnicianFor(requestId) {
            // Find and highlight nearest technicians
            alert(`Finding nearest technicians for request ${requestId}. This would show distance calculations.`);
        }

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const viewFilter = document.getElementById('view-filter');
            const statusFilter = document.getElementById('status-filter');
            const serviceFilter = document.getElementById('service-filter');
            
            function applyFilters() {
                const viewValue = viewFilter.value;
                const statusValue = statusFilter.value;
                const serviceValue = serviceFilter.value;
                
                markers.forEach(marker => {
                    let visible = true;
                    
                    // View filter
                    if (viewValue === 'technicians' && marker.type !== 'technician') {
                        visible = false;
                    } else if (viewValue === 'requests' && marker.type !== 'request') {
                        visible = false;
                    }
                    
                    marker.setVisible(visible);
                });
            }
            
            viewFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
            serviceFilter.addEventListener('change', applyFilters);
        });
    </script>
</body>
</html>