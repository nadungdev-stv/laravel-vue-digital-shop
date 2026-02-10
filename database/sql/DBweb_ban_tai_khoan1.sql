-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 27, 2026 at 12:48 AM
-- Server version: 8.0.42-0ubuntu0.20.04.1
-- PHP Version: 7.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_ban_tai_khoan`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts_stock`
--

CREATE TABLE `accounts_stock` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `account_username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('available','sold','reserved') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `sold_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts_stock`
--

INSERT INTO `accounts_stock` (`id`, `product_id`, `account_username`, `account_password`, `account_note`, `status`, `sold_at`, `created_at`, `updated_at`) VALUES
(1, 7, 'nadung2k4@gmail.com', 'nadung2k4@gmail.com', '', 'available', NULL, '2025-10-31 03:55:18', '2025-10-31 03:55:18'),
(2, 32, 'user1@gmail.com', 'Pass123!', 'VIP', 'sold', '2025-10-31 06:38:16', '2025-10-31 04:21:03', '2025-10-31 06:38:16'),
(3, 32, 'user2@gmail.com', 'Pass456!', '', 'available', NULL, '2025-10-31 04:21:03', '2025-10-31 04:21:03'),
(4, 32, 'user3@gmail.com', 'Pass789!', 'Premium', 'available', NULL, '2025-10-31 04:21:03', '2025-10-31 04:21:03'),
(5, 43, '111admin', 'OneDrive 1TB 12 Tháng', '', 'sold', '2025-10-31 20:26:51', '2025-10-31 20:26:36', '2025-10-31 20:26:51'),
(6, 30, 'user1@gmail.com', 'Pass123!', 'VIP', 'available', NULL, '2025-11-01 11:01:09', '2025-11-01 11:01:09'),
(7, 30, 'user2@gmail.com', 'Pass456!', '', 'available', NULL, '2025-11-01 11:01:09', '2025-11-01 11:01:09'),
(8, 30, 'user3@gmail.com', 'Pass789!', 'Premium', 'available', NULL, '2025-11-01 11:01:09', '2025-11-01 11:01:09');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `image`, `link`, `category_id`, `description`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'eSIM Du Lịch', '/public/images/banners/banner_1761949331_5162.svg', '/products?category=esim', NULL, 'Kết nối mọi nơi - không cần thay SIM', 1, 'inactive', '2025-10-31 21:56:17', '2025-11-01 10:36:55'),
(2, 'VPN Bảo Mật', '/public/images/banners/banner_1761949359_2784.svg', '/products?category=vpn', NULL, 'Ứng dụng VPN tốc độ - bảo mật', 0, 'inactive', '2025-10-31 21:56:17', '2025-11-01 08:40:44'),
(3, 'Game Steam', '/public/images/banners/banner_1761949347_8262.svg', '/products?category=game', NULL, 'Tài khoản offline - Game bom tấn', 2, 'inactive', '2025-10-31 21:56:17', '2025-11-01 08:40:46'),
(10, 'Netflix', '/public/images/banners/banner_1767598384_6503.png', 'https://www.veyrix.pro/netflix-premium-12-thang', NULL, '', 0, 'active', '2025-11-01 08:38:37', '2026-01-05 07:33:04'),
(11, 'Spotify', '/public/images/banners/banner_1767598583_9305.jpg', 'https://www.veyrix.pro/spotify-family-slot', NULL, '', 0, 'active', '2025-11-01 08:40:37', '2026-01-05 07:36:23'),
(12, 'Chatgpt', '/public/images/banners/banner_1767598721_8378.jpg', 'https://www.veyrix.pro/chat-gpt-plus', NULL, '', 0, 'active', '2025-11-01 10:36:50', '2026-01-05 07:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `session_id`, `product_id`, `variant_id`, `quantity`, `created_at`, `updated_at`) VALUES
(11, 7, NULL, 46, NULL, 1, '2025-11-01 02:40:56', '2025-11-01 02:40:56'),
(17, 7, NULL, 30, NULL, 2, '2025-11-01 05:44:39', '2025-11-01 05:44:39'),
(34, 8, NULL, 38, NULL, 1, '2025-11-02 19:00:12', '2025-11-02 19:00:12'),
(39, 9, NULL, 30, 11, 1, '2025-11-02 23:21:37', '2025-11-02 23:21:37'),
(41, NULL, '5u38utnp8gqfpjckbdnsvl5dqf', 41, NULL, 1, '2025-11-03 21:49:16', '2025-11-03 21:49:16'),
(43, NULL, 'vb51q6mja5kvkpa2jn14kj1h7t', 30, 11, 2, '2025-11-06 00:52:20', '2025-11-06 00:55:08'),
(45, NULL, 'vb51q6mja5kvkpa2jn14kj1h7t', 38, NULL, 1, '2025-11-06 01:29:28', '2025-11-06 01:29:28'),
(139, NULL, 'sgq3n678jsgllvv1gh9ka6d8g6', 32, NULL, 6, '2025-11-21 02:07:25', '2025-11-21 02:18:27'),
(142, NULL, 'a8cvgb9k7gich8kd3kkbr4d582', 35, NULL, 1, '2025-11-26 16:56:07', '2025-11-26 16:56:07'),
(145, 11, NULL, 30, 9, 1, '2025-12-11 22:58:23', '2025-12-11 23:10:39'),
(147, 1, NULL, 1, 16, 4, '2025-12-18 20:55:37', '2026-01-16 17:11:22'),
(148, 1, NULL, 6, 22, 1, '2025-12-29 17:37:29', '2025-12-29 17:37:29'),
(149, NULL, 'fjie5pkrethlb3d5jn8ggd6lev', 55, 40, 1, '2026-01-07 02:21:33', '2026-01-07 02:21:33'),
(150, NULL, 'fjie5pkrethlb3d5jn8ggd6lev', 30, 11, 1, '2026-01-07 02:21:47', '2026-01-07 02:21:47');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `featured` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `sort_order`, `featured`, `status`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'Streaming', 'streaming', 'Dịch vụ xem phim, nghe nhạc online', 'fas fa-crown', 1, 1, 'active', 0, '2025-10-31 03:48:59', '2025-11-07 17:11:10'),
(2, 'Game', 'game', 'Tài khoản game, nạp thẻ game', 'fas fa-gamepad', 3, 1, 'active', 0, '2025-10-31 03:48:59', '2025-11-07 17:11:08'),
(3, 'VPN', 'vpn', 'Dịch vụ VPN, bảo mật', 'fas fa-shield-alt', 5, 0, 'active', 1, '2025-10-31 03:48:59', '2025-11-07 17:11:08'),
(4, 'Cloud Storage', 'cloud-storage', 'Lưu trữ đám mây', 'fa-solid fa-cloud', 2, 0, 'active', 0, '2025-10-31 03:48:59', '2025-11-07 17:11:10'),
(14, 'Gift Card', 'gift-card', 'Thẻ quà tặng, thẻ cào, voucher', 'fas fa-gift', 4, 0, 'active', 1, '2025-10-31 22:33:24', '2025-11-07 17:11:08'),
(15, 'Giải trí', 'giai-tri', '', 'fas fa-gamepad', 6, 0, 'active', 0, '2025-11-01 05:17:40', '2025-11-01 05:17:45'),
(17, 'Mạng xã hội', 'mang-xa-hoi', 'Facebook, Instagram, Twitter, TikTok Premium', 'fas fa-share-alt', 12, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(18, 'Đám mây & Lưu trữ', 'dam-may-luu-tru', 'Dropbox, OneDrive, iCloud, Cloud storage', 'fas fa-cloud', 13, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(19, 'Âm nhạc & Sáng tạo', 'am-nhac-sang-tao', 'FL Studio, Ableton, Logic Pro, Music production', 'fas fa-music', 14, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(20, 'Fitness & Sức khỏe', 'fitness-suc-khoe', 'MyFitnessPal, Strava, Nike Training, Yoga apps', 'fas fa-heartbeat', 15, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(21, 'Email & Liên lạc', 'email-lien-lac', 'ProtonMail, Gmail Premium, Email marketing tools', 'fas fa-envelope', 16, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(22, 'Dev Tools', 'dev-tools', 'GitHub, JetBrains, Visual Studio, Developer tools', 'fas fa-code', 17, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(23, 'E-Commerce', 'e-commerce', 'Shopify, WooCommerce, BigCommerce tools', 'fas fa-shopping-cart', 18, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(24, 'Marketing & SEO', 'marketing-seo', 'SEMrush, Ahrefs, Mailchimp, Marketing tools', 'fas fa-chart-line', 19, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(25, 'Thiết kế UI/UX', 'thiet-ke-ui-ux', 'Figma, Sketch, Adobe XD, Design tools', 'fas fa-palette', 20, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(26, 'Video Streaming', 'video-streaming', 'Disney+, HBO Max, Amazon Prime, Streaming services', 'fas fa-tv', 21, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(27, 'Đọc sách', 'doc-sach', 'Kindle, Audible, Scribd, E-book services', 'fas fa-book-reader', 22, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(28, 'Quản lý dự án', 'quan-ly-du-an', 'Trello, Asana, Monday, Project management', 'fas fa-tasks', 23, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(30, 'Phần mềm diệt virus', 'phan-mem-diet-virus', 'Norton, McAfee, Kaspersky, Antivirus software', 'fas fa-bug', 25, 0, 'active', 1, '2025-11-09 17:03:29', '2025-11-09 17:03:29'),
(31, 'Học tập', 'hoc-tap', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 06:56:00', '2025-12-12 06:56:00'),
(32, 'Microsoft', 'microsoft', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 07:24:38', '2025-12-12 07:24:38'),
(33, 'Văn Phòng', 'van-phong', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 07:24:38', '2025-12-12 07:24:38'),
(34, 'Video', 'video', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 07:48:36', '2025-12-12 07:48:36'),
(35, 'Âm nhạc', 'am-nhac', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:04:34', '2025-12-12 08:04:34'),
(36, 'Adobe', 'adobe', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:05:52', '2025-12-12 08:05:52'),
(37, 'Làm việc', 'lam-viec', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:05:52', '2025-12-12 08:05:52'),
(38, 'Thiết kế', 'thiet-ke', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:06:51', '2025-12-12 08:06:51'),
(39, 'AI', 'ai', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:10:51', '2025-12-12 08:10:51'),
(40, 'Bản Quyền', 'ban-quyen', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:20:51', '2025-12-12 08:20:51'),
(41, 'Window', 'window', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 08:20:51', '2025-12-12 08:20:51'),
(42, 'Dung lượng', 'dung-luong', NULL, NULL, 0, 0, 'active', 0, '2025-12-12 09:13:02', '2025-12-12 09:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `sender_type` enum('customer','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram_message_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram_chat_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('percent','fixed') COLLATE utf8mb4_unicode_ci DEFAULT 'percent',
  `value` decimal(15,2) NOT NULL,
  `min_amount` decimal(15,2) DEFAULT '0.00',
  `max_discount` decimal(15,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `min_amount`, `max_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'percent', 10.00, 50000.00, 50000.00, NULL, 0, '2025-10-31 03:48:59', '2025-11-30 03:48:59', 'active', '2025-10-31 03:48:59', '2025-10-31 03:48:59'),
