<?php
session_start();
include '../Database_file/config.php';

// Check if admin is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Handle product operations
$message = '';

// Add Product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    
    // Handle image upload
    $uploadedImages = [];
    if (isset($_FILES['image'])) {
        $target_dir = '../assets/img/';

        if (is_array($_FILES['image']['name'])) {
            for ($i = 0; $i < count($_FILES['image']['name']); $i++) {
                if (!isset($_FILES['image']['error'][$i]) || $_FILES['image']['error'][$i] !== 0) {
                    continue;
                }
                $image_name = time() . '_' . $i . '_' . $_FILES['image']['name'][$i];
                if (move_uploaded_file($_FILES['image']['tmp_name'][$i], $target_dir . $image_name)) {
                    $uploadedImages[] = $image_name;
                }
            }
        } else {
            if (isset($_FILES['image']['error']) && $_FILES['image']['error'] === 0) {
                $image_name = time() . '_' . $_FILES['image']['name'];
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name)) {
                    $uploadedImages[] = $image_name;
                }
            }
        }
    }

    $image = !empty($uploadedImages) ? json_encode($uploadedImages) : '';
    
    $sql = "INSERT INTO products (name, category_id, price, discount, stock, image, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidddsss", $name, $category_id, $price, $discount, $stock, $image, $description, $status);
    
    if ($stmt->execute()) {
        $message = "Product added successfully!";
    } else {
        $message = "Error adding product: " . $conn->error;
    }
}

// Edit Product
if (isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    
    // Handle image upload
    $image_update = '';
    $uploadedImages = [];
    if (isset($_FILES['image'])) {
        $target_dir = '../assets/img/';

        if (is_array($_FILES['image']['name'])) {
            for ($i = 0; $i < count($_FILES['image']['name']); $i++) {
                if (!isset($_FILES['image']['error'][$i]) || $_FILES['image']['error'][$i] !== 0) {
                    continue;
                }
                $image_name = time() . '_' . $i . '_' . $_FILES['image']['name'][$i];
                if (move_uploaded_file($_FILES['image']['tmp_name'][$i], $target_dir . $image_name)) {
                    $uploadedImages[] = $image_name;
                }
            }
        } else {
            if (isset($_FILES['image']['error']) && $_FILES['image']['error'] === 0) {
                $image_name = time() . '_' . $_FILES['image']['name'];
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image_name)) {
                    $uploadedImages[] = $image_name;
                }
            }
        }
    }

    if (!empty($uploadedImages)) {
        $image = json_encode($uploadedImages);
        $image_update = ", image = ?";
    }
    
    if ($image_update) {
        $sql = "UPDATE products SET name = ?, category_id = ?, price = ?, discount = ?, stock = ?, description = ?, status = ? $image_update WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidddsssi", $name, $category_id, $price, $discount, $stock, $description, $status, $image, $id);
    } else {
        $sql = "UPDATE products SET name = ?, category_id = ?, price = ?, discount = ?, stock = ?, description = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidddssi", $name, $category_id, $price, $discount, $stock, $description, $status, $id);
    }
    
    if ($stmt->execute()) {
        $message = "Product updated successfully!";
    } else {
        $message = "Error updating product: " . $conn->error;
    }
}

// Delete Product
if (isset($_POST['delete_product'])) {
    $id = $_POST['id'];
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $message = "Product deleted successfully!";
    } else {
        $message = "Error deleting product: " . $conn->error;
    }
}

// Toggle Status
if (isset($_POST['toggle_status'])) {
    $id = $_POST['id'];
    $current_status = $_POST['current_status'];
    $new_status = $current_status == 'active' ? 'inactive' : 'active';
    
    $sql = "UPDATE products SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        $message = "Product status updated!";
    }
}

// Get products with category filter and search
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$products = [];
$categories = [];
$category_product_counts = [];

