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

function get_user_data_file_path($userId) {
    return __DIR__ . '/user_data/' . $userId . '.json';
}

function read_user_data($userId) {
    $path = get_user_data_file_path($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$profileMobile = trim((string)($userData['mobile'] ?? ''));
$profileGender = trim((string)($userData['gender'] ?? ''));
$profileDob = trim((string)($userData['dob'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

// Get dynamic counts for dashboard
$totalOrders = 0;
$wishlistCount = 0;
$couponCount = 0;
$rewardPoints = 0;

// Count total orders
$orderStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
if ($orderStmt) {
    $orderStmt->bind_param('i', $userId);
    $orderStmt->execute();
    $orderRes = $orderStmt->get_result();
    if ($orderRes) {
        $totalOrders = $orderRes->fetch_assoc()['total'] ?? 0;
    }
    $orderStmt->close();
}

// Count wishlist items
$wishStmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
if ($wishStmt) {
    $wishStmt->bind_param('i', $userId);
    $wishStmt->execute();
    $wishRes = $wishStmt->get_result();
    if ($wishRes) {
        $wishlistCount = $wishRes->fetch_assoc()['total'] ?? 0;
    }
    $wishStmt->close();
}

// Count user's available coupons (assigned and not used)
$couponStmt = $conn->prepare("SELECT COUNT(*) as total FROM user_coupons WHERE user_id = ? AND is_used = 0");
if ($couponStmt) {
    $couponStmt->bind_param('i', $userId);
    $couponStmt->execute();
    $couponRes = $couponStmt->get_result();
    if ($couponRes) {
        $couponCount = $couponRes->fetch_assoc()['total'] ?? 0;
    }
    $couponStmt->close();
}

// Calculate reward points (10 points per ₹100 spent)
$pointsStmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND order_status = 'delivered'");
if ($pointsStmt) {
    $pointsStmt->bind_param('i', $userId);
    $pointsStmt->execute();
    $pointsRes = $pointsStmt->get_result();
    if ($pointsRes) {
        $totalSpent = $pointsRes->fetch_assoc()['total'] ?? 0;
        $rewardPoints = floor($totalSpent / 100) * 10;
    }
    $pointsStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E_Cart</title>
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

        .profile-user-text p {
            font-size: 13px;
            color: #717478;
            margin: 0;
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
            font-size: 16px;
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

        .profile-section-header button {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #dadada;
            background-color: #ffffff;
            color: #2874f0;
            cursor: pointer;
            font-weight: 500;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 28px;
        }

        .profile-info-item-label {
            font-size: 12px;
            color: #717478;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 4px;
        }

        .profile-info-item-value {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }

        .profile-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .profile-tag {
            padding: 3px 10px;
            border-radius: 999px;
            background-color: #eff6ff;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 500;
        }

        .profile-section + .profile-section {
            margin-top: 10px;
        }

        .profile-address-card {
            padding: 12px 0 4px;
            border-top: 1px solid #e5e7eb;
        }

        .profile-address-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .profile-address-text {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 2px;
        }

        .profile-address-meta {
            font-size: 12px;
            color: #4b5563;
        }

        .profile-activity-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .profile-activity-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 0;
            border-top: 1px solid #e5e7eb;
        }

        .profile-activity-main {
            font-size: 13px;
            color: #111827;
        }

        .profile-activity-meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        .profile-activity-tag {
            font-size: 11px;
            color: #1d4ed8;
            font-weight: 500;
        }

        .profile-activity-time {
            font-size: 11px;
            color: #6b7280;
            white-space: nowrap;
        }

        /* Dashboard Stats Section */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-info {
            flex: 1;
        }

        .stat-number {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }

        /* Quick Actions */
        .quick-actions {
            margin-bottom: 24px;
        }

        .quick-actions h3 {
            font-size: 14px;
            font-weight: 600;
            color: #212121;
            margin-bottom: 12px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
        }

        .quick-action-btn:hover {
            background: #f0f5ff;
            border-color: #2874f0;
            color: #2874f0;
        }

        .quick-action-btn i {
            font-size: 16px;
            color: #2874f0;
        }

        /* Latest Order Status */
        .latest-order h3 {
            font-size: 14px;
            font-weight: 600;
            color: #212121;
            margin-bottom: 12px;
        }

        .order-track-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .order-id {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .order-status {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .order-status.delivered {
            background: #d4edda;
            color: #155724;
        }

        .order-item {
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
        }

        .order-progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
        }

        .progress-step.completed .step-dot {
            background: #28a745;
        }

        .step-label {
            font-size: 10px;
            color: #999;
            text-align: center;
        }

        .progress-step.completed .step-label {
            color: #28a745;
            font-weight: 500;
        }

        .progress-line {
            flex: 1;
            height: 2px;
            background: #ddd;
            margin: 0 4px;
            margin-bottom: 20px;
        }

        .progress-line.completed {
            background: #28a745;
        }

        /* Responsive */

        @media (max-width: 900px) {
            .profile-main {
                padding: 20px 12px 30px;
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

            .profile-info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 16px 10px 26px;
            }

            .profile-user-row {
                align-items: flex-start;
            }

            .profile-user-text h2 {
                font-size: 17px;
            }

            .profile-section-header h2 {
                font-size: 15px;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .order-progress {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .progress-line {
                display: none;
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
                    <button type="button" class="profile-edit-btn" onclick="window.location.href='profile_edit.php'">
                        <i class="fa-solid fa-pen"></i>
                        Edit Profile
                    </button>
                </div>

                <div class="profile-card">
                    <div class="profile-header-label">Account Menu</div>
                    <div class="profile-menu">
                        <a href="profile.php" class="profile-menu-item profile-menu-active">
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
                    <div class="profile-section-header">
                        <h2>Account Information</h2>
                        <button type="button" onclick="window.location.href='account_edit.php'">Edit</button>
                    </div>
                    <div class="profile-info-grid">
                        <div>
                            <div class="profile-info-item-label">Full Name</div>
                            <div class="profile-info-item-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>

                        <div>
                            <div class="profile-info-item-label">Mobile Number</div>
                            <div class="profile-info-item-value"><?php echo $profileMobile !== '' ? htmlspecialchars($profileMobile) : '--'; ?></div>
                        </div>

                        <div>
                            <div class="profile-info-item-label">Email Address</div>
                            <div class="profile-info-item-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>

                        <div>
                            <div class="profile-info-item-label">Member Since</div>
                            <div class="profile-info-item-value"><?php echo !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '--'; ?></div>
                        </div>

                        <div>
                            <div class="profile-info-item-label">Gender</div>
                            <div class="profile-info-item-value"><?php echo $profileGender !== '' ? htmlspecialchars(ucfirst($profileGender)) : 'Not specified'; ?></div>
                        </div>

                        <div>
                            <div class="profile-info-item-label">Date of Birth</div>
                            <div class="profile-info-item-value"><?php echo $profileDob !== '' ? htmlspecialchars($profileDob) : '--'; ?></div>
                        </div>
                    </div>

                    <div class="profile-tags">
                        <span class="profile-tag">Prime Member</span>
                        <span class="profile-tag">Verified Email</span>
                        <span class="profile-tag">Secure Account</span>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="profile-section-header">
                        <h2>Dashboard Overview</h2>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo $totalOrders; ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon wishlist">
                                <i class="fa-regular fa-heart"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo $wishlistCount; ?></div>
                                <div class="stat-label">Wishlist Items</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon coupons">
                                <i class="fa-solid fa-ticket"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo $couponCount; ?></div>
                                <div class="stat-label">Active Coupons</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon rewards">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-number"><?php echo number_format($rewardPoints); ?></div>
                                <div class="stat-label">Reward Points</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="action-grid">
                            <a href="../Order/order.php" class="quick-action-btn">
                                <i class="fa-solid fa-box"></i>
                                <span>Track Order</span>
                            </a>
                            <a href="../Wishlist/wishlist.php" class="quick-action-btn">
                                <i class="fa-regular fa-heart"></i>
                                <span>View Wishlist</span>
                            </a>
                            <a href="../Coupons/coupon.php" class="quick-action-btn">
                                <i class="fa-solid fa-tags"></i>
                                <span>Apply Coupon</span>
                            </a>
                            <a href="../Address/Address.php" class="quick-action-btn">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Manage Address</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Latest Order Status -->
                    <div class="latest-order">
                        <h3>Latest Order Status</h3>
                        <div class="order-track-card">
                            <div class="order-header">
                                <div class="order-id">Order #EC123456</div>
                                <div class="order-status delivered">Delivered</div>
                            </div>
                            <div class="order-item">Wireless Earbuds with Fast Charging</div>
                            <div class="order-progress">
                                <div class="progress-step completed">
                                    <div class="step-dot"></div>
                                    <div class="step-label">Ordered</div>
                                </div>
                                <div class="progress-line completed"></div>
                                <div class="progress-step completed">
                                    <div class="step-dot"></div>
                                    <div class="step-label">Shipped</div>
                                </div>
                                <div class="progress-line completed"></div>
                                <div class="progress-step completed">
                                    <div class="step-dot"></div>
                                    <div class="step-label">Out for Delivery</div>
                                </div>
                                <div class="progress-line completed"></div>
                                <div class="progress-step completed">
                                    <div class="step-dot"></div>
                                    <div class="step-label">Delivered</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

       <?php include "../../Footer.php"; ?>

    <script src="../../assets/js/index.js"></script>
    <!-- Floating Home Button -->
    <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>

</html>