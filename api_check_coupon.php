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

// Check for any pending coupon (milestone or random)
$sql = "SELECT c.id, c.code, c.description, c.discount_type, c.discount_value, c.max_discount, 
        uc.id as user_coupon_id, uc.assigned_at
        FROM user_coupons uc 
        JOIN coupons c ON uc.coupon_id = c.id 
        WHERE uc.user_id = ? 
        AND uc.is_notified = 0 
        AND uc.is_used = 0 
        AND c.is_active = 1 
        AND c.valid_from <= CURDATE() 
        AND c.valid_until >= CURDATE()
        ORDER BY uc.assigned_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_coupon = $result->fetch_assoc();

if ($pending_coupon) {
    // Format discount display
    $discount_display = $pending_coupon['discount_type'] == 'percentage' 
        ? $pending_coupon['discount_value'] . '% OFF'
        : '₹' . $pending_coupon['discount_value'] . ' OFF';
    
    $response = [
        'success' => true,
        'has_coupon' => true,
        'coupon' => [
            'code' => $pending_coupon['code'],
            'discount' => $discount_display,
            'description' => $pending_coupon['description'],
            'user_coupon_id' => $pending_coupon['user_coupon_id']
        ]
    ];
    
    // Mark as notified so it won't show again
    $update_sql = "UPDATE user_coupons SET is_notified = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('i', $pending_coupon['user_coupon_id']);
    $update_stmt->execute();
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => true, 'has_coupon' => false]);
}

exit;
?>
