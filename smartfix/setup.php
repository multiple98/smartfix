<?php
// SmartFix Database Setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';

// Database connection parameters
$host = "localhost";
$dbname = "smartfix";
$username = "root";
$password = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        // First, connect without database to create it if needed
        $pdo_create = new PDO("mysql:host=$host", $username, $password);
        $pdo_create->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo_create->exec("CREATE DATABASE IF NOT EXISTS $dbname");
        $message .= "✅ Database '$dbname' created/verified<br>";
        
        // Now connect to the specific database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create admins table
        $create_admins = "
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `full_name` varchar(100) DEFAULT NULL,
            `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
            `is_active` tinyint(1) DEFAULT 1,
            `last_login` timestamp NULL DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_admins);
        $message .= "✅ Admins table created<br>";
        
        // Create users table
        $create_users = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `user_type` enum('user','technician') DEFAULT 'user',
            `is_verified` tinyint(1) DEFAULT 0,
            `verification_token` varchar(255) DEFAULT NULL,
            `reset_token` varchar(255) DEFAULT NULL,
            `reset_expires` timestamp NULL DEFAULT NULL,
            `profile_image` varchar(255) DEFAULT NULL,
            `status` enum('active','inactive','suspended') DEFAULT 'active',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_users);
        $message .= "✅ Users table created<br>";
        
        // Create service_requests table
        $create_service_requests = "
        CREATE TABLE IF NOT EXISTS `service_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `service_type` varchar(100) NOT NULL,
            `description` text NOT NULL,
            `location` varchar(255) DEFAULT NULL,
            `latitude` decimal(10,8) DEFAULT NULL,
            `longitude` decimal(11,8) DEFAULT NULL,
            `status` enum('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
            `priority` enum('low','medium','high','emergency') DEFAULT 'medium',
            `estimated_cost` decimal(10,2) DEFAULT NULL,
            `actual_cost` decimal(10,2) DEFAULT NULL,
            `scheduled_date` datetime DEFAULT NULL,
            `completed_date` datetime DEFAULT NULL,
            `rating` int(1) DEFAULT NULL,
            `review` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_service_requests);
        $message .= "✅ Service requests table created<br>";
        
        // Create notifications table
        $create_notifications = "
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `admin_id` int(11) DEFAULT NULL,
            `type` enum('info','success','warning','error','service_update','payment','system') DEFAULT 'info',
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `action_url` varchar(255) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($create_notifications);
        $message .= "✅ Notifications table created<br>";
        
        // Check if admin user exists
        $check_admin = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
        $check_admin->execute();
        
        if ($check_admin->fetchColumn() == 0) {
            // Create default admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin = $pdo->prepare("
                INSERT INTO admins (username, email, password, full_name, role) 
                VALUES ('admin', 'admin@smartfix.com', ?, 'System Administrator', 'super_admin')
            ");
            $insert_admin->execute([$admin_password]);
            $message .= "✅ Default admin user created<br>";
            $message .= "<strong>Admin Login:</strong> username: admin, password: admin123<br>";
        } else {
            $message .= "ℹ️ Admin user already exists<br>";
        }
        
        $message .= "<br><strong>🎉 Setup completed successfully!</strong>";
        
    } catch (PDOException $e) {
        $error = "❌ Setup failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFix Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 50px;
            color: #007BFF;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            color: #333;
            margin: 0;
            font-size: 28px;
        }
        
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
        }
        
        .setup-form {
            text-align: center;
        }
        
        .setup-btn {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px 0;
        }
        
        .setup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        .links {
            text-align: center;
            margin-top: 30px;
        }
        
        .links a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .links a:hover {
            background: #218838;
        }
        
        .links a.admin {
            background: #dc3545;
        }
        
        .links a.admin:hover {
            background: #c82333;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007BFF;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: #007BFF;
        }
        
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <i class="fas fa-tools"></i>
            <h1>SmartFix Setup</h1>
            <p>Database Configuration</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$message && !$error): ?>
            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> Setup Information</h3>
                <p>This setup will create the necessary database tables and default admin user for SmartFix.</p>
                <ul>
                    <li>Creates the 'smartfix' database if it doesn't exist</li>
                    <li>Creates essential tables (admins, users, service_requests, notifications)</li>
                    <li>Creates a default admin user with credentials: admin/admin123</li>
                </ul>
                <p><strong>Note:</strong> Make sure XAMPP/MySQL is running before proceeding.</p>
            </div>
            
            <form method="POST" class="setup-form">
                <button type="submit" name="setup" class="setup-btn">
                    <i class="fas fa-play"></i> Run Database Setup
                </button>
            </form>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="links">
                <a href="auth.php?form=admin" class="admin">
                    <i class="fas fa-user-shield"></i> Admin Login
                </a>
                <a href="auth.php">
                    <i class="fas fa-sign-in-alt"></i> User Login
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        <?php endif; ?>
        
        <div class="links">
            <a href="check_database_structure.php">
                <i class="fas fa-database"></i> Check Database
            </a>
        </div>
    </div>
</body>
</html>