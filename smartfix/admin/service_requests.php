<?php
session_start();
include '../includes/db.php'; // your DB connection

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

// Get admin information
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_id = $_SESSION['admin_id'] ?? 1;

// Handle technician assignment
if (isset($_POST['assign'])) {
    $request_id = $_POST['request_id'];
    $technician_id = $_POST['technician_id'];

    // Sanitize inputs to prevent SQL injection
    $request_id = mysqli_real_escape_string($conn, $request_id);
    $technician_id = mysqli_real_escape_string($conn, $technician_id);

    // Update technician assignment
    $update = "UPDATE service_requests SET technician_id='$technician_id', status='in_progress' WHERE id='$request_id'";
    $update_result = mysqli_query($conn, $update);

    if ($update_result) {
        // Insert notification for technician
        $message = "You have been assigned a new service request (ID: $request_id)";
        $notify = "INSERT INTO notifications (user_id, message, type, created_at) 
                   VALUES ('$technician_id', '$message', 'service', NOW())";
        mysqli_query($conn, $notify);
    } else {
        echo "<div style='color:red;'>Error updating technician: " . mysqli_error($conn) . "</div>";
    }
}


// Handle status update
if (isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    
    // Sanitize inputs to prevent SQL injection
    $request_id = mysqli_real_escape_string($conn, $request_id);
    $new_status = mysqli_real_escape_string($conn, $new_status);
    
    // Validate status value
    $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $update = "UPDATE service_requests SET status='$new_status' WHERE id='$request_id'";
        $result = mysqli_query($conn, $update);
        
        if (!$result) {
            echo "<div style='color:red;'>Error updating status: " . mysqli_error($conn) . "</div>";
        }
    } else {
        echo "<div style='color:red;'>Invalid status value</div>";
    }
}

// Fetch service requests
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT sr.*, 
          IFNULL(u.name, sr.name) AS user_name, 
          t.name AS tech_name 
          FROM service_requests sr 
          LEFT JOIN users u ON sr.user_id = u.id 
          LEFT JOIN technicians t ON sr.technician_id = t.id 
          WHERE (sr.status LIKE '%$filter_status%' AND 
                (sr.name LIKE '%$search%' OR sr.service_type LIKE '%$search%')) 
          ORDER BY COALESCE(sr.request_date, sr.created_at, sr.id) DESC";
$result = mysqli_query($conn, $query);

// Get technicians for dropdown
$tech_query = mysqli_query($conn, "SELECT * FROM technicians");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Service Requests</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 22px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 10px rgba(0,0,0,0.1);
        }

        .filters {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .filters input, .filters select {
            padding: 8px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        form.inline-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        select, button {
            padding: 5px;
        }

        .status {
            font-weight: bold;
            color: #555;
        }

        .status.Assigned { color: orange; }
        .status.In\ Progress { color: dodgerblue; }
        .status.Completed { color: green; }
        .status.Pending { color: crimson; }
    </style>
</head>
<body>

<div class="header">🛠️ Manage Service Requests</div>

<div class="container">
    <div class="filters">
        <form method="get">
            <input type="text" name="search" placeholder="Search customer or service type..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">All Statuses</option>
                <option <?php if ($filter_status == 'pending') echo 'selected'; ?> value="pending">Pending</option>
                <option <?php if ($filter_status == 'in_progress') echo 'selected'; ?> value="in_progress">In Progress</option>
                <option <?php if ($filter_status == 'completed') echo 'selected'; ?> value="completed">Completed</option>
                <option <?php if ($filter_status == 'cancelled') echo 'selected'; ?> value="cancelled">Cancelled</option>
            </select>
            <button type="submit">Filter</button>
        </form>
    </div>

    <table>
        <tr>
            <th>#</th>
            <th>Service Type</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Technician</th>
            <th>Assign Technician</th>
            <th>Update Status</th>
            <th>Date</th>
        </tr>

        <?php 
        $i = 1; 
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)): 
        ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo $row['service_type'] ?? 'N/A'; ?></td>
                <td><?php echo $row['user_name'] ?? 'N/A'; ?></td>
                <td class="status <?php echo str_replace(' ', '\ ', $row['status']); ?>"><?php echo $row['status']; ?></td>
                <td><?php echo $row['tech_name'] ? $row['tech_name'] : '—'; ?></td>
                
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                        <select name="technician_id">
                            <?php 
                            // Reset the technician query result pointer
                            mysqli_data_seek($tech_query, 0);
                            while ($tech = mysqli_fetch_assoc($tech_query)): 
                            ?>
                                <option value="<?php echo $tech['id']; ?>"><?php echo $tech['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button name="assign">Assign</button>
                    </form>
                </td>

                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                        <select name="status">
                            <option <?php if ($row['status'] == 'pending') echo 'selected'; ?>>pending</option>
                            <option <?php if ($row['status'] == 'in_progress') echo 'selected'; ?>>in_progress</option>
                            <option <?php if ($row['status'] == 'completed') echo 'selected'; ?>>completed</option>
                            <option <?php if ($row['status'] == 'cancelled') echo 'selected'; ?>>cancelled</option>
                        </select>
                        <button name="update_status">Update</button>
                    </form>
                </td>

                <td><?php echo date('d M Y', strtotime($row['request_date'])); ?></td>
            </tr>
        <?php 
            endwhile; 
        } else {
            echo '<tr><td colspan="8">No service requests found</td></tr>';
        }
        ?>
    </table>
</div>

</body>
</html>
