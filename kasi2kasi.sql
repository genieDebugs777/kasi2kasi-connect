-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 12, 2026 at 07:47 PM
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
-- Database: `kasi2kasi`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `created_at`) VALUES
(1, 4, '2026-04-29 11:29:32'),
(2, 7, '2026-04-29 18:55:24'),
(3, 8, '2026-04-29 23:22:19'),
(4, 5, '2026-05-01 15:43:55'),
(5, 15, '2026-05-02 17:45:31'),
(6, 14, '2026-05-02 20:08:56'),
(7, 9, '2026-06-01 11:49:22'),
(8, 13, '2026-06-01 12:27:18'),
(9, 17, '2026-06-05 04:04:25');

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item`
--

INSERT INTO `cart_item` (`cart_item_id`, `cart_id`, `product_id`, `quantity`) VALUES
(8, 6, 20, 1),
(10, 3, 25, 1),
(12, 7, 3, 1),
(13, 7, 25, 1),
(14, 7, 14, 1);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `name`, `slug`) VALUES
(1, 'Electronics', 'electronics'),
(2, 'Fashion', 'fashion'),
(3, 'Home', 'home'),
(4, 'Crafts', 'crafts'),
(5, 'Books', 'books'),
(6, 'Sports', 'sports');

-- --------------------------------------------------------

--
-- Table structure for table `conversation`
--

CREATE TABLE `conversation` (
  `conversation_id` int(11) NOT NULL,
  `user1_id` int(11) NOT NULL,
  `user2_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversation`
--

INSERT INTO `conversation` (`conversation_id`, `user1_id`, `user2_id`, `created_at`) VALUES
(1, 4, 8, '2026-04-29 23:23:37'),
(2, 8, 15, '2026-05-02 16:10:34'),
(3, 14, 15, '2026-05-02 17:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`message_id`, `conversation_id`, `sender_id`, `content`, `is_read`, `sent_at`) VALUES
(1, 1, 8, 'Test message', 1, '2026-04-29 23:24:00'),
(2, 1, 4, 'kudos amigo', 1, '2026-04-29 23:24:52'),
(3, 2, 8, 'yo', 1, '2026-05-02 16:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` enum('low_stock','out_of_stock','new_order','order_update','verification') DEFAULT 'order_update',
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notification_id`, `user_id`, `product_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 17, NULL, 'order_update', 'Order Status Updated', '✅ Your order #9 has been marked as PAID. The seller will prepare your items soon.', 0, '2026-06-05 05:40:24'),
(2, 17, NULL, 'order_update', 'Order Status Updated', '🚚 Your order #9 has been SHIPPED! Track your delivery for updates.', 0, '2026-06-05 05:40:36'),
(3, 17, NULL, 'order_update', 'Order Status Updated', '📦 Your order #9 has been DELIVERED. Thank you for shopping on Kasi2Kasi!', 0, '2026-06-05 05:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` varchar(500) DEFAULT NULL,
  `delivery_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `buyer_id`, `total_amount`, `status`, `delivery_address`, `delivery_phone`, `created_at`) VALUES
(1, 4, 150.00, 'pending', '1135 Francis Baard Street', '0712055435', '2026-04-29 11:40:12'),
(3, 8, 150.00, 'pending', '1035 Burnett Street', '0745642357', '2026-04-29 23:23:03'),
(4, 8, 730.00, 'pending', '1135 Francis Baard Street', '0712055435', '2026-05-02 16:12:46'),
(5, 14, 1250.00, 'pending', '1135 Francis Baard Street', '0712055435', '2026-05-02 20:09:10'),
(6, 4, 850.00, 'pending', '1035 Burnett Street', '0745642357', '2026-05-13 09:18:44'),
(7, 9, 1250.00, 'pending', '1135 Francis Baard Street', '0712055435', '2026-06-01 12:24:19'),
(8, 5, 3200.00, 'pending', '1135 Francis Baard Street', '0712055435', '2026-06-05 04:02:53'),
(9, 17, 2200.00, 'delivered', '1135 Francis Baard Street', '0784563478', '2026-06-05 05:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 1, 100.00),
(3, 3, 1, 1, 100.00),
(4, 4, 14, 1, 680.00),
(5, 5, 25, 1, 1200.00),
(6, 6, 1, 1, 100.00),
(7, 6, 20, 1, 700.00),
(8, 7, 25, 1, 1200.00),
(9, 8, 23, 1, 300.00),
(10, 8, 20, 1, 700.00),
(11, 8, 15, 1, 2200.00),
(12, 9, 24, 1, 950.00),
(13, 9, 25, 1, 1200.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` enum('eft','cash','mobile_money','card') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `method`, `status`, `amount`, `reference`, `paid_at`) VALUES
(1, 1, 'cash', 'pending', 150.00, 'K2K-1777462812-1', NULL),
(3, 3, 'cash', 'pending', 150.00, 'K2K-1777504983-3', NULL),
(4, 4, 'eft', 'pending', 730.00, 'K2K-1777738366-4', NULL),
(5, 5, 'eft', 'pending', 1250.00, 'K2K-1777752550-5', NULL),
(6, 6, 'eft', 'pending', 850.00, 'K2K-1778663924-6', NULL),
(7, 7, 'mobile_money', 'pending', 1250.00, 'K2K-1780316659-7', NULL),
(8, 8, 'eft', 'pending', 3200.00, 'K2K-1780632173-8', NULL),
(9, 9, 'eft', 'completed', 2200.00, 'K2K-1780637967-9', '2026-06-05 05:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','sold','removed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `seller_id`, `category_id`, `title`, `description`, `price`, `quantity`, `image_url`, `status`, `created_at`) VALUES
(1, 4, 1, 'Test Product', 'test', 100.00, 1, '', 'active', '2026-04-29 11:16:22'),
(2, 10, 1, 'Samsung Galaxy A14 128GB', 'Good condition smartphone, used for about 8 months. No cracks on screen, slight wear on back cover. Battery still lasts the whole day.\r\n\r\nIncludes charger and box.\r\n\r\nPickup: Soshanguve Block L or can meet around TUT main campus.\r\nColour: Black\r\nReason for selling: Upgraded to a newer phone.', 3199.99, 1, 'https://images.unsplash.com/photo-1662369892303-a63aad6266eb?q=80&w=1074&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 'active', '2026-05-02 14:21:24'),
(3, 12, 1, 'Sony Wireless Bluetooth Headphones', 'Excellent condition, barely used. Sound quality is very clear with strong bass. No scratches.\r\n\r\nComes with charging cable.\r\n\r\nPickup: Pretoria CBD or Sunnyside.\r\nColour: Matte Black\r\nPerfect for music, gaming, and studying.', 1450.00, 3, 'https://images.unsplash.com/photo-1604780032295-9f8186eede96?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8U29ueSUyMFdpcmVsZXNzJTIwQmx1ZXRvb3RoJTIwSGVhZHBob25lc3xlbnwwfHwwfHx8MA%3D%3D', 'active', '2026-05-02 14:51:20'),
(4, 15, 1, 'PlayStation 4 Slim + 2 Controllers', 'Fully working PS4 Slim. Comes with 2 controllers and power cables. No issues at all.\r\n\r\nIncludes FIFA 23.\r\n\r\nPickup only: Mamelodi East.\r\nReason for selling: Not using it anymore.\r\nCondition: Very good.', 3800.00, 1, 'https://images.unsplash.com/photo-1700154636736-cb5f4c3751b3?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mnx8UGxheVN0YXRpb24lMjA0JTIwU2xpbSUyMCUyQiUyMDIlMjBDb250cm9sbGVyc3xlbnwwfHwwfHx8MA%3D%3D', 'active', '2026-05-02 14:54:36'),
(5, 14, 1, 'HP Laptop Backpack', 'Good condition backpack, used for a few months. No tears or damage, all zips are working perfectly.\r\n\r\nFits up to 15.6” laptop comfortably, with extra compartments for charger, books, and accessories. Very lightweight and comfortable for daily use.\r\n\r\nColour: Black with grey accents  \r\nCondition: Very good  \r\nReason for selling: No longer needed  \r\n\r\nPickup: Pretoria CBD / Sunnyside area  \r\nCan meet at a safe public location.\r\n\r\nPerfect for students or office use.', 350.00, 4, 'https://images.unsplash.com/photo-1673505705666-3e11a36e5bf8?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mzh8fGxhcHRvcCUyMGJhZ3xlbnwwfHwwfHx8MA%3D%3D', 'active', '2026-05-02 15:00:20'),
(6, 9, 2, 'Nike Air Max Sneakers Size 9', 'Worn a few times but still in great condition. No damage, just minor signs of use.\r\n\r\nSize: UK 9\r\nColour: White/Red\r\n\r\nPickup: Pretoria West or town.\r\nVery comfortable and original.', 1500.00, 2, 'https://images.unsplash.com/photo-1711491559395-c82f70a68bfb?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8NXx8TmlrZSUyMEFpciUyME1heCUyMFNuZWFrZXJzfGVufDB8fDB8fHww', 'active', '2026-05-02 15:04:44'),
(7, 9, 2, 'Vintage Denim Jacket Size M', 'Stylish vintage jacket, slightly oversized fit. Good quality denim, no tears.\r\n\r\nSize: Medium\r\nColour: Light blue\r\n\r\nPickup: Hatfield area.\r\nPerfect for winter outfits.', 420.00, 4, 'https://images.unsplash.com/photo-1761647500466-3f9ecf46ad18?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mzh8fFZpbnRhZ2UlMjBEZW5pbSUyMEphY2tldHxlbnwwfHwwfHx8MA%3D%3D', 'active', '2026-05-02 15:09:30'),
(8, 14, 2, 'African Print Dress', 'Beautiful handmade African print dress. Worn once for an event.\r\n\r\nSize: Medium\r\nColour: Blue and gold patterns\r\n\r\nPickup: Ga-Rankuwa.\r\nVery unique and eye-catching.', 380.00, 6, 'https://images.unsplash.com/photo-1602185948056-7a517148a391?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MzJ8fEFmcmljYW4lMjBQcmludCUyMERyZXNzfGVufDB8fDB8fHww', 'active', '2026-05-02 15:16:02'),
(9, 11, 2, 'Handmade Beaded Necklace', 'Beautiful handmade beaded necklace crafted locally with traditional African patterns. Each piece is unique and made with care.\r\n\r\nLightweight and comfortable to wear for both casual and special occasions. Perfect as a gift or to complete your outfit.\r\n\r\nColour: Multi-colour (varies slightly per piece)  \r\nCondition: Brand new  \r\nQuantity: Multiple available  \r\n\r\nPickup: Ga-Rankuwa / TUT area  \r\nCan arrange meet-up in town.', 180.00, 10, 'https://images.unsplash.com/photo-1770845402911-b6cf32921d66?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Mzl8fEhhbmRtYWRlJTIwQmVhZGVkJTIwTmVja2xhY2V8ZW58MHx8MHx8fDA%3D', 'active', '2026-05-02 15:24:36'),
(10, 16, 3, 'Wooden Coffee Table', 'Solid wooden table in good condition. Very sturdy and clean.\r\n\r\nDimensions: Medium size\r\nColour: Dark brown\r\n\r\nPickup only: Soshanguve.\r\nSelling because of moving.', 1200.00, 3, 'https://images.pexels.com/photos/15032861/pexels-photo-15032861.jpeg?_gl=1*50x9lu*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3MzU5MTckbzE4JGcxJHQxNzc3NzM1OTU4JGoxOSRsMCRoMA..', 'active', '2026-05-02 15:33:23'),
(11, 9, 3, 'Cast Iron Potjie Pot', 'Perfect for outdoor cooking and family gatherings. Used a few times only.\r\n\r\nSize: No.3\r\nCondition: Excellent\r\n\r\nPickup: Mabopane.\r\nGreat for traditional cooking.', 550.00, 1, 'https://images.unsplash.com/photo-1682996055064-599bec77fc62?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8Nnx8Q2FzdCUyMElyb24lMjBQb3RqaWUlMjBQb3R8ZW58MHx8MHx8fDA%3D', 'active', '2026-05-02 15:36:15'),
(12, 10, 3, 'Modern Desk Lamp', 'Sleek modern desk lamp in very good condition. Provides bright, comfortable lighting that’s perfect for studying, working, or reading at night.\r\n\r\nCompact design doesn’t take up much space and fits well on any desk or bedside table. Easy to use with a stable base.\r\n\r\nColour: White / Black (depending on your listing)  \r\nCondition: Very good  \r\nReason for selling: Not using it anymore  \r\n\r\nPickup: Pretoria Central / Sunnyside  \r\nCan meet in a safe public place.\r\n\r\nGreat for students, home offices, or anyone needing good lighting.', 280.00, 2, 'https://images.unsplash.com/photo-1543512214-4f76e81f8bfc?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8TW9kZXJuJTIwRGVzayUyMExhbXB8ZW58MHx8MHx8fDA%3D', 'active', '2026-05-02 15:41:39'),
(13, 13, 5, 'Grade 12 CAPS Textbook Bundle', 'Includes Maths, Physical Sciences, and Life Sciences textbooks.\r\n\r\nCondition: Good, some highlights inside.\r\n\r\nPickup: Pretoria North.\r\nPerfect for matric learners.', 450.00, 7, 'https://images.unsplash.com/photo-1565022536102-f7645c84354a?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTV8fGhpZ2hzY2hvb2wlMjB0ZXh0Ym9vayUyMGJ1bmRsZXxlbnwwfHwwfHx8MA%3D%3D', 'active', '2026-05-02 15:46:02'),
(14, 15, 6, 'Element Skateboard', 'Good quality Element skateboard, still in solid condition. Deck is strong and wheels roll smoothly. Used but well taken care of.\r\n\r\nPerfect for beginners or casual skating. Can also be used for cruising around campus or the neighborhood.\r\n\r\nCondition: Good (normal signs of use)  \r\nColour: Multi-design Element graphic  \r\nReason for selling: Not using it anymore  \r\n\r\nPickup: Pretoria / TUT area  \r\nCan meet in a safe public place.\r\n\r\nGreat starter board or everyday ride.', 680.00, 5, 'https://images.pexels.com/photos/15244289/pexels-photo-15244289.jpeg?_gl=1*1a9bfd4*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3MzU5MTckbzE4JGcxJHQxNzc3NzM2OTMzJGozNiRsMCRoMA..', 'active', '2026-05-02 15:50:50'),
(15, 12, 6, 'Mountain Bike 21 Speed', 'Good working condition bike. Brakes and gears all functional.\r\n\r\nColour: Red/Black\r\n\r\nPickup: TUT or Soshanguve.\r\nIdeal for commuting or fitness.', 2200.00, 1, 'https://images.unsplash.com/photo-1600968398267-8adf835a8773?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D', 'active', '2026-05-02 17:58:30'),
(16, 11, 4, 'Handmade Clay Vase Set', 'Handmade Clay Vase Set\r\n\r\nSet of beautifully handcrafted clay vases made by a local artisan. Each piece is unique, with natural textures and slight variations that give it an authentic, handmade feel.\r\n\r\nPerfect for home décor, dried flowers, or as a statement piece in your living room, bedroom, or office space. Adds a warm, African-inspired aesthetic to any environment.\r\n\r\nCondition: Brand new  \r\nColour: Natural earth tones (brown / beige variations)  \r\nSet: Includes multiple vases  \r\n\r\nPickup: Ga-Rankuwa / Soshanguve  \r\nHandle with care – fragile item.\r\n\r\nGreat for anyone looking to support local craftsmanship and elevate their space with something unique.', 260.00, 5, 'https://images.pexels.com/photos/18449692/pexels-photo-18449692.jpeg?_gl=1*1ehhplk*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NDQ4OTQkbzE5JGcxJHQxNzc3NzQ0OTAzJGo1MSRsMCRoMA..', 'active', '2026-05-02 18:04:02'),
(17, 10, 1, 'Samsung 32” Smart TV', 'Samsung 32” Smart TV\r\n\r\nFully working smart TV in excellent condition. Supports YouTube, Netflix, and other streaming apps.\r\n\r\nClear HD display with good sound quality. Remote included.\r\n\r\nScreen size: 32 inches  \r\nCondition: Very good  \r\nReason for selling: Upgraded to a bigger TV  \r\n\r\nPickup: Mamelodi West  \r\nCan test before buying.', 4200.00, 1, 'https://images.pexels.com/photos/5202925/pexels-photo-5202925.jpeg?_gl=1*15160zz*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NDQ4OTQkbzE5JGcxJHQxNzc3NzQ1NTc4JGo1MCRsMCRoMA..', 'active', '2026-05-02 18:13:24'),
(18, 15, 1, 'Dell Inspiron Laptop (i5)', 'Dell Inspiron Laptop (Core i5)\r\n\r\nGood condition laptop, works perfectly. Suitable for school, work, and browsing.\r\n\r\nSpecs:\r\n- Intel Core i5\r\n- 8GB RAM\r\n- 500GB storage\r\n\r\nCondition: Good  \r\nIncludes charger  \r\n\r\nPickup: Pretoria CBD  \r\nReason for selling: Bought a new laptop.', 5500.00, 1, 'https://images.pexels.com/photos/811587/pexels-photo-811587.jpeg?_gl=1*1wd30hs*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NDQ4OTQkbzE5JGcxJHQxNzc3NzQ1NzYxJGo0MyRsMCRoMA..', 'active', '2026-05-02 18:16:32'),
(19, 12, 1, 'iPhone 11 64GB', 'iPhone 11 64GB\r\n\r\nPhone is in good condition, everything works perfectly. No cracks on screen.\r\n\r\nBattery health still strong. Minor scratches on body.\r\n\r\nColour: Black  \r\nStorage: 64GB  \r\nIncludes charger  \r\n\r\nPickup: Sunnyside / Hatfield  \r\nSerious buyers only.', 6000.00, 1, 'https://images.pexels.com/photos/30353222/pexels-photo-30353222.jpeg?_gl=1*klo0j0*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NDQ4OTQkbzE5JGcxJHQxNzc3NzQ1OTE5JGo1MyRsMCRoMA..', 'active', '2026-05-02 18:19:17'),
(20, 14, 2, 'Adidas Tracksuit', 'Adidas Tracksuit\r\n\r\nComfortable Adidas tracksuit, great for casual wear or gym.\r\n\r\nCondition: Very good  \r\nSize: Medium  \r\nColour: Black with white stripes  \r\n\r\nPickup: Soshanguve  \r\nReason for selling: Not fitting anymore.', 700.00, 2, 'https://images.pexels.com/photos/11000096/pexels-photo-11000096.jpeg?_gl=1*ms3sou*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NDQ4OTQkbzE5JGcxJHQxNzc3NzQ2MDg0JGo1OSRsMCRoMA..', 'active', '2026-05-02 18:22:05'),
(21, 16, 3, 'Office Chair', 'Office Chair\r\n\r\nComfortable office chair with adjustable height. Good for studying or working from home.\r\n\r\nCondition: Good  \r\nColour: Black  \r\n\r\nPickup only: Pretoria North  \r\nStill strong and stable.', 900.00, 1, 'https://images.unsplash.com/photo-1681418659069-eef28d44aeab?w=1000&auto=format&fit=crop&q=60&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxzZWFyY2h8MTZ8fE9mZmljZSUyMENoYWlyfGVufDB8fDB8fHww', 'active', '2026-05-02 19:37:32'),
(22, 17, 3, 'Microwave Oven', 'Microwave Oven\r\n\r\nFully working microwave in good condition. Heats food quickly and evenly.\r\n\r\nCondition: Good  \r\nColour: White  \r\n\r\nPickup: Mabopane  \r\nReason for selling: Moving out.', 650.00, 1, 'https://images.pexels.com/photos/4686822/pexels-photo-4686822.jpeg?_gl=1*i0rs6n*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NTA4MDAkbzIwJGcxJHQxNzc3NzUwODAxJGo1OSRsMCRoMA..', 'active', '2026-05-02 19:40:35'),
(23, 17, 6, 'Puma Gym Bag', 'Puma Gym Bag\r\n\r\nSpacious gym bag, perfect for gym, sports, or travel.\r\n\r\nCondition: Very good  \r\nColour: Black  \r\n\r\nPickup: TUT / Soshanguve  \r\nStrong material and clean.', 300.00, 2, 'https://images.pexels.com/photos/5384401/pexels-photo-5384401.jpeg?_gl=1*gwj9w8*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NTA4MDAkbzIwJGcxJHQxNzc3NzUxMDE3JGo1OSRsMCRoMA..', 'active', '2026-05-02 19:44:51'),
(24, 13, 1, 'JBL Portable Speaker', 'JBL Portable Bluetooth Speaker\r\n\r\nPowerful sound with deep bass. Works perfectly and battery lasts long.\r\n\r\nCondition: Excellent  \r\nIncludes charging cable  \r\n\r\nPickup: Pretoria Central  \r\nPerfect for outdoor use.', 950.00, 0, 'https://images.pexels.com/photos/34471192/pexels-photo-34471192.jpeg?_gl=1*1th7jnz*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NTA4MDAkbzIwJGcxJHQxNzc3NzUxMjIxJGo1OSRsMCRoMA..', 'active', '2026-05-02 19:47:54'),
(25, 13, 1, 'Smart Watch', 'Smart Watch\r\n\r\nTracks steps, heart rate, and notifications. Compatible with Android and iPhone.\r\n\r\nCondition: Excellent  \r\nColour: Black  \r\n\r\nPickup: Hatfield  \r\nGood for fitness and daily use.', 1200.00, 1, 'https://images.pexels.com/photos/18662969/pexels-photo-18662969.jpeg?_gl=1*1qg3vmk*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NTA4MDAkbzIwJGcxJHQxNzc3NzUxMzUwJGo1MiRsMCRoMA..', 'active', '2026-05-02 19:49:37'),
(26, 16, 3, 'Double Bed Frame', 'Double Bed Frame\r\n\r\nStrong wooden bed frame in good condition. No damage.\r\n\r\nSize: Double  \r\nColour: Brown  \r\n\r\nPickup only: Ga-Rankuwa  \r\nMattress not included.', 1800.00, 1, 'https://images.pexels.com/photos/17219683/pexels-photo-17219683.jpeg?_gl=1*1i98785*_ga*MTc4NTg0MjQ2My4xNzM5ODc2NDQz*_ga_8JE65Q40S6*czE3Nzc3NTA4MDAkbzIwJGcxJHQxNzc3NzUxNDU3JGo1NiRsMCRoMA..', 'active', '2026-05-02 19:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`review_id`, `user_id`, `product_id`, `rating`, `comment`, `created_at`) VALUES
(1, 4, 1, 5, 'test-review', '2026-04-29 13:43:32'),
(2, 13, 2, 5, 'Very clean phone, exactly as described. Battery lasts long. Happy with my purchase.', '2026-05-02 19:55:28'),
(3, 13, 3, 4, 'Good headphones, comfortable to wear for long time.', '2026-05-02 19:57:17');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`, `description`) VALUES
(1, 'Super Admin', 'Full system access'),
(2, 'Verification Officer', 'Handles verification requests'),
(3, 'Content Moderator', 'Handles reports and content moderation'),
(4, 'User', 'Standard buyer/seller');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 4,
  `status` enum('active','suspended','pending') DEFAULT 'active',
  `is_verified` tinyint(1) DEFAULT 0,
  `is_trusted` tinyint(1) DEFAULT 0,
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password_hash`, `phone`, `role_id`, `status`, `is_verified`, `is_trusted`, `avatar_url`, `created_at`) VALUES
(4, 'Test User', 'test@mail.com', '$2y$10$Fyb6ZiKKsdt4PRuo4eE8au0S5xD14ZrCiZqRN.E1CA/sX5YOeUudS', '0784205643', 4, 'active', 0, 0, NULL, '2026-04-29 11:12:31'),
(5, 'AdminTest', 'admin@test.mail', '$2y$10$y21Tic9SPMxHJW4z2dO87uqYnnIbR6ehjuf0L/Jz/IUPNWTNNtYHG', '0713714233', 1, 'active', 1, 1, NULL, '2026-04-29 14:07:48'),
(6, 'VerificationTest', 'verification@mail.com', '$2y$10$z1fLoy0rMGPB3nG3sLlT7.f0PGnn/AYPZZUPcNIYRIhNqdKeF7EA2', '0614205432', 2, 'active', 1, 1, NULL, '2026-04-29 17:20:19'),
(7, 'ModerationTest', 'moderation@mail.com', '$2y$10$qbSeT0cyA5mNMt6u8g0neuQ3frOmDcP2K4L7R9BM7ba.OfLawiJoC', '0606704532', 3, 'active', 1, 1, NULL, '2026-04-29 17:21:05'),
(8, 'TestBuyer', 'buyer@mail.com', '$2y$10$WDo2p8rfNEkcbqw0og3b3Ovn.7co9HshHEF1X6.dXPVpPhBDQ9bd6', '0834567329', 4, 'active', 0, 0, NULL, '2026-04-29 23:21:40'),
(9, 'Keletso Mokoena', 'keletso@kasi.co.za', '$2y$10$fN7YnQB7JYf5lVAimjepCuOrEoAx6XSpu8FGl6kPV6HLJBLUERgHC', '0823456789', 4, 'active', 1, 0, NULL, '2026-05-02 14:08:24'),
(10, 'Cindy Khumalo', 'cindy@kasi.co.za', '$2y$10$6pqu1WpQ3ekFTCLQFPEA1Ojyi5wT11aSfZESORkXVADQXUfq625nq', '0734567834', 4, 'active', 1, 0, NULL, '2026-05-02 14:09:18'),
(11, 'Elsie Pillay', 'elsie@kasi.co.za', '$2y$10$bRx5T8kP5.iMGdpshvdne.8Y5jnyTwHfUgksNeuTlbkX3I5KzpF/G', '0603456782', 4, 'active', 0, 0, NULL, '2026-05-02 14:10:27'),
(12, 'Boikanyo Ndlovu', 'boikanyo@kasi.co.za', '$2y$10$gNeB/AdtRy4grnsW/D.Yb.ZmxjeOZqKP85tHaBW73NdCxR.GOA5fa', '0825678956', 4, 'active', 0, 0, NULL, '2026-05-02 14:11:19'),
(13, 'Sihle Sithole', 'sihle@kasi.co.za', '$2y$10$1sotjdSL9/2p3PjWACXbMe0wVRHvniSDBroLcUwh4r7/TBTITEgje', '0782345672', 4, 'active', 1, 0, NULL, '2026-05-02 14:13:06'),
(14, 'Thando Maseko', 'thando@kasi.co.za', '$2y$10$JGt55FJzyrz9oL6NTRsgAe6xpL80HjtvxnHMUCIg8yjYfy51M.jv2', '0663567834', 4, 'active', 1, 0, NULL, '2026-05-02 14:14:00'),
(15, 'Rele Tshabalala', 'rele@kasi.co.za', '$2y$10$WrWBlSk9dt/Mz8U4/082TuFzOFycn9lw31BhEHl.ZnYh1HiXlvjHm', '0723569898', 4, 'active', 0, 0, NULL, '2026-05-02 14:15:24'),
(16, 'Thabo Mlambo', 'thabo@kasi.co.za', '$2y$10$DZyTDrEaHoXud0YDLyFzzuNzecf0gyn8.8b.z0KpuzkhYEk8jZsJu', '0673426759', 4, 'active', 0, 0, NULL, '2026-05-02 15:28:43'),
(17, 'Khumo Thukoane', 'khumo@kasi.co.za', '$2y$10$vFAw01Mno6kpQF0ds8Qkl.fctRteqOpFnOi/8NbGO59QL4uJMSfFG', '0784563478', 4, 'active', 0, 0, NULL, '2026-05-02 15:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `verification_request`
--

CREATE TABLE `verification_request` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `id_document_url` varchar(500) DEFAULT NULL,
  `selfie_url` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_request`
--

INSERT INTO `verification_request` (`request_id`, `user_id`, `id_number`, `id_document_url`, `selfie_url`, `status`, `reviewed_by`, `reviewed_at`, `notes`, `created_at`) VALUES
(1, 4, '0102094507890', '', '', 'rejected', 5, '2026-04-29 21:39:24', 'Upload ID and photo', '2026-04-29 21:38:10'),
(2, 14, '7812235676789', '', '', 'approved', 5, '2026-05-02 20:07:33', 'Seller verification approved.', '2026-05-02 20:06:51'),
(3, 9, '7812235676789', '', '', 'approved', 5, '2026-06-05 04:01:25', 'Seller verification approved.', '2026-06-04 20:17:57'),
(4, 13, '7812235676789', '', '', 'approved', 5, '2026-06-05 04:01:30', 'Seller verification approved.', '2026-06-04 20:18:26'),
(5, 10, '7812235676789', '', '', 'approved', 5, '2026-06-05 10:16:02', 'Seller verification approved.', '2026-06-04 20:19:11'),
(6, 11, '7812235676789', '', '', 'pending', NULL, NULL, NULL, '2026-06-04 20:19:51'),
(7, 12, '7812235676789', '', '', 'pending', NULL, NULL, NULL, '2026-06-04 20:20:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uniq_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `conversation`
--
ALTER TABLE `conversation`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `uniq_pair` (`user1_id`,`user2_id`),
  ADD KEY `user2_id` (`user2_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `idx_conv` (`conversation_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_buyer` (`buyer_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uniq_user_product_review` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `verification_request`
--
ALTER TABLE `verification_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `conversation`
--
ALTER TABLE `conversation`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `review`
--
ALTER TABLE `review`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `verification_request`
--
ALTER TABLE `verification_request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation`
--
ALTER TABLE `conversation`
  ADD CONSTRAINT `conversation_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversation` (`conversation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`);

--
-- Constraints for table `verification_request`
--
ALTER TABLE `verification_request`
  ADD CONSTRAINT `verification_request_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `verification_request_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
