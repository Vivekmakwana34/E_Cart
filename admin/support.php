<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'e_cart');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle status update
if (isset($_GET['close']) && is_numeric($_GET['close'])) {
    $id = intval($_GET['close']);
    $stmt = $conn->prepare("UPDATE support_tickets SET status = 'closed' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: support.php');
    exit();
}

// Handle respond action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    $id = intval($_POST['ticket_id']);
    $response = $conn->real_escape_string($_POST['response']);
    $stmt = $conn->prepare("UPDATE support_tickets SET status = 'pending', admin_response = ? WHERE id = ?");
    $stmt->bind_param("si", $response, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: support.php');
    exit();
}

// Filters
$filter_status = $_GET['status'] ?? 'all';
$filter_priority = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Fetch all tickets
$tickets = [];
$tableExists = $conn->query("SHOW TABLES LIKE 'support_tickets'")->num_rows > 0;

if ($tableExists) {
    $query = "SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 50";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
    }
}

// Filter tickets
$filteredTickets = [];
foreach ($tickets as $t) {
    if ($filter_status != 'all' && $t['status'] != $filter_status) continue;
    if ($filter_priority != 'all' && $t['priority'] != $filter_priority) continue;
    if ($search && !str_contains(strtolower($t['subject'] . ($t['customer_name'] ?? '') . ($t['ticket_id'] ?? '')), strtolower($search))) continue;
    $filteredTickets[] = $t;
}

