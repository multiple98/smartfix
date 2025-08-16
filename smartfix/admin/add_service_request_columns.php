<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$messages = [];
$success = true;

// Check if technician_id column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'technician_id'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add technician_id column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests ADD COLUMN technician_id INT NULL";
    if (mysqli_query($conn, $alter_query)) {
        $messages[] = "Technician ID column added successfully!";
    } else {
        $messages[] = "Error adding technician_id column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $messages[] = "Technician ID column already exists.";
}

// Check if request_date column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'request_date'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Check if created_at column exists
    $check_created_query = "SHOW COLUMNS FROM service_requests LIKE 'created_at'";
    $created_result = mysqli_query($conn, $check_created_query);
    
    if (mysqli_num_rows($created_result) == 0) {
        // Add request_date column if neither exists
        $alter_query = "ALTER TABLE service_requests ADD COLUMN request_date DATETIME DEFAULT CURRENT_TIMESTAMP";
        if (mysqli_query($conn, $alter_query)) {
            $messages[] = "Request date column added successfully!";
        } else {
            $messages[] = "Error adding request_date column: " . mysqli_error($conn);
            $success = false;
        }
    } else {
        // If created_at exists, add request_date and copy values
        $alter_query = "ALTER TABLE service_requests ADD COLUMN request_date DATETIME";
        if (mysqli_query($conn, $alter_query)) {
            $update_query = "UPDATE service_requests SET request_date = created_at";
            if (mysqli_query($conn, $update_query)) {
                $messages[] = "Request date column added and populated from created_at!";
            } else {
                $messages[] = "Error populating request_date column: " . mysqli_error($conn);
                $success = false;
            }
        } else {
            $messages[] = "Error adding request_date column: " . mysqli_error($conn);
            $success = false;
        }
    }
} else {
    $messages[] = "Request date column already exists.";
}

// Check if status column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'status'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add status column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests ADD COLUMN status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'";
    if (mysqli_query($conn, $alter_query)) {
        $messages[] = "Status column added successfully!";
    } else {
        $messages[] = "Error adding status column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $messages[] = "Status column already exists.";
}

// Check if service_type column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'service_type'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add service_type column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests ADD COLUMN service_type VARCHAR(100) DEFAULT 'General Repair'";
    if (mysqli_query($conn, $alter_query)) {
        $messages[] = "Service type column added successfully!";
    } else {
        $messages[] = "Error adding service_type column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $messages[] = "Service type column already exists.";
}

// Check if name column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'name'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add name column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests ADD COLUMN name VARCHAR(100) NULL";
    if (mysqli_query($conn, $alter_query)) {
        $messages[] = "Name column added successfully!";
    } else {
        $messages[] = "Error adding name column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $messages[] = "Name column already exists.";
}

// Check if email column exists
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'email'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add email column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests ADD COLUMN email VARCHAR(100) NULL";
    if (mysqli_query($conn, $alter_query)) {
        $messages[] = "Email column added successfully!";
    } else {
        $messages[] = "Error adding email column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $messages[] = "Email column already exists.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - SmartFix Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            color: #343a40;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            text-align: center;
            margin: 2rem;
        }
        
        h1 {
            margin-bottom: 1.5rem;
            color: #343a40;
        }
        
        .message {
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 0.75rem;
            text-align: left;
        }
        
        .success {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
            background-color: #007BFF;
            color: white;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Service Requests Table Update</h1>
        
        <?php foreach ($messages as $message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endforeach; ?>
        
        <a href="admin_dashboard_new.php" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>