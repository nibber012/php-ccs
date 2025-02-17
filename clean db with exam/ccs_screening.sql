-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2025 at 12:26 PM
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
(1, 1, 'admin_created', 'Created new admin account for Utahime Iori (test_admin1@css.edu.ph)', NULL, '2025-02-17 11:06:00'),
(2, 1, 'admin_created', 'Created new admin account for Akari Naita (test_admin2@css.edu.ph)', NULL, '2025-02-17 11:06:00'),
(3, 1, 'admin_created', 'Created new admin account for Kento Nanami (test_admin3@css.edu.ph)', NULL, '2025-02-17 11:06:00'),
(4, 5, 'login', 'User logged in successfully', '::1', '2025-02-17 11:06:12'),
(5, 5, 'logout', 'User logged out', '::1', '2025-02-17 11:07:38'),
(6, 1, 'login', 'User logged in successfully', '::1', '2025-02-17 11:07:49'),
(7, 1, 'exam_created', 'Created new exam: General Exam (Part 1)', '::1', '2025-02-17 11:12:01'),
(8, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:14:16'),
(9, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:15:01'),
(10, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:15:44'),
(11, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:16:48'),
(12, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:17:32'),
(13, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:18:25'),
(14, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:19:14'),
(15, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:19:52'),
(16, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:20:31'),
(17, 1, 'question_added', 'Added new question to exam ID: 1', '::1', '2025-02-17 11:21:06');

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
(1, 2, 'Utahime', 'Iori', 'CCS'),
(2, 3, 'Akari', 'Naita', 'CCS'),
(3, 4, 'Kento', 'Nanami', 'CCS');

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
(1, 5, '20250001', 'John', NULL, 'Doe', '09925835705', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(2, 6, '20250002', 'Jane', NULL, 'Smith', '09294339560', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(3, 7, '20250003', 'Michael', NULL, 'Johnson', '09549602259', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(4, 8, '20250004', 'Emily', NULL, 'Davis', '09221014083', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(5, 9, '20250005', 'Daniel', NULL, 'Martinez', '09341867093', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(6, 10, '20250006', 'Olivia', NULL, 'Anderson', '09881988659', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(7, 11, '20250007', 'William', NULL, 'Brown', '09328682689', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science'),
(8, 12, '20250008', 'Sophia', NULL, 'Wilson', '09880922823', '', '0000-00-00', '', 'BSIT', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Information Technology'),
(9, 13, '20250009', 'James', NULL, 'Harris', '09599394694', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Information Technology'),
(10, 14, '20250010', 'Isabella', NULL, 'Miller', '09966660801', '', '0000-00-00', '', 'BSCS', '1st Year', 'registered', '2025-02-17 11:06:00', '2025-02-17 11:06:00', NULL, NULL, 'BS Computer Science');

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
(1, 'General Exam', 'The General Exam for the CCS department is crucial in assessing your eligibility to apply for courses. Your performance on this test will play a significant role in determining your suitability for the program. Please proceed with caution. Good Luck!', 'mcq', '1', 60, 75, 'draft', 1, '2025-02-17 11:12:00', '2025-02-17 11:12:00', 'Avoid logging out during the examination. You are given 1 hour for this exam. Should the time ran out you will not be able to take the exam anymore and will be automatically marked as failed');

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

-- --------------------------------------------------------

--
-- Table structure for table `exam_start_times`
--

CREATE TABLE `exam_start_times` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `started_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 1, 'What is the main function of the Central Processing Unit (CPU)?', 'multiple_choice', 10, NULL, NULL, NULL, 'The CPU is the brain of the computer. Its main function is to execute instructions, performing arithmetic and logical operations to process data.', '[\"To store data\",\"To perform arithmetic and logical operations\",\"To manage software\",\"To display graphics\"]', '1', '2025-02-17 11:14:15', '2025-02-17 11:14:15'),
(2, 1, 'Which of the following is not a programming language?', 'multiple_choice', 10, NULL, NULL, NULL, 'HTML (HyperText Markup Language) is used for creating web pages and web applications. It is not a programming language but a markup language.', '[\"Python\",\"Java\",\"HTML\",\"C++\"]', '2', '2025-02-17 11:15:01', '2025-02-17 11:15:01'),
(3, 1, 'What does RAM stand for?', 'multiple_choice', 10, NULL, NULL, NULL, 'RAM stands for Random Access Memory, which is a type of computer memory that can be accessed randomly. It is used for storing working data and machine code currently in use.', '[\"Random Access Memory\",\"Read Access Memory\",\"Rapid Access Memory\",\"Recurrent Access Memory\"]', '0', '2025-02-17 11:15:44', '2025-02-17 11:15:44'),
(4, 1, 'Which of the following is a type of network topology?', 'multiple_choice', 10, NULL, NULL, NULL, 'A star topology is a type of network topology where each node is connected to a central hub or switch. Other types include bus, ring, and mesh topologies.', '[\"Star\",\"Circle\",\"Rectangle\",\"Triangle\"]', '0', '2025-02-17 11:16:48', '2025-02-17 11:16:48'),
(5, 1, 'Which of the following best describes an algorithm?', 'multiple_choice', 10, NULL, NULL, NULL, 'An algorithm is a step-by-step procedure or formula for solving a problem or performing a task.', '[\"A type of computer hardware\",\"A set of instructions to solve a problem\",\"A programming language\",\"A storage device\"]', '1', '2025-02-17 11:17:31', '2025-02-17 11:17:31'),
(6, 1, 'In which language is SQL used?', 'multiple_choice', 10, NULL, NULL, NULL, 'SQL stands for Structured Query Language. It is used for managing and manipulating relational databases.', '[\"Structured Query Language\",\"Sequential Query Language\",\"Simple Query Language\",\"Standard Query Language\"]', '0', '2025-02-17 11:18:24', '2025-02-17 11:18:24'),
(7, 1, 'Which of the following is an example of a high-level programming language?', 'multiple_choice', 10, NULL, NULL, NULL, 'Python is a high-level programming language known for its readability and simplicity. Assembly and machine code are low-level languages, and binary is a form of data representation.', '[\"Assembly\",\"Machine code\",\"Python\",\"Binary\"]', '2', '2025-02-17 11:19:14', '2025-02-17 11:19:14'),
(8, 1, 'What does the term \'bit\' stand for?', 'multiple_choice', 10, NULL, NULL, NULL, 'A \'bit\' stands for binary digit, which is the most basic unit of data in computing, representing a 0 or 1.', '[\"Binary Information Technique\",\"Binary Digit\",\"Binary Integer\",\"Binary Internal Translation\"]', '1', '2025-02-17 11:19:51', '2025-02-17 11:19:51'),
(9, 1, 'Which of the following is used to uniquely identify a record in a database table?', 'multiple_choice', 10, NULL, NULL, NULL, 'A primary key is a unique identifier for a record in a database table. It ensures that each record can be uniquely identified.', '[\"Primary Key\",\"Foreign Key\",\"Composite Key\",\"Secondary Key\"]', '0', '2025-02-17 11:20:31', '2025-02-17 11:20:31'),
(10, 1, 'Which of the following is a type of operating system?', 'multiple_choice', 10, NULL, NULL, NULL, 'Windows is a type of operating system developed by Microsoft. It provides a graphical user interface (GUI) and manages hardware and software resources on a computer.', '[\"Windows\",\"Google\",\"Python\",\"Facebook\"]', '0', '2025-02-17 11:21:06', '2025-02-17 11:21:06');

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
(1, 'superadmin@ccs.edu.ph', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Gojo', 'Satoru', 'super_admin', 'active', '2025-02-17 11:05:58', '2025-02-17 11:05:58', 69420),
(2, 'test_admin1@css.edu.ph', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Utahime', 'Iori', 'admin', 'active', '2025-02-17 11:05:58', '2025-02-17 11:05:58', 1),
(3, 'test_admin2@css.edu.ph', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Akari', 'Naita', 'admin', 'active', '2025-02-17 11:05:58', '2025-02-17 11:05:58', 1),
(4, 'test_admin3@css.edu.ph', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Kento', 'Nanami', 'admin', 'active', '2025-02-17 11:05:58', '2025-02-17 11:05:58', 1),
(5, 'test_applicant1@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'John', 'Doe', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(6, 'test_applicant2@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Jane', 'Smith', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(7, 'test_applicant3@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Michael', 'Johnson', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(8, 'test_applicant4@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Emily', 'Davis', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(9, 'test_applicant5@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Daniel', 'Martinez', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(10, 'test_applicant6@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Olivia', 'Anderson', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(11, 'test_applicant7@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'William', 'Brown', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(12, 'test_applicant8@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Sophia', 'Wilson', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(13, 'test_applicant9@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'James', 'Harris', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420),
(14, 'test_applicant10@gmail.com', '$2y$10$GutilQeoQQlOqddBN.eOcuz2JDM4Xw8CGJMaTV8ANHBfms7OGiqfG', 'Isabella', 'Miller', 'applicant', 'approved', '2025-02-17 11:06:00', '2025-02-17 11:06:00', 69420);

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
-- Indexes for table `exam_start_times`
--
ALTER TABLE `exam_start_times`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_start_applicant` (`applicant_id`);

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
  ADD KEY `created_by` (`created_by`),
  ADD KEY `status_history_ibfk_1` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `applicant_answers`
--
ALTER TABLE `applicant_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_start_times`
--
ALTER TABLE `exam_start_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_status`
--
ALTER TABLE `exam_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `status_history`
--
ALTER TABLE `status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `applicants_ibfk_3` FOREIGN KEY (`exam_status_id`) REFERENCES `exam_status` (`id`);

--
-- Constraints for table `applicant_answers`
--
ALTER TABLE `applicant_answers`
  ADD CONSTRAINT `applicant_answers_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`),
  ADD CONSTRAINT `applicant_answers_ibfk_3` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Constraints for table `exam_start_times`
--
ALTER TABLE `exam_start_times`
  ADD CONSTRAINT `fk_exam_start_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`user_id`);

--
-- Constraints for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD CONSTRAINT `interview_schedules_ibfk_2` FOREIGN KEY (`interviewer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `interview_scores`
--
ALTER TABLE `interview_scores`
  ADD CONSTRAINT `interview_scores_ibfk_1` FOREIGN KEY (`interview_id`) REFERENCES `interview_schedules` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

--
-- Constraints for table `status_history`
--
ALTER TABLE `status_history`
  ADD CONSTRAINT `status_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
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
