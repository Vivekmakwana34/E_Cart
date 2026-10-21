<?php
session_start();
header('Content-Type: application/json');

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Check if wishlist_id or product_id is provided
if (isset($_POST['wishlist_id'])) {
    $wishlistId = (int)$_POST['wishlist_id'];
    if ($wishlistId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid wishlist_id']);
        exit;
    }
    
    $stmt = $conn->prepare('DELETE FROM wishlist WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error']);
        exit;
    }
    $stmt->bind_param('ii', $wishlistId, $userId);
    $success = $stmt->execute();
    
} elseif (isset($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];
    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product_id']);
        exit;
    }
    
    $stmt = $conn->prepare('DELETE FROM wishlist WHERE product_id = ? AND user_id = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error']);
        exit;
    }
    $stmt->bind_param('ii', $productId, $userId);
    $success = $stmt->execute();
    
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'wishlist_id or product_id required']);
    exit;
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to remove']);
}
?>
