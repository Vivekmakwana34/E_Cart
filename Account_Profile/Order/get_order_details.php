<?php
session_start();
include '../../Database_file/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$userId = $_SESSION['user_id'];

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;

if ($order) {
    echo json_encode(['success' => true, 'order' => $order]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
?>
