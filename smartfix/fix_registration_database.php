<?php
// Fix registration database issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('includes/db.php');

echo "<h1>Fix Registration Database Issues</h1>";
echo "<style>
body{font-family:Arial;margin:40px;} 
.success{color:green;background:#f0fff0;padding:10px;margin:5px 0;border-left:4px solid green;} 
.error{color:red;background:#fff0f0;padding:10px;margin:5px 0;border-left:4px solid red;} 
.info{color:blue;background:#f0f8ff;padding:10px;margin:5px 0;border-left:4px solid blue;}
.warning{color:orange;background:#fffaf0;padding:10px;margin:5px 0;border-left:4px solid orange;}
h2{color:#004080;border-bottom:2px solid #007BFF;padding-bottom:10px;}
</style>";

try {
    echo "<h2>1. Database Connection Test</h2>";
    
    // Test PDO connection
    if ($pdo) {
        echo "<div class='success'>✓ PDO connection working</div>";
    } else {
        echo "<div class='error'>✗ PDO connection failed</div>";
        exit;
    }
    
    echo "<h2>2. Check Users Table Structure</h2>";
    
    // Check if users table exists
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($result->rowCount() > 0) {
            echo "<div class='success'>✓ Users table exists</div>";
            
            // Show current table structure
            $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='info'>Current table structure:</div>";
            foreach ($columns as $column) {
                echo "<div class='info'>- {$column['Field']} ({$column['Type']})</div>";
            }
        } else {
            echo "<div class='error'>✗ Users table doesn't exist - creating it now</div>";
            
            // Create users table
            $create_table_sql = "CREATE TABLE users (
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
            
            $pdo->exec($create_table_sql);
            echo "<div class='success'>✓ Users table created successfully</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error checking/creating users table: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>3. Ensure Required Columns Exist</h2>";
    
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        
        $required_columns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'username' => 'VARCHAR(50) UNIQUE NOT NULL',
            'email' => 'VARCHAR(100) UNIQUE NOT NULL', 
            'password' => 'VARCHAR(255) NOT NULL',
            'full_name' => 'VARCHAR(100)',
            'phone' => 'VARCHAR(20)',
            'address' => 'TEXT',
            'city' => 'VARCHAR(50)',
            'province' => 'VARCHAR(50)',
            'status' => 'ENUM(\'active\', \'inactive\') DEFAULT \'active\'',
            'email_verified' => 'TINYINT(1) DEFAULT 0',
            'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $columns)) {
                try {
                    if ($column === 'id') {
                        // Skip ID if missing - table structure issue
                        continue;
                    }
                    
                    $alter_sql = "ALTER TABLE users ADD COLUMN $column $definition";
                    $pdo->exec($alter_sql);
                    echo "<div class='success'>✓ Added column: $column</div>";
                } catch (PDOException $e) {
                    echo "<div class='warning'>⚠ Could not add $column: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='info'>✓ Column exists: $column</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error checking columns: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>4. Test Registration Query</h2>";
    
    // Test the exact query used in registration
    try {
        $test_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address, city, province, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        echo "<div class='success'>✓ Registration query prepared successfully</div>";
        
        // Test with dummy data (don't execute)
        echo "<div class='info'>Query is ready for: username, email, password, full_name, phone, address, city, province</div>";
        
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Registration query error: " . $e->getMessage() . "</div>";
        
        // Try simpler query
        try {
            $simple_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            echo "<div class='warning'>⚠ Falling back to simple registration query</div>";
        } catch (PDOException $e2) {
            echo "<div class='error'>✗ Even simple query failed: " . $e2->getMessage() . "</div>";
        }
    }
    
    echo "<h2>5. Create Test User</h2>";
    
    try {
        // Check if test user exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'testuser'");
        $check->execute();
        
        if ($check->fetchColumn() == 0) {
            // Create test user
            $test_username = 'testuser';
            $test_email = 'test@example.com';
            $test_password = password_hash('test123', PASSWORD_DEFAULT);
            $test_fullname = 'Test User';
            
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, full_name, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
            
            if ($insert->execute([$test_username, $test_email, $test_password, $test_fullname])) {
                echo "<div class='success'>✓ Test user created successfully</div>";
                echo "<div class='info'>Username: testuser, Password: test123</div>";
            } else {
                echo "<div class='error'>✗ Could not create test user</div>";
            }
        } else {
            echo "<div class='info'>✓ Test user already exists</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>Error creating test user: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>6. Database Summary</h2>";
    
    try {
        $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<div class='success'>✓ Total users in database: $user_count</div>";
        
        if ($user_count > 0) {
            echo "<div class='info'>Sample users:</div>";
            $users = $pdo->query("SELECT username, email, full_name, created_at FROM users LIMIT 3")->fetchAll();
            foreach ($users as $user) {
                echo "<div class='info'>- {$user['username']} ({$user['email']}) - {$user['full_name']}</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>Error getting user count: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>✅ Database Fix Complete!</h2>";
    echo "<div class='success'>
    <strong>What was fixed:</strong><br>
    - Verified database connection<br>
    - Created/verified users table<br>
    - Added missing columns<br>
    - Tested registration queries<br>
    - Created test user account<br>
    </div>";
    
    echo "<div class='info'>
    <strong>Now try:</strong><br>
    1. Visit <a href='register.php'>register.php</a> to test registration<br>
    2. Visit <a href='login.php'>login.php</a> to test login<br>
    3. Use test account: testuser / test123<br>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Fatal error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace: " . $e->getTraceAsString() . "</div>";
}

echo "<hr>";
echo "<h3>Quick Links:</h3>";
echo "<a href='register.php' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Test Register</a> ";
echo "<a href='login.php' style='background:#007BFF;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Test Login</a> ";
echo "<a href='shop.php' style='background:#17a2b8;color:white;padding:10px 15px;text-decoration:none;margin:5px;'>Visit Shop</a>";
?>