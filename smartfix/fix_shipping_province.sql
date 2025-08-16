-- Fix for shipping_province column issue
-- Run this in your MySQL/phpMyAdmin

-- First, check if the orders table exists and add shipping_province column if missing
ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_province VARCHAR(50) NOT NULL DEFAULT 'Lusaka' AFTER shipping_city;

-- If the above doesn't work (older MySQL versions), use this instead:
-- First check if column exists, if not add it
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'orders' 
AND COLUMN_NAME = 'shipping_province';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(50) NOT NULL DEFAULT ''Lusaka'' AFTER shipping_city', 
    'SELECT ''Column shipping_province already exists'' AS Status');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create orders table if it doesn't exist at all
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
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'processing',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create order_items table if missing
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
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

-- Show final structure
DESCRIBE orders;