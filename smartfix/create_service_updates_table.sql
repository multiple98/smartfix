USE smartfix;

-- Create service_updates table to track detailed service progress
CREATE TABLE IF NOT EXISTS service_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_request_id INT NOT NULL,
    update_text TEXT NOT NULL,
    status VARCHAR(20),
    created_by INT,
    created_by_type ENUM('admin', 'technician') DEFAULT 'technician',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_service_request_id ON service_updates(service_request_id);