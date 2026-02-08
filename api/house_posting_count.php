<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Only landlords can post houses
if ($user_type !== 'landlord') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only landlords can post houses']);
    exit;
}

try {
    // Count houses posted by this landlord
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as house_count
        FROM houses
        WHERE landlord_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $house_count = (int)$result['house_count'];
    
    // Check if user has full access
    $has_full_access = false;
    $stmt = $pdo->prepare("SELECT full_access FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['full_access']) {
        $has_full_access = true;
    }
    
    $free_houses_limit = 3;
    $has_free_houses = $has_full_access || $house_count < $free_houses_limit;

    echo json_encode([
        'success' => true,
        'data' => [
            'house_count' => $house_count,
            'free_houses_limit' => $free_houses_limit,
            'has_free_houses' => $has_free_houses,
            'remaining_free_houses' => max(0, $free_houses_limit - $house_count),
            'has_full_access' => $has_full_access
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>