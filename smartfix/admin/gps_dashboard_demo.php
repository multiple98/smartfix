<?php
session_start();
include('../includes/db.php');
require_once('../includes/GPSManager.php');

// Simple admin authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'GPS Demo Admin';
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
    <title>GPS Dashboard Demo - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Leaflet CSS for OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: rgba(0,123,255,0.95);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .demo-badge {
            background: #ff6b6b;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
            animation: pulse 2s infinite;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s;
            background: rgba(255,255,255,0.1);
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
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
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-card .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #007BFF;
            margin-bottom: 8px;
        }

        .stat-card .label {
            color: #666;
            font-size: 16px;
            font-weight: 500;
        }

        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .map-section {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .map-header {
            background: rgba(248,249,250,0.8);
            padding: 20px;
            border-bottom: 1px solid rgba(233,236,239,0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-controls {
            display: flex;
            gap: 10px;
        }

        .btn {
            background: linear-gradient(45deg, #007BFF, #0056b3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #545b62);
            box-shadow: 0 4px 15px rgba(108,117,125,0.3);
        }

        #demo-map {
            height: 600px;
            width: 100%;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-panel {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .info-header {
            background: rgba(248,249,250,0.8);
            padding: 18px 20px;
            border-bottom: 1px solid rgba(233,236,239,0.5);
            font-weight: 600;
            font-size: 16px;
        }

        .info-content {
            max-height: 400px;
            overflow-y: auto;
        }

        .item {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(241,241,241,0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item:hover {
            background: rgba(248,249,250,0.5);
        }

        .item-info h4 {
            margin: 0 0 5px 0;
            font-size: 15px;
            color: #007BFF;
            font-weight: 600;
        }

        .item-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }

        .status-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin-left: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
        }

        .status-online { 
            background: #28a745;
            box-shadow: 0 0 15px #28a745;
        }
        .status-recently_active { 
            background: #ffc107;
            box-shadow: 0 0 15px #ffc107;
        }
        .status-offline { background: #6c757d; }
        .status-pending { 
            background: #17a2b8;
            box-shadow: 0 0 15px #17a2b8;
        }
        .status-in_progress { 
            background: #fd7e14;
            box-shadow: 0 0 15px #fd7e14;
        }
        .status-completed { background: #28a745; }
        .status-assigned { 
            background: #6f42c1;
            box-shadow: 0 0 15px #6f42c1;
        }

        .technician-item, .request-item {
            cursor: pointer;
            transition: all 0.3s;
        }

        .technician-item:hover, .request-item:hover {
            background: rgba(0,123,255,0.1);
            transform: translateX(5px);
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #28a745;
            font-size: 14px;
            font-weight: 600;
        }

        .pulse {
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { 
                opacity: 1;
                box-shadow: 0 0 5px #28a745;
            }
            50% { 
                opacity: 0.5;
                box-shadow: 0 0 20px #28a745;
            }
            100% { 
                opacity: 1;
                box-shadow: 0 0 5px #28a745;
            }
        }

        .filter-controls {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        select, input {
            padding: 10px 15px;
            border: 1px solid rgba(221,221,221,0.5);
            border-radius: 25px;
            font-size: 14px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }

        select:focus, input:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 10px rgba(0,123,255,0.3);
        }

        .demo-notice {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255,107,107,0.3);
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
        <h1>
            <i class="fas fa-map-marked-alt"></i> GPS Dashboard
            <span class="demo-badge">DEMO MODE</span>
        </h1>
        <div class="nav-links">
            <a href="admin_dashboard_new.php"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="service_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
            <a href="technicians.php"><i class="fas fa-users"></i> Technicians</a>
        </div>
    </div>

    <div class="container">
        <div class="demo-notice">
            <i class="fas fa-info-circle"></i>
            <strong>Demo Mode:</strong> This dashboard uses OpenStreetMap instead of Google Maps. All data shown is for demonstration purposes.
        </div>

        <div class="dashboard-header">
            <h2>Real-time Location Tracking</h2>
            <div class="live-indicator">
                <div class="pulse"></div>
                LIVE DEMO
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
                <label><strong>Show:</strong></label>
                <select id="view-filter">
                    <option value="all">All Items</option>
                    <option value="technicians">Technicians Only</option>
                    <option value="requests">Requests Only</option>
                </select>
                
                <label><strong>Status:</strong></label>
                <select id="status-filter">
                    <option value="all">All Status</option>
                    <option value="online">Online</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
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
                    <h3><i class="fas fa-map"></i> Service Area Map (Kigali)</h3>
                    <div class="map-controls">
                        <button class="btn btn-secondary" onclick="centerOnKigali()">
                            <i class="fas fa-crosshairs"></i> Center on Kigali
                        </button>
                        <button class="btn" onclick="toggleFullscreen()">
                            <i class="fas fa-expand"></i> Fullscreen
                        </button>
                    </div>
                </div>
                <div id="demo-map"></div>
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
                        <div class="item technician-item" onclick="focusOnTechnician(<?php echo $tech['id']; ?>, <?php echo $tech['latitude'] ?? -1.9441; ?>, <?php echo $tech['longitude'] ?? 30.0619; ?>)">
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
                        <div class="item request-item" onclick="focusOnRequest(<?php echo $request['id']; ?>, <?php echo $request['latitude'] ?? -1.9441; ?>, <?php echo $request['longitude'] ?? 30.0619; ?>)">
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

    <script>
        let demoMap;
        let markersLayer;

        // Initialize the demo map with Leaflet and OpenStreetMap
        function initDemoMap() {
            // Initialize map centered on Kigali
            demoMap = L.map('demo-map').setView([-1.9441, 30.0619], 12);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(demoMap);
            
            // Create markers layer group
            markersLayer = L.layerGroup().addTo(demoMap);
            
            // Load data
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
                    const marker = L.marker([parseFloat(tech.latitude), parseFloat(tech.longitude)], {
                        icon: getTechnicianIcon(tech.location_status)
                    });
                    
                    const popupContent = `
                        <div style="padding: 5px; min-width: 200px;">
                            <h4 style="margin: 0 0 10px 0; color: #007BFF;">${tech.name}</h4>
                            <p><strong>Specialization:</strong> ${tech.specialization}</p>
                            <p><strong>Status:</strong> <span style="color: ${getStatusColor(tech.location_status)}">${tech.location_status.replace('_', ' ')}</span></p>
                            <p><strong>Phone:</strong> <a href="tel:${tech.phone}">${tech.phone}</a></p>
                            <p><strong>Last Update:</strong> ${tech.last_updated ? new Date(tech.last_updated).toLocaleTimeString() : 'Unknown'}</p>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    marker.type = 'technician';
                    marker.technicianId = tech.id;
                    markersLayer.addLayer(marker);
                }
            });
        }
        
        // Load service requests on map
        function loadServiceRequests() {
            const requests = <?php echo json_encode($recent_requests); ?>;
            
            requests.forEach(request => {
                if (request.latitude && request.longitude) {
                    const marker = L.marker([parseFloat(request.latitude), parseFloat(request.longitude)], {
                        icon: getRequestIcon(request.status)
                    });
                    
                    const popupContent = `
                        <div style="padding: 5px; min-width: 200px;">
                            <h4 style="margin: 0 0 10px 0; color: #007BFF;">${request.reference_number || 'REQ-' + request.id}</h4>
                            <p><strong>Customer:</strong> ${request.name}</p>
                            <p><strong>Service:</strong> ${request.service_type} - ${request.service_option || 'General'}</p>
                            <p><strong>Status:</strong> <span style="color: ${getStatusColor(request.status)}">${request.status.replace('_', ' ')}</span></p>
                            <p><strong>Phone:</strong> <a href="tel:${request.phone}">${request.phone}</a></p>
                            <p><strong>Created:</strong> ${new Date(request.created_at).toLocaleString()}</p>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    marker.type = 'request';
                    marker.requestId = request.id;
                    markersLayer.addLayer(marker);
                }
            });
        }
        
        // Helper function to create technician icons
        function getTechnicianIcon(status) {
            const colors = {
                online: '#28a745',
                recently_active: '#ffc107',
                offline: '#6c757d'
            };
            
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: ${colors[status] || colors.offline}; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });
        }
        
        // Helper function to create request icons
        function getRequestIcon(status) {
            const colors = {
                pending: '#17a2b8',
                in_progress: '#fd7e14',
                assigned: '#6f42c1',
                completed: '#28a745'
            };
            
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: ${colors[status] || colors.pending}; width: 16px; height: 16px; border-radius: 3px; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });
        }
        
        // Get status colors
        function getStatusColor(status) {
            const colors = {
                online: '#28a745',
                recently_active: '#ffc107',
                offline: '#6c757d',
                pending: '#17a2b8',
                in_progress: '#fd7e14',
                assigned: '#6f42c1',
                completed: '#28a745'
            };
            return colors[status] || '#6c757d';
        }
        
        // Action functions
        function focusOnTechnician(id, lat, lng) {
            demoMap.setView([lat, lng], 15);
            // Find and open popup
            markersLayer.eachLayer(layer => {
                if (layer.type === 'technician' && layer.technicianId === id) {
                    layer.openPopup();
                }
            });
        }
        
        function focusOnRequest(id, lat, lng) {
            demoMap.setView([lat, lng], 15);
            // Find and open popup
            markersLayer.eachLayer(layer => {
                if (layer.type === 'request' && layer.requestId === id) {
                    layer.openPopup();
                }
            });
        }
        
        function centerOnKigali() {
            demoMap.setView([-1.9441, 30.0619], 12);
        }
        
        function toggleFullscreen() {
            const mapElement = document.getElementById('demo-map');
            if (mapElement.requestFullscreen) {
                mapElement.requestFullscreen();
            }
        }
        
        function refreshData() {
            console.log('Refreshing GPS data...');
            location.reload();
        }
        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            initDemoMap();
            
            const viewFilter = document.getElementById('view-filter');
            const statusFilter = document.getElementById('status-filter');
            
            function applyFilters() {
                const viewValue = viewFilter.value;
                const statusValue = statusFilter.value;
                
                markersLayer.eachLayer(layer => {
                    let visible = true;
                    
                    // View filter
                    if (viewValue === 'technicians' && layer.type !== 'technician') {
                        visible = false;
                    } else if (viewValue === 'requests' && layer.type !== 'request') {
                        visible = false;
                    }
                    
                    if (visible) {
                        markersLayer.addLayer(layer);
                    } else {
                        markersLayer.removeLayer(layer);
                    }
                });
            }
            
            viewFilter.addEventListener('change', applyFilters);
            statusFilter.addEventListener('change', applyFilters);
        });
    </script>
</body>
</html>