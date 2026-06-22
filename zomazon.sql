-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 09:26 AM
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
-- Database: `zomazon`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'Sandeep Das', 'Sandeep1810');

-- --------------------------------------------------------

--
-- Table structure for table `local_shops`
--

CREATE TABLE `local_shops` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(100) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `seller_email` varchar(100) DEFAULT NULL,
  `PASSWORD` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `local_shops`
--

INSERT INTO `local_shops` (`id`, `shop_name`, `owner_name`, `phone`, `address`, `area`, `image`, `city`, `seller_email`, `PASSWORD`, `category`) VALUES
(21, 'Asish Brother\'s Medical Store', 'Asish Solanki', '7894561230', 'Dibrugarh', 'Lahoal', 'Screenshot_22-6-2026_0727_www.bing.com.jpeg', 'Dibrugarh', 'dassandeep479@gmail.com', '$2y$10$./KdyfXgxLUO3ApASTcYz.Mm9dNbKqJK32RNSwGZShV3vefUmXnAC', 'Medical');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT NULL,
  `platform_fee` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shop_id` varchar(155) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `products` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `full_name`, `email`, `phone`, `address`, `subtotal`, `delivery_fee`, `platform_fee`, `total`, `payment_method`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `delivery_date`, `status`, `created_at`, `shop_id`, `product_id`, `products`, `price`) VALUES
(47, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 50.00, 40.00, NULL, 99.00, 'Cash on Delivery', 'pending', '', '', '', '2026-06-16', 'pending', '2026-06-13 15:03:17', NULL, 3, '[{\"product_id\":3,\"name\":\"banana\",\"price\":50,\"qty\":1}]', 99.00),
(48, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 80.00, 40.00, NULL, 129.00, 'Cash on Delivery', 'pending', '', '', '', '2026-06-16', 'pending', '2026-06-13 16:04:11', NULL, 4, '[{\"product_id\":4,\"name\":\"orange\",\"price\":80,\"qty\":1}]', 129.00),
(49, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 10.00, 40.00, NULL, 59.00, 'Cash on Delivery', 'pending', '', '', '', '2026-06-20', 'Delivered', '2026-06-17 15:07:06', '12', 56, '[{\"product_id\":56,\"name\":\"Pudin Hara\",\"price\":10,\"qty\":1}]', 59.00),
(50, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 50.00, 40.00, NULL, 99.00, 'Online Payment', 'paid', 'order_T4Kv7lSCulFnmF', 'pay_T4Kvu6u2dJyoQF', '74ff3afa2c7cdeb131408fb2e8db612f7e70fa54c3d62fb47e27e15345b2b1f1', '2026-06-24', 'pending', '2026-06-21 15:50:32', NULL, 3, '[{\"product_id\":3,\"name\":\"banana\",\"price\":50,\"qty\":1}]', 99.00),
(51, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 80.00, 40.00, 9.00, 129.00, 'Online Payment', 'paid', 'order_T4LBhDRAg8Ryj7', 'pay_T4LCJGlGG81If0', 'd7ca78710b70596554b3f1118316ed0ff8055b783afa95d7d14d1bbc5a83d145', '2026-06-24', 'pending', '2026-06-21 16:05:47', NULL, 4, '[{\"product_id\":4,\"name\":\"orange\",\"price\":80,\"qty\":1}]', 129.00),
(52, 30, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855432', 'fghj', 50.00, 40.00, 9.00, 99.00, 'Cash on Delivery', 'pending', '', '', '', '2026-06-24', 'pending', '2026-06-21 16:44:28', NULL, 3, '[{\"product_id\":3,\"name\":\"banana\",\"price\":50,\"qty\":1}]', 99.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `shop_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `category`, `image`, `stock`, `shop_id`, `description`) VALUES
(1, 'Apple', 120, 'Fruits', 'apple.jpeg', 0, NULL, NULL),
(3, 'banana', 50, 'Fruits', 'Banana.png', 10, NULL, NULL),
(4, 'orange', 80, 'Fruits', 'Orange.jpg', 9, NULL, NULL),
(5, 'mango', 150, 'Fruits', 'Mango.jpg', 14, NULL, NULL),
(6, 'grapes', 90, 'Fruits', 'Grapes.jpg', 0, NULL, NULL),
(7, 'pineapple', 70, 'Fruits', 'Pineapple.jpeg', 0, NULL, NULL),
(8, 'kiwi', 700, 'Fruits', 'Kiwi.jpeg', 0, NULL, NULL),
(9, 'Lays Chips', 20, 'Snacks', 'Lays.jpeg', 0, NULL, NULL),
(10, 'Haldiram Bhujia', 50, 'Snacks', 'Bhujia.jpeg', 0, NULL, NULL),
(11, 'Good Day Biscuits', 30, 'Snacks', 'Goodday.jpg', 0, NULL, NULL),
(12, 'Hide & Seek Cookies', 35, 'Snacks', 'Hide and seek.jpeg', 0, NULL, NULL),
(13, 'Act II popcorn', 40, 'Snacks', 'Popcorn.jpeg', 0, NULL, NULL),
(14, 'Dairy Milk CHoclate', 45, 'Snacks', 'Dairy milk.png,lfo-bottom_right,w-200,h-90,c-at_least,cm-pad_resize,l-end', 0, NULL, NULL),
(15, 'Potato', 25, 'Vegetable', 'Potato.jpeg', 0, NULL, NULL),
(16, 'Tomato', 30, 'Vegetable', 'Tomato.jpg', 0, NULL, NULL),
(17, 'Onion', 35, 'Vegetable', 'onion.jpeg', 0, NULL, NULL),
(18, 'Carrot', 40, 'Vegetable', 'Carrot.jpg', 0, NULL, NULL),
(19, 'Cabbage', 28, 'Vegetable', 'cabbage.jpg', 6, NULL, NULL),
(20, 'Cauliflower', 35, 'Vegetable', 'cauliflower.jpg', 0, NULL, NULL),
(21, 'Amul Milk', 60, 'Dairy', 'amul_milk.png', 0, NULL, NULL),
(22, 'Fresh Curd', 40, 'Dairy', 'Curd.jpg', 0, NULL, NULL),
(23, 'Amul Butter', 55, 'Dairy', 'Amul butter.jpg', 0, NULL, NULL),
(24, 'Paneer', 90, 'Dairy', 'Paneer.jpg', 0, NULL, NULL),
(25, 'Cheese Slices', 120, 'Dairy', 'Amul cheese.jpg', 0, NULL, NULL),
(26, 'Pure Ghee', 120, 'Dairy', 'Ghee.jpeg', 0, NULL, NULL),
(27, 'Rasgulla', 100, 'Dairy', 'Rasgulla.jpeg', 0, NULL, NULL),
(28, 'Coca Cola', 40, 'Beverages', 'Coca Cola.jpeg', 0, NULL, NULL),
(29, 'Pepsi', 40, 'Beverages', 'Pepsi.jpeg', 0, NULL, NULL),
(30, 'Tropicana Orange Juice', 110, 'Beverages', 'Tropicana Orange.jpeg', 0, NULL, NULL),
(31, 'Tata Tea', 150, 'Beverages', 'Tata Tea.jpg', 0, NULL, NULL),
(32, 'Nescafe Coffee', 180, 'Beverages', 'Nescafe Coffee.jpeg', 0, NULL, NULL),
(33, 'Red Bull', 110, 'Beverages', 'Redbull.jpeg', 0, NULL, NULL),
(34, 'Tide Detergent', 60, 'Household', 'Tide.jpeg', 0, NULL, NULL),
(35, 'Vim Liquid', 900, 'Household', 'Vim.jpeg', 0, NULL, NULL),
(36, 'Floor Cleaner', 110, 'Household', 'Floor Cleaner.jpeg', 0, NULL, NULL),
(37, 'Paper Towel', 80, 'Household', 'Paper Towel.jpeg', 0, NULL, NULL),
(39, 'Garbage Bags', 30, 'Household', 'Garbagebag.jpeg', 0, NULL, NULL),
(40, 'Phenyl', 60, 'Household', 'Phenyl.jpeg', 0, NULL, NULL),
(41, 'Masoor Dal', 80, 'Household', 'Masoor Dal.jpeg', 0, NULL, NULL),
(42, 'Basmati Rice', 499, 'Household', 'Basmatirice.jpeg', 0, NULL, NULL),
(43, 'Sugar', 45, 'Household', 'Refinedsugar.jpeg', 0, NULL, NULL),
(44, 'Sunflower Oil', 129, 'Household', 'Sunfloweroil.jpeg', 0, NULL, NULL),
(45, 'Pan', 2, 'Guthka', 'Pan.jpeg', 0, NULL, NULL),
(46, 'Bettlenut(Tambul)', 8, 'Guthka', 'Bettlenut.jpeg', 0, NULL, NULL),
(47, 'Khaini', 20, 'Guthka', 'Khaini.jpeg', 4, NULL, NULL),
(48, 'Rajnigandha', 449, 'Guthka', 'Rajnigandha.jpeg', 10, NULL, NULL),
(49, 'Pan', 5, 'Guthka', 'Pan.jpeg', 11, NULL, NULL),
(50, 'Vimal Pan Masala(Bolo Zubaan Kesari)', 10, 'Guthka', 'Vimal.jpg', 10, NULL, NULL),
(58, 'Pudin Hara', 89, 'Medical', 'Screenshot_22-6-2026_0116_www.bing.com.jpeg', 70, 21, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stars` tinyint(4) NOT NULL CHECK (`stars` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_name` varchar(100) DEFAULT 'Anonymous'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shop_reviews`
--

CREATE TABLE `shop_reviews` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_reviews`
--

INSERT INTO `shop_reviews` (`id`, `shop_id`, `user_id`, `user_name`, `rating`, `comment`, `created_at`) VALUES
(1, 20, 30, 'Sandeep Das', 3, 'gfdsazxcv b', '2026-06-15 10:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `p_id` int(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `address` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` int(11) NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`p_id`, `name`, `email`, `phone`, `address`, `password`, `role`, `created_at`, `reset_token`, `reset_expires`) VALUES
(31, 'Sandeep Das', 'dassandeep479@gmail.com', '0967855433', 'Thowra Tea Estate\r\nthowra 4no. lain\r\np.o mohukutie', '$2y$10$Tb5vxWaXEn5v4pGsZZ/Dzu4FjpIVnSRPgHU.ZMy45xWzh6wfZ.Yc2', 'user', 2147483647, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `local_shops`
--
ALTER TABLE `local_shops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shop_reviews`
--
ALTER TABLE `shop_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`p_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `local_shops`
--
ALTER TABLE `local_shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shop_reviews`
--
ALTER TABLE `shop_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `p_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `local_shops` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
