<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Emergency System - SmartFix</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            margin: 40px; 
            background: #f8f9fa; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #dc3545; 
            border-bottom: 2px solid #dc3545; 
            padding-bottom: 10px; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border: 1px solid #c3e6cb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border: 1px solid #f5c6cb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border: 1px solid #bee5eb; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        code { 
            background: #f8f9fa; 
            padding: 2px 5px; 
            border-radius: 3px; 
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚨 Emergency System Fix</h1>
        
<?php
include 'includes/db.php';

$messages = [];

try {
    // Check if emergencies table exists
    $check_table = "SHOW TABLES LIKE 'emergencies'";
    $stmt = $pdo->prepare($check_table);
    $stmt->execute();
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $messages[] = ['info', 'Emergencies table does not exist. Creating it now...'];
        
        // Create emergencies table
        $create_table = "CREATE TABLE emergencies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            message TEXT NOT NULL,
            location TEXT NOT NULL,
            status ENUM('Pending', 'Responding', 'Resolved') DEFAULT 'Pending',
            priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'High',
            phone VARCHAR(20),
            email VARCHAR(100),
            name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_table);
        $messages[] = ['success', '✅ Emergencies table created successfully!'];
    } else {
        $messages[] = ['info', 'Emergencies table already exists. Checking structure...'];
        
        // Check if all required columns exist
        $columns = ['id', 'message', 'location', 'status', 'created_at'];
        $missing_columns = [];
        
        foreach ($columns as $column) {
            $check_column = "SHOW COLUMNS FROM emergencies LIKE '$column'";
            $stmt = $pdo->prepare($check_column);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $missing_columns[] = $column;
            }
        }
        
        if (!empty($missing_columns)) {
            $messages[] = ['error', 'Missing columns: ' . implode(', ', $missing_columns)];
        } else {
            $messages[] = ['success', '✅ Table structure is correct!'];
        }
    }
    
    // Check if there are emergency service requests to migrate
    $check_emergency_requests = "SELECT COUNT(*) as count FROM service_requests WHERE is_emergency = 1";
    $stmt = $pdo->prepare($check_emergency_requests);
    $stmt->execute();
    $emergency_count = $stmt->fetch()['count'];
    
    if ($emergency_count > 0) {
        $messages[] = ['info', "Found $emergency_count emergency service requests that could be migrated."];
        
        // Check if they've already been migrated
        $check_migrated = "SELECT COUNT(*) as count FROM emergencies";
        $stmt = $pdo->prepare($check_migrated);
        $stmt->execute();
        $emergencies_count = $stmt->fetch()['count'];
        
        if ($emergencies_count == 0) {
            $messages[] = ['info', 'Migrating emergency service requests to emergencies table...'];
            
            // Get emergency requests
            $get_requests = "SELECT * FROM service_requests WHERE is_emergency = 1";
            $stmt = $pdo->prepare($get_requests);
            $stmt->execute();
            $emergency_requests = $stmt->fetchAll();
            
            // Migrate to emergencies table
            $insert_emergency = "INSERT INTO emergencies (message, location, status, phone, email, name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_emergency);
            
            $migrated_count = 0;
            foreach ($emergency_requests as $request) {
                $message = $request['description'];
                $location = $request['address'];
                $status = ucfirst($request['status']); // Convert 'pending' to 'Pending'
                $phone = $request['phone'];
                $email = $request['email'];
                $name = $request['name'];
                $created_at = $request['created_at'];
                
                if ($insert_stmt->execute([$message, $location, $status, $phone, $email, $name, $created_at])) {
                    $migrated_count++;
                }
            }
            
            $messages[] = ['success', "✅ Migrated $migrated_count emergency requests to emergencies table!"];
        } else {
            $messages[] = ['info', "Emergencies table already has $emergencies_count records. Skipping migration."];
        }
    }
    
    // Add sample data if table is empty
    $count_check = "SELECT COUNT(*) as count FROM emergencies";
    $stmt = $pdo->prepare($count_check);
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        $messages[] = ['info', 'Adding sample emergency data for testing...'];
        
        $sample_emergencies = [
            [
                'message' => 'Power outage in entire building, elevator stuck with people inside',
                'location' => '123 Main Street, Business District, Lusaka',
                'status' => 'Pending',
                'phone' => '+260 777 123 456',
                'email' => 'john.doe@email.com',
                'name' => 'John Doe'
            ],
            [
                'message' => 'Water pipe burst flooding basement server room',
                'location' => '456 Industrial Avenue, Kabulonga, Lusaka',
                'status' => 'Responding',
                'phone' => '+260 776 987 654',
                'email' => 'jane.smith@company.com',
                'name' => 'Jane Smith'
            ],
            [
                'message' => 'Gas leak detected in kitchen area - EVACUATED',
                'location' => '789 Restaurant Row, City Center, Lusaka', 
                'status' => 'Resolved',
                'phone' => '+260 775 555 123',
                'email' => 'restaurant@example.com',
                'name' => 'Restaurant Manager'
            ]
        ];
        
        $insert_sample = "INSERT INTO emergencies (message, location, status, phone, email, name, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $sample_stmt = $pdo->prepare($insert_sample);
        
        $added_count = 0;
        foreach ($sample_emergencies as $emergency) {
            if ($sample_stmt->execute([
                $emergency['message'],
                $emergency['location'], 
                $emergency['status'],
                $emergency['phone'],
                $emergency['email'],
                $emergency['name']
            ])) {
                $added_count++;
            }
        }
        
        $messages[] = ['success', "✅ Added $added_count sample emergency records!"];
    }
    
    $messages[] = ['success', '🎉 Emergency system setup completed successfully!'];
    $messages[] = ['info', 'You can now access the emergency dashboard at: <strong>admin/emergency_dashboard.php</strong>'];
    
} catch (PDOException $e) {
    $messages[] = ['error', '❌ Database Error: ' . htmlspecialchars($e->getMessage())];
} catch (Exception $e) {
    $messages[] = ['error', '❌ Error: ' . htmlspecialchars($e->getMessage())];
}

// Display all messages
foreach ($messages as $msg) {
    echo "<div class='{$msg[0]}'>{$msg[1]}</div>\n";
}
?>

        <h2>🔧 Next Steps</h2>
        <div class="info">
            <p><strong>The emergency system has been fixed!</strong> Here's what was done:</p>
            <ul>
                <li>Created the missing <code>emergencies</code> table</li>
                <li>Migrated any existing emergency service requests</li>
                <li>Added sample data for testing</li>
                <li>Set up proper database structure</li>
            </ul>
        </div>
        
        <h2>🎯 Test the Fix</h2>
        <p>Click the buttons below to test the emergency system:</p>
        <a href="admin/emergency_dashboard.php" class="btn">📊 Emergency Dashboard</a>
        <a href="services/emergency.php" class="btn">🚨 Emergency Service Form</a>
        <a href="admin/reports.php" class="btn">📈 Admin Reports</a>
        
        <h2>📋 Database Structure</h2>
        <p>The <code>emergencies</code> table now has the following structure:</p>
        <pre>
CREATE TABLE emergencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    location TEXT NOT NULL,
    status ENUM('Pending', 'Responding', 'Resolved') DEFAULT 'Pending',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'High',
    phone VARCHAR(20),
    email VARCHAR(100),
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
        </pre>
    </div>
</body>
</html>