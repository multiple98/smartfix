<?php
include('../includes/db.php');

// Mark all as read
mysqli_query($conn, "UPDATE notifications SET is_read = 1");

$result = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
</head>
<body>
    <h2>🔔 Notifications</h2>
    <ul>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <li>
                <strong><?= ucfirst($row['type']) ?>:</strong> <?= $row['message'] ?> 
                <small>(<?= $row['created_at'] ?>)</small>
            </li>
        <?php endwhile; ?>
    </ul>
    <a href="../admin_dashboard.php">⬅️ Back to Dashboard</a>
</body>
</html>
