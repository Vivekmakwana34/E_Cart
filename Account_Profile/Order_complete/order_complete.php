<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = (int)$_POST['product_id'];
    $order_id = (int)$_POST['order_id'];
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $user_id = $_SESSION['user_id'];
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $review_error = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($review_text)) {
        $review_error = "Please write a review.";
    } else {
        // Handle image uploads
        $imagePaths = [];
        if (isset($_FILES['review_images']) && !empty($_FILES['review_images']['name'][0])) {
            $uploadDir = '../../assets/review_images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['review_images']['name'] as $key => $name) {
                if ($_FILES['review_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['review_images']['tmp_name'][$key];
                    $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_ext) && $_FILES['review_images']['size'][$key] <= 5 * 1024 * 1024) {
                        $new_filename = 'review_' . $user_id . '_' . $product_id . '_' . time() . '_' . $key . '.' . $file_ext;
                        if (move_uploaded_file($tmp_name, $uploadDir . $new_filename)) {
                            $imagePaths[] = 'assets/review_images/' . $new_filename;
                        }
                    }
                }
            }
        }
        
        $images_json = !empty($imagePaths) ? json_encode($imagePaths) : null;
        
        // Insert review into database
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, review_text, images, status, helpful_count, created_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', 0, NOW())");
        $stmt->bind_param("iiiiss", $user_id, $product_id, $order_id, $rating, $review_text, $images_json);
        
        if ($stmt->execute()) {
            $review_success = "Thank you! Your review has been submitted successfully.";
        } else {
            $review_error = "Error submitting review. Please try again.";
        }
        $stmt->close();
    }
}

// Check if viewing specific order from profile
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$orderFromDB = null;

if ($orderId > 0) {
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $orderId, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $orderFromDB = $res ? $res->fetch_assoc() : null;
}

