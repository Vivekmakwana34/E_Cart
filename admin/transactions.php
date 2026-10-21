<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'e_cart');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch transactions from orders
$query = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 50";
$result = $conn->query($query);

$transactions = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Calculate statistics
$statsQuery = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount,
    SUM(CASE WHEN payment_status = 'cancelled' THEN total_amount ELSE 0 END) as refunded_amount
FROM orders";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : [
    'total_orders' => 0, 'total_revenue' => 0, 'paid_amount' => 0,
    'pending_amount' => 0, 'refunded_amount' => 0
];

// Filters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Filter transactions
$filteredTransactions = [];
foreach ($transactions as $t) {
    if ($filter_type != 'all') {
        if ($filter_type == 'income' && $t['payment_status'] == 'cancelled') continue;
        if ($filter_type == 'expense' && $t['payment_status'] != 'cancelled') continue;
    }
    if ($filter_status != 'all' && $t['payment_status'] != $filter_status) continue;
    if ($search && !str_contains(strtolower($t['order_number'].($t['user_name']??'')), strtolower($search))) continue;
    $filteredTransactions[] = $t;
}

// Calculate stats
$totalIncome = $stats['paid_amount'] ?? 0;
$totalExpenses = ($stats['refunded_amount'] ?? 0) * 0.1;
$currentBalance = $totalIncome - $totalExpenses;
$pendingAmount = $stats['pending_amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Transactions & Wallet - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Additional styles for transactions page */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card.income i {
            color: #4caf50;
        }

        .stat-card.expense i {
            color: #f44336;
        }

        .stat-card.wallet i {
            color: #ff9800;
        }

        .stat-card.pending i {
            color: #ff9800;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-section select,
        .filter-section input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-section button {
            padding: 10px 20px;
            background-color: #ff9800;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .filter-section button:hover {
            background-color: #e68900;
        }

        .transactions-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .transactions-container h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .transaction-id {
            font-family: monospace;
            background-color: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .amount {
            font-weight: 600;
            font-size: 1rem;
        }

        .amount.credit {
            color: #4caf50;
        }

        .amount.debit {
            color: #f44336;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: #e8f5e8;
            color: #4caf50;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #ff9800;
            color: white;
        }

        .btn-primary:hover {
            background-color: #e68900;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
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
                <li><a href="transactions.php" class="active"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <header>
                <h1>Transactions & Wallet</h1>
                <div style="display: flex; gap: 15px;">
                    <button class="btn-action btn-primary" style="padding: 10px 20px;" onclick="alert('Download feature coming soon!')">
                        <i class="fas fa-file-download"></i> Download Statement
                    </button>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card income">
                    <i class="fas fa-arrow-up"></i>
                    <h3>₹<?php echo number_format($totalIncome, 2); ?></h3>
                    <p>Total Income</p>
                </div>
                <div class="stat-card expense">
                    <i class="fas fa-arrow-down"></i>
                    <h3>₹<?php echo number_format($totalExpenses, 2); ?></h3>
                    <p>Total Expenses</p>
                </div>
                <div class="stat-card wallet">
                    <i class="fas fa-wallet"></i>
                    <h3>₹<?php echo number_format($currentBalance, 2); ?></h3>
                    <p>Current Balance</p>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <h3>₹<?php echo number_format($pendingAmount, 2); ?></h3>
                    <p>Pending Transactions</p>
                </div>
            </div>

            <form class="filter-section" method="GET">
                <select name="type" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_type=='all'?'selected':''; ?>>All Types</option>
                    <option value="income" <?php echo $filter_type=='income'?'selected':''; ?>>Income</option>
                    <option value="expense" <?php echo $filter_type=='expense'?'selected':''; ?>>Expense</option>
                </select>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>All Status</option>
                    <option value="paid" <?php echo $filter_status=='paid'?'selected':''; ?>>Paid</option>
                    <option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>Pending</option>
                </select>
                <input type="text" name="search" placeholder="Search transactions..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Apply Filters</button>
                <?php if ($search || $filter_type!='all' || $filter_status!='all'): ?>
                    <a href="transactions.php" style="color: #666; margin-left: 10px;"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <div class="transactions-container">
                <h3>Recent Transactions (<?php echo count($filteredTransactions); ?>)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredTransactions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 60px; color: #888;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                    <p>No transactions found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredTransactions as $t): 
                                $isIncome = $t['payment_status'] != 'cancelled';
                            ?>
                                <tr>
                                    <td><span class="transaction-id">#<?php echo $t['order_number'] ?? $t['id']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($t['user_name'] ?? 'Guest'); ?></td>
                                    <td><?php echo $isIncome ? 'Income' : 'Refund'; ?></td>
                                    <td>
                                        <span class="amount <?php echo $isIncome ? 'credit' : 'debit'; ?>">
                                            <?php echo $isIncome ? '+' : '-'; ?>₹<?php echo number_format($t['total_amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $t['payment_status']; ?>">
                                            <?php echo ucfirst($t['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $t['id']; ?>" class="btn-action btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const sidebarNav = document.getElementById('sidebarNav');

        navbarToggle.addEventListener('click', () => {
            sidebarNav.classList.toggle('open');
        });
    </script>
</body>
</html>