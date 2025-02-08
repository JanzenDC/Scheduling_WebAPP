-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2025 at 03:41 AM
-- Server version: 10.4.11-MariaDB
-- PHP Version: 7.4.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ss_webapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `module_alias` varchar(255) NOT NULL,
  `sequence_number` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `module_name`, `module_alias`, `sequence_number`, `created_at`, `updated_at`) VALUES
(4, 'Accounts & Roles', 'account_management', 99, '2025-01-17 12:45:12', '2025-01-21 16:08:11'),
(13, 'Authorization Management', 'authorization_management', 100, '2025-01-17 14:43:43', '2025-01-21 16:08:24'),
(17, 'Task Management', 'task_management', 98, '2025-02-08 01:49:58', '2025-02-08 01:49:58');

-- --------------------------------------------------------

--
-- Table structure for table `module_permissions`
--

CREATE TABLE `module_permissions` (
  `module_permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `module_permissions`
--

INSERT INTO `module_permissions` (`module_permission_id`, `role_id`, `module_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`, `updated_at`) VALUES
(7, 1, 4, 0, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(8, 1, 13, 0, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(13, 3, 4, 0, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03'),
(14, 3, 13, 0, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `page_id` int(11) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_alias` varchar(255) NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `sequence_number` int(11) DEFAULT NULL,
  `is_active` varchar(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`page_id`, `icon`, `page_name`, `page_alias`, `module_id`, `sequence_number`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 'fa-solid fa-user', 'User Management', 'user_management', 4, 1, '1', '2025-01-17 14:28:25', '2025-01-18 03:35:56'),
(8, 'fa-solid fa-gear', 'Permissions', 'permission_management', 13, 1, '1', '2025-01-18 02:14:10', '2025-01-18 02:14:10'),
(12, 'fa-solid fa-user', 'Roles', 'roles_management', 4, 2, '1', '2025-01-18 04:03:20', '2025-01-18 04:03:20'),
(13, 'fa-regular fa-note-sticky', 'Assign Roles', 'assign_roles', 4, 3, '1', '2025-01-19 01:57:03', '2025-01-19 01:57:03'),
(52, 'fa-solid fa-list-check', 'Create Task', 'createtask_management', 17, 1, '1', '2025-02-08 01:54:17', '2025-02-08 01:54:17');

-- --------------------------------------------------------

--
-- Table structure for table `page_permissions`
--

CREATE TABLE `page_permissions` (
  `page_permission_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `page_permissions`
--

INSERT INTO `page_permissions` (`page_permission_id`, `role_id`, `page_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `created_at`, `updated_at`) VALUES
(10, 1, 5, 1, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(11, 1, 12, 1, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(12, 1, 13, 1, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(13, 1, 8, 1, 0, 0, 0, '2025-01-19 10:15:27', '2025-01-19 10:15:27'),
(22, 3, 5, 1, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03'),
(23, 3, 12, 0, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03'),
(24, 3, 13, 1, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03'),
(25, 3, 8, 0, 0, 0, 0, '2025-01-21 12:12:03', '2025-01-21 12:12:03');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'Has full access to all system functionalities', 1, '2025-01-18 08:58:24', '2025-01-18 09:24:33'),
(2, 'Admin', 'Has administrative access with some restrictions', 1, '2025-01-18 08:58:24', '2025-01-18 08:58:24'),
(3, 'User', 'Regular user with basic access rights', 1, '2025-01-18 08:58:24', '2025-01-18 08:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `task_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `task_name`, `description`, `task_date`, `start_time`, `end_time`, `created_at`) VALUES
(1, 'Birthday Ko', 'test', '2025-02-08', '10:14:00', '23:15:00', '2025-02-08 02:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `assignment_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`assignment_id`, `task_id`, `user_id`) VALUES
(1, 1, 1),
(2, 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `mname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fname`, `mname`, `lname`, `email`, `password`, `date_of_birth`, `phone_number`, `address`, `city`, `state`, `postal_code`, `country`, `created_at`) VALUES
(1, 'Nexus', 'Capstone', 'Maker', 'nexusph@gmail.com', '$2y$10$cu/7k25Qj9SJkRJ15RYKkOrE96YF827kUt3H0yUhlKBFRow1aMVIW', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-22 15:16:48'),
(4, 'Janzen', 'Agarin', 'Dela Cruz', 'meniski2345@gmail.com', '$2y$10$yTGdvn7BCnCoH/eIJXIk1e8h51pw7E/Whozv/aGXelBVsYzQ.n7PC', NULL, '09123456789', 'test', 'test', 'test', NULL, 'test', '2025-01-18 05:50:28');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_role_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_role_id`, `user_id`, `role_id`, `assigned_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 0, '2025-01-19 02:25:10', '2025-01-19 02:25:10'),
(3, 4, 3, 0, '2025-01-19 11:13:22', '2025-01-19 11:13:22'),
(4, 5, 3, 0, '2025-01-21 12:11:36', '2025-01-21 12:11:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `module_permissions`
--
ALTER TABLE `module_permissions`
  ADD PRIMARY KEY (`module_permission_id`),
  ADD KEY `idx_module_permissions_role_id` (`role_id`),
  ADD KEY `idx_module_permissions_module_id` (`module_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`page_id`);

--
-- Indexes for table `page_permissions`
--
ALTER TABLE `page_permissions`
  ADD PRIMARY KEY (`page_permission_id`),
  ADD KEY `idx_page_permissions_role_id` (`role_id`),
  ADD KEY `idx_page_permissions_page_id` (`page_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`assignment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_role_id`),
  ADD KEY `idx_user_roles_user_id` (`user_id`),
  ADD KEY `idx_user_roles_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `module_permissions`
--
ALTER TABLE `module_permissions`
  MODIFY `module_permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `page_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `page_permissions`
--
ALTER TABLE `page_permissions`
  MODIFY `page_permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
