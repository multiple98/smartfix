-- Create admins table for SmartFix
USE smartfix;

-- Drop table if exists to ensure clean creation
DROP TABLE IF EXISTS admins;

-- Create admins table
CREATE TABLE admins (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    role ENUM('super_admin','admin','moderator') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password: admin123 (will be hashed in PHP)
INSERT INTO admins (username, email, password, full_name, role) 
VALUES ('admin', 'admin@smartfix.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Note: The password hash above is for 'password' - you should change this
-- To generate a proper hash for 'admin123', use: password_hash('admin123', PASSWORD_DEFAULT) in PHP

SELECT 'Admin table created successfully!' as message;
SELECT * FROM admins;