(2, 'NEWYEAR20', 'percent', 20.00, 100000.00, 80000.00, 100, 0, '2025-10-31 03:51:34', '2025-12-30 03:51:34', 'active', '2025-10-31 03:51:34', '2025-10-31 03:51:34'),
(3, 'VIP50K', 'fixed', 50000.00, 200000.00, NULL, 50, 5, '2025-10-31 03:51:34', '2025-11-30 03:51:34', 'active', '2025-10-31 03:51:34', '2025-11-06 16:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `netflix_email_cache`
--

CREATE TABLE `netflix_email_cache` (
  `id` int NOT NULL,
  `email_uid` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UID + Recipient Hash or Message-ID',
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `code` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `netflix_notifications`
--

CREATE TABLE `netflix_notifications` (
  `id` int NOT NULL,
  `email_uid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci,
  `code` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('success','info','warning','error') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--
-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

CREATE TABLE `online_users` (
  `session_id` varchar(191) NOT NULL,
  `last_activity` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `order_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `final_amount` decimal(15,2) NOT NULL,
  `payment_method` enum('bank_transfer','momo','zalopay','wallet') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `order_status` enum('pending','processing','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `customer_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_note` text COLLATE utf8mb4_unicode_ci,
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `telegram_message_id` int DEFAULT NULL,
  `sepay_transaction_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--
--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `product_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) GENERATED ALWAYS AS ((`quantity` * `price`)) STORED,
  `account_delivered` text COLLATE utf8mb4_unicode_ci COMMENT 'Thông tin tài khoản đã giao (JSON)',
  `customer_account_info` text COLLATE utf8mb4_unicode_ci COMMENT 'Thông tin tài khoản khách hàng cung cấp (JSON: username, password)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--



--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rating` decimal(3,2) DEFAULT '0.00' COMMENT 'Điểm đánh giá trung bình',
  `features` text COLLATE utf8mb4_unicode_ci,
  `delivery_type` enum('account','email_only','customer_account') COLLATE utf8mb4_unicode_ci DEFAULT 'account' COMMENT 'Loại giao hàng: account = Từ kho, email_only = Lời mời nhóm, customer_account = Tài khoản khách hàng',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `sold_count` int DEFAULT '0',
  `status` enum('active','inactive','out_of_stock') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `tags`, `description`, `rating`, `features`, `delivery_type`, `image`, `price`, `sale_price`, `stock_quantity`, `sold_count`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'Netflix Premium', 'netflix-premium', 'App, Giải trí, Streaming', '<h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Đây là <strong>tài khoản Netflix Premium (UltraHD)</strong> được tạo sẵn và chia thành nhiều User. Mỗi khách hàng sau khi mua sẽ được cấp <strong>1 User riêng</strong> để sử dụng ổn định.</p><h2><strong>2. Thông tin nhận hàng</strong></h2><p>Ngay sau khi thanh toán, bạn sẽ nhận được:</p><ul><li>Email đăng nhập Netflix</li><li>Mật khẩu</li><li>Số User được cấp</li><li>Mã PIN User (nếu có)</li></ul><h2><strong>3. Thời gian xử lý</strong></h2><p>✔ Ngay lập tức sau khi thanh toán thành công.</p><h2><strong>4. Hình thức giao hàng</strong></h2><p>✔ Thông tin đăng nhập sẽ hiển thị ngay trong đơn hàng.</p><h2><strong>5. Thiết bị hỗ trợ</strong></h2><ul><li>Đăng nhập được trên nhiều thiết bị, <strong>nhưng chỉ xem được 1 thiết bị tại 1 thời điểm</strong> (do là tài khoản share).</li><li>Không hỗ trợ sử dụng với máy chiếu.</li></ul><h2><strong>6. Khu vực sử dụng</strong></h2><p>Sản phẩm <strong>chỉ dùng được tại Việt Nam</strong>, không hoạt động ở nước ngoài.</p><h2><strong>7. Hướng dẫn đăng nhập Netflix</strong></h2><p>Có kèm trong đơn hàng để bạn thao tác nhanh chóng.</p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><p><strong>30 ngày</strong> kể từ khi kích hoạt.</p><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu tài khoản gặp lỗi sẽ được <strong>đổi user mới/ngay lập tức</strong>.</li><li>Nếu không còn tài khoản thay thế → <strong>hoàn tiền</strong>.</li></ul><p>👉 Xem chi tiết tại trang <strong>Chính sách bảo hành</strong>.</p><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop sẽ từ chối bảo hành hoặc thu hồi tài khoản nếu khách hàng vi phạm:</p><ul><li>Tự ý đổi email/mật khẩu tài khoản.</li><li>Tự ý thay đổi gói cước hoặc thông tin thanh toán.</li><li>Chia sẻ tài khoản hoặc dùng nhiều hơn 1 thiết bị cùng lúc.</li><li>Sử dụng tài khoản ở <strong>ngoài Việt Nam</strong>.</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3>1. Netflix Premium là gì?</h3><p>Là gói xem phim chất lượng cao nhất của Netflix với kho nội dung lớn, tốc độ nhanh, hỗ trợ 4K và phụ đề tiếng Việt.</p><h3>2. Cách mua Netflix?</h3><p>Nhấn <strong>Mua Ngay</strong>, chọn thanh toán bằng Momo / Ngân hàng / Số dư. Tài khoản được giao tự động.</p><h3>3. Khi lỗi tài khoản thì sao?</h3><p>Shop hỗ trợ <strong>đổi user mới ngay</strong>, hoặc hoàn tiền nếu không còn tài khoản thay thế.</p><h3>4. Gói Premium có gì?</h3><ul><li>Xem phim <strong>4K UltraHD</strong></li><li>Tải phim xem offline</li><li>Nội dung đa dạng, phụ đề tiếng Việt</li><li>Xem trên TV, mobile, PC</li></ul><h3>5. Có dùng trên nhiều thiết bị không?</h3><p>Đăng nhập thoải mái, nhưng <strong>chỉ xem được 1 thiết bị tại 1 thời điểm</strong>.</p><h3>6. Có hỗ trợ xem 4K không?</h3><p>Có, miễn là thiết bị và tốc độ mạng của bạn đủ tiêu chuẩn. Trên điện thoại, chất lượng tối đa là <strong>1080p</strong>.</p><h3>7. Không tìm thấy một số phim?</h3><p>Một số nội dung chỉ có ở từng khu vực. Muốn xem cần dùng <strong>VPN đổi IP</strong> sang khu vực tương ứng.</p><h3>8. Có chỉnh độ phân giải không?</h3><p>Không. Netflix tự điều chỉnh theo tốc độ mạng.</p><h3>9. Trình duyệt nào xem được 4K?</h3><ul><li>Microsoft Edge</li><li>Safari (macOS 11 trở lên)</li></ul><h3>10. Bị lỗi tài khoản phải làm sao?</h3><p>Liên hệ CSKH (8h30 – 23h) để được hỗ trợ ngay. Ngoài giờ, truy cập trang bảo hành.</p><h3>11. Có thay đổi ngôn ngữ tài khoản được không?</h3><p>Có thể đổi tự do.</p><h3>12. Có chỉnh kích cỡ/màu sắc phụ đề không?</h3><p>Có hỗ trợ tùy chỉnh đầy đủ.</p><h3>13. Đã đổi ngôn ngữ nhưng không có phụ đề tiếng Việt?</h3><p>Phụ đề phụ thuộc vào từng nội dung, không phải phim nào cũng có.</p><h3>14. Có chơi game Netflix được không?</h3><p>Không. Netflix Game chưa hỗ trợ tại Việt Nam.</p><h3>15. Có tải phim xem offline được không?</h3><p>Có, nhưng cần mở mạng <strong>ít nhất 1 lần mỗi 7 ngày</strong>.</p><h3>16. Báo quá tải thiết bị phải làm sao?</h3><p>Do tài khoản share nên đôi khi bị quá tải. Liên hệ CSKH giờ làm việc để đổi user.</p><h3>17. Có gia hạn tài khoản cũ được không?</h3><p>Chưa hỗ trợ gia hạn. Khi hết hạn bạn cần mua tài khoản mới.</p><h3>18. Ở nước ngoài có dùng được không?</h3><p>Có. Tài khoản có thể hoạt động ở nước ngoài</p>', 0.00, '<p>Xem 4K HDR Không quảng cáo Tải offline</p>', 'account', '', 135000.00, 120000.00, 50, 2, 'active', 1, '2025-10-31 03:48:59', '2025-11-20 19:03:07'),
(2, 15, 'Spotify Family - Slot Tham Gia', 'spotify-family-slot', 'Giải trí', '<h1>⭐ Giới thiệu sản phẩm Spotify Premium Family</h1><h2>📌 Spotify Family là gì?</h2><p><strong>Spotify Premium Family</strong> là gói nghe nhạc cao cấp cho gia đình với 6 tài khoản thành viên, sử dụng độc lập và không ảnh hưởng lẫn nhau.</p><p> Khi tham gia Family, bạn sẽ được hưởng toàn bộ lợi ích của gói Premium:</p><ul><li>Nghe nhạc chất lượng cao</li><li>Không quảng cáo</li><li>Tải nhạc offline</li><li>Chuyển bài nhanh, không giới hạn</li><li>Playlist cá nhân – đề xuất nhạc thông minh</li></ul><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>lời mời vào Family Spotify Premium</strong>.</li><li>Bạn <strong>không cần cung cấp mật khẩu</strong>, chỉ cần <strong>Email tài khoản Spotify</strong> để nhận lời mời Family.</li><li>Tài khoản sau khi tham gia sẽ trở thành <strong>Premium đầy đủ tính năng</strong>.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Bạn gửi email Spotify (hoặc đăng ký email mới tùy thích)</li><li>Shop gửi <strong>lời mời Family Premium</strong></li><li>Bạn chấp nhận → tài khoản trở thành Premium ngay</li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ 1–5 phút sau khi thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Link/lời mời Family Spotify</li><li>Hướng dẫn tham gia</li><li>CSKH hỗ trợ kỹ thuật trong toàn bộ thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ Spotify</strong></h3><ul><li>Android / iOS</li><li>Windows / macOS</li><li>TV / Android Box</li><li>Web Player</li><li>Loa thông minh (Google Home, Alexa…)</li></ul><h3><strong>6. Khu vực sử dụng</strong></h3><p>✔ Spotify Family sử dụng được <strong>toàn cầu</strong>, chỉ cần xác nhận đúng vùng theo hướng dẫn.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu bị rời Family → shop mời lại ngay</li><li>Nếu Family lỗi → chuyển qua Family mới</li><li>Nếu không thể khắc phục → hoàn tiền thời gian còn lại</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành nếu:</p><ul><li>Bạn tự rời Family</li><li>Tự thay đổi quốc gia vùng tài khoản không theo hướng dẫn</li><li>Chia sẻ tài khoản cho quá nhiều thiết bị làm tăng tỷ lệ quét</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Spotify Family khác gì Premium thường?</strong></h3><ul><li>Premium: 1 người dùng</li><li>Family: gói 6 người dùng, giá rẻ hơn, lợi ích giống Premium</li></ul><h3><strong>2. Tôi cần cung cấp gì để lên Premium Family?</strong></h3><p>Chỉ cần <strong>email đăng nhập Spotify</strong>, không cần mật khẩu.</p><h3><strong>3. Tôi dùng trên nhiều thiết bị được không?</strong></h3><p>Có, mỗi tài khoản Premium dùng trên nhiều thiết bị.</p><h3><strong>4. Bị yêu cầu xác minh địa chỉ thì sao?</strong></h3><p>Shop sẽ hướng dẫn xác minh nhanh 5 giây, hoặc đổi Family vùng phù hợp.</p><h3><strong>5. Tôi nghe nhạc offline được không?</strong></h3><p>Có, tải nhạc chất lượng cao.</p><h3><strong>6. Có mất playlist cũ không?</strong></h3><p>Không, playlist và nhạc yêu thích giữ nguyên 100%.</p><h3><strong>7. Spotify Family dùng ở nước ngoài được không?</strong></h3><p>Có, chỉ cần đăng nhập đúng vùng theo hướng dẫn.</p><h3><strong>8. Tôi muốn đổi email Spotify được không?</strong></h3><p>Được, chỉ cần bạn thông báo lại để shop gửi lời mời mới.</p><p><br></p><h1>🎯 Ưu điểm khi mua Spotify Family tại Shop</h1><ul><li>⭐ Giá rẻ hơn Premium trực tiếp</li><li>⚡ Nhận lời mời nhanh chóng</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ</li><li>🎧 Nghe nhạc chất lượng cao – không quảng cáo</li><li>📱 Hỗ trợ mọi thiết bị</li><li>🔥 Giữ playlist, lịch sử nghe nhạc, thói quen đề xuất</li></ul><p><br></p>', 0.00, '<p>Nghe nhạc không giới hạn Không quảng cáo Tải offline</p>', 'email_only', '/public/images/products/product-2-1763711412.jpg', 25000.00, NULL, 100, 1, 'active', 1, '2025-10-31 03:48:59', '2025-12-12 08:04:58'),
(5, 2, 'Liên Quân Garena Acc Rank Cao', 'lien-quan-rank-cao', '', '<p>Tài khoản Liên Quân có nhiều tướng và skin hiếm</p>', 0.00, '<p>Rank Kim Cương Nhiều skin Full ngọc</p>', 'account', '/public/images/products/product-5-1763711837.jpg', 250000.00, 199000.00, 20, 0, 'inactive', 0, '2025-10-31 03:51:29', '2025-12-12 08:23:35'),
(6, 3, 'NordVPN sử dụng 5 thiết bị', 'nordvpn', '', '<h1>⭐ Giới thiệu sản phẩm NordVPN (Tài khoản VPN bảo mật tốc độ cao)</h1><h2>📌 NordVPN là gì?</h2><p><strong>NordVPN</strong> là dịch vụ VPN hàng đầu thế giới, nổi tiếng nhờ <strong>tốc độ nhanh – bảo mật mạnh – vượt chặn tốt</strong>. Khi sử dụng NordVPN, bạn có thể truy cập Internet an toàn, ẩn danh IP, mở khóa nội dung bị giới hạn theo khu vực và bảo vệ mọi kết nối của mình trên mọi thiết bị.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>tài khoản NordVPN Premium</strong> (gói Full Premium / gói 1 năm / gói 2 năm… tùy sản phẩm).</li><li>Sau khi mua, bạn sẽ được cấp <strong>Email + Mật khẩu đăng nhập</strong> vào ứng dụng NordVPN.</li></ul><h3><strong>2. Hướng dẫn sử dụng</strong></h3><ul><li>Tải ứng dụng NordVPN cho: Windows, macOS, iOS, Android, Linux, Chrome, Firefox.</li><li>Đăng nhập bằng thông tin shop cung cấp.</li><li>Chọn quốc gia → Kết nối → Sử dụng ngay.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Tự động – nhận hàng ngay sau thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><p>Bạn sẽ nhận được:</p><ul><li>Email đăng nhập NordVPN</li><li>Mật khẩu</li><li>Hướng dẫn cài đặt và sử dụng chi tiết</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Máy tính Windows / Mac</li><li>Điện thoại Android / iPhone</li><li>Máy tính bảng</li><li>Router, Smart TV (tùy model)</li><li>Extension trên trình duyệt Chrome / Firefox</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Sử dụng <strong>toàn cầu</strong> – không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày hoặc 3–6–12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Lỗi không đăng nhập được → cấp thông tin mới ngay.</li><li>Nếu không còn tài khoản thay thế → hoàn tiền theo thời gian còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop từ chối bảo hành nếu:</p><ul><li>Bạn tự đổi email/mật khẩu tài khoản.</li><li>Dùng tài khoản sai mục đích, chia sẻ cho người khác.</li><li>Tác động trái phép đến cấu hình bảo mật, 2FA, hoặc thay đổi gói dịch vụ.</li><li>Đăng nhập bất thường gây khóa tài khoản (đăng nhập quá nhiều thiết bị cùng lúc…).</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. NordVPN dùng để làm gì?</strong></h3><ul><li>Ẩn địa chỉ IP và vị trí thật</li><li>Truy cập nội dung bị chặn theo quốc gia (Netflix, YouTube, TikTok, Facebook…)</li><li>Tránh bị theo dõi, bảo vệ quyền riêng tư</li><li>Bảo vệ khi dùng WiFi công cộng</li><li>Tăng tốc độ truy cập server quốc tế</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><p>Tùy gói, thông thường dùng được <strong>tối đa 6 thiết bị</strong> cùng lúc.</p><h3><strong>3. Dùng ở nước ngoài được không?</strong></h3><p>Có. NordVPN hoạt động <strong>toàn cầu</strong>, không bị giới hạn.</p><h3><strong>4. Tốc độ có nhanh không?</strong></h3><p>Rất nhanh nhờ công nghệ <strong>NordLynx / WireGuard</strong>.</p><p> Tốc độ xem phim 4K, chơi game, tải file đều ổn định.</p><h3><strong>5. Có xem được Netflix US/UK/Japan không?</strong></h3><p>Có, NordVPN hỗ trợ <strong>mở khóa nhiều thư viện Netflix quốc tế</strong>.</p><h3><strong>6. Có bị giảm ping khi chơi game không?</strong></h3><p>Có thể cải thiện khi kết nối server gần hoặc ổn định hơn tuyến quốc tế.</p><h3><strong>7. Tài khoản này có dùng được trên Router?</strong></h3><p>Có, tùy theo từng model Router hỗ trợ OpenVPN/WireGuard.</p><h3><strong>8. Tôi có bị lộ thông tin không?</strong></h3><p>Không. NordVPN sử dụng mã hóa cấp quân sự AES-256 và không lưu log người dùng.</p><h3><strong>9. Nếu bị yêu cầu xác minh email thì sao?</strong></h3><p>Bạn chỉ cần liên hệ CSKH để được hỗ trợ đổi tài khoản mới.</p><h3><strong>10. Có hỗ trợ đổi IP theo quốc gia không?</strong></h3><p>Có hơn <strong>60+ quốc gia</strong> để lựa chọn như: US, UK, Canada, Japan, Singapore, Vietnam,...</p><p><br></p><h1>🎯 Ưu điểm khi mua NordVPN tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký chính hãng</li><li>⚡ Nhận tài khoản ngay lập tức</li><li>🔒 Bảo mật, tốc độ nhanh, ổn định</li><li>🛡 Bảo hành đầy đủ</li><li>🌍 Vượt chặn mọi nền tảng: Facebook, TikTok, Netflix, ChatGPT…</li><li>🔄 Sử dụng đa thiết bị – tiện lợi</li></ul><p><br></p>', 0.00, '<p>Server toàn cầu Tốc độ cao Không giới hạn</p>', 'account', '/public/images/products/product-6-1763667261.jpg', 180000.00, 150000.00, 30, 0, 'active', 1, '2025-10-31 03:51:29', '2025-11-20 19:34:21'),
(7, 4, 'Google Drive 5TB Lifetime', 'google-drive-5tb', '', '<p>Drive lifetime dung lượng 5TB</p>', 0.00, '<p>Chia sẻ lifetime Tốc độ cao Ổn định lâu dài</p>', 'email_only', '/public/images/products/product-7-1763711752.png', 200000.00, 180000.00, 101, 0, 'active', 0, '2025-10-31 03:51:29', '2025-12-12 08:23:41'),
(30, 15, 'YouTube Premium', 'youtube-premium', 'App, Giải trí, Youtube', '<h1><strong>YouTube Premium 1 Năm – Gia Hạn Chính Chủ (Family Invite)</strong></h1><h1><strong>Giới thiệu sản phẩm</strong></h1><p>Đây là <strong>gói gia hạn YouTube Premium chính chủ 12 tháng</strong>, được kích hoạt trực tiếp trên tài khoản Google của bạn thông qua link mời tham gia Family.</p><p>Sau khi thanh toán, bạn sẽ <strong>nhận link invite ngay lập tức</strong>, chỉ cần bấm chấp nhận lời mời để nâng cấp Premium.</p><blockquote><strong>Lưu ý:</strong> Sản phẩm chỉ hỗ trợ tài khoản Google có <strong>region Việt Nam</strong>.</blockquote><h1><strong>Quy trình nhận hàng</strong></h1><ol><li>Thanh toán thành công trên website.</li><li>Hệ thống tự động gửi <strong>link mời Family YouTube Premium</strong> ngay lập tức.</li><li>Bạn bấm “Chấp nhận” để vào Family → Tài khoản được nâng cấp lên Premium ngay.</li><li>Hoàn tất.</li></ol><p><strong>Thời gian xử lý: Tự động – trong vài giây.</strong></p><p><br></p><h1><strong>Lợi ích khi dùng YouTube Premium</strong></h1><h2><strong>1. Xem video hoàn toàn không quảng cáo</strong></h2><ul><li>Không bị quảng cáo xen giữa video.</li><li>Hỗ trợ trên tất cả thiết bị đăng nhập Google: điện thoại, máy tính, Smart TV, tablet…</li></ul><h2><strong>2. Dùng YouTube Music Premium miễn phí</strong></h2><p>Bao gồm đầy đủ các tính năng:</p><ul><li>Nghe nhạc không quảng cáo.</li><li>Tải nhạc/podcast để nghe offline.</li><li>Phát nhạc nền (khi tắt màn hình hoặc chuyển ứng dụng).</li><li>Chế độ chỉ âm thanh giúp tiết kiệm dữ liệu.</li></ul><h2><strong>3. Tải video để xem offline</strong></h2><ul><li>Tải video &amp; playlist trong ứng dụng YouTube, YouTube Music, YouTube Kids.</li><li>Có “Tải xuống thông minh” tự đề xuất nội dung.</li></ul><h2><strong>4. Quyền sử dụng chính chủ trong 12 tháng</strong></h2><p>Gói được gia hạn trực tiếp vào tài khoản Google của bạn, <strong>không dùng tài khoản lạ</strong>, đảm bảo riêng tư tuyệt đối.</p><p><br></p><h1><strong>Chính sách bảo hành – 12 tháng</strong></h1><p>Chúng tôi hỗ trợ bảo hành trọn thời gian sử dụng, ngoại trừ các trường hợp:</p><ul><li>Khách tự rời Family trong thời gian sử dụng.</li><li>Tài khoản vi phạm chính sách Google/YouTube.</li><li>Dùng email G-Suite / email do công ty–trường học quản lý.</li><li>Region tài khoản không phải Việt Nam.</li></ul><p>Link xem chi tiết bảo hành được cung cấp sau khi mua hàng.</p><p><br></p><h1><strong>Câu hỏi thường gặp (FAQ)</strong></h1><h3><strong>1. Tôi dùng tài khoản của mình hay tài khoản shop tạo sẵn?</strong></h3><p>Bạn dùng <strong>tài khoản Google của chính bạn</strong>. Shop chỉ gửi link để bạn vào Family và tự nâng cấp Premium.</p><h3><strong>2. Sau khi mua tôi phải làm gì?</strong></h3><p>Chỉ cần bấm vào <strong>link invite</strong> gửi vào email/website → chọn \"Chấp nhận\" → Premium kích hoạt ngay.</p><h3><strong>3. Khi hết hạn 1 năm, tôi có thể gia hạn tiếp không?</strong></h3><p>Có. Bạn có thể tiếp tục mua gói gia hạn.</p><p>Tuy nhiên Google giới hạn: <strong>mỗi năm chỉ được tham gia tối đa 2 Family khác nhau</strong>.</p><p>→ Vì vậy gói 1 năm là ổn định và tối ưu nhất.</p><h3><strong>4. Lỡ thoát Family hoặc đăng nhập nhầm tài khoản thì sao?</strong></h3><p>Bạn có thể liên hệ CSKH, shop hỗ trợ đổi lại tài khoản khác.</p><h3><strong>5. Một tài khoản Premium đăng nhập được bao nhiêu thiết bị?</strong></h3><p>Google không giới hạn cụ thể. Thực tế vẫn dùng tốt trên <strong>5 thiết bị cùng lúc</strong>.</p><h3><strong>6. Người khác trong Family có xem được thông tin tài khoản Google của tôi không?</strong></h3><p>Không. Thông tin tài khoản Google của bạn luôn <strong>bảo mật tuyệt đối</strong>.</p><h3><strong>7. Tôi đang ở nước ngoài có tham gia được không?</strong></h3><p>Không. Sản phẩm chỉ hỗ trợ tài khoản Google <strong>khu vực Việt Nam</strong>.</p><h3><strong>8. Email công ty / trường học có dùng được không?</strong></h3><p>Không. Email G-Suite do tổ chức quản lý <strong>không thể tham gia Family</strong>.</p><p>Vui lòng dùng email cá nhân (Gmail thông thường).</p><h3><strong>9. Thời gian sử dụng tính từ khi nào?</strong></h3><p>Thời gian tính từ <strong>lúc bạn thanh toán</strong>, nên hãy tham gia Family càng sớm càng tốt để không mất ngày.</p><h3><strong>10. Tôi có thể đổi sang tài khoản Google khác khi kích hoạt không?</strong></h3><p>Có. Khi mở link invite → chọn <strong>“Chuyển đổi tài khoản”</strong> để dùng tài khoản mong muốn.</p><h3><strong>11. Tôi không dùng YouTube Music thì có được giảm giá không?</strong></h3><p>Không. YouTube Music là tính năng đi kèm mặc định của gói Premium.</p><h3><strong>12. Playlist nhạc trên YouTube có đồng bộ sang YouTube Music không?</strong></h3><p>Có. Playlist của bạn sẽ tự đồng bộ.</p><h3><strong>13. YouTube Music có thể nghe trên nhiều thiết bị cùng lúc không?</strong></h3><p>Có. YouTube Music <strong>không giới hạn số thiết bị phát</strong>, khác với Spotify.</p>', 5.00, '<p>Phát nhạc nền Tải video Không quảng cáo</p>', 'email_only', '', 210000.00, 195000.00, 43, 7, 'active', 1, '2025-10-31 04:08:41', '2026-01-20 17:56:03'),
(32, 15, 'Disney+ Hotstar', 'disney-hotstar', '', '<h1>⭐ Giới thiệu sản phẩm Disney+ Hotstar (Premium)</h1><h2>📌 Disney+ Hotstar là gì?</h2><p><strong>Disney+ Hotstar</strong> là nền tảng xem phim trực tuyến thuộc Disney, sở hữu kho nội dung cực lớn từ:</p><ul><li><strong>Marvel – Star Wars – Pixar – Disney – National Geographic</strong></li><li>Hàng ngàn bộ phim điện ảnh, series độc quyền</li><li>Phim hoạt hình Disney/Pixar chất lượng cao</li><li>TV shows &amp; nội dung giải trí quốc tế</li></ul><p>Gói này cực phù hợp cho gia đình và mọi đối tượng thích phim bom tấn, hoạt hình và series độc quyền.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>tài khoản Disney+ Hotstar Premium</strong> (quốc gia phù hợp, xem ổn định).</li><li>Bạn sẽ nhận <strong>Email + Mật khẩu</strong> để đăng nhập ngay lập tức.</li><li>Hỗ trợ xem trên TV, điện thoại, máy tính.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Tải ứng dụng Disney+ hoặc Disney+ Hotstar</li><li>Đăng nhập bằng tài khoản được cung cấp</li><li>Chọn profile → xem phim mượt không giới hạn</li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao tài khoản ngay sau thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email đăng nhập</li><li>Mật khẩu</li><li>Hướng dẫn chi tiết</li><li>CSKH hỗ trợ trong toàn bộ thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Android</li><li>iOS</li><li>Smart TV (Android TV, Samsung, LG…)</li><li>Laptop / PC (trình duyệt)</li><li>TV Box / Fire TV</li><li>Tablet</li></ul><h3><strong>6. Khu vực sử dụng</strong></h3><p>✔ Hoạt động theo gói (Việt Nam, Singapore, Ấn Độ… tùy shop).</p><p> ✔ Có hướng dẫn đổi vùng nếu cần.</p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng tùy gói.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Lỗi đăng nhập → cấp tài khoản mới ngay.</li><li>Khoá tài khoản → hỗ trợ đổi tài khoản.</li><li>Không khắc phục được → hoàn tiền phần còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop không bảo hành nếu:</p><ul><li>Khách tự đổi email/mật khẩu</li><li>Chia sẻ tài khoản cho quá nhiều người</li><li>Dùng sai khu vực gây lỗi</li><li>Sử dụng trên thiết bị không tương thích</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Disney+ Hotstar khác gì Disney+ thường?</strong></h3><ul><li><strong>Hotstar</strong>: có thêm nội dung khu vực, phim Châu Á, TV shows, đôi khi có giá rẻ hơn</li><li><strong>Disney+</strong>: thuần nội dung Disney/Marvel/Pixar/Star Wars</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><p>Tùy gói, thường:</p><ul><li><strong>Đăng nhập nhiều thiết bị</strong></li><li><strong>Xem tối đa 1–2 thiết bị cùng lúc</strong></li></ul><h3><strong>3. Có hỗ trợ xem 4K không?</strong></h3><p>Có, nếu gói Premium và thiết bị hỗ trợ.</p><h3><strong>4. Dùng ở nước ngoài được không?</strong></h3><p>Phụ thuộc gói.</p><p> Nếu không xem được → dùng thêm <strong>VPN</strong> (NordVPN/Surfshark/ProtonVPN).</p><h3><strong>5. Tài khoản có đổi mật khẩu được không?</strong></h3><p>Không, để đảm bảo bảo hành.</p><h3><strong>6. Có xem được Marvel, Star Wars không?</strong></h3><p>Có đầy đủ toàn bộ vũ trụ <strong>MCU</strong>, <strong>Star Wars</strong>, <strong>Pixar</strong>, <strong>Disney Originals</strong>, v.v.</p><h3><strong>7. Có phụ đề tiếng Việt không?</strong></h3><p>Có — hầu hết nội dung đều hỗ trợ phụ đề Việt.</p><p><br></p><h1>🎯 Ưu điểm khi mua Disney+ Hotstar tại Shop</h1><ul><li>⭐ Giá tốt hơn đăng ký trực tiếp</li><li>⚡ Giao tài khoản ngay lập tức</li><li>🛡 Bảo hành đầy đủ</li><li>📱 Hỗ trợ đa nền tảng (TV – Mobile – PC)</li><li>🎬 Kho phim Disney/Marvel/Pixar cực lớn</li><li>🎧 Xem phim chất lượng cao, phụ đề Việt chuẩn</li><li>🔥 Xem không giới hạn, tải ngoại tuyến tùy gói</li></ul><p><br></p>', 0.00, '<p>Xem Marvel, Star Wars 4K HDR Không quảng cáo</p>', 'account', '/public/images/products/product-32-1763711038.jpg', 850000.00, 750000.00, 28, 2, 'inactive', 0, '2025-10-31 04:08:41', '2026-01-20 17:53:34'),
(35, 2, 'Valorant Acc Level 20+', 'valorant-acc-20', '', '<p>Tài khoản Valorant đủ điều kiện rank</p>', 0.00, '<p>Skin súng đẹp Chưa bị khóa Đăng nhập Riot</p>', 'account', '/public/images/products/product-35-1763712492.jpg', 180000.00, 150000.00, 30, 1, 'inactive', 0, '2025-10-31 04:08:41', '2025-12-12 08:23:30'),
(37, 2, 'Steam Random Key', 'steam-random-key', '', '<p>Mua key game Steam ngẫu nhiên</p>', 0.00, '<p>100% key hợp lệ Trò chơi ngẫu nhiên từ 50-500k</p>', 'email_only', '/public/images/products/product-37-1763711785.jpg', 50000.00, 40000.00, 200, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 08:23:26'),
(38, 3, 'NordVPN sử dụng 2 thiết bị', 'nordvpn-12-thang', '', '<h1>⭐ Giới thiệu sản phẩm NordVPN (Tài khoản VPN bảo mật tốc độ cao)</h1><h2>📌 NordVPN là gì?</h2><p><strong>NordVPN</strong> là dịch vụ VPN hàng đầu thế giới, nổi tiếng nhờ <strong>tốc độ nhanh – bảo mật mạnh – vượt chặn tốt</strong>. Khi sử dụng NordVPN, bạn có thể truy cập Internet an toàn, ẩn danh IP, mở khóa nội dung bị giới hạn theo khu vực và bảo vệ mọi kết nối của mình trên mọi thiết bị.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>tài khoản NordVPN Premium</strong> (gói Full Premium / gói 1 năm / gói 2 năm… tùy sản phẩm).</li><li>Sau khi mua, bạn sẽ được cấp <strong>Email + Mật khẩu đăng nhập</strong> vào ứng dụng NordVPN.</li></ul><h3><strong>2. Hướng dẫn sử dụng</strong></h3><ul><li>Tải ứng dụng NordVPN cho: Windows, macOS, iOS, Android, Linux, Chrome, Firefox.</li><li>Đăng nhập bằng thông tin shop cung cấp.</li><li>Chọn quốc gia → Kết nối → Sử dụng ngay.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Tự động – nhận hàng ngay sau thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><p>Bạn sẽ nhận được:</p><ul><li>Email đăng nhập NordVPN</li><li>Mật khẩu</li><li>Hướng dẫn cài đặt và sử dụng chi tiết</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Máy tính Windows / Mac</li><li>Điện thoại Android / iPhone</li><li>Máy tính bảng</li><li>Router, Smart TV (tùy model)</li><li>Extension trên trình duyệt Chrome / Firefox</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Sử dụng <strong>toàn cầu</strong> – không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày hoặc 3–6–12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Lỗi không đăng nhập được → cấp thông tin mới ngay.</li><li>Nếu không còn tài khoản thay thế → hoàn tiền theo thời gian còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop từ chối bảo hành nếu:</p><ul><li>Bạn tự đổi email/mật khẩu tài khoản.</li><li>Dùng tài khoản sai mục đích, chia sẻ cho người khác.</li><li>Tác động trái phép đến cấu hình bảo mật, 2FA, hoặc thay đổi gói dịch vụ.</li><li>Đăng nhập bất thường gây khóa tài khoản (đăng nhập quá nhiều thiết bị cùng lúc…).</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. NordVPN dùng để làm gì?</strong></h3><ul><li>Ẩn địa chỉ IP và vị trí thật</li><li>Truy cập nội dung bị chặn theo quốc gia (Netflix, YouTube, TikTok, Facebook…)</li><li>Tránh bị theo dõi, bảo vệ quyền riêng tư</li><li>Bảo vệ khi dùng WiFi công cộng</li><li>Tăng tốc độ truy cập server quốc tế</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><p>Tùy gói, thông thường dùng được <strong>tối đa 6 thiết bị</strong> cùng lúc.</p><h3><strong>3. Dùng ở nước ngoài được không?</strong></h3><p>Có. NordVPN hoạt động <strong>toàn cầu</strong>, không bị giới hạn.</p><h3><strong>4. Tốc độ có nhanh không?</strong></h3><p>Rất nhanh nhờ công nghệ <strong>NordLynx / WireGuard</strong>.</p><p> Tốc độ xem phim 4K, chơi game, tải file đều ổn định.</p><h3><strong>5. Có xem được Netflix US/UK/Japan không?</strong></h3><p>Có, NordVPN hỗ trợ <strong>mở khóa nhiều thư viện Netflix quốc tế</strong>.</p><h3><strong>6. Có bị giảm ping khi chơi game không?</strong></h3><p>Có thể cải thiện khi kết nối server gần hoặc ổn định hơn tuyến quốc tế.</p><h3><strong>7. Tài khoản này có dùng được trên Router?</strong></h3><p>Có, tùy theo từng model Router hỗ trợ OpenVPN/WireGuard.</p><h3><strong>8. Tôi có bị lộ thông tin không?</strong></h3><p>Không. NordVPN sử dụng mã hóa cấp quân sự AES-256 và không lưu log người dùng.</p><h3><strong>9. Nếu bị yêu cầu xác minh email thì sao?</strong></h3><p>Bạn chỉ cần liên hệ CSKH để được hỗ trợ đổi tài khoản mới.</p><h3><strong>10. Có hỗ trợ đổi IP theo quốc gia không?</strong></h3><p>Có hơn <strong>60+ quốc gia</strong> để lựa chọn như: US, UK, Canada, Japan, Singapore, Vietnam,...</p><p><br></p><h1>🎯 Ưu điểm khi mua NordVPN tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký chính hãng</li><li>⚡ Nhận tài khoản ngay lập tức</li><li>🔒 Bảo mật, tốc độ nhanh, ổn định</li><li>🛡 Bảo hành đầy đủ</li><li>🌍 Vượt chặn mọi nền tảng: Facebook, TikTok, Netflix, ChatGPT…</li><li>🔄 Sử dụng đa thiết bị – tiện lợi</li></ul><p><br></p>', 0.00, '<p>Server toàn cầu Bảo mật tuyệt đối Không giới hạn thiết bị</p>', 'account', '/public/images/products/product-38-1763667415.jpg', 320000.00, 290000.00, 25, 2, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 08:23:22'),
(39, 3, 'Surfshark VPN', 'surfshark', '', '<h1>⭐ Giới thiệu sản phẩm Surfshark VPN (Unlimited Devices)</h1><h2>📌 Surfshark là gì?</h2><p><strong>Surfshark VPN</strong> là dịch vụ VPN cao cấp nổi tiếng với <strong>tốc độ nhanh – bảo mật mạnh – giá tốt – không giới hạn số thiết bị</strong>. Đây là một trong những VPN được ưa chuộng nhất để truy cập nội dung quốc tế, vượt chặn Facebook/TikTok/Netflix, bảo mật WiFi công cộng và ẩn danh IP.</p><p>Surfshark được đánh giá là một trong những VPN <strong>ổn định – đa năng – đáng dùng nhất</strong> hiện nay.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>tài khoản Surfshark VPN Premium</strong> (gói 1 năm / 2 năm).</li><li>Bạn sẽ được cấp <strong>Email + Mật khẩu đăng nhập</strong> vào ứng dụng Surfshark.</li><li>Full tính năng Surfshark One/One+ tuỳ gói.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Tải ứng dụng Surfshark (Windows, macOS, iOS, Android, Linux…).</li><li>Đăng nhập bằng thông tin shop cung cấp.</li><li>Chọn server → Kết nối → Dùng ngay.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao hàng ngay sau thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email đăng nhập Surfshark</li><li>Mật khẩu</li><li>Hướng dẫn chi tiết</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows / macOS</li><li>Android / iOS</li><li>Linux</li><li>Smart TV</li><li>Router</li><li>Trình duyệt Chrome / Firefox (Extension)</li></ul><h3><strong>6. Khu vực sử dụng</strong></h3><p>✔ Dùng được <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3–6–12 tháng tùy gói mua.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Không đăng nhập được → hỗ trợ đổi tài khoản ngay</li><li>Mất quyền truy cập → cấp mới</li><li>Không khôi phục được → hoàn tiền phần còn lại</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành trong trường hợp:</p><ul><li>Tự đổi email/mật khẩu</li><li>Đăng nhập quá nhiều thiết bị gây khóa tài khoản</li><li>Lạm dụng VPN cho mục đích bất hợp pháp</li><li>Chia sẻ tài khoản công khai</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Surfshark mạnh hơn NordVPN hay HMA không?</strong></h3><ul><li><strong>Không giới hạn số thiết bị</strong> → Surfshark vượt trội</li><li><strong>Bảo mật &amp; tốc độ cao</strong> → ngang hàng NordVPN</li><li><strong>Đổi IP, vượt chặn tốt</strong> → mạnh hơn HMA ở một số quốc gia</li><li> Mỗi VPN có điểm mạnh riêng, nhưng Surfshark cực đáng dùng cho gia đình &amp; đa thiết bị.</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><p>✔ Không giới hạn!</p><p> Bạn có thể dùng trên <strong>10–20 thiết bị</strong> tùy ý.</p><h3><strong>3. Surfshark có xem được Netflix US/UK không?</strong></h3><p>Có. Surfshark mở khóa <strong>nhiều thư viện Netflix quốc tế</strong> + Disney+, Hulu, iPlayer…</p><h3><strong>4. Dùng được ở nước ngoài không?</strong></h3><p>Có, hoạt động toàn cầu.</p><h3><strong>5. Tốc độ có nhanh không?</strong></h3><p>Rất nhanh nhờ sử dụng giao thức <strong>WireGuard</strong> và hệ thống server tối ưu.</p><h3><strong>6. Surfshark có No-log không?</strong></h3><p>Có chính sách <strong>No-Logs</strong> — không lưu bất kỳ dữ liệu người dùng.</p><h3><strong>7. Surfshark có đổi IP tự động không?</strong></h3><p>Có tính năng <strong>Rotating IP</strong> đổi IP liên tục mà không ngắt kết nối.</p><h3><strong>8. Surfshark có chặn quảng cáo không?</strong></h3><p>Có tính năng <strong>CleanWeb</strong> chặn quảng cáo, popup, malware rất mạnh.</p><p><br></p><h1>🎯 Ưu điểm khi mua Surfshark tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp</li><li>⚡ Nhận tài khoản ngay</li><li>🔒 Không giới hạn thiết bị</li><li>🌍 Server mạnh toàn cầu</li><li>🛡 Bảo hành đầy đủ</li><li>🚀 Tốc độ cao – ổn định</li><li>🔥 Vượt chặn mọi trang: Facebook, TikTok, Netflix, YouTube…</li><li>🧹 Chặn quảng cáo, web độc hại với CleanWeb</li></ul><p><br></p>', 0.00, '<p>Ẩn IP Bảo mật cao Tốc độ cao</p>', 'account', '/public/images/products/product-39-1763710139.jpg', 280000.00, 250000.00, 30, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 08:22:53'),
(40, 3, 'ExpressVPN', 'expressvpn', '', '<h1>⭐ Giới thiệu sản phẩm ExpressVPN (Premium)</h1><h2>📌 ExpressVPN là gì?</h2><p><strong>ExpressVPN</strong> là một trong những dịch vụ VPN cao cấp nhất thế giới, nổi tiếng với <strong>tốc độ siêu nhanh – bảo mật cực mạnh – ổn định tuyệt đối</strong> và khả năng vượt chặn nội dung tốt nhất hiện nay.</p><p> Được sử dụng bởi hơn 180 quốc gia, ExpressVPN phù hợp cho người cần VPN <strong>tốc độ cao, xem video mượt, chơi game ổn định và bảo mật tuyệt đối</strong>.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>tài khoản ExpressVPN Premium bản quyền</strong> (gói 6–12 tháng tùy shop).</li><li>Bạn sẽ được cấp <strong>Email + Mật khẩu</strong> để đăng nhập và sử dụng đầy đủ tính năng.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Tải ExpressVPN trên Windows / Mac / iOS / Android / TV…</li><li>Đăng nhập bằng tài khoản được cung cấp</li><li>Chọn server → Connect → Dùng ngay</li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Nhận tài khoản ngay sau thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email đăng nhập</li><li>Mật khẩu</li><li>Hướng dẫn cài đặt chi tiết</li><li>Hỗ trợ kỹ thuật xuyên suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows / macOS</li><li>Android / iOS</li><li>Linux</li><li>Smart TV</li><li>Apple TV / Fire TV / Android Box</li><li>Router</li><li>Extension Chrome / Firefox / Edge</li></ul><h3><strong>6. Khu vực sử dụng</strong></h3><p>✔ Sử dụng <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Không đăng nhập được → đổi tài khoản ngay</li><li>Tài khoản bị khóa → cấp tài khoản mới</li><li>Không thể khắc phục → hoàn tiền phần còn lại</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành nếu:</p><ul><li>Bạn tự đổi email/mật khẩu</li><li>Đăng nhập trên quá nhiều thiết bị gây khóa</li><li>Vi phạm chính sách ExpressVPN</li><li>Chia sẻ tài khoản công khai</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. ExpressVPN mạnh hơn NordVPN hay Surfshark không?</strong></h3><ul><li><strong>Tốc độ quốc tế cực nhanh</strong> → ExpressVPN mạnh nhất</li><li><strong>Bảo mật cao</strong> → ngang hàng ProtonVPN</li><li><strong>Không giới hạn thiết bị?</strong> → Surfshark mạnh hơn</li><li> ExpressVPN phù hợp nhất cho <strong>xem phim – chơi game – tốc độ cao</strong>.</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><ul><li><strong>5 thiết bị cùng lúc</strong> (tùy gói).</li></ul><h3><strong>3. ExpressVPN có xem được Netflix US / UK / Japan không?</strong></h3><p>Có, được đánh giá <strong>mượt và ổn định nhất</strong> trong các VPN.</p><h3><strong>4. ExpressVPN dùng được ở nước ngoài không?</strong></h3><p>Có, hoạt động tại 180+ quốc gia.</p><h3><strong>5. ExpressVPN có No-Log không?</strong></h3><p>Có — chính sách <strong>No-Logs Strict</strong>, được kiểm toán độc lập.</p><h3><strong>6. Giao thức kết nối dùng gì?</strong></h3><ul><li>Lightway (siêu nhanh, độc quyền ExpressVPN)</li><li>OpenVPN</li><li>IKEv2</li></ul><h3><strong>7. ExpressVPN có Kill Switch không?</strong></h3><p>Có — tính năng Network Lock rất mạnh.</p><h3><strong>8. Tốc độ khi chơi game có tốt không?</strong></h3><p>Rất tốt, ping ổn định, phù hợp cả game quốc tế.</p><p><br></p><h1>🎯 Ưu điểm khi mua ExpressVPN tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp</li><li>⚡ Nhận tài khoản ngay</li><li>🚀 Tốc độ cực nhanh – xem phim 4K mượt</li><li>🔒 Bảo mật mạnh – No-Logs chuẩn quốc tế</li><li>🌍 Server phủ hơn 90+ quốc gia</li><li>🛡 Bảo hành đầy đủ</li><li>🖥 Hỗ trợ nhiều nền tảng: PC – Mobile – TV – Router</li></ul><p><br></p>', 0.00, '<p>Không giới hạn băng thông Server khắp thế giới</p>', 'account', '/public/images/products/product-40-1763710564.jpg', 450000.00, 399000.00, 15, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 09:12:06'),
(41, 3, 'ProtonVPN', 'protonvpn', '', '<h1>⭐ Giới thiệu sản phẩm ProtonVPN (Plus / Unlimited)</h1><h2>📌 ProtonVPN là gì?</h2><p><strong>ProtonVPN</strong> là dịch vụ VPN cao cấp đến từ Thụy Sĩ – nổi tiếng với <strong>mức độ bảo mật hàng đầu thế giới</strong>, tốc độ nhanh, không lưu log và hạ tầng được xây dựng theo tiêu chuẩn an ninh quân sự.</p><p>ProtonVPN đặc biệt phù hợp cho người dùng cần:</p><ul><li>Vượt chặn nội dung quốc tế</li><li>Ẩn danh IP tuyệt đối</li><li>Bảo mật kết nối khi dùng WiFi công cộng</li><li>Truy cập server tốc độ cao cho Netflix, YouTube, TikTok…</li><li>Ưu tiên bảo mật và quyền riêng tư</li></ul><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>ProtonVPN Plus/Unlimited bản quyền</strong>, dùng full server &amp; tốc độ tối đa.</li><li>Bạn sẽ được cấp <strong>Email + Mật khẩu</strong> để đăng nhập ProtonVPN.</li><li>Sử dụng được toàn bộ tính năng của gói Premium.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Tải ứng dụng ProtonVPN (Windows, macOS, Android, iOS, Linux).</li><li>Đăng nhập tài khoản được cung cấp.</li><li>Chọn server → Connect → Dùng ngay.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao tài khoản ngay sau thanh toán (2–5 phút).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email đăng nhập</li><li>Mật khẩu</li><li>Hướng dẫn cài đặt ứng dụng đầy đủ</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows</li><li>macOS</li><li>Linux</li><li>iOS</li><li>Android</li><li>TV / Router (OpenVPN, WireGuard)</li><li>Trình duyệt hỗ trợ extension</li></ul><h3><strong>6. Khu vực sử dụng</strong></h3><p>✔ Hoạt động <strong>toàn cầu</strong>, không bị giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3–6–12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Lỗi đăng nhập → cấp tài khoản thay thế</li><li>Khóa bất thường → hỗ trợ đổi ngay</li><li>Không thể khắc phục → hoàn tiền phần còn lại</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành nếu:</p><ul><li>Bạn tự đổi Email/Mật khẩu</li><li>Đăng nhập quá nhiều thiết bị gây khóa</li><li>Dùng tài khoản để spam/tải nội dung vi phạm pháp luật</li><li>Chia sẻ tài khoản cho người khác</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. ProtonVPN có mạnh hơn NordVPN/Surfshark không?</strong></h3><ul><li><strong>Mạnh nhất về bảo mật &amp; quyền riêng tư</strong></li><li><strong>Không nhanh bằng NordVPN</strong>, nhưng rất ổn định</li><li><strong>Ít server hơn</strong>, nhưng chất lượng cao và không quá tải</li><li>Surfshark mạnh về unlimited devices; Proton mạnh về bảo mật, chống theo dõi</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><ul><li>Thông thường <strong>10 thiết bị đồng thời</strong> (tùy gói Pro/Unlimited).</li></ul><h3><strong>3. ProtonVPN có đầy đủ server Netflix không?</strong></h3><p>Có. ProtonVPN mở khóa Netflix/Disney+/Hulu… ở nhiều khu vực.</p><h3><strong>4. Có dùng được ở nước ngoài không?</strong></h3><p>Có, dùng toàn cầu.</p><h3><strong>5. ProtonVPN có No-Log không?</strong></h3><p>Có — <strong>No-Logs chuẩn Thụy Sĩ</strong>, nằm ngoài Liên minh Five Eyes.</p><h3><strong>6. ProtonVPN có dùng giao thức WireGuard không?</strong></h3><p>Có, giúp tốc độ rất nhanh và ổn định.</p><h3><strong>7. ProtonVPN có Auto Kill Switch không?</strong></h3><p>Có — hỗ trợ Kill Switch &amp; Always-On VPN.</p><p><br></p><h1>🎯 Ưu điểm khi mua ProtonVPN tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp từ Proton</li><li>⚡ Giao tài khoản ngay</li><li>🔒 Bảo mật cấp cao – không lưu log</li><li>🌍 Server phủ rộng nhiều quốc gia</li><li>🚀 Tốc độ ổn định, xem Netflix mượt</li><li>🛡 Bảo hành đầy đủ</li><li>📱 Dùng được trên nhiều thiết bị</li><li>🔥 Ẩn danh tuyệt đối – vượt chặn mạnh</li></ul>', 0.00, '<p>Server châu Âu Không quảng cáo Tốc độ cao</p>', 'account', '/public/images/products/product-41-1765530660-693bdc2405aea.png', 600000.00, 550000.00, 10, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 09:11:00'),
(42, 4, 'Google Drive 2TB kèm theo Gemini Pro', 'google-drive-2tb-gemini', '', '<h1>⭐ Giới thiệu sản phẩm Google Drive 2TB (Google One 2TB)</h1><h2>📌 Google Drive 2TB là gì?</h2><p><strong>Google Drive 2TB (Google One 2TB)</strong> là gói lưu trữ cao cấp của Google, giúp bạn mở rộng dung lượng Drive/Gmail/Google Photos lên tới <strong>2TB</strong>. Đây là giải pháp tối ưu để lưu ảnh, video, tài liệu công việc, sao lưu điện thoại Android và đồng bộ nhiều thiết bị với tốc độ cực ổn định.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>gói Google One 2TB chính chủ</strong>, được kích hoạt thông qua <strong>Family Google</strong>.</li><li>Sau khi mua, bạn sẽ được <strong>mời vào nhóm Family</strong> để sử dụng trọn vẹn 2TB dung lượng.</li><li>Bạn <strong>không cần cung cấp mật khẩu</strong>, chỉ cần <strong>Email Gmail</strong>.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Cung cấp Gmail của bạn</li><li>Nhận lời mời Family Google</li><li>Chấp nhận → tài khoản tự động nâng cấp thành <strong>Google One 2TB</strong></li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao hàng ngay sau khi thanh toán (2–5 phút).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email mời vào nhóm Google One</li><li>Xác nhận dung lượng Google Drive tăng lên 2TB</li><li>Hướng dẫn sử dụng Google Drive, Photos, Gmail</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Android</li><li>iOS</li><li>Windows / macOS</li><li>Chromebook</li><li>Web browser</li><li>Tự động đồng bộ ảnh/video trên điện thoại</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Sử dụng <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu bị mất quyền Family → được mời lại ngay.</li><li>Nếu không thể mời lại → hoàn tiền theo thời gian còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop không bảo hành nếu khách:</p><ul><li>Tự rời nhóm Family</li><li>Thay đổi quốc gia tài khoản gây mất hiệu lực gói</li><li>Dùng tài khoản sai mục đích (spam, upload nội dung vi phạm bản quyền…)</li><li>Can thiệp bất thường vào cài đặt quản trị Family</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Google Drive 2TB dùng để làm gì?</strong></h3><ul><li>Lưu trữ &amp; sao lưu dữ liệu lớn</li><li>Backup ảnh/video chất lượng cao</li><li>Đồng bộ nhiều thiết bị</li><li>Tự động sao lưu điện thoại Android</li><li>Mở rộng dung lượng Gmail/Google Photos</li><li>Chia sẻ file dung lượng lớn</li></ul><h3><strong>2. Tôi cần cung cấp gì để kích hoạt?</strong></h3><p>Chỉ cần <strong>Email Gmail</strong>, không cần mật khẩu.</p><h3><strong>3. Sau khi tham gia Family Google, dữ liệu cũ của tôi có bị mất không?</strong></h3><p>Không. Mọi dữ liệu Drive, Photos, Gmail được giữ nguyên.</p><h3><strong>4. Sử dụng được ở nước ngoài không?</strong></h3><p>Có. Drive và Google One hoạt động toàn cầu.</p><h3><strong>5. Nếu tôi rời nhóm thì sao?</strong></h3><p>Dữ liệu vẫn còn, nhưng dung lượng trở về 15GB → có thể bị vượt quota.</p><h3><strong>6. Drive 2TB có xem được ảnh/video trực tiếp không?</strong></h3><p>Có, Drive hỗ trợ xem/tải trực tiếp với tốc độ nhanh.</p><h3><strong>7. Tôi có thể backup iPhone không?</strong></h3><p>Có, backup ảnh/video qua <strong>Google Photos</strong> rất nhanh và ổn định.</p><h3><strong>8. Google One 2TB có bao gồm VPN của Google không?</strong></h3><p>Không, VPN chỉ có ở một số quốc gia, không phải tất cả tài khoản.</p><h3><strong>9. Tôi đổi quốc gia Google Play có ảnh hưởng không?</strong></h3><p>Có thể ảnh hưởng đến gói Family — hãy hỏi CSKH trước khi đổi.</p><h3><strong>10. Tôi gặp lỗi không nhận được lời mời Family thì làm sao?</strong></h3><p>CSKH sẽ hỗ trợ mời lại hoặc cấp nhóm khác.</p><p><br></p><h1>🎯 Ưu điểm khi mua Google Drive 2TB tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký Google One trực tiếp</li><li>⚡ Giao hàng nhanh – kích hoạt trong vài phút</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ</li><li>📂 Đồng bộ dữ liệu cực nhanh</li><li>🌍 Hoạt động toàn cầu</li><li>💾 Lưu trữ ảnh/video thoải mái – không lo đầy bộ nhớ</li></ul><p><br></p>', 0.00, '<p>Dùng ổn định Chia sẻ lifetime Backup an toàn</p>', 'email_only', '/public/images/products/product-42-1763668051.jpg', 350000.00, 320000.00, 80, 2, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 08:23:17'),
(43, 42, 'OneDrive 1TB', 'onedrive-1tb', '', '<h1>⭐ Giới thiệu sản phẩm OneDrive (1TB – Microsoft 365)</h1><h2>📌 OneDrive là gì?</h2><p><strong>OneDrive</strong> là dịch vụ lưu trữ đám mây của Microsoft, giúp bạn sao lưu – đồng bộ – chia sẻ dữ liệu tốc độ cao trên mọi thiết bị. Khi nâng cấp OneDrive (kèm theo Microsoft 365), bạn sẽ có <strong>1TB dung lượng</strong> để lưu ảnh, video, tài liệu, file công việc… cùng đầy đủ bộ ứng dụng Office (Word, Excel, PowerPoint…) bản quyền.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>gói Onedrive 1TB</strong> kèm bộ <strong>Microsoft 365 chính chủ</strong>.</li><li>Khách hàng sẽ được <strong>mời vào nhóm Family Microsoft</strong> để sử dụng toàn bộ dung lượng và ứng dụng bản quyền.</li><li>Chỉ cần cung cấp <strong>Email Microsoft (Outlook/Hotmail/Live)</strong> – KHÔNG cần mật khẩu.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Shop mời bạn vào nhóm Family.</li><li>Chấp nhận lời mời → tài khoản tự động kích hoạt <strong>OneDrive 1TB + Microsoft 365</strong>.</li><li>Bạn có thể dùng ngay Word, Excel, PowerPoint trên web/app + đồng bộ file 1TB.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao hàng ngay sau thanh toán (2–5 phút).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><p>Bạn nhận được:</p><ul><li>Email mời vào nhóm Family</li><li>Xác nhận kích hoạt Microsoft 365 &amp; OneDrive 1TB</li><li>Hướng dẫn sử dụng chi tiết</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows</li><li>macOS</li><li>Android</li><li>iOS</li><li>Web OneDrive.com</li><li>Ứng dụng OneDrive đồng bộ file tự động desktop</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Dùng được <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Mất quyền Family → mời lại ngay.</li><li>Không còn Family thay thế → hoàn tiền theo thời gian còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop không bảo hành nếu khách:</p><ul><li>Tự rời nhóm Family</li><li>Thay đổi vùng quốc gia tài khoản gây mất hiệu lực</li><li>Share tài khoản sai cách hoặc vi phạm chính sách của Microsoft</li><li>Dùng vào mục đích bất hợp pháp, upload nội dung nhạy cảm/vi phạm bản quyền</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. OneDrive 1TB dùng để làm gì?</strong></h3><ul><li>Lưu trữ dữ liệu dung lượng lớn</li><li>Sao lưu tự động ảnh/video trên điện thoại</li><li>Đồng bộ file giữa máy tính &amp; điện thoại</li><li>Chia sẻ dữ liệu dung lượng lớn</li><li>Làm việc nhóm thời gian thực qua Office Online</li></ul><h3><strong>2. Tôi cần cung cấp gì để kích hoạt?</strong></h3><p>Chỉ cần <strong>Email Microsoft</strong> (Outlook/Hotmail).</p><h3><strong>3. Sau khi kích hoạt thì dữ liệu cũ có bị mất không?</strong></h3><p>Không. Mọi dữ liệu OneDrive cũ được giữ nguyên.</p><h3><strong>4. Tôi có dùng Office bản quyền được không?</strong></h3><p>Có. Gói này bao gồm:</p><ul><li>Word</li><li>Excel</li><li>PowerPoint</li><li>Outlook</li><li>OneNote</li><li>Access (PC)</li><li>Publisher (PC)</li></ul><h3><strong>5. Tài khoản dùng được ở nước ngoài không?</strong></h3><p>Có. OneDrive là dịch vụ toàn cầu.</p><h3><strong>6. Tôi đổi máy hoặc reset máy có ảnh hưởng không?</strong></h3><p>Không. Chỉ cần đăng nhập lại OneDrive là toàn bộ dữ liệu đồng bộ lại.</p><h3><strong>7. Rời nhóm Family thì dữ liệu còn không?</strong></h3><p>Có, nhưng dung lượng sẽ về 5GB → nếu vượt quota sẽ không upload thêm được.</p><h3><strong>8. So với Google Drive hay Dropbox, OneDrive mạnh chỗ nào?</strong></h3><ul><li>Tích hợp sâu vào Windows</li><li>Tốc độ đồng bộ rất nhanh</li><li>Có bộ Office Online mạnh mẽ</li><li>Giá rẻ so với 1TB dung lượng</li></ul><h3><strong>9. Nếu bị lỗi không đăng nhập Office thì sao?</strong></h3><p>Liên hệ CSKH để được hỗ trợ mời lại Family hoặc cấp nhóm khác.</p><p><br></p><h1>🎯 Ưu điểm khi mua OneDrive 1TB tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký trực tiếp Microsoft</li><li>⚡ Kích hoạt cực nhanh – dùng được ngay</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ</li><li>📂 Đồng bộ file tự động, tốc độ nhanh</li><li>🌍 Sử dụng toàn cầu</li><li>🔄 Bao gồm cả Office bản quyền – làm việc cực tiện</li></ul><p><br></p>', 4.00, '<p>Đồng bộ Office 365 Lưu trữ an toàn</p>', 'account', '/public/images/products/product-43-1763667763.png', 380000.00, 350000.00, 0, 2, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 09:13:39');
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `tags`, `description`, `rating`, `features`, `delivery_type`, `image`, `price`, `sale_price`, `stock_quantity`, `sold_count`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(44, 42, 'Dropbox Plus 2TB', 'dropbox-plus-2tb', '', '<h1>⭐ Giới thiệu sản phẩm Dropbox Plus (2TB – Đồng bộ đa thiết bị)</h1><h2>📌 Dropbox Plus là gì?</h2><p><strong>Dropbox Plus</strong> là gói nâng cấp cao cấp của Dropbox, cung cấp tới <strong>2TB dung lượng</strong> để lưu trữ, đồng bộ và chia sẻ dữ liệu an toàn. Đây là lựa chọn hàng đầu cho người dùng cần sao lưu ảnh/video, tài liệu công việc, dữ liệu cá nhân… trên nhiều thiết bị với tốc độ nhanh và bảo mật cao.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>tài khoản Dropbox Plus 2TB chính chủ</strong>.</li><li>Khách hàng sẽ được mời vào <strong>nhóm Family / Team</strong> để sử dụng đầy đủ dung lượng.</li><li>Không cần cung cấp mật khẩu—chỉ cần <strong>Email Dropbox</strong> để nhận lời mời.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Bạn cung cấp email Dropbox.</li><li>Shop gửi lời mời gia nhập nhóm.</li><li>Chấp nhận lời mời → tài khoản tự động nâng lên <strong>Dropbox Plus 2TB</strong>.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Ngay lập tức sau khi thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email mời vào Team/Family</li><li>Xác nhận dung lượng 2TB đã được kích hoạt</li><li>Hướng dẫn sử dụng Dropbox hiệu quả (máy tính + điện thoại)</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows / macOS</li><li>Android / iOS</li><li>Linux</li><li>Đồng bộ file trên trình duyệt</li><li>Ứng dụng Dropbox dành cho Desktop (tự lưu và backup file realtime)</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Dùng <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói trên shop.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu bị lỗi quyền Team hoặc mất dung lượng 2TB → được <strong>mời lại vào nhóm mới</strong> ngay.</li><li>Nếu không thể cấp lại → hoàn tiền theo thời gian bảo hành còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop sẽ không bảo hành nếu:</p><ul><li>Khách tự rời nhóm Team/Family.</li><li>Thay đổi thông tin gây xung đột (đổi quốc gia, đổi loại tài khoản...).</li><li>Sử dụng tài khoản vào mục đích vi phạm: spam file, phát tán phần mềm độc hại, share link bất hợp pháp.</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Dropbox Plus dùng để làm gì?</strong></h3><ul><li>Lưu trữ &amp; sao lưu dữ liệu 2TB</li><li>Đồng bộ file giữa máy tính &amp; điện thoại</li><li>Chia sẻ link dung lượng lớn</li><li>Backup tự động ảnh/video</li><li>Làm việc nhóm, đồng bộ theo thời gian thực</li></ul><h3><strong>2. Tôi cần cung cấp gì để kích hoạt?</strong></h3><p>Chỉ cần <strong>Email đăng nhập Dropbox</strong> (không cần mật khẩu).</p><h3><strong>3. Sau khi nhận lời mời thì dữ liệu cũ có bị mất không?</strong></h3><p>Không. Toàn bộ dữ liệu cũ của bạn giữ nguyên.</p><h3><strong>4. Dropbox có đồng bộ tự động như iCloud không?</strong></h3><p>Có, thậm chí mạnh hơn cho file PC/laptop.</p><h3><strong>5. Tài khoản này dùng được trên bao nhiêu thiết bị?</strong></h3><p>Không giới hạn thiết bị. Đồng bộ toàn bộ hệ sinh thái của bạn.</p><h3><strong>6. Tôi có thể tải file dung lượng lớn không?</strong></h3><p>Có, Dropbox hỗ trợ upload file lớn lên tới hàng chục GB.</p><h3><strong>7. Rời nhóm Team thì dữ liệu còn không?</strong></h3><p>Dữ liệu vẫn còn nhưng dung lượng trở về gói free → có thể bị vượt quá quota.</p><h3><strong>8. Dropbox Plus 2TB có mạnh hơn Google Drive không?</strong></h3><p>Về tốc độ đồng bộ file máy tính → <strong>Dropbox mạnh nhất thị trường</strong>.</p><h3><strong>9. Tôi dùng trên nước ngoài được không?</strong></h3><p>Hoàn toàn được.</p><h3><strong>10. Tôi đăng nhập bị lỗi thì làm sao?</strong></h3><p>Liên hệ CSKH để được mời lại nhóm hoặc cấp nhóm khác.</p><p><br></p><h1>🎯 Ưu điểm khi mua Dropbox Plus tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký trực tiếp</li><li>⚡ Kích hoạt nhanh – dùng ngay sau thanh toán</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ trong suốt gói</li><li>🌍 Dùng được mọi nơi – mọi thiết bị</li><li>📂 Đồng bộ file cực nhanh, ổn định</li><li>🔄 Dễ dàng backup &amp; khôi phục dữ liệu</li></ul>', 0.00, '<p>Đồng bộ nhanh Hỗ trợ đa thiết bị</p>', 'email_only', '', 420000.00, 399000.00, 0, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 09:13:02'),
(46, 4, 'iCloud 200GB', 'icloud-200g', '', '<h1>⭐ Giới thiệu sản phẩm iCloud / iCloud+ (Nâng dung lượng iCloud)</h1><h2>📌 iCloud+ là gì?</h2><p><strong>iCloud+</strong> là gói dịch vụ mở rộng của Apple giúp bạn <strong>sao lưu – lưu trữ – bảo vệ dữ liệu</strong> trên các thiết bị iPhone/iPad/Mac. Khi nâng cấp iCloud+, bạn có thêm dung lượng để lưu ảnh, video, danh bạ, ghi chú và toàn bộ dữ liệu quan trọng mà không lo bị đầy bộ nhớ.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>gói iCloud+ chính chủ</strong>, được kích hoạt thông qua phương thức <strong>mời vào Family Apple</strong>.</li><li>Sau khi kích hoạt, bạn được sử dụng đầy đủ dung lượng của gói iCloud+ (50GB / 200GB / 2TB tùy lựa chọn).</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Bạn chỉ cần cung cấp <strong>Email Apple ID</strong> (không cần mật khẩu).</li><li>Shop sẽ gửi <strong>lời mời Family</strong> để bạn tham gia và nhận dung lượng iCloud+.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Ngay sau khi thanh toán đơn hàng thành công (24/7 tự động hoặc trong 2–5 phút giờ làm việc).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><p>Bạn sẽ nhận được:</p><ul><li>Lời mời Family Apple qua Email</li><li>Hướng dẫn tham gia nhóm</li><li>Xác nhận dung lượng iCloud+ đã được kích hoạt</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>iPhone</li><li>iPad</li><li>MacBook</li><li>Apple Watch (đồng bộ theo iPhone)</li><li>Tự động đồng bộ ảnh, video, tài liệu, ghi chú, tin nhắn…</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Sử dụng được <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tuỳ gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu tài khoản Family lỗi hoặc mất quyền truy cập → <strong>Gửi lại lời mời mới ngay</strong>.</li><li>Nếu không thể cấp lại → <strong>Hoàn tiền theo thời gian còn lại</strong>.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop sẽ từ chối bảo hành nếu:</p><ul><li>Bạn tự ý rời group Family</li><li>Tự đổi quốc gia Apple ID làm mất đăng ký</li><li>Tự ý chỉnh sửa cài đặt Family (quản lý chia sẻ, người tổ chức…)</li><li>Dùng iCloud vào mục đích vi phạm chính sách của Apple</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. iCloud+ là gì?</strong></h3><p>Là gói mở rộng dung lượng và tính năng của iCloud, bao gồm:</p><ul><li>Lưu trữ dung lượng lớn (50GB, 200GB, 2TB)</li><li>Ẩn email (Hide My Email)</li><li>Private Relay (VPN ẩn danh của Apple)</li><li>Tăng bảo mật HomeKit và Camera</li></ul><h3><strong>2. Tôi cần cung cấp gì để được nâng cấp?</strong></h3><p>Chỉ cần <strong>Email Apple ID</strong>, KHÔNG cần mật khẩu.</p><h3><strong>3. Sau khi vào Family có bị mất dữ liệu không?</strong></h3><p>Không. Tất cả dữ liệu trên iCloud của bạn được giữ nguyên.</p><h3><strong>4. Tôi có thể xem dung lượng ở đâu?</strong></h3><p>Vào: <strong>Cài đặt → Tên bạn → iCloud → Quản lý dung lượng</strong></p><h3><strong>5. Tôi rời Family có bị mất dữ liệu không?</strong></h3><p>Các dữ liệu đã sao lưu vẫn còn, nhưng bạn sẽ <strong>không thể tải thêm</strong> nếu dung lượng vượt hạn mức miễn phí.</p><h3><strong>6. iCloud được sử dụng ở nước ngoài không?</strong></h3><p>Có. iCloud là dịch vụ toàn cầu.</p><h3><strong>7. Tôi đổi quốc gia Apple ID có ảnh hưởng không?</strong></h3><p>Có thể làm mất hiệu lực gói. Vui lòng hỏi CSKH trước khi đổi.</p><h3><strong>8. Tôi có thể sao lưu toàn bộ iPhone bằng gói này không?</strong></h3><p>Hoàn toàn được nếu dung lượng gói đủ (200GB – 2TB).</p><h3><strong>9. Family có thấy dữ liệu của nhau không?</strong></h3><p>Không. <strong>Dữ liệu hoàn toàn riêng tư.</strong></p><h3><strong>10. Tôi gặp lỗi không vào được Family thì làm sao?</strong></h3><p>Liên hệ CSKH 8h30 – 23h để được hỗ trợ ngay.</p><h3><strong>11. Tôi có thể nâng cấp lại khi hết hạn không?</strong></h3><p>Có. Bạn có thể mua lại gói mới bất cứ lúc nào.</p><p><br></p><h1>🎯 Ưu điểm khi mua iCloud+ tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký trực tiếp Apple</li><li>⚡ Nhận hàng siêu nhanh</li><li>🔒 Không cần cung cấp mật khẩu Apple ID</li><li>🛡 Bảo hành đầy đủ</li><li>📱 Hỗ trợ mọi thiết bị Apple</li><li>🔄 Dễ đổi máy – tự động đồng bộ dữ liệu</li></ul><p><br></p>', 0.00, '<p>Backup iPhone tự động Hỗ trợ đa thiết bị</p>', 'customer_account', '/public/images/products/product-46-1763666935.jpg', 250000.00, 220000.00, 40, 0, 'active', 0, '2025-10-31 04:08:41', '2025-12-12 08:23:09'),
(47, 31, 'Duolingo Super', 'duolingo-super', 'Học tập', '<h1>⭐ Giới thiệu sản phẩm Duolingo Super / Duolingo Max</h1><h2>📌 Duolingo là gì?</h2><p><strong>Duolingo</strong> là ứng dụng học ngoại ngữ phổ biến nhất thế giới, giúp bạn học tiếng Anh, Nhật, Hàn, Trung, Tây Ban Nha… theo cách <strong>dễ hiểu – vui vẻ – hiệu quả</strong>.</p><p> Khi nâng cấp lên <strong>Duolingo Super hoặc Duolingo Max</strong>, bạn sẽ mở khóa toàn bộ tính năng cao cấp, không quảng cáo và học tập mượt hơn rất nhiều.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>gói Duolingo Super / Max chính chủ</strong> (tùy lựa chọn).</li><li>Khách hàng được <strong>mời vào nhóm Family</strong> để kích hoạt gói Super/Max.</li><li>Bạn <strong>không cần cung cấp mật khẩu</strong>, chỉ cần <strong>Email đăng ký Duolingo</strong>.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Cung cấp email tài khoản Duolingo.</li><li>Shop gửi lời mời vào Family Duolingo.</li><li>Chấp nhận → tài khoản tự động lên <strong>Super hoặc Max</strong> tùy gói.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao hàng ngay sau thanh toán (2–5 phút).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email mời Family</li><li>Xác nhận tài khoản đã lên Super/Max</li><li>Hướng dẫn sử dụng chi tiết</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>iOS</li><li>Android</li><li>Web (duolingo.com)</li><li>Tablet / iPad</li><li>Đồng bộ tiến độ giữa tất cả thiết bị</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Sử dụng <strong>toàn cầu</strong>, không giới hạn vị trí.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Nếu bị mất quyền Family → shop sẽ mời lại ngay.</li><li>Nếu không thể mời lại → hoàn tiền phần thời gian còn lại.</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Duolingo Super / Max có gì khác bản thường?</strong></h3><ul><li>Không quảng cáo</li><li>Bài luyện tập không giới hạn</li><li>Tính năng <strong>Heart không giới hạn</strong></li><li>Xem phân tích lỗi chi tiết</li><li>Truy cập bài học ngoại tuyến</li><li>Với <strong>Duolingo Max</strong>: có thêm tính năng AI giải thích lỗi &amp; luyện hội thoại</li></ul><h3><strong>2. Tôi cần cung cấp gì để kích hoạt?</strong></h3><p>Chỉ cần <strong>Email tài khoản Duolingo</strong>.</p><h3><strong>3. Tôi có thay đổi ngôn ngữ học được không?</strong></h3><p>Được. Duolingo hỗ trợ hàng chục ngôn ngữ khác nhau.</p><h3><strong>4. Tôi có thể học trên nhiều thiết bị không?</strong></h3><p>Có. Tiến độ học sẽ được đồng bộ liên tục.</p><h3><strong>5. Duolingo Super với Max khác nhau thế nào?</strong></h3><ul><li><strong>Super:</strong> Không quảng cáo + nhiều lợi ích học tập</li><li><strong>Max:</strong> Có tất cả của Super + thêm tính năng AI (Explain My Answer, Roleplay)</li></ul><h3><strong>6. Tôi dùng ở nước ngoài được không?</strong></h3><p>Có. Duolingo hoạt động toàn cầu.</p><h3><strong>7. Tôi rời Family thì còn Super không?</strong></h3><p>Không. Nếu rời Family, tài khoản sẽ trở về bản miễn phí.</p><h3><strong>8. Tôi vào Family nhưng app không hiện Super?</strong></h3><p>Chỉ cần thoát app → mở lại, hoặc đăng xuất đăng nhập.</p><h3><strong>9. Tài khoản có bị ảnh hưởng dữ liệu học cũ không?</strong></h3><p>Không. Mọi tiến trình học vẫn giữ nguyên.</p><p><br></p><h1>🎯 Ưu điểm khi mua Duolingo Super / Max tại Shop</h1><ul><li>⭐ Giá tốt hơn đăng ký trực tiếp</li><li>⚡ Kích hoạt siêu nhanh – nhận hàng ngay</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành toàn thời gian gói</li><li>🌍 Sử dụng mọi nơi</li><li>🎧 Trải nghiệm học ngoại ngữ mượt hơn, không gián đoạn</li><li>🤖 Có thể học bằng AI (với Duolingo Max)</li></ul><p><br></p>', 0.00, '<p><br></p>', 'email_only', '/public/images/products/product-1763668383-691f719fd3e81.png', 500000.00, 300000.00, 100, 0, 'active', 1, '2025-11-20 19:53:03', '2025-12-12 07:06:57'),
(48, 3, 'HMA VPN (Hide My Ass! Pro)', 'hma-vpn-hide-my-ass-pro', '', '<h1>⭐ Giới thiệu sản phẩm HMA VPN (Hide My Ass! Pro)</h1><h2>📌 HMA VPN là gì?</h2><p><strong>HMA VPN (Hide My Ass!)</strong> là một trong những dịch vụ VPN lâu đời và mạnh mẽ nhất thế giới, nổi tiếng với tốc độ nhanh, khả năng đổi IP tự động và mạng lưới <strong>1.100+ server tại 210+ quốc gia</strong> — nhiều nhất trong các dịch vụ VPN hiện nay.</p><p> HMA giúp bạn <strong>ẩn danh IP</strong>, vượt chặn website, truy cập nội dung toàn cầu và bảo vệ kết nối khi dùng WiFi công cộng.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>tài khoản HMA Pro VPN bản quyền</strong> (gói 1 năm / 3 năm / Premium).</li><li>Sau khi mua, bạn sẽ nhận:</li><li> ✔ Email đăng nhập</li><li> ✔ Mật khẩu</li><li> ✔ Toàn quyền sử dụng full tính năng gói Pro.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ul><li>Tải ứng dụng HMA VPN trên Windows, macOS, Android, iOS hoặc Router.</li><li>Đăng nhập bằng thông tin shop cung cấp.</li><li>Chọn quốc gia → Kết nối → Dùng ngay.</li></ul><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Giao hàng ngay sau thanh toán (tự động hoặc 2–5 phút).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email &amp; mật khẩu HMA</li><li>Hướng dẫn cài đặt</li><li>Hỗ trợ trong suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows</li><li>macOS</li><li>iOS</li><li>Android</li><li>Linux</li><li>Router hỗ trợ OpenVPN</li><li>Extension trên Chrome / Firefox</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Hoạt động <strong>toàn cầu</strong> – không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3–6–12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Không đăng nhập được → cấp tài khoản mới ngay.</li><li>Nếu không còn tài khoản thay thế → hoàn tiền phần còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop không bảo hành nếu:</p><ul><li>Tự thay đổi thông tin tài khoản (email/mật khẩu).</li><li>Đăng nhập quá nhiều thiết bị gây khóa tài khoản.</li><li>Dùng VPN vào mục đích bất hợp pháp.</li><li>Chia sẻ tài khoản cho nhiều người khác.</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. HMA VPN dùng để làm gì?</strong></h3><ul><li>Ẩn IP và bảo vệ quyền riêng tư</li><li>Vượt chặn website, ứng dụng bị khóa theo vùng</li><li>Bảo vệ khi dùng WiFi công cộng</li><li>Tăng tốc truy cập server quốc tế</li><li>Đổi IP tự động theo giờ</li></ul><h3><strong>2. Một tài khoản dùng được bao nhiêu thiết bị?</strong></h3><p>HMA hỗ trợ <strong>tối đa 5 thiết bị</strong> hoạt động đồng thời (tùy gói).</p><h3><strong>3. HMA có mạnh hơn NordVPN không?</strong></h3><ul><li>Vượt chặn và đổi IP liên tục → HMA mạnh.</li><li>Bảo mật &amp; tốc độ cao → NordVPN mạnh.</li><li> Tùy nhu cầu, mỗi loại có ưu điểm riêng.</li></ul><h3><strong>4. Có xem được Netflix US không?</strong></h3><p>Có, tùy server. HMA hỗ trợ mở khóa nhiều thư viện.</p><h3><strong>5. Dùng ở nước ngoài được không?</strong></h3><p>Có. HMA là VPN toàn cầu.</p><h3><strong>6. Tài khoản có bị lưu log không?</strong></h3><p>HMA áp dụng chính sách <strong>No-log</strong>, không lưu hoạt động người dùng.</p><h3><strong>7. Có server Việt Nam không?</strong></h3><p>Có, hỗ trợ kết nối IP Việt Nam ổn định.</p><h3><strong>8. HMA có đổi IP tự động không?</strong></h3><p>Có, dùng tính năng <strong>IP Shuffle</strong> – đổi IP theo thời gian bạn thiết lập.</p><p><br></p><h1>🎯 Ưu điểm khi mua HMA tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp</li><li>⚡ Nhận tài khoản ngay</li><li>🔒 Đổi IP tự động – an toàn hơn</li><li>🌍 Server phủ khắp 210+ quốc gia</li><li>🛡 Bảo hành đầy đủ</li><li>📱 Dùng được nhiều thiết bị</li><li>🔥 Tốc độ nhanh, ổn định, không lag</li></ul><p><br></p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-1763669051-691f743b22c30.png', 200000.00, 100000.00, 100, 0, 'active', 0, '2025-11-20 20:04:11', '2025-12-12 08:22:50'),
(49, 31, 'Canva Pro', 'canva-pro', 'Học tập', '<h1>⭐ Giới thiệu sản phẩm Canva Pro – Thiết kế chuyên nghiệp, dễ dàng cho mọi người</h1><h2>📌 Canva Pro là gì?</h2><p><strong>Canva Pro</strong> là phiên bản cao cấp của nền tảng thiết kế Canva, cung cấp đầy đủ công cụ giúp bạn tạo ảnh, video, poster, banner, logo, CV… chỉ trong vài phút.</p><p> Với Canva Pro, bạn mở khóa toàn bộ kho tài nguyên khổng lồ: <strong>100+ triệu ảnh – video – template – font</strong>, cùng các tính năng nâng cao để thiết kế nhanh, đẹp và chuyên nghiệp.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>gói Canva Pro chính chủ</strong>, được kích hoạt qua <strong>Team (Nhóm)</strong>.</li><li>Bạn <strong>không cần cung cấp mật khẩu</strong>, chỉ cần <strong>Email tài khoản Canva</strong> để mình mời vào Team Pro.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Bạn cung cấp email đăng nhập Canva</li><li>Shop gửi lời mời vào Team Canva Pro</li><li>Chấp nhận → tài khoản tự động nâng cấp <strong>Canva Pro full tính năng</strong></li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ 2–5 phút sau khi thanh toán (nhanh – tự động).</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email mời Team Pro</li><li>Xác nhận tài khoản đã nâng cấp</li><li>Hướng dẫn sử dụng Canva Pro</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Web (canva.com)</li><li>iOS / Android</li><li>Windows / macOS (ứng dụng)</li><li>Đồng bộ toàn bộ thiết kế giữa các thiết bị</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Canva Pro dùng được <strong>toàn cầu</strong> – không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3 tháng / 6 tháng / 12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Mất quyền Pro → shop mời lại ngay.</li><li>Nếu không còn Team → hoàn tiền phần còn lại.</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành nếu:</p><ul><li>Bạn tự rời nhóm Team</li><li>Thay đổi email tài khoản</li><li>Vi phạm chính sách Canva (spam template, chia sẻ link bất hợp pháp, dùng quá hạn mức lưu trữ…)</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Canva Pro có gì khác bản miễn phí?</strong></h3><ul><li>100+ triệu ảnh, video, âm thanh, icon Pro</li><li>Kho template premium cực lớn</li><li>Tính năng <strong>Xóa nền Background Remover</strong></li><li>Tạo ảnh bằng AI (Magic Media)</li><li>Tự động chỉnh sửa – tối ưu bố cục</li><li>Xuất file chất lượng cao (PNG, PDF in ấn, MP4…)</li><li>Brand Kit: Lưu logo – màu sắc – font thương hiệu</li><li>Lên lịch đăng bài Facebook, Instagram, TikTok…</li></ul><h3><strong>2. Tôi cần cung cấp gì để kích hoạt Canva Pro?</strong></h3><p>Chỉ cần <strong>Email tài khoản Canva</strong>, không cần mật khẩu.</p><h3><strong>3. Canva Pro có dùng được trên nhiều thiết bị không?</strong></h3><p>Có, đồng bộ toàn bộ thiết kế 100%.</p><h3><strong>4. Canva Pro dùng để làm gì?</strong></h3><ul><li>Thiết kế logo, banner, poster</li><li>Tạo video marketing</li><li>Thiết kế CV, card, hồ sơ thầu</li><li>Làm slide PowerPoint đẹp nhanh</li><li>Ảnh quảng cáo Facebook / TikTok</li><li>Template branding cho doanh nghiệp</li></ul><h3><strong>5. Canva Pro có dùng được ở nước ngoài không?</strong></h3><p>Có, không giới hạn quốc gia.</p><h3><strong>6. Nếu tôi rời Team thì còn Pro không?</strong></h3><p>Không, tài khoản sẽ trở về bản miễn phí.</p><h3><strong>7. Dữ liệu thiết kế có bị mất không khi rời Team?</strong></h3><p>Thiết kế vẫn còn, nhưng tài nguyên Pro sẽ bị khóa.</p><h3><strong>8. Tôi dùng quá tài nguyên Team có bị khóa không?</strong></h3><p>Miễn là dùng đúng mục đích cá nhân thiết kế, không sao.</p><p> Chỉ vi phạm spam hoặc chia sẻ công khai tài nguyên mới bị cảnh báo.</p><h1>🎯 Ưu điểm khi mua Canva Pro tại Shop</h1><ul><li>⭐ Giá rẻ hơn đăng ký chính hãng</li><li>⚡ Kích hoạt nhanh, không chờ đợi</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ</li><li>🎨 Full kho template – ảnh – video – font</li><li>✂ Xóa nền – chỉnh ảnh – video chuyên nghiệp</li><li>🌍 Dùng mọi nơi, trên mọi thiết bị</li></ul><p><br></p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-1763669657-691f7699466ad.png', 300000.00, 150000.00, 100, 0, 'active', 0, '2025-11-20 20:14:17', '2025-12-12 08:23:03'),
(50, 31, 'Grammarly Pro (AI)', 'grammarly-pro-ai', 'Học tập', '<h1>⭐ Giới thiệu sản phẩm Grammarly Premium (Grammarly Pro + AI)</h1><h2>📌 Grammarly Premium là gì?</h2><p><strong>Grammarly Premium (Grammarly Pro)</strong> là phiên bản cao cấp của công cụ kiểm tra ngữ pháp tiếng Anh số 1 thế giới. Ngoài việc sửa lỗi chính tả – ngữ pháp – dấu câu – viết lại câu, bản Premium còn có <strong>AI viết nội dung</strong>, giúp bạn cải thiện bài viết, email, luận văn, văn bản công việc một cách chuyên nghiệp và nhanh chóng.</p><p>Grammarly Pro tích hợp AI mạnh mẽ như:</p><ul><li>Viết lại toàn đoạn theo yêu cầu</li><li>Tối ưu giọng văn (Formal, Friendly, Academic…)</li><li>Tóm tắt văn bản</li><li>Viết nội dung từ đầu</li><li>Gợi ý cấu trúc &amp; từ vựng nâng cao</li></ul><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Đây là <strong>gói Grammarly Premium/Pro chính chủ</strong>, full tính năng AI.</li><li>Kích hoạt qua <strong>Team / Business</strong>, bạn chỉ cần <strong>Email Grammarly</strong> để được mời.</li><li>Không cần mật khẩu — tài khoản của bạn giữ nguyên.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Bạn gửi email dùng để đăng nhập Grammarly</li><li>Shop mời vào Team Premium</li><li>Chấp nhận → tài khoản tự động nâng cấp ngay</li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ 1–5 phút sau khi thanh toán.</p><h3><strong>4. Thông tin nhận hàng</strong></h3><ul><li>Email mời Team Pro</li><li>Xác nhận kích hoạt thành công</li><li>Hướng dẫn sử dụng Grammarly trên Chrome &amp; máy tính</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Chrome / Firefox / Edge (extension)</li><li>Windows / macOS (ứng dụng)</li><li>iOS / Android</li><li>Web editor: Grammarly.com</li></ul><h3><strong>6. Khu vực hoạt động</strong></h3><p>✔ Dùng <strong>toàn cầu</strong>, không giới hạn quốc gia.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>30 ngày / 3–6–12 tháng tùy gói bạn chọn.</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Mất Premium → mời lại ngay</li><li>Không vào Team được → hỗ trợ đổi Team</li><li>Nếu không thể cấp lại → hoàn tiền phần còn lại</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Shop không bảo hành khi:</p><ul><li>Bạn tự rời Team</li><li>Đổi email tài khoản Grammarly</li><li>Dùng tính năng AI sai mục đích hoặc spam hệ thống</li><li>Chia sẻ tài khoản cho nhiều người</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Grammarly Premium khác bản Free thế nào?</strong></h3><p>Premium mở khóa:</p><ul><li>Sửa lỗi nâng cao</li><li>Gợi ý từ vựng chuyên sâu</li><li>Rewrite lại cả đoạn</li><li>Tối ưu giọng văn</li><li>Kiểm tra đạo văn (Plagiarism Checker)</li><li>Tính năng AI viết nội dung</li><li>Phân tích phong cách bài viết chuyên nghiệp</li></ul><h3><strong>2. Grammarly AI làm được gì?</strong></h3><ul><li>Viết lại câu/đoạn theo tone yêu cầu</li><li>Tóm tắt văn bản</li><li>Viết email, bài luận, nội dung marketing</li><li>Gợi ý cách diễn đạt tự nhiên hơn</li><li>Hoàn thiện bài viết theo ngữ cảnh</li></ul><h3><strong>3. Tôi có dùng Grammarly Premium trên điện thoại không?</strong></h3><p>Có, hỗ trợ đầy đủ cả Android, iPhone.</p><h3><strong>4. Khi được mời vào Team có mất dữ liệu cũ không?</strong></h3><p>Không. Toàn bộ lịch sử sửa lỗi và tài liệu cũ đều giữ nguyên.</p><h3><strong>5. Dùng được ở nước ngoài không?</strong></h3><p>Có. Grammarly hoạt động toàn cầu.</p><h3><strong>6. Nếu tôi không thấy Premium thì sao?</strong></h3><p>Thoát trình duyệt → mở lại hoặc đăng xuất đăng nhập.</p><h3><strong>7. Tôi rời Team có còn Premium không?</strong></h3><p>Không. Khi rời Team, tài khoản trở về bản Free.</p><p><br></p><h1>🎯 Ưu điểm khi mua Grammarly Pro tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp Grammarly</li><li>⚡ Nhận hàng ngay sau thanh toán</li><li>🔒 Không cần cung cấp mật khẩu</li><li>🛡 Bảo hành đầy đủ</li><li>🤖 Dùng full AI viết nội dung</li><li>💼 Phù hợp cho học sinh – sinh viên – dân văn phòng – freelancer</li><li>✍ Tăng chất lượng bài viết 200% chỉ với 1 click</li></ul><p><br></p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-1763669885-691f777d647df.png', 900000.00, 500000.00, 1000, 0, 'active', 0, '2025-11-20 20:18:05', '2025-12-12 08:23:05'),
(51, 36, 'Adobe Acrobat Pro DC 2019', 'adobe-acrobat-pro-dc-2019', 'Văn phòng', '<h1>⭐ Giới thiệu sản phẩm Adobe Acrobat Pro DC 2019</h1><h2>📌 Adobe Acrobat Pro DC 2019 là gì?</h2><p><strong>Adobe Acrobat Pro DC 2019</strong> là phiên bản cao cấp của bộ công cụ xử lý PDF mạnh mẽ nhất hiện nay.</p><p> Với Acrobat Pro DC, bạn có thể <strong>tạo, chỉnh sửa, chuyển đổi, ký số, bảo mật và quản lý PDF chuyên nghiệp</strong>.</p><p>Đây là phần mềm chuẩn doanh nghiệp – được các công ty, văn phòng, sinh viên, giáo viên… tin dùng nhờ:</p><ul><li>Chỉnh sửa PDF như Word</li><li>Ghép – tách – nén PDF</li><li>Chuyển đổi PDF sang Word/Excel/PowerPoint</li><li>Ký điện tử – bảo mật file</li><li>OCR nhận dạng chữ trong ảnh (Scan to PDF)</li></ul><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h3><strong>1. Hình thức sản phẩm</strong></h3><ul><li>Sản phẩm là <strong>key kích hoạt Adobe Acrobat Pro DC 2019 bản quyền</strong> hoặc <strong>tài khoản kích hoạt theo gói</strong>, tùy lựa chọn.</li><li>Dùng vĩnh viễn hoặc theo thời hạn bảo hành tùy gói.</li></ul><h3><strong>2. Cách sử dụng</strong></h3><ol><li>Tải Acrobat Pro DC 2019</li><li>Cài đặt trên máy</li><li>Kích hoạt bằng Key / Tài khoản được cung cấp</li><li>Sử dụng đầy đủ tính năng Pro</li></ol><h3><strong>3. Thời gian xử lý</strong></h3><p>✔ Gửi key hoặc tài khoản ngay sau thanh toán.</p><h3><strong>4. Bạn sẽ nhận được</strong></h3><ul><li>Link tải phần mềm</li><li>Key / Tài khoản kích hoạt</li><li>Hướng dẫn cài đặt chi tiết</li><li>CSKH hỗ trợ cài đặt miễn phí</li></ul><h3><strong>5. Thiết bị hỗ trợ</strong></h3><ul><li>Windows</li><li>macOS</li><li>Hỗ trợ đầy đủ tính năng trên PC/Laptop</li></ul><h1>🛡 Chính sách bảo hành</h1><h3>⏳ Thời gian bảo hành</h3><ul><li>Theo gói bạn chọn: 1 tháng / 3 tháng / 12 tháng</li></ul><h3>📌 Nội dung bảo hành</h3><ul><li>Key kích hoạt lỗi → đổi key mới</li><li>Tài khoản không vào được → cấp tài khoản thay thế</li><li>Lỗi phần mềm → hỗ trợ cài đặt &amp; khắc phục</li></ul><h3>❌ Miễn trừ trách nhiệm</h3><p>Không bảo hành khi:</p><ul><li>Tự ý gỡ, chỉnh sửa file kích hoạt</li><li>Cài sai hướng dẫn gây lỗi</li><li>Dùng key cho quá nhiều thiết bị (vượt giới hạn)</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. Adobe Acrobat Pro DC 2019 khác gì bản Reader?</strong></h3><ul><li><strong>Reader:</strong> chỉ đọc PDF</li><li><strong>Pro DC:</strong> chỉnh sửa, chuyển đổi, ký số, bảo mật, OCR và nhiều tính năng nâng cao</li></ul><h3><strong>2. Dùng được trên mấy máy?</strong></h3><ul><li>Tùy từng key/tài khoản (thường 1–2 thiết bị).</li></ul><h3><strong>3. Acrobat Pro DC 2019 có ổn định không?</strong></h3><p>Rất ổn định và nhẹ hơn bản 2020/2023, rất phù hợp cho máy cấu hình trung bình.</p><h3><strong>4. Có cần mạng để dùng không?</strong></h3><p>Không bắt buộc — dùng offline vẫn đầy đủ tính năng.</p><h3><strong>5. Có hỗ trợ OCR không?</strong></h3><p>Có, nhận dạng nhiều ngôn ngữ, kể cả tiếng Việt.</p><h3><strong>6. Tài khoản Adobe có bị ảnh hưởng dữ liệu cá nhân không?</strong></h3><p>Không — shop chỉ cung cấp key hoặc tài khoản riêng, <em>không yêu cầu tài khoản cá nhân của bạn</em>.</p><p><br></p><h1>🎯 Ưu điểm khi mua Adobe Acrobat Pro DC 2019 tại Shop</h1><ul><li>⭐ Giá rẻ hơn mua trực tiếp của Adobe</li><li>⚡ Gửi key kích hoạt nhanh</li><li>🛡 Bảo hành đầy đủ</li><li>💻 Hỗ trợ cài đặt trọn đời</li><li>📌 Ổn định – nhẹ – phù hợp cho mọi cấu hình</li><li>📑 Công cụ PDF mạnh nhất hiện nay</li></ul><p><br></p>', 0.00, '<p><br></p>', 'email_only', '/public/images/products/product-1763712161-69201ca13d9bd.jpg', 400000.00, 200000.00, 100, 0, 'active', 0, '2025-11-21 08:02:41', '2025-12-12 08:23:00'),
(53, 4, 'Microsoft Office 365 1Tb Dữ Liệu', 'microsoft-office-365-1tb-du-lieu', 'Microsoft, Văn Phòng', '<h1>⭐ Giới thiệu sản phẩm Microsoft 365 – OneDrive 1TB</h1><p>📌 <strong>OneDrive 1TB</strong> là gói lưu trữ đám mây cao cấp của Microsoft, đi kèm bộ ứng dụng <strong>Microsoft 365 bản quyền</strong>. Với dung lượng <strong>1TB</strong>, bạn có thể lưu trữ ảnh, video, tài liệu, dữ liệu công việc… và đồng bộ trên mọi thiết bị.</p><p> Ngoài OneDrive, bạn còn được sử dụng <strong>Word, Excel, PowerPoint</strong> và toàn bộ hệ sinh thái Office Online một cách chính chủ – an toàn – tốc độ cao.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Sản phẩm là gói <strong>Microsoft 365 Family</strong>, trong đó bạn được mời vào nhóm để kích hoạt:</p><p> ✔ OneDrive 1TB</p><p> ✔ Bộ ứng dụng Microsoft 365 bản quyền</p><p> 👉 <strong>Chỉ cần cung cấp Email Microsoft (Outlook/Hotmail/Live)</strong> – <strong>không cần mật khẩu</strong></p><p> 👉 Sau khi vào Family, tài khoản tự hoạt động vĩnh viễn theo thời hạn gói bạn chọn.</p><h2><strong>2. Cách sử dụng</strong></h2><p>Quy trình cực đơn giản:</p><ol><li>Shop gửi lời mời Family vào email của bạn</li><li>Bạn bấm <strong>Accept / Chấp nhận</strong></li><li>Ngay lập tức tài khoản được kích hoạt:</li></ol><ul><li class=\"ql-indent-1\">OneDrive 1TB</li><li class=\"ql-indent-1\">Office Word/Excel/PowerPoint bản quyền</li><li class=\"ql-indent-1\">Đồng bộ đa thiết bị</li></ul><ol><li>Có thể dùng ngay trên <strong>Web / App / PC / Mac / Mobile</strong></li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>Giao hàng siêu nhanh 2–5 phút sau khi thanh toán.</strong></p><p> Hỗ trợ 24/7 nếu bạn cần kích hoạt gấp.</p><h2><strong>4. Thông tin bạn nhận được</strong></h2><p>Sau khi mua, bạn sẽ nhận:</p><ul><li>Email mời vào Microsoft Family</li><li>Xác nhận kích hoạt OneDrive 1TB + Microsoft 365</li><li>Hướng dẫn sử dụng chi tiết từng bước</li><li>Hỗ trợ kỹ thuật &amp; bảo hành theo gói</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>Bạn có thể dùng trên tất cả nền tảng:</p><ul><li>Windows PC / Laptop</li><li>macOS</li><li>iPhone / iPad (iOS)</li><li>Android</li><li>Trình duyệt Web</li><li>Ứng dụng OneDrive Desktop (tự đồng bộ file)</li></ul><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 <strong>Dùng được toàn cầu</strong> – không giới hạn quốc gia, không bị khóa vùng.</p><p><br></p><h1>🛡 Chính sách bảo hành</h1><h2><strong>⏳ Thời gian bảo hành</strong></h2><p>Tùy gói bạn chọn:</p><p> ✔ 30 ngày</p><p> ✔ 3 tháng</p><p> ✔ 6 tháng</p><p> ✔ 12 tháng</p><h2><strong>📌 Nội dung bảo hành</strong></h2><ul><li>Nếu mất quyền Family → <strong>mời lại ngay</strong></li><li>Nếu Family cũ hết slot → <strong>cấp nhóm khác hoặc hoàn tiền theo thời gian còn lại</strong></li></ul><h2>❌ <strong>Miễn trừ trách nhiệm</strong></h2><p>Shop không bảo hành nếu khách hàng:</p><ul><li>Tự rời nhóm Family</li><li>Thay đổi vùng quốc gia của tài khoản → khiến Microsoft thu hồi quyền</li><li>Chia sẻ tài khoản sai cách / vi phạm chính sách Microsoft</li><li>Sử dụng để lưu nội dung bất hợp pháp / nhạy cảm</li></ul><h1>❓ Câu hỏi thường gặp (FAQ)</h1><h3><strong>1. OneDrive 1TB dùng để làm gì?</strong></h3><ul><li>Lưu trữ dữ liệu dung lượng lớn</li><li>Sao lưu tự động ảnh/video điện thoại</li><li>Đồng bộ file giữa máy tính – điện thoại</li><li>Chia sẻ tài liệu nhanh chóng</li><li>Làm việc nhóm qua Office Online</li></ul><h3><strong>2. Cần cung cấp thông tin gì?</strong></h3><p>👉 Chỉ cần <strong>Email Microsoft</strong> (Outlook/Hotmail). Không cần mật khẩu.</p><h3><strong>3. Dữ liệu cũ có bị mất không?</strong></h3><p>Không. Toàn bộ dữ liệu OneDrive cũ được giữ nguyên.</p><h3><strong>4. Có dùng Office bản quyền được không?</strong></h3><p>Có. Dùng đầy đủ:</p><ul><li>Word</li><li>Excel</li><li>PowerPoint</li><li>Outlook</li><li>OneNote</li><li>Access (PC)</li><li>Publisher (PC)</li></ul><h3><strong>5. Dùng ở nước ngoài có ổn không?</strong></h3><p>Có. Microsoft 365 hoạt động toàn cầu.</p><h3><strong>6. Đổi máy / reset máy có mất quyền không?</strong></h3><p>Không. Chỉ cần đăng nhập lại → dữ liệu đồng bộ đầy đủ.</p><h3><strong>7. Nếu rời nhóm Family thì sao?</strong></h3><p>Dữ liệu vẫn tồn tại, nhưng dung lượng về <strong>5GB</strong> → nếu vượt quota sẽ không upload thêm.</p><h3><strong>8. So với Google Drive hay Dropbox, OneDrive mạnh ở đâu?</strong></h3><ul><li>Tích hợp sâu với Windows</li><li>Tốc độ đồng bộ rất nhanh</li><li>Có Office Online siêu tiện</li><li>Giá rẻ nhất so với dung lượng 1TB</li></ul><h3><strong>9. Không đăng nhập được Office thì sao?</strong></h3><p>Liên hệ shop → hỗ trợ mời lại hoặc cấp nhóm khác.</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-53-1765524278-693bc336dbe13.jpg', 500000.00, NULL, 100, 0, 'active', 0, '2025-12-12 07:24:38', '2025-12-12 08:22:46'),
(54, 31, 'Goodnote 6', 'goodnote-6', 'Ipad', '<h1>⭐ Giới thiệu sản phẩm GoodNotes Premium / GoodNotes 6</h1><p>📌 <strong>GoodNotes</strong> là ứng dụng ghi chú số (digital note-taking) mạnh mẽ, được ưa chuộng bởi học sinh – sinh viên – giáo viên – người đi làm. GoodNotes cho phép viết tay mượt mà, tạo sổ tay, vẽ sơ đồ, import PDF/PowerPoint để ghi chú, học bài và quản lý tài liệu dễ dàng.</p><p>Với <strong>GoodNotes Premium</strong>, bạn được mở khóa toàn bộ tính năng:</p><p> ✔ Ghi chú không giới hạn</p><p> ✔ Chuyển chữ viết tay → văn bản</p><p> ✔ Đồng bộ đa thiết bị (iOS, iPadOS, macOS, Android, Windows)</p><p> ✔ Template sổ tay, bìa, sticker cao cấp</p><p> ✔ Quản lý PDF hiệu quả cho học tập &amp; công việc</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp:</p><ul><li><strong>GoodNotes Premium chính chủ</strong> (kích hoạt bằng email)</li><li>Hoặc <strong>Tài khoản dùng chung</strong> tùy theo gói bạn chọn</li></ul><p>👉 <strong>Không cần mật khẩu iCloud</strong></p><p> 👉 Hoàn toàn an toàn – chỉ kích hoạt quyền trong ứng dụng GoodNotes</p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Bạn gửi email để shop kích hoạt bản quyền</li><li>Shop gửi hướng dẫn đăng nhập vào GoodNotes → kích hoạt Premium</li><li>Bạn mở ứng dụng → tính năng Premium tự hoạt động ngay</li><li>Đồng bộ ghi chú trên các thiết bị (iPad/iPhone/Mac/Android/Windows)</li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>2–10 phút sau thanh toán</strong></p><p> Hỗ trợ nhanh nếu bạn cần kích hoạt gấp.</p><h2><strong>4. Thông tin nhận hàng</strong></h2><p>Sau khi mua, bạn nhận được:</p><ul><li>Email kích hoạt Premium</li><li>Hướng dẫn đăng nhập và kích hoạt</li><li>Tài khoản hoặc mã bản quyền (tùy gói)</li><li>Bộ template sổ tay, bìa, sticker tặng thêm (nếu có)</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>GoodNotes Premium dùng được trên:</p><ul><li>iPad</li><li>iPhone</li><li>MacBook (macOS)</li><li>Android (Samsung, Xiaomi, Oppo,...)</li><li>Windows (phiên bản GoodNotes Web/Win)</li></ul><p>Dùng rất mượt với: Apple Pencil, Logitech Crayon, bút cảm ứng.</p><p><br></p><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 <strong>Dùng toàn cầu – không giới hạn vùng quốc gia.</strong></p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-54-1765525359-693bc76fbabe3.jpeg', 600000.00, NULL, 100, 0, 'active', 0, '2025-12-12 07:42:39', '2025-12-12 08:22:42'),
(55, 34, 'Capcut Pro', 'capcut-pro', 'Edit video, Capcut', '<h1>⭐ Giới thiệu sản phẩm CapCut Pro – Full Tính Năng</h1><p>📌 <strong>CapCut Pro</strong> là phiên bản nâng cấp của ứng dụng chỉnh sửa video CapCut, mở khóa toàn bộ hiệu ứng cao cấp, template edit chuyên nghiệp, không watermark, xuất video chất lượng cao, dùng được cho creator, editor TikTok, Facebook, YouTube, kinh doanh online và doanh nghiệp sản xuất nội dung.</p><p>Với <strong>CapCut Pro</strong>, bạn được quyền sử dụng toàn bộ tài nguyên premium:</p><p> ✔ Template Pro</p><p> ✔ Hiệu ứng – filter – font chữ cao cấp</p><p> ✔ Tách nền AI</p><p> ✔ Chỉnh sửa video 4K – HDR</p><p> ✔ Không watermark</p><p> ✔ Dùng được trên nhiều thiết bị: iPhone, Android, PC.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp 2 dạng kích hoạt:</p><h3><strong>A. Kích hoạt qua tài khoản (login)</strong></h3><p>Bạn nhận tài khoản CapCut Pro riêng để đăng nhập và sử dụng.</p><h3><strong>B. Kích hoạt qua liên kết invite (không cần mật khẩu)</strong></h3><p>Bạn chỉ cung cấp email / số điện thoại → shop gửi “liên kết Pro” để nhận full quyền.</p><p>👉 <strong>Không cần cung cấp mật khẩu cá nhân</strong></p><p> 👉 Tài khoản Pro hoạt động theo đúng thời hạn gói bạn chọn.</p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Shop gửi tài khoản Pro hoặc link kích hoạt</li><li>Bạn đăng nhập vào CapCut (mobile hoặc PC)</li><li>Vào mục <strong>Subscription</strong> → hiển thị trạng thái <strong>Premium / Pro</strong></li><li>Dùng đầy đủ tính năng Pro: template, AI, export high quality…</li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>2–10 phút</strong> sau khi thanh toán.</p><p> Hỗ trợ 24/7 kích hoạt nhanh.</p><h2><strong>4. Thông tin bạn nhận được</strong></h2><ul><li>Tài khoản CapCut Pro hoặc link kích hoạt</li><li>Hướng dẫn đăng nhập / sử dụng</li><li>Xác nhận trạng thái Pro</li><li>Hỗ trợ kỹ thuật trong toàn thời gian bảo hành</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>CapCut Pro dùng được trên:</p><ul><li>iPhone</li><li>iPad</li><li>Android</li><li>CapCut Desktop (Windows / macOS)</li><li>CapCut Web Editor</li></ul><p>Có thể sử dụng đồng thời nhiều thiết bị (tùy gói).</p><p><br></p><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 <strong>Dùng được toàn cầu</strong>, không giới hạn vùng quốc gia.</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-55-1765525716-693bc8d46d76d.jpg', 120000.00, NULL, 100, 0, 'active', 0, '2025-12-12 07:48:36', '2025-12-12 08:22:40'),
(56, 15, 'Vieon Vip', 'vieon-vip', '', '<h1>⭐ Giới thiệu sản phẩm VieON VIP</h1><p>📌 <strong>VieON VIP</strong> là gói dịch vụ cao cấp của VieON – nền tảng giải trí hàng đầu Việt Nam với kho phim khổng lồ gồm phim bộ, phim điện ảnh, TV Show, bóng đá, truyền hình trực tiếp… Với gói VIP, bạn được thưởng thức nội dung không quảng cáo, chất lượng Full HD/4K, xem trên nhiều thiết bị, tốc độ tải nhanh và không bị giới hạn nội dung.</p><p>Gói <strong>VieON VIP</strong> rất phù hợp cho cá nhân, gia đình, quán café, khách sạn hoặc người thích xem phim – bóng đá chất lượng cao.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp 2 loại:</p><h3><strong>A. Tài khoản VieON VIP riêng (login)</strong></h3><p>Bạn nhận <strong>tài khoản riêng</strong> để đăng nhập trực tiếp trên mọi thiết bị.</p><h3><strong>B. Kích hoạt bằng mã / gói tặng VIP</strong></h3><p>Bạn chỉ cần cung cấp email / số điện thoại để shop <strong>kích hoạt VIP</strong> vào tài khoản hiện có.</p><p> 👉 <strong>Không cần mật khẩu</strong>, an toàn tuyệt đối.</p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Shop gửi tài khoản VIP hoặc kích hoạt trực tiếp vào tài khoản của bạn</li><li>Bạn đăng nhập vào app VieON (TV/Điện thoại/Máy tính)</li><li>Vào phần Tài khoản → hiển thị <strong>VieON VIP</strong></li><li>Xem phim – bóng đá – truyền hình không giới hạn</li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>Nhanh 1–5 phút</strong> sau khi thanh toán.</p><p><br></p><h2><strong>4. Thông tin bạn nhận được</strong></h2><ul><li>Tài khoản VieON VIP hoặc xác nhận kích hoạt VIP</li><li>Hướng dẫn đăng nhập</li><li>Thời hạn gói</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>VieON VIP xem được trên:</p><ul><li>Smart TV (Samsung, LG, Android TV)</li><li>iPhone / iPad</li><li>Android</li><li>Laptop / PC</li><li>TV Box</li><li>Web vieon.vn</li></ul><p>Có thể xem cùng lúc nhiều thiết bị (tùy gói).</p><p><br></p><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 <strong>VieON hoạt động tốt tại Việt Nam &amp; quốc tế</strong>.</p><p> Khách ở nước ngoài vẫn xem được (có thể cần VPN cho một số nội dung bản quyền).</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-56-1765526464-693bcbc00bdf6.jpg', 300000.00, NULL, 200, 0, 'active', 0, '2025-12-12 08:01:04', '2025-12-19 07:26:06'),
(57, 31, 'Chat GPT Plus', 'chat-gpt-plus', '', '<h1>⭐ Giới thiệu sản phẩm ChatGPT Plus / GPT-4 / GPT-5</h1><p>📌 <strong>ChatGPT</strong> là trợ lý AI mạnh nhất hiện nay của OpenAI, hỗ trợ viết nội dung, lập trình, học tập, phân tích dữ liệu, marketing, quảng cáo, thiết kế, dịch thuật, viết CV, làm báo cáo, kinh doanh online…</p><p> Với gói <strong>ChatGPT Plus</strong>, bạn được sử dụng các mô hình nâng cấp như <strong>GPT-4, GPT-4o, GPT-5, Advanced Reasoning</strong>, tốc độ phản hồi siêu nhanh và độ chính xác cao hơn nhiều so với bản Free.</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp 2 dạng:</p><h3><strong>A. Tài khoản ChatGPT Plus riêng</strong></h3><p>Khách nhận tài khoản riêng (email + mật khẩu) đã kích hoạt Plus.</p><h3><strong>B. Nâng cấp Plus vào tài khoản của bạn</strong></h3><p>Bạn chỉ cần cung cấp email để shop gửi lời mời vào <strong>Team/Family</strong>, không cần mật khẩu.</p><p> 👉 <strong>Tài khoản của bạn sẽ được nâng cấp lên ChatGPT Plus / Team</strong>.</p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Shop gửi <strong>email mời Team/Plus</strong> hoặc tài khoản Plus</li><li>Bạn chấp nhận lời mời → tài khoản lên <strong>ChatGPT Plus ngay</strong></li><li>Dùng được toàn bộ tính năng cao cấp:</li></ol><ul><li class=\"ql-indent-1\">GPT-4, GPT-4o, GPT-5</li><li class=\"ql-indent-1\">Advanced Reasoning</li><li class=\"ql-indent-1\">Tạo hình ảnh DALL·E</li><li class=\"ql-indent-1\">Upload file, PDF</li><li class=\"ql-indent-1\">Phân tích bảng Excel, viết code</li><li class=\"ql-indent-1\">Trả lời nhanh, thông minh, độ chính xác cao</li></ul><ol><li>Dùng trên Web và App iOS/Android</li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>1–5 phút</strong> sau khi thanh toán.</p><p><br></p><h2><strong>4. Thông tin bạn nhận được</strong></h2><ul><li>Email mời nâng cấp hoặc tài khoản Plus</li><li>Hướng dẫn chi tiết kích hoạt</li><li>Xác nhận thành công</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>ChatGPT hoạt động trên:</p><ul><li>Máy tính Windows / macOS</li><li>iPhone / iPad</li><li>Android</li><li>Trình duyệt Web</li><li>Ứng dụng ChatGPT App</li></ul><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 <strong>Dùng được toàn cầu</strong>, không giới hạn quốc gia (kể cả Việt Nam).</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-57-1765527051-693bce0b1feb2.jpg', 500000.00, NULL, 100, 0, 'active', 1, '2025-12-12 08:10:51', '2025-12-18 12:54:45'),
(58, 31, 'ELSA Speak Pro', 'elsa-speak-pro', 'Học tập', '<h1>⭐ Giới thiệu sản phẩm ELSA Speak PRO</h1><p>📌 <strong>ELSA Speak</strong> là ứng dụng luyện phát âm tiếng Anh bằng trí tuệ nhân tạo (AI) hàng đầu thế giới.</p><p> Gói <strong>ELSA PRO</strong> giúp bạn cải thiện phát âm, nói chuẩn accent quốc tế, tăng điểm IELTS Speaking, tự tin giao tiếp và học theo lộ trình cá nhân hóa.</p><p>Với ELSA PRO, bạn được mở khóa toàn bộ:</p><p> ✔ Hơn 7.000 bài luyện nói &amp; phát âm</p><p> ✔ Chấm điểm AI chi tiết từng âm</p><p> ✔ Lộ trình học theo mục tiêu (IELTS/TOEIC/Giao tiếp)</p><p> ✔ Luyện hội thoại – nói theo ngữ cảnh</p><p> ✔ Theo dõi tiến trình học mỗi ngày</p><p> ✔ Không quảng cáo – không giới hạn bài học</p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp 2 dạng:</p><h3><strong>A. Tài khoản ELSA PRO riêng (login)</strong></h3><p>Bạn nhận email + mật khẩu tài khoản PRO dùng riêng.</p><h3><strong>B. Kích hoạt PRO vào tài khoản của bạn</strong></h3><p>Chỉ cần cung cấp email đăng ký ELSA → Shop kích hoạt trực tiếp.</p><p> 👉 <strong>Không cần mật khẩu</strong>, an toàn tuyệt đối.</p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Shop gửi tài khoản PRO hoặc kích hoạt vào tài khoản của bạn</li><li>Đăng nhập ứng dụng ELSA Speak</li><li>Màn hình hiển thị trạng thái <strong>ELSA PRO</strong></li><li>Dùng toàn bộ bài học nâng cao, kiểm tra phát âm bằng AI và lộ trình cá nhân hóa</li></ol><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>1–5 phút</strong> sau thanh toán.</p><p><br></p><h2><strong>4. Bạn sẽ nhận được</strong></h2><ul><li>Tài khoản PRO hoặc email xác nhận kích hoạt</li><li>Hướng dẫn đăng nhập</li><li>Thời hạn gói</li><li>Hỗ trợ kỹ thuật trong suốt thời gian bảo hành</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>ELSA Speak PRO hoạt động trên:</p><ul><li>iPhone / iPad</li><li>Android</li><li>Web ELSA Dashboard</li><li>Máy tính bảng các loại</li></ul><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 Dùng được tại <strong>mọi quốc gia</strong>, không giới hạn vùng.</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-58-1765527233-693bcec1a5aff.jpg', 1200000.00, NULL, 100, 0, 'active', 0, '2025-12-12 08:13:53', '2025-12-18 12:55:43'),
(59, 40, 'Key Kích Hoạt Windows 10/11/Pro Vĩnh Viễn', 'key-kich-hoat-windows-1011pro', '', '<h1>⭐ Giới thiệu sản phẩm Key Kích Hoạt Windows 10 / Windows 11 / Windows Pro</h1><p>📌 <strong>Windows bản quyền</strong> giúp máy tính hoạt động ổn định, an toàn, bảo mật và sử dụng đầy đủ tính năng của Microsoft.</p><p> Với <strong>Key kích hoạt Windows 10/11/Home/Pro</strong>, bạn có thể kích hoạt vĩnh viễn hệ điều hành mà không lo lỗi bản quyền, không watermark, không bị giới hạn cập nhật.</p><p>Key do shop cung cấp là <strong>key chính hãng</strong>, kích hoạt trực tuyến trực tiếp từ máy bạn.</p><p><br></p><h1>⭐ Chi tiết sản phẩm &amp; Quy trình nhận hàng</h1><h2><strong>1. Hình thức sản phẩm</strong></h2><p>Shop cung cấp các loại key:</p><ul><li>🔹 <strong>Windows 10 Home</strong></li><li>🔹 <strong>Windows 10 Pro</strong></li><li>🔹 <strong>Windows 11 Home</strong></li><li>🔹 <strong>Windows 11 Pro</strong></li><li>🔹 Key OEM / Retail tùy gói</li><li>🔹 Key số lượng lớn cho doanh nghiệp (nếu cần)</li></ul><p>Tất cả đều là <strong>key hợp lệ</strong>, kích hoạt <strong>online</strong> ngay trên máy.</p><p><br></p><h2><strong>2. Cách sử dụng</strong></h2><ol><li>Bạn gửi phiên bản Windows đang dùng</li><li>Shop gửi key phù hợp</li><li>Bạn nhập key vào phần <strong>Activation</strong></li><li>Windows tự động kích hoạt → <strong>hiển thị trạng thái “Windows is activated”</strong></li></ol><h3>👉 Hướng dẫn kích hoạt nhanh:</h3><p><strong>Settings → Update &amp; Security → Activation → Change product key → Nhập key → Activate</strong></p><p><br></p><h2><strong>3. Thời gian xử lý</strong></h2><p>⚡ <strong>1–3 phút</strong> sau khi thanh toán.</p><p> Hỗ trợ từ xa (TeamViewer/Ultraview) nếu bạn chưa biết kích hoạt.</p><h2><strong>4. Thông tin bạn nhận được</strong></h2><ul><li>Key bản quyền Windows</li><li>Hướng dẫn kích hoạt chi tiết</li><li>File ISO cài đặt (nếu bạn cần)</li><li>Video hướng dẫn hoặc hỗ trợ kích hoạt từ xa</li><li>Bảo hành theo thời hạn gói</li></ul><h2><strong>5. Thiết bị hỗ trợ</strong></h2><p>Key dùng cho:</p><ul><li>Máy tính PC</li><li>Laptop (Dell, HP, Asus, Acer, Lenovo, MSI…)</li><li>Máy cài Windows 10/11 bản Home hoặc Pro</li><li>CPU Intel &amp; AMD đều được</li></ul><h2><strong>6. Khu vực hoạt động</strong></h2><p>🌍 Kích hoạt được toàn cầu – không giới hạn quốc gia.</p>', 0.00, '<p><br></p>', 'account', '/public/images/products/product-59-1765527651-693bd063d9596.jpg', 1000000.00, 200000.00, 100, 0, 'active', 0, '2025-12-12 08:20:51', '2025-12-12 08:22:32');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int NOT NULL,
  `category_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(2, 15),
(2, 35),
(5, 2),
(30, 15),
(32, 15),
(35, 2),
(40, 3),
(41, 3),
(43, 32),
(43, 42),
(44, 42),
(47, 31),
(49, 31),
(49, 38),
(50, 31),
(51, 36),
(51, 37),
(53, 4),
(53, 32),
(53, 33),
(54, 31),
(55, 34),
(56, 15),
(57, 31),
(57, 39),
(58, 31),
(59, 40),
(59, 41);

-- --------------------------------------------------------

--
-- Table structure for table `product_gallery`
--

CREATE TABLE `product_gallery` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_gallery`
--

INSERT INTO `product_gallery` (`id`, `product_id`, `image_path`, `sort_order`, `created_at`) VALUES
(6, 1, '/public/images/products/gallery-1-1763665387-0.jpg', 0, '2025-11-21 02:03:07'),
(7, 44, '/public/images/products/gallery-44-1763667697-0.jpg', 0, '2025-11-21 02:41:37'),
(8, 30, '/public/images/products/gallery-30-1763706407-0.png', 0, '2025-11-21 13:26:47'),
(11, 40, '/public/images/products/gallery-40-1763710564-0.png', 0, '2025-11-21 14:36:04'),
(12, 2, '/public/images/products/gallery-2-1763711412-0.jpg', 0, '2025-11-21 14:50:12'),
(13, 2, '/public/images/products/gallery-2-1763711412-1.png', 1, '2025-11-21 14:50:12'),
(14, 51, '/public/images/products/gallery/gallery-51-1763712161-1.jpg', 1, '2025-11-21 15:02:41'),
(19, 47, '/public/images/products/product-47-1765522576-693bbc9012405.jpg', 1, '2025-12-12 13:56:16'),
(20, 53, '/public/images/products/product-53-1765524278-693bc336dbe13.jpg', 1, '2025-12-12 14:24:38'),
(21, 54, '/public/images/products/product-54-1765525359-693bc76fbabe3.jpeg', 1, '2025-12-12 14:42:39'),
(22, 55, '/public/images/products/product-55-1765525716-693bc8d46d76d.jpg', 1, '2025-12-12 14:48:36'),
(23, 56, '/public/images/products/product-56-1765526464-693bcbc00bdf6.jpg', 1, '2025-12-12 15:01:04'),
(24, 57, '/public/images/products/product-57-1765527051-693bce0b1feb2.jpg', 1, '2025-12-12 15:10:51'),
(25, 58, '/public/images/products/product-58-1765527233-693bcec1a5aff.jpg', 1, '2025-12-12 15:13:53'),
(27, 59, '/public/images/products/product-59-1765527651-693bd063d985b.jpg', 2, '2025-12-12 15:20:51'),
(28, 41, '/public/images/products/product-41-1765530660-693bdc2405aea.png', 1, '2025-12-12 16:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tên gói, VD: 6 Tháng, 12 Tháng',
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL của variant',
  `duration` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `sort_order` int DEFAULT '0',
  `is_main` tinyint(1) DEFAULT '0' COMMENT 'Gói chính của sản phẩm',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `delivery_type` enum('account','email_only','customer_account') COLLATE utf8mb4_unicode_ci DEFAULT 'account' COMMENT 'Loại giao hàng',
  `variant_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tiêu đề riêng cho variant (optional)',
  `variant_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ảnh riêng cho variant (optional)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `name`, `slug`, `duration`, `price`, `sale_price`, `stock_quantity`, `sort_order`, `is_main`, `status`, `delivery_type`, `variant_title`, `variant_image`, `created_at`, `updated_at`) VALUES
(8, 30, '1 tháng', 'youtube-premium-1thang', '1 tháng', 60000.00, 40000.00, 100, 1, 0, 'active', 'email_only', 'YouTube Premium 1 tháng', '', '2025-11-02 02:44:48', '2026-01-21 00:56:03'),
(9, 30, '3 tháng', 'youtube-premium-3thang', '3 tháng', 180000.00, 120000.00, 100, 2, 0, 'active', 'email_only', 'YouTube Premium 3 tháng', '', '2025-11-02 02:45:57', '2026-01-21 00:56:03'),
(10, 30, '6 tháng', 'youtube-premium-6thang', '6 tháng', 300000.00, 240000.00, 100, 3, 0, 'active', 'email_only', 'YouTube Premium 6 tháng', '', '2025-11-02 02:46:35', '2026-01-21 00:56:03'),
(11, 30, '12 tháng', 'youtube-premium-12thang', '12 tháng', 550000.00, 450000.00, 100, 4, 1, 'active', 'email_only', 'YouTube Premium 12 tháng', '/public/images/variants/variant-11-1763700410.png', '2025-11-02 02:47:23', '2026-01-21 00:56:03'),
(14, 1, '7 ngày', 'netflix-premium-7-ngay', '', 40000.00, 20000.00, 100, 0, 0, 'active', 'account', 'Netflix Premium 7 ngày', '', '2025-11-21 01:13:47', '2025-11-21 01:13:47'),
(15, 1, '15 ngày', 'netflix-premium-15-ngay', '', 75000.00, 40000.00, 100, 0, 0, 'active', 'account', 'Netflix Premium 15 ngày', '', '2025-11-21 01:14:30', '2025-11-21 01:14:30'),
(16, 1, '1 tháng', 'netflix-premium-1-thang', '', 100000.00, 75000.00, 100, 0, 0, 'active', 'account', 'Netflix Premium 1 tháng', '', '2025-11-21 01:14:51', '2025-11-21 01:14:51'),
(17, 1, '3 tháng', 'netflix-premium-3-thang', '', 330000.00, 225000.00, 100, 0, 1, 'active', 'account', 'Netflix Premium 3 tháng', '', '2025-11-21 01:15:22', '2025-11-21 01:16:36'),
(18, 1, '6 tháng', 'netflix-premium-6-thang', '', 550000.00, 450000.00, 100, 0, 0, 'active', 'account', 'Netflix Premium 6 tháng', '', '2025-11-21 01:16:04', '2025-11-21 01:16:04'),
(19, 1, '12 tháng', 'netflix-premium-12-thang', '', 1300000.00, 900000.00, 100, 0, 0, 'active', 'account', 'Netflix Premium 12 tháng', '', '2025-11-21 01:16:32', '2025-11-21 01:16:32'),
(20, 46, '12 tháng', 'icloud-200g-12-thang', '', 700000.00, 400000.00, 100, 0, 1, 'active', 'email_only', 'iCloud 200GB 12 tháng', '', '2025-11-21 02:30:20', '2025-11-21 14:01:37'),
(21, 46, '6 tháng', 'icloud-200g-6-thang', '', 400000.00, 230000.00, 100, 0, 0, 'active', 'email_only', 'iCloud 200GB 6 tháng', '', '2025-11-21 02:30:40', '2025-11-21 02:30:40'),
(22, 6, '6 tháng', 'nordvpn-6-thang', '', 240000.00, 150000.00, 100, 0, 1, 'active', 'account', 'NordVPN sử dụng 5 thiết bị 6 tháng', '', '2025-11-21 02:35:00', '2025-11-21 14:01:37'),
(23, 6, '12 tháng', 'nordvpn-12-thang', '', 500000.00, 280000.00, 100, 0, 0, 'active', 'account', 'NordVPN sử dụng 5 thiết bị 12 tháng', '', '2025-11-21 02:35:19', '2025-11-21 02:35:19'),
(24, 38, '12 tháng', 'nordvpn-12-thang-12-thang', '', 300000.00, 200000.00, 100, 0, 1, 'active', 'account', 'NordVPN sử dụng 2 thiết bị 12 tháng', '', '2025-11-21 02:37:16', '2025-11-21 14:01:37'),
(25, 44, '6 tháng', 'dropbox-plus-2tb-12-thang-6-thang', '', 600000.00, 500000.00, 0, 1, 1, 'active', 'email_only', 'Dropbox Plus 2TB 6 tháng', '', '2025-11-21 02:39:06', '2025-12-12 16:13:02'),
(26, 44, '12 tháng', 'dropbox-plus-2tb-12-thang-12-thang', '', 1400000.00, 800000.00, 0, 2, 0, 'active', 'email_only', 'Dropbox Plus 2TB 12 tháng', '', '2025-11-21 02:39:36', '2025-12-12 16:13:02'),
(27, 43, '12 tháng', 'onedrive-1tb-12-thang', '', 600000.00, 400000.00, 0, 1, 1, 'active', 'email_only', 'OneDrive 1TB 12 tháng', '', '2025-11-21 02:43:15', '2025-12-12 16:13:39'),
(28, 42, '6 tháng', 'google-drive-2tb-gemini-6-thang', '', 400000.00, 300000.00, 100, 0, 1, 'active', 'customer_account', 'Google Drive 2TB kèm theo Gemini Pro 6 tháng', '', '2025-11-21 02:48:02', '2025-11-21 14:01:37'),
(29, 42, '12 tháng', 'google-drive-2tb-gemini-12-thang', '', 1200000.00, 500000.00, 100, 0, 0, 'active', 'customer_account', 'Google Drive 2TB kèm theo Gemini Pro 12 tháng', '', '2025-11-21 02:48:26', '2025-11-21 02:48:26'),
(30, 49, '12 tháng', 'canva-pro-12-thang', '', 300000.00, 150000.00, 100, 1, 1, 'active', 'email_only', 'Canva Pro 12 tháng', '', '2025-11-21 03:14:42', '2025-12-12 15:06:51'),
(31, 39, '12 tháng', 'surfshark-12-thang', '', 1200000.00, 500000.00, 100, 0, 1, 'active', 'account', 'Surfshark VPN 12 tháng', '', '2025-11-21 14:29:44', '2025-11-21 14:29:44'),
(32, 41, '14 ngày', 'protonvpn-14-ngay', '', 50000.00, 40000.00, 100, 1, 1, 'active', 'account', 'ProtonVPN 12 Tháng 12 tháng', '/public/images/variants/variant-32-1763902982.jpg', '2025-11-21 14:33:38', '2025-12-12 16:11:00'),
(33, 40, '30 Ngày', 'expressvpn-30-ngay', '', 100000.00, 50000.00, 100, 1, 1, 'active', 'account', 'ExpressVPN 30 Ngày', '', '2025-11-21 14:36:21', '2025-12-12 16:12:06'),
(34, 32, '12 tháng', 'disney-hotstar-12-thang', '', 2000000.00, 900000.00, 100, 1, 1, 'active', 'account', 'Disney+ Hotstar 12 tháng', '', '2025-11-21 14:44:25', '2026-01-21 00:53:34'),
(35, 2, '6 tháng', 'spotify-family-slot-6-thang', '', 270000.00, 170000.00, 100, 1, 1, 'active', 'customer_account', 'Spotify Family - Slot Tham Gia 6 tháng', '', '2025-11-21 14:50:36', '2025-12-12 15:04:58'),
(36, 2, '12 tháng', 'spotify-family-slot-12-thang', '', 500000.00, 300000.00, 100, 2, 0, 'active', 'customer_account', 'Spotify Family - Slot Tham Gia 12 tháng', '', '2025-11-21 14:51:16', '2025-12-12 15:04:58'),
(37, 47, '1 năm Không Cần Pass', 'duolingo-super-1-nam-khong-can-pass', '', 500000.00, 230000.00, 100, 1, 1, 'active', 'email_only', 'Duolingo Super 1 năm Không Cần Pass', '', '2025-12-12 13:58:05', '2025-12-12 14:01:47'),
(38, 53, '1 Năm', 'microsoft-office-365-1tb-du-lieu-1-nam', NULL, 500000.00, 300000.00, 100, 1, 1, 'active', 'email_only', '', '', '2025-12-12 14:24:38', '2025-12-12 14:26:30'),
(39, 54, 'Gói 1 Năm', 'goodnote-6-1-nam', '', 600000.00, 250000.00, 100, 1, 1, 'active', 'account', '', '', '2025-12-12 14:42:39', '2025-12-12 14:54:43'),
(40, 55, '1 Tháng', 'capcut-pro-1-thang', '', 120000.00, 60000.00, 100, 1, 1, 'active', 'account', '', '', '2025-12-12 14:48:36', '2025-12-12 14:54:15'),
(41, 56, '3 Tháng', 'vieon-vip-3-thang', NULL, 300000.00, 150000.00, 100, 1, 1, 'active', 'customer_account', '', '', '2025-12-12 15:01:04', '2025-12-19 14:26:06'),
(42, 56, '6 Tháng', 'vieon-vip-6-thang', NULL, 500000.00, 280000.00, 100, 2, 0, 'active', 'customer_account', '', '', '2025-12-12 15:01:04', '2025-12-19 14:26:06'),
(43, 57, '1 Tháng Nâng Chính Chủ', 'chat-gpt-plus-1-thang-nang-chinh-chu', NULL, 500000.00, 100000.00, 100, 1, 1, 'active', 'customer_account', '', '', '2025-12-12 15:10:51', '2025-12-18 19:54:45'),
(44, 58, '1 Năm', 'elsa-speak-pro-1-nam', NULL, 1200000.00, 600000.00, 100, 1, 1, 'active', 'customer_account', '', '', '2025-12-12 15:13:53', '2025-12-18 19:55:43');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'ID đơn hàng (nếu có)',
  `rating` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `admin_reply` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Veyrix Shop', 'general', '2025-10-31 03:48:59', '2025-12-11 12:29:08'),
(2, 'site_description', 'Shop bán tài khoản premium uy tín hàng đầu Việt Nam. Cung cấp YouTube Premium, Netflix, Spotify, VPN, Canva với giá tốt nhất. Bảo hành trọn gói.', 'general', '2025-10-31 03:48:59', '2025-12-11 12:28:36'),
(3, 'contact_email', '', 'contact', '2025-10-31 03:48:59', '2025-12-11 12:28:36'),
(4, 'contact_phone', '', 'contact', '2025-10-31 03:48:59', '2025-12-11 12:28:36'),
(5, 'auto_delivery', '1', 'payment', '2025-10-31 03:48:59', '2025-10-31 03:48:59'),
(6, 'currency_symbol', '₫', 'general', '2025-10-31 03:48:59', '2025-10-31 03:48:59'),
(7, 'bank_name', 'MB Bank', 'general', '2025-10-31 07:00:38', '2025-10-31 22:12:57'),
(8, 'bank_account_number', '567892868', 'general', '2025-10-31 07:00:38', '2025-10-31 22:12:51'),
(9, 'bank_account_name', 'Ngo Anh Dung', 'general', '2025-10-31 07:00:38', '2025-10-31 22:13:04'),
(10, 'enable_registration', '1', 'general', '2025-10-31 07:00:38', '2025-10-31 07:00:38'),
(11, 'enable_reviews', '1', 'general', '2025-10-31 07:00:38', '2025-10-31 07:00:38'),
(47, 'bank_code', 'MB', 'general', '2025-11-01 21:54:31', '2025-11-01 21:55:06'),
(50, 'promo_banners', '[{\"image\":\"\\/public\\/images\\/banners\\/691f6aac26f40-1763666604.png\",\"link\":\"\\/youtube-premium\"},{\"image\":\"\\/public\\/images\\/banners\\/691f6aba92f5c-1763666618.png\",\"link\":\"https:\\/\\/www.veyrix.pro\\/duolingo-super-1-nam-khong-can-pass\"}]', 'general', '2025-12-11 12:28:36', '2025-12-12 07:04:41'),
(51, 'bottom_banners', '[{\"image\":\"/public/images/banners/steam-banner.svg\",\"link\":\"/products?category=game-steam\"},{\"image\":\"/public/images/banners/vpn-banner.svg\",\"link\":\"/products?category=edit-anh-video\"},{\"image\":\"/public/images/banners/steam-banner.svg\",\"link\":\"/products?category=game-steam\"},{\"image\":\"/public/images/banners/esim-banner.svg\",\"link\":\"/products?category=window-office\"}]', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(53, 'site_slogan', 'Mua bán tài khoản uy tín', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(55, 'site_logo', 'https://www.veyrix.pro/public/images/logo.ico', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(56, 'site_favicon', 'https://www.veyrix.pro/public/images/logo.ico', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(57, 'timezone', 'Asia/Ho_Chi_Minh', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(58, 'language', 'vi', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(59, 'allow_registration', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(62, 'contact_address', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(63, 'social_facebook', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(64, 'social_zalo', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(65, 'social_telegram', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(66, 'social_youtube', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(67, 'currency', 'VND', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(68, 'min_order_amount', '0', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(69, 'auto_deliver', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(70, 'mail_from_address', '18ukn', 'general', '2025-12-11 12:28:36', '2026-01-26 15:56:30'),
(71, 'mail_from_name', 'Veyrix Shop', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(72, 'smtp_host', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(73, 'smtp_port', '587', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(74, 'smtp_encryption', 'tls', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(75, 'smtp_username', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(76, 'smtp_password', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(77, 'email_notifications', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(78, 'meta_title', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(79, 'meta_description', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(80, 'meta_keywords', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(81, 'google_analytics', 'G-3ZDWMNDKGT', 'general', '2025-12-11 12:28:36', '2026-01-16 12:34:07'),
(82, 'facebook_pixel', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(83, 'google_site_verification', '', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(84, 'enable_cache', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(85, 'cache_time', '3600', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(86, 'max_login_attempts', '5', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(87, 'login_lockout_time', '30', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(88, 'enable_logs', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(89, 'maintenance_mode', '0', 'general', '2025-12-11 12:28:36', '2025-12-18 16:22:40'),
(90, 'netflix_max_days_ago', '3', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(91, 'netflix_allowed_senders', 'info@account.netflix.com', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(92, 'netflix_admin_password', 'anhdung789', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(93, 'debug_mode', '1', 'general', '2025-12-11 12:28:36', '2025-12-11 12:28:36'),
(310, 'seo_title', 'Veyrix Shop: Tài khoản Premium uy tín - YouTube, Netflix ...', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(311, 'seo_description', 'Shop bán tài khoản premium uy tín hàng đầu Việt Nam. Cung cấp YouTube Premium, Netflix, Spotify, VPN, Canva với giá tốt nhất. Bảo hành trọn gói.', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(312, 'seo_keywords', 'YouTube Premium, Netflix, Spotify, VPN, Canva Pro, Youtube Vip, YT giá rẻ', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(313, 'og_image', '', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(314, 'og_site_name', 'Veyrix Shop', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(315, 'og_type', 'website', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(317, 'gtm_id', '', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(319, 'google_verification', '', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(320, 'robots_meta', 'index, follow', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(321, 'canonical_url', 'https://veyrix.pro', 'general', '2026-01-16 12:24:19', '2026-01-16 12:24:19'),
(418, 'sepay_enabled', '1', 'general', '2026-01-26 15:56:30', '2026-01-26 15:56:30'),
(419, 'sepay_api_key', 'spsk_live_pWTFDraMHrKGW4nzA4HuDz8FNzdarYQD', 'general', '2026-01-26 15:56:30', '2026-01-26 16:14:42'),
(420, 'sepay_account_number', '567892868', 'general', '2026-01-26 15:56:30', '2026-01-26 15:56:30'),
(421, 'sepay_bank_code', 'MB', 'general', '2026-01-26 15:56:30', '2026-01-26 15:56:30'),
(422, 'sepay_account_name', 'NGO ANH DUNG', 'general', '2026-01-26 15:56:30', '2026-01-26 15:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('deposit','withdraw','order','refund') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference_id` int DEFAULT NULL COMMENT 'ID đơn hàng hoặc giao dịch liên quan',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `created_at`) VALUES
(1, 1, 'deposit', 200000.00, 50000.00, 250000.00, 'Nạp tiền ví MoMo', NULL, '2025-10-31 03:51:52'),
(2, 1, 'order', 135000.00, 250000.00, 115000.00, 'Thanh toán đơn ORD10001', 1, '2025-10-31 03:51:52'),
(3, 2, 'order', 25000.00, 50000.00, 25000.00, 'Thanh toán đơn ORD10002', 2, '2025-10-31 03:51:52'),
(4, 5, '', 400000.00, 0.00, 0.00, '', NULL, '2025-10-31 06:37:04'),
(5, 1, '', 500000.00, 0.00, 0.00, '', NULL, '2025-10-31 07:20:33'),
(6, 8, '', 400000.00, 0.00, 0.00, '', NULL, '2025-11-01 13:40:49'),
(7, 1, '', 900000.00, 0.00, 0.00, 'a', NULL, '2025-12-12 09:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `role` enum('customer','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'customer',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `status`, `balance`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@veyrix.pro', '$2y$10$5.VXTTIB5OZG0mqe.ah.8e8.hYZyS7HTJgRpJXoeG3773nGs6z70S', 'Administrator', '', NULL, 'admin', 'active', 1400000.00, '2025-10-31 03:48:59', '2025-12-12 09:50:50'),
(2, 'dungna', 'dung@veyrix.pro', '$2y$10$5.VXTTIB5OZG0mqe.ah.8e8.hYZyS7HTJgRpJXoeG3773nGs6z70S', 'Ngô Anh Dũng', '0987654321', NULL, 'customer', 'active', 250000.00, '2025-10-31 03:51:22', '2025-10-31 03:54:28'),
(4, 'admin2', 'admin2@veyrix.pro', '$2y$10$5.VXTTIB5OZG0mqe.ah.8e8.hYZyS7HTJgRpJXoeG3773nGs6z70S', 'Quản trị phụ', '0909090909', NULL, 'admin', 'active', 0.00, '2025-10-31 03:51:22', '2025-10-31 03:54:30'),
(5, 'admin1', 'nadung2k4@gmail.com', '$2y$10$Prb4N0B8NbgFZU5i1x6g6Oj0QXijz49/7r7Ck04HIiRs4So8LYl4m', 'Ngô Anh Dũng1', '', NULL, 'admin', 'active', 380000.00, '2025-10-31 03:53:39', '2025-11-07 17:41:40'),
(6, 'nadung2k41', 'nadung2k41@gmail.com', '$2y$10$ypMg701E6Quksc/Ib7M/1OhQB/tLsXFT2DzxbKnmQ57mFnVH1pMkW', 'nadung2k41@gmail.com', NULL, NULL, 'customer', 'active', 0.00, '2025-10-31 09:07:55', '2025-10-31 19:57:43'),
(7, 'fastuser', 'nadung2k412@gmail.com', '$2y$10$YxX3rB92oUaNfkxMEr1pVOxWFlqH6LqxyeYhVsO5gLp3GUxMmcvA2', 'Anh Dũng Ngô', NULL, NULL, 'customer', 'active', 0.00, '2025-10-31 19:39:05', '2025-10-31 19:55:41'),
(8, 'fastuser1', 'nadung2k4@gmail.com1', '$2y$10$/QoE.3H02tWQJ1jDH2whTOmVncZkkLHLIIDkaviZxP/5NnC0WvjsW', 'Anh Dũng Ngô', NULL, NULL, 'customer', 'active', 280000.00, '2025-11-01 08:27:43', '2025-11-02 10:02:26'),
(9, 'fastuser2', 'nadung2k4@gmail.com3', '$2y$10$l/TFpfZPhGubZHqiSQFGp.4q9K5qQMGxw2xfiYJTyU4tSwXqnleDG', '1', NULL, NULL, '', 'active', 0.00, '2025-11-02 14:05:38', '2025-11-02 14:05:38'),
(10, 'nadung1', 'nadung2k411@gmail.com', '$2y$10$S.5spuVqarQzuHz51EYv6.jOLZtbRu1pQytrzQF8QuqYrbFbgdCKO', 'Ngô Anh Dũng', NULL, NULL, '', 'active', 0.00, '2025-12-11 15:44:11', '2025-12-11 15:44:11'),
(11, 'fastuser33', 'nadung2k131@gmail.com', '$2y$10$3j2/dnzWVQRRAP0HEd1Dj.cF38d/Wx4cTWoXeS6OE7XOObY/3Kvw6', 'Ngô Anh Dũng', NULL, NULL, '', 'active', 0.00, '2025-12-11 16:10:39', '2025-12-11 16:10:39'),
(12, 'fastuser523', 'fastuser523@gmail.com', '$2y$10$WtXALsGfKjoY86YNKBYOEO/fWHIdFFDssfJdz/ncMPZ.eXsRcjBg6', 'Ngô Anh Dũng', NULL, NULL, '', 'active', 0.00, '2025-12-11 17:10:41', '2025-12-11 17:10:41'),
(13, 'dolliesilver', 'dolliesilver@airsworld.net', '$2y$10$nop7kSm2MBco58h7oS0.4.p7cbt0WKHLnHvt4DL9wnRtnR74bos9G', 'Linh Linh', NULL, NULL, '', 'active', 0.00, '2025-12-27 18:51:53', '2025-12-27 18:51:53'),
(14, 'saybluza', 'nonghongtuan06@gmail.com', '$2y$10$h0gPzX3MA3ju/focgJ3zQ.TJ0wc.FwwYsMXrdSFiIHpnUk9GLg9e2', 'Hồng Tuấn', NULL, NULL, '', 'active', 0.00, '2026-01-20 12:12:17', '2026-01-20 12:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_logins`
--

-- --------------------------------------------------------

--
-- Table structure for table `visitor_logs`
--

CREATE TABLE `visitor_logs` (
  `id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `request_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_stats`
--

CREATE TABLE `visitor_stats` (
  `date` date NOT NULL,
  `access_count` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `visitor_stats`
--

INSERT INTO `visitor_stats` (`date`, `access_count`) VALUES
('2025-12-11', 2045),
('2025-12-12', 3282),
('2025-12-13', 1058),
('2025-12-14', 533),
('2025-12-15', 722),
('2025-12-16', 815),
('2025-12-17', 344),
('2025-12-18', 1696),
('2025-12-19', 785),
('2025-12-20', 780),
('2025-12-21', 805),
('2025-12-22', 5064),
('2025-12-23', 515),
('2025-12-24', 1056),
('2025-12-25', 655),
('2025-12-26', 884),
('2025-12-27', 1532),
('2025-12-28', 623),
('2025-12-29', 775),
('2025-12-30', 882),
('2025-12-31', 1202),
('2026-01-01', 906),
('2026-01-02', 1156),
('2026-01-03', 1496),
('2026-01-04', 1302),
('2026-01-05', 997),
('2026-01-06', 914),
('2026-01-07', 945),
('2026-01-08', 280),
('2026-01-09', 516),
('2026-01-10', 745),
('2026-01-11', 1146),
('2026-01-12', 1175),
('2026-01-13', 802),
('2026-01-14', 1017),
('2026-01-15', 473),
('2026-01-16', 1315),
('2026-01-17', 342),
('2026-01-18', 298),
('2026-01-19', 419),
('2026-01-20', 371),
('2026-01-21', 522),
('2026-01-22', 768),
('2026-01-23', 460),
('2026-01-24', 452),
('2026-01-25', 442),
('2026-01-26', 737),
('2026-01-27', 41);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('deposit','withdraw','purchase','refund','bonus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `created_at`) VALUES
(1, 7, 'deposit', 500000.00, 0.00, 0.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-01 03:14:09'),
(2, 5, 'deposit', 100000.00, 400000.00, 400000.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-01 03:28:35'),
(3, 5, 'deposit', 500000.00, 400000.00, 400000.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-01 15:09:40'),
(4, 8, 'deposit', 500000.00, 0.00, 0.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-01 19:58:32'),
(5, 8, '', -40000.00, 400000.00, 360000.00, 'Thanh toán đơn hàng #C440', 77, '2025-11-02 04:20:11'),
(6, 8, '', -40000.00, 360000.00, 320000.00, 'Thanh toán đơn hàng #Y784', 78, '2025-11-02 04:21:59'),
(7, 8, 'deposit', 100000.00, 320000.00, 320000.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-02 04:24:10'),
(8, 8, '', -40000.00, 320000.00, 280000.00, 'Thanh toán đơn hàng #T530', 92, '2025-11-02 17:02:26'),
(9, 5, 'deposit', 500000.00, 400000.00, 400000.00, 'Nạp tiền - Chờ xác nhận', 0, '2025-11-07 03:16:20'),
(10, 5, '', -10000.00, 400000.00, 390000.00, 'Thanh toán đơn hàng #W607', 128, '2025-11-08 00:24:44'),
(11, 5, '', -10000.00, 390000.00, 380000.00, 'Thanh toán đơn hàng #K357', 129, '2025-11-08 00:41:40');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(7, 7, 46, '2025-11-01 04:33:29'),
(9, 7, 44, '2025-11-01 04:33:31'),
(10, 7, 41, '2025-11-01 04:33:32'),
(11, 7, 42, '2025-11-01 04:33:33'),
(12, 7, 40, '2025-11-01 04:33:34'),
(21, 8, 30, '2025-11-02 17:23:14'),
(52, 5, 30, '2025-11-08 00:22:16'),
(56, 1, 57, '2025-12-18 19:58:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts_stock`
--
ALTER TABLE `accounts_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`,`variant_id`),
  ADD UNIQUE KEY `unique_guest_cart_item` (`session_id`,`product_id`,`variant_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `cart_ibfk_2` (`product_id`),
  ADD KEY `idx_cart_user` (`user_id`,`created_at`),
  ADD KEY `idx_cart_session` (`session_id`,`created_at`),
  ADD KEY `cart_ibfk_3` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_sender_type` (`sender_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_chat_messages_image` (`image`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_telegram_chat_id` (`telegram_chat_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `netflix_email_cache`
--
ALTER TABLE `netflix_email_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_uid_idx` (`email_uid`,`recipient`),
  ADD KEY `recipient_idx` (`recipient`),
  ADD KEY `email_date_idx` (`email_date`);

--
-- Indexes for table `netflix_notifications`
--
ALTER TABLE `netflix_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_uid` (`email_uid`),
  ADD KEY `idx_recipient` (`recipient`),
  ADD KEY `idx_email_date` (`email_date`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_recipient_id` (`recipient`,`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `online_users`
--
ALTER TABLE `online_users`
  ADD PRIMARY KEY (`session_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `order_status` (`order_status`),
  ADD KEY `idx_customer_email` (`customer_email`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`);

--
-- Indexes for table `product_gallery`
--
ALTER TABLE `product_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `visitor_stats`
--
ALTER TABLE `visitor_stats`
  ADD PRIMARY KEY (`date`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts_stock`
--
ALTER TABLE `accounts_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `netflix_email_cache`
--
ALTER TABLE `netflix_email_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `netflix_notifications`
--
ALTER TABLE `netflix_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `product_gallery`
--
ALTER TABLE `product_gallery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=535;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `visitor_logs`
--
ALTER TABLE `visitor_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts_stock`
--
ALTER TABLE `accounts_stock`
  ADD CONSTRAINT `accounts_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_gallery`
--
ALTER TABLE `product_gallery`
  ADD CONSTRAINT `product_gallery_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
