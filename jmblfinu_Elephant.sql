-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 04, 2026 at 10:13 AM
-- Server version: 10.11.10-MariaDB-cll-lve
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jmblfinu_Elephant`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_wallet`
--

CREATE TABLE `admin_wallet` (
  `id` int(11) NOT NULL,
  `profit` decimal(15,2) DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_wallet`
--

INSERT INTO `admin_wallet` (`id`, `profit`) VALUES
(1, '362.00');

-- --------------------------------------------------------

--
-- Table structure for table `bets`
--

CREATE TABLE `bets` (
  `id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bet_index` tinyint(1) NOT NULL DEFAULT 1,
  `amount` decimal(15,2) NOT NULL,
  `cashed_out_at` decimal(10,4) DEFAULT NULL,
  `status` enum('active','cashed_out','lost') DEFAULT 'active'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bets`
--

INSERT INTO `bets` (`id`, `round_id`, `user_id`, `bet_index`, `amount`, `cashed_out_at`, `status`) VALUES
(1, 45, 1, 1, '100.00', NULL, 'lost'),
(2, 46, 1, 1, '100.00', NULL, 'lost'),
(3, 47, 1, 1, '500.00', NULL, 'lost'),
(4, 48, 1, 1, '500.00', NULL, 'lost'),
(5, 48, 1, 1, '500.00', NULL, 'active'),
(6, 49, 1, 1, '500.00', NULL, 'lost'),
(7, 49, 1, 1, '500.00', NULL, 'lost'),
(8, 54, 1, 1, '100.00', NULL, 'active'),
(9, 55, 1, 2, '100.00', NULL, 'active'),
(10, 55, 1, 1, '100.00', NULL, 'active'),
(11, 56, 1, 1, '100.00', NULL, 'lost'),
(12, 56, 1, 2, '100.00', NULL, 'lost'),
(13, 110, 1, 1, '100.00', NULL, 'active'),
(14, 110, 1, 2, '100.00', NULL, 'active'),
(15, 135, 1, 1, '100.00', NULL, 'lost'),
(16, 135, 1, 2, '100.00', NULL, 'lost'),
(17, 137, 1, 2, '100.00', NULL, 'lost'),
(18, 137, 1, 1, '100.00', NULL, 'lost'),
(19, 145, 1, 1, '100.00', NULL, 'lost'),
(20, 145, 1, 2, '100.00', NULL, 'lost'),
(21, 157, 1, 1, '100.00', NULL, 'lost'),
(22, 157, 1, 2, '100.00', NULL, 'lost'),
(23, 173, 1, 2, '100.00', NULL, 'active'),
(24, 174, 1, 1, '100.00', NULL, 'lost'),
(25, 175, 1, 2, '100.00', NULL, 'lost'),
(26, 209, 1, 1, '100.00', NULL, 'lost'),
(27, 209, 1, 2, '100.00', NULL, 'lost'),
(28, 220, 1, 1, '10.00', NULL, 'lost'),
(29, 220, 1, 2, '10.00', NULL, 'lost');

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` varchar(50) NOT NULL DEFAULT 'mpesa',
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `screenshot` varchar(255) DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `bonus_percentage` decimal(5,2) DEFAULT 0.00,
  `min_deposit` decimal(15,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `title`, `description`, `bonus_percentage`, `min_deposit`, `active`, `created_at`) VALUES
(1, 'Welcome Bonus', 'Get 100% bonus on your first deposit up to Ksh 5,000', '100.00', '100.00', 1, '2026-11-04 09:18:35'),
(2, 'Aviator Cashback', 'Get 10% cashback on net losses every Monday', '10.00', '500.00', 1, '2026-11-04 09:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `bonus_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rounds`
--

CREATE TABLE `rounds` (
  `id` int(11) NOT NULL,
  `crash_point` decimal(10,4) NOT NULL,
  `start_time` int(11) NOT NULL,
  `crash_time` int(11) NOT NULL,
  `status` enum('waiting','running','crashed') DEFAULT 'waiting',
  `total_wagered` decimal(15,2) DEFAULT 0.00,
  `total_paid` decimal(15,2) DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rounds`
--

INSERT INTO `rounds` (`id`, `crash_point`, `start_time`, `crash_time`, `status`, `total_wagered`, `total_paid`) VALUES
(1, '1.0000', 1793783643, 1793783643, 'crashed', '0.00', '0.00'),
(2, '1.0000', 1793783661, 1793783661, 'crashed', '0.00', '0.00'),
(3, '1.0000', 1793783672, 1793783672, 'crashed', '0.00', '0.00'),
(4, '1.0000', 1793783695, 1793783695, 'crashed', '0.00', '0.00'),
(5, '1.0000', 1793783705, 1793783705, 'crashed', '0.00', '0.00'),
(6, '1.0000', 1793783716, 1793783716, 'crashed', '0.00', '0.00'),
(7, '1.0000', 1793783736, 1793783736, 'crashed', '0.00', '0.00'),
(8, '1.0000', 1793783943, 1793783943, 'crashed', '0.00', '0.00'),
(9, '1.0000', 1793783982, 1793783982, 'crashed', '0.00', '0.00'),
(10, '1.0000', 1793783993, 1793783993, 'crashed', '0.00', '0.00'),
(11, '1.0000', 1793784027, 1793784027, 'crashed', '0.00', '0.00'),
(12, '1.0000', 1793784038, 1793784038, 'crashed', '0.00', '0.00'),
(13, '1.0000', 1793784050, 1793784050, 'crashed', '0.00', '0.00'),
(14, '1.0000', 1793784061, 1793784061, 'crashed', '0.00', '0.00'),
(15, '1.0000', 1793785384, 1793785384, 'crashed', '0.00', '0.00'),
(16, '1.0000', 1793785404, 1793785404, 'crashed', '0.00', '0.00'),
(17, '1.0000', 1793785543, 1793785543, 'crashed', '0.00', '0.00'),
(18, '1.0000', 1793785952, 1793785952, 'crashed', '0.00', '0.00'),
(19, '1.0000', 1793785998, 1793785998, 'crashed', '0.00', '0.00'),
(20, '1.0000', 1793786019, 1793786019, 'crashed', '0.00', '0.00'),
(21, '1.0000', 1793786032, 1793786032, 'crashed', '0.00', '0.00'),
(22, '1.0000', 1793786049, 1793786049, 'crashed', '0.00', '0.00'),
(23, '1.0000', 1793786064, 1793786064, 'crashed', '0.00', '0.00'),
(24, '1.0000', 1793786652, 1793786652, 'crashed', '0.00', '0.00'),
(25, '1.0000', 1793786663, 1793786663, 'crashed', '0.00', '0.00'),
(26, '1.0000', 1793786680, 1793786680, 'crashed', '0.00', '0.00'),
(27, '1.0000', 1793786695, 1793786695, 'crashed', '0.00', '0.00'),
(28, '1.0000', 1793786710, 1793786710, 'crashed', '0.00', '0.00'),
(29, '1.0000', 1793786845, 1793786845, 'crashed', '0.00', '0.00'),
(30, '1.0000', 1793787145, 1793787145, 'crashed', '0.00', '0.00'),
(31, '1.0000', 1793787179, 1793787179, 'crashed', '0.00', '0.00'),
(32, '1.0000', 1793787210, 1793787210, 'crashed', '0.00', '0.00'),
(33, '1.0000', 1793787222, 1793787222, 'crashed', '0.00', '0.00'),
(34, '1.0000', 1793787238, 1793787238, 'crashed', '0.00', '0.00'),
(35, '1.0000', 1793787249, 1793787249, 'crashed', '0.00', '0.00'),
(36, '1.0000', 1793787259, 1793787259, 'crashed', '0.00', '0.00'),
(37, '1.0000', 1793787275, 1793787275, 'crashed', '0.00', '0.00'),
(38, '1.0000', 1793787506, 1793787506, 'crashed', '0.00', '0.00'),
(39, '1.0000', 1793789285, 1793789285, 'crashed', '0.00', '0.00'),
(40, '1.0000', 1793789303, 1793789303, 'crashed', '0.00', '0.00'),
(41, '1.0000', 1793789316, 1793789316, 'crashed', '0.00', '0.00'),
(42, '1.0000', 1793789363, 1793789363, 'crashed', '0.00', '0.00'),
(43, '1.0000', 1793789375, 1793789375, 'crashed', '0.00', '0.00'),
(44, '1.0000', 1793789386, 1793789386, 'crashed', '0.00', '0.00'),
(45, '1.0000', 1793789406, 1793789406, 'crashed', '100.00', '0.00'),
(46, '1.0000', 1793789419, 1793789419, 'crashed', '100.00', '0.00'),
(47, '1.0000', 1793789435, 1793789435, 'crashed', '500.00', '0.00'),
(48, '1.0000', 1793789451, 1793789451, 'crashed', '1000.00', '0.00'),
(49, '1.0000', 1793789466, 1793789466, 'crashed', '1000.00', '0.00'),
(50, '1.0000', 1793789482, 1793789482, 'crashed', '0.00', '0.00'),
(51, '1.0000', 1793789495, 1793789495, 'crashed', '0.00', '0.00'),
(52, '1.0000', 1793789509, 1793789509, 'crashed', '0.00', '0.00'),
(53, '1.0000', 1793789538, 1793789538, 'crashed', '0.00', '0.00'),
(54, '1.0000', 1793793347, 1793793347, 'crashed', '100.00', '0.00'),
(55, '1.0000', 1793793364, 1793793364, 'crashed', '200.00', '0.00'),
(56, '1.0000', 1793793374, 1793793374, 'crashed', '200.00', '0.00'),
(57, '1.0000', 1793793450, 1793793450, 'crashed', '0.00', '0.00'),
(58, '1.0000', 1793793471, 1793793471, 'crashed', '0.00', '0.00'),
(59, '1.0000', 1793793481, 1793793481, 'crashed', '0.00', '0.00'),
(60, '1.0000', 1793793491, 1793793491, 'crashed', '0.00', '0.00'),
(61, '1.0000', 1793793502, 1793793502, 'crashed', '0.00', '0.00'),
(62, '1.0000', 1793793512, 1793793512, 'crashed', '0.00', '0.00'),
(63, '1.0000', 1793793627, 1793793627, 'crashed', '0.00', '0.00'),
(64, '1.0000', 1793793642, 1793793642, 'crashed', '0.00', '0.00'),
(65, '1.0000', 1793793683, 1793793683, 'crashed', '0.00', '0.00'),
(66, '1.0000', 1793793693, 1793793693, 'crashed', '0.00', '0.00'),
(67, '1.0000', 1793793707, 1793793707, 'crashed', '0.00', '0.00'),
(68, '1.0000', 1793793718, 1793793718, 'crashed', '0.00', '0.00'),
(69, '1.0000', 1793793728, 1793793728, 'crashed', '0.00', '0.00'),
(70, '1.0000', 1793793739, 1793793739, 'crashed', '0.00', '0.00'),
(71, '1.0000', 1793793759, 1793793759, 'crashed', '0.00', '0.00'),
(72, '1.0000', 1793793774, 1793793774, 'crashed', '0.00', '0.00'),
(73, '1.0000', 1793793785, 1793793785, 'crashed', '0.00', '0.00'),
(74, '1.0000', 1793794491, 1793794491, 'crashed', '0.00', '0.00'),
(75, '1.0000', 1793794513, 1793794513, 'crashed', '0.00', '0.00'),
(76, '1.0000', 1793794539, 1793794539, 'crashed', '0.00', '0.00'),
(77, '1.0000', 1793794549, 1793794549, 'crashed', '0.00', '0.00'),
(78, '1.0000', 1793794561, 1793794561, 'crashed', '0.00', '0.00'),
(79, '1.0000', 1793794600, 1793794600, 'crashed', '0.00', '0.00'),
(80, '1.0000', 1793794613, 1793794613, 'crashed', '0.00', '0.00'),
(81, '1.0000', 1793794630, 1793794630, 'crashed', '0.00', '0.00'),
(82, '1.0000', 1793794651, 1793794651, 'crashed', '0.00', '0.00'),
(83, '1.0000', 1793794661, 1793794661, 'crashed', '0.00', '0.00'),
(84, '1.0000', 1793794690, 1793794690, 'crashed', '0.00', '0.00'),
(85, '1.0000', 1793794700, 1793794700, 'crashed', '0.00', '0.00'),
(86, '1.0000', 1793794727, 1793794727, 'crashed', '0.00', '0.00'),
(87, '1.0000', 1793794738, 1793794738, 'crashed', '0.00', '0.00'),
(88, '1.0000', 1793794756, 1793794756, 'crashed', '0.00', '0.00'),
(89, '1.0000', 1793794771, 1793794771, 'crashed', '0.00', '0.00'),
(90, '1.0000', 1793794786, 1793794786, 'crashed', '0.00', '0.00'),
(91, '1.0000', 1793794801, 1793794801, 'crashed', '0.00', '0.00'),
(92, '1.0000', 1793794812, 1793794812, 'crashed', '0.00', '0.00'),
(93, '1.0000', 1793794823, 1793794823, 'crashed', '0.00', '0.00'),
(94, '1.0000', 1793794839, 1793794839, 'crashed', '0.00', '0.00'),
(95, '1.0000', 1793794852, 1793794852, 'crashed', '0.00', '0.00'),
(96, '1.0000', 1793794868, 1793794868, 'crashed', '0.00', '0.00'),
(97, '1.0000', 1793794881, 1793794881, 'crashed', '0.00', '0.00'),
(98, '1.0000', 1793794893, 1793794893, 'crashed', '0.00', '0.00'),
(99, '1.0000', 1793794906, 1793794906, 'crashed', '0.00', '0.00'),
(100, '1.0000', 1793794916, 1793794916, 'crashed', '0.00', '0.00'),
(101, '1.0000', 1793794927, 1793794927, 'crashed', '0.00', '0.00'),
(102, '1.0000', 1793794947, 1793794947, 'crashed', '0.00', '0.00'),
(103, '1.0000', 1793794957, 1793794957, 'crashed', '0.00', '0.00'),
(104, '1.0000', 1793794970, 1793794970, 'crashed', '0.00', '0.00'),
(105, '1.0000', 1793794980, 1793794980, 'crashed', '0.00', '0.00'),
(106, '1.0000', 1793794993, 1793794993, 'crashed', '0.00', '0.00'),
(107, '1.0000', 1793795016, 1793795016, 'crashed', '0.00', '0.00'),
(108, '1.0000', 1793795027, 1793795027, 'crashed', '0.00', '0.00'),
(109, '1.0000', 1793795040, 1793795040, 'crashed', '0.00', '0.00'),
(110, '1.0000', 1793795059, 1793795059, 'crashed', '200.00', '0.00'),
(111, '1.0000', 1793795080, 1793795080, 'crashed', '0.00', '0.00'),
(112, '1.0000', 1793795127, 1793795127, 'crashed', '0.00', '0.00'),
(113, '1.0000', 1793795138, 1793795138, 'crashed', '0.00', '0.00'),
(114, '1.0000', 1793795160, 1793795160, 'crashed', '0.00', '0.00'),
(115, '1.0000', 1793795170, 1793795170, 'crashed', '0.00', '0.00'),
(116, '1.0000', 1793795192, 1793795192, 'crashed', '0.00', '0.00'),
(117, '1.0000', 1793795228, 1793795228, 'crashed', '0.00', '0.00'),
(118, '1.0000', 1793795244, 1793795244, 'crashed', '0.00', '0.00'),
(119, '1.0000', 1793795262, 1793795262, 'crashed', '0.00', '0.00'),
(120, '1.0000', 1793795284, 1793795284, 'crashed', '0.00', '0.00'),
(121, '1.0000', 1793795298, 1793795298, 'crashed', '0.00', '0.00'),
(122, '1.0000', 1793795310, 1793795310, 'crashed', '0.00', '0.00'),
(123, '1.0000', 1793795322, 1793795322, 'crashed', '0.00', '0.00'),
(124, '1.0000', 1793795334, 1793795334, 'crashed', '0.00', '0.00'),
(125, '1.0000', 1793795350, 1793795350, 'crashed', '0.00', '0.00'),
(126, '1.0000', 1793795385, 1793795385, 'crashed', '0.00', '0.00'),
(127, '1.0000', 1793795397, 1793795397, 'crashed', '0.00', '0.00'),
(128, '1.0000', 1793795434, 1793795434, 'crashed', '0.00', '0.00'),
(129, '1.0000', 1793795491, 1793795491, 'crashed', '0.00', '0.00'),
(130, '1.0000', 1793795520, 1793795520, 'crashed', '0.00', '0.00'),
(131, '1.0000', 1793795533, 1793795533, 'crashed', '0.00', '0.00'),
(132, '1.0000', 1793795561, 1793795561, 'crashed', '0.00', '0.00'),
(133, '1.0000', 1793795572, 1793795572, 'crashed', '0.00', '0.00'),
(134, '1.0000', 1793795589, 1793795589, 'crashed', '0.00', '0.00'),
(135, '1.0000', 1793795609, 1793795609, 'crashed', '200.00', '0.00'),
(136, '1.0000', 1793795624, 1793795624, 'crashed', '0.00', '0.00'),
(137, '1.0000', 1793795638, 1793795638, 'crashed', '200.00', '0.00'),
(138, '1.0000', 1793795653, 1793795653, 'crashed', '0.00', '0.00'),
(139, '1.0000', 1793795671, 1793795671, 'crashed', '0.00', '0.00'),
(140, '1.0000', 1793795697, 1793795697, 'crashed', '0.00', '0.00'),
(141, '1.0000', 1793795735, 1793795735, 'crashed', '0.00', '0.00'),
(142, '1.0000', 1793795809, 1793795809, 'crashed', '0.00', '0.00'),
(143, '1.0000', 1793795953, 1793795953, 'crashed', '0.00', '0.00'),
(144, '1.0000', 1793795970, 1793795970, 'crashed', '0.00', '0.00'),
(145, '1.0000', 1793795984, 1793795984, 'crashed', '200.00', '0.00'),
(146, '1.0000', 1793796011, 1793796011, 'crashed', '0.00', '0.00'),
(147, '1.0000', 1793796051, 1793796051, 'crashed', '0.00', '0.00'),
(148, '1.0000', 1793796077, 1793796077, 'crashed', '0.00', '0.00'),
(149, '1.0000', 1793796090, 1793796090, 'crashed', '0.00', '0.00'),
(150, '1.0000', 1793796108, 1793796108, 'crashed', '0.00', '0.00'),
(151, '1.0000', 1793796118, 1793796118, 'crashed', '0.00', '0.00'),
(152, '1.0000', 1793796128, 1793796128, 'crashed', '0.00', '0.00'),
(153, '1.0000', 1793796153, 1793796153, 'crashed', '0.00', '0.00'),
(154, '1.0000', 1793796176, 1793796176, 'crashed', '0.00', '0.00'),
(155, '1.0000', 1793796186, 1793796186, 'crashed', '0.00', '0.00'),
(156, '1.0000', 1793796361, 1793796361, 'crashed', '0.00', '0.00'),
(157, '1.0000', 1793796374, 1793796374, 'crashed', '200.00', '0.00'),
(158, '1.0000', 1793796404, 1793796404, 'crashed', '0.00', '0.00'),
(159, '1.0000', 1793796420, 1793796420, 'crashed', '0.00', '0.00'),
(160, '1.0000', 1793796433, 1793796433, 'crashed', '0.00', '0.00'),
(161, '1.0000', 1793796465, 1793796465, 'crashed', '0.00', '0.00'),
(162, '1.0000', 1793796477, 1793796477, 'crashed', '0.00', '0.00'),
(163, '1.0000', 1793796496, 1793796496, 'crashed', '0.00', '0.00'),
(164, '1.0000', 1793796511, 1793796511, 'crashed', '0.00', '0.00'),
(165, '1.0000', 1793796524, 1793796524, 'crashed', '0.00', '0.00'),
(166, '1.0000', 1793796626, 1793796626, 'crashed', '0.00', '0.00'),
(167, '1.0000', 1793796701, 1793796701, 'crashed', '0.00', '0.00'),
(168, '1.0000', 1793796721, 1793796721, 'crashed', '0.00', '0.00'),
(169, '1.0000', 1793796735, 1793796735, 'crashed', '0.00', '0.00'),
(170, '1.0000', 1793796746, 1793796746, 'crashed', '0.00', '0.00'),
(171, '1.0000', 1793796756, 1793796756, 'crashed', '0.00', '0.00'),
(172, '1.0000', 1793796767, 1793796767, 'crashed', '0.00', '0.00'),
(173, '1.0000', 1793796785, 1793796785, 'crashed', '100.00', '0.00'),
(174, '1.0000', 1793797204, 1793797204, 'crashed', '100.00', '0.00'),
(175, '1.0000', 1793797218, 1793797218, 'crashed', '100.00', '0.00'),
(176, '1.0000', 1793797246, 1793797246, 'crashed', '0.00', '0.00'),
(177, '1.0000', 1793797264, 1793797264, 'crashed', '0.00', '0.00'),
(178, '1.0000', 1793797280, 1793797280, 'crashed', '0.00', '0.00'),
(179, '1.0000', 1793797296, 1793797296, 'crashed', '0.00', '0.00'),
(180, '1.0000', 1793797306, 1793797306, 'crashed', '0.00', '0.00'),
(181, '1.0000', 1793797335, 1793797335, 'crashed', '0.00', '0.00'),
(182, '1.0000', 1793797350, 1793797350, 'crashed', '0.00', '0.00'),
(183, '1.0000', 1793797367, 1793797367, 'crashed', '0.00', '0.00'),
(184, '1.0000', 1793797381, 1793797381, 'crashed', '0.00', '0.00'),
(185, '1.0000', 1793797409, 1793797409, 'crashed', '0.00', '0.00'),
(186, '1.0000', 1793797431, 1793797431, 'crashed', '0.00', '0.00'),
(187, '1.0000', 1793797443, 1793797443, 'crashed', '0.00', '0.00'),
(188, '1.0000', 1793797466, 1793797466, 'crashed', '0.00', '0.00'),
(189, '1.0000', 1793797481, 1793797481, 'crashed', '0.00', '0.00'),
(190, '1.0000', 1793797498, 1793797498, 'crashed', '0.00', '0.00'),
(191, '1.0000', 1793797522, 1793797522, 'crashed', '0.00', '0.00'),
(192, '1.0000', 1793797532, 1793797532, 'crashed', '0.00', '0.00'),
(193, '1.0000', 1793797553, 1793797553, 'crashed', '0.00', '0.00'),
(194, '1.0000', 1793797576, 1793797576, 'crashed', '0.00', '0.00'),
(195, '1.0000', 1793797586, 1793797586, 'crashed', '0.00', '0.00'),
(196, '1.0000', 1793797601, 1793797601, 'crashed', '0.00', '0.00'),
(197, '1.0000', 1793797626, 1793797626, 'crashed', '0.00', '0.00'),
(198, '1.0000', 1793797653, 1793797653, 'crashed', '0.00', '0.00'),
(199, '1.0000', 1793797669, 1793797669, 'crashed', '0.00', '0.00'),
(200, '1.0000', 1793797691, 1793797691, 'crashed', '0.00', '0.00'),
(201, '1.0000', 1793797707, 1793797707, 'crashed', '0.00', '0.00'),
(202, '1.0000', 1793797717, 1793797717, 'crashed', '0.00', '0.00'),
(203, '1.0000', 1793797727, 1793797727, 'crashed', '0.00', '0.00'),
(204, '1.0000', 1793797739, 1793797739, 'crashed', '0.00', '0.00'),
(205, '1.0000', 1793797752, 1793797752, 'crashed', '0.00', '0.00'),
(206, '1.0000', 1793797789, 1793797789, 'crashed', '0.00', '0.00'),
(207, '1.0000', 1793797804, 1793797804, 'crashed', '0.00', '0.00'),
(208, '1.0000', 1793797818, 1793797818, 'crashed', '0.00', '0.00'),
(209, '1.0000', 1793797835, 1793797835, 'crashed', '200.00', '0.00'),
(210, '1.0000', 1793797851, 1793797851, 'crashed', '0.00', '0.00'),
(211, '1.0000', 1793797872, 1793797872, 'crashed', '0.00', '0.00'),
(212, '1.0000', 1793797885, 1793797885, 'crashed', '0.00', '0.00'),
(213, '1.0000', 1793797895, 1793797895, 'crashed', '0.00', '0.00'),
(214, '1.0000', 1793797907, 1793797907, 'crashed', '0.00', '0.00'),
(215, '1.0000', 1793797931, 1793797931, 'crashed', '0.00', '0.00'),
(216, '1.0000', 1793797942, 1793797942, 'crashed', '0.00', '0.00'),
(217, '1.0000', 1793797952, 1793797952, 'crashed', '0.00', '0.00'),
(218, '1.0000', 1793797970, 1793797970, 'crashed', '0.00', '0.00'),
(219, '1.0000', 1793798971, 1793798971, 'crashed', '0.00', '0.00'),
(220, '1.0000', 1793798991, 1793798991, 'crashed', '20.00', '0.00'),
(221, '1.0000', 1793799024, 1793799024, 'crashed', '0.00', '0.00'),
(222, '1.0000', 1793799036, 1793799036, 'crashed', '0.00', '0.00'),
(223, '1.0000', 1793799051, 1793799051, 'crashed', '0.00', '0.00'),
(224, '1.0000', 1793799061, 1793799061, 'crashed', '0.00', '0.00'),
(225, '1.0000', 1793799075, 1793799075, 'crashed', '0.00', '0.00'),
(226, '1.0000', 1793799097, 1793799097, 'crashed', '0.00', '0.00'),
(227, '1.0000', 1793799109, 1793799109, 'crashed', '0.00', '0.00'),
(228, '1.0000', 1793799120, 1793799120, 'crashed', '0.00', '0.00'),
(229, '1.0000', 1793799146, 1793799146, 'crashed', '0.00', '0.00'),
(230, '1.0000', 1793799162, 1793799162, 'crashed', '0.00', '0.00'),
(231, '1.0000', 1793799178, 1793799178, 'crashed', '0.00', '0.00'),
(232, '1.0000', 1793799197, 1793799197, 'crashed', '0.00', '0.00'),
(233, '1.0000', 1793799216, 1793799216, 'crashed', '0.00', '0.00'),
(234, '1.0000', 1793800052, 1793800052, 'crashed', '0.00', '0.00'),
(235, '1.0000', 1793800062, 1793800062, 'crashed', '0.00', '0.00'),
(236, '1.0000', 1793800081, 1793800081, 'crashed', '0.00', '0.00'),
(237, '1.0000', 1793800102, 1793800102, 'crashed', '0.00', '0.00'),
(238, '1.0000', 1793800706, 1793800706, 'running', '0.00', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key_name`, `key_value`, `description`) VALUES
('site_name', 'Elephant Bet', 'Platform name'),
('min_deposit', '100', 'Minimum deposit amount'),
('min_withdrawal', '200', 'Minimum withdrawal amount'),
('max_withdrawal', '100000', 'Maximum withdrawal amount'),
('referral_bonus', '100', 'Bonus for each referral (Ksh)'),
('mpesa_paybill', '123456', 'M-Pesa Paybill Number'),
('mpesa_account', 'ELEPHANT', 'M-Pesa Account Number'),
('paypal_email', 'pay@elephantbet.com', 'PayPal email'),
('support_email', 'support@elephantbet.com', 'Support email'),
('support_whatsapp', '+254700000000', 'WhatsApp support');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdrawal','bet','win','referral_bonus','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) DEFAULT 0.00,
  `reference` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `balance_before`, `balance_after`, `reference`, `description`, `status`, `created_at`) VALUES
(1, 1, 'withdrawal', '1000.00', '1000.00', '0.00', 'WD-1', 'Withdrawal request via paypal', 'completed', '2026-11-04 09:45:24'),
(2, 1, 'bet', '100.00', '0.00', '0.00', 'BET-54-1', 'Bet placed on round #54', 'completed', '2026-11-04 11:55:57'),
(3, 1, 'bet', '100.00', '0.00', '0.00', 'BET-55-2', 'Bet placed on round #55', 'completed', '2026-11-04 11:56:05'),
(4, 1, 'bet', '100.00', '0.00', '0.00', 'BET-55-1', 'Bet placed on round #55', 'completed', '2026-11-04 11:56:06'),
(5, 1, 'bet', '100.00', '0.00', '0.00', 'BET-56-1', 'Bet placed on round #56', 'completed', '2026-11-04 11:56:11'),
(6, 1, 'bet', '100.00', '0.00', '0.00', 'BET-56-2', 'Bet placed on round #56', 'completed', '2026-11-04 11:56:14'),
(7, 1, 'bet', '100.00', '0.00', '0.00', 'BET-110-1', 'Bet placed on round #110', 'completed', '2026-11-04 12:24:21'),
(8, 1, 'bet', '100.00', '0.00', '0.00', 'BET-110-2', 'Bet placed on round #110', 'completed', '2026-11-04 12:24:25'),
(9, 1, 'bet', '100.00', '0.00', '0.00', 'BET-135-1', 'Bet placed on round #135', 'completed', '2026-11-04 12:33:24'),
(10, 1, 'bet', '100.00', '0.00', '0.00', 'BET-135-2', 'Bet placed on round #135', 'completed', '2026-11-04 12:33:27'),
(11, 1, 'bet', '100.00', '0.00', '0.00', 'BET-137-2', 'Bet placed on round #137', 'completed', '2026-11-04 12:33:53'),
(12, 1, 'bet', '100.00', '0.00', '0.00', 'BET-137-1', 'Bet placed on round #137', 'completed', '2026-11-04 12:33:53'),
(13, 1, 'bet', '100.00', '0.00', '0.00', 'BET-145-1', 'Bet placed on round #145', 'completed', '2026-11-04 12:39:39'),
(14, 1, 'bet', '100.00', '0.00', '0.00', 'BET-145-2', 'Bet placed on round #145', 'completed', '2026-11-04 12:39:39'),
(15, 1, 'bet', '100.00', '0.00', '0.00', 'BET-157-1', 'Bet placed on round #157', 'completed', '2026-11-04 12:46:09'),
(16, 1, 'bet', '100.00', '0.00', '0.00', 'BET-157-2', 'Bet placed on round #157', 'completed', '2026-11-04 12:46:09'),
(17, 1, 'bet', '100.00', '0.00', '0.00', 'BET-173-2', 'Bet placed on round #173', 'completed', '2026-11-04 12:59:59'),
(18, 1, 'bet', '100.00', '0.00', '0.00', 'BET-174-1', 'Bet placed on round #174', 'completed', '2026-11-04 13:00:00'),
(19, 1, 'bet', '100.00', '0.00', '0.00', 'BET-175-2', 'Bet placed on round #175', 'completed', '2026-11-04 13:00:14'),
(20, 1, 'bet', '100.00', '0.00', '0.00', 'BET-209-1', 'Bet placed on round #209', 'completed', '2026-11-04 13:10:31'),
(21, 1, 'bet', '100.00', '0.00', '0.00', 'BET-209-2', 'Bet placed on round #209', 'completed', '2026-11-04 13:10:32'),
(22, 1, 'bet', '10.00', '0.00', '0.00', 'BET-220-1', 'Bet placed on round #220', 'completed', '2026-11-04 13:29:47'),
(23, 1, 'bet', '10.00', '0.00', '0.00', 'BET-220-2', 'Bet placed on round #220', 'completed', '2026-11-04 13:29:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 1000.00,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_earnings` decimal(15,2) DEFAULT 0.00,
  `total_deposits` decimal(15,2) DEFAULT 0.00,
  `total_withdrawals` decimal(15,2) DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `phone`, `balance`, `referral_code`, `referred_by`, `referral_earnings`, `total_deposits`, `total_withdrawals`) VALUES
(1, 'Eric', '$2y$10$OZAyIqg4BbwOddWdRoAJYeHbpftzmAzu5Lc5lODaQzAgNzDmDcPBG', NULL, '60.00', '5A15CEEA', NULL, '0.00', '0.00', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` varchar(50) NOT NULL DEFAULT 'mpesa',
  `account_number` varchar(100) NOT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','processing') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `user_id`, `amount`, `method`, `account_number`, `account_name`, `status`, `admin_note`, `created_at`, `processed_at`) VALUES
(1, 1, '1000.00', 'paypal', '0703834247', 'Eric Mbondo', 'pending', NULL, '2026-11-04 09:45:24', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_wallet`
--
ALTER TABLE `admin_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bets`
--
ALTER TABLE `bets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Indexes for table `rounds`
--
ALTER TABLE `rounds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key_name`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `referral_code` (`referral_code`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bets`
--
ALTER TABLE `bets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rounds`
--
ALTER TABLE `rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
