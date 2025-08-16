<?php
session_start();
include('includes/db.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Form Submission Debug</h1>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Process the form data
    try {
        // Get form data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : '';
        $regions = isset($_POST['regions']) ? $_POST['regions'] : [];
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
        
        echo "<h2>Processed Form Data:</h2>";
        echo "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        echo "<p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p><strong>Specialization:</strong> " . htmlspecialchars($specialization) . "</p>";
        echo "<p><strong>Regions:</strong> " . htmlspecialchars(implode(', ', $regions)) . "</p>";
        echo "<p><strong>Address:</strong> " . htmlspecialchars($address) . "</p>";
        echo "<p><strong>Bio:</strong> " . htmlspecialchars($bio) . "</p>";
        
        // Check if technicians table exists
        $check_table = $pdo->query("SHOW TABLES LIKE 'technicians'");
        if ($check_table->rowCount() == 0) {
            echo "<p style='color:red'>Technicians table does not exist. Creating it now...</p>";
            
            // Create technicians table
            $create_table = "CREATE TABLE technicians (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(100),
                specialization VARCHAR(100) NOT NULL,
                regions TEXT NOT NULL,
                address TEXT,
                latitude VARCHAR(20),
                longitude VARCHAR(20),
                status ENUM('available', 'busy', 'offline') DEFAULT 'available',
                bio TEXT,
                rating DECIMAL(3,1) DEFAULT 0,
                total_jobs INT DEFAULT 0,
                user_id INT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $pdo->exec($create_table);
            echo "<p style='color:green'>Technicians table created successfully.</p>";
        } else {
            echo "<p style='color:green'>Technicians table already exists.</p>";
            
            // Check table structure
            $result = $pdo->query("DESCRIBE technicians");
            echo "<h3>Technicians Table Structure:</h3>";
            echo "<pre>";
            $columns = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
                print_r($row);
            }
            echo "</pre>";
            
            // Check if bio column exists
            if (!in_array('bio', $columns)) {
                echo "<p style='color:red'>Bio column is missing! Adding it now...</p>";
                $pdo->exec("ALTER TABLE technicians ADD COLUMN bio TEXT AFTER status");
                echo "<p style='color:green'>Bio column added successfully.</p>";
            } else {
                echo "<p style='color:green'>Bio column exists.</p>";
            }
        }
        
        // Convert regions array to comma-separated string
        $regions_str = implode(',', $regions);
        
        // Insert new technician
        echo "<h2>Attempting to insert data:</h2>";
        
        $query = "INSERT INTO technicians (name, phone, email, specialization, regions, address, bio, status) 
                  VALUES (:name, :phone, :email, :specialization, :regions, :address, :bio, 'available')";
        $stmt = $pdo->prepare($query);
        
        $params = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'specialization' => $specialization,
            'regions' => $regions_str,
            'address' => $address,
            'bio' => $bio
        ];
        
        echo "<pre>";
        print_r($params);
        echo "</pre>";
        
        $result = $stmt->execute($params);
        
        if ($result) {
            echo "<p style='color:green'>Technician registered successfully! ID: " . $pdo->lastInsertId() . "</p>";
        } else {
            echo "<p style='color:red'>Failed to register technician.</p>";
            echo "<pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
        }
        
    } catch (PDOException $e) {
        echo "<h2 style='color:red'>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        
        // If it's a bio column error, suggest reset
        if (strpos($e->getMessage(), "Unknown column 'bio'") !== false) {
            echo "<p>This appears to be an issue with the 'bio' column missing from the technicians table.</p>";
            echo "<p><a href='reset_technicians_table.php' style='color:blue;'>Click here to reset the technicians table</a></p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        
        h1, h2, h3 {
            color: #004080;
        }
        
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        
        form {
            max-width: 600px;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button {
            background-color: #004080;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
        }
        
        .checkbox-item input {
            width: auto;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <h1>Debug Form Submission</h1>
    
    <p>This page helps debug form submission issues. Fill out the form below to test the technician registration process.</p>
    
    <form method="POST" action="">
        <div>
            <label for="name">Full Name*</label>
            <input type="text" name="name" id="name" required value="Test Technician">
        </div>
        
        <div>
            <label for="phone">Phone Number*</label>
            <input type="tel" name="phone" id="phone" required value="+260 977123456">
        </div>
        
        <div>
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" value="test@example.com">
        </div>
        
        <div>
            <label for="specialization">Primary Specialization*</label>
            <select name="specialization" id="specialization" required>
                <option value="">Select Your Specialization</option>
                <option value="Phone Repair" selected>Phone Repair</option>
                <option value="Computer Repair">Computer Repair</option>
                <option value="Vehicle Repair">Vehicle Repair</option>
                <option value="Plumbing">Plumbing</option>
                <option value="Electrical">Electrical</option>
                <option value="Home Appliance Repair">Home Appliance Repair</option>
                <option value="General Maintenance">General Maintenance</option>
                <option value="HVAC">HVAC</option>
                <option value="Carpentry">Carpentry</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div>
            <label>Regions You Serve*</label>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" name="regions[]" id="region_Lusaka" value="Lusaka" checked>
                    <label for="region_Lusaka">Lusaka</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="regions[]" id="region_Copperbelt" value="Copperbelt" checked>
                    <label for="region_Copperbelt">Copperbelt</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="regions[]" id="region_Central" value="Central">
                    <label for="region_Central">Central</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="regions[]" id="region_Eastern" value="Eastern">
                    <label for="region_Eastern">Eastern</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="regions[]" id="region_Luapula" value="Luapula">
                    <label for="region_Luapula">Luapula</label>
                </div>
            </div>
        </div>
        
        <div>
            <label for="address">Business Address</label>
            <textarea name="address" id="address">123 Test Street, Lusaka</textarea>
        </div>
        
        <div>
            <label for="bio">About Yourself & Your Experience*</label>
            <textarea name="bio" id="bio" required>This is a test bio for debugging purposes.</textarea>
        </div>
        
        <button type="submit" name="register_technician">Submit Test Form</button>
    </form>
    
    <p><a href="reset_technicians_table.php">Reset Technicians Table</a> | <a href="register_technician.php">Go to Regular Registration Form</a></p>
</body>
</html>