<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$filter = isset($_GET['property_type']) ? $_GET['property_type'] : 'all';

try {
    if ($id) {
        $stmt = $pdo->prepare("SELECT h.*, a.name as area_name FROM houses h LEFT JOIN areas a ON h.area_id = a.id WHERE h.id = ?");
        $stmt->execute([$id]);
        $houses = $stmt->fetchAll();
    } elseif ($filter === 'all') {
        $stmt = $pdo->prepare("SELECT h.*, a.name as area_name FROM houses h LEFT JOIN areas a ON h.area_id = a.id ORDER BY h.created_at DESC LIMIT 6");
        $stmt->execute();
        $houses = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT h.*, a.name as area_name FROM houses h LEFT JOIN areas a ON h.area_id = a.id WHERE h.property_type = ? ORDER BY h.created_at DESC LIMIT 6");
        $stmt->execute([$filter]);
        $houses = $stmt->fetchAll();
    }
    
    // Get listing_type from database - fetch fresh from houses table
    $stmt = $pdo->prepare("SELECT id, listing_type, price_night, price_sale, price FROM houses");
    $stmt->execute();
    $allHouses = $stmt->fetchAll();
    
    // Create a map of id to listing_type and prices
    $houseTypes = [];
    foreach ($allHouses as $house) {
        $houseTypes[$house['id']] = [
            'listing_type' => $house['listing_type'],
            'price_night' => $house['price_night'],
            'price_sale' => $house['price_sale'],
            'price' => $house['price']
        ];
    }
    
    // Attach listing_type and prices to each house
    foreach ($houses as &$house) {
        if (isset($houseTypes[$house['id']])) {
            $house['listing_type'] = $houseTypes[$house['id']]['listing_type'];
            $house['price_night'] = $houseTypes[$house['id']]['price_night'];
            $house['price_sale'] = $houseTypes[$house['id']]['price_sale'];
        } else {
            $house['listing_type'] = 'rent';
            $house['price_night'] = null;
            $house['price_sale'] = null;
        }
    }
    unset($house);

    echo json_encode([
        'success' => true,
        'data' => $houses
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>