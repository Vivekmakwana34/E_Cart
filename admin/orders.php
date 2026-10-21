<!DOCTYPE html>
<html lang="en">

<?php
session_start();
include '../Database_file/config.php';

// Set admin session for testing
$_SESSION['is_admin'] = true;
$_SESSION['logged_in'] = true;

// Fetch orders
$orders = [];
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($conn) && ($conn instanceof mysqli)) {
    $sql = "SELECT o.id, o.order_number, o.total_amount, o.order_status, o.shipping_address, o.created_at,
                   o.user_id,
                   u.full_name AS customer_name, u.email AS customer_email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id";
    
    // Add search condition if search query exists
    if (!empty($searchQuery)) {
        $searchParam = $conn->real_escape_string($searchQuery);
        $sql .= " WHERE o.id = '{$searchParam}' 
                  OR o.order_number LIKE '%{$searchParam}%' 
                  OR o.user_id = '{$searchParam}'
                  OR u.full_name LIKE '%{$searchParam}%'
                  OR u.email LIKE '%{$searchParam}%'";
    }
    
    $sql .= " ORDER BY o.created_at DESC";

    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}

function ec_status_badge_class($status) {
    $s = strtolower(trim((string)$status));
    if ($s === 'pending') return 'status-badge-pending';
    if ($s === 'shipped') return 'status-badge-shipped';
    if ($s === 'delivered') return 'status-badge-delivered';
    if ($s === 'cancelled' || $s === 'canceled') return 'status-badge-cancelled';
    return 'status-badge-pending';
}

function ec_status_label($status) {
    $s = strtolower(trim((string)$status));
    if ($s === 'cancelled') return 'Cancelled';
    if ($s === 'canceled') return 'Cancelled';
    return ucfirst($s !== '' ? $s : 'pending');
}

