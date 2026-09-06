-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: inventory_sys_db
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `inventory_sys_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `inventory_sys_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `inventory_sys_db`;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'Admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Ian Kevin Mendova','admin','$2y$10$Ovz.c9IKW.0q7cg3QhE/mu9mDBd7SDyPpMLfbPNk7IXjS4AYKUSVq','Admin','2026-08-10 14:03:39');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee`
--

DROP TABLE IF EXISTS `employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(50) NOT NULL,
  `emp_name` varchar(100) NOT NULL,
  `emp_position` varchar(100) NOT NULL,
  `emp_email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_id` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee`
--

LOCK TABLES `employee` WRITE;
/*!40000 ALTER TABLE `employee` DISABLE KEYS */;
INSERT INTO `employee` VALUES (5,'10','Ian Kevin Tuazon','Administrative Officer II','iankevinmendova@gmail.com'),(7,'5','Shella Caballero','Teacher II','shella.caballero@deped.gov.ph'),(8,'1','Ylona Rizza B. Molito','Teacher II','ylonarizza.basada@deped.gov.ph'),(9,'2','Arlene R. Nuevo','Teacher III','arlene.nuevo@deped.gov.ph'),(10,'3','Leah B. Balangatan','Teacher III','leah.balangatan@deped.gov.ph'),(11,'4','Ma. Gaudencia P. Mabini','Teacher III','magaudencia.mabini@deped.gov.ph'),(12,'6','Diana M. Braga','Teacher III','diana.braga@deped.gov.ph'),(13,'7','Mylyn A. Bernales','Master Teacher I','mylyn.bernales@deped.gov.ph'),(14,'8','Sherlyn A. Lara','Teacher III','sherlyn.lara@deped.gov.ph'),(15,'9','Roselle U. Gayamat','School Head','roselle.gayamat@deped.gov.ph');
/*!40000 ALTER TABLE `employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lr_sme`
--

DROP TABLE IF EXISTS `lr_sme`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lr_sme` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lr_code` varchar(50) NOT NULL,
  `lr_item` varchar(255) NOT NULL,
  `lr_qty` int NOT NULL DEFAULT '0',
  `lr_unit` varchar(50) DEFAULT NULL,
  `lr_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lr_sme`
--

LOCK TABLES `lr_sme` WRITE;
/*!40000 ALTER TABLE `lr_sme` DISABLE KEYS */;
INSERT INTO `lr_sme` VALUES (2,'2','Magnetic Board with magnetic strips',2,'set','Science'),(4,'4','Fraction Set',5,'set','Math'),(6,'6','Place Value Chart',4,'set','Math'),(7,'7','Storage Cabinet (for Science), steel',2,'unit','Science'),(8,'8','Storage Cabinet (for Math), steel',2,'unit','Math'),(9,'9','Double-pan Balance, 500-gram capacity',4,'unit','Science'),(11,'11','Connecting Wires with bulb & socket assembly 250mm long Connecting Wire w/ crocodile clips',7,'pc','Science'),(12,'12','Connecting Wires with bulb & socket assembly Bulb and Socket assembly',4,'set','Science'),(13,'13','Dry Cell Holder, 1 chamber, for size D dry cell',1,'pc','Science'),(14,'14','Toy Car, non-friction, non-battery',1,'pc','Science'),(15,'15','Hand Magnifying Lens, 5x',6,'pc','Science'),(16,'16','Pair of Bar Magnets',4,'pair','Science'),(17,'17','Weighing Scale, bathroom-type',2,'unit','Science'),(18,'18','Beral Pipette, 5 mL',5,'pc','Science'),(20,'20','Human Ear Model',1,'pc','Science'),(21,'21','Human Nose Model',1,'pc','Science'),(24,'24','Human Torso Model (miniature-type)',2,'pc','Science'),(26,'26','Set of Measuring Cups and Spoons',2,'set','Science'),(28,'28','Plastic Ruler, 12 inches or 30 cm',1,'pc','Science'),(29,'29','Digital Clock, tabletop',1,'unit','Math'),(30,'30','Beads, Ø16mm',42,'pc','Math'),(31,'31','Weighing Scale, analog, 5 kg. capacity',1,'unit','Math'),(32,'32','Weighing Scale, 1 kg. capacity',1,'pc','Math'),(34,'34','Square Tiles, 2.54 x 2.54cm, plastic',96,'pc','Math'),(35,'35','Pattern Blocks, 250 pcs/set',10,'set','Math'),(36,'36','Cuisenaire Rods/Number Sticks, 250 pcs/set',10,'set','Math'),(38,'38','Demonstration Clock',1,'pc','Math'),(39,'39','Measuring Cup, 250 mL capacity, w/ graduations',5,'pc','Math'),(42,'42','Basic 3-Dimensional Models',1,'set','Math'),(43,'43','Geoboard, 11 x 11',4,'pc','Math'),(54,'11','250mm long Connecting Wire w/ crocodile clips',7,'pc','Science'),(55,'12','Bulb and Socket assembly',4,'set','Science'),(88,'45','Wire Gauze',3,'pc','Science'),(89,'46','Tripod',4,'pc','Science'),(90,'47','Test Tube Holder',5,'pc','Science'),(92,'49','Pulley Set: - Single Pulley',5,'set','Science'),(93,'50','Test Tube Rack',5,'pc','Science'),(94,'51','Protractor, blackboard',1,'pc','Science'),(96,'53','Models of 7-sided to 12-sided Regular Polygons',4,'set','Science'),(97,'54','Linear Pair/Angle Demonstrator',1,'pc','Science'),(99,'56','Manipulative Water Consumption Meter Model, blackboard',1,'pc','Science'),(100,'57','Manipulative Electricity Consumption Meter Model, blackboard',1,'pc','Science'),(101,'58','Storage Cabinet (for Science)',2,'unit','Science'),(102,'59','Storage Cabinet (for Mathematics)',2,'unit','Math'),(103,'60','Simple Anemometer',1,'set','Math'),(104,'61','Magnetic Compass',5,'unit','Math'),(106,'63','Aneroid Barometer, wall-mount',1,'unit','Math'),(107,'64','Human Torso Model',2,'unit','Math'),(118,'75','First Aid Kit',4,'kit','Math'),(122,'79','Alcohol Thermometer, -20⁰C to 110⁰C',1,'pc','Math'),(123,'80','Stirring Rod, Ǿ 6mm x 250mm long',6,'pc','Math'),(124,'81','Alcohol Lamp/Burner, glass, 150 ml. capacity',4,'pc','Math'),(125,'82','Mortar and Pestle, porcelain, 150 ml.',4,'set','Math'),(126,'83','Funnel, plastic',5,'pc','Math'),(127,'84','Test Tube, Ǿ 16mm x 150mm long, borosilicate',20,'pc','Math'),(129,'86','Beaker, 250 ml., borosilicate',4,'pc','Math'),(130,'87','Classroom Thermometer',1,'pc','Math'),(131,'88','Beral Pipette, 5 ml.',5,'pc','Math'),(132,'89','Graduated Cylinder, 250 ml., plastic',5,'pc','Math'),(134,'91','Base Ten Blocks',4,'set','Math'),(137,'94','Meterstick, plastic',4,'pc','Math'),(141,'98','Geoboard, 5 x 5',5,'pc','Math'),(144,'101','Circle Area Demonstrator',1,'pc','Math'),(145,'102','Volume Demonstrator Set: Cylinder and Cone Volume Comparing Tool',1,'set','Math'),(146,'103','Volume Demonstrator Set: Quadrangular Volume Demonstrator',1,'set','Math'),(150,'107','Geostrips',1,'set','Math'),(151,'108','Protractor (for student)',22,'pc','Math'),(152,'109','Compass (for student)',23,'pc','Math'),(153,'110','Sphere with 32 movable segments',1,'set','Math');
/*!40000 ALTER TABLE `lr_sme` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lr_textbooks`
--

