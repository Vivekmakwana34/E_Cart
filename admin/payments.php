<!DOCTYPE html>
<html lang="en">
<?php
// Database connection
require_once __DIR__ . '/../Database_file/config.php';

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment_status') {
    $orderId = $_POST['order_id'] ?? '';
    $newStatus = $_POST['payment_status'] ?? '';
    
    if ($orderId && $newStatus) {
        $updateQuery = "UPDATE orders SET payment_status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to refresh the page
        header('Location: payments.php?success=status_updated');
        exit;
    }
}

// Fetch all users with their payment data
$usersQuery = "SELECT id, full_name, email FROM users ORDER BY id DESC";
$usersResult = $conn->query($usersQuery);
$users = [];

if ($usersResult && $usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        // Get user payment data from JSON file
        $userDataFile = '../Account_Profile/Profile/user_data/' . $row['id'] . '.json';
        $paymentData = [
            'cards' => [],
            'upi_ids' => [],
            'wallet_balance' => 0
        ];
        
        if (file_exists($userDataFile)) {
            $jsonData = json_decode(file_get_contents($userDataFile), true);
            if (isset($jsonData['payments'])) {
                $paymentData = $jsonData['payments'];
            }
        }
        
        $row['payments'] = $paymentData;
        $row['total_cards'] = count($paymentData['cards'] ?? []);
        $row['total_upi'] = count($paymentData['upi_ids'] ?? []);
        $row['wallet_balance'] = $paymentData['wallet_balance'] ?? 0;
        $users[] = $row;
    }
}

