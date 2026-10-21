<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'e_cart');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status'])) {
    $review_id = intval($_POST['review_id']);
    $status = $_POST['status'];
    $updateStmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $status, $review_id);
    $updateStmt->execute();
    $updateStmt->close();
    header('Location: reviews.php');
    exit();
}

// Detect products table columns
$productCols = [];
$pColRes = $conn->query("SHOW COLUMNS FROM products");
if ($pColRes) { while ($col = $pColRes->fetch_assoc()) $productCols[] = $col['Field']; }

// Detect users table columns
$userCols = [];
$uColRes = $conn->query("SHOW COLUMNS FROM users");
if ($uColRes) { while ($col = $uColRes->fetch_assoc()) $userCols[] = $col['Field']; }

// Resolve product name column
$prodNameCol = 'name';
foreach (['name', 'product_name', 'title'] as $c) {
    if (in_array($c, $productCols)) { $prodNameCol = $c; break; }
}
// Resolve product image column
$prodImgCol = 'image';
foreach (['image', 'img', 'photo', 'thumbnail', 'product_image'] as $c) {
    if (in_array($c, $productCols)) { $prodImgCol = $c; break; }
}
// Fix: users table has full_name — prioritize it correctly
$userNameCol = 'full_name';
foreach (['full_name', 'name', 'username', 'user_name', 'display_name'] as $c) {
    if (in_array($c, $userCols)) { $userNameCol = $c; break; }
}

// Check what product_id column exists in reviews
$reviewCols = [];
$rColRes = $conn->query("SHOW COLUMNS FROM reviews");
if ($rColRes) { while ($col = $rColRes->fetch_assoc()) $reviewCols[] = $col['Field']; }

// Find the actual product foreign key column in reviews table
$reviewProdCol = 'product_id';
foreach (['product_id', 'prod_id', 'productid', 'item_id'] as $c) {
    if (in_array($c, $reviewCols)) { $reviewProdCol = $c; break; }
}

// Find actual primary key of products table
$prodIdCol = 'id';
foreach (['id', 'product_id', 'prod_id'] as $c) {
    if (in_array($c, $productCols)) { $prodIdCol = $c; break; }
}

