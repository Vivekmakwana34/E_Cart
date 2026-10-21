<?php
session_start();

// TEMPORARILY DISABLE ADMIN CHECK FOR TESTING
// Check if admin is logged in (simple check)
// if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//     header("Location: ../login.php");
//     exit();
// }

// Set admin session for testing
$_SESSION['is_admin'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'admin@ecart.com';
$_SESSION['full_name'] = 'Admin';
$_SESSION['logged_in'] = true;

// Include database configuration
require_once '../Database_file/config.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_user') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = trim($_POST['role']);
        $status = trim($_POST['status']);
        
        // Validation
        if (empty($full_name) || empty($email) || empty($password) || empty($role) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        // Password validation
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            exit();
        }
        
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user (email_verified = 1, is_active based on status)
        $is_active = ($status === 'active') ? 1 : 0;
        $email_verified = 1; // Skip email verification for admin-added users
        
        $insert_sql = "INSERT INTO users (full_name, email, password, role, is_active, email_verified, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssis", $full_name, $email, $hashed_password, $role, $is_active, $email_verified);
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'User added successfully!<br><br>'.
                
                '&#x1F4E7; Email: ' . $email . '<br>'.
                '&#x1F510; Password: ' . $password . '<br><br>'.
                
                'Customer can login with:<br>'.
                '&#x2022; Email: ' . $email . '<br>'.
                '&#x2022; Password: ' . $password . '<br>'.
                '&#x2022; Login URL: ' . $_SERVER['HTTP_HOST'] . '/login.php<br><br>'.
                
                'Note: Email verification is skipped for admin-added users.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'update_user') {
        $user_id = intval($_POST['user_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $status = trim($_POST['status']);
        
        // Validation
        if (empty($user_id) || empty($full_name) || empty($email) || empty($role) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        // Check if email already exists (excluding current user)
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        
        // Update user
        $is_active = ($status === 'active') ? 1 : 0;
        
        $update_sql = "UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssii", $full_name, $email, $role, $is_active, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'toggle_status') {
        $user_id = intval($_POST['user_id']);
        $status = trim($_POST['status']);
        
        if (empty($user_id) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'User ID and status are required']);
            exit();
        }
        
        $is_active = ($status === 'active') ? 1 : 0;
        
        $update_sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $is_active, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User status updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        
        if (empty($user_id)) {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit();
        }
        
        // Delete user
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
        exit();
    }
}

// Fetch all users from database
$users = [];
$sql = "SELECT id, full_name, email, role, is_active, email_verified, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Get admin email from database
$admin_email = null;
$admin_check = $conn->query("SELECT email FROM users WHERE id = 20 LIMIT 1"); // Changed to ID 20 based on your image
if ($admin_check && $admin_row = $admin_check->fetch_assoc()) {
    $admin_email = $admin_row['email'];
}

