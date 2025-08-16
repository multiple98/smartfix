USE smartfix;

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample notifications
INSERT INTO notifications (admin_id, title, message) VALUES
(1, 'New User Registration', 'A new user has registered on the platform.'),
(1, 'New Service Request', 'A customer has submitted a new service request for phone repair.'),
(1, 'System Update', 'The system has been updated to the latest version.');