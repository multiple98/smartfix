<?php
// This script updates the service_requests table structure if needed
include('includes/db.php');

try {
    // Check if the service_requests table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'service_requests'");
    $table_exists = $check_table->rowCount() > 0;
    
    if ($table_exists) {
        // Check if reference_number column exists
        $check_column = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'reference_number'");
        $reference_number_exists = $check_column->rowCount() > 0;
        
        if (!$reference_number_exists) {
            // Add reference_number column
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN reference_number VARCHAR(20) AFTER id");
            echo "Added reference_number column to service_requests table.<br>";
            
            // Generate reference numbers for existing records
            $get_records = $pdo->query("SELECT id FROM service_requests");
            $records = $get_records->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($records as $record) {
                $id = $record['id'];
                $reference_number = 'SF' . str_pad($id, 6, '0', STR_PAD_LEFT);
                
                $update = $pdo->prepare("UPDATE service_requests SET reference_number = ? WHERE id = ?");
                $update->execute([$reference_number, $id]);
            }
            
            echo "Generated reference numbers for " . count($records) . " existing service requests.<br>";
        }
        
        // Check if completed_at column exists
        $check_column = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'completed_at'");
        $completed_at_exists = $check_column->rowCount() > 0;
        
        if (!$completed_at_exists) {
            // Add completed_at column
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN completed_at DATETIME AFTER technician_id");
            echo "Added completed_at column to service_requests table.<br>";
            
            // Set completed_at for completed requests
            $pdo->exec("UPDATE service_requests SET completed_at = request_date WHERE status = 'completed'");
            echo "Updated completed_at timestamps for completed service requests.<br>";
        }
        
        // Check if user_id column exists
        $check_column = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'user_id'");
        $user_id_exists = $check_column->rowCount() > 0;
        
        if (!$user_id_exists) {
            // Add user_id column
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN user_id INT AFTER technician_id");
            echo "Added user_id column to service_requests table.<br>";
        }
        
        echo "Service requests table structure is now up to date.<br>";
    } else {
        // Create the service_requests table
        $create_table = "CREATE TABLE service_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reference_number VARCHAR(20),
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            service_type VARCHAR(50) NOT NULL,
            service_option VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            address TEXT,
            preferred_date DATE,
            preferred_time VARCHAR(20),
            status VARCHAR(20) DEFAULT 'pending',
            technician_id INT,
            user_id INT,
            completed_at DATETIME,
            request_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        
        echo "Created service_requests table.<br>";
    }
    
    // Check if service_updates table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'service_updates'");
    $table_exists = $check_table->rowCount() > 0;
    
    if (!$table_exists) {
        // Create service_updates table
        $create_table = "CREATE TABLE service_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_request_id INT NOT NULL,
            update_text TEXT NOT NULL,
            status VARCHAR(20),
            created_by INT,
            created_by_type ENUM('admin', 'technician') DEFAULT 'technician',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE
        )";
        $pdo->exec($create_table);
        
        // Add index for faster queries
        $pdo->exec("CREATE INDEX idx_service_request_id ON service_updates(service_request_id)");
        
        echo "Created service_updates table.<br>";
    }
    
    echo "Database update completed successfully.";
    
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>