<?php
session_start();
include '../Database_file/config.php';

// Set admin session for testing
$_SESSION['is_admin'] = true;
$_SESSION['logged_in'] = true;

$message = '';
$error = '';

// Handle Add Gift Card
if (isset($_POST['add_gift_card'])) {
    $card_code = trim($_POST['card_code'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    
    if (!empty($card_code) && $amount > 0 && !empty($expiry_date)) {
        // Check if card code already exists
        $check = $conn->prepare("SELECT id FROM gift_cards WHERE card_code = ?");
        $check->bind_param('s', $card_code);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'Gift card code already exists!';
        } else {
            $stmt = $conn->prepare("INSERT INTO gift_cards (card_code, amount, balance, status, expiry_date, created_by) VALUES (?, ?, ?, 'active', ?, ?)");
            $admin_id = $_SESSION['user_id'] ?? 1;
            $stmt->bind_param('sddsi', $card_code, $amount, $amount, $expiry_date, $admin_id);
            
            if ($stmt->execute()) {
                $message = 'Gift card added successfully!';
            } else {
                $error = 'Failed to add gift card: ' . $conn->error;
            }
        }
    } else {
        $error = 'Please fill all fields correctly!';
    }
}

// Handle Edit Gift Card
if (isset($_POST['edit_gift_card'])) {
    $id = intval($_POST['id'] ?? 0);
    $card_code = trim($_POST['card_code'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $expiry_date = $_POST['expiry_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    if ($id > 0 && !empty($card_code) && $amount > 0) {
        // Check if card code exists for other cards
        $check = $conn->prepare("SELECT id FROM gift_cards WHERE card_code = ? AND id != ?");
        $check->bind_param('si', $card_code, $id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error = 'Gift card code already exists!';
        } else {
            $stmt = $conn->prepare("UPDATE gift_cards SET card_code = ?, amount = ?, expiry_date = ?, status = ? WHERE id = ?");
            $stmt->bind_param('sdssi', $card_code, $amount, $expiry_date, $status, $id);
            
            if ($stmt->execute()) {
                $message = 'Gift card updated successfully!';
            } else {
                $error = 'Failed to update gift card: ' . $conn->error;
            }
        }
    } else {
        $error = 'Please fill all fields correctly!';
    }
}

// Handle Delete Gift Card
if (isset($_POST['delete_gift_card'])) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM gift_cards WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $message = 'Gift card deleted successfully!';
        } else {
            $error = 'Failed to delete gift card: ' . $conn->error;
        }
    }
}

// Fetch all gift cards
$gift_cards = [];
$result = $conn->query("SELECT * FROM gift_cards ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gift_cards[] = $row;
    }
}

