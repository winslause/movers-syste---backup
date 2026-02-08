-- Database update to support for sale properties
-- Run this SQL to fix the database schema

USE movers_system;

-- Make price column nullable (it's only used for rent, not for sale/airbnb)
ALTER TABLE houses MODIFY COLUMN price DECIMAL(10,2);

-- Add price_sale column if it doesn't exist (for properties for sale)
ALTER TABLE houses ADD COLUMN IF NOT EXISTS price_sale DECIMAL(12,2);

-- Add price_night column if it doesn't exist (for airbnb)
ALTER TABLE houses ADD COLUMN IF NOT EXISTS price_night DECIMAL(10,2);

-- Add listing_type column if it doesn't exist
ALTER TABLE houses ADD COLUMN IF NOT EXISTS listing_type ENUM('rent', 'airbnb', 'sale') DEFAULT 'rent';

-- Update existing houses to have listing_type = 'rent' where null
UPDATE houses SET listing_type = 'rent' WHERE listing_type IS NULL;

-- Set price_sale for existing sale properties (update based on your data)
-- For example: UPDATE houses SET listing_type = 'sale', price_sale = price WHERE price > 100000;
