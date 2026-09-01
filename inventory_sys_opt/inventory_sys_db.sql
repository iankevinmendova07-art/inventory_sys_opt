-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 29, 2026 at 02:52 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_sys_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'Admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `admin_name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Ian Kevin Mendova', 'admin', '$2y$10$HKc2FFBeLMbMvDCxXqQ5me67NKQUkT6IFUUoVgEfzzmMRGDfq07K.', 'Admin', '2026-08-10 14:03:39');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int NOT NULL,
  `emp_id` varchar(50) NOT NULL,
  `emp_name` varchar(100) NOT NULL,
  `emp_position` varchar(100) NOT NULL,
  `emp_email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`id`, `emp_id`, `emp_name`, `emp_position`, `emp_email`) VALUES
(5, '10', 'Ian Kevin Tuazon', 'Administrative Officer II', 'iankevinmendova@gmail.com'),
(7, '5', 'Shella Caballero', 'Teacher II', 'shella.caballero@deped.gov.ph'),
(8, '1', 'Ylona Rizza B. Molito', 'Teacher II', 'ylonarizza.basada@deped.gov.ph'),
(9, '2', 'Arlene R. Nuevo', 'Teacher III', 'arlene.nuevo@deped.gov.ph'),
(10, '3', 'Leah B. Balangatan', 'Teacher III', 'leah.balangatan@deped.gov.ph'),
(11, '4', 'Ma. Gaudencia P. Mabini', 'Teacher III', 'magaudencia.mabini@deped.gov.ph'),
(12, '6', 'Diana M. Braga', 'Teacher III', 'diana.braga@deped.gov.ph'),
(13, '7', 'Mylyn A. Bernales', 'Master Teacher I', 'mylyn.bernales@deped.gov.ph'),
(14, '8', 'Sherlyn A. Lara', 'Teacher III', 'sherlyn.lara@deped.gov.ph'),
(15, '9', 'Roselle U. Gayamat', 'School Head', 'roselle.gayamat@deped.gov.ph');

-- --------------------------------------------------------

--
-- Table structure for table `lr_sme`
--

CREATE TABLE `lr_sme` (
  `id` int NOT NULL,
  `lr_code` varchar(50) NOT NULL,
  `lr_item` varchar(255) NOT NULL,
  `lr_qty` int NOT NULL DEFAULT '0',
  `lr_unit` varchar(50) DEFAULT NULL,
  `lr_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lr_sme`
--

INSERT INTO `lr_sme` (`id`, `lr_code`, `lr_item`, `lr_qty`, `lr_unit`, `lr_type`) VALUES
(2, '2', 'Magnetic Board with magnetic strips', 2, 'set', 'Science'),
(4, '4', 'Fraction Set', 5, 'set', 'Math'),
(6, '6', 'Place Value Chart', 4, 'set', 'Math'),
(7, '7', 'Storage Cabinet (for Science), steel', 2, 'unit', 'Science'),
(8, '8', 'Storage Cabinet (for Math), steel', 2, 'unit', 'Math'),
(9, '9', 'Double-pan Balance, 500-gram capacity', 4, 'unit', 'Science'),
(11, '11', 'Connecting Wires with bulb & socket assembly 250mm long Connecting Wire w/ crocodile clips', 7, 'pc', 'Science'),
(12, '12', 'Connecting Wires with bulb & socket assembly Bulb and Socket assembly', 4, 'set', 'Science'),
(13, '13', 'Dry Cell Holder, 1 chamber, for size D dry cell', 1, 'pc', 'Science'),
(14, '14', 'Toy Car, non-friction, non-battery', 1, 'pc', 'Science'),
(15, '15', 'Hand Magnifying Lens, 5x', 6, 'pc', 'Science'),
(16, '16', 'Pair of Bar Magnets', 4, 'pair', 'Science'),
(17, '17', 'Weighing Scale, bathroom-type', 2, 'unit', 'Science'),
(18, '18', 'Beral Pipette, 5 mL', 5, 'pc', 'Science'),
(20, '20', 'Human Ear Model', 1, 'pc', 'Science'),
(21, '21', 'Human Nose Model', 1, 'pc', 'Science'),
(24, '24', 'Human Torso Model (miniature-type)', 2, 'pc', 'Science'),
(26, '26', 'Set of Measuring Cups and Spoons', 2, 'set', 'Science'),
(28, '28', 'Plastic Ruler, 12 inches or 30 cm', 1, 'pc', 'Science'),
(29, '29', 'Digital Clock, tabletop', 1, 'unit', 'Math'),
(30, '30', 'Beads, Ø16mm', 42, 'pc', 'Math'),
(31, '31', 'Weighing Scale, analog, 5 kg. capacity', 1, 'unit', 'Math'),
(32, '32', 'Weighing Scale, 1 kg. capacity', 1, 'pc', 'Math'),
(34, '34', 'Square Tiles, 2.54 x 2.54cm, plastic', 96, 'pc', 'Math'),
(35, '35', 'Pattern Blocks, 250 pcs/set', 10, 'set', 'Math'),
(36, '36', 'Cuisenaire Rods/Number Sticks, 250 pcs/set', 10, 'set', 'Math'),
(38, '38', 'Demonstration Clock', 1, 'pc', 'Math'),
(39, '39', 'Measuring Cup, 250 mL capacity, w/ graduations', 5, 'pc', 'Math'),
(42, '42', 'Basic 3-Dimensional Models', 1, 'set', 'Math'),
(43, '43', 'Geoboard, 11 x 11', 4, 'pc', 'Math'),
(54, '11', '250mm long Connecting Wire w/ crocodile clips', 7, 'pc', 'Science'),
(55, '12', 'Bulb and Socket assembly', 4, 'set', 'Science'),
(88, '45', 'Wire Gauze', 3, 'pc', 'Science'),
(89, '46', 'Tripod', 4, 'pc', 'Science'),
(90, '47', 'Test Tube Holder', 5, 'pc', 'Science'),
(92, '49', 'Pulley Set: - Single Pulley', 5, 'set', 'Science'),
(93, '50', 'Test Tube Rack', 5, 'pc', 'Science'),
(94, '51', 'Protractor, blackboard', 1, 'pc', 'Science'),
(96, '53', 'Models of 7-sided to 12-sided Regular Polygons', 4, 'set', 'Science'),
(97, '54', 'Linear Pair/Angle Demonstrator', 1, 'pc', 'Science'),
(99, '56', 'Manipulative Water Consumption Meter Model, blackboard', 1, 'pc', 'Science'),
(100, '57', 'Manipulative Electricity Consumption Meter Model, blackboard', 1, 'pc', 'Science'),
(101, '58', 'Storage Cabinet (for Science)', 2, 'unit', 'Science'),
(102, '59', 'Storage Cabinet (for Mathematics)', 2, 'unit', 'Math'),
(103, '60', 'Simple Anemometer', 1, 'set', 'Math'),
(104, '61', 'Magnetic Compass', 5, 'unit', 'Math'),
(106, '63', 'Aneroid Barometer, wall-mount', 1, 'unit', 'Math'),
(107, '64', 'Human Torso Model', 2, 'unit', 'Math'),
(118, '75', 'First Aid Kit', 4, 'kit', 'Math'),
(122, '79', 'Alcohol Thermometer, -20⁰C to 110⁰C', 1, 'pc', 'Math'),
(123, '80', 'Stirring Rod, Ǿ 6mm x 250mm long', 6, 'pc', 'Math'),
(124, '81', 'Alcohol Lamp/Burner, glass, 150 ml. capacity', 4, 'pc', 'Math'),
(125, '82', 'Mortar and Pestle, porcelain, 150 ml.', 4, 'set', 'Math'),
(126, '83', 'Funnel, plastic', 5, 'pc', 'Math'),
(127, '84', 'Test Tube, Ǿ 16mm x 150mm long, borosilicate', 20, 'pc', 'Math'),
(129, '86', 'Beaker, 250 ml., borosilicate', 4, 'pc', 'Math'),
(130, '87', 'Classroom Thermometer', 1, 'pc', 'Math'),
(131, '88', 'Beral Pipette, 5 ml.', 5, 'pc', 'Math'),
(132, '89', 'Graduated Cylinder, 250 ml., plastic', 5, 'pc', 'Math'),
(134, '91', 'Base Ten Blocks', 4, 'set', 'Math'),
(137, '94', 'Meterstick, plastic', 4, 'pc', 'Math'),
(141, '98', 'Geoboard, 5 x 5', 5, 'pc', 'Math'),
(144, '101', 'Circle Area Demonstrator', 1, 'pc', 'Math'),
(145, '102', 'Volume Demonstrator Set: Cylinder and Cone Volume Comparing Tool', 1, 'set', 'Math'),
(146, '103', 'Volume Demonstrator Set: Quadrangular Volume Demonstrator', 1, 'set', 'Math'),
(150, '107', 'Geostrips', 1, 'set', 'Math'),
(151, '108', 'Protractor (for student)', 22, 'pc', 'Math'),
(152, '109', 'Compass (for student)', 23, 'pc', 'Math'),
(153, '110', 'Sphere with 32 movable segments', 1, 'set', 'Math');

-- --------------------------------------------------------

--
-- Table structure for table `lr_textbooks`
--

CREATE TABLE `lr_textbooks` (
  `id` int NOT NULL,
  `lr_item` varchar(255) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `lr_subject` varchar(255) NOT NULL,
  `lr_qty` int NOT NULL DEFAULT '0',
  `lr_unit` varchar(50) DEFAULT 'pc',
  `recipient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lr_textbooks`
--

INSERT INTO `lr_textbooks` (`id`, `lr_item`, `grade_level`, `lr_subject`, `lr_qty`, `lr_unit`, `recipient`, `created_at`) VALUES
(5, 'Kindergarten Learner Material', 'Kinder', 'Kindergarten', 15, 'Pcs', 'Ylona Rizza B. Molito', '2026-08-29 14:24:45'),
(6, 'Language Book Vol 1', 'Grade I', 'Language', 25, 'Pcs', 'Arlene R. Nuevo', '2026-08-29 14:24:45'),
(7, 'Reading and Literacy Reader', 'Grade II', 'Reading and Literacy', 10, 'Pcs', 'Leah B. Balangatan', '2026-08-29 14:24:45'),
(8, 'Filipino Aklat ng Pagbasa', 'Grade III', 'Filipino', 30, 'Pcs', 'Ma. Gaudencia P. Mabini', '2026-08-29 14:24:45'),
(9, 'English Textbook for Beginners', 'Grade IV', 'English', 18, 'Pcs', 'Shella Caballero', '2026-08-29 14:24:45'),
(10, 'Mathematics Learner Module', 'Grade V', 'Mathematics', 12, 'Pcs', 'Diana M. Braga', '2026-08-29 14:24:45'),
(11, 'Science Explorer', 'Grade VI', 'Science', 20, 'Pcs', 'Mylyn A. Bernales', '2026-08-29 14:24:45'),
(12, 'Araling Panlipunan Kasaysayan', 'Grade IV', 'Araling Panlipunan', 14, 'Pcs', 'Shella Caballero', '2026-08-29 14:24:45'),
(13, 'Makabansa Workbook', 'Grade I', 'Makabansa', 22, 'Pcs', 'Arlene R. Nuevo', '2026-08-29 14:24:45'),
(14, 'GMRC Values Education', 'Grade II', 'GMRC – Good Manners and Right Conduct', 25, 'Pcs', 'Leah B. Balangatan', '2026-08-29 14:24:45'),
(15, 'EPP Pangkabuhayan Skills', 'Grade V', 'Edukasyong Pantahanan at Pangkabuhayan (EPP)', 8, 'Pcs', 'Diana M. Braga', '2026-08-29 14:24:45'),
(16, 'MAPEH Arts and Music', 'Grade VI', 'MAPEH', 16, 'Pcs', 'Mylyn A. Bernales', '2026-08-29 14:24:45');

-- --------------------------------------------------------

--
-- Table structure for table `nonconsumable`
--

CREATE TABLE `nonconsumable` (
  `id` int NOT NULL,
  `trans_code` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `item_type` varchar(255) NOT NULL,
  `property_number` varchar(100) NOT NULL,
  `unit_of_measure` varchar(50) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qty_property_card` int NOT NULL DEFAULT '0',
  `qty_physical_count` int NOT NULL DEFAULT '0',
  `shortage_overage_qty` int NOT NULL DEFAULT '0',
  `shortage_overage_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remarks` text,
  `recepient` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nonconsumable`
--

INSERT INTO `nonconsumable` (`id`, `trans_code`, `description`, `item_type`, `property_number`, `unit_of_measure`, `unit_cost`, `total_cost`, `qty_property_card`, `qty_physical_count`, `shortage_overage_qty`, `shortage_overage_value`, `remarks`, `recepient`, `created_at`) VALUES
(5, '2026-08-0001', 'Lapel', 'ICT EQUIPMENT', '123', 'Unit', 0.00, 0.00, 1, 0, 0, 0.00, 'Working', 'Leah B. Balangatan', '2026-08-14 13:41:31'),
(7, '2026-08-0002', 'Laptop Acer', 'ICT EQUIPMENT', '456', 'Unit', 2.00, 2.00, 2, 2, 0, 0.00, 'Working', 'Ma. Gaudencia P. Mabini', '2026-08-14 13:45:59'),
(8, '2026-08-0003', 'Arm Chair', 'FURNITURE & FIXTURES', '789', 'Pcs', 50000.00, 50000.00, 1000, 1000, 1, -0.01, 'Made of Plastic', 'Arlene R. Nuevo', '2026-08-14 13:55:24'),
(9, '2026-08-0004', 'DepEd Modified Building', 'BUILDINGS', '10', 'Unit', 150000.00, 150000.00, 1, 1, 0, 0.00, 'Building 1/Kinder', 'Ylona Rizza B. Molito', '2026-08-23 14:59:52'),
(10, '2026-08-0005', 'MSi Laptop DPC Package Batch 25', 'ICT EQUIPMENT', '12', 'Set', 57000.00, 57000.00, 1, 1, 0, 0.00, 'For instructional use', 'Sherlyn A. Lara', '2026-08-23 15:25:33');

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `id` int NOT NULL,
  `position_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`id`, `position_name`) VALUES
