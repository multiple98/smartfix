<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Order Tracking Table - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fix Order Tracking Table</h1>
        <div class='info'>Fixing the order_tracking table structure to resolve timestamp column issues.</div>";

$fixes_applied = 0;
$errors = 0;

// Check if order_tracking table exists
try {
    $check_table = "SHOW TABLES LIKE 'order_tracking'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Order tracking table doesn't exist. Creating it...</div>";
        
        // Create the table with proper structure
        $create_table = "CREATE TABLE order_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            status VARCHAR(100) NOT NULL,
            description TEXT,
            location VARCHAR(200),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_created_at (created_at)
        )";
        
        $pdo->exec($create_table);
        echo "<div class='success'>✅ Created order_tracking table with proper structure!</div>";
        $fixes_applied++;
    } else {
        echo "<div class='success'>✅ Order tracking table exists.</div>";
        
        // Check current structure
        $describe = $pdo->query("DESCRIBE order_tracking");
        $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>Current table structure:</div>";
        echo "<table>
                <tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $existing_columns = [];
        foreach ($columns as $column) {
            $existing_columns[] = $column['Field'];
            echo "<tr>
                    <td>" . htmlspecialchars($column['Field']) . "</td>
                    <td>" . htmlspecialchars($column['Type']) . "</td>
                    <td>" . htmlspecialchars($column['Null']) . "</td>
                    <td>" . htmlspecialchars($column['Key']) . "</td>
                    <td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>
                  </tr>";
        }
        echo "</table>";
        
        // Check for required columns and add if missing
        $required_columns = [
            'created_at' => "ALTER TABLE order_tracking ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "ALTER TABLE order_tracking ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column_name => $sql) {
            if (!in_array($column_name, $existing_columns)) {
                try {
                    echo "<div class='info'>Adding missing column: $column_name...</div>";
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ Added $column_name column!</div>";
                    $fixes_applied++;
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to add $column_name: " . $e->getMessage() . "</div>";
                    $errors++;
                }
            } else {
                echo "<div class='success'>✅ Column $column_name already exists.</div>";
            }
        }
        
        // Remove problematic timestamp column if it exists and is causing issues
        if (in_array('timestamp', $existing_columns) && !in_array('created_at', $existing_columns)) {
            try {
                echo "<div class='info'>Renaming 'timestamp' column to 'created_at' for consistency...</div>";
                $pdo->exec("ALTER TABLE order_tracking CHANGE timestamp created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                echo "<div class='success'>✅ Renamed timestamp column to created_at!</div>";
                $fixes_applied++;
            } catch (PDOException $e) {
                echo "<div class='warning'>⚠️ Could not rename timestamp column: " . $e->getMessage() . "</div>";
                // Try to add created_at alongside timestamp
                try {
                    $pdo->exec("ALTER TABLE order_tracking ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                    echo "<div class='success'>✅ Added created_at column alongside timestamp!</div>";
                    $fixes_applied++;
                } catch (PDOException $e2) {
                    echo "<div class='error'>❌ Could not add created_at column: " . $e2->getMessage() . "</div>";
                    $errors++;
                }
            }
        }
    }
    
    // Test the table structure
    echo "<div class='info'>🧪 Testing the fixed table structure...</div>";
    
    $test_queries = [
        'Basic Select' => "SELECT * FROM order_tracking LIMIT 1",
        'Order by created_at' => "SELECT * FROM order_tracking ORDER BY created_at DESC LIMIT 1",
        'Insert Test' => "INSERT INTO order_tracking (order_id, status, description, location) VALUES (0, 'Test', 'Test entry', 'Test Location')"
    ];
    
    foreach ($test_queries as $test_name => $query) {
        try {
            if (strpos($query, 'INSERT') === 0) {
                $pdo->exec($query);
                // Clean up test record
                $pdo->exec("DELETE FROM order_tracking WHERE order_id = 0 AND status = 'Test'");
            } else {
                $pdo->query($query);
            }
            echo "<div class='success'>✅ $test_name test PASSED!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ $test_name test FAILED: " . $e->getMessage() . "</div>";
            $errors++;
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error working with order_tracking table: " . $e->getMessage() . "</div>";
    $errors++;
}

// Summary
if ($errors == 0) {
    echo "<div class='success'>
            <h2>🎉 Order Tracking Table Fixed Successfully!</h2>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p>The order confirmation page should now work without timestamp column errors!</p>
          </div>";
} else {
    echo "<div class='warning'>
            <h2>⚠️ Fix Completed with Some Issues</h2>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p>Most issues have been resolved, but please check the errors above.</p>
          </div>";
}

echo "
        <h2>🎯 Next Steps</h2>
        <div class='info'>
            <p>Now that the order_tracking table has been fixed:</p>
            <ul>
                <li>Test the order confirmation page</li>
                <li>Try placing an order and viewing the confirmation</li>
                <li>Test transport selection functionality</li>
                <li>Check order tracking updates</li>
            </ul>
        </div>
        
        <a href='shop/order_confirmation.php?id=1' class='btn'>🧪 Test Order Confirmation</a>
        <a href='test_transport_selection.php' class='btn'>🚚 Test Transport Selection</a>
        <a href='admin/admin_dashboard_new.php' class='btn'>🏠 Admin Dashboard</a>
        
    </div>
</body>
</html>";
?>