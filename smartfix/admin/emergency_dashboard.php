<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Auto-create emergencies table if it doesn't exist
try {
    $check_table = "SHOW TABLES LIKE 'emergencies'";
    $result_check = mysqli_query($conn, $check_table);
    
    if (mysqli_num_rows($result_check) == 0) {
        // Table doesn't exist, create it
        $create_sql = "CREATE TABLE emergencies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            message TEXT NOT NULL,
            location TEXT NOT NULL,
            status ENUM('Pending', 'Responding', 'Resolved') DEFAULT 'Pending',
            priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'High',
            phone VARCHAR(20),
            email VARCHAR(100),
            name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (mysqli_query($conn, $create_sql)) {
            // Insert sample data
            $sample_sql = "INSERT INTO emergencies (message, location, status, phone, email, name) VALUES
                ('Power outage in building', '123 Main Street, Lusaka', 'Pending', '+260 777 123 456', 'john@email.com', 'John Doe'),
                ('Water pipe burst', '456 Industrial Avenue, Lusaka', 'Responding', '+260 776 987 654', 'jane@company.com', 'Jane Smith'),
                ('Gas leak resolved', '789 Restaurant Row, Lusaka', 'Resolved', '+260 775 555 123', 'restaurant@example.com', 'Manager')";
            mysqli_query($conn, $sample_sql);
            
            $table_created = true;
        }
    }
} catch (Exception $e) {
    // Continue anyway
}

// Update emergency status
if (isset($_POST['update_status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    
    // Validate status
    $allowed_statuses = ['Pending', 'Responding', 'Resolved'];
    if (in_array($status, $allowed_statuses) && $id > 0) {
        $update = "UPDATE emergencies SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update);
        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Fetch all emergency records with LEFT JOIN to handle cases where user_id is NULL
$query = "SELECT e.*, COALESCE(u.name, e.name, 'Anonymous') AS user_name FROM emergencies e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            margin: 0;
        }

        .header {
            background: #d32f2f;
            color: white;
            padding: 20px;
            font-size: 24px;
            text-align: center;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
        }

        .status {
            font-weight: bold;
        }

        .status.Pending { color: crimson; }
        .status.Responding { color: orange; }
        .status.Resolved { color: green; }

        .form-inline {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        select, button {
            padding: 4px 8px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="header">🚨 Emergency Dashboard</div>

<div class="container">
    
    <?php if (isset($table_created) && $table_created): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">
            <strong>✅ Emergency System Initialized!</strong><br>
            The emergencies table was created automatically and sample data was added. The system is now ready to use.
        </div>
    <?php endif; ?>
    <table>
        <tr>
            <th>#</th>
            <th>User</th>
            <th>Contact</th>
            <th>Message</th>
            <th>Location</th>
            <th>Status</th>
            <th>Update</th>
            <th>Reported On</th>
        </tr>

        <?php 
        $i = 1; 
        $has_records = false;
        while ($row = mysqli_fetch_assoc($result)): 
            $has_records = true;
        ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                <td>
                    <?php if (!empty($row['phone'])): ?>
                        <div><strong>📞</strong> <?php echo htmlspecialchars($row['phone']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($row['email'])): ?>
                        <div><strong>✉️</strong> <?php echo htmlspecialchars($row['email']); ?></div>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['message']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td class="status <?php echo $row['status']; ?>"><?php echo $row['status']; ?></td>
                <td>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <select name="status">
                            <option <?php if ($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option <?php if ($row['status'] == 'Responding') echo 'selected'; ?>>Responding</option>
                            <option <?php if ($row['status'] == 'Resolved') echo 'selected'; ?>>Resolved</option>
                        </select>
                        <button name="update_status">Update</button>
                    </form>
                </td>
                <td><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
            </tr>
        <?php endwhile; ?>
        
        <?php if (!$has_records): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 20px;">🎉</div>
                    <h3>No Emergency Records Found</h3>
                    <p>Great news! There are currently no emergency requests.</p>
                    <p>Emergency requests will appear here when they are submitted through the <a href="../services/emergency.php">Emergency Service</a> form.</p>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
