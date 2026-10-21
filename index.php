<?php
session_start();
include 'Database_file/config.php';

// Check if user just registered and show welcome popup
$show_welcome_popup = false;
if (isset($_SESSION['show_welcome_coupon'])) {
    $show_welcome_popup = true;
    unset($_SESSION['show_welcome_coupon']);
}

// Initialize milestone popup variables
$show_milestone_popup = false;
$milestone_coupon = null;

// Check for order milestone popup from database (for when admin marks order delivered)
if (isset($_SESSION['user_id'])) {
    $pending_coupon = getPendingMilestoneCoupon($conn, $_SESSION['user_id']);
    if ($pending_coupon) {
        $show_milestone_popup = true;
        $milestone_coupon = [
            'code' => $pending_coupon['code'],
            'discount' => $pending_coupon['discount_type'] == 'percentage' ? 
                $pending_coupon['discount_value'] . '% OFF' : 
                '₹' . $pending_coupon['discount_value'] . ' OFF',
            'description' => $pending_coupon['description'],
            'order_count' => $pending_coupon['code'] == 'FIRSTORDER' ? 1 : 5,
            'user_coupon_id' => $pending_coupon['user_coupon_id']
        ];
        // Mark as notified so it won't show again
        markCouponAsNotified($conn, $pending_coupon['user_coupon_id']);
    }
}

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
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Check if product already in cart
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
    
    // No redirect - stay on home page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Cart - Online Shopping</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- NAVBAR Section -->
   <?php include 'Header.php'; ?>

    <?php if ($show_welcome_popup): ?>
    <!-- Welcome Coupon Popup -->
    <div id="welcomePopup" class="welcome-popup-overlay" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div class="welcome-popup" style="background: white; border-radius: 20px; padding: 40px; max-width: 450px; width: 90%; text-align: center; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popupSlide 0.3s ease;">
            <button onclick="closeWelcomePopup()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            <div style="margin-bottom: 20px;">
                <i class="fa-solid fa-gift" style="font-size: 60px; color: #667eea;"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 10px; font-size: 28px;">Welcome to E-Cart!</h2>
            <p style="color: #666; margin-bottom: 25px; font-size: 16px;">Thank you for joining us. Here's a special gift for your first order!</p>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 25px; margin-bottom: 25px; color: white;">
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.9;">Your Welcome Coupon</div>
                <div style="font-size: 36px; font-weight: 800; margin-bottom: 10px;">10% OFF</div>
                <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 10px; font-family: monospace; font-size: 20px; letter-spacing: 2px;">WELCOME10</div>
            </div>
            
            <p style="font-size: 13px; color: #888; margin-bottom: 20px;">Valid for your first order only • Min order ₹100</p>
            
            <button onclick="copyWelcomeCode()" id="copyBtn" style="background: #10b981; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fa-regular fa-copy"></i> Copy Code
            </button>
            <a href="cart.php" style="background: #3b82f6; color: white; text-decoration: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; display: block;">
                Shop Now <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <style>
        @keyframes popupSlide {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
    
    <script>
        function closeWelcomePopup() {
            document.getElementById('welcomePopup').style.display = 'none';
        }
        
        function copyWelcomeCode() {
            navigator.clipboard.writeText('WELCOME10').then(function() {
                var btn = document.getElementById('copyBtn');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                btn.style.background = '#059669';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy Code';
                    btn.style.background = '#10b981';
                }, 2000);
            });
        }
    </script>
    <?php endif; ?>

    <?php if ($show_milestone_popup && $milestone_coupon): ?>
    <!-- Order Milestone Popup -->
    <div id="milestonePopup" class="milestone-popup-overlay" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div class="milestone-popup" style="background: white; border-radius: 20px; padding: 40px; max-width: 450px; width: 90%; text-align: center; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popupSlide 0.3s ease;">
            <button onclick="closeMilestonePopup()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            <div style="margin-bottom: 20px;">
                <i class="fa-solid fa-trophy" style="font-size: 60px; color: #f59e0b;"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 10px; font-size: 28px;"><?php echo $milestone_coupon['order_count'] == 1 ? '1st Order Complete!' : '5 Orders Milestone!'; ?></h2>
            <p style="color: #666; margin-bottom: 25px; font-size: 16px;">Congratulations! You've earned a special reward for your loyalty!</p>
            
            <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 15px; padding: 25px; margin-bottom: 25px; color: white;">
                <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.9;">Your Reward Coupon</div>
                <div style="font-size: 36px; font-weight: 800; margin-bottom: 10px;"><?php echo $milestone_coupon['discount']; ?></div>
                <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 10px; font-family: monospace; font-size: 20px; letter-spacing: 2px;"><?php echo $milestone_coupon['code']; ?></div>
            </div>
            
            <p style="font-size: 13px; color: #888; margin-bottom: 20px;"><?php echo $milestone_coupon['description']; ?></p>
            
            <button onclick="copyMilestoneCode('<?php echo $milestone_coupon['code']; ?>')" id="milestoneCopyBtn" style="background: #10b981; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fa-regular fa-copy"></i> Copy Code
            </button>
            <a href="cart.php" style="background: #3b82f6; color: white; text-decoration: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; display: block;">
                Use Coupon <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <script>
        function closeMilestonePopup() {
            document.getElementById('milestonePopup').style.display = 'none';
        }
        
        function copyMilestoneCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                var btn = document.getElementById('milestoneCopyBtn');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                btn.style.background = '#059669';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy Code';
                    btn.style.background = '#10b981';
                }, 2000);
            });
        }
        
        // AJAX Polling for milestone coupons - Check every 3 seconds
        <?php if (isset($_SESSION['user_id'])): ?>
        let couponCheckInterval;
        let popupShown = false;
        
        function checkForMilestoneCoupon() {
            if (popupShown) return; // Don't check if popup already shown
            
            fetch('api_check_coupon.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_coupon && !popupShown) {
                        popupShown = true;
                        showMilestonePopup(data.coupon);
                        clearInterval(couponCheckInterval); // Stop polling after showing
                    }
                })
                .catch(error => {
                    console.error('Error checking for coupon:', error);
                });
        }
        
        function showMilestonePopup(coupon) {
            // Determine title based on coupon type
            var title = 'Special Offer!';
            var icon = 'fa-gift';
            var iconColor = '#667eea';
            var gradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            
            // Check if it's a milestone coupon
            if (coupon.code === 'FIRSTORDER' || coupon.code === 'FIFTHORDER') {
                title = coupon.order_count == 1 ? '1st Order Complete!' : '5 Orders Milestone!';
                icon = 'fa-trophy';
                iconColor = '#f59e0b';
                gradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
            }
            
            // Create popup HTML dynamically
            var popupHTML = `
            <div id="milestonePopup" class="milestone-popup-overlay" style="display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
                <div class="milestone-popup" style="background: white; border-radius: 20px; padding: 40px; max-width: 450px; width: 90%; text-align: center; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: popupSlide 0.3s ease;">
                    <button onclick="closeMilestonePopup()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
                    <div style="margin-bottom: 20px;">
                        <i class="fa-solid ${icon}" style="font-size: 60px; color: ${iconColor};"></i>
                    </div>
                    <h2 style="color: #333; margin-bottom: 10px; font-size: 28px;">${title}</h2>
                    <p style="color: #666; margin-bottom: 25px; font-size: 16px;">${coupon.description || 'Congratulations! You\'ve earned a special reward!'}</p>
                    
                    <div style="background: ${gradient}; border-radius: 15px; padding: 25px; margin-bottom: 25px; color: white;">
                        <div style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.9;">Your Coupon</div>
                        <div style="font-size: 36px; font-weight: 800; margin-bottom: 10px;">${coupon.discount}</div>
                        <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 10px; font-family: monospace; font-size: 20px; letter-spacing: 2px;">${coupon.code}</div>
                    </div>
                    
                    <button onclick="copyMilestoneCode('${coupon.code}')" id="milestoneCopyBtn" style="background: #10b981; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-regular fa-copy"></i> Copy Code
                    </button>
                    <a href="cart.php" style="background: #3b82f6; color: white; text-decoration: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; display: block;">
                        Use Coupon <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            `;
            
            // Insert popup into body
            var div = document.createElement('div');
            div.innerHTML = popupHTML;
            document.body.appendChild(div);
        }
        
        // Start polling when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Check immediately on load
            checkForMilestoneCoupon();
            // Then check every 3 seconds
            couponCheckInterval = setInterval(checkForMilestoneCoupon, 3000);
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>

