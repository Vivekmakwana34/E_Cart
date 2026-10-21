<?php
session_start();
include '../../Database_file/config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$paymentMethod = (string)($data['paymentMethod'] ?? ($_SESSION['paymentMethod'] ?? 'card'));
$selectedAddressId = (int)($data['selectedAddress'] ?? ($_SESSION['selectedAddress'] ?? 0));

$priceData = $data['priceData'] ?? [];
$totalAmount = 0.0;
if (is_array($priceData) && isset($priceData['finalTotal'])) {
    $totalAmount = (float)$priceData['finalTotal'];
}

// Fallback: compute total from cart if not provided
if ($totalAmount <= 0) {
    $cartItems = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $cartSubtotal = 0.0;
    foreach ($cartItems as $it) {
        $qty = (int)($it['quantity'] ?? 0);
        $price = (float)($it['price'] ?? 0);
        if ($qty <= 0) continue;
        $cartSubtotal += $price * $qty;
    }
    $regularDiscount = $cartSubtotal * 0.30;
    $totalAmount = max(0, $cartSubtotal - $regularDiscount);
}

// Fetch selected address
$address = null;
if ($selectedAddressId > 0) {
    $stmt = $conn->prepare('SELECT address_line1, address_line2, city, state, postal_code, phone FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $selectedAddressId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $address = $res ? $res->fetch_assoc() : null;
    }
}

$shippingAddress = '';
if ($address) {
    $shippingAddress = trim((string)($address['address_line1'] ?? ''));
    $line2 = trim((string)($address['address_line2'] ?? ''));
    if ($line2 !== '') $shippingAddress .= ', ' . $line2;
    $city = trim((string)($address['city'] ?? ''));
    $state = trim((string)($address['state'] ?? ''));
    $pin = trim((string)($address['postal_code'] ?? ''));
    $phone = trim((string)($address['phone'] ?? ''));
    $tailParts = array_filter([$city, $state], fn($v) => $v !== '');
    if (!empty($tailParts)) $shippingAddress .= ', ' . implode(', ', $tailParts);
    if ($pin !== '') $shippingAddress .= ' - ' . $pin;
    if ($phone !== '') $shippingAddress .= ' (Phone: ' . $phone . ')';
}

// Generate unique-ish order number
$orderNumber = 'EC' . date('YmdHis') . str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);

$insert = $conn->prepare('INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, order_status, shipping_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
if (!$insert) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$paymentStatus = 'completed';
$orderStatus = 'pending';
$insert->bind_param('isdssss', $userId, $orderNumber, $totalAmount, $paymentMethod, $paymentStatus, $orderStatus, $shippingAddress);

if (!$insert->execute()) {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $insert->error]);
    exit();
}

$orderId = $insert->insert_id;

// Mark coupon as used if applied
if (isset($_SESSION['applied_coupon']) && isset($_SESSION['user_id'])) {
    $couponData = $_SESSION['applied_coupon'];
    $couponCode = is_array($couponData) ? $couponData['code'] : $couponData;
    $userId = $_SESSION['user_id'];
    
    $mark_used_sql = "UPDATE user_coupons uc 
                      JOIN coupons c ON uc.coupon_id = c.id 
                      SET uc.is_used = 1, uc.used_at = NOW() 
                      WHERE uc.user_id = ? AND c.code = ? AND uc.is_used = 0";
    $mark_stmt = $conn->prepare($mark_used_sql);
    $mark_stmt->bind_param('is', $userId, $couponCode);
    $mark_stmt->execute();
    
    // Also clear the applied coupon from session
    unset($_SESSION['applied_coupon']);
}

// Keep order id/number in session for other pages if needed
$_SESSION['last_order_id'] = $orderId;
$_SESSION['last_order_number'] = $orderNumber;

echo json_encode(['success' => true, 'orderId' => $orderId, 'orderNumber' => $orderNumber]);
?>
