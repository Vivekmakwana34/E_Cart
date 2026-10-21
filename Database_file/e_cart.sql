-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20251216.8148b4bc72
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 02, 2026 at 09:06 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_cart`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `position` varchar(100) NOT NULL DEFAULT 'home_main',
  `status` enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `image` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `type`, `position`, `status`, `image`, `link`, `sort_order`, `created_at`, `updated_at`) VALUES
(7, 'Best', 'video', 'home_main', 'enabled', 'banners/1773933164_video1.mp4', '', 0, '2026-03-19 15:12:44', '2026-03-31 04:35:27'),
(10, 'Second', 'video', 'home_main', 'enabled', 'banners/1773933372_video2.mp4', '', 0, '2026-03-19 15:16:12', '2026-03-19 15:16:12'),
(11, 'Beat Headphones', 'video', 'home_main', 'enabled', 'banners/1774075975_video3.mp4', '', 0, '2026-03-21 06:52:55', '2026-03-21 06:52:55'),
(12, 'Best Airbads', 'video', 'home_main', 'enabled', 'banners/1774076002_video4.mp4', '', 0, '2026-03-21 06:53:22', '2026-03-21 06:53:22');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `image` varchar(255) NOT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(18, 'Mobile12', 'from 10000-50000', '1773239940_2_Iphone_2.jpg', 1, 1, '2026-03-11 15:05:10', '2026-04-01 08:52:50'),
(19, 'T.V23', 'From 1000-5000', '1773223893_1773223846_product9.jpeg', 2, 1, '2026-03-11 15:05:23', '2026-04-01 08:53:19'),
(20, 'Headphones23', 'From 1000-5000', 'Headphone.jpg', 3, 1, '2026-03-11 15:05:38', '2026-04-01 08:53:34'),
(22, 'Electronics', 'from 10000-50000', 'computer_accessories.jpg', 4, 1, '2026-03-11 15:07:11', '2026-03-11 15:07:11'),
(23, 'Vivo', 'from 10000-50000', 'product1.jpg', 5, 1, '2026-03-11 15:07:40', '2026-04-01 07:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int UNSIGNED NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topic` enum('order','payment','product','account','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('new','read','replied','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `full_name`, `email`, `phone`, `order_id`, `topic`, `subject`, `message`, `consent`, `ip_address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'bhargav', 'dudhagarabgo@gmail.com', '+919106536420', 'EC20260331103723098', 'order', 'order', 'zdxfcnvmcnbxdx', 1, '::1', 'read', '2026-04-01 11:18:30', '2026-04-01 11:18:43');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `for_new_users_only` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `valid_from`, `valid_until`, `usage_limit`, `usage_count`, `is_active`, `for_new_users_only`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'New User Special - 10% Off on your first order', 'percentage', 10.00, 0.00, NULL, '2026-03-19', '2027-03-19', NULL, 0, 1, 1, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(2, 'SAVE20', 'Flat 20% Off - Limited Time Offer', 'percentage', 20.00, 500.00, NULL, '2026-03-19', '2026-06-19', 100, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(3, 'FLAT101', 'Rs. 100 Off on orders above Rs. 999', 'fixed', 100.00, 999.00, 100.00, '2026-03-19', '2026-09-19', NULL, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:38:26'),
(4, 'MEGA25', 'Mega Sale - 25% Off on Electronics', 'percentage', 25.00, 2000.00, 2000.00, '2026-03-19', '2026-04-19', 50, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(5, 'FREESHIP', 'Free Shipping - No minimum order', 'fixed', 50.00, 0.00, 50.00, '2026-03-19', '2026-05-19', NULL, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(6, 'FLASH15', 'Flash Sale - 15% Off Today Only', 'percentage', 15.00, 0.00, NULL, '2026-03-19', '2026-03-26', 200, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(7, 'DIWALI50', 'Festival Special - 50% Off (Max Rs. 1000)', 'percentage', 50.00, 3000.00, 1000.00, '2026-03-19', '2026-05-19', NULL, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(8, 'BUNDLE10', 'Bundle Deal - Extra 10% Off on 2+ items', 'percentage', 10.00, 0.00, 500.00, '2026-03-19', '2026-06-19', NULL, 0, 1, 0, '2026-03-19 05:10:43', '2026-03-19 05:10:43'),
(9, 'BHARGAV20', 'Maximum 25 Order Complete after', 'percentage', 100.00, 35000.00, 50000.00, '2026-03-19', '2026-04-18', 1, 0, 1, 0, '2026-03-19 05:20:50', '2026-03-20 02:44:35'),
(10, 'VIVEK', 'vivek coupon', 'percentage', 5000.00, 5.00, 10.00, '2026-04-01', '2026-04-01', 1, 0, 1, 1, '2026-04-01 10:14:53', '2026-04-01 10:14:53');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `verification_token`, `is_verified`, `expires_at`, `created_at`) VALUES
(14, 25, 'bhargavdudhagara019@gmail.com', '05f8d16f5ff005af837c660f7df9637fa3c06e5f063898113cd4196462416c00', 1, '2026-03-10 09:19:10', '2026-03-09 09:19:10'),
(22, 36, 'bdudhagara165@rku.ac.in', '27a772a6ca806a63d89825fa2828afbc562d79df7bdb761cf024a8fd8cd91c8d', 1, '2026-03-20 10:18:46', '2026-03-19 10:18:46'),
(23, 38, 'dudhagarabgo@gmail.com', '5843b3ffc7b40d09c0857c9fef39532daac605487d70bb849d489b2b39cb9cea', 1, '2026-04-02 10:27:10', '2026-04-01 10:27:10');

-- --------------------------------------------------------

--
-- Table structure for table `forgot_pass`
--

CREATE TABLE `forgot_pass` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `OTP` varchar(6) NOT NULL,
  `last_attempt` timestamp NOT NULL,
  `attempt_count` int NOT NULL,
  `last_otp_sent` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `forgot_pass`
--

INSERT INTO `forgot_pass` (`id`, `user_id`, `token`, `OTP`, `last_attempt`, `attempt_count`, `last_otp_sent`) VALUES
(4, 35, '2ac90b2fed1e9894369f3d0afc9ccb5e8ef74bd80656bbe1d8edaa9b6d1d4510', '839059', '2026-04-01 08:51:26', 2, '2026-04-01 03:21:06');

-- --------------------------------------------------------

--
-- Table structure for table `gift_cards`
--

CREATE TABLE `gift_cards` (
  `id` int NOT NULL,
  `card_code` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `status` enum('active','inactive','expired','used') NOT NULL DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gift_cards`
--

INSERT INTO `gift_cards` (`id`, `card_code`, `amount`, `balance`, `status`, `created_by`, `user_id`, `created_at`, `updated_at`, `expiry_date`) VALUES
(16, 'GC-DAAC0DA8-2026', 1000.00, 1000.00, 'active', 1, 25, '2026-03-20 03:23:22', '2026-03-20 03:23:22', '2027-03-20'),
(18, 'GC-173512DF-2026', 500.00, 500.00, 'active', 1, 35, '2026-03-21 06:57:55', '2026-03-21 06:57:55', '2027-03-21');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(5,2) NOT NULL,
  `stock` int NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `price`, `discount`, `stock`, `image`, `description`, `status`, `created_at`) VALUES
