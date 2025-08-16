<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix All Timestamp Issues - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
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
        .section { margin: 30px 0; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Fix All Timestamp Issues</h1>
        <div class='info'>Comprehensive fix for all timestamp-related errors in the SmartFix system.</div>";

$fixes_applied = 0;
$errors = 0;

// Section 1: Fix order_tracking table
echo "<div class='section'>
        <h2>1. Order Tracking Table</h2>";

try {
    // Check if table exists
    $check_table = "SHOW TABLES LIKE 'order_tracking'";
    $result = $pdo->query($check_table);
    
    if ($result->rowCount() == 0) {
        echo "<div class='warning'>⚠️ Order tracking table doesn't exist. Creating it...</div>";
        
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
        // Check current structure
        $describe = $pdo->query("DESCRIBE order_tracking");
        $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
        
        $existing_columns = [];
        foreach ($columns as $column) {
            $existing_columns[] = $column['Field'];
        }
        
        echo "<div class='info'>Current columns: " . implode(', ', $existing_columns) . "</div>";
        
        // Add missing columns
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
            }
        }
        
        // Handle problematic timestamp column
        if (in_array('timestamp', $existing_columns) && !in_array('created_at', $existing_columns)) {
            try {
                echo "<div class='info'>Renaming 'timestamp' column to 'created_at'...</div>";
                $pdo->exec("ALTER TABLE order_tracking CHANGE timestamp created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                echo "<div class='success'>✅ Renamed timestamp column to created_at!</div>";
                $fixes_applied++;
            } catch (PDOException $e) {
                echo "<div class='warning'>⚠️ Could not rename timestamp column: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Test the table
    echo "<div class='info'>Testing order_tracking table...</div>";
    $test_queries = [
        'Select with created_at' => "SELECT * FROM order_tracking ORDER BY created_at DESC LIMIT 1",
        'Insert test' => "INSERT INTO order_tracking (order_id, status, description, location) VALUES (0, 'Test', 'Test entry', 'Test Location')"
    ];
    
    foreach ($test_queries as $test_name => $query) {
        try {
            if (strpos($query, 'INSERT') === 0) {
                $pdo->exec($query);
                $pdo->exec("DELETE FROM order_tracking WHERE order_id = 0 AND status = 'Test'");
            } else {
                $pdo->query($query);
            }
            echo "<div class='success'>✅ $test_name - PASSED</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ $test_name - FAILED: " . $e->getMessage() . "</div>";
            $errors++;
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error with order_tracking table: " . $e->getMessage() . "</div>";
    $errors++;
}
echo "</div>";

// Section 2: Check other tables that might have timestamp issues
echo "<div class='section'>
        <h2>2. Other Tables with Timestamp Columns</h2>";

$tables_to_check = ['orders', 'transport_quotes', 'notifications', 'users', 'products'];

foreach ($tables_to_check as $table_name) {
    try {
        $check_table = "SHOW TABLES LIKE '$table_name'";
        $result = $pdo->query($check_table);
        
        if ($result->rowCount() > 0) {
            $describe = $pdo->query("DESCRIBE $table_name");
            $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
            
            $timestamp_columns = [];
            foreach ($columns as $column) {
                if (strpos(strtolower($column['Type']), 'timestamp') !== false || 
                    strpos(strtolower($column['Type']), 'datetime') !== false ||
                    in_array($column['Field'], ['created_at', 'updated_at', 'timestamp'])) {
                    $timestamp_columns[] = $column['Field'];
                }
            }
            
            if (!empty($timestamp_columns)) {
                echo "<div class='success'>✅ Table '$table_name' has timestamp columns: " . implode(', ', $timestamp_columns) . "</div>";
            } else {
                echo "<div class='info'>ℹ️ Table '$table_name' has no timestamp columns</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ Table '$table_name' doesn't exist</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error checking table '$table_name': " . $e->getMessage() . "</div>";
        $errors++;
    }
}
echo "</div>";

// Section 3: Test timestamp functionality in key areas
echo "<div class='section'>
        <h2>3. Test Timestamp Functionality</h2>";

$test_scenarios = [
    'Order Tracking Query' => "SELECT * FROM order_tracking ORDER BY created_at DESC LIMIT 1",
    'Orders with Timestamps' => "SELECT id, created_at FROM orders ORDER BY created_at DESC LIMIT 1",
    'Transport Quotes' => "SELECT * FROM transport_quotes ORDER BY created_at DESC LIMIT 1"
];

foreach ($test_scenarios as $test_name => $query) {
    try {
        $result = $pdo->query($query);
        if ($result) {
            $row_count = $result->rowCount();
            echo "<div class='success'>✅ $test_name - Query successful ($row_count rows)</div>";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "<div class='warning'>⚠️ $test_name - Table doesn't exist (not critical)</div>";
        } else {
            echo "<div class='error'>❌ $test_name - FAILED: " . $e->getMessage() . "</div>";
            $errors++;
        }
    }
}
echo "</div>";

// Section 4: Create sample tracking data if none exists
echo "<div class='section'>
        <h2>4. Sample Tracking Data</h2>";

try {
    $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM order_tracking");
    $tracking_count = $count_stmt->fetchColumn();
    
    if ($tracking_count == 0) {
        echo "<div class='info'>No tracking data found. Creating sample entries...</div>";
        
        // Get a sample order ID
        $order_stmt = $pdo->query("SELECT id FROM orders LIMIT 1");
        $sample_order = $order_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sample_order) {
            $sample_tracking = [
                ['Order Placed', 'Your order has been received and is being processed.', 'SmartFix Warehouse'],
                ['Processing', 'Order is being prepared for shipment.', 'SmartFix Warehouse'],
                ['Ready for Pickup', 'Order is ready for transport pickup.', 'SmartFix Warehouse']
            ];
            
            foreach ($sample_tracking as $index => $track) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO order_tracking (order_id, status, description, location, created_at) VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
                    $stmt->execute([$sample_order['id'], $track[0], $track[1], $track[2], (3 - $index)]);
                    echo "<div class='success'>✅ Added sample tracking: " . $track[0] . "</div>";
                    $fixes_applied++;
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to add sample tracking: " . $e->getMessage() . "</div>";
                    $errors++;
                }
            }
        } else {
            echo "<div class='warning'>⚠️ No orders found to create sample tracking data</div>";
        }
    } else {
        echo "<div class='success'>✅ Found $tracking_count existing tracking entries</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error with sample tracking data: " . $e->getMessage() . "</div>";
    $errors++;
}
echo "</div>";

// Summary
echo "<div class='section'>
        <h2>🎯 Fix Summary</h2>";

$total_operations = $fixes_applied + $errors;
$success_rate = $total_operations > 0 ? round(($fixes_applied / $total_operations) * 100) : 100;

if ($errors == 0) {
    echo "<div class='success'>
            <h3>🎉 All Timestamp Issues Fixed!</h3>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p><strong>Success Rate:</strong> $success_rate%</p>
            <p>All timestamp-related functionality should now work properly!</p>
          </div>";
} else {
    echo "<div class='warning'>
            <h3>⚠️ Most Issues Fixed</h3>
            <p><strong>Fixes Applied:</strong> $fixes_applied</p>
            <p><strong>Errors:</strong> $errors</p>
            <p><strong>Success Rate:</strong> $success_rate%</p>
            <p>Most timestamp issues have been resolved. Please review any remaining errors above.</p>
          </div>";
}

echo "
        <h2>🧪 Test Your System</h2>
        <div class='info'>
            <p>Now test these areas to ensure timestamp issues are resolved:</p>
            <ul>
                <li><strong>Order Confirmation</strong> - View any order confirmation page</li>
                <li><strong>Order Tracking</strong> - Check order tracking displays</li>
                <li><strong>Transport Selection</strong> - Test transport provider selection</li>
                <li><strong>Admin Dashboard</strong> - Check order statistics and tracking</li>
            </ul>
        </div>
        
        <a href='shop/order_confirmation.php?id=1' class='btn'>🧪 Test Order Confirmation</a>
        <a href='test_transport_selection.php' class='btn'>🚚 Test Transport Selection</a>
        <a href='admin/admin_dashboard_new.php' class='btn'>🏠 Admin Dashboard</a>
        <a href='diagnose_transport_tables.php' class='btn'>🔍 Run Diagnostics</a>
        
    </div>
</body>
</html>";
?>