// Simple direct query
$reviewsResult = $conn->query("
    SELECT r.*,
           p.$prodNameCol  AS product_name,
           p.$prodImgCol   AS product_image,
           u.$userNameCol  AS reviewer_name
    FROM reviews r
    LEFT JOIN products p ON p.$prodIdCol = r.$reviewProdCol
    LEFT JOIN users    u ON u.id = r.user_id
    ORDER BY r.created_at DESC
");

// Stats
$stats = $conn->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
    AVG(rating) as avg_rating FROM reviews")->fetch_assoc();

$filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build reviews array
$reviews = [];
if ($reviewsResult) {
    while ($r = $reviewsResult->fetch_assoc()) {
        $prod_name   = $r['product_name'] ?? '';
        $prod_img    = $r['product_image'] ?? '';
        $prod_id     = $r['product_id'] ?? 0;
        $displayUser = !empty($r['reviewer_name']) ? strtoupper($r['reviewer_name']) : 'Anonymous';

        // Fallback: fetch product directly if join returned nothing
        if (empty($prod_name) && !empty($prod_id)) {
            $prodStmt = $conn->prepare("SELECT $prodNameCol as pname, $prodImgCol as pimg FROM products WHERE id = ?");
            $prodStmt->bind_param("i", $prod_id);
            $prodStmt->execute();
            $prodRes = $prodStmt->get_result();
            if ($prodRow = $prodRes->fetch_assoc()) {
                $prod_name = $prodRow['pname'] ?? '';
                $prod_img  = $prodRow['pimg'] ?? '';
            }
            $prodStmt->close();
        }

        $r['prod_name']    = !empty($prod_name) ? $prod_name : 'Product #' . $prod_id;
        $r['prod_img']     = $prod_img;
        $r['display_user'] = $displayUser;

        // Apply filters
        if ($filter !== 'all' && $r['status'] !== $filter) continue;
        if ($search) {
            $haystack = strtolower(($r['review_text'] ?? '') . $r['prod_name'] . $displayUser);
            if (!str_contains($haystack, strtolower($search))) continue;
        }

        $reviews[] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Admin</title>
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
        
        .stat-box h4 {
            font-size: 28px;
            margin: 0;
        }
        
        .stat-box span {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .stat-box:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-box:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-box:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .filter-bar {
            margin-bottom: 25px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
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

        .filter-bar select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .reviews-grid {
            display: grid;
            gap: 20px;
        }
        
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 20px;
            align-items: start;
        }
        
        .review-img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .review-img-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        .review-content h4 {
            margin: 0 0 5px 0;
            color: #333;
            font-size: 16px;
        }
        
        .review-content .meta {
            color: #888;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .review-content .stars {
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .review-content .text {
            color: #555;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .review-images {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        .review-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .review-actions {
            text-align: right;
        }
        
        .status-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        
        .btn-small {
            padding: 6px 14px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 5px;
        }
        
        .btn-approve { background: #28a745; color: white; }
        .btn-pending { background: #ffc107; color: #333; }
        
        @media (max-width: 768px) {
            .review-card {
                grid-template-columns: 1fr;
            }
            .review-actions {
                text-align: left;
            }
            .stats-bar {
                flex-wrap: wrap;
            }
            .stat-box {
                flex: 1 1 45%;
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
                <li><a href="reviews.php" class="active"><i class="fa-solid fa-star" style="margin-right: 8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Customer Reviews</h1>
                    <p style="color: #888; margin: 5px 0 0 0;">Manage product reviews and ratings</p>
                </div>
                <span style="background: #e0e7ff; color: #3730a3; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                    <?php echo count($reviews); ?> reviews
                </span>
            </div>

            
            <!-- Stats -->
            <div class="stats-bar">
                <div class="stat-box">
                    <h4><?php echo round($stats['avg_rating'], 1); ?></h4>
                    <span>Average Rating</span>
                </div>
                <div class="stat-box">
                    <h4><?php echo $stats['total']; ?></h4>
                    <span>Total Reviews</span>
                </div>
                <div class="stat-box">
                    <h4><?php echo $stats['pending']; ?></h4>
                    <span>Pending</span>
                </div>
                <div class="stat-box">
                    <h4><?php echo $stats['approved']; ?></h4>
                    <span>Approved</span>
                </div>
            </div>
            
            <!-- Filter -->
            <div class="filter-bar">
                <div class="search-box">
                    <i class="fa-solid fa-search" style="color: #9ca3af; font-size: 16px;"></i>
                    <input type="text" id="reviewSearchInput" placeholder="Search by product, user, review..." autocomplete="off" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="button" id="clearReviewSearch" class="btn-clear <?php echo $search ? '' : 'hidden'; ?>" title="Clear search"><i class="fa-solid fa-times"></i></button>
                </div>
                <form method="GET" style="display:flex; align-items:center; gap:10px; margin:0;">
                    <?php if ($search): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter=='all'?'selected':''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter=='pending'?'selected':''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter=='approved'?'selected':''; ?>>Approved</option>
                    </select>
                </form>
            </div>
            
            <!-- Reviews Grid -->
            <div class="reviews-grid" id="reviewsGrid">
                <?php if (empty($reviews)): ?>
                    <div style="text-align: center; padding: 60px; color: #888;">
                        <i class="fas fa-comment-slash" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                        <p>No reviews found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-card"
                            data-product="<?php echo htmlspecialchars(strtolower($r['prod_name'] ?? '')); ?>"
                            data-user="<?php echo htmlspecialchars(strtolower($r['display_user'] ?? '')); ?>"
                            data-text="<?php echo htmlspecialchars(strtolower($r['review_text'] ?? '')); ?>">
                            <div>
                                <?php if (!empty($r['prod_img']) && file_exists('../'.$r['prod_img'])): ?>
                                    <img src="../<?php echo htmlspecialchars($r['prod_img']); ?>" class="review-img" alt="">
                                <?php else: ?>
                                    <div class="review-img-placeholder"><i class="fas fa-box"></i></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-content">
                                <h4><?php echo htmlspecialchars($r['prod_name']); ?></h4>
                                <div class="meta">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($r['display_user']); ?> 
                                    • <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                </div>
                                <div class="stars">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo $i<=$r['rating']?'#ffc107':'#ddd'; ?>"></i>
                                    <?php endfor; ?>
                                    <span style="color: #888; margin-left: 5px;">(<?php echo $r['rating']; ?>/5)</span>
                                </div>
                                <div class="text"><?php echo htmlspecialchars($r['review_text']); ?></div>
                                
                                <?php if (!empty($r['review_images'])): ?>
                                    <div class="review-images">
                                        <?php foreach (json_decode($r['review_images'], true) ?? [] as $img): 
                                            if (file_exists('../uploads/reviews/'.$img)): ?>
                                            <img src="../uploads/reviews/<?php echo $img; ?>" onclick="window.open(this.src)">
                                        <?php endif; endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-actions">
                                <div class="status-pill status-<?php echo $r['status']; ?>">
                                    <?php echo ucfirst($r['status']); ?>
                                </div>
                                <div>
                                    <?php if ($r['status'] != 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn-small btn-approve">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($r['status'] != 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="btn-small btn-pending">Pending</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
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

        // Real-time review search (like orders.php)
        (function() {
            var reviewSearchInput = document.getElementById('reviewSearchInput');
            var clearReviewSearchBtn = document.getElementById('clearReviewSearch');
            var reviewsGrid = document.getElementById('reviewsGrid');
            var reviewCountBadge = document.querySelector('.page-header span');
            var allReviewCards = reviewsGrid ? Array.from(reviewsGrid.querySelectorAll('.review-card')) : [];

            function filterReviews(searchTerm) {
                var term = searchTerm.toLowerCase().trim();
                var visibleCount = 0;

                allReviewCards.forEach(function(card) {
                    var product = card.getAttribute('data-product') || '';
                    var user = card.getAttribute('data-user') || '';
                    var text = card.getAttribute('data-text') || '';

                    if (!term || product.includes(term) || user.includes(term) || text.includes(term)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (reviewCountBadge) {
                    reviewCountBadge.textContent = visibleCount + ' reviews';
                }

                if (clearReviewSearchBtn) {
                    if (term.length > 0) {
                        clearReviewSearchBtn.classList.remove('hidden');
                    } else {
                        clearReviewSearchBtn.classList.add('hidden');
                    }
                }

                var noResultsMsg = reviewsGrid ? reviewsGrid.querySelector('.no-results-msg') : null;
                if (visibleCount === 0 && term.length > 0) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.className = 'no-results-msg';
                        noResultsMsg.style.cssText = 'text-align:center;padding:60px;color:#888;';
                        noResultsMsg.innerHTML = '<i class="fas fa-search" style="font-size:48px;margin-bottom:15px;color:#ddd;display:block;"></i><p>No reviews found matching "' + escapeHtml(term) + '"</p>';
                        reviewsGrid.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (reviewSearchInput) {
                reviewSearchInput.addEventListener('input', function() {
                    filterReviews(this.value);
                });
                reviewSearchInput.addEventListener('paste', function(e) {
                    setTimeout(function() { filterReviews(reviewSearchInput.value); }, 0);
                });
                if (reviewSearchInput.value) {
                    filterReviews(reviewSearchInput.value);
                }
            }

            if (clearReviewSearchBtn) {
                clearReviewSearchBtn.addEventListener('click', function() {
                    reviewSearchInput.value = '';
                    filterReviews('');
                    reviewSearchInput.focus();
                });
            }
        })();
    </script>
</body>
</html>