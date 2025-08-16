<?php
session_start();
include('includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;

if (!$order_id) {
    header("Location: user/dashboard.php");
    exit();
}

// Verify order belongs to user
$verify_query = "SELECT o.*, dt.* FROM orders o 
                 LEFT JOIN delivery_tracking dt ON o.id = dt.order_id 
                 WHERE o.id = ? AND o.user_id = ?";
$stmt = $pdo->prepare($verify_query);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: user/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Delivery Tracking - SmartFix</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #007BFF;
            margin-bottom: 10px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            background: rgba(0, 123, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            border-left: 4px solid #007BFF;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: bold;
            color: #333;
        }

        .tracking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 20px;
            margin-bottom: 20px;
        }

        .map-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .map-header {
            background: #007BFF;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        #tracking-map {
            height: 500px;
            width: 100%;
        }

        .status-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .status-header {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }

        .status-content {
            padding: 20px;
        }

        .current-status {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .status-text {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .status-time {
            font-size: 14px;
            opacity: 0.9;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .timeline-item.active {
            background: rgba(0, 123, 255, 0.1);
            border-left: 4px solid #007BFF;
        }

        .timeline-item.completed {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
        }

        .timeline-dot {
            position: absolute;
            left: -23px;
            top: 20px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #e9ecef;
            border: 3px solid white;
        }

        .timeline-item.active .timeline-dot {
            background: #007BFF;
        }

        .timeline-item.completed .timeline-dot {
            background: #28a745;
        }

        .timeline-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .timeline-time {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .timeline-description {
            font-size: 14px;
            color: #555;
        }

        .driver-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .driver-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .driver-avatar {
            width: 40px;
            height: 40px;
            background: #007BFF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .contact-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: #007BFF;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .eta-card {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .eta-time {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .eta-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0, 123, 255, 0.4);
        }

        .refresh-btn.spinning {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .tracking-container {
                grid-template-columns: 1fr;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
            
            #tracking-map {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-truck"></i> Live Delivery Tracking</h1>
            <p>Track your order in real-time</p>
            
            <div class="order-info">
                <div class="info-item">
                    <div class="info-label">Order Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['tracking_number'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Delivery Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['delivery_address'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order Total</div>
                    <div class="info-value">K<?php echo number_format($order['total_amount'] ?? 0, 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Transport Cost</div>
                    <div class="info-value">K<?php echo number_format($order['transport_cost'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Main Tracking Content -->
        <div class="tracking-container">
            <!-- Map Section -->
            <div class="map-section">
                <div class="map-header">
                    <h3><i class="fas fa-map-marked-alt"></i> Live Location</h3>
                    <div class="live-indicator">
                        <div class="pulse-dot"></div>
                        LIVE TRACKING
                    </div>
                </div>
                <div id="tracking-map"></div>
            </div>

            <!-- Status Panel -->
            <div class="status-panel">
                <div class="status-header">
                    <i class="fas fa-info-circle"></i> Delivery Status
                </div>
                <div class="status-content">
                    <!-- Current Status -->
                    <div class="current-status">
                        <div class="status-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="status-text" id="current-status-text">In Transit</div>
                        <div class="status-time" id="current-status-time">Updated 5 minutes ago</div>
                    </div>

                    <!-- ETA Card -->
                    <div class="eta-card">
                        <div class="eta-time" id="eta-time">45 min</div>
                        <div class="eta-label">Estimated Arrival</div>
                    </div>

                    <!-- Driver Information -->
                    <div class="driver-info" id="driver-info" style="display: none;">
                        <div class="driver-header">
                            <div class="driver-avatar" id="driver-avatar">JD</div>
                            <div>
                                <div class="driver-name" id="driver-name">John Doe</div>
                                <div class="driver-vehicle" id="driver-vehicle">Toyota Hiace - ABC 123</div>
                            </div>
                        </div>
                        <div class="contact-buttons">
                            <a href="#" class="btn btn-primary" id="call-driver">
                                <i class="fas fa-phone"></i> Call Driver
                            </a>
                            <a href="#" class="btn btn-success" id="message-driver">
                                <i class="fas fa-sms"></i> Send SMS
                            </a>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="timeline" id="delivery-timeline">
                        <!-- Timeline items will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" id="refresh-btn" onclick="refreshTracking()">
        <i class="fas fa-sync-alt"></i>
    </button>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBOti4mM-6x9WDnZIjIeyb7TlR-2K7_BDc&libraries=geometry"></script>
    
    <script>
        let map;
        let deliveryMarker;
        let routePath;
        let trackingInterval;
        const orderId = <?php echo $order_id; ?>;

        // Initialize map
        function initMap() {
            // Default center (Lusaka)
            const defaultCenter = { lat: -15.3875, lng: 28.3228 };
            
            map = new google.maps.Map(document.getElementById('tracking-map'), {
                zoom: 13,
                center: defaultCenter,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            // Load initial tracking data
            loadTrackingData();
            
            // Set up auto-refresh
            trackingInterval = setInterval(loadTrackingData, 30000); // Refresh every 30 seconds
        }

        // Load tracking data from API
        async function loadTrackingData() {
            try {
                const response = await fetch(`/smartfix/api/delivery-tracking.php?order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success && data.tracking) {
                    updateTrackingDisplay(data.tracking, data.history);
                } else {
                    console.error('Failed to load tracking data:', data.error);
                }
            } catch (error) {
                console.error('Error loading tracking data:', error);
            }
        }

        // Update tracking display
        function updateTrackingDisplay(tracking, history) {
            // Update current status
            updateCurrentStatus(tracking);
            
            // Update map
            updateMap(tracking);
            
            // Update timeline
            updateTimeline(history);
            
            // Update driver info
            updateDriverInfo(tracking);
            
            // Update ETA
            updateETA(tracking);
        }

        // Update current status
        function updateCurrentStatus(tracking) {
            const statusText = document.getElementById('current-status-text');
            const statusTime = document.getElementById('current-status-time');
            
            const statusLabels = {
                'pickup_scheduled': 'Pickup Scheduled',
                'picked_up': 'Package Picked Up',
                'in_transit': 'In Transit',
                'out_for_delivery': 'Out for Delivery',
                'delivered': 'Delivered',
                'failed_delivery': 'Delivery Failed'
            };
            
            statusText.textContent = statusLabels[tracking.status] || tracking.status;
            
            const updatedTime = new Date(tracking.updated_at);
            const now = new Date();
            const diffMinutes = Math.floor((now - updatedTime) / (1000 * 60));
            
            if (diffMinutes < 1) {
                statusTime.textContent = 'Updated just now';
            } else if (diffMinutes < 60) {
                statusTime.textContent = `Updated ${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
            } else {
                const diffHours = Math.floor(diffMinutes / 60);
                statusTime.textContent = `Updated ${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            }
        }

        // Update map with current location
        function updateMap(tracking) {
            if (tracking.current_latitude && tracking.current_longitude) {
                const currentPos = {
                    lat: parseFloat(tracking.current_latitude),
                    lng: parseFloat(tracking.current_longitude)
                };
                
                // Update or create delivery marker
                if (deliveryMarker) {
                    deliveryMarker.setPosition(currentPos);
                } else {
                    deliveryMarker = new google.maps.Marker({
                        position: currentPos,
                        map: map,
                        title: 'Delivery Vehicle',
                        icon: {
                            url: 'data:image/svg+xml;base64,' + btoa(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="12" fill="#007BFF"/>
                                    <path d="M10 16l4 4 8-8" stroke="white" stroke-width="2" fill="none"/>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16)
                        }
                    });
                }
                
                // Center map on current location
                map.setCenter(currentPos);
            }
        }

        // Update timeline
        function updateTimeline(history) {
            const timeline = document.getElementById('delivery-timeline');
            timeline.innerHTML = '';
            
            const timelineSteps = [
                { status: 'pickup_scheduled', title: 'Pickup Scheduled', icon: 'fas fa-calendar-check' },
                { status: 'picked_up', title: 'Package Picked Up', icon: 'fas fa-box' },
                { status: 'in_transit', title: 'In Transit', icon: 'fas fa-truck' },
                { status: 'out_for_delivery', title: 'Out for Delivery', icon: 'fas fa-shipping-fast' },
                { status: 'delivered', title: 'Delivered', icon: 'fas fa-check-circle' }
            ];
            
            timelineSteps.forEach((step, index) => {
                const historyItem = history.find(h => h.status === step.status);
                const isCompleted = historyItem !== undefined;
                const isActive = !isCompleted && index === 0; // First incomplete step is active
                
                const timelineItem = document.createElement('div');
                timelineItem.className = `timeline-item ${isCompleted ? 'completed' : ''} ${isActive ? 'active' : ''}`;
                
                timelineItem.innerHTML = `
                    <div class="timeline-dot"></div>
                    <div class="timeline-title">
                        <i class="${step.icon}"></i> ${step.title}
                    </div>
                    ${historyItem ? `
                        <div class="timeline-time">${new Date(historyItem.created_at).toLocaleString()}</div>
                        ${historyItem.notes ? `<div class="timeline-description">${historyItem.notes}</div>` : ''}
                    ` : ''}
                `;
                
                timeline.appendChild(timelineItem);
            });
        }

        // Update driver information
        function updateDriverInfo(tracking) {
            const driverInfo = document.getElementById('driver-info');
            
            if (tracking.driver_name && tracking.driver_phone) {
                document.getElementById('driver-name').textContent = tracking.driver_name;
                document.getElementById('driver-vehicle').textContent = tracking.vehicle_number || 'Vehicle Info Not Available';
                document.getElementById('driver-avatar').textContent = tracking.driver_name.split(' ').map(n => n[0]).join('').toUpperCase();
                
                document.getElementById('call-driver').href = `tel:${tracking.driver_phone}`;
                document.getElementById('message-driver').href = `sms:${tracking.driver_phone}`;
                
                driverInfo.style.display = 'block';
            } else {
                driverInfo.style.display = 'none';
            }
        }

        // Update ETA
        function updateETA(tracking) {
            const etaTime = document.getElementById('eta-time');
            
            if (tracking.estimated_arrival) {
                const eta = new Date(tracking.estimated_arrival);
                const now = new Date();
                const diffMinutes = Math.floor((eta - now) / (1000 * 60));
                
                if (diffMinutes > 0) {
                    if (diffMinutes < 60) {
                        etaTime.textContent = `${diffMinutes} min`;
                    } else {
                        const hours = Math.floor(diffMinutes / 60);
                        const minutes = diffMinutes % 60;
                        etaTime.textContent = `${hours}h ${minutes}m`;
                    }
                } else {
                    etaTime.textContent = 'Arriving soon';
                }
            } else {
                etaTime.textContent = 'Calculating...';
            }
        }

        // Refresh tracking data
        function refreshTracking() {
            const refreshBtn = document.getElementById('refresh-btn');
            refreshBtn.classList.add('spinning');
            
            loadTrackingData().finally(() => {
                setTimeout(() => {
                    refreshBtn.classList.remove('spinning');
                }, 1000);
            });
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });

        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            if (trackingInterval) {
                clearInterval(trackingInterval);
            }
        });
    </script>
</body>
</html>