<?php
session_start();
header('Content-Type: application/json');

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
$status = trim((string)($_POST['status'] ?? ''));

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}

if ($status === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$allowed = ['pending', 'shipped', 'delivered', 'cancelled', 'canceled'];
if (!in_array(strtolower($status), $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$stmt = $conn->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('si', $status, $orderId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
    exit;
}

// Check for milestone/random coupons if order is delivered
$assigned_coupon = null;
if (strtolower($status) === 'delivered') {
    // Get user_id for this order
    $userStmt = $conn->prepare('SELECT user_id FROM orders WHERE id = ?');
    $userStmt->bind_param('i', $orderId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    if ($userRow = $userResult->fetch_assoc()) {
        $userId = $userRow['user_id'];
        // Assign random coupon to user
        $assigned_coupon = assignRandomCouponOnDelivered($conn, $userId);
    }
}

$response = ['success' => true, 'message' => 'Status updated'];
if ($assigned_coupon) {
    $response['coupon'] = $assigned_coupon;
}

echo json_encode($response);
exit;
?>
