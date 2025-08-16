<?php
include 'includes/db.php';

// Check service_requests table structure
$query = "DESCRIBE service_requests";
$result = mysqli_query($conn, $query);

echo "<h2>Service Requests Table Structure:</h2>";
echo "<pre>";
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($conn);
    
    // Check if table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'service_requests'");
    if (mysqli_num_rows($check_table) == 0) {
        echo "\n\nTable 'service_requests' does not exist. You may need to run the create_service_requests_table.sql script.";
    }
}
echo "</pre>";

// Display sample data if available
echo "<h2>Sample Service Requests:</h2>";
echo "<pre>";
$sample_query = "SELECT * FROM service_requests LIMIT 5";
$sample_result = mysqli_query($conn, $sample_query);

if ($sample_result && mysqli_num_rows($sample_result) > 0) {
    while ($row = mysqli_fetch_assoc($sample_result)) {
        print_r($row);
    }
} else {
    echo "No service requests found or error: " . mysqli_error($conn);
}
echo "</pre>";
?>