(8, 'Iphone253', 18, 49200.00, 10.00, 5, '[\"1773242130_0_1773239446_2_1773224911_4_Iphone_4.jpg\",\"1773242130_1_1773239940_1_1773239446_3_Iphone_1.jpg\",\"1773242130_2_1773239940_2_Iphone_2.jpg\"]', 'Best phone', 'active', '2026-03-11 15:15:30'),
(10, 'Samsung', 19, 29000.00, 2.00, 5, '[\"1775028136_0_1773223893_1773223846_product9.jpeg\"]', 'Best T.V', 'active', '2026-04-01 07:22:16');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `review_text` text,
  `images` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `order_id`, `rating`, `review_text`, `images`, `status`, `helpful_count`, `created_at`, `updated_at`) VALUES
(9, 35, 0, 0, 5, 'bhargav', '[\"assets\\/review_images\\/review_35_0_1774935569_0.jpg\"]', 'approved', 0, '2026-03-31 05:39:29', '2026-03-31 10:38:21'),
(10, 35, 0, 0, 5, 'bhaarararar', '[\"assets\\/review_images\\/review_35_0_1774935682_0.jpg\"]', 'approved', 0, '2026-03-31 05:41:22', '2026-03-31 10:38:20'),
(11, 35, 0, 0, 3, 'best', '[\"assets\\/review_images\\/review_35_0_1774953457_0.png\"]', 'approved', 0, '2026-03-31 10:37:38', '2026-03-31 10:38:19'),
(12, 35, 0, 0, 3, 'best', '[\"assets\\/review_images\\/review_35_0_1774953504_0.png\"]', 'approved', 0, '2026-03-31 10:38:24', '2026-03-31 10:38:24'),
(13, 35, 0, 0, 5, 'sdfgkjghjfthdrgsfb', '[\"assets\\/review_images\\/review_35_0_1775028365_0.jpg\"]', 'approved', 0, '2026-04-01 07:26:05', '2026-04-01 07:26:05');

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
--

