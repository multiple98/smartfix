<?php
include 'includes/db.php';

// Check if status column exists
$check_query = "SHOW COLUMNS FROM users LIKE 'status'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add status column if it doesn't exist
    $alter_query = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
    if (mysqli_query($conn, $alter_query)) {
        echo "Status column added successfully!";
    } else {
        echo "Error adding status column: " . mysqli_error($conn);
    }
} else {
    echo "Status column already exists.";
}
?>