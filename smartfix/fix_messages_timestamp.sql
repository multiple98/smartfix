-- Fix messages table timestamp column
-- Run this in phpMyAdmin or MySQL command line

-- Create the complete messages table if it doesn't exist
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    sender_type ENUM('user','admin','technician') DEFAULT 'user',
    receiver_type ENUM('user','admin','technician') DEFAULT 'admin',
    request_id INT,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add timestamp column if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'messages' 
                   AND COLUMN_NAME = 'timestamp');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE messages ADD COLUMN timestamp DATETIME DEFAULT CURRENT_TIMESTAMP', 
    'SELECT ''timestamp column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add other potentially missing columns
-- Add sender_id if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'messages' 
                   AND COLUMN_NAME = 'sender_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE messages ADD COLUMN sender_id INT', 
    'SELECT ''sender_id column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add request_id if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'messages' 
                   AND COLUMN_NAME = 'request_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE messages ADD COLUMN request_id INT', 
    'SELECT ''request_id column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add is_read if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'messages' 
                   AND COLUMN_NAME = 'is_read');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0', 
    'SELECT ''is_read column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records with timestamp if they have created_at but no timestamp
UPDATE messages SET timestamp = created_at WHERE timestamp IS NULL AND created_at IS NOT NULL;

-- Create replies table if it doesn't exist
CREATE TABLE IF NOT EXISTS replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    reply_message TEXT NOT NULL,
    sender_id INT,
    sender_type ENUM('user','admin','technician') DEFAULT 'admin',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Show final result
SELECT 'Messages table timestamp column fixed successfully!' AS status;
DESCRIBE messages;