USE smartfix;

-- Add status column if it doesn't exist
ALTER TABLE service_requests ADD COLUMN IF NOT EXISTS status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending';