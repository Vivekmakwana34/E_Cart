<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

require_once '../../../includes/db_connect.php';

$userId = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $conn->prepare("SELECT order_number, order_status, total_amount, payment_method, shipping_address, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

// Create CSV content
$csv = "Order Number,Status,Total Amount,Payment Method,Shipping Address,Order Date\n";

while ($order = $result->fetch_assoc()) {
    $csv .= '"' . $order['order_number'] . '",';
    $csv .= '"' . ucfirst($order['order_status']) . '",';
    $csv .= '"' . $order['total_amount'] . '",';
    $csv .= '"' . $order['payment_method'] . '",';
    $csv .= '"' . str_replace('"', '""', $order['shipping_address']) . '",';
    $csv .= '"' . date('Y-m-d H:i:s', strtotime($order['created_at'])) . '"' . "\n";
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="order_history.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

echo $csv;
exit;
?>
