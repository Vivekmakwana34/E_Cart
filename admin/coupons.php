<?php
session_start();
include '../Database_file/config.php';

// Check if user is admin (you may want to add proper admin check)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = '';

// Handle Add Coupon
if (isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $for_new_users_only = isset($_POST['for_new_users_only']) ? 1 : 0;
    
    // Check if coupon code already exists
    $checkStmt = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
    $checkStmt->bind_param('s', $code);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "Coupon code '$code' already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_until, usage_limit, is_active, for_new_users_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssddsssiis', $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount, $valid_from, $valid_until, $usage_limit, $is_active, $for_new_users_only);
        
        if ($stmt->execute()) {
            $message = "Coupon '$code' added successfully!";
        } else {
            $error = "Error adding coupon: " . $conn->error;
        }
    }
}

// Handle Edit Coupon
if (isset($_POST['edit_coupon'])) {
    $id = (int)$_POST['coupon_id'];
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $min_order_amount = (float)($_POST['min_order_amount'] ?? 0);
    $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $for_new_users_only = isset($_POST['for_new_users_only']) ? 1 : 0;
    
    // Check if coupon code already exists (excluding current coupon)
    $checkStmt = $conn->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
    $checkStmt->bind_param('si', $code, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $error = "Coupon code '$code' already exists!";
    } else {
        $stmt = $conn->prepare("UPDATE coupons SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_order_amount = ?, max_discount = ?, valid_from = ?, valid_until = ?, usage_limit = ?, is_active = ?, for_new_users_only = ? WHERE id = ?");
        $stmt->bind_param('sssddsssiisi', $code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount, $valid_from, $valid_until, $usage_limit, $is_active, $for_new_users_only, $id);
        
        if ($stmt->execute()) {
            $message = "Coupon '$code' updated successfully!";
            // Redirect to clear the edit_id from URL and close the modal
            header('Location: coupons.php?message=' . urlencode($message));
            exit();
        } else {
            $error = "Error updating coupon: " . $conn->error;
        }
    }
}

// Handle Delete Coupon
if (isset($_POST['delete_coupon'])) {
    $id = (int)$_POST['coupon_id'];
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $message = "Coupon deleted successfully!";
    } else {
        $error = "Error deleting coupon: " . $conn->error;
    }
}

// Fetch all coupons
$coupons = [];
$result = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row;
    }
}

