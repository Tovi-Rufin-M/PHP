-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 02:26 PM
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
-- Database: `technowatchclub_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`user_id`, `username`, `password_hash`, `email`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$P0ad.jdfzDAxdMMP8P0NSOjGfNKEpU/D1jA3PypVYILQKVIx5/Edm', 'technowatch@tup.edu.ph', 'TechnoWatch Administrator', '2025-11-15 18:38:11');

-- --------------------------------------------------------

--
-- Table structure for table `events_news`
--

CREATE TABLE `events_news` (
  `item_id` int(11) NOT NULL,
  `type` enum('event','news') NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `job_id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_website` varchar(512) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `salary_range` varchar(255) DEFAULT NULL,
  `application_link` varchar(512) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `job_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `merch`
--

CREATE TABLE `merch` (
  `merch_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officers_club`
--

CREATE TABLE `officers_club` (
  `officer_id` int(11) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `position` varchar(100) NOT NULL COMMENT 'e.g., Club President, Vice President, Creative Member',
  `category` varchar(50) NOT NULL COMMENT 'Grouping: EXECUTIVE, REPRESENTATIVES, CREATIVES',
  `short_bio` text DEFAULT NULL COMMENT 'Short description or tagline (Used for Motto/Biography)',
  `email` varchar(100) DEFAULT NULL COMMENT 'Optional contact email for the officer',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'Path relative to the root (e.g., assets/officers/image.jpg)',
  `sort_order` int(5) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `officials_staff`
--

CREATE TABLE `officials_staff` (
  `staff_id` int(11) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(100) NOT NULL COMMENT 'e.g., Department Head, Instructor I, Mayor',
  `section` varchar(50) NOT NULL COMMENT 'Category: DEPT_HEAD, FACULTY, MAYOR_S09, MAYOR_T09, MAYOR_F09',
  `quote` text DEFAULT NULL COMMENT 'Optional quote or short description',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'Path relative to the root',
  `email` varchar(100) DEFAULT NULL,
  `sort_order` int(5) NOT NULL DEFAULT 0 COMMENT 'Lower number shows first',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `officials_staff`
--

INSERT INTO `officials_staff` (`staff_id`, `full_name`, `role`, `section`, `quote`, `image_path`, `email`, `sort_order`, `is_active`) VALUES
(1, 'gseg', 'DEPARTMENT HEAD', 'DEPT_HEAD', 'gew', 'assets/officials/gseg_1763644615_Screenshot 2025-08-17 170724.png', NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `full_description` text NOT NULL,
  `tag` varchar(100) NOT NULL,
  `categories` varchar(255) NOT NULL COMMENT 'Space-separated list of categories like "ai-robotics current"',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) NOT NULL COMMENT 'Path to the project image, e.g., assets/imgs/carousel_11.jpg',
  `features` text DEFAULT NULL COMMENT 'Comma-separated string of key features',
  `case_study_link` varchar(255) DEFAULT '#'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `events_news`
--
ALTER TABLE `events_news`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`job_id`);

--
-- Indexes for table `merch`
--
ALTER TABLE `merch`
  ADD PRIMARY KEY (`merch_id`);

--
-- Indexes for table `officers_club`
--
ALTER TABLE `officers_club`
  ADD PRIMARY KEY (`officer_id`);

--
-- Indexes for table `officials_staff`
--
ALTER TABLE `officials_staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events_news`
--
ALTER TABLE `events_news`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `merch`
--
ALTER TABLE `merch`
  MODIFY `merch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `officers_club`
--
ALTER TABLE `officers_club`
  MODIFY `officer_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `officials_staff`
--
ALTER TABLE `officials_staff`
  MODIFY `staff_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
