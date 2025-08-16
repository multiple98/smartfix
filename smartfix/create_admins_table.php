<?php
// Create admins table if it doesn't exist
include('includes/db.php');

try {
    // Check if admins table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
    
    if (mysqli_num_rows($table_check) == 0) {
        // Create admins table
        $create_table = "CREATE TABLE `admins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `full_name` varchar(100) DEFAULT NULL,
            `security_question` int(11) NOT NULL,
            `security_answer` varchar(255) NOT NULL,
            `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
            `is_active` tinyint(1) DEFAULT 1,
            `last_login` timestamp NULL DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (mysqli_query($conn, $create_table)) {
            echo "✅ Admins table created successfully!<br>";
        } else {
            echo "❌ Error creating admins table: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "ℹ️ Admins table already exists.<br>";
    }
    
    // Check if there are any admin users
    $admin_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
    $count_result = mysqli_fetch_assoc($admin_count);
    
    if ($count_result['count'] == 0) {
        echo "ℹ️ No admin users found. You can now register an admin user.<br>";
    } else {
        echo "ℹ️ Found " . $count_result['count'] . " admin user(s) in the database.<br>";
    }
    
    echo "<br><a href='admin/admin_register.php'>Go to Admin Registration</a><br>";
    echo "<a href='admin/admin_login.php'>Go to Admin Login</a><br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>