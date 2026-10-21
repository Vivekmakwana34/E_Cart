<?php
// Search API - Returns products and categories matching search query
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'Database_file/config.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['categories' => [], 'products' => []]);
    exit;
}

$searchTerm = "%{$query}%";
$categories = [];
$products = [];

try {
    // Search Categories (without status check if column doesn't exist)
    $catSql = "SELECT id, name FROM categories WHERE name LIKE ? LIMIT 5";
    $catStmt = $conn->prepare($catSql);
    if ($catStmt) {
        $catStmt->bind_param("s", $searchTerm);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $categories[] = $row;
        }
        $catStmt->close();
    }

    // Search Products (without status check if column doesn't exist)
    $prodSql = "SELECT p.id, p.name, p.price, p.image, p.category_id, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.name LIKE ? OR p.description LIKE ?) 
                LIMIT 8";
    $prodStmt = $conn->prepare($prodSql);
    if ($prodStmt) {
        $prodStmt->bind_param("ss", $searchTerm, $searchTerm);
        $prodStmt->execute();
        $prodResult = $prodStmt->get_result();
        while ($row = $prodResult->fetch_assoc()) {
            // Parse image JSON array and get first image
            $images = [];
            if (!empty($row['image'])) {
                $decoded = json_decode($row['image'], true);
                if (is_array($decoded) && !empty($decoded)) {
                    $images = $decoded;
                } else {
                    $images = [$row['image']];
                }
            }
            $row['image'] = !empty($images) ? 'assets/img/' . $images[0] : '';
            $products[] = $row;
        }
        $prodStmt->close();
    }
} catch (Exception $e) {
    // Silently handle errors
}

echo json_encode([
    'categories' => $categories,
    'products' => $products
]);
?>