CREATE TABLE `shipping_zones` (
  `id` int NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `locations` text NOT NULL,
  `shipping_charge` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `shipping_zones`
--

INSERT INTO `shipping_zones` (`id`, `zone_name`, `locations`, `shipping_charge`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Local City', 'Pune, PCMC', 80.00, 'active', '2026-03-21 06:39:07', '2026-03-21 06:54:39'),
(2, 'Maharashtra', 'Mumbai, Thane, Nashik, Nagpur', 49.00, 'active', '2026-03-21 06:39:07', '2026-03-21 06:39:07'),
(3, 'Rest of India', 'All other states', 99.00, 'active', '2026-03-21 06:39:07', '2026-03-21 06:39:07'),
(4, 'delhhi', 'navi delhi', 60.00, 'active', '2026-03-21 06:46:27', '2026-03-21 06:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `email_verified` tinyint(1) DEFAULT '0',
  `user_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `role`, `password`, `created_at`, `updated_at`, `is_active`, `email_verified`, `user_type`, `reset_token`, `token_expiry`) VALUES
(36, 'BHARGAV', 'bdudhagara165@rku.ac.in', 'user', '$2y$10$/EWOh9M/uSWUech6kr/2.eEMd1J.06/Zo/UDxamvp0y7yV/Cw6/Wu', '2026-03-19 10:18:46', '2026-03-19 10:19:07', 1, 1, 'user', NULL, NULL),
(37, 'bhagrav', 'bhargavdudhagara019@gmail.com', 'admin', '$2y$10$ud.YLoDJ22.SH6dLMkCOyuJG8PvpbyG30j/hxa8cmQZxt0fl1pdjm', '2026-03-21 06:35:42', '2026-03-21 06:36:22', 1, 1, 'admin', NULL, NULL),
(38, 'Dhruval', 'dudhagarabgo@gmail.com', 'user', '$2y$10$itEjfGv70quDucQC82fIJO6AH52OvsI9wu6P9HR6ix1E4hG5HwePO', '2026-04-01 10:27:09', '2026-04-01 10:29:57', 1, 1, 'user', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `address_type` enum('home','work','other') DEFAULT 'home',
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `country` varchar(100) DEFAULT 'India',
  `phone` varchar(15) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_type`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`, `phone`, `is_default`, `created_at`) VALUES
(10, 36, 'home', 'YOGESHWAR KRUPA,', 'MEGHANI NAGAR, STREET NO-5,', 'rajkot', 'DL', '360002', 'India', '09106536420', 1, '2026-03-19 10:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_coupons`
--

CREATE TABLE `user_coupons` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `coupon_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `used_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `is_notified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_coupons`
--

INSERT INTO `user_coupons` (`id`, `user_id`, `coupon_id`, `assigned_at`, `used_at`, `is_used`, `is_notified`) VALUES
(8, 36, 1, '2026-03-19 10:18:46', '2026-03-19 10:19:51', 1, 0),
(9, 36, 2, '2026-03-19 10:20:13', NULL, 0, 0),
(12, 38, 1, '2026-04-01 10:27:09', NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_price` decimal(10,2) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`verification_token`);

--
-- Indexes for table `forgot_pass`
--
ALTER TABLE `forgot_pass`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gift_cards`
--
ALTER TABLE `gift_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_coupons`
--
ALTER TABLE `user_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_coupon` (`user_id`,`coupon_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `forgot_pass`
--
ALTER TABLE `forgot_pass`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gift_cards`
--
ALTER TABLE `gift_cards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_zones`
--
ALTER TABLE `shipping_zones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_coupons`
--
ALTER TABLE `user_coupons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_coupons`
--
ALTER TABLE `user_coupons`
  ADD CONSTRAINT `user_coupons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_coupons_ibfk_2` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
