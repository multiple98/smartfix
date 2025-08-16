<?php
// Fix Admin Table - Create missing admins table and default admin user
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$results = [];

try {
    $results[] = "🔧 Fixing Admin Table Issue...";
    
    // Test database connection
    $pdo->query("SELECT 1");
    $results[] = "✅ Database connection successful";
    
    // Check if admins table exists
    $results[] = "\n📋 Checking admins table:";
    try {
        $stmt = $pdo->query("DESCRIBE admins");
        $results[] = "✅ admins table already exists";
    } catch (PDOException $e) {
        $results[] = "❌ admins table missing, creating now...";
        
        // Create admins table
        $create_table_sql = "
        CREATE TABLE admins (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            security_question INT(11) NOT NULL DEFAULT 0,
            security_answer VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL
        )";
        
        $pdo->exec($create_table_sql);
        $results[] = "✅ admins table created successfully";
    }
    
    // Check if default admin exists
    $results[] = "\n👤 Checking default admin user:";
    $check_admin = $pdo->prepare("SELECT id, username FROM admins WHERE username = ?");
    $check_admin->execute(['admin']);
    $admin_exists = $check_admin->fetch();
    
    if ($admin_exists) {
        $results[] = "✅ Default admin user already exists (ID: {$admin_exists['id']})";
    } else {
        $results[] = "❌ Default admin user missing, creating now...";
        
        // Create default admin user
        // Password: admin123 (hashed with bcrypt)
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insert_admin = $pdo->prepare("
            INSERT INTO admins (username, password, email, full_name, security_question, security_answer) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $insert_admin->execute([
            'admin',
            $hashed_password,
            'admin@smartfix.com',
            'System Administrator',
            0, // Security question ID (0 = "What was the name of your first pet?")
            password_hash('Fluffy', PASSWORD_DEFAULT) // Hashed security answer
        ]);
        
        $admin_id = $pdo->lastInsertId();
        $results[] = "✅ Default admin user created successfully (ID: $admin_id)";
        $results[] = "   Username: admin";
        $results[] = "   Password: admin123";
        $results[] = "   Email: admin@smartfix.com";
    }
    
    // Verify admin table structure
    $results[] = "\n🔍 Verifying admin table structure:";
    $stmt = $pdo->query("DESCRIBE admins");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results[] = "✅ Admin table structure:";
    foreach ($columns as $column) {
        $results[] = "   - {$column['Field']} ({$column['Type']})";
    }
    
    // Test admin login functionality
    $results[] = "\n🧪 Testing admin authentication:";
    $test_login = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $test_login->execute(['admin']);
    $test_admin = $test_login->fetch();
    
    if ($test_admin && password_verify('admin123', $test_admin['password'])) {
        $results[] = "✅ Admin authentication test successful";
    } else {
        $results[] = "❌ Admin authentication test failed";
    }
    
    // Check for other essential tables
    $results[] = "\n📊 Checking other essential tables:";
    
    $essential_tables = [
        'users' => 'User accounts',
        'service_requests' => 'Service requests',
        'technicians' => 'Technician profiles',
        'notifications' => 'System notifications'
    ];
    
    foreach ($essential_tables as $table => $description) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $results[] = "✅ $table table exists ($description)";
        } catch (PDOException $e) {
            $results[] = "⚠️ $table table missing ($description)";
        }
    }
    
    $results[] = "\n🎯 Admin table fix complete!";
    $results[] = "You can now login to admin panel with:";
    $results[] = "Username: admin";
    $results[] = "Password: admin123";
    
} catch (PDOException $e) {
    $results[] = "❌ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Admin Table - SmartFix</title>
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
            max-height: 600px;
            overflow-y: auto;
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
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Admin Table Fix</h1>
        
        <div class="alert alert-info">
            <strong>Info:</strong> This script creates the missing admins table and default admin user to fix the authentication error.
        </div>
        
        <div class="results"><?php 
            foreach ($results as $result) {
                echo htmlspecialchars($result) . "\n";
            }
        ?></div>
        
        <div class="alert alert-success">
            <strong>Next Steps:</strong><br>
            1. Try logging in to the admin panel with username: <strong>admin</strong> and password: <strong>admin123</strong><br>
            2. Change the default password after first login<br>
            3. Create additional admin users as needed
        </div>
        
        <div class="buttons">
            <a href="auth.php?form=admin" class="btn btn-success">🔐 Try Admin Login</a>
            <a href="admin/admin_dashboard_new.php" class="btn">👨‍💼 Admin Dashboard</a>
            <a href="check_database_structure.php" class="btn btn-warning">🔍 Check Database</a>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>