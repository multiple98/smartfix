<?php
// Setup script for email notification system
include('includes/db.php');

echo "Setting up email notification system...\n<br>";

try {
    // Create email_logs table
    $create_email_logs = "
        CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            email_type VARCHAR(50) NOT NULL,
            status ENUM('sent', 'failed') NOT NULL,
            request_id INT DEFAULT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipient (recipient),
            INDEX idx_request_id (request_id),
            INDEX idx_sent_at (sent_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_email_logs);
    echo "✅ email_logs table created successfully\n<br>";
    
    // Update service_requests table to ensure all required columns exist
    $columns_to_add = [
        'reference_number' => "ALTER TABLE service_requests ADD COLUMN reference_number VARCHAR(20) UNIQUE DEFAULT NULL",
        'name' => "ALTER TABLE service_requests ADD COLUMN name VARCHAR(100) DEFAULT NULL",
        'email' => "ALTER TABLE service_requests ADD COLUMN email VARCHAR(100) DEFAULT NULL", 
        'contact' => "ALTER TABLE service_requests ADD COLUMN contact VARCHAR(20) DEFAULT NULL",
        'address' => "ALTER TABLE service_requests ADD COLUMN address TEXT DEFAULT NULL",
        'service_option' => "ALTER TABLE service_requests ADD COLUMN service_option VARCHAR(100) DEFAULT NULL",
        'priority' => "ALTER TABLE service_requests ADD COLUMN priority VARCHAR(20) DEFAULT 'normal'",
        'assigned_technician' => "ALTER TABLE service_requests ADD COLUMN assigned_technician INT DEFAULT NULL",
        'scheduled_date' => "ALTER TABLE service_requests ADD COLUMN scheduled_date DATETIME DEFAULT NULL",
        'completion_date' => "ALTER TABLE service_requests ADD COLUMN completion_date DATETIME DEFAULT NULL",
        'notes' => "ALTER TABLE service_requests ADD COLUMN notes TEXT DEFAULT NULL"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Added column '$column' to service_requests table\n<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "✅ Column '$column' already exists\n<br>";
            } else {
                echo "⚠️ Could not add column '$column': " . $e->getMessage() . "\n<br>";
            }
        }
    }
    
    // Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_service_requests_email ON service_requests(email)",
        "CREATE INDEX IF NOT EXISTS idx_service_requests_reference ON service_requests(reference_number)",
        "CREATE INDEX IF NOT EXISTS idx_service_requests_assigned ON service_requests(assigned_technician)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
            echo "✅ Index created successfully\n<br>";
        } catch (PDOException $e) {
            echo "⚠️ Index creation: " . $e->getMessage() . "\n<br>";
        }
    }
    
    echo "\n<br>✅ Email notification system setup completed successfully!\n<br>";
    echo "<a href='services/request_service.php?type=phone'>Test Service Request</a> | ";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";
    
} catch (PDOException $e) {
    echo "❌ Error setting up email system: " . $e->getMessage() . "\n<br>";
}
?>