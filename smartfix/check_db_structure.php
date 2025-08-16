<?php
include('includes/db.php');

echo "<h2>Database Structure Check</h2>";

try {
    // Check if users table exists and show its structure
    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll();
    
    echo "<h3>Users Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $user_columns = [];
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        $user_columns[] = $column['Field'];
    }
    echo "</table>";
    
    // Check for user_type column
    if (!in_array('user_type', $user_columns)) {
        echo "<p style='color: red;'>❌ user_type column is MISSING from users table</p>";
        echo "<p>This is causing the admin dashboard error.</p>";
    } else {
        echo "<p style='color: green;'>✅ user_type column exists in users table</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking users table: " . $e->getMessage() . "</p>";
}

// Check if technicians table exists
try {
    $stmt = $pdo->query('DESCRIBE technicians');
    $columns = $stmt->fetchAll();
    
    echo "<h3>Technicians Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ technicians table exists</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ technicians table does not exist: " . $e->getMessage() . "</p>";
}

// Check service_requests table
try {
    $stmt = $pdo->query('DESCRIBE service_requests');
    $columns = $stmt->fetchAll();
    
    echo "<h3>Service Requests Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✅ service_requests table exists</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ service_requests table does not exist: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Recommended Fix for Admin Dashboard:</h3>";
echo "<p>Since the users table likely doesn't have a 'user_type' column, we should:</p>";
echo "<ol>";
echo "<li><strong>Option 1:</strong> Count all users from the 'users' table</li>";
echo "<li><strong>Option 2:</strong> Count technicians from a separate 'technicians' table if it exists</li>";
echo "<li><strong>Option 3:</strong> Add a 'user_type' column to the users table</li>";
echo "</ol>";

echo "<p><a href='admin/admin_dashboard_new.php'>← Back to Admin Dashboard</a></p>";
?>