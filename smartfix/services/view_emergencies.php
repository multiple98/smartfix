<?php
include 'includes/db.php';
$requests = $pdo->query("SELECT e.*, u.username FROM emergency_requests e JOIN users u ON e.user_id = u.id ORDER BY requested_at DESC")->fetchAll();
$pdo->query("UPDATE emergency_requests SET is_read = 1 WHERE is_read = 0");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Requests</title>
    <style>
        body { font-family: Arial; background: #fafafa; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #f44336; color: white; }
    </style>
</head>
<body>

<h2>Emergency Requests</h2>
<table>
    <tr>
        <th>User</th>
        <th>Message</th>
        <th>Location</th>
        <th>Time</th>
    </tr>
    <?php foreach ($requests as $req): ?>
    <tr>
        <td><?= htmlspecialchars($req['username']) ?></td>
        <td><?= htmlspecialchars($req['message']) ?></td>
        <td>
            <a href="https://www.google.com/maps?q=<?= $req['latitude'] ?>,<?= $req['longitude'] ?>" target="_blank">
                View on Map
            </a>
        </td>
        <td><?= $req['requested_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>