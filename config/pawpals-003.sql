-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2025 at 06:45 AM
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
(4, 'new test announcement', 'fasfas', 'all', 'landing', 1, NULL, NULL, 'uploads/announcements/YERMUJ.png', NULL, 1, 1, '2025-08-21 09:02:26', '2025-08-22 17:10:57');

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
(2, 'password_reset', 'Password Reset', 'Reset your PawPals password', '<h2 style=\"margin:0 0 6px\">{{title}}</h2>\r\n  <p>Click the button below to reset your password.</p>\r\n  <p><a href=\"{{reset_url}}\" style=\"background:#10b981;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block\">Reset Password</a></p>\r\n  <p style=\"color:#6b7280\">The link expires in {{ttl}} minutes.</p>\r\n  <p style=\"color:#6b7280\">— {{brand_name}}</p>', 'Reset link (valid {{ttl}} minutes): {{reset_url}}', 1, NULL, '2025-08-22 08:01:52', '2025-08-22 08:01:52'),
(3, 'email_verify', 'Email Verification', 'Verify your PawPals email', '<h2 style=\"margin:0 0 6px\">{{title}}</h2><p>Confirm your email to finish creating your PawPals account.</p><p><a href=\"{{verify_url}}\" style=\"background:#10b981;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;display:inline-block\">Verify my email</a></p><p style=\"color:#6b7280\">This link expires in {{ttl}} minutes. If you didn\'t create an account, you can ignore this email.</p><p style=\"color:#6b7280\">— {{brand_name}}</p>', 'Verify link (valid {{ttl}} minutes): {{verify_url}}', 1, NULL, '2025-08-26 03:16:25', '2025-08-26 03:16:25'),
(4, 'staff_welcome', 'Staff Welcome', 'Your PawPals staff account', '<h2 style=\"margin:0 0 10px\">Welcome to {{brand_name}}</h2><p>Hi {{first_name}}, your staff account has been created.</p><p><b>Username:</b> {{username}}<br><b>Temporary password:</b> {{temp_password}}</p><p>You can sign in here:</p><p><a href=\"{{login_url}}\" style=\"background:#10b981;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;display:inline-block\">Open Dashboard</a></p><p style=\"color:#6b7280\">For security, you will be asked to change your password after logging in.</p><p style=\"color:#6b7280\">— {{brand_name}}</p>', 'Your staff account is ready. Username: {{username}} Temp password: {{temp_password}} Login: {{login_url}}', 1, NULL, '2025-08-27 08:33:14', '2025-08-27 08:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `purpose` enum('verify') NOT NULL DEFAULT 'verify',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `email`, `token_hash`, `purpose`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 2, 'minionm219@gmail.com', '0fc35e05a3231923465b5ba3458a5b7fb41b1b15fffeff813cb2cb8078e2dd49', 'verify', '2025-08-26 12:25:06', '2025-08-26 11:27:01', '2025-08-26 11:25:06'),
(2, 3, 'minion.new002@gmail.com', '1882798563292d2b301fd691911df4163b72124fcb49614dde2faa5017098bc1', 'verify', '2025-08-26 14:07:02', NULL, '2025-08-26 13:07:02'),
(3, 3, 'minion.new002@gmail.com', '55bec56dba06d14aea0e7aac9fae7106c55884ffa7bca03800db1d6ec6b7edec', 'verify', '2025-08-26 14:29:42', '2025-08-26 13:30:34', '2025-08-26 13:29:42');

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
(1, NULL, 'aly', 'admin@example.com', 'testing feedback', 2.0, 'approved', '2025-08-22 08:10:43', '2025-08-23 01:18:47', '2025-08-23 01:18:47', 1, '2025-08-22 13:10:06', 1),
(2, 1, 'Aly Yla', 'admin@example.com', 'testing 2', 5.0, 'approved', '2025-08-22 10:03:01', '2025-08-23 01:18:44', '2025-08-23 01:18:44', 1, '2025-08-22 13:53:54', 1),
(3, NULL, 'devdev', 'dev@gmail.com', 'test 3', 2.8, 'approved', '2025-08-22 11:25:59', '2025-08-22 13:10:12', '2025-08-22 13:10:12', 1, NULL, NULL),
(4, NULL, 'Dr Aly Yla', 'aly@gmail.com', 'testing feedback\n\nmay erro ako sa pet care tips ayusin ko nalgn po hehe may hindi na call na syntax s codes', 1.4, 'archived', '2025-08-23 01:18:06', '2025-08-26 08:51:43', '2025-08-23 01:18:20', 1, '2025-08-26 08:51:43', 1),
(5, 1, 'testing name', 'aly@gmail.com', 'testing', 2.5, 'approved', '2025-08-26 08:52:19', '2025-08-26 08:52:52', '2025-08-26 08:52:52', 1, '2025-08-26 08:52:51', 1);

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
(23, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-22 17:44:51'),
(24, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-23 00:57:57'),
(25, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-23 01:09:37'),
(26, 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_password', '2025-08-23 01:22:28'),
(27, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 01:32:04'),
(28, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 05:09:07'),
(29, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 05:11:09'),
(30, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-26 05:13:55'),
(31, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 05:31:57'),
(32, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 05:39:19'),
(33, 2, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_password', '2025-08-26 12:12:24'),
(34, 2, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_password', '2025-08-26 12:14:01'),
(35, 3, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_session_conflict', '2025-08-26 13:31:12'),
(36, 3, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_session_conflict', '2025-08-26 13:33:50'),
(37, 3, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'failed_session_conflict', '2025-08-26 13:45:17'),
(38, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-26 14:08:43'),
(39, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-27 18:34:35'),
(40, 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_session_conflict', '2025-08-27 18:36:06'),
(41, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'failed_password', '2025-08-28 10:00:54');

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
(6, 1, 'menongdc@gmail.com', '3cdfac5c2782e8b48cc4e5a77718f10938e32224184b1bb13bdd83a2f838', 'reset', '2025-08-22 17:54:09', NULL, '2025-08-22 17:44:09'),
(7, 1, 'gamecode83@gmail.com', '9cc2c59be25fb2d9697f74b9063f2b414b2d322d7fd799f3155dfaa6ee67', 'reset', '2025-08-23 01:31:27', '2025-08-26 06:10:58', '2025-08-23 01:21:27'),
(8, 1, 'gamecode83@gmail.com', '$2y$10$zIXPM2sXisPcIevdPmo5KeyX20mE47vwoER4l13l6r.F8bs.g2FYy', 'reset', '2025-08-26 04:52:35', '2025-08-26 06:10:58', '2025-08-26 04:42:35'),
(9, 1, 'gamecode83@gmail.com', '$2y$10$J60OI2kDeC.tb3Jh3K/TPe/NRZwfusmAvFz1m5dWKckhk9HblNnWq', 'reset', '2025-08-26 05:12:32', '2025-08-26 06:10:58', '2025-08-26 05:02:32'),
(10, 1, 'gamecode83@gmail.com', '$2y$10$WAeUClhbTzXxK2eiU3lHEuupvicgb6lVw.tW3ZSIRwfT4j16BVTlG', 'reset', '2025-08-26 05:15:41', '2025-08-26 05:08:45', '2025-08-26 05:05:41'),
(11, 1, 'gamecode83@gmail.com', '$2y$10$WnmdPdMHFQntA90O7Masx.0ptUkGm7IhUlleTZkIe.26hHceKIENi', 'reset', '2025-08-26 05:42:12', '2025-08-26 05:32:56', '2025-08-26 05:32:12'),
(12, 1, 'gamecode83@gmail.com', '$2y$10$RRf3zCROzVw1HgC4D3jrEu3jGVvxQCh.Qyh22BP/J8adUqOwTuKFy', 'reset', '2025-08-26 05:49:24', '2025-08-26 05:39:47', '2025-08-26 05:39:24'),
(13, 1, 'gamecode83@gmail.com', '$2y$10$Kv6ux7IQ1UBOl/A16D3Uled3oD6bo6pyYSnXhbrMkt0cxbJixiwLa', 'reset', '2025-08-26 05:53:15', '2025-08-26 06:10:58', '2025-08-26 05:50:15'),
(14, 1, 'gamecode83@gmail.com', '$2y$10$gzrXo7IsOcWopzWo/efWyOvmDhPaUnZ5N/9.UqUazqeisXhoBphHy', 'reset', '2025-08-26 06:03:41', '2025-08-26 06:10:58', '2025-08-26 05:53:41'),
(15, 1, 'gamecode83@gmail.com', '$2y$10$mpdmPwE1YUaTD5nLYY4qle4bnZzxVwJJNBVBrbNgLA5VSpDZra/0q', 'reset', '2025-08-26 06:04:45', '2025-08-26 06:10:58', '2025-08-26 05:54:45'),
(16, 1, 'gamecode83@gmail.com', '$2y$10$oUNif34cY/nIALhxLujnFuI1r8nBntDhU6M3NPJ8Ur8s0azXJT6Ii', 'reset', '2025-08-26 06:05:14', '2025-08-26 06:10:58', '2025-08-26 05:55:14'),
(17, 1, 'gamecode83@gmail.com', '$2y$10$q84Ma5.umDTEA0lzx2Nauu7P1UQ0qBZE6nLaqu0WFHD3uXCtnNF7G', 'reset', '2025-08-26 06:07:14', '2025-08-26 05:58:18', '2025-08-26 05:57:14'),
(18, 1, 'gamecode83@gmail.com', '$2y$10$JfKM/fMWKZhc3JCyfhBLH./jVOKoqSkKCrhlZqEBFo1pr.1nSiiIa', 'reset', '2025-08-26 06:20:28', '2025-08-26 06:10:58', '2025-08-26 06:10:28'),
(19, 1, 'gamecode83@gmail.com', '$2y$10$0toTA0qcArEakqJ1Sr1IBOLI2y2WOaFqpELeJsXBMZTGwqIXt8HEe', 'reset', '2025-08-26 06:20:58', '2025-08-26 06:12:01', '2025-08-26 06:10:58'),
(20, 2, 'minionm219@gmail.com', '$2y$10$kSazbcQ4pIFtl2UkOPgxU.5CndCz77cu3R5G29QOzWqv1yFg8NnqO', 'reset', '2025-08-27 14:26:05', '2025-08-27 14:16:44', '2025-08-27 14:16:05');

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
(2, 1, 'menongdc@gmail.com', 'cf1f5983a4fdf0177b5eeb5969841a8ccf565f1f35670d3e93d96ecf3e51d847', '2025-08-22 18:15:17', NULL, '2025-08-22 17:45:17'),
(3, 1, 'gamecode83@gmail.com', 'f8e3fdc62bdccebad7ec039a3cc1ed48b60c967a21406b20cdcd1ede55bbffbb', '2025-08-26 05:39:41', '2025-08-26 05:10:54', '2025-08-26 05:09:41'),
(4, 1, 'gamecode83@gmail.com', '016018ef8d15300bb4a59168deb52bf04ea8845af3082de8ff8a8c15baf8fec6', '2025-08-26 05:45:59', NULL, '2025-08-26 05:15:59'),
(5, 1, 'gamecode83@gmail.com', '3227267917e466bfc90f54b496173b4dce34ff561f8c176c848fd7efd7e68145', '2025-08-26 06:00:50', '2025-08-26 05:31:51', '2025-08-26 05:30:50'),
(6, 1, 'gamecode83@gmail.com', '1186d7219c485fbefba96f3721bed5b32e4fadba90a342a6486e4bf9521c22ef', '2025-08-26 06:08:03', '2025-08-26 05:38:35', '2025-08-26 05:38:03'),
(7, 1, 'gamecode83@gmail.com', 'd15a95c14a32308367d81730b14794ac431273b1259264f4c39a71e3538ff6f5', '2025-08-26 06:16:49', '2025-08-26 05:47:26', '2025-08-26 05:46:49'),
(8, 1, 'gamecode83@gmail.com', '4ae55e530eac12cca6c5a927996ce6c0d6448862892bc5fe2db756918999e374', '2025-08-26 06:19:20', '2025-08-26 05:49:55', '2025-08-26 05:49:20'),
(9, 1, 'gamecode83@gmail.com', 'ed62727b09074fe143fd923af1fb7f9ed3c2f4f1610bf0efce076f0b270995a8', '2025-08-26 06:39:39', NULL, '2025-08-26 06:09:39'),
(10, 1, 'gamecode83@gmail.com', '7f26054be4895bced11ba0ca0829147d93dabd4c18ae9d13b67a9a19d030f503', '2025-08-26 06:46:54', '2025-08-26 06:17:33', '2025-08-26 06:16:54');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `species` enum('dog','cat','bird','rabbit','hamster','fish','reptile','other') NOT NULL,
  `breed` varchar(120) DEFAULT NULL,
  `sex` enum('male','female','unknown') NOT NULL DEFAULT 'unknown',
  `color` varchar(80) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `microchip_no` varchar(64) DEFAULT NULL,
  `rabies_tag_no` varchar(64) DEFAULT NULL,
  `sterilized` tinyint(1) NOT NULL DEFAULT 0,
  `blood_type` varchar(20) DEFAULT NULL,
  `species_other` varchar(120) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `user_id`, `name`, `species`, `breed`, `sex`, `color`, `birthdate`, `weight_kg`, `microchip_no`, `rabies_tag_no`, `sterilized`, `blood_type`, `species_other`, `photo_path`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 2, 'luna', 'dog', 'German Shepard', 'male', 'Brown', '2025-08-26', 12.00, NULL, NULL, 0, NULL, 'sd', 'uploads/pets/pet-images/ashley_dane_aed1b8.png', 'furryD', '2025-08-26 17:37:18', '2025-08-27 08:17:56', NULL),
(2, 2, 'princess', 'cat', 'catsss', 'female', 'white', '2025-08-26', NULL, NULL, NULL, 1, NULL, NULL, 'uploads/pets/pet-images/ashley_dane_55218e.png', 'ASDASDvvvv', '2025-08-26 21:49:48', '2025-08-27 10:24:04', NULL),
(3, 2, 'PETO', 'rabbit', NULL, 'unknown', NULL, '2025-08-26', NULL, NULL, NULL, 0, NULL, NULL, 'uploads/pets/pet-images/ashley_dane_fba138.jpg', 'ASDASD', '2025-08-27 08:18:38', '2025-08-27 10:02:01', '2025-08-27 10:02:01'),
(4, 3, 'ALEX', 'bird', 'PARROT', 'male', 'GREEN', '2025-08-27', 0.50, NULL, NULL, 0, NULL, NULL, 'uploads/pets/pet-images/drane_delacruz_750e9f.png', 'TALKING', '2025-08-27 08:44:32', '2025-08-27 08:44:32', NULL),
(5, 2, 'PETO', 'dog', 'German Shepard', 'unknown', 'white', '2025-08-27', NULL, NULL, NULL, 0, NULL, NULL, 'uploads/pets/pet-images/ashley_dane_71bfde.png', 'ascascasc', '2025-08-27 12:46:17', '2025-08-27 12:46:17', NULL),
(6, 2, 'princess', 'dog', NULL, 'unknown', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-08-27 13:30:29', '2025-08-27 13:30:29', NULL),
(7, 2, 'asdas', 'dog', NULL, 'unknown', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-08-27 13:44:00', '2025-08-27 13:44:20', '2025-08-27 13:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `pet_allergies`
--

CREATE TABLE `pet_allergies` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `allergen` varchar(160) NOT NULL,
  `reaction` varchar(160) DEFAULT NULL,
  `severity` enum('mild','moderate','severe') DEFAULT 'mild',
  `noted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(3, 'Testing Pdf', 'health', 'pdf summury', '', 'file', '', 'uploads/petcare/images/omSfE8.png', 'uploads/petcare/files/LtAppQ.pdf', 1, '2025-08-21 12:00:00', NULL, 1, 1, '2025-08-21 12:47:22', '2025-08-22 05:55:12'),
(4, 'Health issue for all animals', 'diet', 'testing summary ', 'test article for this pet care tips', 'text', '', 'uploads/petcare/images/hxgQxX.png', NULL, 1, '2025-08-23 12:00:00', '2025-08-26 12:00:00', 1, 1, '2025-08-22 17:13:18', '2025-08-26 00:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `pet_deworming`
--

CREATE TABLE `pet_deworming` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `dose` varchar(40) DEFAULT NULL,
  `targets` varchar(120) DEFAULT NULL,
  `date_administered` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `vet_name` varchar(120) DEFAULT NULL,
  `clinic_name` varchar(120) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_documents`
--

CREATE TABLE `pet_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `doc_type` enum('vaccination_record','lab_result','prescription','xray','insurance','others') DEFAULT 'others',
  `title` varchar(160) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size_bytes` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_medications`
--

CREATE TABLE `pet_medications` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `drug_name` varchar(160) NOT NULL,
  `dosage` varchar(80) DEFAULT NULL,
  `frequency` varchar(80) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `prescribing_vet` varchar(160) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_parasite_preventions`
--

CREATE TABLE `pet_parasite_preventions` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `type` enum('tick_flea','heartworm','broad_spectrum','other') DEFAULT 'other',
  `product_name` varchar(120) NOT NULL,
  `route` enum('oral','topical','injection','other') DEFAULT 'other',
  `date_administered` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `vet_name` varchar(120) DEFAULT NULL,
  `clinic_name` varchar(120) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pet_vaccinations`
--

CREATE TABLE `pet_vaccinations` (
  `id` int(10) UNSIGNED NOT NULL,
  `pet_id` int(10) UNSIGNED NOT NULL,
  `vaccine_name` varchar(120) NOT NULL,
  `dose_no` varchar(20) DEFAULT NULL,
  `date_administered` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `vet_name` varchar(120) DEFAULT NULL,
  `clinic_name` varchar(120) DEFAULT NULL,
  `batch_no` varchar(64) DEFAULT NULL,
  `lot_no` varchar(64) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'clinic_name', 'PawPals test', 1, '2025-08-22 17:06:13'),
(2, 'clinic_tagline', 'Dedicated to providing top-tier veterinary services and compassionate care for your beloved pets in Marawi City.', NULL, '2025-08-20 11:32:24'),
(4, 'contact_phone', '6436 346 3466', 1, '2025-08-22 17:07:11'),
(5, 'contact_email', 'support@pawpals.com', 1, '2025-08-22 17:07:11'),
(6, 'hero_title', 'We take care of your pets with experts 🐱', 1, '2025-08-22 17:06:13'),
(7, 'hero_subtitle', 'The best place for your best friend. Providing top-tier veterinary services and compassionate care in Marawi City. ', 1, '2025-08-21 00:38:17'),
(8, 'footer_tagline', 'Dedicated to providing top-tier veterinary services and compassionate care for your beloved pets in Marawi City. test', 1, '2025-08-22 17:07:11'),
(9, 'contact_houseno', '002', 1, '2025-08-22 17:07:11'),
(10, 'contact_street', 'streets', 1, '2025-08-21 00:13:53'),
(11, 'contact_barangay', 'barangay', 1, '2025-08-21 00:13:46'),
(12, 'contact_municipality', 'Marawi City', 1, '2025-08-20 11:46:03'),
(13, 'contact_province', 'Lanao del Sur', 1, '2025-08-20 14:16:40'),
(14, 'contact_zipcode', '9700', 1, '2025-08-20 11:46:03'),
(48, 'hero_image_path', 'uploads/settings/Screenshot2025-07-17014601_1d8006f7113b.png', 1, '2025-08-22 17:06:13');

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
(7, 'TikTok', 'fa-brands fa-tiktok', 'https://facebook.com/pawpals', 3, NULL, NULL, '2025-08-20 18:13:57', '2025-08-20 18:13:57'),
(8, 'Twitter/X', 'fa-brands fa-x-twitter', 'https://twitter.com/pawpals', 4, NULL, NULL, '2025-08-20 18:20:55', '2025-08-20 19:08:31'),
(10, 'Website', 'fa-solid fa-globe', 'https://facebosok.com/pawpals', 6, NULL, NULL, '2025-08-20 19:37:25', '2025-08-20 19:37:25'),
(11, 'LinkedIn', 'fa-brands fa-linkedin', 'https://www.faSceCbook.com', 7, NULL, NULL, '2025-08-21 00:28:26', '2025-08-21 00:28:26'),
(12, 'WhatsApp', 'fa-brands fa-whatsapp', 'https://www.fccacebook.com', 8, NULL, NULL, '2025-08-21 09:35:19', '2025-08-21 09:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `staff_documents`
--

CREATE TABLE `staff_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `kind` enum('resume','id','license','photo','other') NOT NULL DEFAULT 'other',
  `orig_name` varchar(191) DEFAULT NULL,
  `doc_type` enum('resume','id','license','other') NOT NULL DEFAULT 'resume',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(191) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_documents`
--

INSERT INTO `staff_documents` (`id`, `user_id`, `kind`, `orig_name`, `doc_type`, `file_path`, `original_name`, `mime_type`, `size_bytes`, `uploaded_at`) VALUES
(3, 5, 'id', 'ito yung orginal dsign ko nas paat mong sundin.png', 'resume', 'uploads/staffs/5/JuanDelaCruz_1aeffa.png', NULL, 'image/png', 185737, '2025-08-28 08:15:07'),
(4, 5, 'other', 'BSN Grading Sheet 2425 - Google Sheets.pdf', 'resume', 'uploads/staffs/5/JuanDelaCruz_3d8929.pdf', NULL, 'application/pdf', 213922, '2025-08-28 08:16:11'),
(5, 5, 'resume', 'AgentBiz_Meta_App_Review_Submitted_On_2025-08-16.pdf', 'resume', 'uploads/staffs/5/JuanDelaCruz_141d74.pdf', NULL, 'application/pdf', 1050343, '2025-08-28 08:17:06'),
(6, 6, 'resume', 'AgentBiz_Meta_App_Review_Submitted_On_2025-08-16.pdf', 'resume', 'uploads/staffs/6/minacruz_d215ed.pdf', NULL, 'application/pdf', 1050343, '2025-08-28 08:19:37'),
(7, 6, 'license', 'Gemini_Generated_Image_r9gdevr9gdevr9gd.png', 'resume', 'uploads/staffs/6/minacruz_b485d5.png', NULL, 'image/png', 375053, '2025-08-28 08:19:37'),
(8, 5, 'resume', 'Student_Grades_A4.pdf', 'resume', 'uploads/staffs/5/JuanDelaCruz_92dca2.pdf', NULL, 'application/pdf', 528205, '2025-08-28 08:28:08');

-- --------------------------------------------------------

--
-- Table structure for table `staff_permissions`
--

CREATE TABLE `staff_permissions` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `allowed_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`allowed_json`)),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_permissions`
--

INSERT INTO `staff_permissions` (`user_id`, `allowed_json`, `updated_at`) VALUES
(5, '[\"appointments\",\"medical\"]', '2025-08-28 09:52:56'),
(6, '[\"appointments\",\"pets\"]', '2025-08-28 08:29:05');

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
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
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

INSERT INTO `users` (`id`, `username`, `username_last_changed_at`, `email`, `password`, `role`, `first_name`, `last_name`, `sex`, `phone`, `is_active`, `must_change_password`, `active_session_id`, `is_online`, `last_login_at`, `last_logout_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, 'gamecode83@gmail.com', '$2y$10$5G3QSp89vORvbQORPA1.eOwg25QBu1jB3VcaADLT/oMGPfOyOdtDG', 'admin', 'Alya', 'Yla', 'female', '64363473737', 1, 0, 'pl0lat7clrlic0t043pd6rhs78', 1, '2025-08-28 15:13:34', '2025-08-28 10:05:40', '::1', '2025-08-20 09:21:28', '2025-08-28 15:13:34'),
(2, 'ashley', NULL, 'minionm219@gmail.com', '$2y$10$ANIJP0rJQ7jId9OYwm.JM.32j8w9y.6bziQH/7aMxoYCdky7oNqa6', 'user', 'Ashley', 'Dane', 'female', '09876543221', 1, 0, 'gvg14926vm5fuon7082sgeje3c', 1, '2025-08-27 18:34:46', '2025-08-27 15:31:38', '::1', '2025-08-26 11:25:06', '2025-08-27 18:34:46'),
(3, 'drane123', NULL, 'minion.new002@gmail.com', '$2y$10$vlTfxn79h.S5dVOb5TJJqecC2f4unrtU4Ab0U8lKgrbNSkqI3rqUS', 'user', 'drane', 'dela cruz', 'male', '09123212414', 1, 0, NULL, 0, '2025-08-26 14:08:55', '2025-08-27 19:34:42', '::1', '2025-08-26 13:07:02', '2025-08-27 19:34:42'),
(5, 'juan123', NULL, 'ojtservices24@gmail.com', '$2y$10$hMyasqyEW6ETGbnRyStVVeQ3hvdyqr9KzL2uOv6uUbW746PCol2TO', 'staff', 'Juan', 'Dela Cruz', NULL, '09876543211', 1, 1, NULL, 0, '2025-08-28 10:01:14', '2025-08-28 10:05:04', '::1', '2025-08-27 18:19:06', '2025-08-28 15:13:47'),
(6, 'mina123', NULL, 'staff@gmail.com', '$2y$10$q6iZL9O3xx9GcerLCkUnruUiVG2Fj/M4xBw0O9XCsEkqGEK4KBwF2', 'staff', 'mina', 'cruz', NULL, '52356253523', 1, 1, NULL, 0, NULL, NULL, NULL, '2025-08-28 08:19:37', '2025-08-28 08:29:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_email_status`
--

CREATE TABLE `user_email_status` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_email_status`
--

INSERT INTO `user_email_status` (`user_id`, `email`, `verified_at`, `created_at`, `updated_at`) VALUES
(2, 'minionm219@gmail.com', '2025-08-26 11:27:01', '2025-08-26 11:25:06', '2025-08-26 11:27:01'),
(3, 'minion.new002@gmail.com', '2025-08-26 13:30:34', '2025-08-26 13:07:02', '2025-08-26 13:30:34');

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
(1, 1, 'Dr', 'A', NULL, 'Clinic Admin', '001ss', 'street H', 'Marawi City', 'Lanao del Sur', '9700', 'views/profile-images/db5f8d_admin.png', '2025-08-20 05:05:59', '2025-08-26 04:30:51'),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'views/profile-images/cd2eb0_ashley.png', '2025-08-26 03:25:06', '2025-08-26 04:30:05'),
(3, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 05:07:02', '2025-08-26 05:07:02'),
(5, 5, NULL, NULL, NULL, 'Veterinarian', NULL, NULL, NULL, NULL, NULL, 'views/profile-images/d6f5a4_juan123.png', '2025-08-27 10:19:06', '2025-08-28 01:57:05'),
(18, 6, NULL, NULL, NULL, 'Clinic Assistant', NULL, NULL, NULL, NULL, NULL, 'views/profile-images/minacruz_abd5f2.png', '2025-08-28 00:19:37', '2025-08-28 00:29:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_security_flags`
--

CREATE TABLE `user_security_flags` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ev_email` (`email`),
  ADD KEY `idx_ev_user` (`user_id`),
  ADD KEY `idx_ev_token` (`token_hash`);

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
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_microchip` (`microchip_no`),
  ADD KEY `idx_pets_user` (`user_id`),
  ADD KEY `idx_pets_deleted` (`deleted_at`);

--
-- Indexes for table `pet_allergies`
--
ALTER TABLE `pet_allergies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_allergy_pet` (`pet_id`);

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
-- Indexes for table `pet_deworming`
--
ALTER TABLE `pet_deworming`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deworm_pet` (`pet_id`),
  ADD KEY `idx_deworm_due` (`next_due_date`);

--
-- Indexes for table `pet_documents`
--
ALTER TABLE `pet_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_docs_pet` (`pet_id`),
  ADD KEY `idx_docs_user` (`user_id`);

--
-- Indexes for table `pet_medications`
--
ALTER TABLE `pet_medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meds_pet` (`pet_id`);

--
-- Indexes for table `pet_parasite_preventions`
--
ALTER TABLE `pet_parasite_preventions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prev_pet` (`pet_id`),
  ADD KEY `idx_prev_due` (`next_due_date`);

--
-- Indexes for table `pet_vaccinations`
--
ALTER TABLE `pet_vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vax_pet` (`pet_id`),
  ADD KEY `idx_vax_due` (`next_due_date`);

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
-- Indexes for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sdoc_user` (`user_id`);

--
-- Indexes for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_email_status`
--
ALTER TABLE `user_email_status`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_security_flags`
--
ALTER TABLE `user_security_flags`
  ADD PRIMARY KEY (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pet_allergies`
--
ALTER TABLE `pet_allergies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_care_tips`
--
ALTER TABLE `pet_care_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pet_deworming`
--
ALTER TABLE `pet_deworming`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_documents`
--
ALTER TABLE `pet_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_medications`
--
ALTER TABLE `pet_medications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_parasite_preventions`
--
ALTER TABLE `pet_parasite_preventions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pet_vaccinations`
--
ALTER TABLE `pet_vaccinations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `staff_documents`
--
ALTER TABLE `staff_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_feedbacks_user_approved` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedbacks_user_archived` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedbacks_user_submit` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `fk_pets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_allergies`
--
ALTER TABLE `pet_allergies`
  ADD CONSTRAINT `fk_allergy_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_deworming`
--
ALTER TABLE `pet_deworming`
  ADD CONSTRAINT `fk_deworm_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_documents`
--
ALTER TABLE `pet_documents`
  ADD CONSTRAINT `fk_docs_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_docs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_medications`
--
ALTER TABLE `pet_medications`
  ADD CONSTRAINT `fk_meds_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_parasite_preventions`
--
ALTER TABLE `pet_parasite_preventions`
  ADD CONSTRAINT `fk_prev_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pet_vaccinations`
--
ALTER TABLE `pet_vaccinations`
  ADD CONSTRAINT `fk_vax_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff_documents`
--
ALTER TABLE `staff_documents`
  ADD CONSTRAINT `fk_sdoc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD CONSTRAINT `fk_sp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_email_status`
--
ALTER TABLE `user_email_status`
  ADD CONSTRAINT `fk_ues_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_security_flags`
--
ALTER TABLE `user_security_flags`
  ADD CONSTRAINT `fk_usf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