<?php
// Fetch enabled banners from database - sort by id ASC so new banners appear at the end
$home_banners = [];
$banner_result = $conn->query("SELECT * FROM banners WHERE status = 'enabled' AND position = 'home_main' ORDER BY id ASC");
if ($banner_result) {
    while ($row = $banner_result->fetch_assoc()) {
        $home_banners[] = $row;
    }
}
?>

                 <!-- BANNER SECTION -->     <!-- BANNER SECTION -->    
                 <!-- BANNER SECTION -->     <!-- BANNER SECTION -->    


    <main>
        <section class="hero-banner-wrapper">
            <button type="button" class="banner-arrow banner-arrow-left">
                <i class="fa-solid fa-angle-left"></i>
            </button>
            <div class="hero-banner" id="heroBanner">
                <?php if (empty($home_banners)): ?>
                <?php else: ?>
                    <?php foreach ($home_banners as $banner): ?>
                        <?php if ($banner['type'] === 'video'): ?>
                            <div class="banner-video banner-item">
                                <?php if (!empty($banner['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($banner['link']); ?>">
                                <?php endif; ?>
                                <video src="assets/img/<?php echo htmlspecialchars($banner['image']); ?>" autoplay muted playsinline preload="none" style="width: 100%; height: 100%; object-fit: cover;"></video>
                                <?php if (!empty($banner['link'])): ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="banner-image banner-item">
                                <?php if (!empty($banner['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($banner['link']); ?>">
                                <?php endif; ?>
                                <img src="assets/img/<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                                <?php if (!empty($banner['link'])): ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

               <!-- CETEGORY SECTION -->     <!-- CETEGORY SECTION -->
               <!-- CETEGORY SECTION -->     <!-- CETEGORY SECTION -->

            </div>
            <button type="button" class="banner-arrow banner-arrow-right">
                <i class="fa-solid fa-angle-right"></i>
            </button>
            <div class="banner-progress">
                <div class="banner-progress-fill" id="bannerProgressFill"></div>
            </div>
        </section>
        
        <section class="home-section home-top-categories">
            <div class="home-section-header">
                <h2>Top Categories</h2>
                <a href="#">VIEW ALL</a>
            </div>
            <div class="category-carousel">
                <button type="button" class="category-scroll-btn category-scroll-left" onclick="scrollCategories(-1)" aria-label="Scroll categories left">
                    <i class="fa-solid fa-angle-left"></i>
                </button>
                <div class="category-grid category-scroll" id="categoryScroll">
                <?php
                include 'Database_file/config.php';
                $topCategories = $conn->query("SELECT id, name, description, image FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 20");
                if ($topCategories && $topCategories->num_rows > 0):
                    while ($cat = $topCategories->fetch_assoc()):
                ?>
                        <a href="category.php?id=<?php echo $cat['id']; ?>&name=<?php echo urlencode($cat['name']); ?>" class="category-card">
                            <div class="category-icon">
                                <?php if (!empty($cat['image'])): ?>
                                    <img src="assets/img/<?php echo htmlspecialchars($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-tags"></i>
                                <?php endif; ?>
                            </div>
                            <div class="category-title"><?php echo htmlspecialchars($cat['name']); ?></div>
                            <div class="category-subtitle"><?php echo htmlspecialchars($cat['description']); ?></div>
                        </a>
                <?php
                    endwhile;
                endif;
                ?>
                </div>
                <button type="button" class="category-scroll-btn category-scroll-right" onclick="scrollCategories(1)" aria-label="Scroll categories right">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
            </div>
        </section>





        <!-- PRODUCT SECTION -->    <!-- PRODUCT SECTION -->
        <!-- PRODUCT SECTION -->    <!-- PRODUCT SECTION -->

        <section class="home-section home-products">
            <div class="home-products-row">
                <div class="product-column">
                    <div class="product-column-title">Top Selection</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product1.jpg" alt="Product 1">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="101">
                                            <input type="hidden" name="product_name" value="Samsung Galaxy S23">
                                            <input type="hidden" name="product_price" value="69999">
                                            <input type="hidden" name="product_image" value="product1.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button> 
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="101" data-product-name="Samsung Galaxy S23" data-product-price="69999" data-product-image="product1.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Samsung Galaxy S23</div>
                            <div class="product-tagline">Top Deals</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product2.jpg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="102">
                                            <input type="hidden" name="product_name" value="Asus Laptop TUF Gaming">
                                            <input type="hidden" name="product_price" value="74999">
                                            <input type="hidden" name="product_image" value="product2.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="102" data-product-name="Asus Laptop TUF Gaming" data-product-price="74999" data-product-image="product2.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Asus Laptop TUF Gaming</div>
                            <div class="product-tagline">Special offer</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/Iphone_2.jpg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="103">
                                            <input type="hidden" name="product_name" value="Apple MacBook Air M2">
                                            <input type="hidden" name="product_price" value="99999">
                                            <input type="hidden" name="product_image" value="Iphone_2.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="103" data-product-name="Apple MacBook Air M2" data-product-price="99999" data-product-image="Iphone_2.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Apple MacBook Air M2</div>
                            <div class="product-tagline">Best Performance</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product4.jpeg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="104">
                                            <input type="hidden" name="product_name" value="Smart Watch">
                                            <input type="hidden" name="product_price" value="2999">
                                            <input type="hidden" name="product_image" value="product4.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="104" data-product-name="Smart Watch" data-product-price="2999" data-product-image="product4.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Smart Watch </div>
                            <div class="product-tagline">Top Deals</div>
                        </div>
                    </div>
                </div>

                <div class="product-column">
                    <div class="product-column-title">Top picks of the sale</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product5.jpg" alt="Product 1">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="201">
                                            <input type="hidden" name="product_name" value="True Wireless Earbuds">
                                            <input type="hidden" name="product_price" value="1499">
                                            <input type="hidden" name="product_image" value="product5.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="201" data-product-name="True Wireless Earbuds" data-product-price="1499" data-product-image="product5.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">True Wireless Earbuds</div>
                            <div class="product-tagline">Min. 50% Off</div>
                        </div>
                        
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product6.jpeg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="202">
                                            <input type="hidden" name="product_name" value="Gaming PC Cabinet">
                                            <input type="hidden" name="product_price" value="4599">
                                            <input type="hidden" name="product_image" value="product6.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="202" data-product-name="Gaming PC Cabinet" data-product-price="4599" data-product-image="product6.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Gaming PC Cabinet</div>
                            <div class="product-tagline">Min. 50% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product17.jpeg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="203">
                                            <input type="hidden" name="product_name" value="Action Camera 4K">
                                            <input type="hidden" name="product_price" value="5999">
                                            <input type="hidden" name="product_image" value="product17.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="203" data-product-name="Action Camera 4K" data-product-price="5999" data-product-image="product17.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Action Camera 4K</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product8.jpeg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="205">
                                            <input type="hidden" name="product_name" value="Mechanical Keyboard RGB">
                                            <input type="hidden" name="product_price" value="2499">
                                            <input type="hidden" name="product_image" value="product8.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="205" data-product-name="Mechanical Keyboard RGB" data-product-price="2499" data-product-image="product8.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Mechanical Keyboard RGB</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                    </div>
                </div>

                <div class="product-promo-banner">
                    <div class="promo-content">
                        <div class="promo-image">
                            <img src="assets/img/computer_accessories.jpg" alt="Computer Accessories Banner">
                        </div>
                        <div class="promo-text">
                            <div class="promo-label">Shop your</div>
                            <div class="promo-title">Computer Accessories</div>
                            <div class="promo-subtitle">with Latest &amp; Trendy Choices</div>
                            <button class="promo-btn">Shop Now</button>
                        </div>
                    </div>
                </div>
                

                 <div class="product-column">
                    <div class="product-column-title">Best Sellers</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product3.jpg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="103">
                                            <input type="hidden" name="product_name" value="Apple MacBook Air M2">
                                            <input type="hidden" name="product_price" value="99999">
                                            <input type="hidden" name="product_image" value="product3.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="103" data-product-name="Apple MacBook Air M2" data-product-price="99999" data-product-image="product3.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Apple MacBook Air M2</div>
                            <div class="product-tagline">Best Performance</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product10.jpeg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="301">
                                            <input type="hidden" name="product_name" value="Android Tablet 10-inch">
                                            <input type="hidden" name="product_price" value="15999">
                                            <input type="hidden" name="product_image" value="product10.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="301" data-product-name="Android Tablet 10-inch" data-product-price="15999" data-product-image="product10.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Android Tablet 10-inch</div>
                            <div class="product-tagline">Hot Deal</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product11.jpeg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="302">
                                            <input type="hidden" name="product_name" value="DSLR Camera Lens">
                                            <input type="hidden" name="product_price" value="21999">
                                            <input type="hidden" name="product_image" value="product11.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="302" data-product-name="DSLR Camera Lens" data-product-price="21999" data-product-image="product11.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">DSLR Camera Lens</div>
                            <div class="product-tagline">Limited Offer</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product12.jpeg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="303">
                                            <input type="hidden" name="product_name" value="Tripod Stand">
                                            <input type="hidden" name="product_price" value="1299">
                                            <input type="hidden" name="product_image" value="product12.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="303" data-product-name="Tripod Stand" data-product-price="1299" data-product-image="product12.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Tripod Stand</div>
                            <div class="product-tagline">Top Rated</div>
                        </div>
                    </div>
                </div>


                
                 <div class="product-column">
                    <div class="product-column-title">New Arrivals</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product13.jpeg" alt="Product 1">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="401">
                                            <input type="hidden" name="product_name" value="Fitness Smart Band">
                                            <input type="hidden" name="product_price" value="1299">
                                            <input type="hidden" name="product_image" value="product13.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="401" data-product-name="Fitness Smart Band" data-product-price="1299" data-product-image="product13.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Fitness Smart Band</div>
                            <div class="product-tagline">New Arrival</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product15.jpeg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="402">
                                            <input type="hidden" name="product_name" value="Power Bank 20000mAh">
                                            <input type="hidden" name="product_price" value="1499">
                                            <input type="hidden" name="product_image" value="product15.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="402" data-product-name="Power Bank 20000mAh" data-product-price="1499" data-product-image="product15.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Power Bank 20000mAh</div>
                            <div class="product-tagline">Just Launched</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product16.jpeg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="403">
                                            <input type="hidden" name="product_name" value="Handheld Game Console">
                                            <input type="hidden" name="product_price" value="18999">
                                            <input type="hidden" name="product_image" value="product16.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="404" data-product-name="Handheld Game Console" data-product-price="3999" data-product-image="Game Console.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Handheld Game Console</div>
                            <div class="product-tagline">Fresh Stock</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/Mouse.jpg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="405">
                                            <input type="hidden" name="product_name" value="Wireless Mouse">
                                            <input type="hidden" name="product_price" value="599">
                                            <input type="hidden" name="product_image" value="Mouse.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="405" data-product-name="Wireless Mouse" data-product-price="599" data-product-image="Mouse.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Wireless Mouse</div>
                            <div class="product-tagline">Latest</div>
                        </div>
                    </div>
                </div>



                
                 <div class="product-column">
                    <div class="product-column-title">Top picks of the sale</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product18.jpeg" alt="Product 1">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="501">
                                            <input type="hidden" name="product_name" value="Smartwatch Charging Stand">
                                            <input type="hidden" name="product_price" value="799">
                                            <input type="hidden" name="product_image" value="product18.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="501" data-product-name="Smartwatch Charging Stand" data-product-price="799" data-product-image="product18.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Smartwatch Charging Stand</div>
                            <div class="product-tagline">Min. 50% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product19.jpeg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="502">
                                            <input type="hidden" name="product_name" value="Wireless Charger Pad">
                                            <input type="hidden" name="product_price" value="999">
                                            <input type="hidden" name="product_image" value="product19.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="502" data-product-name="Wireless Charger Pad" data-product-price="999" data-product-image="product19.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Wireless Charger Pad</div>
                            <div class="product-tagline">Min. 50% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product20.jpeg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="503">
                                            <input type="hidden" name="product_name" value="Laptop Sleeve Bag">
                                            <input type="hidden" name="product_price" value="1199">
                                            <input type="hidden" name="product_image" value="product20.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="503" data-product-name="Laptop Sleeve Bag" data-product-price="1199" data-product-image="product20.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Laptop Sleeve Bag</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/true_wireless.jpg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="504">
                                            <input type="hidden" name="product_name" value="True Wireless Earbuds">
                                            <input type="hidden" name="product_price" value="1499">
                                            <input type="hidden" name="product_image" value="true_wireless.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="504" data-product-name="True Wireless Earbuds" data-product-price="1499" data-product-image="true_wireless.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">True Wireless Earbuds</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                    </div>
                </div>


              <div class="product-column">
                    <div class="product-column-title">Top Selection</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/Iphone_1.jpg" alt="Iphone">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="601">
                                            <input type="hidden" name="product_name" value="iPhone">
                                            <input type="hidden" name="product_price" value="50000">
                                            <input type="hidden" name="product_image" value="Iphone_1.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="601" data-product-name="iPhone" data-product-price="50000" data-product-image="Iphone_1.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Iphone</div>
                            <div class="product-tagline">Top Deals</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product8.jpeg" alt="Hand Grips">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="602">
                                            <input type="hidden" name="product_name" value="Mechanical Keyboard RGB">
                                            <input type="hidden" name="product_price" value="2499">
                                            <input type="hidden" name="product_image" value="product8.jpeg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="602" data-product-name="Mechanical Keyboard RGB" data-product-price="2499" data-product-image="product8.jpeg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Mechanical Keyboard RGB</div>
                            <div class="product-tagline">Special offer</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/Keyboard.jpg" alt="Keyboards">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="603">
                                            <input type="hidden" name="product_name" value="Wireless Keyboard">
                                            <input type="hidden" name="product_price" value="1299">
                                            <input type="hidden" name="product_image" value="Keyboard.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="603" data-product-name="Wireless Keyboard" data-product-price="1299" data-product-image="Keyboard.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Wireless Keyboard</div>
                            <div class="product-tagline">Special offer</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/Headphone.jpg" alt="Headphones">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="604">
                                            <input type="hidden" name="product_name" value="Gaming Headset">
                                            <input type="hidden" name="product_price" value="1999">
                                            <input type="hidden" name="product_image" value="Headphone.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="604" data-product-name="Gaming Headset" data-product-price="1999" data-product-image="Headphone.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Gaming Headset</div>
                            <div class="product-tagline">Top Deals</div>
                        </div>
                    </div>
                </div>

                <div class="product-column">
                    <div class="product-column-title">Top picks of the sale</div>
                    <div class="product-grid">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product1.jpg" alt="Product 1">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="701">
                                            <input type="hidden" name="product_name" value="Samsung Galaxy S23">
                                            <input type="hidden" name="product_price" value="69999">
                                            <input type="hidden" name="product_image" value="product1.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="701" data-product-name="Samsung Galaxy S23" data-product-price="69999" data-product-image="product1.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Samsung Galaxy S23</div>
                            <div class="product-tagline">Min. 50% Off</div>
                            </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product2.jpg" alt="Product 2">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="702">
                                            <input type="hidden" name="product_name" value="Asus Laptop TUF Gaming">
                                            <input type="hidden" name="product_price" value="74999">
                                            <input type="hidden" name="product_image" value="product2.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="702" data-product-name="Asus Laptop TUF Gaming" data-product-price="74999" data-product-image="product2.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Asus Laptop TUF Gaming</div>
                            <div class="product-tagline">Min. 50% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product3.jpg" alt="Product 3">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="703">
                                            <input type="hidden" name="product_name" value="Apple MacBook Air M2">
                                            <input type="hidden" name="product_price" value="99999">
                                            <input type="hidden" name="product_image" value="product3.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="703" data-product-name="Apple MacBook Air M2" data-product-price="99999" data-product-image="product3.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">Apple MacBook Air M2</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/img/product5.jpg" alt="Product 5">
                                <div class="product-overlay">
                                    <div class="product-icons">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="704">
                                            <input type="hidden" name="product_name" value="True Wireless Earbuds">
                                            <input type="hidden" name="product_price" value="1499">
                                            <input type="hidden" name="product_image" value="product5.jpg">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="product-icon-btn add-to-cart" title="Add to Cart">
                                                <i class="fa-solid fa-shopping-cart"></i>
                                            </button>
                                        </form>
                                        <button class="product-icon-btn wishlist" title="Add to Wishlist" data-product-id="704" data-product-name="True Wireless Earbuds" data-product-price="1499" data-product-image="product5.jpg">
                                            <i class="fa-solid fa-heart"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="product-name">True Wireless Earbuds</div>
                            <div class="product-tagline">Min. 30% Off</div>
                        </div>
                    </div>
                </div>

                <div class="product-promo-banner">
                    <div class="promo-content">
                        <div class="promo-image">
                            <img src="assets/img/computer_accessories.jpg" alt="Computer Accessories Banner">
                        </div>
                        <div class="promo-text">
                            <div class="promo-label">Shop your</div>
                            <div class="promo-title">Computer Accessories</div>
                            <div class="promo-subtitle">with Latest &amp; Trendy Choices</div>
                            <button class="promo-btn">Shop Now</button>
                        </div>
                    </div>
                </div>


            </div>
        </section>
    </main>



         
  <?php include "Footer.php"; ?>
       
    <script src="assets/js/index.js"></script>

    <script>
        function scrollCategories(direction) {
            var el = document.getElementById('categoryScroll');
            if (!el) return;
            var step = Math.max(220, Math.floor(el.clientWidth * 0.8));
            el.scrollBy({ left: direction * step, behavior: 'smooth' });
        }

        // Wishlist functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle wishlist button clicks
            document.addEventListener('click', function(e) {
                const wishlistBtn = e.target.closest('.wishlist');
                if (!wishlistBtn) return;

                e.preventDefault();
                
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
                        window.location.href = 'login.php';
                        return;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        // Toggle the visual state of the heart icon
                        const icon = wishlistBtn.querySelector('i');
                        if (data.action === 'added') {
                            icon.style.color = '#ff6161';
                            // Show notification
                            showNotification('Product added to wishlist!', 'success');
                        } else if (data.action === 'removed') {
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