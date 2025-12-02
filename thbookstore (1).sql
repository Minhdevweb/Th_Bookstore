-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 06:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thbookstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `rating` float DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `author`, `price`, `rating`, `category`, `image`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', 12.99, 4.5, 'English Books', 'https://images.unsplash.com/photo-1512820790803-83ca734da794'),
(2, 'To Kill a Mockingbird', 'Harper Lee', 10.50, 4.8, 'English Books', 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f'),
(3, 'Nhà Giả Kim', 'Paulo Coelho', 8.20, 4.7, 'Vietnamese Books', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66'),
(4, 'Sapiens: Lược Sử Loài Người', 'Yuval Noah Harari', 18.99, 4.9, 'English Books', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c'),
(5, 'Dế Mèn Phiêu Lưu Ký', 'Tô Hoài', 6.90, 4.6, 'Vietnamese Books', 'https://images.unsplash.com/photo-1519681393784-d120267933ba'),
(6, 'Harry Potter', 'J.K. Rowling', 25.00, 4.9, 'English Books', 'https://images.unsplash.com/photo-1507842217343-583bb7270b66'),
(15, 'ngochuyen', 'tuanminh', 13.44, 3.4, 'Vietnamese Books', '1760429000_ngochuyen.jpg'),
(16, 'những con rắn độc', 'thùy linh', 12.11, 4.1, 'Vietnamese Books', '1760545686_z7098216143805_2e9776b31389ad715975171749f2b247.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'abc', '$2y$10$9MHT8/0VpzFx38r0HyghR.k2Ju2Vi.3Tx7Jfe7X61m7jFE0rDEoZ6'),
(2, 'min', '$2y$10$3lDvw8CAcB0.IHkMsdGUqebEwFt5qfusb6xBKHvK3WijHHVEsAXLy'),
(3, 'minhvt.24itb@vku.udn.vn', '$2y$10$TKlNvPOyWecjPf/JkyN0JO1Jjj1YC15yRWxvIL0//RyLnyFfwtmN2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
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
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
