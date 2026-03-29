-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: 127.0.0.1
-- Generation Time: Dec 21, 2025 at 05:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `booking`
--

-- --------------------------------------------------------

--
-- 1. Bảng `users`
-- (Đã tích hợp cột Hạng thành viên và Tổng chi tiêu)
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `phonenumber` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `total_spent` bigint(20) DEFAULT 0 COMMENT 'Tổng tiền đã thanh toán (Dùng xếp hạng)',
  `rank_level` enum('Level 1','Level 2','Level 3','Level 4') DEFAULT 'Level 1' COMMENT 'Hạng thành viên',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 2. Bảng `platform`
--
DROP TABLE IF EXISTS `platform`;
CREATE TABLE `platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('page','group','tiktok') DEFAULT 'page',
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 3. Bảng `package`
--
DROP TABLE IF EXISTS `package`;
CREATE TABLE `package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `overview` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `slot_count` int(11) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 4. Bảng `service_option`
--
DROP TABLE IF EXISTS `service_option`;
CREATE TABLE `service_option` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `platform_id` (`platform_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 5. Bảng `orders`
-- (Đã tích hợp cột thống kê tương tác FB)
--
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `service_option_id` int(11) NOT NULL,
  `price_at_purchase` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `product_link` varchar(255) NOT NULL,
  `content_url` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `admin_feedback_content` text DEFAULT NULL,
  `admin_feedback_files` text DEFAULT NULL,
  `result_links` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','design_review','waiting_payment','paid','in_progress','completed','cancelled') DEFAULT 'pending',
  -- Các cột mới cho tính năng đo lường hiệu quả
  `fb_likes` int(11) DEFAULT 0,
  `fb_comments` int(11) DEFAULT 0,
  `fb_shares` int(11) DEFAULT 0,
  `stats_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `staff_id` (`staff_id`),
  KEY `service_option_id` (`service_option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 6. Bảng `post`
--
DROP TABLE IF EXISTS `post`;
CREATE TABLE `post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  `result_link` TEXT DEFAULT NULL COMMENT 'Link bài đăng thực tế',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 7. Bảng `schedules`
--
DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `platform_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','scheduled','posted','cancelled') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_slot` (`platform_id`,`start_time`),
  KEY `post_id` (`post_id`),
  KEY `platform_id` (`platform_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 8. Bảng `portfolio`
--
DROP TABLE IF EXISTS `portfolio`;
CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT '#',
  `type` enum('link','pdf') DEFAULT 'link',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 9. Bảng `contacts`
--
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 10. Bảng `notifications`
--
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 11. Bảng `settings`
--
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key_name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- DỮ LIỆU MẪU (DATA SEEDING)
-- --------------------------------------------------------

-- 1. Platform (3 Kênh như trong ảnh báo giá)
INSERT INTO `platform` (`id`, `name`, `type`, `active`) VALUES
(1, 'PAGE GRAB FAN THÁNG 9', 'page', 1),
(2, 'PAGE RAP FAN THÁM THÍNH', 'page', 1),
(3, 'GROUP CỘNG ĐỒNG GRAB VIỆT Level 2', 'group', 1);

-- 2. Package
INSERT INTO `package` (`id`, `slug`, `name`, `overview`, `description`, `slot_count`, `active`) VALUES
(1, 'goi-1a', 'Gói 1A', 'Gói cơ bản nhất để ra mắt hình ảnh sản phẩm.', '- 1 poster sản phẩm\r\n- Hỗ trợ resize ảnh bìa', 1, 1),
(2, 'goi-1b', 'Gói 1B', 'Gói tập trung vào định dạng video highlight.', '- 1 video highlight (30s)\r\n- Chèn text hiệu ứng', 1, 1),
(3, 'goi-1c', 'Gói 1C', 'Giải pháp chia sẻ link trực tiếp.', '- Share link sản phẩm trực tiếp từ Youtube/Soundcloud', 1, 1),
(4, 'goi-2', 'Gói 2', 'Combo hình ảnh và video tiêu chuẩn.', '- 1 poster sản phẩm\r\n- 1 video highlight', 2, 1),
(5, 'goi-3', 'Gói 3', 'Gói phổ biến với đầy đủ định dạng cơ bản.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight', 3, 1),
(6, 'goi-4', 'Gói 4', 'Gói nâng cao với ghim bài và bình luận.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight\r\n- 1 post bình luận về sản phẩm\r\n- 1 tuần ghim bài đăng trên page', 4, 1),
(7, 'goi-5', 'Gói 5', 'Gói toàn diện nhất, bao gồm cả meme và ảnh bìa.', '- 1 poster sản phẩm\r\n- 1 post trích lyrics highlight\r\n- 1 video highlight\r\n- 1 post bình luận về sản phẩm\r\n- 2 bài đăng về tin tức/meme\r\n- 2 tuần ghim bài đăng trên page\r\n- Đặt poster làm ảnh bìa 1 tuần', 6, 1);

-- 3. Service Option (CẬP NHẬT GIÁ MỚI 100k - 700k)
INSERT INTO `service_option` (`package_id`, `platform_id`, `price`) VALUES
-- Gói 1A, 1B, 1C: Đồng giá 100k
(1, 1, 100000), (1, 2, 100000), (1, 3, 100000),
(2, 1, 100000), (2, 2, 100000), (2, 3, 100000),
(3, 1, 100000), (3, 2, 100000), (3, 3, 100000),
-- Gói 2: 200k
(4, 1, 200000), (4, 2, 200000), (4, 3, 200000),
-- Gói 3: 300k
(5, 1, 300000), (5, 2, 300000), (5, 3, 300000),
-- Gói 4: 500k
(6, 1, 500000), (6, 2, 500000), (6, 3, 500000),
-- Gói 5: 700k
(7, 1, 700000), (7, 2, 700000), (7, 3, 700000);

-- 4. Users (Mật khẩu mặc định: 123456)
-- Cập nhật dữ liệu Total Spent và Rank cho user
INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `phonenumber`, `is_active`, `total_spent`, `rank_level`) VALUES
(1, 'Lê Văn Staff', 'staff@schedio.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '0988888888', 1, 0, 'Level 1'),
(2, 'Admin User', 'admin@schedio.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0999999999', 1, 0, 'Level 1'),
(3, 'Nguyễn Văn A (MCK)', 'mck@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0911111111', 1, 100000, 'Level 1'),
(4, 'Trần Thị B (Tlinh)', 'tlinh@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0922222222', 1, 2500000, 'Level 2'),
(5, 'Phạm D (Binz)', 'binz@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0933333333', 1, 15000000, 'Level 4'),
(6, 'Hoàng E (Rhymastic)', 'rhym@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0944444444', 1, 5500000, 'Level 3');

-- 5. Orders (Đơn hàng đa dạng trạng thái)
INSERT INTO `orders` (`id`, `user_id`, `staff_id`, `service_option_id`, `price_at_purchase`, `title`, `product_link`, `content_url`, `note`, `status`, `result_links`, `created_at`, `fb_likes`, `fb_comments`) VALUES
-- Đơn chờ xử lý
(1, 3, 1, 1, 100000, 'MV Chìm Sâu', 'https://youtube.com/watch?v=mck', 'https://drive.google.com/mck', 'Làm poster mood buồn.', 'pending', NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 0),
-- Đơn đang duyệt demo (Khách Tlinh)
(2, 4, 1, 10, 200000, 'Single Gái Độc Thân', 'https://youtube.com/watch?v=tlinh', 'https://drive.google.com/tlinh', 'Style sexy, màu hồng neon.', 'design_review', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), 0, 0),
-- Đơn đã hoàn thành (Khách Binz - VIP)
(3, 5, 1, 21, 700000, 'Bigcityboi Comeback', 'https://youtube.com/watch?v=binz', 'https://drive.google.com/binz', 'Full option, push mạnh nhất có thể.', 'completed', 'https://facebook.com/schedio/posts/123456789', DATE_SUB(NOW(), INTERVAL 10 DAY), 15400, 340),
-- Đơn đã thanh toán chờ đăng (Khách Rhymastic)
(4, 6, 1, 15, 300000, 'Nến và Hoa', 'https://youtube.com/watch?v=rhym', 'https://drive.google.com/rhym', 'Đăng đúng giờ hoàng đạo nha.', 'paid', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), 0, 0),
-- Đơn đã hủy
(5, 3, NULL, 7, 100000, 'Demo Rap Nháp', 'https://soundcloud.com/mck', '', 'Thôi mình đổi ý.', 'cancelled', NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), 0, 0);

-- 6. Post & Schedules
-- Đơn 1 (Pending)
INSERT INTO `post` (`order_id`, `status`) VALUES (1, 'pending');
INSERT INTO `schedules` (`post_id`, `platform_id`, `start_time`, `end_time`, `status`) VALUES 
(LAST_INSERT_ID(), 1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 20:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 20:30:00'), 'pending');

-- Đơn 2 (Design Review - Combo 2 slot)
INSERT INTO `post` (`order_id`, `status`) VALUES (2, 'pending');
INSERT INTO `schedules` (`post_id`, `platform_id`, `start_time`, `end_time`, `status`) VALUES 
(LAST_INSERT_ID(), 1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 19:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 19:30:00'), 'pending');
INSERT INTO `post` (`order_id`, `status`) VALUES (2, 'pending'); -- Slot 2
INSERT INTO `schedules` (`post_id`, `platform_id`, `start_time`, `end_time`, `status`) VALUES 
(LAST_INSERT_ID(), 1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY), ' 09:30:00'), 'pending');

-- Đơn 3 (Completed - Gói 5 có 6 slot, demo 1 bài posted)
INSERT INTO `post` (`order_id`, `status`) VALUES (3, 'posted');
INSERT INTO `schedules` (`post_id`, `platform_id`, `start_time`, `end_time`, `status`) VALUES 
(LAST_INSERT_ID(), 3, CONCAT(DATE_SUB(CURDATE(), INTERVAL 9 DAY), ' 20:00:00'), CONCAT(DATE_SUB(CURDATE(), INTERVAL 9 DAY), ' 20:30:00'), 'posted');

-- Đơn 4 (Paid - Scheduled)
INSERT INTO `post` (`order_id`, `status`) VALUES (4, 'scheduled');
INSERT INTO `schedules` (`post_id`, `platform_id`, `start_time`, `end_time`, `status`) VALUES 
(LAST_INSERT_ID(), 3, CONCAT(CURDATE(), ' 21:00:00'), CONCAT(CURDATE(), ' 21:30:00'), 'scheduled');

-- 7. Notifications
INSERT INTO `notifications` (`user_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(3, 1, 'Đặt hàng thành công', 'Đơn hàng #SCD-001 của bạn đã được khởi tạo.', 'success', 0, NOW()),
(4, 2, 'Admin phản hồi', 'Admin đã gửi Demo cho đơn hàng #SCD-002. Vui lòng kiểm tra.', 'warning', 0, NOW()),
(5, 3, 'Hoàn tất đơn hàng', 'Đơn hàng #SCD-003 đã hoàn thành. Cảm ơn bạn đã sử dụng dịch vụ.', 'success', 1, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- 8. Settings
INSERT INTO `settings` (`key_name`, `value`) VALUES
('site_name', 'Schedio'),
('site_description', 'Dịch vụ booking đăng bài truyền thông trên các Fanpage'),
('site_logo', ''), 
('contact_email', 'support@schedio.vn'),
('contact_hotline', '(084) 123 456 789'),
('contact_address', 'Hàn Thuyên, khu phố 6, P.Linh Trung, Thủ Đức, TP.HCM'),
('bank_name', 'MB'),
('bank_account', '0344377104'),
('bank_owner', 'TRAN ANH DUC'),
('social_facebook', 'https://facebook.com/schedio'),
('social_tiktok', 'https://tiktok.com/@schedio'),
('maintenance_mode', '0');

-- 9. Constraints (Khóa ngoại)
ALTER TABLE `service_option`
  ADD CONSTRAINT `service_option_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`),
  ADD CONSTRAINT `service_option_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`);

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_option_id`) REFERENCES `service_option` (`id`),
  ADD CONSTRAINT `fk_order_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `schedules_ibfk_2` FOREIGN KEY (`platform_id`) REFERENCES `platform` (`id`);

ALTER TABLE `portfolio`
  ADD CONSTRAINT `portfolio_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package` (`id`);

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;