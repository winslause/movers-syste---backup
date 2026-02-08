<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../db.php';

$price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 200000000; // Increased max for sale
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$landlord_id = isset($_GET['landlord_id']) ? (int)$_GET['landlord_id'] : null;
$listing_type = isset($_GET['listing_type']) ? $_GET['listing_type'] : ''; // rent, airbnb, sale

try {
    // Check if listing_type column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM houses LIKE 'listing_type'");
    $has_listing_type = $stmt->fetch() !== false;
    
    // Build base query - include COALESCE to handle null prices
    $query = "SELECT h.*, a.name as area_name FROM houses h LEFT JOIN areas a ON h.area_id = a.id WHERE 1=1";
    $params = [];

    // Apply listing_type filter only if column exists
    if ($has_listing_type && $listing_type && in_array($listing_type, ['rent', 'airbnb', 'sale'])) {
        $query .= " AND h.listing_type = ?";
        $params[] = $listing_type;
    }

    // Apply price filter based on listing type
    if ($listing_type === 'airbnb') {
        $query .= " AND COALESCE(h.price_night, h.price, 0) >= ? AND COALESCE(h.price_night, h.price, 0) <= ?";
        $params = array_merge($params, [$price_min, $price_max]);
    } elseif ($listing_type === 'sale') {
        $query .= " AND COALESCE(h.price_sale, h.price, 0) >= ? AND COALESCE(h.price_sale, h.price, 0) <= ?";
        $params = array_merge($params, [$price_min, $price_max]);
    } else {
        // Default rent filter
        $query .= " AND COALESCE(h.price, 0) >= ? AND COALESCE(h.price, 0) <= ?";
        $params = array_merge($params, [$price_min, $price_max]);
    }

    if ($property_type) {
        $query .= " AND h.property_type = ?";
        $params[] = $property_type;
    }

    if ($bedrooms && $bedrooms !== '4+') {
        $query .= " AND h.bedrooms = ?";
        $params[] = (int)$bedrooms;
    } elseif ($bedrooms === '4+') {
        $query .= " AND h.bedrooms >= 4";
    }

    if ($location) {
        $query .= " AND h.location LIKE ?";
        $params[] = '%' . $location . '%';
    }

    if ($landlord_id) {
        $query .= " AND h.landlord_id = ?";
        $params[] = $landlord_id;
    }

    // Sorting
    switch ($sort) {
        case 'price-low':
            if ($listing_type === 'airbnb') {
                $query .= " ORDER BY COALESCE(h.price_night, h.price) ASC";
            } elseif ($listing_type === 'sale') {
                $query .= " ORDER BY COALESCE(h.price_sale, h.price) ASC";
            } else {
                $query .= " ORDER BY h.price ASC";
            }
            break;
        case 'price-high':
            if ($listing_type === 'airbnb') {
                $query .= " ORDER BY COALESCE(h.price_night, h.price) DESC";
            } elseif ($listing_type === 'sale') {
                $query .= " ORDER BY COALESCE(h.price_sale, h.price) DESC";
            } else {
                $query .= " ORDER BY h.price DESC";
            }
            break;
        case 'verified':
            $query .= " ORDER BY h.verified DESC, h.created_at DESC";
            break;
        case 'newest':
        default:
            $query .= " ORDER BY h.created_at DESC";
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $houses = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $houses,
        'count' => count($houses)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>