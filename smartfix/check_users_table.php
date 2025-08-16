<?php
include('includes/db.php');

echo "<h2>Users Table Structure Check</h2>";

try {
    // Check if users table exists and has required columns
    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll();
    
    echo "<h3>Current Users Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $required_columns = ['is_verified', 'verification_token', 'verification_sent_at', 'email_verified_at'];
    $existing_columns = [];
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        $existing_columns[] = $column['Field'];
    }
    echo "</table>";
    
    // Check for missing columns
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        echo "<h3 style='color: red;'>Missing Required Columns:</h3>";
        echo "<ul>";
        foreach ($missing_columns as $column) {
            echo "<li style='color: red;'>$column</li>";
        }
        echo "</ul>";
        
        echo "<h3>SQL to Add Missing Columns:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        
        $sql_commands = [];
        if (in_array('is_verified', $missing_columns)) {
            $sql_commands[] = "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0;";
        }
        if (in_array('verification_token', $missing_columns)) {
            $sql_commands[] = "ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL;";
        }
        if (in_array('verification_sent_at', $missing_columns)) {
            $sql_commands[] = "ALTER TABLE users ADD COLUMN verification_sent_at TIMESTAMP NULL;";
        }
        if (in_array('email_verified_at', $missing_columns)) {
            $sql_commands[] = "ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;";
        }
        
        foreach ($sql_commands as $sql) {
            echo $sql . "\n";
        }
        echo "</pre>";
        
        // Auto-fix option
        if (isset($_GET['fix']) && $_GET['fix'] == 'true') {
            echo "<h3>Executing Fixes...</h3>";
            foreach ($sql_commands as $sql) {
                try {
                    $pdo->exec($sql);
                    echo "<p style='color: green;'>✓ Executed: $sql</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>✗ Failed: $sql - " . $e->getMessage() . "</p>";
                }
            }
            echo "<p><a href='check_users_table.php'>Refresh to see updated structure</a></p>";
        } else {
            echo "<p><a href='check_users_table.php?fix=true' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Auto-Fix Missing Columns</a></p>";
        }
    } else {
        echo "<h3 style='color: green;'>✓ All required columns are present!</h3>";
    }
    
    // Check for email_verification_logs table
    echo "<h3>Email Verification Logs Table:</h3>";
    try {
        $stmt = $pdo->query('DESCRIBE email_verification_logs');
        $log_columns = $stmt->fetchAll();
        echo "<p style='color: green;'>✓ email_verification_logs table exists</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ email_verification_logs table missing</p>";
        echo "<h4>SQL to Create Logs Table:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        $create_logs_sql = "
CREATE TABLE email_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    verification_token VARCHAR(64),
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_token (verification_token)
);";
        echo $create_logs_sql;
        echo "</pre>";
        
        if (isset($_GET['create_logs']) && $_GET['create_logs'] == 'true') {
            try {
                $pdo->exec($create_logs_sql);
                echo "<p style='color: green;'>✓ Created email_verification_logs table</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to create logs table: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p><a href='check_users_table.php?create_logs=true' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Create Logs Table</a></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='register.php'>← Back to Registration</a> | <a href='quick_verify.php'>Quick Verify Page</a></p>";
?>