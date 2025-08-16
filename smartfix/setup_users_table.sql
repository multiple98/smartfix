USE smartfix;

-- Drop and recreate the table to ensure all columns are present
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    user_type ENUM('user', 'admin') DEFAULT 'user',
    profile_image VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert a default user
INSERT INTO users (name, email, password, phone, user_type)
VALUES ('Test User', 'user@smartfix.com', '$2y$10$8KQT.Yw/Y6jKgFH3VpvhAOi9Ow/XCQRJFRQEPvTF.1vBgBTaQFSMi', '+260123456789', 'user');

-- Note: The default password is 'admin123' (hashed with bcrypt)