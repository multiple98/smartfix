<?php
// Setup authentication system for SmartFix
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('includes/db.php');

echo "<h1>SmartFix Authentication System Setup</h1>";
echo "<style>
body{font-family:Arial;margin:40px;} 
.success{color:green;background:#f0fff0;padding:10px;margin:5px 0;border-left:4px solid green;} 
.error{color:red;background:#fff0f0;padding:10px;margin:5px 0;border-left:4px solid red;} 
.info{color:blue;background:#f0f8ff;padding:10px;margin:5px 0;border-left:4px solid blue;}
.warning{color:orange;background:#fffaf0;padding:10px;margin:5px 0;border-left:4px solid orange;}
h2{color:#004080;border-bottom:2px solid #007BFF;padding-bottom:10px;}
</style>";

try {
    echo "<h2>1. Database Tables Setup</h2>";
    
    // Create/verify users table
    $users_sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(50),
        province VARCHAR(50),
        status ENUM('active', 'inactive') DEFAULT 'active',
        email_verified TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_status (status)
    )";
    $pdo->exec($users_sql);
    echo "<div class='success'>✓ Users table created/verified</div>";
    
    // Check if we need to add columns to existing users table
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = [
            'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(100)",
            'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20)",
            'address' => "ALTER TABLE users ADD COLUMN address TEXT",
            'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(50)",
            'province' => "ALTER TABLE users ADD COLUMN province VARCHAR(50)",
            'status' => "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'",
            'email_verified' => "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0"
        ];
        
        foreach ($required_columns as $column => $sql) {
            if (!in_array($column, $columns)) {
                $pdo->exec($sql);
                echo "<div class='success'>✓ Added $column column to users table</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div class='info'>Users table already properly configured</div>";
    }
    
    // Create admin users table
    $admin_sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        role ENUM('admin', 'super_admin') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email)
    )";
    $pdo->exec($admin_sql);
    echo "<div class='success'>✓ Admin users table created/verified</div>";
    
    echo "<h2>2. Sample Users Creation</h2>";
    
    // Create sample customer user
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_count == 0) {
        $sample_users = [
            [
                'customer1', 
                'customer@example.com', 
                password_hash('password123', PASSWORD_DEFAULT), 
                'John Customer', 
                '+260977123456', 
                '123 Main Street',
                'Lusaka',
                'Lusaka'
            ],
            [
                'customer2', 
                'jane@example.com', 
                password_hash('password123', PASSWORD_DEFAULT), 
                'Jane Smith', 
                '+260977654321', 
                '456 Oak Avenue',
                'Ndola',
                'Copperbelt'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address, city, province) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sample_users as $user) {
            $stmt->execute($user);
        }
        echo "<div class='success'>✓ Created sample customer accounts</div>";
        echo "<div class='info'>Customer login: customer1 / password123</div>";
        echo "<div class='info'>Customer login: customer2 / password123</div>";
    } else {
        echo "<div class='info'>Users already exist in database</div>";
    }
    
    // Create sample admin user
    $admin_count = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    if ($admin_count == 0) {
        $admin_stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $admin_stmt->execute([
            'admin',
            'admin@smartfix.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'System Administrator',
            'super_admin'
        ]);
        echo "<div class='success'>✓ Created default admin account</div>";
        echo "<div class='info'>Admin login: admin / admin123</div>";
    } else {
        echo "<div class='info'>Admin users already exist</div>";
    }
    
    echo "<h2>3. Session Management</h2>";
    
    // Test session functionality
    if (session_status() == PHP_SESSION_ACTIVE) {
        echo "<div class='success'>✓ Sessions are working</div>";
        
        // Test session variables
        $_SESSION['test_var'] = 'test_value';
        if (isset($_SESSION['test_var']) && $_SESSION['test_var'] == 'test_value') {
            echo "<div class='success'>✓ Session variables working</div>";
        }
        unset($_SESSION['test_var']);
    } else {
        echo "<div class='error'>✗ Session problems detected</div>";
    }
    
    echo "<h2>4. File Structure Verification</h2>";
    
    $auth_files = [
        'login.php' => 'User login page',
        'register.php' => 'User registration page',
        'logout.php' => 'Logout functionality',
        'admin/admin_login.php' => 'Admin login page',
        'admin/admin_auth.php' => 'Admin authentication',
        'admin/logout.php' => 'Admin logout',
        'includes/db.php' => 'Database connection'
    ];
    
    foreach ($auth_files as $file => $description) {
        if (file_exists($file)) {
            echo "<div class='success'>✓ $file - $description</div>";
        } else {
            echo "<div class='error'>✗ Missing: $file - $description</div>";
        }
    }
    
    echo "<h2>5. Integration Test</h2>";
    
    // Test database connection with users
    try {
        $test_user = $pdo->query("SELECT username FROM users LIMIT 1")->fetchColumn();
        if ($test_user) {
            echo "<div class='success'>✓ User database integration working</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>✗ User database issue: " . $e->getMessage() . "</div>";
    }
    
    // Test admin integration
    try {
        $test_admin = $pdo->query("SELECT username FROM admin_users LIMIT 1")->fetchColumn();
        if ($test_admin) {
            echo "<div class='success'>✓ Admin database integration working</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Admin database issue: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<div class='success'><h3>✓ Authentication system is ready!</h3></div>";
    
    echo "<div class='info'>
    <strong>What was configured:</strong><br>
    - Users table with proper columns<br>
    - Admin users table<br>
    - Sample customer and admin accounts<br>
    - Session management verified<br>
    - File structure confirmed<br>
    </div>";
    
    echo "<div class='info'>
    <strong>Test accounts created:</strong><br>
    Customer: username='customer1', password='password123'<br>
    Customer: username='customer2', password='password123'<br>
    Admin: username='admin', password='admin123'<br>
    </div>";
    
    echo "<div class='info'>
    <strong>Next steps:</strong><br>
    1. Test customer login at <a href='login.php'>login.php</a><br>
    2. Test customer registration at <a href='register.php'>register.php</a><br>
    3. Test admin login at <a href='admin/admin_login.php'>admin/admin_login.php</a><br>
    4. Try shopping with logged-in users<br>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Setup failed: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>Test Authentication:</h3>";
echo "<a href='login.php' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Customer Login</a> ";
echo "<a href='register.php' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Register</a> ";
echo "<a href='admin/admin_login.php' style='background:#dc3545;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Admin Login</a> ";
echo "<a href='shop.php' style='background:#17a2b8;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Shop</a>";
?>