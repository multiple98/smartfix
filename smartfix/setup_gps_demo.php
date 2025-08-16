<?php
session_start();
include('includes/db.php');
require_once('includes/GPSManager.php');

// Simple demo authentication
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_name'] = 'GPS Demo Admin';

echo "<!DOCTYPE html>
<html>
<head>
    <title>GPS Dashboard Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007BFF; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 10px 5px 10px 0; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h1>🚀 SmartFix GPS Dashboard Setup</h1>";

try {
    $gps = new GPSManager($pdo);
    
    echo "<h2>📊 Current Database Status</h2>";
    
    // Check existing data
    $techCount = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
    $requestCount = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
    
    echo "<div class='success'>✅ Connected to database successfully</div>";
    echo "<div class='success'>👥 Found {$techCount} technicians in database</div>";
    echo "<div class='success'>📋 Found {$requestCount} service requests in database</div>";
    
    // Check if we have sample technicians
    if ($techCount < 5) {
        echo "<h3>Creating Sample Technicians...</h3>";
        
        $sample_technicians = [
            ['John Smith', 'john@smartfix.com', '0788123456', 'phone,computer', 4.5, 'active'],
            ['Mary Johnson', 'mary@smartfix.com', '0788234567', 'car,mechanical', 4.8, 'active'],
            ['Robert Brown', 'robert@smartfix.com', '0788345678', 'plumber,electrical', 4.3, 'active'],
            ['Sarah Wilson', 'sarah@smartfix.com', '0788456789', 'phone,computer', 4.7, 'active'],
            ['David Lee', 'david@smartfix.com', '0788567890', 'electrical,mechanical', 4.6, 'active']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO technicians (name, email, phone, specialization, rating, status) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($sample_technicians as $tech) {
            try {
                $stmt->execute($tech);
                echo "<div class='success'>➕ Created technician: {$tech[0]}</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error creating {$tech[0]}: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Check if we have sample service requests  
    if ($requestCount < 5) {
        echo "<h3>Creating Sample Service Requests...</h3>";
        
        $sample_requests = [
            ['Alice Cooper', '0783111222', 'alice@email.com', 'phone', 'screen_repair', 'Cracked iPhone screen needs replacement', 'pending'],
            ['Bob Martin', '0783222333', 'bob@email.com', 'computer', 'virus_removal', 'Computer running very slow, possible virus', 'in_progress'], 
            ['Carol White', '0783333444', 'carol@email.com', 'car', 'engine_repair', 'Car engine making strange noises', 'pending'],
            ['Dan Black', '0783444555', 'dan@email.com', 'plumber', 'pipe_repair', 'Kitchen sink pipe is leaking', 'assigned'],
            ['Eva Green', '0783555666', 'eva@email.com', 'electrician', 'wiring', 'Need electrical wiring for new room', 'pending']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO service_requests (name, phone, email, service_type, service_option, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($sample_requests as $req) {
            try {
                $stmt->execute($req);
                echo "<div class='success'>📋 Created service request: {$req[0]} - {$req[3]} repair</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Error creating request for {$req[0]}: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Add GPS coordinates
    echo "<h3>🗺️ Adding GPS Coordinates...</h3>";
    
    // Kigali area coordinates
    $kigali_coords = [
        [-1.9441, 30.0619, "Kigali City Center"], 
        [-1.9506, 30.1044, "Kacyiru District"],
        [-1.9378, 30.0851, "Kimisagara Area"], 
        [-1.9702, 30.1044, "Gisozi District"],
        [-1.9219, 30.0877, "Nyamirambo Area"]
    ];
    
    // Update technician locations
    $technicians = $pdo->query("SELECT id FROM technicians ORDER BY id LIMIT 5")->fetchAll();
    foreach ($technicians as $index => $tech) {
        if (isset($kigali_coords[$index])) {
            $lat = $kigali_coords[$index][0];
            $lng = $kigali_coords[$index][1]; 
            $location = $kigali_coords[$index][2];
            
            $success = $gps->updateTechnicianLocation($tech['id'], $lat, $lng, 12);
            if ($success) {
                echo "<div class='success'>📍 Updated technician {$tech['id']} location: {$location}</div>";
            }
        }
    }
    
    // Update service request locations
    $requests = $pdo->query("SELECT id FROM service_requests ORDER BY id LIMIT 5")->fetchAll();
    foreach ($requests as $index => $req) {
        if (isset($kigali_coords[$index])) {
            // Add slight variation for customer locations
            $lat = $kigali_coords[$index][0] + (rand(-20, 20) * 0.0001);
            $lng = $kigali_coords[$index][1] + (rand(-20, 20) * 0.0001);
            $location = $kigali_coords[$index][2] . " (Customer)";
            
            $success = $gps->saveCustomerLocation($req['id'], $lat, $lng, $location, 15);
            if ($success) {
                echo "<div class='success'>🏠 Updated service request {$req['id']} location: {$location}</div>";
            }
        }
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>✅ All sample data has been created successfully</div>";
    echo "<div class='success'>🗺️ GPS coordinates have been added for demo purposes</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. <strong>For Demo Mode:</strong> Click the button below to view the GPS dashboard with sample data</p>";
    echo "<p>2. <strong>For Full Features:</strong> Get a Google Maps API key and update config/maps_config.php</p>";
    
    echo "<br>";
    echo "<a href='admin/gps_dashboard.php' class='btn'>🚀 View GPS Dashboard</a>";
    echo "<a href='admin/gps_dashboard_demo.php' class='btn'>📊 View Demo Dashboard (No API Key Needed)</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Setup Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>