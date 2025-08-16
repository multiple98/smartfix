<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Messages - SmartFix</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f4f4f4; 
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message-box {
            padding: 15px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .message-box strong { 
            color: #007BFF; 
        }
        .message-box small { 
            color: #777; 
            font-size: 12px; 
        }
        .error { 
            padding: 15px; 
            border: 1px solid #dc3545; 
            border-radius: 8px; 
            background: #f8d7da; 
            color: #721c24; 
            margin-bottom: 15px;
        }
        .back-btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-btn:hover { background: #0056b3; }
        h1 { color: #333; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-btn">← Back to Home</a>
        <h1>📧 Messages</h1>

<?php
// Include database connection
include('includes/db.php');

// Check which timestamp column exists and use it
try {
    // Check available columns in messages table
    $check_columns = "SHOW COLUMNS FROM messages";
    $columns_result = $pdo->query($check_columns);
    $columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine which timestamp column to use
    $timestamp_column = 'created_at'; // default fallback
    if (in_array('timestamp', $columns)) {
        $timestamp_column = 'timestamp';
    } elseif (in_array('created_at', $columns)) {
        $timestamp_column = 'created_at';
    }
    
    $stmt = $pdo->query("
        SELECT m.message, m.$timestamp_column as timestamp, u.name AS sender_name, sr.id AS request_id
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN service_requests sr ON m.request_id = sr.id
        ORDER BY m.$timestamp_column DESC
    ");

    if ($stmt->rowCount() > 0) {
        while ($msg = $stmt->fetch()) {
            echo "<div class='message-box'>
                    <strong>From:</strong> " . ($msg['sender_name'] ?? 'Unknown User') . "<br>
                    <strong>Request ID:</strong> " . ($msg['request_id'] ?? 'N/A') . "<br>
                    <strong>Message:</strong> " . htmlspecialchars($msg['message']) . "<br>
                    <small><em>Sent on: {$msg['timestamp']}</em></small>
                  </div>";
        }
    } else {
        echo "<div class='message-box'>
                <p style='color: #666; text-align: center;'>
                    <em>No messages found.</em>
                </p>
              </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>
            <strong>Messages table needs to be fixed.</strong><br>
            Error: " . $e->getMessage() . "<br>
            <a href='fix_messages_table_timestamp.php' style='color: #721c24; text-decoration: underline;'>
                Click here to fix the messages table
            </a>
          </div>";
}
?>

    </div>
</body>
</html>
