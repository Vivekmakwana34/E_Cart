 <!-- NAVBAR Section -->
    <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="floating_home.css">
    <style>
        .search-box { position: relative; }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .search-results.active { display: block; }
        .search-section { padding: 10px 15px; border-bottom: 1px solid #eee; }
        .search-section-title {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .search-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            cursor: pointer;
            text-decoration: none;
            color: #333;
        }
        .search-item:hover { background: #f5f5f5; }
        .search-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 12px;
        }
        .search-item-info { flex: 1; }
        .search-item-name { font-size: 14px; font-weight: 500; }
        .search-item-price { font-size: 13px; color: #388e3c; }
        .search-item-category {
            font-size: 12px;
            color: #666;
            padding: 6px 0;
        }
        .search-empty {
            padding: 20px;
            text-align: center;
            color: #666;
        }
    </style>
    </head>
    <header>
        <div class="navbar-container">
            <div class="logo-section">
                <div class="logo">
                    <div class="logo1">E_<span>Cart</span></div>
                </div>
            </div>

            <div class="search-box">
                <button class="search-btn" onclick="performSearch()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <input type="text" id="searchInput" placeholder="Search for Products, Brands and More" onkeyup="searchProducts(this.value)">
                <div id="searchResults" class="search-results"></div>
            </div>

            <nav class="nav-links" id="navLinks">
                <div class="nav-item user-menu">
                    <?php
                    // Determine correct login path based on current file location
                    $login_path = 'login.php'; // Default for root level
                    if (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false) {
                        $login_path = '../login.php';
                    }
                    
                    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                        // User is logged in - show user name and menu
                        echo '<a href="#" class="user-menu-trigger">';
                        echo '<i class="fa-regular fa-circle-user"></i>';
                        echo '<span>' . htmlspecialchars($_SESSION['full_name']) . '</span>'; 
                        echo '<i class="fa-solid fa-angle-down arrow-icon"></i>';
                        echo '</a>';
                    } else {
                        // User is not logged in - show login
                        echo '<a href="' . $login_path . '" class="user-menu-trigger">';
                        echo '<i class="fa-regular fa-circle-user"></i>';
                        echo '<span>Login</span>'; 
                        echo '<i class="fa-solid fa-angle-down arrow-icon"></i>';
                        echo '</a>';
                    }
                    ?>
                    <div class="user-dropdown" style="position: absolute; top: 100%; left: 0;">
                        <?php
                        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                            // Logged in user menu
                            $is_in_profile = strpos($_SERVER['PHP_SELF'], '/Account_Profile/Profile/profile.php') !== false;
                            
                            echo '<a href="' . ($is_in_profile ? 'profile.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../Account_Profile/Profile/profile.php' : 'Account_Profile/Profile/profile.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-regular fa-user"></i> My Profile';
                            echo '</a>';
                            echo '<a href="' . ($is_in_profile ? '../Wishlist/wishlist.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../Account_Profile/Wishlist/wishlist.php' : 'Account_Profile/Wishlist/wishlist.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-regular fa-heart"></i> Wishlist';
                            echo '</a>';
                            echo '<a href="' . ($is_in_profile ? '../Order/order.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../Account_Profile/Order/order.php' : 'Account_Profile/Order/order.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-solid fa-bag-shopping"></i> Orders';
                            echo '</a>';
                            echo '<a href="' . ($is_in_profile ? '../Coupons/coupon.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../Account_Profile/Coupons/coupon.php' : 'Account_Profile/Coupons/coupon.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-solid fa-tags"></i> Coupons';
                            echo '</a>';
                            echo '<a href="' . ($is_in_profile ? '../Gift_Cards/gift.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../Account_Profile/Gift_Cards/gift.php' : 'Account_Profile/Gift_Cards/gift.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-solid fa-gift"></i> Gift Cards';
                            echo '</a>';
                            echo '<a href="' . ($is_in_profile ? '../logout.php' : (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../logout.php' : 'logout.php')) . '" class="user-dropdown-item">';
                            echo '<i class="fa-solid fa-right-from-bracket"></i> Logout';
                            echo '</a>';
                        } else {
                            // Not logged in - show login option
                            $login_path = (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../login.php' : 'login.php');
                            echo '<a href="' . $login_path . '" class="user-dropdown-item">';
                            echo '<i class="fa-solid fa-right-to-bracket"></i> Login';
                            echo '</a>';
                        }
                        ?>
                    </div>
                </div>

                <?php
// Calculate total cart items
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../cart.php' : 'cart.php'); ?>" class="nav-item">
                    <div class="cart-icon-container">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    </div>
                    <span>Cart</span>
                </a>

                <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../become_seller.php' : 'become_seller.php'); ?>" class="nav-item">
                    <i class="fa-solid fa-store"></i>
                    <span>Become a Seller</span>
                </a>

                  <div class="nav-item options-menu">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                    <div class="options-dropdown">
                        <a href="customer_care.php" class="options-dropdown-item">
                            <i class="fa-solid fa-headset"></i> 24x7 Customer Care
                        </a>
                        <a href="help.php" class="options-dropdown-item">
                            <i class="fa-regular fa-circle-question"></i> Help
                        </a>
                        <a href="#" class="options-dropdown-item">
                            <i class="fa-solid fa-download"></i> Download App
                        </a>
                    </div>
                </div>
            </nav>

            <div class="hamburger" onclick="toggleMenu()">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </header>
    
    <!-- Search JavaScript -->
    <script>
        let searchTimeout;
        
        function searchProducts(query) {
            clearTimeout(searchTimeout);
            const resultsDiv = document.getElementById('searchResults');
            
            if (query.length < 2) {
                resultsDiv.classList.remove('active');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                // Determine base path
                const basePath = window.location.pathname.includes('/Account_Profile/') || 
                                 window.location.pathname.includes('/Product_Pages/') ? '../' : '';
                
                fetch(basePath + 'search_api.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        displaySearchResults(data, basePath);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                    });
            }, 300);
        }
        
        function displaySearchResults(data, basePath) {
            const resultsDiv = document.getElementById('searchResults');
            let html = '';
            
            // Categories Section
            if (data.categories && data.categories.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-title">Categories</div>';
                data.categories.forEach(cat => {
                    html += '<a href="' + basePath + 'category.php?id=' + cat.id + '" class="search-item">';
                    html += '<div class="search-item-category"><i class="fas fa-folder" style="margin-right:8px;"></i>' + cat.name + '</div>';
                    html += '</a>';
                });
                html += '</div>';
            }
            
            // Products Section
            if (data.products && data.products.length > 0) {
                html += '<div class="search-section">';
                html += '<div class="search-section-title">Products</div>';
                data.products.forEach(prod => {
                    let imgPath = prod.image || (basePath + 'assets/images/default-product.jpg');
                    
                    html += '<a href="' + basePath + 'Product_Pages/product.php?id=' + prod.id + '" class="search-item">';
                    html += '<img src="' + imgPath + '" alt="' + prod.name + '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;" onerror="this.src=\'' + basePath + 'assets/images/default-product.jpg\'">';
                    html += '<div class="search-item-info">';
                    html += '<div class="search-item-name">' + prod.name + '</div>';
                    html += '<div class="search-item-price">₹' + prod.price + '</div>';
                    html += '</div>';
                    html += '</a>';
                });
                html += '</div>';
            }
            
            // No Results
            if ((!data.categories || data.categories.length === 0) && 
                (!data.products || data.products.length === 0)) {
                html += '<div class="search-empty">No results found</div>';
            }
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.add('active');
        }
        
        function performSearch() {
            const query = document.getElementById('searchInput').value;
            if (query.length >= 2) {
                const basePath = window.location.pathname.includes('/Account_Profile/') || 
                                 window.location.pathname.includes('/Product_Pages/') ? '../' : '';
                window.location.href = basePath + 'search_results.php?q=' + encodeURIComponent(query);
            }
        }
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            const searchBox = document.querySelector('.search-box');
            const resultsDiv = document.getElementById('searchResults');
            if (!searchBox.contains(e.target)) {
                resultsDiv.classList.remove('active');
            }
        });
    </script>
    
    <!-- Floating Home Button -->
    <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/Account_Profile/') !== false || strpos($_SERVER['PHP_SELF'], '/Product_Pages/') !== false ? '../index.php' : 'index.php'); ?>" class="floating-home" title="Go to Home">
        <i class="fas fa-home"></i>
    </a>








