<?php
// Database Configuration for E-Cart
$host = "localhost";
$username = "root";
$password = "";
$database = "e_cart";

// Create database connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to support multiple languages
$conn->set_charset("utf8mb4");

// Create database if not exists
$create_db_sql = "CREATE DATABASE IF NOT EXISTS e_cart";
$conn->query($create_db_sql);

// Select the database
$conn->select_db($database);

// Create users table if not exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1
)";

$conn->query($create_table_sql);

// Create user_profiles table if not exists
$create_user_profiles_sql = "CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    gender ENUM('male', 'female', 'other'),
    birth_date DATE,
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($create_user_profiles_sql);

// Create user_addresses table if not exists
$create_user_addresses_sql = "CREATE TABLE IF NOT EXISTS user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    address_type ENUM('home', 'work', 'other') DEFAULT 'home',
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    phone VARCHAR(15),
    is_default BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($create_user_addresses_sql);

// Create wishlist table if not exists
$create_wishlist_sql = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    product_name VARCHAR(255),
    product_price DECIMAL(10,2),
    product_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($create_wishlist_sql);

// Create coupons table if not exists
$create_coupons_sql = "CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    valid_from DATE,
    valid_until DATE,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    for_new_users_only BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_coupons_sql);

// Insert default coupons if table is empty
$checkCoupons = $conn->query("SELECT COUNT(*) as count FROM coupons");
if ($checkCoupons && $checkCoupons->fetch_assoc()['count'] == 0) {
    $defaultCoupons = [
        ['WELCOME10', 'New User Special - 10% Off on your first order', 'percentage', 10.00, 0, NULL, date('Y-m-d'), date('Y-m-d', strtotime('+1 year')), NULL, 0, 1, 1],
        ['FIRSTORDER', 'Congrats on your 1st Order! Get 15% Off', 'percentage', 15.00, 0, 500, date('Y-m-d'), date('Y-m-d', strtotime('+6 months')), NULL, 0, 1, 0],
        ['FIFTHORDER', 'Milestone Reward - 25% Off on your 5th Order', 'percentage', 25.00, 0, 1000, date('Y-m-d'), date('Y-m-d', strtotime('+6 months')), NULL, 0, 1, 0],
        ['SAVE20', 'Flat 20% Off - Limited Time Offer', 'percentage', 20.00, 500, NULL, date('Y-m-d'), date('Y-m-d', strtotime('+3 months')), 100, 0, 1, 0],
        ['FLAT100', 'Rs. 100 Off on orders above Rs. 999', 'fixed', 100.00, 999, 100, date('Y-m-d'), date('Y-m-d', strtotime('+6 months')), NULL, 0, 1, 0],
        ['MEGA25', 'Mega Sale - 25% Off on Electronics', 'percentage', 25.00, 2000, 2000, date('Y-m-d'), date('Y-m-d', strtotime('+1 month')), 50, 0, 1, 0],
        ['FREESHIP', 'Free Shipping - No minimum order', 'fixed', 50.00, 0, 50, date('Y-m-d'), date('Y-m-d', strtotime('+2 months')), NULL, 0, 1, 0],
        ['FLASH15', 'Flash Sale - 15% Off Today Only', 'percentage', 15.00, 0, NULL, date('Y-m-d'), date('Y-m-d', strtotime('+7 days')), 200, 0, 1, 0],
        ['DIWALI50', 'Festival Special - 50% Off (Max Rs. 1000)', 'percentage', 50.00, 3000, 1000, date('Y-m-d'), date('Y-m-d', strtotime('+2 months')), NULL, 0, 1, 0],
        ['BUNDLE10', 'Bundle Deal - Extra 10% Off on 2+ items', 'percentage', 10.00, 0, 500, date('Y-m-d'), date('Y-m-d', strtotime('+3 months')), NULL, 0, 1, 0],
    ];
    
    $stmt = $conn->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_until, usage_limit, usage_count, is_active, for_new_users_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaultCoupons as $coupon) {
        $stmt->bind_param('sssddsssiiss', $coupon[0], $coupon[1], $coupon[2], $coupon[3], $coupon[4], $coupon[5], $coupon[6], $coupon[7], $coupon[8], $coupon[9], $coupon[10], $coupon[11]);
        $stmt->execute();
    }
}

// Create orders table if not exists
$create_orders_sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($create_orders_sql);

// Create user_coupons table to track assigned coupons
$create_user_coupons_sql = "CREATE TABLE IF NOT EXISTS user_coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    coupon_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    is_used BOOLEAN DEFAULT 0,
    is_notified BOOLEAN DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_coupon (user_id, coupon_id)
)";
$conn->query($create_user_coupons_sql);

// Add is_notified column if it doesn't exist (for existing tables)
$check_column = $conn->query("SHOW COLUMNS FROM user_coupons LIKE 'is_notified'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE user_coupons ADD COLUMN is_notified BOOLEAN DEFAULT 0");
}

// Success message (remove in production)
// echo "Connected successfully to database: " . $database;

// Function to close connection
if (!function_exists('closeConnection')) {
    function closeConnection($conn) {
        $conn->close();
    }
}

// Function to execute prepared statements safely
if (!function_exists('executeQuery')) {
    function executeQuery($conn, $sql, $params = [], $types = "") {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            return $stmt;
        } else {
            die("Error executing statement: " . $stmt->error);
        }
    }
}

