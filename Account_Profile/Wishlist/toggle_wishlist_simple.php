<?php
session_start();

// Support being called from different base URLs by resolving config path safely
$configPath = __DIR__ . '/../../Database_file/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__, 2) . '/Database_file/config.php';
}
include $configPath;

// Check if logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: /E_Cart/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E_Cart/index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$productName = trim((string)($_POST['product_name'] ?? ''));
$productPrice = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 0;
$productImage = trim((string)($_POST['product_image'] ?? ''));
$redirectUrl = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '/E_Cart/index.php';

if ($productId <= 0) {
    header('Location: ' . $redirectUrl);
    exit;
}

// Check if already wishlisted
$checkStmt = $conn->prepare('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
$checkStmt->bind_param('ii', $userId, $productId);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();

if ($existing) {
    // Remove from wishlist
    $delStmt = $conn->prepare('DELETE FROM wishlist WHERE id = ? AND user_id = ?');
    $delStmt->bind_param('ii', $existing['id'], $userId);
    $delStmt->execute();
} else {
    // Add to wishlist
    $insStmt = $conn->prepare('INSERT INTO wishlist (user_id, product_id, product_name, product_price, product_image) VALUES (?, ?, ?, ?, ?)');
    $insStmt->bind_param('iisds', $userId, $productId, $productName, $productPrice, $productImage);
    $insStmt->execute();
}

// Redirect back to the original page
header('Location: ' . $redirectUrl);
exit;
?>
