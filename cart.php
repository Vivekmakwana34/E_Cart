<?php
session_start();
include 'Database_file/config.php';

// Handle add to cart
if (isset($_POST['add_to_cart']) && $_POST['add_to_cart'] == 1) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Check if product already in cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = array(
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'quantity' => $quantity
        );
    }
}

// Handle remove from cart
if (isset($_POST['remove_from_cart']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if (isset($_POST['update_quantity']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
    header('Location: cart.php');
    exit();
}

// Handle Apply Coupon
$coupon_error = '';
$coupon_success = '';
$discount_amount = 0;

if (isset($_POST['apply_coupon']) && isset($_POST['coupon_code'])) {
    $coupon_code = strtoupper(trim($_POST['coupon_code']));
    
    if (!empty($coupon_code)) {
        // Check coupon validity
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $coupon_code);
        $stmt->execute();
        $coupon_result = $stmt->get_result();
        $coupon = $coupon_result->fetch_assoc();
        
        if ($coupon) {
            $today = date('Y-m-d');
            
            // Check if coupon already used by user
            if (isset($_SESSION['user_id'])) {
                $check_used = $conn->prepare("SELECT uc.is_used FROM user_coupons uc JOIN coupons c ON uc.coupon_id = c.id WHERE uc.user_id = ? AND c.code = ? AND uc.is_used = 1");
                $check_used->bind_param('is', $_SESSION['user_id'], $coupon_code);
                $check_used->execute();
                $used_result = $check_used->get_result();
                if ($used_result->num_rows > 0) {
                    $coupon_error = 'You have already used this coupon!';
                }
            }
            
            // Only continue if no error
            if (empty($coupon_error)) {
                // Check expiry
                if ($coupon['valid_until'] < $today) {
                    $coupon_error = 'Coupon has expired!';
                } elseif ($coupon['valid_from'] > $today) {
                    $coupon_error = 'Coupon is not valid yet!';
                } elseif ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
                    $coupon_error = 'Coupon usage limit reached!';
                } else {
                // Calculate cart total
                $cart_total = 0;
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as $item) {
                        $cart_total += $item['price'] * $item['quantity'];
                    }
                }
                
                // Check minimum order amount
                if ($cart_total < $coupon['min_order_amount']) {
                    $coupon_error = 'Minimum order amount of ₹' . $coupon['min_order_amount'] . ' required!';
                } else {
                    // Apply coupon
                    $_SESSION['applied_coupon'] = $coupon;
                    $coupon_success = 'Coupon applied successfully! You saved ';
                    
                    // Calculate discount
                    if ($coupon['discount_type'] == 'percentage') {
                        $discount_amount = $cart_total * ($coupon['discount_value'] / 100);
                        if ($coupon['max_discount'] && $discount_amount > $coupon['max_discount']) {
                            $discount_amount = $coupon['max_discount'];
                        }
                        $coupon_success .= '₹' . number_format($discount_amount, 2) . ' (' . $coupon['discount_value'] . '%)';
                    } else {
                        $discount_amount = $coupon['discount_value'];
                        $coupon_success .= '₹' . number_format($discount_amount, 2);
                    }
                    }
                }
            }
        } else {
            $coupon_error = 'Invalid coupon code!';
        }
    }
}

// Handle Remove Coupon
if (isset($_POST['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
    header('Location: cart.php');
    exit();
}

// Fetch available coupons to display
$available_coupons = [];
if (isset($conn)) {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE is_active = 1 AND valid_from <= ? AND valid_until >= ? AND (usage_limit IS NULL OR usage_count < usage_limit) ORDER BY discount_value DESC LIMIT 5");
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    $coupons_result = $stmt->get_result();
    while ($row = $coupons_result->fetch_assoc()) {
        $available_coupons[] = $row;
    }
}

// Calculate cart totals
$cart_total = 0;
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_total += $item['price'] * $item['quantity'];
        $cart_count += $item['quantity'];
    }
}

