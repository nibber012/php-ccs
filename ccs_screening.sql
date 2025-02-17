-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 16, 2025 at 03:31 PM
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
-- Database: `ccs_screening`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 08:39:51'),
(2, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 08:39:53'),
(3, 2, 'logout', 'User logged out', '::1', '2025-02-12 08:53:12'),
(4, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 08:53:17'),
(5, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 08:53:17'),
(6, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:13:25'),
(7, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:13:26'),
(8, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:15:00'),
(9, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:15:04'),
(10, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:15:05'),
(11, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:18:31'),
(12, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:18:34'),
(13, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:18:34'),
(14, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:18:34'),
(15, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:18:41'),
(16, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:18:41'),
(17, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:18:41'),
(18, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:19:00'),
(19, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:19:00'),
(20, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:19:00'),
(21, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:21:09'),
(22, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:21:09'),
(23, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:21:09'),
(24, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:24:06'),
(25, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:24:06'),
(26, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:24:07'),
(27, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 10:32:30'),
(28, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 10:32:30'),
(29, 2, 'logout', 'User logged out', '::1', '2025-02-12 10:32:30'),
(30, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 11:04:40'),
(31, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 11:04:40'),
(32, 2, 'logout', 'User logged out', '::1', '2025-02-12 11:04:40'),
(33, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 11:08:08'),
(34, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 11:08:08'),
(35, 2, 'logout', 'User logged out', '::1', '2025-02-12 11:08:08'),
(36, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 11:10:17'),
(37, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-12 11:10:17'),
(38, 2, 'logout', 'User logged out', '::1', '2025-02-12 11:10:17'),
(39, 2, 'login', 'User logged in successfully', '::1', '2025-02-12 11:10:25'),
(40, 2, 'logout', 'User logged out', '::1', '2025-02-12 11:10:25'),
(41, 2, 'login', 'User logged in as admin', NULL, '2025-02-12 11:10:25'),
(42, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 08:37:59'),
(43, 2, 'login', 'User logged in as super_admin', NULL, '2025-02-13 08:38:00'),
(44, 2, 'logout', 'User logged out', '::1', '2025-02-13 08:38:00'),
(45, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 11:59:44'),
(46, 2, 'logout', 'User logged out', '::1', '2025-02-13 11:59:44'),
(47, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:09:40'),
(48, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:09:41'),
(49, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:10:42'),
(50, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:10:42'),
(51, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:16:49'),
(52, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:16:50'),
(53, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:21:31'),
(54, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:21:31'),
(55, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:33:41'),
(56, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:33:41'),
(57, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:43:35'),
(58, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:43:35'),
(59, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 12:55:48'),
(60, 2, 'logout', 'User logged out', '::1', '2025-02-13 12:55:48'),
(61, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:00:49'),
(62, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:10:58'),
(63, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:11:07'),
(64, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:11:07'),
(65, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:11:43'),
(66, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:16:20'),
(67, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:16:28'),
(68, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:16:28'),
(69, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:18:02'),
(70, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:18:39'),
(71, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:18:51'),
(72, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:18:52'),
(73, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:23:01'),
(74, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:27:39'),
(75, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:27:45'),
(76, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:27:45'),
(77, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:28:47'),
(78, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:28:47'),
(79, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:31:43'),
(80, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:31:43'),
(81, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:34:50'),
(82, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:34:50'),
(83, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:34:57'),
(84, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:34:57'),
(85, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:37:47'),
(86, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:37:47'),
(87, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:38:51'),
(88, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:38:51'),
(89, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:41:00'),
(90, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:44:57'),
(91, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:45:30'),
(92, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:45:50'),
(93, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:47:09'),
(94, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:49:41'),
(95, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:49:49'),
(96, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:49:55'),
(97, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:53:29'),
(98, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:54:07'),
(99, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:54:15'),
(100, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:54:21'),
(101, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:55:03'),
(102, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:55:10'),
(103, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:55:46'),
(104, 2, 'logout', 'User logged out', '::1', '2025-02-13 13:55:51'),
(105, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 13:56:59'),
(106, 2, 'logout', 'User logged out', '::1', '2025-02-13 14:00:03'),
(107, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 14:00:09'),
(108, 2, 'logout', 'User logged out', '::1', '2025-02-13 14:02:17'),
(109, 2, 'login', 'User logged in successfully', '::1', '2025-02-13 14:02:24'),
(110, 2, 'logout', 'User logged out', '::1', '2025-02-13 14:02:27'),
(111, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 01:38:29'),
(112, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 05:20:04'),
(113, 2, 'logout', 'User logged out', '::1', '2025-02-14 05:59:56'),
(114, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 06:00:05'),
(115, 2, 'logout', 'User logged out', '::1', '2025-02-14 06:03:06'),
(116, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 06:03:15'),
(117, 2, 'admin_created', 'Created new admin account for ADMIN2 test (test_admin@gmail.com)', '::1', '2025-02-14 06:05:41'),
(118, 2, 'admin_created', 'Created new admin account for ADMIN2 test (test_admin@gmail.com)', '::1', '2025-02-14 06:06:03'),
(119, 2, 'logout', 'User logged out', '::1', '2025-02-14 06:09:35'),
(120, 5, 'login', 'User logged in successfully', '::1', '2025-02-14 06:09:41'),
(121, 5, 'logout', 'User logged out', '::1', '2025-02-14 06:11:03'),
(122, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 06:11:08'),
(123, 2, 'exam_created', 'Created new exam: Entrance Exam (Part 1)', '::1', '2025-02-14 06:42:04'),
(124, 2, 'exam_created', 'Created new exam: Entrance Exam (Part 1)', '::1', '2025-02-14 06:42:13'),
(125, 2, 'question_added', 'Added new question to exam ID: 2', '::1', '2025-02-14 06:44:11'),
(126, 2, 'question_added', 'Added new question to exam ID: 2', '::1', '2025-02-14 06:44:42'),
(127, 2, 'question_added', 'Added new question to exam ID: 2', '::1', '2025-02-14 06:44:57'),
(128, 2, 'question_added', 'Added new question to exam ID: 2', '::1', '2025-02-14 06:45:11'),
(129, 2, 'question_added', 'Added new question to exam ID: 2', '::1', '2025-02-14 06:45:41'),
(130, 2, 'exam_updated', 'Updated exam ID: 1', '::1', '2025-02-14 06:53:54'),
(131, 2, 'exam_updated', 'Updated exam ID: 1', '::1', '2025-02-14 06:54:06'),
(135, 9, 'register', 'New user registration', '::1', '2025-02-14 10:29:37'),
(136, 10, 'register', 'New user registration', '::1', '2025-02-14 10:32:05'),
(137, 11, 'register', 'New user registration', '::1', '2025-02-14 10:34:16'),
(138, 12, 'register', 'New user registration', '::1', '2025-02-14 10:38:37'),
(139, 13, 'register', 'New user registration', '::1', '2025-02-14 10:43:12'),
(140, 14, 'register', 'New user registration', '::1', '2025-02-14 10:49:44'),
(141, 15, 'register', 'New user registration', '::1', '2025-02-14 10:55:38'),
(142, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 10:56:11'),
(143, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 11:53:52'),
(144, 5, 'login', 'User logged in successfully', '::1', '2025-02-14 12:51:43'),
(145, 2, 'logout', 'User logged out', '::1', '2025-02-14 14:02:21'),
(146, 5, 'login', 'User logged in successfully', '::1', '2025-02-14 14:02:29'),
(147, 5, 'logout', 'User logged out', '::1', '2025-02-14 14:10:59'),
(148, 2, 'login', 'User logged in successfully', '::1', '2025-02-14 14:11:05'),
(149, 2, 'logout', 'User logged out', '::1', '2025-02-14 14:24:49'),
(150, 5, 'login', 'User logged in successfully', '::1', '2025-02-14 14:25:05'),
(151, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 02:23:41'),
(152, 5, 'logout', 'User logged out', '::1', '2025-02-15 02:24:25'),
(153, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 02:24:29'),
(154, 5, 'logout', 'User logged out', '::1', '2025-02-15 02:24:32'),
(155, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 02:24:55'),
(156, 5, 'logout', 'User logged out', '::1', '2025-02-15 02:25:11'),
(157, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 02:25:14'),
(158, 5, 'logout', 'User logged out', '::1', '2025-02-15 02:39:45'),
(159, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 02:39:48'),
(160, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:01:21'),
(161, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:01:23'),
(162, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:01:28'),
(163, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:01:30'),
(164, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:01:35'),
(165, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:03:15'),
(166, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:03:46'),
(167, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:03:50'),
(168, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:07:21'),
(169, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:07:22'),
(170, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:09:25'),
(171, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:09:27'),
(172, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:12:26'),
(173, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:12:29'),
(174, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:13:56'),
(175, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:13:59'),
(176, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:15:43'),
(177, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:15:45'),
(178, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:15:58'),
(179, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:16:00'),
(180, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:18:24'),
(181, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:26:01'),
(182, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:30:01'),
(183, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:30:07'),
(184, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:33:27'),
(185, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:33:30'),
(186, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 04:37:51'),
(187, 5, 'logout', 'User logged out', '::1', '2025-02-15 04:37:54'),
(188, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:11:38'),
(189, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:21:52'),
(190, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:21:57'),
(191, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:25:56'),
(192, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:26:08'),
(193, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:27:51'),
(194, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:27:54'),
(195, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:31:49'),
(196, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:31:51'),
(197, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:36:27'),
(198, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:36:29'),
(199, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:40:44'),
(200, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:40:45'),
(201, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:41:34'),
(202, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:42:18'),
(203, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:52:08'),
(204, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:52:09'),
(205, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:52:11'),
(206, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:52:11'),
(207, 2, 'login', 'User logged in successfully', '::1', '2025-02-15 05:52:17'),
(208, 2, 'logout', 'User logged out', '::1', '2025-02-15 05:52:21'),
(209, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:52:28'),
(210, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:55:46'),
(211, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:55:48'),
(212, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:56:34'),
(213, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 05:56:35'),
(214, 5, 'logout', 'User logged out', '::1', '2025-02-15 05:56:54'),
(215, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 06:16:21'),
(216, 5, 'logout', 'User logged out', '::1', '2025-02-15 07:15:45'),
(217, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 07:15:48'),
(218, 5, 'logout', 'User logged out', '::1', '2025-02-15 08:24:18'),
(219, 16, 'register', 'New user registration', '::1', '2025-02-15 08:33:10'),
(220, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 08:36:21'),
(221, 5, 'logout', 'User logged out', '::1', '2025-02-15 09:16:33'),
(222, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 09:16:35'),
(223, 5, 'logout', 'User logged out', '::1', '2025-02-15 09:42:05'),
(224, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 09:42:58'),
(225, 5, 'logout', 'User logged out', '::1', '2025-02-15 09:43:13'),
(226, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 09:43:15'),
(227, 5, 'logout', 'User logged out', '::1', '2025-02-15 09:50:07'),
(228, 15, 'login', 'User logged in successfully', '::1', '2025-02-15 09:59:34'),
(229, 15, 'logout', 'User logged out', '::1', '2025-02-15 09:59:56'),
(230, 15, 'login', 'User logged in successfully', '::1', '2025-02-15 10:00:20'),
(231, 15, 'logout', 'User logged out', '::1', '2025-02-15 10:00:22'),
(232, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:00:48'),
(233, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:01:40'),
(234, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:01:42'),
(235, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:02:05'),
(236, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:02:07'),
(237, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:02:40'),
(238, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:13:20'),
(239, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:14:07'),
(240, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:14:10'),
(241, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:14:16'),
(242, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:21:56'),
(243, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:22:00'),
(244, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:22:03'),
(245, 15, 'login', 'User logged in successfully', '::1', '2025-02-15 10:22:08'),
(246, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 10:34:57'),
(247, 5, 'logout', 'User logged out', '::1', '2025-02-15 10:35:09'),
(248, 2, 'login', 'User logged in successfully', '::1', '2025-02-15 10:35:34'),
(249, 2, 'exam_publish', 'Exam (ID: 1) status changed to published', '::1', '2025-02-15 10:36:18'),
(250, 5, 'login', 'User logged in successfully', '::1', '2025-02-15 11:26:47'),
(251, 5, 'logout', 'User logged out', '::1', '2025-02-15 11:26:53'),
(252, 2, 'login', 'User logged in successfully', '::1', '2025-02-15 11:27:00'),
(253, 2, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-15 11:28:07'),
(254, 2, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-15 11:28:18'),
(255, 2, 'logout', 'User logged out', '::1', '2025-02-15 11:28:22'),
(256, 2, 'login', 'User logged in successfully', '::1', '2025-02-15 11:38:05'),
(257, 2, 'exam_publish', 'Exam (ID: 2) status changed to published', '::1', '2025-02-15 11:38:24'),
(258, 15, 'login', 'User logged in successfully', '::1', '2025-02-15 13:59:17'),
(259, 15, 'login', 'User logged in successfully', '::1', '2025-02-16 10:57:43'),
(260, 15, 'logout', 'User logged out', '::1', '2025-02-16 12:32:34'),
(261, 15, 'login', 'User logged in successfully', '::1', '2025-02-16 12:32:39'),
(262, 15, 'logout', 'User logged out', '::1', '2025-02-16 13:03:45'),
(263, 14, 'login', 'User logged in successfully', '::1', '2025-02-16 13:03:51'),
(264, 14, 'logout', 'User logged out', '::1', '2025-02-16 14:09:38'),
(265, 5, 'login', 'User logged in successfully', '::1', '2025-02-16 14:09:53'),
(266, 5, 'logout', 'User logged out', '::1', '2025-02-16 14:11:16'),
(267, 13, 'login', 'User logged in successfully', '::1', '2025-02-16 14:11:25'),
(268, 13, 'logout', 'User logged out', '::1', '2025-02-16 14:28:49'),
(269, 12, 'login', 'User logged in successfully', '::1', '2025-02-16 14:28:59'),
(270, 12, 'logout', 'User logged out', '::1', '2025-02-16 14:29:15'),
(271, 12, 'login', 'User logged in successfully', '::1', '2025-02-16 14:29:26');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `user_id`, `first_name`, `last_name`, `department`) VALUES
(2, 5, 'ADMIN2', 'test', 'CCS');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','admin','applicant') NOT NULL DEFAULT 'all',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `applicant_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `birth_date` date NOT NULL,
  `school` varchar(255) NOT NULL,
  `course` enum('BSCS','BSIT') NOT NULL,
  `year_level` enum('1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate') NOT NULL,
  `progress_status` enum('registered','part1_pending','part1_completed','part2_pending','part2_completed','interview_pending','interview_completed','passed','failed') NOT NULL DEFAULT 'registered',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `application_status_id` int(11) DEFAULT NULL,
  `exam_status_id` int(11) DEFAULT NULL,
  `preferred_course` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `user_id`, `applicant_number`, `first_name`, `middle_name`, `last_name`, `contact_number`, `address`, `birth_date`, `school`, `course`, `year_level`, `progress_status`, `created_at`, `updated_at`, `application_status_id`, `exam_status_id`, `preferred_course`) VALUES
(3, 9, '20250001', 'applicant 1', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-14 10:29:37', '2025-02-15 08:34:31', NULL, NULL, 'BS Information Technology'),
(4, 10, '20250002', 'applicant 2', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-14 10:32:05', '2025-02-15 08:34:31', NULL, NULL, 'BS Information Technology'),
(5, 11, '20250003', 'applicant 3', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-14 10:34:16', '2025-02-14 10:34:16', NULL, NULL, 'BS Computer Science'),
(6, 12, '20250004', 'applicant 4', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-14 10:38:37', '2025-02-14 10:38:37', NULL, NULL, 'BS Computer Science'),
(7, 13, '20250005', 'applicant 5', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-14 10:43:12', '2025-02-15 08:34:31', NULL, NULL, 'BS Information Technology'),
(8, 14, '20250006', 'applicant 6', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-14 10:49:44', '2025-02-14 10:49:44', NULL, NULL, 'BS Computer Science'),
(9, 15, '20250007', 'applicant 7', NULL, 'test', '09272521245', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-14 10:55:38', '2025-02-15 08:34:31', NULL, NULL, 'BS Information Technology'),
(10, 16, '20250008', 'John Doe', NULL, 'Mamah', '09651242386', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-15 08:33:10', '2025-02-15 08:33:10', NULL, NULL, 'BS Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_answers`
--

CREATE TABLE `applicant_answers` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_answers`
--

INSERT INTO `applicant_answers` (`id`, `applicant_id`, `exam_id`, `question_id`, `answer`, `is_correct`, `score`, `submitted_at`) VALUES
(2, 15, 2, 1, '12', 0, 0, '2025-02-16 12:54:38'),
(3, 15, 2, 2, 'qw', 0, 0, '2025-02-16 12:54:38'),
(4, 15, 2, 3, '456', 0, 0, '2025-02-16 12:54:38'),
(5, 15, 2, 4, '12', 0, 0, '2025-02-16 12:54:38'),
(6, 15, 2, 5, '123', 0, 0, '2025-02-16 12:54:38');

-- --------------------------------------------------------

--
-- Table structure for table `application_status`
--

CREATE TABLE `application_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_status`
--

INSERT INTO `application_status` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'pending', 'Application is under review', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(2, 'approved', 'Application has been approved', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(3, 'rejected', 'Application has been rejected', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(4, 'incomplete', 'Application is missing required documents', '2025-02-12 02:03:31', '2025-02-12 02:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template_name` varchar(50) NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `related_type` enum('interview_schedule','interview_result','interview_reminder','interview_cancellation') NOT NULL,
  `related_id` int(11) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('mcq','coding') NOT NULL,
  `part` enum('1','2') NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `passing_score` int(11) NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `description`, `type`, `part`, `duration_minutes`, `passing_score`, `status`, `created_by`, `created_at`, `updated_at`, `instructions`) VALUES
(1, 'test 1', 'Please be informed that the following exam will determine your eligibility for enrollment in the course. Kindly take it seriously. Thank you.', 'mcq', '1', 60, 75, 'published', 2, '2025-02-14 06:42:04', '2025-02-15 10:36:18', ''),
(2, 'Entrance Exam', 'Please be informed that the following exam will determine your eligibility for enrollment in the course. Kindly take it seriously. Thank you.', 'mcq', '1', 60, 75, 'published', 2, '2025-02-14 06:42:13', '2025-02-15 11:38:24', '');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `passing_score` int(11) NOT NULL,
  `status` enum('pass','fail') NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_time` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `applicant_id`, `exam_id`, `score`, `passing_score`, `status`, `started_at`, `completed_at`, `completion_time`, `created_at`) VALUES
(23, 9, 2, 0, 75, 'fail', NULL, '2025-02-16 12:54:38', NULL, '2025-02-16 20:54:38'),
(24, 15, 2, 0, 75, 'fail', NULL, '2025-02-16 12:54:38', NULL, '2025-02-16 20:54:38'),
(27, 14, 2, 75, 75, 'pass', NULL, '2025-02-16 14:03:15', NULL, '2025-02-16 22:03:15'),
(28, 14, 2, 75, 75, 'pass', NULL, '2025-02-16 14:03:15', NULL, '2025-02-16 22:03:15'),
(29, 13, 2, 75, 75, 'pass', NULL, '2025-02-16 14:11:43', NULL, '2025-02-16 22:11:43'),
(30, 13, 2, 75, 75, 'pass', NULL, '2025-02-16 14:11:43', NULL, '2025-02-16 22:11:43'),
(31, 12, 2, 75, 75, 'pass', NULL, '2025-02-16 14:29:11', NULL, '2025-02-16 22:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `exam_status`
--

CREATE TABLE `exam_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_status`
--

INSERT INTO `exam_status` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'not_started', 'Exam has not been started', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(2, 'in_progress', 'Exam is currently in progress', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(3, 'completed', 'Exam has been completed', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(4, 'graded', 'Exam has been graded', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(5, 'failed', 'Failed to meet the required score', '2025-02-12 02:03:31', '2025-02-12 02:03:31'),
(6, 'passed', 'Passed the required score', '2025-02-12 02:03:31', '2025-02-12 02:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedules`
--

CREATE TABLE `interview_schedules` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `interviewer_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `interview_status` enum('pending','passed','failed') NOT NULL DEFAULT 'pending',
  `total_score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_scores`
--

CREATE TABLE `interview_scores` (
  `id` int(11) NOT NULL,
  `interview_id` int(11) NOT NULL,
  `category` enum('technical_skills','communication','problem_solving','cultural_fit','overall_impression') NOT NULL,
  `score` int(11) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','coding') NOT NULL,
  `points` int(11) NOT NULL DEFAULT 1,
  `coding_template` text DEFAULT NULL,
  `test_cases` text DEFAULT NULL,
  `solution` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `exam_id`, `question_text`, `question_type`, `points`, `coding_template`, `test_cases`, `solution`, `explanation`, `options`, `correct_answer`, `created_at`, `updated_at`) VALUES
(1, 2, 'What is the size of maaah pet cock', 'multiple_choice', 15, NULL, NULL, NULL, 'Correct Answer is A cause its a wiener you cuck sucker', '[\"12\",\"32\",\"12\",\"12\"]', '0', '2025-02-14 06:44:11', '2025-02-14 06:44:11'),
(2, 2, 'test', 'multiple_choice', 15, NULL, NULL, NULL, '', '[\"qw\",\"qwe\",\"qweq\",\"weq\"]', '0', '2025-02-14 06:44:42', '2025-02-14 06:44:42'),
(3, 2, '7t7tt7ttuuttutu', 'multiple_choice', 15, NULL, NULL, NULL, '', '[\"456\",\"456\",\"4564\",\"456\"]', '0', '2025-02-14 06:44:57', '2025-02-14 06:44:57'),
(4, 2, '234', 'multiple_choice', 15, NULL, NULL, NULL, 'qwe', '[\"12\",\"sdr\",\"wer\",\"wer\"]', '0', '2025-02-14 06:45:10', '2025-02-14 06:45:10'),
(5, 2, 'qweq', 'multiple_choice', 15, NULL, NULL, NULL, 'asda', '[\"123\",\"1231\",\"2312\",\"3123\"]', '0', '2025-02-14 06:45:41', '2025-02-14 06:45:41'),
(6, 1, '123123', 'multiple_choice', 50, NULL, NULL, NULL, '', '[\"123123\",\"123\",\"123123\",\"123123\"]', '0', '2025-02-15 11:28:07', '2025-02-15 11:28:07'),
(7, 1, '123212', 'multiple_choice', 50, NULL, NULL, NULL, '', '[\"123\",\"12312\",\"3123\",\"1231\"]', '0', '2025-02-15 11:28:18', '2025-02-15 11:28:18');

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE `status_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_history`
--

INSERT INTO `status_history` (`id`, `user_id`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 15, 'approved', '', 5, '2025-02-15 07:15:27'),
(2, 16, 'rejected', '', 5, '2025-02-15 09:16:29'),
(3, 14, 'pending', '', 5, '2025-02-15 09:43:11'),
(4, 14, 'approved', '', 5, '2025-02-15 09:44:23'),
(5, 13, 'approved', '', 5, '2025-02-16 14:10:18'),
(6, 12, 'approved', '', 5, '2025-02-16 14:10:40'),
(7, 11, 'approved', '', 5, '2025-02-16 14:10:42'),
(8, 10, 'approved', '', 5, '2025-02-16 14:10:46'),
(9, 9, 'approved', '', 5, '2025-02-16 14:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_admins`
--

INSERT INTO `super_admins` (`id`, `user_id`, `first_name`, `last_name`) VALUES
(2, 2, 'Carlo Joshua', 'Abellera');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','applicant') NOT NULL,
  `status` enum('active','inactive','pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `role`, `status`, `created_at`, `updated_at`, `updated_by`) VALUES
(2, 'abellera.cj.bsinfotech@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Carlo Joshua', 'Abellera', 'super_admin', 'active', '2025-02-12 02:50:16', '2025-02-14 06:00:43', NULL),
(5, 'test_admin@gmail.com', '$2y$10$im1kdvuom2.t/ku3opOvHOBG7fNdSngM4XOdSMFiX8GDPWgqcp/se', '', '', 'admin', 'active', '2025-02-14 06:06:03', '2025-02-14 06:06:03', NULL),
(9, 'testingapplicant@gmail.com', '$2y$10$t8w2EU/cdswTUrV2wbKRTeMgbuGf2uwEdo4gmZAIwVTjElllIVSNS', '', '', 'applicant', 'approved', '2025-02-14 10:29:37', '2025-02-16 14:11:05', 5),
(10, 'testingapplicant2@gmail.com', '$2y$10$KF99MANxSdg6MglMMKXKJenkRIhjyTrZtYmefE.tpdTRRs.nKkpcW', '', '', 'applicant', 'approved', '2025-02-14 10:32:05', '2025-02-16 14:10:46', 5),
(11, 'testingapplicant3@gmail.com', '$2y$10$K84gqSvABeAhWLCeHRaPiu/VTzwfyDASfkp/zEjQf2gD/9biHFR4q', '', '', 'applicant', 'approved', '2025-02-14 10:34:16', '2025-02-16 14:10:42', 5),
(12, 'testingapplicant4@gmail.com', '$2y$10$yta6GEhZyGWplgXkrm6RTeuK1AuirVxwr2sjqrHCW7kAF29JS/Ud6', '', '', 'applicant', 'approved', '2025-02-14 10:38:37', '2025-02-16 14:10:39', 5),
(13, 'testingapplicant5@gmail.com', '$2y$10$PK/e4PgeWel2Pvbte2D84ejd9KkMvwJSCopS/YsQvcRTjfVvM4HuC', '', '', 'applicant', 'approved', '2025-02-14 10:43:12', '2025-02-16 14:10:18', 5),
(14, 'testingapplicant6@gmail.com', '$2y$10$iiDQuEDvbWoR0QcRIXhIi.TrfIO1NMCAC0mtayWlGx4p.A.5IyGxa', '', '', 'applicant', 'approved', '2025-02-14 10:49:44', '2025-02-15 09:44:23', 5),
(15, 'testingapplicant7@gmail.com', '$2y$10$4U6Xp.i5SMwPtXu0tMv9guKwbd8SSA8ONwyTyAl4QoaD4NUx4xFH.', '', '', 'applicant', 'approved', '2025-02-14 10:55:38', '2025-02-15 09:34:10', 5),
(16, 'john_doe.mama@gmail.com', '$2y$10$QesfjKh7Xdd6P6BWO96ACegaOitFDLyxl6AheQZR3/ykoYXOstsIK', '', '', 'applicant', 'rejected', '2025-02-15 08:33:10', '2025-02-15 09:16:29', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `application_status_id` (`application_status_id`),
  ADD KEY `exam_status_id` (`exam_status_id`);

--
-- Indexes for table `applicant_answers`
--
ALTER TABLE `applicant_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_email` (`recipient_email`),
  ADD KEY `template_name` (`template_name`),
  ADD KEY `status` (`status`),
  ADD KEY `related_type_id` (`related_type`,`related_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_status`
--
ALTER TABLE `exam_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `interviewer_id` (`interviewer_id`);

--
-- Indexes for table `interview_scores`
--
ALTER TABLE `interview_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interview_id` (`interview_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `status_history`
--
ALTER TABLE `status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=272;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `applicant_answers`
--
ALTER TABLE `applicant_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `exam_status`
--
ALTER TABLE `exam_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_scores`
--
ALTER TABLE `interview_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applicants_ibfk_2` FOREIGN KEY (`application_status_id`) REFERENCES `application_status` (`id`),
  ADD CONSTRAINT `applicants_ibfk_3` FOREIGN KEY (`exam_status_id`) REFERENCES `exam_status` (`id`);

--
-- Constraints for table `applicant_answers`
--
ALTER TABLE `applicant_answers`
  ADD CONSTRAINT `applicant_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `applicant_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `fk_applicant_answers_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `fk_exam_results_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD CONSTRAINT `interview_schedules_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`),
  ADD CONSTRAINT `interview_schedules_ibfk_2` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `interview_scores`
--
ALTER TABLE `interview_scores`
  ADD CONSTRAINT `interview_scores_ibfk_1` FOREIGN KEY (`interview_id`) REFERENCES `interview_schedules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `status_history`
--
ALTER TABLE `status_history`
  ADD CONSTRAINT `status_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `super_admins`
--
ALTER TABLE `super_admins`
  ADD CONSTRAINT `super_admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