// Load cart items and pricing (from DB order if available, otherwise from session)
if ($orderFromDB) {
    // Get order items from database
    $cartItems = [];
    $itemsQuery = $conn->prepare("SELECT oi.*, p.name as product_name, p.image as product_image, p.description as product_description, p.price as product_price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $itemsQuery->bind_param("i", $orderFromDB['id']);
    $itemsQuery->execute();
    $itemsResult = $itemsQuery->get_result();
    
    while ($item = $itemsResult->fetch_assoc()) {
        $cartItems[] = [
            'product_id' => $item['product_id'],
            'name' => $item['product_name'],
            'image' => $item['product_image'],
            'description' => $item['product_description'],
            'price' => $item['product_price'],
            'quantity' => $item['quantity']
        ];
    }
    $itemsQuery->close();
    $cartSubtotal = $orderFromDB['total_amount'] ?? 0;
    $regularDiscount = 0; // TODO: Load from order if discount stored
    $payableBeforeCoupon = $cartSubtotal;
    $paymentMethod = $orderFromDB['payment_method'] ?? 'card';
    $selectedAddress = null; // Parse from shipping_address if needed
} else {
    // Load from session (new order flow)
    $cartItems = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $cartItemCount = 0;
    $cartSubtotal = 0.0;
    foreach ($cartItems as $it) {
        $qty = (int)($it['quantity'] ?? 0);
        $price = (float)($it['price'] ?? 0);
        if ($qty <= 0) continue;
        $cartItemCount += $qty;
        $cartSubtotal += $price * $qty;
    }
    $regularDiscount = $cartSubtotal * 0.30;
    $payableBeforeCoupon = max(0, $cartSubtotal - $regularDiscount);

    // Load selected address
    $selectedAddressId = (int)($_SESSION['selectedAddress'] ?? 0);
    $selectedAddress = null;
    if ($selectedAddressId > 0) {
        $stmt = $conn->prepare('SELECT * FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $selectedAddressId, $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $selectedAddress = $res ? $res->fetch_assoc() : null;
    }

    // Load payment method from session (set by payment.php)
    $paymentMethod = $_SESSION['paymentMethod'] ?? 'card';
}

// Load user details
$user = null;
$stmt = $conn->prepare('SELECT full_name, email FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Complete - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">
    <style>
        /* Override any external backgrounds */
        body {
            background: #f8f9fa !important;
            margin: 0;
            padding: 0;
        }
        
        /* Order Complete Page - Modern Professional Design */
        .order-complete-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px 40px;
            background: transparent;
            min-height: calc(100vh - 200px);
            position: relative;
        }

        /* Top Section Layout - Order Items + Tracking */
        .top-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Success Section - Removed */
        .success-section {
            display: none;
        }

        /* Order Details Section */
        .order-details-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px;
            margin: 40px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 32px;
            position: relative;
            padding-bottom: 16px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            border-radius: 2px;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
        }

        .detail-card {
            background: linear-gradient(135deg, rgba(40, 116, 240, 0.05), rgba(94, 146, 243, 0.05));
            padding: 24px;
            border-radius: 16px;
            border: 1px solid rgba(40, 116, 240, 0.1);
        }

        .detail-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .detail-card h3 i {
            color: #2874f0;
            font-size: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(40, 116, 240, 0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }

        .detail-value {
            font-size: 14px;
            color: #2d3748;
            font-weight: 600;
        }

        /* Order Items Section - Top Section */
        .order-items-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px;
            margin: 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .order-items-grid {
            display: grid;
            gap: 24px;
        }

        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f3f6;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .item-specs {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 18px;
            font-weight: 700;
            color: #2874f0;
        }

        .item-quantity {
            font-size: 14px;
            color: #4a5568;
            margin-left: 12px;
        }

        /* Action Buttons Section */
        .actions-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px;
            margin: 40px 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .actions-grid {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: none;
            min-width: 180px;
            justify-content: center;
        }

        .action-button.primary {
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            color: white;
            box-shadow: 0 8px 24px rgba(40, 116, 240, 0.3);
        }

        .action-button.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(40, 116, 240, 0.4);
        }

        .action-button.secondary {
            background: white;
            color: #2874f0;
            border: 2px solid #2874f0;
            box-shadow: none;
        }

        .action-button.secondary:hover {
            background: rgba(40, 116, 240, 0.1);
            transform: translateY(-2px);
        }

        /* Tracking Section - Modern Order Tracking */
        .tracking-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 40px;
            margin: 0;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .tracking-container {
            max-width: 100%;
        }

        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(40, 116, 240, 0.1);
        }

        .tracking-info {
            flex: 1;
        }

        .tracking-number {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .tracking-status {
            font-size: 18px;
            font-weight: 600;
            color: #2874f0;
            margin-bottom: 4px;
        }

        .tracking-eta {
            font-size: 14px;
            color: #4a5568;
        }

        .tracking-actions {
            display: flex;
            gap: 12px;
        }

        .track-btn {
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(40, 116, 240, 0.3);
        }

        .track-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 116, 240, 0.4);
        }

        .track-btn.secondary {
            background: white;
            color: #2874f0;
            border: 2px solid #2874f0;
            box-shadow: none;
        }

        .track-btn.secondary:hover {
            background: rgba(40, 116, 240, 0.1);
        }

        .tracking-timeline {
            position: relative;
            padding: 0;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .tracking-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 24px;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2874f0, #5e92f3);
            border-radius: 2px;
            z-index: 1;
        }

        .tracking-step {
            position: relative;
            flex: 1;
            text-align: center;
            padding: 0 10px;
            z-index: 2;
        }

        .tracking-step:hover {
            transform: translateY(-2px);
        }

        .tracking-step.completed {
            color: #4caf50;
        }

        .tracking-step.active {
            color: #2874f0;
        }

        .tracking-step.pending {
            color: #e0e0e0;
            opacity: 0.7;
        }

        .step-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin: 0 auto 12px;
            background: white;
            border: 3px solid;
            transition: all 0.3s ease;
        }

        .tracking-step.completed .step-icon {
            border-color: #4caf50;
            color: #4caf50;
        }

        .tracking-step.active .step-icon {
            border-color: #2874f0;
            color: #2874f0;
            animation: pulse 2s infinite;
        }

        .tracking-step.pending .step-icon {
            border-color: #e0e0e0;
            color: #e0e0e0;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 116, 240, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(40, 116, 240, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 116, 240, 0); }
        }
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .tracking-step.completed .step-icon {
            background: linear-gradient(135deg, #4caf50, #8bc34a);
        }

        .tracking-step.active .step-icon {
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            animation: pulse 2s infinite;
        }

        .tracking-step.pending .step-icon {
            background: #e0e0e0;
            color: #9e9e9e;
        }

        .step-icon.processing {
            animation: spin 2s linear infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .step-content {
            padding-left: 20px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .step-description {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .step-time {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
        }

        /* Tracking Enhancements */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge.in-transit {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
        }
        
        .status-badge.delivered {
            background: linear-gradient(135deg, #4caf50, #8bc34a);
            color: white;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        /* Rating Popup Styles */
        .rating-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .rating-popup {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .popup-header h3 {
            color: #333;
            font-size: 1.5rem;
            margin: 0;
        }

        .close-popup {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-popup:hover {
            background: #f0f0f0;
            color: #333;
        }

        .popup-content p {
            color: #666;
            margin-bottom: 20px;
            text-align: center;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .product-details h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .product-details p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .rating-section {
            margin-bottom: 25px;
        }

        .rating-section label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .star-rating {
            display: flex;
            gap: 5px;
            font-size: 2rem;
            justify-content: center;
        }

        .star {
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .star:hover,
        .star.active {
            color: #ffc107;
            transform: scale(1.1);
        }

        .review-section {
            margin-bottom: 25px;
        }

        .review-section label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .review-section textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            resize: vertical;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .review-section textarea:focus {
            outline: none;
            border-color: #2874f0;
        }

        .popup-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-skip,
        .btn-submit {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-skip {
            background: #f0f0f0;
            color: #666;
        }

        .btn-skip:hover {
            background: #e0e0e0;
        }

        .btn-submit {
            background: linear-gradient(135deg, #2874f0, #5e92f3);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40, 116, 240, 0.3);
        }

        /* Mobile Responsive for Popup */
        @media (max-width: 600px) {
            .rating-popup {
                padding: 20px;
                margin: 20px;
            }

            .popup-actions {
                flex-direction: column;
            }

            .btn-skip,
            .btn-submit {
                width: 100%;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .order-complete-container {
                padding: 0 12px;
            }

            .top-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .order-details-section,
            .actions-section {
                padding: 24px 20px;
            }

            .order-items-section,
            .tracking-section {
                padding: 24px 20px;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                margin: 0 auto;
            }

            .actions-grid {
                flex-direction: column;
                align-items: center;
            }

            .action-button {
                width: 100%;
                max-width: 280px;
            }
        }
        
        /* Full Width Footer */
        .site-footer {
            width: 100vw !important;
            max-width: 100vw !important;
            position: relative;
            left: 50%;
            right: 50%;
            margin-left: -50vw !important;
            margin-right: -50vw !important;
        }
        
        .footer-inner {
            max-width: 1400px !important;
            margin: 0 auto !important;
            width: 100% !important;
            padding: 40px 20px !important;
        }
    </style>
</head>
<body>

    <?php include '../../Header.php'; ?>


    <!-- Order Complete Main Content -->
    <main class="order-complete-container">
        <!-- Top Section: Order Items + Tracking -->
        <div class="top-section">
            <!-- Order Items Section - Smaller -->
            <section class="order-items-section">
                <h2 class="section-title">Order Items</h2>
                <div class="order-items-grid">
                    <?php if (!empty($cartItems)): ?>
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image'] ?? '../../assets/img/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name'] ?? 'Product'); ?>" class="item-image">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name'] ?? 'Product'); ?></div>
                                    <div class="item-specs"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                    <div class="item-price">₹<?php echo number_format((float)($item['price'] ?? 0), 0); ?> <span class="item-quantity">Qty: <?php echo (int)($item['quantity'] ?? 1); ?></span></div>
                                    <button type="button" class="rate-product-btn" onclick="openRatingPopup(<?php echo (int)($item['product_id'] ?? 0); ?>, <?php echo (int)($orderFromDB['id'] ?? 0); ?>, '<?php echo addslashes(htmlspecialchars($item['name'] ?? 'Product')); ?>')" style="margin-top: 10px; background: #ff9800; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 500;">
                                        <i class="fas fa-star"></i> Rate This Product
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 20px; color: #878787;">No items in cart.</div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Order Details Section - Replace Tracking -->
            <section class="tracking-section">
                <h2 class="section-title">Order Details</h2>
                <div class="details-container" style="background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2874f0;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Order Number</div>
                            <div style="font-size: 16px; font-weight: 600; color: #333;"><?php echo $orderFromDB ? htmlspecialchars($orderFromDB['order_number']) : 'EC' . date('ymdHis'); ?></div>
                        </div>
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #4caf50;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Order Status</div>
                            <div style="font-size: 16px; font-weight: 600; color: #4caf50;"><?php echo $orderFromDB ? ucfirst($orderFromDB['order_status']) : 'Pending'; ?></div>
                        </div>
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #ff9800;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Payment Method</div>
                            <div style="font-size: 16px; font-weight: 600; color: #333;"><?php 
                                $methodMap = ['card' => 'Credit/Debit Card', 'upi' => 'UPI', 'wallet' => 'Wallet', 'cod' => 'Cash on Delivery'];
                                echo htmlspecialchars($methodMap[$paymentMethod] ?? 'Credit/Debit Card'); 
                            ?></div>
                        </div>
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #9c27b0;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Total Amount</div>
                            <div style="font-size: 16px; font-weight: 600; color: #333;">Rs. <?php echo number_format($payableBeforeCoupon, 0); ?></div>
                        </div>
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2196f3;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Order Date</div>
                            <div style="font-size: 16px; font-weight: 600; color: #333;"><?php echo date('d M Y', strtotime($orderFromDB['created_at'] ?? 'now')); ?></div>
                        </div>
                        <div class="detail-item" style="padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #00bcd4;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Estimated Delivery</div>
                            <div style="font-size: 16px; font-weight: 600; color: #333;"><?php echo date('d M Y', strtotime('+3 days')); ?></div>
                        </div>
                    </div>
                    
                    <?php if ($orderFromDB && !empty($orderFromDB['shipping_address'])): ?>
                    <div style="margin-top: 25px; padding: 20px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid #2874f0;">
                        <div style="font-size: 14px; font-weight: 600; color: #2874f0; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> Shipping Address
                        </div>
                        <div style="color: #333; line-height: 1.5;"><?php echo htmlspecialchars($orderFromDB['shipping_address']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        <!-- Order Details Section -->
        <section class="order-details-section">
            <h2 class="section-title">Order Details</h2>
            <div class="order-details-grid">
                <div class="detail-card">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                    <?php if ($selectedAddress): ?>
                        <div class="detail-item">
                            <span class="detail-label">Address</span>
                            <span class="detail-value" style="text-align: right;"><?php echo htmlspecialchars(trim(($selectedAddress['address_line1'] ?? '').' '.($selectedAddress['address_line2'] ?? ''))); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">City</span>
                            <span class="detail-value"><?php echo htmlspecialchars($selectedAddress['city'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pincode</span>
                            <span class="detail-value"><?php echo htmlspecialchars($selectedAddress['postal_code'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">State</span>
                            <span class="detail-value"><?php echo htmlspecialchars($selectedAddress['state'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Call</span>
                            <span class="detail-value">
                                <a href="tel:+91<?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $selectedAddress['phone'] ?? '')); ?>" style="background: linear-gradient(135deg, #2874f0, #5e92f3); color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(40, 116, 240, 0.3); transition: all 0.3s ease;">
                                    <i class="fas fa-phone" style="font-size: 12px;"></i>
                                    +91 <?php echo htmlspecialchars(preg_replace('/[^0-9]/', '', $selectedAddress['phone'] ?? '')); ?>
                                </a>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="detail-item">
                            <span class="detail-value" style="color:#878787;">No address selected</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <div class="detail-item">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value"><?php 
                            $methodMap = ['card' => 'Credit/Debit Card', 'upi' => 'UPI', 'wallet' => 'Wallet', 'cod' => 'Cash on Delivery'];
                            echo htmlspecialchars($methodMap[$paymentMethod] ?? 'Credit/Debit Card'); 
                        ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value" style="color: #4caf50;">Paid</span>
                    </div>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    <div class="detail-item">
                        <span class="detail-label">Subtotal</span>
                        <span class="detail-value">₹<?php echo number_format($cartSubtotal, 0); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Delivery Charges</span>
                        <span class="detail-value">₹0</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Discount</span>
                        <span class="detail-value" style="color: #4caf50;">-₹<?php echo number_format($regularDiscount, 0); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label"><strong>Total Amount</strong></span>
                        <span class="detail-value" id="finalTotalAmount" style="color: #2874f0; font-weight: 700;">₹<?php echo number_format($payableBeforeCoupon, 0); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action Buttons Section -->
        <section class="actions-section">
            <div class="actions-grid">
                <a href="../../index.php" class="action-button primary">
                    <i class="fas fa-home"></i>
                    Continue Shopping
                </a>
                <a href="order.php" class="action-button secondary">
                    <i class="fas fa-box"></i>
                    View All Orders
                </a>
                <a href="../Profile/profile.php" class="action-button secondary">
                    <i class="fas fa-user"></i>
                    My Account
                </a>
            </div>
        </section>
    </main>
        
<?php include '../../Footer.php'; ?>

    <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>

    <!-- Rating Popup Modal -->
    <div id="ratingPopup" class="rating-popup-overlay" style="display: none;">
        <div class="rating-popup-content">
            <div class="rating-popup-header">
                <h3>Rate Your Order</h3>
                <button onclick="closeRatingPopup()" class="close-btn">&times;</button>
            </div>
            <div class="rating-popup-body">
                <div class="product-info" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div class="product-name" id="popupProductName" style="font-weight: 600; color: #333; margin-bottom: 5px;"></div>
                    <div style="font-size: 14px; color: #666;">How would you rate your experience with this product?</div>
                </div>
                <div class="star-rating">
                    <span class="star" data-rating="1"><i class="fas fa-star"></i></span>
                    <span class="star" data-rating="2"><i class="fas fa-star"></i></span>
                    <span class="star" data-rating="3"><i class="fas fa-star"></i></span>
                    <span class="star" data-rating="4"><i class="fas fa-star"></i></span>
                    <span class="star" data-rating="5"><i class="fas fa-star"></i></span>
                </div>
                <div id="ratingText" class="rating-text"></div>
                <form method="POST" action="" id="reviewForm" enctype="multipart/form-data" style="margin-top: 20px;">
                    <input type="hidden" name="product_id" id="popupProductId" value="">
                    <input type="hidden" name="order_id" id="popupOrderId" value="">
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                    <div class="form-group">
                        <textarea name="review_text" placeholder="Share your experience with this product..." required style="width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit;"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Add Photos (Optional)</label>
                        <div class="image-upload-area" style="border: 2px dashed #ddd; border-radius: 8px; padding: 20px; text-align: center; background: #fafafa; cursor: pointer;" onclick="document.getElementById('reviewImages').click()">
                            <i class="fas fa-camera" style="font-size: 24px; color: #999; margin-bottom: 8px;"></i>
                            <div style="color: #666; font-size: 14px;">Click to upload photos</div>
                            <div style="color: #999; font-size: 12px; margin-top: 5px;">JPG, PNG up to 5MB</div>
                            <input type="file" id="reviewImages" name="review_images[]" multiple accept="image/*" style="display: none;" onchange="handleImageSelect(event)">
                        </div>
                        <div id="imagePreview" style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;"></div>
                    </div>
                    <button type="submit" name="submit_review" class="submit-rating-btn" style="background: #ff9800; color: white; border: none; padding: 12px 30px; border-radius: 25px; font-weight: 600; cursor: pointer; margin-top: 10px;">Submit Review</button>
                </form>
            </div>
        </div>
    </div>

    <style>
    .rating-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    .rating-popup-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 450px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        position: relative;
    }
    .rating-popup-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .rating-popup-header h3 {
        margin: 0;
        color: #333;
        font-size: 20px;
    }
    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #666;
    }
    .close-btn:hover {
        color: #ff9800;
    }
    .star-rating {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 20px 0;
    }
    .star {
        font-size: 32px;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s;
    }
    .star:hover,
    .star.active {
        color: #ff9800;
        transform: scale(1.1);
    }
    .rating-text {
        text-align: center;
        color: #ff9800;
        font-weight: 600;
        min-height: 24px;
        margin-bottom: 15px;
    }
    </style>

    <script>
    // Rating popup functions
    let selectedRating = 0;
    
    function openRatingPopup(productId, orderId, productName) {
        document.getElementById('popupProductId').value = productId;
        document.getElementById('popupOrderId').value = orderId;
        document.getElementById('popupProductName').textContent = productName || 'Product';
        document.getElementById('ratingPopup').style.display = 'flex';
        selectedRating = 0;
        updateStars(0);
        document.getElementById('ratingInput').value = 0;
        document.getElementById('ratingText').textContent = '';
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('reviewImages').value = '';
    }
    
    function closeRatingPopup() {
        document.getElementById('ratingPopup').style.display = 'none';
    }
    
    function updateStars(rating) {
        const stars = document.querySelectorAll('.star');
        const ratingTexts = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
                star.innerHTML = '<i class="fas fa-star"></i>';
            } else {
                star.classList.remove('active');
                star.innerHTML = '<i class="far fa-star"></i>';
            }
        });
        
        if (rating > 0) {
            document.getElementById('ratingText').textContent = ratingTexts[rating - 1];
            document.getElementById('ratingInput').value = rating;
        } else {
            document.getElementById('ratingText').textContent = '';
        }
    }
    
    // Handle image selection
    function handleImageSelect(event) {
        const files = event.target.files;
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/') && file.size <= 5 * 1024 * 1024) { // 5MB limit
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.style.position = 'relative';
                    imgContainer.style.display = 'inline-block';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '80px';
                    img.style.height = '80px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid #ddd';
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.innerHTML = '×';
                    removeBtn.style.position = 'absolute';
                    removeBtn.style.top = '-5px';
                    removeBtn.style.right = '-5px';
                    removeBtn.style.background = '#ff4444';
                    removeBtn.style.color = 'white';
                    removeBtn.style.border = 'none';
                    removeBtn.style.borderRadius = '50%';
                    removeBtn.style.width = '20px';
                    removeBtn.style.height = '20px';
                    removeBtn.style.cursor = 'pointer';
                    removeBtn.style.fontSize = '12px';
                    removeBtn.onclick = function() {
                        imgContainer.remove();
                    };
                    
                    imgContainer.appendChild(img);
                    imgContainer.appendChild(removeBtn);
                    preview.appendChild(imgContainer);
                };
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Star click handlers
    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.rating);
            updateStars(selectedRating);
        });
        
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            updateStars(hoverRating);
        });
    });
    
    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        updateStars(selectedRating);
    });
    
    // Review form validation
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        const rating = document.getElementById('ratingInput').value;
        if (rating === '0' || rating === '') {
            e.preventDefault();
            alert('Please select a rating before submitting your review.');
            return false;
        }
    });
    </script>
</body>
</html>
