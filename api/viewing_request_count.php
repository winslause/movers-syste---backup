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

try {
    // Count viewing requests for this user
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as request_count
        FROM requests
        WHERE house_hunter_id = ? AND request_type = 'viewing'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $request_count = (int)$result['request_count'];
    
    // Check if user has full access
    $has_full_access = false;
    $stmt = $pdo->prepare("SELECT full_access FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['full_access']) {
        $has_full_access = true;
    }
    
    $free_requests_limit = 3;
    $has_free_requests = $has_full_access || $request_count < $free_requests_limit;

    echo json_encode([
        'success' => true,
        'data' => [
            'request_count' => $request_count,
            'free_requests_limit' => $free_requests_limit,
            'has_free_requests' => $has_free_requests,
            'remaining_free_requests' => max(0, $free_requests_limit - $request_count),
            'has_full_access' => $has_full_access
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>