// Fetch shipping zones from database
$shipping_zones = [];
$selected_city = isset($_POST['delivery_city']) ? $_POST['delivery_city'] : (isset($_SESSION['delivery_city']) ? $_SESSION['delivery_city'] : '');
$shipping_charge = 0;

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM shipping_zones WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $shipping_zones[] = $row;
    }
    $stmt->close();
}

// Calculate shipping charge based on selected city
if ($selected_city && !empty($shipping_zones)) {
    foreach ($shipping_zones as $zone) {
        $locations = explode(',', $zone['locations']);
        $locations = array_map('trim', $locations);
        $locations = array_map('strtolower', $locations);
        if (in_array(strtolower($selected_city), $locations)) {
            $shipping_charge = $zone['shipping_charge'];
            $_SESSION['shipping_zone'] = $zone['zone_name'];
            $_SESSION['shipping_charge'] = $shipping_charge;
            break;
        }
    }
    $_SESSION['delivery_city'] = $selected_city;
}

// Calculate discount if coupon applied
$discount_amount = 0;
if (isset($_SESSION['applied_coupon'])) {
    $coupon = $_SESSION['applied_coupon'];
    if ($coupon['discount_type'] == 'percentage') {
        $discount_amount = $cart_total * ($coupon['discount_value'] / 100);
        if ($coupon['max_discount'] && $discount_amount > $coupon['max_discount']) {
            $discount_amount = $coupon['max_discount'];
        }
    } else {
        $discount_amount = $coupon['discount_value'];
    }
    // Ensure discount doesn't exceed cart total
    if ($discount_amount > $cart_total) {
        $discount_amount = $cart_total;
    }
}