// Calculate stats
$openCount = count(array_filter($tickets, fn($t) => $t['status'] == 'open'));
$pendingCount = count(array_filter($tickets, fn($t) => $t['status'] == 'pending'));
$closedCount = count(array_filter($tickets, fn($t) => $t['status'] == 'closed'));
$urgentCount = count(array_filter($tickets, fn($t) => $t['priority'] == 'high' && $t['status'] != 'closed'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Customer Support - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Additional styles for support page */
        .status-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-open {
            background-color: #e8f5e8;
            color: #4caf50;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }

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

        .stat-card.open i {
            color: #4caf50;
        }

        .stat-card.pending i {
            color: #ff9800;
        }

        .stat-card.closed i {
            color: #f44336;
        }

        .stat-card.urgent i {
            color: #e91e63;
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

        .tickets-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .tickets-container h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }

        .ticket-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 0;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .ticket-item:last-child {
            border-bottom: none;
        }

        .ticket-priority {
            width: 4px;
            background-color: #ddd;
            border-radius: 2px;
            flex-shrink: 0;
        }

        .ticket-priority.high {
            background-color: #f44336;
        }

        .ticket-priority.medium {
            background-color: #ff9800;
        }

        .ticket-priority.low {
            background-color: #4caf50;
        }

        .ticket-content {
            flex: 1;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .ticket-info h4 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .ticket-info .ticket-id {
            color: #666;
            font-size: 0.9rem;
            font-family: monospace;
            background-color: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .ticket-info .customer-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .ticket-info .ticket-date {
            color: #999;
            font-size: 0.8rem;
        }

        .ticket-subject {
            color: #333;
            line-height: 1.6;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .ticket-message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .ticket-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-respond {
            background-color: #ff9800;
            color: white;
        }

        .btn-respond:hover {
            background-color: #e68900;
        }

        .btn-close {
            background-color: #f44336;
            color: white;
        }

        .btn-close:hover {
            background-color: #da190b;
        }

        .btn-view {
            background-color: #2196f3;
            color: white;
        }

        .btn-view:hover {
            background-color: #0b7dda;
        }

        .ticket-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open {
            background-color: #e8f5e8;
            color: #4caf50;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
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
            .ticket-item {
                flex-direction: column;
            }
            .ticket-priority {
                width: 100%;
                height: 4px;
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
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php" class="active"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <header>
                <h1>Customer Support</h1>
                <div class="status-pills">
                    <span class="status-pill status-open">Open: <?php echo $openCount; ?></span>
                    <span class="status-pill status-pending">Pending: <?php echo $pendingCount; ?></span>
                    <span class="status-pill status-closed">Closed: <?php echo $closedCount; ?></span>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card open">
                    <i class="fas fa-envelope-open-text"></i>
                    <h3><?php echo $openCount; ?></h3>
                    <p>Open Tickets</p>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <h3><?php echo $pendingCount; ?></h3>
                    <p>Pending Response</p>
                </div>
                <div class="stat-card closed">
                    <i class="fas fa-check-circle"></i>
                    <h3><?php echo $closedCount; ?></h3>
                    <p>Closed Today</p>
                </div>
                <div class="stat-card urgent">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3><?php echo $urgentCount; ?></h3>
                    <p>Urgent Tickets</p>
                </div>
            </div>

            <form class="filter-section" method="GET">
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_status=='all'?'selected':''; ?>>All Status</option>
                    <option value="open" <?php echo $filter_status=='open'?'selected':''; ?>>Open</option>
                    <option value="pending" <?php echo $filter_status=='pending'?'selected':''; ?>>Pending</option>
                    <option value="closed" <?php echo $filter_status=='closed'?'selected':''; ?>>Closed</option>
                </select>
                <select name="priority" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_priority=='all'?'selected':''; ?>>All Priority</option>
                    <option value="high" <?php echo $filter_priority=='high'?'selected':''; ?>>High</option>
                    <option value="medium" <?php echo $filter_priority=='medium'?'selected':''; ?>>Medium</option>
                    <option value="low" <?php echo $filter_priority=='low'?'selected':''; ?>>Low</option>
                </select>
                <input type="text" name="search" placeholder="Search tickets..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Apply Filters</button>
                <?php if ($search || $filter_status!='all' || $filter_priority!='all'): ?>
                    <a href="support.php" style="color: #666; margin-left: 10px;"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <div class="tickets-container">
                <h3>Support Tickets (<?php echo count($filteredTickets); ?>)</h3>
                
                <?php if (empty($filteredTickets)): ?>
                    <div style="text-align: center; padding: 60px; color: #888;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                        <p>No tickets found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($filteredTickets as $t): ?>
                        <div class="ticket-item">
                            <div class="ticket-priority <?php echo $t['priority'] ?? 'low'; ?>"></div>
                            <div class="ticket-content">
                                <div class="ticket-header">
                                    <div class="ticket-info">
                                        <h4><?php echo htmlspecialchars($t['subject'] ?? 'No Subject'); ?></h4>
                                        <span class="ticket-id"><?php echo htmlspecialchars($t['ticket_id'] ?? 'SUP-' . str_pad($t['id'], 3, '0', STR_PAD_LEFT)); ?></span>
                                        <div class="customer-info"><?php echo htmlspecialchars($t['customer_name'] ?? 'Unknown'); ?> - <?php echo htmlspecialchars($t['customer_email'] ?? ''); ?></div>
                                        <div class="ticket-date"><?php echo date('M d, Y - h:i A', strtotime($t['created_at'])); ?></div>
                                    </div>
                                    <span class="ticket-status status-<?php echo $t['status'] ?? 'open'; ?>"><?php echo ucfirst($t['status'] ?? 'Open'); ?></span>
                                </div>
                                <div class="ticket-subject">
                                    <?php echo htmlspecialchars($t['subject'] ?? ''); ?>
                                </div>
                                <div class="ticket-message">
                                    <?php echo nl2br(htmlspecialchars($t['message'] ?? $t['description'] ?? '')); ?>
                                </div>
                                <div class="ticket-actions">
                                    <?php if ($t['status'] != 'closed'): ?>
                                        <a href="?close=<?php echo $t['id']; ?>" class="btn-action btn-close" onclick="return confirm('Close this ticket?')">Close Ticket</a>
                                    <?php endif; ?>
                                    <button class="btn-action btn-view" onclick="alert('View details: <?php echo $t['id']; ?>')">View Details</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
