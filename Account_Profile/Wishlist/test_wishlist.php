<?php
session_start();
header('Content-Type: application/json');

// Debug endpoint to check wishlist functionality
$response = [
    'session_status' => 'unknown',
    'session_data' => [],
    'post_data' => $_POST,
    'server_request_method' => $_SERVER['REQUEST_METHOD'],
    'test_insert' => false,
    'errors' => []
];

try {
    // Check session
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $response['session_status'] = 'logged_in';
        $response['session_data']['user_id'] = $_SESSION['user_id'] ?? null;
    } else {
        $response['session_status'] = 'not_logged_in';
    }
    
    // Try to include config
    $configPath = __DIR__ . '/../../Database_file/config.php';
    if (!file_exists($configPath)) {
        $configPath = dirname(__DIR__, 2) . '/Database_file/config.php';
    }
    
    if (file_exists($configPath)) {
        include $configPath;
        $response['config_included'] = true;
        
        // Try a test query if logged in
        if ($response['session_status'] === 'logged_in' && isset($conn)) {
            $userId = (int)$_SESSION['user_id'];
            
            // Check existing wishlist count
            $checkStmt = $conn->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?');
            if ($checkStmt) {
                $checkStmt->bind_param('i', $userId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();
                $response['existing_wishlist_count'] = $row['count'];
            }
            
            // If POST data provided, try actual insert
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
                $productId = (int)$_POST['product_id'];
                $productName = trim((string)($_POST['product_name'] ?? 'Test Product'));
                $productPrice = isset($_POST['product_price']) ? (float)$_POST['product_price'] : 99.99;
                $productImage = trim((string)($_POST['product_image'] ?? 'test.jpg'));
                
                // Check if already exists
                $checkStmt = $conn->prepare('SELECT id FROM wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
                $checkStmt->bind_param('ii', $userId, $productId);
                $checkStmt->execute();
                $existing = $checkStmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    // Remove
                    $delStmt = $conn->prepare('DELETE FROM wishlist WHERE id = ? AND user_id = ?');
                    $delStmt->bind_param('ii', $existing['id'], $userId);
                    $result = $delStmt->execute();
                    $response['test_insert'] = $result ? 'removed' : 'remove_failed';
                } else {
                    // Insert
                    $insStmt = $conn->prepare('INSERT INTO wishlist (user_id, product_id, product_name, product_price, product_image) VALUES (?, ?, ?, ?, ?)');
                    $insStmt->bind_param('iisds', $userId, $productId, $productName, $productPrice, $productImage);
                    $result = $insStmt->execute();
                    $response['test_insert'] = $result ? 'added' : 'insert_failed';
                    if (!$result) {
                        $response['errors'][] = 'Insert failed: ' . $conn->error;
                    }
                }
                
                // Recount after operation
                $checkStmt = $conn->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?');
                $checkStmt->bind_param('i', $userId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $row = $result->fetch_assoc();
                $response['wishlist_count_after'] = $row['count'];
            }
        }
    } else {
        $response['config_included'] = false;
        $response['errors'][] = 'Config file not found at: ' . $configPath;
    }
} catch (Exception $e) {
    $response['errors'][] = 'Exception: ' . $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