// Handle search
$search = $_GET['search'] ?? '';
if ($search) {
    $users = array_filter($users, function($u) use ($search) {
        return stripos($u['full_name'], $search) !== false || 
               stripos($u['email'], $search) !== false ||
               stripos((string)$u['id'], $search) !== false;
    });
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Management - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-color: #f0f2f5;
            color: #333333;
            min-height: 100vh;
        }

        .container {
            display: flex;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 290px;
            background: transparent;
        }


        .container {
            display: flex;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 290px;
            background: transparent;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
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
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .modal-close:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .modal-body {
            padding: 20px 24px;
        }

        .modal-actions {
            padding: 16px 24px 20px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .detail-value {
            color: #6b7280;
        }

        .card-list, .upi-list {
            margin-top: 15px;
        }

        .card-list h4, .upi-list h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #374151;
        }

        .card-item, .upi-item {
            padding: 10px 12px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header {
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .payment-management {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .payment-section {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px 22px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: box-shadow 0.2s ease;
        }

        .payment-section:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .payment-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .payment-section-header h2 {
            font-size: 18px;
            margin: 0;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .search-box input {
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
            width: 280px;
            padding: 4px 8px;
        }

        .search-box input::placeholder {
            color: #9ca3af;
        }

        .search-box button,
        .search-box .btn-clear {
            background: #ff9800;
            border: none;
            color: #ffffff;
            cursor: pointer;
            padding: 6px 14px;
            font-size: 13px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-weight: 500;
            text-decoration: none;
        }

        .search-box button:hover {
            background: #f57c00;
        }

        .search-box .btn-clear {
            background: #e5e7eb;
            color: #374151;
        }

        .search-box .btn-clear:hover {
            background: #d1d5db;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .stat-card h3 {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 8px 0;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .payment-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .payment-table th,
        .payment-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .payment-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .payment-table thead tr:first-child th:first-child {
            border-top-left-radius: 12px;
        }

        .payment-table thead tr:first-child th:last-child {
            border-top-right-radius: 12px;
        }

        .payment-table tbody tr {
            transition: all 0.2s ease;
        }

        .payment-table tbody tr:hover {
            background-color: #f8fafc;
            transform: scale(1.002);
        }

        .payment-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 12px;
        }

        .payment-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 12px;
        }

        .payment-table tbody tr:last-child td {
            border-bottom: none;
        }

        .payment-table th:nth-child(4),
        .payment-table td:nth-child(4),
        .payment-table th:nth-child(5),
        .payment-table td:nth-child(5),
        .payment-table th:nth-child(6),
        .payment-table td:nth-child(6) {
            text-align: center;
        }

        .payment-table th:last-child,
        .payment-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .payment-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .payment-table td:last-child .btn {
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #f57c00;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #333333;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-update-payment-status {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-update-payment-status:hover {
            background-color: #1d4ed8;
            color: #ffffff;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.01em;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-enabled {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-disabled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-payment-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-payment-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-payment-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-payment-refunded {
            background-color: #e5edff;
            color: #1d4ed8;
        }

        .status-zero {
            background-color: #f1f5f9;
            color: #64748b;
        }

        .user-details-modal .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .user-details-modal .detail-row:last-child {
            border-bottom: none;
        }

        .user-details-modal .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .user-details-modal .detail-value {
            color: #6b7280;
        }

        @media (max-width: 1024px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .payment-management {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0;
                padding: 12px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .payment-section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box input {
                width: 200px;
            }

            .payment-section-header button {
                width: 100%;
            }

            .payment-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .payment-table thead,
            .payment-table tbody,
            .payment-table tr,
            .payment-table th,
            .payment-table td {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav class="navbar" id="sidebarNav">
            <div class="navbar-header">
                <h2>Admin Panel</h2>
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <ul>
                <li><a href="home.php"><i class="fa-solid fa-gauge" style="margin-right: 8px;"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users" style="margin-right: 8px;"></i>Users</a></li>
                <li><a href="banner.php"><i class="fa-solid fa-image" style="margin-right: 8px;"></i>Banner</a></li>
                <li><a href="categories.php"><i class="fa-solid fa-list" style="margin-right: 8px;"></i>Categories</a></li>
                <li><a href="products.php"><i class="fa-solid fa-box" style="margin-right: 8px;"></i>Products</a></li>
                <li><a href="orders.php"><i class="fa-solid fa-cart-shopping" style="margin-right: 8px;"></i>Orders</a></li>
                <li><a href="coupons.php"><i class="fa-solid fa-ticket" style="margin-right: 8px;"></i>Coupons</a></li>
                <li><a href="Gift.php"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i>Gift Cards</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-credit-card" style="margin-right: 8px;"></i>Payments</a></li>
                <li><a href="shipping.php"><i class="fa-solid fa-truck" style="margin-right: 8px;"></i>Shipping</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i>Reports</a></li>
                <li><a href="reviews.php"><i class="fa-solid fa-star" style="margin-right: 8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Payment Management</h1>
            </header>

            <?php
            // Calculate stats
            $totalUsers = count($users);
            $totalCards = array_sum(array_column($users, 'total_cards'));
            $totalUpi = array_sum(array_column($users, 'total_upi'));
            $totalWalletBalance = array_sum(array_column($users, 'wallet_balance'));
            ?>

            <div class="stats-cards">
                <div class="stat-card">
                    <h3>Total Users with Payments</h3>
                    <div class="value"><?php echo $totalUsers; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Saved Cards</h3>
                    <div class="value"><?php echo $totalCards; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total UPI IDs</h3>
                    <div class="value"><?php echo $totalUpi; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Wallet Balance</h3>
                    <div class="value">₹<?php echo number_format($totalWalletBalance, 2); ?></div>
                </div>
            </div>

            <section class="payment-management">
                <div class="payment-section">
                    <div class="payment-section-header">
                        <h2>User Payment Details</h2>
                        <form method="get" class="search-box" onsubmit="return false;">
                            <i class="fa-solid fa-search" style="color: #9ca3af; font-size: 16px;"></i>
                            <input type="text" name="search" id="searchInput" placeholder="Search by User ID, Name, Email..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                            <a href="payments.php" class="btn-clear" id="clearSearch" style="display: <?php echo $search ? 'flex' : 'none'; ?>;" title="Clear search"><i class="fa-solid fa-times"></i></a>
                        </form>
                    </div>  

                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Saved Cards</th>
                                <th>UPI IDs</th>
                                <th>Wallet Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>#<?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td style="text-align: center;">
                                            <span class="status-badge <?php echo $user['total_cards'] > 0 ? 'status-enabled' : 'status-zero'; ?>"><?php echo $user['total_cards']; ?> Cards</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="status-badge <?php echo $user['total_upi'] > 0 ? 'status-payment-paid' : 'status-zero'; ?>"><?php echo $user['total_upi']; ?> UPI</span>
                                        </td>
                                        <td style="text-align: center;">
                                            <strong>₹<?php echo number_format($user['wallet_balance'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="showUserDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fa-solid fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Payments Section -->
            <section class="payment-section" style="margin-top: 60px; margin-bottom: 20px;">
                <div class="payment-section-header">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="margin-right: 8px; color: #ff9800;"></i>Recent Payments</h2>
                </div>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch one order of each status (paid, pending, failed)
                        $recentOrders = [];
                        
                        // Get one paid order
                        $paidQuery = "SELECT o.id, o.user_id, o.total_amount, o.payment_method, o.payment_status, o.created_at, u.full_name 
                                     FROM orders o 
                                     LEFT JOIN users u ON o.user_id = u.id 
                                     WHERE o.payment_status = 'paid'
                                     ORDER BY o.created_at DESC 
                                     LIMIT 1";
                        $paidResult = $conn->query($paidQuery);
                        if ($paidResult && $paidResult->num_rows > 0) {
                            $recentOrders[] = $paidResult->fetch_assoc();
                        }
                        
                        // Get one pending order
                        $pendingQuery = "SELECT o.id, o.user_id, o.total_amount, o.payment_method, o.payment_status, o.created_at, u.full_name 
                                        FROM orders o 
                                        LEFT JOIN users u ON o.user_id = u.id 
                                        WHERE o.payment_status = 'pending'
                                        ORDER BY o.created_at DESC 
                                        LIMIT 1";
                        $pendingResult = $conn->query($pendingQuery);
                        if ($pendingResult && $pendingResult->num_rows > 0) {
                            $recentOrders[] = $pendingResult->fetch_assoc();
                        }
                        
                        // Get one failed order
                        $failedQuery = "SELECT o.id, o.user_id, o.total_amount, o.payment_method, o.payment_status, o.created_at, u.full_name 
                                       FROM orders o 
                                       LEFT JOIN users u ON o.user_id = u.id 
                                       WHERE o.payment_status = 'failed'
                                       ORDER BY o.created_at DESC 
                                       LIMIT 1";
                        $failedResult = $conn->query($failedQuery);
                        if ($failedResult && $failedResult->num_rows > 0) {
                            $recentOrders[] = $failedResult->fetch_assoc();
                        }
                        
                        if (!empty($recentOrders)):
                            foreach ($recentOrders as $order):
                        ?>
                            <tr data-order-id="<?php echo $order['id']; ?>">
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['full_name'] ?? 'Unknown'); ?></td>
                                <td style="text-align: center;"><strong>₹<?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                                <td style="text-align: center;">
                                    <span class="status-badge 
                                        <?php 
                                        $status = $order['payment_status'] ?? 'pending';
                                        if ($status === 'paid') echo 'status-payment-paid';
                                        elseif ($status === 'pending') echo 'status-payment-pending';
                                        elseif ($status === 'failed') echo 'status-payment-failed';
                                        elseif ($status === 'refunded') echo 'status-payment-refunded';
                                        else echo 'status-payment-pending';
                                        ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openEditStatusModal(<?php echo $order['id']; ?>, '<?php echo $status; ?>')">
                                        <i class="fa-solid fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">No recent payments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
        
    </div>

    

    <!-- Edit Payment Status Modal -->
    <div id="editStatusModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Update Payment Status</h3>
                <button type="button" class="modal-close" onclick="closeEditStatusModal()">&times;</button>
            </div>
            <form method="post" action="payments.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_payment_status">
                    <input type="hidden" name="order_id" id="editOrderId">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Payment Status</label>
                        <select name="payment_status" id="editPaymentStatus" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;">
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditStatusModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal-overlay" style="display: none;">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3>User Payment Details</h3>
                <button type="button" class="modal-close" onclick="closeUserDetails()">&times;</button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeUserDetails()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Real-time search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearch');
        const tableRows = document.querySelectorAll('.payment-table tbody tr');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                // Show/hide clear button
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = searchTerm ? 'flex' : 'none';
                }
                
                // Filter table rows
                tableRows.forEach(row => {
                    const userId = row.cells[0]?.textContent?.toLowerCase() || '';
                    const userName = row.cells[1]?.textContent?.toLowerCase() || '';
                    const email = row.cells[2]?.textContent?.toLowerCase() || '';
                    
                    if (userId.includes(searchTerm) || userName.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.dispatchEvent(new Event('input'));
                }
                // Reload page to clear PHP search parameter
                window.location.href = 'payments.php';
            });
        }

        // Highlight current page in sidebar
        const navbar = document.getElementById('sidebarNav');
        const navbarToggle = document.getElementById('navbarToggle');
        if (navbar && navbarToggle) {
            navbarToggle.addEventListener('click', function () {
                navbar.classList.toggle('open');
            });
        }

        if (navbar) {
            const links = navbar.querySelectorAll('a');
            const currentPage = window.location.pathname.split('/').pop();

            links.forEach(link => {
                const href = link.getAttribute('href');
                if (!href) return;
                const hrefPage = href.split('/').pop();
                if (hrefPage === currentPage) {
                    link.classList.add('active');
                }
            });
        }

        // Edit Payment Status Modal
        function openEditStatusModal(orderId, currentStatus) {
            document.getElementById('editOrderId').value = orderId;
            document.getElementById('editPaymentStatus').value = currentStatus;
            document.getElementById('editStatusModal').style.display = 'flex';
        }

        function closeEditStatusModal() {
            document.getElementById('editStatusModal').style.display = 'none';
        }

        // User Details Modal
        function showUserDetails(user) {
            const modal = document.getElementById('userDetailsModal');
            const content = document.getElementById('userDetailsContent');
            
            let cardsHtml = '';
            if (user.payments && user.payments.cards && user.payments.cards.length > 0) {
                cardsHtml = '<div class="card-list"><h4>Saved Cards:</h4>';
                user.payments.cards.forEach(function(card) {
                    cardsHtml += '<div class="card-item">' +
                        '<span><strong>' + card.bank + ' ' + (card.type === 'credit' ? 'Credit' : 'Debit') + ' Card</strong> ending in ' + card.number + ' (Exp: ' + card.expiry + ')</span>' +
                        (card.is_default ? '<span class="status-badge status-enabled">Default</span>' : '') +
                    '</div>';
                });
                cardsHtml += '</div>';
            }
            
            let upiHtml = '';
            if (user.payments && user.payments.upi_ids && user.payments.upi_ids.length > 0) {
                upiHtml = '<div class="upi-list"><h4>UPI IDs:</h4>';
                user.payments.upi_ids.forEach(function(upi) {
                    upiHtml += '<div class="upi-item">' +
                        '<span><strong>' + upi.upi_id + '</strong> (' + upi.provider + ') - ' + upi.bank + '</span>' +
                        (upi.is_default ? '<span class="status-badge status-enabled">Default</span>' : '') +
                    '</div>';
                });
                upiHtml += '</div>';
            }
            
            content.innerHTML = 
                '<div class="detail-row">' +
                    '<span class="detail-label">User ID:</span>' +
                    '<span class="detail-value">#' + user.id + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="detail-label">Full Name:</span>' +
                    '<span class="detail-value">' + user.full_name + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="detail-label">Email:</span>' +
                    '<span class="detail-value">' + user.email + '</span>' +
                '</div>' +
                '<div class="detail-row">' +
                    '<span class="detail-label">Wallet Balance:</span>' +
                    '<span class="detail-value"><strong>Rs ' + parseFloat(user.wallet_balance).toFixed(2) + '</strong></span>' +
                '</div>' +
                cardsHtml + upiHtml;
            
            modal.style.display = 'flex';
        }

        function closeUserDetails() {
            document.getElementById('userDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userDetailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<script>
            if (cancelEditMethodBtn && editMethodModalBackdrop) {
                cancelEditMethodBtn.addEventListener('click', function () {
                    editMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingEdited = null;
                });
            }

            if (closeEditMethodBtn && editMethodModalBackdrop) {
                closeEditMethodBtn.addEventListener('click', function () {
                    editMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingEdited = null;
                });
            }

            if (cancelDeleteMethodBtn && deleteMethodModalBackdrop) {
                cancelDeleteMethodBtn.addEventListener('click', function () {
                    deleteMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingDeleted = null;
                });
            }

            if (closeDeleteMethodBtn && deleteMethodModalBackdrop) {
                closeDeleteMethodBtn.addEventListener('click', function () {
                    deleteMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingDeleted = null;
                });
            }

            // Add Method submit
            if (addMethodForm) {
                addMethodForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const result = validateMethodForm('add', null);
                    if (!result.isValid) {
                        return;
                    }

                    const values = result.values;

                    const tr = document.createElement('tr');
                    tr.dataset.methodName = values.name;
                    tr.dataset.methodStatus = values.status;

                    const nameTd = document.createElement('td');
                    nameTd.textContent = values.name;

                    const typeTd = document.createElement('td');
                    typeTd.textContent = values.type;

                    const statusTd = document.createElement('td');
                    const statusSpan = document.createElement('span');
                    statusSpan.className = 'status-badge ' + (values.status === 'enabled' ? 'status-enabled' : 'status-disabled');
                    statusSpan.textContent = values.status === 'enabled' ? 'Enabled' : 'Disabled';
                    statusTd.appendChild(statusSpan);

                    const actionsTd = document.createElement('td');
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'btn btn-secondary btn-edit-method';
                    editBtn.textContent = 'Edit';

                    const toggleBtn = document.createElement('button');
                    toggleBtn.type = 'button';
                    toggleBtn.className = 'btn btn-secondary btn-toggle-method';
                    toggleBtn.textContent = values.status === 'enabled' ? 'Disable' : 'Enable';

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'btn btn-danger btn-delete-method';
                    deleteBtn.textContent = 'Delete';

                    actionsTd.appendChild(editBtn);
                    actionsTd.appendChild(toggleBtn);
                    actionsTd.appendChild(deleteBtn);

                    tr.appendChild(nameTd);
                    tr.appendChild(typeTd);
                    tr.appendChild(statusTd);
                    tr.appendChild(actionsTd);

                    paymentMethodTableBody.appendChild(tr);

                    addMethodModalBackdrop.classList.add('hidden');
                });
            }

            // Edit / Toggle / Delete events for methods
            if (paymentMethodTableBody) {
                paymentMethodTableBody.addEventListener('click', function (e) {
                    const target = e.target;
                    const row = target.closest('tr');
                    if (!row) return;

                    if (target.classList.contains('btn-edit-method')) {
                        methodRowBeingEdited = row;

                        const nameCell = row.children[0];
                        const typeCell = row.children[1];
                        const statusBadge = row.querySelector('.status-badge');

                        const nameInput = document.getElementById('editMethodName');
                        const typeSelect = document.getElementById('editMethodType');
                        const statusSelect = document.getElementById('editMethodStatus');

                        nameInput.value = nameCell ? nameCell.textContent.trim() : '';
                        typeSelect.value = typeCell ? typeCell.textContent.trim() : '';

                        let statusValue = 'enabled';
                        if (statusBadge && statusBadge.classList.contains('status-disabled')) {
                            statusValue = 'disabled';
                        }
                        statusSelect.value = statusValue;

                        clearFieldError(nameInput, editMethodNameError);
                        clearFieldError(typeSelect, editMethodTypeError);
                        clearFieldError(statusSelect, editMethodStatusError);

                        editMethodModalBackdrop.classList.remove('hidden');
                    }

                    if (target.classList.contains('btn-toggle-method')) {
                        const statusBadge = row.querySelector('.status-badge');
                        if (!statusBadge) return;

                        const currentlyEnabled = statusBadge.classList.contains('status-enabled');
                        if (currentlyEnabled) {
                            statusBadge.classList.remove('status-enabled');
                            statusBadge.classList.add('status-disabled');
                            statusBadge.textContent = 'Disabled';
                            row.dataset.methodStatus = 'disabled';
                            target.textContent = 'Enable';
                        } else {
                            statusBadge.classList.remove('status-disabled');
                            statusBadge.classList.add('status-enabled');
                            statusBadge.textContent = 'Enabled';
                            row.dataset.methodStatus = 'enabled';
                            target.textContent = 'Disable';
                        }
                    }

                    if (target.classList.contains('btn-delete-method')) {
                        methodRowBeingDeleted = row;
                        const nameCell = row.children[0];
                        const methodName = nameCell ? nameCell.textContent.trim() : 'this method';
                        deleteMethodText.textContent = 'Are you sure you want to delete payment method "' + methodName + '"?';
                        deleteMethodModalBackdrop.classList.remove('hidden');
                    }
                });
            }

            // Edit method submit
            if (editMethodForm) {
                editMethodForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!methodRowBeingEdited) return;

                    const result = validateMethodForm('edit', methodRowBeingEdited);
                    if (!result.isValid) {
                        return;
                    }

                    const values = result.values;

                    const nameCell = methodRowBeingEdited.children[0];
                    const typeCell = methodRowBeingEdited.children[1];
                    const statusCell = methodRowBeingEdited.children[2];
                    const statusBadge = statusCell ? statusCell.querySelector('.status-badge') : null;
                    const toggleBtn = methodRowBeingEdited.querySelector('.btn-toggle-method');

                    if (nameCell) nameCell.textContent = values.name;
                    if (typeCell) typeCell.textContent = values.type;

                    if (statusBadge) {
                        if (values.status === 'enabled') {
                            statusBadge.className = 'status-badge status-enabled';
                            statusBadge.textContent = 'Enabled';
                        } else {
                            statusBadge.className = 'status-badge status-disabled';
                            statusBadge.textContent = 'Disabled';
                        }
                    }

                    if (toggleBtn) {
                        toggleBtn.textContent = values.status === 'enabled' ? 'Disable' : 'Enable';
                    }

                    methodRowBeingEdited.dataset.methodName = values.name;
                    methodRowBeingEdited.dataset.methodStatus = values.status;

                    editMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingEdited = null;
                });
            }

            // Confirm delete method
            if (confirmDeleteMethodBtn) {
                confirmDeleteMethodBtn.addEventListener('click', function () {
                    if (methodRowBeingDeleted) {
                        methodRowBeingDeleted.remove();
                    }
                    deleteMethodModalBackdrop.classList.add('hidden');
                    methodRowBeingDeleted = null;
                });
            }

            // Open payment status modal
            if (paymentsTableBody) {
                paymentsTableBody.addEventListener('click', function (e) {
                    const target = e.target;
                    const row = target.closest('tr');
                    if (!row) return;

                    if (target.classList.contains('btn-update-payment-status')) {
                        paymentRowBeingUpdated = row;

                        const statusBadge = row.querySelector('.status-badge');
                        let currentStatus = '';

                        if (statusBadge) {
                            if (statusBadge.classList.contains('status-payment-pending')) {
                                currentStatus = 'pending';
                            } else if (statusBadge.classList.contains('status-payment-paid')) {
                                currentStatus = 'paid';
                            } else if (statusBadge.classList.contains('status-payment-failed')) {
                                currentStatus = 'failed';
                            } else if (statusBadge.classList.contains('status-payment-refunded')) {
                                currentStatus = 'refunded';
                            }
                        }

                        paymentStatusSelect.value = currentStatus;
                        paymentStatusError.textContent = '';
                        paymentStatusSelect.classList.remove('input-error');

                        paymentStatusModalBackdrop.classList.remove('hidden');
                    }
                });
            }

            function closePaymentStatusModal() {
                paymentStatusModalBackdrop.classList.add('hidden');
                paymentRowBeingUpdated = null;
                paymentStatusError.textContent = '';
                paymentStatusSelect.classList.remove('input-error');
            }

            if (closePaymentStatusBtn) {
                closePaymentStatusBtn.addEventListener('click', closePaymentStatusModal);
            }

            if (cancelPaymentStatusBtn) {
                cancelPaymentStatusBtn.addEventListener('click', closePaymentStatusModal);
            }

            // Update payment status submit
            if (paymentStatusForm) {
                paymentStatusForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!paymentRowBeingUpdated) return;

                    const value = paymentStatusSelect.value;
                    paymentStatusError.textContent = '';
                    paymentStatusSelect.classList.remove('input-error');

                    if (!value) {
                        paymentStatusError.textContent = 'Please select status.';
                        paymentStatusSelect.classList.add('input-error');
                        return;
                    }

                    const statusCell = paymentRowBeingUpdated.children[5];
                    const statusBadge = statusCell ? statusCell.querySelector('.status-badge') : null;
                    if (!statusBadge) {
                        closePaymentStatusModal();
                        return;
                    }

                    statusBadge.classList.remove('status-payment-pending', 'status-payment-paid', 'status-payment-failed', 'status-payment-refunded');

                    let label = '';
                    if (value === 'pending') {
                        statusBadge.classList.add('status-payment-pending');
                        label = 'Pending';
                    } else if (value === 'paid') {
                        statusBadge.classList.add('status-payment-paid');
                        label = 'Paid';
                    } else if (value === 'failed') {
                        statusBadge.classList.add('status-payment-failed');
                        label = 'Failed';
                    } else if (value === 'refunded') {
                        statusBadge.classList.add('status-payment-refunded');
                        label = 'Refunded';
                    }

                    statusBadge.textContent = label;

                    closePaymentStatusModal();
                });
            }
        })();
    </script>
</body>

</html>
