<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Get admin information
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['admin_id'] ?? 1;

// Get service request ID
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($request_id <= 0) {
    header('Location: service_requests.php');
    exit;
}

// Get service request details
$request = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        header('Location: service_requests.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching service request: " . $e->getMessage());
}

// Process technician assignment
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_technician'])) {
    $technician_id = intval($_POST['technician_id']);
    
    if ($technician_id <= 0) {
        $error_message = "Please select a technician.";
    } else {
        try {
            // Update service request with technician ID and change status to 'assigned'
            $stmt = $pdo->prepare("UPDATE service_requests SET technician_id = ?, status = 'assigned', assigned_at = NOW() WHERE id = ?");
            $stmt->execute([$technician_id, $request_id]);
            
            // Update technician status to busy
            $stmt = $pdo->prepare("UPDATE technicians SET status = 'busy' WHERE id = ?");
            $stmt->execute([$technician_id]);
            
            // Get technician details
            $stmt = $pdo->prepare("SELECT name, phone, email FROM technicians WHERE id = ?");
            $stmt->execute([$technician_id]);
            $technician = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create notification for customer
            try {
                $notification_query = "INSERT INTO notifications (type, message, is_read, created_at) 
                                      VALUES ('technician_assigned', :message, 0, NOW())";
                $notification_stmt = $pdo->prepare($notification_query);
                $notification_stmt->execute([
                    'message' => "Technician {$technician['name']} has been assigned to your service request ({$request['reference_number']}). They will contact you shortly."
                ]);
            } catch (PDOException $e) {
                // Notifications table might not exist yet, ignore error
            }
            
            $success_message = "Technician assigned successfully. The customer will be notified.";
        } catch (PDOException $e) {
            $error_message = "Error assigning technician: " . $e->getMessage();
        }
    }
}

// Get available technicians based on service type and location
$available_technicians = [];
try {
    // Get the service type and location from the request
    $service_type = $request['service_type'];
    $customer_lat = $request['latitude'];
    $customer_lng = $request['longitude'];
    $customer_address = $request['address'];
    
    // Extract region from address (simple approach)
    $region = '';
    foreach ($zambian_regions as $r) {
        if (stripos($customer_address, $r) !== false) {
            $region = $r;
            break;
        }
    }
    
    // Query to find technicians
    $query = "SELECT t.*, 
              (CASE 
                WHEN t.latitude IS NOT NULL AND t.longitude IS NOT NULL AND ? IS NOT NULL AND ? IS NOT NULL
                THEN (6371 * acos(cos(radians(?)) * cos(radians(t.latitude)) * cos(radians(t.longitude) - radians(?)) + sin(radians(?)) * sin(radians(t.latitude))))
                ELSE 9999
              END) AS distance
              FROM technicians t
              WHERE t.status = 'available'";
    
    $params = [$customer_lat, $customer_lng, $customer_lat, $customer_lng, $customer_lat];
    
    // Filter by specialization if it matches service type
    if (!empty($service_type)) {
        // Map service types to specializations
        $specialization_map = [
            'phone' => 'Phone Repair',
            'computer' => 'Computer Repair',
            'car' => 'Vehicle Repair',
            'plumber' => 'Plumbing',
            'electrician' => 'Electrical',
            'house' => 'General Maintenance'
        ];
        
        if (isset($specialization_map[$service_type])) {
            $query .= " AND t.specialization = ?";
            $params[] = $specialization_map[$service_type];
        }
    }
    
    // Filter by region if we could determine it
    if (!empty($region)) {
        $query .= " AND (t.regions LIKE ? OR t.regions LIKE ? OR t.regions LIKE ?)";
        $params[] = $region;
        $params[] = $region . ',%';
        $params[] = '%,' . $region . ',%';
    }
    
    // Order by distance and then rating
    $query .= " ORDER BY distance ASC, t.rating DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $available_technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error finding technicians: " . $e->getMessage();
}

