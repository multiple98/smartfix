<?php
// Include database connection
include('includes/db.php');

echo "<h1>Database Test</h1>";

try {
    // Check if technicians table exists
    $result = $pdo->query("SHOW TABLES LIKE 'technicians'");
    if ($result->rowCount() > 0) {
        echo "<p>Technicians table exists.</p>";
        
        // Check technicians table structure
        $result = $pdo->query("DESCRIBE technicians");
        echo "<h2>Technicians Table Structure:</h2>";
        echo "<pre>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
        
        // Check if bio column exists
        $hasBio = false;
        $result = $pdo->query("DESCRIBE technicians");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Field'] === 'bio') {
                $hasBio = true;
                break;
            }
        }
        
        if ($hasBio) {
            echo "<p style='color:green'>Bio column exists in technicians table.</p>";
        } else {
            echo "<p style='color:red'>Bio column is missing from technicians table!</p>";
        }
    } else {
        echo "<p>Technicians table does not exist.</p>";
    }
    
    // Check if bookings table exists
    $result = $pdo->query("SHOW TABLES LIKE 'bookings'");
    if ($result->rowCount() > 0) {
        echo "<p>Bookings table exists.</p>";
        
        // Check bookings table structure
        $result = $pdo->query("DESCRIBE bookings");
        echo "<h2>Bookings Table Structure:</h2>";
        echo "<pre>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<p>Bookings table does not exist.</p>";
    }
    
    // Check if users table exists
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "<p>Users table exists.</p>";
        
        // Check users table structure
        $result = $pdo->query("DESCRIBE users");
        echo "<h2>Users Table Structure:</h2>";
        echo "<pre>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<p>Users table does not exist.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>