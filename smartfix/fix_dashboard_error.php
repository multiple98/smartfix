<?php
// Fix Dashboard Column Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$messages = [];
$success = true;

try {
    $messages[] = "🔧 Fixing Admin Dashboard Column Error";
    $messages[] = "====================================";
    
    // Check what columns exist in service_requests table
    $messages[] = "\n🔍 Checking service_requests table structure...";
    
    $columns_result = $pdo->query("DESCRIBE service_requests");
    $existing_columns = [];
    
    while ($column = $columns_result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $column['Field'];
        $messages[] = "   - {$column['Field']} ({$column['Type']})";
    }
    
    // Check if created_at column exists
    if (in_array('created_at', $existing_columns)) {
        $messages[] = "✅ created_at column already exists";
    } else {
        $messages[] = "⚠️ created_at column missing - adding it...";
        
        try {
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            $messages[] = "✅ created_at column added successfully";
        } catch (PDOException $e) {
            $messages[] = "❌ Could not add created_at column: " . $e->getMessage();
            $success = false;
        }
    }
    
    // Check if request_date column exists
    if (in_array('request_date', $existing_columns)) {
        $messages[] = "✅ request_date column exists";
    } else {
        $messages[] = "⚠️ request_date column missing - adding it...";
        
        try {
            $pdo->exec("ALTER TABLE service_requests ADD COLUMN request_date DATETIME DEFAULT CURRENT_TIMESTAMP");
            $messages[] = "✅ request_date column added successfully";
        } catch (PDOException $e) {
            $messages[] = "❌ Could not add request_date column: " . $e->getMessage();
            $success = false;
        }
    }
    
    // Update existing records that might have NULL dates
    $messages[] = "\n🔄 Updating existing records with missing dates...";
    
    $update_queries = [
        "UPDATE service_requests SET created_at = NOW() WHERE created_at IS NULL",
        "UPDATE service_requests SET request_date = created_at WHERE request_date IS NULL AND created_at IS NOT NULL",
        "UPDATE service_requests SET request_date = NOW() WHERE request_date IS NULL"
    ];
    
    foreach ($update_queries as $query) {
        try {
            $result = $pdo->exec($query);
            $messages[] = "✅ Updated $result records";
        } catch (PDOException $e) {
            $messages[] = "⚠️ Update query issue: " . $e->getMessage();
        }
    }
    
    // Test the admin dashboard query
    $messages[] = "\n🧪 Testing admin dashboard queries...";
    
    try {
        // Test today's count query
        $today_query = "SELECT COUNT(*) as count FROM service_requests WHERE DATE(COALESCE(request_date, created_at)) = CURDATE()";
        $today_stmt = $pdo->prepare($today_query);
        $today_stmt->execute();
        $today_count = $today_stmt->fetchColumn();
        $messages[] = "✅ Today's requests query works: $today_count requests today";
        
        // Test total count
        $total_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests");
        $total_stmt->execute();
        $total_count = $total_stmt->fetchColumn();
        $messages[] = "✅ Total requests query works: $total_count total requests";
        
        // Test pending count
        $pending_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
        $pending_stmt->execute();
        $pending_count = $pending_stmt->fetchColumn();
        $messages[] = "✅ Pending requests query works: $pending_count pending requests";
        
        // Test recent requests query
        $recent_query = "SELECT * FROM service_requests ORDER BY COALESCE(request_date, created_at) DESC LIMIT 5";
        $recent_stmt = $pdo->prepare($recent_query);
        $recent_stmt->execute();
        $recent_requests = $recent_stmt->fetchAll();
        $messages[] = "✅ Recent requests query works: " . count($recent_requests) . " recent requests found";
        
    } catch (PDOException $e) {
        $messages[] = "❌ Dashboard query test failed: " . $e->getMessage();
        $success = false;
    }
    
    if ($success) {
        $messages[] = "\n🎉 SUCCESS: Admin dashboard should now work without errors!";
    } else {
        $messages[] = "\n⚠️ Some issues remain. Please check the errors above.";
    }
    
} catch (Exception $e) {
    $messages[] = "❌ Critical error: " . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Dashboard Error - SmartFix</title>
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
            color: <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            margin-top: 0;
            border-bottom: 3px solid <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            padding-bottom: 15px;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            width: 100%;
            <?php if ($success): ?>
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            <?php else: ?>
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            <?php endif; ?>
        }
        
        .results {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            white-space: pre-line;
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Admin Dashboard Fix</h1>
        
        <div class="status-badge">
            <?php if ($success): ?>
                ✅ Dashboard Error Fixed
            <?php else: ?>
                ⚠️ Issues Need Attention
            <?php endif; ?>
        </div>
        
        <div class="results"><?php 
            foreach ($messages as $message) {
                echo htmlspecialchars($message) . "\n";
            }
        ?></div>
        
        <div class="buttons">
            <?php if ($success): ?>
                <a href="admin/admin_dashboard_new.php" class="btn btn-success">👨‍💼 Test Admin Dashboard</a>
                <a href="services/request_service.php?type=phone" class="btn">📱 Test Service Request</a>
            <?php endif; ?>
            <a href="check_database_structure.php" class="btn">🔍 Check Database</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>