<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once 'Database_file/config.php';

$user_id = $_SESSION['user_id'];

// Get coupon code from request
$data = json_decode(file_get_contents('php://input'), true);
$coupon_code = $data['coupon_code'] ?? '';

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'message' => 'Coupon code required']);
    exit;
}

// Find the user's coupon and mark it as used
$sql = "UPDATE user_coupons uc 
        JOIN coupons c ON uc.coupon_id = c.id 
        SET uc.is_used = 1, uc.used_at = NOW() 
        WHERE uc.user_id = ? 
        AND c.code = ? 
        AND uc.is_used = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $coupon_code);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Coupon marked as used']);
} else {
    echo json_encode(['success' => false, 'message' => 'Coupon not found or already used']);
}

exit;
?>
