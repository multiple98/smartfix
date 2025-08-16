<?php
// Complete Email Verification System Setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$messages = [];
$errors = [];

try {
    $messages[] = "🔧 Setting up complete email verification system...";
    
    // 1. Check and update users table structure
    $messages[] = "Checking users table structure...";
    
    // Get existing columns
    $result = $pdo->query("DESCRIBE users");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add email verification columns if they don't exist
    $verification_columns = [
        'is_verified' => "BOOLEAN DEFAULT FALSE",
        'verification_token' => "VARCHAR(255) DEFAULT NULL",
        'verification_sent_at' => "DATETIME DEFAULT NULL",
        'email_verified_at' => "DATETIME DEFAULT NULL"
    ];
    
    foreach ($verification_columns as $column => $definition) {
        if (!in_array($column, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
                $messages[] = "✅ Added column '$column' to users table";
            } catch (PDOException $e) {
                $errors[] = "❌ Could not add column '$column': " . $e->getMessage();
            }
        } else {
            $messages[] = "✅ Column '$column' already exists";
        }
    }
    
    // 2. Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_verification_token ON users(verification_token)",
        "CREATE INDEX IF NOT EXISTS idx_users_is_verified ON users(is_verified)",
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (PDOException $e) {
            // Ignore if index already exists
        }
    }
    $messages[] = "✅ Database indexes optimized";
    
    // 3. Create email verification logs table
    $messages[] = "Creating email_verification_logs table...";
    $create_logs_table = "
        CREATE TABLE IF NOT EXISTS email_verification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            verification_token VARCHAR(255) NOT NULL,
            action ENUM('sent', 'verified', 'resent', 'expired') NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_token (verification_token),
            INDEX idx_action (action),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_logs_table);
    $messages[] = "✅ email_verification_logs table created/verified";
    
    // 4. Update existing users to be verified (for backward compatibility)
    $messages[] = "Updating existing users...";
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, email_verified_at = NOW() WHERE is_verified IS NULL OR is_verified = 0");
    $updated = $stmt->execute();
    $count = $stmt->rowCount();
    $messages[] = "✅ Updated $count existing users to verified status";
    
    $messages[] = "🎉 Email verification system setup completed successfully!";
    
} catch (PDOException $e) {
    $errors[] = "❌ Database Error: " . $e->getMessage();
} catch (Exception $e) {
    $errors[] = "❌ General Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification System Setup - SmartFix</title>
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
            max-width: 800px;
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
        
        .message-list {
            margin: 30px 0;
            padding: 0;
            list-style: none;
        }
        
        .message-list li {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-left: 4px solid #007BFF;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        
        .warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        
        .error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .celebration {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            font-weight: bold;
        }
        
        .buttons {
            margin-top: 40px;
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
        <h1>📧 Email Verification System Setup</h1>
        
        <ul class="message-list">
            <?php foreach ($messages as $message): ?>
                <li class="<?php 
                    if (strpos($message, '✅') !== false) echo 'success';
                    elseif (strpos($message, '⚠️') !== false) echo 'warning';
                    elseif (strpos($message, '🎉') !== false) echo 'celebration';
                    else echo '';
                ?>"><?php echo $message; ?></li>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $error): ?>
                <li class="error"><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <div class="buttons">
            <a href="create_email_verification_components.php" class="btn btn-success">📝 Create Email Components</a>
            <a href="register.php" class="btn">🔐 Test Registration</a>
            <a href="index.php" class="btn">🏠 Home Page</a>
        </div>
    </div>
</body>
</html>