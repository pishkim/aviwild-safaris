-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2026 at 08:21 PM
-- Server version: 10.4.17-MariaDB
-- PHP Version: 8.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_title` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user`, `action`, `target_type`, `target_id`, `target_title`, `details`, `ip_address`, `created_at`) VALUES
(1, 'Admin', 'Updated', 'System', NULL, 'Company Profile', 'Updated company info: AviWild Safaris (ID: 1)', '::1', '2026-09-13 17:28:36'),
(2, 'Admin', 'Created', 'Blog Post', 24, 'Bird1', 'New post by kim (published)', '::1', '2026-09-13 18:05:23'),
(3, 'Admin', 'Updated', 'Blog Post', 24, 'Bird1', 'Updated post by kim', '::1', '2026-09-13 18:05:52'),
(4, 'Admin', 'Updated', 'Blog Post', 24, 'Bird1', 'Updated post by kim', '::1', '2026-09-13 18:06:10'),
(5, 'Admin', 'Created', 'Blog Post', 25, 'test2', 'New post by kimani (published)', '::1', '2026-09-13 18:06:38');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(150) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `description`, `performed_by`, `created_at`) VALUES
(1, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:30:43'),
(2, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:39:33'),
(3, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:40:17'),
(4, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:40:26'),
(5, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:42:14'),
(6, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:42:24'),
(7, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:43:30'),
(8, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:43:37'),
(9, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:48:10'),
(10, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:48:24'),
(11, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:50:55'),
(12, 'UPDATE', 'Updated company information (ID: 1)', 'Admin', '2026-09-13 14:55:25');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `author` varchar(100) NOT NULL,
  `publication_date` date NOT NULL,
  `introduction` text NOT NULL,
  `call_to_action` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `image`, `author`, `publication_date`, `introduction`, `call_to_action`, `status`, `created_at`) VALUES
(3, '7-Day Healthy Meal Plan for Beginners', 'healthy-meal-plan.jpg', 'Dr. Emily Chen', '2024-03-05', 'Starting a healthy eating journey can be overwhelming. This simple 7-day meal plan is designed for beginners, featuring easy-to-prepare recipes that are both nutritious and delicious. Learn how to balance your meals without sacrificing flavor.', 'Get Your Free Meal Plan', 'published', '2026-09-08 19:34:59'),
(4, 'Hidden Gems: 10 Must-Visit Places in Southeast Asia', 'southeast-asia-travel.jpg', 'Lisa Park', '2024-05-12', 'While popular destinations like Bali and Bangkok attract millions, Southeast Asia is full of hidden treasures waiting to be discovered. From the pristine beaches of the Philippines to the ancient temples of Myanmar, explore the region\'s best-kept secrets.', 'Plan Your Adventure', 'published', '2026-09-08 19:35:07'),
(8, 'test2', '1789156425_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'Keynet', '2026-09-11', 'test2', 'Sign up', 'published', '2026-09-11 19:53:45'),
(15, 'we will make it', '1789157932_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'kim', '2026-09-11', 'edewdfe', 'ask how', 'published', '2026-09-11 20:18:52'),
(16, 'dhhdghj', '1789160323_WhatsApp Image 2025-03-08 at 20.48.22.jpeg', 'dfgjdfgj', '2026-09-11', 'dtyjyjy', 'dytjyjh', 'published', '2026-09-11 20:58:43'),
(17, 'patience', '1789190774_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'kim', '2026-09-12', 'dwdwqdqd', 'Patience', 'published', '2026-09-12 05:26:14'),
(20, 'hgghghhj', '1789193439_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'dfhg', '2026-09-12', 'tuyiufih', 'tyuily', 'draft', '2026-09-12 06:10:39'),
(21, 'knmnmmn', '1789195224_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'vghvmh', '2026-09-12', 'tyuhjmgjh', 'gfhmfhn', 'draft', '2026-09-12 06:40:24'),
(23, 'stephen', '1789243708_WhatsApp Image 2026-07-30 at 11.10.34 AM.jpeg', 'karanja', '2026-09-12', 'test', 'www.keynet.co.ke', 'published', '2026-09-12 20:08:28'),
(24, 'Bird1', '1789322770_Brown-crowned Tchagra_0462.JPG', 'kim', '2026-09-13', 'Successful', 'ask how', 'published', '2026-09-13 18:05:23'),
(25, 'test2', '1789322798_Black-collared Apalis.JPG', 'kimani', '2026-09-13', 'wrgrtbsrtv', 'jojokl', 'published', '2026-09-13 18:06:38');

-- --------------------------------------------------------

--
-- Table structure for table `company_info`
--

CREATE TABLE `company_info` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `company_info`
--

INSERT INTO `company_info` (`id`, `company_name`, `tagline`, `email`, `phone`, `address`, `website`, `about`, `mission`, `vision`, `logo`, `updated_at`) VALUES
(1, 'AviWild Safaris', 'Explore the world of birds with us', 'amukoya8@gmail.com', '+254796844645', 'Nairobi, Kenya', 'https://www.aviwildsafaris.com/', 'We are a passionate tour company...', 'To deliver unforgettable journeys', 'To be the leading tour operator', '1789320516_logo.jpeg', '2026-09-13 17:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `full_name`, `email`, `password`, `phone`, `bio`, `avatar`, `role`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(10, 'njogu', 'njogu@gmail.com', NULL, '0110055964', 'difhfdg', '1789205979_d1146203.jpg', 'user', 'active', '2026-09-12 09:39:39', '2026-09-12 09:39:39', NULL),
(13, 'gbgbgfbgfbs', 'kimanipeter907@gmail.com', NULL, '45675464', 'wgsdfv', '', 'editor', 'active', '2026-09-12 09:54:11', '2026-09-12 09:54:11', NULL),
(14, 'Peter Kimani', 'kimanipeter@gmail.com', '$2y$10$CF.F3cggtvKG0wIa2h97n.EJBJykj8qT7FtdDRppa0U02wGJ9AsRK', NULL, NULL, NULL, 'user', 'active', '2026-09-12 19:02:45', '2026-09-12 19:10:15', '2026-09-12 19:10:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_info`
--
ALTER TABLE `company_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `company_info`
--
ALTER TABLE `company_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
