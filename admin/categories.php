<?php
session_start();
include '../Database_file/config.php';

// Check if admin is logged in - Correct session variables
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $sort_order = $_POST['sort_order'] ?? 0;
    
    // Handle file upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/';
        $file_name = basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $file_name;
        }
    }
    
    $sql = "INSERT INTO categories (name, description, image, sort_order) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $description, $image, $sort_order);
    
    if ($stmt->execute()) {
        $success = "Category added successfully!";
    } else {
        $error = "Error adding category: " . $conn->error;
    }
}

// Handle Update Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $sort_order = $_POST['sort_order'] ?? 0;
    
    // Handle file upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/';
        $file_name = basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image = $file_name;
        }
    } else {
        // Keep existing image if no new file uploaded
        $result = $conn->query("SELECT image FROM categories WHERE id = $id");
        if ($row = $result->fetch_assoc()) {
            $image = $row['image'];
        }
    }
    
    $sql = "UPDATE categories SET name = ?, description = ?, image = ?, sort_order = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $name, $description, $image, $sort_order, $id);
    
    if ($stmt->execute()) {
        $success = "Category updated successfully!";
    } else {
        $error = "Error updating category: " . $conn->error;
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $id = $_POST['id'];
    
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Category deleted successfully!";
    } else {
        $error = "Error deleting category: " . $conn->error;
    }
}

// Handle Toggle Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $id = $_POST['id'];
    $is_active = $_POST['is_active'];
    
    $sql = "UPDATE categories SET is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $is_active, $id);
    
    if ($stmt->execute()) {
        $success = "Category status updated successfully!";
    } else {
        $error = "Error updating status: " . $conn->error;
    }
}

