<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in and is a landlord or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['landlord', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get listing type
    $listing_type = $_POST['listing_type'] ?? 'rent';
    
    // Validate required fields based on listing type
    $required_fields = ['title', 'description', 'location', 'bedrooms', 'bathrooms'];
    
    if ($listing_type === 'rent') {
        $required_fields[] = 'price';
    } elseif ($listing_type === 'sale') {
        $required_fields[] = 'price_sale';
    } elseif ($listing_type === 'airbnb') {
        $required_fields[] = 'price_night';
    }
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '' || $_POST[$field] === null) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Handle photo uploads
    $image_urls = [null, null, null];
    $upload_dir = __DIR__ . '/../uploads/houses/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    for ($i = 1; $i <= 3; $i++) {
        $photo_key = 'photo_' . $i;
        if (isset($_FILES[$photo_key]) && $_FILES[$photo_key]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$photo_key];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $filename = 'house_' . time() . '_' . $i . '.' . $ext;
                $filepath = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $image_urls[$i-1] = 'uploads/houses/' . $filename;
                }
            }
        }
    }

    $price = null;
    $price_sale = null;
    $price_night = null;
    
    if ($listing_type === 'rent') {
        $price = $_POST['price'];
    } elseif ($listing_type === 'sale') {
        $price_sale = $_POST['price_sale'];
    } elseif ($listing_type === 'airbnb') {
        $price_night = $_POST['price_night'];
    }
    
    // Insert house
    $stmt = $pdo->prepare("
        INSERT INTO houses (
            title, description, price, security_deposit, available_from, location,
            area_id, category_id, property_type, bedrooms, bathrooms, size_sqft,
            parking_spaces, wifi, swimming_pool, gym, security_24_7, pet_friendly,
            dedicated_parking, image_url_1, image_url_2, image_url_3, verified, featured,
            landlord_id, listing_type, price_night, price_sale, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $price,
        $_POST['security_deposit'] ?? ($listing_type === 'rent' ? $_POST['price'] : null),
        $_POST['available_from'] ?? date('Y-m-d'),
        $_POST['location'],
        $_POST['area_id'] ?? 1,
        $_POST['category_id'] ?? 2,
        $_POST['property_type'] ?? 'apartment',
        $_POST['bedrooms'],
        $_POST['bathrooms'],
        $_POST['size_sqft'] ?? null,
        $_POST['parking_spaces'] ?? 0,
        $_POST['wifi'] ?? 0,
        $_POST['pool'] ?? 0,
        $_POST['gym'] ?? 0,
        $_POST['security'] ?? 0,
        $_POST['pets'] ?? 0,
        $_POST['parking'] ?? 0,
        $image_urls[0],
        $image_urls[1],
        $image_urls[2],
        1, // verified
        0, // featured
        $_SESSION['user_id'],
        $listing_type,
        $price_night,
        $price_sale
    ]);

    $house_id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Property listed successfully',
        'house_id' => $house_id
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>