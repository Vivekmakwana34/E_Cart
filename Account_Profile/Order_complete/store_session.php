<?php
session_start();
header('Content-Type: application/json');

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Store in PHP session
if (isset($data['paymentMethod'])) {
    $_SESSION['paymentMethod'] = $data['paymentMethod'];
}
if (isset($data['selectedAddress'])) {
    $_SESSION['selectedAddress'] = $data['selectedAddress'];
}

echo json_encode(['success' => true]);
?>
