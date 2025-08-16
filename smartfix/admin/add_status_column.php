<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Check if status column exists
$check_query = "SHOW COLUMNS FROM users LIKE 'status'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add status column if it doesn't exist
    $alter_query = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
    if (mysqli_query($conn, $alter_query)) {
        $message = "Status column added successfully!";
        $success = true;
    } else {
        $message = "Error adding status column: " . mysqli_error($conn);
        $success = false;
    }
} else {
    $message = "Status column already exists.";
    $success = true;
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
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        h1 {
            margin-bottom: 1.5rem;
            color: #343a40;
        }
        
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
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
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Update</h1>
        
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        
        <a href="manage_users.php" class="btn">Go to User Management</a>
    </div>
</body>
</html>