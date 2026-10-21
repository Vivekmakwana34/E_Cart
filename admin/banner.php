<?php
// Banner Management with Database CRUD Operations
session_start();

// Database connection
require_once __DIR__ . '/../Database_file/config.php';

$message = $_SESSION['banner_message'] ?? '';
$error = $_SESSION['banner_error'] ?? '';
unset($_SESSION['banner_message'], $_SESSION['banner_error']);

// Handle Add Banner
if (isset($_POST['add_banner'])) {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'image';
    $position = $_POST['position'] ?? 'home_main';
    $status = $_POST['status'] ?? 'enabled';
    $link = trim($_POST['link'] ?? '');
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/img/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['banner_image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
            $image = 'banners/' . $fileName;
        }
    }
    
    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO banners (title, type, position, status, image, link) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $title, $type, $position, $status, $image, $link);
        
        if ($stmt->execute()) {
            $_SESSION['banner_message'] = 'Banner added successfully!';
        } else {
            $_SESSION['banner_error'] = 'Failed to add banner: ' . $conn->error;
        }
    } else {
        $_SESSION['banner_error'] = 'Banner title is required!';
    }
    header('Location: banner.php');
    exit;
}

// Handle Edit Banner
if (isset($_POST['edit_banner'])) {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'image';
    $position = $_POST['position'] ?? 'home_main';
    $status = $_POST['status'] ?? 'enabled';
    $link = trim($_POST['link'] ?? '');
    
    if ($id > 0 && !empty($title)) {
        // Check if new image uploaded
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['banner_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
                $image = 'banners/' . $fileName;
                
                $stmt = $conn->prepare("UPDATE banners SET title = ?, type = ?, position = ?, status = ?, image = ?, link = ? WHERE id = ?");
                $stmt->bind_param('ssssssi', $title, $type, $position, $status, $image, $link, $id);
            }
        } else {
            $stmt = $conn->prepare("UPDATE banners SET title = ?, type = ?, position = ?, status = ?, link = ? WHERE id = ?");
            $stmt->bind_param('sssssi', $title, $type, $position, $status, $link, $id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['banner_message'] = 'Banner updated successfully!';
        } else {
            $_SESSION['banner_error'] = 'Failed to update banner: ' . $conn->error;
        }
    }
    header('Location: banner.php');
    exit;
}

// Handle Delete Banner
if (isset($_POST['delete_banner'])) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        // Get image path first
        $stmt = $conn->prepare("SELECT image FROM banners WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            // Delete image file if exists
            if ($banner && !empty($banner['image'])) {
                $imagePath = __DIR__ . '/../assets/img/' . $banner['image'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $_SESSION['banner_message'] = 'Banner deleted successfully!';
        } else {
            $_SESSION['banner_error'] = 'Failed to delete banner: ' . $conn->error;
        }
    }
    header('Location: banner.php');
    exit;
}

// Handle Toggle Status
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['id'] ?? 0);
    $current_status = $_POST['current_status'] ?? 'enabled';
    $new_status = $current_status === 'enabled' ? 'disabled' : 'enabled';
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE banners SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['banner_message'] = 'Banner status updated!';
        } else {
            $_SESSION['banner_error'] = 'Failed to update status: ' . $conn->error;
        }
    }
    header('Location: banner.php');
    exit;
}

// Fetch all banners - sort by ID ASC so new banners appear at the end
$banners = [];
$result = $conn->query("SELECT * FROM banners ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $banners[] = $row;
    }
}

