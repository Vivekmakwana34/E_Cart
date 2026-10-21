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

function get_user_data_file_path_gift($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_gift($userId) {
    $path = get_user_data_file_path_gift($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_gift($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

$message = '';
$error = '';

if (isset($_SESSION['gift_flash_message'])) {
    $message = (string)$_SESSION['gift_flash_message'];
    unset($_SESSION['gift_flash_message']);
}

if (isset($_SESSION['gift_flash_error'])) {
    $error = (string)$_SESSION['gift_flash_error'];
    unset($_SESSION['gift_flash_error']);
}

// Handle Gift Card Purchase
if (isset($_POST['purchase_gift_card'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $card_type = $_POST['card_type'] ?? 'general';
    
    if ($amount >= 100 && $amount <= 10000) {
        // Generate unique card code
        $card_code = 'GC-' . strtoupper(substr(uniqid(), -8)) . '-' . date('Y');
        $expiry_date = date('Y-m-d', strtotime('+1 year'));
        
        $stmt = $conn->prepare("INSERT INTO gift_cards (card_code, amount, balance, status, user_id, expiry_date, created_by) VALUES (?, ?, ?, 'active', ?, ?, ?)");
        $admin_id = 1; // System/admin as creator
        $stmt->bind_param('sddisi', $card_code, $amount, $amount, $userId, $expiry_date, $admin_id);
        
        if ($stmt->execute()) {
            $_SESSION['gift_flash_message'] = "Gift card purchased successfully! Your card code: " . $card_code;
            header('Location: gift.php');
            exit();
        } else {
            $_SESSION['gift_flash_error'] = "Failed to purchase gift card. Please try again.";
            header('Location: gift.php');
            exit();
        }
    } else {
        $_SESSION['gift_flash_error'] = "Invalid amount. Please enter between ₹100 and ₹10,000.";
        header('Location: gift.php');
        exit();
    }
}

// Fetch user's gift cards from database
$userGiftCards = [];
$totalBalance = 0;
$stmt = $conn->prepare("SELECT * FROM gift_cards WHERE user_id = ? AND user_id IS NOT NULL AND status = 'active' ORDER BY id DESC");
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userGiftCards[] = $row;
        $totalBalance += $row['balance'];
    }
}

// Fetch available gift cards (admin-created, not assigned to any user)
$availableCards = [];
$availResult = $conn->query("SELECT * FROM gift_cards WHERE user_id IS NULL AND status = 'active' ORDER BY id DESC");
if ($availResult) {
    while ($row = $availResult->fetch_assoc()) {
        $availableCards[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Gift Cards - E_Cart</title>
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

        .gift-tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .gift-tab {
            padding: 10px 0;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .gift-tab.active {
            color: #2874f0;
            border-bottom-color: #2874f0;
        }

        .gift-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .gift-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .gift-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .gift-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .gift-card-birthday {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .gift-card-festival {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .gift-card-corporate {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .gift-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .gift-card-brand {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .gift-card-logo {
            font-size: 24px;
            opacity: 0.8;
        }

        .gift-card-amount {
            font-size: 28px;
            font-weight: 700;
            margin: 16px 0;
        }

        .gift-card-code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .gift-card-meta {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 16px;
        }

        .gift-card-actions {
            display: flex;
            gap: 8px;
        }

        .gift-card-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .gift-card-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .gift-card-btn.primary {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .gift-card-btn.primary:hover {
            background: white;
        }

        .purchase-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .purchase-title {
            font-size: 16px;
            font-weight: 600;
            color: #212121;
            margin-bottom: 16px;
        }

        .amount-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .amount-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .amount-option:hover {
            border-color: #2874f0;
            background-color: #f0f5ff;
        }

        .amount-option.selected {
            border-color: #2874f0;
            background-color: #2874f0;
            color: white;
        }

        .amount-option-value {
            font-size: 18px;
            font-weight: 600;
        }

        .amount-option-label {
            font-size: 12px;
            opacity: 0.8;
        }

        .custom-amount {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .custom-amount input {
            flex: 1;
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
        }

        .custom-amount input:focus {
            outline: none;
            border-color: #2874f0;
        }

        .purchase-btn {
            width: 100%;
            padding: 12px;
            background-color: #2874f0;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .purchase-btn:hover {
            background-color: #1d4ed8;
        }

        .empty-gift-cards {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }

        .empty-gift-cards i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-gift-cards h3 {
            font-size: 16px;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-gift-cards p {
            font-size: 14px;
            margin-bottom: 16px;
        }

        .balance-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .balance-amount {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .balance-label {
            font-size: 13px;
            opacity: 0.9;
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

            .gift-card-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
            }

            .gift-tabs {
                gap: 12px;
            }

            .gift-tab {
                font-size: 13px;
            }

            .gift-card-grid {
                grid-template-columns: 1fr;
            }

            .amount-options {
                grid-template-columns: repeat(2, 1fr);
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

                         <a href="gift.php" class="profile-menu-item profile-menu-active">
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
                            <h2>My Gift Cards</h2>
                            <p>Buy gift cards for your loved ones or manage your existing cards</p>
                        </div>
                    </div>

                    <div class="balance-info">
                        <div class="balance-amount">₹<?php echo number_format($totalBalance, 0); ?></div>
                        <div class="balance-label">Total Gift Card Balance</div>
                    </div>

                    <?php if ($message): ?>
                        <div style="background: #e3fcef; color: #0e9f6e; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
                            <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div style="background: #fdecea; color: #e02424; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;">
                            <i class="fa-solid fa-exclamation-circle" style="margin-right: 8px;"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="gift-tabs">
                        <div class="gift-tab active" onclick="switchTab('my-cards', this)">My Cards (<?php echo count($userGiftCards); ?>)</div>
                        <div class="gift-tab" onclick="switchTab('available-cards', this)">Available Cards (<?php echo count($availableCards); ?>)</div>
                        <div class="gift-tab" onclick="switchTab('buy-cards', this)">Buy New Card</div>
                        <div class="gift-tab" onclick="switchTab('transactions', this)">Transactions</div>
                    </div>

                    <div id="my-cards" class="gift-card-grid">
                        <?php if (empty($userGiftCards)): ?>
                            <div class="empty-gift-cards">
                                <i class="fa-solid fa-gift"></i>
                                <h3>No Gift Cards Yet</h3>
                                <p>You don't have any gift cards. Check available cards or buy a new one!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($userGiftCards as $card): ?>
                                <div class="gift-card">
                                    <div class="gift-card-header">
                                        <div class="gift-card-brand">E_CART</div>
                                        <div class="gift-card-logo">🎁</div>
                                    </div>
                                    <div class="gift-card-amount">₹<?php echo number_format($card['balance'], 0); ?></div>
                                    <div class="gift-card-code"><?php echo htmlspecialchars($card['card_code']); ?></div>
                                    <div class="gift-card-meta">Valid until: <?php echo date('M d, Y', strtotime($card['expiry_date'])); ?></div>
                                    <div class="gift-card-actions">
                                        <button class="gift-card-btn" onclick="viewDetails('<?php echo htmlspecialchars($card['card_code']); ?>')">Details</button>
                                        <button class="gift-card-btn primary" onclick="useCard('<?php echo htmlspecialchars($card['card_code']); ?>')">Use Now</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="available-cards" class="gift-card-grid" style="display: none;">
                        <?php if (empty($availableCards)): ?>
                            <div class="empty-gift-cards">
                                <i class="fa-solid fa-store"></i>
                                <h3>No Available Cards</h3>
                                <p>No admin gift cards available right now. Buy a new card to get started!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($availableCards as $card): ?>
                                <div class="gift-card">
                                    <div class="gift-card-header">
                                        <div class="gift-card-brand">E_CART</div>
                                        <div class="gift-card-logo">🎁</div>
                                    </div>
                                    <div class="gift-card-amount">₹<?php echo number_format($card['balance'], 0); ?></div>
                                    <div class="gift-card-code"><?php echo htmlspecialchars($card['card_code']); ?></div>
                                    <div class="gift-card-meta">Valid until: <?php echo date('M d, Y', strtotime($card['expiry_date'])); ?></div>
                                    <div class="gift-card-actions">
                                        <button class="gift-card-btn" onclick="viewDetails('<?php echo htmlspecialchars($card['card_code']); ?>')">Details</button>
                                        <button class="gift-card-btn primary" onclick="claimCard('<?php echo htmlspecialchars($card['card_code']); ?>')">Claim Card</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div id="buy-cards" style="display: none;">
                        <form method="POST" class="purchase-section">
                            <div class="purchase-title">Choose Gift Card Amount</div>
                            <div class="amount-options">
                                <div class="amount-option" onclick="selectAmount(this, 500)">
                                    <div class="amount-option-value">₹500</div>
                                    <div class="amount-option-label">Basic</div>
                                </div>
                                <div class="amount-option selected" onclick="selectAmount(this, 1000)">
                                    <div class="amount-option-value">₹1,000</div>
                                    <div class="amount-option-label">Popular</div>
                                </div>
                                <div class="amount-option" onclick="selectAmount(this, 2000)">
                                    <div class="amount-option-value">₹2,000</div>
                                    <div class="amount-option-label">Premium</div>
                                </div>
                                <div class="amount-option" onclick="selectAmount(this, 5000)">
                                    <div class="amount-option-value">₹5,000</div>
                                    <div class="amount-option-label">Deluxe</div>
                                </div>
                            </div>
                            <div class="custom-amount">
                                <input type="number" id="customAmount" placeholder="Enter custom amount (₹100-₹10000)" min="100" max="10000">
                                <button type="button" class="gift-card-btn" onclick="setCustomAmount()">Set Amount</button>
                            </div>
                            <div class="purchase-title" style="margin-top: 20px;">Choose Card Type</div>
                            <div class="amount-options">
                                <div class="amount-option selected" onclick="selectCardType(this, 'general')">
                                    <div class="amount-option-value">🎁</div>
                                    <div class="amount-option-label">General</div>
                                </div>
                                <div class="amount-option" onclick="selectCardType(this, 'birthday')">
                                    <div class="amount-option-value">🎂</div>
                                    <div class="amount-option-label">Birthday</div>
                                </div>
                                <div class="amount-option" onclick="selectCardType(this, 'festival')">
                                    <div class="amount-option-value">🎉</div>
                                    <div class="amount-option-label">Festival</div>
                                </div>
                                <div class="amount-option" onclick="selectCardType(this, 'corporate')">
                                    <div class="amount-option-value">💼</div>
                                    <div class="amount-option-label">Corporate</div>
                                </div>
                            </div>
                            <input type="hidden" name="amount" id="purchaseAmount" value="1000">
                            <input type="hidden" name="card_type" id="purchaseCardType" value="general">
                            <button type="submit" name="purchase_gift_card" class="purchase-btn">
                                <i class="fa-solid fa-shopping-cart"></i> Purchase Gift Card - ₹1,000
                            </button>
                        </form>
                    </div>

                    <div id="transactions" style="display: none;">
                        <div class="empty-gift-cards">
                            <i class="fa-solid fa-receipt"></i>
                            <h3>No Transactions Yet</h3>
                            <p>Your gift card transaction history will appear here</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

        <?php include "../../Footer.php"; ?>

    <script>
        let selectedAmount = 1000;
        let selectedCardType = 'general';

        function switchTab(tabName, element) {
            // Hide all tab contents
            document.getElementById('my-cards').style.display = 'none';
            document.getElementById('available-cards').style.display = 'none';
            document.getElementById('buy-cards').style.display = 'none';
            document.getElementById('transactions').style.display = 'none';
            
            // Remove active class from all tabs
            document.querySelectorAll('.gift-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab and mark as active
            if (tabName === 'my-cards' || tabName === 'available-cards') {
                document.getElementById(tabName).style.display = 'grid';
            } else {
                document.getElementById(tabName).style.display = 'block';
            }
            element.classList.add('active');
        }

        function selectAmount(element, amount) {
            // Remove selected class from all amount options
            document.querySelectorAll('.amount-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            selectedAmount = amount;
            
            // Update hidden input
            document.getElementById('purchaseAmount').value = amount;
            
            // Update purchase button
            updatePurchaseButton();
            
            // Clear custom amount input
            document.getElementById('customAmount').value = '';
        }

        function setCustomAmount() {
            const customAmount = document.getElementById('customAmount').value;
            if (customAmount >= 100 && customAmount <= 10000) {
                selectedAmount = parseInt(customAmount);
                
                // Update hidden input
                document.getElementById('purchaseAmount').value = customAmount;
                
                // Remove selected class from preset options
                document.querySelectorAll('.amount-option').forEach(option => {
                    option.classList.remove('selected');
                });
                
                updatePurchaseButton();
            } else {
                alert('Please enter an amount between ₹100 and ₹10,000');
            }
        }

        function selectCardType(element, type) {
            // Remove selected class from all card type options
            document.querySelectorAll('.amount-option').forEach(option => {
                if (option.onclick.toString().includes('selectCardType')) {
                    option.classList.remove('selected');
                }
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            selectedCardType = type;
            
            // Update hidden input
            document.getElementById('purchaseCardType').value = type;
        }

        function updatePurchaseButton() {
            const purchaseBtn = document.querySelector('.purchase-btn');
            purchaseBtn.innerHTML = `<i class="fa-solid fa-shopping-cart"></i> Purchase Gift Card - ₹${selectedAmount}`;
        }

        function purchaseGiftCard() {
            alert(`Gift card purchased successfully!\nAmount: ₹${selectedAmount}\nType: ${selectedCardType}\n\nYou will receive the gift card code via email and SMS.`);
        }

        function viewDetails(cardCode) {
            alert(`Gift Card Details:\n\nCard Code: ${cardCode}\nStatus: Active\nBalance: ₹500\nPurchased: Jan 15, 2025\nExpires: Dec 31, 2025`);
        }

        function useCard(cardCode) {
            if (confirm(`Use gift card ${cardCode} for your current cart?`)) {
                // Redirect to cart with gift card code
                window.location.href = `../../cart.php?giftCard=${cardCode}`;
            }
        }
    </script>

    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>
</html>