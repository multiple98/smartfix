-- Complete Orders Table Fix
-- Run this in phpMyAdmin or MySQL command line

-- Create the complete orders table structure if it doesn't exist
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    tracking_number VARCHAR(20) UNIQUE,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_email VARCHAR(100),
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
    shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash_on_delivery',
    transport_id INT,
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add missing columns if table already exists
-- Add shipping_province column if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'shipping_province');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(50) NOT NULL DEFAULT ''Lusaka'' AFTER shipping_city', 
    'SELECT ''shipping_province column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add total_amount column if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'total_amount');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER notes', 
    'SELECT ''total_amount column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add other potentially missing columns
-- Add user_id if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'user_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN user_id INT AFTER id', 
    'SELECT ''user_id column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add tracking_number if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'tracking_number');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(20) UNIQUE AFTER user_id', 
    'SELECT ''tracking_number column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add transport_id if missing
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'orders' 
                   AND COLUMN_NAME = 'transport_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN transport_id INT AFTER payment_method', 
    'SELECT ''transport_id column already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create supporting tables
-- Create order_items table if missing
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create order_tracking table if missing
CREATE TABLE IF NOT EXISTS order_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Show final result
SELECT 'Orders table structure fixed successfully!' AS status;
DESCRIBE orders;