// Get banner for edit
$edit_banner = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_banner = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Banner Management - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333333;
        }

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

        .banner-management {
            margin-top: 20px;
        }

        .banner-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 12px;
        }

        .banner-management-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .banner-management-header .btn-primary {
            white-space: nowrap;
        }

        .banner-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .banner-table th,
        .banner-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .banner-table th {
            background-color: #e5e7eb;
            font-weight: 600;
        }

        .banner-table th:first-child,
        .banner-table td:first-child {
            text-align: center;
            width: 90px;
        }

        .banner-table th:nth-child(3),
        .banner-table td:nth-child(3),
        .banner-table th:nth-child(4),
        .banner-table td:nth-child(4),
        .banner-table th:nth-child(5),
        .banner-table td:nth-child(5),
        .banner-table th:nth-child(6),
        .banner-table td:nth-child(6) {
            text-align: center;
        }

        .banner-table th:nth-child(7),
        .banner-table td:nth-child(7) {
            text-align: center;
            font-size: 12px;
        }

        .banner-table th:last-child,
        .banner-table td:last-child {
            text-align: center;
        }

        .banner-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .banner-table td:last-child .btn {
            margin: 0;
        }

        .banner-table tr:hover {
            background-color: #f9fafb;
        }

        .banner-thumb {
            width: 60px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            object-position: center;
            background-color: #f3f4f6;
            display: block;
            border: 1px solid #e5e7eb;
            max-width: 100%;
        }

        .banner-table td:nth-child(2) {
            text-align: center;
            width: 80px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-enabled {
            background-color: #e3fcef;
            color: #0e9f6e;
        }

        .status-disabled {
            background-color: #fdecea;
            color: #e02424;
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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
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
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #c0392b;
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
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background-color: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
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

            .main-content {
                margin-left: 0;
                padding: 12px;
            }

            .banner-management-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .banner-management-header button {
                width: 100%;
            }

            .banner-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .banner-table thead,
            .banner-table tbody,
            .banner-table tr,
            .banner-table th,
            .banner-table td {
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
                <h1>Banner Management</h1>
            </header>

            <section class="banner-management">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="banner-management-header">
                    <h2>Banners (<?php echo count($banners); ?>)</h2>
                    <button id="openAddBanner" class="btn btn-primary" type="button"><i class="fa-solid fa-plus"></i> Add New Banner</button>
                </div>

                <table class="banner-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bannerTableBody">
                        <?php if (empty($banners)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fa-solid fa-image" style="font-size: 32px; color: #9ca3af; margin-bottom: 10px;"></i>
                                    <p>No banners found. Add your first banner!</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($banners as $banner): ?>
                                <tr data-banner-id="<?php echo $banner['id']; ?>">
                                    <td style="text-align: center; font-weight: 600; color: #666;">#<?php echo $banner['id']; ?></td>
                                    <td>
                                        <?php if (!empty($banner['image'])): ?>
                                            <img src="../assets/img/<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" class="banner-thumb">
                                        <?php else: ?>
                                            <div style="width: 100px; height: 50px; background: #f3f4f6; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 12px;">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($banner['title']); ?></td>
                                    <td><?php echo ucfirst($banner['type']); ?></td>
                                    <td><?php echo htmlspecialchars($banner['position']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $banner['status']; ?>">
                                            <?php echo $banner['status'] === 'enabled' ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-size: 12px; color: #666;"><?php echo date('Y-m-d', strtotime($banner['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                                            <a href="?edit_id=<?php echo $banner['id']; ?>" class="btn btn-secondary" style="text-decoration: none; padding: 6px 12px; font-size: 12px;">Edit</a>
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $banner['status']; ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                                    <?php echo $banner['status'] === 'enabled' ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Delete this banner?');">
                                                <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
                                                <button type="submit" name="delete_banner" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="addBannerModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeAddBanner">&times;</button>
            <div class="modal-header">Add New Banner</div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="addBannerTitle">Banner Title *</label>
                        <input type="text" name="title" id="addBannerTitle" placeholder="e.g. Summer Sale" required>
                    </div>
                    <div class="form-group">
                        <label for="addBannerType">Banner Type</label>
                        <select name="type" id="addBannerType">
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addBannerPosition">Position</label>
                        <select name="position" id="addBannerPosition">
                            <option value="home_main">Home Main Banner</option>
                            <option value="home_secondary">Home Secondary</option>
                            <option value="category_page">Category Page</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addBannerImage">Banner Image</label>
                        <input type="file" name="banner_image" id="addBannerImage" accept="image/*,video/*">
                    </div>
                    <div class="form-group">
                        <label for="addBannerStatus">Status</label>
                        <select name="status" id="addBannerStatus">
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="cancelAddBanner" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="add_banner" class="btn btn-primary">Add Banner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($edit_banner): ?>
    <div id="editBannerModalBackdrop" class="modal-backdrop" style="display: flex;">
        <div class="modal" style="max-width: 450px;">
            <a href="banner.php" class="modal-close" style="text-decoration: none;">&times;</a>
            <div class="modal-header">Edit Banner</div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $edit_banner['id']; ?>">
                    <div class="form-group">
                        <label for="editBannerTitle">Banner Title *</label>
                        <input type="text" name="title" id="editBannerTitle" value="<?php echo htmlspecialchars($edit_banner['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="editBannerType">Banner Type</label>
                        <select name="type" id="editBannerType">
                            <option value="image" <?php echo $edit_banner['type'] === 'image' ? 'selected' : ''; ?>>Image</option>
                            <option value="video" <?php echo $edit_banner['type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editBannerPosition">Position</label>
                        <select name="position" id="editBannerPosition">
                            <option value="home_main" <?php echo $edit_banner['position'] === 'home_main' ? 'selected' : ''; ?>>Home Main Banner</option>
                            <option value="home_secondary" <?php echo $edit_banner['position'] === 'home_secondary' ? 'selected' : ''; ?>>Home Secondary</option>
                            <option value="category_page" <?php echo $edit_banner['position'] === 'category_page' ? 'selected' : ''; ?>>Category Page</option>
                        </select>
                    </div>
                    <?php if (!empty($edit_banner['image'])): ?>
                    <div class="form-group">
                        <label>Current Image</label>
                        <img src="../assets/img/<?php echo htmlspecialchars($edit_banner['image']); ?>" alt="Current" style="max-width: 100%; max-height: 150px; border-radius: 6px; margin-top: 8px;">
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="editBannerImage"><?php echo !empty($edit_banner['image']) ? 'Change Image' : 'Upload Image'; ?></label>
                        <input type="file" name="banner_image" id="editBannerImage" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="editBannerStatus">Status</label>
                        <select name="status" id="editBannerStatus">
                            <option value="enabled" <?php echo $edit_banner['status'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo $edit_banner['status'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <a href="banner.php" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
                        <button type="submit" name="edit_banner" class="btn btn-primary">Update Banner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="deleteBannerModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeDeleteBanner">&times;</button>
            <div class="modal-header">Delete Banner</div>
            <div class="modal-body">
                <p id="deleteBannerText">Are you sure you want to delete this banner?</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelDeleteBanner" class="btn btn-secondary">Cancel</button>
                <button type="button" id="confirmDeleteBanner" class="btn btn-danger">Delete</button>
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

            // Simple modal handling for Add Banner
            const openAddBannerBtn = document.getElementById('openAddBanner');
            const addBannerModalBackdrop = document.getElementById('addBannerModalBackdrop');
            const cancelAddBannerBtn = document.getElementById('cancelAddBanner');
            const closeAddBannerBtn = document.getElementById('closeAddBanner');

            if (openAddBannerBtn && addBannerModalBackdrop) {
                openAddBannerBtn.addEventListener('click', function () {
                    addBannerModalBackdrop.classList.remove('hidden');
                });
            }

            if (cancelAddBannerBtn && addBannerModalBackdrop) {
                cancelAddBannerBtn.addEventListener('click', function () {
                    addBannerModalBackdrop.classList.add('hidden');
                });
            }

            if (closeAddBannerBtn && addBannerModalBackdrop) {
                closeAddBannerBtn.addEventListener('click', function () {
                    addBannerModalBackdrop.classList.add('hidden');
                });
            }
        })();
    </script>
</body>

</html>