// Function to fetch all results
if (!function_exists('fetchAll')) {
    function fetchAll($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Function to fetch single result
if (!function_exists('fetchOne')) {
    function fetchOne($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}

// Function to check and award order milestone coupons
if (!function_exists('checkOrderMilestone')) {
    function checkOrderMilestone($conn, $user_id) {
        // Count user's completed orders
        $sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ? AND order_status = 'delivered'";
        $stmt = executeQuery($conn, $sql, [$user_id], "i");
        $result = fetchOne($stmt);
        $order_count = $result['order_count'];
        
        // Debug logging
        error_log("checkOrderMilestone: User $user_id has $order_count delivered orders");
        
        $milestone_coupon = null;
        
        // Check for 1st order milestone
        if ($order_count == 1) {
            error_log("checkOrderMilestone: 1st order milestone triggered for user $user_id");
            $coupon_code = 'FIRSTORDER';
            $coupon_id = getCouponIdByCode($conn, $coupon_code);
            error_log("checkOrderMilestone: Coupon ID for FIRSTORDER = " . ($coupon_id ?: 'NULL'));
            if ($coupon_id && assignCouponToUser($conn, $user_id, $coupon_id)) {
                error_log("checkOrderMilestone: Successfully assigned FIRSTORDER to user $user_id");
                $milestone_coupon = [
                    'code' => $coupon_code,
                    'discount' => '15% OFF',
                    'description' => 'Congrats on your 1st Order! Get 15% Off (Max ₹500)',
                    'order_count' => 1
                ];
            } else {
                error_log("checkOrderMilestone: Failed to assign FIRSTORDER - coupon_id: $coupon_id");
            }
        }
        // Check for 5th order milestone
        elseif ($order_count == 5) {
            error_log("checkOrderMilestone: 5th order milestone triggered for user $user_id");
            $coupon_code = 'FIFTHORDER';
            $coupon_id = getCouponIdByCode($conn, $coupon_code);
            if ($coupon_id && assignCouponToUser($conn, $user_id, $coupon_id)) {
                $milestone_coupon = [
                    'code' => $coupon_code,
                    'discount' => '25% OFF',
                    'description' => 'Milestone Reward - 25% Off on your 5th Order (Max ₹1000)',
                    'order_count' => 5
                ];
            }
        } else {
            error_log("checkOrderMilestone: No milestone triggered. Order count = $order_count");
        }
        
        return $milestone_coupon;
    }
}

// Function to get coupon ID by code
if (!function_exists('getCouponIdByCode')) {
    function getCouponIdByCode($conn, $code) {
        $sql = "SELECT id FROM coupons WHERE code = ? AND is_active = 1";
        $stmt = executeQuery($conn, $sql, [$code], "s");
        $result = fetchOne($stmt);
        return $result ? $result['id'] : null;
    }
}

// Function to assign coupon to user
if (!function_exists('assignCouponToUser')) {
    function assignCouponToUser($conn, $user_id, $coupon_id) {
        // Check if already assigned
        $check_sql = "SELECT id FROM user_coupons WHERE user_id = ? AND coupon_id = ?";
        $check_stmt = executeQuery($conn, $check_sql, [$user_id, $coupon_id], "ii");
        $check_result = fetchOne($check_stmt);
        
        if ($check_result) {
            return false; // Already assigned
        }
        
        // Assign coupon to user (is_notified = 0 means popup not shown yet)
        $sql = "INSERT INTO user_coupons (user_id, coupon_id, is_used, is_notified) VALUES (?, ?, 0, 0)";
        $stmt = executeQuery($conn, $sql, [$user_id, $coupon_id], "ii");
        return $stmt->affected_rows > 0;
    }
}

// Function to get pending milestone coupons for user (is_notified = 0)
if (!function_exists('getPendingMilestoneCoupon')) {
    function getPendingMilestoneCoupon($conn, $user_id) {
        $sql = "SELECT c.code, c.description, c.discount_type, c.discount_value, c.max_discount, uc.id as user_coupon_id 
                FROM user_coupons uc 
                JOIN coupons c ON uc.coupon_id = c.id 
                WHERE uc.user_id = ? AND uc.is_notified = 0 AND uc.is_used = 0 
                AND c.code IN ('FIRSTORDER', 'FIFTHORDER')
                LIMIT 1";
        $stmt = executeQuery($conn, $sql, [$user_id], "i");
        return fetchOne($stmt);
    }
}

// Function to mark coupon as notified
if (!function_exists('markCouponAsNotified')) {
    function markCouponAsNotified($conn, $user_coupon_id) {
        $sql = "UPDATE user_coupons SET is_notified = 1 WHERE id = ?";
        $stmt = executeQuery($conn, $sql, [$user_coupon_id], "i");
        return $stmt->affected_rows > 0;
    }
}

// Function to get random active coupon
if (!function_exists('getRandomActiveCoupon')) {
    function getRandomActiveCoupon($conn) {
        $sql = "SELECT id, code, description, discount_type, discount_value, max_discount 
                FROM coupons 
                WHERE is_active = 1 
                AND valid_from <= CURDATE() 
                AND valid_until >= CURDATE() 
                AND (usage_limit IS NULL OR usage_count < usage_limit)
                ORDER BY RAND() 
                LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
}

// Function to assign random coupon to user on order delivered
if (!function_exists('assignRandomCouponOnDelivered')) {
    function assignRandomCouponOnDelivered($conn, $user_id) {
        // Get a random coupon
        $coupon = getRandomActiveCoupon($conn);
        
        if ($coupon && assignCouponToUser($conn, $user_id, $coupon['id'])) {
            return [
                'code' => $coupon['code'],
                'discount' => $coupon['discount_type'] == 'percentage' ? 
                    $coupon['discount_value'] . '% OFF' : 
                    '₹' . $coupon['discount_value'] . ' OFF',
                'description' => $coupon['description'],
                'coupon_id' => $coupon['id']
            ];
        }
        
        return null;
    }
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>