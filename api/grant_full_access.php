<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/../db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

$target_user_id = (int)$data['user_id'];
$grant_access = isset($data['grant_access']) ? (bool)$data['grant_access'] : true;

try {
    // Check if target user exists
    $stmt = $pdo->prepare("SELECT id, full_access FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Update full_access status
    $stmt = $pdo->prepare("UPDATE users SET full_access = ? WHERE id = ?");
    $stmt->execute([$grant_access ? 1 : 0, $target_user_id]);

    echo json_encode([
        'success' => true,
        'message' => $grant_access ? 'Full access granted successfully' : 'Full access revoked successfully',
        'data' => [
            'user_id' => $target_user_id,
            'full_access' => $grant_access
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
