<?php
session_start();
require_once '../includes/db.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Messages - SmartFix</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f4; }
        .msg-box {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
        }
        .msg-box strong { color: #007BFF; }
        .msg-box small { color: #777; font-size: 12px; }
    </style>
</head>
<body>
<h2>📨 User Messages</h2>

<?php
// Check which timestamp column exists and use it
try {
    // Try to find out which timestamp column exists
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
        SELECT m.id AS message_id, m.message, m.$timestamp_column as timestamp, u.name AS sender_name, sr.id AS request_id
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN service_requests sr ON m.request_id = sr.id
        ORDER BY m.$timestamp_column DESC
    ");

    while ($msg = $stmt->fetch()) {
        echo "<div class='msg-box'>
                <strong>From:</strong> " . ($msg['sender_name'] ?? 'Unknown User') . "<br>
                <strong>Request ID:</strong> " . ($msg['request_id'] ?? 'N/A') . "<br>
                <p>" . htmlspecialchars($msg['message']) . "</p>
                <small>Sent: {$msg['timestamp']}</small>";

        // ✅ Show previous replies for this message
        try {
            $replyStmt = $pdo->prepare("SELECT reply_text, replied_at FROM replies WHERE message_id = ?");
            $replyStmt->execute([$msg['message_id']]);
            $replies = $replyStmt->fetchAll();

            if ($replies) {
                echo "<div style='margin-top:10px; padding-left:10px;'>";
                foreach ($replies as $reply) {
                    echo "<div style='background:#e7f3ff; border-left: 4px solid #007BFF; margin-bottom:5px; padding:8px; border-radius:5px;'>
                            <strong>Admin Reply:</strong> " . htmlspecialchars($reply['reply_text']) . "<br>
                            <small>Replied: {$reply['replied_at']}</small>
                          </div>";
                }
                echo "</div>";
            }
        } catch (PDOException $e) {
            // Replies table might not exist, that's okay
        }

        // ✅ Reply form
        echo "<form method='POST' action='reply_to_message.php' style='margin-top:10px;'>
                <input type='hidden' name='message_id' value='{$msg['message_id']}'>
                <textarea name='reply_text' rows='2' cols='50' placeholder='Write your reply...' required></textarea><br>
                <button type='submit'>Reply</button>
              </form>";

        echo "</div>";
    }
    
} catch (PDOException $e) {
    // If there's still an error, create a basic messages table
    echo "<div class='error'>Messages table needs to be fixed. Error: " . $e->getMessage() . "</div>";
    echo "<div class='info'><a href='../fix_messages_table_timestamp.php'>Click here to fix the messages table</a></div>";
}
?>


</body>
</html>