// Debug: Print admin email
error_log("Admin Email Found: " . ($admin_email ? $admin_email : "NULL"));

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>User Management - Admin Panel</title>
     <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
        }

        .container {
            display: flex;
        }

        .navbar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
        }

        .navbar h2 {
            text-align: center;
        }

        .navbar ul {
            list-style: none;
            padding: 0;
        }

        .navbar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 5px;
            transition: background-color 0.3s ease, padding-left 0.3s ease;
        }

        .navbar ul li a:hover {
            background-color: #3551b5;
            padding-left: 25px;
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

        .dashboard-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            flex-basis: 220px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card h3 {
            margin-top: 0;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0 0;
        }

        .user-management {
            margin-top: 20px;
        }

        .user-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .user-table th,
        .user-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .user-table th {
            background-color: #e5e7eb;
            font-weight: 600;
        }
        
        /* Name column (2nd) - center align */
        .user-table th:nth-child(2),
        .user-table td:nth-child(2) {
            text-align: center;
        }
        
        /* Email column (3rd) - center align */
        .user-table th:nth-child(3),
        .user-table td:nth-child(3) {
            text-align: center;
        }
        
        /* User Type column (4th) - center align */
        .user-table th:nth-child(4),
        .user-table td:nth-child(4) {
            text-align: center;
        }
        
        /* Status column (5th) - center align */
        .user-table th:nth-child(5),
        .user-table td:nth-child(5) {
            text-align: center;
        }
        
        /* Email Verified column (6th) - center align */
        .user-table th:nth-child(6),
        .user-table td:nth-child(6) {
            text-align: center;
        }
        
        /* Actions column (last) - center align */
        .user-table th:last-child,
        .user-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .user-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .user-table td:last-child .btn {
            margin: 0;
        }

        .user-table tr:hover {
            background-color: #f9fafb;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background-color: #e3fcef;
            color: #0e9f6e;
        }

        .status-blocked {
            background-color: #ef4444;
            color: white;
        }

        .user-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .user-type-admin {
            background-color: #8b5cf6;
            color: white;
        }

        .user-type-customer {
            background-color: #10b981;
            color: white;
        }

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
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        }

        .btn-primary {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #f57c00;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #333333;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .form-card {
            text-align: left;
        }

        .form-card h3 {
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 14px;
            box-sizing: border-box;
        }

        .input-error {
            border-color: #e74c3c;
        }

        .error-text {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 2px;
            display: block;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background-color: #fff;
            padding: 20px 24px;
            border-radius: 8px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 8px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            color: #7f8c8d;
        }

        .modal-close:hover {
            color: #2c3e50;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .modal-body {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .navbar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 12px;
            }

            .user-management-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .user-management-header button {
                width: 100%;
                align-self: stretch;
            }

            .user-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .user-table thead,
            .user-table tbody,
            .user-table tr,
            .user-table th,
            .user-table td {
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
                <h1>User Management</h1>
            </header>

            <section class="user-management">
                <div class="user-management-header">
                    <h2>Users</h2>
                    <button id="openAddUser" class="btn btn-primary" type="button">Add New User</button>
                </div>

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Email Verified</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i><br>
                                No users found in database.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-user-email="<?php echo htmlspecialchars($user['email']); ?>" data-user-role="<?php echo htmlspecialchars($user['role'] ?? 'user'); ?>" data-user-status="<?php echo $user['is_active'] ? 'active' : 'blocked'; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php 
                                    $is_admin = ($user['id'] == 20 || $user['role'] === 'admin');
                                    ?>
                                    <span class="user-type-badge <?php echo $is_admin ? 'user-type-admin' : 'user-type-customer'; ?>">
                                        <?php echo $is_admin ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-blocked'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Blocked'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['email_verified'] ? 'status-active' : 'status-blocked'; ?>">
                                        <?php echo $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-edit">Edit</button>
                                    <button type="button" class="btn btn-secondary btn-toggle-status">
                                        <?php echo $user['is_active'] ? 'Block' : 'Unblock'; ?>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-delete">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="forms-grid">
                </div>
            </section>
        </main>
    </div>

    <div id="addUserModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" id="closeAddUser" class="modal-close">&times;</button>
            <div class="modal-header">Add User</div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="form-group">
                        <label for="addName">Full Name</label>
                        <input type="text" id="addName" placeholder="Enter full name">
                        <small id="addNameError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="addEmail">Email</label>
                        <input type="email" id="addEmail" placeholder="Enter email">
                        <small id="addEmailError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="addPassword">Password</label>
                        <input type="password" id="addPassword" placeholder="Enter password (min 6 characters)">
                        <small id="addPasswordError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="addRole">Role</label>
                        <select id="addRole">
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                        <small id="addRoleError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="addStatus">Status</label>
                        <select id="addStatus">
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <small id="addStatusError" class="error-text"></small>
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="cancelAddUser" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editUserModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" id="closeEditUser" class="modal-close">&times;</button>
            <div class="modal-header">Edit User</div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="form-group">
                        <label for="editName">Full Name</label>
                        <input type="text" id="editName" placeholder="Enter full name">
                        <small id="editNameError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email</label>
                        <input type="email" id="editEmail" placeholder="Enter email">
                        <small id="editEmailError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="editRole">Role</label>
                        <select id="editRole">
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                        <small id="editRoleError" class="error-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus">
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <small id="editStatusError" class="error-text"></small>
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="cancelEditUser" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" id="closeDeleteUser" class="modal-close">&times;</button>
            <div class="modal-header">Delete User</div>
            <div class="modal-body">
                <p id="deleteModalText">Are you sure you want to delete this user?</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelDelete" class="btn btn-secondary">Cancel</button>
                <button type="button" id="confirmDelete" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const navbar = document.getElementById('sidebarNav');
            const navbarToggle = document.getElementById('navbarToggle');
            if (navbar && navbarToggle) {
                navbarToggle.addEventListener('click', () => {
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
        })();

        const addUserForm = document.getElementById('addUserForm');
        const editUserForm = document.getElementById('editUserForm');
        const openAddUserBtn = document.getElementById('openAddUser');
        const addUserModalBackdrop = document.getElementById('addUserModalBackdrop');
        const cancelAddUserBtn = document.getElementById('cancelAddUser');
        const closeAddUserBtn = document.getElementById('closeAddUser');
        const editUserModalBackdrop = document.getElementById('editUserModalBackdrop');
        const cancelEditUserBtn = document.getElementById('cancelEditUser');
        const closeEditUserBtn = document.getElementById('closeEditUser');
        const deleteBackdrop = document.getElementById('deleteModalBackdrop');
        const deleteModalText = document.getElementById('deleteModalText');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const closeDeleteUserBtn = document.getElementById('closeDeleteUser');

        openAddUserBtn.addEventListener('click', () => {
            addUserForm.reset();
            clearAddUserErrors();
            addUserModalBackdrop.classList.remove('hidden');
        });

        cancelAddUserBtn.addEventListener('click', () => {
            addUserModalBackdrop.classList.add('hidden');
        });

        closeAddUserBtn.addEventListener('click', () => {
            addUserModalBackdrop.classList.add('hidden');
        });

        cancelEditUserBtn.addEventListener('click', () => {
            editUserModalBackdrop.classList.add('hidden');
        });

        closeEditUserBtn.addEventListener('click', () => {
            editUserModalBackdrop.classList.add('hidden');
        });

        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const userId = row.dataset.userId;
                const name = row.dataset.userName;
                const email = row.children[2].textContent.trim();
                const badge = row.querySelector('.status-badge');
                let statusValue = '';
                if (badge) {
                    if (badge.classList.contains('status-active')) {
                        statusValue = 'active';
                    } else if (badge.classList.contains('status-blocked')) {
                        statusValue = 'blocked';
                    }
                }

                // Set hidden user ID
                document.getElementById('editUserId').value = userId;
                document.getElementById('editName').value = name;
                document.getElementById('editEmail').value = email;
                document.getElementById('editRole').value = row.dataset.userRole || 'user';
                document.getElementById('editStatus').value = statusValue;

                clearEditUserErrors();
                editUserModalBackdrop.classList.remove('hidden');
            });
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const userId = row.dataset.userId;
                const name = row.dataset.userName;
                
                // Store user ID in modal backdrop
                deleteBackdrop.dataset.userId = userId;
                deleteModalText.textContent = `Are you sure you want to delete ${name}?`;
                deleteBackdrop.classList.remove('hidden');
            });
        });

        cancelDeleteBtn.addEventListener('click', () => {
            deleteBackdrop.classList.add('hidden');
        });

        closeDeleteUserBtn.addEventListener('click', () => {
            deleteBackdrop.classList.add('hidden');
        });

        confirmDeleteBtn.addEventListener('click', () => {
            const userId = deleteBackdrop.dataset.userId;
            
            // Send AJAX request to delete user
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User deleted successfully!');
                    deleteBackdrop.classList.add('hidden');
                    // Refresh page to remove deleted user
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete user. Please try again.');
            });
        });

        document.querySelectorAll('.btn-toggle-status').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                const userId = row.dataset.userId;
                const badge = row.querySelector('.status-badge');
                const currentStatus = badge.classList.contains('status-active') ? 'active' : 'blocked';
                const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
                
                // Send AJAX request to toggle status
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('user_id', userId);
                formData.append('status', newStatus);

                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI immediately
                        if (badge.classList.contains('status-active')) {
                            badge.classList.remove('status-active');
                            badge.classList.add('status-blocked');
                            badge.textContent = 'Blocked';
                            e.target.textContent = 'Unblock';
                        } else {
                            badge.classList.remove('status-blocked');
                            badge.classList.add('status-active');
                            badge.textContent = 'Active';
                            e.target.textContent = 'Block';
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update status. Please try again.');
                });
            });
        });

        function clearAddUserErrors() {
            const errorFields = ['addNameError', 'addEmailError', 'addPasswordError', 'addRoleError', 'addStatusError'];
            errorFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });

            ['addName', 'addEmail', 'addPassword', 'addRole', 'addStatus'].forEach(id => {
                const input = document.getElementById(id);
                if (input) input.classList.remove('input-error');
            });
        }

        function clearEditUserErrors() {
            const errorFields = ['editNameError', 'editEmailError', 'editRoleError', 'editStatusError'];
            errorFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });

            ['editName', 'editEmail', 'editRole', 'editStatus'].forEach(id => {
                const input = document.getElementById(id);
                if (input) input.classList.remove('input-error');
            });
        }

        addUserForm.addEventListener('submit', (e) => {
            e.preventDefault();

            clearAddUserErrors();

            const nameInput = document.getElementById('addName');
            const emailInput = document.getElementById('addEmail');
            const passwordInput = document.getElementById('addPassword');
            const roleSelect = document.getElementById('addRole');
            const statusSelect = document.getElementById('addStatus');

            const nameError = document.getElementById('addNameError');
            const emailError = document.getElementById('addEmailError');
            const passwordError = document.getElementById('addPasswordError');
            const roleError = document.getElementById('addRoleError');
            const statusError = document.getElementById('addStatusError');

            let isValid = true;

            if (!nameInput.value.trim()) {
                nameError.textContent = 'Please enter full name';
                nameInput.classList.add('input-error');
                isValid = false;
            }

            const emailValue = emailInput.value.trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailValue) {
                emailError.textContent = 'Please enter email address';
                emailInput.classList.add('input-error');
                isValid = false;
            } else if (!emailPattern.test(emailValue)) {
                emailError.textContent = 'Please enter a valid email';
                emailInput.classList.add('input-error');
                isValid = false;
            }

            if (!roleSelect.value) {
                roleError.textContent = 'Please select role';
                roleSelect.classList.add('input-error');
                isValid = false;
            }

            if (!statusSelect.value) {
                statusError.textContent = 'Please select status';
                statusSelect.classList.add('input-error');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            // Send AJAX request to add user
            const formData = new FormData();
            formData.append('action', 'add_user');
            formData.append('full_name', nameInput.value.trim());
            formData.append('email', emailValue);
            formData.append('password', passwordInput.value.trim());
            formData.append('role', roleSelect.value);
            formData.append('status', statusSelect.value);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User added successfully!');
                    addUserForm.reset();
                    addUserModalBackdrop.classList.add('hidden');
                    // Refresh page to show new user
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add user. Please try again.');
            });
        });

        editUserForm.addEventListener('submit', (e) => {
            e.preventDefault();

            clearEditUserErrors();

            const nameInput = document.getElementById('editName');
            const emailInput = document.getElementById('editEmail');
            const roleSelect = document.getElementById('editRole');
            const statusSelect = document.getElementById('editStatus');

            const nameError = document.getElementById('editNameError');
            const emailError = document.getElementById('editEmailError');
            const roleError = document.getElementById('editRoleError');
            const statusError = document.getElementById('editStatusError');

            let isValid = true;

            if (!nameInput.value.trim()) {
                nameError.textContent = 'Please enter full name';
                nameInput.classList.add('input-error');
                isValid = false;
            }

            const emailValue = emailInput.value.trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailValue) {
                emailError.textContent = 'Please enter email address';
                emailInput.classList.add('input-error');
                isValid = false;
            } else if (!emailPattern.test(emailValue)) {
                emailError.textContent = 'Please enter a valid email';
                emailInput.classList.add('input-error');
                isValid = false;
            }

            if (!roleSelect.value) {
                roleError.textContent = 'Please select role';
                roleSelect.classList.add('input-error');
                isValid = false;
            }

            if (!statusSelect.value) {
                statusError.textContent = 'Please select status';
                statusSelect.classList.add('input-error');
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            // Get user ID from the form (add hidden field in edit modal)
            const userId = document.getElementById('editUserId').value;

            // Send AJAX request to update user
            const formData = new FormData();
            formData.append('action', 'update_user');
            formData.append('user_id', userId);
            formData.append('full_name', nameInput.value.trim());
            formData.append('email', emailValue);
            formData.append('role', roleSelect.value);
            formData.append('status', statusSelect.value);

            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User updated successfully!');
                    editUserModalBackdrop.classList.add('hidden');
                    // Refresh page to show updated user
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update user. Please try again.');
            });
        });
    </script>
</body>
</html>
