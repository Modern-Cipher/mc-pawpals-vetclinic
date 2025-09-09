-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 22, 2025 at 06:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pawpals`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `audience` enum('all','admins','staff','owners') NOT NULL DEFAULT 'all',
  `location` enum('dashboard','landing','both') NOT NULL DEFAULT 'dashboard',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(2048) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `audience`, `location`, `is_published`, `published_at`, `expires_at`, `image_path`, `external_url`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'Maintenances', 'Adding New Featuresx', 'all', 'landing', 0, '2025-08-21 11:00:00', '2025-08-31 11:03:00', 'uploads/announcements/CMJSNA.png', 'https://www.facebook.com', 1, 1, '2025-08-21 03:09:31', '2025-08-21 04:47:27'),
(2, 'Maintenance Today', 'testing announcement', 'staff', 'dashboard', 1, '2025-08-21 11:24:00', '2025-08-21 17:01:00', 'uploads/announcements/5JNM2W.png', 'https://www.facebook.com', 1, 1, '2025-08-21 03:24:22', '2025-08-21 09:00:26'),
(3, 'SDFSDF', 'SDFSDFSD', 'all', 'landing', 1, '2025-08-21 12:00:00', '2025-08-31 23:00:00', 'uploads/announcements/WBU73B.png', 'https://www.facebook.com', 1, 1, '2025-08-21 07:14:37', '2025-08-21 09:55:11'),
(4, 'fasf', 'fasfas', 'staff', 'landing', 1, NULL, NULL, NULL, NULL, 1, 1, '2025-08-21 09:02:26', '2025-08-21 09:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `email_configs`
--

CREATE TABLE `email_configs` (
  `id` int(11) NOT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `smtp_host` varchar(191) NOT NULL,
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_secure` enum('tls','ssl') NOT NULL DEFAULT 'tls',
  `smtp_user` varchar(191) NOT NULL,
  `smtp_pass_enc` text NOT NULL,
  `from_email` varchar(191) NOT NULL,
  `from_name` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_configs`
--

INSERT INTO `email_configs` (`id`, `provider`, `smtp_host`, `smtp_port`, `smtp_secure`, `smtp_user`, `smtp_pass_enc`, `from_email`, `from_name`, `is_active`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'gmail', 'smtp.gmail.com', 587, 'tls', 'mdctechservices@gmail.com', 'jjei jeyz ojmx isdb', 'mdctechservices@gmail.com', 'PawPals', 1, NULL, '2025-08-22 08:25:38', '2025-08-22 08:12:16'),
(2, 'gmail', 'smtp.gmail.com', 587, 'tls', 'mdctechservices@gmail.com', 'jjei jeyz ojmx isdb', 'mdctechservices@gmail.com', 'PawPals', 0, NULL, '2025-08-22 08:23:34', '2025-08-22 08:20:29');

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `subject` varchar(191) NOT NULL,
  `html` mediumtext NOT NULL,
  `text` mediumtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (`id`, `slug`, `name`, `subject`, `html`, `text`, `is_active`, `updated_by`, `updated_at`, `created_at`) VALUES
(1, 'otp', 'One-Time Password', 'Your OTP Code', '<h2 style=\"margin:0 0 6px\">{{title}}</h2>\r\n  <p>Use the code below to continue.</p>\r\n  <div style=\"font-size:28px;letter-spacing:6px;font-weight:700;background:#0ea5e9;color:#fff;display:inline-block;padding:12px 18px;border-radius:10px\">\r\n    {{code}}\r\n  </div>\r\n  <p style=\"color:#6b7280\">This code expires in {{ttl}} minutes.</p>\r\n  <p style=\"color:#6b7280\">— {{brand_name}}</p>', 'Your code: {{code}} (expires in {{ttl}} minutes)', 1, NULL, '2025-08-22 08:01:52', '2025-08-22 08:01:52'),
(2, 'password_reset', 'Password Reset', 'Reset your PawPals password', '<h2 style=\"margin:0 0 6px\">{{title}}</h2>\r\n  <p>Click the button below to reset your password.</p>\r\n  <p><a href=\"{{reset_url}}\" style=\"background:#10b981;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block\">Reset Password</a></p>\r\n  <p style=\"color:#6b7280\">The link expires in {{ttl}} minutes.</p>\r\n  <p style=\"color:#6b7280\">— {{brand_name}}</p>', 'Reset link (valid {{ttl}} minutes): {{reset_url}}', 1, NULL, '2025-08-22 08:01:52', '2025-08-22 08:01:52');

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `rating` decimal(3,1) NOT NULL,
  `status` enum('pending','approved','archived') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(10) UNSIGNED DEFAULT NULL,
  `message_100` varchar(100) GENERATED ALWAYS AS (left(`message`,100)) STORED,
  `unique_key` char(64) GENERATED ALWAYS AS (sha2(concat_ws('|',lcase(`email`),`message_100`,cast(`created_at` as date)),256)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`id`, `user_id`, `name`, `email`, `message`, `rating`, `status`, `created_at`, `updated_at`, `approved_at`, `approved_by`, `archived_at`, `archived_by`) VALUES
(1, NULL, 'aly', 'admin@example.com', 'testing feedback', 2.0, 'archived', '2025-08-22 08:10:43', '2025-08-22 13:10:06', '2025-08-22 13:10:05', 1, '2025-08-22 13:10:06', 1),
(2, 1, 'Aly Yla', 'admin@example.com', 'testing 2', 5.0, 'archived', '2025-08-22 10:03:01', '2025-08-22 13:53:54', '2025-08-22 12:20:49', 1, '2025-08-22 13:53:54', 1),
(3, NULL, 'devdev', 'dev@gmail.com', 'test 3', 2.8, 'approved', '2025-08-22 11:25:59', '2025-08-22 13:10:12', '2025-08-22 13:10:12', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` varchar(50) NOT NULL COMMENT 'e.g., failed_password, failed_session_conflict',
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `ip_address`, `user_agent`, `status`, `attempted_at`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '', '2025-08-20 11:32:23'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '', '2025-08-20 11:32:46'),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '', '2025-08-20 11:36:29'),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 14:38:58'),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 14:39:41'),
(6, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-20 14:40:04'),
(7, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 14:55:43'),
(8, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 15:00:16'),
(9, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 15:01:17'),
(10, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-20 15:02:05'),
(11, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-20 15:09:35'),
(12, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 19:41:09'),
(13, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-20 22:27:29'),
(14, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-20 22:27:43'),
(15, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-20 22:29:02'),
(16, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-21 01:04:30'),
(17, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-21 01:04:47'),
(18, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-21 01:05:42'),
(19, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-21 08:03:50'),
(20, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-22 17:13:45'),
(21, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-22 17:18:29'),
(22, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-22 17:39:57'),
(23, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-22 17:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `code_hash` char(60) NOT NULL,
  `purpose` varchar(50) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `user_id`, `email`, `code_hash`, `purpose`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'menongdc@gmail.com', '$2y$10$u7kb8WXuFqChZKMO5rYyTuLYtV1jCd231pCrYI/Za2IsD47bEV1E2', 'password_reset', '2025-08-22 17:21:05', '2025-08-22 17:12:13', '2025-08-22 17:11:05'),
(2, 1, 'menongdc@gmail.com', '583d29d03b4c0b2c48bad8b4aec5049d54f5fd15a062d977c0891a8d93ea', 'reset', '2025-08-22 17:50:56', NULL, '2025-08-22 17:40:56'),
(3, 1, 'menongdc@gmail.com', '75773d570729817e084aa62a65c61f33f4d77ceaab6d7a98bbeeecd0e759', 'reset', '2025-08-22 17:52:39', NULL, '2025-08-22 17:42:39'),
(4, 1, 'menongdc@gmail.com', 'a2980a5a63cd11d2496c429e334bee388b16e7aa4b96127bc450041aa047', 'reset', '2025-08-22 17:53:19', NULL, '2025-08-22 17:43:19'),
(5, 1, 'menongdc@gmail.com', '6a8522949139595b2256d3b230c1820b9b4e01930c3651896c6e87148241', 'reset', '2025-08-22 17:53:33', NULL, '2025-08-22 17:43:33'),
(6, 1, 'menongdc@gmail.com', '3cdfac5c2782e8b48cc4e5a77718f10938e32224184b1bb13bdd83a2f838', 'reset', '2025-08-22 17:54:09', NULL, '2025-08-22 17:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `email`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'menongdc@gmail.com', 'ae27e8b25090ab134775d1c33485162fe64dc84deac51aa8b8d678114dc2a9c0', '2025-08-22 17:44:37', '2025-08-22 17:18:04', '2025-08-22 17:14:37'),
(2, 1, 'menongdc@gmail.com', 'cf1f5983a4fdf0177b5eeb5969841a8ccf565f1f35670d3e93d96ecf3e51d847', '2025-08-22 18:15:17', NULL, '2025-08-22 17:45:17');

-- --------------------------------------------------------

--
-- Table structure for table `pet_care_tips`
--

CREATE TABLE `pet_care_tips` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` enum('diet','puppy','health','other') DEFAULT 'health',
  `summary` text DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `content_type` enum('text','file','url') DEFAULT 'text',
  `external_url` varchar(1000) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pet_care_tips`
--

INSERT INTO `pet_care_tips` (`id`, `title`, `category`, `summary`, `body`, `content_type`, `external_url`, `image_path`, `file_path`, `is_published`, `published_at`, `expires_at`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '5 Tips for a Healthy Pet Diet', 'diet', 'Ensure your furry friend gets the best nutrition with these simple dietary tips.', 'Ensure your furry friend gets the best nutrition with these simple dietary tips.', 'text', '', 'uploads/petcare/images/W2arUD.png', NULL, 1, NULL, NULL, 1, 1, '2025-08-21 12:31:22', '2025-08-21 12:31:22'),
(2, 'Welcoming a New Puppy', 'puppy', 'From socialization to house training, here\'s what you need for a smooth transition.', 'From socialization to house training, here\'s what you need for a smooth transition.', 'text', '', 'uploads/petcare/images/FP5LLz.png', NULL, 1, NULL, NULL, 1, 1, '2025-08-21 12:32:53', '2025-08-21 12:32:53'),
(3, 'Testing Pdf', 'health', 'pdf summury', '', 'file', '', 'uploads/petcare/images/omSfE8.png', 'uploads/petcare/files/LtAppQ.pdf', 1, '2025-08-21 12:00:00', NULL, 1, 1, '2025-08-21 12:47:22', '2025-08-22 05:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL COMMENT 'FK to users.id',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_by`, `updated_at`) VALUES
(1, 'clinic_name', 'PawPals', 1, '2025-08-20 15:25:17'),
(2, 'clinic_tagline', 'Dedicated to providing top-tier veterinary services and compassionate care for your beloved pets in Marawi City.', NULL, '2025-08-20 11:32:24'),
(4, 'contact_phone', '6391 234 5678', 1, '2025-08-20 11:46:03'),
(5, 'contact_email', 'info@pawpals.com', 1, '2025-08-20 11:46:03'),
(6, 'hero_title', 'We take care of your pets with experts 🐱ddd', 1, '2025-08-22 05:57:58'),
(7, 'hero_subtitle', 'The best place for your best friend. Providing top-tier veterinary services and compassionate care in Marawi City. ', 1, '2025-08-21 00:38:17'),
(8, 'footer_tagline', 'Dedicated to providing top-tier veterinary services and compassionate care for your beloved pets in Marawi City.', 1, '2025-08-21 00:38:12'),
(9, 'contact_houseno', '001', 1, '2025-08-21 00:13:53'),
(10, 'contact_street', 'streets', 1, '2025-08-21 00:13:53'),
(11, 'contact_barangay', 'barangay', 1, '2025-08-21 00:13:46'),
(12, 'contact_municipality', 'Marawi City', 1, '2025-08-20 11:46:03'),
(13, 'contact_province', 'Lanao del Sur', 1, '2025-08-20 14:16:40'),
(14, 'contact_zipcode', '9700', 1, '2025-08-20 11:46:03'),
(48, 'hero_image_path', 'uploads/settings/veterinarian_2_86bd8c8c957b.jpg', 1, '2025-08-20 19:07:13');

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `platform` varchar(50) NOT NULL,
  `icon_class` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `icon_class`, `url`, `display_order`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(5, 'Facebooks', 'fa-brands fa-facebook', 'https://www.facebook.com', 1, NULL, NULL, '2025-08-20 16:55:21', '2025-08-21 00:25:36'),
(6, 'Instagram', 'fa-brands fa-instagram', 'https://instagram.com/pawpals', 2, NULL, NULL, '2025-08-20 16:58:14', '2025-08-20 16:58:14'),
(7, 'TikTok', 'fa-brands fa-tiktok', 'https://facebook.com/pawpals', 3, NULL, NULL, '2025-08-20 18:13:57', '2025-08-20 18:13:57'),
(8, 'Twitter/X', 'fa-brands fa-x-twitter', 'https://twitter.com/pawpals', 4, NULL, NULL, '2025-08-20 18:20:55', '2025-08-20 19:08:31'),
(9, 'Pinterest', 'fa-brands fa-pinterest', 'https://faceblook.com/pawpals', 5, NULL, NULL, '2025-08-20 19:37:02', '2025-08-20 19:37:02'),
(10, 'Website', 'fa-solid fa-globe', 'https://facebosok.com/pawpals', 6, NULL, NULL, '2025-08-20 19:37:25', '2025-08-20 19:37:25'),
(11, 'LinkedIn', 'fa-brands fa-linkedin', 'https://www.faSceCbook.com', 7, NULL, NULL, '2025-08-21 00:28:26', '2025-08-21 00:28:26'),
(12, 'WhatsApp', 'fa-brands fa-whatsapp', 'https://www.fccacebook.com', 8, NULL, NULL, '2025-08-21 09:35:19', '2025-08-21 09:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `username_last_changed_at` datetime DEFAULT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') NOT NULL DEFAULT 'user',
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) NOT NULL,
  `sex` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `active_session_id` varchar(128) DEFAULT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `last_logout_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `username_last_changed_at`, `email`, `password`, `role`, `first_name`, `last_name`, `sex`, `phone`, `is_active`, `active_session_id`, `is_online`, `last_login_at`, `last_logout_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, 'menongdc@gmail.com', '$2y$10$P6Crt.EiZ.xxlD8Yw/CFMeNQrycfbQydsXeeqqFv6VJ2wyj8TcX9y', 'admin', 'Aly', 'Yla', 'female', '32423423423', 1, NULL, 0, '2025-08-22 17:44:59', '2025-08-22 17:45:13', '::1', '2025-08-20 09:21:28', '2025-08-22 17:45:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `prefix` varchar(10) DEFAULT NULL COMMENT 'e.g., Mr., Ms., Dr.',
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL COMMENT 'e.g., Jr., Sr., III',
  `designation` varchar(100) DEFAULT NULL COMMENT 'e.g., Clinic Administrator',
  `address_line1` varchar(255) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(100) DEFAULT NULL,
  `address_province` varchar(100) DEFAULT NULL,
  `address_zip` varchar(10) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `prefix`, `middle_name`, `suffix`, `designation`, `address_line1`, `address_street`, `address_city`, `address_province`, `address_zip`, `avatar_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dr', 'A', NULL, 'Clinic Admin', '001ss', 'street H', 'Marawi City', 'Lanao del Sur', '9700', 'views/profile-images/3f838d_admin.png', '2025-08-20 05:05:59', '2025-08-21 00:58:20');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_feedbacks_pending_badge`
-- (See below for the actual view)
--
CREATE TABLE `v_feedbacks_pending_badge` (
`pending_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_public_testimonials`
-- (See below for the actual view)
--
CREATE TABLE `v_public_testimonials` (
`id` int(10) unsigned
,`user_id` int(10) unsigned
,`name` varchar(120)
,`email` varchar(120)
,`message` text
,`rating` decimal(3,1)
,`created_at` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `v_feedbacks_pending_badge`
--
DROP TABLE IF EXISTS `v_feedbacks_pending_badge`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_feedbacks_pending_badge`  AS SELECT count(0) AS `pending_count` FROM `feedbacks` WHERE `feedbacks`.`status` = 'pending' ;

-- --------------------------------------------------------

--
-- Structure for view `v_public_testimonials`
--
DROP TABLE IF EXISTS `v_public_testimonials`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_public_testimonials`  AS SELECT `feedbacks`.`id` AS `id`, `feedbacks`.`user_id` AS `user_id`, `feedbacks`.`name` AS `name`, `feedbacks`.`email` AS `email`, `feedbacks`.`message` AS `message`, `feedbacks`.`rating` AS `rating`, `feedbacks`.`created_at` AS `created_at` FROM `feedbacks` WHERE `feedbacks`.`status` = 'approved' AND `feedbacks`.`archived_at` is null ORDER BY `feedbacks`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_created_by` (`created_by`),
  ADD KEY `idx_ann_updated_by` (`updated_by`),
  ADD KEY `idx_ann_pub` (`is_published`,`published_at`),
  ADD KEY `idx_ann_exp` (`expires_at`);

--
-- Indexes for table `email_configs`
--
ALTER TABLE `email_configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_feedback_daily` (`unique_key`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_fk_user_id` (`user_id`),
  ADD KEY `idx_fk_approved_by` (`approved_by`),
  ADD KEY `idx_fk_archived_by` (`archived_by`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `purpose` (`purpose`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token_hash` (`token_hash`);

--
-- Indexes for table `pet_care_tips`
--
ALTER TABLE `pet_care_tips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `is_published` (`is_published`),
  ADD KEY `published_at` (`published_at`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_social_links_created_by` (`created_by`),
  ADD KEY `idx_social_links_updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_configs`
--
ALTER TABLE `email_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pet_care_tips`
--
ALTER TABLE `pet_care_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=274;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_created_by_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ann_updated_by_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_feedbacks_user_approved` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedbacks_user_archived` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedbacks_user_submit` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
