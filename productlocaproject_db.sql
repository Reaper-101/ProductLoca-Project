-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Apr 09, 2025 at 07:26 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `productlocaproject_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `businessinfo`
--

CREATE TABLE `businessinfo` (
  `BusinessID` int(11) NOT NULL,
  `BusinessName` varchar(255) NOT NULL,
  `Address` text NOT NULL,
  `BusinessContactNum` varchar(20) DEFAULT NULL,
  `BusinessLicense` varchar(100) DEFAULT NULL,
  `BIRnumber` varchar(50) DEFAULT NULL,
  `BusinessEmail` varchar(255) DEFAULT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `Visibility` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businessinfo`
--

INSERT INTO `businessinfo` (`BusinessID`, `BusinessName`, `Address`, `BusinessContactNum`, `BusinessLicense`, `BIRnumber`, `BusinessEmail`, `status`, `Visibility`) VALUES
(1, 'Robinsons', 'Jaro, iloilo City', '00000', '12345', '6789', 'robjaro@gmail.com', 'active', 1),
(2, 'SM', 'Mandurriao, Iloilo city', '020304', '1010101', '101010', 'smcity@gmail.com', 'active', 1),
(3, 'Gaisano', 'Molo, iloilo City', '09087', '22334455', '2323', 'gaisano@gmail.com', 'active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kioskdevice`
--

CREATE TABLE `kioskdevice` (
  `KioskID` int(11) NOT NULL,
  `KioskStatus` enum('active','inactive','assigned') NOT NULL DEFAULT 'inactive',
  `KioskLoc` varchar(255) DEFAULT NULL,
  `KioskNum` varchar(50) DEFAULT NULL,
  `kioskCode` varchar(10) NOT NULL,
  `BusinessID` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `StoreID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kioskdevice`
--

INSERT INTO `kioskdevice` (`KioskID`, `KioskStatus`, `KioskLoc`, `KioskNum`, `kioskCode`, `BusinessID`, `status`, `StoreID`) VALUES
(2, 'assigned', 'Entrance', '1', 'K001', 1, 'active', 1),
(3, 'assigned', 'Entrance', '1', 'K002', 2, 'active', NULL),
(4, 'active', 'Exit', '2', 'K003', 1, 'active', 12),
(5, 'active', 'Third Floor', '3', 'K004', 1, 'active', 12),
(6, 'active', 'Unknown', '4', 'K005', 1, 'active', NULL),
(7, 'active', 'Unknown', '5', 'K006', 1, 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `location`
--

CREATE TABLE `location` (
  `LocID` int(11) NOT NULL,
  `LocName` varchar(255) DEFAULT NULL,
  `FloorLevel` varchar(50) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `KioskID` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `BusinessID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location`
--

INSERT INTO `location` (`LocID`, `LocName`, `FloorLevel`, `StoreID`, `KioskID`, `status`, `BusinessID`) VALUES
(2, 'Nike Store', 'Second Floor', NULL, NULL, 'active', NULL),
(45, 'S1', 'Level 1', 1, 2, 'active', 1),
(46, 'S6', 'Level 1', 11, 2, 'active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `locationimage`
--

CREATE TABLE `locationimage` (
  `ImageID` int(11) NOT NULL,
  `Filename` varchar(255) NOT NULL,
  `MapCode` varchar(20) DEFAULT NULL,
  `AlbumID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locationimage`
--

INSERT INTO `locationimage` (`ImageID`, `Filename`, `MapCode`, `AlbumID`) VALUES
(74, '1744012337_K1 - KIOSK TO STORE.png', 'S1-001', 72),
(76, '1744012538_K2 - KIOSK TO STORE.png', 'S1-002', 69),
(77, '1744012562_K3 - ELEVATOR TO SCORE.png', 'S1-003', 69),
(78, '1744012562_K3 - KIOSK TO ELEVATOR.png', 'S1-004', 69),
(79, '1744012589_K3 - ESCALATOR TO SCORE.png', 'S1-005', 69),
(80, '1744012589_K3 - KIOSK TO ESCALATOR.png', 'S1-006', 69),
(81, '1744012645_K1 - KIOSK TO STORE.png', 'S6-001', 73);

-- --------------------------------------------------------

--
-- Table structure for table `location_albums`
--

CREATE TABLE `location_albums` (
  `AlbumID` int(11) NOT NULL,
  `LocID` int(11) DEFAULT NULL,
  `AlbumName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_albums`
--

INSERT INTO `location_albums` (`AlbumID`, `LocID`, `AlbumName`, `Description`) VALUES
(69, NULL, 'S1', NULL),
(71, NULL, 'S6', NULL),
(72, 45, 'Kiosk 1 to store map', 'This map will bring you to Nike store(Store 1) from kiosk 1'),
(73, 46, 'Kiosk 1 to store map', 'This map will bring you to Gevinchy store(Store 6) from kiosk 1');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ID` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `AttemptTime` datetime NOT NULL,
  `IP` varchar(45) NOT NULL,
  `LockedUntil` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`ID`, `Username`, `AttemptTime`, `IP`, `LockedUntil`) VALUES
(12, 'hana1234', '2025-03-11 15:56:08', '::1', NULL),
(26, 'deptadmin', '2025-03-17 13:51:21', '::1', NULL),
(27, 'deptadmin', '2025-03-17 13:51:43', '::1', NULL),
(28, 'deptadmin', '2025-03-17 13:58:55', '::1', NULL),
(29, 'deptadmin', '2025-03-17 13:59:14', '::1', NULL),
(30, 'deptadmin', '2025-03-17 13:59:33', '::1', NULL),
(31, 'deptadmin', '2025-03-17 14:01:40', '::1', NULL),
(43, 'eli12345', '2025-03-31 12:15:03', '::1', NULL),
(44, 'eli1234', '2025-03-31 12:15:24', '::1', NULL),
(45, 'eli1234', '2025-03-31 12:15:37', '::1', NULL),
(46, 'eli12344', '2025-03-31 12:16:07', '::1', NULL),
(47, 'eli12344', '2025-03-31 12:16:19', '::1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProdID` int(11) NOT NULL,
  `Prod_name` varchar(255) DEFAULT NULL,
  `Prod_type` varchar(100) DEFAULT NULL,
  `Prod_color` varchar(50) DEFAULT NULL,
  `Prod_description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `Brand` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProdID`, `Prod_name`, `Prod_type`, `Prod_color`, `Prod_description`, `Price`, `Image`, `StoreID`, `status`, `Brand`) VALUES
(7, 'Nike', 'Round Neck T-shirt', 'White', 'Round neck white nike', '700.00', 'white.jpg', 1, 'active', 'Nike'),
(23, 'Dior', 'T shirt', 'Black', 'Dior round neck t-shirt ', '800.00', 'OIP (7).jpg', 10, 'active', 'Dior'),
(24, 'Dior', 'T shirt', 'Black', 'Round neck white Dior t-shirt', '800.00', 'OIP (6).jpg', 10, 'active', 'Dior'),
(25, 'Dry fit t-shirt', 'T shirt', 'Green', 'Green dry fit Dior t-shirt', '850.00', 'OIP (9).jpg', 10, 'active', 'Dior'),
(26, 'Dior cotton t-shirt', 'T shirt', 'Yellow', 'Yellow cotton t-shirt', '890.00', 'OIP (8).jpg', 10, 'active', 'Dior'),
(27, 'Fila', 'Sweater', 'Orange', 'Orange Fila sweater with smooth cotton texture', '1500.00', 'OIP (4).jpg', 17, 'active', 'Fila'),
(28, 'Fila', 'Sweater', 'White', 'Fila white cotton sweater', '1500.00', 'OIP (3).jpg', 17, 'active', 'Fila'),
(29, 'Fila', 'Crop top', 'White', 'Fila white crop top', '450.00', 'OIP (2).jpg', 17, 'active', 'Fila'),
(30, 'Fila', 'Crop top', 'Black', 'Black Fila crop top', '450.00', 'OIP (1).jpg', 17, 'active', 'Fila'),
(31, 'Fila t-shirt', 'T shirt', 'Yellow', 'Fila cotton yellow shirt', '650.00', 'OIP (5).jpg', 17, 'active', 'Fila'),
(32, 'Fila', 'T shirt', 'Green', 'Green Fila t shirt', '650.00', 'R.jpg', 17, 'active', 'Fila'),
(33, 'Fila Polo shirt', 'Polo Shirt', 'Green', 'Green polo shirt with cotton texture', '900.00', 'OIP (23).jpg', 17, 'active', 'Fila'),
(34, 'Gevinchy', 'T shirt', 'Green', 'Green Gevinchy cotton t shirt', '750.00', 'OIP (15).jpg', 11, 'active', 'Gevinchy'),
(35, 'Gevinchy ', 'T shirt', 'White', 'White Gevinchy round neck t shirt', '700.00', 'OIP (16).jpg', 11, 'active', 'Gevinchy'),
(36, 'Gevinchy', 'T shirt', 'Blue', 'Blue Gevinchy round neck t-shirt', '700.00', '1594726282-6203298116-1.jpg', 11, 'active', 'Gevinchy'),
(37, 'Nike', 'T shirt', 'Blue', 'Blue nike t-shirt', '730.00', 'OIP (20).jpg', 1, 'active', 'Nike'),
(38, 'Nike', 'Sweater', 'Dark Blue', 'Dark blue nike sweater', '1700.00', 'OIP (19).jpg', 1, 'active', 'Nike'),
(42, 'Adidas', 'T shirt', 'White', 'White cotton Adidas shirt', '600.00', 'download (3).jpg', 19, 'active', 'Adidas'),
(43, 'Adidas shirt', 'T shirt', 'Black', 'Cotton black adidas t-shirt', '650.00', 'download (4).jpg', 19, 'active', 'Adidas'),
(44, 'Adidas pants', 'Pants', 'Black', 'Black Adidas pants', '450.00', 'OIP (30).jpg', 19, 'active', 'Adidas'),
(45, 'Adidas', 'Pants', 'Red', 'Red adidas pants', '450.00', 'OIP (31).jpg', 19, 'active', 'Adidas'),
(46, 'Black Crop top', 'Crop top', 'Black', 'Adidas black crop top ', '400.00', 'adidas---Women_s-Adicolor-Crop-T-Shirt-_IC2379_-01_1024x.webp', 19, 'active', 'Adidas'),
(47, 'Adidas sweater', 'Sweater', 'Black', 'Black adidas sweater smooth texture', '1700.00', 'OIP (18).jpg', 19, 'active', 'Adidas'),
(48, 'Adidas short', 'Short', 'Black', 'Black adidas short', '300.00', 'OIP (33).jpg', 19, 'active', 'Adidas'),
(49, 'Adidas crop top', 'Crop top', 'White', 'White cotton adidas crop top', '250.00', 'OIP (29).jpg', 19, 'active', 'Adidas'),
(50, 'Fila short', 'Short', 'Black', 'Black Fila short', '250.00', 'fila_teens_boys_rene_swim_short_bla_3950615_1155.jpg', 17, 'active', 'Fila'),
(51, 'Fila', 'Short', 'Red', 'Fila red shorts', '300.00', 'OIP (11).jpg', 17, 'active', 'Fila'),
(52, 'White Addidas', 'T-shirt', 'White', 'White adidas t-shirt', '700.00', 'download (3).jpg', 20, 'active', 'Adidas'),
(53, 'Black Addidas', 'T-shirt', 'Black', 'Black adidas t-shirt', '650.00', 'download (4).jpg', 20, 'active', 'Adidas');

-- --------------------------------------------------------

--
-- Table structure for table `searchanalytics`
--

CREATE TABLE `searchanalytics` (
  `ID` int(11) NOT NULL,
  `SearchQuery` varchar(255) DEFAULT NULL,
  `SearchDate` date DEFAULT NULL,
  `SearchCount` int(11) DEFAULT 1,
  `SearchTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `searchanalytics`
--

INSERT INTO `searchanalytics` (`ID`, `SearchQuery`, `SearchDate`, `SearchCount`, `SearchTime`) VALUES
(1, 'round neck t-shirt', '2024-10-03', 1, '20:55:40'),
(2, 'white adidas', '2024-10-03', 7, '20:55:40'),
(3, 'adidas short', '2024-10-03', 1, '20:55:40'),
(4, 'adidas red short', '2024-10-03', 1, '20:55:40'),
(5, 'nike', '2024-10-03', 2, '20:55:40'),
(6, 'white gucci', '2024-10-03', 1, '20:55:40'),
(7, 'short', '2024-10-03', 4, '20:55:40'),
(8, '', '2024-10-03', 8, '20:55:40'),
(9, 'short', '2024-10-10', 31, '20:55:40'),
(10, 'nike ', '2024-10-10', 8, '20:55:40'),
(11, 'adidas', '2024-10-10', 3, '20:55:40'),
(12, 'white adidas', '2024-10-10', 10, '20:55:40'),
(13, 'white adidas t-shirt', '2024-10-10', 1, '20:55:40'),
(14, 't-shirt', '2024-10-10', 3, '20:55:40'),
(15, 'red tshirt', '2024-10-10', 1, '20:55:40'),
(16, 'tshirt', '2024-10-10', 1, '20:55:40'),
(17, 't shirt', '2024-10-10', 1, '20:55:40'),
(18, 'shirt', '2024-10-10', 1, '20:55:40'),
(19, 'whtite gucci', '2024-10-10', 1, '20:55:40'),
(20, 'white gucci', '2024-10-10', 4, '20:55:40'),
(21, 'white ', '2024-10-10', 7, '20:55:40'),
(22, 'adidas', '2024-10-14', 1, '20:55:40'),
(23, 'fashion', '2024-10-14', 1, '20:55:40'),
(24, 'red', '2024-10-14', 2, '20:55:40'),
(26, '', '2024-10-15', 10, '20:55:40'),
(27, 'nike', '2024-10-15', 2, '20:55:40'),
(28, 'nike red', '2024-10-15', 1, '20:55:40'),
(29, 'red', '2024-10-15', 1, '20:55:40'),
(30, 'white tshirt', '2024-10-15', 1, '20:55:40'),
(31, 'tshirt', '2024-10-15', 1, '20:55:40'),
(32, 't-shirt', '2024-10-15', 20, '20:55:40'),
(33, 'short', '2024-10-17', 7, '20:55:40'),
(34, 'nike ', '2024-10-17', 3, '20:55:40'),
(35, 't-shirt', '2024-10-17', 1, '20:55:40'),
(36, 'levi\'s', '2024-10-21', 1, '20:55:40'),
(37, 'pants', '2024-10-21', 5, '20:55:40'),
(38, ' red pants', '2024-10-21', 2, '20:55:40'),
(39, ' white adidas', '2024-10-21', 1, '20:55:40'),
(40, ' white', '2024-10-21', 1, '20:55:40'),
(41, ' white t-shirt', '2024-10-21', 1, '20:55:40'),
(42, 'red levi\'s', '2024-10-21', 1, '20:55:40'),
(43, 'red levi\\\'s', '2024-10-21', 1, '20:55:40'),
(44, 'red levi\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\'s', '2024-10-21', 1, '20:55:40'),
(45, 'red short', '2024-10-21', 1, '20:55:40'),
(46, 'red pants', '2024-10-21', 4, '20:55:40'),
(47, 'short', '2024-11-04', 13, '20:55:40'),
(48, 'nike ', '2024-11-04', 1, '20:55:40'),
(49, 'shirt', '2024-11-05', 1, '20:55:40'),
(50, 't-shirt', '2024-11-05', 2, '20:55:40'),
(51, 'white t-shirt', '2024-11-05', 7, '20:55:40'),
(52, 'nike white', '2024-11-05', 1, '20:55:40'),
(53, 'white', '2024-11-05', 1, '20:55:40'),
(54, 'pants', '2024-11-05', 2, '20:59:46'),
(55, 'gucci', '2025-03-05', 2, '08:59:02'),
(56, 'short', '2025-03-05', 1, '09:00:33'),
(57, 'Nike ', '2025-03-05', 1, '09:05:46'),
(58, 't-shirt', '2025-03-06', 10, '15:37:45'),
(59, 'Pants', '2025-03-06', 1, '10:04:05'),
(60, 'gucci', '2025-03-06', 1, '10:06:22'),
(61, '750', '2025-03-06', 1, '14:57:26'),
(62, '500', '2025-03-06', 1, '14:57:32'),
(63, '550', '2025-03-06', 1, '14:57:45'),
(64, 'short 550', '2025-03-06', 1, '14:57:54'),
(65, '750 t shirt', '2025-03-06', 1, '14:58:56'),
(66, 't-shirt', '2025-03-07', 1, '10:26:10'),
(67, 'Nike ', '2025-03-10', 1, '09:59:38'),
(68, 'white nike', '2025-03-10', 1, '09:59:46'),
(69, 't-shirt', '2025-03-10', 6, '21:05:39'),
(70, 'short', '2025-03-10', 3, '20:31:23'),
(71, 'short', '2025-03-11', 1, '08:47:18'),
(72, '3', '2025-03-11', 1, '08:52:03'),
(73, '1', '2025-03-11', 1, '08:52:12'),
(74, '750', '2025-03-11', 1, '08:52:20'),
(75, 't-shirt', '2025-03-17', 4, '13:54:23'),
(76, 'Nike ', '2025-03-19', 2, '14:50:52'),
(77, 't-shirt', '2025-03-19', 4, '15:44:57'),
(78, 'white t-shirt', '2025-03-19', 2, '15:31:23'),
(79, '750 t shirt', '2025-03-19', 2, '15:41:41'),
(80, '750 ', '2025-03-19', 2, '15:41:40'),
(81, 'nike 750', '2025-03-19', 1, '15:39:56'),
(82, 'tshirt', '2025-03-19', 1, '15:44:18'),
(83, 't-shirt 750', '2025-03-20', 12, '13:36:25'),
(84, '750', '2025-03-20', 6, '11:21:52'),
(85, 't-shirt ', '2025-03-20', 5, '14:31:00'),
(86, 't-shirt  white', '2025-03-20', 2, '10:48:14'),
(87, 't-shirt  white red', '2025-03-20', 1, '10:47:57'),
(88, '750 t shirt', '2025-03-20', 1, '11:21:56'),
(89, '500', '2025-03-20', 10, '13:36:26'),
(90, '300', '2025-03-20', 5, '13:36:16'),
(91, '300 t-shirt', '2025-03-20', 1, '11:22:44'),
(92, '550 t-shirt', '2025-03-20', 1, '11:22:53'),
(93, '750 t-shirt', '2025-03-20', 8, '13:36:26'),
(94, 't-shirt 500', '2025-03-20', 9, '13:36:25'),
(95, 't-shirt 550', '2025-03-20', 4, '13:36:24'),
(96, 'nike shirt', '2025-03-20', 2, '13:36:24'),
(97, 'nike t shirt', '2025-03-20', 4, '13:36:24'),
(98, 'nike ', '2025-03-20', 2, '13:36:23'),
(99, 'adiddas', '2025-03-20', 2, '13:36:23'),
(100, 'adidas', '2025-03-20', 2, '13:36:22'),
(101, 't-shirt 500.00', '2025-03-20', 2, '13:36:22'),
(102, 't-shirt 600', '2025-03-20', 4, '13:36:21'),
(103, 'white shirt', '2025-03-20', 2, '13:36:21'),
(104, 'white shirt 500', '2025-03-20', 4, '13:36:20'),
(105, 'white shirt 300', '2025-03-20', 4, '13:36:19'),
(106, 't shirt 300', '2025-03-20', 2, '13:36:18'),
(107, 'short 50', '2025-03-20', 1, '13:24:36'),
(108, 'short 500', '2025-03-20', 2, '13:36:15'),
(109, 'short 550', '2025-03-20', 2, '13:36:15'),
(110, 'short ', '2025-03-20', 8, '13:36:14'),
(111, 'levis', '2025-03-20', 2, '13:36:11'),
(112, 'levi\\\'s', '2025-03-20', 2, '13:36:10'),
(113, 'pants 500', '2025-03-20', 2, '13:35:52'),
(114, 'gucci t-shirt', '2025-03-20', 1, '14:31:17'),
(115, 'gucci t-shirt 700', '2025-03-20', 1, '14:31:25'),
(116, 'gucci t-shirt 500', '2025-03-20', 1, '14:31:34'),
(117, 'gucci 500', '2025-03-20', 25, '14:38:23'),
(118, 'pants', '2025-03-21', 9, '13:31:38'),
(119, 'nike', '2025-03-21', 3, '13:39:41'),
(120, 'adidas', '2025-03-21', 1, '13:40:16'),
(121, 'all', '2025-03-21', 1, '13:41:12'),
(122, 'ggdgfdfssf', '2025-03-21', 1, '13:43:03'),
(123, 'gdgdgd', '2025-03-21', 2, '13:45:16'),
(124, '500 t shirt', '2025-03-24', 1, '10:16:22'),
(125, 't-shirt', '2025-03-24', 13, '13:17:25'),
(126, 'white t-shirt', '2025-03-24', 14, '22:06:05'),
(127, 't shirt', '2025-03-24', 1, '10:36:19'),
(128, 'short', '2025-03-24', 1, '10:36:46'),
(129, '750 t shirt', '2025-03-24', 1, '10:54:04'),
(130, '500 pants', '2025-03-24', 2, '10:54:42'),
(131, 'white t-shirt 700', '2025-03-24', 4, '11:44:24'),
(132, 'pants 500', '2025-03-24', 2, '11:44:23'),
(133, 't shirt 700', '2025-03-24', 5, '12:53:37'),
(134, '750 t shirt', '2025-03-25', 1, '13:37:42'),
(135, '500 t shirt', '2025-03-25', 1, '13:39:04'),
(136, '500', '2025-03-25', 1, '13:39:12'),
(137, '20', '2025-03-25', 24, '19:59:55'),
(138, '800', '2025-03-25', 1, '13:40:30'),
(139, 'Nike ', '2025-03-25', 2, '19:28:19'),
(140, 't-shirt', '2025-03-27', 2, '18:18:06'),
(141, 't-shirt 700', '2025-03-27', 2, '21:40:00'),
(142, 'white t-shirt', '2025-03-27', 8, '22:14:31'),
(143, 'Nike ', '2025-03-27', 1, '23:54:14'),
(144, 'nike shirt', '2025-03-28', 2, '04:12:38'),
(145, 'nike ', '2025-03-28', 2, '04:13:19'),
(146, '1000 t-shirt', '2025-03-28', 4, '02:50:02'),
(147, '750', '2025-03-28', 7, '03:52:22'),
(148, '700', '2025-03-28', 1, '03:52:32'),
(149, 'shirt 500', '2025-03-28', 1, '03:54:52'),
(150, 'white t-shirt', '2025-03-28', 5, '04:02:15'),
(151, 'white t-shirt 800', '2025-03-28', 1, '04:00:22'),
(152, 'white t-shirt 500', '2025-03-28', 7, '04:01:16'),
(153, 'white t-shirt 700', '2025-03-28', 1, '04:02:20'),
(154, 'white t-shirt 750', '2025-03-28', 1, '04:02:27'),
(155, 'white t-shirt 300', '2025-03-28', 1, '04:02:35'),
(156, 't-shirt 800', '2025-03-28', 3, '04:09:27'),
(157, 't-shirt', '2025-03-31', 10, '00:57:01'),
(158, 'blue t shirt', '2025-03-31', 3, '00:56:53'),
(159, 'short', '2025-03-31', 2, '10:19:17'),
(160, 'green t shirt', '2025-03-31', 1, '10:19:25'),
(161, '700 tb shirts', '2025-03-31', 1, '10:19:49'),
(162, '700 tb shirt', '2025-03-31', 1, '10:19:54'),
(163, '700 t-shirt', '2025-03-31', 1, '10:20:00'),
(164, '750 t-shirt', '2025-03-31', 1, '10:20:30'),
(165, '750 ', '2025-03-31', 1, '10:20:42'),
(166, '750 shirt', '2025-03-31', 3, '10:22:11'),
(167, 'white t-shirt', '2025-03-31', 1, '11:44:45'),
(168, 'white shirt', '2025-03-31', 2, '11:49:17'),
(169, '700 shirt', '2025-03-31', 1, '12:03:46');

-- --------------------------------------------------------

--
-- Table structure for table `store`
--

CREATE TABLE `store` (
  `StoreID` int(11) NOT NULL,
  `StoreBrandName` varchar(255) DEFAULT NULL,
  `StoreDescription` text DEFAULT NULL,
  `BusinessID` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `LocID` int(11) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store`
--

INSERT INTO `store` (`StoreID`, `StoreBrandName`, `StoreDescription`, `BusinessID`, `status`, `LocID`, `Image`) VALUES
(1, 'Nike Store(S1)', 'Nagbebenta ng nike', 1, 'active', 2, 'nike store.jpg'),
(8, 'Prada Store', 'Selling prada products', 1, 'active', NULL, NULL),
(9, 'Guess', 'Selling gues products', 1, 'active', NULL, ''),
(10, 'Dior store', 'Selling Dior Products', 1, 'active', NULL, ''),
(11, 'Gevinchy(S6)', 'Selling givenchy products', 1, 'active', NULL, ''),
(12, 'Bench Store', 'Selling bench products', 1, 'active', NULL, 'OIP.jpg'),
(17, 'Fila(S4)', 'Selling fila products', 1, 'active', NULL, NULL),
(18, 'Mang Inasal', 'Selling Inasal na manok', 1, 'active', NULL, NULL),
(19, 'Addidas Store(S2)', 'Selling Adidas products', 1, 'active', NULL, NULL),
(20, 'Addidas Store(S2)', 'Selling Adidas products', 2, 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `store_images`
--

CREATE TABLE `store_images` (
  `ImageID` int(11) NOT NULL,
  `StoreID` int(11) NOT NULL,
  `ImagePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_images`
--

INSERT INTO `store_images` (`ImageID`, `StoreID`, `ImagePath`) VALUES
(5, 17, 'fila1.webp'),
(6, 17, 'fila2.webp'),
(7, 18, 'black pants.webp'),
(8, 18, 'red adidas.webp'),
(9, 19, 'OIP (25).jpg'),
(10, 11, '../uploads/download (1).jpg'),
(11, 10, '../uploads/download (2).jpg'),
(12, 8, '../uploads/OIP (28).jpg'),
(13, 9, '../uploads/OIP (27).jpg');

-- --------------------------------------------------------

--
-- Table structure for table `useraccount`
--

CREATE TABLE `useraccount` (
  `UserAccountID` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `UserFirstName` varchar(255) DEFAULT NULL,
  `UserLastName` varchar(255) DEFAULT NULL,
  `UserType` enum('customer','store_manager','admin','superadmin') NOT NULL,
  `BusinessID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccount`
--

INSERT INTO `useraccount` (`UserAccountID`, `Username`, `Password`, `Email`, `PhoneNumber`, `UserFirstName`, `UserLastName`, `UserType`, `BusinessID`, `StoreID`, `status`) VALUES
(2, 'manager123', '$2y$10$AyO3DTUl6S6/TQNXxy/ydOsY61yHmNs.567Z2JzJAyZWO/Dz4LsjO', 'piang@gmail.com', '00009890', 'piang', 'piang', 'store_manager', NULL, 1, 'active'),
(3, 'superadmin', '$2y$10$ikEWxbLvSg0RkVV8zLk83ODsaxxTsNvZfDpj.ELI3Jn4cMBmvr9Zi', 'superadmin@admin.com', '1234567890', 'Super', 'Admin', 'superadmin', NULL, NULL, 'active'),
(5, 'manager2', '$2y$10$IFz5Y2UHzjmhegH1l.9liOETha1A0eA81cYkdBQSRBYulbLTbwjt2', 'mark@gmail.com', '1234567', 'mark', 'beth', 'store_manager', NULL, 2, 'active'),
(6, 'hana1', '$2y$10$xt6LHlnI5ZM2EBRvig5eL.fP.QLe4BLtus9eWijuU3XsQ.FvLXN/2', 'hana@gmail.com', '12345', 'Hana', 'Calanuga', 'store_manager', NULL, 3, 'active'),
(7, 'prin1', '$2y$10$QjDxbx0J10ZkpQ6z0Q5lmuA/GZ50LJffyh5vF3Nl0rJQ7FE9H6.Je', 'prin@gmail.com', '09944332', 'Princess', 'Gomez', 'store_manager', NULL, 4, 'active'),
(10, 'depadmin', '$2y$10$ZIHl4EtKizijx.O/37RtT.yIXnpvQO8kfHkdmll72FJP96RN8VBfu', 'ser@gmail.com', '00009890', 'Isaac', 'Delgado', 'admin', 1, NULL, 'active'),
(17, 'rush1', '$2y$10$JJe36GvB8AQIMziHyXwmnuCSM/ozQM4GKLtvc/jRaSzhGBI/Vxuii', 'rush@gmail.com', '00009890', 'Asreil', 'Rush', '', NULL, NULL, 'active'),
(20, 'depadmin2', '$2y$10$iguHeOPMtX9WBz9h9wlEpuKqAq1.JTHHz5Qpnv/wGnoJX08ANpDJO', 'jake@gmail.com', NULL, 'jake', 'Delgado', 'admin', 2, NULL, 'active'),
(21, 'managerJ', '$2y$10$HqHBpQQF9woTgfRZjPhX9Ozv9GHC0oYY2HdHG6Rm4kvBY9jD2on.u', 'joy@gmail.com', NULL, 'Joy', 'To the world', 'store_manager', NULL, 5, 'active'),
(22, 'smadmin', '$2y$10$ljBf1Nt5pDOuoyikPD9/IOUUBFMCvpc02iQJr1PsvuBvbf2tUkYZ.', 'john@gmail.com', NULL, 'John', 'Doe', 'admin', 2, NULL, 'active'),
(23, 'storemanager', '$2y$10$5JtgNRlleGuS1DERGcN5TOTz3J/5Zev/2Z.Ic2Pf4eUP/DHOzpuha', 'mic@gmail.com', NULL, 'Mich', 'Chel', 'store_manager', NULL, 6, 'active'),
(27, 'juan', '$2y$10$MCcePfa7E1qpH8cFgcNdpOtlRNhnJc5lp.LQLhqt/yBy7IykgXzSK', 'juan@gmail.com', NULL, 'Juan', 'Dela Cruz', 'store_manager', NULL, 7, 'active'),
(30, 'johndoe', '$2y$10$3yCXea3OpZTwaBwMuPLmEu.wJBU.esaybuknnL8pTOV81bd9Uqhay', 'doe@gmail.com', NULL, 'John', 'Doe', 'store_manager', NULL, 17, 'active'),
(31, 'xerx0312', '$2y$10$3ND/S.Zc2j/JxnAW.LI.X.8Mcj3ewZoTrW3Njzxjhxr/RX8vfJuUO', 'xerx@gmail.com', NULL, 'Xerx', 'Sales', 'store_manager', NULL, 10, 'active'),
(32, 'merry01', '$2y$10$ibDSNe21WPsCK.18pCUSYuQ3K.HZoJhx2rFxWGrkhGr5uVrks/NKa', 'marry@gmail.com', NULL, 'Merry', 'Poe', 'store_manager', NULL, 11, 'active'),
(33, 'ben123', '$2y$10$BAdDmQ8FaeDHm8b0dD/m4.prnYA/0h4CxF92PobZ9fus8BhLpeFLm', 'ben@gmail.com', NULL, 'Ben', 'Turner', 'store_manager', NULL, 8, 'active'),
(34, 'eli1234', '$2y$10$pe5WLArNhmII3wW4sZZ1YuPzrOl3XXLQQmMsAktDr52WBcAbJdsNi', 'eli@gmail.com', NULL, 'Eli', 'Zabeth', 'store_manager', NULL, 19, 'active'),
(35, 'eli12344', '$2y$10$/zWiYXHNX00oisNLvxtfA.JIStcddu9mN.F1wrMo1.Eik1hsKrPSm', 'eliz@gmail.com', NULL, 'Eli', 'Zabeth', 'store_manager', NULL, 20, 'active'),
(36, 'isa12', '$2y$10$32RvrcBSKpz8yjsFObXaTOgMt5GR1D04ADqYolwGy9yaAo6AsGyPW', 'isaac@gmail.com', NULL, 'isa', 'ser', 'store_manager', NULL, 20, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businessinfo`
--
ALTER TABLE `businessinfo`
  ADD PRIMARY KEY (`BusinessID`);

--
-- Indexes for table `kioskdevice`
--
ALTER TABLE `kioskdevice`
  ADD PRIMARY KEY (`KioskID`),
  ADD UNIQUE KEY `kioskCode` (`kioskCode`),
  ADD UNIQUE KEY `kioskCode_2` (`kioskCode`),
  ADD KEY `BusinessID` (`BusinessID`),
  ADD KEY `fk_kiosk_store` (`StoreID`);

--
-- Indexes for table `location`
--
ALTER TABLE `location`
  ADD PRIMARY KEY (`LocID`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `KioskID` (`KioskID`);

--
-- Indexes for table `locationimage`
--
ALTER TABLE `locationimage`
  ADD PRIMARY KEY (`ImageID`),
  ADD UNIQUE KEY `MapCode` (`MapCode`),
  ADD KEY `locationimage_ibfk_1` (`AlbumID`);

--
-- Indexes for table `location_albums`
--
ALTER TABLE `location_albums`
  ADD PRIMARY KEY (`AlbumID`),
  ADD KEY `location_albums_ibfk_1` (`LocID`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_username_ip` (`Username`,`IP`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProdID`),
  ADD KEY `product_ibfk_1` (`StoreID`);

--
-- Indexes for table `searchanalytics`
--
ALTER TABLE `searchanalytics`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `store`
--
ALTER TABLE `store`
  ADD PRIMARY KEY (`StoreID`),
  ADD KEY `BusinessID` (`BusinessID`),
  ADD KEY `fk_store_location` (`LocID`);

--
-- Indexes for table `store_images`
--
ALTER TABLE `store_images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `StoreID` (`StoreID`);

--
-- Indexes for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD PRIMARY KEY (`UserAccountID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `BusinessID` (`BusinessID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `businessinfo`
--
ALTER TABLE `businessinfo`
  MODIFY `BusinessID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kioskdevice`
--
ALTER TABLE `kioskdevice`
  MODIFY `KioskID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `location`
--
ALTER TABLE `location`
  MODIFY `LocID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `locationimage`
--
ALTER TABLE `locationimage`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `location_albums`
--
ALTER TABLE `location_albums`
  MODIFY `AlbumID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProdID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `searchanalytics`
--
ALTER TABLE `searchanalytics`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `store`
--
ALTER TABLE `store`
  MODIFY `StoreID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `store_images`
--
ALTER TABLE `store_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `useraccount`
--
ALTER TABLE `useraccount`
  MODIFY `UserAccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kioskdevice`
--
ALTER TABLE `kioskdevice`
  ADD CONSTRAINT `fk_kiosk_store` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`),
  ADD CONSTRAINT `kioskdevice_ibfk_1` FOREIGN KEY (`BusinessID`) REFERENCES `businessinfo` (`BusinessID`);

--
-- Constraints for table `location`
--
ALTER TABLE `location`
  ADD CONSTRAINT `location_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`),
  ADD CONSTRAINT `location_ibfk_2` FOREIGN KEY (`KioskID`) REFERENCES `kioskdevice` (`KioskID`);

--
-- Constraints for table `locationimage`
--
ALTER TABLE `locationimage`
  ADD CONSTRAINT `locationimage_ibfk_1` FOREIGN KEY (`AlbumID`) REFERENCES `location_albums` (`AlbumID`) ON DELETE CASCADE;

--
-- Constraints for table `location_albums`
--
ALTER TABLE `location_albums`
  ADD CONSTRAINT `location_albums_ibfk_1` FOREIGN KEY (`LocID`) REFERENCES `location` (`LocID`) ON DELETE SET NULL;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`) ON DELETE CASCADE;

--
-- Constraints for table `store`
--
ALTER TABLE `store`
  ADD CONSTRAINT `fk_store_location` FOREIGN KEY (`LocID`) REFERENCES `location` (`LocID`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_ibfk_1` FOREIGN KEY (`BusinessID`) REFERENCES `businessinfo` (`BusinessID`);

--
-- Constraints for table `store_images`
--
ALTER TABLE `store_images`
  ADD CONSTRAINT `FK_StoreID` FOREIGN KEY (`StoreID`) REFERENCES `store` (`StoreID`) ON DELETE CASCADE;

--
-- Constraints for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD CONSTRAINT `useraccount_ibfk_1` FOREIGN KEY (`BusinessID`) REFERENCES `businessinfo` (`BusinessID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