// Get coupon for editing
$edit_coupon = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    $edit_coupon = $edit_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Coupon Management - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
     <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .coupon-management {
            margin-top: 10px;
        }

        .coupon-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            gap: 12px;
        }

        .coupon-management-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .coupon-management-header .btn-primary {
            white-space: nowrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
        }

        .btn-primary {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #f97316;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #111827;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-danger {
            background-color: #ef4444;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-backdrop.hidden {
            display: none;
        }

        .modal {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px 22px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
            position: relative;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .modal-body {
            padding-top: 4px;
        }

        .modal-actions {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 12px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #6b7280;
        }

        .modal-close:hover {
            color: #111827;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"] {
            width: 100%;
            padding: 8px 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            box-sizing: border-box;
            text-align: left;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 1px rgba(255, 152, 0, 0.25);
        }

        .error-text {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #dc2626;
        }

        .input-error {
            border-color: #dc2626 !important;
        }

        .coupon-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .coupon-table th,
        .coupon-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .coupon-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .coupon-table tbody tr:hover {
            background-color: #f3f4f6;
        }

        .coupon-table th:nth-child(2),
        .coupon-table td:nth-child(2),
        .coupon-table th:nth-child(3),
        .coupon-table td:nth-child(3),
        .coupon-table th:nth-child(4),
        .coupon-table td:nth-child(4) {
            text-align: center;
        }

        .coupon-table th:last-child,
        .coupon-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .coupon-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .coupon-table td:last-child .btn {
            margin: 0;
        }

        .status-active {
            background-color: #e3fcef;
            color: #0e9f6e;
        }

        .status-expired {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333333;
        }

        @media (max-width: 768px) {
            header h1 {
                font-size: 20px;
            }

            .coupon-management-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .coupon-management-header button {
                width: 100%;
            }

            .modal {
                margin: 0 12px;
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
                <h1>Coupon Management</h1>
            </header>

<?php if ($message): ?>
                <div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                    <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <section class="coupon-management">
                <div class="coupon-management-header">
                    <h2>All Coupons (<?php echo count($coupons); ?>)</h2>
                    <button id="openAddCoupon" class="btn btn-primary" type="button" onclick="document.getElementById('addCouponModalBackdrop').classList.remove('hidden')">
                        <i class="fa-solid fa-plus"></i> Add New Coupon
                    </button>
                </div>

                <table class="table coupon-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="couponTableBody">
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    <i class="fa-solid fa-ticket" style="font-size: 32px; color: #9ca3af; margin-bottom: 10px;"></i>
                                    <p>No coupons found. Add your first coupon!</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): 
                                $today = date('Y-m-d');
                                $is_expired = $coupon['valid_until'] < $today;
                                $status_class = $coupon['is_active'] ? ($is_expired ? 'status-expired' : 'status-active') : 'status-inactive';
                                $status_text = $coupon['is_active'] ? ($is_expired ? 'Expired' : 'Active') : 'Inactive';
                                $discount_display = $coupon['discount_type'] == 'percentage' ? $coupon['discount_value'] . '%' : '₹' . $coupon['discount_value'];
                            ?>
                                <tr data-coupon-id="<?php echo $coupon['id']; ?>">
                                    <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                    <td><?php echo $discount_display; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($coupon['valid_until'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <a href="?edit_id=<?php echo $coupon['id']; ?>" class="btn btn-secondary btn-edit" style="text-decoration: none;">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this coupon?');">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" name="delete_coupon" class="btn btn-danger btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="addCouponModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" onclick="document.getElementById('addCouponModalBackdrop').classList.add('hidden')">&times;</button>
            <div class="modal-header">Add New Coupon</div>
            <div class="modal-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Coupon Code *</label>
                            <input type="text" name="code" required placeholder="e.g. SAVE20">
                        </div>
                        <div class="form-group">
                            <label>Discount Type *</label>
                            <select name="discount_type" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Discount Value *</label>
                            <input type="number" name="discount_value" required min="0" step="0.01" placeholder="e.g. 20">
                        </div>
                        <div class="form-group">
                            <label>Max Discount (optional)</label>
                            <input type="number" name="max_discount" min="0" step="0.01" placeholder="For % coupons">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Min Order Amount</label>
                            <input type="number" name="min_order_amount" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Usage Limit (optional)</label>
                            <input type="number" name="usage_limit" min="1" placeholder="Unlimited if empty">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Valid From *</label>
                            <input type="date" name="valid_from" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Valid Until *</label>
                            <input type="date" name="valid_until" required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Coupon description..."></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_active" checked style="width: auto;">
                            <label style="margin: 0;">Active</label>
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="for_new_users_only" style="width: auto;">
                            <label style="margin: 0;">New Users Only</label>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addCouponModalBackdrop').classList.add('hidden')">Cancel</button>
                        <button type="submit" name="add_coupon" class="btn btn-primary">Add Coupon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($edit_coupon): ?>
    <div id="editCouponModalBackdrop" class="modal-backdrop" style="display: flex;">
        <div class="modal">
            <a href="coupons.php" class="modal-close" style="text-decoration: none;">&times;</a>
            <div class="modal-header">Edit Coupon</div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="coupon_id" value="<?php echo $edit_coupon['id']; ?>">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Coupon Code *</label>
                            <input type="text" name="code" required value="<?php echo htmlspecialchars($edit_coupon['code']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Discount Type *</label>
                            <select name="discount_type" required>
                                <option value="percentage" <?php echo $edit_coupon['discount_type'] == 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="fixed" <?php echo $edit_coupon['discount_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Discount Value *</label>
                            <input type="number" name="discount_value" required min="0" step="0.01" value="<?php echo $edit_coupon['discount_value']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Max Discount (optional)</label>
                            <input type="number" name="max_discount" min="0" step="0.01" value="<?php echo $edit_coupon['max_discount'] ?? ''; ?>">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Min Order Amount</label>
                            <input type="number" name="min_order_amount" min="0" step="0.01" value="<?php echo $edit_coupon['min_order_amount']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Usage Limit (optional)</label>
                            <input type="number" name="usage_limit" min="1" value="<?php echo $edit_coupon['usage_limit'] ?? ''; ?>">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Valid From *</label>
                            <input type="date" name="valid_from" required value="<?php echo $edit_coupon['valid_from']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Valid Until *</label>
                            <input type="date" name="valid_until" required value="<?php echo $edit_coupon['valid_until']; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2"><?php echo htmlspecialchars($edit_coupon['description'] ?? ''); ?></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="is_active" <?php echo $edit_coupon['is_active'] ? 'checked' : ''; ?> style="width: auto;">
                            <label style="margin: 0;">Active</label>
                        </div>
                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="for_new_users_only" <?php echo $edit_coupon['for_new_users_only'] ? 'checked' : ''; ?> style="width: auto;">
                            <label style="margin: 0;">New Users Only</label>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <a href="coupons.php" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
                        <button type="submit" name="edit_coupon" class="btn btn-primary">Update Coupon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="deleteCouponModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeDeleteCoupon">&times;</button>
            <div class="modal-header">Delete Coupon</div>
            <div class="modal-body">
                <p id="deleteCouponText">Are you sure you want to delete this coupon?</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelDeleteCoupon" class="btn btn-secondary">Cancel</button>
                <button type="button" id="confirmDeleteCoupon" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const navbar = document.getElementById('sidebarNav');
            const navbarToggle = document.getElementById('navbarToggle');
            if (navbar && navbarToggle) {
                navbarToggle.addEventListener('click', function () {
                    navbar.classList.toggle('open');
                });
            }

            // Highlight current page in sidebar
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

            const couponTableBody = document.getElementById('couponTableBody');
            const addCouponForm = document.getElementById('addCouponForm');
            const editCouponForm = document.getElementById('editCouponForm');

            const openAddCouponBtn = document.getElementById('openAddCoupon');
            const addCouponModalBackdrop = document.getElementById('addCouponModalBackdrop');
            const cancelAddCouponBtn = document.getElementById('cancelAddCoupon');
            const closeAddCouponBtn = document.getElementById('closeAddCoupon');

            const editCouponModalBackdrop = document.getElementById('editCouponModalBackdrop');
            const cancelEditCouponBtn = document.getElementById('cancelEditCoupon');
            const closeEditCouponBtn = document.getElementById('closeEditCoupon');

            const deleteCouponModalBackdrop = document.getElementById('deleteCouponModalBackdrop');
            const cancelDeleteCouponBtn = document.getElementById('cancelDeleteCoupon');
            const closeDeleteCouponBtn = document.getElementById('closeDeleteCoupon');
            const confirmDeleteCouponBtn = document.getElementById('confirmDeleteCoupon');
            const deleteCouponText = document.getElementById('deleteCouponText');

            const addCouponCodeError = document.getElementById('addCouponCodeError');
            const addCouponDiscountError = document.getElementById('addCouponDiscountError');
            const addCouponExpiryError = document.getElementById('addCouponExpiryError');

            const editCouponCodeError = document.getElementById('editCouponCodeError');
            const editCouponDiscountError = document.getElementById('editCouponDiscountError');
            const editCouponExpiryError = document.getElementById('editCouponExpiryError');

            let rowBeingEdited = null;
            let rowBeingDeleted = null;

            function clearFieldError(input, errorElement) {
                if (input) {
                    input.classList.remove('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }

            function showFieldError(input, errorElement, message) {
                if (input) {
                    input.classList.add('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = message || '';
                }
            }

            function couponCodeExists(code, ignoreRow) {
                const rows = couponTableBody.querySelectorAll('tr');
                const target = code.trim().toUpperCase();
                for (const row of rows) {
                    if (ignoreRow && row === ignoreRow) continue;
                    const codeCell = row.children[0];
                    if (!codeCell) continue;
                    if (codeCell.textContent.trim().toUpperCase() === target) {
                        return true;
                    }
                }
                return false;
            }

            function setCouponRowStatus(row) {
                const expiryCell = row.children[2];
                const statusCell = row.children[3];
                if (!expiryCell || !statusCell) return;

                const expiryText = expiryCell.textContent.trim();
                if (!expiryText) {
                    statusCell.textContent = '';
                    return;
                }

                const expiryDate = new Date(expiryText);
                if (isNaN(expiryDate.getTime())) {
                    statusCell.textContent = '';
                    return;
                }

                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const isExpired = expiryDate < today;

                statusCell.textContent = '';
                const span = document.createElement('span');
                span.className = 'status-pill ' + (isExpired ? 'status-expired' : 'status-active');
                span.textContent = isExpired ? 'Expired' : 'Active';
                statusCell.appendChild(span);
            }

            function updateAllCouponStatuses() {
                const rows = couponTableBody.querySelectorAll('tr');
                rows.forEach(setCouponRowStatus);
            }

            function validateCouponFields(codeInput, discountInput, expiryInput, codeError, discountError, expiryError, ignoreRow) {
                clearFieldError(codeInput, codeError);
                clearFieldError(discountInput, discountError);
                clearFieldError(expiryInput, expiryError);

                const rawCode = (codeInput.value || '').trim();
                const code = rawCode.toUpperCase();
                const rawDiscount = discountInput.value;
                const discount = rawDiscount === '' ? NaN : Number(rawDiscount);
                const expiryValue = expiryInput.value;

                let hasError = false;

                if (!code) {
                    showFieldError(codeInput, codeError, 'Please enter a coupon code.');
                    hasError = true;
                } else if (!/^[A-Z0-9_-]{3,20}$/.test(code)) {
                    showFieldError(codeInput, codeError, 'Coupon code should be 3-20 characters (A-Z, 0-9, - or _).');
                    hasError = true;
                } else if (couponCodeExists(code, ignoreRow)) {
                    showFieldError(codeInput, codeError, 'A coupon with this code already exists.');
                    hasError = true;
                }

                if (rawDiscount === '') {
                    showFieldError(discountInput, discountError, 'Please enter a discount percentage.');
                    hasError = true;
                } else if (isNaN(discount)) {
                    showFieldError(discountInput, discountError, 'Please enter a valid discount percentage.');
                    hasError = true;
                } else if (discount <= 0 || discount > 100) {
                    showFieldError(discountInput, discountError, 'Discount should be between 1 and 100.');
                    hasError = true;
                }

                if (!expiryValue) {
                    showFieldError(expiryInput, expiryError, 'Please select an expiry date.');
                    hasError = true;
                } else {
                    const expiryDate = new Date(expiryValue);
                    if (isNaN(expiryDate.getTime())) {
                        showFieldError(expiryInput, expiryError, 'Please select a valid date.');
                        hasError = true;
                    } else {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        if (expiryDate < today) {
                            showFieldError(expiryInput, expiryError, 'Expiry date cannot be in the past.');
                            hasError = true;
                        }
                    }
                }

                if (hasError) {
                    return null;
                }

                return {
                    code,
                    discount,
                    expiry: expiryValue
                };
            }

            if (openAddCouponBtn && addCouponModalBackdrop && addCouponForm) {
                openAddCouponBtn.addEventListener('click', function () {
                    addCouponForm.reset();
                    clearFieldError(document.getElementById('addCouponCode'), addCouponCodeError);
                    clearFieldError(document.getElementById('addCouponDiscount'), addCouponDiscountError);
                    clearFieldError(document.getElementById('addCouponExpiry'), addCouponExpiryError);
                    addCouponModalBackdrop.classList.remove('hidden');
                });
            }

            if (cancelAddCouponBtn && addCouponModalBackdrop) {
                cancelAddCouponBtn.addEventListener('click', function () {
                    addCouponModalBackdrop.classList.add('hidden');
                });
            }

            if (closeAddCouponBtn && addCouponModalBackdrop) {
                closeAddCouponBtn.addEventListener('click', function () {
                    addCouponModalBackdrop.classList.add('hidden');
                });
            }

            if (cancelEditCouponBtn && editCouponModalBackdrop) {
                cancelEditCouponBtn.addEventListener('click', function () {
                    editCouponModalBackdrop.classList.add('hidden');
                    rowBeingEdited = null;
                });
            }

            if (closeEditCouponBtn && editCouponModalBackdrop) {
                closeEditCouponBtn.addEventListener('click', function () {
                    editCouponModalBackdrop.classList.add('hidden');
                    rowBeingEdited = null;
                });
            }

            if (cancelDeleteCouponBtn && deleteCouponModalBackdrop) {
                cancelDeleteCouponBtn.addEventListener('click', function () {
                    deleteCouponModalBackdrop.classList.add('hidden');
                    rowBeingDeleted = null;
                });
            }

            if (closeDeleteCouponBtn && deleteCouponModalBackdrop) {
                closeDeleteCouponBtn.addEventListener('click', function () {
                    deleteCouponModalBackdrop.classList.add('hidden');
                    rowBeingDeleted = null;
                });
            }

            addCouponForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const codeInput = document.getElementById('addCouponCode');
                const discountInput = document.getElementById('addCouponDiscount');
                const expiryInput = document.getElementById('addCouponExpiry');

                const values = validateCouponFields(
                    codeInput,
                    discountInput,
                    expiryInput,
                    addCouponCodeError,
                    addCouponDiscountError,
                    addCouponExpiryError,
                    null
                );

                if (!values) return;

                const tr = document.createElement('tr');
                tr.dataset.couponCode = values.code;

                const codeTd = document.createElement('td');
                codeTd.textContent = values.code;

                const discountTd = document.createElement('td');
                discountTd.textContent = values.discount + '%';

                const expiryTd = document.createElement('td');
                expiryTd.textContent = values.expiry;

                const statusTd = document.createElement('td');

                const actionsTd = document.createElement('td');
                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'btn btn-secondary btn-edit';
                editBtn.textContent = 'Edit';

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'btn btn-danger btn-delete';
                deleteBtn.textContent = 'Delete';

                actionsTd.appendChild(editBtn);
                actionsTd.appendChild(deleteBtn);

                tr.appendChild(codeTd);
                tr.appendChild(discountTd);
                tr.appendChild(expiryTd);
                tr.appendChild(statusTd);
                tr.appendChild(actionsTd);

                couponTableBody.appendChild(tr);
                setCouponRowStatus(tr);

                addCouponModalBackdrop.classList.add('hidden');
            });

            couponTableBody.addEventListener('click', function (e) {
                const target = e.target;
                const row = target.closest('tr');
                if (!row) return;

                if (target.classList.contains('btn-edit')) {
                    rowBeingEdited = row;
                    const codeCell = row.children[0];
                    const discountCell = row.children[1];
                    const expiryCell = row.children[2];

                    const codeInput = document.getElementById('editCouponCode');
                    const discountInput = document.getElementById('editCouponDiscount');
                    const expiryInput = document.getElementById('editCouponExpiry');

                    codeInput.value = codeCell.textContent.trim();
                    const discountText = discountCell.textContent.trim().replace('%', '');
                    discountInput.value = discountText;
                    expiryInput.value = expiryCell.textContent.trim();

                    clearFieldError(codeInput, editCouponCodeError);
                    clearFieldError(discountInput, editCouponDiscountError);
                    clearFieldError(expiryInput, editCouponExpiryError);

                    editCouponModalBackdrop.classList.remove('hidden');
                }

                if (target.classList.contains('btn-delete')) {
                    rowBeingDeleted = row;
                    const codeCell = row.children[0];
                    deleteCouponText.textContent = 'Are you sure you want to delete coupon "' + codeCell.textContent.trim() + '"?';
                    deleteCouponModalBackdrop.classList.remove('hidden');
                }
            });

            editCouponForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!rowBeingEdited) return;

                const codeInput = document.getElementById('editCouponCode');
                const discountInput = document.getElementById('editCouponDiscount');
                const expiryInput = document.getElementById('editCouponExpiry');

                const values = validateCouponFields(
                    codeInput,
                    discountInput,
                    expiryInput,
                    editCouponCodeError,
                    editCouponDiscountError,
                    editCouponExpiryError,
                    rowBeingEdited
                );

                if (!values) return;

                const codeCell = rowBeingEdited.children[0];
                const discountCell = rowBeingEdited.children[1];
                const expiryCell = rowBeingEdited.children[2];

                codeCell.textContent = values.code;
                discountCell.textContent = values.discount + '%';
                expiryCell.textContent = values.expiry;
                rowBeingEdited.dataset.couponCode = values.code;

                setCouponRowStatus(rowBeingEdited);

                editCouponModalBackdrop.classList.add('hidden');
                rowBeingEdited = null;
            });

            confirmDeleteCouponBtn.addEventListener('click', function () {
                if (rowBeingDeleted) {
                    rowBeingDeleted.remove();
                }
                deleteCouponModalBackdrop.classList.add('hidden');
                rowBeingDeleted = null;
            });

            updateAllCouponStatuses();
        })();
    </script>
</body>

</html>
