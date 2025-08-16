<?php
include 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Transport Tables Diagnostic - SmartFix</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007BFF; border-bottom: 2px solid #007BFF; padding-bottom: 10px; }
        h2 { color: #28a745; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .missing { background: #f8d7da; }
        .exists { background: #d4edda; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Transport Tables Diagnostic</h1>
        <div class='info'>This diagnostic will check all transport-related tables and their columns.</div>";

$transport_tables = ['transport_providers', 'transport_quotes', 'delivery_tracking'];

foreach ($transport_tables as $table_name) {
    echo "<h2>📋 Table: $table_name</h2>";
    
    try {
        // Check if table exists
        $check_table = "SHOW TABLES LIKE '$table_name'";
        $result = $pdo->query($check_table);
        
        if ($result->rowCount() == 0) {
            echo "<div class='error'>❌ Table '$table_name' does not exist!</div>";
            continue;
        }
        
        echo "<div class='success'>✅ Table '$table_name' exists.</div>";
        
        // Get table structure
        $describe = $pdo->query("DESCRIBE $table_name");
        $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
                <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>";
        
        foreach ($columns as $column) {
            echo "<tr>
                    <td><strong>" . htmlspecialchars($column['Field']) . "</strong></td>
                    <td>" . htmlspecialchars($column['Type']) . "</td>
                    <td>" . htmlspecialchars($column['Null']) . "</td>
                    <td>" . htmlspecialchars($column['Key']) . "</td>
                    <td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>
                    <td>" . htmlspecialchars($column['Extra']) . "</td>
                  </tr>";
        }
        echo "</table>";
        
        // Count records
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM $table_name");
        $record_count = $count_stmt->fetchColumn();
        echo "<div class='info'>📊 Records in table: $record_count</div>";
        
        // Show sample data if exists
        if ($record_count > 0 && $record_count <= 5) {
            echo "<div class='info'>📄 Sample data:</div>";
            $sample_stmt = $pdo->query("SELECT * FROM $table_name LIMIT 3");
            $sample_data = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sample_data)) {
                echo "<table>";
                // Headers
                echo "<tr>";
                foreach (array_keys($sample_data[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                // Data
                foreach ($sample_data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        $display_value = $value;
                        if (strlen($display_value) > 50) {
                            $display_value = substr($display_value, 0, 50) . '...';
                        }
                        echo "<td>" . htmlspecialchars($display_value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error checking table '$table_name': " . $e->getMessage() . "</div>";
    }
}

// Check for expected columns in transport_providers
echo "<h2>🔍 Expected Columns Check</h2>";

$expected_columns = [
    'transport_providers' => [
        'id', 'name', 'contact', 'email', 'description', 'regions', 'address',
        'cost_per_km', 'base_cost', 'estimated_days', 'max_weight_kg',
        'vehicle_type', 'service_type', 'status', 'rating',
        'latitude', 'longitude', 'operating_hours', 'created_at', 'updated_at'
    ],
    'transport_quotes' => [
        'id', 'order_id', 'transport_provider_id', 'pickup_address', 'delivery_address',
        'distance_km', 'estimated_cost', 'estimated_delivery_time', 'quote_valid_until',
        'status', 'created_at', 'updated_at'
    ],
    'delivery_tracking' => [
        'id', 'order_id', 'transport_provider_id', 'driver_name', 'driver_phone',
        'vehicle_number', 'current_latitude', 'current_longitude', 'status',
        'estimated_arrival', 'actual_delivery_time', 'delivery_notes',
        'customer_signature', 'proof_of_delivery', 'created_at', 'updated_at'
    ]
];

foreach ($expected_columns as $table_name => $expected_cols) {
    echo "<h3>Table: $table_name</h3>";
    
    try {
        $check_table = "SHOW TABLES LIKE '$table_name'";
        $result = $pdo->query($check_table);
        
        if ($result->rowCount() == 0) {
            echo "<div class='error'>❌ Table '$table_name' missing - all columns missing!</div>";
            continue;
        }
        
        // Get actual columns
        $describe = $pdo->query("DESCRIBE $table_name");
        $actual_columns = [];
        while ($row = $describe->fetch(PDO::FETCH_ASSOC)) {
            $actual_columns[] = $row['Field'];
        }
        
        echo "<table>
                <tr><th>Expected Column</th><th>Status</th></tr>";
        
        foreach ($expected_cols as $expected_col) {
            $status = in_array($expected_col, $actual_columns) ? 'exists' : 'missing';
            $class = $status === 'exists' ? 'exists' : 'missing';
            $icon = $status === 'exists' ? '✅' : '❌';
            
            echo "<tr class='$class'>
                    <td>$expected_col</td>
                    <td>$icon " . ucfirst($status) . "</td>
                  </tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error checking columns for '$table_name': " . $e->getMessage() . "</div>";
    }
}

echo "
        <h2>🛠️ Recommended Actions</h2>
        <div class='info'>
            <p>Based on the diagnostic results above:</p>
            <ul>
                <li>If tables are missing: Run the complete transport system setup</li>
                <li>If columns are missing: Run the emergency column fix</li>
                <li>If everything exists: Test the transport functionality</li>
            </ul>
        </div>
        
        <a href='enhanced_transport_system.php' class='btn'>🔧 Complete Setup</a>
        <a href='emergency_fix_address_column.php' class='btn'>🚨 Emergency Fix</a>
        <a href='fix_transport_database_complete.php' class='btn'>🔧 Complete Fix</a>
        <a href='admin/test_transport_integration.php' class='btn'>🧪 Test System</a>
        
    </div>
</body>
</html>";
?>