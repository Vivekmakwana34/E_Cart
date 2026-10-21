<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'e_cart');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: contact.php');
    exit();
}

// Handle mark as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $id = intval($_GET['read']);
    $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header('Location: contact.php');
    exit();
}

// Fetch all contact messages
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}

// Count stats
$total  = count($messages);
$unread = count(array_filter($messages, fn($m) => $m['status'] == 'new' || empty($m['status'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .stats-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            flex: 1;
            text-align: center;
        }
        .stat-box h4 { font-size: 28px; margin: 0; }
        .stat-box span { font-size: 13px; opacity: 0.9; }
        .stat-box:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .messages-grid { display: grid; gap: 20px; }
        .message-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        }
        .message-card.unread {
            border-left-color: #f5576c;
            background: #fff9f9;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .sender-info h4 { margin: 0 0 5px 0; color: #333; font-size: 16px; }
        .sender-info p  { margin: 0; color: #666; font-size: 13px; }
        .message-meta { text-align: right; color: #888; font-size: 12px; }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 5px;
        }
        .status-unread { background: #ffebee; color: #c62828; }
        .status-read   { background: #e8f5e9; color: #2e7d32; }
        .topic-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            background: #e8eaf6;
            color: #3949ab;
            margin-bottom: 8px;
        }
        .message-body {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .message-actions { display: flex; gap: 10px; }
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-read   { background: #4caf50; color: white; }
        .btn-delete { background: #f44336; color: white; }
        .empty-state { text-align: center; padding: 60px; color: #888; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
        @media (max-width: 768px) {
            .stats-bar { flex-wrap: wrap; }
            .stat-box  { flex: 1 1 45%; }
            .message-header { flex-direction: column; }
            .message-meta { text-align: left; margin-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar" id="sidebarNav">
            <div class="navbar-header">
                <h2>Admin Panel</h2>
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span><span></span><span></span>
                </button>
            </div>
            <ul>
                <li><a href="home.php"><i class="fa-solid fa-gauge" style="margin-right:8px;"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users" style="margin-right:8px;"></i>Users</a></li>
                <li><a href="banner.php"><i class="fa-solid fa-image" style="margin-right:8px;"></i>Banner</a></li>
                <li><a href="categories.php"><i class="fa-solid fa-list" style="margin-right:8px;"></i>Categories</a></li>
                <li><a href="products.php"><i class="fa-solid fa-box" style="margin-right:8px;"></i>Products</a></li>
                <li><a href="orders.php"><i class="fa-solid fa-cart-shopping" style="margin-right:8px;"></i>Orders</a></li>
                <li><a href="coupons.php"><i class="fa-solid fa-ticket" style="margin-right:8px;"></i>Coupons</a></li>
                <li><a href="Gift.php"><i class="fa-solid fa-gift" style="margin-right:8px;"></i>Gift Cards</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-credit-card" style="margin-right:8px;"></i>Payments</a></li>
                <li><a href="shipping.php"><i class="fa-solid fa-truck" style="margin-right:8px;"></i>Shipping</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-chart-line" style="margin-right:8px;"></i>Reports</a></li>
                <li><a href="reviews.php"><i class="fa-solid fa-star" style="margin-right:8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right:8px;"></i>Transactions</a></li>
                <li><a href="contact.php" class="active"><i class="fa-solid fa-envelope" style="margin-right:8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right:8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right:8px;"></i>Settings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Contact Messages</h1>
                    <p style="color:#888;margin:5px 0 0 0;">Manage customer inquiries</p>
                </div>
            </div>

            <div class="stats-bar">
                <div class="stat-box">
                    <h4><?php echo $total; ?></h4>
                    <span>Total Messages</span>
                </div>
                <div class="stat-box">
                    <h4><?php echo $unread; ?></h4>
                    <span>Unread</span>
                </div>
            </div>

            <div class="messages-grid">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m):
                        $isUnread = $m['status'] == 'new' || empty($m['status']);
                        $topicLabels = [
                            'order'   => 'Order / Delivery',
                            'payment' => 'Payment / Refund',
                            'product' => 'Product Details',
                            'account' => 'Account / Login',
                            'other'   => 'Something Else',
                        ];
                        $topicLabel = $topicLabels[$m['topic'] ?? ''] ?? ucfirst($m['topic'] ?? '');
                    ?>
                        <div class="message-card <?php echo $isUnread ? 'unread' : ''; ?>">
                            <div class="message-header">
                                <div class="sender-info">
                                    <h4><i class="fas fa-user-circle" style="margin-right:8px;"></i><?php echo htmlspecialchars($m['full_name'] ?? 'Anonymous'); ?></h4>
                                    <p><i class="fas fa-envelope" style="margin-right:8px;"></i><?php echo htmlspecialchars($m['email']); ?></p>
                                    <?php if (!empty($m['phone'])): ?>
                                        <p><i class="fas fa-phone" style="margin-right:8px;"></i><?php echo htmlspecialchars($m['phone']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($m['order_id'])): ?>
                                        <p><i class="fas fa-hashtag" style="margin-right:8px;"></i>Order: <?php echo htmlspecialchars($m['order_id']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="message-meta">
                                    <div><?php echo date('M d, Y h:i A', strtotime($m['created_at'])); ?></div>
                                    <span class="status-badge <?php echo $isUnread ? 'status-unread' : 'status-read'; ?>">
                                        <?php echo $isUnread ? 'Unread' : 'Read'; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($topicLabel)): ?>
                                <div><span class="topic-badge"><i class="fas fa-tag" style="margin-right:5px;"></i><?php echo $topicLabel; ?></span></div>
                            <?php endif; ?>

                            <div class="message-body">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($m['subject'] ?? 'No Subject'); ?>
                                <br><br>
                                <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                            </div>

                            <div class="message-actions">
                                <?php if ($isUnread): ?>
                                    <a href="?read=<?php echo $m['id']; ?>" class="btn-small btn-read">
                                        <i class="fas fa-check"></i> Mark Read
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $m['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Delete this message?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('navbarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebarNav')?.classList.toggle('open');
        });
    </script>
</body>
</html>