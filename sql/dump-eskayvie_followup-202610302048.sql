-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: eskayvie_followup
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `activity_plan_entries`
--

DROP TABLE IF EXISTS `activity_plan_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_plan_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activity_plan_id` int(10) unsigned NOT NULL,
  `day_of_month` tinyint(3) unsigned NOT NULL,
  `activity` text DEFAULT NULL,
  `status_id` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_plan_day` (`activity_plan_id`,`day_of_month`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `activity_plan_entries_ibfk_1` FOREIGN KEY (`activity_plan_id`) REFERENCES `activity_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_plan_entries_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_plan_entries`
--

LOCK TABLES `activity_plan_entries` WRITE;
/*!40000 ALTER TABLE `activity_plan_entries` DISABLE KEYS */;
INSERT INTO `activity_plan_entries` VALUES (1,1,1,'HBO\r\nHome Sharing',2),(5,1,30,'Meeting dengan Pak Sam',1),(7,1,11,'A\r\nB\r\nC',1);
/*!40000 ALTER TABLE `activity_plan_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_plans`
--

DROP TABLE IF EXISTS `activity_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_plans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `plan_month` tinyint(3) unsigned NOT NULL,
  `plan_year` smallint(5) unsigned NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_plan` (`username`,`plan_year`,`plan_month`),
  CONSTRAINT `activity_plans_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_plans`
--

LOCK TABLES `activity_plans` WRITE;
/*!40000 ALTER TABLE `activity_plans` DISABLE KEYS */;
INSERT INTO `activity_plans` VALUES (1,'ESK320289',10,2026,'2026-10-07 11:00:45',NULL);
/*!40000 ALTER TABLE `activity_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bc_items`
--

DROP TABLE IF EXISTS `bc_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bc_items` (
  `no` bigint(20) NOT NULL AUTO_INCREMENT,
  `id` char(36) NOT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `kind` enum('folder','file','link') NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `modified_at` varchar(30) NOT NULL,
  `owner` varchar(120) NOT NULL,
  `status` enum('Approved','In review','Ready') NOT NULL DEFAULT 'Approved',
  `starred` tinyint(1) NOT NULL DEFAULT 0,
  `shared` tinyint(1) NOT NULL DEFAULT 0,
  `stored_name` varchar(255) DEFAULT NULL,
  `url` varchar(2048) DEFAULT NULL,
  `thumbnail_url` varchar(2048) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bc_items_no_unique` (`no`),
  UNIQUE KEY `items_parent_name_idx` (`parent_id`,`name`),
  KEY `items_modified_idx` (`modified_at`),
  KEY `items_starred_idx` (`starred`),
  KEY `items_shared_idx` (`shared`),
  CONSTRAINT `items_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `bc_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bc_items`
--

LOCK TABLES `bc_items` WRITE;
/*!40000 ALTER TABLE `bc_items` DISABLE KEYS */;
INSERT INTO `bc_items` VALUES (1,'00000000-0000-4000-8000-000000000001',NULL,'Bootcamp','folder',NULL,NULL,'2026-10-27T01:33:17Z','System','Approved',0,0,NULL,NULL,NULL,NULL),(2,'00000000-0000-4000-8000-000000000002','00000000-0000-4000-8000-000000000001','Session 1','folder',NULL,NULL,'2026-10-14T13:30:12Z','System','Approved',0,0,NULL,NULL,NULL,NULL),(3,'00000000-0000-4000-8000-000000000003','00000000-0000-4000-8000-000000000001','Session 2','folder',NULL,NULL,'2026-10-14T13:30:12Z','System','In review',0,0,NULL,NULL,NULL,NULL),(4,'00000000-0000-4000-8000-000000000004','00000000-0000-4000-8000-000000000001','Session 3','folder',NULL,NULL,'2026-10-14T13:30:12Z','System','In review',0,0,NULL,NULL,NULL,NULL),(5,'00000000-0000-4000-8000-000000000005','00000000-0000-4000-8000-000000000001','Session 4','folder',NULL,NULL,'2026-10-14T13:30:12Z','System','In review',0,0,NULL,NULL,NULL,NULL),(6,'00000000-0000-4000-8000-000000000006','00000000-0000-4000-8000-000000000001','Session 5','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',''),(7,'00000000-0000-4000-8000-000000000007','00000000-0000-4000-8000-000000000001','Session 6','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(8,'00000000-0000-4000-8000-000000000008','00000000-0000-4000-8000-000000000001','Session 7','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(9,'00000000-0000-4000-8000-000000000009','00000000-0000-4000-8000-000000000001','Session 8','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(10,'00000000-0000-4000-8000-000000000010','00000000-0000-4000-8000-000000000001','Session 9','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(11,'00000000-0000-4000-8000-000000000011','00000000-0000-4000-8000-000000000001','Session 10','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(12,'00000000-0000-4000-8000-000000000012','00000000-0000-4000-8000-000000000001','Session 11','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(13,'00000000-0000-4000-8000-000000000013','00000000-0000-4000-8000-000000000001','Session 12','folder','',NULL,'2026-10-14T13:30:12Z','System','In review',0,0,'','','',NULL),(14,'00000000-0000-4000-8000-000000000101','00000000-0000-4000-8000-000000000001','Quarterly account statement — Q3 2026.pdf','file','application/pdf',1887436,'2026-10-15T13:30:12.041000Z','Alex Morgan','In review',1,0,NULL,NULL,NULL,NULL),(15,'00000000-0000-4000-8000-000000000102','00000000-0000-4000-8000-000000000001','Interest rates — October 2026.xlsx','file','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',700416,'2026-10-10T13:30:12.041000Z','Taylor Reed','In review',0,0,NULL,NULL,NULL,NULL),(16,'00000000-0000-4000-8000-000000000103','00000000-0000-4000-8000-000000000001','Monthly transaction summary.csv','file','text/csv',421888,'2026-10-07T13:30:12.041000Z','Jordan Lee','In review',0,0,NULL,NULL,NULL,NULL),(17,'00000000-0000-4000-8000-000000000104','00000000-0000-4000-8000-000000000001','Customer onboarding checklist.docx','file','application/vnd.openxmlformats-officedocument.wordprocessingml.document',253952,'2026-10-14T13:30:12.041000Z','Priya Shah','In review',0,1,NULL,NULL,NULL,NULL),(18,'00000000-0000-4000-8000-000000000105','00000000-0000-4000-8000-000000000002','Mortgage application guide.pdf','file','application/pdf',2202010,'2026-10-14T13:30:12.041000Z','Jordan Lee','In review',0,0,NULL,NULL,NULL,NULL),(19,'00000000-0000-4000-8000-000000000106','00000000-0000-4000-8000-000000000002','Loan servicing procedures.docx','file','application/vnd.openxmlformats-officedocument.wordprocessingml.document',546816,'2026-10-11T13:30:12.041000Z','Alex Morgan','In review',0,0,NULL,NULL,NULL,NULL),(20,'00000000-0000-4000-8000-000000000107','00000000-0000-4000-8000-000000000002','Small business lending criteria.xlsx','file','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',1468006,'2026-10-09T13:30:12.041000Z','Priya Shah','In review',0,0,NULL,NULL,NULL,NULL),(21,'00000000-0000-4000-8000-000000000108','00000000-0000-4000-8000-000000000003','Customer data handling policy.pdf','file','application/pdf',942080,'2026-10-12T13:30:12.041000Z','Priya Shah','In review',1,0,NULL,NULL,NULL,NULL),(22,'00000000-0000-4000-8000-000000000109','00000000-0000-4000-8000-000000000003','Anti-money laundering overview.pdf','file','application/pdf',1258291,'2026-10-10T13:30:12.041000Z','Priya Shah','In review',0,1,NULL,NULL,NULL,NULL),(23,'00000000-0000-4000-8000-000000000110','00000000-0000-4000-8000-000000000003','Annual compliance training.docx','file','application/vnd.openxmlformats-officedocument.wordprocessingml.document',333824,'2026-10-07T13:30:12.041000Z','Alex Morgan','In review',0,0,NULL,NULL,NULL,NULL),(24,'00000000-0000-4000-8000-000000000111','00000000-0000-4000-8000-000000000004','New checking account campaign.pptx','file','application/vnd.openxmlformats-officedocument.presentationml.presentation',5872640,'2026-10-09T13:30:12.041000Z','Taylor Reed','In review',0,0,NULL,NULL,NULL,NULL),(25,'00000000-0000-4000-8000-000000000112','00000000-0000-4000-8000-000000000004','Brand guidelines 2026.pdf','file','application/pdf',3565158,'2026-10-08T13:30:12.041000Z','Taylor Reed','In review',0,0,NULL,NULL,NULL,NULL),(26,'00000000-0000-4000-8000-000000000113','00000000-0000-4000-8000-000000000004','October newsletter draft.docx','file','application/vnd.openxmlformats-officedocument.wordprocessingml.document',798720,'2026-10-06T13:30:12.041000Z','Jordan Lee','In review',0,0,NULL,NULL,NULL,NULL),(27,'00000000-0000-4000-8000-000000000114','00000000-0000-4000-8000-000000000001','Wire transfer guide.pdf','file','application/pdf',1153433,'2026-10-09T13:30:12.041000Z','Alex Morgan','In review',0,0,NULL,NULL,NULL,NULL),(28,'00000000-0000-4000-8000-000000000115','00000000-0000-4000-8000-000000000001','Branch performance report.xlsx','file','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',3355443,'2026-10-13T13:30:12.041000Z','Jordan Lee','In review',0,0,NULL,NULL,NULL,NULL),(29,'1',NULL,'Eskayvie_Warrior_Bootcamp_2026_ADVAN_Sponsorship_15.jpeg','file','image/jpeg',1,'2026-10-14T13:30:12Z','System','Ready',0,0,NULL,NULL,NULL,NULL),(30,'1fdae9cc-353a-4827-8be4-d4fe1bfd53a8','00000000-0000-4000-8000-000000000001','pangeran mangkubumi','link',NULL,NULL,'2026-10-26T14:30:17Z','ESK320289','In review',0,0,NULL,'https://www.youtube.com/watch?v=LN05WIg6Eo4&feature=youtu.be',NULL,NULL),(31,'b71a49b7-dae0-4eb3-a789-2361bd9e5e1d','00000000-0000-4000-8000-000000000002','[M-PLAN] Entrepreneurship Program [Repaired].pdf','file','application/pdf',2437547,'2026-10-26T14:32:18Z','ESK320289','Approved',0,0,'e6562485-513b-4801-a45f-97f88851a1be.pdf',NULL,NULL,NULL),(32,'b7ac4d94-fa1c-4fbd-82d7-563f76bd0d4f','00000000-0000-4000-8000-000000000002','Eskayvie_Warrior_Bootcamp_2026_ADVAN_Sponsorship.pdf','file','application/pdf',802741,'2026-10-26T14:31:31Z','ESK320289','Approved',0,0,'bb0eaf72-fa2f-400b-be0e-f12fc5f9e884.pdf',NULL,NULL,NULL),(33,'c5da5961-dd0d-4eef-a7c1-c6572d6196d6','00000000-0000-4000-8000-000000000002','Eskayvie_Warrior_Bootcamp_2026_ADVAN_Sponsorship_1.jpeg','file','image/jpeg',728399,'2026-10-26T14:27:59Z','ESK320289','Approved',0,0,'42b043e7-e29d-4533-bf8b-73bd9ea354a5.jpeg',NULL,NULL,NULL);
/*!40000 ALTER TABLE `bc_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bc_notifications`
--

DROP TABLE IF EXISTS `bc_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bc_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bc_notifications_user_created_idx` (`user_id`,`created_at`),
  KEY `bc_notifications_user_read_idx` (`user_id`,`read_at`),
  KEY `bc_notifications_item_fk` (`item_id`),
  CONSTRAINT `bc_notifications_item_fk` FOREIGN KEY (`item_id`) REFERENCES `bc_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bc_notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bc_notifications`
--

LOCK TABLES `bc_notifications` WRITE;
/*!40000 ALTER TABLE `bc_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `bc_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_products`
--

