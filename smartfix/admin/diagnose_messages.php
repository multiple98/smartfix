<?php
session_start();
include '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages Table Diagnostics - SmartFix Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Messages Table Diagnostics</h1>
        
        <?php
        try {
            echo "<h2>1. Checking if messages table exists...</h2>";
            $table_check = $pdo->query("SHOW TABLES LIKE 'messages'");
            if ($table_check->rowCount() > 0) {
                echo "<div class='success'>✓ Messages table exists</div>";
                
                echo "<h2>2. Current table structure:</h2>";
                $columns_result = $pdo->query("DESCRIBE messages");
                $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h2>3. Checking for required columns:</h2>";
                $column_names = array_column($columns, 'Field');
                $required_columns = ['id', 'message', 'sender_id', 'is_read', 'created_at'];
                $missing_columns = [];
                
                foreach ($required_columns as $req_col) {
                    if (in_array($req_col, $column_names)) {
                        echo "<div class='success'>✓ Column '$req_col' exists</div>";
                    } else {
                        echo "<div class='error'>✗ Column '$req_col' is missing</div>";
                        $missing_columns[] = $req_col;
                    }
                }
                
                if (!empty($missing_columns)) {
                    echo "<h2>4. Adding missing columns:</h2>";
                    $column_definitions = [
                        'sender_id' => 'INT',
                        'user_id' => 'INT',
                        'is_read' => 'TINYINT(1) DEFAULT 0',
                        'subject' => 'VARCHAR(255)',
                        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
                    ];
                    
                    foreach ($missing_columns as $col) {
                        if (isset($column_definitions[$col])) {
                            try {
                                $pdo->exec("ALTER TABLE messages ADD COLUMN $col " . $column_definitions[$col]);
                                echo "<div class='success'>✓ Added column '$col'</div>";
                            } catch (PDOException $e) {
                                echo "<div class='error'>✗ Failed to add column '$col': " . $e->getMessage() . "</div>";
                            }
                        }
                    }
                }
                
                echo "<h2>5. Sample data:</h2>";
                $sample_query = "SELECT * FROM messages LIMIT 5";
                $sample_result = $pdo->query($sample_query);
                $sample_data = $sample_result->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($sample_data)) {
                    echo "<table>";
                    echo "<tr>";
                    foreach (array_keys($sample_data[0]) as $header) {
                        echo "<th>" . htmlspecialchars($header) . "</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($sample_data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars(substr($value ?? '', 0, 50)) . (strlen($value ?? '') > 50 ? '...' : '') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<div class='info'>No messages found in the table</div>";
                }
                
                echo "<h2>6. Test query:</h2>";
                $test_query = "SELECT m.id, m.message, 
                              COALESCE(m.created_at, m.timestamp, 'No date') as date_field,
                              COALESCE(m.is_read, 0) as read_status,
                              u.name as user_name
                              FROM messages m 
                              LEFT JOIN users u ON COALESCE(m.sender_id, m.user_id) = u.id 
                              ORDER BY m.id DESC LIMIT 3";
                
                echo "<div class='info'>Query: " . htmlspecialchars($test_query) . "</div>";
                
                $test_result = $pdo->query($test_query);
                $test_data = $test_result->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='success'>✓ Test query executed successfully! Found " . count($test_data) . " records.</div>";
                
            } else {
                echo "<div class='error'>✗ Messages table does not exist</div>";
                echo "<h2>Creating messages table...</h2>";
                
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
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                $pdo->exec($create_sql);
                echo "<div class='success'>✓ Messages table created successfully!</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <hr>
        <h2>Actions:</h2>
        <a href="messages.php" class="btn btn-success">Go to Messages</a>
        <a href="admin_dashboard_new.php" class="btn">Back to Dashboard</a>
        <a href="diagnose_messages.php" class="btn">Refresh Diagnostics</a>
    </div>
</body>
</html>