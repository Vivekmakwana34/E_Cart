<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

include '../../Database_file/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection not available']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Accept JSON OR normal POST
$orderId = 0;
$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');

if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (is_array($payload) && isset($payload['order_id'])) {
        $orderId = (int)$payload['order_id'];
    }
} else {
    $orderId = (int)($_POST['order_id'] ?? 0);
}

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

$stmt = $conn->prepare('SELECT id, order_status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ii', $orderId, $userId);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$order = $result ? $result->fetch_assoc() : null;

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$status = strtolower(trim((string)($order['order_status'] ?? '')));
$nonCancellable = ['shipped', 'delivered', 'cancelled', 'canceled'];
if (in_array($status, $nonCancellable, true)) {
    echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage']);
    exit;
}

$updateStmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ? AND user_id = ?");
if (!$updateStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$updateStmt->bind_param('ii', $orderId, $userId);
if (!$updateStmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $updateStmt->error]);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
exit;
?>
