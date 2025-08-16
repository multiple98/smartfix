USE smartfix;

-- Insert sample notifications
INSERT INTO notifications (user_id, message, type, is_read) VALUES
(NULL, 'A new user has registered on the platform.', 'service', 0),
(NULL, 'A customer has submitted a new service request for phone repair.', 'service', 0),
(NULL, 'Emergency repair request received from a customer.', 'emergency', 0),
(NULL, 'New message received from a customer regarding their order.', 'message', 0);