// Define Zambian regions/provinces for reference
$zambian_regions = [
    'Lusaka',
    'Copperbelt',
    'Central',
    'Eastern',
    'Luapula',
    'Muchinga',
    'Northern',
    'North-Western',
    'Southern',
    'Western'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Technician - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: #004080;
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #004080;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .user-info span {
            font-weight: 500;
        }
        
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 18px;
            color: #004080;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .info-label {
            width: 150px;
            font-weight: bold;
            color: #004080;
        }
        
        .info-value {
            flex: 1;
        }
        
        .technician-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .technician-card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .technician-card:hover {
            border-color: #007BFF;
            transform: translateY(-5px);
        }
        
        .technician-card.selected {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        
        .technician-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .technician-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #007BFF;
        }
        
        .technician-info h3 {
            margin: 0 0 5px;
            font-size: 18px;
            color: #004080;
        }
        
        .technician-specialization {
            font-size: 14px;
            color: #666;
        }
        
        .technician-rating {
            display: flex;
            align-items: center;
            margin-top: 5px;
        }
        
        .technician-rating i {
            color: #ffc107;
            margin-right: 5px;
        }
        
        .technician-details {
            margin-top: 15px;
        }
        
        .technician-detail {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .technician-detail i {
            width: 20px;
            margin-right: 10px;
            color: #007BFF;
        }
        
        .distance-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #007BFF;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .map-container {
            height: 400px;
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .main-content {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 10px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SmartFix Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="service_requests.php" class="active"><i class="fas fa-tools"></i> Service Requests</a></li>
                <li><a href="products.php"><i class="fas fa-shopping-cart"></i> Products</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a></li>
                <li><a href="transport_providers.php"><i class="fas fa-truck"></i> Transport Providers</a></li>
                <li><a href="technicians.php"><i class="fas fa-user-hard-hat"></i> Technicians</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-user-hard-hat"></i> Assign Technician</h1>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="Admin">
                    <span>Admin</span>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h2>Service Request Details</h2>
                    <a href="service_requests.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Requests</a>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Reference Number:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['reference_number']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Customer Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['phone']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Service Type:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['service_type']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Service Option:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['service_option']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['description']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['address']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Requested On:</div>
                    <div class="info-value"><?php echo date('F j, Y, g:i a', strtotime($request['created_at'])); ?></div>
                </div>
                
                <?php if (!empty($request['preferred_date'])): ?>
                <div class="info-row">
                    <div class="info-label">Preferred Date:</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($request['preferred_date'])); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($request['preferred_time'])): ?>
                <div class="info-row">
                    <div class="info-label">Preferred Time:</div>
                    <div class="info-value"><?php echo htmlspecialchars($request['preferred_time']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Customer Location</h2>
                </div>
                
                <div id="map" class="map-container"></div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Available Technicians</h2>
                </div>
                
                <?php if (count($available_technicians) > 0): ?>
                    <p>Select a technician to assign to this service request. Technicians are sorted by proximity to the customer's location.</p>
                    
                    <form method="POST" action="assign_technician.php?id=<?php echo $request_id; ?>">
                        <input type="hidden" id="technician_id" name="technician_id" value="">
                        
                        <div class="technician-cards">
                            <?php foreach ($available_technicians as $tech): ?>
                                <div class="technician-card" onclick="selectTechnician(this, <?php echo $tech['id']; ?>)">
                                    <?php if ($tech['distance'] < 9999): ?>
                                        <div class="distance-badge">
                                            <?php echo number_format($tech['distance'], 1); ?> km away
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="technician-header">
                                        <div class="technician-avatar">
                                            <i class="fas fa-user-hard-hat"></i>
                                        </div>
                                        <div class="technician-info">
                                            <h3><?php echo htmlspecialchars($tech['name']); ?></h3>
                                            <div class="technician-specialization"><?php echo htmlspecialchars($tech['specialization']); ?></div>
                                            <div class="technician-rating">
                                                <i class="fas fa-star"></i>
                                                <?php 
                                                $rating = $tech['rating'] > 0 ? $tech['rating'] : 'New';
                                                echo is_numeric($rating) ? number_format($rating, 1) . '/5.0' : $rating;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="technician-details">
                                        <div class="technician-detail">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($tech['phone']); ?></span>
                                        </div>
                                        <div class="technician-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($tech['address']); ?></span>
                                        </div>
                                        <div class="technician-detail">
                                            <i class="fas fa-globe"></i>
                                            <span><?php echo htmlspecialchars($tech['regions']); ?></span>
                                        </div>
                                        <div class="technician-detail">
                                            <i class="fas fa-briefcase"></i>
                                            <span><?php echo $tech['total_jobs']; ?> completed jobs</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" name="assign_technician" class="btn btn-success" id="assign-btn" disabled>
                                <i class="fas fa-user-check"></i> Assign Selected Technician
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        No available technicians found for this service type and location. Please add technicians or check back later.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Function to select a technician
        function selectTechnician(element, techId) {
            // Remove selected class from all cards
            document.querySelectorAll('.technician-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Set the technician ID in the hidden input
            document.getElementById('technician_id').value = techId;
            
            // Enable the assign button
            document.getElementById('assign-btn').disabled = false;
        }
        
        // Initialize map when the page loads
        function initMap() {
            // Customer location
            var customerLat = <?php echo !empty($request['latitude']) ? $request['latitude'] : '-15.4167'; ?>;
            var customerLng = <?php echo !empty($request['longitude']) ? $request['longitude'] : '28.2833'; ?>;
            
            // Create map centered on customer location
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: customerLat, lng: customerLng},
                zoom: 12
            });
            
            // Add marker for customer location
            var customerMarker = new google.maps.Marker({
                position: {lat: customerLat, lng: customerLng},
                map: map,
                title: 'Customer Location',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                }
            });
            
            // Add info window for customer
            var customerInfo = new google.maps.InfoWindow({
                content: '<div><strong>Customer:</strong> <?php echo htmlspecialchars($request['name']); ?></div>' +
                         '<div><strong>Address:</strong> <?php echo htmlspecialchars($request['address']); ?></div>'
            });
            
            customerMarker.addListener('click', function() {
                customerInfo.open(map, customerMarker);
            });
            
            // Add markers for technicians
            <?php foreach ($available_technicians as $tech): ?>
                <?php if (!empty($tech['latitude']) && !empty($tech['longitude'])): ?>
                    var techMarker = new google.maps.Marker({
                        position: {lat: <?php echo $tech['latitude']; ?>, lng: <?php echo $tech['longitude']; ?>},
                        map: map,
                        title: '<?php echo htmlspecialchars($tech['name']); ?>',
                        icon: {
                            url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                        }
                    });
                    
                    var techInfo = new google.maps.InfoWindow({
                        content: '<div><strong>Technician:</strong> <?php echo htmlspecialchars($tech['name']); ?></div>' +
                                 '<div><strong>Specialization:</strong> <?php echo htmlspecialchars($tech['specialization']); ?></div>' +
                                 '<div><strong>Phone:</strong> <?php echo htmlspecialchars($tech['phone']); ?></div>' +
                                 '<div><strong>Distance:</strong> <?php echo number_format($tech['distance'], 1); ?> km</div>'
                    });
                    
                    techMarker.addListener('click', function() {
                        techInfo.open(map, techMarker);
                    });
                <?php endif; ?>
            <?php endforeach; ?>
        }
    </script>
    
    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>