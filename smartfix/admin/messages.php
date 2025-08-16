<?php
session_start();
include '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize variables
$messages = [];
$error_message = '';

// Mark as read functionality
if (isset($_POST['mark_read'])) {
    $msg_id = intval($_POST['msg_id']);
    try {
        // First check if is_read column exists
        $check_columns = "SHOW COLUMNS FROM messages LIKE 'is_read'";
        $column_check = $pdo->query($check_columns);
        
        if ($column_check->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $stmt->execute([$msg_id]);
            $success_message = "Message marked as read.";
        } else {
            // Add the column if it doesn't exist
            $pdo->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $stmt->execute([$msg_id]);
            $success_message = "Message marked as read (column was added).";
        }
    } catch (PDOException $e) {
        $error_message = "Error marking message as read: " . $e->getMessage();
    }
}

// Fetch all messages with comprehensive error handling
try {
    // First, check if messages table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($table_check->rowCount() == 0) {
        // Create messages table if it doesn't exist
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
        $error_message = "Messages table was missing and has been created. Please refresh the page.";
    } else {
        // Check which columns exist in messages table
        $check_columns = "SHOW COLUMNS FROM messages";
        $columns_result = $pdo->query($check_columns);
        $columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine which user ID column to use
        $user_column = 'sender_id'; // default
        if (in_array('user_id', $columns)) {
            $user_column = 'user_id';
        } elseif (in_array('sender_id', $columns)) {
            $user_column = 'sender_id';
        }
        
        // Add missing columns if needed first
        $required_columns = [
            'sender_id' => 'INT',
            'user_id' => 'INT',
            'is_read' => 'TINYINT(1) DEFAULT 0',
            'subject' => 'VARCHAR(255)',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE messages ADD COLUMN $column $definition");
                    $columns[] = $column; // Add to our columns array
                } catch (PDOException $e) {
                    // Column might already exist or there might be a constraint issue
                }
            }
        }
        
        // Determine timestamp column after adding missing columns
        $time_column = 'id'; // fallback to id if no timestamp column exists
        if (in_array('created_at', $columns)) {
            $time_column = 'created_at';
        } elseif (in_array('timestamp', $columns)) {
            $time_column = 'timestamp';
        }
        
        // Build the SELECT query carefully
        $select_fields = ['m.id', 'm.message'];
        
        // Add optional fields if they exist
        if (in_array('subject', $columns)) {
            $select_fields[] = 'm.subject';
        }
        if (in_array('is_read', $columns)) {
            $select_fields[] = 'm.is_read';
        }
        if (in_array('created_at', $columns)) {
            $select_fields[] = 'm.created_at';
        }
        if (in_array('timestamp', $columns)) {
            $select_fields[] = 'm.timestamp';
        }
        if (in_array($user_column, $columns)) {
            $select_fields[] = "m.$user_column";
        }
        
        $select_clause = implode(', ', $select_fields);
        
        // Build and execute the query
        $query = "SELECT $select_clause, u.name AS user_name FROM messages m 
                  LEFT JOIN users u ON m.$user_column = u.id 
                  ORDER BY m.$time_column DESC";
        $result = $pdo->query($query);
        $messages = $result->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Try to provide helpful information
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $error_message .= " - The messages table or required columns may be missing.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Messages</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #eef1f5;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .msg-box {
            border-bottom: 1px solid #ccc;
            padding: 15px 0;
        }

        .msg-box:last-child {
            border-bottom: none;
        }

        .msg-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .msg-header h4 {
            margin: 0;
            font-size: 18px;
        }

        .msg-header span {
            font-size: 13px;
            color: #666;
        }

        .msg-body {
            margin: 10px 0;
            font-size: 14px;
            color: #333;
        }

        .unread {
            background: #f9f9f9;
        }

        .mark-btn {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border: none;
            font-size: 13px;
            border-radius: 5px;
            cursor: pointer;
        }

        .mark-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="header">📨 User Messages</div>

<div class="container">
    <?php if (!empty($error_message)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            <br><br>
            <a href="../fix_messages_error.php" style="color: #721c24; text-decoration: underline;">Click here to fix database issues</a>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $row): ?>
            <div class="msg-box <?php echo (isset($row['is_read']) && !$row['is_read']) || !isset($row['is_read']) ? 'unread' : ''; ?>">
                <div class="msg-header">
                    <h4><?php echo htmlspecialchars($row['subject'] ?? 'No Subject'); ?> 
                        <span>from <?php echo htmlspecialchars($row['user_name'] ?? 'Unknown User'); ?></span>
                    </h4>
                    <span><?php 
                        $time_field = '';
                        if (isset($row['created_at']) && !empty($row['created_at'])) {
                            $time_field = $row['created_at'];
                        } elseif (isset($row['timestamp']) && !empty($row['timestamp'])) {
                            $time_field = $row['timestamp'];
                        }
                        echo $time_field ? date("d M Y, H:i", strtotime($time_field)) : 'No date'; 
                    ?></span>
                </div>
                <div class="msg-body">
                    <?php echo nl2br(htmlspecialchars($row['message'] ?? 'No message content')); ?>
                </div>
                <?php if (isset($row['is_read']) && !$row['is_read']): ?>
                    <form method="post">
                        <input type="hidden" name="msg_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="mark_read" class="mark-btn">Mark as Read</button>
                    </form>
                <?php elseif (!isset($row['is_read'])): ?>
                    <form method="post">
                        <input type="hidden" name="msg_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="mark_read" class="mark-btn">Mark as Read</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php elseif (empty($error_message)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <p>📭 No messages available.</p>
            <p><small>Messages from users will appear here when received.</small></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
