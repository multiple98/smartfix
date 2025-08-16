<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

$messages = [];
$errors = [];

// Function to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // Fallback method for older MySQL versions or compatibility issues
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Field'] === $column) {
                    return true;
                }
            }
            return false;
        } catch (PDOException $e2) {
            return false;
        }
    }
}

// Function to add column if it doesn't exist
function addColumnIfNotExists($pdo, $table, $column, $definition, &$messages, &$errors) {
    if (!columnExists($pdo, $table, $column)) {
        try {
            $query = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $pdo->exec($query);
            $messages[] = "✅ Added column '$column' to table '$table'";
            return true;
        } catch (Exception $e) {
            $errors[] = "❌ Failed to add column '$column' to table '$table': " . $e->getMessage();
            return false;
        }
    } else {
        $messages[] = "ℹ️ Column '$column' already exists in table '$table'";
        return true;
    }
}

// Run fixes if requested
if (isset($_POST['fix_table'])) {
    try {
        // Check if service_requests table exists
        $table_check = $pdo->prepare("SHOW TABLES LIKE 'service_requests'");
        $table_check->execute();
        
        if ($table_check->rowCount() == 0) {
            // Create the service_requests table
            $create_table = "
                CREATE TABLE `service_requests` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `email` varchar(100) NOT NULL,
                    `phone` varchar(20) DEFAULT NULL,
                    `service_type` varchar(50) NOT NULL,
                    `service_option` varchar(100) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `address` text DEFAULT NULL,
                    `preferred_date` date DEFAULT NULL,
                    `preferred_time` time DEFAULT NULL,
                    `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
                    `technician_id` int(11) DEFAULT NULL,
                    `user_id` int(11) DEFAULT NULL,
                    `priority` enum('low','normal','high','emergency') DEFAULT 'normal',
                    `is_emergency` tinyint(1) DEFAULT 0,
                    `latitude` decimal(10,8) DEFAULT NULL,
                    `longitude` decimal(11,8) DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    `request_date` timestamp NULL DEFAULT current_timestamp(),
                    `reference_number` varchar(20) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_technician` (`technician_id`),
                    KEY `idx_user` (`user_id`),
                    KEY `idx_created_at` (`created_at`),
                    KEY `idx_reference` (`reference_number`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $pdo->exec($create_table);
            $messages[] = "✅ Created service_requests table with proper structure";
        } else {
            $messages[] = "ℹ️ Service_requests table already exists - checking columns...";
            
            // Define required columns
            $required_columns = [
                'name' => 'varchar(100) NOT NULL',
                'email' => 'varchar(100) NOT NULL', 
                'phone' => 'varchar(20) DEFAULT NULL',
                'service_type' => 'varchar(50) NOT NULL',
                'service_option' => 'varchar(100) DEFAULT NULL',
                'description' => 'text DEFAULT NULL',
                'address' => 'text DEFAULT NULL',
                'preferred_date' => 'date DEFAULT NULL',
                'preferred_time' => 'time DEFAULT NULL',
                'status' => 'enum(\'pending\',\'confirmed\',\'in_progress\',\'completed\',\'cancelled\') DEFAULT \'pending\'',
                'technician_id' => 'int(11) DEFAULT NULL',
                'user_id' => 'int(11) DEFAULT NULL',
                'priority' => 'enum(\'low\',\'normal\',\'high\',\'emergency\') DEFAULT \'normal\'',
                'is_emergency' => 'tinyint(1) DEFAULT 0',
                'latitude' => 'decimal(10,8) DEFAULT NULL',
                'longitude' => 'decimal(11,8) DEFAULT NULL',
                'created_at' => 'timestamp NULL DEFAULT current_timestamp()',
                'updated_at' => 'timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()',
                'request_date' => 'timestamp NULL DEFAULT current_timestamp()',
                'reference_number' => 'varchar(20) DEFAULT NULL'
            ];
            
            // Add missing columns
            foreach ($required_columns as $column => $definition) {
                addColumnIfNotExists($pdo, 'service_requests', $column, $definition, $messages, $errors);
            }
            
            // Add indexes if they don't exist
            $indexes = [
                'idx_status' => 'CREATE INDEX idx_status ON service_requests (status)',
                'idx_technician' => 'CREATE INDEX idx_technician ON service_requests (technician_id)',
                'idx_user' => 'CREATE INDEX idx_user ON service_requests (user_id)',
                'idx_created_at' => 'CREATE INDEX idx_created_at ON service_requests (created_at)',
                'idx_reference' => 'CREATE INDEX idx_reference ON service_requests (reference_number)'
            ];
            
            foreach ($indexes as $index_name => $index_query) {
                try {
                    // Check if index exists
                    $check_index = $pdo->prepare("SHOW INDEX FROM service_requests WHERE Key_name = ?");
                    $check_index->execute([$index_name]);
                    
                    if ($check_index->rowCount() == 0) {
                        $pdo->exec($index_query);
                        $messages[] = "✅ Added index '$index_name'";
                    } else {
                        $messages[] = "ℹ️ Index '$index_name' already exists";
                    }
                } catch (Exception $e) {
                    $errors[] = "❌ Failed to add index '$index_name': " . $e->getMessage();
                }
            }
        }
        
        // Add sample data if table is empty
        $count_check = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
        if ($count_check == 0) {
            $sample_requests = [
                ['John Doe', 'john@example.com', '555-0101', 'phone', 'Screen Repair', 'iPhone screen cracked after drop', '123 Main St', '2024-01-15', '10:00:00', 'pending'],
                ['Jane Smith', 'jane@example.com', '555-0102', 'laptop', 'Hardware Issue', 'Laptop won\'t turn on', '456 Oak Ave', '2024-01-16', '14:00:00', 'pending'],
                ['Mike Johnson', 'mike@example.com', '555-0103', 'car', 'Oil Change', 'Regular maintenance required', '789 Pine St', '2024-01-17', '09:00:00', 'confirmed'],
                ['Sarah Wilson', 'sarah@example.com', '555-0104', 'appliance', 'Refrigerator Repair', 'Not cooling properly', '321 Elm St', '2024-01-18', '11:00:00', 'in_progress'],
                ['Tom Brown', 'tom@example.com', '555-0105', 'plumbing', 'Leak Repair', 'Kitchen sink is leaking', '654 Cedar Ave', '2024-01-19', '13:00:00', 'completed']
            ];
            
            $insert_stmt = $pdo->prepare("INSERT INTO service_requests (name, email, phone, service_type, service_option, description, address, preferred_date, preferred_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            foreach ($sample_requests as $request) {
                $insert_stmt->execute($request);
            }
            
            $messages[] = "✅ Added " . count($sample_requests) . " sample service requests";
        }
        
        $messages[] = "🎉 Service requests table structure has been verified and fixed!";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error fixing service requests table: " . $e->getMessage();
    }
}

// Get current table structure
$current_structure = [];
try {
    $structure_query = "DESCRIBE service_requests";
    $structure_result = $pdo->query($structure_query);
    $current_structure = $structure_result->fetchAll();
} catch (Exception $e) {
    $errors[] = "❌ Could not get table structure: " . $e->getMessage();
}

// Get row count
$row_count = 0;
try {
    $row_count = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
} catch (Exception $e) {
    // Table might not exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Service Requests Table - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--light-bg);
            padding: 2rem;
            color: var(--dark-color);
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            text-align: center;
        }
        
        .header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .fix-btn {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .fix-btn:hover {
            background: #218838;
        }
        
        .messages {
            margin: 2rem 0;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            border-left: 4px solid;
        }
        
        .message.success {
            background: rgba(40, 167, 69, 0.1);
            border-color: var(--success-color);
            color: #155724;
        }
        
        .message.error {
            background: rgba(220, 53, 69, 0.1);
            border-color: var(--danger-color);
            color: #721c24;
        }
        
        .structure-card, .status-card {
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            background: var(--light-bg);
        }
        
        .card-header h2 {
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .card-header h2 i {
            margin-right: 0.75rem;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .structure-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .structure-table th,
        .structure-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .structure-table th {
            background: var(--light-bg);
            font-weight: 600;
        }
        
        .structure-table tbody tr:hover {
            background: rgba(0, 123, 255, 0.03);
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .status-item {
            text-align: center;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        .status-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .status-icon.success {
            color: var(--success-color);
        }
        
        .status-icon.warning {
            color: var(--warning-color);
        }
        
        .status-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .status-label {
            color: var(--dark-color);
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .back-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard_new.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-tools"></i> Service Requests Table Repair Tool</h1>
            <p>Fix and verify the service_requests database table structure</p>
            
            <form method="POST" style="margin-top: 2rem;">
                <button type="submit" name="fix_table" class="fix-btn" onclick="return confirm('This will modify your database structure. Continue?')">
                    <i class="fas fa-wrench"></i> Fix & Verify Table Structure
                </button>
            </form>
        </div>
        
        <?php if (!empty($messages) || !empty($errors)): ?>
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="status-card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Current Status</h2>
            </div>
            <div class="card-body">
                <div class="status-grid">
                    <div class="status-item">
                        <div class="status-icon <?php echo !empty($current_structure) ? 'success' : 'warning'; ?>">
                            <i class="fas fa-table"></i>
                        </div>
                        <div class="status-value"><?php echo !empty($current_structure) ? 'EXISTS' : 'MISSING'; ?></div>
                        <div class="status-label">Table Status</div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon success">
                            <i class="fas fa-columns"></i>
                        </div>
                        <div class="status-value"><?php echo count($current_structure); ?></div>
                        <div class="status-label">Columns</div>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon success">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="status-value"><?php echo number_format($row_count); ?></div>
                        <div class="status-label">Records</div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($current_structure)): ?>
        <div class="structure-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Current Table Structure</h2>
            </div>
            <div class="card-body">
                <table class="structure-table">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Type</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                            <th>Extra</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_structure as $column): ?>
                            <tr>
                                <td><strong><?php echo $column['Field']; ?></strong></td>
                                <td><?php echo $column['Type']; ?></td>
                                <td><?php echo $column['Null']; ?></td>
                                <td><?php echo $column['Key']; ?></td>
                                <td><?php echo $column['Default'] ?? 'NULL'; ?></td>
                                <td><?php echo $column['Extra']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="service_requests.php" class="back-btn">
                <i class="fas fa-list"></i> View Service Requests
            </a>
            <a href="admin_dashboard_new.php" class="back-btn">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
        </div>
    </div>
</body>
</html>