DROP TABLE IF EXISTS `lr_textbooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lr_textbooks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lr_item` varchar(255) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `lr_subject` varchar(255) NOT NULL,
  `lr_qty` int NOT NULL DEFAULT '0',
  `lr_unit` varchar(50) DEFAULT 'pc',
  `recipient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `condition` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lr_textbooks`
--

LOCK TABLES `lr_textbooks` WRITE;
/*!40000 ALTER TABLE `lr_textbooks` DISABLE KEYS */;
INSERT INTO `lr_textbooks` VALUES (5,'Kindergarten Learner Material','Kinder','Kindergarten',15,'Pcs','Ylona Rizza B. Molito','','2026-08-29 14:24:45'),(6,'Language Book Vol 1','Grade I','Language',25,'Pcs','Arlene R. Nuevo','','2026-08-29 14:24:45'),(7,'Reading and Literacy Reader','Grade II','Reading and Literacy',10,'Pcs','Leah B. Balangatan','','2026-08-29 14:24:45'),(8,'Filipino Aklat ng Pagbasa','Grade III','Filipino',30,'Pcs','Ma. Gaudencia P. Mabini','','2026-08-29 14:24:45'),(9,'English Textbook for Beginners','Grade IV','English',18,'Pcs','Shella Caballero','','2026-08-29 14:24:45'),(10,'Mathematics Learner Module','Grade V','Mathematics',12,'Pcs','Diana M. Braga','','2026-08-29 14:24:45'),(11,'Science Explorer','Grade VI','Science',20,'Pcs','Mylyn A. Bernales','','2026-08-29 14:24:45'),(12,'Araling Panlipunan Kasaysayan','Grade IV','Araling Panlipunan',14,'Pcs','Shella Caballero','','2026-08-29 14:24:45'),(13,'Makabansa Workbook','Grade I','Makabansa',22,'Pcs','Arlene R. Nuevo','','2026-08-29 14:24:45'),(14,'GMRC Values Education','Grade II','GMRC – Good Manners and Right Conduct',25,'Pcs','Leah B. Balangatan','','2026-08-29 14:24:45'),(15,'EPP Pangkabuhayan Skills','Grade V','Edukasyong Pantahanan at Pangkabuhayan (EPP)',8,'Pcs','Diana M. Braga','','2026-08-29 14:24:45'),(16,'MAPEH Arts and Music','Grade VI','MAPEH',16,'Pcs','Mylyn A. Bernales','','2026-08-29 14:24:45');
/*!40000 ALTER TABLE `lr_textbooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nonconsumable`
--

DROP TABLE IF EXISTS `nonconsumable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nonconsumable` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nonconsumable`
--

LOCK TABLES `nonconsumable` WRITE;
/*!40000 ALTER TABLE `nonconsumable` DISABLE KEYS */;
INSERT INTO `nonconsumable` VALUES (5,'2026-08-0001','Lapel','ICT EQUIPMENT','123','Unit',0.00,0.00,1,0,0,0.00,'Working','Leah B. Balangatan','2026-08-14 13:41:31'),(7,'2026-08-0002','Laptop Acer','ICT EQUIPMENT','456','Unit',2.00,2.00,2,2,0,0.00,'Working','Ma. Gaudencia P. Mabini','2026-08-14 13:45:59'),(8,'2026-08-0003','Arm Chair','FURNITURE & FIXTURES','789','Pcs',50000.00,50000.00,1000,1000,1,-0.01,'Made of Plastic','Arlene R. Nuevo','2026-08-14 13:55:24'),(9,'2026-08-0004','DepEd Modified Building','BUILDINGS','10','Unit',1500000.00,150000.00,1,1,0,0.00,'Building 1/Kinder','Ylona Rizza B. Molito','2026-08-23 14:59:52'),(10,'2026-08-0005','MSi Laptop DPC Package Batch 25','ICT EQUIPMENT','12','Set',57000.00,57000.00,1,1,0,0.00,'For instructional use','Sherlyn A. Lara','2026-08-23 15:25:33'),(11,'2026-09-0001','Iphone 17 Pro Max','COMM. EQUIPMENT','0000','Unit',150000.00,150000.00,1,1,0,0.00,'For communication used','Ian Kevin Tuazon','2026-09-01 12:24:18');
/*!40000 ALTER TABLE `nonconsumable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `position`
--

DROP TABLE IF EXISTS `position`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `position` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `position`
--

LOCK TABLES `position` WRITE;
/*!40000 ALTER TABLE `position` DISABLE KEYS */;
INSERT INTO `position` VALUES (1,'Teacher I'),(2,'Teacher II'),(4,'Master Teacher I'),(7,'Administrative Officer II'),(8,'Teacher III'),(9,'School Head');
/*!40000 ALTER TABLE `position` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_card`
--

DROP TABLE IF EXISTS `stock_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_card` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supply_code` varchar(255) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `item_unit` varchar(255) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `qty` int NOT NULL,
  `reference` varchar(255) NOT NULL,
  `recepient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_card`
--

LOCK TABLES `stock_card` WRITE;
/*!40000 ALTER TABLE `stock_card` DISABLE KEYS */;
INSERT INTO `stock_card` VALUES (11,'1','Epson Ink 003','Set','2026-08-19','IN',1,'MOOE','Administrative Officer II','2026-08-19 14:45:03'),(12,'1','Epson Ink 003','Set','2026-08-19','IN',5,'MOOE JULY','Administrative Officer II','2026-08-19 14:45:28'),(13,'1','Epson Ink 003','Set','2026-08-19','OUT',1,'RIS No. 2026-8-001','Arlene R. Nuevo','2026-08-19 14:51:52'),(14,'1','Epson Ink 003','Set','2026-08-19','OUT',1,'RIS No. 2026-8-002','Diana M. Braga','2026-08-19 14:51:52'),(15,'1','Epson Ink 003','Set','2026-08-19','OUT',1,'RIS No. 2026-8-003','Ian Kevin Tuazon','2026-08-19 14:51:52'),(16,'2','Bond Paper A4','Box','2026-08-19','IN',5,'MOOE','Administrative Officer II','2026-08-19 14:52:43'),(17,'2','Bond Paper A4','Box','2026-08-19','IN',5,'MOOE','Administrative Officer II','2026-08-19 14:52:58'),(18,'3','Ballpen - Black','Box','2026-08-19','IN',5,'Donation','Administrative Officer II','2026-08-19 14:59:54'),(19,'3','Ballpen - Black','Box','2026-08-19','IN',5,'MOOE','Administrative Officer II','2026-08-19 15:00:24'),(20,'2','Bond Paper A4','Box','2026-08-21','OUT',5,'RIS No. 2026-8-004','Ma. Gaudencia P. Mabini','2026-08-21 14:34:09'),(21,'2','Bond Paper A4','Box','2026-08-21','OUT',5,'RIS No. 2026-8-005','Mylyn A. Bernales','2026-08-21 14:34:09'),(22,'2','Bond Paper A4','Box','2026-08-21','IN',4,'Donation','Administrative Officer II','2026-08-21 14:34:43'),(23,'1','Epson Ink 003','Set','2026-08-21','IN',2,'DOnation','Administrative Officer II','2026-08-21 14:41:01'),(24,'1','Epson Ink 003','Set','2026-08-21','IN',5,'DOnation','Administrative Officer II','2026-08-21 14:41:22'),(25,'4','Brown Envelope Long','Pcs','2026-08-21','IN',500,'MOOE August 2026','Administrative Officer II','2026-08-21 14:43:30'),(26,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-006','Arlene R. Nuevo','2026-08-21 14:44:10'),(27,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-007','Diana M. Braga','2026-08-21 14:44:10'),(28,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-008','Ian Kevin Tuazon','2026-08-21 14:44:10'),(29,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-009','Leah B. Balangatan','2026-08-21 14:44:10'),(30,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-010','Ma. Gaudencia P. Mabini','2026-08-21 14:44:10'),(31,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-011','Mylyn A. Bernales','2026-08-21 14:44:10'),(32,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-012','Roselle U. Gayamat','2026-08-21 14:44:10'),(33,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-013','Shella Caballero','2026-08-21 14:44:10'),(34,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-014','Sherlyn A. Lara','2026-08-21 14:44:10'),(35,'4','Brown Envelope Long','Pcs','2026-08-21','OUT',13,'RIS No. 2026-8-015','Ylona Rizza B. Molito','2026-08-21 14:44:10'),(36,'1','Epson Ink 003','Set','2026-09-01','IN',110,'DOnation','Administrative Officer II','2026-09-01 11:42:53'),(37,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-016','Arlene R. Nuevo','2026-09-01 11:43:12'),(38,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-016','Arlene R. Nuevo','2026-09-01 11:43:12'),(39,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-017','Diana M. Braga','2026-09-01 11:43:12'),(40,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-017','Diana M. Braga','2026-09-01 11:43:12'),(41,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-018','Ian Kevin Tuazon','2026-09-01 11:43:12'),(42,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-018','Ian Kevin Tuazon','2026-09-01 11:43:12'),(43,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-019','Leah B. Balangatan','2026-09-01 11:43:12'),(44,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-019','Leah B. Balangatan','2026-09-01 11:43:12'),(45,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-020','Ma. Gaudencia P. Mabini','2026-09-01 11:43:12'),(46,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-020','Ma. Gaudencia P. Mabini','2026-09-01 11:43:12'),(47,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-021','Mylyn A. Bernales','2026-09-01 11:43:12'),(48,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-021','Mylyn A. Bernales','2026-09-01 11:43:12'),(49,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-022','Roselle U. Gayamat','2026-09-01 11:43:12'),(50,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-022','Roselle U. Gayamat','2026-09-01 11:43:12'),(51,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-023','Shella Caballero','2026-09-01 11:43:12'),(52,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-023','Shella Caballero','2026-09-01 11:43:12'),(53,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-024','Sherlyn A. Lara','2026-09-01 11:43:12'),(54,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-024','Sherlyn A. Lara','2026-09-01 11:43:12'),(55,'1','Epson Ink 003','Set','2026-09-01','OUT',1,'RIS No. 2026-9-025','Ylona Rizza B. Molito','2026-09-01 11:43:12'),(56,'4','Brown Envelope Long','Pcs','2026-09-01','OUT',1,'RIS No. 2026-9-025','Ylona Rizza B. Molito','2026-09-01 11:43:12'),(57,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-026','Arlene R. Nuevo','2026-09-02 12:12:26'),(58,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-026','Arlene R. Nuevo','2026-09-02 12:12:26'),(59,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-027','Diana M. Braga','2026-09-02 12:12:26'),(60,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-027','Diana M. Braga','2026-09-02 12:12:26'),(61,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-028','Ian Kevin Tuazon','2026-09-02 12:12:26'),(62,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-028','Ian Kevin Tuazon','2026-09-02 12:12:26'),(63,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-029','Arlene R. Nuevo','2026-09-02 12:13:00'),(64,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-029','Arlene R. Nuevo','2026-09-02 12:13:00'),(65,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-030','Diana M. Braga','2026-09-02 12:13:00'),(66,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-030','Diana M. Braga','2026-09-02 12:13:00'),(67,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-031','Leah B. Balangatan','2026-09-02 12:13:00'),(68,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-031','Leah B. Balangatan','2026-09-02 12:13:00'),(69,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-032','Ian Kevin Tuazon','2026-09-02 12:15:14'),(70,'2','Bond Paper A4','Box','2026-09-02','OUT',1,'RIS No. 2026-9-032','Ian Kevin Tuazon','2026-09-02 12:15:14'),(71,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-033','Roselle U. Gayamat','2026-09-02 12:15:14'),(72,'2','Bond Paper A4','Box','2026-09-02','OUT',1,'RIS No. 2026-9-033','Roselle U. Gayamat','2026-09-02 12:15:14'),(73,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-034','Arlene R. Nuevo','2026-09-02 12:16:01'),(74,'3','Ballpen - Black','Box','2026-09-02','OUT',1,'RIS No. 2026-9-034','Arlene R. Nuevo','2026-09-02 12:16:01'),(75,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-035','Diana M. Braga','2026-09-02 12:16:01'),(76,'3','Ballpen - Black','Box','2026-09-02','OUT',1,'RIS No. 2026-9-035','Diana M. Braga','2026-09-02 12:16:01'),(77,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-036','Ian Kevin Tuazon','2026-09-02 12:16:01'),(78,'3','Ballpen - Black','Box','2026-09-02','OUT',1,'RIS No. 2026-9-036','Ian Kevin Tuazon','2026-09-02 12:16:01'),(79,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-037','Roselle U. Gayamat','2026-09-02 12:17:14'),(80,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-037','Roselle U. Gayamat','2026-09-02 12:17:14'),(81,'3','Ballpen - Black','Box','2026-09-02','OUT',1,'RIS No. 2026-9-037','Roselle U. Gayamat','2026-09-02 12:17:14'),(82,'2','Bond Paper A4','Box','2026-09-02','OUT',1,'RIS No. 2026-9-037','Roselle U. Gayamat','2026-09-02 12:17:14'),(83,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',1,'RIS No. 2026-9-038','Shella Caballero','2026-09-02 12:17:14'),(84,'1','Epson Ink 003','Set','2026-09-02','OUT',1,'RIS No. 2026-9-038','Shella Caballero','2026-09-02 12:17:14'),(85,'3','Ballpen - Black','Box','2026-09-02','OUT',1,'RIS No. 2026-9-038','Shella Caballero','2026-09-02 12:17:14'),(86,'2','Bond Paper A4','Box','2026-09-02','OUT',1,'RIS No. 2026-9-038','Shella Caballero','2026-09-02 12:17:14'),(87,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-039','Arlene R. Nuevo','2026-09-02 12:18:45'),(88,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-039','Arlene R. Nuevo','2026-09-02 12:18:45'),(89,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-040','Diana M. Braga','2026-09-02 12:18:45'),(90,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-040','Diana M. Braga','2026-09-02 12:18:45'),(91,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-041','Ian Kevin Tuazon','2026-09-02 12:18:45'),(92,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-041','Ian Kevin Tuazon','2026-09-02 12:18:45'),(93,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-042','Leah B. Balangatan','2026-09-02 12:18:45'),(94,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-042','Leah B. Balangatan','2026-09-02 12:18:45'),(95,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-043','Ma. Gaudencia P. Mabini','2026-09-02 12:18:45'),(96,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-043','Ma. Gaudencia P. Mabini','2026-09-02 12:18:45'),(97,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-044','Mylyn A. Bernales','2026-09-02 12:18:45'),(98,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-044','Mylyn A. Bernales','2026-09-02 12:18:45'),(99,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-045','Roselle U. Gayamat','2026-09-02 12:18:45'),(100,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-045','Roselle U. Gayamat','2026-09-02 12:18:45'),(101,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-046','Shella Caballero','2026-09-02 12:18:45'),(102,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-046','Shella Caballero','2026-09-02 12:18:45'),(103,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-047','Sherlyn A. Lara','2026-09-02 12:18:45'),(104,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-047','Sherlyn A. Lara','2026-09-02 12:18:45'),(105,'4','Brown Envelope Long','Pcs','2026-09-02','OUT',2,'RIS No. 2026-9-048','Ylona Rizza B. Molito','2026-09-02 12:18:45'),(106,'1','Epson Ink 003','Set','2026-09-02','OUT',2,'RIS No. 2026-9-048','Ylona Rizza B. Molito','2026-09-02 12:18:45'),(107,'3','Ballpen - Black','Box','2026-09-02','IN',5,'MOOE','Administrative Officer II','2026-09-02 13:28:22');
/*!40000 ALTER TABLE `stock_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplies`
--

DROP TABLE IF EXISTS `supplies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supply_code` varchar(255) NOT NULL,
  `supply_name` varchar(255) NOT NULL,
  `supply_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `supply_qty` int NOT NULL,
  `reference` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supply_code` (`supply_code`),
  UNIQUE KEY `supply_name` (`supply_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplies`
--

LOCK TABLES `supplies` WRITE;
/*!40000 ALTER TABLE `supplies` DISABLE KEYS */;
INSERT INTO `supplies` VALUES (7,'1','Epson Ink 003','Set',82,'MOOE'),(8,'2','Bond Paper A4','Box',0,'MOOE'),(9,'3','Ballpen - Black','Box',10,'Donation'),(10,'4','Brown Envelope Long','Pcs',327,'MOOE August 2026');
/*!40000 ALTER TABLE `supplies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_log`
--

DROP TABLE IF EXISTS `transaction_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trans_code` varchar(255) NOT NULL,
  `supply_code` varchar(255) NOT NULL,
  `supply_name` varchar(255) NOT NULL,
  `supply_unit` varchar(255) NOT NULL,
  `supply_qty` int NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `emp_email` varchar(255) NOT NULL,
  `release_by` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_log`
--

LOCK TABLES `transaction_log` WRITE;
/*!40000 ALTER TABLE `transaction_log` DISABLE KEYS */;
INSERT INTO `transaction_log` VALUES (22,'2026-8-001','1','Epson Ink 003','Set',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-08-19 14:51:52'),(23,'2026-8-002','1','Epson Ink 003','Set',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-08-19 14:51:52'),(24,'2026-8-003','1','Epson Ink 003','Set',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-08-19 14:51:52'),(25,'2026-8-004','2','Bond Paper A4','Box',5,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:34:09'),(26,'2026-8-005','2','Bond Paper A4','Box',5,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:34:09'),(27,'2026-8-006','4','Brown Envelope Long','Pcs',13,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(28,'2026-8-007','4','Brown Envelope Long','Pcs',13,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(29,'2026-8-008','4','Brown Envelope Long','Pcs',13,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-08-21 14:44:10'),(30,'2026-8-009','4','Brown Envelope Long','Pcs',13,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(31,'2026-8-010','4','Brown Envelope Long','Pcs',13,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(32,'2026-8-011','4','Brown Envelope Long','Pcs',13,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(33,'2026-8-012','4','Brown Envelope Long','Pcs',13,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-08-21 14:44:10'),(34,'2026-8-013','4','Brown Envelope Long','Pcs',13,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-21 14:44:10'),(35,'2026-8-014','4','Brown Envelope Long','Pcs',13,'Sherlyn A. Lara','sherlyn.lara@deped.gov.ph','Ian Kevin Mendova','2026-09-21 14:44:10'),(36,'2026-8-015','4','Brown Envelope Long','Pcs',13,'Ylona Rizza B. Molito','ylonarizza.basada@deped.gov.ph','Ian Kevin Mendova','2026-09-21 14:44:10'),(37,'2026-9-016','1','Epson Ink 003','Set',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(38,'2026-9-016','4','Brown Envelope Long','Pcs',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(39,'2026-9-017','1','Epson Ink 003','Set',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(40,'2026-9-017','4','Brown Envelope Long','Pcs',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(41,'2026-9-018','1','Epson Ink 003','Set',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-01 11:43:12'),(42,'2026-9-018','4','Brown Envelope Long','Pcs',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-01 11:43:12'),(43,'2026-9-019','1','Epson Ink 003','Set',1,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(44,'2026-9-019','4','Brown Envelope Long','Pcs',1,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(45,'2026-9-020','1','Epson Ink 003','Set',1,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(46,'2026-9-020','4','Brown Envelope Long','Pcs',1,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(47,'2026-9-021','1','Epson Ink 003','Set',1,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(48,'2026-9-021','4','Brown Envelope Long','Pcs',1,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(49,'2026-9-022','1','Epson Ink 003','Set',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(50,'2026-9-022','4','Brown Envelope Long','Pcs',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(51,'2026-9-023','1','Epson Ink 003','Set',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(52,'2026-9-023','4','Brown Envelope Long','Pcs',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(53,'2026-9-024','1','Epson Ink 003','Set',1,'Sherlyn A. Lara','sherlyn.lara@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(54,'2026-9-024','4','Brown Envelope Long','Pcs',1,'Sherlyn A. Lara','sherlyn.lara@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(55,'2026-9-025','1','Epson Ink 003','Set',1,'Ylona Rizza B. Molito','ylonarizza.basada@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(56,'2026-9-025','4','Brown Envelope Long','Pcs',1,'Ylona Rizza B. Molito','ylonarizza.basada@deped.gov.ph','Ian Kevin Mendova','2026-09-01 11:43:12'),(57,'2026-9-026','4','Brown Envelope Long','Pcs',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:12:26'),(58,'2026-9-026','1','Epson Ink 003','Set',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:12:26'),(59,'2026-9-027','4','Brown Envelope Long','Pcs',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:12:26'),(60,'2026-9-027','1','Epson Ink 003','Set',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:12:26'),(61,'2026-9-028','4','Brown Envelope Long','Pcs',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:12:26'),(62,'2026-9-028','1','Epson Ink 003','Set',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:12:26'),(63,'2026-9-029','4','Brown Envelope Long','Pcs',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(64,'2026-9-029','1','Epson Ink 003','Set',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(65,'2026-9-030','4','Brown Envelope Long','Pcs',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(66,'2026-9-030','1','Epson Ink 003','Set',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(67,'2026-9-031','4','Brown Envelope Long','Pcs',1,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(68,'2026-9-031','1','Epson Ink 003','Set',1,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:13:00'),(69,'2026-9-032','4','Brown Envelope Long','Pcs',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:15:14'),(70,'2026-9-032','2','Bond Paper A4','Box',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:15:14'),(71,'2026-9-033','4','Brown Envelope Long','Pcs',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:15:14'),(72,'2026-9-033','2','Bond Paper A4','Box',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:15:14'),(73,'2026-9-034','4','Brown Envelope Long','Pcs',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:16:01'),(74,'2026-9-034','3','Ballpen - Black','Box',1,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:16:01'),(75,'2026-9-035','4','Brown Envelope Long','Pcs',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:16:01'),(76,'2026-9-035','3','Ballpen - Black','Box',1,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:16:01'),(77,'2026-9-036','4','Brown Envelope Long','Pcs',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:16:01'),(78,'2026-9-036','3','Ballpen - Black','Box',1,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:16:01'),(79,'2026-9-037','4','Brown Envelope Long','Pcs',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(80,'2026-9-037','1','Epson Ink 003','Set',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(81,'2026-9-037','3','Ballpen - Black','Box',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(82,'2026-9-037','2','Bond Paper A4','Box',1,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(83,'2026-9-038','4','Brown Envelope Long','Pcs',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(84,'2026-9-038','1','Epson Ink 003','Set',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(85,'2026-9-038','3','Ballpen - Black','Box',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(86,'2026-9-038','2','Bond Paper A4','Box',1,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:17:14'),(87,'2026-9-039','4','Brown Envelope Long','Pcs',2,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(88,'2026-9-039','1','Epson Ink 003','Set',2,'Arlene R. Nuevo','arlene.nuevo@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(89,'2026-9-040','4','Brown Envelope Long','Pcs',2,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(90,'2026-9-040','1','Epson Ink 003','Set',2,'Diana M. Braga','diana.braga@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(91,'2026-9-041','4','Brown Envelope Long','Pcs',2,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:18:45'),(92,'2026-9-041','1','Epson Ink 003','Set',2,'Ian Kevin Tuazon','iankevinmendova@gmail.com','Ian Kevin Mendova','2026-09-02 12:18:45'),(93,'2026-9-042','4','Brown Envelope Long','Pcs',2,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(94,'2026-9-042','1','Epson Ink 003','Set',2,'Leah B. Balangatan','leah.balangatan@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(95,'2026-9-043','4','Brown Envelope Long','Pcs',2,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(96,'2026-9-043','1','Epson Ink 003','Set',2,'Ma. Gaudencia P. Mabini','magaudencia.mabini@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(97,'2026-9-044','4','Brown Envelope Long','Pcs',2,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(98,'2026-9-044','1','Epson Ink 003','Set',2,'Mylyn A. Bernales','mylyn.bernales@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(99,'2026-9-045','4','Brown Envelope Long','Pcs',2,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(100,'2026-9-045','1','Epson Ink 003','Set',2,'Roselle U. Gayamat','roselle.gayamat@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(101,'2026-9-046','4','Brown Envelope Long','Pcs',2,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(102,'2026-9-046','1','Epson Ink 003','Set',2,'Shella Caballero','shella.caballero@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(103,'2026-9-047','4','Brown Envelope Long','Pcs',2,'Sherlyn A. Lara','sherlyn.lara@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(104,'2026-9-047','1','Epson Ink 003','Set',2,'Sherlyn A. Lara','sherlyn.lara@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(105,'2026-9-048','4','Brown Envelope Long','Pcs',2,'Ylona Rizza B. Molito','ylonarizza.basada@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45'),(106,'2026-9-048','1','Epson Ink 003','Set',2,'Ylona Rizza B. Molito','ylonarizza.basada@deped.gov.ph','Ian Kevin Mendova','2026-09-02 12:18:45');
/*!40000 ALTER TABLE `transaction_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_measure`
--

DROP TABLE IF EXISTS `unit_measure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_measure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_measure`
--

LOCK TABLES `unit_measure` WRITE;
/*!40000 ALTER TABLE `unit_measure` DISABLE KEYS */;
INSERT INTO `unit_measure` VALUES (3,'Box'),(4,'Ream'),(5,'Pc'),(6,'Pcs'),(7,'Set'),(8,'Unit');
/*!40000 ALTER TABLE `unit_measure` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-06  8:18:35
