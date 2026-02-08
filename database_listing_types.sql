-- Add listing_type columns for rent, airbnb, and sale support
USE movers_system;

-- Add listing_type column (rent, airbnb, sale)
ALTER TABLE houses ADD COLUMN listing_type ENUM('rent', 'airbnb', 'sale') DEFAULT 'rent';

-- Add price per night for airbnb
ALTER TABLE houses ADD COLUMN price_night DECIMAL(10,2);

-- Add selling price for properties for sale
ALTER TABLE houses ADD COLUMN price_sale DECIMAL(12,2);

-- Update existing houses to have listing_type = 'rent'
UPDATE houses SET listing_type = 'rent' WHERE listing_type IS NULL;
