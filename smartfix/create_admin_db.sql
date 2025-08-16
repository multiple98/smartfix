-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS smartfix;

-- Use the smartfix database
USE smartfix;

-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user with hashed password (password is 'admin123')
-- The password is hashed and would be equivalent to using password_hash('admin123', PASSWORD_DEFAULT) in PHP
INSERT INTO admins (username, password, email) 
VALUES ('admin', '$2y$10$8MJvKgPRiBRFQDEJLHigZeQyWZs458UBvUBnUdCKEGQ.jYJO3nM6K', 'admin@smartfix.com')
ON DUPLICATE KEY UPDATE password = VALUES(password), email = VALUES(email);