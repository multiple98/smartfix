<?php
session_start();
include '../includes/db.php';

// OPTIONAL: Add authentication check for admin/tech here

if (isset($_POST['update_status'])) {
    $req_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $update = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
    $update->execute([$new_status, $req_id]);
}

$requests = $pdo->query("SELECT sr.*, u.username FROM service_requests sr JOIN users u ON sr.user_id = u.id ORDER BY requested_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Requests</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th, td { padding: 10px; text-align: center; border: 1px solid #ccc; }
        th { background: #007bff; color: white; }
        select, button { padding: 5px; }
    </style>
</head>
<body>

<h2>All Service Requests</h2>

<table>
    <tr>
        <th>#</th>
        <th>Username</th>
        <th>Service</th>
        <th>Description</th>
        <th>Status</th>
        <th>Change Status</th>
        <th>Date</th>
    </tr>
    <?php foreach ($requests as $i => $req): ?>
    <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($req['username']) ?></td>
        <td><?= htmlspecialchars($req['service_type']) ?></td>
        <td><?= htmlspecialchars($req['description']) ?></td>
        <td><?= $req['status'] ?></td>
        <td>
            <form method="POST">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <select name="status">
                    <option <?= $req['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option <?= $req['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option <?= $req['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
                <button type="submit" name="update_status">Update</button>
            </form>
        </td>
        <td><?= $req['requested_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>