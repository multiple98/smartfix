<?php
include('includes/db.php');

// Check if technicians table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'technicians'")->fetchAll();
    if (count($result) > 0) {
        echo "Technicians table exists\n";
    } else {
        echo "Technicians table does not exist\n";
        
        // Create technicians table
        $create_table = "CREATE TABLE technicians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20) NOT NULL,
            specialization VARCHAR(50) NOT NULL,
            regions TEXT,
            address TEXT,
            latitude VARCHAR(20),
            longitude VARCHAR(20),
            status ENUM('available', 'busy', 'offline') DEFAULT 'available',
            rating DECIMAL(3,1) DEFAULT 0,
            total_jobs INT DEFAULT 0,
            user_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created technicians table\n";
    }
} catch (PDOException $e) {
    echo "Error checking technicians table: " . $e->getMessage() . "\n";
}

// Check if transport_providers table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'transport_providers'")->fetchAll();
    if (count($result) > 0) {
        echo "Transport providers table exists\n";
    } else {
        echo "Transport providers table does not exist\n";
        
        // Create transport_providers table
        $create_table = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            contact VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            regions TEXT,
            address TEXT,
            cost_per_km DECIMAL(10,2) DEFAULT 0,
            estimated_days INT DEFAULT 1,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created transport_providers table\n";
    }
} catch (PDOException $e) {
    echo "Error checking transport_providers table: " . $e->getMessage() . "\n";
}

// Check if notifications table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetchAll();
    if (count($result) > 0) {
        echo "Notifications table exists\n";
    } else {
        echo "Notifications table does not exist\n";
        
        // Create notifications table
        $create_table = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT 0,
            user_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created notifications table\n";
    }
} catch (PDOException $e) {
    echo "Error checking notifications table: " . $e->getMessage() . "\n";
}

// Check if service_requests table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'service_requests'")->fetchAll();
    if (count($result) > 0) {
        echo "Service requests table exists\n";
        
        // Check if assigned_at column exists
        $result = $pdo->query("SHOW COLUMNS FROM service_requests LIKE 'assigned_at'")->fetchAll();
        if (count($result) == 0) {
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN assigned_at DATETIME AFTER technician_id");
            echo "Added assigned_at column to service_requests table\n";
        }
    } else {
        echo "Service requests table does not exist\n";
    }
} catch (PDOException $e) {
    echo "Error checking service_requests table: " . $e->getMessage() . "\n";
}

// Check if orders table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    if (count($result) > 0) {
        echo "Orders table exists\n";
        
        // Check if transport_id column exists
        $result = $pdo->query("SHOW COLUMNS FROM orders LIKE 'transport_id'")->fetchAll();
        if (count($result) == 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN transport_id INT AFTER payment_method");
            echo "Added transport_id column to orders table\n";
        }
    } else {
        echo "Orders table does not exist\n";
    }
} catch (PDOException $e) {
    echo "Error checking orders table: " . $e->getMessage() . "\n";
}

// Check if order_tracking table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'order_tracking'")->fetchAll();
    if (count($result) > 0) {
        echo "Order tracking table exists\n";
    } else {
        echo "Order tracking table does not exist\n";
        
        // Create order_tracking table
        $create_table = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(100),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created order_tracking table\n";
    }
} catch (PDOException $e) {
    echo "Error checking order_tracking table: " . $e->getMessage() . "\n";
}

echo "Database check complete\n";
?>