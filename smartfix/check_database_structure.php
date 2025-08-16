<?php
// Database Structure Diagnostic Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$results = [];

try {
    $results[] = "🔍 Checking SmartFix Database Structure...";
    
    // Test database connection
    $pdo->query("SELECT 1");
    $results[] = "✅ Database connection successful";
    
    // Check service_requests table
    $results[] = "\n📋 Checking service_requests table:";
    try {
        $stmt = $pdo->query("DESCRIBE service_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results[] = "✅ service_requests table exists";
        $results[] = "   Columns found:";
        foreach ($columns as $column) {
            $results[] = "   - {$column['Field']} ({$column['Type']})";
        }
        
        // Check for required columns
        $column_names = array_column($columns, 'Field');
        $required_columns = ['id', 'name', 'email', 'service_type', 'description'];
        
        foreach ($required_columns as $req_col) {
            if (in_array($req_col, $column_names)) {
                $results[] = "   ✅ Required column '$req_col' exists";
            } else {
                $results[] = "   ❌ Missing required column '$req_col'";
            }
        }
        
    } catch (PDOException $e) {
        $results[] = "❌ service_requests table missing or inaccessible: " . $e->getMessage();
    }
    
    // Check notifications table
    $results[] = "\n🔔 Checking notifications table:";
    try {
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results[] = "✅ notifications table exists";
        $results[] = "   Columns found:";
        foreach ($columns as $column) {
            $results[] = "   - {$column['Field']} ({$column['Type']})";
        }
        
    } catch (PDOException $e) {
        $results[] = "❌ notifications table missing: " . $e->getMessage();
    }
    
    // Check email_logs table
    $results[] = "\n📧 Checking email_logs table:";
    try {
        $stmt = $pdo->query("DESCRIBE email_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results[] = "✅ email_logs table exists";
        $results[] = "   Columns found:";
        foreach ($columns as $column) {
            $results[] = "   - {$column['Field']} ({$column['Type']})";
        }
        
    } catch (PDOException $e) {
        $results[] = "❌ email_logs table missing: " . $e->getMessage();
    }
    
    // Test insert capability
    $results[] = "\n🧪 Testing insert capability:";
    try {
        $test_query = "INSERT INTO service_requests (name, email, service_type, description, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $test_stmt = $pdo->prepare($test_query);
        $test_stmt->execute(['Test User', 'test@example.com', 'phone', 'Test description', 'pending']);
        
        $test_id = $pdo->lastInsertId();
        $results[] = "✅ Test insert successful (ID: $test_id)";
        
        // Clean up
        $pdo->prepare("DELETE FROM service_requests WHERE id = ?")->execute([$test_id]);
        $results[] = "✅ Test data cleaned up";
        
    } catch (PDOException $e) {
        $results[] = "❌ Insert test failed: " . $e->getMessage();
    }
    
    // Check existing service requests
    $results[] = "\n📊 Checking existing data:";
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM service_requests");
        $count = $count_stmt->fetch()['count'];
        $results[] = "📈 Total service requests: $count";
        
        $today_stmt = $pdo->query("SELECT COUNT(*) as count FROM service_requests WHERE DATE(created_at) = CURDATE()");
        $today_count = $today_stmt->fetch()['count'];
        $results[] = "📅 Today's requests: $today_count";
        
    } catch (PDOException $e) {
        $results[] = "⚠️ Could not check existing data: " . $e->getMessage();
    }
    
    $results[] = "\n🎯 Diagnosis complete!";
    
} catch (PDOException $e) {
    $results[] = "❌ Database connection failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #007BFF;
            margin-top: 0;
            border-bottom: 3px solid #007BFF;
            padding-bottom: 15px;
            text-align: center;
        }
        
        .results {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            white-space: pre-line;
        }
        
        .buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Structure Diagnostic</h1>
        
        <div class="results"><?php 
            foreach ($results as $result) {
                echo htmlspecialchars($result) . "\n";
            }
        ?></div>
        
        <div class="buttons">
            <a href="setup_complete_email_system.php" class="btn btn-warning">🔧 Run Database Setup</a>
            <a href="debug_service_request.php" class="btn btn-success">🧪 Test Service Request</a>
            <a href="services/request_service.php?type=phone" class="btn">📱 Regular Service Form</a>
            <a href="admin/admin_dashboard_new.php" class="btn">👨‍💼 Admin Dashboard</a>
        </div>
    </div>
</body>
</html>