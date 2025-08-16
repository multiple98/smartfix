<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/GPSManager.php';
require_once 'config/maps_config.php';

// Initialize GPS Manager
$gps = new GPSManager($pdo);

// Zambia geographical boundaries
$zambia_bounds = [
    'north' => -8.224,
    'south' => -18.079,
    'east' => 33.706,
    'west' => 21.999
];

// Get all technicians with their locations within Zambia
try {
    $query = "SELECT 
                t.id,
                t.name,
                t.email,
                t.phone,
                t.specialization,
                t.regions,
                t.address,
                t.bio,
                t.rating,
                t.total_jobs,
                t.status,
                tl.latitude,
                tl.longitude,
                tl.last_updated,
                CASE 
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                    WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                    ELSE 'offline'
                END AS location_status
              FROM technicians t
              LEFT JOIN technician_locations tl ON t.id = tl.technician_id
              WHERE t.status = 'available'
              AND (tl.latitude IS NULL OR (
                  tl.latitude BETWEEN ? AND ? 
                  AND tl.longitude BETWEEN ? AND ?
              ))
              ORDER BY t.rating DESC, t.total_jobs DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $zambia_bounds['south'], 
        $zambia_bounds['north'], 
        $zambia_bounds['west'], 
        $zambia_bounds['east']
    ]);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching technicians: " . $e->getMessage());
    $technicians = [];
}

