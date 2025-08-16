<?php
// Direct approach to create emergencies table
echo "<h2>Creating Emergency Table...</h2>";

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smartfix';

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p>✅ Connected to database successfully</p>";
    
    // Create the emergencies table
    $sql = "CREATE TABLE IF NOT EXISTS emergencies (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Emergencies table created successfully!</p>";
        
        // Insert sample data
        $sample_data = [
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
        
        $insert_sql = "INSERT INTO emergencies (message, location, status, phone, email, name) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        $inserted_count = 0;
        foreach ($sample_data as $data) {
            $stmt->bind_param("ssssss", 
                $data['message'], 
                $data['location'], 
                $data['status'], 
                $data['phone'], 
                $data['email'], 
                $data['name']
            );
            
            if ($stmt->execute()) {
                $inserted_count++;
            }
        }
        
        echo "<p>✅ Inserted $inserted_count sample emergency records</p>";
        
        // Test the table
        $test_sql = "SELECT COUNT(*) as count FROM emergencies";
        $result = $conn->query($test_sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>✅ Table verification successful. Total records: " . $row['count'] . "</p>";
        }
        
        echo "<hr>";
        echo "<h3>🎉 Emergency System Fixed!</h3>";
        echo "<p>The emergency dashboard should now work properly.</p>";
        echo "<p><a href='admin/emergency_dashboard.php' target='_blank'>🔗 Test Emergency Dashboard</a></p>";
        echo "<p><a href='services/emergency.php' target='_blank'>🔗 Test Emergency Service Form</a></p>";
        
    } else {
        echo "<p>❌ Error creating table: " . $conn->error . "</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>