(1, 'Teacher I'),
(2, 'Teacher II'),
(4, 'Master Teacher I'),
(7, 'Administrative Officer II'),
(8, 'Teacher III'),
(9, 'School Head');

-- --------------------------------------------------------

--
-- Table structure for table `stock_card`
--

CREATE TABLE `stock_card` (
  `id` int NOT NULL,
  `supply_code` varchar(255) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `item_unit` varchar(255) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `qty` int NOT NULL,
  `reference` varchar(255) NOT NULL,
  `recepient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_card`
--

INSERT INTO `stock_card` (`id`, `supply_code`, `item_name`, `item_unit`, `transaction_date`, `transaction_type`, `qty`, `reference`, `recepient`, `created_at`) VALUES
(11, '1', 'Epson Ink 003', 'Set', '2026-08-19', 'IN', 1, 'MOOE', 'Administrative Officer II', '2026-08-19 14:45:03'),
(12, '1', 'Epson Ink 003', 'Set', '2026-08-19', 'IN', 5, 'MOOE JULY', 'Administrative Officer II', '2026-08-19 14:45:28'),
(13, '1', 'Epson Ink 003', 'Set', '2026-08-19', 'OUT', 1, 'RIS No. 2026-8-001', 'Arlene R. Nuevo', '2026-08-19 14:51:52'),
(14, '1', 'Epson Ink 003', 'Set', '2026-08-19', 'OUT', 1, 'RIS No. 2026-8-002', 'Diana M. Braga', '2026-08-19 14:51:52'),
(15, '1', 'Epson Ink 003', 'Set', '2026-08-19', 'OUT', 1, 'RIS No. 2026-8-003', 'Ian Kevin Tuazon', '2026-08-19 14:51:52'),
(16, '2', 'Bond Paper A4', 'Box', '2026-08-19', 'IN', 5, 'MOOE', 'Administrative Officer II', '2026-08-19 14:52:43'),
(17, '2', 'Bond Paper A4', 'Box', '2026-08-19', 'IN', 5, 'MOOE', 'Administrative Officer II', '2026-08-19 14:52:58'),
(18, '3', 'Ballpen - Black', 'Box', '2026-08-19', 'IN', 5, 'Donation', 'Administrative Officer II', '2026-08-19 14:59:54'),
(19, '3', 'Ballpen - Black', 'Box', '2026-08-19', 'IN', 5, 'MOOE', 'Administrative Officer II', '2026-08-19 15:00:24'),
(20, '2', 'Bond Paper A4', 'Box', '2026-08-21', 'OUT', 5, 'RIS No. 2026-8-004', 'Ma. Gaudencia P. Mabini', '2026-08-21 14:34:09'),
(21, '2', 'Bond Paper A4', 'Box', '2026-08-21', 'OUT', 5, 'RIS No. 2026-8-005', 'Mylyn A. Bernales', '2026-08-21 14:34:09'),
(22, '2', 'Bond Paper A4', 'Box', '2026-08-21', 'IN', 4, 'Donation', 'Administrative Officer II', '2026-08-21 14:34:43'),
(23, '1', 'Epson Ink 003', 'Set', '2026-08-21', 'IN', 2, 'DOnation', 'Administrative Officer II', '2026-08-21 14:41:01'),
(24, '1', 'Epson Ink 003', 'Set', '2026-08-21', 'IN', 5, 'DOnation', 'Administrative Officer II', '2026-08-21 14:41:22'),
(25, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'IN', 500, 'MOOE August 2026', 'Administrative Officer II', '2026-08-21 14:43:30'),
(26, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-006', 'Arlene R. Nuevo', '2026-08-21 14:44:10'),
(27, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-007', 'Diana M. Braga', '2026-08-21 14:44:10'),
(28, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-008', 'Ian Kevin Tuazon', '2026-08-21 14:44:10'),
(29, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-009', 'Leah B. Balangatan', '2026-08-21 14:44:10'),
(30, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-010', 'Ma. Gaudencia P. Mabini', '2026-08-21 14:44:10'),
(31, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-011', 'Mylyn A. Bernales', '2026-08-21 14:44:10'),
(32, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-012', 'Roselle U. Gayamat', '2026-08-21 14:44:10'),
(33, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-013', 'Shella Caballero', '2026-08-21 14:44:10'),
(34, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-014', 'Sherlyn A. Lara', '2026-08-21 14:44:10'),
(35, '4', 'Brown Envelope Long', 'Pcs', '2026-08-21', 'OUT', 13, 'RIS No. 2026-8-015', 'Ylona Rizza B. Molito', '2026-08-21 14:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `supplies`
--

CREATE TABLE `supplies` (
  `id` int NOT NULL,
  `supply_code` varchar(255) NOT NULL,
  `supply_name` varchar(255) NOT NULL,
  `supply_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `supply_category` varchar(255) NOT NULL,
  `supply_qty` int NOT NULL,
  `reference` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplies`
--

INSERT INTO `supplies` (`id`, `supply_code`, `supply_name`, `supply_unit`, `supply_category`, `supply_qty`, `reference`) VALUES
(7, '1', 'Epson Ink 003', 'Set', 'Consumable Supply', 10, 'MOOE'),
(8, '2', 'Bond Paper A4', 'Box', 'Consumable Supply', 4, 'MOOE'),
(9, '3', 'Ballpen - Black', 'Box', 'Consumable Supply', 10, 'Donation'),
(10, '4', 'Brown Envelope Long', 'Pcs', 'Consumable Supply', 370, 'MOOE August 2026');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_log`
--

CREATE TABLE `transaction_log` (
  `id` int NOT NULL,
  `trans_code` varchar(255) NOT NULL,
  `supply_code` varchar(255) NOT NULL,
  `supply_name` varchar(255) NOT NULL,
  `supply_unit` varchar(255) NOT NULL,
  `supply_qty` int NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `emp_email` varchar(255) NOT NULL,
  `release_by` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaction_log`
--

INSERT INTO `transaction_log` (`id`, `trans_code`, `supply_code`, `supply_name`, `supply_unit`, `supply_qty`, `emp_name`, `emp_email`, `release_by`, `created_at`) VALUES
(22, '2026-8-001', '1', 'Epson Ink 003', 'Set', 1, 'Arlene R. Nuevo', 'arlene.nuevo@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-19 14:51:52'),
(23, '2026-8-002', '1', 'Epson Ink 003', 'Set', 1, 'Diana M. Braga', 'diana.braga@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-19 14:51:52'),
(24, '2026-8-003', '1', 'Epson Ink 003', 'Set', 1, 'Ian Kevin Tuazon', 'iankevinmendova@gmail.com', 'Ian Kevin Mendova', '2026-08-19 14:51:52'),
(25, '2026-8-004', '2', 'Bond Paper A4', 'Box', 5, 'Ma. Gaudencia P. Mabini', 'magaudencia.mabini@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:34:09'),
(26, '2026-8-005', '2', 'Bond Paper A4', 'Box', 5, 'Mylyn A. Bernales', 'mylyn.bernales@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:34:09'),
(27, '2026-8-006', '4', 'Brown Envelope Long', 'Pcs', 13, 'Arlene R. Nuevo', 'arlene.nuevo@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(28, '2026-8-007', '4', 'Brown Envelope Long', 'Pcs', 13, 'Diana M. Braga', 'diana.braga@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(29, '2026-8-008', '4', 'Brown Envelope Long', 'Pcs', 13, 'Ian Kevin Tuazon', 'iankevinmendova@gmail.com', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(30, '2026-8-009', '4', 'Brown Envelope Long', 'Pcs', 13, 'Leah B. Balangatan', 'leah.balangatan@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(31, '2026-8-010', '4', 'Brown Envelope Long', 'Pcs', 13, 'Ma. Gaudencia P. Mabini', 'magaudencia.mabini@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(32, '2026-8-011', '4', 'Brown Envelope Long', 'Pcs', 13, 'Mylyn A. Bernales', 'mylyn.bernales@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(33, '2026-8-012', '4', 'Brown Envelope Long', 'Pcs', 13, 'Roselle U. Gayamat', 'roselle.gayamat@deped.gov.ph', 'Ian Kevin Mendova', '2026-08-21 14:44:10'),
(34, '2026-8-013', '4', 'Brown Envelope Long', 'Pcs', 13, 'Shella Caballero', 'shella.caballero@deped.gov.ph', 'Ian Kevin Mendova', '2026-09-21 14:44:10'),
(35, '2026-8-014', '4', 'Brown Envelope Long', 'Pcs', 13, 'Sherlyn A. Lara', 'sherlyn.lara@deped.gov.ph', 'Ian Kevin Mendova', '2026-09-21 14:44:10'),
(36, '2026-8-015', '4', 'Brown Envelope Long', 'Pcs', 13, 'Ylona Rizza B. Molito', 'ylonarizza.basada@deped.gov.ph', 'Ian Kevin Mendova', '2026-09-21 14:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `unit_measure`
--

CREATE TABLE `unit_measure` (
  `id` int NOT NULL,
  `unit_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `unit_measure`
--

INSERT INTO `unit_measure` (`id`, `unit_name`) VALUES
(3, 'Box'),
(4, 'Ream'),
(5, 'Pc'),
(6, 'Pcs'),
(7, 'Set'),
(8, 'Unit');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_id` (`emp_id`);

--
-- Indexes for table `lr_sme`
--
ALTER TABLE `lr_sme`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lr_textbooks`
--
ALTER TABLE `lr_textbooks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nonconsumable`
--
ALTER TABLE `nonconsumable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_card`
--
ALTER TABLE `stock_card`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplies`
--
ALTER TABLE `supplies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supply_code` (`supply_code`),
  ADD UNIQUE KEY `supply_name` (`supply_name`);

--
-- Indexes for table `transaction_log`
--
ALTER TABLE `transaction_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unit_measure`
--
ALTER TABLE `unit_measure`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lr_sme`
--
ALTER TABLE `lr_sme`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `lr_textbooks`
--
ALTER TABLE `lr_textbooks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `nonconsumable`
--
ALTER TABLE `nonconsumable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stock_card`
--
ALTER TABLE `stock_card`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `supplies`
--
ALTER TABLE `supplies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transaction_log`
--
ALTER TABLE `transaction_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `unit_measure`
--
ALTER TABLE `unit_measure`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
