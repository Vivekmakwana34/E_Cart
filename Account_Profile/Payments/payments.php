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

function get_user_data_file_path_payments($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_payments($userId) {
    $path = get_user_data_file_path_payments($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_payments($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

// Initialize payment data structure
if (!isset($userData['payments'])) {
    $userData['payments'] = [
        'cards' => [],
        'upi_ids' => [],
        'wallet_balance' => 0.00,
        'default_payment' => 'upi',
        'cod_enabled' => true,
        'billing_address' => 'primary'
    ];
}

$payments = &$userData['payments'];

// Ensure default sample data for demo if empty AND no file exists
if (empty($payments['cards']) && !file_exists(get_user_data_file_path_payments($userId))) {
    $payments['cards'] = [
        [
            'id' => 1,
            'type' => 'credit',
            'bank' => 'HDFC',
            'number' => '1234',
            'expiry' => '08/27',
            'name' => $user['full_name'] ?? 'User',
            'is_default' => true
        ],
        [
            'id' => 2,
            'type' => 'debit',
            'bank' => 'SBI',
            'number' => '9988',
            'expiry' => '01/29',
            'name' => $user['full_name'] ?? 'User',
            'is_default' => false
        ]
    ];
}

if (empty($payments['upi_ids']) && !file_exists(get_user_data_file_path_payments($userId))) {
    $payments['upi_ids'] = [
        [
            'id' => 1,
            'upi_id' => 'akash@upi',
            'provider' => 'PhonePe',
            'bank' => 'HDFC Bank',
            'verified' => true,
            'is_default' => true
        ],
        [
            'id' => 2,
            'upi_id' => '9106536420@paytm',
            'provider' => 'Paytm',
            'bank' => 'SBI Bank',
            'verified' => true,
            'is_default' => false
        ]
    ];
}

if ($payments['wallet_balance'] == 0 && !file_exists(get_user_data_file_path_payments($userId))) {
    $payments['wallet_balance'] = 250.00;
}

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_card') {
        $newCard = [
            'id' => time(),
            'type' => $_POST['card_type'] ?? 'credit',
            'bank' => $_POST['bank_name'] ?? 'Bank',
            'number' => substr($_POST['card_number'] ?? '0000', -4),
            'expiry' => $_POST['expiry'] ?? '12/30',
            'name' => $_POST['card_name'] ?? $user['full_name'],
            'is_default' => empty($payments['cards'])
        ];
        $payments['cards'][] = $newCard;
        $message = 'Card added successfully!';
    }
    
    if ($action === 'add_upi') {
        $newUpi = [
            'id' => time(),
            'upi_id' => $_POST['upi_id'] ?? '',
            'provider' => $_POST['provider'] ?? 'UPI',
            'bank' => $_POST['bank'] ?? 'Bank',
            'verified' => true,
            'is_default' => empty($payments['upi_ids'])
        ];
        $payments['upi_ids'][] = $newUpi;
        $message = 'UPI ID added successfully!';
    }
    
    if ($action === 'remove_card') {
        $cardId = $_POST['card_id'] ?? 0;
        $payments['cards'] = array_filter($payments['cards'], function($c) use ($cardId) {
            return $c['id'] != $cardId;
        });
        $payments['cards'] = array_values($payments['cards']);
        $message = 'Card removed successfully!';
    }
    
    if ($action === 'remove_upi') {
        $upiId = $_POST['upi_id'] ?? 0;
        $payments['upi_ids'] = array_filter($payments['upi_ids'], function($u) use ($upiId) {
            return $u['id'] != $upiId;
        });
        $payments['upi_ids'] = array_values($payments['upi_ids']);
        $message = 'UPI ID removed successfully!';
    }
    
    if ($action === 'set_default_card') {
        $cardId = $_POST['card_id'] ?? 0;
        foreach ($payments['cards'] as &$card) {
            $card['is_default'] = ($card['id'] == $cardId);
        }
        $message = 'Default card updated!';
    }
    
    if ($action === 'set_default_upi') {
        $upiId = $_POST['upi_id'] ?? 0;
        foreach ($payments['upi_ids'] as &$upi) {
            $upi['is_default'] = ($upi['id'] == $upiId);
        }
        $message = 'Default UPI updated!';
    }
    
    if ($action === 'update_preferences') {
        $payments['default_payment'] = $_POST['default_payment'] ?? 'upi';
        $payments['cod_enabled'] = isset($_POST['cod_enabled']);
        $payments['billing_address'] = $_POST['billing_address'] ?? 'primary';
        $message = 'Preferences updated successfully!';
    }
    
    // Save to file
    $userData['payments'] = $payments;
    file_put_contents(get_user_data_file_path_payments($userId), json_encode($userData, JSON_PRETTY_PRINT));
}

// Helper functions
function getCardIcon($type) {
    return $type === 'credit' ? 'fa-credit-card' : 'fa-credit-card';
}

function getCardTypeLabel($type) {
    return $type === 'credit' ? 'Credit' : 'Debit';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - E_Cart</title>
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

        .payments-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 24px;
        }

        .payments-subtitle {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }

        .payment-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .payment-list-item {
            padding: 14px 16px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            background: #ffffff;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            flex-wrap: wrap;
        }

        .payment-list-item:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }

        .payment-list-main {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }

        .payment-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .payment-icon-circle.upi {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .payment-icon-circle.card {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
        }

        .payment-list-text-main {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }

        .payment-list-text-meta {
            font-size: 12px;
            color: #6b7280;
        }

        .payment-badges {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }

        .badge-default {
            padding: 3px 8px;
            border-radius: 999px;
            background-color: #dcfce7;
            color: #166534;
            font-weight: 600;
        }

        .badge-upi {
            padding: 3px 8px;
            border-radius: 999px;
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: 500;
        }

        .btn-chip {
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-chip i {
            font-size: 11px;
        }

        .btn-chip:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-chip.primary {
            background-color: #eff6ff;
            border-color: #dbeafe;
            color: #1d4ed8;
        }

        .btn-chip.primary:hover {
            background-color: #dbeafe;
        }

        .btn-chip.danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }

        .btn-chip.danger:hover {
            background-color: #fecaca;
        }

        .wallet-balance {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .wallet-meta {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.5;
        }

        .wallet-actions {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-small-primary,
        .btn-small-secondary {
            border-radius: 6px;
            padding: 7px 14px;
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

        .wallet-activity-list {
            list-style: none;
            margin: 10px 0 0;
            padding: 0;
            font-size: 12px;
            color: #4b5563;
        }

        .wallet-activity-item + .wallet-activity-item {
            margin-top: 4px;
        }

        .payments-preferences-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .payments-preferences-item-title {
            font-size: 13px;
            font-weight: 500;
            color: #111827;
        }

        .payments-preferences-item-meta {
            font-size: 12px;
            color: #6b7280;
        }

        .payments-toggle-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 12px;
            color: #374151;
        }

        .payments-toggle-row input[type="checkbox"] {
            width: 14px;
            height: 14px;
        }

        .payments-select {
            margin-top: 4px;
            padding: 7px 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            color: #111827;
        }

        .payments-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.3);
        }

        .recent-payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .recent-payments-table th,
        .recent-payments-table td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .recent-payments-table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }

        .recent-payments-table td {
            color: #111827;
        }

        .badge-status {
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-failed {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .payments-table-wrapper {
            width: 100%;
            overflow-x: auto;
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

            .payments-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 18px 10px 26px;
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

                        <a href="payments.php" class="profile-menu-item profile-menu-active">
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
                            <h2>Payments & Wallets</h2>
                            <p>Manage your saved cards, UPI IDs and payment preferences for faster checkout.</p>
                        </div>
                    </div>

                    <div class="payments-grid-2">
                        <div>
                            <div class="payments-subtitle">Saved Cards</div>
                            <ul class="payment-list">
                                <?php foreach ($payments['cards'] as $card): ?>
                                <li class="payment-list-item">
                                    <div class="payment-list-main">
                                        <div class="payment-icon-circle card">
                                            <i class="fa-regular fa-credit-card"></i>
                                        </div>
                                        <div>
                                            <div class="payment-list-text-main"><?php echo htmlspecialchars($card['bank'] . ' ' . getCardTypeLabel($card['type']) . ' Card •••• ' . $card['number']); ?></div>
                                            <div class="payment-list-text-meta">Expires <?php echo htmlspecialchars($card['expiry']); ?> • Name: <?php echo htmlspecialchars($card['name']); ?></div>
                                        </div>
                                    </div>
                                    <div class="payment-badges">
                                        <?php if ($card['is_default']): ?>
                                            <span class="badge-default">Default</span>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="set_default_card">
                                                <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                                <button type="submit" class="btn-chip primary"><i class="fa-solid fa-star"></i> Make Default</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Remove this card?');">
                                            <input type="hidden" name="action" value="remove_card">
                                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                            <button type="submit" class="btn-chip danger"><i class="fa-solid fa-trash"></i> Remove</button>
                                        </form>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div style="margin-top: 10px;">
                                <button type="button" class="btn-small-secondary" onclick="openModal('cardModal')"><i class="fa-solid fa-plus"></i> Add New Card</button>
                            </div>
                        </div>

                        <div>
                            <div class="payments-subtitle">Saved UPI IDs</div>
                            <ul class="payment-list">
                                <?php foreach ($payments['upi_ids'] as $upi): ?>
                                <li class="payment-list-item">
                                    <div class="payment-list-main">
                                        <div class="payment-icon-circle upi">
                                            <i class="fa-solid fa-indian-rupee-sign"></i>
                                        </div>
                                        <div>
                                            <div class="payment-list-text-main"><?php echo htmlspecialchars($upi['upi_id']); ?> (<?php echo htmlspecialchars($upi['provider']); ?>)</div>
                                            <div class="payment-list-text-meta">Linked to <?php echo htmlspecialchars($upi['bank']); ?> • <?php echo $upi['verified'] ? 'Verified' : 'Not Verified'; ?></div>
                                        </div>
                                    </div>
                                    <div class="payment-badges">
                                        <?php if ($upi['is_default']): ?>
                                            <span class="badge-default">Default</span>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="set_default_upi">
                                                <input type="hidden" name="upi_id" value="<?php echo $upi['id']; ?>">
                                                <button type="submit" class="btn-chip primary"><i class="fa-solid fa-star"></i> Make Default</button>
                                            </form>
                                        <?php endif; ?>
                                        <span class="badge-upi">UPI</span>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Remove this UPI ID?');">
                                            <input type="hidden" name="action" value="remove_upi">
                                            <input type="hidden" name="upi_id" value="<?php echo $upi['id']; ?>">
                                            <button type="submit" class="btn-chip danger"><i class="fa-solid fa-trash"></i> Remove</button>
                                        </form>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div style="margin-top: 10px;">
                                <button type="button" class="btn-small-secondary" onclick="openModal('upiModal')"><i class="fa-solid fa-plus"></i> Add New UPI ID</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="profile-section-header">
                        <div>
                            <h2>E_Cart Wallet</h2>
                        </div>
                    </div>
                    <div>
                        <div class="wallet-balance">₹ <?php echo number_format($payments['wallet_balance'], 2); ?></div>
                        <div class="wallet-meta">This balance will be auto-applied to your next eligible order.</div>
                        <div class="wallet-actions">
                            <button type="button" class="btn-small-primary"><i class="fa-solid fa-arrow-rotate-right"></i> Withdraw to Bank (demo)</button>
                            <button type="button" class="btn-small-secondary"><i class="fa-solid fa-circle-info"></i> View Wallet Terms</button>
                        </div>
                        <ul class="wallet-activity-list">
                            <li class="wallet-activity-item">+ ₹200 • Refund from Order #EC123456 • 2 days ago</li>
                            <li class="wallet-activity-item">+ ₹50 • Promotional credit • 1 week ago</li>
                        </ul>
                    </div>
                </div>

                <div class="profile-section">
                    <div class="profile-section-header">
                        <div>
                            <h2>Payment Preferences</h2>
                        </div>
                    </div>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="update_preferences">
                        <ul class="payments-preferences-list">
                            <li>
                                <div class="payments-preferences-item-title">Default payment method</div>
                                <div class="payments-preferences-item-meta">This will be pre-selected at checkout. You can always change it while placing an order.</div>
                                <select class="payments-select" name="default_payment">
                                    <?php foreach ($payments['upi_ids'] as $upi): ?>
                                        <option value="upi_<?php echo $upi['id']; ?>" <?php echo $payments['default_payment'] === 'upi_' . $upi['id'] ? 'selected' : ''; ?>>UPI - <?php echo htmlspecialchars($upi['upi_id']); ?></option>
                                    <?php endforeach; ?>
                                    <?php foreach ($payments['cards'] as $card): ?>
                                        <option value="card_<?php echo $card['id']; ?>" <?php echo $payments['default_payment'] === 'card_' . $card['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($card['bank'] . ' ' . getCardTypeLabel($card['type']) . ' Card •••• ' . $card['number']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </li>
                            <li>
                                <div class="payments-preferences-item-title">Cash on Delivery (COD)</div>
                                <div class="payments-preferences-item-meta">Allow COD on your orders where available.</div>
                                <div class="payments-toggle-row">
                                    <input type="checkbox" name="cod_enabled" <?php echo $payments['cod_enabled'] ? 'checked' : ''; ?>>
                                    <span>Enable COD for my account (subject to eligibility).</span>
                                </div>
                            </li>
                            <li>
                                <div class="payments-preferences-item-title">Billing address preference</div>
                                <div class="payments-preferences-item-meta">Choose which address is used for billing on online payments.</div>
                                <select class="payments-select" name="billing_address">
                                    <option value="primary" <?php echo $payments['billing_address'] === 'primary' ? 'selected' : ''; ?>>Use primary delivery address</option>
                                    <option value="ask" <?php echo $payments['billing_address'] === 'ask' ? 'selected' : ''; ?>>Ask me on every payment</option>
                                </select>
                            </li>
                        </ul>
                        <div style="margin-top: 15px;">
                            <button type="submit" class="btn-small-primary"><i class="fa-solid fa-save"></i> Save Preferences</button>
                        </div>
                    </form>
                </div>

                <div class="profile-section">
                    <div class="profile-section-header">
                        <div>
                            <h2>Recent Payments</h2>
                        </div>
                    </div>
                    <div class="payments-table-wrapper">
                        <table class="recent-payments-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#EC123456</td>
                                    <td>UPI - akash@upi</td>
                                    <td>₹ 1,299</td>
                                    <td>10 Jan 2026</td>
                                    <td><span class="badge-status badge-paid">Paid</span></td>
                                </tr>
                                <tr>
                                    <td>#EC987654</td>
                                    <td>HDFC Credit Card •••• 1234</td>
                                    <td>₹ 42,999</td>
                                    <td>25 Jan 2026</td>
                                    <td><span class="badge-status badge-pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>#EC567890</td>
                                    <td>UPI - 9106536420@paytm</td>
                                    <td>₹ 799</td>
                                    <td>5 Jan 2026</td>
                                    <td><span class="badge-status badge-failed">Failed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>
    
     <?php include "../../Footer.php"; ?>

    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>

    <!-- Add Card Modal -->
    <div id="cardModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3>Add New Card</h3>
                <button type="button" class="modal-close" onclick="closeModal('cardModal')">&times;</button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_card">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Card Type</label>
                        <select name="card_type" class="form-control" required>
                            <option value="credit">Credit Card</option>
                            <option value="debit">Debit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="e.g. HDFC, SBI, ICICI" required>
                    </div>
                    <div class="form-group">
                        <label>Card Number (Last 4 digits)</label>
                        <input type="text" name="card_number" class="form-control" maxlength="4" placeholder="1234" required>
                    </div>
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" name="expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>Card Holder Name</label>
                        <input type="text" name="card_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-small-secondary" onclick="closeModal('cardModal')">Cancel</button>
                    <button type="submit" class="btn-small-primary">Add Card</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add UPI Modal -->
    <div id="upiModal" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3>Add New UPI ID</h3>
                <button type="button" class="modal-close" onclick="closeModal('upiModal')">&times;</button>
            </div>
            <form method="post" action="">
                <input type="hidden" name="action" value="add_upi">
                <div class="modal-body">
                    <div class="form-group">
                        <label>UPI ID</label>
                        <input type="text" name="upi_id" class="form-control" placeholder="e.g. yourname@upi" required>
                    </div>
                    <div class="form-group">
                        <label>UPI Provider</label>
                        <select name="provider" class="form-control" required>
                            <option value="PhonePe">PhonePe</option>
                            <option value="Paytm">Paytm</option>
                            <option value="Google Pay">Google Pay</option>
                            <option value="Amazon Pay">Amazon Pay</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Linked Bank</label>
                        <input type="text" name="bank" class="form-control" placeholder="e.g. HDFC Bank, SBI Bank">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-small-secondary" onclick="closeModal('upiModal')">Cancel</button>
                    <button type="submit" class="btn-small-primary">Add UPI</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }
        .modal-body {
            padding: 20px 24px;
        }
        .modal-footer {
            padding: 16px 24px 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
    </style>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
