<?php
require_once 'db.php';

try {
    // Add listing_type column
    $pdo->exec("ALTER TABLE houses ADD COLUMN listing_type ENUM('rent', 'airbnb', 'sale') DEFAULT 'rent'");
    echo "Added listing_type column successfully\n";
    
    // Add price_night column
    $pdo->exec("ALTER TABLE houses ADD COLUMN price_night DECIMAL(10,2)");
    echo "Added price_night column successfully\n";
    
    // Add price_sale column
    $pdo->exec("ALTER TABLE houses ADD COLUMN price_sale DECIMAL(12,2)");
    echo "Added price_sale column successfully\n";
    
    // Update existing houses
    $pdo->exec("UPDATE houses SET listing_type = 'rent' WHERE listing_type IS NULL");
    echo "Updated existing houses to rent listing type\n";
    
    // Add some sample "For Sale" houses for testing
    $stmt = $pdo->prepare("
        INSERT INTO houses (
            title, description, price, price_sale, available_from, location, area_id, category_id,
            property_type, bedrooms, bathrooms, size_sqft, parking_spaces,
            wifi, swimming_pool, gym, security_24_7, pet_friendly, dedicated_parking,
            image_url_1, image_url_2, image_url_3, verified, featured, listing_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sale')
    ");
    
    $saleHouses = [
        [
            'Stunning 4-Bedroom Villa in Karen', 
            'Luxurious villa with swimming pool, garden, and modern finishes. Perfect for families looking for premium living.',
            350000, 45000000, '2024-03-01', 'Karen, Nairobi', 7, 2, 'house', 4, 4, 3500, 2, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE,
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1153&q=80',
            'https://images.unsplash.com/photo-1600607687644-c7171b42498f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80', TRUE, TRUE
        ],
        [
            'Modern 3-Bedroom Apartment in Kilimani', 
            'Contemporary apartment with city views, gym access, and 24/7 security. Ideal for professionals.',
            120000, 18500000, '2024-02-15', 'Kilimani, Nairobi', 2, 2, 'apartment', 3, 2, 1500, 1, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE,
            'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE, FALSE
        ],
        [
            'Spacious 5-Bedroom Family Home in Lavington', 
            'Large family home with mature garden, swimming pool, and excellent security. Close to top schools.',
            250000, 65000000, '2024-03-15', 'Lavington, Nairobi', 6, 4, 'house', 5, 5, 4200, 3, TRUE, TRUE, FALSE, TRUE, TRUE, TRUE,
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80', TRUE, TRUE
        ],
        [
            'Elegant 2-Bedroom Condo in Westlands', 
            'Modern condo in prime location with rooftop pool, gym, and stunning city views.',
            180000, 28000000, '2024-02-20', 'Westlands, Nairobi', 1, 2, 'apartment', 2, 2, 1200, 1, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE,
            'https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1073&q=80',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80',
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80', TRUE, FALSE
        ],
        [
            'Charming 3-Bedroom Townhouse in Kileleshwa', 
            'Beautiful townhouse with private garden, modern kitchen, and spacious living areas.',
            200000, 38000000, '2024-03-01', 'Kileleshwa, Nairobi', 3, 4, 'house', 3, 3, 2000, 2, TRUE, FALSE, FALSE, TRUE, TRUE, TRUE,
            'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?ixlib=rb-4.0.3&auto=format&fit=crop&w=1078&q=80',
            'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80', TRUE, TRUE
        ],
        [
            'Luxury 6-Bedroom Estate in Langata', 
            'Grand estate with private compound, guest house, and premium finishes. Perfect for large families.',
