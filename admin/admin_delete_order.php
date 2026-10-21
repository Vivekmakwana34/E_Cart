<?php
session_start();
header('Content-Type: application/json');

// Simple admin check (mirrors current admin area behavior)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

require_once '../Database_file/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

// Ensure only cancelled orders can be deleted
$check = $conn->prepare('SELECT id, order_status FROM orders WHERE id = ? LIMIT 1');
if (!$check) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$check->bind_param('i', $orderId);
$check->execute();
$res = $check->get_result();
$order = $res ? $res->fetch_assoc() : null;

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$status = strtolower(trim((string)($order['order_status'] ?? '')));
if ($status !== 'cancelled' && $status !== 'canceled') {
    echo json_encode(['success' => false, 'message' => 'Only cancelled orders can be deleted']);
    exit;
}

$del = $conn->prepare('DELETE FROM orders WHERE id = ?');
if (!$del) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$del->bind_param('i', $orderId);

if (!$del->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $del->error]);
    exit;
}

echo json_encode(['success' => true]);
exit;
?>
