<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

function get_user_data_file_path_orders($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_orders($userId) {
    $path = get_user_data_file_path_orders($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_orders($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

// Load real orders from database
$orders = [];
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
if ($orderStmt) {
    $orderStmt->bind_param('i', $userId);
    $orderStmt->execute();
    $orderRes = $orderStmt->get_result();
    while ($row = $orderRes->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - E_Cart</title>
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

        .profile-user-text h2 {
            font-size: 18px;
            font-weight: 600;
            color: #212121;
            margin-bottom: 4px;
        }

        .profile-user-text p {
            font-size: 13px;
            color: #717478;
            margin: 0;
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

        .orders-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .orders-header h1 {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }

        .orders-header-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 2px;
        }

        .orders-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-card {
            padding: 12px 0;
            border-top: 1px solid #e5e7eb;
        }

        .order-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .order-id {
            font-size: 13px;
            color: #4b5563;
            font-weight: 500;
        }

        .order-status {
            font-size: 12px;
            padding: 3px 9px;
            border-radius: 999px;
            font-weight: 600;
        }

        .order-status-delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .order-status-intransit {
            background-color: #eff6ff;
            color: #1d4ed8;
        }

        .order-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .order-card-body {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 14px;
            align-items: flex-start;
        }

        .order-thumb {
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

        .order-info-title {
            font-size: 14px;
            font-weight: 500;
            color: #111827;
            margin-bottom: 4px;
        }

        .order-info-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .order-info-price {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }

        .order-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .btn-small-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.3s ease;
        }

        .btn-small-danger:hover {
            background: #c82333;
        }

        .orders-empty {
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

            .order-card-body {
                grid-template-columns: auto 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
            }

            .order-card-body {
                grid-template-columns: 1fr;
            }

            .order-thumb {
                width: 64px;
                height: 64px;
            }

            .order-actions {
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

                        <a href="order.php" class="profile-menu-item profile-menu-active">
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
                    <div class="orders-header">
                        <div>
                            <h1>My Orders</h1>
                            <div class="orders-header-meta">2 orders placed in the last 30 days</div>
                        </div>
                        <div class="orders-actions">
                            <button type="button" class="btn-small-secondary" onclick="downloadOrderHistory()">
                                <i class="fas fa-file-arrow-down"></i>
                                <span>Download Order History</span>
                            </button>
                        </div>
                    </div>

                    <div class="orders-list">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-card-top">
                                        <div class="order-id"><?php echo htmlspecialchars($order['order_number'] ?? 'Order #' . $order['id']); ?></div>
                                        <div class="order-status order-status-<?php echo strtolower($order['order_status'] ?? 'pending'); ?>"><?php echo ucfirst($order['order_status'] ?? 'Pending'); ?></div>
                                    </div>
                                    <div class="order-card-meta">
                                        <span>Placed on <?php echo date('d M Y', strtotime($order['created_at'] ?? 'now')); ?></span>
                                        <span>Rs. <?php echo number_format($order['total_amount'] ?? 0, 0); ?> &middot; 1 item</span>
                                    </div>
                                    <div class="order-card-body">
                                        <div class="order-thumb">
                                            <i class="fa-solid fa-box"></i>
                                        </div>
                                        <div>
                                            <div class="order-info-title">Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></div>
                                            <div class="order-info-meta"><?php echo ucfirst($order['order_status'] ?? 'Pending'); ?></div>
                                            <div class="order-info-price">Rs. <?php echo number_format($order['total_amount'] ?? 0, 0); ?></div>
                                        </div>
                                        <div class="order-actions">
                                            <button type="button" class="btn-small-primary" onclick="showOrderDetails(<?php echo $order['id']; ?>)">
                                                <i class="fa-solid fa-circle-info"></i>
                                                <span>View Details</span>
                                            </button>
                                            <button type="button" class="btn-small-secondary" onclick="downloadInvoice(<?php echo $order['id']; ?>)">
                                                <i class="fa-solid fa-file-arrow-down"></i>
                                                <span>Invoice</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="orders-empty">You have no orders. Start shopping to see them here.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

       <?php include "../../Footer.php"; ?>

    <script src="../../assets/js/index.js"></script>
    <script>
        function showOrderDetails(orderId) {
            // Create modal popup for order details with tracking
            const modal = document.createElement('div');
            modal.id = 'orderModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.6);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(5px);
                animation: fadeIn 0.3s ease;
            `;
            
            modal.innerHTML = `
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2px; border-radius: 16px; max-width: 700px; width: 90%; max-height: 80vh; overflow-y: auto; animation: slideUp 0.3s ease;">
                    <div style="background: white; border-radius: 14px; padding: 0;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 25px; border-radius: 14px 14px 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <h2 style="margin: 0; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-box"></i> Order Details & Tracking
                            </h2>
                            <button onclick="closeOrderModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div id="orderDetailsContent" style="padding: 25px;">
                            <div style="text-align: center; padding: 40px; color: #666;">
                                <div style="width: 40px; height: 40px; border: 3px solid #667eea; border-top: 3px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
                                Loading order details...
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translateY(30px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    @keyframes pulse {
                        0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.4); }
                        70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
                    }
                    @keyframes trackingFlow {
                        0% { background-position: 0% 0%; }
                        100% { background-position: 200% 0%; }
                    }
                    .ec-track {
                        position: relative;
                        padding: 0;
                        margin-bottom: 8px;
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                    }

                    .ec-track-line {
                        position: absolute;
                        left: 0;
                        top: 24px;
                        right: 0;
                        height: 3px;
                        background: #e0e0e0;
                        border-radius: 2px;
                        z-index: 1;
                        overflow: hidden;
                    }

                    .ec-track-fill {
                        height: 100%;
                        width: 0%;
                        background: #e0e0e0;
                        border-radius: 2px;
                        transition: background 0.3s ease, width 1.6s ease;
                    }

                    .ec-track-fill.is-active {
                        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
                    }

                    .ec-track-step {
                        position: relative;
                        flex: 1;
                        text-align: center;
                        padding: 0 10px;
                        z-index: 2;
                        user-select: none;
                    }

                    .ec-track-icon {
                        width: 48px;
                        height: 48px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 18px;
                        margin: 0 auto 12px;
                        background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
                        color: white;
                        box-shadow: 0 4px 15px rgba(224, 224, 224, 0.4);
                        transition: all 0.4s ease;
                    }

                    .ec-track-step.is-done .ec-track-icon {
                        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
                        box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
                    }

                    .ec-track-step.is-active .ec-track-icon {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                        animation: pulse 2s infinite;
                    }

                    .ec-track-title {
                        font-size: 12px;
                        font-weight: 600;
                        color: #9e9e9e;
                        transition: color 0.4s ease;
                    }

                    .ec-track-step.is-done .ec-track-title {
                        color: #43e97b;
                    }

                    .ec-track-step.is-active .ec-track-title {
                        color: #667eea;
                    }

                    .ec-track-meta {
                        font-size: 10px;
                        color: #666;
                        margin-top: 2px;
                    }
                </style>
            `;
            
            document.body.appendChild(modal);
            
            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeOrderModal();
                }
            });
            
            // Fetch order details
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    const content = document.getElementById('orderDetailsContent');
                    if (data.success) {
                        content.innerHTML = `
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #333; margin-bottom: 20px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-info-circle" style="color: #667eea;"></i> Order Information
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                                    <div style="padding: 15px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Order Number</div>
                                        <div style="font-size: 16px; font-weight: 600;">${data.order.order_number}</div>
                                    </div>
                                    <div style="padding: 15px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Status</div>
                                        <div style="font-size: 16px; font-weight: 600;">${data.order.order_status}</div>
                                    </div>
                                    <div style="padding: 15px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Total Amount</div>
                                        <div style="font-size: 16px; font-weight: 600;">Rs. ${data.order.total_amount}</div>
                                    </div>
                                    <div style="padding: 15px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(250, 112, 154, 0.3);">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Payment Method</div>
                                        <div style="font-size: 16px; font-weight: 600;">${data.order.payment_method}</div>
                                    </div>
                                    <div style="padding: 15px; background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(48, 207, 208, 0.3); grid-column: span 2;">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Shipping Address</div>
                                        <div style="font-size: 14px; font-weight: 600; line-height: 1.4;">${data.order.shipping_address}</div>
                                    </div>
                                    <div style="padding: 15px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; border-radius: 10px; box-shadow: 0 4px 15px rgba(168, 237, 234, 0.3); grid-column: span 2;">
                                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">Placed on</div>
                                        <div style="font-size: 16px; font-weight: 600;">${new Date(data.order.created_at).toLocaleDateString()}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #333; margin-bottom: 20px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-cog" style="color: #667eea;"></i> Order Actions
                                </h3>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="button" class="btn-small-secondary" onclick="downloadInvoice(${data.order.id})" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                        <i class="fas fa-file-arrow-down"></i>
                                        <span>Download Invoice</span>
                                    </button>
                                    ${data.order.order_status.toLowerCase() === 'pending' ? `
                                    <button type="button" class="btn-small-danger" onclick="cancelOrder(${data.order.id}, '${data.order.order_number}')" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border: none; background: #dc3545; color: white; border-radius: 6px; cursor: pointer; font-size: 14px;">
                                        <i class="fas fa-times"></i>
                                        <span>Cancel Order</span>
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: #333; margin-bottom: 20px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-route" style="color: #667eea;"></i> Order Tracking
                                </h3>
                                <div class="ec-track" id="ecTrack">
                                    <div class="ec-track-line">
                                        <div class="ec-track-fill" id="ecTrackFill"></div>
                                    </div>

                                    <div class="ec-track-step" data-step="1">
                                        <div class="ec-track-icon"><i class="fas fa-check"></i></div>
                                        <div class="ec-track-title">Order Placed</div>
                                        <div class="ec-track-meta" id="ecStep1Meta">Starting...</div>
                                    </div>

                                    <div class="ec-track-step" data-step="2">
                                        <div class="ec-track-icon"><i class="fas fa-check"></i></div>
                                        <div class="ec-track-title">Confirmed</div>
                                        <div class="ec-track-meta" id="ecStep2Meta">Pending</div>
                                    </div>

                                    <div class="ec-track-step" data-step="3">
                                        <div class="ec-track-icon"><i class="fas fa-box"></i></div>
                                        <div class="ec-track-title">Processing</div>
                                        <div class="ec-track-meta" id="ecStep3Meta">Pending</div>
                                    </div>

                                    <div class="ec-track-step" data-step="4">
                                        <div class="ec-track-icon"><i class="fas fa-truck"></i></div>
                                        <div class="ec-track-title">Shipped</div>
                                        <div class="ec-track-meta" id="ecStep4Meta">Pending</div>
                                    </div>

                                    <div class="ec-track-step" data-step="5">
                                        <div class="ec-track-icon"><i class="fas fa-home"></i></div>
                                        <div class="ec-track-title">Delivered</div>
                                        <div class="ec-track-meta" id="ecStep5Meta">Pending</div>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Step-by-step animation: line reaches step based on order status
                        const trackRoot = document.getElementById('ecTrack');
                        const fillEl = document.getElementById('ecTrackFill');
                        const steps = trackRoot ? Array.from(trackRoot.querySelectorAll('.ec-track-step')) : [];

                        const setStepState = (activeStep) => {
                            steps.forEach((s, idx) => {
                                const stepNo = idx + 1;
                                s.classList.remove('is-done', 'is-active');
                                if (stepNo < activeStep) s.classList.add('is-done');
                                if (stepNo === activeStep) s.classList.add('is-active');
                            });
                        };

                        // Fill widths: 0% (start), 0% (reach step1), 25% (reach step2), 50%, 75%, 100%
                        const widths = [0, 0, 25, 50, 75, 100];
                        
                        const status = (data.order.order_status || '').toLowerCase();
                        let targetStep = 1; // default to Order Placed
                        if (status === 'pending' || status === 'confirmed') targetStep = 2;
                        else if (status === 'processing') targetStep = 3;
                        else if (status === 'shipped') targetStep = 4;
                        else if (status === 'delivered') targetStep = 5;
                        else if (status === 'cancelled' || status === 'canceled') targetStep = 0; // no progress

                        let current = 1;
                        
                        // Start with all circles white/gray (no colors initially)
                        steps.forEach(s => s.classList.remove('is-done', 'is-active'));
                        
                        // start line from left slowly, then reach circles
                        if (fillEl) fillEl.style.width = widths[1] + '%';

                        const run = () => {
                            // Move fill to next step
                            const next = current + 1;
                            if (next > targetStep) return; // Stop at target step based on status

                            // show current step meta
                            const meta1 = document.getElementById('ecStep1Meta');
                            if (meta1 && current === 1) meta1.textContent = new Date(data.order.created_at).toLocaleDateString();

                            // animate line to next
                            if (fillEl) fillEl.style.width = widths[next] + '%';
                            // after line arrives, switch state
                            setTimeout(() => {
                                setStepState(next);
                                current = next;
                                // continue only if not at target step
                                if (current < targetStep) {
                                    setTimeout(run, 1200);
                                }
                            }, 1700);
                        };

                        // first: animate from start to step 1 (Order Placed)
                        setTimeout(() => {
                            // make line green when it reaches first circle
                            if (fillEl) {
                                fillEl.classList.add('is-active');
                                fillEl.style.width = widths[1] + '%';
                            }
                            
                            setTimeout(() => {
                                // set step 1 as done (green) - now it becomes green
                                steps.forEach(s => s.classList.remove('is-done', 'is-active'));
                                if (steps[0]) steps[0].classList.add('is-done');
                                
                                // Update meta texts based on status
                                const meta2 = document.getElementById('ecStep2Meta');
                                const meta3 = document.getElementById('ecStep3Meta');
                                const meta4 = document.getElementById('ecStep4Meta');
                                const meta5 = document.getElementById('ecStep5Meta');
                                
                                if (status === 'pending' || status === 'confirmed') {
                                    if (meta2) meta2.textContent = 'Confirmed';
                                } else if (status === 'processing') {
                                    if (meta2) meta2.textContent = 'Confirmed';
                                    if (meta3) meta3.textContent = 'Processing';
                                } else if (status === 'shipped') {
                                    if (meta2) meta2.textContent = 'Confirmed';
                                    if (meta3) meta3.textContent = 'Processing';
                                    if (meta4) meta4.textContent = 'Shipped';
                                } else if (status === 'delivered') {
                                    if (meta2) meta2.textContent = 'Confirmed';
                                    if (meta3) meta3.textContent = 'Processing';
                                    if (meta4) meta4.textContent = 'Shipped';
                                    if (meta5) meta5.textContent = 'Delivered';
                                } else if (status === 'cancelled' || status === 'canceled') {
                                    if (meta2) meta2.textContent = 'Cancelled';
                                }
                                
                                // continue line to target step
                                if (targetStep >= 2) {
                                    if (fillEl) fillEl.style.width = widths[targetStep] + '%';
                                    setTimeout(() => {
                                        setStepState(targetStep);
                                        current = targetStep;
                                    }, 1700);
                                }
                            }, 800);
                        }, 600);
                    } else {
                        content.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: #e74c3c;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 15px;"></i>
                                <div style="font-size: 18px; font-weight: 600;">Failed to load order details</div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #e74c3c;">
                            <i class="fas fa-wifi" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <div style="font-size: 18px; font-weight: 600;">Error loading order details</div>
                        </div>
                    `;
                });
        }
        
        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }
        
        // Download Order History
        function downloadOrderHistory() {
            fetch('download_order_history.php')
                .then(response => {
                    if (response.ok) {
                        return response.blob();
                    }
                    throw new Error('Failed to download order history');
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'order_history.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                })
                .catch(error => {
                    alert('Error downloading order history: ' + error.message);
                });
        }
        
        // Download Invoice
        function downloadInvoice(orderId) {
            window.open(`download_invoice.php?order_id=${orderId}`, '_blank');
        }
        
        // Cancel Order
        function cancelOrder(orderId, orderNumber) {
            if (confirm(`Are you sure you want to cancel order ${orderNumber}? This action cannot be undone.`)) {
                console.log('Attempting to cancel order:', orderId);
                
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        location.reload(); // Refresh to show updated status
                    } else {
                        alert('Error cancelling order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling order: ' + error.message);
                });
            }
        }
        
        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>