// Get specialization statistics
try {
    $spec_query = "SELECT specialization, COUNT(*) as count 
                   FROM technicians 
                   WHERE status = 'available' 
                   GROUP BY specialization 
                   ORDER BY count DESC";
    $spec_stmt = $pdo->prepare($spec_query);
    $spec_stmt->execute();
    $specializations = $spec_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $specializations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Map - SmartFix Zambia</title>
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
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1rem 0;
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

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-bar {
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .stats-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }

        .stat-number {
            font-weight: bold;
            color: #28a745;
            font-size: 18px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .map-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            min-height: 600px;
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .filter-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .technician-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .technician-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .technician-item:hover {
            background: #e9ecef;
            border-color: #28a745;
            transform: translateY(-2px);
        }

        .technician-item.selected {
            border-color: #28a745;
            background: #d4edda;
        }

        .tech-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .tech-specialization {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .tech-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }

        .stars {
            color: #ffc107;
        }

        .tech-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-online { background: #28a745; }
        .status-recently_active { background: #ffc107; }
        .status-offline { background: #6c757d; }

        .map-wrapper {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .control-btn {
            background: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s;
        }

        .control-btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .legend {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .legend-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .legend-marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .map-container {
                grid-template-columns: 1fr;
                grid-template-rows: 300px 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .map-wrapper {
                order: 1;
            }
            
            .stats-content {
                justify-content: center;
            }
        }

        /* Loading Animation */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #28a745;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-map-marked-alt"></i>
                SmartFix Technician Map - Zambia
            </h1>
            <div>
                <a href="index.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stats-content">
            <div class="stat-item">
                <i class="fas fa-users"></i>
                <span class="stat-number"><?php echo count($technicians); ?></span>
                <span>Available Technicians</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-map-marker-alt"></i>
                <span class="stat-number"><?php echo count(array_filter($technicians, function($t) { return $t['latitude'] && $t['longitude']; })); ?></span>
                <span>With GPS Location</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-circle" style="color: #28a745;"></i>
                <span class="stat-number"><?php echo count(array_filter($technicians, function($t) { return $t['location_status'] === 'online'; })); ?></span>
                <span>Online Now</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-tools"></i>
                <span class="stat-number"><?php echo count($specializations); ?></span>
                <span>Service Types</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="map-container">
            <div class="sidebar">
                <div class="sidebar-header">
                    <i class="fas fa-filter"></i>
                    Filters & Technicians
                </div>
                
                <div class="filters">
                    <div class="filter-group">
                        <label for="specialization-filter">Service Type:</label>
                        <select id="specialization-filter" class="filter-select">
                            <option value="">All Services</option>
                            <?php foreach ($specializations as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec['specialization']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($spec['specialization'])); ?> (<?php echo $spec['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="online">Online</option>
                            <option value="recently_active">Recently Active</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="rating-filter">Minimum Rating:</label>
                        <select id="rating-filter" class="filter-select">
                            <option value="">Any Rating</option>
                            <option value="4.5">4.5+ Stars</option>
                            <option value="4.0">4.0+ Stars</option>
                            <option value="3.5">3.5+ Stars</option>
                        </select>
                    </div>
                </div>
                
                <div class="technician-list" id="technician-list">
                    <?php if (empty($technicians)): ?>
                        <div class="loading">
                            <div class="spinner"></div>
                            Loading technicians...
                        </div>
                    <?php else: ?>
                        <?php foreach ($technicians as $tech): ?>
                            <div class="technician-item" 
                                 data-id="<?php echo $tech['id']; ?>"
                                 data-specialization="<?php echo htmlspecialchars($tech['specialization']); ?>"
                                 data-status="<?php echo $tech['location_status']; ?>"
                                 data-rating="<?php echo $tech['rating']; ?>"
                                 data-lat="<?php echo $tech['latitude']; ?>"
                                 data-lng="<?php echo $tech['longitude']; ?>">
                                
                                <div class="tech-name"><?php echo htmlspecialchars($tech['name']); ?></div>
                                <div class="tech-specialization">
                                    <i class="fas fa-tools"></i>
                                    <?php echo htmlspecialchars(ucfirst($tech['specialization'])); ?>
                                </div>
                                <div class="tech-rating">
                                    <div class="stars">
                                        <?php 
                                        $rating = floatval($tech['rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span><?php echo number_format($rating, 1); ?> (<?php echo $tech['total_jobs']; ?> jobs)</span>
                                </div>
                                <div class="tech-status">
                                    <div class="status-dot status-<?php echo $tech['location_status']; ?>"></div>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $tech['location_status'])); ?></span>
                                    <?php if ($tech['latitude'] && $tech['longitude']): ?>
                                        <i class="fas fa-map-marker-alt" style="color: #28a745; margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="map-wrapper">
                <div class="map-controls">
                    <button class="control-btn" onclick="centerMapOnZambia()" title="Center on Zambia">
                        <i class="fas fa-home"></i>
                    </button>
                    <button class="control-btn" onclick="toggleTraffic()" title="Toggle Traffic">
                        <i class="fas fa-road"></i>
                    </button>
                    <button class="control-btn" onclick="refreshTechnicians()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                
                <div class="legend">
                    <div class="legend-title">Technician Status</div>
                    <div class="legend-item">
                        <div class="legend-marker status-online"></div>
                        <span>Online (Active within 15 min)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-marker status-recently_active"></div>
                        <span>Recently Active (Within 2 hours)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-marker status-offline"></div>
                        <span>Offline</span>
                    </div>
                </div>
                
                <div id="map"></div>
            </div>
        </div>
    </div>

    <!-- Technician Details Modal -->
    <div id="technicianModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 10px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="modalTechName" style="margin: 0; color: #333;"></h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            </div>
            <div id="modalTechDetails"></div>
            <div style="margin-top: 20px; text-align: center;">
                <button onclick="requestService()" style="background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    <i class="fas fa-phone"></i> Request Service
                </button>
            </div>
        </div>
    </div>

    <script>
        let map;
        let markers = [];
        let trafficLayer;
        let selectedTechnicianId = null;
        
        // Zambia boundaries
        const zambiaBounds = {
            north: -8.224,
            south: -18.079,
            east: 33.706,
            west: 21.999
        };
        
        // Technician data from PHP
        const technicians = <?php echo json_encode($technicians); ?>;
        
        function initMap() {
            // Center map on Zambia
            const zambiaCenter = { lat: -13.133, lng: 27.849 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 6,
                center: zambiaCenter,
                mapTypeId: 'roadmap',
                restriction: {
                    latLngBounds: {
                        north: zambiaBounds.north,
                        south: zambiaBounds.south,
                        east: zambiaBounds.east,
                        west: zambiaBounds.west
                    },
                    strictBounds: false
                },
                styles: [
                    {
                        featureType: 'administrative.country',
                        elementType: 'geometry.stroke',
                        stylers: [{ color: '#28a745' }, { weight: 2 }]
                    }
                ]
            });
            
            // Initialize traffic layer
            trafficLayer = new google.maps.TrafficLayer();
            
            // Add technician markers
            addTechnicianMarkers();
            
            // Add event listeners
            setupEventListeners();
        }
        
        function addTechnicianMarkers() {
            // Clear existing markers
            markers.forEach(marker => marker.setMap(null));
            markers = [];
            
            technicians.forEach(tech => {
                if (tech.latitude && tech.longitude) {
                    // Validate coordinates are within Zambia
                    const lat = parseFloat(tech.latitude);
                    const lng = parseFloat(tech.longitude);
                    
                    if (lat >= zambiaBounds.south && lat <= zambiaBounds.north &&
                        lng >= zambiaBounds.west && lng <= zambiaBounds.east) {
                        
                        const marker = new google.maps.Marker({
                            position: { lat: lat, lng: lng },
                            map: map,
                            title: tech.name,
                            icon: getMarkerIcon(tech.location_status),
                            animation: google.maps.Animation.DROP
                        });
                        
                        // Create info window
                        const infoWindow = new google.maps.InfoWindow({
                            content: createInfoWindowContent(tech)
                        });
                        
                        // Add click listener
                        marker.addListener('click', () => {
                            // Close other info windows
                            markers.forEach(m => {
                                if (m.infoWindow) {
                                    m.infoWindow.close();
                                }
                            });
                            
                            infoWindow.open(map, marker);
                            selectTechnician(tech.id);
                        });
                        
                        marker.infoWindow = infoWindow;
                        marker.technicianData = tech;
                        markers.push(marker);
                    }
                }
            });
        }
        
        function getMarkerIcon(status) {
            const colors = {
                'online': '#28a745',
                'recently_active': '#ffc107',
                'offline': '#6c757d'
            };
            
            return {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: colors[status] || '#6c757d',
                fillOpacity: 0.8,
                strokeColor: '#ffffff',
                strokeWeight: 2
            };
        }
        
        function createInfoWindowContent(tech) {
            const rating = parseFloat(tech.rating);
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="fas fa-star" style="color: #ffc107;"></i>';
                } else if (i - 0.5 <= rating) {
                    stars += '<i class="fas fa-star-half-alt" style="color: #ffc107;"></i>';
                } else {
                    stars += '<i class="far fa-star" style="color: #ffc107;"></i>';
                }
            }
            
            return `
                <div style="max-width: 250px; padding: 10px;">
                    <h4 style="margin: 0 0 10px 0; color: #333;">${tech.name}</h4>
                    <p style="margin: 5px 0; color: #666;">
                        <i class="fas fa-tools"></i> ${tech.specialization}
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <i class="fas fa-phone"></i> ${tech.phone}
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <i class="fas fa-map-marker-alt"></i> ${tech.address || 'Location available'}
                    </p>
                    <div style="margin: 10px 0;">
                        ${stars} ${rating.toFixed(1)} (${tech.total_jobs} jobs)
                    </div>
                    <button onclick="showTechnicianDetails(${tech.id})" 
                            style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; width: 100%;">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                </div>
            `;
        }
        
        function setupEventListeners() {
            // Filter event listeners
            document.getElementById('specialization-filter').addEventListener('change', filterTechnicians);
            document.getElementById('status-filter').addEventListener('change', filterTechnicians);
            document.getElementById('rating-filter').addEventListener('change', filterTechnicians);
            
            // Technician list item clicks
            document.querySelectorAll('.technician-item').forEach(item => {
                item.addEventListener('click', () => {
                    const techId = parseInt(item.dataset.id);
                    const lat = parseFloat(item.dataset.lat);
                    const lng = parseFloat(item.dataset.lng);
                    
                    if (lat && lng) {
                        map.setCenter({ lat, lng });
                        map.setZoom(12);
                        
                        // Find and click the marker
                        const marker = markers.find(m => m.technicianData.id === techId);
                        if (marker) {
                            google.maps.event.trigger(marker, 'click');
                        }
                    }
                    
                    selectTechnician(techId);
                });
            });
        }
        
        function filterTechnicians() {
            const specializationFilter = document.getElementById('specialization-filter').value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;
            const ratingFilter = parseFloat(document.getElementById('rating-filter').value) || 0;
            
            document.querySelectorAll('.technician-item').forEach(item => {
                const specialization = item.dataset.specialization.toLowerCase();
                const status = item.dataset.status;
                const rating = parseFloat(item.dataset.rating);
                
                let show = true;
                
                if (specializationFilter && !specialization.includes(specializationFilter)) {
                    show = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                if (ratingFilter && rating < ratingFilter) {
                    show = false;
                }
                
                item.style.display = show ? 'block' : 'none';
            });
            
            // Filter map markers
            markers.forEach(marker => {
                const tech = marker.technicianData;
                const specialization = tech.specialization.toLowerCase();
                const status = tech.location_status;
                const rating = parseFloat(tech.rating);
                
                let show = true;
                
                if (specializationFilter && !specialization.includes(specializationFilter)) {
                    show = false;
                }
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                if (ratingFilter && rating < ratingFilter) {
                    show = false;
                }
                
                marker.setVisible(show);
            });
        }
        
        function selectTechnician(techId) {
            // Remove previous selection
            document.querySelectorAll('.technician-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection to current technician
            const selectedItem = document.querySelector(`[data-id="${techId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
                selectedItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            selectedTechnicianId = techId;
        }
        
        function showTechnicianDetails(techId) {
            const tech = technicians.find(t => t.id === techId);
            if (!tech) return;
            
            document.getElementById('modalTechName').textContent = tech.name;
            
            const rating = parseFloat(tech.rating);
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="fas fa-star" style="color: #ffc107;"></i>';
                } else if (i - 0.5 <= rating) {
                    stars += '<i class="fas fa-star-half-alt" style="color: #ffc107;"></i>';
                } else {
                    stars += '<i class="far fa-star" style="color: #ffc107;"></i>';
                }
            }
            
            const statusColors = {
                'online': '#28a745',
                'recently_active': '#ffc107',
                'offline': '#6c757d'
            };
            
            document.getElementById('modalTechDetails').innerHTML = `
                <div style="margin-bottom: 15px;">
                    <h5 style="color: #666; margin-bottom: 5px;">Contact Information</h5>
                    <p><i class="fas fa-envelope"></i> ${tech.email || 'Not provided'}</p>
                    <p><i class="fas fa-phone"></i> ${tech.phone}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${tech.address || 'Location available via GPS'}</p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <h5 style="color: #666; margin-bottom: 5px;">Service Information</h5>
                    <p><i class="fas fa-tools"></i> <strong>Specialization:</strong> ${tech.specialization}</p>
                    <p><i class="fas fa-map"></i> <strong>Service Regions:</strong> ${tech.regions || 'Available nationwide'}</p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <h5 style="color: #666; margin-bottom: 5px;">Performance</h5>
                    <div style="margin-bottom: 10px;">
                        ${stars} ${rating.toFixed(1)} out of 5
                    </div>
                    <p><i class="fas fa-briefcase"></i> <strong>Total Jobs:</strong> ${tech.total_jobs}</p>
                    <p><i class="fas fa-circle" style="color: ${statusColors[tech.location_status]};"></i> 
                       <strong>Status:</strong> ${tech.location_status.replace('_', ' ').toUpperCase()}</p>
                </div>
                
                ${tech.bio ? `
                <div style="margin-bottom: 15px;">
                    <h5 style="color: #666; margin-bottom: 5px;">About</h5>
                    <p style="font-style: italic; color: #555;">${tech.bio}</p>
                </div>
                ` : ''}
                
                ${tech.last_updated ? `
                <div style="font-size: 12px; color: #999; text-align: center;">
                    Last location update: ${new Date(tech.last_updated).toLocaleString()}
                </div>
                ` : ''}
            `;
            
            selectedTechnicianId = techId;
            document.getElementById('technicianModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('technicianModal').style.display = 'none';
        }
        
        function requestService() {
            if (selectedTechnicianId) {
                window.location.href = `services/request_service.php?technician_id=${selectedTechnicianId}`;
            }
        }
        
        function centerMapOnZambia() {
            map.setCenter({ lat: -13.133, lng: 27.849 });
            map.setZoom(6);
        }
        
        function toggleTraffic() {
            if (trafficLayer.getMap()) {
                trafficLayer.setMap(null);
            } else {
                trafficLayer.setMap(map);
            }
        }
        
        function refreshTechnicians() {
            location.reload();
        }
        
        // Close modal when clicking outside
        document.getElementById('technicianModal').addEventListener('click', (e) => {
            if (e.target.id === 'technicianModal') {
                closeModal();
            }
        });
        
        // Initialize map when page loads
        window.onload = function() {
            if (typeof google !== 'undefined') {
                initMap();
            }
        };
    </script>
    
    <!-- Google Maps API -->
    <?php 
    $maps_url = getGoogleMapsJsUrl('initMap');
    if ($maps_url): 
    ?>
        <script async defer src="<?php echo $maps_url; ?>"></script>
    <?php else: ?>
        <script>
            // Fallback when Google Maps is not configured
            console.warn('Google Maps API not configured. Please update config/maps_config.php');
            function initMap() {
                document.getElementById('map').innerHTML = 
                    '<div style="display: flex; justify-content: center; align-items: center; height: 100%; background: #f8f9fa; color: #666;">' +
                    '<div style="text-align: center;">' +
                    '<i class="fas fa-map-marked-alt" style="font-size: 48px; margin-bottom: 20px;"></i><br>' +
                    '<h3>Map Not Available</h3>' +
                    '<p>Google Maps API key not configured.<br>Please contact administrator.</p>' +
                    '</div></div>';
            }
            window.onload = initMap;
        </script>
    <?php endif; ?>
</body>
</html>