$final_total = $cart_total - $discount_amount + $shipping_charge;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">
    <style>
        body {
            margin: 0;
            background-color: #f1f3f6;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .cart-page {
            max-width: 1200px;
            margin: 20px auto 40px;
            padding: 0 12px;
        }

        .cart-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .cart-page-header h1 {
            font-size: 20px;
            margin: 0;
        }

        .cart-delivery-text {
            font-size: 13px;
            color: #555;
        }

        .cart-delivery-text i {
            color: #2874f0;
            margin-right: 4px;
        }

        .cart-layout {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .cart-items-panel {
            flex: 2.2;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .cart-items-header {
            padding: 12px 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: 500;
        }

        .cart-secure {
            font-size: 12px;
            color: #4caf50;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .cart-secure i {
            color: #4caf50;
        }

        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-item:last-of-type {
            border-bottom: none;
        }

        .cart-item-image {
            width: 96px;
            flex-shrink: 0;
        }

        .cart-item-image img {
            width: 100%;
            border-radius: 6px;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cart-item-title {
            font-size: 15px;
            font-weight: 500;
            margin: 0;
        }

        .cart-item-meta {
            font-size: 12px;
            color: #777;
        }

        .cart-item-price-row {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-top: 4px;
        }

        .cart-price-current {
            font-size: 16px;
            font-weight: 600;
        }

        .cart-price-original {
            font-size: 13px;
            color: #878787;
            text-decoration: line-through;
        }

        .cart-price-off {
            font-size: 13px;
            color: #388e3c;
            font-weight: 500;
        }

        .cart-item-delivery {
            font-size: 12px;
            color: #555;
        }

        .cart-item-delivery span {
            color: #388e3c;
            font-weight: 500;
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .cart-qty {
            display: inline-flex;
            align-items: center;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .cart-qty button {
            border: none;
            background: #f5f5f5;
            width: 28px;
            height: 28px;
            cursor: pointer;
            font-size: 16px;
        }

        .cart-qty span {
            width: 32px;
            text-align: center;
            font-size: 14px;
        }

        .cart-action-link {
            border: none;
            background: none;
            font-size: 12px;
            font-weight: 600;
            color: #2874f0;
            cursor: pointer;
            padding: 0;
        }

        
        .cart-place-order-btn {
            padding: 10px 24px;
            border-radius: 4px;
            border: none;
            background-color: #fb641b;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .cart-place-order-btn:hover {
            background-color: #e65a18;
        }

        .cart-summary-panel {
            flex: 1;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            padding: 12px 16px 16px;
        }

        .cart-summary-title {
            font-size: 14px;
            font-weight: 600;
            color: #878787;
            margin-bottom: 8px;
        }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin: 6px 0;
        }

        .cart-summary-row.discount span:last-child {
            color: #388e3c;
        }

        .cart-summary-divider {
            border-top: 1px dashed #e0e0e0;
            margin: 8px 0;
        }

        .cart-summary-total-row {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            font-size: 14px;
            margin: 8px 0;
        }

        .cart-summary-savings {
            font-size: 12px;
            color: #388e3c;
            margin-top: 4px;
        }

        /* Coupon Section Styles */
        .cart-coupon-section {
            margin: 12px 0;
            padding: 12px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .coupon-input-wrapper {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .coupon-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .coupon-input:focus {
            border-color: #2874f0;
            box-shadow: 0 0 0 2px rgba(40, 116, 240, 0.1);
        }

        .coupon-apply-btn {
            padding: 10px 16px;
            background-color: #2874f0;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .coupon-apply-btn:hover {
            background-color: #1d4ed8;
        }

        .coupon-message {
            font-size: 13px;
            padding: 6px 8px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .coupon-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .coupon-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .available-coupons-link {
            text-align: right;
        }

        .available-coupons-link a {
            font-size: 12px;
            color: #2874f0;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.2s ease;
        }

        .available-coupons-link a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .available-coupons-link i {
            font-size: 11px;
        }

        .cart-empty-state {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px 16px;
            text-align: center;
            margin-top: 16px;
            display: none;
        }

        .cart-empty-state h2 {
            margin: 12px 0 4px;
            font-size: 18px;
        }

        .cart-empty-state p {
            font-size: 13px;
            color: #555;
            margin: 0 0 12px;
        }

        .cart-empty-btn {
            padding: 8px 20px;
            border-radius: 4px;
            border: none;
            background-color: #2874f0;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
        }

        @media (max-width: 900px) {
            .cart-layout {
                flex-direction: column;
            }

            .cart-summary-panel {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .cart-page {
                margin-top: 12px;
            }

            .cart-page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .cart-item {
                padding: 12px;
            }

            .cart-item-image {
                width: 80px;
            }
        }
    </style>
</head>

<body>
    <?php include 'Header.php'; ?>

    <main>
        <div class="cart-page">
            <div class="cart-page-header">
                <h1>My Cart (<?php echo isset($total_items) ? $total_items : 0; ?> item<?php echo isset($total_items) && $total_items > 1 ? 's' : ''; ?>)</h1>
                <div class="cart-delivery-text">
                    <i class="fa-solid fa-location-dot"></i>
                    Deliver to <strong>Mumbai, 400001</strong>
                </div>
            </div>

            <div class="cart-layout">
                <section class="cart-items-panel">
                    <div class="cart-items-header">
                        <span>Items in your cart</span>
                        <span class="cart-secure">
                            <i class="fa-solid fa-shield-halved"></i>
                            100% Secure Payments
                        </span>
                    </div>

                    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                        <?php 
                        $total_price = 0;
                        $total_items = 0;
                        foreach ($_SESSION['cart'] as $item):
                            $item_total = $item['price'] * $item['quantity'];
                            $total_price += $item_total;
                            $total_items += $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <img src="assets/img/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="cart-item-details">
                                <h2 class="cart-item-title"><?php echo htmlspecialchars($item['name']); ?></h2>
                                <div class="cart-item-meta">Seller: E-Cart Retail</div>
                                <div class="cart-item-price-row">
                                    <span class="cart-price-current">₹<?php echo number_format($item['price']); ?></span>
                                    <span class="cart-price-original">₹<?php echo number_format($item['price'] * 1.3); ?></span>
                                    <span class="cart-price-off">30% off</span>
                                </div>
                                <p class="cart-item-delivery">Delivery by Tomorrow | <span>Free</span></p>
                                <div class="cart-item-actions">
                                    <div class="cart-qty">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="update_quantity" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                            <button type="submit" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>-</button>
                                        </form>
                                        <span><?php echo $item['quantity']; ?></span>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="update_quantity" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                            <button type="submit">+</button>
                                        </form>
                                    </div>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="remove_from_cart" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="cart-action-link">REMOVE</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="cart-empty-state">
                            <i class="fa-regular fa-face-smile" style="font-size: 32px; color: #2874f0;"></i>
                            <h2>Your cart is empty!</h2>
                            <p>Browse our categories and discover best offers for you.</p>
                            <button type="button" class="cart-empty-btn" onclick="window.location.href='index.php'">Shop Now</button>
                        </div>
                    <?php endif; ?>

                </section>

                <aside class="cart-summary-panel">
                    <div class="cart-summary-title">PRICE DETAILS</div>
                    <div class="cart-summary-divider"></div>
                    
                    <!-- Delivery City Selection -->
                    <div style="background: #f8fafc; border-radius: 8px; padding: 12px; margin: 12px 0;">
                        <form method="POST" action="cart.php" id="deliveryCityForm">
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #374151;">
                                <i class="fa-solid fa-location-dot" style="color: #2874f0; margin-right: 6px;"></i>
                                Select Delivery City
                            </label>
                            <select name="delivery_city" id="deliveryCity" onchange="this.form.submit()" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; margin-bottom: 8px;">
                                <option value="">-- Select your city --</option>
                                <?php foreach ($shipping_zones as $zone): ?>
                                    <?php $cities = explode(',', $zone['locations']); ?>
                                    <?php foreach ($cities as $city): ?>
                                        <?php $city = trim($city); ?>
                                        <?php if ($city): ?>
                                            <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $selected_city === $city ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($city); ?> (<?php echo $zone['zone_name']; ?> - ₹<?php echo number_format($zone['shipping_charge'], 0); ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($selected_city && $shipping_charge > 0): ?>
                                <div style="font-size: 13px; color: #16a34a; background: #dcfce7; padding: 8px 12px; border-radius: 6px;">
                                    <i class="fa-solid fa-check-circle"></i> 
                                    Shipping to <?php echo htmlspecialchars($selected_city); ?> - ₹<?php echo number_format($shipping_charge, 2); ?>
                                </div>
                            <?php elseif ($selected_city && $shipping_charge == 0): ?>
                                <div style="font-size: 13px; color: #16a34a; background: #dcfce7; padding: 8px 12px; border-radius: 6px;">
                                    <i class="fa-solid fa-gift"></i> 
                                    Free shipping to <?php echo htmlspecialchars($selected_city); ?>!
                                </div>
                            <?php else: ?>
                                <div style="font-size: 12px; color: #dc2626;">
                                    <i class="fa-solid fa-info-circle"></i> 
                                    Please select a city to see shipping charges
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="cart-summary-divider"></div>
                    
                    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                        <div class="cart-summary-row">
                            <span>Price (<?php echo $total_items; ?> item<?php echo $total_items > 1 ? 's' : ''; ?>)</span>
                            <span>₹<?php echo number_format($total_price); ?></span>
                        </div>
                        <div class="cart-summary-row discount">
                            <span>Discount</span>
                            <span>-₹<?php echo number_format($total_price * 0.3); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="cart-summary-row">
                            <span>Price (0 items)</span>
                            <span>₹0</span>
                        </div>
                        <div class="cart-summary-row discount">
                            <span>Discount</span>
                            <span>-₹0</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Coupon Section -->
                    <div style="background: #f8fafc; border-radius: 8px; padding: 12px; margin: 12px 0;">
                        <?php if (isset($_SESSION['applied_coupon'])): 
                            $coupon = $_SESSION['applied_coupon'];
                        ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #dcfce7; padding: 10px 12px; border-radius: 6px; margin-bottom: 8px;">
                                <div>
                                    <strong style="color: #166534;"><i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($coupon['code']); ?></strong>
                                    <br><small style="color: #15803d;">Coupon applied!</small>
                                </div>
                                <form method="POST" style="margin: 0;">
                                    <button type="submit" name="remove_coupon" style="background: #166534; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Remove</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="POST" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" name="coupon_code" placeholder="Enter coupon code" style="flex: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                                <button type="submit" name="apply_coupon" style="background: #3b82f6; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: 500;">Apply</button>
                            </form>
                            <?php if ($coupon_error): ?>
                                <div style="color: #dc2626; font-size: 13px; margin-bottom: 8px;"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $coupon_error; ?></div>
                            <?php endif; ?>
                            <?php if ($coupon_success): ?>
                                <div style="color: #16a34a; font-size: 13px; margin-bottom: 8px;"><i class="fa-solid fa-check-circle"></i> <?php echo $coupon_success; ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($available_coupons) && !isset($_SESSION['applied_coupon'])): ?>
                            <div style="border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 8px;">
                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px; border-radius: 8px; color: white; text-align: center;">
                                    <i class="fa-solid fa-gift" style="font-size: 20px; margin-bottom: 6px;"></i>
                                    <div style="font-weight: 600; font-size: 14px;">Special Offers Available!</div>
                                    <div style="font-size: 12px; margin-top: 4px; opacity: 0.9;">Enter coupon code at checkout to get amazing discounts</div>
                                    <div style="font-size: 11px; margin-top: 8px; opacity: 0.8;">
                                        <i class="fa-solid fa-circle-info"></i> Check your email or account for exclusive coupon codes
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-summary-divider"></div>
                    <div class="cart-summary-total-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <?php if ($discount_amount > 0): ?>
                        <div class="cart-summary-row" style="color: #16a34a;">
                            <span>Coupon Discount (<?php echo htmlspecialchars($_SESSION['applied_coupon']['code']); ?>)</span>
                            <span>-₹<?php echo number_format($discount_amount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="cart-summary-row">
                        <span>Delivery Charges</span>
                        <span><?php echo $shipping_charge > 0 ? '₹' . number_format($shipping_charge, 2) : '<span style="color: #16a34a;">Free</span>'; ?></span>
                    </div>
                    
                    <div class="cart-summary-divider"></div>
                    
                    <div class="cart-summary-total-row" style="font-size: 18px;">
                        <span>Total Amount</span>
                        <span>₹<?php echo number_format($final_total, 2); ?></span>
                    </div>
                    
                    <?php if ($discount_amount > 0 || $shipping_charge == 0): ?>
                        <p class="cart-summary-savings" style="color: #16a34a; font-weight: 500;">
                            <?php if ($discount_amount > 0): ?>
                                <i class="fa-solid fa-piggy-bank"></i> You saved ₹<?php echo number_format($discount_amount, 2); ?><?php echo $shipping_charge == 0 ? ' + Free shipping!' : ''; ?>
                            <?php elseif ($shipping_charge == 0 && $selected_city): ?>
                                <i class="fa-solid fa-gift"></i> Free shipping to <?php echo htmlspecialchars($selected_city); ?>!
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="cart-summary-divider" style="margin-top: 16px;"></div>
                    
                    <a href="Account_Profile/Order_complete/select_address.php">
                        <button type="button" class="cart-place-order-btn" style="width: 100%; margin-top: 12px;" <?php echo !isset($total_price) || $total_price == 0 ? 'disabled' : ''; ?>>
                            Place Order
                        </button>
                    </a>
                </aside>
            </div>

            <section class="cart-empty-state">
                <i class="fa-regular fa-face-smile" style="font-size: 32px; color: #2874f0;"></i>
                <h2>Your cart is empty!</h2>
                <p>Browse our categories and discover best offers for you.</p>
                <button type="button" class="cart-empty-btn" onclick="window.location.href='index.php'">Shop Now</button>
            </section>
        </div>
    </main>

      <?php include "Footer.php"; ?>

<script src="assets/js/index.js"></script>
    
<script>
    // Coupon functionality
    let currentDiscount = 0;
    let originalTotal = 2398;

    // Initialize pricing on page load
    window.addEventListener('load', () => {
        // Clear any existing coupon discount if we're starting fresh
        if (!document.getElementById('discountRow') || document.getElementById('discountRow').style.display === 'none') {
            sessionStorage.removeItem('couponDiscount');
            
            // Initialize basic price data
            const initialPriceData = {
                originalTotal: 3498, // Base price of items
                discount: 1100,      // Regular discount
                couponDiscount: 0,
                deliveryCharges: 40,
                finalTotal: 2398     // total amount without coupon
            };
            sessionStorage.setItem('priceData', JSON.stringify(initialPriceData));
        }
    });

    function applyCoupon() {
        const couponCode = document.getElementById('couponCode').value.trim().toUpperCase();
        const messageDiv = document.getElementById('couponMessage');
        const discountRow = document.getElementById('discountRow');
        const discountAmount = document.getElementById('discountAmount');
        const totalAmount = document.getElementById('totalAmount');

        messageDiv.style.display = 'block';
        messageDiv.className = 'coupon-message';

        if (!couponCode) {
            showCouponMessage('Please enter a coupon code', 'error');
            return;
        }

        // Simulate coupon validation
        let discount = 0;
        let message = '';

        switch(couponCode) {
            case 'WELCOME20':
                if (originalTotal >= 299) {
                    discount = Math.round(originalTotal * 0.20);
                    message = '20% discount applied successfully!';
                } else {
                    message = 'Minimum order amount ₹299 required for this coupon';
                }
                break;
            case 'SAVE100':
                if (originalTotal >= 599) {
                    discount = 100;
                    message = '₹100 discount applied successfully!';
                } else {
                    message = 'Minimum order amount ₹599 required for this coupon';
                }
                break;
            case 'FREESHIP':
                discount = 0;
                message = 'Free delivery applied! (Already free)';
                break;
            case 'FIRST15':
                discount = Math.round(originalTotal * 0.15);
                message = '15% discount applied successfully!';
                break;
            case 'SPECIAL50':
                discount = 50;
                message = '₹50 discount applied successfully!';
                break;
            default:
                message = 'Invalid coupon code';
        }

        if (discount > 0 || couponCode === 'FREESHIP') {
            currentDiscount = discount;
            const newTotal = originalTotal - discount;

            if (discount > 0) {
                discountRow.style.display = 'flex';
                discountAmount.textContent = `-₹${discount}`;
            } else {
                discountRow.style.display = 'none';
            }

            totalAmount.textContent = `₹${newTotal}`;
            showCouponMessage(message, 'success');

            // Store coupon discount in sessionStorage for checkout
            sessionStorage.setItem('couponDiscount', discount.toString());

            // Update price details in sessionStorage for select address page
            updatePriceDataInSession(discount, newTotal);

            // Update savings message
            const savingsText = document.querySelector('.cart-summary-savings');
            if (savingsText) {
                const totalSavings = 1140 + discount;
                savingsText.textContent = `You will save ₹${totalSavings} on this order`;
            }
        } else {
            showCouponMessage(message, 'error');
        }
    }

    // Update price data in sessionStorage
    function updatePriceDataInSession(couponDiscount, newTotal) {
        const priceData = {
            originalTotal: originalTotal,
            discount: 1100,
            couponDiscount: couponDiscount,
            deliveryCharges: 40,
            finalTotal: newTotal
        };
        sessionStorage.setItem('priceData', JSON.stringify(priceData));
    }

    function showCouponMessage(message, type) {
        const messageDiv = document.getElementById('couponMessage');
        messageDiv.textContent = message;
        messageDiv.className = `coupon-message ${type}`;
        messageDiv.style.display = 'block';
    }

    // Allow Enter key to apply coupon
    document.getElementById('couponCode').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            applyCoupon();
        }
    });
</script>

  <a href="index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>

</html>
