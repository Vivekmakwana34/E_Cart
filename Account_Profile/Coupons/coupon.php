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

function get_user_data_file_path_coupons($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_coupons($userId) {
    $path = get_user_data_file_path_coupons($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_coupons($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

// Fetch user's assigned coupons (only earned coupons)
$available_coupons = [];
$stmt = $conn->prepare("
    SELECT c.*, uc.is_used, uc.assigned_at 
    FROM coupons c 
    INNER JOIN user_coupons uc ON c.id = uc.coupon_id 
    WHERE uc.user_id = ? 
    AND c.is_active = 1 
    AND c.valid_from <= CURDATE() 
    AND c.valid_until >= CURDATE() 
    AND uc.is_used = 0
    ORDER BY uc.assigned_at DESC
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$coupons_result = $stmt->get_result();
while ($row = $coupons_result->fetch_assoc()) {
    $available_coupons[] = $row;
}

$available_count = count($available_coupons);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Coupons - E_Cart</title>
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

        .profile-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .profile-section-header h2 {
            font-size: 16px;
            font-weight: 600;
            color: #212121;
        }

        .profile-section-header p {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }

        .coupon-tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .coupon-tab {
            padding: 10px 0;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .coupon-tab.active {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }

        .coupon-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px 0;
        }

        @media (max-width: 768px) {
            .coupon-list {
                grid-template-columns: 1fr;
            }
        }

        .coupon-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .coupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .coupon-card.expired {
            opacity: 0.7;
            filter: grayscale(0.5);
        }

        .coupon-card.used {
            opacity: 0.8;
        }

        .coupon-header-bg {
            height: 80px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .coupon-header-bg.percentage {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .coupon-header-bg.fixed {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .coupon-discount-badge {
            position: absolute;
            background: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 22px;
            font-weight: 800;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            bottom: -20px;
        }

        .coupon-body {
            padding: 35px 20px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .coupon-code-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            color: #6b7280;
        }

        .coupon-code-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .coupon-code {
            font-size: 20px;
            font-weight: 800;
            color: #1e293b;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        .coupon-description {
            font-size: 14px;
            color: #475569;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .coupon-details {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .coupon-detail-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 15px;
        }

        .coupon-detail-item i {
            font-size: 10px;
            color: #94a3b8;
        }

        .coupon-badge-new {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .coupon-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-copy, .btn-use {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-copy {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-copy:hover {
            background: #e2e8f0;
            transform: scale(1.02);
        }

        .btn-use {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
        }

        .btn-use:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .empty-coupons i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-coupons h3 {
            font-size: 16px;
            color: #374151;                 
            margin-bottom: 8px;
        }

        .empty-coupons p {
            font-size: 14px;
            margin-bottom: 16px;
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

            .coupon-list {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
            }

            .coupon-tabs {
                gap: 12px;
            }

            .coupon-tab {
                font-size: 13px;
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

                        <a href="../Wishlist/wishlist.php" class="profile-menu-item">
                            <div class="profile-menu-item-left">
                                <i class="fa-regular fa-heart"></i>
                                <div>
                                    <span class="profile-menu-label">Wishlist</span><br>
                                    <span class="profile-menu-meta">Saved items</span>
                                </div>
                            </div>
                            <i class="fa-solid fa-angle-right"></i>
                        </a>

                        <a href="coupon.php" class="profile-menu-item profile-menu-active">
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
                    <div class="profile-section-header">
                        <div>
                        </div>

                        <div id="available-coupons" class="coupon-list">
                            <?php if (empty($available_coupons)): ?>
                                <div class="empty-coupons" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                                    <i class="fa-solid fa-ticket"></i>
                                    <h3>No Coupons Available</h3>
                                    <p>Check back later for exciting offers!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($available_coupons as $coupon): 
                                    $discount_display = $coupon['discount_type'] == 'percentage' ? $coupon['discount_value'] . '% OFF' : '₹' . $coupon['discount_value'] . ' OFF';
                                    $header_class = $coupon['discount_type'] == 'percentage' ? 'percentage' : 'fixed';
                                    $is_used = $coupon['is_used'] == 1;
                                    $card_class = $is_used ? 'coupon-card used' : 'coupon-card';
                                ?>
                                    <div class="<?php echo $card_class; ?>">
                                        <?php if ($is_used): ?>
                                            <div class="coupon-used-badge">USED</div>
                                        <?php elseif ($coupon['for_new_users_only']): ?>
                                            <div class="coupon-badge-new">NEW USER</div>
                                        <?php endif; ?>
                                        <div class="coupon-header-bg <?php echo $header_class; ?>">
                                            <div class="coupon-discount-badge"><?php echo $discount_display; ?></div>
                                        </div>
                                        <div class="coupon-body">
                                            <div class="coupon-code-box">
                                                <div class="coupon-code-label">Coupon Code</div>
                                                <div class="coupon-code"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                            </div>
                                            <?php if ($coupon['description']): ?>
                                                <div class="coupon-description"><?php echo htmlspecialchars($coupon['description']); ?></div>
                                            <?php endif; ?>
                                            <div class="coupon-details">
                                                <?php if ($coupon['min_order_amount'] > 0): ?>
                                                    <div class="coupon-detail-item">
                                                        <i class="fa-solid fa-cart-shopping"></i>
                                                        <span>Min ₹<?php echo $coupon['min_order_amount']; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($coupon['max_discount']): ?>
                                                    <div class="coupon-detail-item">
                                                        <i class="fa-solid fa-tag"></i>
                                                        <span>Max ₹<?php echo $coupon['max_discount']; ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="coupon-detail-item">
                                                    <i class="fa-solid fa-calendar"></i>
                                                    <span><?php echo $is_used ? 'Used on ' . date('d M', strtotime($coupon['used_at'])) : 'Till ' . date('d M', strtotime($coupon['valid_until'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="coupon-actions">
                                                <?php if ($is_used): ?>
                                                    <button class="btn-copy" disabled style="background: #2d3748; color: #718096; cursor: not-allowed; opacity: 0.6;">
                                                        <i class="fa-solid fa-check"></i> Used
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-copy" onclick="copyCoupon('<?php echo $coupon['code']; ?>', this)">
                                                        <i class="fa-regular fa-copy"></i> Copy
                                                    </button>
                                                <?php endif; ?>
                                                <a href="../../cart.php" class="btn-use" <?php echo $is_used ? 'style="background: #2d3748; color: #718096; cursor: not-allowed; pointer-events: none;"' : ''; ?>>
                                                    <?php echo $is_used ? 'Already Used' : 'Use Now'; ?> <?php if (!$is_used) echo '<i class="fa-solid fa-arrow-right"></i>'; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div id="used-coupons" class="coupon-list" style="display: none;">
                            <div class="coupon-card used">
                                <div class="coupon-discount">15% OFF</div>
                                <div class="coupon-info">
                                    <div class="coupon-code">FIRST15</div>
                                    <div class="coupon-description">15% off on first purchase</div>
                                    <div class="coupon-meta">
                                        <span>Used on: Order #EC123456</span>
                                        <span class="separator">•</span>
                                        <span>10 Jan 2026</span>
                                    </div>
                                </div>
                                <div class="coupon-action">
                                    <button class="btn-applied" disabled>
                                        <i class="fa-solid fa-check"></i> Applied
                                    </button>
                                </div>
                                <div class="coupon-status-badge status-used">Used</div>
                            </div>

                            <div class="coupon-card used">
                                <div class="coupon-discount">₹50 OFF</div>
                                <div class="coupon-info">
                                    <div class="coupon-code">SPECIAL50</div>
                                    <div class="coupon-description">Special discount coupon</div>
                                    <div class="coupon-meta">
                                        <span>Used on: Order #EC987654</span>
                                        <span class="separator">•</span>
                                        <span>25 Jan 2026</span>
                                    </div>
                                </div>
                                <div class="coupon-action">
                                    <button class="btn-applied" disabled>
                                        <i class="fa-solid fa-check"></i> Applied
                                    </button>
                                </div>
                                <div class="coupon-status-badge status-used">Used</div>
                            </div>
                        </div>

                        <div id="expired-coupons" class="coupon-list" style="display: none;">
                            <div class="empty-coupons" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                                <i class="fa-solid fa-clock"></i>
                                <h3>No Expired Coupons</h3>
                                <p>All your coupons are still valid!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

         <?php include "../../Footer.php"; ?>

    <script>
        function switchTab(tabName, element) {
            // Hide all coupon lists
            document.getElementById('available-coupons').style.display = 'none';
            document.getElementById('used-coupons').style.display = 'none';
            document.getElementById('expired-coupons').style.display = 'none';
            
            // Remove active class from all tabs
            document.querySelectorAll('.coupon-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab and mark as active
            document.getElementById(tabName + '-coupons').style.display = 'flex';
            element.classList.add('active');
        }

        function copyCoupon(code, button) {
            navigator.clipboard.writeText(code).then(function() {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                button.style.backgroundColor = '#10b981';
                button.style.color = 'white';
                
                setTimeout(function() {
                    button.innerHTML = originalHTML;
                    button.style.backgroundColor = '';
                    button.style.color = '';
                }, 2000);
            });
        }
    </script>

    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>