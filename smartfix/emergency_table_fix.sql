-- Emergency Table Fix SQL Script
-- Run this directly in phpMyAdmin or MySQL command line

USE smartfix;

-- Create the emergencies table
CREATE TABLE IF NOT EXISTS emergencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    location TEXT NOT NULL,
    status ENUM('Pending', 'Responding', 'Resolved') DEFAULT 'Pending',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'High',
    phone VARCHAR(20),
    email VARCHAR(100),
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample emergency data
INSERT INTO emergencies (message, location, status, phone, email, name) VALUES
('Power outage in entire building, elevator stuck with people inside', '123 Main Street, Business District, Lusaka', 'Pending', '+260 777 123 456', 'john.doe@email.com', 'John Doe'),
('Water pipe burst flooding basement server room', '456 Industrial Avenue, Kabulonga, Lusaka', 'Responding', '+260 776 987 654', 'jane.smith@company.com', 'Jane Smith'),
('Gas leak detected in kitchen area - EVACUATED', '789 Restaurant Row, City Center, Lusaka', 'Resolved', '+260 775 555 123', 'restaurant@example.com', 'Restaurant Manager');

-- Verify the table was created
SELECT 'Table created successfully' as status, COUNT(*) as total_records FROM emergencies;