function ec_is_cancelled($status) {
    $s = strtolower(trim((string)$status));
    return ($s === 'cancelled' || $s === 'canceled');
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Order Management - Admin Panel</title>
     <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .container {
            display: flex;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 290px;
        }

        header {
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .order-management {
            margin-top: 20px;
        }

        .order-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
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
            width: 250px;
            padding: 4px 8px;
        }

        .search-box input::placeholder {
            color: #9ca3af;
        }

        .search-box button,
        .search-box .btn-clear {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .search-box button:hover,
        .search-box .btn-clear:hover {
            color: #374151;
        }

        .search-box .btn-clear.hidden {
            display: none;
        }

        .no-results-message {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-size: 14px;
        }

        .order-count-badge {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .orders-table th,
        .orders-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .orders-table th {
            background-color: #e5e7eb;
            font-weight: 600;
        }

        .orders-table th:nth-child(4),
        .orders-table td:nth-child(4),
        .orders-table th:nth-child(5),
        .orders-table td:nth-child(5) {
            text-align: center;
        }

        .orders-table th:last-child,
        .orders-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .orders-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .orders-table td:last-child .btn {
            margin: 0;
        }

        .orders-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-badge-shipped {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .status-badge-delivered {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-badge-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        /* Base button styles (similar to users page) */
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #333333;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-primary {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #f57c00;
            color: #ffffff;
        }

        /* Orders table action buttons */

        /* View = primary orange */
        .btn-view {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-view:hover {
            background-color: #f57c00;
            color: #ffffff;
        }

        /* Update Status = blue */
        .btn-update-status {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-update-status:hover {
            background-color: #1d4ed8;
            color: #ffffff;
        }

        /* Print Bill = green */
        .btn-print {
            background-color: #10b981;
            color: #ffffff;
        }

        .btn-print:hover {
            background-color: #059669;
            color: #ffffff;
        }

        /* Spacing between action buttons */
        .orders-table td .btn {
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.45);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }

        .modal-backdrop.hidden {
            display: none;
        }

        .modal {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px 22px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            position: relative;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            border: none;
            background: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
        }

        .modal-body {
            margin-bottom: 16px;
            font-size: 14px;
        }

        .detail-row {
            margin-bottom: 6px;
        }

        .detail-label {
            font-weight: 600;
            margin-right: 6px;
            color: #4b5563;
        }

        .detail-items-list {
            margin: 6px 0 0 0;
            padding-left: 18px;
        }

        .detail-items-list li {
            margin-bottom: 2px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .error-text {
            display: block;
            font-size: 12px;
            color: #dc2626;
            margin-top: 4px;
        }

        .input-error {
            border-color: #dc2626 !important;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333333;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0;
                padding: 12px;
            }

            .orders-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .orders-table thead,
            .orders-table tbody,
            .orders-table tr,
            .orders-table th,
            .orders-table td {
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
                <h1>Order Management</h1>
            </header>

            <section class="order-management">
                <div class="order-management-header">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <h2>Orders</h2>
                        <span class="order-count-badge" id="orderCountBadge"><?php echo count($orders); ?> items</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <!-- Search Box - Real Time -->
                        <div class="search-box">
                            <i class="fa-solid fa-search" style="color: #9ca3af; font-size: 16px;"></i>
                            <input type="text" id="orderSearchInput" placeholder="Search by Order ID, User ID, Name..." autocomplete="off">
                            <button type="button" id="clearOrderSearch" class="btn-clear hidden" title="Clear search"><i class="fa-solid fa-times"></i></button>
                        </div>
                    </div>
                </div>

                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>User ID</th>
                            <th>Customer Details</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $o): ?>
                                <?php
                                    $orderNumber = (string)($o['order_number'] ?? ('Order #' . ($o['id'] ?? '')));
                                    $userId = (int)($o['user_id'] ?? 0);
                                    $customerName = (string)($o['customer_name'] ?? '');
                                    $customerEmail = (string)($o['customer_email'] ?? '');
                                    $shippingAddress = (string)($o['shipping_address'] ?? '');
                                    $createdAt = (string)($o['created_at'] ?? '');
                                    $status = (string)($o['order_status'] ?? 'pending');
                                    $total = (float)($o['total_amount'] ?? 0);

                                    $dateLabel = $createdAt ? date('d M Y', strtotime($createdAt)) : '';
                                    $displayName = $customerName !== '' ? $customerName : ($customerEmail !== '' ? $customerEmail : 'Guest User');
                                ?>
                                <tr
                                    data-order-id="<?php echo htmlspecialchars($orderNumber); ?>"
                                    data-user-id="<?php echo $userId; ?>"
                                    data-customer-name="<?php echo htmlspecialchars($customerName); ?>"
                                    data-customer-email="<?php echo htmlspecialchars($customerEmail); ?>"
                                    data-customer-phone=""
                                    data-shipping-address="<?php echo htmlspecialchars($shippingAddress); ?>"
                                    data-order-date="<?php echo htmlspecialchars($dateLabel); ?>"
                                    data-order-total="<?php echo htmlspecialchars((string)$total); ?>"
                                    data-order-items=""
                                    data-payment-method="<?php echo htmlspecialchars($o['payment_method'] ?? 'N/A'); ?>"
                                    data-payment-status="<?php echo htmlspecialchars($o['payment_status'] ?? 'N/A'); ?>"
                                    data-db-order-id="<?php echo (int)($o['id'] ?? 0); ?>">
                                    <td><?php echo htmlspecialchars($orderNumber); ?></td>
                                    <td><span style="background:#e0e7ff;color:#3730a3;padding:4px 10px;border-radius:4px;font-weight:600;font-size:13px;">#<?php echo $userId; ?></span></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($displayName); ?></div>
                                        <?php if ($customerEmail): ?>
                                            <div style="font-size:12px;color:#64748b;"><?php echo htmlspecialchars($customerEmail); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($dateLabel); ?></td>
                                    <td><span class="status-badge <?php echo ec_status_badge_class($status); ?>"><?php echo htmlspecialchars(ec_status_label($status)); ?></span></td>
                                    <td>₹<?php echo htmlspecialchars(number_format($total, 0)); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-secondary btn-view">View</button>
                                        <button type="button" class="btn btn-secondary btn-update-status">Update Status</button>
                                        <button type="button" class="btn btn-secondary btn-print">Print Bill</button>
                                        <?php if (ec_is_cancelled($status)): ?>
                                            <button type="button" class="btn btn-secondary btn-delete" data-db-order-id="<?php echo (int)($o['id'] ?? 0); ?>">Delete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding: 14px; color: #6b7280;">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <!-- Order Detail Modal -->
    <div id="orderDetailModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeOrderDetail">&times;</button>
            <div class="modal-header">Order Details</div>
            <div class="modal-body">
                <div class="detail-row"><span class="detail-label">Order ID:</span><span id="detailOrderId"></span>
                </div>
                <div class="detail-row"><span class="detail-label">Customer:</span><span
                        id="detailCustomerName"></span></div>
                <div class="detail-row"><span class="detail-label">Email:</span><span
                        id="detailCustomerEmail"></span></div>
                <div class="detail-row"><span class="detail-label">Phone:</span><span
                        id="detailCustomerPhone"></span></div>
                <div class="detail-row"><span class="detail-label">Address:</span><span
                        id="detailShippingAddress"></span></div>
                <div class="detail-row"><span class="detail-label">Date:</span><span id="detailOrderDate"></span></div>
                <div class="detail-row"><span class="detail-label">Total:</span><span id="detailOrderTotal"></span>
                </div>
                <div class="detail-row"><span class="detail-label">Items:</span>
                    <ul id="detailOrderItems" class="detail-items-list"></ul>
                </div>
                <div class="detail-row"><span class="detail-label">Payment Method:</span><span id="detailPaymentMethod"></span></div>
                <div class="detail-row"><span class="detail-label">Payment Status:</span><span id="detailPaymentStatus"></span></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="closeOrderDetailFooter">Close</button>
            </div>
        </div>
    </div>

    <!-- Order Status Modal -->
    <div id="orderStatusModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeOrderStatus">&times;</button>
            <div class="modal-header">Update Order Status</div>
            <div class="modal-body">
                <form id="orderStatusForm">
                    <div class="form-group">
                        <label for="orderStatusSelect">Status</label>
                        <select id="orderStatusSelect">
                            <option value="">Select status</option>
                            <option value="pending">Pending</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <span class="error-text" id="orderStatusError"></span>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelOrderStatus">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Navbar toggle
            var navbar = document.getElementById('sidebarNav');
            var navbarToggle = document.getElementById('navbarToggle');
            if (navbar && navbarToggle) {
                navbarToggle.addEventListener('click', function () {
                    navbar.classList.toggle('open');
                });
            }

            // Highlight current page in sidebar
            if (navbar) {
                var links = navbar.querySelectorAll('a');
                var currentPage = window.location.pathname.split('/').pop();

                for (var i = 0; i < links.length; i++) {
                    var href = links[i].getAttribute('href');
                    if (!href) continue;
                    var hrefPage = href.split('/').pop();
                    if (hrefPage === currentPage) {
                        links[i].classList.add('active');
                    }
                }
            }

            // Real-time order search
            var orderSearchInput = document.getElementById('orderSearchInput');
            var clearOrderSearchBtn = document.getElementById('clearOrderSearch');
            var orderCountBadge = document.getElementById('orderCountBadge');
            var allOrderRows = Array.from(ordersTableBody.querySelectorAll('tr'));

            function filterOrders(searchTerm) {
                var term = searchTerm.toLowerCase().trim();
                var visibleCount = 0;

                allOrderRows.forEach(function(row) {
                    var orderId = row.getAttribute('data-order-id')?.toLowerCase() || '';
                    var userId = row.getAttribute('data-user-id')?.toLowerCase() || '';
                    var customerName = row.getAttribute('data-customer-name')?.toLowerCase() || '';
                    var customerEmail = row.getAttribute('data-customer-email')?.toLowerCase() || '';
                    var shippingAddress = row.getAttribute('data-shipping-address')?.toLowerCase() || '';
                    
                    if (orderId.includes(term) || userId.includes(term) || customerName.includes(term) || 
                        customerEmail.includes(term) || shippingAddress.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update count badge
                if (orderCountBadge) {
                    orderCountBadge.textContent = visibleCount + ' items';
                }

                // Show/hide clear button
                if (term.length > 0) {
                    clearOrderSearchBtn.classList.remove('hidden');
                } else {
                    clearOrderSearchBtn.classList.add('hidden');
                }

                // Show no results message if needed
                var noResultsRow = ordersTableBody.querySelector('.no-results-row');
                if (visibleCount === 0 && term.length > 0) {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = '<td colspan="7" class="no-results-message"><i class="fa-solid fa-search" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>No orders found matching "' + escapeHtml(term) + '"</td>';
                        ordersTableBody.appendChild(noResultsRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (orderSearchInput) {
                orderSearchInput.addEventListener('input', function() {
                    filterOrders(this.value);
                });

                // Also handle paste events
                orderSearchInput.addEventListener('paste', function(e) {
                    setTimeout(function() { filterOrders(orderSearchInput.value); }, 0);
                });
            }

            if (clearOrderSearchBtn) {
                clearOrderSearchBtn.addEventListener('click', function() {
                    orderSearchInput.value = '';
                    filterOrders('');
                    orderSearchInput.focus();
                });
            }

            var orderDetailModalBackdrop = document.getElementById('orderDetailModalBackdrop');
            var closeOrderDetailBtn = document.getElementById('closeOrderDetail');
            var closeOrderDetailFooterBtn = document.getElementById('closeOrderDetailFooter');

            var detailOrderId = document.getElementById('detailOrderId');
            var detailCustomerName = document.getElementById('detailCustomerName');
            var detailCustomerEmail = document.getElementById('detailCustomerEmail');
            var detailCustomerPhone = document.getElementById('detailCustomerPhone');
            var detailShippingAddress = document.getElementById('detailShippingAddress');
            var detailOrderDate = document.getElementById('detailOrderDate');
            var detailOrderTotal = document.getElementById('detailOrderTotal');
            var detailOrderItems = document.getElementById('detailOrderItems');
            var detailPaymentMethod = document.getElementById('detailPaymentMethod');
            var detailPaymentStatus = document.getElementById('detailPaymentStatus');

            var orderStatusModalBackdrop = document.getElementById('orderStatusModalBackdrop');
            var closeOrderStatusBtn = document.getElementById('closeOrderStatus');
            var cancelOrderStatusBtn = document.getElementById('cancelOrderStatus');
            var orderStatusForm = document.getElementById('orderStatusForm');
            var orderStatusSelect = document.getElementById('orderStatusSelect');
            var orderStatusError = document.getElementById('orderStatusError');

            var rowBeingUpdated = null;

            function openOrderDetailModal(row) {
                if (!row) return;

                detailOrderId.textContent = row.getAttribute('data-order-id') || '';
                detailCustomerName.textContent = row.getAttribute('data-customer-name') || '';
                detailCustomerEmail.textContent = row.getAttribute('data-customer-email') || '';
                detailCustomerPhone.textContent = row.getAttribute('data-customer-phone') || '';
                detailShippingAddress.textContent = row.getAttribute('data-shipping-address') || '';
                detailOrderDate.textContent = row.getAttribute('data-order-date') || '';

                var total = row.getAttribute('data-order-total');
                detailOrderTotal.textContent = total ? 'Rs. ' + total : '';

                while (detailOrderItems.firstChild) {
                    detailOrderItems.removeChild(detailOrderItems.firstChild);
                }

                var itemsRaw = row.getAttribute('data-order-items') || '';
                if (itemsRaw) {
                    var parts = itemsRaw.split('|');
                    for (var i = 0; i < parts.length; i++) {
                        var text = parts[i].trim();
                        if (!text) continue;
                        var li = document.createElement('li');
                        li.textContent = text;
                        detailOrderItems.appendChild(li);
                    }
                }

                detailPaymentMethod.textContent = row.getAttribute('data-payment-method') || 'N/A';
                detailPaymentStatus.textContent = row.getAttribute('data-payment-status') || 'N/A';

                orderDetailModalBackdrop.classList.remove('hidden');
            }

            function closeOrderDetailModal() {
                orderDetailModalBackdrop.classList.add('hidden');
            }

            if (closeOrderDetailBtn) {
                closeOrderDetailBtn.addEventListener('click', closeOrderDetailModal);
            }
            if (closeOrderDetailFooterBtn) {
                closeOrderDetailFooterBtn.addEventListener('click', closeOrderDetailModal);
            }

            function openOrderStatusModal(row) {
                if (!row) return;
                rowBeingUpdated = row;

                var badge = row.querySelector('.status-badge');
                var currentStatus = '';

                if (badge) {
                    if (badge.classList.contains('status-badge-pending')) {
                        currentStatus = 'pending';
                    } else if (badge.classList.contains('status-badge-shipped')) {
                        currentStatus = 'shipped';
                    } else if (badge.classList.contains('status-badge-delivered')) {
                        currentStatus = 'delivered';
                    } else if (badge.classList.contains('status-badge-cancelled')) {
                        currentStatus = 'cancelled';
                    }
                }

                orderStatusSelect.value = currentStatus;
                orderStatusError.textContent = '';
                orderStatusSelect.classList.remove('input-error');

                orderStatusModalBackdrop.classList.remove('hidden');
            }

            function closeOrderStatusModal() {
                orderStatusModalBackdrop.classList.add('hidden');
                rowBeingUpdated = null;
                orderStatusError.textContent = '';
                orderStatusSelect.classList.remove('input-error');
            }

            if (closeOrderStatusBtn) {
                closeOrderStatusBtn.addEventListener('click', closeOrderStatusModal);
            }
            if (cancelOrderStatusBtn) {
                cancelOrderStatusBtn.addEventListener('click', closeOrderStatusModal);
            }

            if (ordersTableBody) {
                ordersTableBody.addEventListener('click', function (e) {
                    var target = e.target;
                    var row = target.closest('tr');
                    if (!row) return;

                    if (target.classList.contains('btn-view')) {
                        openOrderDetailModal(row);
                    } else if (target.classList.contains('btn-update-status')) {
                        openOrderStatusModal(row);
                    } else if (target.classList.contains('btn-delete')) {
                        var dbOrderId = target.getAttribute('data-db-order-id') || '';
                        if (!dbOrderId) return;
                        if (!confirm('Delete this cancelled order?')) return;

                        var form = new FormData();
                        form.append('order_id', dbOrderId);

                        fetch('admin_delete_order.php', {
                            method: 'POST',
                            body: form
                        })
                            .then(function (res) {
                                return res.text().then(function (t) {
                                    var parsed = null;
                                    try {
                                        parsed = JSON.parse(t);
                                    } catch (e) {
                                        parsed = { success: false, message: t || ('HTTP ' + res.status) };
                                    }
                                    parsed._http_status = res.status;
                                    return parsed;
                                });
                            })
                            .then(function (data) {
                                if (data && data.success) {
                                    row.remove();
                                } else {
                                    alert((data && data.message) ? data.message : 'Failed to delete order');
                                }
                            })
                            .catch(function () {
                                alert('Failed to delete order');
                            });
                    }
                });
            }

            if (orderStatusForm) {
                orderStatusForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!rowBeingUpdated) return;

                    var value = orderStatusSelect.value;
                    orderStatusError.textContent = '';
                    orderStatusSelect.classList.remove('input-error');

                    if (!value) {
                        orderStatusError.textContent = 'Please select a status.';
                        orderStatusSelect.classList.add('input-error');
                        return;
                    }

                    var dbOrderId = rowBeingUpdated.getAttribute('data-db-order-id') || '';
                    if (!dbOrderId) {
                        alert('Missing order id');
                        return;
                    }

                    var form = new FormData();
                    form.append('order_id', dbOrderId);
                    form.append('status', value);

                    fetch('admin_update_order_status.php', {
                        method: 'POST',
                        body: form
                    })
                        .then(function (res) {
                            return res.text().then(function (t) {
                                var parsed = null;
                                try {
                                    parsed = JSON.parse(t);
                                } catch (e) {
                                    parsed = { success: false, message: t || ('HTTP ' + res.status) };
                                }
                                parsed._http_status = res.status;
                                return parsed;
                            });
                        })
                        .then(function (data) {
                            if (data && data.success) {
                                var badge = rowBeingUpdated.querySelector('.status-badge');
                                if (badge) {
                                    badge.classList.remove('status-badge-pending', 'status-badge-shipped', 'status-badge-delivered', 'status-badge-cancelled');
                                    var label = '';
                                    if (value === 'pending') {
                                        badge.classList.add('status-badge-pending');
                                        label = 'Pending';
                                    } else if (value === 'shipped') {
                                        badge.classList.add('status-badge-shipped');
                                        label = 'Shipped';
                                    } else if (value === 'delivered') {
                                        badge.classList.add('status-badge-delivered');
                                        label = 'Delivered';
                                    } else if (value === 'cancelled') {
                                        badge.classList.add('status-badge-cancelled');
                                        label = 'Cancelled';
                                    }
                                    badge.textContent = label;
                                }
                                closeOrderStatusModal();
                            } else {
                                alert((data && data.message) ? data.message : 'Failed to update status');
                            }
                        })
                        .catch(function () {
                            alert('Failed to update status');
                        });
                });
            }
        })();
    </script>
</body>

</html>
