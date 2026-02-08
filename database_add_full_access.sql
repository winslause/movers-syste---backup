-- Add full_access column to users table
ALTER TABLE users ADD COLUMN full_access BOOLEAN DEFAULT FALSE AFTER email_verified;

-- Create index for faster queries
CREATE INDEX idx_full_access ON users(full_access);
