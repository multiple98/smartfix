USE smartfix;

-- Create service_requests table with complete structure
-- This matches the structure expected by the PHP code

DROP TABLE IF EXISTS service_requests;

CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    service_option VARCHAR(100),
    description TEXT NOT NULL,
    address TEXT,
    preferred_date DATE,
    preferred_time VARCHAR(20),
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    technician_id INT,
    user_id INT,
    notes TEXT,
    completed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better performance
    INDEX idx_status (status),
    INDEX idx_service_type (service_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_number (reference_number),
    INDEX idx_technician_id (technician_id),
    INDEX idx_user_id (user_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample data for testing
INSERT INTO service_requests (
    reference_number, name, email, phone, service_type, service_option, 
    description, address, priority, status, created_at
) VALUES 
(
    'SF000001', 
    'John Doe', 
    'john.doe@example.com', 
    '+250123456789', 
    'phone', 
    'Screen Replacement',
    'Phone screen is cracked and needs replacement. iPhone 12 Pro Max.',
    '123 Main Street, Kigali',
    'normal',
    'pending',
    NOW()
),
(
    'SF000002', 
    'Jane Smith', 
    'jane.smith@example.com', 
    '+250987654321', 
    'computer', 
    'Virus Removal',
    'Computer is running very slow and showing popup ads. Needs virus removal.',
    '456 Tech Avenue, Kigali',
    'high',
    'pending',
    NOW()
),
(
    'SF000003', 
    'Bob Wilson', 
    'bob.wilson@example.com', 
    '+250555123456', 
    'car', 
    'Engine Diagnostics',
    'Car engine making strange noises and check engine light is on.',
    '789 Auto Street, Kigali',
    'urgent',
    'assigned',
    DATE_SUB(NOW(), INTERVAL 2 HOUR)
);