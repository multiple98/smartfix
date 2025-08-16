<?php
// Fix messages table error
include 'includes/db.php';

echo "<h2>Fixing Messages Table Error</h2>";

try {
    // Check if messages table exists
    $check_table = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($check_table->rowCount() == 0) {
        echo "<p>Messages table doesn't exist. Creating it...</p>";
        
        $create_sql = "CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT,
            user_id INT,
            receiver_id INT,
            sender_type ENUM('user','admin','technician') DEFAULT 'user',
            receiver_type ENUM('user','admin','technician') DEFAULT 'admin',
            request_id INT,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($create_sql);
        echo "<p style='color: green;'>✓ Messages table created successfully!</p>";
    } else {
        echo "<p>Messages table exists. Checking columns...</p>";
        
        // Get current columns
        $columns_result = $pdo->query("SHOW COLUMNS FROM messages");
        $existing_columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Current columns: " . implode(', ', $existing_columns) . "</p>";
        
        // Add missing columns
        $required_columns = [
            'sender_id' => 'INT',
            'user_id' => 'INT',
            'receiver_id' => 'INT',
            'sender_type' => 'ENUM("user","admin","technician") DEFAULT "user"',
            'receiver_type' => 'ENUM("user","admin","technician") DEFAULT "admin"',
            'request_id' => 'INT',
            'subject' => 'VARCHAR(255)',
            'message' => 'TEXT NOT NULL',
            'is_read' => 'TINYINT(1) DEFAULT 0',
            'timestamp' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                try {
                    $pdo->exec("ALTER TABLE messages ADD COLUMN $column $definition");
                    echo "<p style='color: green;'>✓ Added column: $column</p>";
                } catch (PDOException $e) {
                    echo "<p style='color: orange;'>⚠ Could not add column $column: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Test the query that was failing
    echo "<h3>Testing Messages Query</h3>";
    
    // Check which user column exists
    $columns_result = $pdo->query("SHOW COLUMNS FROM messages");
    $columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
    
    $user_column = 'sender_id'; // default
    if (in_array('user_id', $columns)) {
        $user_column = 'user_id';
    } elseif (in_array('sender_id', $columns)) {
        $user_column = 'sender_id';
    }
    
    $time_column = 'created_at'; // default
    if (in_array('timestamp', $columns)) {
        $time_column = 'timestamp';
    }
    
    $test_query = "SELECT m.*, u.name AS user_name FROM messages m 
                   LEFT JOIN users u ON m.$user_column = u.id 
                   ORDER BY m.$time_column DESC LIMIT 5";
    
    echo "<p>Test query: " . htmlspecialchars($test_query) . "</p>";
    
    $result = $pdo->query($test_query);
    $messages = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ Query executed successfully! Found " . count($messages) . " messages.</p>";
    
    if (count($messages) > 0) {
        echo "<h4>Sample Messages:</h4>";
        foreach ($messages as $msg) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>ID:</strong> " . ($msg['id'] ?? 'N/A') . "<br>";
            echo "<strong>User:</strong> " . ($msg['user_name'] ?? 'Unknown') . "<br>";
            echo "<strong>Message:</strong> " . htmlspecialchars(substr($msg['message'] ?? 'No message', 0, 100)) . "<br>";
            echo "<strong>Time:</strong> " . ($msg[$time_column] ?? 'No timestamp') . "<br>";
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>This might indicate a database connection issue or missing database.</p>";
}

echo "<hr>";
echo "<p><a href='admin/messages.php'>← Back to Messages</a></p>";
echo "<p><a href='admin/admin_dashboard_new.php'>← Back to Admin Dashboard</a></p>";
?>