DROP TABLE IF EXISTS `customer_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_products` (
  `customer_id` int(10) unsigned NOT NULL,
  `product_code` varchar(20) NOT NULL,
  PRIMARY KEY (`customer_id`,`product_code`),
  KEY `product_code` (`product_code`),
  CONSTRAINT `customer_products_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_products_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_products`
--

LOCK TABLES `customer_products` WRITE;
/*!40000 ALTER TABLE `customer_products` DISABLE KEYS */;
INSERT INTO `customer_products` VALUES (2,'P001'),(2,'P002');
/*!40000 ALTER TABLE `customer_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone_number` varchar(40) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_customers_user_name` (`username`,`name`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (2,'ESK320289','Hilal','08180','1998-11-10',NULL,NULL,'2026-10-07 14:35:47',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_task_entries`
--

DROP TABLE IF EXISTS `daily_task_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_task_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `month_record_id` int(10) unsigned NOT NULL,
  `day_of_month` tinyint(3) unsigned NOT NULL,
  `learn` tinyint(1) NOT NULL DEFAULT 0,
  `video_onboarding` tinyint(1) NOT NULL DEFAULT 0,
  `consume_product` tinyint(1) NOT NULL DEFAULT 0,
  `repeat_order` tinyint(1) NOT NULL DEFAULT 0,
  `attend_zoom` tinyint(1) NOT NULL DEFAULT 0,
  `contact_upline` tinyint(1) NOT NULL DEFAULT 0,
  `sponsoring` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_task_day` (`month_record_id`,`day_of_month`),
  CONSTRAINT `daily_task_entries_ibfk_1` FOREIGN KEY (`month_record_id`) REFERENCES `daily_task_months` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_task_entries`
--

LOCK TABLES `daily_task_entries` WRITE;
/*!40000 ALTER TABLE `daily_task_entries` DISABLE KEYS */;
INSERT INTO `daily_task_entries` VALUES (3,3,1,1,0,0,0,0,0,0),(4,3,2,0,0,1,0,0,0,0),(5,3,3,0,1,0,0,0,0,0);
/*!40000 ALTER TABLE `daily_task_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_task_months`
--

DROP TABLE IF EXISTS `daily_task_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_task_months` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `task_month` tinyint(3) unsigned NOT NULL,
  `task_year` smallint(5) unsigned NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_daily_task_month` (`username`,`task_year`,`task_month`),
  CONSTRAINT `daily_task_months_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_task_months`
--

LOCK TABLES `daily_task_months` WRITE;
/*!40000 ALTER TABLE `daily_task_months` DISABLE KEYS */;
INSERT INTO `daily_task_months` VALUES (3,'ESK320289',1,2026,'2026-10-07 19:52:55',NULL);
/*!40000 ALTER TABLE `daily_task_months` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `followups`
--

DROP TABLE IF EXISTS `followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `followups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `member_code` varchar(20) NOT NULL,
  `consume_product` tinyint(1) NOT NULL DEFAULT 0,
  `product_name` varchar(100) DEFAULT NULL,
  `consume_date` date DEFAULT NULL,
  `prospect_list` tinyint(1) NOT NULL DEFAULT 0,
  `welcome_to_eskayvie` tinyint(1) NOT NULL DEFAULT 0,
  `product` tinyint(1) NOT NULL DEFAULT 0,
  `marketing_plan` tinyint(1) NOT NULL DEFAULT 0,
  `hbo` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_date` datetime DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `deleted_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_follow` (`username`,`member_code`),
  KEY `member_code` (`member_code`),
  CONSTRAINT `followups_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`),
  CONSTRAINT `followups_ibfk_2` FOREIGN KEY (`member_code`) REFERENCES `members` (`member_code`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followups`
--

LOCK TABLES `followups` WRITE;
/*!40000 ALTER TABLE `followups` DISABLE KEYS */;
INSERT INTO `followups` VALUES (1,'ESK320289','ESK107005',1,'P005','2026-10-02',1,1,1,1,0,'hhhh','2026-10-02 10:09:01','2026-10-08 11:15:30',NULL,'ESK320289','ESK320289',NULL),(18,'ESK320289','ESK478273',0,NULL,NULL,0,1,0,0,0,NULL,'2026-10-07 13:55:22',NULL,NULL,'ESK320289',NULL,NULL);
/*!40000 ALTER TABLE `followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` varchar(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `join_date` date NOT NULL,
  `rank_code` varchar(10) NOT NULL DEFAULT 'MGR',
  `upline_code` varchar(20) DEFAULT NULL,
  `sponsor_code` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_date` datetime DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `deleted_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_code` (`member_code`),
  KEY `sponsor_code` (`sponsor_code`),
  KEY `upline_code` (`upline_code`),
  KEY `rank_code` (`rank_code`),
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`rank_code`) REFERENCES `ranks` (`rank_code`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,'ESK534083','hilal','2026-09-30','MGR',NULL,NULL,'Active','2026-09-30 19:00:17',NULL,NULL,'self-signup',NULL,NULL),(2,'ESK674195','hilal002','2026-09-30','MGR',NULL,NULL,'Active','2026-09-30 19:07:46',NULL,NULL,'self-signup',NULL,NULL),(3,'ESK546107','downline001-hilal002','2026-09-30','MGR','ESK674195','ESK674195','Active','2026-09-30 19:12:53',NULL,NULL,'ESK674195',NULL,NULL),(4,'ESK140082','hilal001','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 09:55:20',NULL,NULL,'self-signup',NULL,NULL),(5,'ESK611636','downlina001','2026-10-01','MGR','ESK140082','ESK140082','Active','2026-10-01 09:56:59',NULL,NULL,'ESK140082',NULL,NULL),(6,'ESK669188','d002','2026-10-01','SMGR','ESK140082','ESK140082','Active','2026-10-01 09:57:56',NULL,NULL,'ESK140082',NULL,NULL),(7,'ESK615479','Rudy','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 10:43:27',NULL,NULL,'self-signup',NULL,NULL),(8,'ESK248430','Rudy001','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 10:44:08',NULL,NULL,'self-signup',NULL,NULL),(9,'ESK828932','hargi','2026-10-01','MGR','ESK248430','ESK248430','Active','2026-10-01 10:45:04',NULL,NULL,'ESK248430',NULL,NULL),(10,'ESK842874','andhika','2026-07-28','SMGR','ESK248430','ESK248430','Active','2026-10-01 10:47:00',NULL,NULL,'ESK248430',NULL,NULL),(11,'ESK930735','sam','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:52:26',NULL,NULL,'self-signup',NULL,NULL),(12,'ESK176225','sam001','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:54:02',NULL,NULL,'self-signup',NULL,NULL),(13,'ESK498154','hilal009','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:55:09',NULL,NULL,'self-signup',NULL,NULL),(14,'ESK770995','hilal010','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:57:31',NULL,NULL,'self-signup',NULL,NULL),(15,'ESK740625','hilal011','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:58:15',NULL,NULL,'self-signup',NULL,NULL),(16,'ESK269924','hilal012','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 15:59:12',NULL,NULL,'self-signup',NULL,NULL),(17,'ESK562924','hilal013','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:00:09',NULL,NULL,'self-signup',NULL,NULL),(18,'ESK987476','hilal014','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:16:36',NULL,NULL,'self-signup',NULL,NULL),(20,'ESK162311','hilal015','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:38:18',NULL,NULL,'self-signup',NULL,NULL),(21,'ESK779846','hilal016','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:39:30',NULL,NULL,'self-signup',NULL,NULL),(22,'ESK382444','hilal017','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:41:16',NULL,NULL,'self-signup',NULL,NULL),(23,'ESK749130','hilal018','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:42:59',NULL,NULL,'self-signup',NULL,NULL),(24,'ESK520943','sam100','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:52:16',NULL,NULL,'self-signup',NULL,NULL),(25,'ESK397713','sam101','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:53:10',NULL,NULL,'self-signup',NULL,NULL),(26,'ESK354755','sam102','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:58:14',NULL,NULL,'self-signup',NULL,NULL),(27,'ESK148698','sam103','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 16:59:08',NULL,NULL,'self-signup',NULL,NULL),(28,'ESK320289','sam105','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 17:02:07',NULL,NULL,'self-signup',NULL,NULL),(29,'ESK810613','sam110','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 17:03:22',NULL,NULL,'self-signup',NULL,NULL),(30,'ESK102771','sam115','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 17:05:19',NULL,NULL,'self-signup',NULL,NULL),(31,'ESK669954','sam111','2026-10-01','MGR',NULL,NULL,'Active','2026-10-01 17:08:09',NULL,NULL,'self-signup',NULL,NULL),(32,'ESK192054','a','2026-10-01','MBR','ESK669954','ESK669954','Active','2026-10-01 17:08:44',NULL,NULL,'ESK669954',NULL,NULL),(33,'ESK210208','b','2026-10-01','SMB','ESK669954','ESK669954','Active','2026-10-01 17:08:58',NULL,NULL,'ESK669954',NULL,NULL),(36,'ESK705500','Roslan','2026-10-02','SMB','ESK320289','ESK320289','Active','2026-10-02 10:07:55',NULL,NULL,'ESK320289',NULL,NULL),(37,'ESK478273','Roslan','2026-10-02','SMB','ESK320289','ESK320289','Active','2026-10-02 10:07:56',NULL,NULL,'ESK320289',NULL,NULL),(38,'ESK107005','Andhika','2026-10-02','MBR','ESK320289','ESK320289','Active','2026-10-02 10:08:40','2026-10-08 11:15:29',NULL,'ESK320289','ESK320289',NULL),(39,'ESK632552','Vina','2026-10-01','SMB','ESK320289','ESK320289','Active','2026-10-08 11:15:43',NULL,NULL,'ESK320289',NULL,NULL);
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_target_entries`
--

DROP TABLE IF EXISTS `monthly_target_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_target_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `monthly_target_id` int(10) unsigned NOT NULL,
  `day_of_month` tinyint(3) unsigned NOT NULL,
  `target_income` decimal(15,2) DEFAULT NULL,
  `target_recruit` int(10) unsigned DEFAULT NULL,
  `target_ro` int(10) unsigned DEFAULT NULL,
  `rank_code` varchar(10) DEFAULT NULL,
  `effort` text DEFAULT NULL,
  `status_id` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monthly_target_day` (`monthly_target_id`,`day_of_month`),
  KEY `rank_code` (`rank_code`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `monthly_target_entries_ibfk_1` FOREIGN KEY (`monthly_target_id`) REFERENCES `monthly_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_target_entries_ibfk_2` FOREIGN KEY (`rank_code`) REFERENCES `ranks` (`rank_code`),
  CONSTRAINT `monthly_target_entries_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_target_entries`
--

LOCK TABLES `monthly_target_entries` WRITE;
/*!40000 ALTER TABLE `monthly_target_entries` DISABLE KEYS */;
INSERT INTO `monthly_target_entries` VALUES (1,1,6,1000000.00,100,1400000,'DMGR','test1\r\ntset2\r\ntest 3',2),(2,1,4,NULL,NULL,NULL,'SMB',NULL,NULL),(3,1,5,NULL,NULL,NULL,'SMB',NULL,NULL),(20,1,12,5000.00,60,45,'DMGR','test',2);
/*!40000 ALTER TABLE `monthly_target_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `monthly_targets`
--

DROP TABLE IF EXISTS `monthly_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `monthly_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `target_month` tinyint(3) unsigned NOT NULL,
  `target_year` smallint(5) unsigned NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_monthly_target` (`username`,`target_year`,`target_month`),
  CONSTRAINT `monthly_targets_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `monthly_targets`
--

LOCK TABLES `monthly_targets` WRITE;
/*!40000 ALTER TABLE `monthly_targets` DISABLE KEYS */;
INSERT INTO `monthly_targets` VALUES (1,'ESK320289',10,2026,'2026-10-07 10:09:09',NULL);
/*!40000 ALTER TABLE `monthly_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `no` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(20) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_date` datetime DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `deleted_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`no`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'P001','Phytax',100.00,'Active','2026-10-07 13:34:36',NULL,NULL,NULL,NULL,NULL),(2,'P002','Nutrelle',200.00,'Active','2026-10-07 13:34:36',NULL,NULL,NULL,NULL,NULL),(3,'P003','Savva',300.00,'Active','2026-10-07 13:34:36',NULL,NULL,NULL,NULL,NULL),(4,'P004','Afeeya',400.00,'Active','2026-10-07 13:34:36',NULL,NULL,NULL,NULL,NULL),(5,'P005','Xeero',500.00,'Active','2026-10-07 13:34:36',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prospects`
--

DROP TABLE IF EXISTS `prospects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone_number` varchar(40) DEFAULT NULL,
  `domicile` varchar(120) DEFAULT NULL,
  `occupation` varchar(120) DEFAULT NULL,
  `prospect_date` date DEFAULT NULL,
  `response` enum('Baik','Kurang','Tidak Baik') DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `follow_up` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prospects_user` (`username`,`id`),
  CONSTRAINT `prospects_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prospects`
--

LOCK TABLES `prospects` WRITE;
/*!40000 ALTER TABLE `prospects` DISABLE KEYS */;
INSERT INTO `prospects` VALUES (2,'ESK320289','Prospek 001','0817','Jakarta',NULL,'2026-10-07','Baik',NULL,0,NULL,'2026-10-07 15:57:37',NULL);
/*!40000 ALTER TABLE `prospects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ranks`
--

DROP TABLE IF EXISTS `ranks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ranks` (
  `rank_code` varchar(10) NOT NULL,
  `rank_name` varchar(50) NOT NULL,
  `no` int(11) NOT NULL,
  PRIMARY KEY (`rank_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ranks`
--

LOCK TABLES `ranks` WRITE;
/*!40000 ALTER TABLE `ranks` DISABLE KEYS */;
INSERT INTO `ranks` VALUES ('CM','Crown Manager',9),('DM','Diamond Manager',8),('DMGR','Diamond Manager',6),('EMB','Elite Member',3),('GM','Gold Manager',7),('MBR','Member',1),('MGR','Manager',4),('SMB','Senior Member',2),('SMGR','Sapphire Manager',5);
/*!40000 ALTER TABLE `ranks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `side_volume_entries`
--

DROP TABLE IF EXISTS `side_volume_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `side_volume_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `side_volume_month_id` int(10) unsigned NOT NULL,
  `product_code` varchar(20) NOT NULL,
  `target_value` decimal(15,2) DEFAULT NULL,
  `actual_value` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_side_volume_product` (`side_volume_month_id`,`product_code`),
  KEY `product_code` (`product_code`),
  CONSTRAINT `side_volume_entries_ibfk_1` FOREIGN KEY (`side_volume_month_id`) REFERENCES `side_volume_months` (`id`) ON DELETE CASCADE,
  CONSTRAINT `side_volume_entries_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `side_volume_entries`
--

LOCK TABLES `side_volume_entries` WRITE;
/*!40000 ALTER TABLE `side_volume_entries` DISABLE KEYS */;
INSERT INTO `side_volume_entries` VALUES (3,25,'P004',1000.00,NULL),(4,26,'P004',200.00,NULL),(5,27,'P004',20.00,NULL),(9,32,'P004',300.00,NULL),(14,34,'P004',30.00,NULL);
/*!40000 ALTER TABLE `side_volume_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `side_volume_months`
--

DROP TABLE IF EXISTS `side_volume_months`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `side_volume_months` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `target_year` smallint(5) unsigned NOT NULL,
  `target_month` tinyint(3) unsigned NOT NULL,
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_side_volume_month` (`username`,`target_year`,`target_month`),
  CONSTRAINT `side_volume_months_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `side_volume_months`
--

LOCK TABLES `side_volume_months` WRITE;
/*!40000 ALTER TABLE `side_volume_months` DISABLE KEYS */;
INSERT INTO `side_volume_months` VALUES (25,'ESK320289',2026,1,'2026-10-07 20:05:11',NULL),(26,'ESK320289',2026,2,'2026-10-07 20:05:11',NULL),(27,'ESK320289',2026,3,'2026-10-07 20:05:11',NULL),(28,'ESK320289',2026,4,'2026-10-07 20:05:11',NULL),(29,'ESK320289',2026,5,'2026-10-07 20:05:11',NULL),(30,'ESK320289',2026,6,'2026-10-07 20:05:11',NULL),(31,'ESK320289',2026,7,'2026-10-07 20:05:11',NULL),(32,'ESK320289',2026,8,'2026-10-07 20:05:11',NULL),(33,'ESK320289',2026,9,'2026-10-07 20:05:11',NULL),(34,'ESK320289',2026,10,'2026-10-07 20:05:11',NULL),(35,'ESK320289',2026,11,'2026-10-07 20:05:11',NULL),(36,'ESK320289',2026,12,'2026-10-07 20:05:11',NULL);
/*!40000 ALTER TABLE `side_volume_months` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuses` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  `sort_order` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuses`
--

LOCK TABLES `statuses` WRITE;
/*!40000 ALTER TABLE `statuses` DISABLE KEYS */;
INSERT INTO `statuses` VALUES (1,'Not Started',1),(2,'In Progress',2),(3,'Achieved',3),(4,'Not Achieved',4),(5,'Canceled',5);
/*!40000 ALTER TABLE `statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `member_code` varchar(20) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `confirm_token` char(64) DEFAULT NULL,
  `email_confirmed_at` datetime DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `content_role` enum('Member','Supervisor','Administrator') NOT NULL DEFAULT 'Member',
  `created_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_date` datetime DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `deleted_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `member_code` (`member_code`),
  UNIQUE KEY `email` (`email`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`member_code`) REFERENCES `members` (`member_code`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'ESK534083','ESK534083','hilal@mail.com','$2y$10$JWVwJWEhqfW4kkxIRULo6emfv7Lh0fURY1h/z6mIjhXAy6IX1SSwi',NULL,'2026-09-30 19:07:19','Active','Member','2026-09-30 19:00:17','2026-10-26 20:21:37',NULL,'self-signup',NULL,NULL),(2,'ESK674195','ESK674195','hilal002@email.com','$2y$10$gHH8DJ9KDg0aOZVykx1BsegFdxxumO2g8bjZ6fJLISckpc22ZGakq',NULL,'2026-09-30 19:09:05','Active','Member','2026-09-30 19:07:46','2026-09-30 19:09:05',NULL,'self-signup',NULL,NULL),(3,'ESK140082','ESK140082','hilal001@mail.com','$2y$10$cm9FuKY6PuZcAiAO6WNLueBemALWlTeyqODpcK.Ga/CP4.MCt5LGq',NULL,'2026-10-01 09:55:27','Active','Member','2026-10-01 09:55:20','2026-10-01 09:55:27',NULL,'self-signup',NULL,NULL),(4,'ESK615479','ESK615479','rudy@email.com','$2y$10$t/ZjLf//zGSBYlydqZF.Xe27QdQT184nDHsJH1NWAPvyrcGzzKIrO','bbcea696c071bc08661c6d426fdf0a84b49ade99855cd8218b9fa704fea9ef86',NULL,'Active','Member','2026-10-01 10:43:27',NULL,NULL,'self-signup',NULL,NULL),(5,'ESK248430','ESK248430','rudy001@email.com','$2y$10$cpGfaB/YmnC0xL4fUCobxuJlzS5N/B4kMUS2IYiKnLTRvLU2/ne.u',NULL,'2026-10-01 10:44:15','Active','Member','2026-10-01 10:44:08','2026-10-01 10:44:15',NULL,'self-signup',NULL,NULL),(6,'ESK930735','ESK930735','sam@email.com','$2y$10$izmUuDk1Og0SAdYozKZFMuYn9UdGcNOH1AKyMAfi/nwEewxIjxGhq','3b07698216b632ff8412be6bac368d22c7c2832022fd171a7795b22df4b94f9f',NULL,'Active','Member','2026-10-01 15:52:26',NULL,NULL,'self-signup',NULL,NULL),(7,'ESK176225','ESK176225','sam001@email.com','$2y$10$9zeS04GpEFammHkgGYpG5uEANsL8tSPsl1uqRpfpJnYkEAOIEkQoW','16c7a5e622782e47b125ae857a3af95f087098db8c81a7c8cc6cd4ca592e6d64',NULL,'Active','Member','2026-10-01 15:54:02',NULL,NULL,'self-signup',NULL,NULL),(8,'ESK498154','ESK498154','hilal009@email.com','$2y$10$4lZBjLkQcrtNCrPUKOOkmeQLxPCELlODz4i92O/UjQhvsQ09xQIwK','f41d7b7278e2dc89fa8ee58582f5dfafcdd18c7598edb400018ec23bdea19652',NULL,'Active','Member','2026-10-01 15:55:09',NULL,NULL,'self-signup',NULL,NULL),(9,'ESK770995','ESK770995','hilal010@email.com','$2y$10$magY9/u6VfwhdOhXRVhvXuMdGZgjtMRmFxMUP59fH8CjpR.59WxH6','26f42bfa0d726f794544d4aeacb13bfde65bf46987b9891e845d6d47e338dcda',NULL,'Active','Member','2026-10-01 15:57:31',NULL,NULL,'self-signup',NULL,NULL),(10,'ESK740625','ESK740625','hilal011@email.com','$2y$10$7GrGkEHXC8A6Nylcw/1tLOrKDYAL6/alFQg/YhiQ/KjabEuh4d.Xy','a33c1428c1c3055206c9e09b8c18609d952f1eb804a30eb35996407e1aa87f34',NULL,'Active','Member','2026-10-01 15:58:15',NULL,NULL,'self-signup',NULL,NULL),(11,'ESK269924','ESK269924','hilal012@email.com','$2y$10$FgORrhEHoT0xfwlbnDj0deWuP.zU5lsy2YgRRNbM9ja3wOMj4RgLO','623070e737a3a8625973efd6bf26072fbca3e44487f2a11f6ddecffe5f63cb7e',NULL,'Active','Member','2026-10-01 15:59:12',NULL,NULL,'self-signup',NULL,NULL),(12,'ESK562924','ESK562924','hilal013@email.com','$2y$10$iPVGznCIDndEyxSQJYPDFeiHIE9ZWsKV4SUfIuCMNP25rHoWH2Pyy',NULL,'2026-10-01 16:02:21','Active','Member','2026-10-01 16:00:09','2026-10-01 16:02:21',NULL,'self-signup',NULL,NULL),(13,'ESK987476','ESK987476','hilal014@email.com','$2y$10$m0uYxB7Ti7gAWm3ba3D2mOraatB1u.l86qZWuXzHVcuBrvnQzb0DG','08c326757b4c6148ec48673d73ddc09c25f7f8191257af8d1d5b74128d6c3538',NULL,'Active','Member','2026-10-01 16:16:36',NULL,NULL,'self-signup',NULL,NULL),(15,'ESK162311','ESK162311','hilal015@email.com','$2y$10$XsyX6x519dyxnM78pceK6u.PDTfOabkstTuwIuwjCptnEmZiTEcmO','5295a7bd3e99d9de1ca4c1feeadfb6f579fa4038b2e45e4ea17a9ba286f5464d',NULL,'Active','Member','2026-10-01 16:38:19',NULL,NULL,'self-signup',NULL,NULL),(16,'ESK779846','ESK779846','hilal016@email.com','$2y$10$kamLan1nPkZrs//qMB9DkuIregD0W5ATWl5iEbwUAbUfAdT/bDMrW','2c870bd25c43361bc3ec1c038d60ad0aa87f818666652294a74add4250231f77',NULL,'Active','Member','2026-10-01 16:39:31',NULL,NULL,'self-signup',NULL,NULL),(17,'ESK382444','ESK382444','hilal017@email.com','$2y$10$idDECWoFFWOYwQqHJpZKSuZInwDUHQ0ZX5VMpcuFc0m7wjZ.6GpaS','c1a77a30ad0807693c12cc9e3eca076a1469a4bc3622a1c4809b77d1f714e5dd',NULL,'Active','Member','2026-10-01 16:41:16',NULL,NULL,'self-signup',NULL,NULL),(18,'ESK749130','ESK749130','hilal018@email.com','$2y$10$/fhCP2d1fnC.ir5hmye4QuU1/AeM7tDkcul1APpCU8XqSyZ97KVGC','b07c056ad4ee18e3de99b11ebed979908e487e6f7712ec91cdcdc39fc104488c',NULL,'Active','Member','2026-10-01 16:42:59',NULL,NULL,'self-signup',NULL,NULL),(19,'ESK520943','ESK520943','sam100@emil.com','$2y$10$zx0Ko5TBwrA0V0DjfvQGbu5R2UVWHfZV1MMINXRhF4FNvLdr5/nOO','8f29176ba9560100a95584b6b5a7b785998acda2fb38b72908b0a98021510837',NULL,'Active','Member','2026-10-01 16:52:16',NULL,NULL,'self-signup',NULL,NULL),(20,'ESK397713','ESK397713','sam101@email.com','$2y$10$vCsT7.xLq2br7gS.LUxm1.ApKiOvwUX/62lFZbs6GesseiuNLXyJm','6947aa4a0b482f941b73d7ed94bf6fb08c723d8b79ffd93da360d8dbb7c3ad88',NULL,'Active','Member','2026-10-01 16:53:10',NULL,NULL,'self-signup',NULL,NULL),(21,'ESK354755','ESK354755','sam102@email.com','$2y$10$D5m7VgzVoItVGWe.m2R2GuDDyjBp6b6wxxdNw/Z5NG5FpLwmU7cqa','af6614b6afd09ee8d873a63aa9e58040f2adf1ab6636861ddaca8ed68a2340db',NULL,'Active','Member','2026-10-01 16:58:15',NULL,NULL,'self-signup',NULL,NULL),(22,'ESK148698','ESK148698','sam103@email.com','$2y$10$mOR1q3.5Yd1o7VIzKSdec.yiNP7yAjay8e4IOEbwTa.0cSrmL5iN2','f982bd3b072131b3d1c9e767d747777ea68a957545028f761b3314da39e6db0a',NULL,'Active','Member','2026-10-01 16:59:09',NULL,NULL,'self-signup',NULL,NULL),(23,'ESK320289','ESK320289','sam105@email.com','$2y$10$oVNR1MYalvGiUWDGHU.PiOEsMNY7yQPy9Eb6mN0aTxgJR8y/2YFWu',NULL,'2026-10-01 17:02:11','Active','Administrator','2026-10-01 17:02:07','2026-10-26 21:24:20',NULL,'self-signup',NULL,NULL),(24,'ESK810613','ESK810613','sam110@email.com','$2y$10$t2gD/APQL0SUIY8QBOHv1.sCvZlNq9ISMrnCsogRdQlgdnkG7kxwy',NULL,'2026-10-01 17:03:26','Active','Member','2026-10-01 17:03:22','2026-10-01 17:03:26',NULL,'self-signup',NULL,NULL),(25,'ESK102771','ESK102771','sam115@email.com','$2y$10$4/hBVpe67fMF/ccfhq88S.DMIsixcuq744CQIDUVbW55NhBhV4PAi',NULL,'2026-10-01 17:05:26','Active','Member','2026-10-01 17:05:19','2026-10-01 17:05:26',NULL,'self-signup',NULL,NULL),(26,'ESK669954','ESK669954','sam111@email.com','$2y$10$1vzXxPBBZV958rvDmmIGeeuXz6A6Br0jB9TXWO3.oJ/lSXc4OivbO',NULL,'2026-10-01 17:08:15','Active','Member','2026-10-01 17:08:09','2026-10-01 17:08:15',NULL,'self-signup',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'eskayvie_followup'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-30 20:48:58
