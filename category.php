<?php
session_start();
include 'Database_file/config.php';

// Get category ID and name from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_name = isset($_GET['name']) ? urldecode($_GET['name']) : '';

// Get category details
$category = null;
$products = array();

$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$free_delivery = isset($_GET['free_delivery']) ? (int)$_GET['free_delivery'] : 0;
$new_arrival = isset($_GET['new_arrival']) ? (int)$_GET['new_arrival'] : 0;
$trending = isset($_GET['trending']) ? (int)$_GET['trending'] : 0;

$minCategoryPrice = 0;
$maxCategoryPrice = 0;

if ($category_id > 0) {
    // Get category info
    $cat_query = $conn->prepare("SELECT id, name, description, image FROM categories WHERE id = ? AND is_active = 1");
    $cat_query->bind_param("i", $category_id);
    $cat_query->execute();
    $cat_result = $cat_query->get_result();
    $category = $cat_result->fetch_assoc();
    
    // Price bounds for filters
    $bounds_stmt = $conn->prepare("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM products WHERE category_id = ? AND status = 'active'");
    $bounds_stmt->bind_param("i", $category_id);
    $bounds_stmt->execute();
    $bounds_res = $bounds_stmt->get_result();
    $bounds = $bounds_res ? $bounds_res->fetch_assoc() : null;
    $minCategoryPrice = isset($bounds['min_price']) ? (float)$bounds['min_price'] : 0;
    $maxCategoryPrice = isset($bounds['max_price']) ? (float)$bounds['max_price'] : 0;

    if ($min_price === null) {
        $min_price = $minCategoryPrice;
    }
    if ($max_price === null || $max_price == 0) {
        $max_price = $maxCategoryPrice;
    }

    // Build products query with filters
    $where = "WHERE category_id = ? AND status = 'active'";
    $types = "i";
    $params = [$category_id];

    if ($min_price !== null && $min_price > 0) {
        $where .= " AND price >= ?";
        $types .= "d";
        $params[] = (float)$min_price;
    }
    if ($max_price !== null && $max_price > 0) {
        $where .= " AND price <= ?";
        $types .= "d";
        $params[] = (float)$max_price;
    }

    // Simple quick flags (no DB columns) -> treated as UI-only for now
    // free_delivery, new_arrival, trending are kept to preserve URL state

    $orderBy = "ORDER BY id DESC";
    if ($sort === 'price_asc') {
        $orderBy = "ORDER BY price ASC";
    } elseif ($sort === 'price_desc') {
        $orderBy = "ORDER BY price DESC";
    } elseif ($sort === 'name_asc') {
        $orderBy = "ORDER BY name ASC";
    } elseif ($sort === 'name_desc') {
        $orderBy = "ORDER BY name DESC";
    }

    $sql = "SELECT id, name, price, image, description FROM products $where $orderBy";
    $prod_query = $conn->prepare($sql);
    $prod_query->bind_param($types, ...$params);
    $prod_query->execute();
    $prod_result = $prod_query->get_result();
    while ($product = $prod_result->fetch_assoc()) {
        $imgVal = (string)($product['image'] ?? '');
        $imgArr = json_decode($imgVal, true);
        if (is_array($imgArr) && !empty($imgArr)) {
            $product['image'] = $imgArr[0];
        }
        $products[] = $product;
    }
}