// Get all categories with product counts
$cat_result = $conn->query("SELECT c.id, c.name, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    WHERE c.is_active = 1 
    GROUP BY c.id, c.name 
    ORDER BY c.name");
while ($cat = $cat_result->fetch_assoc()) {
    $categories[] = $cat;
    $category_product_counts[$cat['id']] = $cat['product_count'];
}

// Build product query
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";
$where_conditions = [];
$params = [];
$types = "";

if ($category_filter > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $where_conditions[] = "(p.name LIKE ? OR c.name LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY p.id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

while ($product = $result->fetch_assoc()) {
    $products[] = $product;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Product Management - Admin Panel</title>
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

        .product-management {
            margin-top: 20px;
        }

        .product-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Product Search Box - Same as orders */
        .product-search-container {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .product-search-container input {
            border: none;
            background: transparent;
            font-size: 14px;
            outline: none;
            width: 250px;
            padding: 4px 8px;
        }

        .product-search-container input::placeholder {
            color: #9ca3af;
        }

        .product-search-container .btn-clear {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 14px;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .product-search-container .btn-clear:hover {
            color: #374151;
        }

        .product-search-container .btn-clear.visible {
            display: flex;
        }

        .product-count-badge {
            background: #e0e7ff;
            color: #3730a3;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .category-filter-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .category-pill {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .category-pill:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .category-pill.active {
            background: #ff9800;
            color: white;
            border-color: #ff9800;
        }

        .category-pill .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .category-pill.active .count {
            background: rgba(255,255,255,0.3);
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .product-table th,
        .product-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .product-table th {
            background-color: #e5e7eb;
            font-weight: 600;
        }
        .product-table th:first-child,
        .product-table td:first-child {
            text-align: center;
            width: 70px;
        }

        .product-table th:nth-child(4),
        .product-table td:nth-child(4),
        .product-table th:nth-child(5),
        .product-table td:nth-child(5),
        .product-table th:nth-child(6),
        .product-table td:nth-child(6),
        .product-table th:nth-child(7),
        .product-table td:nth-child(7) {
            text-align: center;
        }

        .product-table th:last-child,
        .product-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .product-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .product-table td:last-child .btn {
            margin: 0;
        }

        .product-table tr:hover {
            background-color: #f9fafb;
        }

        .product-image-thumb {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            object-fit: cover;
            background-color: #f3f4f6;
            display: block;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
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

        .forms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d0d7de;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 70px;
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

            .product-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .product-table thead,
            .product-table tbody,
            .product-table tr,
            .product-table th,
            .product-table td {
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
                <h1>Product Management</h1>
            </header>

            <section class="product-management">
                <?php if ($message): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="product-management-header">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <h2>Products</h2>
                        <span class="product-count-badge"><?php echo count($products); ?> items</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <!-- Search Box - Real Time -->
                        <div class="product-search-container">
                            <i class="fa-solid fa-search" style="color: #9ca3af;"></i>
                            <input type="text" id="productSearchInput" placeholder="Search products or categories..." autocomplete="off">
                            <button type="button" id="clearSearchBtn" class="btn-clear" title="Clear search"><i class="fa-solid fa-times"></i></button>
                        </div>
                        <button id="openAddProduct" class="btn btn-primary" type="button"><i class="fa-solid fa-plus"></i> Add New Product</button>
                    </div>
                </div>


                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <?php foreach ($products as $product): ?>
                            <tr data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-status="<?php echo $product['status']; ?>">
                                <td>
                                    <?php if ($product['image']): ?>
                                        <?php
                                            $imgVal = (string)($product['image'] ?? '');
                                            $imgArr = json_decode($imgVal, true);
                                            $thumb = '';
                                            if (is_array($imgArr) && !empty($imgArr)) {
                                                $thumb = $imgArr[0];
                                            } else {
                                                $thumb = $imgVal;
                                            }
                                        ?>
                                        <img src="../assets/img/<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image-thumb">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/40" alt="No Image" class="product-image-thumb">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['discount']; ?>%</td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><span class="status-badge status-<?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-secondary btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $product['status']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-toggle-status">
                                            <?php echo $product['status'] == 'active' ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="addProductModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeAddProduct">&times;</button>
            <div class="modal-header">Add Product</div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="addProductName">Product Name</label>
                        <input type="text" name="name" id="addProductName" placeholder="e.g. Wireless Earbuds" required>
                    </div>
                    <div class="form-group">
                        <label for="addProductCategory">Category</label>
                        <select name="category_id" id="addProductCategory" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addProductPrice">Price (₹)</label>
                        <input type="number" name="price" id="addProductPrice" min="0" step="0.01" placeholder="e.g. 2499" required>
                    </div>
                    <div class="form-group">
                        <label for="addProductDiscount">Discount (%)</label>
                        <input type="number" name="discount" id="addProductDiscount" min="0" max="100" step="1" placeholder="e.g. 10" value="0">
                    </div>
                    <div class="form-group">
                        <label for="addProductStock">Stock</label>
                        <input type="number" name="stock" id="addProductStock" min="0" step="1" placeholder="e.g. 100" required>
                    </div>
                    <div class="form-group">
                        <label for="addProductImages">Product Image</label>
                        <input type="file" name="image[]" id="addProductImages" accept="image/*" multiple>
                    </div>
                    <div class="form-group">
                        <label for="addProductDescription">Description</label>
                        <textarea name="description" id="addProductDescription" placeholder="Product description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="addProductStatus">Status</label>
                        <select name="status" id="addProductStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="cancelAddProduct" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editProductModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeEditProduct">&times;</button>
            <div class="modal-header">Edit Product</div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="editProductForm">
                    <input type="hidden" name="id" id="editProductId">
                    <div class="form-group">
                        <label for="editProductName">Product Name</label>
                        <input type="text" name="name" id="editProductName" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductCategory">Category</label>
                        <select name="category_id" id="editProductCategory" required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editProductPrice">Price (₹)</label>
                        <input type="number" name="price" id="editProductPrice" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductDiscount">Discount (%)</label>
                        <input type="number" name="discount" id="editProductDiscount" min="0" max="100" step="1">
                    </div>
                    <div class="form-group">
                        <label for="editProductStock">Stock</label>
                        <input type="number" name="stock" id="editProductStock" min="0" step="1" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductImages">Product Image</label>
                        <input type="file" name="image[]" id="editProductImages" accept="image/*" multiple>
                    </div>
                    <div class="form-group">
                        <label for="editProductDescription">Description</label>
                        <textarea name="description" id="editProductDescription"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editProductStatus">Status</label>
                        <select name="status" id="editProductStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>    
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="cancelEditProduct" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="edit_product" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteProductModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeDeleteProduct">&times;</button>
            <div class="modal-header">Delete Product</div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="id" id="deleteProductId">
                    <p id="deleteProductText">Are you sure you want to delete this product?</p>
                    <div class="modal-actions">
                        <button type="button" id="cancelDeleteProduct" class="btn btn-secondary">Cancel</button>
                        <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                    </div>
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

                links.forEach(function (link) {
                    const href = link.getAttribute('href');
                    if (!href) return;
                    const hrefPage = href.split('/').pop();
                    if (hrefPage === currentPage) {
                        link.classList.add('active');
                    }
                });
            }

            // JavaScript functions for modal management
            window.editProduct = function (productId) {
                const products = <?php echo json_encode($products); ?>;
                const product = products.find(p => p.id == productId);
                
                if (product) {
                    document.getElementById('editProductId').value = product.id;
                    document.getElementById('editProductName').value = product.name;
                    document.getElementById('editProductCategory').value = product.category_id;
                    document.getElementById('editProductPrice').value = product.price;
                    document.getElementById('editProductDiscount').value = product.discount;
                    document.getElementById('editProductStock').value = product.stock;
                    document.getElementById('editProductDescription').value = product.description;
                    document.getElementById('editProductStatus').value = product.status;
                    document.getElementById('editProductModalBackdrop').classList.remove('hidden');
                }
            };

            window.deleteProduct = function (productId) {
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteProductModalBackdrop').classList.remove('hidden');
            };

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
            }

            // Modal close handlers
            document.getElementById('closeAddProduct').addEventListener('click', () => closeModal('addProductModalBackdrop'));
            document.getElementById('cancelAddProduct').addEventListener('click', () => closeModal('addProductModalBackdrop'));
            document.getElementById('closeEditProduct').addEventListener('click', () => closeModal('editProductModalBackdrop'));
            document.getElementById('cancelEditProduct').addEventListener('click', () => closeModal('editProductModalBackdrop'));
            document.getElementById('closeDeleteProduct').addEventListener('click', () => closeModal('deleteProductModalBackdrop'));
            document.getElementById('cancelDeleteProduct').addEventListener('click', () => closeModal('deleteProductModalBackdrop'));

            // Real-time product search
            const searchInput = document.getElementById('productSearchInput');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            const productTableBody = document.getElementById('productTableBody');
            const productCountBadge = document.querySelector('.product-count-badge');
            const allRows = Array.from(productTableBody.querySelectorAll('tr'));

            function filterProducts(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                let visibleCount = 0;

                allRows.forEach(row => {
                    const productName = row.querySelector('td:nth-child(2)')?.textContent?.toLowerCase() || '';
                    const categoryName = row.querySelector('td:nth-child(3)')?.textContent?.toLowerCase() || '';
                    
                    if (productName.includes(term) || categoryName.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update count badge
                if (productCountBadge) {
                    productCountBadge.textContent = visibleCount + ' items';
                }

                // Show/hide clear button
                if (term.length > 0) {
                    clearSearchBtn.classList.add('visible');
                } else {
                    clearSearchBtn.classList.remove('visible');
                }

                // Show no results message if needed
                let noResultsRow = productTableBody.querySelector('.no-results-row');
                if (visibleCount === 0 && term.length > 0) {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = '<td colspan="8" class="no-results-message"><i class="fa-solid fa-search" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>No products found matching "' + escapeHtml(term) + '"</td>';
                        productTableBody.appendChild(noResultsRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterProducts(this.value);
                });

                // Also handle paste events
                searchInput.addEventListener('paste', function(e) {
                    setTimeout(() => filterProducts(this.value), 0);
                });
            }

            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    filterProducts('');
                    searchInput.focus();
                });
            }

            // Open add product modal
            document.getElementById('openAddProduct').addEventListener('click', () => {
                document.getElementById('addProductModalBackdrop').classList.remove('hidden');
            });

            // Close modals when clicking backdrop
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                backdrop.addEventListener('click', (e) => {
                    if (e.target === backdrop) {
                        backdrop.classList.add('hidden');
                    }
                });
            });
        })();
    </script>
</body>
</html>
