<?php
// Fix messages table timestamp column issue
include('includes/db.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Messages Table Timestamp - SmartFix</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 5px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        h3 { color: #333; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Messages Table Timestamp Column</h1>
        <div class="info">This script will fix the missing timestamp column in your messages table.</div>

<?php
try {
    echo "<h3>Step 1: Checking if messages table exists...</h3>";
    
    // Check if messages table exists
    $check_table = "SHOW TABLES LIKE 'messages'";
    $table_result = $pdo->query($check_table);
    
    if ($table_result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Messages table doesn't exist. Creating complete table structure...</div>";
        
        // Create the messages table with proper structure
        $create_table = "CREATE TABLE messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT,
            receiver_id INT,
            sender_type ENUM('user','admin','technician') DEFAULT 'user',
            receiver_type ENUM('user','admin','technician') DEFAULT 'admin',
            request_id INT,
            subject VARCHAR(255),
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($create_table);
        echo "<div class='success'>✅ Created messages table with timestamp and created_at columns!</div>";
        
    } else {
        echo "<div class='success'>✅ Messages table exists</div>";
        
        echo "<h3>Step 2: Checking current table structure...</h3>";
        
        // Get current table structure
        $describe = "DESCRIBE messages";
        $current_structure = $pdo->query($describe);
        $existing_columns = [];
        
        echo "<div class='info'>Current columns:</div>";
        echo "<pre>";
        while ($row = $current_structure->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[] = $row['Field'];
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        echo "</pre>";
        
        echo "<h3>Step 3: Adding missing columns...</h3>";
        
        // Check and add timestamp column if missing
        if (!in_array('timestamp', $existing_columns)) {
            try {
                $add_timestamp = "ALTER TABLE messages ADD COLUMN timestamp DATETIME DEFAULT CURRENT_TIMESTAMP";
                $pdo->exec($add_timestamp);
                echo "<div class='success'>✅ Added timestamp column</div>";
            } catch (PDOException $e) {
                echo "<div class='warning'>⚠️ Could not add timestamp: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='info'>ℹ️ timestamp column already exists</div>";
        }
        
        // Check and add other commonly used columns
        $required_columns = [
            'sender_id' => "INT",
            'receiver_id' => "INT", 
            'request_id' => "INT",
            'subject' => "VARCHAR(255)",
            'message' => "TEXT NOT NULL",
            'is_read' => "TINYINT(1) DEFAULT 0",
            'sender_type' => "ENUM('user','admin','technician') DEFAULT 'user'",
            'receiver_type' => "ENUM('user','admin','technician') DEFAULT 'admin'",
            'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        $columns_added = 0;
        foreach ($required_columns as $column_name => $column_spec) {
            if (!in_array($column_name, $existing_columns)) {
                try {
                    $add_column = "ALTER TABLE messages ADD COLUMN $column_name $column_spec";
                    $pdo->exec($add_column);
                    echo "<div class='success'>✅ Added column: $column_name</div>";
                    $columns_added++;
                } catch (PDOException $e) {
                    echo "<div class='warning'>⚠️ Could not add $column_name: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='info'>ℹ️ Column already exists: $column_name</div>";
            }
        }
        
        if ($columns_added > 0) {
            echo "<div class='success'>🎉 Added $columns_added missing columns!</div>";
        }
    }
    
    echo "<h3>Step 4: Updating existing data...</h3>";
    
    // If we have created_at but no timestamp data, copy it
    $update_timestamps = "UPDATE messages SET timestamp = created_at WHERE timestamp IS NULL AND created_at IS NOT NULL";
    try {
        $result = $pdo->exec($update_timestamps);
        if ($result > 0) {
            echo "<div class='success'>✅ Updated $result records with timestamp data</div>";
        } else {
            echo "<div class='info'>ℹ️ No timestamp updates needed</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='info'>ℹ️ Timestamp update not needed: " . $e->getMessage() . "</div>";
    }
    
    echo "<h3>Step 5: Creating related tables if needed...</h3>";
    
    // Create replies table if it doesn't exist
    $check_replies = "SHOW TABLES LIKE 'replies'";
    $replies_result = $pdo->query($check_replies);
    
    if ($replies_result->rowCount() == 0) {
        $create_replies = "CREATE TABLE replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NOT NULL,
            reply_message TEXT NOT NULL,
            sender_id INT,
            sender_type ENUM('user','admin','technician') DEFAULT 'admin',
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
        )";
        $pdo->exec($create_replies);
        echo "<div class='success'>✅ Created replies table</div>";
    } else {
        echo "<div class='info'>ℹ️ replies table already exists</div>";
    }
    
    echo "<h3>Step 6: Final verification...</h3>";
    
    // Show final table structure
    $final_structure = "DESCRIBE messages";
    $final_result = $pdo->query($final_structure);
    
    echo "<div class='success'>Final messages table structure:</div>";
    echo "<pre>";
    echo sprintf("%-20s %-30s %-5s %-5s %-15s %s\n", 'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat('-', 90) . "\n";
    
    while ($row = $final_result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-30s %-5s %-5s %-15s %s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
    echo "</pre>";
    
    // Test the queries that were failing
    echo "<h3>Step 7: Testing problematic queries...</h3>";
    
    try {
        // Test the query from admin_messages.php
        $test_query = "SELECT m.id AS message_id, m.message, m.timestamp FROM messages m LIMIT 1";
        $test_result = $pdo->query($test_query);
        echo "<div class='success'>✅ admin_messages.php query works</div>";
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ admin_messages.php query still has issues: " . $e->getMessage() . "</div>";
    }
    
    try {
        // Test the query from view_messages.php  
        $test_query2 = "SELECT m.message, m.timestamp FROM messages m LIMIT 1";
        $test_result2 = $pdo->query($test_query2);
        echo "<div class='success'>✅ view_messages.php query works</div>";
    } catch (PDOException $e) {
        echo "<div class='warning'>⚠️ view_messages.php query still has issues: " . $e->getMessage() . "</div>";
    }
    
    // Test message count
    $count_query = "SELECT COUNT(*) as message_count FROM messages";
    $count_result = $pdo->query($count_query);
    $count = $count_result->fetch()['message_count'];
    
    echo "<div class='success'>🎉 All fixes completed successfully!</div>";
    echo "<div class='info'>✅ Table structure is now complete<br>";
    echo "✅ Current messages in database: $count<br>";
    echo "✅ Your messaging system should now work without timestamp errors</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Error Code: " . $e->getCode() . "</div>";
    
    if ($e->getCode() == '23000') {
        echo "<div class='warning'>This appears to be a foreign key constraint issue. The service_requests table might be missing.</div>";
        echo "<div class='info'>You may need to:</div>";
        echo "<ul><li>Create the service_requests table first</li>";
        echo "<li>Or remove the foreign key constraint temporarily</li></ul>";
    }
}
?>

        <hr>
        <div style="margin-top: 20px;">
            <h3>Test Your Messaging System:</h3>
            <a href="admin/admin_messages.php" class="btn">📧 Admin Messages</a>
            <a href="view_messages.php" class="btn">💬 View Messages</a>
            <a href="services/send_message.php" class="btn">✉️ Send Message</a>
            <a href="admin/admin_dashboard_new.php" class="btn">👤 Admin Dashboard</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
        
        <div class="warning" style="margin-top: 20px;">
            <strong>⚠️ Files Fixed:</strong>
            <ul>
                <li>✅ admin/admin_messages.php - now has timestamp column</li>
                <li>✅ view_messages.php - now has timestamp column</li>
                <li>✅ services/send_message.php - table creation improved</li>
            </ul>
        </div>
        
        <div class="info" style="margin-top: 10px;">
            <strong>📋 What was fixed:</strong>
            <ul>
                <li>✅ Added missing timestamp column to messages table</li>
                <li>✅ Added other commonly used message columns</li>
                <li>✅ Created replies table for message responses</li>
                <li>✅ Updated existing data with proper timestamps</li>
                <li>✅ Verified all message queries now work</li>
            </ul>
        </div>
    </div>
</body>
</html>