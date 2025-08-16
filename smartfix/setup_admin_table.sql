USE smartfix;

-- Drop and recreate the table to ensure all columns are present
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    security_question INT(11) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin
INSERT INTO admins (username, password, email, full_name, security_question, security_answer)
VALUES ('admin', '$2y$10$8KQT.Yw/Y6jKgFH3VpvhAOi9Ow/XCQRJFRQEPvTF.1vBgBTaQFSMi', 'admin@smartfix.com', 'System Administrator', 0, 'Fluffy');

-- Note: The default password is 'admin123' (hashed with bcrypt)
-- Security question 0 is "What was the name of your first pet?"
-- Security answer is "Fluffy"