// Fetch user purchases (gift cards with user_id assigned)
$user_purchases = [];
$purchase_result = $conn->query("SELECT g.*, u.full_name as user_name, u.email as user_email 
    FROM gift_cards g 
    LEFT JOIN users u ON g.user_id = u.id 
    WHERE g.user_id IS NOT NULL 
    ORDER BY g.id DESC");
if ($purchase_result) {
    while ($row = $purchase_result->fetch_assoc()) {
        $user_purchases[] = $row;
    }
}

// Get gift card for edit
$edit_card = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM gift_cards WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_card = $result->fetch_assoc();
}
?>
<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <title>Gift Card Management - Admin Panel</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="stylesheet" href="../admin/css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"

        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="

        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>

        .gift-management {

            margin-top: 10px;

        }



        .gift-management-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 16px;

            gap: 12px;

        }



        .gift-management-header h2 {

            margin: 0;

            font-size: 18px;

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
            text-decoration: none;
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
            text-decoration: none !important;
            border: none !important;
        }



        .btn-secondary:hover {
            background-color: #d1d5db;
            text-decoration: none !important;
            border: none !important;
        }



        .btn-danger {

            background-color: #ef4444;

            color: #ffffff;

        }



        .btn-danger:hover {

            background-color: #dc2626;

        }



        .status-pill {

            padding: 4px 10px;

            border-radius: 12px;

            font-size: 12px;

            font-weight: 500;

        }



        .status-active {

            background-color: #e3fcef;

            color: #0e9f6e;

        }



        .status-used {

            background-color: #f3f4f6;

            color: #6b7280;

        }



        .status-expired {

            background-color: #fee2e2;

            color: #b91c1c;

        }


        

        .modal-backdrop {

            position: fixed;

            inset: 0;

            background-color: rgba(15, 23, 42, 0.35);

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



        .form-group {

            margin-bottom: 12px;

        }



        .form-group label {

            display: block;

            margin-bottom: 4px;

            font-weight: 500;

            font-size: 14px;

        }



        .form-control {

            width: 100%;

            padding: 8px 10px;

            font-size: 14px;

            border-radius: 6px;

            border: 1px solid #d1d5db;

            box-sizing: border-box;

        }



        .form-control:focus {

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



        .modal-footer {

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



        .table th:nth-child(2),

        .table td:nth-child(2),

        .table th:nth-child(3),

        .table td:nth-child(3),

        .table th:nth-child(4),

        .table td:nth-child(4) {

            text-align: center;

        }



        .table th:last-child,

        .table td:last-child {

            text-align: center;

            white-space: nowrap;

        }



        .actions-cell {

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 8px;

            flex-wrap: nowrap;

        }



        .actions-cell a,

        .actions-cell form {

            display: inline-flex;

            margin: 0;

        }



        .table th:nth-child(2),

        .table td:nth-child(2),

        .table th:nth-child(3),

        .table td:nth-child(3),

        .table th:nth-child(4),

        .table td:nth-child(4) {

            text-align: center;

        }



        .table th:last-child,

        .table td:last-child {

            text-align: center;

            white-space: nowrap;

        }



        .user-purchases-section {

            margin-top: 40px;

        }



        @media (max-width: 768px) {

            .gift-management-header {

                flex-direction: column;

                align-items: stretch;

            }

            

            .table-responsive {

                overflow-x: auto;

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

                <h1>Gift Card Management</h1>

            </header>



            <section class="gift-management">
                <div class="gift-management-header">
                    <h2>Gift Cards</h2>
                    <button id="openAddGift" class="btn btn-primary">Add New Gift</button>
                </div>

                <?php if ($message): ?>
                    <div style="background: #e3fcef; color: #0e9f6e; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div style="background: #fdecea; color: #e02424; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Card Code</th>
                                <th>Value (₹)</th>
                                <th>Balance (₹)</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="giftTableBody">
                            <?php if (empty($gift_cards)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fa-solid fa-gift" style="font-size: 32px; color: #9ca3af; margin-bottom: 10px;"></i>
                                        <p>No gift cards found. Add your first gift card!</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($gift_cards as $card): ?>
                                    <tr>
                                        <td>#<?php echo $card['id']; ?></td>
                                        <td><?php echo htmlspecialchars($card['card_code']); ?></td>
                                        <td>₹<?php echo number_format($card['amount'], 0); ?></td>
                                        <td>₹<?php echo number_format($card['balance'], 0); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($card['expiry_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'status-active';
                                            if ($card['status'] == 'used') $status_class = 'status-used';
                                            if ($card['status'] == 'expired') $status_class = 'status-expired';
                                            ?>
                                            <span class="status-pill <?php echo $status_class; ?>"><?php echo ucfirst($card['status']); ?></span>
                                        </td>
                                        <td>
                                            <div class="actions-cell" style="display: flex; justify-content: center; align-items: center; gap: 8px;"><a href="?edit_id=<?php echo $card['id']; ?>" class="btn btn-secondary">Edit</a><form method="POST" style="display: inline-flex; margin: 0; padding: 0;" onsubmit="return confirm('Delete this gift card?');"><input type="hidden" name="id" value="<?php echo $card['id']; ?>"><button type="submit" name="delete_gift_card" class="btn btn-danger">Delete</button></form></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- User Purchases Section -->
            <section class="gift-management user-purchases-section">
                <div class="gift-management-header">
                    <h2>User Purchases</h2>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Card Code</th>
                                <th>Value (₹)</th>
                                <th>Balance (₹)</th>
                                <th>User</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_purchases)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fa-solid fa-users" style="font-size: 32px; color: #9ca3af; margin-bottom: 10px;"></i>
                                        <p>No user purchases yet.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($user_purchases as $purchase): ?>
                                    <tr>
                                        <td>#<?php echo $purchase['id']; ?></td>
                                        <td><?php echo htmlspecialchars($purchase['card_code']); ?></td>
                                        <td>₹<?php echo number_format($purchase['amount'], 0); ?></td>
                                        <td>₹<?php echo number_format($purchase['balance'], 0); ?></td>
                                        <td>
                                            <?php if ($purchase['user_name']): ?>
                                                <strong><?php echo htmlspecialchars($purchase['user_name']); ?></strong><br>
                                                <small style="color: #6b7280;"><?php echo htmlspecialchars($purchase['user_email']); ?></small>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">User #<?php echo $purchase['user_id']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($purchase['expiry_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'status-active';
                                            if ($purchase['status'] == 'used') $status_class = 'status-used';
                                            if ($purchase['status'] == 'expired') $status_class = 'status-expired';
                                            ?>
                                            <span class="status-pill <?php echo $status_class; ?>"><?php echo ucfirst($purchase['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div id="addGiftModal" class="modal-backdrop hidden">
 
        <div class="modal">

            <button type="button" class="modal-close" onclick="closeModal('addGiftModal')">&times;</button>

            <div class="modal-header">Add Gift Card</div>

            <form id="addGiftForm" method="POST" action="">     
                <div class="form-group">
                    <label>Card Code</label>
                    <input type="text" class="form-control" name="card_code" id="giftCode" placeholder="e.g. GIFT-1000-NEW" required>
                    <span class="error-text" id="giftCodeError"></span>
                </div>
                <div class="form-group">
                    <label>Value (₹)</label>
                    <input type="number" class="form-control" name="amount" id="giftValue" placeholder="e.g. 1000" required min="1">
                    <span class="error-text" id="giftValueError"></span>
                </div>
                <div class="form-group">
                    <label>Balance (₹)</label>
                    <input type="number" class="form-control" id="giftBalance" placeholder="Auto-set from Value" readonly style="background-color: #f3f4f6;">
                    <small style="color: #6b7280; font-size: 12px;">Balance will be equal to Value</small>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status" id="giftStatus">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                        <option value="used">Used</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date" id="giftExpiry" required>
                    <span class="error-text" id="giftExpiryError"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addGiftModal')">Cancel</button>
                    <button type="submit" name="add_gift_card" class="btn btn-primary">Add Gift</button>
                </div>
            </form>

        </div>

    </div>



    <!-- Edit Gift Card Modal -->

    <div id="editGiftModal" class="modal-backdrop hidden">

        <div class="modal">

            <button type="button" class="modal-close" onclick="closeModal('editGiftModal')">&times;</button>

            <div class="modal-header">Edit Gift Card</div>

            <form id="editGiftForm" method="POST" action="">
                <input type="hidden" name="id" id="editGiftId">
                <div class="form-group">
                    <label>Card Code</label>
                    <input type="text" class="form-control" name="card_code" id="editGiftCode" required>
                    <span class="error-text" id="editGiftCodeError"></span>
                </div>
                <div class="form-group">
                    <label>Value (₹)</label>
                    <input type="number" class="form-control" name="amount" id="editGiftValue" required min="1">
                    <span class="error-text" id="editGiftValueError"></span>
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" class="form-control" name="expiry_date" id="editGiftExpiry" required>
                    <span class="error-text" id="editGiftExpiryError"></span>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="status" id="editGiftStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                        <option value="used">Used</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editGiftModal')">Cancel</button>
                    <button type="submit" name="edit_gift_card" class="btn btn-primary">Update Gift</button>
                </div>
            </form>

        </div>

    </div>



    <script>
        (function() {
            const navbar = document.getElementById('sidebarNav');
            const navbarToggle = document.getElementById('navbarToggle');

            // Sidebar Toggle
            if (navbar && navbarToggle) {
                navbarToggle.addEventListener('click', function () {
                    navbar.classList.toggle('open');
                });
            }

            // Highlight current page in sidebar
            if (navbar) {
                const links = navbar.querySelectorAll('a');
                const currentPage = window.location.pathname.split('/').pop();
                console.log('Current page:', currentPage); // Debug log

                links.forEach(link => {
                    const href = link.getAttribute('href');
                    if (!href) return;
                    const hrefPage = href.split('/').pop();
                    console.log('Checking link:', hrefPage, 'against:', currentPage); // Debug log
                    if (hrefPage === currentPage) {
                        link.classList.add('active');
                        console.log('Added active class to:', hrefPage); // Debug log
                    }
                });
            }

            const giftTableBody = document.getElementById('giftTableBody');
            const addGiftForm = document.getElementById('addGiftForm');
            const editGiftForm = document.getElementById('editGiftForm');
            let rowBeingEdited = null;

            window.closeModal = function(id) {

                document.getElementById(id).classList.add('hidden');

                resetErrors();

            }



            document.getElementById('openAddGift').onclick = () => {

                addGiftForm.reset();

                resetErrors();

                document.getElementById('addGiftModal').classList.remove('hidden');

            };



            function resetErrors() {

                document.querySelectorAll('.error-text').forEach(el => el.textContent = '');

                document.querySelectorAll('.form-control').forEach(el => el.classList.remove('input-error'));

            }



            function showError(inputId, errorId, message) {

                document.getElementById(inputId).classList.add('input-error');

                document.getElementById(errorId).textContent = message;

            }



            function validateForm(code, value, expiry, prefix = '') {

                resetErrors();

                let isValid = true;



                // For Edit form, prefix is 'edit'

                const codeId = prefix ? 'editGiftCode' : 'giftCode';

                const codeErrorId = prefix ? 'editGiftCodeError' : 'giftCodeError';

                const valueId = prefix ? 'editGiftValue' : 'giftValue';

                const valueErrorId = prefix ? 'editGiftValueError' : 'giftValueError';

                const expiryId = prefix ? 'editGiftExpiry' : 'giftExpiry';

                const expiryErrorId = prefix ? 'editGiftExpiryError' : 'giftExpiryError';



                if (!code.trim()) {

                    showError(codeId, codeErrorId, 'Please enter a card code.');

                    isValid = false;

                }

                if (!value || value <= 0) {

                    showError(valueId, valueErrorId, 'Please enter a valid value.');

                    isValid = false;

                }

                if (!expiry) {

                    showError(expiryId, expiryErrorId, 'Please select an expiry date.');

                    isValid = false;

                } else {

                    const selectedDate = new Date(expiry);

                    const today = new Date();

                    today.setHours(0,0,0,0);

                    if (selectedDate < today) {

                        showError(expiryId, expiryErrorId, 'Expiry date cannot be in the past.');

                        isValid = false;

                    }

                }

                return isValid;
            }

            addGiftForm.onsubmit = (e) => {
                const code = document.getElementById('giftCode').value;
                const value = document.getElementById('giftValue').value;
                const expiry = document.getElementById('giftExpiry').value;

                if (!validateForm(code, value, expiry)) {
                    e.preventDefault();
                    return false;
                }
                return true;
            };

            giftTableBody.onclick = (e) => {
                const target = e.target;
                const row = target.closest('tr');
                if (!row) return;
                if (target.closest('form')) return;

                if (target.classList.contains('btn-delete')) {
                    return confirm('Are you sure you want to delete this gift card?');
                }
            };

            editGiftForm.onsubmit = (e) => {
                const code = document.getElementById('editGiftCode').value;
                const value = document.getElementById('editGiftValue').value;
                const expiry = document.getElementById('editGiftExpiry').value;

                if (!validateForm(code, value, expiry, 'edit')) {
                    e.preventDefault();
                    return false;
                }
                return true;
            };

            <?php if ($edit_card): ?>
            document.getElementById('editGiftId').value = '<?php echo $edit_card['id']; ?>';
            document.getElementById('editGiftCode').value = '<?php echo htmlspecialchars($edit_card['card_code']); ?>';
            document.getElementById('editGiftValue').value = '<?php echo $edit_card['amount']; ?>';
            document.getElementById('editGiftExpiry').value = '<?php echo date('Y-m-d', strtotime($edit_card['expiry_date'])); ?>';
            document.getElementById('editGiftStatus').value = '<?php echo $edit_card['status']; ?>';
            document.getElementById('editGiftModal').classList.remove('hidden');
            <?php endif; ?>

        })();

    </script>

</body>



</html>