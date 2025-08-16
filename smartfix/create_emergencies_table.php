<?php
include 'includes/db.php';

try {
    // Create emergencies table
    $create_table = "CREATE TABLE IF NOT EXISTS emergencies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
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
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB";
    
    $pdo->exec($create_table);
    echo "✅ Emergencies table created successfully!\n";
    
    // Check if there are emergency service requests to migrate
    $check_emergency_requests = "SELECT * FROM service_requests WHERE is_emergency = 1";
    $stmt = $pdo->prepare($check_emergency_requests);
    $stmt->execute();
    $emergency_requests = $stmt->fetchAll();
    
    if (count($emergency_requests) > 0) {
        echo "Found " . count($emergency_requests) . " emergency service requests to migrate...\n";
        
        // Migrate existing emergency requests to the emergencies table
        $insert_emergency = "INSERT INTO emergencies (message, location, status, phone, email, name, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $pdo->prepare($insert_emergency);
        
        foreach ($emergency_requests as $request) {
            // Map service request fields to emergency fields
            $message = $request['description'];
            $location = $request['address'];
            $status = ucfirst($request['status']); // Convert 'pending' to 'Pending'
            $phone = $request['phone'];
            $email = $request['email'];
            $name = $request['name'];
            $created_at = $request['created_at'];
            
            $insert_stmt->execute([$message, $location, $status, $phone, $email, $name, $created_at]);
        }
        
        echo "✅ Migrated " . count($emergency_requests) . " emergency requests to emergencies table!\n";
    }
    
    // Insert some sample emergency data if table is empty
    $count_check = "SELECT COUNT(*) as count FROM emergencies";
    $count_stmt = $pdo->prepare($count_check);
    $count_stmt->execute();
    $count = $count_stmt->fetch()['count'];
    
    if ($count == 0) {
        echo "Adding sample emergency data...\n";
        
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
                'message' => 'Gas leak detected in kitchen area',
                'location' => '789 Restaurant Row, City Center, Lusaka', 
                'status' => 'Resolved',
                'phone' => '+260 775 555 123',
                'email' => 'restaurant@example.com',
                'name' => 'Restaurant Manager'
            ]
        ];
        
        $insert_sample = "INSERT INTO emergencies (message, location, status, phone, email, name, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $sample_stmt = $pdo->prepare($insert_sample);
        
        foreach ($sample_emergencies as $emergency) {
            $sample_stmt->execute([
                $emergency['message'],
                $emergency['location'], 
                $emergency['status'],
                $emergency['phone'],
                $emergency['email'],
                $emergency['name']
            ]);
        }
        
        echo "✅ Added " . count($sample_emergencies) . " sample emergency records!\n";
    }
    
    echo "\n🎉 Emergency system setup completed successfully!\n";
    echo "You can now access the emergency dashboard without errors.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit;
}
?>