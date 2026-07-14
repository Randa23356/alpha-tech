-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 15, 2025 at 07:23 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alpha_tech`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_features`
--

CREATE TABLE `about_features` (
  `id` int(11) NOT NULL,
  `feature_text` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_features`
--

INSERT INTO `about_features` (`id`, `feature_text`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Interface modern dan mudah digunakan', 1, 1, '2025-10-04 09:40:34', '2025-10-10 15:44:14'),
(2, 'Sistem approval untuk menjaga kualitas konten', 2, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34'),
(3, 'Galeri foto untuk dokumentasi visual', 3, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34'),
(4, 'Responsive design untuk semua perangkat', 4, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `file_path`, `file_name`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Jadwal Workshop Bulan Ini', 'Workshop pemrograman web akan dilaksanakan pada tanggal 15 setiap bulan. Pastikan untuk mendaftar terlebih dahulu.', NULL, NULL, 1, 1, '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(2, 'Pendaftaran Kompetisi Programming', 'Kompetisi programming tingkat nasional akan segera dibuka. Persiapkan diri Anda untuk berkompetisi!', NULL, NULL, 1, 1, '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(3, 'Pengumuman Libur Semester', 'Libur semester akan dimulai minggu depan. Selamat beristirahat dan tetap semangat belajar!', NULL, NULL, 1, 1, '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(4, 'Materi bro', 'Hai inimateri bro', 'announcements/68ee42401ec8d_1760444992.pdf', 'Arman-Wiranda-PTI-Informatika-A.pdf', 1, 8, '2025-10-14 12:29:52', '2025-10-14 12:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `is_read`, `created_at`, `status`) VALUES
(1, 'Arman Wiranda', 'randamoonton@gmail.com', 'Informasi', 'Hai Bro Gue Ingin Menyampaikan Informasi', 0, '2025-10-11 11:09:17', 'read');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `button_text` varchar(100) DEFAULT 'Learn More',
  `button_url` varchar(255) DEFAULT '#',
  `background_image` varchar(255) NOT NULL,
  `slide_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `autoplay_duration` int(11) DEFAULT 5000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `title_color` varchar(7) DEFAULT '#ffffff',
  `subtitle_color` varchar(7) DEFAULT '#f3f4f6',
  `description_color` varchar(7) DEFAULT '#d1d5db',
  `icon_color` varchar(7) DEFAULT '#ffffff',
  `icon_bg_color` varchar(50) DEFAULT 'rgba(255,255,255,0.1)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `button_text`, `button_url`, `background_image`, `slide_order`, `is_active`, `autoplay_duration`, `created_at`, `updated_at`, `title_color`, `subtitle_color`, `description_color`, `icon_color`, `icon_bg_color`) VALUES
(21, 'Welcome to AlphaTech', 'We are from Informatics A', 'This is my class', 'Learn More', 'http://localhost/informatics_a/hero', 'hero_68ef775f365f0.jpeg', 1, 1, 5000, '2025-10-15 10:28:47', '2025-10-15 10:28:47', '#ffffff', '#f3f4f6', '#d1d5db', '#ffffff', 'rgba(255,255,255,0.1)'),
(22, 'Motto AlphaTech', 'Lead the code Build the future', 'This is motto', 'Learn More', 'http://localhost/informatics_a/hero', 'hero_68ef77bb15103.jpeg', 2, 1, 5000, '2025-10-15 10:30:19', '2025-10-15 10:30:19', '#ffffff', '#f3f4f6', '#d1d5db', '#ffffff', 'rgba(255,255,255,0.1)');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('post','comment') DEFAULT 'post',
  `comment_id` int(11) DEFAULT NULL,
  `target_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `post_id`, `user_id`, `created_at`, `type`, `comment_id`, `target_id`) VALUES
(40, NULL, 8, '2025-10-15 17:14:52', 'comment', NULL, 24);

-- --------------------------------------------------------

--
-- Table structure for table `navbar_icons`
--

CREATE TABLE `navbar_icons` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `navbar_icons`
--

INSERT INTO `navbar_icons` (`id`, `filename`, `original_filename`, `file_path`, `file_size`, `mime_type`, `width`, `height`, `alt_text`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'navbar_icon_1760365517.jpeg', 'WhatsApp Image 2025-10-13 at 21.29.32.jpeg', 'public/images/navbar_icon_1760365517.jpeg', 175237, 'image/jpeg', 3264, 3264, 'Navbar Icon', 1, 0, '2025-10-13 14:25:17', '2025-10-13 14:25:17'),
(2, 'navbar_icon_1760438171.jpeg', 'WhatsApp Image 2025-10-13 at 23.05.49.jpeg', 'public/images/navbar_icon_1760438171.jpeg', 129557, 'image/jpeg', 3264, 1836, 'Navbar Icon', 1, 0, '2025-10-14 10:36:11', '2025-10-14 10:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `platform_features`
--

CREATE TABLE `platform_features` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `icon_name` varchar(50) DEFAULT 'default',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_features`
--

INSERT INTO `platform_features` (`id`, `title`, `description`, `icon_name`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Posting Kegiatan', 'Bagikan kegiatan kelas dengan mudah dan cepat. Setiap postingan akan di-review oleh admin sebelum dipublikasikan.', 'document', 1, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34'),
(2, 'Galeri Foto', 'Dokumentasi visual kegiatan dalam satu tempat. Foto otomatis diambil dari kegiatan yang sudah di-approve.', 'photo', 2, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34'),
(3, 'Komentar', 'Diskusi dan berikan feedback pada setiap kegiatan. Interaksi antar anggota kelas lebih mudah.', 'chat', 3, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34'),
(4, 'Pengumuman', 'Informasi penting dari koordinator kelas dan admin. Pastikan tidak ketinggalan update terbaru.', 'announcement', 4, 1, '2025-10-04 09:40:34', '2025-10-04 09:40:34');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`id`, `post_id`, `image_path`, `image_order`, `created_at`) VALUES
(1, 1, 'kegiatan_1_1.jpg', 1, '2025-10-10 15:22:39'),
(2, 1, 'kegiatan_1_2.jpg', 2, '2025-10-10 15:22:39'),
(3, 1, 'kegiatan_1_3.jpg', 3, '2025-10-10 15:22:39'),
(4, 2, 'kegiatan_2_1.jpg', 1, '2025-10-10 15:22:39'),
(5, 2, 'kegiatan_2_2.jpg', 2, '2025-10-10 15:22:39'),
(9, 5, 'kegiatan_5_1.jpg', 1, '2025-10-10 15:22:39'),
(10, 5, 'kegiatan_5_2.jpg', 2, '2025-10-10 15:22:39'),
(11, 6, 'kegiatan_6_1.jpg', 1, '2025-10-10 15:22:39'),
(12, 6, 'kegiatan_6_2.jpg', 2, '2025-10-10 15:22:39'),
(13, 6, 'kegiatan_6_3.jpg', 3, '2025-10-10 15:22:39'),
(21, 10, 'korti_0_68edbeb5b11d90.42877497.jpeg', 0, '2025-10-14 03:08:37'),
(22, 10, 'korti_1_68edbeb5b16167.69388989.jpeg', 1, '2025-10-14 03:08:37'),
(23, 10, 'korti_2_68edbeb5b17617.06206713.jpeg', 2, '2025-10-14 03:08:37'),
(24, 11, 'korti_0_68edbeccd19538.70431120.jpeg', 0, '2025-10-14 03:09:00'),
(25, 11, 'korti_1_68edbeccd1afb3.01987188.jpeg', 1, '2025-10-14 03:09:00'),
(26, 11, 'korti_2_68edbeccd1bb25.78637458.jpeg', 2, '2025-10-14 03:09:00'),
(27, 12, 'korti_0_68edbefdb7f936.63081296.jpg', 0, '2025-10-14 03:09:49'),
(28, 13, 'kegiatan_0_68edc664cb0901.33747221.jpeg', 0, '2025-10-14 03:41:24'),
(29, 14, 'kegiatan_0_68edc680ee8cd7.64181541.jpeg', 0, '2025-10-14 03:41:52'),
(30, 15, 'kegiatan_0_68ee42a94b39a5.37732028.jpeg', 0, '2025-10-14 12:31:37'),
(31, 15, 'kegiatan_1_68ee42a94ba574.38408624.jpeg', 1, '2025-10-14 12:31:37'),
(32, 15, 'kegiatan_2_68ee42a94bb545.27070527.jpeg', 2, '2025-10-14 12:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'AlphaTech', '2025-10-10 15:22:39', '2025-10-13 12:54:11'),
(2, 'hero_title', 'Selamat Datang di AlphaTech', '2025-10-10 15:22:39', '2025-10-13 12:55:15'),
(3, 'hero_subtitle', 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik untuk berbagi kegiatan, pengumuman, dan galeri foto.', '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(4, 'about_title', 'Tentang AlphaTech', '2025-10-10 15:22:39', '2025-10-13 12:55:15'),
(5, 'about_description', 'Platform digital yang dirancang khusus untuk memfasilitasi dokumentasi, berbagi informasi, dan kolaborasi antar anggota kelas AlphaTech Informatics.', '2025-10-10 15:22:39', '2025-10-13 12:55:15'),
(6, 'about_feature_1', 'Interface modern dan mudah digunakan', '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(7, 'about_feature_2', 'Sistem approval untuk menjaga kualitas konten', '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(8, 'about_feature_3', 'Galeri foto untuk dokumentasi visual', '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(9, 'about_feature_4', 'Responsive design untuk semua perangkat', '2025-10-10 15:22:39', '2025-10-10 15:22:39'),
(10, 'primary_color', '#18247b', '2025-10-10 15:22:39', '2025-10-15 01:36:50'),
(11, 'secondary_color', '#3c66c8', '2025-10-10 15:22:39', '2025-10-15 01:36:50'),
(12, 'accent_color', '#000000', '2025-10-10 15:22:39', '2025-10-13 16:37:16'),
(13, 'contact_email', 'alpha.tech.informatics.a@gmail.com', '2025-10-10 15:22:39', '2025-10-13 12:57:05'),
(14, 'contact_instagram', '@alpha.tech.informatics', '2025-10-10 15:22:39', '2025-10-13 12:57:05'),
(16, 'site_tagline', 'Platform kolaborasi dan dokumentasi kelas Informatika terbaik', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(21, 'about_vision', 'Menjadi platform terdepan dalam mendokumentasikan dan berbagi kegiatan kelas.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(22, 'about_mission', 'Memfasilitasi kolaborasi antar mahasiswa melalui teknologi.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(27, 'platform_feature_1_title', 'Posting Kegiatan', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(28, 'platform_feature_1_desc', 'Bagikan kegiatan kelas dengan mudah dan cepat. Setiap postingan akan di-review oleh admin sebelum dipublikasikan.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(29, 'platform_feature_2_title', 'Galeri Foto', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(30, 'platform_feature_2_desc', 'Dokumentasi visual kegiatan dalam satu tempat. Foto otomatis diambil dari kegiatan yang sudah di-approve.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(31, 'platform_feature_3_title', 'Komentar', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(32, 'platform_feature_3_desc', 'Diskusi dan berikan feedback pada setiap kegiatan. Interaksi antar anggota kelas lebih mudah.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(33, 'platform_feature_4_title', 'Pengumuman', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(34, 'platform_feature_4_desc', 'Informasi penting dari koordinator kelas dan admin. Pastikan tidak ketinggalan update terbaru.', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(38, 'success_color', '#10b981', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(39, 'warning_color', '#f59e0b', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(40, 'danger_color', '#ef4444', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(41, 'navbar_bg_color', '#ffffff', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(42, 'navbar_font_color', '#000000', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(43, 'footer_text', 'All rights reserved. Built by AlphaTech Informatics.', '2025-10-10 15:32:38', '2025-10-13 13:24:00'),
(46, 'contact_phone', '+62 812-3456-7890', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(47, 'contact_address', 'Jl. Pendidikan No. 123, Jakarta, Indonesia', '2025-10-10 15:32:38', '2025-10-10 15:32:38'),
(48, 'google_maps_embed', '<iframe src=\"https://www.google.com/maps/place/Institut+Agama+Islam+Hamzanwadi+Pancor+Lombok+Timur+(IAIH+PANCOR)/@-8.6481226,116.5297599,16.18z/data=!4m6!3m5!1s0x2dcc4eba8290d87b:0x8dc25094bcbd6d40!8m2!3d-8.6460774!4d116.5279037!16s%2Fg%2F1pp2w_nbb?entry=ttu&g_ep=EgoyMDI1MTAxMi4wIKXMDSoASAFQAw%3D%3D\"> </iframe>', '2025-10-10 15:32:38', '2025-10-15 01:45:09'),
(49, 'hero_autoplay', 'false', '2025-10-11 11:21:54', '2025-10-12 06:33:38'),
(50, 'hero_transition', 'slide', '2025-10-11 11:21:54', '2025-10-12 06:35:13'),
(51, 'hero_show_arrows', 'true', '2025-10-11 11:21:54', '2025-10-13 17:24:55'),
(52, 'hero_show_dots', 'true', '2025-10-11 11:21:54', '2025-10-11 11:21:54'),
(707, 'navbar_icon', 'public/images/navbar_icon_1760365517.jpeg', '2025-10-13 13:24:00', '2025-10-13 14:25:40'),
(918, 'navbar_icon_id', '1', '2025-10-13 13:40:41', '2025-10-13 14:25:40'),
(1189, 'hero_show_arrows_mobile', 'false', '2025-10-13 17:24:19', '2025-10-13 17:30:21'),
(1190, 'hero_show_arrows_tablet', 'false', '2025-10-13 17:24:19', '2025-10-13 17:28:03'),
(1191, 'hero_show_arrows_desktop', 'true', '2025-10-13 17:24:19', '2025-10-13 17:27:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','korti','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(255) DEFAULT NULL,
  `bio` varchar(255) DEFAULT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `full_name`, `bio`, `contact`, `profile_pic`, `google_id`, `email_verified`) VALUES
(7, 'admin', 'admin@informaticsa.edu', '$2y$10$WqYrIK/HrhVPMBh0zHeRGOSklBpkVQ5NO2RiRCxVaCX8I84nlLZdO', 'admin', '2025-10-04 09:45:00', 'Arman Wiranda', '', '', '../../public/uploads/profil_68e9d5f4d3de56.99042141.jpg', NULL, 0),
(8, 'korti', 'korti@informaticsa.edu', '$2y$10$qcMy72Ue/6qZssj9pP8TzetoCQVjT4Y3YB9Yf58942iOvVLbO7R5W', 'korti', '2025-10-04 09:45:00', 'Selfiardiani', '', '', '../../public/uploads/profil_68e9c11443bce5.22215745.jpeg', NULL, 0),
(13, '250602009', 'randamoonton@gmail.com', '$2y$10$YmkeqlQByKs57EGGC7XseOCxe9T5f6C2tNyotSoUbx0gteypOT0dm', 'user', '2025-10-15 16:10:45', NULL, NULL, NULL, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_features`
--
ALTER TABLE `about_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slide_order` (`slide_order`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`user_id`,`post_id`,`comment_id`,`type`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_comment_id` (`comment_id`);

--
-- Indexes for table `navbar_icons`
--
ALTER TABLE `navbar_icons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `platform_features`
--
ALTER TABLE `platform_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_google_id` (`google_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_features`
--
ALTER TABLE `about_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `navbar_icons`
--
ALTER TABLE `navbar_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `platform_features`
--
ALTER TABLE `platform_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `post_images`
--
ALTER TABLE `post_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1528;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_comment_fk` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
