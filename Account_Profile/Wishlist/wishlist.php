<?php
session_start();

include '../../Database_file/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];
$user = null;

$userStmt = $conn->prepare("SELECT id, full_name, email, created_at FROM users WHERE id = ? LIMIT 1");
if ($userStmt) {
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userRes = $userStmt->get_result();
    $user = $userRes ? $userRes->fetch_assoc() : null;
}

if (!$user) {
    header('Location: ../../logout.php');
    exit();
}

// Load wishlist items from database
$wishlistItems = [];
$wishlistStmt = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? ORDER BY created_at DESC");
if ($wishlistStmt) {
    $wishlistStmt->bind_param('i', $userId);
    $wishlistStmt->execute();
    $wishlistRes = $wishlistStmt->get_result();
    if ($wishlistRes) {
        while ($row = $wishlistRes->fetch_assoc()) {
            $wishlistItems[] = $row;
        }
    }
}

function make_initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'U';
    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $second = '';
    if (count($parts) > 1) {
        $second = strtoupper(substr($parts[1] ?? '', 0, 1));
    } elseif (strlen($name) > 1) {
        $second = strtoupper(substr($name, 1, 1));
    }
    $initials = $first . $second;
    return substr($initials, 0, 2);
}

function get_user_data_file_path_wishlist($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_wishlist($userId) {
    $path = get_user_data_file_path_wishlist($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_wishlist($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css">
    <link rel="stylesheet" href="../../floating_home.css">

    <style>
        body {
            background-color: #f1f2f4;
        }

        .profile-main {
            padding: 26px 16px 40px;
        }

        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: flex-start;
            gap: 24px;
        }

        .profile-sidebar {
            flex: 0 0 280px;
        }

        .profile-card,
        .profile-section {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            padding: 20px 20px 18px;
            margin-bottom: 16px;
        }

        .profile-header-label {
            font-size: 13px;
            color: #717478;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .profile-header-label.profile-initials-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .profile-initials-value {
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            flex: 0 0 auto;
        }

        .profile-user-row {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1d4ed8, #020617);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 32px;
            font-weight: 600;
            overflow: hidden;
            flex-shrink: 0;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-name {
            font-size: 20px;
            font-weight: 900;
            color: #0b1220;
            letter-spacing: -0.02em;
            margin: 0;
            line-height: 1.1;
            display: inline-block;
            position: relative;
        }

        .profile-name::after {
            content: '';
            display: block;
            width: 36px;
            height: 3px;
            border-radius: 999px;
            background: #2874f0;
            margin-top: 6px;
        }

        .profile-username-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .profile-email {
            margin-top: 2px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            color: #334155;
            font-size: 14px;
            max-width: 100%;
        }

        .profile-email i {
            color: #2874f0;
            font-size: 12px;
            flex: 0 0 auto;
        }

        .profile-email span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            max-width: 210px;
        }

        .profile-bio {
            margin-top: 10px;
            padding: 6px 8px;
            border-radius: 10px;
            background: #f3f6ff;
            border: 1px solid #e2e9ff;
            border-left: 3px solid #2874f0;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.35;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .profile-bio span {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .profile-edit-btn {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            border: 1px solid #dadada;
            background-color: #ffffff;
            color: #2874f0;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .profile-edit-btn i {
            font-size: 13px;
        }

        .profile-menu {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 10px;
            border-radius: 8px;
            cursor: pointer;
            color: #212121;
            font-size: 15px;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .profile-menu-item-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-menu-item i {
            color: #2874f0;
            font-size: 15px;
        }

        .profile-menu-item span.profile-menu-label {
            font-weight: 500;
        }

        .profile-menu-item span.profile-menu-meta {
            font-size: 16px;
            color: #717478;
        }

        .profile-menu-item.profile-menu-active,
        .profile-menu-item:hover {
            background-color: #f0f5ff;
            color: #212121;
        }

        .profile-menu-item.profile-menu-danger i,
        .profile-menu-item.profile-menu-danger span.profile-menu-label {
            color: #dc2626;
        }

        .profile-content {
            flex: 1;
            min-width: 0;
        }

        .wishlist-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .wishlist-header h1 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .wishlist-header-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .wishlist-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .wishlist-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .wishlist-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 14px;
            padding: 12px 0;
            border-top: 1px solid #e5e7eb;
        }

        .wishlist-item-thumb {
            width: 72px;
            height: 72px;
            border-radius: 8px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 20px;
        }

        .wishlist-item-title {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            margin-bottom: 4px;
        }

        .wishlist-item-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .wishlist-price-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            font-size: 14px;
        }

        .wishlist-price-current {
            color: #111827;
            font-weight: 600;
        }

        .wishlist-price-old {
            color: #9ca3af;
            text-decoration: line-through;
            font-size: 12px;
        }

        .wishlist-price-off {
            color: #16a34a;
            font-size: 12px;
            font-weight: 600;
        }

        .wishlist-item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .btn-small-primary,
        .btn-small-secondary {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-small-primary {
            background-color: #fb641b;
            border-color: #fb641b;
            color: #ffffff;
        }

        .btn-small-secondary {
            background-color: #ffffff;
            border-color: #d1d5db;
            color: #374151;
        }

        .btn-small-primary:hover {
            background-color: #e45a18;
            border-color: #e45a18;
        }

        .btn-small-secondary:hover {
            background-color: #f3f4f6;
        }

        .wishlist-empty {
            padding: 16px 0 4px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
        }

        @media (max-width: 900px) {
            .profile-main {
                padding: 22px 12px 32px;
            }

            .profile-wrapper {
                flex-direction: column;
            }

            .profile-sidebar {
                flex: 1;
                width: 100%;
            }

            .profile-card,
            .profile-section {
                padding: 18px 16px 16px;
            }

            .wishlist-item {
                grid-template-columns: auto 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
            }

            .wishlist-item {
                grid-template-columns: 1fr;
            }

            .wishlist-item-thumb {
                width: 64px;
                height: 64px;
            }

            .wishlist-item-actions {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

   <?php include '../../Header.php'; ?>

    <main class="profile-main">
        <div class="profile-wrapper">
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-header-label profile-initials-label">
                        <span class="profile-initials-value"><?php echo htmlspecialchars($initials); ?></span>
                    </div>
                    <div class="profile-user-row">
                        <div class="profile-avatar">
                            <?php if (!empty($avatarUrl)): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials); ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-user-text">
                            <div class="profile-username-label">Username</div>
                            <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <div class="profile-email">
                                <i class="fa-regular fa-envelope"></i>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <?php if (!empty($profileTagline)): ?>
                                <div class="profile-bio">
                                    <span><?php echo htmlspecialchars($profileTagline); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="profile-card">
                    <div class="profile-header-label">Account Menu</div>
                    <div class="profile-menu">
                        <a href="../Profile/profile.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-regular fa-user"></i>
                                <div>
                                    <span class="profile-menu-label">Overview</span><br>
                                    <span class="profile-menu-meta">Profile, contact info</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Order/order.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-bag-shopping"></i>
                                <div>
                                    <span class="profile-menu-label">Orders</span><br>
                                    <span class="profile-menu-meta">Track, return or buy again</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="wishlist.php" class="profile-menu-item profile-menu-active">
                            <div class="profile-menu-item-left">
                                <i class="fa-regular fa-heart"></i>
                                <div>
                                    <span class="profile-menu-label">Wishlist</span><br>
                                    <span class="profile-menu-meta">Saved items</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                          <a href="../Coupons/coupon.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-tags"></i>
                                <div>
                                    <span class="profile-menu-label">Coupons</span><br>
                                    <span class="profile-menu-meta">Available offers</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                        
                         <a href="../Gift_Cards/gift.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-gift"></i>
                                <div>
                                    <span class="profile-menu-label">Gift Cards</span><br>
                                    <span class="profile-menu-meta">Buy & manage cards</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Address/Address.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-location-dot"></i>
                                <div>
                                    <span class="profile-menu-label">Addresses</span><br>
                                    <span class="profile-menu-meta">Manage delivery locations</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Change_pass/change_password.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-lock"></i>
                                <div>
                                    <span class="profile-menu-label">Change Password</span><br>
                                    <span class="profile-menu-meta">Update your login password</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="../Payments/payments.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-wallet"></i>
                                <div>
                                    <span class="profile-menu-label">Payments</span><br>
                                    <span class="profile-menu-meta">Cards, UPI & wallets</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="#" class="profile-menu-item profile-menu-danger">
                            <div class="profile-menu-item-left">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span class="profile-menu-label">Logout</span>
                            </div>
                        </a>
                    </div>
                </div>
            </aside>

            <section class="profile-content">
                <div class="profile-section">
                    <div class="wishlist-header">
                        <div>
                            <h1>My Wishlist</h1>
                            <div class="wishlist-header-meta"><?php echo count($wishlistItems); ?> items in your wishlist</div>
                        </div>
                        <div class="wishlist-actions">
                           <a href="../../index.php"> <button type="button" class="btn-small-secondary">Continue Shopping</button></a>
                        </div>
                    </div>

                    <div class="wishlist-items">
                        <?php if (empty($wishlistItems)): ?>
                            <div style="text-align: center; padding: 40px; color: #666;">
                                <i class="fa-solid fa-heart" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                                <p>Your wishlist is empty</p>
                                <p style="font-size: 14px; margin-top: 8px;">Add items you love to keep them here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($wishlistItems as $item): ?>
                                <div class="wishlist-item" data-wishlist-id="<?php echo $item['id']; ?>">
                                    <div class="wishlist-item-thumb">
                                        <?php if ($item['product_image']): ?>
                                            <img src="../../assets/img/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <i class="fa-solid fa-box"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="wishlist-item-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="wishlist-item-meta">Product ID: <?php echo $item['product_id']; ?></div>
                                        <div class="wishlist-price-row">
                                            <span class="wishlist-price-current">₹<?php echo number_format($item['product_price'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="wishlist-item-actions">
                                        <button type="button" class="btn-small-primary" onclick="moveToCart(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>', <?php echo $item['product_price']; ?>, '<?php echo htmlspecialchars($item['product_image']); ?>')">
                                            <i class="fa-solid fa-cart-plus"></i>
                                            <span>Move to Cart</span>
                                        </button>
                                        <button type="button" class="btn-small-secondary" onclick="removeFromWishlist(<?php echo $item['id']; ?>, this)">
                                            <i class="fa-solid fa-xmark"></i>
                                            <span>Remove</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

         <?php include "../../Footer.php"; ?>

    <script src="../../assets/js/index.js"></script>
    
    <script>
        // Move to Cart function
        function moveToCart(productId, productName, productPrice, productImage) {
            // Add to cart session
            fetch('../../cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    add_to_cart: 1,
                    product_id: productId,
                    product_name: productName,
                    product_price: productPrice,
                    product_image: productImage,
                    quantity: 1
                })
            })
            .then(response => response.text())
            .then(data => {
                // Remove from wishlist after adding to cart
                removeFromWishlistByProductId(productId);
                showNotification('Product moved to cart!', 'success');
                // Redirect to cart page after a short delay
                setTimeout(() => {
                    window.location.href = '../../cart.php';
                }, 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error moving to cart', 'error');
            });
        }

        // Remove from wishlist function
        function removeFromWishlist(wishlistId, buttonElement) {
            if (!confirm('Are you sure you want to remove this item from your wishlist?')) {
                return;
            }

            fetch('remove_from_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    wishlist_id: wishlistId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item from DOM
                    const wishlistItem = buttonElement.closest('.wishlist-item');
                    wishlistItem.style.transition = 'opacity 0.3s, transform 0.3s';
                    wishlistItem.style.opacity = '0';
                    wishlistItem.style.transform = 'translateX(-20px)';
                    
                    setTimeout(() => {
                        wishlistItem.remove();
                        // Update the count
                        const countElement = document.querySelector('.wishlist-header-meta');
                        const currentCount = parseInt(countElement.textContent.match(/\d+/)[0]);
                        countElement.textContent = (currentCount - 1) + ' items in your wishlist';
                        
                        // Show empty message if no items left
                        if (currentCount - 1 === 0) {
                            const wishlistItems = document.querySelector('.wishlist-items');
                            wishlistItems.innerHTML = `
                                <div style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fa-solid fa-heart" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                                    <p>Your wishlist is empty</p>
                                    <p style="font-size: 14px; margin-top: 8px;">Add items you love to keep them here</p>
                                </div>
                            `;
                        }
                    }, 300);
                    
                    showNotification('Product removed from wishlist', 'info');
                } else {
                    showNotification('Error removing from wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error removing from wishlist', 'error');
            });
        }

        // Remove from wishlist by product ID (used after moving to cart)
        function removeFromWishlistByProductId(productId) {
            fetch('remove_from_wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find and remove the item
                    const wishlistItems = document.querySelectorAll('.wishlist-item');
                    wishlistItems.forEach(item => {
                        const removeBtn = item.querySelector('button[onclick*="moveToCart"]');
                        if (removeBtn && removeBtn.getAttribute('onclick').includes(productId)) {
                            item.style.transition = 'opacity 0.3s, transform 0.3s';
                            item.style.opacity = '0';
                            item.style.transform = 'translateX(-20px)';
                            
                            setTimeout(() => {
                                item.remove();
                                // Update count
                                const countElement = document.querySelector('.wishlist-header-meta');
                                const currentCount = parseInt(countElement.textContent.match(/\d+/)[0]);
                                countElement.textContent = (currentCount - 1) + ' items in your wishlist';
                            }, 300);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Update cart count in header
        function updateCartCount() {
            // This would typically fetch the latest cart count from server
            // For now, just reload the page or update based on session
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Simple notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                border-radius: 5px;
                z-index: 10000;
                font-family: Arial, sans-serif;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    </script>
    
    <!-- Floating Home Button -->
    <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>
