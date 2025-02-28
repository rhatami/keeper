-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 28, 2025 at 05:15 PM
-- Server version: 11.4.5-MariaDB
-- PHP Version: 8.3.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `keeper`
--


--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`) VALUES
(1, 'demo', '$2y$10$y22W1AFMBycg.S/7.aDUSO4xtgU1/ZK91vgd8X5a7wOIiVQ9RW2Ba');

-- --------------------------------------------------------

--
-- Table structure for table `asset`
--

CREATE TABLE `asset` (
  `id` int(11) NOT NULL,
  `symbol` varchar(25) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(10) NOT NULL,
  `color` varchar(10) NOT NULL DEFAULT '#868686'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `asset`
--

INSERT INTO `asset` (`id`, `symbol`, `name`, `unit`, `color`) VALUES
(1, 'Gold18', 'طلا (گرم 18)', 'گرم', '#fed000'),
(2, 'Silver', 'نقره', 'گرم', '#657c87'),
(3, 'USD', 'دلار', 'دلار', '#4caf50'),
(4, 'USDT', 'تتر', 'تتر', '#26a69a'),
(5, 'BTC', 'بیت کوین', 'بیت کوین', '#eab002'),
(6, 'ETH', 'اتریوم', 'اتر', '#2a3192'),
(7, 'Coin', 'سکه', 'عدد', '#fed000'),
(8, 'HCoin', 'نیم سکه', 'عدد', '#fed000'),
(9, 'QCoin', 'ربع سکه', 'عدد', '#fed000'),
(10, 'Gold24', 'طلا (گرم 24)', 'گرم', '#fed000'),
(11, 'EUR', 'یورو', 'یورو', '#0d63ab'),
(12, 'Stock', 'سهام', 'سهم', '#3877ff');

-- --------------------------------------------------------

--
-- Table structure for table `balance`
--

CREATE TABLE `balance` (
  `user` int(11) NOT NULL,
  `asset` int(11) NOT NULL,
  `balance` float NOT NULL,
  `average` double NOT NULL DEFAULT 0 COMMENT 'Average Buy Price Per Each Unit'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buy`
--

CREATE TABLE `buy` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `asset` int(11) NOT NULL,
  `quantity` float NOT NULL,
  `buy_date` date NOT NULL,
  `total_price` bigint(20) NOT NULL COMMENT 'in Toman',
  `note` varchar(100) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL COMMENT 'Type of that Symbol if applies'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sell`
--

CREATE TABLE `sell` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `asset` int(11) NOT NULL,
  `quantity` float NOT NULL,
  `sell_date` date NOT NULL,
  `total_price` bigint(20) NOT NULL,
  `note` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `type` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'Type of that Symbol if applies'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
--
-- Indexes for dumped tables
--

--
-- Indexes for table `asset`
--
ALTER TABLE `asset`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_symbol_unique` (`symbol`);

--
-- Indexes for table `balance`
--
ALTER TABLE `balance`
  ADD UNIQUE KEY `idx_user_asset` (`user`,`asset`),
  ADD KEY `asset` (`asset`);

--
-- Indexes for table `buy`
--
ALTER TABLE `buy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `asset` (`asset`);

--
-- Indexes for table `sell`
--
ALTER TABLE `sell`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset` (`asset`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_unique` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `asset`
--
ALTER TABLE `asset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `buy`
--
ALTER TABLE `buy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `sell`
--
ALTER TABLE `sell`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `balance`
--
ALTER TABLE `balance`
  ADD CONSTRAINT `balance_ibfk_1` FOREIGN KEY (`asset`) REFERENCES `asset` (`id`),
  ADD CONSTRAINT `balance_ibfk_2` FOREIGN KEY (`user`) REFERENCES `user` (`id`);

--
-- Constraints for table `buy`
--
ALTER TABLE `buy`
  ADD CONSTRAINT `buy_ibfk_1` FOREIGN KEY (`user`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `buy_ibfk_2` FOREIGN KEY (`asset`) REFERENCES `asset` (`id`);

--
-- Constraints for table `sell`
--
ALTER TABLE `sell`
  ADD CONSTRAINT `sell_ibfk_1` FOREIGN KEY (`asset`) REFERENCES `asset` (`id`),
  ADD CONSTRAINT `sell_ibfk_2` FOREIGN KEY (`user`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
