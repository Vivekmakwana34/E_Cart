<?php
session_start();
include '../Database_file/config.php';

// Create reviews table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    images JSON,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Insert sample review for testing if none exists for current product
$check_sample = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE product_id = ?");
$check_sample->bind_param("i", $product_id);
$check_sample->execute();
$sample_result = $check_sample->get_result();
$sample_count = $sample_result->fetch_assoc()['count'];
$check_sample->close();

if ($sample_count == 0 && $product_id > 0) {
    // Insert sample review
    $insert_sql = "INSERT INTO reviews (user_id, product_id, order_id, rating, review_text, status) VALUES (?, ?, ?, ?, ?, 'approved')";
    $stmt = $conn->prepare($insert_sql);
    $user_id = 25; // Using existing user ID
    $order_id = 1;
    $rating = 5;
    $review_text = "Excellent product! The build quality is amazing and the mouse is very smooth. Keyboard keys are soft and responsive. USB hub is a bonus. Great value for money.";
    
    $stmt->bind_param("iiiis", $user_id, $product_id, $order_id, $rating, $review_text);
    $stmt->execute();
    $stmt->close();
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details from database
$product = null;
if ($product_id > 0) {
    $prod_query = $conn->prepare("SELECT id, name, price, image, description, category_id, stock FROM products WHERE id = ? AND status = 'active'");
    $prod_query->bind_param("i", $product_id);
    $prod_query->execute();
    $prod_result = $prod_query->get_result();
    $product = $prod_result->fetch_assoc();
}

// Get product reviews from database
$productReviews = [];
$debug_info = [];
if ($product_id > 0) {
    $debug_info['product_id'] = $product_id;
    
    // First try without JOIN to see if reviews exist
    $simple_query = $conn->prepare("SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $simple_query->bind_param("i", $product_id);
    $simple_query->execute();
    $simple_result = $simple_query->get_result();
    $debug_info['simple_query_rows'] = $simple_result->num_rows;
    
    if ($simple_result->num_rows > 0) {
        // Reviews exist, now get with user info
        $reviews_query = $conn->prepare("SELECT r.rating, r.review_text, r.images, r.created_at, u.full_name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
        $reviews_query->bind_param("i", $product_id);
        $reviews_query->execute();
        $reviews_result = $reviews_query->get_result();
        
        $debug_info['query_executed'] = true;
        $debug_info['num_rows'] = $reviews_result->num_rows;
        
        while ($review = $reviews_result->fetch_assoc()) {
            // Use user name or fallback
            if (empty($review['user_name'])) {
                $review['user_name'] = 'Anonymous User';
            }
            $productReviews[] = $review;
            $debug_info['fetched_reviews'][] = [
                'rating' => $review['rating'],
                'user' => $review['user_name'],
                'text' => substr($review['review_text'], 0, 50) . '...'
            ];
        }
        $reviews_query->close();
    } else {
        $debug_info['no_reviews_found'] = true;
    }
    $simple_query->close();
}

// Calculate average rating
$averageRating = 0;
$totalReviews = count($productReviews);
if ($totalReviews > 0) {
    $totalRating = 0;
    foreach ($productReviews as $review) {
        $totalRating += $review['rating'];
    }
    $averageRating = round($totalRating / $totalReviews, 1);
    $debug_info['average_rating'] = $averageRating;
}

$productImages = [];
$imageVal = (string)($product['image'] ?? '');
if ($imageVal !== '') {
    $decoded = json_decode($imageVal, true);
    if (is_array($decoded) && !empty($decoded)) {
        $productImages = array_values(array_filter($decoded, fn($v) => is_string($v) && $v !== ''));
    } else {
        $productImages = [$imageVal];
    }
}

$primaryImage = $productImages[0] ?? '';

// If no product found, redirect to home
if (!$product) {
    header('Location: ../index.php');
    exit();
}

// Handle add to cart
if (isset($_POST['add_to_cart']) && $_POST['add_to_cart'] == 1) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    
    $add_product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Check if product already in cart
    if (isset($_SESSION['cart'][$add_product_id])) {
        $_SESSION['cart'][$add_product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$add_product_id] = array(
            'id' => $add_product_id,
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'quantity' => $quantity
        );
    }
    
    // No redirect - stay on product page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../floating_home.css">
    <style>
        /* Flipkart Style Product Page */
        body {
            background: #f1f3f6 !important;
            margin: 0;
            padding: 0;
        }

        .product-container {
            max-width: 1248px;
            margin: 0 auto;
            padding: 8px 0;
            background: #f1f3f6;
            min-height: calc(100vh - 200px);
        }

        .product-breadcrumb {
            padding: 16px 24px;
            font-size: 14px;
            color: #878787;
            background: white;
            margin-bottom: 8px;
        }

        .product-breadcrumb a {
            color: #2874f0;
            text-decoration: none;
        }

        .product-breadcrumb a:hover {
            text-decoration: underline;
        }

        .product-main {
            display: grid;
            grid-template-columns: 44% 56%;
            gap: 0;
            background: white;
            margin-bottom: 8px;
        }

        /* Left Section - Images */
        .product-images-section {
            padding: 24px;
            border-right: 1px solid #f0f0f0;
        }

        .main-image-container {
            position: relative;
            margin-bottom: 16px;
            background: white;
            border-radius: 4px;
            overflow: hidden;
        }

        .main-image {
            width: 100%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            position: relative;
        }

        .main-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-zoom-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e0e0e0;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #878787;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .image-zoom-icon:hover {
            background: white;
            color: #2874f0;
        }

        .thumbnail-container,
        .thumbnail-images {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 6px 0;
        }

        .thumbnail {
            flex: 0 0 auto;
            width: 80px;
            height: 80px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            position: relative;
        }

        .thumbnail.active {
            border-color: #2874f0;
            box-shadow: 0 0 0 2px rgba(40, 116, 240, 0.1);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 4px;
        }

        /* Product Features Section */
        .product-features-section {
            margin-top: 30px;
            padding: 16px;
            background: #fffef0;
            border: 1px solid #f0f0f0;
            border-radius: 4px;
        }

        .features-title {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;   
        }

        .features-title i {
            color: #ff9f00;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #212121;
            line-height: 1.4;
        }

        .feature-item i {
            color: #388e3c;
            font-size: 12px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .feature-badge {
            display: inline-block;
            background: #e8f5e8;
            color: #388e3c;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 4px;
        }

        /* Payment Offers Section - Professional Design */
        .payment-offers-section {
            margin-top: 20px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .payment-offers-title {
            font-size: 18px;
            font-weight: 600;
            color: #212121;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .payment-offers-title i {
            color: #2874f0;
            font-size: 16px;
        }

        .offers-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .offer-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .offer-type {
            min-width: 100px;
            padding: 4px 8px;
            background: #f0f0f0;
            color: #212121;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .offer-type.wallet {
            background: #e8f5e8;
            color: #388e3c;
        }

        .offer-type.emi {
            background: #e3f2fd;
            color: #1976d2;
        }

        .offer-type.partner {
            background: #fff3e0;
            color: #f57c00;
        }

        .offer-details {
            flex: 1;
        }

        .offer-title {
            font-size: 14px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .offer-code {
            font-size: 12px;
            color: #2874f0;
            font-weight: 500;
            cursor: pointer;
        }

        .offers-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }

        .offers-footer a {
            color: #2874f0;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }

        /* Highlights Section - Enhanced Design */
        .highlights-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            border: 2px solid #dee2e6;
        }

        .highlights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px;
        }

        .highlight-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .highlight-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            flex-shrink: 0;
        }

        .highlight-item span {
            font-size: 13px;
            color: #495057;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Services Section - Enhanced Design */
        .services-section {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-radius: 12px;
            border: 2px solid #ffc107;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .service-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: white;
            border-radius: 10px;
            border: 1px solid #ffc107;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
        }

        .service-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .service-icon.return {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .service-icon.delivery {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }

        .service-icon.support {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
        }

        .service-icon.warranty {
            background: linear-gradient(135deg, #fd7e14, #e55a00);
        }

        .service-content {
            flex: 1;
        }

        .service-title {
            font-size: 14px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 4px;
        }

        .service-desc {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.3;
        }

        /* Product Variants Section */
        .variants-section {
            margin-bottom: 24px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .variant-group {
            margin-bottom: 20px;
        }

        .variant-group:last-child {
            margin-bottom: 0;
        }

        .variant-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 12px;
        }

        .variant-label i {
            color: #2874f0;
        }

        .selected-variant {
            color: #2874f0;
            font-weight: 700;
        }

        .color-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .color-option.active {
            border-color: #2874f0;
            box-shadow: 0 0 0 2px rgba(40, 116, 240, 0.2);
        }

        .color-option.active::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }

        .size-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .size-option {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            transition: all 0.3s ease;
        }

        .size-option.active {
            border-color: #2874f0;
            background: #2874f0;
            color: white;
        }

        /* Stock Counter Section - Professional Design */
        .stock-counter {
            margin-bottom: 24px;
            padding: 16px;
            background: #fff8f8;
            border: 1px solid #ffebee;
            border-radius: 8px;
        }

        .stock-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .stock-icon {
            width: 36px;
            height: 36px;
            background: #ff5252;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .stock-details {
            flex: 1;
        }

        .stock-text {
            font-size: 14px;
            font-weight: 600;
            color: #d32f2f;
            margin-bottom: 2px;
        }

        .stock-count {
            font-size: 16px;
            font-weight: 700;
            color: #ff5252;
        }

        .stock-subtitle {
            font-size: 12px;
            color: #757575;
        }

        .stock-progress {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-bar {
            flex: 1;
            height: 6px;
            background: #ffebee;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #ff5252;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 11px;
            font-weight: 600;
            color: #d32f2f;
        }

        /* Product Tags Section - Professional Design */
        .product-tags-section {
            margin-bottom: 24px;
            padding: 16px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .tags-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #424242;
            margin-bottom: 12px;
        }

        .tags-title i {
            color: #757575;
        }

        .tags-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tag-category {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .tag-label {
            min-width: 70px;
            font-size: 12px;
            font-weight: 600;
            color: #616161;
            padding-top: 2px;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            flex: 1;
        }

        .tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .category-tag {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .brand-tag {
            background: #f3e5f5;
            color: #6a1b9a;
            border: 1px solid #e1bee7;
        }

        .feature-tag {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }

        .tag:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Product Features Section */
        .product-features-section {
            margin-bottom: 24px;
            padding: 16px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .features-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #424242;
            margin-bottom: 12px;
        }

        .features-title i {
            color: #ffc107;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 8px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .feature-item i {
            color: #28a745;
            font-size: 12px;
            flex-shrink: 0;
        }

        .feature-item span {
            font-size: 12px;
            color: #495057;
            line-height: 1.3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .payment-offers-section {
                margin-top: 16px;
                padding: 16px;
            }

            .payment-offers-title {
                font-size: 16px;
                margin-bottom: 12px;
            }

            .offer-item {
                gap: 12px;
                padding: 10px 0;
            }

            .offer-type {
                min-width: 80px;
                font-size: 11px;
                padding: 3px 6px;
            }

            .offer-title {
                font-size: 13px;
            }

            .offer-code {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .payment-offers-section {
                padding: 12px;
            }

            .payment-offers-title {
                font-size: 15px;
                gap: 6px;
            }

            .offer-item {
                flex-direction: column;
                gap: 8px;
                padding: 8px 0;
            }

            .offer-type {
                min-width: auto;
                width: fit-content;
                align-self: flex-start;
            }

            .offer-details {
                width: 100%;
            }
        }

        .share-section {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }

        .share-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            color: #878787;
            font-size: 14px;
            cursor: pointer;
        }

        /* Right Section - Product Details */
        .product-details-section {
            padding: 24px;
        }

        .product-rating-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .rating-badge {
            background: #388e3c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .rating-count {
            color: #878787;
            font-size: 14px;
            font-weight: 400;
        }

        .product-title {
            font-size: 18px;
            font-weight: 500;
            color: #212121;
            line-height: 1.4;
            margin-bottom: 16px;
        }

        .brand-name {
            color: #878787;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .brand-name span {
            color: #2874f0;
            font-weight: 500;
        }

        .price-section {
            margin-bottom: 20px;
        }

        .current-price {
            font-size: 28px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 8px;
        }

        .original-price {
            font-size: 16px;
            color: #878787;
            text-decoration: line-through;
            margin-right: 8px;
        }

        .discount-badge {
            background: #ff6161;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }

        .offers-section {
            background: #fffef0;
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .offers-title {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .offers-title i {
            color: #ff9f00;
        }

        .offer-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #212121;
        }

        .offer-item i {
            color: #2874f0;
            font-size: 12px;
            margin-top: 3px;
        }

        .delivery-section {
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .delivery-input-group {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .pincode-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }

        .pincode-input:focus {
            border-color: #2874f0;
        }

        .check-btn {
            padding: 10px 20px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            color: #2874f0;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .check-btn:hover {
            background: #f5f5f5;
        }

        .delivery-info {
            font-size: 14px;
            color: #878787;
        }

        .delivery-info strong {
            color: #212121;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .add-to-cart-btn {
            flex: 1;
            padding: 16px;
            background: #ff9f00;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .buy-now-btn {
            flex: 1;
            padding: 16px;
            background: #fb641b;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Seller Info */
        .seller-info {
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .seller-name {
            font-size: 14px;
            color: #212121;
            margin-bottom: 8px;
        }

        .seller-name span {
            color: #2874f0;
            font-weight: 500;
        }

        .seller-ratings {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #878787;
        }

        /* Specifications */
        .specifications-section {
            background: white;
            margin-bottom: 8px;
        }

        .specifications-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 18px;
            font-weight: 500;
            color: #212121;
        }

        .specifications-content {
            padding: 24px;
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }

        .spec-table tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .spec-table tr:last-child {
            border-bottom: none;
        }

        .spec-table td {
            padding: 16px 0;
            font-size: 14px;
            vertical-align: top;
        }

        .spec-table td:first-child {
            width: 40%;
            color: #878787;
            font-weight: 400;
        }

        .spec-table td:last-child {
            width: 60%;
            color: #212121;
            font-weight: 500;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            margin-bottom: 8px;
        }

        .reviews-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 18px;
            font-weight: 500;
            color: #212121;
        }

        .reviews-content {
            padding: 24px;
        }

        .review-card {
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #878787;
            font-weight: 500;
        }

        .reviewer-name {
            font-size: 14px;
            font-weight: 500;
            color: #212121;
        }

        .review-date {
            font-size: 12px;
            color: #878787;
        }

        .review-rating {
            color: #ffa500;
            font-size: 14px;
        }

        .review-text {
            font-size: 14px;
            color: #212121;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .review-images {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .review-image {
            width: 60px;
            height: 60px;
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            object-fit: cover;
            cursor: pointer;
        }

        /* Similar Products Section */
        .similar-products-section {
            background: white;
            margin-bottom: 8px;
        }

        .similar-products-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 18px;
            font-weight: 500;
            color: #212121;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .similar-products-header i {
            color: #2874f0;
        }

        .similar-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            padding: 24px;
        }

        .similar-product-card {
            border: 1px solid #f0f0f0;
            border-radius: 4px;
            padding: 16px;
            background: white;
            cursor: pointer;
        }

        .similar-product-image {
            width: 100%;
            height: 160px;
            background: #f5f5f5;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .similar-product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .similar-product-title {
            font-size: 14px;
            font-weight: 500;
            color: #212121;
            line-height: 1.3;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .similar-product-brand {
            font-size: 12px;
            color: #878787;
            margin-bottom: 8px;
        }

        .similar-product-brand span {
            color: #2874f0;
            font-weight: 500;
        }

        .similar-product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .similar-rating-stars {
            color: #ffa500;
        }

        .similar-rating-count {
            color: #878787;
        }

        .similar-product-price {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .similar-current-price {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
        }

        .similar-original-price {
            font-size: 12px;
            color: #878787;
            text-decoration: line-through;
        }

        .similar-discount {
            font-size: 11px;
            color: #388e3c;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .product-images-section {
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
            }

            .main-image {
                height: 400px;
            }

            .product-features-section {
                margin-top: 16px;
            }
        }

        @media (max-width: 768px) {
            .product-container {
                padding: 0;
            }

            .product-breadcrumb {
                padding: 12px 16px;
                font-size: 12px;
            }

            .product-images-section,
            .product-details-section {
                padding: 16px;
            }

            .main-image {
                height: 300px;
            }

            .thumbnail {
                min-width: 60px;
                height: 60px;
            }

            .product-title {
                font-size: 16px;
            }

            .current-price {
                font-size: 24px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .spec-table td {
                padding: 12px 0;
                font-size: 12px;
            }

            .spec-table td:first-child {
                width: 35%;
            }

            .spec-table td:last-child {
                width: 65%;
            }

            .similar-products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 16px;
            }

            .similar-product-image {
                height: 120px;
            }

            .similar-product-title {
                font-size: 12px;
            }

            .similar-current-price {
                font-size: 14px;
            }

            /* Move payment offers to bottom on mobile */
            .payment-offers-section {
                order: 10;
                margin-top: 20px;
            }
        }

        @media (max-width: 480px) {
            .main-image {
                height: 250px;
            }

            .product-title {
                font-size: 14px;
            }

            .current-price {
                font-size: 20px;
            }

            .offers-section,
            .delivery-section,
            .seller-info {
                padding: 12px;
            }

            .action-buttons {
                gap: 8px;
            }

            .add-to-cart-btn,
            .buy-now-btn {
                padding: 12px;
                font-size: 14px;
            }

            /* Reorder sections on mobile - Price first, then features */
            .product-details-section {
                display: flex;
                flex-direction: column;
            }

            .product-details-section > * {
                order: 2;
            }

            .product-rating-row {
                order: 1;
            }

            .brand-name {
                order: 3;
            }

            .product-title {
                order: 4;
            }

            .price-section {
                order: 5;
            }

            .offers-section {
                order: 6;
            }

            .delivery-section {
                order: 7;
            }

            .action-buttons {
                order: 8;
            }

            .seller-info {
                order: 9;
            }

            .product-details-section .offers-section:not(.payment-offers-section) {
                order: 11;
            }

            .product-details-section .seller-info {
                order: 12;
            }
        }
    </style>
</head>
<body>

   <?php include '../Header.php'; ?>

    <!-- Product Container -->
    <div class="product-container">
        <!-- Breadcrumb -->
        <div class="product-breadcrumb">
            <a href="../index.php">Home</a> / 
            <a href="category.php?id=<?php echo $product['category_id']; ?>">Category</a> / 
            <?php echo htmlspecialchars($product['name']); ?>
        </div>

        <!-- Main Product Section -->
        <main class="product-main">
            <!-- Left Section - Images -->
            <section class="product-images-section">
                <div class="main-image-container">
                    <div class="main-image">
                        <img id="mainProductImage" src="../assets/img/<?php echo htmlspecialchars($primaryImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>

                    <?php if (!empty($productImages) && count($productImages) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($productImages as $idx => $img): ?>
                            <div class="thumbnail <?php echo $idx === 0 ? 'active' : ''; ?>" onclick="setMainImage('../assets/img/<?php echo htmlspecialchars($img); ?>', this)">
                                <img src="../assets/img/<?php echo htmlspecialchars($img); ?>" alt="Thumbnail">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                <div class="share-section">
                    <form method="POST" action="../Account_Profile/Wishlist/toggle_wishlist_simple.php" style="display:inline; margin:0;">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($primaryImage); ?>">
                        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                        <button type="submit" class="share-button" title="Add to Wishlist">
                            <i class="fas fa-heart"></i>
                            Wishlist
                        </button>
                    </form>
                    <button class="share-button">
                        <i class="fas fa-share-alt"></i>
                        Share
                    </button>
                </div>

                <!-- Product Variants Section -->
                <div class="variants-section">
                    <!-- Size Variants -->
                    <div class="variant-group">
                        <div class="variant-label">
                            <i class="fas fa-ruler"></i>
                            Size: <span class="selected-variant">Standard</span>
                        </div>
                        <div class="size-options">
                            <div class="size-option active" data-size="Standard">Standard</div>
                            <div class="size-option" data-size="Compact">Compact</div>
                            <div class="size-option" data-size="Extended">Extended</div>
                            <div class="size-option" data-size="Premium">Premium</div>
                        </div>
                    </div>
                </div>

                <!-- Stock Counter -->
                <?php $stockCount = (int)($product['stock'] ?? 0); ?>
                <?php if ($stockCount > 0): ?>
                <?php
                    $progressPercent = max(8, min(100, (int)round((1 - min($stockCount, 50) / 50) * 100)));
                ?>
                <div class="stock-counter">
                    <div class="stock-info">
                        <div class="stock-icon">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stock-details">
                            <div class="stock-text">Only <span class="stock-count"><?php echo $stockCount; ?></span> left in stock!</div>
                            <div class="stock-subtitle">Hurry up! Limited stock available</div>
                        </div>
                    </div>
                    <div class="stock-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                        </div>
                        <span class="progress-text"><?php echo $progressPercent; ?>% Sold</span>
                    </div>
                </div>
                <?php else: ?>
                    <div class="stock-counter">
                        <div class="stock-info">
                            <div class="stock-icon">
                                <i class="fas fa-circle-xmark"></i>
                            </div>
                            <div class="stock-details">
                                <div class="stock-text">Out of stock</div>
                                <div class="stock-subtitle">This item is currently unavailable</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Product Tags -->
                <div class="product-tags-section">
                    <div class="tags-title">
                        <i class="fas fa-tags"></i>
                        Product Tags
                    </div>
                    <div class="tags-container">
                        <div class="tag-category">
                            <span class="tag-label">Category:</span>
                            <div class="tag-list">
                                <span class="tag category-tag">Computer Accessories</span>
                                <span class="tag category-tag">Electronics</span>
                                <span class="tag category-tag">Peripherals</span>
                            </div>
                        </div>
                        <div class="tag-category">
                            <span class="tag-label">Brand:</span>
                            <div class="tag-list">
                                <span class="tag brand-tag">E-Cart Basics</span>
                                <span class="tag brand-tag">Premium</span>
                            </div>
                        </div>
                        <div class="tag-category">
                            <span class="tag-label">Features:</span>
                            <div class="tag-list">
                                <span class="tag feature-tag">USB 3.0</span>
                                <span class="tag feature-tag">Wireless</span>
                                <span class="tag feature-tag">Ergonomic</span>
                                <span class="tag feature-tag">Gaming</span>
                                <span class="tag feature-tag">Plug & Play</span>
                            </div>
                        </div>
                    </div>
                </div>

                      <!-- Payment Offers Section -->
                <div class="payment-offers-section">
                    <div class="payment-offers-title">
                        <i class="fas fa-tag"></i>
                        Available Offers
                    </div>
                    <div class="offers-list">
                        <div class="offer-item">
                            <div class="offer-type">Bank Offer</div>
                            <div class="offer-details">
                                <div class="offer-title">10% Instant Discount on ICICI Bank Credit Cards</div>
                                <div class="offer-code">Code: ICICI10</div>
                            </div>
                        </div>
                        <div class="offer-item">
                            <div class="offer-type">Bank Offer</div>
                            <div class="offer-details">
                                <div class="offer-title">10% Instant Discount on HDFC Bank Credit Cards</div>
                                <div class="offer-code">Code: HDFC10</div>
                            </div>
                        </div>
                        <div class="offer-item">
                            <div class="offer-type wallet">Wallet Offer</div>
                            <div class="offer-details">
                                <div class="offer-title">5% Cashback up to ₹100 on Mobikwik Wallet</div>
                                <div class="offer-code">Code: MOBI5</div>
                            </div>
                        </div>
                        <div class="offer-item">
                            <div class="offer-type emi">EMI Offer</div>
                            <div class="offer-details">
                                <div class="offer-title">No Cost EMI on Bajaj Finserv EMI Card</div>
                                <div class="offer-code">View Plans</div>
                            </div>
                        </div>
                        <div class="offer-item">
                            <div class="offer-type partner">Partner Offer</div>
                            <div class="offer-details">
                                <div class="offer-title">Sign up for E-Cart Prime and get Free Delivery</div>
                                <div class="offer-code">Know More</div>
                            </div>
                        </div>
                    </div>
                    <div class="offers-footer">
                        <a href="#">View all offers and T&C</a>
                    </div>
                </div>
            </section>

            </section>

            <!-- Right Section - Product Details -->
            <section class="product-details-section">
                <div class="product-rating-row">
                    <?php if ($totalReviews > 0): ?>
                        <div class="rating-badge">
                            <?php echo $averageRating; ?> <i class="fas fa-star"></i>
                        </div>
                        <span class="rating-count"><?php echo $totalReviews; ?> Rating<?php echo $totalReviews > 1 ? 's' : ''; ?></span>
                    <?php else: ?>
                        <div class="rating-badge" style="background: #878787;">
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="rating-count">No ratings yet</span>
                    <?php endif; ?>
                </div>

                <div class="brand-name">
                    Brand: <span>E-Cart Essentials</span>
                </div>

                <h1 class="product-title">
                    <?php echo htmlspecialchars($product['name']); ?>
                </h1>

                <div class="price-section">
                    <div class="current-price">₹<?php echo number_format($product['price']); ?></div>
                </div>

                <!-- Product Features Section -->
                <div class="product-features-section">
                    <div class="features-title">
                        <i class="fas fa-star"></i>
                        Key Features
                    </div>
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>Complete Combo: Mouse + Keyboard + USB Hub</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>Ergonomic Design for Comfortable Use</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>Plug and Play - Easy Setup</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>1 Year Manufacturer Warranty</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>Compatible with Windows, Mac, Linux</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span>1200 DPI Optical Mouse for Precision</span>
                        </div>
                    </div>
                </div>

                <!-- Offers Section -->
                <div class="offers-section">
                    <div class="offers-title">
                        <i class="fas fa-tag"></i>
                        Available Offers
                    </div>
                    <div class="offer-item">
                        <i class="fas fa-circle"></i>
                        <span>Bank Offer: 10% instant discount on ICICI Bank Credit Cards</span>
                    </div>
                    <div class="offer-item">
                        <i class="fas fa-circle"></i>
                        <span>Special Price: Get extra 20% off (price inclusive of discount)</span>
                    </div>
                    <div class="offer-item">
                        <i class="fas fa-circle"></i>
                        <span>No Cost EMI: On EMI on Credit Cards</span>
                    </div>
                    <div class="offer-item">
                        <i class="fas fa-circle"></i>
                        <span>Partner Offer: Sign up for E-Cart Prime and get Free Delivery</span>
                    </div>
                </div>

                <!-- Delivery Section -->
                <div class="delivery-section">
                    <div class="delivery-input-group">
                        <input type="text" class="pincode-input" placeholder="Enter Delivery Pincode" value="400001">
                        <button class="check-btn">Check</button>
                    </div>
                    <div class="delivery-info">
                        <strong>Delivery by Tomorrow, 10 AM - 6 PM</strong><br>
                        Fastest delivery: Today, 9 PM
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i>
                            ADD TO CART
                        </button>
                    </form>
                    <button class="buy-now-btn" onclick="buyNow()">
                        <i class="fas fa-bolt"></i>
                        BUY NOW
                    </button>
                </div>

                <!-- Seller Info -->
                <div class="seller-info">
                    <div class="seller-name">
                        Seller: <span>E-Cart Retail</span>
                    </div>
                    <div class="seller-ratings">
                        <span>4.5★ Rating</span>
                        <span>87% Positive Ratings</span>
                        <span>Fast Delivery</span>
                    </div>
                </div>

                <!-- Product Highlights -->
                <div class="offers-section highlights-section">
                    <div class="offers-title">
                        <i class="fas fa-star"></i>
                        Highlights
                    </div>
                    <div class="highlights-grid">
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="description-content">
                                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Compatible with Windows, Mac, Linux</span>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Ergonomic Design for Comfortable Use</span>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Plug and Play - Easy Setup</span>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>1 Year Manufacturer Warranty</span>
                        </div>
                        <div class="highlight-item">
                            <div class="highlight-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <span>Complete Combo: Mouse + Keyboard + USB Hub</span>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="offers-section services-section">
                    <div class="offers-title">
                        <i class="fas fa-shield-alt"></i>
                        Services
                    </div>
                    <div class="services-grid">
                        <div class="service-item">
                            <div class="service-icon return">
                                <i class="fas fa-undo"></i>
                            </div>
                            <div class="service-content">
                                <div class="service-title">7 Days Return</div>
                                <div class="service-desc">Easy returns within 7 days</div>
                            </div>
                        </div>
                        <div class="service-item">
                            <div class="service-icon delivery">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="service-content">
                                <div class="service-title">Free Delivery</div>
                                <div class="service-desc">On orders above ₹500</div>
                            </div>
                        </div>
                        <div class="service-item">
                            <div class="service-icon support">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="service-content">   
                                <div class="service-title">24/7 Support</div>
                                <div class="service-desc">Round the clock assistance</div>
                            </div>
                        </div>
                        <div class="service-item">
                            <div class="service-icon warranty">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div class="service-content">
                                <div class="service-title">1 Year Warranty</div>
                                <div class="service-desc">Manufacturer warranty included</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Specifications Section -->
        <section class="specifications-section">
            <div class="specifications-header">
                Specifications
            </div>
            <div class="specifications-content">
                <table class="spec-table">
                    <tr>
                        <td>Product Name</td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                    </tr>
                    <tr>
                        <td>Availability</td>
                        <td><?php echo ((string)($product['status'] ?? '') === 'active') ? 'Available' : 'Unavailable'; ?></td>
                    </tr>
                    <tr>
                        <td>Price</td>
                        <td>₹<?php echo number_format((float)$product['price'], 2); ?></td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td><?php echo (float)($product['discount'] ?? 0); ?>%</td>
                    </tr>
                    <tr>
                        <td>Stock</td>
                        <td><?php echo (int)($product['stock'] ?? 0); ?></td>
                    </tr>
                    <tr>
                        <td>Images</td>
                        <td><?php echo !empty($productImages) ? count($productImages) . ' image(s)' : 'No image'; ?></td>
                    </tr>
                    <tr>
                        <td>Description</td>
                        <td><?php echo nl2br(htmlspecialchars((string)($product['description'] ?? ''))); ?></td>
                    </tr>
                </table>
            </div>
        </section>

        <!-- Reviews Section
        <section class="reviews-section">
            <div class="reviews-header">
                Reviews & Ratings 
                <?php if ($totalReviews > 0): ?>
                    <span style="font-size: 14px; color: #878787; font-weight: normal;">(<?php echo $totalReviews; ?> Review<?php echo $totalReviews > 1 ? 's' : ''; ?>)</span>
                <?php endif; ?>
            </div> -->
            
            <!-- Debug Info -->
            <!-- <div style="background: #f0f8ff; padding: 10px; margin: 10px 0; border-radius: 5px; font-size: 12px;">
                <strong>Debug Info:</strong><br>
                Product ID: <?php echo $debug_info['product_id'] ?? 'N/A'; ?><br>
                Query Executed: <?php echo $debug_info['query_executed'] ? 'Yes' : 'No'; ?><br>
                Rows Found: <?php echo $debug_info['num_rows'] ?? 0; ?><br>
                Total Reviews: <?php echo $totalReviews; ?><br>
                <?php if (isset($debug_info['fetched_reviews'])): ?>
                    Fetched: <?php echo count($debug_info['fetched_reviews']); ?> reviews
                <?php endif; ?>
            </div> -->
            
            <div class="reviews-content">
                <?php if (!empty($productReviews)): ?>
                    <?php foreach ($productReviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar"><?php echo strtoupper(substr($review['user_name'], 0, 1)); ?></div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                        <div class="review-date"><?php echo date('j M Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php 
                                    $stars = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $stars .= $i <= $review['rating'] ? '★' : '☆';
                                    }
                                    echo $stars;
                                    ?>
                                </div>
                            </div>
                            <div class="review-text">
                                <?php echo htmlspecialchars($review['review_text']); ?>
                            </div>
                            <?php 
                            
                            $reviewImages = json_decode($review['images'] ?? '[]', true);
                            if (!empty($reviewImages) && is_array($reviewImages)): 
                            ?>
                                <div class="review-images">
                                    <?php foreach ($reviewImages as $image): ?>
                                        <img src="../<?php echo htmlspecialchars($image); ?>" alt="Review Image" class="review-image" style="cursor: pointer;" onclick="window.open(this.src, '_blank')">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #878787;">
                        <i class="fas fa-star" style="font-size: 48px; margin-bottom: 16px; color: #ddd;"></i>
                        <h3 style="color: #666; margin-bottom: 8px;">No Reviews Yet</h3>
                        <p>Be the first to review this product!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Similar Products Section -->
        <section class="similar-products-section">
            <div class="similar-products-header">
                <i class="fas fa-th-large"></i>
                Similar Products
            </div>
            <div class="similar-products-grid">
                <!-- Product 1 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(1)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Mouse" alt="Wireless Mouse">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>Logitech</span>
                    </div>
                    <div class="similar-product-title">
                        Wireless Mouse with USB Receiver
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★☆</span>
                        <span class="similar-rating-count">(8,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹899</span>
                        <span class="similar-original-price">₹1,299</span>
                        <span class="similar-discount">31% off</span>
                    </div>
                </div>

                <!-- Product 2 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(2)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Keyboard" alt="Mechanical Keyboard">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>HP</span>
                    </div>
                    <div class="similar-product-title">
                        Mechanical Gaming Keyboard RGB
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(12,456)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹1,599</span>
                        <span class="similar-original-price">₹2,499</span>
                        <span class="similar-discount">36% off</span>
                    </div>
                </div>

                <!-- Product 3 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(3)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=USB Hub" alt="USB Hub">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>Portronics</span>
                    </div>
                    <div class="similar-product-title">
                        7 Port USB 3.0 Hub with Adapter
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★☆</span>
                        <span class="similar-rating-count">(5,678)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹699</span>
                        <span class="similar-original-price">₹999</span>
                        <span class="similar-discount">30% off</span>
                    </div>
                </div>

                <!-- Product 4 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(4)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Webcam" alt="Webcam">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>Zebronics</span>
                    </div>
                    <div class="similar-product-title">
                        HD 1080p Webcam with Microphone
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★☆</span>
                        <span class="similar-rating-count">(3,456)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹1,299</span>
                        <span class="similar-original-price">₹1,899</span>
                        <span class="similar-discount">32% off</span>
                    </div>
                </div>

                <!-- Product 5 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(5)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Headphone" alt="Headphone">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>JBL</span>
                    </div>
                    <div class="similar-product-title">
                        Wired Headphone with Mic
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(15,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹799</span>
                        <span class="similar-original-price">₹1,499</span>
                        <span class="similar-discount">47% off</span>
                    </div>
                </div>

                <!-- Product 6 -->
                <div class="similar-product-card" onclick="viewSimilarProduct(6)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Speaker" alt="Speaker">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>Intex</span>
                    </div>
                    <div class="similar-product-title">
                        2.1 Channel Multimedia Speakers
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★☆</span>
                        <span class="similar-rating-count">(7,890)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹1,199</span>
                        <span class="similar-original-price">₹1,799</span>
                        <span class="similar-discount">33% off</span>
                    </div>
                </div>

                 <div class="similar-product-card" onclick="viewSimilarProduct(7)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Headphone" alt="Headphone">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>JBL</span>
                    </div>
                    <div class="similar-product-title">
                        Wired Headphone with Mic
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(15,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹799</span>
                        <span class="similar-original-price">₹1,499</span>
                        <span class="similar-discount">47% off</span>
                    </div>
                </div>

               <div class="similar-product-card" onclick="viewSimilarProduct(9)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=WATCH" alt="WATCH">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>ROLEX</span>
                    </div>
                    <div class="similar-product-title">
                        Rolex Watch
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(15,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹1,299</span>
                        <span class="similar-original-price">₹5,499</span>
                        <span class="similar-discount">75% off</span>
                    </div>
                </div>

                 <div class="similar-product-card" onclick="viewSimilarProduct(5)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Headphone" alt="Vivo">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>VIVO</span>
                    </div>
                    <div class="similar-product-title">
                       VIvo Mobile Phone
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(15,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹15,999</span>
                        <span class="similar-original-price">₹25,999</span>
                        <span class="similar-discount">47% off</span>
                    </div>
                </div>

                 <div class="similar-product-card" onclick="viewSimilarProduct(5)">
                    <div class="similar-product-image">
                        <img src="https://via.placeholder.com/160x160/f0f0f0/878787?text=Headphone" alt="Apple">
                    </div>
                    <div class="similar-product-brand">
                        Brand: <span>APPLE</span>
                    </div>
                    <div class="similar-product-title">
                        Apple iPhone 15 Pro
                    </div>
                    <div class="similar-product-rating">
                        <span class="similar-rating-stars">★★★★★</span>
                        <span class="similar-rating-count">(15,234)</span>
                    </div>
                    <div class="similar-product-price">
                        <span class="similar-current-price">₹79,999</span>
                        <span class="similar-original-price">₹1,49,999</span>
                        <span class="similar-discount">47% off</span>
                    </div>
                </div>




            </div>
        </section>
    </div>

    <script>
        function setMainImage(src, el) {
            const main = document.getElementById('mainProductImage');
            if (main) {
                main.src = src;
            }

            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            if (el) {
                el.classList.add('active');
            }
        }

        // Thumbnail Image Switching
        document.querySelectorAll('.thumbnail').forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', function() {
                // Remove active class from all thumbnails
                document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                // Add active class to clicked thumbnail
                this.classList.add('active');
                
                // Change main image based on thumbnail
                const mainImage = document.querySelector('.main-image img');
                const thumbnailImg = this.querySelector('img');
                
                if (thumbnailImg) {
                    mainImage.src = thumbnailImg.src;
                    mainImage.alt = thumbnailImg.alt;
                    console.log('Main image changed to:', thumbnailImg.src);
                }
            });
        });

        // Add to Cart Function
        function addToCart() {
            // Show success message
            showNotification('Product added to cart successfully!', 'success');
            
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                const currentCount = parseInt(cartBadge.textContent);
                cartBadge.textContent = currentCount + 1;
            }
        }

        // Buy Now Function
        function buyNow() {
            // Redirect to checkout or payment page
            window.location.href = '../Account_Profile/Order_complete/select_address.php';
        }

        // Pincode Check Function
        document.querySelector('.check-btn').addEventListener('click', function() {
            const pincode = document.querySelector('.pincode-input').value;
            if (pincode && pincode.length === 6) {
                showNotification('Delivery available for pincode ' + pincode, 'success');
            } else {
                showNotification('Please enter a valid 6-digit pincode', 'error');
            }
        });

        // Wishlist Function
        document.querySelector('.share-button').addEventListener('click', function() {
            if (this.innerHTML.includes('Wishlist')) {
                this.innerHTML = '<i class="fas fa-heart" style="color: #ff6161;"></i> Wishlisted';
                showNotification('Product added to wishlist!', 'success');
            } else {
                this.innerHTML = '<i class="fas fa-heart"></i> Wishlist';
                showNotification('Product removed from wishlist!', 'info');
            }
        });

        // Share Function
        document.querySelectorAll('.share-button')[1].addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($product['name']); ?>',
                    text: 'Check out this amazing product on E-Cart',
                    url: window.location.href
                });
            } else {
                // Fallback - copy link to clipboard
                navigator.clipboard.writeText(window.location.href);
                showNotification('Product link copied to clipboard!', 'success');
            }
        });

        // Notification Function
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
                color: white;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 500;
                z-index: 9999;
                animation: slideIn 0.3s ease;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            notification.textContent = message;
            
            // Add animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Toggle Menu Function
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }

        // Image Zoom Function
        document.querySelector('.image-zoom-icon').addEventListener('click', function() {
            const mainImage = document.querySelector('.main-image img');
            // In real implementation, would open image in modal or lightbox
            showNotification('Image zoom feature coming soon!', 'info');
        });

        // Similar Products Click Function
        function viewSimilarProduct(productId) {
            // In real implementation, would navigate to product page
            showNotification(`Viewing product ${productId} details...`, 'info');
            // window.location.href = `product.php?id=${productId}`;
        }
    </script>
    
    <!-- Floating Home Button -->
    <a href="../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
