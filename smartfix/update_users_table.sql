USE smartfix;

-- Add columns if they don't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Insert a default user if the table is empty (will be ignored if email already exists)
INSERT IGNORE INTO users (name, email, password, phone, user_type)
VALUES ('Test User', 'user@smartfix.com', '$2y$10$8KQT.Yw/Y6jKgFH3VpvhAOi9Ow/XCQRJFRQEPvTF.1vBgBTaQFSMi', '+260123456789', 'user');

-- Note: The default password is 'admin123' (hashed with bcrypt)