// Create categories table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table_sql);

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY sort_order ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Category Management - Admin Panel</title>
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

        .category-management {
            margin-top: 20px;
        }

        .category-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-card {
            background-color: #ffffff;
            padding: 16px 18px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .form-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            vertical-align: middle;
            line-height: 1.4;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .category-table th:nth-child(1),
        .category-table td:nth-child(1) {
            text-align: center;
            width: 60px;
            font-weight: 600;
        }
        
        .category-table th:nth-child(2),
        .category-table td:nth-child(2) {
            width: 150px;
            font-weight: 500;
        }
        
        .category-table th:nth-child(3),
        .category-table td:nth-child(3) {
            width: 200px;
            color: #6c757d;
        }
        
        .category-table th:nth-child(4),
        .category-table td:nth-child(4) {
            text-align: center;
            width: 80px;
        }
        
        .category-table th:nth-child(5),
        .category-table td:nth-child(5) {
            text-align: center;
            width: 80px;
            font-weight: 500;
        }
        
        .category-table th:nth-child(6),
        .category-table td:nth-child(6) {
            text-align: center; 
            width: 100px;
        }
        
        .category-table th:nth-child(7),
        .category-table td:nth-child(7) {
            text-align: center;
            width: 240px;
        }
        
        .status-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pill-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pill-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .category-image {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .category-table td:nth-child(7) {
            padding: 10px 8px;
        }
        
        .category-table td:nth-child(7) .btn {
            margin: 0 3px;
            min-width: 60px;
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
            max-width: 380px;
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

            .forms-grid {
                grid-template-columns: 1fr;
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
                <h1>Category Management</h1>
            </header>

            <section class="category-management">
                <div class="category-management-header">
                    <h2>Categories</h2>
                    <button id="openAddCategory" class="btn btn-primary" type="button">Add New Category</button>
                </div>

                <table class="table category-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <?php 
                        // Reset the result pointer to start from beginning
                        if ($categories) {
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()): 
                        ?>
                            <tr data-category-id="<?php echo $category['id']; ?>">
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <?php if ($category['image']): ?>
                                        <img src="../assets/img/<?php echo htmlspecialchars($category['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                             class="category-image">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td>
                                    <span class="status-pill status-pill-<?php echo $category['is_active'] ? 'paid' : 'pending'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-edit" 
                                            onclick="showEditCategoryModal(<?php echo $category['id']; ?>)">Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $category['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-<?php echo $category['is_active'] ? 'secondary' : 'success'; ?>">
                                            <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-delete" 
                                            onclick="showDeleteCategoryModal(<?php echo $category['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        } else {
                        ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">
                                    No categories found. Click "Add New Category" to create your first category.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="addCategoryModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeAddCategory">&times;</button>
            <div class="modal-header">Add Category</div>
            <div class="modal-body">
                <form id="addCategoryForm" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="add_category" value="1">
                    <div class="form-group">
                        <label for="addCategoryName">Category Name</label>
                        <input type="text" id="addCategoryName" name="name" placeholder="e.g. Electronics" required>
                        <span class="error-text" id="addCategoryNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addCategoryDescription">Description</label>
                        <input type="text" id="addCategoryDescription" name="description" placeholder="e.g. From ₹1000 to ₹50000" required>
                        <span class="error-text" id="addCategoryDescriptionError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addCategoryImage">Select Image</label>
                        <input type="file" id="addCategoryImage" name="image" accept="image/*" required>
                        <span class="error-text" id="addCategoryImageError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addCategorySortOrder">Sort Order</label>
                        <input type="number" id="addCategorySortOrder" name="sort_order" placeholder="e.g. 1" value="0" min="0">
                        <span class="error-text" id="addCategorySortOrderError"></span>
                    </div>

                    <div class="modal-actions">
                        <button type="button" id="cancelAddCategory" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editCategoryModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeEditCategory">&times;</button>
            <div class="modal-header">Edit Category</div>
            <div class="modal-body">
                <form id="editCategoryForm" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="update_category" value="1">
                    <input type="hidden" id="editCategoryId" name="id">
                    <div class="form-group">
                        <label for="editCategoryName">Category Name</label>
                        <input type="text" id="editCategoryName" name="name" required>
                        <span class="error-text" id="editCategoryNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editCategoryDescription">Description</label>
                        <input type="text" id="editCategoryDescription" name="description" required>
                        <span class="error-text" id="editCategoryDescriptionError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editCategoryImage">Select Image</label>
                        <input type="file" id="editCategoryImage" name="image" accept="image/*">
                        <span class="error-text" id="editCategoryImageError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editCategorySortOrder">Sort Order</label>
                        <input type="number" id="editCategorySortOrder" name="sort_order" min="0">
                        <span class="error-text" id="editCategorySortOrderError"></span>
                    </div>

                    <div class="modal-actions">
                        <button type="button" id="cancelEditCategory" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteCategoryModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeDeleteCategory">&times;</button>
            <div class="modal-header">Delete Category</div>
            <div class="modal-body">
                <p id="deleteCategoryText">Are you sure you want to delete this category?</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="cancelDeleteCategory" class="btn btn-secondary">Cancel</button>
                <form id="deleteCategoryForm" method="POST" style="display:inline;">
                    <input type="hidden" name="delete_category" value="1">
                    <input type="hidden" id="deleteCategoryId" name="id" value="">
                    <button type="submit" id="confirmDeleteCategory" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div id="messageModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeMessageModal">&times;</button>
            <div class="modal-header" id="messageModalTitle">Message</div>
            <div class="modal-body">
                <p id="messageModalText">This is a message.</p>
            </div>
            <div class="modal-actions">
                <button type="button" id="okMessageModal" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Mobile navbar toggle
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

            const categoryTableBody = document.getElementById('categoryTableBody');
            const addCategoryForm = document.getElementById('addCategoryForm');
            const editCategoryForm = document.getElementById('editCategoryForm');

            const openAddCategoryBtn = document.getElementById('openAddCategory');
            const addCategoryModalBackdrop = document.getElementById('addCategoryModalBackdrop');
            const cancelAddCategoryBtn = document.getElementById('cancelAddCategory');
            const closeAddCategoryBtn = document.getElementById('closeAddCategory');

            const editCategoryModalBackdrop = document.getElementById('editCategoryModalBackdrop');
            const cancelEditCategoryBtn = document.getElementById('cancelEditCategory');
            const closeEditCategoryBtn = document.getElementById('closeEditCategory');

            const deleteBackdrop = document.getElementById('deleteCategoryModalBackdrop');
            const deleteText = document.getElementById('deleteCategoryText');
            const cancelDeleteCategoryBtn = document.getElementById('cancelDeleteCategory');
            const confirmDeleteCategoryBtn = document.getElementById('confirmDeleteCategory');
            const closeDeleteCategoryBtn = document.getElementById('closeDeleteCategory');

            const messageBackdrop = document.getElementById('messageModalBackdrop');
            const messageTitle = document.getElementById('messageModalTitle');
            const messageText = document.getElementById('messageModalText');
            const okMessageModalBtn = document.getElementById('okMessageModal');
            const closeMessageModalBtn = document.getElementById('closeMessageModal');

            const addCategoryNameError = document.getElementById('addCategoryNameError');
            const addCategoryImageError = document.getElementById('addCategoryImageError');
            const editCategoryNameError = document.getElementById('editCategoryNameError');
            const editCategoryImageError = document.getElementById('editCategoryImageError');

            let rowBeingEdited = null;
            let rowBeingDeleted = null;

            function openMessageModal(title, text) {
                if (title) messageTitle.textContent = title;
                if (text) messageText.textContent = text;
                messageBackdrop.classList.remove('hidden');
            }

            function closeMessageModal() {
                messageBackdrop.classList.add('hidden');
            }

            okMessageModalBtn.addEventListener('click', closeMessageModal);
            closeMessageModalBtn.addEventListener('click', closeMessageModal);

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

            function categoryNameExists(name, ignoreRow) {
                const rows = categoryTableBody.querySelectorAll('tr');
                const target = name.trim().toLowerCase();
                for (const row of rows) {
                    if (ignoreRow && row === ignoreRow) continue;
                    const nameCell = row.children[1];
                    if (!nameCell) continue;
                    if (nameCell.textContent.trim().toLowerCase() === target) {
                        return true;
                    }
                }
                return false;
            }

            const MAX_IMAGE_SIZE_MB = 2; // maximum image size in megabytes
            const MAX_IMAGE_SIZE_BYTES = MAX_IMAGE_SIZE_MB * 1024 * 1024;

            function isValidImageFile(file) {
                if (!file) return true;
                if (file.size > MAX_IMAGE_SIZE_BYTES) {
                    return false;
                }
                return true;
            }

            function isValidImageUrl(url) {
                if (!url) return true;
                const trimmed = url.trim();
                if (/^https?:\/\//i.test(trimmed)) return true;
                if (/^blob:/i.test(trimmed)) return true;
                return false;
            }

            if (openAddCategoryBtn && addCategoryModalBackdrop && addCategoryForm) {
                openAddCategoryBtn.addEventListener('click', function () {
                    addCategoryForm.reset();
                    clearFieldError(document.getElementById('addCategoryName'), addCategoryNameError);
                    clearFieldError(document.getElementById('addCategoryImage'), addCategoryImageError);
                    addCategoryModalBackdrop.classList.remove('hidden');
                });
            }

            if (cancelAddCategoryBtn && addCategoryModalBackdrop) {
                cancelAddCategoryBtn.addEventListener('click', function () {
                    addCategoryModalBackdrop.classList.add('hidden');
                    clearFieldError(document.getElementById('addCategoryName'), addCategoryNameError);
                    clearFieldError(document.getElementById('addCategoryImage'), addCategoryImageError);
                });
            }

            if (closeAddCategoryBtn && addCategoryModalBackdrop) {
                closeAddCategoryBtn.addEventListener('click', function () {
                    addCategoryModalBackdrop.classList.add('hidden');
                    clearFieldError(document.getElementById('addCategoryName'), addCategoryNameError);
                    clearFieldError(document.getElementById('addCategoryImage'), addCategoryImageError);
                });
            }

            if (cancelEditCategoryBtn && editCategoryModalBackdrop) {
                cancelEditCategoryBtn.addEventListener('click', function () {
                    editCategoryModalBackdrop.classList.add('hidden');
                    rowBeingEdited = null;
                    clearFieldError(document.getElementById('editCategoryName'), editCategoryNameError);
                    clearFieldError(document.getElementById('editCategoryImage'), editCategoryImageError);
                });
            }

            if (closeEditCategoryBtn && editCategoryModalBackdrop) {
                closeEditCategoryBtn.addEventListener('click', function () {
                    editCategoryModalBackdrop.classList.add('hidden');
                    rowBeingEdited = null;
                    clearFieldError(document.getElementById('editCategoryName'), editCategoryNameError);
                    clearFieldError(document.getElementById('editCategoryImage'), editCategoryImageError);
                });
            }

            window.showEditCategoryModal = function (categoryId) {
                const row = categoryTableBody.querySelector('tr[data-category-id="' + categoryId + '"]');
                if (!row) return;
                rowBeingEdited = row;

                const cells = row.children;
                const nameCell = cells[1];
                const descCell = cells[2];
                const sortCell = cells[4];

                const idInput = document.getElementById('editCategoryId');
                const nameInput = document.getElementById('editCategoryName');
                const descInput = document.getElementById('editCategoryDescription');
                const sortInput = document.getElementById('editCategorySortOrder');
                const imageInput = document.getElementById('editCategoryImage');

                if (idInput) idInput.value = categoryId;
                if (nameInput) nameInput.value = nameCell ? nameCell.textContent.trim() : '';
                if (descInput) descInput.value = descCell ? descCell.textContent.trim() : '';
                if (sortInput) sortInput.value = sortCell ? (sortCell.textContent.trim() || '0') : '0';
                if (imageInput) imageInput.value = '';

                clearFieldError(nameInput, editCategoryNameError);
                clearFieldError(imageInput, editCategoryImageError);

                editCategoryModalBackdrop.classList.remove('hidden');
            };

            addCategoryForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const nameInput = document.getElementById('addCategoryName');
                const imageInput = document.getElementById('addCategoryImage');

                clearFieldError(nameInput, addCategoryNameError);
                clearFieldError(imageInput, addCategoryImageError);

                const name = nameInput.value.trim();
                const file = imageInput.files && imageInput.files[0];

                let hasError = false;

                if (!name) {
                    showFieldError(nameInput, addCategoryNameError, 'Please enter a category name.');
                    hasError = true;
                } else if (name.length < 3) {
                    showFieldError(nameInput, addCategoryNameError, 'Category name should be at least 3 characters long.');
                    hasError = true;
                } else if (categoryNameExists(name)) {
                    showFieldError(nameInput, addCategoryNameError, 'A category with this name already exists.');
                    hasError = true;
                }

                if (!file) {
                    showFieldError(imageInput, addCategoryImageError, 'Please select an image.');
                    hasError = true;
                } else {
                    if (file.type && !file.type.startsWith('image/')) {
                        showFieldError(imageInput, addCategoryImageError, 'Please select a valid image file.');
                        hasError = true;
                    } else if (!isValidImageFile(file)) {
                        showFieldError(imageInput, addCategoryImageError, 'Image size should not exceed ' + MAX_IMAGE_SIZE_MB + 'MB.');
                        hasError = true;
                    }
                }

                if (hasError) {
                    return;
                }

                addCategoryForm.submit();
            });

            categoryTableBody.addEventListener('click', function (e) {
                const target = e.target;
                const row = target.closest('tr');
                if (!row) return;

                if (target.classList.contains('btn-edit')) {
                    rowBeingEdited = row;
                    const nameCell = row.children[1];
                    const statusCell = row.children[5];
                    const statusSpan = statusCell ? statusCell.querySelector('.status-pill') : null;
                    const nameInputEdit = document.getElementById('editCategoryName');
                    const imageInputEdit = document.getElementById('editCategoryImage');

                    if (nameInputEdit) {
                        nameInputEdit.value = nameCell.textContent.trim();
                    }
                    if (imageInputEdit) {
                        imageInputEdit.value = '';
                    }

                    clearFieldError(nameInputEdit, editCategoryNameError);
                    clearFieldError(imageInputEdit, editCategoryImageError);

                    editCategoryModalBackdrop.classList.remove('hidden');
                }

                if (target.classList.contains('btn-delete')) {
                    rowBeingDeleted = row;
                    const nameCell = row.children[1];
                    const rowId = row.getAttribute('data-category-id');
                    const deleteCategoryIdInput = document.getElementById('deleteCategoryId');
                    if (deleteCategoryIdInput) {
                        deleteCategoryIdInput.value = rowId || '';
                    }
                    deleteText.textContent = 'Are you sure you want to delete category "' + nameCell.textContent.trim() + '"?';
                    deleteBackdrop.classList.remove('hidden');
                }
            });

            editCategoryForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!rowBeingEdited) return;

                const nameInput = document.getElementById('editCategoryName');
                const imageInput = document.getElementById('editCategoryImage');

                clearFieldError(nameInput, editCategoryNameError);
                clearFieldError(imageInput, editCategoryImageError);

                const newName = nameInput.value.trim();
                const file = imageInput.files && imageInput.files[0];

                let hasError = false;

                if (!newName) {
                    showFieldError(nameInput, editCategoryNameError, 'Please enter a category name.');
                    hasError = true;
                } else if (newName.length < 3) {
                    showFieldError(nameInput, editCategoryNameError, 'Category name should be at least 3 characters long.');
                    hasError = true;
                } else if (categoryNameExists(newName, rowBeingEdited)) {
                    showFieldError(nameInput, editCategoryNameError, 'Another category with this name already exists.');
                    hasError = true;
                }

                if (file) {
                    if (file.type && !file.type.startsWith('image/')) {
                        showFieldError(imageInput, editCategoryImageError, 'Please select a valid image file.');
                        hasError = true;
                    } else if (!isValidImageFile(file)) {
                        showFieldError(imageInput, editCategoryImageError, 'Image size should not exceed ' + MAX_IMAGE_SIZE_MB + 'MB.');
                        hasError = true;
                    }
                }

                if (hasError) {
                    return;
                }

                const rowId = rowBeingEdited.getAttribute('data-category-id');
                const idInput = document.getElementById('editCategoryId');
                if (idInput) {
                    idInput.value = rowId || '';
                }

                editCategoryForm.submit();
            });

            function closeDeleteModal() {
                deleteBackdrop.classList.add('hidden');
                rowBeingDeleted = null;
            }

            cancelDeleteCategoryBtn.addEventListener('click', closeDeleteModal);
            closeDeleteCategoryBtn.addEventListener('click', closeDeleteModal);

            // Delete is handled by form submit to PHP
        })();
    </script>

</body>

</html>