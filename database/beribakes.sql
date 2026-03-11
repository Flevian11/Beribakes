-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2026 at 04:38 PM
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
-- Database: `beribakes_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `created_at`) VALUES
(1, 1, 'User logged in', '2026-02-09 15:19:28'),
(2, 1, 'Created new product: Classic Chocolate Cake', '2026-02-11 15:19:28'),
(3, 1, 'Updated product prices', '2026-02-14 15:19:28'),
(4, 1, 'Processed order #1', '2026-02-15 15:19:28'),
(5, 1, 'Added new customer: John Kamau', '2026-02-16 15:19:28'),
(6, 1, 'Generated sales report', '2026-02-17 15:19:28'),
(7, 1, 'Updated inventory levels', '2026-02-19 15:19:28'),
(8, 1, 'Processed order #5', '2026-02-21 15:19:28'),
(9, 1, 'Added ledger transaction for sales', '2026-02-22 15:19:28'),
(10, 1, 'User logged in', '2026-02-24 15:19:28'),
(11, 1, 'Updated order #8 status to completed', '2026-02-25 15:19:28'),
(12, 1, 'Added new product: Red Velvet Cake', '2026-02-27 15:19:28'),
(13, 1, 'Processed refund for order #25', '2026-03-01 15:19:28'),
(14, 1, 'User logged in', '2026-03-03 15:19:28'),
(15, 1, 'Generated product performance report', '2026-03-04 15:19:28'),
(16, 1, 'Updated stock for bread products', '2026-03-05 15:19:28'),
(17, 1, 'Added new customer: Cynthia Moraa', '2026-03-06 15:19:28'),
(18, 1, 'Processed order #16', '2026-03-07 15:19:28'),
(19, 1, 'User logged in', '2026-03-08 15:19:28'),
(20, 1, 'Generated daily sales report', '2026-03-09 15:19:28'),
(21, 1, 'Updated system settings', '2026-03-10 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`) VALUES
(1, 'Cakes'),
(2, 'Bread'),
(3, 'Pastries'),
(4, 'Desserts'),
(5, 'Snacks'),
(6, 'Cookies'),
(7, 'Muffins'),
(8, 'Special Occasion');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `created_at`, `password`) VALUES
(1, 'John Kamau', '0712345678', 'john.kamau@gmail.com', '2025-09-12 15:19:28', '$2y$10$YourHashedPasswordHere'),
(2, 'Mary Wanjiku', '0723456789', 'mary.wanjiku@yahoo.com', '2025-09-27 15:19:28', '$2y$10$YourHashedPasswordHere'),
(3, 'Peter Odhiambo', '0734567890', 'peter.odhiambo@gmail.com', '2025-10-12 15:19:28', '$2y$10$YourHashedPasswordHere'),
(4, 'Sarah Akinyi', '0745678901', 'sarah.akinyi@outlook.com', '2025-10-27 15:19:28', '$2y$10$YourHashedPasswordHere'),
(5, 'David Mwangi', '0756789012', 'david.mwangi@gmail.com', '2025-11-11 15:19:28', '$2y$10$YourHashedPasswordHere'),
(6, 'Grace Achieng', '0767890123', 'grace.achieng@yahoo.com', '2025-11-26 15:19:28', '$2y$10$YourHashedPasswordHere'),
(7, 'James Omondi', '0778901234', 'james.omondi@gmail.com', '2025-12-11 15:19:28', '$2y$10$YourHashedPasswordHere'),
(8, 'Lucy Njeri', '0789012345', 'lucy.njeri@outlook.com', '2025-12-26 15:19:28', '$2y$10$YourHashedPasswordHere'),
(9, 'Robert Kiprono', '0790123456', 'robert.kiprono@gmail.com', '2026-01-10 15:19:28', '$2y$10$YourHashedPasswordHere'),
(10, 'Elizabeth Muthoni', '0701234567', 'elizabeth.muthoni@yahoo.com', '2026-01-25 15:19:28', '$2y$10$YourHashedPasswordHere'),
(11, 'Berita Wangui', '0795323141', 'bwabgui@gmail.com', '2026-02-09 15:19:28', '$2y$10$jHQF2ZPVwfVF8QIC427kXOxE0EwDryjjiLvY3jZwR1o/vCv.qsB3C'),
(12, 'Cynthia Moraa', '0722334455', 'cynthia.moraa@gmail.com', '2026-02-19 15:19:28', NULL),
(13, 'Brian Otieno', '0733445566', 'brian.otieno@yahoo.com', '2026-02-24 15:19:28', NULL),
(14, 'Janet Wairimu', '0744556677', 'janet.wairimu@gmail.com', '2026-03-01 15:19:28', NULL),
(15, 'Samuel Kariuki', '0755667788', 'samuel.kariuki@outlook.com', '2026-03-06 15:19:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ledger_accounts`
--

CREATE TABLE `ledger_accounts` (
  `id` int(11) NOT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_type` enum('asset','liability','income','expense') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ledger_accounts`
--

INSERT INTO `ledger_accounts` (`id`, `account_name`, `account_type`, `created_at`) VALUES
(1, 'Cash on Hand', 'asset', '2025-03-11 15:19:28'),
(2, 'M-Pesa Business Account', 'asset', '2025-03-11 15:19:28'),
(3, 'Bank Account - Equity', 'asset', '2025-03-11 15:19:28'),
(4, 'Sales Revenue', 'income', '2025-03-11 15:19:28'),
(5, 'Cost of Goods Sold', 'expense', '2025-03-11 15:19:28'),
(6, 'Rent Expense', 'expense', '2025-03-11 15:19:28'),
(7, 'Utilities Expense', 'expense', '2025-03-11 15:19:28'),
(8, 'Salaries Expense', 'expense', '2025-03-11 15:19:28'),
(9, 'Equipment', 'asset', '2025-03-11 15:19:28'),
(10, 'Accounts Payable', 'liability', '2025-03-11 15:19:28'),
(11, 'Loan Payable', 'liability', '2025-03-11 15:19:28'),
(12, 'Inventory Asset', 'asset', '2025-03-11 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `ledger_transactions`
--

CREATE TABLE `ledger_transactions` (
  `id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `transaction_type` enum('debit','credit') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ledger_transactions`
--

INSERT INTO `ledger_transactions` (`id`, `account_id`, `transaction_type`, `amount`, `reference_type`, `reference_id`, `description`, `created_at`) VALUES
(1, 1, 'debit', 1250.00, 'order', 3, 'Cash payment for Order #3', '2026-02-17 15:19:28'),
(2, 4, 'credit', 1250.00, 'order', 3, 'Sales revenue - Order #3', '2026-02-17 15:19:28'),
(3, 1, 'debit', 980.00, 'order', 5, 'Cash payment for Order #5', '2026-02-20 15:19:28'),
(4, 4, 'credit', 980.00, 'order', 5, 'Sales revenue - Order #5', '2026-02-20 15:19:28'),
(5, 1, 'debit', 1430.00, 'order', 9, 'Cash payment for Order #9', '2026-02-24 15:19:28'),
(6, 4, 'credit', 1430.00, 'order', 9, 'Sales revenue - Order #9', '2026-02-24 15:19:28'),
(7, 1, 'debit', 1950.00, 'order', 11, 'Cash payment for Order #11', '2026-02-26 15:19:28'),
(8, 4, 'credit', 1950.00, 'order', 11, 'Sales revenue - Order #11', '2026-02-26 15:19:28'),
(9, 1, 'debit', 1350.00, 'order', 15, 'Cash payment for Order #15', '2026-03-02 15:19:28'),
(10, 4, 'credit', 1350.00, 'order', 15, 'Sales revenue - Order #15', '2026-03-02 15:19:28'),
(11, 2, 'debit', 1850.00, 'order', 1, 'M-Pesa payment for Order #1', '2026-02-14 15:19:28'),
(12, 4, 'credit', 1850.00, 'order', 1, 'Sales revenue - Order #1', '2026-02-14 15:19:28'),
(13, 2, 'debit', 3200.00, 'order', 4, 'M-Pesa payment for Order #4', '2026-02-19 15:19:28'),
(14, 4, 'credit', 3200.00, 'order', 4, 'Sales revenue - Order #4', '2026-02-19 15:19:28'),
(15, 2, 'debit', 1670.00, 'order', 7, 'M-Pesa payment for Order #7', '2026-02-22 15:19:28'),
(16, 4, 'credit', 1670.00, 'order', 7, 'Sales revenue - Order #7', '2026-02-22 15:19:28'),
(17, 2, 'debit', 2890.00, 'order', 8, 'M-Pesa payment for Order #8', '2026-02-23 15:19:28'),
(18, 4, 'credit', 2890.00, 'order', 8, 'Sales revenue - Order #8', '2026-02-23 15:19:28'),
(19, 2, 'debit', 2240.00, 'order', 12, 'M-Pesa payment for Order #12', '2026-02-27 15:19:28'),
(20, 4, 'credit', 2240.00, 'order', 12, 'Sales revenue - Order #12', '2026-02-27 15:19:28'),
(21, 2, 'debit', 1680.00, 'order', 13, 'M-Pesa payment for Order #13', '2026-02-28 15:19:28'),
(22, 4, 'credit', 1680.00, 'order', 13, 'Sales revenue - Order #13', '2026-02-28 15:19:28'),
(23, 3, 'debit', 2340.00, 'order', 2, 'Card payment for Order #2', '2026-02-16 15:19:28'),
(24, 4, 'credit', 2340.00, 'order', 2, 'Sales revenue - Order #2', '2026-02-16 15:19:28'),
(25, 3, 'debit', 2150.00, 'order', 6, 'Card payment for Order #6', '2026-02-21 15:19:28'),
(26, 4, 'credit', 2150.00, 'order', 6, 'Sales revenue - Order #6', '2026-02-21 15:19:28'),
(27, 3, 'debit', 3720.00, 'order', 10, 'Card payment for Order #10', '2026-02-25 15:19:28'),
(28, 4, 'credit', 3720.00, 'order', 10, 'Sales revenue - Order #10', '2026-02-25 15:19:28'),
(29, 3, 'debit', 2920.00, 'order', 14, 'Card payment for Order #14', '2026-03-01 15:19:28'),
(30, 4, 'credit', 2920.00, 'order', 14, 'Sales revenue - Order #14', '2026-03-01 15:19:28'),
(31, 6, 'debit', 45000.00, 'expense', NULL, 'Monthly rent - March 2026', '2026-02-24 15:19:28'),
(32, 1, 'credit', 45000.00, 'expense', NULL, 'Rent payment', '2026-02-24 15:19:28'),
(33, 7, 'debit', 8750.00, 'expense', NULL, 'Electricity bill - March 2026', '2026-03-01 15:19:28'),
(34, 1, 'credit', 8750.00, 'expense', NULL, 'Electricity payment', '2026-03-01 15:19:28'),
(35, 8, 'debit', 120000.00, 'expense', NULL, 'Staff salaries - March 2026', '2026-03-06 15:19:28'),
(36, 3, 'credit', 120000.00, 'expense', NULL, 'Salary payments', '2026-03-06 15:19:28'),
(37, 5, 'debit', 35000.00, 'purchase', NULL, 'Flour, sugar, and ingredients purchase', '2026-02-19 15:19:28'),
(38, 2, 'credit', 35000.00, 'purchase', NULL, 'Supplier payment - Unga Ltd', '2026-02-19 15:19:28'),
(39, 5, 'debit', 18500.00, 'purchase', NULL, 'Dairy products - Milk, butter, cream', '2026-02-27 15:19:28'),
(40, 1, 'credit', 18500.00, 'purchase', NULL, 'Supplier payment - Brookside Dairy', '2026-02-27 15:19:28'),
(41, 9, 'debit', 75000.00, 'purchase', NULL, 'New industrial oven', '2026-01-25 15:19:28'),
(42, 11, 'credit', 75000.00, 'purchase', NULL, 'Equipment loan', '2026-01-25 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `payment_method` enum('cash','mpesa','card') DEFAULT NULL,
  `order_status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `delivery_address`, `payment_method`, `order_status`, `order_date`) VALUES
(1, 1, 1850.00, '123 Kenyatta Ave, Nairobi', 'mpesa', 'completed', '2026-02-14 15:19:28'),
(2, 2, 2340.00, '45 Moi Ave, Mombasa', 'card', 'completed', '2026-02-16 15:19:28'),
(3, 3, 1250.00, '78 Oginga Odinga St, Kisumu', 'cash', 'completed', '2026-02-17 15:19:28'),
(4, 4, 3200.00, '12 Kimathi St, Nakuru', 'mpesa', 'completed', '2026-02-19 15:19:28'),
(5, 5, 980.00, '56 Kencom House, Nairobi', 'cash', 'completed', '2026-02-20 15:19:28'),
(6, 6, 2150.00, '89 Sarit Centre, Nairobi', 'card', 'completed', '2026-02-21 15:19:28'),
(7, 7, 1670.00, '34 Westlands, Nairobi', 'mpesa', 'completed', '2026-02-22 15:19:28'),
(8, 8, 2890.00, '67 Thika Road Mall, Thika', 'mpesa', 'completed', '2026-02-23 15:19:28'),
(9, 9, 1430.00, '23 Two Rivers Mall, Nairobi', 'cash', 'completed', '2026-02-24 15:19:28'),
(10, 10, 3720.00, '90 Garden City, Nairobi', 'card', 'completed', '2026-02-25 15:19:28'),
(11, 11, 1950.00, '45 Eastleigh, Nairobi', 'cash', 'completed', '2026-02-26 15:19:28'),
(12, 12, 2240.00, '67 Buruburu, Nairobi', 'mpesa', 'completed', '2026-02-27 15:19:28'),
(13, 1, 1680.00, '123 Kenyatta Ave, Nairobi', 'mpesa', 'completed', '2026-02-28 15:19:28'),
(14, 3, 2920.00, '78 Oginga Odinga St, Kisumu', 'card', 'completed', '2026-03-01 15:19:28'),
(15, 5, 1350.00, '56 Kencom House, Nairobi', 'cash', 'completed', '2026-03-02 15:19:28'),
(16, 2, 2130.00, '45 Moi Ave, Mombasa', 'mpesa', 'processing', '2026-03-08 15:19:28'),
(17, 4, 1840.00, '12 Kimathi St, Nakuru', 'cash', 'processing', '2026-03-09 15:19:28'),
(18, 6, 2560.00, '89 Sarit Centre, Nairobi', 'card', 'processing', '2026-03-10 15:19:28'),
(19, 8, 1420.00, '67 Thika Road Mall, Thika', 'mpesa', 'processing', '2026-03-10 15:19:28'),
(20, 7, 3210.00, '34 Westlands, Nairobi', 'mpesa', 'pending', '2026-03-11 03:19:28'),
(21, 9, 1780.00, '23 Two Rivers Mall, Nairobi', 'cash', 'pending', '2026-03-11 07:19:28'),
(22, 10, 2950.00, '90 Garden City, Nairobi', 'card', 'pending', '2026-03-11 10:19:28'),
(23, 11, 1430.00, '45 Eastleigh, Nairobi', 'mpesa', 'pending', '2026-03-11 12:19:28'),
(24, 13, 2190.00, '67 Buruburu, Nairobi', 'cash', 'pending', '2026-03-11 13:19:28'),
(25, 12, 1890.00, '45 Moi Ave, Mombasa', 'mpesa', 'cancelled', '2026-03-04 15:19:28'),
(26, 14, 2760.00, '12 Kimathi St, Nakuru', 'card', 'cancelled', '2026-03-05 15:19:28'),
(27, 15, 1580.00, '56 Kencom House, Nairobi', 'cash', 'cancelled', '2026-03-06 15:19:28'),
(28, 1, 3420.00, '123 Kenyatta Ave, Nairobi', 'mpesa', 'cancelled', '2026-03-07 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 1200.00),
(2, 1, 13, 2, 220.00),
(3, 1, 19, 3, 120.00),
(4, 2, 7, 2, 450.00),
(5, 2, 14, 3, 250.00),
(6, 2, 20, 4, 150.00),
(7, 3, 8, 3, 180.00),
(8, 3, 25, 2, 180.00),
(9, 3, 13, 1, 220.00),
(10, 4, 3, 1, 1400.00),
(11, 4, 2, 1, 1350.00),
(12, 4, 21, 1, 600.00),
(13, 5, 9, 1, 300.00),
(14, 5, 26, 2, 190.00),
(15, 5, 15, 1, 280.00),
(16, 6, 4, 1, 1500.00),
(17, 6, 16, 2, 200.00),
(18, 6, 22, 1, 350.00),
(19, 7, 5, 1, 1100.00),
(20, 7, 10, 2, 220.00),
(21, 7, 17, 1, 230.00),
(22, 8, 2, 1, 1350.00),
(23, 8, 11, 2, 380.00),
(24, 8, 23, 1, 380.00),
(25, 9, 6, 1, 1300.00),
(26, 9, 12, 1, 320.00),
(27, 9, 18, 2, 240.00),
(28, 10, 1, 2, 1200.00),
(29, 10, 7, 1, 450.00),
(30, 10, 24, 1, 320.00),
(31, 11, 8, 2, 180.00),
(32, 11, 9, 2, 300.00),
(33, 11, 25, 3, 180.00),
(34, 12, 3, 1, 1400.00),
(35, 12, 14, 2, 250.00),
(36, 12, 20, 3, 150.00),
(37, 13, 4, 1, 1500.00),
(38, 13, 15, 1, 280.00),
(39, 13, 19, 2, 120.00),
(40, 14, 2, 1, 1350.00),
(41, 14, 13, 3, 220.00),
(42, 14, 21, 1, 600.00),
(43, 15, 5, 1, 1100.00),
(44, 15, 16, 1, 200.00),
(45, 16, 6, 1, 1300.00),
(46, 16, 11, 2, 380.00),
(47, 17, 7, 2, 450.00),
(48, 17, 22, 2, 350.00),
(49, 18, 8, 3, 180.00),
(50, 18, 9, 2, 300.00),
(51, 18, 10, 1, 220.00),
(52, 19, 1, 1, 1200.00),
(53, 19, 17, 1, 230.00),
(54, 20, 3, 1, 1400.00),
(55, 20, 14, 3, 250.00),
(56, 20, 18, 2, 240.00),
(57, 21, 2, 1, 1350.00),
(58, 21, 23, 1, 380.00),
(59, 22, 5, 1, 1100.00),
(60, 22, 7, 2, 450.00),
(61, 22, 13, 3, 220.00),
(62, 23, 4, 1, 1500.00),
(63, 23, 15, 1, 280.00),
(64, 24, 8, 3, 180.00),
(65, 24, 9, 2, 300.00),
(66, 24, 25, 3, 180.00),
(67, 25, 6, 1, 1300.00),
(68, 25, 19, 4, 120.00),
(69, 26, 1, 1, 1200.00),
(70, 26, 2, 1, 1350.00),
(71, 27, 10, 3, 220.00),
(72, 27, 11, 2, 380.00),
(73, 28, 3, 1, 1400.00),
(74, 28, 4, 1, 1500.00),
(75, 28, 16, 2, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','mpesa','card') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `payment_status`, `paid_at`) VALUES
(1, 1, 'mpesa', 1850.00, 'paid', '2026-02-14 15:19:28'),
(2, 2, 'card', 2340.00, 'paid', '2026-02-16 15:19:28'),
(3, 3, 'cash', 1250.00, 'paid', '2026-02-17 15:19:28'),
(4, 4, 'mpesa', 3200.00, 'paid', '2026-02-19 15:19:28'),
(5, 5, 'cash', 980.00, 'paid', '2026-02-20 15:19:28'),
(6, 6, 'card', 2150.00, 'paid', '2026-02-21 15:19:28'),
(7, 7, 'mpesa', 1670.00, 'paid', '2026-02-22 15:19:28'),
(8, 8, 'mpesa', 2890.00, 'paid', '2026-02-23 15:19:28'),
(9, 9, 'cash', 1430.00, 'paid', '2026-02-24 15:19:28'),
(10, 10, 'card', 3720.00, 'paid', '2026-02-25 15:19:28'),
(11, 11, 'cash', 1950.00, 'paid', '2026-02-26 15:19:28'),
(12, 12, 'mpesa', 2240.00, 'paid', '2026-02-27 15:19:28'),
(13, 13, 'mpesa', 1680.00, 'paid', '2026-02-28 15:19:28'),
(14, 14, 'card', 2920.00, 'paid', '2026-03-01 15:19:28'),
(15, 15, 'cash', 1350.00, 'paid', '2026-03-02 15:19:28'),
(16, 16, 'mpesa', 2130.00, 'pending', '2026-03-11 15:19:28'),
(17, 17, 'cash', 1840.00, 'pending', '2026-03-11 15:19:28'),
(18, 18, 'card', 2560.00, 'pending', '2026-03-11 15:19:28'),
(19, 19, 'mpesa', 1420.00, 'pending', '2026-03-11 15:19:28'),
(20, 20, 'mpesa', 3210.00, 'pending', '2026-03-11 15:19:28'),
(21, 21, 'cash', 1780.00, 'pending', '2026-03-11 15:19:28'),
(22, 22, 'card', 2950.00, 'pending', '2026-03-11 15:19:28'),
(23, 23, 'mpesa', 1430.00, 'pending', '2026-03-11 15:19:28'),
(24, 24, 'cash', 2190.00, 'pending', '2026-03-11 15:19:28'),
(25, 25, 'mpesa', 1890.00, 'paid', '2026-03-04 15:19:28'),
(26, 26, 'card', 2760.00, 'pending', '2026-03-11 15:19:28'),
(27, 27, 'cash', 1580.00, 'paid', '2026-03-06 15:19:28'),
(28, 28, 'mpesa', 3420.00, 'pending', '2026-03-11 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category_id`, `price`, `stock`, `description`, `image`, `created_at`) VALUES
(1, 'Classic Chocolate Cake', 1, 1200.00, 15, 'Rich, moist chocolate cake layered with decadent chocolate ganache. Perfect for celebrations!', 'chocolate-cake.jpg', '2026-01-10 15:19:28'),
(2, 'Strawberry Delight Cake', 1, 1350.00, 8, 'Fresh strawberry cake with whipped cream frosting and real strawberry pieces.', 'strawberry-cake.jpg', '2026-01-15 15:19:28'),
(3, 'Red Velvet Cake', 1, 1400.00, 10, 'Classic red velvet with cream cheese frosting - a customer favorite!', '69affa122dc71_1773140498.jpg', '2026-01-20 15:19:28'),
(4, 'Black Forest Cake', 1, 1500.00, 7, 'Decadent chocolate cake with cherries and whipped cream layers.', '69affadbea11b_1773140699.jpg', '2026-01-25 15:19:28'),
(5, 'Carrot Cake with Walnuts', 1, 1100.00, 12, 'Moist carrot cake with crushed walnuts and cream cheese frosting.', '69affaf257bb7_1773140722.jpg', '2026-01-30 15:19:28'),
(6, 'Birthday Funfetti Cake', 1, 1300.00, 9, 'Colorful sprinkles in vanilla cake with buttercream frosting.', '69affb2f5e90f_1773140783.jpg', '2026-02-04 15:19:28'),
(7, 'Artisan Sourdough', 2, 450.00, 25, 'Traditional sourdough with perfect crust and airy crumb. Made with 20-year-old starter.', 'american-heritage-chocolate-vdx5hPQhXFk-unsplash.jpg', '2026-01-12 15:19:28'),
(8, 'Fresh White Bread', 2, 180.00, 50, 'Classic white loaf, baked fresh daily. Perfect for sandwiches.', 'fresh-bread.jpg', '2026-01-18 15:19:28'),
(9, 'Banana Bread', 2, 300.00, 18, 'Moist banana bread made with ripe bananas and a hint of cinnamon.', 'banana-bread.jpg', '2026-01-22 15:19:28'),
(10, 'Whole Wheat Bread', 2, 220.00, 30, 'Healthy whole wheat bread with seeds and grains.', 'deva-williamson-ntfGWVbBiO0-unsplash.jpg', '2026-01-28 15:19:28'),
(11, 'Brioche Loaf', 2, 380.00, 15, 'Rich, buttery French bread - perfect for French toast!', 'deva-williamson-S2jw81lfrG0-unsplash.jpg', '2026-02-01 15:19:28'),
(12, 'Rye Bread', 2, 320.00, 12, 'Traditional rye bread with caraway seeds.', 'elena-koycheva-PFzy4N0_R3M-unsplash.jpg', '2026-02-06 15:19:28'),
(13, 'Butter Croissant', 3, 220.00, 40, 'Flaky, buttery croissant with 27 layers of perfection.', 'croissant.jpg', '2026-01-13 15:19:28'),
(14, 'Chocolate Croissant', 3, 250.00, 35, 'Buttery croissant filled with rich dark chocolate.', 'jacob-thomas-6jHpcBPw7i8-unsplash.jpg', '2026-01-17 15:19:28'),
(15, 'Almond Croissant', 3, 280.00, 20, 'Croissant filled with almond cream and topped with sliced almonds.', 'katie-rosario-QNyRp21hb5I-unsplash.jpg', '2026-01-23 15:19:28'),
(16, 'Apple Turnover', 3, 200.00, 25, 'Puff pastry filled with spiced apple compote.', 'luisana-zerpa-MJPr6nOdppw-unsplash.jpg', '2026-01-27 15:19:28'),
(17, 'Danish Pastry', 3, 230.00, 22, 'Flaky pastry with cream cheese and fruit topping.', 'mae-mu-kID9sxbJ3BQ-unsplash.jpg', '2026-02-02 15:19:28'),
(18, 'Pain au Chocolat', 3, 240.00, 30, 'French chocolate bread - a classic!', 'meritt-thomas-Ao09kk2ovB0-unsplash.jpg', '2026-02-07 15:19:28'),
(19, 'Chocolate Chip Cookie', 4, 120.00, 100, 'Classic cookie with generous chocolate chips.', 'donut.jpg', '2026-01-14 15:19:28'),
(20, 'Double Chocolate Cookie', 4, 150.00, 80, 'Rich chocolate cookie with white chocolate chunks.', 'muffin.jpg', '2026-01-19 15:19:28'),
(21, 'Macarons (Box of 6)', 4, 600.00, 15, 'Assorted French macarons - vanilla, chocolate, strawberry.', NULL, '2026-01-24 15:19:28'),
(22, 'Cheesecake Slice', 4, 350.00, 20, 'New York style cheesecake with berry compote.', NULL, '2026-01-29 15:19:28'),
(23, 'Tiramisu', 4, 380.00, 12, 'Classic Italian dessert with coffee and mascarpone.', NULL, '2026-02-03 15:19:28'),
(24, 'Fruit Tart', 4, 320.00, 10, 'Buttery tart shell with pastry cream and fresh fruits.', NULL, '2026-02-08 15:19:28'),
(25, 'Blueberry Muffin', 7, 180.00, 30, 'Soft muffin bursting with fresh blueberries.', 'muffin.jpg', '2026-01-16 15:19:28'),
(26, 'Chocolate Chip Muffin', 7, 190.00, 28, 'Moist muffin loaded with chocolate chips.', 'cupcake.jpg', '2026-01-21 15:19:28'),
(27, 'Banana Nut Muffin', 7, 200.00, 22, 'Banana muffin with crunchy walnuts.', NULL, '2026-01-26 15:19:28'),
(28, 'Pumpkin Spice Muffin', 7, 210.00, 15, 'Seasonal favorite with warm spices.', NULL, '2026-01-31 15:19:28'),
(29, 'Lemon Poppy Seed', 7, 190.00, 18, 'Zesty lemon muffin with poppy seeds.', NULL, '2026-02-05 15:19:28');

-- --------------------------------------------------------

--
-- Stand-in structure for view `report_product_sales`
-- (See below for the actual view)
--
CREATE TABLE `report_product_sales` (
`product_name` varchar(150)
,`total_sold` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `report_sales`
-- (See below for the actual view)
--
CREATE TABLE `report_sales` (
`sales_date` date
,`total_orders` bigint(21)
,`total_sales` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `sales_summary` (
`order_id` int(11)
,`customer` varchar(100)
,`total_amount` decimal(10,2)
,`order_status` enum('pending','processing','completed','cancelled')
,`order_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `movement_type` enum('in','out') DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `movement_type`, `quantity`, `reference`, `created_at`) VALUES
(1, 1, 'in', 50, 'Initial stock', '2025-12-11 15:19:28'),
(2, 2, 'in', 40, 'Initial stock', '2025-12-11 15:19:28'),
(3, 3, 'in', 35, 'Initial stock', '2025-12-11 15:19:28'),
(4, 4, 'in', 30, 'Initial stock', '2025-12-11 15:19:28'),
(5, 5, 'in', 40, 'Initial stock', '2025-12-11 15:19:28'),
(6, 6, 'in', 35, 'Initial stock', '2025-12-11 15:19:28'),
(7, 7, 'in', 60, 'Initial stock', '2025-12-11 15:19:28'),
(8, 8, 'in', 100, 'Initial stock', '2025-12-11 15:19:28'),
(9, 9, 'in', 45, 'Initial stock', '2025-12-11 15:19:28'),
(10, 10, 'in', 55, 'Initial stock', '2025-12-11 15:19:28'),
(11, 11, 'in', 40, 'Initial stock', '2025-12-11 15:19:28'),
(12, 12, 'in', 35, 'Initial stock', '2025-12-11 15:19:28'),
(13, 13, 'in', 80, 'Initial stock', '2025-12-11 15:19:28'),
(14, 14, 'in', 70, 'Initial stock', '2025-12-11 15:19:28'),
(15, 15, 'in', 45, 'Initial stock', '2025-12-11 15:19:28'),
(16, 16, 'in', 50, 'Initial stock', '2025-12-11 15:19:28'),
(17, 17, 'in', 45, 'Initial stock', '2025-12-11 15:19:28'),
(18, 18, 'in', 55, 'Initial stock', '2025-12-11 15:19:28'),
(19, 19, 'in', 200, 'Initial stock', '2025-12-11 15:19:28'),
(20, 20, 'in', 150, 'Initial stock', '2025-12-11 15:19:28'),
(21, 21, 'in', 30, 'Initial stock', '2025-12-11 15:19:28'),
(22, 22, 'in', 35, 'Initial stock', '2025-12-11 15:19:28'),
(23, 23, 'in', 25, 'Initial stock', '2025-12-11 15:19:28'),
(24, 24, 'in', 20, 'Initial stock', '2025-12-11 15:19:28'),
(25, 25, 'in', 60, 'Initial stock', '2025-12-11 15:19:28'),
(26, 26, 'in', 55, 'Initial stock', '2025-12-11 15:19:28'),
(27, 27, 'in', 40, 'Initial stock', '2025-12-11 15:19:28'),
(28, 28, 'in', 30, 'Initial stock', '2025-12-11 15:19:28'),
(29, 29, 'in', 35, 'Initial stock', '2025-12-11 15:19:28'),
(30, 1, 'out', 5, 'Order #1, #10, #18', '2026-02-14 15:19:28'),
(31, 2, 'out', 3, 'Order #4, #8, #14', '2026-02-19 15:19:28'),
(32, 3, 'out', 4, 'Order #4, #12, #20', '2026-02-21 15:19:28'),
(33, 4, 'out', 3, 'Order #6, #13, #23', '2026-02-24 15:19:28'),
(34, 5, 'out', 3, 'Order #7, #15, #21', '2026-02-27 15:19:28'),
(35, 6, 'out', 3, 'Order #9, #16, #25', '2026-03-01 15:19:28'),
(36, 7, 'out', 6, 'Order #2, #10, #17, #22', '2026-02-17 15:19:28'),
(37, 8, 'out', 11, 'Various orders', '2026-02-19 15:19:28'),
(38, 9, 'out', 6, 'Order #5, #11, #18', '2026-02-24 15:19:28'),
(39, 10, 'out', 6, 'Order #7, #18, #27', '2026-02-27 15:19:28'),
(40, 11, 'out', 5, 'Order #8, #16, #27', '2026-03-01 15:19:28'),
(41, 12, 'out', 2, 'Order #9, #18', '2026-03-03 15:19:28'),
(42, 13, 'out', 9, 'Various orders', '2026-02-19 15:19:28'),
(43, 14, 'out', 8, 'Order #2, #12, #20', '2026-02-21 15:19:28'),
(44, 15, 'out', 4, 'Order #5, #13, #23', '2026-02-24 15:19:28'),
(45, 16, 'out', 4, 'Order #6, #15, #28', '2026-02-27 15:19:28'),
(46, 17, 'out', 3, 'Order #7, #19', '2026-03-01 15:19:28'),
(47, 18, 'out', 5, 'Order #9, #20, #27', '2026-03-03 15:19:28'),
(48, 19, 'out', 12, 'Various orders', '2026-02-24 15:19:28'),
(49, 20, 'out', 11, 'Order #2, #12, #16', '2026-02-27 15:19:28'),
(50, 21, 'out', 3, 'Order #4, #14', '2026-03-01 15:19:28'),
(51, 22, 'out', 4, 'Order #6, #17', '2026-03-03 15:19:28'),
(52, 23, 'out', 2, 'Order #8, #21', '2026-03-06 15:19:28'),
(53, 24, 'out', 2, 'Order #10, #22', '2026-03-08 15:19:28'),
(54, 25, 'out', 8, 'Order #3, #11, #24', '2026-02-27 15:19:28'),
(55, 26, 'out', 5, 'Order #5, #18', '2026-03-01 15:19:28'),
(56, 27, 'out', 3, 'Order #11, #24', '2026-03-03 15:19:28'),
(57, 28, 'out', 2, 'Order #18', '2026-03-06 15:19:28'),
(58, 29, 'out', 2, 'Order #24', '2026-03-08 15:19:28'),
(59, 1, 'in', 20, 'Restock - Weekly delivery', '2026-03-01 15:19:28'),
(60, 2, 'in', 15, 'Restock - Weekly delivery', '2026-03-01 15:19:28'),
(61, 7, 'in', 30, 'Restock - Bakery supplies', '2026-03-04 15:19:28'),
(62, 8, 'in', 50, 'Restock - Daily bread', '2026-03-06 15:19:28'),
(63, 13, 'in', 40, 'Restock - Pastries', '2026-03-05 15:19:28'),
(64, 19, 'in', 100, 'Restock - Cookies', '2026-03-07 15:19:28'),
(65, 25, 'in', 30, 'Restock - Muffins', '2026-03-08 15:19:28');

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--

CREATE TABLE `system_info` (
  `id` int(11) NOT NULL,
  `system_name` varchar(100) DEFAULT NULL,
  `system_tagline` varchar(255) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_info`
--

INSERT INTO `system_info` (`id`, `system_name`, `system_tagline`, `currency`, `contact_email`, `contact_phone`, `address`, `created_at`) VALUES
(1, 'BeriBakes Bakery', 'Fresh Bread, Cakes & Pastries', 'KES', 'info@beribakes.com', '+254790 958657', 'Eldoret, Kenya', '2026-03-09 17:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@beribakes.com', '$2y$10$eG2nRmTjL/tnA9fiUbt9kO55ijpQCahqlN53jRSJKWXpLtj0X9grK', 'admin', '2026-03-09 17:10:21');

-- --------------------------------------------------------

--
-- Structure for view `report_product_sales`
--
DROP TABLE IF EXISTS `report_product_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `report_product_sales`  AS SELECT `p`.`product_name` AS `product_name`, sum(`oi`.`quantity`) AS `total_sold` FROM (`order_items` `oi` join `products` `p` on(`oi`.`product_id` = `p`.`id`)) GROUP BY `oi`.`product_id` ORDER BY sum(`oi`.`quantity`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `report_sales`
--
DROP TABLE IF EXISTS `report_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `report_sales`  AS SELECT cast(`orders`.`order_date` as date) AS `sales_date`, count(`orders`.`id`) AS `total_orders`, sum(`orders`.`total_amount`) AS `total_sales` FROM `orders` WHERE `orders`.`order_status` = 'completed' GROUP BY cast(`orders`.`order_date` as date) ;

-- --------------------------------------------------------

--
-- Structure for view `sales_summary`
--
DROP TABLE IF EXISTS `sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `sales_summary`  AS SELECT `o`.`id` AS `order_id`, `c`.`name` AS `customer`, `o`.`total_amount` AS `total_amount`, `o`.`order_status` AS `order_status`, `o`.`order_date` AS `order_date` FROM (`orders` `o` left join `customers` `c` on(`o`.`customer_id` = `c`.`id`)) ;

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ledger_accounts`
--
ALTER TABLE `ledger_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ledger_transactions`
--
ALTER TABLE `ledger_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ledger_accounts`
--
ALTER TABLE `ledger_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ledger_transactions`
--
ALTER TABLE `ledger_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT for table `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ledger_transactions`
--
ALTER TABLE `ledger_transactions`
  ADD CONSTRAINT `ledger_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `ledger_accounts` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
