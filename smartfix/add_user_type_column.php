<?php
include('includes/db.php');

echo "<h2>Add user_type Column to Users Table</h2>";

try {
    // Check if user_type column already exists
    $check_query = "SHOW COLUMNS FROM users LIKE 'user_type'";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ user_type column already exists in users table!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ user_type column does not exist in users table.</p>";
        
        if (isset($_GET['add']) && $_GET['add'] == 'true') {
            try {
                // Add the user_type column
                $alter_query = "ALTER TABLE users ADD COLUMN user_type ENUM('user', 'technician', 'admin') DEFAULT 'user'";
                $pdo->exec($alter_query);
                
                echo "<p style='color: green;'>✅ Successfully added user_type column to users table!</p>";
                
                // Optionally set existing users to have proper types
                echo "<h3>Setting Default Values:</h3>";
                
                // Set all existing users as 'user' type (they already have DEFAULT 'user')
                echo "<p>All existing users will have user_type = 'user' by default.</p>";
                
                // Check if there are records in the technicians table to update user types
                try {
                    $tech_check = "SELECT email FROM technicians LIMIT 1";
                    $tech_stmt = $pdo->prepare($tech_check);
                    $tech_stmt->execute();
                    
                    if ($tech_stmt->rowCount() > 0) {
                        echo "<p>Found technicians table. You can run this query to update user types for technicians:</p>";
                        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
                        echo "UPDATE users u \nINNER JOIN technicians t ON u.email = t.email \nSET u.user_type = 'technician';";
                        echo "</pre>";
                        
                        if (isset($_GET['update_techs']) && $_GET['update_techs'] == 'true') {
                            try {
                                $update_query = "UPDATE users u INNER JOIN technicians t ON u.email = t.email SET u.user_type = 'technician'";
                                $pdo->exec($update_query);
                                echo "<p style='color: green;'>✅ Updated technician user types successfully!</p>";
                            } catch (Exception $e) {
                                echo "<p style='color: red;'>❌ Error updating technician types: " . $e->getMessage() . "</p>";
                            }
                        } else {
                            echo "<p><a href='add_user_type_column.php?add=true&update_techs=true' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Update Technician Types</a></p>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p>No technicians table found or error checking: " . $e->getMessage() . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error adding user_type column: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<h3>What this will do:</h3>";
            echo "<ul>";
            echo "<li>Add a user_type column to the users table</li>";
            echo "<li>Column type: ENUM('user', 'technician', 'admin')</li>";
            echo "<li>Default value: 'user'</li>";
            echo "<li>All existing users will be set as 'user' type</li>";
            echo "</ul>";
            
            echo "<p><a href='add_user_type_column.php?add=true' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Add user_type Column</a></p>";
        }
    }
    
    // Show current users table structure
    echo "<h3>Current Users Table Structure:</h3>";
    $describe_query = "DESCRIBE users";
    $describe_stmt = $pdo->prepare($describe_query);
    $describe_stmt->execute();
    $columns = $describe_stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        $highlight = ($column['Field'] == 'user_type') ? "style='background-color: #d4edda;'" : "";
        echo "<tr $highlight>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Alternative Solutions:</h3>";
echo "<p>If you prefer not to add a user_type column, the admin dashboard has been fixed to:</p>";
echo "<ul>";
echo "<li><strong>Count users:</strong> From the 'users' table (all users)</li>";
echo "<li><strong>Count technicians:</strong> From the 'technicians' table if it exists</li>";
echo "</ul>";

echo "<p><a href='admin/admin_dashboard_new.php'>← Back to Admin Dashboard</a> | <a href='check_db_structure.php'>View Database Structure</a></p>";
?>