// If no category found, redirect to home
if (!$category) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - E_Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="floating_home.css">
    <style>
        body {
            background: #f6f7fb;
        }
        .category-header {
            background: linear-gradient(135deg, #ffffff 0%, #f5f8ff 40%, #ffffff 100%);
            color: #111827;
            padding: 18px 0;
            border-bottom: 1px solid #e9eef7;
        }
        .category-header .container {
            max-width: 1100px;
            padding: 0 20px;
            margin: 0 auto;
        }
        .category-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 2px 0;
        }
        .category-hero-left {
            min-width: 0;
        }
        .category-hero-right {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            border: 1px solid #e9eef7;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.08);
        }
        .category-hero-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .category-layout {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 20px 24px;
            display: grid;
            grid-template-columns: 270px 1fr;
            gap: 14px;
            align-items: start;
        }
        .filter-panel {
            background: #ffffff;
            border: 1px solid #e9eef7;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            position: sticky;
            top: 14px;
        }
        .filter-title {
            font-size: 13px;
            font-weight: 900;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .filter-section {
            padding-top: 12px;
            margin-top: 12px;
            border-top: 1px solid #f0f3fa;
        }
        .filter-section:first-of-type {
            padding-top: 0;
            margin-top: 0;
            border-top: 0;
        }
        .filter-section h3 {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 900;
            color: #111827;
            letter-spacing: 0.2px;
        }
        .filter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #374151;
        }
        .filter-row input[type="range"] {
            width: 100%;
        }
        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 12px;
            border: 1px solid #e9eef7;
            background: #fbfcff;
            font-size: 12px;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }
        .filter-chip input {
            margin: 0;
        }
        .content-panel {
            min-width: 0;
        }
        .crumbs {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .crumbs a {
            color: #2874f0;
            text-decoration: none;
            font-weight: 600;
        }
        .crumbs a:hover {
            text-decoration: underline;
        }
        .category-header h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .category-header p {
            font-size: 13px;
            margin: 6px 0 0;
            color: #6b7280;
            max-width: 760px;
        }
        .category-meta {
            margin-top: 6px;
            font-size: 12px;
            color: #9ca3af;
        }
        .category-products {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0;
        }
        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin: 14px 0 12px;
        }
        .section-toolbar {
            background: #ffffff;
            border: 1px solid #e9eef7;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            margin: 14px 0 14px;
        }
        .section-toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .section-toolbar h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: #111827;
        }
        .section-pill {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            background: #f3f6ff;
            border: 1px solid #e2e9ff;
            padding: 6px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .section-head h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
        }
        .section-head .count {
            font-size: 12px;
            color: #6b7280;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
        }
        .product-card-wrap {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #edf0f5;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        .product-card-wrap:hover {
            transform: translateY(-2px);
            border-color: #d6deea;
            box-shadow: 0 10px 26px rgba(16, 24, 40, 0.12);
        }
        .product-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .product-link:visited {
            color: inherit;
        }
        .product-image {
            position: relative;
            height: 190px;
            overflow: hidden;
            background: radial-gradient(circle at 30% 30%, #ffffff 0%, #f6f8ff 55%, #eef2f8 100%);
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }
        .product-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.2px;
            background: rgba(17, 24, 39, 0.82);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(17,24,39,0.0) 0%, rgba(17,24,39,0.65) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        .product-icons {
            display: flex;
            gap: 12px;
        }
        .product-icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        .product-icon-btn:hover {
            background: #2874f0;
            color: white;
            transform: scale(1.1);
        }
        .product-info {
            padding: 12px 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .product-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #111827;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .price-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
        }
        .product-price {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }
        .price-sub {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
        }
        .card-cta {
            padding: 10px 12px 12px;
            border-top: 1px solid #f1f3f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .view-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 800;
            color: #2874f0;
            background: rgba(40, 116, 240, 0.10);
            cursor: pointer;
            user-select: none;
        }
        .view-btn:hover {
            background: rgba(40, 116, 240, 0.14);
        }
        .view-btn i {
            font-size: 12px;
        }
        .mini-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mini-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid #e7edf7;
            background: #ffffff;
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }
        .mini-btn:hover {
            background: #f5f8ff;
            border-color: #d7e4ff;
            transform: translateY(-1px);
        }
        @media (max-width: 640px) {
            .category-header {
                padding: 14px 0;
            }
            .category-header h1 {
                font-size: 18px;
            }
            .category-hero-right {
                width: 54px;
                height: 54px;
                border-radius: 14px;
            }
            .section-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        @media (max-width: 960px) {
            .category-layout {
                grid-template-columns: 1fr;
            }
            .filter-panel {
                position: relative;
                top: auto;
            }
        }
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #878787;
        }
        .no-products i {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2874f0;
            text-decoration: none;
            margin-bottom: 0;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'Header.php'; ?>

    <main>
        <div class="category-header">
            <div class="container">
                <div class="crumbs">
                    <a href="index.php">Home</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($category['name']); ?></span>
                </div>
                <div class="category-hero">
                    <div class="category-hero-left">
                        <h1><?php echo htmlspecialchars($category['name']); ?></h1>
                        <p><?php echo htmlspecialchars($category['description']); ?></p>
                        <div class="category-meta">Curated picks in this category</div>
                    </div>
                    <div class="category-hero-right">
                        <?php if (!empty($category['image'])): ?>
                            <img src="assets/img/<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-tags" style="color:#2874f0;"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="category-layout">
            <aside class="filter-panel">
                <div class="filter-title">
                    <span>Filters</span>
                    <span style="font-size:12px; color:#6b7280;"><?php echo count($products); ?> items</span>
                </div>
                <form method="GET" action="category.php">
                    <input type="hidden" name="id" value="<?php echo (int)$category_id; ?>">
                    <input type="hidden" name="name" value="<?php echo urlencode($category['name']); ?>">

                    <div class="filter-section">
                        <h3>Quick</h3>
                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                            <label class="filter-chip"><input type="checkbox" name="free_delivery" value="1" <?php echo $free_delivery ? 'checked' : ''; ?>> Free Delivery</label>
                            <label class="filter-chip"><input type="checkbox" name="new_arrival" value="1" <?php echo $new_arrival ? 'checked' : ''; ?>> New Arrival</label>
                            <label class="filter-chip"><input type="checkbox" name="trending" value="1" <?php echo $trending ? 'checked' : ''; ?>> Trending</label>
                        </div>
                        <div style="margin-top:12px;">
                            <a href="index.php" class="back-link">
                                <i class="fa-solid fa-arrow-left"></i>
                                Back to Home
                            </a>
                        </div>
                    </div>

                    <div class="filter-section">
                        <h3>Price</h3>
                        <div class="filter-row">
                            <span>Min</span>
                            <span>Max</span>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <input type="number" name="min_price" min="0" value="<?php echo htmlspecialchars((string)$min_price); ?>" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid #e9eef7;">
                            <input type="number" name="max_price" min="0" value="<?php echo htmlspecialchars((string)$max_price); ?>" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid #e9eef7;">
                        </div>
                        <div style="margin-top:10px; font-size:12px; color:#6b7280;">Range: ₹<?php echo number_format($minCategoryPrice); ?> - ₹<?php echo number_format($maxCategoryPrice); ?></div>
                    </div>

                    <div class="filter-section">
                        <h3>Sort</h3>
                        <div style="display:grid; gap:10px;">
                            <label class="filter-chip"><input type="radio" name="sort" value="newest" <?php echo $sort === 'newest' ? 'checked' : ''; ?>> Newest First</label>
                            <label class="filter-chip"><input type="radio" name="sort" value="price_asc" <?php echo $sort === 'price_asc' ? 'checked' : ''; ?>> Price: Low to High</label>
                            <label class="filter-chip"><input type="radio" name="sort" value="price_desc" <?php echo $sort === 'price_desc' ? 'checked' : ''; ?>> Price: High to Low</label>
                            <label class="filter-chip"><input type="radio" name="sort" value="name_asc" <?php echo $sort === 'name_asc' ? 'checked' : ''; ?>> Name: A to Z</label>
                            <label class="filter-chip"><input type="radio" name="sort" value="name_desc" <?php echo $sort === 'name_desc' ? 'checked' : ''; ?>> Name: Z to A</label>
                        </div>
                    </div>

                    <div class="filter-section" style="display:flex; gap:10px;">
                        <button type="submit" class="btn" style="background:#2874f0; color:#fff; border:none; padding:10px 12px; border-radius:12px; font-weight:800; cursor:pointer; flex:1;">Apply</button>
                        <a class="btn" href="category.php?id=<?php echo (int)$category_id; ?>&name=<?php echo urlencode($category['name']); ?>" style="background:#f3f6ff; color:#2874f0; border:1px solid #e2e9ff; padding:10px 12px; border-radius:12px; font-weight:800; text-align:center; text-decoration:none; flex:1;">Clear</a>
                    </div>
                </form>
            </aside>

            <div class="content-panel">
                <div class="category-products">
                    <div class="section-toolbar">
                        <div class="section-toolbar-left">
                            <h2>All products</h2>
                            <span class="section-pill"><?php echo htmlspecialchars($category['name']); ?></span>
                        </div>
                        <div class="count"><?php echo count($products); ?> item(s)</div>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="product-card-wrap">
                                    <a href="Product_Pages/product.php?id=<?php echo $product['id']; ?>" class="product-link">
                                        <div class="product-image">
                                            <div class="product-badge"><i class="fa-solid fa-bolt"></i> Trending</div>
                                            <img src="assets/img/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <div class="product-overlay">
                                                <div class="product-icons">
                                                    <button class="product-icon-btn" type="button" title="Quick view" onclick="event.preventDefault(); window.location.href='Product_Pages/product.php?id=<?php echo $product['id']; ?>';">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="price-row">
                                                <div class="product-price">₹<?php echo number_format($product['price']); ?></div>
                                                <div class="price-sub">Free delivery</div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="card-cta">
                                        <div class="view-btn" onclick="window.location.href='Product_Pages/product.php?id=<?php echo $product['id']; ?>';">
                                            View
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </div>
                                        <div class="mini-actions" onclick="event.stopPropagation();">
                                            <form method="POST" action="" style="display:inline; margin:0;">
                                                <input type="hidden" name="add_to_cart" value="1">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                                <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="mini-btn" title="Add to Cart">
                                                    <i class="fa-solid fa-cart-shopping"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="Account_Profile/Wishlist/toggle_wishlist_simple.php" style="display:inline; margin:0;">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                                <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                                <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image']); ?>">
                                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                <button type="submit" class="mini-btn" title="Add to Wishlist">
                                                    <i class="fa-regular fa-heart"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-products">
                            <i class="fa-solid fa-box-open"></i>
                            <h3>No products found in this category</h3>
                            <p>Check back later or browse other categories</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'Footer.php'; ?>

    <?php
    // Handle add to cart
    if (isset($_POST['add_to_cart']) && $_POST['add_to_cart'] == 1) {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit();
        }
        
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $product_price = $_POST['product_price'];
        $product_image = $_POST['product_image'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = array(
                'id' => $product_id,
                'name' => $product_name,
                'price' => $product_price,
                'image' => $product_image,
                'quantity' => $quantity
            );
        }
    }
    ?>

    <script>
        // Wishlist functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle wishlist button clicks
            document.addEventListener('click', function(e) {
                const wishlistBtn = e.target.closest('.wishlist');
                if (!wishlistBtn) return;

                e.stopPropagation();
                
                const productId = wishlistBtn.dataset.productId;
                const productName = wishlistBtn.dataset.productName;
                const productPrice = wishlistBtn.dataset.productPrice;
                const productImage = wishlistBtn.dataset.productImage;

                if (!productId) {
                    console.error('Product ID missing');
                    return;
                }

                // Send AJAX request to toggle wishlist
                fetch('/E_Cart/Account_Profile/Wishlist/toggle_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        product_id: productId,
                        product_name: productName,
                        product_price: productPrice,
                        product_image: productImage
                    })
                })
                .then(response => {
                    if (response.status === 401) {
                        // Redirect to login if unauthorized
                        window.location.href = '/E_Cart/login.php';
                        return;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // Toggle the visual state of the heart icon
                        const icon = wishlistBtn.querySelector('i');
                        if (data.action === 'added') {
                            icon.classList.remove('fa-regular');
                            icon.classList.add('fa-solid');
                            icon.style.color = '#ff6161';
                            // Show notification
                            showNotification('Product added to wishlist!', 'success');
                        } else if (data.action === 'removed') {
                            icon.classList.remove('fa-solid');
                            icon.classList.add('fa-regular');
                            icon.style.color = '';
                            // Show notification
                            showNotification('Product removed from wishlist!', 'info');
                        }
                    } else {
                        console.error('Error:', data ? data.message : 'Unknown error');
                        showNotification('Error updating wishlist', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error updating wishlist', 'error');
                });
            });
        });

        // Simple notification function
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                border-radius: 5px;
                z-index: 10000;
                font-family: Arial, sans-serif;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
    </script>
</body>
</html>
