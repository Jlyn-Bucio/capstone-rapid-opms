-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2025 at 11:59 AM
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
-- Database: `rapid_opms`
--

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `billing_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `invoice_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`id`, `customer_id`, `project_id`, `amount`, `billing_date`, `due_date`, `description`, `created_at`, `invoice_number`, `notes`) VALUES
(1, 1, 1, 21232.00, '0000-00-00', NULL, NULL, '2025-06-07 18:19:11', '', ''),
(2, 1, 1, 21232.00, '0000-00-00', NULL, NULL, '2025-06-07 18:20:34', '', ''),
(3, 2, 2, 12342.00, '0000-00-00', NULL, NULL, '2025-06-07 18:21:10', '', ''),
(4, 2, 2, 12342.00, '0000-00-00', NULL, NULL, '2025-06-07 18:23:05', '', ''),
(5, 2, 2, 12342.00, '0000-00-00', NULL, NULL, '2025-06-07 18:23:32', '', ''),
(6, 2, 2, 12342.00, '0000-00-00', NULL, NULL, '2025-06-07 18:25:20', '', ''),
(7, 2, 2, 12342.00, '0000-00-00', NULL, NULL, '2025-06-07 18:26:11', '', ''),
(8, 2, 2, 12342.00, '2025-12-23', NULL, NULL, '2025-06-07 18:33:28', '', ''),
(9, 2, 2, 12342.00, '2025-12-23', NULL, NULL, '2025-06-07 18:33:40', '', ''),
(10, 4, 11, 5110000.00, '2025-06-09', NULL, NULL, '2025-06-08 17:47:40', '', ''),
(11, 3, 11, 155000.00, '2025-06-09', NULL, NULL, '2025-06-09 03:28:09', 'INV-20250508-7354', ''),
(12, 4, 11, 100000.00, '2025-06-14', NULL, NULL, '2025-06-10 09:04:23', '', 'Big Building');

-- --------------------------------------------------------

--
-- Table structure for table `billing_files`
--

CREATE TABLE `billing_files` (
  `id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `company_name`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'ABC Corp', NULL, NULL, NULL, '2025-06-04 09:20:20'),
(2, 'Maria Clara', 'XYZ Ltd', NULL, NULL, NULL, '2025-06-04 09:20:20'),
(3, 'Jason', 'OPMS', 'jason@gmail.com', '09301476473', 'North Fairview', '2025-06-08 12:17:39'),
(4, 'Nick', 'OPMS', 'nick@gmail.com', '09977232799', 'North Fairview', '2025-06-08 17:43:58'),
(5, 'Juan Dela Cruz', NULL, NULL, NULL, NULL, '2025-06-08 16:00:00'),
(6, 'Dano', 'Club House', 'dano@gmail.com', '09977232799', 'Bulacan', '2025-06-10 09:08:35');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `in_stock` int(11) NOT NULL,
  `buying_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `contractor` varchar(255) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `project_manager` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `date`, `location`, `contractor`, `size`, `start_date`, `customer_id`, `project_manager`, `description`, `created_at`) VALUES
(1, 'ABCD', '2025-06-06', 'Caloocan City', 'Kang', '300', '2025-06-24', 2, 'Juan', '', '2025-06-06 12:52:00'),
(2, 'ABCD', '2025-06-07', 'Montalban', 'kim', '350', '2025-06-18', 1, 'kim', '', '2025-06-06 12:53:15'),
(3, 'House', '2025-06-08', 'Caloocan City', 'Tresh', '1500', '2025-06-17', 2, 'Paul', '', '2025-06-07 18:46:18'),
(4, 'House', '2025-06-08', 'Caloocan City', 'Tresh', '1500', '2025-06-17', 2, 'Paul', '', '2025-06-07 18:48:41'),
(5, 'House', '2025-06-08', 'Caloocan City', 'Tresh', '1500', '2025-06-17', 2, 'Paul', '', '2025-06-07 18:48:46'),
(6, 'House', '2025-06-08', 'Montalban', 'john', '300', '2025-06-11', 2, 'Paul', '', '2025-06-07 18:49:35'),
(7, 'House', '2025-06-08', 'Montalban', 'john', '300', '2025-06-11', 2, 'Paul', '', '2025-06-07 18:51:41'),
(8, 'Concrete', '2025-06-08', 'Fairview', 'May', '7500', '2025-06-17', 2, '', '', '2025-06-08 03:55:51'),
(9, 'Concrete', '2025-06-08', 'Fairview', 'May', '7500', '2025-06-17', 2, '', '', '2025-06-08 04:03:03'),
(10, 'House', '2025-06-08', 'Montalban', 'May', '7500', '2025-06-19', 3, 'Paul', '', '2025-06-08 12:21:29'),
(11, 'Building', '2025-06-09', 'Pasig', 'May', '1500', '2025-06-16', 4, 'john', '', '2025-06-08 17:45:57'),
(12, 'PlayGround', '2025-06-10', 'Bulacan', 'Tresh', '1500', '2025-06-19', 6, 'john', '', '2025-06-10 09:09:33'),
(13, 'test', '2025-06-30', 'Montalban', 'Tresh', '1500', '2025-08-01', 4, 'Juan', '', '2025-06-30 09:31:31');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'ABC Trading Co.', 'John Doe', 'john@abctrading.com', '09171234567', '123 ABC Street, Manila', '2025-06-08 11:25:21', '2025-06-08 11:25:21'),
(2, 'XYZ Industrial', 'Jane Smith', 'jane@xyzindustrial.com', '09229876543', '456 XYZ Avenue, Cebu', '2025-06-08 11:25:21', '2025-06-08 11:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `amount`, `transaction_date`, `description`) VALUES
(1, 1000.00, '2025-06-09 00:00:00', NULL),
(2, 500.00, '2025-06-08 00:00:00', NULL),
(3, 250.00, '2025-06-07 00:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `position`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$xwDOdvydbEqP7KSOOEbenOwNGGjkWFgO2mnIEvO76oYUbykc59ubm', 'Admin', '2025-06-03 13:26:10'),
(3, 'jen', 'jen@gmail.com', '$2y$10$s2B4Ev4Tgi4eOy18Xga0K.Vqhn1CZe96m/U7mZeelK/NT28JFDLry', 'Manager', '2025-06-03 13:31:14'),
(4, 'Accounting', 'accounting@gmail.com', '$2y$10$iK.HfOUmU3KJZ.WyT9RqM.d4w0g2WfoG5Rd.hLzXAvVAy5HWOPjZ.', 'Accountant', '2025-06-04 03:23:16'),
(6, 'yoli', 'yoli@gmail.com', '$2y$10$npuV6FbmJWz42yFmvxmDcuVrP7iLw/6DePzDi0Xa0MgatBZ90WBl6', 'Admin', '2025-06-30 09:30:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `billing_files`
--
ALTER TABLE `billing_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `billing_id` (`billing_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `billing_files`
--
ALTER TABLE `billing_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
