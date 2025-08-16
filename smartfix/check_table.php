<?php
include 'includes/db.php';

// Check users table structure
$query = "DESCRIBE users";
$result = mysqli_query($conn, $query);

echo "<h2>Users Table Structure:</h2>";
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";
?>