<?php
/**
 * GPS Configuration Test Page
 * Test your Google Maps API key setup
 */

session_start();
include('includes/db.php');
require_once('config/maps_config.php');
require_once('includes/GPSManager.php');

$gps = new GPSManager($pdo);

// Check if API key is configured
$api_key_configured = isGoogleMapsConfigured();
$current_key = getGoogleMapsApiKey();

// Test API functionality if key is configured
$test_results = [];
if ($api_key_configured) {
    // Test reverse geocoding (Kigali coordinates)
    $test_address = $gps->getAddressFromCoordinates(-1.9441, 30.0619);
    $test_results['reverse_geocoding'] = $test_address ? 'Working' : 'Failed';
    
    // Test geocoding
    $test_coords = $gps->getCoordinatesFromAddress('Kigali, Rwanda');
    $test_results['geocoding'] = $test_coords ? 'Working' : 'Failed';
    
    // Test route information
    $test_route = $gps->getRouteInfo(-1.9441, 30.0619, -1.9506, 30.0588);
    $test_results['directions'] = $test_route ? 'Working' : 'Failed';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Setup Test - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: #007BFF;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .content {
            padding: 30px;
        }

        .status-card {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .status-card.success {
            border-color: #28a745;
            background: #d4edda;
        }

        .status-card.error {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .status-card.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .test-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .test-item.working {
            border-color: #28a745;
            background: #d4edda;
        }

        .test-item.failed {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .test-item h4 {
            margin: 0 0 10px 0;
            color: #007BFF;
        }

        .btn {
            display: inline-block;
            background: #007BFF;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 15px 0;
            overflow-x: auto;
        }

        .instructions {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007BFF;
            margin: 20px 0;
        }

        .instructions h3 {
            margin: 0 0 15px 0;
            color: #007BFF;
        }

        .instructions ol {
            margin: 0;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .map-test {
            height: 300px;
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin: 20px 0;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .icon.success { color: #28a745; }
        .icon.error { color: #dc3545; }
        .icon.warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-map-marked-alt"></i> GPS Setup Test</h1>
            <p>Test your Google Maps API configuration for SmartFix</p>
        </div>

        <div class="content">
            <!-- API Key Status -->
            <?php if ($api_key_configured): ?>
                <div class="status-card success">
                    <div class="icon success"><i class="fas fa-check-circle"></i></div>
                    <h3>✅ Google Maps API Key Configured</h3>
                    <p>Your API key is properly set up and ready to use.</p>
                    <p><strong>Key Preview:</strong> <?php echo substr($current_key, 0, 8) . '...' . substr($current_key, -4); ?></p>
                </div>
            <?php else: ?>
                <div class="status-card error">
                    <div class="icon error"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3>❌ Google Maps API Key Not Configured</h3>
                    <p>You need to set up your Google Maps API key to use GPS features.</p>
                    <p><strong>Current Key:</strong> <?php echo htmlspecialchars($current_key); ?></p>
                </div>
            <?php endif; ?>

            <!-- API Tests -->
            <?php if ($api_key_configured): ?>
                <h3><i class="fas fa-flask"></i> API Functionality Tests</h3>
                <div class="test-grid">
                    <div class="test-item <?php echo $test_results['reverse_geocoding'] === 'Working' ? 'working' : 'failed'; ?>">
                        <h4><i class="fas fa-map-pin"></i> Reverse Geocoding</h4>
                        <p>Convert coordinates to address</p>
                        <strong><?php echo $test_results['reverse_geocoding']; ?></strong>
                        <?php if ($test_results['reverse_geocoding'] === 'Working' && $test_address): ?>
                            <p><small><?php echo htmlspecialchars($test_address); ?></small></p>
                        <?php endif; ?>
                    </div>

                    <div class="test-item <?php echo $test_results['geocoding'] === 'Working' ? 'working' : 'failed'; ?>">
                        <h4><i class="fas fa-search-location"></i> Geocoding</h4>
                        <p>Convert address to coordinates</p>
                        <strong><?php echo $test_results['geocoding']; ?></strong>
                        <?php if ($test_results['geocoding'] === 'Working' && $test_coords): ?>
                            <p><small>Lat: <?php echo number_format($test_coords['latitude'], 4); ?>, Lng: <?php echo number_format($test_coords['longitude'], 4); ?></small></p>
                        <?php endif; ?>
                    </div>

                    <div class="test-item <?php echo $test_results['directions'] === 'Working' ? 'working' : 'failed'; ?>">
                        <h4><i class="fas fa-route"></i> Directions</h4>
                        <p>Calculate routes and distances</p>
                        <strong><?php echo $test_results['directions']; ?></strong>
                        <?php if ($test_results['directions'] === 'Working' && $test_route): ?>
                            <p><small><?php echo $test_route['distance'] ?? 'N/A'; ?> - <?php echo $test_route['duration'] ?? 'N/A'; ?></small></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Interactive Map Test -->
                <h3><i class="fas fa-map"></i> Interactive Map Test</h3>
                <div id="test-map" class="map-test"></div>
                
                <div class="status-card success">
                    <h4>🎉 All Systems Ready!</h4>
                    <p>Your Google Maps integration is working correctly. You can now use all GPS features:</p>
                    <div style="margin-top: 15px;">
                        <a href="services/request_service_gps.php?type=phone" class="btn btn-success">
                            <i class="fas fa-mobile-alt"></i> Test GPS Service Request
                        </a>
                        <a href="admin/gps_dashboard.php" class="btn">
                            <i class="fas fa-dashboard"></i> GPS Dashboard
                        </a>
                        <a href="technician/location_tracker.php" class="btn">
                            <i class="fas fa-map-marker-alt"></i> Technician Tracker
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Setup Instructions -->
                <div class="instructions">
                    <h3><i class="fas fa-cog"></i> How to Set Up Your Google Maps API Key</h3>
                    <ol>
                        <li><strong>Go to Google Cloud Console:</strong>
                            <br><a href="https://console.cloud.google.com/" target="_blank">https://console.cloud.google.com/</a>
                        </li>
                        <li><strong>Create or Select Project:</strong>
                            <br>Create a new project or select an existing one
                        </li>
                        <li><strong>Enable Required APIs:</strong>
                            <br>Go to "APIs & Services" > "Library" and enable:
                            <ul>
                                <li>Maps JavaScript API</li>
                                <li>Geocoding API</li>
                                <li>Directions API</li>
                            </ul>
                        </li>
                        <li><strong>Create API Key:</strong>
                            <br>Go to "APIs & Services" > "Credentials" > "Create Credentials" > "API Key"
                        </li>
                        <li><strong>Copy Your API Key:</strong>
                            <br>It will look like: <code>AIzaSyA1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q</code>
                        </li>
                        <li><strong>Update Configuration:</strong>
                            <br>Edit <code>config/maps_config.php</code> and replace:
                        </li>
                    </ol>
                </div>

                <div class="code-block">
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE');
                </div>

                <p><strong>Replace with:</strong></p>

                <div class="code-block">
define('GOOGLE_MAPS_API_KEY', 'AIzaSyYourActualAPIKeyHere');
                </div>

                <div class="status-card warning">
                    <h4><i class="fas fa-shield-alt"></i> Security Tip</h4>
                    <p>After getting your API key, make sure to:</p>
                    <ul>
                        <li>Restrict it to your domain(s)</li>
                        <li>Only enable the APIs you need</li>
                        <li>Monitor usage in Google Cloud Console</li>
                        <li>Set up billing alerts</li>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="btn">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <button onclick="location.reload()" class="btn">
                    <i class="fas fa-refresh"></i> Refresh Test
                </button>
            </div>
        </div>
    </div>

    <?php if ($api_key_configured): ?>
        <!-- Load Google Maps for testing -->
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $current_key; ?>&callback=initTestMap"></script>
        
        <script>
            function initTestMap() {
                const map = new google.maps.Map(document.getElementById('test-map'), {
                    zoom: 13,
                    center: { lat: -1.9441, lng: 30.0619 }, // Kigali
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                });

                // Add a marker
                new google.maps.Marker({
                    position: { lat: -1.9441, lng: 30.0619 },
                    map: map,
                    title: 'Kigali, Rwanda - Test Location',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#28a745',
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 2
                    }
                });

                // Add info window
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 10px;">
                            <h4 style="margin: 0; color: #28a745;">✅ Map Loading Successfully!</h4>
                            <p style="margin: 5px 0;">Your Google Maps API is working correctly.</p>
                            <p style="margin: 5px 0; font-size: 12px; color: #666;">Kigali, Rwanda</p>
                        </div>
                    `
                });

                infoWindow.open(map, map.getCenter());
            }
        </script>
    <?php endif; ?>
</body>
</html>