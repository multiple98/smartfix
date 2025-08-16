<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

include('../includes/db.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #004080;
            margin-bottom: 20px;
        }
        .notification {
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #004080;
            background-color: #f0f8ff;
            border-radius: 4px;
        }
        .notification.unread {
            background-color: #e6f7ff;
            border-left-color: #0066cc;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .notification-time {
            color: #666;
            font-size: 0.9em;
        }
        .notification-title {
            font-weight: bold;
            color: #004080;
        }
        .notification-content {
            color: #333;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #004080;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .no-notifications {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Notifications</h1>
        
        <?php
        // Query to get all notifications
        $query = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10";
        
        // Check if the notifications table exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
        
        if (mysqli_num_rows($check_table) > 0) {
            $result = mysqli_query($conn, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($notification = mysqli_fetch_assoc($result)) {
                    $unread_class = $notification['is_read'] ? '' : 'unread';
                    $notification_type = ucfirst($notification['type']); // Capitalize first letter
                    
                    echo "<div class='notification {$unread_class}'>";
                    echo "<div class='notification-header'>";
                    echo "<div class='notification-title'>{$notification_type} Notification</div>";
                    echo "<div class='notification-time'>{$notification['created_at']}</div>";
                    echo "</div>";
                    echo "<div class='notification-content'>{$notification['message']}</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='no-notifications'>No notifications at this time.</div>";
            }
        } else {
            echo "<div class='no-notifications'>Notification system is being set up.</div>";
        }
        ?>
        
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>