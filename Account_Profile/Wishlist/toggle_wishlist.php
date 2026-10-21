<?php
session_start();
header('Content-Type: application/json');

// Support being called from different base URLs by resolving config path safely
$configPath = __DIR__ . '/../../Database_file/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/Database_file/config.php';
}
include $configPath;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'redirect' => '/E_Cart/login.php'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$productName = trim((string)($_POST['product_name'] ?? ''));
$productPrice = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 0;
$productImage = trim((string)($_POST['product_image'] ?? ''));

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product_id']);
    exit;
}

// Check if already wishlisted
$checkStmt = $conn->prepare('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}
$checkStmt->bind_param('ii', $userId, $productId);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
$existing = $checkRes ? $checkRes->fetch_assoc() : null;

if ($existing) {
    $delStmt = $conn->prepare('DELETE FROM wishlist WHERE id = ? AND user_id = ?');
    if (!$delStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB error']);
        exit;
    }
    $wishId = (int)$existing['id'];
    $delStmt->bind_param('ii', $wishId, $userId);
    $ok = $delStmt->execute();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to remove']);
        exit;
    }

    echo json_encode(['success' => true, 'action' => 'removed', 'product_id' => $productId]);
    exit;
}

$insStmt = $conn->prepare('INSERT INTO wishlist (user_id, product_id, product_name, product_price, product_image) VALUES (?, ?, ?, ?, ?)');
if (!$insStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}
$insStmt->bind_param('iisds', $userId, $productId, $productName, $productPrice, $productImage);
$ok = $insStmt->execute();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add']);
    exit;
}

echo json_encode(['success' => true, 'action' => 'added', 'product_id' => $productId]);
exit;
