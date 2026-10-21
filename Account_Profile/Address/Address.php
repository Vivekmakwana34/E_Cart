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

function get_user_data_file_path_address($userId) {
    return __DIR__ . '/../Profile/user_data/' . $userId . '.json';
}

function read_user_data_address($userId) {
    $path = get_user_data_file_path_address($userId);
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$userData = read_user_data_address($userId);
$profileInitials = (string)($userData['profile_initials'] ?? '');
if ($profileInitials !== '' && preg_match('/^[A-Za-z]{1,3}$/', $profileInitials)) {
    $initials = strtoupper($profileInitials);
} else {
    $initials = make_initials($user['full_name'] ?? '');
}

$profileTagline = trim((string)($userData['profile_tagline'] ?? ''));

$avatarFile = (string)($userData['profile_image'] ?? '');
$avatarUrl = $avatarFile !== '' ? '../../assets/img/' . $avatarFile : '';

$addresses = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_address_id'])) {
    $deleteId = (int)$_POST['delete_address_id'];
    if ($deleteId > 0) {
        $delStmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        if ($delStmt) {
            $delStmt->bind_param('ii', $deleteId, $userId);
            $delStmt->execute();
        }
    }
    header('Location: Address.php');
    exit();
}

$addrStmt = $conn->prepare("SELECT id, address_type, address_line1, address_line2, city, state, postal_code, country, phone, is_default FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
if ($addrStmt) {
    $addrStmt->bind_param('i', $userId);
    $addrStmt->execute();
    $addrRes = $addrStmt->get_result();
    if ($addrRes) {
        while ($row = $addrRes->fetch_assoc()) {
            $addresses[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses - E_Cart</title>
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

        .profile-address-card {
            padding: 12px 0 4px;
            border-top: 1px solid #e5e7eb;
        }

        .profile-address-actions {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .address-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            color: #374151;
            cursor: pointer;
        }

        .address-action-btn i {
            font-size: 13px;
        }

        .address-action-edit {
            border-color: #bfdbfe;
            background-color: #eff6ff;
            color: #1d4ed8;
        }

        .address-action-delete {
            border-color: #fecaca;
            background-color: #fef2f2;
            color: #b91c1c;
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
        }

        @media (max-width: 600px) {
            .profile-main {
                padding: 16px 10px 26px;
            }

            .profile-user-row {
                align-items: flex-start;
            }

            .profile-avatar {
                width: 64px;
                height: 64px;
                font-size: 24px;
            }

            .profile-user-text h2 {
                font-size: 17px;
            }

            .profile-section-header h2 {
                font-size: 15px;
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
                        <a href="Address.php" class="profile-menu-item profile-menu-active">
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
                        <h2>Saved Addresses</h2>
                        <button type="button" onclick="window.location.href='add_address.php'">Add New Address</button>
                    </div>

                    <?php if (empty($addresses)): ?>
                        <div style="padding: 14px; color: #6b7280; font-size: 14px;">
                            No saved addresses yet. Click "Add New Address" to add one.
                        </div>
                    <?php else: ?>
                        <?php foreach ($addresses as $addr): ?>
                            <?php
                                $type = strtoupper((string)($addr['address_type'] ?? 'HOME'));
                                $line1 = trim((string)($addr['address_line1'] ?? ''));
                                $line2 = trim((string)($addr['address_line2'] ?? ''));
                                $city = trim((string)($addr['city'] ?? ''));
                                $state = trim((string)($addr['state'] ?? ''));
                                $postal = trim((string)($addr['postal_code'] ?? ''));
                                $country = trim((string)($addr['country'] ?? 'India'));
                                $phone = trim((string)($addr['phone'] ?? ''));
                                $isDefault = (int)($addr['is_default'] ?? 0) === 1;

                                $addressText = $line1;
                                if ($line2 !== '') $addressText .= ', ' . $line2;
                                $addressText .= ', ' . $city . ', ' . $state;
                                if ($postal !== '') $addressText .= ' - ' . $postal;
                                if ($country !== '') $addressText .= ', ' . $country;
                            ?>
                            <div class="profile-address-card">
                                <div class="profile-address-label"><?php echo htmlspecialchars($type); ?></div>
                                <div class="profile-address-text"><?php echo htmlspecialchars($addressText); ?></div>
                                <div class="profile-address-meta">
                                    <?php if ($phone !== ''): ?>
                                        <?php echo htmlspecialchars($phone); ?>
                                    <?php endif; ?>
                                    <?php if ($isDefault): ?>
                                        <?php echo $phone !== '' ? ' • ' : ''; ?>Default delivery address
                                    <?php endif; ?>
                                </div>
                                <div class="profile-address-actions">
                                    <button type="button" class="address-action-btn address-action-edit" onclick="window.location.href='address_edit.php?id=<?php echo (int)$addr['id']; ?>'">
                                        <i class="fa-solid fa-pen"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form method="post" style="display:inline; margin:0;">
                                        <input type="hidden" name="delete_address_id" value="<?php echo (int)$addr['id']; ?>">
                                        <button type="submit" class="address-action-btn address-action-delete" onclick="return confirm('Delete this address?');">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

     <?php include "../../Footer.php"; ?>


    <script src="../../assets/js/index.js"></script>
     <a href="../../index.php" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>
</body>

</html>