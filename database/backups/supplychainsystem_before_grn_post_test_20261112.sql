-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: supplychainsystem
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `audit_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `organization_id` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_log_id`),
  KEY `fk_audit_logs_user` (`user_id`),
  KEY `idx_audit_logs_organization_created` (`organization_id`,`created_at`),
  KEY `idx_audit_logs_entity` (`entity_type`,`entity_id`),
  CONSTRAINT `fk_audit_logs_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=676 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,5,'USER_CREATED','USER',17,'Created STAFF user joy033042@example.com','2026-10-10 00:30:43'),(2,22,21,'USER_UPDATED','USER',22,'Updated rule134250@example.com: name Only Admin to Only Renamed','2026-10-10 10:42:51'),(3,23,22,'USER_UPDATED','USER',23,'Updated rule134256@example.com: name Only Admin to Only Renamed','2026-10-10 10:42:56'),(4,1,5,'BRANCH_CREATED','BRANCH',25,'Created branch Westlands 134903','2026-10-10 10:49:03'),(5,1,5,'BRANCH_UPDATED','BRANCH',25,'Updated branch Westlands 134903: location Nairobi to Mombasa','2026-10-10 10:49:03'),(6,1,5,'BRANCH_UPDATED','BRANCH',25,'Updated branch Westlands 134903: status ACTIVE to INACTIVE','2026-10-10 10:49:03'),(7,1,5,'BRANCH_CREATED','BRANCH',26,'Created branch Westlands 134950','2026-10-10 10:49:50'),(8,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Westlands 134950: location Nairobi to Mombasa','2026-10-10 10:49:51'),(9,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Westlands 134950: status ACTIVE to INACTIVE','2026-10-10 10:49:51'),(10,1,5,'ORGANIZATION_UPDATED','ORGANIZATION',5,'Updated organisation profile: KRA PIN  to A111111111A, phone  to +254 700 000 000','2026-10-10 16:00:27'),(11,1,5,'ORGANIZATION_UPDATED','ORGANIZATION',5,'Updated organisation profile: KRA PIN A111111111A to ','2026-10-10 16:00:28'),(17,4,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',2,'Acknowledged a product change','2026-10-14 14:48:13'),(18,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',2,'Acknowledged a product change','2026-10-14 14:49:31'),(22,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',1,'Acknowledged a product change','2026-10-14 16:32:29'),(23,7,2,'CHANGE_CREATED','PRODUCT_CHANGE',5,'Prepared a change for Madeira Cake: barcode 2000000000032 to 60009778786876','2026-10-15 01:36:28'),(24,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',5,'Published the change for Madeira Cake to 2 organisations','2026-10-15 01:36:42'),(25,4,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',5,'Acknowledged a product change','2026-10-15 01:37:05'),(26,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',5,'Acknowledged a product change','2026-10-15 01:37:26'),(27,7,2,'PRODUCT_UPDATED','PRODUCT',4,'Updated product mill mini short cake and replaced its image.','2026-10-15 14:16:29'),(28,7,2,'PRODUCT_UPDATED','PRODUCT',4,'Updated product mill mini short cake.','2026-10-15 14:16:37'),(29,7,2,'CHANGE_CREATED','PRODUCT_CHANGE',6,'Prepared a change for mill mini short cake: barcode 2000000000049 to 99000000001','2026-10-15 14:19:12'),(30,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',6,'Published the change for mill mini short cake to 2 organisations','2026-10-15 14:19:15'),(31,7,2,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',1,'Published product announcement for Demo Bread to 1 retailer organization(s).','2026-10-15 15:52:27'),(32,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',1,'Acknowledged receipt of product announcement #1.','2026-10-15 15:52:50'),(33,7,2,'PRODUCT_CREATED','PRODUCT',7,'Created product Mill bakers multi grain bread with initial barcode 78589494955998.','2026-10-15 16:31:11'),(34,7,2,'PRODUCT_UPDATED','PRODUCT',7,'Updated product Mill bakers multi grain bread.','2026-10-15 16:31:14'),(35,7,2,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',2,'Published product announcement for Mill bakers multi grain bread to 2 retailer organization(s).','2026-10-15 16:35:10'),(36,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',2,'Acknowledged receipt of product announcement #2.','2026-10-15 16:37:19'),(37,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',6,'Acknowledged a product change','2026-10-15 16:44:08'),(38,4,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',2,'Acknowledged receipt of product announcement #2.','2026-10-15 18:08:47'),(39,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Westlands 134950: status INACTIVE to ACTIVE','2026-10-15 21:01:48'),(40,7,2,'PRODUCT_CREATED','PRODUCT',8,'Created product Mill cookies with initial barcode 61620034567834.','2026-10-16 02:52:07'),(41,7,2,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',3,'Published product announcement for Mill cookies to 2 retailer organization(s).','2026-10-16 02:54:03'),(42,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',3,'Acknowledged receipt of product announcement #3.','2026-10-16 02:54:46'),(43,7,2,'USER_UPDATED','USER',7,'Updated manager@millbakers.local: name Mill Manager to Barry Hilton','2026-10-16 04:20:56'),(44,7,2,'USER_CREATED','USER',27,'Created STAFF user shanicemueni@millbakers.com','2026-10-16 09:33:59'),(45,27,2,'PRODUCT_CREATED','PRODUCT',9,'Created product Standard madeira with initial barcode 61620094556564.','2026-10-16 09:40:05'),(46,27,2,'PRODUCT_UPDATED','PRODUCT',9,'Updated product Standard madeira.','2026-10-16 09:44:14'),(47,27,2,'CHANGE_CREATED','PRODUCT_CHANGE',7,'Prepared a change for Standard madeira: barcode 61620094556564 to 65656565656565','2026-10-16 09:46:26'),(48,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',7,'Published the change for Standard madeira to 2 organisations','2026-10-16 09:47:05'),(49,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',7,'Acknowledged a product change','2026-10-16 09:47:37'),(50,7,2,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',4,'Published product announcement for Standard madeira to 2 retailer organization(s).','2026-10-16 09:49:07'),(51,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',4,'Acknowledged receipt of product announcement #4.','2026-10-16 09:51:53'),(52,1,5,'USER_UPDATED','USER',17,'Updated joy033042@example.com: status ACTIVE to INACTIVE','2026-10-16 09:52:44'),(53,1,5,'USER_UPDATED','USER',1,'Updated admin@target.local: no changes','2026-10-16 09:53:01'),(54,1,5,'USER_UPDATED','USER',3,'Updated manager@target.local: status ACTIVE to INACTIVE','2026-10-16 09:53:15'),(55,7,2,'PRODUCT_UPDATED','PRODUCT',3,'Updated product Madeira Cake and replaced its image.','2026-10-17 07:57:26'),(56,7,2,'USER_UPDATED','USER',7,'Updated manager@millbakers.local: name Barry Hilton to Barry Kimani','2026-10-17 08:33:03'),(57,7,2,'ORGANIZATION_UPDATED','ORGANIZATION',2,'Updated organisation profile: email  to millbakerskenya@gmail.co.ke','2026-10-17 11:23:54'),(58,9,9,'ORGANIZATION_UPDATED','ORGANIZATION',9,'Updated organisation profile: name Proctor and Gamble limitted to Proctor Gamble limitted','2026-10-17 16:44:40'),(59,1,5,'BRANCH_UPDATED','BRANCH',7,'Updated branch Main Branch: location Kenya to Nairobi Kenya','2026-10-17 16:48:17'),(60,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Westlands 134950: name Westlands 134950 to Target Supermarket','2026-10-17 16:49:08'),(61,7,2,'USER_UPDATED','USER',7,'Updated manager@millbakers.local: no changes','2026-10-18 11:44:17'),(62,2,1,'RELATIONSHIP_REQUESTED','RELATIONSHIP',6,'Requested a supply relationship with Fratres Limited Kitui','2026-10-18 15:56:23'),(63,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',6,'Accepted a supply relationship request','2026-10-18 15:56:23'),(64,2,1,'RELATIONSHIP_ENDED','RELATIONSHIP',6,'Ended a supply relationship','2026-10-18 15:56:23'),(65,2,1,'RELATIONSHIP_REQUESTED','RELATIONSHIP',7,'Requested a supply relationship with Fratres Limited Kitui','2026-10-18 22:16:22'),(66,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',7,'Accepted a supply relationship request','2026-10-18 22:21:58'),(67,28,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',7,'Acknowledged a product change','2026-10-19 05:26:00'),(68,11,11,'RELATIONSHIP_REQUESTED','RELATIONSHIP',8,'Requested a supply relationship with Target Supermarket','2026-10-19 11:28:17'),(69,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',8,'Accepted a supply relationship request','2026-10-19 11:28:39'),(70,11,11,'PRODUCT_CREATED','PRODUCT',10,'Created product Quencher lime with initial barcode 61616161616166.','2026-10-19 11:39:17'),(71,11,11,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',5,'Published product announcement for Quencher lime to 1 retailer organization(s).','2026-10-19 11:40:40'),(72,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',5,'Acknowledged receipt of product announcement #5.','2026-10-19 11:41:45'),(73,7,2,'RELATIONSHIP_REQUESTED','RELATIONSHIP',9,'Requested a supply relationship with Target Supermarket','2026-10-19 11:44:00'),(74,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',9,'Accepted a supply relationship request','2026-10-19 11:44:27'),(75,2,1,'RELATIONSHIP_ENDED','RELATIONSHIP',7,'Ended a supply relationship','2026-10-20 04:50:22'),(76,28,3,'RELATIONSHIP_REQUESTED','RELATIONSHIP',10,'Requested a supply relationship with Chandaria Industries','2026-10-20 04:50:51'),(77,2,1,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',10,'Accepted a supply relationship request','2026-10-20 04:51:31'),(78,29,26,'PRODUCT_CREATED','PRODUCT',11,'Created product Omo washing powder with initial barcode 65654664757647.','2026-10-20 06:40:30'),(79,29,26,'CHANGE_CREATED','PRODUCT_CHANGE',8,'Prepared a change for Omo washing powder: barcode 65654664757647 to 67672345345626','2026-10-20 06:41:58'),(80,29,26,'RELATIONSHIP_REQUESTED','RELATIONSHIP',11,'Requested a supply relationship with Target Supermarket','2026-10-20 06:42:37'),(81,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',11,'Accepted a supply relationship request','2026-10-20 06:43:11'),(82,29,26,'CHANGE_PUBLISHED','PRODUCT_CHANGE',8,'Published the change for Omo washing powder to 1 organisations','2026-10-20 06:44:07'),(83,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',8,'Acknowledged a product change','2026-10-20 06:45:39'),(84,29,26,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',6,'Published product announcement for Omo washing powder to 1 retailer organization(s).','2026-10-20 06:48:33'),(85,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',6,'Acknowledged receipt of product announcement #6.','2026-10-20 06:49:04'),(86,29,26,'USER_CREATED','USER',30,'Created STAFF user jacobochieng@uniliver.co.ke','2026-10-20 06:52:33'),(87,29,26,'BRANCH_CREATED','BRANCH',31,'Created branch uniliver','2026-10-20 06:55:11'),(88,29,26,'ORGANIZATION_UPDATED','ORGANIZATION',26,'Updated organisation profile: KRA PIN  to A123456789Z, email  to uniliverkenya@gmail.co.ke, phone  to 073462783843, address  to Thika mombasa rd','2026-10-20 06:57:35'),(89,31,27,'PRODUCT_CREATED','PRODUCT',12,'Created product Mint chocolate with initial barcode 68686868668868.','2026-10-20 08:10:22'),(90,31,27,'RELATIONSHIP_REQUESTED','RELATIONSHIP',12,'Requested a supply relationship with Fratres Limited Kitui','2026-10-20 08:12:01'),(91,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',12,'Accepted a supply relationship request','2026-10-20 08:12:19'),(92,31,27,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',7,'Published product announcement for Mint chocolate to 2 retailer organization(s).','2026-10-20 08:14:14'),(93,28,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',7,'Acknowledged receipt of product announcement #7.','2026-10-20 08:15:37'),(94,31,27,'USER_CREATED','USER',32,'Created STAFF user abimwanikicadbury@gmail.com','2026-10-20 08:18:39'),(95,31,27,'BRANCH_CREATED','BRANCH',33,'Created branch Cadbury','2026-10-20 08:19:23'),(96,31,27,'ORGANIZATION_UPDATED','ORGANIZATION',27,'Updated organisation profile: email  to cadburylimited@gmail.co.ke, phone  to 075677464785','2026-10-20 08:21:21'),(97,31,27,'USER_CREATED','USER',33,'Created STAFF user sussankihikacadbury@gmail.com','2026-10-20 09:42:05'),(98,31,27,'USER_CREATED','USER',34,'Created ADMIN user shanicemosli@gmail.co.ke','2026-10-20 09:43:45'),(99,31,27,'USER_UPDATED','USER',33,'Updated sussankihikacadbury@gmail.com: status ACTIVE to INACTIVE','2026-10-20 09:59:24'),(100,31,27,'USER_UPDATED','USER',34,'Updated shanicemosli@gmail.co.ke: no changes','2026-10-20 09:59:35'),(101,31,27,'USER_UPDATED','USER',34,'Updated shanicemosli@gmail.co.ke: status ACTIVE to INACTIVE','2026-10-20 10:00:29'),(102,31,27,'BRANCH_UPDATED','BRANCH',33,'Updated branch Cadbury: status ACTIVE to INACTIVE','2026-10-20 10:00:39'),(103,31,27,'USER_UPDATED','USER',32,'Updated abimwanikicadbury@gmail.com: no changes','2026-10-20 10:54:23'),(104,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',7,'Acknowledged receipt of product announcement #7.','2026-10-20 10:54:51'),(105,9,9,'RELATIONSHIP_REQUESTED','RELATIONSHIP',13,'Requested a supply relationship with Target Supermarket','2026-10-20 12:55:21'),(106,9,9,'RELATIONSHIP_REQUESTED','RELATIONSHIP',14,'Requested a supply relationship with Summer Africa','2026-10-20 12:55:36'),(107,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',13,'Accepted a supply relationship request','2026-10-20 12:56:44'),(108,9,9,'PRODUCT_CREATED','PRODUCT',13,'Created product Ariel lavender 5kg with initial barcode 60673847276346.','2026-10-20 13:10:27'),(109,35,28,'RELATIONSHIP_REQUESTED','RELATIONSHIP',15,'Requested a supply relationship with Cadbury limited','2026-10-20 13:15:28'),(110,35,28,'RELATIONSHIP_REQUESTED','RELATIONSHIP',16,'Requested a supply relationship with Proctor Gamble limitted','2026-10-20 13:15:34'),(111,35,28,'RELATIONSHIP_REQUESTED','RELATIONSHIP',17,'Requested a supply relationship with Mill Bakers','2026-10-20 13:15:45'),(112,35,28,'RELATIONSHIP_REQUESTED','RELATIONSHIP',18,'Requested a supply relationship with Target Supermarket','2026-10-20 13:15:54'),(113,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',18,'Accepted a supply relationship request','2026-10-20 13:16:14'),(114,7,2,'RELATIONSHIP_DECLINED','RELATIONSHIP',17,'Declined a supply relationship request','2026-10-20 13:20:01'),(115,31,27,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',15,'Accepted a supply relationship request','2026-10-20 13:21:17'),(116,9,9,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',16,'Accepted a supply relationship request','2026-10-20 13:27:45'),(117,9,9,'PRODUCT_CREATED','PRODUCT',14,'Created product Aeriel stawberry fabric softener 1l with initial barcode 67554974887859.','2026-10-21 02:10:16'),(118,9,9,'PRODUCT_CREATED','PRODUCT',15,'Created product Aerile vanilla fabric softener 1l with initial barcode 64646464535532.','2026-10-21 02:12:56'),(119,9,9,'ORGANIZATION_UPDATED','ORGANIZATION',9,'Updated organisation profile: name Proctor Gamble limitted to P&G limitted','2026-10-21 02:19:48'),(120,9,9,'PRODUCT_CREATED','PRODUCT',16,'Created product Aeriel washing powder 1kg with initial barcode 68543546453433.','2026-10-21 02:22:56'),(121,9,9,'PRODUCT_CREATED','PRODUCT',17,'Created product Oral b cross toothbrush with initial barcode 65748359335343.','2026-10-21 02:26:18'),(122,9,9,'PRODUCT_CREATED','PRODUCT',18,'Created product gillete razor pack with initial barcode 69475823452323.','2026-10-21 02:29:32'),(123,9,9,'PRODUCT_CREATED','PRODUCT',19,'Created product pampers baby pants with initial barcode 68454536456374.','2026-10-21 02:33:04'),(124,31,27,'PRODUCT_CREATED','PRODUCT',20,'Created product Cadburry classic with initial barcode 64537893545322.','2026-10-21 02:35:25'),(125,1,5,'RELATIONSHIP_ENDED','RELATIONSHIP',13,'Ended a supply relationship','2026-10-21 09:29:59'),(126,1,5,'RELATIONSHIP_REQUESTED','RELATIONSHIP',19,'Requested a supply relationship with Summer Africa','2026-10-21 10:38:00'),(127,28,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',4,'Acknowledged receipt of product announcement #4.','2026-10-21 11:14:20'),(128,28,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',3,'Acknowledged receipt of product announcement #3.','2026-10-21 11:14:27'),(129,31,27,'PRODUCT_UPDATED','PRODUCT',12,'Updated product Mint chocolate and replaced its image.','2026-10-21 11:17:49'),(130,29,26,'PRODUCT_UPDATED','PRODUCT',11,'Updated product Omo washing powder and replaced its image.','2026-10-21 11:21:51'),(131,35,28,'RELATIONSHIP_ENDED','RELATIONSHIP',16,'Ended a supply relationship','2026-10-21 11:25:32'),(132,35,28,'RELATIONSHIP_REQUESTED','RELATIONSHIP',20,'Requested a supply relationship with P&G limitted','2026-10-21 11:25:43'),(133,9,9,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',20,'Accepted a supply relationship request','2026-10-21 11:26:15'),(134,7,2,'RELATIONSHIP_ENDED','RELATIONSHIP',9,'Ended a supply relationship','2026-10-21 11:56:13'),(135,9,9,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',9,'Updated organisation logo.','2026-10-21 12:19:10'),(136,9,9,'ORGANIZATION_UPDATED','ORGANIZATION',9,'Updated organisation profile: no changes','2026-10-21 12:22:31'),(137,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:31:32'),(138,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:31:43'),(139,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:34:30'),(140,31,27,'ORGANIZATION_UPDATED','ORGANIZATION',27,'Updated organisation profile: no changes','2026-10-21 12:34:33'),(141,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:41:22'),(142,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:41:39'),(143,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:42:20'),(144,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:45:45'),(145,31,27,'ORGANIZATION_UPDATED','ORGANIZATION',27,'Updated organisation profile: no changes','2026-10-21 12:45:50'),(146,31,27,'ORGANIZATION_LOGO_REMOVED','ORGANIZATION',27,'Removed organisation logo.','2026-10-21 12:54:15'),(147,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 12:58:26'),(148,31,27,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',27,'Updated organisation logo.','2026-10-21 13:03:19'),(149,31,27,'ORGANIZATION_UPDATED','ORGANIZATION',27,'Updated organisation profile: no changes','2026-10-21 13:03:24'),(150,1,5,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',5,'Updated organisation logo.','2026-10-21 13:07:44'),(151,28,3,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',3,'Updated organisation logo.','2026-10-21 13:08:05'),(152,7,2,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',2,'Updated organisation logo.','2026-10-21 13:15:35'),(153,7,2,'ORGANIZATION_UPDATED','ORGANIZATION',2,'Updated organisation profile: no changes','2026-10-21 13:15:40'),(154,2,1,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',1,'Updated organisation logo.','2026-10-21 13:16:11'),(155,29,26,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',26,'Updated organisation logo.','2026-10-21 13:24:44'),(156,35,28,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',28,'Updated organisation logo.','2026-10-21 13:25:50'),(157,11,11,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',11,'Updated organisation logo.','2026-10-21 13:28:15'),(158,1,5,'ORGANIZATION_UPDATED','ORGANIZATION',5,'Updated organisation profile: email  to targetsupermarkets@globall.co.ke','2026-10-21 13:41:17'),(159,9,9,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',8,'Published product announcement for Oral b cross toothbrush to 2 retailer organization(s).','2026-10-22 07:16:47'),(160,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',8,'Acknowledged receipt of product announcement #8.','2026-10-22 07:17:23'),(161,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',21,'Requested a supply relationship with Fratres Limited Kitui','2026-10-22 14:17:26'),(162,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',22,'Requested a supply relationship with Hasbah','2026-10-22 14:17:42'),(163,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',23,'Requested a supply relationship with Summer Africa','2026-10-22 14:17:52'),(164,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',24,'Requested a supply relationship with Chandaria Industries','2026-10-22 14:18:00'),(165,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',25,'Requested a supply relationship with Uniliver limitted','2026-10-22 14:18:07'),(166,36,29,'RELATIONSHIP_REQUESTED','RELATIONSHIP',26,'Requested a supply relationship with Excel Chemicals','2026-10-22 14:18:12'),(167,1,5,'ORGANIZATION_LOGO_UPDATED','ORGANIZATION',5,'Updated organisation logo.','2026-10-22 19:03:28'),(168,1,5,'ORGANIZATION_UPDATED','ORGANIZATION',5,'Updated organisation profile: no changes','2026-10-22 19:03:32'),(169,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Target Supermarket: no changes','2026-10-23 04:10:38'),(170,1,5,'BRANCH_CREATED','BRANCH',36,'Created branch Target Machakos','2026-10-23 05:09:31'),(171,1,5,'USER_CREATED','USER',37,'Created branch administrator jollymosiria@target.co.ke for Target Machakos','2026-10-23 05:09:31'),(172,1,5,'USER_CREATED','USER',38,'Created BRANCH_ADMIN user leonerd@target.local','2026-10-23 11:24:02'),(173,1,5,'USER_UPDATED','USER',1,'Updated admin@target.local: name System Admin to Paul Mutemi','2026-10-24 07:48:03'),(174,1,5,'BRANCH_UPDATED','BRANCH',7,'Updated branch Main Branch: county Not mapped to Nairobi','2026-10-24 11:15:29'),(175,1,5,'BRANCH_UPDATED','BRANCH',26,'Updated branch Target Supermarket: county Not mapped to Mombasa','2026-10-24 11:15:46'),(176,1,5,'BRANCH_UPDATED','BRANCH',36,'Updated branch Target Machakos: county Not mapped to Machakos','2026-10-24 11:16:02'),(177,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',4,'Appointed Fratres Limited Kitui for Madeira Cake in area ID 14 from 2026-10-24 to 2028-01-24 (non-exclusive).','2026-10-24 11:36:51'),(178,7,2,'PRODUCT_DISTRIBUTION_ENDED','PRODUCT_SUPPLIER',4,'Ended/cancelled appointment of Fratres Limited Kitui for Madeira Cake in Kakamega.','2026-10-24 11:40:57'),(179,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',5,'Appointed Fratres Limited Kitui for Demo Bread in area ID 29 from 2026-10-24 to 2026-10-25 (non-exclusive).','2026-10-24 11:58:27'),(180,7,2,'PRODUCT_DISTRIBUTION_ENDED','PRODUCT_SUPPLIER',5,'Ended/cancelled appointment of Fratres Limited Kitui for Demo Bread in Mombasa.','2026-10-24 12:03:21'),(181,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',1,'Declared supplier coverage for Mombasa from 2026-10-24 through 2026-10-25.','2026-10-24 12:15:28'),(182,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',1,'Ended supplier coverage for Mombasa.','2026-10-24 12:16:16'),(183,28,3,'USER_UPDATED','USER',28,'Updated admin@fratres.local: name Fratres Admin to Paul Walker','2026-10-24 12:21:33'),(184,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',2,'Declared supplier coverage for Nairobi from 2026-10-24 through 2026-10-25.','2026-10-24 12:30:07'),(185,9,9,'RELATIONSHIP_REQUESTED','RELATIONSHIP',27,'Requested a supply relationship with Fratres Limited Kitui','2026-10-24 12:38:28'),(186,9,9,'RELATIONSHIP_ENDED','RELATIONSHIP',14,'Ended a supply relationship','2026-10-24 12:39:39'),(187,9,9,'RELATIONSHIP_ENDED','RELATIONSHIP',27,'Ended a supply relationship','2026-10-24 12:39:49'),(188,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',2,'Ended supplier coverage for Nairobi.','2026-10-24 12:43:13'),(189,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',7,'Declared supplier coverage for Nairobi from 2026-10-24 through 2026-10-25.','2026-10-24 13:05:31'),(190,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',8,'Declared supplier coverage for Nakuru from 2026-10-24 with no planned end date.','2026-10-24 13:14:46'),(191,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',6,'Appointed Fratres Limited Kitui for Demo Bread in Nakuru from 2026-10-24 to 2026-10-25 (non-exclusive).','2026-10-24 13:16:13'),(192,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',8,'Ended supplier coverage for Nakuru. 1 active or scheduled product appointment(s) were left unchanged.','2026-10-24 13:17:46'),(193,7,2,'PRODUCT_DISTRIBUTION_ENDED','PRODUCT_SUPPLIER',6,'Ended/cancelled appointment of Fratres Limited Kitui for Demo Bread in Nakuru.','2026-10-24 13:19:55'),(194,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',9,'Declared supplier coverage for Nakuru from 2026-10-24 with no planned end date.','2026-10-24 13:25:49'),(195,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',8,'Appointed Fratres Limited Kitui for Demo Bread in Nakuru from 2026-10-24 to 2026-10-25 (non-exclusive).','2026-10-24 13:30:24'),(196,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',9,'Ended supplier coverage for Nakuru. 1 active or scheduled product appointment(s) were left unchanged.','2026-10-24 13:32:03'),(197,7,2,'PRODUCT_DISTRIBUTION_ENDED','PRODUCT_SUPPLIER',8,'Ended/cancelled appointment of Fratres Limited Kitui for Demo Bread in Nakuru.','2026-10-24 13:32:37'),(198,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',9,'Appointed Fratres Limited Kitui for Demo Bread in Nairobi from 2026-10-24 to 2026-10-25 (non-exclusive).','2026-10-24 14:01:36'),(199,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',10,'Declared supplier coverage for Kwale from 2026-10-24 through 2026-10-25.','2026-10-24 14:08:23'),(200,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',10,'Appointed Fratres Limited Kitui for Demo Bread in Kwale from 2026-10-24 to 2026-10-25 (non-exclusive).','2026-10-24 14:09:51'),(201,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',10,'Ended supplier coverage for Kwale. 1 active or scheduled product appointment(s) were left unchanged.','2026-10-24 14:11:09'),(202,1,5,'STOCK_UPDATED','PRODUCT',6,'Set Demo Bread version 2 stock at Main Branch to 74 units; reorder level 20','2026-10-24 15:23:31'),(203,1,5,'STOCK_UPDATED','PRODUCT',6,'Set Demo Bread version 2 stock at Main Branch to 75 units; reorder level 20','2026-10-24 15:23:37'),(204,1,5,'SALE_RECORDED','PRODUCT',6,'Recorded 5 units of Demo Bread version 2 sold at Main Branch on 2026-10-24','2026-10-24 15:29:41'),(205,1,5,'STOCK_UPDATED','PRODUCT',15,'Set Aerile vanilla fabric softener 1l version 1 stock at Main Branch to 26 units; reorder level 10','2026-10-24 15:36:22'),(206,1,5,'SALE_RECORDED','PRODUCT',15,'Recorded 5 units of Aerile vanilla fabric softener 1l version 1 sold at Main Branch on 2026-10-24','2026-10-24 15:36:36'),(207,7,2,'PRODUCT_TAX_CLASSIFICATION_DECLARED','PRODUCT_TAX_CLASSIFICATION',1,'Declared STANDARD_RATED tax classification for Demo Bread version 2 (450g) from 2026-10-24 with no planned end date. Rate at start: 16.00%.','2026-10-24 17:28:10'),(208,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',1,'Set Main Branch selling price to KSh 200.00 VAT_INCLUSIVE for Demo Bread version 2 from 2026-10-24 with no planned end date.','2026-10-24 17:28:45'),(209,1,5,'STOCK_UPDATED','PRODUCT',20,'Set Cadburry classic version 1 stock at Main Branch to 20 units; reorder level 10','2026-10-24 17:36:01'),(210,1,5,'STOCK_UPDATED','PRODUCT',20,'Set Cadburry classic version 1 stock at Main Branch to 20 units; reorder level 10','2026-10-24 17:36:04'),(211,1,5,'STOCK_UPDATED','PRODUCT',10,'Set Quencher lime version 1 stock at Main Branch to 16 units; reorder level 12','2026-10-24 18:51:10'),(212,1,5,'STOCK_UPDATED','PRODUCT',11,'Set Omo washing powder version 1 stock at Main Branch to 58 units; reorder level 120','2026-10-24 18:52:09'),(213,1,5,'STOCK_UPDATED','PRODUCT',5,'Set Muffins version 1 stock at Main Branch to 6 units; reorder level 6','2026-10-24 18:57:35'),(214,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',2,'Set Main Branch selling price to KSh 1500.00 VAT_INCLUSIVE for Aeriel stawberry fabric softener 1l version 1 from 2026-10-24 with no planned end date.','2026-10-24 19:12:57'),(215,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',3,'Set Main Branch selling price to KSh 400.00 VAT_INCLUSIVE for Aeriel washing powder 1kg version 1 from 2026-10-24 with no planned end date.','2026-10-24 19:13:19'),(216,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',4,'Set Main Branch selling price to KSh 1499.87 VAT_INCLUSIVE for Aerile vanilla fabric softener 1l version 1 from 2026-10-24 with no planned end date.','2026-10-24 19:13:35'),(217,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',5,'Set Main Branch selling price to KSh 2250.00 VAT_INCLUSIVE for Ariel lavender 5kg version 1 from 2026-10-24 with no planned end date.','2026-10-24 19:13:51'),(218,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',11,'Appointed Fratres Limited Kitui for Heart Deli Bread in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:56:55'),(219,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',12,'Appointed Fratres Limited Kitui for Madeira Cake in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:12'),(220,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',13,'Appointed Fratres Limited Kitui for Mill bakers multi grain bread in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:19'),(221,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',14,'Appointed Fratres Limited Kitui for Mill cookies in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:25'),(222,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',15,'Appointed Fratres Limited Kitui for mill mini short cake in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:31'),(223,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',16,'Appointed Fratres Limited Kitui for Muffins in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:37'),(224,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',17,'Appointed Fratres Limited Kitui for Standard madeira in Nairobi from 2026-10-25 with no planned end date (non-exclusive). Supplier coverage warning acknowledged by manufacturer.','2026-10-25 00:57:52'),(225,28,3,'SUPPLIER_COVERAGE_ENDED','SUPPLIER_COVERAGE',7,'Ended supplier coverage for Nairobi. 8 active or scheduled product appointment(s) were left unchanged.','2026-10-25 00:58:43'),(226,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',11,'Declared supplier coverage for Nairobi from 2026-10-25 with no planned end date.','2026-10-25 00:58:49'),(227,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',12,'Declared supplier coverage for Kiambu from 2026-10-25 with no planned end date.','2026-10-25 01:33:33'),(228,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',13,'Declared supplier coverage for Kitui from 2026-10-25 with no planned end date.','2026-10-25 01:33:39'),(229,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',18,'Appointed Fratres Limited Kitui for Demo Bread in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(230,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',19,'Appointed Fratres Limited Kitui for Heart Deli Bread in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(231,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',20,'Appointed Fratres Limited Kitui for Madeira Cake in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(232,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',21,'Appointed Fratres Limited Kitui for Mill bakers multi grain bread in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(233,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',22,'Appointed Fratres Limited Kitui for Mill cookies in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(234,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',23,'Appointed Fratres Limited Kitui for mill mini short cake in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(235,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',24,'Appointed Fratres Limited Kitui for Muffins in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(236,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',25,'Appointed Fratres Limited Kitui for Standard madeira in Kiambu from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:41:45'),(237,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',26,'Appointed Fratres Limited Kitui for Demo Bread in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(238,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',27,'Appointed Fratres Limited Kitui for Heart Deli Bread in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(239,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',28,'Appointed Fratres Limited Kitui for Madeira Cake in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(240,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',29,'Appointed Fratres Limited Kitui for Mill bakers multi grain bread in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(241,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',30,'Appointed Fratres Limited Kitui for Mill cookies in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(242,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',31,'Appointed Fratres Limited Kitui for mill mini short cake in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(243,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',32,'Appointed Fratres Limited Kitui for Muffins in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(244,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',33,'Appointed Fratres Limited Kitui for Standard madeira in Kitui from 2026-10-25 with no planned end date (non-exclusive).','2026-10-25 01:42:02'),(245,7,2,'BRANCH_UPDATED','BRANCH',4,'Updated branch Main Branch: county Not mapped to Nairobi','2026-10-25 01:45:44'),(246,7,2,'RELATIONSHIP_REQUESTED','RELATIONSHIP',28,'Requested a supply relationship with Hasbah','2026-10-25 05:25:20'),(247,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',21,'Accepted a supply relationship request','2026-10-25 06:03:50'),(248,1,5,'RETAILER_BRANCH_PRICE_ENDED','RETAILER_BRANCH_PRICE',2,'Set branch selling price #2 effective-to date to 2026-10-25. The historical price record was retained.','2026-10-25 06:14:05'),(249,1,5,'RETAILER_BRANCH_PRICE_ENDED','RETAILER_BRANCH_PRICE',3,'Set branch selling price #3 effective-to date to 2026-10-31. The historical price record was retained.','2026-10-25 06:14:29'),(250,1,5,'RETAILER_BRANCH_PRICE_ENDED','RETAILER_BRANCH_PRICE',4,'Set branch selling price #4 effective-to date to 2026-10-31. The historical price record was retained.','2026-10-25 06:14:33'),(251,1,5,'RETAILER_BRANCH_PRICE_ENDED','RETAILER_BRANCH_PRICE',5,'Set branch selling price #5 effective-to date to 2026-10-31. The historical price record was retained.','2026-10-25 06:14:38'),(252,1,5,'RETAILER_BRANCH_PRICE_ENDED','RETAILER_BRANCH_PRICE',1,'Set branch selling price #1 effective-to date to 2026-10-31. The historical price record was retained.','2026-10-25 06:14:44'),(253,1,5,'RETAILER_BRANCH_PRICE_CREATED','RETAILER_BRANCH_PRICE',6,'Set Main Branch selling price to KSh 550.00 VAT_INCLUSIVE for Omo washing powder version 1 from 2026-10-25 with no planned end date.','2026-10-25 06:17:02'),(254,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',11,'Daily sales summary #10: Omo washing powder version 1 at Main Branch; units 0 to 50; KSh 27500.00 (VAT_INCLUSIVE); tax status MISSING_CLASSIFICATION; stock adjusted by -50 units','2026-10-25 06:17:49'),(255,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #8: Demo Bread version 2 at Main Branch; units 5 to 5; KSh 176.00 (VAT_INCLUSIVE); tax status AMBIGUOUS_RATE; historical summary saved without changing current stock','2026-10-25 07:34:27'),(256,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #8: Demo Bread version 2 at Main Branch; units 5 to 5; KSh 1000.00 (VAT_INCLUSIVE); tax status CALCULATED; historical summary saved without changing current stock','2026-10-25 07:39:26'),(257,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #8: Demo Bread version 2 at Main Branch; units 5 to 5; KSh 100.00 (VAT_INCLUSIVE); tax status CALCULATED; PRICE OVERRIDE: expected KSh 1000.00, entered KSh 100.00; historical summary saved without changing current stock','2026-10-25 07:39:26'),(258,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #11: Demo Bread version 2 at Main Branch; units 0 to 5; KSh 100.00 (VAT_INCLUSIVE); tax status CALCULATED; PRICE OVERRIDE: expected KSh 1000.00, entered KSh 100.00; stock adjusted by -5 units','2026-10-25 07:49:24'),(259,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #11: Demo Bread version 2 at Main Branch; units 5 to 75; KSh 15000.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -70 units','2026-10-25 07:56:18'),(260,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',11,'Daily sales summary #10: Omo washing powder version 1 at Main Branch; units 50 to 50; KSh 27500.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by 0 units','2026-10-25 08:08:49'),(261,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',11,'Daily sales summary #10: Omo washing powder version 1 at Main Branch; units 50 to 50; KSh 27500.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by 0 units','2026-10-25 08:09:13'),(262,1,5,'STOCK_UPDATED','PRODUCT',19,'Set pampers baby pants version 1 stock at Main Branch to 7 units; reorder level 5','2026-10-25 08:11:50'),(263,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',19,'Daily sales summary #13: pampers baby pants version 1 at Main Branch; units 0 to 2; KSh 1000.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -2 units','2026-10-25 08:12:26'),(264,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',20,'Daily sales summary #14: Cadburry classic version 1 at Main Branch; units 0 to 5; KSh 1250.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -5 units','2026-10-25 08:14:50'),(265,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',16,'Daily sales summary #16: Aeriel washing powder 1kg version 1 at Main Branch; units 0 to 105; KSh 42000.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -105 units','2026-10-25 08:16:30'),(266,1,5,'PRICE_SAVED','PRODUCT',8,'Updated COST price to 150','2026-10-25 08:29:42'),(267,1,5,'PRICE_SAVED','PRODUCT',22,'Updated COST price to 415.01','2026-10-25 10:11:01'),(268,1,5,'PRICE_SAVED','PRODUCT',22,'Updated SELLING price to 569.99','2026-10-25 10:11:01'),(269,7,2,'PRICE_SAVED','PRODUCT',2,'Updated COST price to 284','2026-10-25 15:03:48'),(270,7,2,'PRICE_SAVED','PRODUCT',2,'Updated SELLING price to 320','2026-10-25 15:03:48'),(271,7,2,'PRICE_SAVED','PRODUCT',8,'Updated COST price to 75','2026-10-25 16:44:38'),(272,7,2,'PRICE_SAVED','PRODUCT',8,'Updated SELLING price to 90','2026-10-25 16:44:38'),(273,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',14,'Daily sales summary #35: Aeriel stawberry fabric softener 1l version 1 at Main Branch; units 1 to 87; KSh 130500.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -86 units','2026-10-25 16:50:24'),(274,2,1,'PRODUCT_UPDATED','PRODUCT',1,'Updated product Whitedent and replaced its image.','2026-10-25 17:19:59'),(275,2,1,'PRODUCT_UPDATED','PRODUCT',1,'Updated product Whitedent and replaced its image.','2026-10-25 17:22:42'),(276,2,1,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',24,'Accepted a supply relationship request','2026-10-25 17:23:37'),(277,7,2,'PRICE_SAVED','PRODUCT',2,'Saved COST price 284.00 for from 2026-10-25 with no scheduled end date','2026-10-25 17:34:24'),(278,7,2,'PRICE_SAVED','PRODUCT',2,'Saved SELLING price 320.00 for from 2026-10-25 with no scheduled end date','2026-10-25 17:34:24'),(279,2,1,'PRICE_SAVED','PRODUCT',6,'Saved COST price 182.00 for from 2026-10-25 with no scheduled end date','2026-10-25 17:38:58'),(280,2,1,'PRICE_SAVED','PRODUCT',6,'Saved SELLING price 198.38 for from 2026-10-25 with no scheduled end date','2026-10-25 17:38:58'),(281,1,5,'PRICE_SAVED','PRODUCT',2,'Saved COST price 322.69 for from 2026-10-25 with no scheduled end date','2026-10-25 17:44:09'),(282,1,5,'PRICE_SAVED','PRODUCT',2,'Saved SELLING price 445.00 for from 2026-10-25 with no scheduled end date','2026-10-25 17:44:09'),(285,7,2,'USER_UPDATED','USER',7,'Updated manager@millbakers.local: no changes, password reset','2026-10-26 05:26:20'),(286,7,2,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 91.00 for from 2026-10-26 with no scheduled end date','2026-10-26 05:26:38'),(287,1,5,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',5,'Price Change Protection enabled.','2026-10-26 05:40:30'),(288,7,2,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 98.00 for from 2026-10-26 with no scheduled end date','2026-10-26 05:45:41'),(289,28,3,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',3,'Price Change Protection enabled.','2026-10-26 05:46:36'),(290,7,2,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 100.00 for from 2026-10-26 with no scheduled end date','2026-10-26 05:47:15'),(291,7,2,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 140.00 for from 2026-10-26 with no scheduled end date','2026-10-26 05:56:42'),(292,28,3,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',3,'Price Change Protection enabled.','2026-10-26 05:57:02'),(293,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 141.00; 3 organisation price rows and 0 branch price rows updated; effective 2026-10-26.','2026-10-26 06:10:17'),(294,7,2,'PRICE_SAVED','PRODUCT',8,'Saved manufacturer SELLING price 141.00 effective 2026-10-26; protection-enabled organisations were processed automatically.','2026-10-26 06:10:17'),(295,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 141.01; 3 organisation price rows and 0 branch price rows updated; effective 2026-10-26.','2026-10-26 06:27:05'),(296,7,2,'PRICE_SAVED','PRODUCT',8,'Saved manufacturer SELLING price 141.01 effective 2026-10-26; protection-enabled organisations were processed automatically.','2026-10-26 06:27:05'),(297,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 142.00; 3 organisation price rows and 0 branch price rows updated; effective 2026-10-26.','2026-10-26 06:54:31'),(298,7,2,'PRICE_SAVED','PRODUCT',8,'Saved manufacturer SELLING price 142.00 effective 2026-10-26; protection-enabled organisations were processed automatically.','2026-10-26 06:54:31'),(299,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 175.00; 3 organisation price rows and 0 branch price rows updated; effective 2026-10-26.','2026-10-26 07:02:39'),(300,7,2,'PRICE_SAVED','PRODUCT',8,'Saved manufacturer SELLING price 175.00 effective 2026-10-26; protection-enabled organisations were processed automatically.','2026-10-26 07:02:39'),(301,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 179.00; 3 organisation price rows and 0 branch price rows updated; effective 2026-10-26.','2026-10-26 07:03:19'),(302,7,2,'PRICE_SAVED','PRODUCT',8,'Saved manufacturer SELLING price 179.00 effective 2026-10-26; protection-enabled organisations were processed automatically.','2026-10-26 07:03:19'),(303,28,3,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 190.00 for from 2026-10-26 with no scheduled end date','2026-10-26 07:19:04'),(304,7,2,'SUPPLIER_PRICE_DECREASE_APPLIED','PRODUCT_VERSION',8,'Reduced the direct supplier\'s COST price for product version 8 after a manufacturer selling-price decrease. The supplier\'s SELLING price, retailer prices, and branch prices were left unchanged.','2026-10-26 08:03:28'),(305,7,2,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 90.00 for from 2026-10-26 with no scheduled end date','2026-10-26 08:03:28'),(306,28,3,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',3,'Price Change Protection disabled.','2026-10-26 08:03:52'),(307,1,5,'FINANCE_DOCUMENT_DRAFT_CREATED','FINANCE_DOCUMENT',1,'Created INVOICE draft TST-20261026142023-A; total KES 232.00.','2026-10-26 11:20:48'),(308,1,5,'FINANCE_DOCUMENT_DRAFT_DELETED','FINANCE_DOCUMENT',1,'Deleted INVOICE draft TST-20261026142023-A.','2026-10-26 11:20:48'),(309,1,5,'FINANCE_DOCUMENT_DRAFT_CREATED','FINANCE_DOCUMENT',2,'Created INVOICE draft TST-20261026142023-B; total KES 232.00.','2026-10-26 11:20:48'),(310,1,5,'FINANCE_DOCUMENT_ISSUED','FINANCE_DOCUMENT',2,'Issued INVOICE TST-20261026142023-B for KES 232.00 and posted its journal entry.','2026-10-26 11:20:48'),(311,1,5,'FINANCE_DOCUMENT_VOIDED','FINANCE_DOCUMENT',2,'Voided INVOICE TST-20261026142023-B. Reason: Automated test void','2026-10-26 11:20:49'),(312,1,5,'FINANCE_DOCUMENT_DRAFT_CREATED','FINANCE_DOCUMENT',3,'Created INVOICE draft TST-20261026142023-C; total KES 232.00.','2026-10-26 11:20:49'),(313,1,5,'FINANCE_DOCUMENT_ISSUED','FINANCE_DOCUMENT',3,'Issued INVOICE TST-20261026142023-C for KES 232.00 and posted its journal entry.','2026-10-26 11:20:49'),(314,1,5,'FINANCE_DOCUMENT_VOIDED','FINANCE_DOCUMENT',3,'Voided INVOICE TST-20261026142023-C. Reason: Automated test cleanup','2026-10-26 11:20:49'),(315,1,5,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',1,'Recorded outgoing payment #1 for KES 50.00 to organisation 1; awaiting payee confirmation.','2026-10-26 11:20:49'),(316,1,5,'FINANCE_PAYMENT_CANCELLED','FINANCE_PAYMENT',1,'Cancelled by the payer: Automated test cancel Payment #1 for KES 50.00.','2026-10-26 11:20:49'),(317,7,2,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',2,'Recorded outgoing payment #2 for KES 10000.00 to organisation 3; awaiting payee confirmation.','2026-10-26 11:42:48'),(318,28,3,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',3,'Recorded outgoing payment #3 for KES 10000.00 to organisation 2; awaiting payee confirmation.','2026-10-26 16:46:52'),(319,7,2,'PRICE_SAVED','PRODUCT',13,'Saved COST price 70.00 for from 2026-10-26 with no scheduled end date','2026-10-26 17:37:50'),(320,7,2,'PRICE_SAVED','PRODUCT',13,'Saved SELLING price 90.00 for from 2026-10-26 with no scheduled end date','2026-10-26 17:37:51'),(321,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',8,'Applied manufacturer price change for Demo Bread version 2: KSh 91.00; 1 organisation price rows and 0 branch price rows updated; effective 2026-10-27.','2026-10-27 02:46:05'),(322,7,2,'FINANCE_PAYMENT_CONFIRMED','FINANCE_PAYMENT',3,'Confirmed receipt of payment #3 for KES 10000.00. Posted balanced journal entries for both organisations.','2026-10-27 03:51:56'),(323,7,2,'PROMOTION_CREATED','PROMOTION',2,'Saved draft campaign \"TST-20261027073239-A\" (2027-08-23 to 2027-09-02) with 1 products and 1 retailers.','2026-10-27 04:33:01'),(324,7,2,'PROMOTION_UPDATED','PROMOTION',2,'Saved draft campaign \"TST-20261027073239-A\" (2027-08-23 to 2027-09-02) with 1 products and 1 retailers.','2026-10-27 04:33:02'),(325,7,2,'PROMOTION_SUBMITTED','PROMOTION',2,'Campaign \"TST-20261027073239-A\": The campaign was submitted for approval.','2026-10-27 04:33:02'),(326,7,2,'PROMOTION_CREATED','PROMOTION',3,'Saved draft campaign \"TST-20261027073239-B\" (2027-08-28 to 2027-09-07) with 1 products and 1 retailers.','2026-10-27 04:33:02'),(327,7,2,'PROMOTION_APPROVED','PROMOTION',2,'Campaign \"TST-20261027073239-A\": The campaign was approved.','2026-10-27 04:33:02'),(328,7,2,'PROMOTION_CANCELLED','PROMOTION',2,'Campaign \"TST-20261027073239-A\": The campaign was cancelled. Reason: Automated test','2026-10-27 04:33:02'),(329,7,2,'PROMOTION_SUBMITTED','PROMOTION',3,'Campaign \"TST-20261027073239-B\": The campaign was submitted for approval.','2026-10-27 04:33:02'),(330,7,2,'PROMOTION_CREATED','PROMOTION',4,'Saved draft campaign \"TST-20261027073239-C\" (2026-10-26 to 2026-10-26) with 1 products and 1 retailers.','2026-10-27 04:33:02'),(331,7,2,'PROMOTION_CANCELLED','PROMOTION',3,'Campaign \"TST-20261027073239-B\": The campaign was cancelled. Reason: Automated test cleanup','2026-10-27 04:33:02'),(332,7,2,'PROMOTION_CANCELLED','PROMOTION',4,'Campaign \"TST-20261027073239-C\": The campaign was cancelled. Reason: Automated test cleanup','2026-10-27 04:33:02'),(333,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',8,'Published RRP for Demo Bread version 2 at KSh 150.00 effective 2026-10-28. Previous effective recommendation: none. Notified 2 organisations.','2026-10-28 03:43:31'),(334,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',8,'Published RSP for Demo Bread version 2 at KSh 120.00 effective 2026-10-28. Previous effective recommendation: none. Notified 1 organisations.','2026-10-28 03:43:53'),(335,7,2,'PROMOTION_CREATED','PROMOTION',5,'Saved draft campaign \"Testing promotional campaings\" (2026-10-28 to 2026-11-01) with 1 products and 1 retailers.','2026-10-28 03:48:01'),(336,7,2,'PROMOTION_SUBMITTED','PROMOTION',5,'Campaign \"Testing promotional campaings\": The campaign was submitted for approval.','2026-10-28 03:48:06'),(337,7,2,'PROMOTION_APPROVED','PROMOTION',5,'Campaign \"Testing promotional campaings\": The campaign was approved.','2026-10-28 03:48:10'),(338,1,5,'MANUFACTURER_CAMPAIGN_ACCEPTED','PROMOTION',5,'Campaign \"Testing promotional campaings\" action: accept.','2026-10-28 04:42:47'),(339,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',5,'Campaign \"Testing promotional campaings\" action: start_branch.','2026-10-28 04:47:23'),(340,28,3,'PRICE_SAVED','PRODUCT',8,'Saved SELLING price 120.00 for from 2026-10-28 with no scheduled end date','2026-10-28 06:01:50'),(341,11,11,'PRICE_SAVED','PRODUCT',17,'Saved COST price 70.00 for from 2026-10-28 with no scheduled end date','2026-10-28 11:49:13'),(342,11,11,'PRICE_SAVED','PRODUCT',17,'Saved SELLING price 120.00 for from 2026-10-28 with no scheduled end date','2026-10-28 11:49:14'),(343,11,11,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',17,'Published RRP for Quencher lime version 1 at KSh 170.00 effective 2026-10-28. Previous effective recommendation: none. Notified 1 organisations.','2026-10-28 11:49:36'),(344,11,11,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',17,'Published RRP for Quencher lime version 1 at KSh 195.00 effective 2026-10-28. Previous effective recommendation: 170.00. Notified 1 organisations.','2026-10-28 11:49:53'),(345,11,11,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',17,'Published RSP for Quencher lime version 1 at KSh 165.00 effective 2026-10-28. Previous effective recommendation: none. Notified 0 organisations.','2026-10-28 11:50:30'),(346,29,26,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',25,'Accepted a supply relationship request','2026-10-28 11:53:20'),(347,31,27,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',28,'Applied manufacturer price change for Cadburry classic version 1: KSh 65.00; 1 organisation price rows and 0 branch price rows updated; effective 2026-10-28.','2026-10-28 11:54:52'),(348,31,27,'PRICE_SAVED','PRODUCT',28,'Saved manufacturer SELLING price 65.00 effective 2026-10-28; protection-enabled organisations were processed automatically.','2026-10-28 11:54:52'),(349,31,27,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',28,'Published RRP for Cadburry classic version 1 at KSh 120.00 effective 2026-10-28. Previous effective recommendation: none. Notified 2 organisations.','2026-10-28 11:55:25'),(350,31,27,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',28,'Published RSP for Cadburry classic version 1 at KSh 85.00 effective 2026-10-28. Previous effective recommendation: none. Notified 2 organisations.','2026-10-28 11:55:36'),(351,7,2,'PRICE_SAVED','PRODUCT',12,'Saved COST price 50.00 for from 2026-10-28 with no scheduled end date','2026-10-28 13:51:04'),(352,7,2,'PRICE_SAVED','PRODUCT',12,'Saved SELLING price 70.00 for from 2026-10-28 with no scheduled end date','2026-10-28 13:51:05'),(353,7,2,'PRICE_SAVED','PRODUCT',14,'Saved COST price 70.00 for from 2026-10-28 with no scheduled end date','2026-10-28 13:53:34'),(354,7,2,'PRICE_SAVED','PRODUCT',14,'Saved SELLING price 90.00 for from 2026-10-28 with no scheduled end date','2026-10-28 13:53:34'),(355,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',14,'Published RRP for Mill cookies version 1 at KSh 150.00 effective 2026-10-28. Previous effective recommendation: none. Notified 2 organisations.','2026-10-28 13:54:35'),(356,2,1,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',14,'Declared supplier coverage for Nairobi from 2026-10-28 with no planned end date.','2026-10-28 14:13:57'),(357,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',15,'Declared supplier coverage for Baringo from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(358,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',16,'Declared supplier coverage for Bomet from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(359,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',17,'Declared supplier coverage for Bungoma from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(360,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',18,'Declared supplier coverage for Busia from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(361,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',19,'Declared supplier coverage for Elgeyo-Marakwet from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(362,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',20,'Declared supplier coverage for Embu from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(363,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',21,'Declared supplier coverage for Garissa from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(364,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',22,'Declared supplier coverage for Homa Bay from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(365,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',23,'Declared supplier coverage for Isiolo from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(366,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',24,'Declared supplier coverage for Kajiado from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(367,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',25,'Declared supplier coverage for Kakamega from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(368,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',26,'Declared supplier coverage for Kericho from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(369,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',27,'Declared supplier coverage for Kilifi from 2026-10-28 with no planned end date.','2026-10-28 14:29:27'),(370,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',28,'Declared supplier coverage for Kirinyaga from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(371,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',29,'Declared supplier coverage for Kisii from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(372,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',30,'Declared supplier coverage for Kisumu from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(373,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',31,'Declared supplier coverage for Kwale from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(374,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',32,'Declared supplier coverage for Laikipia from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(375,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',33,'Declared supplier coverage for Lamu from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(376,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',34,'Declared supplier coverage for Machakos from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(377,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',35,'Declared supplier coverage for Makueni from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(378,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',36,'Declared supplier coverage for Mandera from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(379,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',37,'Declared supplier coverage for Marsabit from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(380,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',38,'Declared supplier coverage for Meru from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(381,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',39,'Declared supplier coverage for Migori from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(382,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',40,'Declared supplier coverage for Mombasa from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(383,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',41,'Declared supplier coverage for Murang\'a from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(384,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',42,'Declared supplier coverage for Nakuru from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(385,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',43,'Declared supplier coverage for Nandi from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(386,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',44,'Declared supplier coverage for Narok from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(387,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',45,'Declared supplier coverage for Nyamira from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(388,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',46,'Declared supplier coverage for Nyandarua from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(389,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',47,'Declared supplier coverage for Nyeri from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(390,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',48,'Declared supplier coverage for Samburu from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(391,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',49,'Declared supplier coverage for Siaya from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(392,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',50,'Declared supplier coverage for Taita-Taveta from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(393,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',51,'Declared supplier coverage for Tana River from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(394,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',52,'Declared supplier coverage for Tharaka-Nithi from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(395,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',53,'Declared supplier coverage for Trans Nzoia from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(396,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',54,'Declared supplier coverage for Turkana from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(397,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',55,'Declared supplier coverage for Uasin Gishu from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(398,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',56,'Declared supplier coverage for Vihiga from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(399,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',57,'Declared supplier coverage for Wajir from 2026-10-28 with no planned end date.','2026-10-28 14:29:28'),(400,28,3,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',58,'Declared supplier coverage for West Pokot from 2026-10-28 with no planned end date.','2026-10-28 14:29:29'),(401,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',34,'Appointed Fratres Limited Kitui for Demo Bread in Nairobi from 2026-10-28 with no planned end date (non-exclusive).','2026-10-28 14:30:22'),(402,7,2,'PROMOTION_CREATED','PROMOTION',6,'Saved draft campaign \"Back from school\" (2026-10-28 to 2026-11-01) with 2 products and 2 retailers.','2026-10-28 14:36:38'),(403,7,2,'PROMOTION_CANCELLED','PROMOTION',6,'Campaign \"Back from school\": The campaign was cancelled. Reason: Campaign clash','2026-10-28 14:37:41'),(404,7,2,'PROMOTION_CREATED','PROMOTION',7,'Saved draft campaign \"Holiday Times\" (2026-10-29 to 2026-11-07) with 1 products and 2 retailers.','2026-10-28 14:43:15'),(405,7,2,'PROMOTION_SUBMITTED','PROMOTION',7,'Campaign \"Holiday Times\": The campaign was submitted for approval.','2026-10-28 14:43:17'),(406,7,2,'PROMOTION_APPROVED','PROMOTION',7,'Campaign \"Holiday Times\": The campaign was approved.','2026-10-28 14:43:26'),(407,1,5,'MANUFACTURER_CAMPAIGN_ACCEPTED','PROMOTION',7,'Campaign \"Holiday Times\" action: accept.','2026-10-28 14:44:31'),(408,7,2,'PRICE_SAVED','PRODUCT',11,'Saved COST price 120.00 for from 2026-10-28 with no scheduled end date','2026-10-28 14:51:51'),(409,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',14,'Published RSP for Mill cookies version 1 at KSh 120.00 effective 2026-10-28. Previous effective recommendation: none. Notified 1 organisations.','2026-10-28 14:54:02'),(410,7,2,'CHANGE_CREATED','PRODUCT_CHANGE',9,'Prepared a change for Demo Bread: barcode 6001234567891 to 60000567383955','2026-10-28 17:12:45'),(411,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',9,'Published the change for Demo Bread to 3 organisations','2026-10-28 17:12:52'),(412,11,11,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',26,'Accepted a supply relationship request','2026-10-28 19:19:11'),(413,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',59,'Declared supplier coverage for Baringo from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(414,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',60,'Declared supplier coverage for Bomet from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(415,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',61,'Declared supplier coverage for Bungoma from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(416,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',62,'Declared supplier coverage for Busia from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(417,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',63,'Declared supplier coverage for Elgeyo-Marakwet from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(418,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',64,'Declared supplier coverage for Embu from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(419,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',65,'Declared supplier coverage for Garissa from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(420,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',66,'Declared supplier coverage for Homa Bay from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(421,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',67,'Declared supplier coverage for Isiolo from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(422,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',68,'Declared supplier coverage for Kajiado from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(423,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',69,'Declared supplier coverage for Kakamega from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(424,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',70,'Declared supplier coverage for Kericho from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(425,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',71,'Declared supplier coverage for Kiambu from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(426,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',72,'Declared supplier coverage for Kilifi from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(427,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',73,'Declared supplier coverage for Kirinyaga from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(428,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',74,'Declared supplier coverage for Kisii from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(429,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',75,'Declared supplier coverage for Kisumu from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(430,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',76,'Declared supplier coverage for Kitui from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(431,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',77,'Declared supplier coverage for Kwale from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(432,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',78,'Declared supplier coverage for Laikipia from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(433,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',79,'Declared supplier coverage for Lamu from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(434,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',80,'Declared supplier coverage for Machakos from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(435,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',81,'Declared supplier coverage for Makueni from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(436,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',82,'Declared supplier coverage for Mandera from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(437,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',83,'Declared supplier coverage for Marsabit from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(438,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',84,'Declared supplier coverage for Meru from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(439,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',85,'Declared supplier coverage for Migori from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(440,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',86,'Declared supplier coverage for Mombasa from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(441,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',87,'Declared supplier coverage for Murang\'a from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(442,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',88,'Declared supplier coverage for Nairobi from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(443,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',89,'Declared supplier coverage for Nakuru from 2026-10-28 with no planned end date.','2026-10-28 19:19:54'),(444,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',90,'Declared supplier coverage for Nandi from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(445,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',91,'Declared supplier coverage for Narok from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(446,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',92,'Declared supplier coverage for Nyamira from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(447,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',93,'Declared supplier coverage for Nyandarua from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(448,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',94,'Declared supplier coverage for Nyeri from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(449,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',95,'Declared supplier coverage for Samburu from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(450,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',96,'Declared supplier coverage for Siaya from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(451,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',97,'Declared supplier coverage for Taita-Taveta from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(452,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',98,'Declared supplier coverage for Tana River from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(453,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',99,'Declared supplier coverage for Tharaka-Nithi from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(454,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',100,'Declared supplier coverage for Trans Nzoia from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(455,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',101,'Declared supplier coverage for Turkana from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(456,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',102,'Declared supplier coverage for Uasin Gishu from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(457,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',103,'Declared supplier coverage for Vihiga from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(458,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',104,'Declared supplier coverage for Wajir from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(459,11,11,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',105,'Declared supplier coverage for West Pokot from 2026-10-28 with no planned end date.','2026-10-28 19:19:55'),(460,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:51:50'),(461,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 0 product lines.','2026-10-29 06:51:56'),(462,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:52:00'),(463,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 0 product lines.','2026-10-29 06:52:02'),(464,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:54:39'),(465,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:54:41'),(466,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:54:42'),(467,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:54:43'),(468,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 06:54:45'),(469,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 0 product lines.','2026-10-29 07:12:29'),(470,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 07:43:22'),(471,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 0 product lines.','2026-10-29 07:49:24'),(472,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 1 product lines.','2026-10-29 07:49:30'),(473,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 2 product lines.','2026-10-29 07:49:35'),(474,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 2 product lines.','2026-10-29 07:49:39'),(475,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 3 product lines.','2026-10-29 07:49:50'),(476,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 4 product lines.','2026-10-29 07:49:55'),(477,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 5 product lines.','2026-10-29 07:50:07'),(478,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 5 product lines.','2026-10-29 07:50:11'),(479,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 6 product lines.','2026-10-29 07:50:22'),(480,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 7 product lines.','2026-10-29 07:50:31'),(481,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 8 product lines.','2026-10-29 07:50:41'),(482,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 9 product lines.','2026-10-29 07:50:49'),(483,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 10 product lines.','2026-10-29 07:51:17'),(484,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 11 product lines.','2026-10-29 07:51:23'),(485,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 12 product lines.','2026-10-29 07:51:27'),(486,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 12 product lines.','2026-10-29 07:51:31'),(487,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 13 product lines.','2026-10-29 07:51:46'),(488,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',1,'Saved draft LPO #1 for supplier organisation #3 with 13 product lines.','2026-10-29 08:12:06'),(489,1,5,'SUPPLY_ORDER_SUBMITTED','SUPPLY_ORDER',1,'Submitted LPO #1 to supplier organisation #3 with 13 product lines.','2026-10-29 08:12:06'),(490,28,3,'SUPPLY_ORDER_CONFIRMED','SUPPLY_ORDER',1,'Supplier confirmed LPO #1.','2026-10-29 08:59:21'),(491,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',7,'Campaign \"Holiday Times\" action: start_branch.','2026-10-29 09:26:44'),(492,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',7,'Campaign \"Holiday Times\" action: start_branch.','2026-10-29 09:26:45'),(493,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',7,'Campaign \"Holiday Times\" action: start_branch.','2026-10-29 09:26:47'),(494,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',15,'Daily sales summary #48: Aerile vanilla fabric softener 1l version 1 at Main Branch; units 0 to 21; KSh 31497.27 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -21 units','2026-10-29 09:43:10'),(495,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',13,'Daily sales summary #49: Ariel lavender 5kg version 1 at Main Branch; units 0 to 100; KSh 225000.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -100 units','2026-10-29 09:44:35'),(496,1,5,'PRICE_SAVED','PRODUCT',29,'Saved SELLING price 120.00 for from 2026-10-29 with no scheduled end date','2026-10-29 11:34:17'),(497,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',18,'Daily sales summary #50: gillete razor pack version 1 at Main Branch; units 0 to 90; KSh 13050.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:31:38'),(498,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',2,'Daily sales summary #51: Heart Deli Bread version 1 at Main Branch; units 0 to 90; KSh 40050.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:31:48'),(499,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',10,'Daily sales summary #52: Quencher lime version 1 at Main Branch; units 0 to 16; KSh 5759.84 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -16 units','2026-10-29 12:32:00'),(500,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',19,'Daily sales summary #53: pampers baby pants version 1 at Main Branch; units 0 to 4; KSh 640.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -4 units','2026-10-29 12:32:09'),(501,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #54: Whitedent version 1 at Main Branch; units 0 to 90; KSh 38250.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:32:20'),(502,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #55: Whitedent version 2 at Main Branch; units 0 to 90; KSh 26550.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:32:28'),(503,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',9,'Daily sales summary #56: Standard madeira version 1 at Main Branch; units 0 to 90; KSh 26099.10 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:32:35'),(504,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',17,'Daily sales summary #57: Oral b cross toothbrush version 1 at Main Branch; units 0 to 90; KSh 30600.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-10-29 12:32:42'),(505,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',6,'Daily sales summary #58: Demo Bread version 1 at Main Branch; units 0 to 101; KSh 30298.99 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -101 units','2026-10-29 12:32:54'),(506,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',2,'Daily sales summary #51: Heart Deli Bread version 1 at Main Branch; units 90 to 10; KSh 4450.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by 80 units','2026-10-29 12:33:07'),(507,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',3,'Daily sales summary #59: Madeira Cake version 2 at Main Branch; units 0 to 99; KSh 14850.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -99 units','2026-10-29 12:33:14'),(508,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',7,'Daily sales summary #60: Mill bakers multi grain bread version 1 at Main Branch; units 0 to 99; KSh 35640.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -99 units','2026-10-29 12:33:20'),(509,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',14,'Daily sales summary #61: Aeriel stawberry fabric softener 1l version 1 at Main Branch; units 0 to 14; KSh 2379.86 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -14 units','2026-10-29 14:11:55'),(510,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',9,'Daily sales summary #62: Standard madeira version 2 at Main Branch; units 0 to 100; KSh 46999.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -100 units','2026-10-29 14:12:19'),(511,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #55: Whitedent version 2 at Main Branch; units 90 to 10; KSh 2950.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by 80 units','2026-10-29 14:12:27'),(512,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #55: Whitedent version 2 at Main Branch; units 10 to 90; KSh 26550.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -80 units','2026-10-29 14:12:32'),(513,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #55: Whitedent version 2 at Main Branch; units 90 to 10; KSh 2950.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by 80 units','2026-10-29 14:12:36'),(514,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #55: Whitedent version 2 at Main Branch; units 10 to 90; KSh 26550.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -80 units','2026-10-29 14:12:40'),(515,39,30,'RELATIONSHIP_REQUESTED','RELATIONSHIP',32,'Requested a supply relationship with Target Supermarket','2026-10-29 14:55:55'),(516,1,5,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',32,'Accepted a supply relationship request','2026-10-29 14:57:09'),(517,39,30,'PRODUCT_CREATED','PRODUCT',21,'Created product brookside whole milk with initial barcode 61623456353627.','2026-10-29 15:04:57'),(518,39,30,'PRICE_SAVED','PRODUCT',30,'Saved COST price 35.00 for from 2026-10-29 with no scheduled end date','2026-10-29 15:06:33'),(519,39,30,'PRICE_SAVED','PRODUCT',30,'Saved SELLING price 50.00 for from 2026-10-29 with no scheduled end date','2026-10-29 15:06:33'),(520,39,30,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',30,'Published RRP for brookside whole milk version 1 at KSh 65.00 effective 2026-10-29. Previous effective recommendation: none. Notified 1 organisations.','2026-10-29 15:07:09'),(521,39,30,'CHANGE_CREATED','PRODUCT_CHANGE',10,'Prepared a change for brookside whole milk: barcode 61623456353627 to 67584947590687','2026-10-29 15:12:59'),(522,39,30,'CHANGE_PUBLISHED','PRODUCT_CHANGE',10,'Published the change for brookside whole milk to 1 organisations','2026-10-29 15:13:22'),(523,39,30,'SUPPLIER_COVERAGE_CREATED','SUPPLIER_COVERAGE',106,'Declared supplier coverage for Nairobi from 2026-10-29 with no planned end date.','2026-10-29 15:19:29'),(524,40,31,'PRODUCT_CREATED','PRODUCT',22,'Created product Fresh fri with initial barcode 20010000000005.','2026-10-29 16:25:25'),(525,40,31,'RELATIONSHIP_REQUESTED','RELATIONSHIP',33,'Requested a supply relationship with Fratres Limited Kitui','2026-10-29 16:27:51'),(526,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',33,'Accepted a supply relationship request','2026-10-29 16:30:25'),(527,40,31,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',50,'Appointed Fratres Limited Kitui for Fresh fri in Nairobi from 2026-10-29 to 2027-01-01 (non-exclusive).','2026-10-29 16:33:17'),(528,40,31,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',9,'Published product announcement for Fresh fri to 3 retailer organization(s).','2026-10-29 16:35:23'),(529,28,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',9,'Acknowledged receipt of product announcement #9.','2026-10-29 16:36:22'),(530,40,31,'PRICE_SAVED','PRODUCT',32,'Saved COST price 250.00 for from 2026-10-29 with no scheduled end date','2026-10-29 16:42:12'),(531,40,31,'PRICE_SAVED','PRODUCT',32,'Saved SELLING price 275.00 for from 2026-10-29 with no scheduled end date','2026-10-29 16:42:13'),(532,40,31,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',31,'Price Change Protection enabled.','2026-10-29 16:43:17'),(533,40,31,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',32,'Published RRP for Fresh fri version 1 at KSh 385.00 effective 2026-10-29. Previous effective recommendation: none. Notified 2 organisations.','2026-10-29 16:44:09'),(534,40,31,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',32,'Published RSP for Fresh fri version 1 at KSh 320.00 effective 2026-10-29. Previous effective recommendation: none. Notified 1 organisations.','2026-10-29 16:44:39'),(535,40,31,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',32,'Published RRP for Fresh fri version 1 at KSh 385.00 effective 2026-10-29. Previous effective recommendation: 385.00. Notified 2 organisations.','2026-10-29 17:47:02'),(536,28,3,'FINANCE_PAYMENT_CONFIRMED','FINANCE_PAYMENT',2,'Confirmed receipt of payment #2 for KES 10000.00. Posted balanced journal entries for both organisations.','2026-10-29 17:50:23'),(537,28,3,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',4,'Recorded outgoing payment #4 for KES 10000.00 to organisation 31; awaiting payee confirmation.','2026-10-29 17:51:12'),(538,28,3,'FINANCE_DOCUMENT_DRAFT_CREATED','FINANCE_DOCUMENT',4,'Created INVOICE draft INV-2026-001; total KES 13000.00.','2026-10-29 17:54:39'),(539,28,3,'FINANCE_DOCUMENT_ISSUED','FINANCE_DOCUMENT',4,'Issued INVOICE INV-2026-001 for KES 13000.00 and posted its journal entry.','2026-10-29 17:54:42'),(540,28,3,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',3,'Price Change Protection enabled.','2026-10-29 18:03:12'),(541,42,33,'RELATIONSHIP_REQUESTED','RELATIONSHIP',34,'Requested a supply relationship with Fratres Limited Kitui','2026-10-29 19:25:41'),(542,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',34,'Accepted a supply relationship request','2026-10-29 19:27:25'),(543,42,33,'PRODUCT_CREATED','PRODUCT',23,'Created product Digestive biscuits with initial barcode 68936476353748.','2026-10-29 19:33:10'),(544,42,33,'PRICE_SAVED','PRODUCT',33,'Saved COST price 59.00 for from 2026-10-29 with no scheduled end date','2026-10-29 19:34:21'),(545,42,33,'PRICE_SAVED','PRODUCT',33,'Saved SELLING price 90.00 for from 2026-10-29 with no scheduled end date','2026-10-29 19:34:21'),(546,42,33,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',51,'Appointed Fratres Limited Kitui for Digestive biscuits in Nairobi from 2026-10-29 with no planned end date (non-exclusive).','2026-10-29 19:35:01'),(547,42,33,'PRODUCT_ANNOUNCEMENT_PUBLISHED','PRODUCT_ANNOUNCEMENT',10,'Published product announcement for Digestive biscuits to 3 retailer organization(s).','2026-10-29 19:36:14'),(548,28,3,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',10,'Acknowledged receipt of product announcement #10.','2026-10-29 19:38:22'),(549,1,5,'PRODUCT_ANNOUNCEMENT_ACKNOWLEDGED','PRODUCT_ANNOUNCEMENT',10,'Acknowledged receipt of product announcement #10.','2026-10-29 19:39:35'),(550,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',5,'Published RRP for Muffins version 1 at KSh 250.00 effective 2026-10-29. Previous effective recommendation: none. Notified 2 organisations.','2026-10-29 19:49:39'),(551,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',5,'Published RSP for Muffins version 1 at KSh 200.00 effective 2026-10-29. Previous effective recommendation: none. Notified 1 organisations.','2026-10-29 19:50:31'),(552,7,2,'PROMOTION_CREATED','PROMOTION',8,'Saved draft campaign \"back to school\" (2026-10-29 to 2026-11-05) with 1 products and 2 retailers.','2026-10-29 19:53:41'),(553,7,2,'PROMOTION_SUBMITTED','PROMOTION',8,'Campaign \"back to school\": The campaign was submitted for approval.','2026-10-29 19:53:46'),(554,7,2,'PROMOTION_APPROVED','PROMOTION',8,'Campaign \"back to school\": The campaign was approved.','2026-10-29 19:53:52'),(555,1,5,'MANUFACTURER_CAMPAIGN_ACCEPTED','PROMOTION',8,'Campaign \"back to school\" action: accept.','2026-10-29 19:54:56'),(556,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',8,'Campaign \"back to school\" action: start_branch.','2026-10-29 19:54:59'),(557,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',8,'Campaign \"back to school\" action: start_branch.','2026-10-29 19:55:01'),(558,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_STARTED','PROMOTION',8,'Campaign \"back to school\" action: start_branch.','2026-10-29 19:55:02'),(559,42,33,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',33,'Price Change Protection enabled.','2026-10-30 03:22:08'),(560,42,33,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',33,'Published RRP for Digestive biscuits version 1 at KSh 185.00 effective 2026-10-30. Previous effective recommendation: none. Notified 2 organisations.','2026-10-30 03:26:55'),(561,42,33,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',33,'Published RSP for Digestive biscuits version 1 at KSh 145.00 effective 2026-10-30. Previous effective recommendation: none. Notified 1 organisations.','2026-10-30 03:27:07'),(562,42,33,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',33,'Published RSP for Digestive biscuits version 1 at KSh 145.00 effective 2026-10-30. Previous effective recommendation: 145.00. Notified 1 organisations.','2026-10-30 03:27:20'),(563,42,33,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',33,'Applied manufacturer price change for Digestive biscuits version 1: KSh 95.00; 1 organisation price rows and 0 branch price rows updated; effective 2026-10-30.','2026-10-30 03:28:03'),(564,42,33,'PRICE_SAVED','PRODUCT',33,'Saved manufacturer SELLING price 95.00 effective 2026-10-30; protection-enabled organisations were processed automatically.','2026-10-30 03:28:03'),(565,1,5,'PRICE_SAVED','PRODUCT',33,'Saved SELLING price 185.00 for from 2026-10-30 with no scheduled end date','2026-10-30 03:29:03'),(566,28,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',9,'Acknowledged a product change','2026-10-30 03:46:26'),(567,28,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',6,'Acknowledged a product change','2026-10-30 03:47:18'),(568,28,3,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',2,'Acknowledged a product change','2026-10-30 03:47:25'),(569,28,3,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',5,'Recorded outgoing payment #5 for KES 10000.00 to organisation 2; awaiting payee confirmation.','2026-10-30 03:48:34'),(570,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',8,'Saved outgoing LPO #8 to manufacturer organisation #33 with 1 product lines.','2026-10-30 03:49:37'),(571,28,3,'SUPPLIER_LPO_SUBMITTED','SUPPLY_ORDER',8,'Submitted outgoing LPO #8 to manufacturer organisation #33 with 1 product lines.','2026-10-30 03:49:40'),(572,42,33,'PRODUCT_CREATED','PRODUCT',24,'Created product Manji Digestive with initial barcode 65748478483993, selling price KSh 250.00 (VAT inclusive, STANDARD_RATED).','2026-10-30 06:54:25'),(573,1,5,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',6,'Recorded outgoing payment #6 for KES 50.00 to organisation 1; awaiting payee confirmation.','2026-10-30 09:16:26'),(574,42,33,'PRICE_SAVED','PRODUCT',34,'Saved COST price 120.00 for from 2026-10-30 with no scheduled end date','2026-10-30 11:47:35'),(575,42,33,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',33,'Applied manufacturer price change for Digestive biscuits version 1: KSh 100.00; 1 organisation price rows and 0 branch price rows updated; effective 2026-10-31.','2026-10-31 03:29:27'),(576,42,33,'PRICE_SAVED','PRODUCT',33,'Saved manufacturer SELLING price 100.00 effective 2026-10-31; protection-enabled organisations were processed automatically.','2026-10-31 03:29:27'),(577,42,33,'PRICE_SAVED','PRODUCT',33,'Saved SELLING price 95.00 for from 2026-10-31 with no scheduled end date','2026-10-31 03:33:32'),(578,1,5,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',7,'Recorded outgoing payment #7 for KES 2500000.00 to organisation 30; awaiting payee confirmation.','2026-11-01 05:51:59'),(579,11,11,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',52,'Appointed Fratres Limited Kitui for Quencher lime in Kitui from 2026-11-01 with no planned end date (non-exclusive).','2026-11-01 14:23:42'),(580,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',53,'Appointed Fratres Limited Kitui for Mill cookies in Kitui from 2026-11-01 with no planned end date (non-exclusive).','2026-11-01 14:32:56'),(581,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',54,'Appointed Fratres Limited Kitui for Mill cookies in Nairobi from 2026-11-01 with no planned end date (non-exclusive).','2026-11-01 14:37:06'),(582,1,5,'SUPPLY_ARRANGEMENT_ACCEPTED','PRODUCT_SUPPLIER',54,'Retailer accepted the supply of Mill cookies by Fratres Limited Kitui in Nairobi.','2026-11-01 14:42:01'),(583,7,2,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',2,'Price Change Protection enabled.','2026-11-01 23:48:40'),(584,7,2,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',2,'Price Change Protection disabled.','2026-11-01 23:48:59'),(585,7,2,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',2,'Price Change Protection disabled.','2026-11-01 23:49:04'),(586,7,2,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',2,'Price Change Protection disabled.','2026-11-01 23:49:05'),(587,11,11,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',11,'Price Change Protection enabled.','2026-11-02 00:18:47'),(588,11,11,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',11,'Price Change Protection disabled.','2026-11-02 00:18:58'),(589,11,11,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',11,'Price Change Protection enabled.','2026-11-02 00:19:29'),(590,7,2,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',2,'Price Change Protection enabled.','2026-11-02 05:26:15'),(591,7,2,'CHANGE_CREATED','PRODUCT_CHANGE',11,'Prepared a change for Mill cookies: barcode 61620034567834 to 65748658465394','2026-11-02 07:16:18'),(592,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',11,'Published the change for Mill cookies to 3 organisations','2026-11-02 07:16:20'),(593,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',9,'Saved outgoing LPO #9 to manufacturer organisation #33 with 1 product lines.','2026-11-02 16:42:54'),(594,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',9,'Saved outgoing LPO #9 to manufacturer organisation #33 with 0 product lines.','2026-11-02 16:42:59'),(595,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',9,'Saved outgoing LPO #9 to manufacturer organisation #33 with 1 product lines.','2026-11-02 16:43:03'),(596,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',9,'Saved outgoing LPO #9 to manufacturer organisation #33 with 1 product lines.','2026-11-02 16:43:07'),(597,28,3,'SUPPLIER_LPO_DRAFT_SAVED','SUPPLY_ORDER',9,'Saved outgoing LPO #9 to manufacturer organisation #33 with 1 product lines.','2026-11-02 16:43:13'),(598,28,3,'SUPPLIER_LPO_SUBMITTED','SUPPLY_ORDER',9,'Submitted outgoing LPO #9 to manufacturer organisation #33 with 1 product lines.','2026-11-02 16:43:42'),(599,42,33,'SUPPLY_ORDER_CONFIRMED','SUPPLY_ORDER',8,'Manufacturer acknowledged LPO LPO-000001 across 1 product line(s). Available: 300, short: 0, out of stock: 0.','2026-11-02 17:35:54'),(600,42,33,'SUPPLY_ORDER_CONFIRMED','SUPPLY_ORDER',9,'Manufacturer acknowledged LPO LPO-000002 across 1 product line(s). Available: 200, short: 100, out of stock: 0.','2026-11-02 17:36:41'),(601,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',55,'Appointed Fratres Limited Kitui for Heart Deli Bread in Nairobi from 2026-11-03 with no planned end date (non-exclusive).','2026-11-03 03:17:31'),(602,1,5,'SUPPLY_ARRANGEMENT_ACCEPTED','PRODUCT_SUPPLIER',55,'Retailer accepted the supply of Heart Deli Bread by Fratres Limited Kitui in Nairobi.','2026-11-03 03:18:14'),(603,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Mill Bakers (organisation 2): cost KSh 244.83 to KSh 244.83, selling price KSh 320.00 to KSh 340.00. Reason: manufacturer selling price change.','2026-11-03 03:48:46'),(604,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Fratres Limited Kitui (organisation 3): cost KSh 305.58 to KSh 340.00, selling price KSh 322.69 to KSh 359.04. Reason: manufacturer selling price change.','2026-11-03 03:48:46'),(605,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Target Supermarket (organisation 5): cost KSh 322.69 to KSh 359.04, selling price KSh 445.00 to KSh 499.99. Reason: manufacturer selling price change.','2026-11-03 03:48:46'),(606,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',2,'Applied manufacturer price change for Heart Deli Bread version 1: KSh 340.00; 5 organisation price rows and 0 branch price rows updated; effective 2026-11-03.','2026-11-03 03:48:46'),(607,1,5,'SUPPLY_ARRANGEMENT_DECLINED','PRODUCT_SUPPLIER',55,'Retailer declined the supply of Heart Deli Bread by Fratres Limited Kitui in Nairobi.','2026-11-03 03:55:27'),(608,1,5,'SUPPLY_ARRANGEMENT_ACCEPTED','PRODUCT_SUPPLIER',55,'Retailer accepted the supply of Heart Deli Bread by Fratres Limited Kitui in Nairobi.','2026-11-03 03:56:24'),(609,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Mill Bakers (organisation 2): cost KSh 244.83 to KSh 244.83, selling price KSh 340.00 to KSh 360.00. Reason: manufacturer selling price change.','2026-11-03 04:02:47'),(610,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Fratres Limited Kitui (organisation 3): cost KSh 340.00 to KSh 360.00, selling price KSh 359.04 to KSh 380.16. Reason: manufacturer selling price change.','2026-11-03 04:02:47'),(611,7,2,'PRICE_CASCADE_ORGANISATION_ADJUSTED','PRODUCT_VERSION',2,'Automatic cascade initiated by Mill Bakers for Heart Deli Bread version 1, effective 2026-11-03. Target Supermarket (organisation 5): cost KSh 359.04 to KSh 380.16, selling price KSh 499.99 to KSh 529.99. Reason: manufacturer selling price change.','2026-11-03 04:02:47'),(612,7,2,'PRODUCT_PRICE_CASCADE_APPLIED','PRODUCT_VERSION',2,'Applied manufacturer price change for Heart Deli Bread version 1: KSh 360.00; 5 organisation price rows and 0 branch price rows updated; effective 2026-11-03.','2026-11-03 04:02:47'),(613,7,2,'RELATIONSHIP_ENDED','RELATIONSHIP',2,'Ended a supply relationship','2026-11-03 09:22:17'),(614,7,2,'RELATIONSHIP_REQUESTED','RELATIONSHIP',35,'Requested a supply relationship with Fratres Limited Kitui','2026-11-03 09:23:56'),(615,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',35,'Accepted a supply relationship request','2026-11-03 09:24:28'),(616,28,3,'RELATIONSHIP_NOTICE_GIVEN','RELATIONSHIP',35,'Fratres Limited Kitui gave notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui. It ends on 2026-11-18, and the appointments and offers between them end on the same date. Replacements are still needed for: Heart Deli Bread (Nairobi), Mill cookies (Kitui), Mill cookies (Nairobi).','2026-11-04 01:14:50'),(617,28,3,'RELATIONSHIP_NOTICE_WITHDRAWN','RELATIONSHIP',35,'The notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui was withdrawn by Fratres Limited Kitui. Supply continues.','2026-11-04 01:15:10'),(618,7,2,'PRODUCT_TAX_CLASSIFICATION_DECLARED','PRODUCT_TAX_CLASSIFICATION',42,'Declared EXEMPT tax classification for Heart Deli Bread version 1 (400g) from 2026-11-04 with no planned end date. Rate at start: 0.00%. Previous open-ended classification closure was requested.','2026-11-04 10:54:49'),(619,7,2,'PRODUCT_TAX_REPRICED','PRODUCT_VERSION',2,'Tax change repricing for Heart Deli Bread version 1 (400g), effective 2026-11-04: Manufacturer selling price KSh 360.00 to KSh 310.34.','2026-11-04 10:54:49'),(620,7,2,'PRODUCT_TAX_REPRICED','PRODUCT_VERSION',2,'Tax change repricing for Heart Deli Bread version 1 (400g), effective 2026-11-04: RSP KSh 352.00 to KSh 303.45.','2026-11-04 10:54:49'),(621,7,2,'PRODUCT_TAX_REPRICED','PRODUCT_VERSION',2,'Tax change repricing for Heart Deli Bread version 1 (400g), effective 2026-11-04: RRP KSh 400.00 to KSh 344.83.','2026-11-04 10:54:49'),(622,35,28,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',28,'Price Change Protection enabled.','2026-11-04 12:20:12'),(623,11,11,'PRICE_SAVED','PRODUCT',17,'Saved SELLING price 110.00 for from 2026-11-04 with no scheduled end date','2026-11-04 12:45:53'),(624,1,5,'PRICE_SAVED','PRODUCT',2,'Saved SELLING price 390.00 for from 2026-11-05 with no scheduled end date','2026-11-04 22:00:33'),(625,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',20,'Daily sales summary #63: Cadburry classic version 1 at Main Branch; units 0 to 15; KSh 1275.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -15 units','2026-11-05 13:28:45'),(626,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',3,'Daily sales summary #64: Madeira Cake version 2 at Main Branch; units 0 to 2; KSh 300.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -2 units','2026-11-05 13:29:08'),(627,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',1,'Daily sales summary #65: Whitedent version 2 at Main Branch; units 0 to 10; KSh 2950.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -10 units','2026-11-05 13:29:26'),(628,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',11,'Daily sales summary #66: Omo washing powder version 2 at Main Branch; units 0 to 100; KSh 35500.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -100 units','2026-11-05 13:29:40'),(629,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',8,'Daily sales summary #67: Mill cookies version 1 at Main Branch; units 0 to 95; KSh 37525.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -95 units','2026-11-05 13:29:51'),(630,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',2,'Daily sales summary #68: Heart Deli Bread version 1 at Main Branch; units 0 to 90; KSh 35100.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -90 units','2026-11-05 13:30:00'),(631,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',2,'Acknowledged a product change','2026-11-06 01:34:57'),(632,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',1,'Acknowledged a product change','2026-11-06 01:35:01'),(633,7,2,'CHANGE_CREATED','PRODUCT_CHANGE',12,'Prepared a change for Demo Bread: barcode 60000567383955 to 67840958048503','2026-11-06 07:16:07'),(634,7,2,'CHANGE_PUBLISHED','PRODUCT_CHANGE',12,'Published the change for Demo Bread to 3 organisations','2026-11-06 07:16:11'),(635,1,5,'CHANGE_ACKNOWLEDGED','PRODUCT_CHANGE',12,'Acknowledged a product change','2026-11-06 07:17:33'),(636,7,2,'PRICE_SAVED','PRODUCT',5,'Saved SELLING price 150.00 for from 2026-11-06 with no scheduled end date','2026-11-06 07:22:09'),(637,7,2,'FINANCE_PAYMENT_CONFIRMED','FINANCE_PAYMENT',5,'Confirmed receipt of payment #5 for KES 10000.00. Posted balanced journal entries for both organisations.','2026-11-06 07:36:05'),(638,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',8,'Campaign \"back to school\" action: complete_branch.','2026-11-07 11:37:41'),(639,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',8,'Campaign \"back to school\" action: complete_branch.','2026-11-07 11:37:44'),(640,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',8,'Campaign \"back to school\" action: complete_branch.','2026-11-07 11:37:48'),(641,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',5,'Campaign \"Testing promotional campaings\" action: complete_branch.','2026-11-07 11:38:07'),(642,7,2,'PRODUCT_CREATED','PRODUCT',25,'Created product High Madeira with initial barcode 67568399903748, selling price KSh 300.00 (VAT inclusive, EXEMPT).','2026-11-07 13:29:51'),(643,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',37,'Published RRP for High Madeira version 1 at KSh 395.00 effective 2026-11-07. Previous effective recommendation: none. Notified 2 organisations.','2026-11-07 13:30:27'),(644,7,2,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',37,'Published RSP for High Madeira version 1 at KSh 355.00 effective 2026-11-07. Previous effective recommendation: none. Notified 1 organisations.','2026-11-07 13:30:42'),(645,7,2,'PRICE_SAVED','PRODUCT',37,'Saved COST price 250.00 for from 2026-11-07 with no scheduled end date','2026-11-07 13:31:43'),(646,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',56,'Appointed Fratres Limited Kitui for High Madeira in Nairobi from 2026-11-07 to 2026-11-08 (non-exclusive).','2026-11-07 13:32:27'),(647,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',19,'Daily sales summary #69: pampers baby pants version 1 at Main Branch; units 0 to 3; KSh 480.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -3 units','2026-11-07 13:50:48'),(648,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',13,'Daily sales summary #70: Ariel lavender 5kg version 1 at Main Branch; units 0 to 14; KSh 3780.00 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -14 units','2026-11-07 13:51:17'),(649,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',58,'Appointed Fratres Limited Kitui for Muffins in Nairobi from 2026-11-08 with no planned end date (non-exclusive).','2026-11-08 04:07:02'),(650,7,2,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',59,'Proposed the appointment of Fratres Limited Kitui for Mill cookies in Mombasa from 2026-11-08 with no planned end date (non-exclusive).','2026-11-08 04:50:40'),(651,28,3,'PRODUCT_DISTRIBUTION_ACCEPTED','PRODUCT_SUPPLIER',59,'Fratres Limited Kitui accepted the appointment to supply Mill cookies in Mombasa.','2026-11-08 04:52:17'),(652,43,34,'PRODUCT_CREATED','PRODUCT',26,'Created product Gold crown with initial barcode 67498596796959, selling price KSh 55.00 (VAT inclusive, EXEMPT).','2026-11-08 12:56:48'),(653,43,34,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',38,'Published RRP for Gold crown version 1 at KSh 70.00 effective 2026-11-08. Previous effective recommendation: none. Notified 0 organisations.','2026-11-08 12:58:12'),(654,43,34,'PRICE_RECOMMENDATION_PUBLISHED','PRODUCT_VERSION',38,'Published RSP for Gold crown version 1 at KSh 60.00 effective 2026-11-08. Previous effective recommendation: none. Notified 0 organisations.','2026-11-08 12:58:26'),(655,43,34,'RELATIONSHIP_REQUESTED','RELATIONSHIP',36,'Requested a supply relationship with Fratres Limited Kitui','2026-11-08 12:59:05'),(656,28,3,'RELATIONSHIP_ACCEPTED','RELATIONSHIP',36,'Accepted a supply relationship request','2026-11-08 13:00:42'),(657,43,34,'PRICE_SAVED','PRODUCT',38,'Saved COST price 45.00 for from 2026-11-08 with no scheduled end date','2026-11-08 13:02:13'),(658,43,34,'PRODUCT_DISTRIBUTION_APPOINTED','PRODUCT_SUPPLIER',60,'Proposed the appointment of Fratres Limited Kitui for Gold crown in Nairobi from 2026-11-08 with no planned end date (non-exclusive).','2026-11-08 13:02:48'),(659,1,5,'SUPPLY_ARRANGEMENT_ACCEPTED','PRODUCT_SUPPLIER',59,'Retailer accepted the supply of Mill cookies by Fratres Limited Kitui in Mombasa.','2026-11-10 01:47:44'),(660,1,5,'SUPPLY_ARRANGEMENT_ACCEPTED','PRODUCT_SUPPLIER',58,'Retailer accepted the supply of Muffins by Fratres Limited Kitui in Nairobi.','2026-11-10 01:48:03'),(661,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',14,'Daily sales summary #71: Aeriel stawberry fabric softener 1l version 1 at Main Branch; units 0 to 15; KSh 2549.85 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -15 units','2026-11-10 10:29:50'),(662,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',7,'Campaign \"Holiday Times\" action: complete_branch.','2026-11-11 09:29:53'),(663,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',7,'Campaign \"Holiday Times\" action: complete_branch.','2026-11-11 09:29:55'),(664,1,5,'MANUFACTURER_CAMPAIGN_BRANCH_COMPLETED','PROMOTION',7,'Campaign \"Holiday Times\" action: complete_branch.','2026-11-11 09:29:56'),(665,1,5,'DAILY_SALES_SUMMARY_SAVED','PRODUCT',16,'Daily sales summary #72: Aeriel washing powder 1kg version 1 at Main Branch; units 0 to 15; KSh 2399.85 (VAT_INCLUSIVE); tax status CALCULATED; stock adjusted by -15 units','2026-11-11 09:30:12'),(666,1,5,'FINANCE_PAYMENT_RECORDED','FINANCE_PAYMENT',8,'Recorded outgoing payment #8 for KES 600000.00 to organisation 3; awaiting payee confirmation.','2026-11-11 09:31:59'),(667,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 1 product lines.','2026-11-11 09:33:41'),(668,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 2 product lines.','2026-11-11 09:33:44'),(669,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 1 product lines.','2026-11-11 09:33:49'),(670,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 2 product lines.','2026-11-11 09:33:56'),(671,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 2 product lines.','2026-11-11 09:33:59'),(672,1,5,'SUPPLY_ORDER_DRAFT_SAVED','SUPPLY_ORDER',10,'Saved draft LPO #10 for supplier organisation #3 with 3 product lines.','2026-11-11 09:34:03'),(673,1,5,'SUPPLY_ORDER_SUBMITTED','SUPPLY_ORDER',10,'Submitted LPO #10 to supplier organisation #3 with 3 product lines.','2026-11-11 09:34:06'),(674,1,5,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',5,'Price Change Protection disabled.','2026-11-11 09:41:31'),(675,1,5,'PRICE_PROTECTION_SETTING_CHANGED','ORGANIZATION',5,'Price Change Protection enabled.','2026-11-11 09:42:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_attempts`
--

DROP TABLE IF EXISTS `auth_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_attempts` (
  `attempt_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scope` enum('LOGIN','ADMIN_CONFIRM') NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_auth_attempt_identifier` (`scope`,`identifier`,`attempted_at`),
  KEY `idx_auth_attempt_ip` (`scope`,`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_attempts`
--

LOCK TABLES `auth_attempts` WRITE;
/*!40000 ALTER TABLE `auth_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_announcement_recipients`
--

DROP TABLE IF EXISTS `branch_announcement_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_announcement_recipients` (
  `recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `status` enum('UNREAD','READ','ACKNOWLEDGED') NOT NULL DEFAULT 'UNREAD',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  PRIMARY KEY (`recipient_id`),
  UNIQUE KEY `uq_branch_announcement_branch` (`announcement_id`,`branch_id`),
  KEY `idx_branch_recipient_branch_status` (`organization_id`,`branch_id`,`status`),
  KEY `fk_branch_recipient_announcement` (`announcement_id`,`organization_id`),
  CONSTRAINT `fk_branch_recipient_announcement` FOREIGN KEY (`announcement_id`, `organization_id`) REFERENCES `branch_announcements` (`announcement_id`, `organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_recipient_branch` FOREIGN KEY (`organization_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_announcement_recipients`
--

LOCK TABLES `branch_announcement_recipients` WRITE;
/*!40000 ALTER TABLE `branch_announcement_recipients` DISABLE KEYS */;
INSERT INTO `branch_announcement_recipients` VALUES (1,1,5,26,'UNREAD','2026-10-23 03:55:26',NULL,NULL),(2,2,5,36,'ACKNOWLEDGED','2026-10-23 11:28:51','2026-10-23 14:29:01','2026-10-23 14:29:44');
/*!40000 ALTER TABLE `branch_announcement_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_announcement_replies`
--

DROP TABLE IF EXISTS `branch_announcement_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_announcement_replies` (
  `reply_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `sender_user_id` int(10) unsigned NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reply_id`),
  KEY `idx_branch_reply_thread` (`announcement_id`,`organization_id`,`branch_id`,`reply_id`),
  KEY `fk_branch_reply_branch` (`organization_id`,`branch_id`),
  KEY `fk_branch_reply_sender` (`sender_user_id`),
  CONSTRAINT `fk_branch_reply_announcement` FOREIGN KEY (`announcement_id`, `organization_id`) REFERENCES `branch_announcements` (`announcement_id`, `organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_reply_branch` FOREIGN KEY (`organization_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_reply_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_announcement_replies`
--

LOCK TABLES `branch_announcement_replies` WRITE;
/*!40000 ALTER TABLE `branch_announcement_replies` DISABLE KEYS */;
INSERT INTO `branch_announcement_replies` VALUES (1,2,5,36,38,'hello head office acknowledge received','2026-10-23 11:29:23');
/*!40000 ALTER TABLE `branch_announcement_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_announcements`
--

DROP TABLE IF EXISTS `branch_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_announcements` (
  `announcement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `sender_user_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`announcement_id`),
  UNIQUE KEY `uq_branch_announcements_id_org` (`announcement_id`,`organization_id`),
  KEY `idx_branch_announcements_org_created` (`organization_id`,`created_at`),
  KEY `fk_branch_announcements_sender` (`sender_user_id`),
  CONSTRAINT `fk_branch_announcements_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_announcements_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_announcements`
--

LOCK TABLES `branch_announcements` WRITE;
/*!40000 ALTER TABLE `branch_announcements` DISABLE KEYS */;
INSERT INTO `branch_announcements` VALUES (1,5,1,'Branch Communication Test','This is a test of direct communication from head office to an individual branch. Please confirm receipt.','2026-10-23 03:55:26'),(2,5,1,'Test message','This is a test message dont reply','2026-10-23 11:28:51');
/*!40000 ALTER TABLE `branch_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_instruction_targets`
--

DROP TABLE IF EXISTS `branch_instruction_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_instruction_targets` (
  `instruction_target_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instruction_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `status` enum('PENDING','ACKNOWLEDGED','IN_PROGRESS','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`instruction_target_id`),
  UNIQUE KEY `uq_branch_instruction_target` (`instruction_id`,`branch_id`),
  KEY `idx_branch_instruction_targets_instruction` (`instruction_id`),
  KEY `idx_branch_instruction_targets_branch` (`branch_id`),
  CONSTRAINT `fk_branch_instruction_targets_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_instruction_targets_instruction` FOREIGN KEY (`instruction_id`) REFERENCES `branch_instructions` (`instruction_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_instruction_targets`
--

LOCK TABLES `branch_instruction_targets` WRITE;
/*!40000 ALTER TABLE `branch_instruction_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_instruction_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_instructions`
--

DROP TABLE IF EXISTS `branch_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_instructions` (
  `instruction_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `instruction_text` text NOT NULL,
  `instruction_type` enum('PROMOTION','STOCK','PRODUCT','GENERAL') NOT NULL DEFAULT 'GENERAL',
  `status` enum('DRAFT','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`instruction_id`),
  KEY `idx_branch_instructions_organization` (`organization_id`),
  KEY `idx_branch_instructions_creator` (`created_by`),
  CONSTRAINT `fk_branch_instructions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_branch_instructions_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_instructions`
--

LOCK TABLES `branch_instructions` WRITE;
/*!40000 ALTER TABLE `branch_instructions` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_instructions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `branch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_name` varchar(150) NOT NULL,
  `location` varchar(255) NOT NULL,
  `area_id` int(10) unsigned DEFAULT NULL,
  `is_main_branch` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `main_branch_key` int(10) unsigned GENERATED ALWAYS AS (case when `is_main_branch` = 1 then `organization_id` else NULL end) STORED,
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `uq_branches_org_branch` (`organization_id`,`branch_id`),
  UNIQUE KEY `uq_branches_one_main` (`main_branch_key`),
  KEY `idx_branches_area_id` (`area_id`),
  CONSTRAINT `fk_branches_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_branches_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (3,1,'Main Branch','Kenya',NULL,1,'ACTIVE','2026-10-05 08:51:51',1),(4,2,'Main Branch','Kenya',1,1,'ACTIVE','2026-10-05 08:51:51',2),(5,3,'Main Branch','Kitui, Kenya',NULL,1,'ACTIVE','2026-10-05 08:51:51',3),(7,5,'Main Branch','Nairobi Kenya',1,1,'ACTIVE','2026-10-05 08:51:51',5),(11,9,'Procotro and Gamble','Thika',NULL,1,'ACTIVE','2026-10-07 03:46:29',9),(12,10,'summer africa','Thika road',NULL,1,'ACTIVE','2026-10-07 07:36:13',10),(13,11,'Excell chemical main','Rongai',NULL,1,'ACTIVE','2026-10-07 07:38:37',11),(14,12,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-08 01:35:56',12),(15,13,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-09 11:07:46',13),(16,14,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-09 23:55:46',14),(17,15,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-09 23:56:34',15),(18,16,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-10 00:12:52',16),(19,17,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-10 00:31:14',17),(20,18,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-10 04:46:01',18),(21,19,'Main','Nairobi',NULL,1,'ACTIVE','2026-10-10 10:36:53',19),(22,20,'Main','Nairobi',NULL,1,'ACTIVE','2026-10-10 10:41:01',20),(23,21,'Main','Nairobi',NULL,1,'ACTIVE','2026-10-10 10:42:50',21),(24,22,'Main','Nairobi',NULL,1,'ACTIVE','2026-10-10 10:42:56',22),(25,5,'Westlands 134903','Mombasa',NULL,0,'INACTIVE','2026-10-10 10:49:03',NULL),(26,5,'Target Supermarket','Mombasa',29,0,'ACTIVE','2026-10-10 10:49:50',NULL),(27,23,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-10 16:05:14',23),(28,24,'Main Branch','Nairobi',NULL,1,'ACTIVE','2026-10-10 16:41:59',24),(29,25,'testmain','thika road',NULL,1,'ACTIVE','2026-10-14 12:42:00',25),(30,26,'uniliver main','Thika Kiambu',NULL,1,'ACTIVE','2026-10-20 06:36:09',26),(31,26,'uniliver','Kitui Kenya',NULL,0,'ACTIVE','2026-10-20 06:55:11',NULL),(32,27,'Cadbury Main','Nairobi Kenya',NULL,1,'ACTIVE','2026-10-20 08:06:35',27),(33,27,'Cadbury','Machakos',NULL,0,'INACTIVE','2026-10-20 08:19:23',NULL),(34,28,'Hasbah Main','Thika',NULL,1,'ACTIVE','2026-10-20 13:15:11',28),(35,29,'Magunas Main','Nakuru',NULL,1,'ACTIVE','2026-10-22 14:17:06',29),(36,5,'Target Machakos','Machakos',2,0,'ACTIVE','2026-10-23 05:09:30',NULL),(37,30,'Brookside Nairobi','Nairobi',1,1,'ACTIVE','2026-10-29 14:54:20',30),(38,31,'Mombasa head office','wahunzi street, Mombasa, Kenya',29,1,'ACTIVE','2026-10-29 16:18:24',31),(39,32,'Greenvast','Garissa Rd/mwingi',4,1,'ACTIVE','2026-10-29 18:59:59',32),(40,33,'Manji headquaters','kisauni',29,1,'ACTIVE','2026-10-29 19:24:56',33),(41,34,'kcc Main','Changamwe, Mombasa',29,1,'ACTIVE','2026-11-08 12:54:06',34);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `change_recipients`
--

DROP TABLE IF EXISTS `change_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_recipients` (
  `change_recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_change_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `notification_status` enum('PENDING','SENT','VIEWED','ACKNOWLEDGED','CLARIFICATION_REQUESTED','RESOLVED') NOT NULL DEFAULT 'PENDING',
  `viewed_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`change_recipient_id`),
  UNIQUE KEY `uq_change_recipient` (`product_change_id`,`organization_id`),
  KEY `fk_change_recipients_organization` (`organization_id`),
  KEY `fk_change_recipients_acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `fk_change_recipients_acknowledged_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_change_recipients_change` FOREIGN KEY (`product_change_id`) REFERENCES `product_changes` (`product_change_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_change_recipients_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `change_recipients`
--

LOCK TABLES `change_recipients` WRITE;
/*!40000 ALTER TABLE `change_recipients` DISABLE KEYS */;
INSERT INTO `change_recipients` VALUES (1,1,5,'ACKNOWLEDGED',NULL,'2026-11-06 04:35:01',1,'2026-10-04 12:51:08'),(2,2,3,'ACKNOWLEDGED',NULL,'2026-10-30 06:47:25',28,'2026-10-05 03:12:12'),(3,2,5,'ACKNOWLEDGED',NULL,'2026-11-06 04:34:57',1,'2026-10-05 03:12:12'),(8,5,3,'ACKNOWLEDGED',NULL,'2026-10-15 04:37:05',4,'2026-10-15 01:36:42'),(9,5,5,'ACKNOWLEDGED',NULL,'2026-10-15 04:37:26',1,'2026-10-15 01:36:42'),(10,6,3,'ACKNOWLEDGED',NULL,'2026-10-30 06:47:18',28,'2026-10-15 14:19:15'),(11,6,5,'ACKNOWLEDGED',NULL,'2026-10-15 19:44:08',1,'2026-10-15 14:19:15'),(12,7,3,'ACKNOWLEDGED',NULL,'2026-10-19 08:26:00',28,'2026-10-16 09:47:05'),(13,7,5,'ACKNOWLEDGED',NULL,'2026-10-16 12:47:37',1,'2026-10-16 09:47:05'),(14,8,5,'ACKNOWLEDGED',NULL,'2026-10-20 09:45:39',1,'2026-10-20 06:44:07'),(15,9,3,'ACKNOWLEDGED',NULL,'2026-10-30 06:46:26',28,'2026-10-28 17:12:52'),(16,9,5,'SENT',NULL,NULL,NULL,'2026-10-28 17:12:52'),(17,9,29,'SENT',NULL,NULL,NULL,'2026-10-28 17:12:52'),(18,10,5,'SENT',NULL,NULL,NULL,'2026-10-29 15:13:22'),(19,11,3,'SENT',NULL,NULL,NULL,'2026-11-02 07:16:20'),(20,11,5,'SENT',NULL,NULL,NULL,'2026-11-02 07:16:20'),(21,11,29,'SENT',NULL,NULL,NULL,'2026-11-02 07:16:20'),(22,12,3,'SENT',NULL,NULL,NULL,'2026-11-06 07:16:11'),(23,12,5,'ACKNOWLEDGED',NULL,'2026-11-06 10:17:33',1,'2026-11-06 07:16:11'),(24,12,29,'SENT',NULL,NULL,NULL,'2026-11-06 07:16:11');
/*!40000 ALTER TABLE `change_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compensation_claims`
--

DROP TABLE IF EXISTS `compensation_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compensation_claims` (
  `claim_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) unsigned NOT NULL,
  `retailer_id` int(10) unsigned NOT NULL,
  `claim_date` date NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','SUBMITTED','UNDER_REVIEW','CLARIFICATION_REQUIRED','APPROVED','REJECTED','SETTLED','CLOSED') NOT NULL DEFAULT 'DRAFT',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `submitted_by` int(10) unsigned NOT NULL,
  `send_approved_by` int(10) unsigned DEFAULT NULL,
  `send_approved_at` datetime DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`claim_id`),
  KEY `fk_compensation_claims_promotion` (`promotion_id`),
  KEY `fk_compensation_claims_retailer` (`retailer_id`),
  KEY `fk_compensation_claims_submitted_by` (`submitted_by`),
  KEY `fk_compensation_claims_reviewed_by` (`reviewed_by`),
  KEY `fk_compensation_claims_send_approved_by` (`send_approved_by`),
  CONSTRAINT `fk_compensation_claims_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_compensation_claims_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_compensation_claims_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compensation_claims_send_approved_by` FOREIGN KEY (`send_approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compensation_claims_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compensation_claims`
--

LOCK TABLES `compensation_claims` WRITE;
/*!40000 ALTER TABLE `compensation_claims` DISABLE KEYS */;
INSERT INTO `compensation_claims` VALUES (1,1,5,'2026-09-30','SUBMITTED',1700.00,3,NULL,NULL,NULL,NULL,'Compensation claim for Whitedent units sold during the September promotion.','2026-10-04 13:07:31');
/*!40000 ALTER TABLE `compensation_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compensation_items`
--

DROP TABLE IF EXISTS `compensation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compensation_items` (
  `compensation_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `claim_id` int(10) unsigned NOT NULL,
  `promotion_product_id` int(10) unsigned NOT NULL,
  `eligible_units` int(10) unsigned NOT NULL,
  `compensation_per_unit` decimal(12,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`compensation_item_id`),
  UNIQUE KEY `uq_compensation_item` (`claim_id`,`promotion_product_id`),
  KEY `fk_compensation_items_product` (`promotion_product_id`),
  CONSTRAINT `fk_compensation_items_claim` FOREIGN KEY (`claim_id`) REFERENCES `compensation_claims` (`claim_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compensation_items_product` FOREIGN KEY (`promotion_product_id`) REFERENCES `promotion_products` (`promotion_product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compensation_items`
--

LOCK TABLES `compensation_items` WRITE;
/*!40000 ALTER TABLE `compensation_items` DISABLE KEYS */;
INSERT INTO `compensation_items` VALUES (1,1,1,85,20.00,1700.00,'2026-10-04 13:08:37');
/*!40000 ALTER TABLE `compensation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `conversation_participant_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `joined_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`conversation_participant_id`),
  UNIQUE KEY `uq_conversation_participant` (`conversation_id`,`organization_id`),
  KEY `fk_conversation_participants_organization` (`organization_id`),
  CONSTRAINT `fk_conversation_participants_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_conversation_participants_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
INSERT INTO `conversation_participants` VALUES (1,2,5,'2026-10-28 21:23:27'),(2,2,11,'2026-10-28 21:23:27'),(3,3,5,'2026-10-28 21:24:46'),(4,3,27,'2026-10-28 21:24:46'),(5,4,5,'2026-10-28 21:25:09'),(6,4,29,'2026-10-28 21:25:09'),(7,5,5,'2026-10-28 21:33:18'),(8,5,3,'2026-10-28 21:33:18'),(9,6,5,'2026-10-28 21:39:09'),(10,6,1,'2026-10-28 21:39:09'),(11,7,11,'2026-10-28 22:15:03'),(12,7,27,'2026-10-28 22:15:03'),(13,8,11,'2026-10-28 22:15:07'),(14,8,28,'2026-10-28 22:15:07'),(15,9,3,'2026-10-28 22:24:58'),(16,9,11,'2026-10-28 22:24:58'),(17,10,30,'2026-10-29 17:58:09'),(18,10,5,'2026-10-29 17:58:09'),(19,11,31,'2026-10-29 19:38:13'),(20,11,3,'2026-10-29 19:38:13'),(21,12,33,'2026-10-29 22:29:16'),(22,12,3,'2026-10-29 22:29:16'),(23,13,5,'2026-11-11 12:40:33'),(24,13,31,'2026-11-11 12:40:33');
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `conversation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(200) NOT NULL,
  `context_type` enum('PRODUCT_CHANGE','SUPPLY_CHANGE','PROMOTION','CLAIM','RELATIONSHIP','DIRECT') DEFAULT NULL,
  `context_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  KEY `fk_conversations_user` (`created_by`),
  KEY `idx_conversations_context` (`context_type`,`context_id`),
  CONSTRAINT `fk_conversations_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
INSERT INTO `conversations` VALUES (1,'Change: Madeira Cake','PRODUCT_CHANGE',5,7,'2026-10-15 06:50:26'),(2,'Conversation with Excel Chemicals','DIRECT',NULL,1,'2026-10-28 18:23:27'),(3,'Conversation with Cadbury limited','DIRECT',NULL,1,'2026-10-28 18:24:46'),(4,'Conversation with Magunas Supermarkets','DIRECT',NULL,1,'2026-10-28 18:25:09'),(5,'Conversation with Fratres Limited Kitui','DIRECT',NULL,1,'2026-10-28 18:33:18'),(6,'Conversation with Chandaria Industries','DIRECT',NULL,1,'2026-10-28 18:39:09'),(7,'Conversation with Cadbury limited','DIRECT',NULL,11,'2026-10-28 19:15:03'),(8,'Conversation with Hasbah','DIRECT',NULL,11,'2026-10-28 19:15:07'),(9,'Conversation with Excel Chemicals','DIRECT',NULL,28,'2026-10-28 19:24:58'),(10,'Conversation with Target Supermarket','DIRECT',NULL,39,'2026-10-29 14:58:09'),(11,'Conversation with Fratres Limited Kitui','DIRECT',NULL,40,'2026-10-29 16:38:13'),(12,'Conversation with Fratres Limited Kitui','DIRECT',NULL,42,'2026-10-29 19:29:16'),(13,'Conversation with Pwani oil products','DIRECT',NULL,1,'2026-11-11 09:40:33');
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `distribution_areas`
--

DROP TABLE IF EXISTS `distribution_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distribution_areas` (
  `area_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `area_name` varchar(100) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`area_id`),
  UNIQUE KEY `area_name` (`area_name`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `distribution_areas`
--

LOCK TABLES `distribution_areas` WRITE;
/*!40000 ALTER TABLE `distribution_areas` DISABLE KEYS */;
INSERT INTO `distribution_areas` VALUES (1,'Nairobi','ACTIVE','2026-10-04 12:27:12'),(2,'Machakos','ACTIVE','2026-10-04 12:27:12'),(3,'Kajiado','ACTIVE','2026-10-04 12:27:12'),(4,'Kitui','ACTIVE','2026-10-04 12:27:12'),(5,'Baringo','ACTIVE','2026-10-24 10:49:30'),(6,'Bomet','ACTIVE','2026-10-24 10:49:30'),(7,'Bungoma','ACTIVE','2026-10-24 10:49:30'),(8,'Busia','ACTIVE','2026-10-24 10:49:30'),(9,'Elgeyo-Marakwet','ACTIVE','2026-10-24 10:49:30'),(10,'Embu','ACTIVE','2026-10-24 10:49:30'),(11,'Garissa','ACTIVE','2026-10-24 10:49:30'),(12,'Homa Bay','ACTIVE','2026-10-24 10:49:30'),(13,'Isiolo','ACTIVE','2026-10-24 10:49:30'),(14,'Kakamega','ACTIVE','2026-10-24 10:49:30'),(15,'Kericho','ACTIVE','2026-10-24 10:49:30'),(16,'Kiambu','ACTIVE','2026-10-24 10:49:30'),(17,'Kilifi','ACTIVE','2026-10-24 10:49:30'),(18,'Kirinyaga','ACTIVE','2026-10-24 10:49:30'),(19,'Kisii','ACTIVE','2026-10-24 10:49:30'),(20,'Kisumu','ACTIVE','2026-10-24 10:49:30'),(21,'Kwale','ACTIVE','2026-10-24 10:49:30'),(22,'Laikipia','ACTIVE','2026-10-24 10:49:30'),(23,'Lamu','ACTIVE','2026-10-24 10:49:30'),(24,'Makueni','ACTIVE','2026-10-24 10:49:30'),(25,'Mandera','ACTIVE','2026-10-24 10:49:30'),(26,'Marsabit','ACTIVE','2026-10-24 10:49:30'),(27,'Meru','ACTIVE','2026-10-24 10:49:30'),(28,'Migori','ACTIVE','2026-10-24 10:49:30'),(29,'Mombasa','ACTIVE','2026-10-24 10:49:30'),(30,'Murang\'a','ACTIVE','2026-10-24 10:49:30'),(31,'Nakuru','ACTIVE','2026-10-24 10:49:30'),(32,'Nandi','ACTIVE','2026-10-24 10:49:30'),(33,'Narok','ACTIVE','2026-10-24 10:49:30'),(34,'Nyamira','ACTIVE','2026-10-24 10:49:30'),(35,'Nyandarua','ACTIVE','2026-10-24 10:49:30'),(36,'Nyeri','ACTIVE','2026-10-24 10:49:30'),(37,'Samburu','ACTIVE','2026-10-24 10:49:30'),(38,'Siaya','ACTIVE','2026-10-24 10:49:30'),(39,'Taita-Taveta','ACTIVE','2026-10-24 10:49:30'),(40,'Tana River','ACTIVE','2026-10-24 10:49:30'),(41,'Tharaka-Nithi','ACTIVE','2026-10-24 10:49:30'),(42,'Trans Nzoia','ACTIVE','2026-10-24 10:49:30'),(43,'Turkana','ACTIVE','2026-10-24 10:49:30'),(44,'Uasin Gishu','ACTIVE','2026-10-24 10:49:30'),(45,'Vihiga','ACTIVE','2026-10-24 10:49:30'),(46,'Wajir','ACTIVE','2026-10-24 10:49:30'),(47,'West Pokot','ACTIVE','2026-10-24 10:49:30');
/*!40000 ALTER TABLE `distribution_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_accounts`
--

DROP TABLE IF EXISTS `finance_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_accounts` (
  `account_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `account_code` varchar(12) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `account_type` enum('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE') NOT NULL,
  `normal_balance` enum('DEBIT','CREDIT') NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uq_finance_accounts_org_code` (`organization_id`,`account_code`),
  KEY `idx_finance_accounts_org_status` (`organization_id`,`status`),
  CONSTRAINT `fk_finance_accounts_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=684 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_accounts`
--

LOCK TABLES `finance_accounts` WRITE;
/*!40000 ALTER TABLE `finance_accounts` DISABLE KEYS */;
INSERT INTO `finance_accounts` VALUES (1,1,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(2,1,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(3,1,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(4,1,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(5,1,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(6,1,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(7,1,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(8,1,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(9,1,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(10,1,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(11,1,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(12,1,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(13,2,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(14,2,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(15,2,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(16,2,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(17,2,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(18,2,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(19,2,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(20,2,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(21,2,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(22,2,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(23,2,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(24,2,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(25,3,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(26,3,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(27,3,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(28,3,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(29,3,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(30,3,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(31,3,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(32,3,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(33,3,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(34,3,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(35,3,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(36,3,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(37,5,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(38,5,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(39,5,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(40,5,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(41,5,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(42,5,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(43,5,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(44,5,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(45,5,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(46,5,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(47,5,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(48,5,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(49,9,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(50,9,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(51,9,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(52,9,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(53,9,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(54,9,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(55,9,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(56,9,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(57,9,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(58,9,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(59,9,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(60,9,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(61,10,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(62,10,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(63,10,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(64,10,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(65,10,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(66,10,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(67,10,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(68,10,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(69,10,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(70,10,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(71,10,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(72,10,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(73,11,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(74,11,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(75,11,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(76,11,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(77,11,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(78,11,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(79,11,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(80,11,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(81,11,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(82,11,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(83,11,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(84,11,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(85,12,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(86,12,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(87,12,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(88,12,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(89,12,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(90,12,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(91,12,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(92,12,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(93,12,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(94,12,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(95,12,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(96,12,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(97,13,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(98,13,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(99,13,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(100,13,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(101,13,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(102,13,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(103,13,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(104,13,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(105,13,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(106,13,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(107,13,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(108,13,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(109,14,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(110,14,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(111,14,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(112,14,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(113,14,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(114,14,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(115,14,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(116,14,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(117,14,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(118,14,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(119,14,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(120,14,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(121,15,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(122,15,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(123,15,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(124,15,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(125,15,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(126,15,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(127,15,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(128,15,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(129,15,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(130,15,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(131,15,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(132,15,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(133,16,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(134,16,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(135,16,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(136,16,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(137,16,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(138,16,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(139,16,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(140,16,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(141,16,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(142,16,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(143,16,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(144,16,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(145,17,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(146,17,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(147,17,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(148,17,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(149,17,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(150,17,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(151,17,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(152,17,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(153,17,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(154,17,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(155,17,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(156,17,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(157,18,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(158,18,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(159,18,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(160,18,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(161,18,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(162,18,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(163,18,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(164,18,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(165,18,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(166,18,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(167,18,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(168,18,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(169,19,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(170,19,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(171,19,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(172,19,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(173,19,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(174,19,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(175,19,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(176,19,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(177,19,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(178,19,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(179,19,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(180,19,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(181,20,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(182,20,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(183,20,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(184,20,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(185,20,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(186,20,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(187,20,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(188,20,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(189,20,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(190,20,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(191,20,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(192,20,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(193,21,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(194,21,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(195,21,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(196,21,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(197,21,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(198,21,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(199,21,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(200,21,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(201,21,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(202,21,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(203,21,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(204,21,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(205,22,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(206,22,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(207,22,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(208,22,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(209,22,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(210,22,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(211,22,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(212,22,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(213,22,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(214,22,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(215,22,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(216,22,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(217,23,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(218,23,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(219,23,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(220,23,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(221,23,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(222,23,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(223,23,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(224,23,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(225,23,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(226,23,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(227,23,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(228,23,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(229,24,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(230,24,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(231,24,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(232,24,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(233,24,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(234,24,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(235,24,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(236,24,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(237,24,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(238,24,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(239,24,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(240,24,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(241,25,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(242,25,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(243,25,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(244,25,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(245,25,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(246,25,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(247,25,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(248,25,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(249,25,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(250,25,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(251,25,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(252,25,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(253,27,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(254,27,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(255,27,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(256,27,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(257,27,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(258,27,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(259,27,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(260,27,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(261,27,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(262,27,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(263,27,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(264,27,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(265,28,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(266,28,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(267,28,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(268,28,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(269,28,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(270,28,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(271,28,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(272,28,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(273,28,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(274,28,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(275,28,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(276,28,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(277,29,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(278,29,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(279,29,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(280,29,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(281,29,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(282,29,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(283,29,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(284,29,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(285,29,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(286,29,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(287,29,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(288,29,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(289,26,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(290,26,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(291,26,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(292,26,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(293,26,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(294,26,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(295,26,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(296,26,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(297,26,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(298,26,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(299,26,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(300,26,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-26 09:39:30','2026-10-26 09:39:30'),(512,1,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(513,1,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(514,1,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(515,2,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(516,2,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(517,2,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(518,3,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(519,3,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(520,3,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(521,5,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(522,5,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(523,5,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(524,9,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(525,9,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(526,9,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(527,10,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(528,10,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(529,10,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(530,11,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(531,11,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(532,11,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(533,12,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(534,12,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(535,12,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(536,13,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(537,13,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(538,13,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(539,14,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(540,14,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(541,14,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(542,15,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(543,15,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(544,15,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(545,16,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(546,16,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(547,16,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(548,17,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(549,17,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(550,17,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(551,18,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(552,18,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(553,18,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(554,19,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(555,19,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(556,19,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(557,20,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(558,20,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(559,20,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(560,21,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(561,21,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(562,21,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(563,22,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(564,22,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(565,22,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(566,23,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(567,23,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(568,23,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(569,24,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(570,24,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(571,24,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(572,25,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(573,25,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(574,25,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(575,27,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(576,27,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(577,27,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(578,28,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(579,28,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(580,28,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(581,29,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(582,29,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(583,29,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(584,26,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(585,26,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(586,26,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-26 09:52:48','2026-10-26 09:52:48'),(639,30,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(640,30,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(641,30,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(642,30,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(643,30,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(644,30,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(645,30,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(646,30,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(647,30,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(648,30,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(649,30,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(650,30,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(651,30,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(652,30,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(653,30,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 14:54:46','2026-10-29 14:54:46'),(654,31,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(655,31,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(656,31,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(657,31,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(658,31,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(659,31,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(660,31,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(661,31,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(662,31,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(663,31,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(664,31,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(665,31,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(666,31,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(667,31,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(668,31,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 16:32:02','2026-10-29 16:32:02'),(669,33,'1000','Cash on hand','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(670,33,'1010','Bank account','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(671,33,'1020','M-Pesa and mobile money','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(672,33,'1030','Other payment clearing','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(673,33,'1100','Accounts receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(674,33,'1200','Inventory','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(675,33,'1300','Input tax receivable','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(676,33,'1400','Supplier advances','ASSET','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(677,33,'2000','Accounts payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(678,33,'2100','Tax payable','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(679,33,'2200','Customer advances','LIABILITY','CREDIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(680,33,'3000','Opening balance equity','EQUITY','CREDIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(681,33,'4000','Sales revenue','INCOME','CREDIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(682,33,'5000','Purchases and cost of goods sold','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07'),(683,33,'5100','Payment and bank fees','EXPENSE','DEBIT',1,'ACTIVE','2026-10-29 19:28:07','2026-10-29 19:28:07');
/*!40000 ALTER TABLE `finance_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_document_lines`
--

DROP TABLE IF EXISTS `finance_document_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_document_lines` (
  `document_line_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT 1.000,
  `unit_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(6,3) NOT NULL DEFAULT 0.000,
  `line_subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_tax` decimal(14,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`document_line_id`),
  UNIQUE KEY `uq_finance_document_line_number` (`document_id`,`line_number`),
  KEY `idx_finance_document_lines_product` (`product_id`),
  CONSTRAINT `fk_finance_document_lines_document` FOREIGN KEY (`document_id`) REFERENCES `finance_documents` (`document_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_document_lines_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_finance_document_line_values` CHECK (`quantity` > 0 and `unit_price` >= 0 and `tax_rate` >= 0 and `line_subtotal` >= 0 and `line_tax` >= 0 and `line_total` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_document_lines`
--

LOCK TABLES `finance_document_lines` WRITE;
/*!40000 ALTER TABLE `finance_document_lines` DISABLE KEYS */;
INSERT INTO `finance_document_lines` VALUES (2,2,1,NULL,'Test item',2.000,100.00,16.000,200.00,32.00,232.00,'2026-10-26 11:20:48'),(3,3,1,NULL,'Test item',2.000,100.00,16.000,200.00,32.00,232.00,'2026-10-26 11:20:49'),(4,4,1,NULL,'for the goods supplied',100000.000,0.13,0.000,13000.00,0.00,13000.00,'2026-10-29 17:54:39');
/*!40000 ALTER TABLE `finance_document_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_documents`
--

DROP TABLE IF EXISTS `finance_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_documents` (
  `document_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `counterparty_organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `source_supply_order_id` int(10) unsigned DEFAULT NULL,
  `document_number` varchar(60) NOT NULL,
  `document_type` enum('INVOICE','BILL','CREDIT_NOTE','DEBIT_NOTE') NOT NULL,
  `direction` enum('RECEIVABLE','PAYABLE') NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'KES',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('DRAFT','ISSUED','PARTIALLY_PAID','PAID','OVERDUE','VOID') NOT NULL DEFAULT 'DRAFT',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `issued_at` datetime DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`document_id`),
  UNIQUE KEY `uq_finance_documents_org_number` (`organization_id`,`document_number`),
  KEY `idx_finance_documents_org_direction_status` (`organization_id`,`direction`,`status`),
  KEY `idx_finance_documents_counterparty` (`counterparty_organization_id`,`status`),
  KEY `idx_finance_documents_due_date` (`organization_id`,`due_date`,`status`),
  KEY `idx_finance_documents_supply_order` (`source_supply_order_id`),
  KEY `fk_finance_documents_branch` (`branch_id`),
  KEY `fk_finance_documents_created_by` (`created_by`),
  CONSTRAINT `fk_finance_documents_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_documents_counterparty` FOREIGN KEY (`counterparty_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_documents_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_documents_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_documents_supply_order` FOREIGN KEY (`source_supply_order_id`) REFERENCES `supply_orders` (`supply_order_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_finance_documents_not_self` CHECK (`organization_id` <> `counterparty_organization_id`),
  CONSTRAINT `chk_finance_documents_amounts` CHECK (`subtotal` >= 0 and `tax_total` >= 0 and `total_amount` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_documents`
--

LOCK TABLES `finance_documents` WRITE;
/*!40000 ALTER TABLE `finance_documents` DISABLE KEYS */;
INSERT INTO `finance_documents` VALUES (2,5,1,NULL,NULL,'TST-20261026142023-B','INVOICE','RECEIVABLE','2026-10-26',NULL,'KES',200.00,32.00,232.00,'VOID','Automated test',1,'2026-10-26 14:20:48','2026-10-26 14:20:49','2026-10-26 11:20:48','2026-10-26 11:20:49'),(3,5,1,NULL,NULL,'TST-20261026142023-C','INVOICE','RECEIVABLE','2026-10-16','2026-10-23','KES',200.00,32.00,232.00,'VOID','Automated test',1,'2026-10-26 14:20:49','2026-10-26 14:20:49','2026-10-26 11:20:49','2026-10-26 11:20:49'),(4,3,5,NULL,NULL,'INV-2026-001','INVOICE','RECEIVABLE','2026-10-29','2026-10-31','KES',13000.00,0.00,13000.00,'ISSUED','check due date',28,'2026-10-29 20:54:42',NULL,'2026-10-29 17:54:39','2026-10-29 17:54:42');
/*!40000 ALTER TABLE `finance_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_journal_entries`
--

DROP TABLE IF EXISTS `finance_journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_journal_entries` (
  `journal_entry_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `entry_number` varchar(60) NOT NULL,
  `source_type` enum('DOCUMENT','PAYMENT','ADJUSTMENT','REVERSAL') NOT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `status` enum('DRAFT','POSTED','REVERSED') NOT NULL DEFAULT 'DRAFT',
  `reversal_of_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_user_id` int(10) unsigned NOT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`journal_entry_id`),
  UNIQUE KEY `uq_finance_journal_org_number` (`organization_id`,`entry_number`),
  UNIQUE KEY `uq_finance_journal_source` (`organization_id`,`source_type`,`source_id`),
  KEY `idx_finance_journal_org_date_status` (`organization_id`,`entry_date`,`status`),
  KEY `fk_finance_journal_reversal` (`reversal_of_entry_id`),
  KEY `fk_finance_journal_created_user` (`created_by_user_id`),
  CONSTRAINT `fk_finance_journal_created_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_journal_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_journal_reversal` FOREIGN KEY (`reversal_of_entry_id`) REFERENCES `finance_journal_entries` (`journal_entry_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_journal_entries`
--

LOCK TABLES `finance_journal_entries` WRITE;
/*!40000 ALTER TABLE `finance_journal_entries` DISABLE KEYS */;
INSERT INTO `finance_journal_entries` VALUES (1,5,'DOC-2','DOCUMENT',2,'2026-10-26','INVOICE TST-20261026142023-B','POSTED',NULL,1,'2026-10-26 14:20:48','2026-10-26 11:20:48'),(2,5,'REV-DOC-2','REVERSAL',2,'2026-10-26','Void INVOICE TST-20261026142023-B','POSTED',1,1,'2026-10-26 14:20:49','2026-10-26 11:20:49'),(3,5,'DOC-3','DOCUMENT',3,'2026-10-16','INVOICE TST-20261026142023-C','POSTED',NULL,1,'2026-10-26 14:20:49','2026-10-26 11:20:49'),(4,5,'REV-DOC-3','REVERSAL',3,'2026-10-26','Void INVOICE TST-20261026142023-C','POSTED',3,1,'2026-10-26 14:20:49','2026-10-26 11:20:49'),(5,3,'PAY-3-OUT','PAYMENT',3,'2026-10-26','Confirmed payment #3 between organisations 3 and 2 (outgoing)','POSTED',NULL,28,'2026-10-27 06:51:56','2026-10-27 03:51:56'),(6,2,'PAY-3-IN','PAYMENT',3,'2026-10-26','Confirmed payment #3 between organisations 3 and 2 (incoming)','POSTED',NULL,7,'2026-10-27 06:51:56','2026-10-27 03:51:56'),(7,2,'PAY-2-OUT','PAYMENT',2,'2026-10-26','Confirmed payment #2 between organisations 2 and 3 (outgoing)','POSTED',NULL,7,'2026-10-29 20:50:23','2026-10-29 17:50:23'),(8,3,'PAY-2-IN','PAYMENT',2,'2026-10-26','Confirmed payment #2 between organisations 2 and 3 (incoming)','POSTED',NULL,28,'2026-10-29 20:50:23','2026-10-29 17:50:23'),(9,3,'DOC-4','DOCUMENT',4,'2026-10-29','INVOICE INV-2026-001','POSTED',NULL,28,'2026-10-29 20:54:42','2026-10-29 17:54:42'),(10,3,'PAY-5-OUT','PAYMENT',5,'2026-10-30','Confirmed payment #5 between organisations 3 and 2 (outgoing)','POSTED',NULL,28,'2026-11-06 10:36:05','2026-11-06 07:36:05'),(11,2,'PAY-5-IN','PAYMENT',5,'2026-10-30','Confirmed payment #5 between organisations 3 and 2 (incoming)','POSTED',NULL,7,'2026-11-06 10:36:05','2026-11-06 07:36:05');
/*!40000 ALTER TABLE `finance_journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_journal_lines`
--

DROP TABLE IF EXISTS `finance_journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_journal_lines` (
  `journal_line_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `line_description` varchar(500) DEFAULT NULL,
  `debit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`journal_line_id`),
  KEY `idx_finance_journal_lines_entry` (`journal_entry_id`),
  KEY `idx_finance_journal_lines_account` (`account_id`),
  CONSTRAINT `fk_finance_journal_lines_account` FOREIGN KEY (`account_id`) REFERENCES `finance_accounts` (`account_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_journal_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `finance_journal_entries` (`journal_entry_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_finance_journal_line_amounts` CHECK (`debit_amount` >= 0 and `credit_amount` >= 0 and (`debit_amount` > 0 and `credit_amount` = 0 or `credit_amount` > 0 and `debit_amount` = 0))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_journal_lines`
--

LOCK TABLES `finance_journal_lines` WRITE;
/*!40000 ALTER TABLE `finance_journal_lines` DISABLE KEYS */;
INSERT INTO `finance_journal_lines` VALUES (1,1,40,'Accounts receivable - TST-20261026142023-B',232.00,0.00,'2026-10-26 11:20:48'),(2,1,46,'Sales revenue - TST-20261026142023-B',0.00,200.00,'2026-10-26 11:20:48'),(3,1,44,'Tax payable - TST-20261026142023-B',0.00,32.00,'2026-10-26 11:20:48'),(4,2,40,'Reversal - Accounts receivable - TST-20261026142023-B',0.00,232.00,'2026-10-26 11:20:49'),(5,2,46,'Reversal - Sales revenue - TST-20261026142023-B',200.00,0.00,'2026-10-26 11:20:49'),(6,2,44,'Reversal - Tax payable - TST-20261026142023-B',32.00,0.00,'2026-10-26 11:20:49'),(7,3,40,'Accounts receivable - TST-20261026142023-C',232.00,0.00,'2026-10-26 11:20:49'),(8,3,46,'Sales revenue - TST-20261026142023-C',0.00,200.00,'2026-10-26 11:20:49'),(9,3,44,'Tax payable - TST-20261026142023-C',0.00,32.00,'2026-10-26 11:20:49'),(10,4,40,'Reversal - Accounts receivable - TST-20261026142023-C',0.00,232.00,'2026-10-26 11:20:49'),(11,4,46,'Reversal - Sales revenue - TST-20261026142023-C',200.00,0.00,'2026-10-26 11:20:49'),(12,4,44,'Reversal - Tax payable - TST-20261026142023-C',32.00,0.00,'2026-10-26 11:20:49'),(13,5,26,'Payment sent - #3',0.00,10000.00,'2026-10-27 03:51:56'),(14,5,519,'Supplier advance - payment #3',10000.00,0.00,'2026-10-27 03:51:56'),(15,6,14,'Payment received - #3',10000.00,0.00,'2026-10-27 03:51:56'),(16,6,517,'Customer advance - payment #3',0.00,10000.00,'2026-10-27 03:51:56'),(17,7,14,'Payment sent - #2',0.00,10000.00,'2026-10-29 17:50:23'),(18,7,516,'Supplier advance - payment #2',10000.00,0.00,'2026-10-29 17:50:23'),(19,8,26,'Payment received - #2',10000.00,0.00,'2026-10-29 17:50:23'),(20,8,520,'Customer advance - payment #2',0.00,10000.00,'2026-10-29 17:50:23'),(21,9,28,'Accounts receivable - INV-2026-001',13000.00,0.00,'2026-10-29 17:54:42'),(22,9,34,'Sales revenue - INV-2026-001',0.00,13000.00,'2026-10-29 17:54:42'),(23,10,26,'Payment sent - #5',0.00,10000.00,'2026-11-06 07:36:05'),(24,10,519,'Supplier advance - payment #5',10000.00,0.00,'2026-11-06 07:36:05'),(25,11,14,'Payment received - #5',10000.00,0.00,'2026-11-06 07:36:05'),(26,11,517,'Customer advance - payment #5',0.00,10000.00,'2026-11-06 07:36:05');
/*!40000 ALTER TABLE `finance_journal_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_payment_allocations`
--

DROP TABLE IF EXISTS `finance_payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_payment_allocations` (
  `allocation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `document_id` bigint(20) unsigned NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL,
  `created_by_user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`allocation_id`),
  UNIQUE KEY `uq_finance_payment_document` (`payment_id`,`document_id`),
  KEY `idx_finance_allocations_document` (`document_id`),
  KEY `fk_finance_allocations_created_user` (`created_by_user_id`),
  CONSTRAINT `fk_finance_allocations_created_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_allocations_document` FOREIGN KEY (`document_id`) REFERENCES `finance_documents` (`document_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_allocations_payment` FOREIGN KEY (`payment_id`) REFERENCES `finance_payments` (`payment_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_finance_allocation_amount` CHECK (`allocated_amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_payment_allocations`
--

LOCK TABLES `finance_payment_allocations` WRITE;
/*!40000 ALTER TABLE `finance_payment_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_payments`
--

DROP TABLE IF EXISTS `finance_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_payments` (
  `payment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payer_organization_id` int(10) unsigned NOT NULL,
  `payee_organization_id` int(10) unsigned NOT NULL,
  `payer_branch_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'KES',
  `payment_method` enum('CASH','BANK_TRANSFER','MPESA','CHEQUE','OTHER') NOT NULL,
  `reference_number` varchar(120) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `status` enum('PENDING','CONFIRMED','FAILED','CANCELLED','REVERSED') NOT NULL DEFAULT 'PENDING',
  `recorded_by_organization_id` int(10) unsigned NOT NULL,
  `recorded_by_user_id` int(10) unsigned NOT NULL,
  `confirmed_by_organization_id` int(10) unsigned DEFAULT NULL,
  `confirmed_by_user_id` int(10) unsigned DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `idx_finance_payments_payer` (`payer_organization_id`,`payment_date`,`status`),
  KEY `idx_finance_payments_payee` (`payee_organization_id`,`payment_date`,`status`),
  KEY `idx_finance_payments_reference` (`payment_method`,`reference_number`),
  KEY `idx_finance_payments_recorded_by_org` (`recorded_by_organization_id`),
  KEY `fk_finance_payments_payer_branch` (`payer_branch_id`),
  KEY `fk_finance_payments_recorded_user` (`recorded_by_user_id`),
  KEY `fk_finance_payments_confirmed_org` (`confirmed_by_organization_id`),
  KEY `fk_finance_payments_confirmed_user` (`confirmed_by_user_id`),
  CONSTRAINT `fk_finance_payments_confirmed_org` FOREIGN KEY (`confirmed_by_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_confirmed_user` FOREIGN KEY (`confirmed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_payee` FOREIGN KEY (`payee_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_payer` FOREIGN KEY (`payer_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_payer_branch` FOREIGN KEY (`payer_branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_recorded_org` FOREIGN KEY (`recorded_by_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_finance_payments_recorded_user` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_finance_payments_distinct_orgs` CHECK (`payer_organization_id` <> `payee_organization_id`),
  CONSTRAINT `chk_finance_payments_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_payments`
--

LOCK TABLES `finance_payments` WRITE;
/*!40000 ALTER TABLE `finance_payments` DISABLE KEYS */;
INSERT INTO `finance_payments` VALUES (1,5,1,NULL,50.00,'KES','CASH','TST-20261026142023','2026-10-26','CANCELLED',5,1,NULL,NULL,NULL,'Cancelled by the payer: Automated test cancel','2026-10-26 11:20:49','2026-10-26 11:20:49'),(2,2,3,NULL,10000.00,'KES','CHEQUE',NULL,'2026-10-26','CONFIRMED',2,7,3,28,'2026-10-29 20:50:23',NULL,'2026-10-26 11:42:48','2026-10-29 17:50:23'),(3,3,2,NULL,10000.00,'KES','CHEQUE',NULL,'2026-10-26','CONFIRMED',3,28,2,7,'2026-10-27 06:51:56',NULL,'2026-10-26 16:46:52','2026-10-27 03:51:56'),(4,3,31,NULL,10000.00,'KES','CHEQUE',NULL,'2026-10-29','PENDING',3,28,NULL,NULL,NULL,NULL,'2026-10-29 17:51:12','2026-10-29 17:51:12'),(5,3,2,NULL,10000.00,'KES','CHEQUE',NULL,'2026-10-30','CONFIRMED',3,28,2,7,'2026-11-06 10:36:05',NULL,'2026-10-30 03:48:34','2026-11-06 07:36:05'),(6,5,1,NULL,50.00,'KES','CASH',NULL,'2026-10-30','PENDING',5,1,NULL,NULL,NULL,NULL,'2026-10-30 09:16:26','2026-10-30 09:16:26'),(7,5,30,NULL,2500000.00,'KES','CHEQUE',NULL,'2026-11-01','PENDING',5,1,NULL,NULL,NULL,NULL,'2026-11-01 05:51:59','2026-11-01 05:51:59'),(8,5,3,NULL,600000.00,'KES','BANK_TRANSFER',NULL,'2026-11-11','PENDING',5,1,NULL,NULL,NULL,'check last latest payment','2026-11-11 09:31:59','2026-11-11 09:31:59');
/*!40000 ALTER TABLE `finance_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_received_note_items`
--

DROP TABLE IF EXISTS `goods_received_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_received_note_items` (
  `grn_item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` bigint(20) unsigned NOT NULL,
  `supply_order_item_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `product_name_snapshot` varchar(150) NOT NULL,
  `barcode_snapshot` varchar(100) DEFAULT NULL,
  `pack_size_snapshot` varchar(100) DEFAULT NULL,
  `lpo_quantity_each` int(10) unsigned NOT NULL,
  `lpo_unit_price_snapshot` decimal(14,4) DEFAULT NULL,
  `previous_cost_snapshot` decimal(14,4) DEFAULT NULL,
  `invoice_quantity` decimal(12,3) unsigned NOT NULL DEFAULT 0.000,
  `invoice_unit` enum('EACH','CTN') NOT NULL DEFAULT 'EACH',
  `units_per_invoice_unit` int(10) unsigned NOT NULL,
  `invoice_unit_price` decimal(14,4) unsigned NOT NULL DEFAULT 0.0000,
  `price_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') NOT NULL,
  `tax_category` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') DEFAULT NULL,
  `tax_rate_percent` decimal(5,2) unsigned DEFAULT NULL,
  `taxable_supply` tinyint(1) unsigned DEFAULT NULL,
  `tax_calculation_status` enum('PENDING','CALCULATED','MISSING_CLASSIFICATION','AMBIGUOUS_CLASSIFICATION','MISSING_RATE','AMBIGUOUS_RATE') NOT NULL DEFAULT 'PENDING',
  `invoice_goods_value` decimal(14,2) unsigned DEFAULT NULL,
  `invoice_vat_amount` decimal(14,2) unsigned DEFAULT NULL,
  `invoice_line_total` decimal(14,2) unsigned NOT NULL DEFAULT 0.00,
  `quantity_delivered_each` int(10) unsigned NOT NULL DEFAULT 0,
  `quantity_accepted_each` int(10) unsigned NOT NULL DEFAULT 0,
  `quantity_rejected_each` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_cost_each` decimal(14,4) unsigned DEFAULT NULL,
  `discrepancy_reason` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`grn_item_id`),
  UNIQUE KEY `uq_grn_lpo_item` (`grn_id`,`supply_order_item_id`),
  KEY `idx_grn_item_product` (`product_id`,`product_version_id`),
  KEY `fk_grn_item_lpo_line` (`supply_order_item_id`),
  CONSTRAINT `fk_grn_item_header` FOREIGN KEY (`grn_id`) REFERENCES `goods_received_notes` (`grn_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grn_item_lpo_line` FOREIGN KEY (`supply_order_item_id`) REFERENCES `supply_order_items` (`supply_order_item_id`),
  CONSTRAINT `fk_grn_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `fk_grn_item_version` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product_versions` (`product_id`, `product_version_id`),
  CONSTRAINT `chk_grn_item_invoice_conversion` CHECK (`invoice_unit` = 'EACH' and `units_per_invoice_unit` = 1 or `invoice_unit` = 'CTN' and `units_per_invoice_unit` >= 1),
  CONSTRAINT `chk_grn_item_received_quantities` CHECK (`quantity_accepted_each` + `quantity_rejected_each` = `quantity_delivered_each`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_received_note_items`
--

LOCK TABLES `goods_received_note_items` WRITE;
/*!40000 ALTER TABLE `goods_received_note_items` DISABLE KEYS */;
INSERT INTO `goods_received_note_items` VALUES (1,1,119,14,22,'Aeriel stawberry fabric softener 1l','67554974887859','1l',14,109.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(2,1,120,16,24,'Aeriel washing powder 1kg','68543546453433','1kg',100,101.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(3,1,121,15,23,'Aerile vanilla fabric softener 1l','64646464535532','1l',14,138.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(4,1,122,13,21,'Ariel lavender 5kg','60673847276346','5kg',50,170.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(5,1,123,20,28,'Cadburry classic','64537893545322','80g',32,85.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(6,1,124,18,26,'gillete razor pack','69475823452323','3 pack',12,91.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(7,1,125,2,2,'Heart Deli Bread','2000000000025','400g',14,284.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(8,1,126,8,14,'Mill cookies','61620034567834','400g',20,120.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(9,1,127,12,20,'Mint chocolate','68686868668868','15g',32,161.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(10,1,128,5,5,'Muffins','2000000000056','Pack',250,161.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(11,1,129,17,25,'Oral b cross toothbrush','65748359335343','single',14,221.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(12,1,130,19,27,'pampers baby pants','68454536456374','4 pack',36,102.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46'),(13,1,131,10,17,'Quencher lime','61616161616166','500ml',14,165.0000,NULL,0.000,'EACH',1,0.0000,'VAT_INCLUSIVE',NULL,NULL,NULL,'PENDING',NULL,NULL,0.00,0,0,0,NULL,NULL,'2026-11-04 05:53:46');
/*!40000 ALTER TABLE `goods_received_note_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_received_notes`
--

DROP TABLE IF EXISTS `goods_received_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_received_notes` (
  `grn_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `inventory_location_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `supply_order_id` int(10) unsigned NOT NULL,
  `grn_number` varchar(32) NOT NULL,
  `lpo_number_snapshot` varchar(32) NOT NULL,
  `supplier_invoice_number` varchar(100) DEFAULT NULL,
  `supplier_invoice_date` date DEFAULT NULL,
  `received_date` date NOT NULL,
  `delivery_note_number` varchar(100) DEFAULT NULL,
  `dispatch_advice_number` varchar(100) DEFAULT NULL,
  `vehicle_registration` varchar(30) DEFAULT NULL,
  `driver_name` varchar(150) DEFAULT NULL,
  `invoice_goods_value` decimal(14,2) DEFAULT NULL,
  `invoice_vat_amount` decimal(14,2) DEFAULT NULL,
  `invoice_total` decimal(14,2) DEFAULT NULL,
  `calculated_goods_value` decimal(14,2) DEFAULT NULL,
  `calculated_vat_amount` decimal(14,2) DEFAULT NULL,
  `calculated_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `reconciliation_difference` decimal(14,2) DEFAULT NULL,
  `reconciliation_status` enum('PENDING','MATCHED','VARIANCE_ACCEPTED') NOT NULL DEFAULT 'PENDING',
  `variance_reason` varchar(500) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('DRAFT','POSTED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `posted_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`grn_id`),
  UNIQUE KEY `uq_grn_number` (`retailer_id`,`grn_number`),
  KEY `idx_grn_lpo` (`supply_order_id`),
  KEY `idx_grn_supplier_invoice` (`supplier_id`,`supplier_invoice_number`),
  KEY `idx_grn_register` (`retailer_id`,`received_date`,`status`),
  KEY `idx_grn_branch` (`retailer_id`,`branch_id`,`received_date`),
  KEY `idx_grn_supplier` (`supplier_id`,`received_date`),
  KEY `fk_grn_created_by` (`created_by`),
  KEY `fk_grn_posted_by` (`posted_by`),
  KEY `idx_grn_inventory_location` (`inventory_location_id`,`retailer_id`,`branch_id`),
  CONSTRAINT `fk_grn_branch` FOREIGN KEY (`retailer_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`),
  CONSTRAINT `fk_grn_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_grn_inventory_location` FOREIGN KEY (`inventory_location_id`, `retailer_id`, `branch_id`) REFERENCES `inventory_locations` (`inventory_location_id`, `organization_id`, `branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_grn_lpo` FOREIGN KEY (`supply_order_id`) REFERENCES `supply_orders` (`supply_order_id`),
  CONSTRAINT `fk_grn_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_grn_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`),
  CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_received_notes`
--

LOCK TABLES `goods_received_notes` WRITE;
/*!40000 ALTER TABLE `goods_received_notes` DISABLE KEYS */;
INSERT INTO `goods_received_notes` VALUES (1,5,7,2,3,1,'GRN-20261104-DBD8CECE','LPO-000001',NULL,NULL,'2026-11-04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,'PENDING',NULL,NULL,'DRAFT',1,NULL,'2026-11-04 05:53:46',NULL);
/*!40000 ALTER TABLE `goods_received_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `information_verifications`
--

DROP TABLE IF EXISTS `information_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `information_verifications` (
  `verification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `verified_by` int(10) unsigned NOT NULL,
  `information_type` enum('PRODUCT','PRICE','PROMOTION','SUPPLY','DISTRIBUTION') NOT NULL,
  `reference_id` int(10) unsigned NOT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `verification_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`verification_id`),
  KEY `fk_information_verifications_organization` (`organization_id`),
  KEY `fk_information_verifications_user` (`verified_by`),
  CONSTRAINT `fk_information_verifications_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_information_verifications_user` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `information_verifications`
--

LOCK TABLES `information_verifications` WRITE;
/*!40000 ALTER TABLE `information_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `information_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_locations`
--

DROP TABLE IF EXISTS `inventory_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_locations` (
  `inventory_location_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `location_code` varchar(40) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `location_type` enum('CENTRAL_WAREHOUSE','BACK_OFFICE','SHOP_FLOOR','OTHER') NOT NULL,
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`inventory_location_id`),
  UNIQUE KEY `uq_inventory_location_code` (`organization_id`,`branch_id`,`location_code`),
  UNIQUE KEY `uq_inventory_location_reference` (`inventory_location_id`,`organization_id`,`branch_id`),
  KEY `idx_inventory_location_branch` (`organization_id`,`branch_id`,`status`),
  CONSTRAINT `fk_inventory_location_branch` FOREIGN KEY (`organization_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_locations`
--

LOCK TABLES `inventory_locations` WRITE;
/*!40000 ALTER TABLE `inventory_locations` DISABLE KEYS */;
INSERT INTO `inventory_locations` VALUES (1,5,7,'CENTRAL-WAREHOUSE','Central Warehouse','CENTRAL_WAREHOUSE',0,'ACTIVE','2026-11-03 14:42:00','2026-11-03 14:42:00'),(2,5,7,'BACK-OFFICE','Back Office','BACK_OFFICE',1,'ACTIVE','2026-11-03 14:42:00','2026-11-03 14:42:00'),(3,5,7,'SHOP-FLOOR','Shop Floor','SHOP_FLOOR',0,'ACTIVE','2026-11-03 14:42:00','2026-11-03 14:42:00');
/*!40000 ALTER TABLE `inventory_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock`
--

DROP TABLE IF EXISTS `inventory_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock` (
  `inventory_stock_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `inventory_location_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `quantity_on_hand` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reorder_level` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`inventory_stock_id`),
  UNIQUE KEY `uq_inventory_stock_location_product` (`inventory_location_id`,`product_id`,`product_version_id`),
  KEY `idx_inventory_stock_organisation` (`organization_id`,`branch_id`,`inventory_location_id`),
  KEY `fk_inventory_stock_location` (`inventory_location_id`,`organization_id`,`branch_id`),
  CONSTRAINT `fk_inventory_stock_location` FOREIGN KEY (`inventory_location_id`, `organization_id`, `branch_id`) REFERENCES `inventory_locations` (`inventory_location_id`, `organization_id`, `branch_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock`
--

LOCK TABLES `inventory_stock` WRITE;
/*!40000 ALTER TABLE `inventory_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `sender_user_id` int(10) unsigned NOT NULL,
  `message_text` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_messages_conversation` (`conversation_id`),
  KEY `fk_messages_sender` (`sender_user_id`),
  CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,7,'Please confirm stock of the old barcode.','2026-10-15 09:50:26',NULL),(2,1,1,'Confirmed.','2026-10-15 09:50:26',NULL),(3,1,7,'Please confirm stock of the old barcode.','2026-10-15 09:50:31',NULL),(4,1,1,'Confirmed.','2026-10-15 09:50:31',NULL),(5,1,7,'Please confirm stock of the old barcode.','2026-10-15 11:30:46',NULL),(6,1,1,'Confirmed.','2026-10-15 11:30:46',NULL),(7,1,7,'hello','2026-10-15 11:49:51',NULL),(8,1,7,'yoh','2026-10-15 11:49:58',NULL),(9,1,1,'whatsup my g','2026-10-15 11:50:23',NULL),(10,1,7,'We will be rolling out the new batches do you want us to include them in your order  and if so how much','2026-10-16 06:52:02',NULL),(11,1,1,'yes please if it is possible iclude like 10 batches in the lpo','2026-10-16 06:53:09',NULL),(12,5,1,'what is going on','2026-10-28 21:33:26',NULL),(13,5,28,'yoh whats up','2026-10-28 21:34:20',NULL),(14,6,1,'Hello can you confirm if you received the check dated on 16 october 2026 for the goods received note dated on 26 september 2026','2026-10-28 21:45:27',NULL),(15,6,2,'Yes acccording to our records your cheque was placed','2026-10-28 21:59:53',NULL),(16,10,39,'Hello we wish to supply your organisation with our products','2026-10-29 17:58:46',NULL),(17,10,1,'hello and received','2026-10-29 18:00:10',NULL),(18,11,40,'Fratres has qualified to supply our products in nairobi county','2026-10-29 19:38:49',NULL),(19,11,28,'ok','2026-10-29 19:39:09',NULL),(20,12,42,'Hello we request you to supply our goods in  nairobi county','2026-10-29 22:29:54',NULL),(21,12,28,'Greetings we are able to do so anytime','2026-10-29 22:30:48',NULL),(22,5,28,'ChainBridge real-time notification test. Please confirm receipt.','2026-11-05 05:47:34',NULL),(23,5,1,'yes received message successfully','2026-11-05 05:48:24',NULL),(24,5,28,'Pooling disabled first message test reply for confirmation','2026-11-05 05:55:13',NULL),(25,5,1,'message received','2026-11-05 05:55:32',NULL),(26,5,28,'hello\ntest 2','2026-11-05 06:24:08',NULL),(27,5,1,'test 2 received','2026-11-05 06:24:29',NULL),(28,5,1,'hello','2026-11-05 06:35:39',NULL),(29,5,28,'test 2 globall notification','2026-11-05 06:36:25',NULL),(30,5,28,'ChainBridge acknowledgement test 05-Nov-2026','2026-11-05 07:52:29',NULL),(31,5,28,'test message 4 today correct timestamp','2026-11-05 08:08:46',NULL),(32,5,1,'test message received','2026-11-05 08:09:01',NULL);
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `notification_type` enum('PRODUCT_CHANGE','SUPPLY_CHANGE','PROMOTION','STOCK_ALERT','GENERAL','LPO_ACKNOWLEDGED','LPO_REJECTED') NOT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('UNREAD','READ') NOT NULL DEFAULT 'UNREAD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `fk_notifications_user` (`user_id`),
  KEY `idx_notifications_unread_scope` (`organization_id`,`status`,`user_id`),
  CONSTRAINT `fk_notifications_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=196 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,5,3,'PRODUCT_CHANGE',1,'Whitedent product information changed','Whitedent has changed from 50ml to 45ml and has been assigned a new barcode. The change is effective from 2026-08-01.','UNREAD','2026-10-04 12:51:32',NULL),(2,5,3,'SUPPLY_CHANGE',1,'Whitedent supplier change','The supplier responsible for Whitedent has changed. The new supplier is Fratres Limited Kitui, effective from 2026-09-01.','UNREAD','2026-10-04 12:54:35',NULL),(3,3,4,'PRODUCT_CHANGE',2,'Demo Bread product information changed','Demo Bread has a product information change effective on 2026-10-10. Reason: Packaging changed from 400g to 450g and a new barcode was introduced.','UNREAD','2026-10-05 03:12:12',NULL),(4,5,1,'PRODUCT_CHANGE',2,'Demo Bread product information changed','Demo Bread has a product information change effective on 2026-10-10. Reason: Packaging changed from 400g to 450g and a new barcode was introduced.','READ','2026-10-05 03:12:12','2026-10-22 08:17:09'),(5,5,3,'PRODUCT_CHANGE',2,'Demo Bread product information changed','Demo Bread has a product information change effective on 2026-10-10. Reason: Packaging changed from 400g to 450g and a new barcode was introduced.','UNREAD','2026-10-05 03:12:12',NULL),(6,5,37,'GENERAL',2,'Branch notice: Test message','This is a test message dont reply','UNREAD','2026-10-23 11:28:51',NULL),(7,5,38,'GENERAL',2,'Branch notice: Test message','This is a test message dont reply','READ','2026-10-23 11:28:51','2026-10-23 14:29:01'),(9,5,1,'GENERAL',2,'Reply to: Test message','hello head office acknowledge received','READ','2026-10-23 11:29:23','2026-10-30 18:18:18'),(10,2,NULL,'SUPPLY_CHANGE',9,'Regional product appointment created','Regional appointment for Demo Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-24 through 2026-10-25.','READ','2026-10-24 14:01:36','2026-10-30 17:39:16'),(11,3,NULL,'SUPPLY_CHANGE',9,'New regional product appointment','Regional appointment for Demo Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-24 through 2026-10-25.','READ','2026-10-24 14:01:36','2026-10-30 17:34:15'),(12,2,NULL,'SUPPLY_CHANGE',10,'Regional product appointment created','Regional appointment for Demo Bread in Kwale: Fratres Limited Kitui was appointed from 2026-10-24 through 2026-10-25.','READ','2026-10-24 14:09:51','2026-10-30 17:39:16'),(13,3,NULL,'SUPPLY_CHANGE',10,'New regional product appointment','Regional appointment for Demo Bread in Kwale: Fratres Limited Kitui was appointed from 2026-10-24 through 2026-10-25.','READ','2026-10-24 14:09:51','2026-10-30 17:34:15'),(14,3,NULL,'SUPPLY_CHANGE',10,'Supplier coverage ended','Your organisation ended declared coverage for Kwale effective 2026-10-24. 1 active or scheduled product appointment(s) remain unchanged and may need review.','READ','2026-10-24 14:11:09','2026-10-30 17:34:15'),(15,2,NULL,'SUPPLY_CHANGE',10,'Supplier coverage ended in Kwale','Fratres Limited Kitui ended declared coverage for Kwale effective 2026-10-24. These product appointments remain active or scheduled and were not automatically ended: Demo Bread. Review regional delivery arrangements.','READ','2026-10-24 14:11:09','2026-10-30 17:39:16'),(16,2,NULL,'SUPPLY_CHANGE',11,'Regional product appointment created','Regional appointment for Heart Deli Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:56:55','2026-10-30 17:39:16'),(17,3,NULL,'SUPPLY_CHANGE',11,'New regional product appointment','Regional appointment for Heart Deli Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:56:55','2026-10-30 17:34:15'),(18,2,NULL,'SUPPLY_CHANGE',12,'Regional product appointment created','Regional appointment for Madeira Cake in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:12','2026-10-30 17:39:16'),(19,3,NULL,'SUPPLY_CHANGE',12,'New regional product appointment','Regional appointment for Madeira Cake in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:12','2026-10-30 17:34:15'),(20,2,NULL,'SUPPLY_CHANGE',13,'Regional product appointment created','Regional appointment for Mill bakers multi grain bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:19','2026-10-30 17:39:16'),(21,3,NULL,'SUPPLY_CHANGE',13,'New regional product appointment','Regional appointment for Mill bakers multi grain bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:19','2026-10-30 17:34:15'),(22,2,NULL,'SUPPLY_CHANGE',14,'Regional product appointment created','Regional appointment for Mill cookies in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:25','2026-10-30 17:39:16'),(23,3,NULL,'SUPPLY_CHANGE',14,'New regional product appointment','Regional appointment for Mill cookies in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:25','2026-10-30 17:34:15'),(24,2,NULL,'SUPPLY_CHANGE',15,'Regional product appointment created','Regional appointment for mill mini short cake in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:31','2026-10-30 17:39:16'),(25,3,NULL,'SUPPLY_CHANGE',15,'New regional product appointment','Regional appointment for mill mini short cake in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:31','2026-10-30 17:34:15'),(26,2,NULL,'SUPPLY_CHANGE',16,'Regional product appointment created','Regional appointment for Muffins in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:37','2026-10-30 17:39:16'),(27,3,NULL,'SUPPLY_CHANGE',16,'New regional product appointment','Regional appointment for Muffins in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:37','2026-10-30 17:34:15'),(28,2,NULL,'SUPPLY_CHANGE',17,'Regional product appointment created','Regional appointment for Standard madeira in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:52','2026-10-30 17:39:16'),(29,3,NULL,'SUPPLY_CHANGE',17,'New regional product appointment','Regional appointment for Standard madeira in Nairobi: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date. The manufacturer acknowledged that the supplier has not declared coverage for the full appointment period.','READ','2026-10-25 00:57:52','2026-10-30 17:34:15'),(30,3,NULL,'SUPPLY_CHANGE',7,'Supplier coverage ended','Your organisation ended declared coverage for Nairobi effective 2026-10-25. 8 active or scheduled product appointment(s) remain unchanged and may need review.','READ','2026-10-25 00:58:43','2026-10-25 04:00:29'),(31,2,NULL,'SUPPLY_CHANGE',7,'Supplier coverage ended in Nairobi','Fratres Limited Kitui ended declared coverage for Nairobi effective 2026-10-25. These product appointments remain active or scheduled and were not automatically ended: Demo Bread, Heart Deli Bread, Madeira Cake, Mill bakers multi grain bread, Mill cookies, mill mini short cake, Muffins, Standard madeira. Review regional delivery arrangements.','READ','2026-10-25 00:58:43','2026-10-30 17:39:16'),(32,2,NULL,'SUPPLY_CHANGE',18,'Regional product appointment created','Regional appointment for Demo Bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(33,3,NULL,'SUPPLY_CHANGE',18,'New regional product appointment','Regional appointment for Demo Bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(34,2,NULL,'SUPPLY_CHANGE',19,'Regional product appointment created','Regional appointment for Heart Deli Bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(35,3,NULL,'SUPPLY_CHANGE',19,'New regional product appointment','Regional appointment for Heart Deli Bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(36,2,NULL,'SUPPLY_CHANGE',20,'Regional product appointment created','Regional appointment for Madeira Cake in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(37,3,NULL,'SUPPLY_CHANGE',20,'New regional product appointment','Regional appointment for Madeira Cake in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(38,2,NULL,'SUPPLY_CHANGE',21,'Regional product appointment created','Regional appointment for Mill bakers multi grain bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(39,3,NULL,'SUPPLY_CHANGE',21,'New regional product appointment','Regional appointment for Mill bakers multi grain bread in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(40,2,NULL,'SUPPLY_CHANGE',22,'Regional product appointment created','Regional appointment for Mill cookies in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(41,3,NULL,'SUPPLY_CHANGE',22,'New regional product appointment','Regional appointment for Mill cookies in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(42,2,NULL,'SUPPLY_CHANGE',23,'Regional product appointment created','Regional appointment for mill mini short cake in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(43,3,NULL,'SUPPLY_CHANGE',23,'New regional product appointment','Regional appointment for mill mini short cake in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(44,2,NULL,'SUPPLY_CHANGE',24,'Regional product appointment created','Regional appointment for Muffins in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(45,3,NULL,'SUPPLY_CHANGE',24,'New regional product appointment','Regional appointment for Muffins in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(46,2,NULL,'SUPPLY_CHANGE',25,'Regional product appointment created','Regional appointment for Standard madeira in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:39:16'),(47,3,NULL,'SUPPLY_CHANGE',25,'New regional product appointment','Regional appointment for Standard madeira in Kiambu: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:41:45','2026-10-30 17:34:15'),(48,2,NULL,'SUPPLY_CHANGE',26,'Regional product appointment created','Regional appointment for Demo Bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(49,3,NULL,'SUPPLY_CHANGE',26,'New regional product appointment','Regional appointment for Demo Bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(50,2,NULL,'SUPPLY_CHANGE',27,'Regional product appointment created','Regional appointment for Heart Deli Bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(51,3,NULL,'SUPPLY_CHANGE',27,'New regional product appointment','Regional appointment for Heart Deli Bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(52,2,NULL,'SUPPLY_CHANGE',28,'Regional product appointment created','Regional appointment for Madeira Cake in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(53,3,NULL,'SUPPLY_CHANGE',28,'New regional product appointment','Regional appointment for Madeira Cake in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(54,2,NULL,'SUPPLY_CHANGE',29,'Regional product appointment created','Regional appointment for Mill bakers multi grain bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(55,3,NULL,'SUPPLY_CHANGE',29,'New regional product appointment','Regional appointment for Mill bakers multi grain bread in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(56,2,NULL,'SUPPLY_CHANGE',30,'Regional product appointment created','Regional appointment for Mill cookies in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(57,3,NULL,'SUPPLY_CHANGE',30,'New regional product appointment','Regional appointment for Mill cookies in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(58,2,NULL,'SUPPLY_CHANGE',31,'Regional product appointment created','Regional appointment for mill mini short cake in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:39:16'),(59,3,NULL,'SUPPLY_CHANGE',31,'New regional product appointment','Regional appointment for mill mini short cake in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(60,2,NULL,'SUPPLY_CHANGE',32,'Regional product appointment created','Regional appointment for Muffins in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-26 17:12:31'),(61,3,NULL,'SUPPLY_CHANGE',32,'New regional product appointment','Regional appointment for Muffins in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(62,2,NULL,'SUPPLY_CHANGE',33,'Regional product appointment created','Regional appointment for Standard madeira in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-26 17:12:27'),(63,3,NULL,'SUPPLY_CHANGE',33,'New regional product appointment','Regional appointment for Standard madeira in Kitui: Fratres Limited Kitui was appointed from 2026-10-25 with no planned end date.','READ','2026-10-25 01:42:02','2026-10-30 17:34:15'),(68,3,NULL,'SUPPLY_CHANGE',8,'Product price updated: Demo Bread','Price update processed for Demo Bread, version 2. Your own recorded cost changed from KSh 85.00 to KSh 141.00, and your own selling price changed from KSh 89.68 to KSh 148.76. Effective date: 2026-10-26. This notification contains only your organisation\'s own prices.','READ','2026-10-26 06:10:17','2026-10-30 17:34:15'),(69,5,NULL,'SUPPLY_CHANGE',8,'Price update needs review: Demo Bread','An existing price schedule for Demo Bread, version 2, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-26.','READ','2026-10-26 06:10:17','2026-10-30 18:18:18'),(70,3,NULL,'SUPPLY_CHANGE',8,'Product price updated: Demo Bread','Price update processed for Demo Bread, version 2. Your own recorded cost changed from KSh 141.00 to KSh 141.01, and your own selling price changed from KSh 148.76 to KSh 148.77. Effective date: 2026-10-26. This notification contains only your organisation\'s own prices.','READ','2026-10-26 06:27:05','2026-10-30 17:34:15'),(71,5,NULL,'SUPPLY_CHANGE',8,'Price update needs review: Demo Bread','An existing price schedule for Demo Bread, version 2, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-26.','READ','2026-10-26 06:27:05','2026-10-30 18:18:18'),(72,3,NULL,'SUPPLY_CHANGE',8,'Product price updated: Demo Bread','Price update processed for Demo Bread, version 2. Your own recorded cost changed from KSh 141.01 to KSh 142.00, and your own selling price changed from KSh 148.77 to KSh 149.81. Effective date: 2026-10-26. This notification contains only your organisation\'s own prices.','READ','2026-10-26 06:54:31','2026-10-30 17:34:15'),(73,5,NULL,'SUPPLY_CHANGE',8,'Price update needs review: Demo Bread','An existing price schedule for Demo Bread, version 2, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-26.','READ','2026-10-26 06:54:31','2026-10-30 18:18:18'),(74,3,NULL,'SUPPLY_CHANGE',8,'Product price updated: Demo Bread','Price update processed for Demo Bread, version 2. Your own recorded cost changed from KSh 142.00 to KSh 175.00, and your own selling price changed from KSh 149.81 to KSh 184.63. Effective date: 2026-10-26. This notification contains only your organisation\'s own prices.','READ','2026-10-26 07:02:39','2026-10-30 17:34:15'),(75,5,NULL,'SUPPLY_CHANGE',8,'Price update needs review: Demo Bread','An existing price schedule for Demo Bread, version 2, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-26.','READ','2026-10-26 07:02:39','2026-10-30 18:18:18'),(76,3,NULL,'SUPPLY_CHANGE',8,'Product price updated: Demo Bread','Price update processed for Demo Bread, version 2. Your own recorded cost changed from KSh 175.00 to KSh 179.00, and your own selling price changed from KSh 184.63 to KSh 188.85. Effective date: 2026-10-26. This notification contains only your organisation\'s own prices.','READ','2026-10-26 07:03:19','2026-10-26 19:56:25'),(77,5,NULL,'SUPPLY_CHANGE',8,'Price update needs review: Demo Bread','An existing price schedule for Demo Bread, version 2, requires review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-26.','READ','2026-10-26 07:03:19','2026-10-30 18:18:18'),(78,3,NULL,'SUPPLY_CHANGE',8,'Supplier cost price reduced: Demo Bread','Your cost price for Demo Bread, version 2, was reduced from KSh 179.00 to KSh 90.00, effective 2026-10-26. This adjustment changed your COST price only. Your SELLING price was left unchanged; no retailer or branch prices were changed automatically.','READ','2026-10-26 08:03:28','2026-10-26 19:56:21'),(79,3,NULL,'SUPPLY_CHANGE',13,'Manufacturer price decreased: Mill bakers multi grain bread','The direct manufacturer supply price for Mill bakers multi grain bread, version 1, decreased from KSh 243.31 to KSh 90.00, effective 2026-10-26. Automatic adjustment was skipped because Price Change Protection is not enabled for your organisation.','READ','2026-10-26 17:37:51','2026-10-30 17:34:15'),(80,5,NULL,'PRODUCT_CHANGE',6,'RRP updated: Demo Bread','A new recommendation of KSh 150.00 was published for Demo Bread (version 2). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 03:43:31','2026-10-28 06:53:47'),(81,29,NULL,'PRODUCT_CHANGE',6,'RRP updated: Demo Bread','A new recommendation of KSh 150.00 was published for Demo Bread (version 2). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-28 03:43:31',NULL),(82,3,NULL,'PRODUCT_CHANGE',6,'RSP updated: Demo Bread','A new recommendation of KSh 120.00 was published for Demo Bread (version 2). The supplier recommended selling price (RSP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 03:43:53','2026-10-30 17:34:15'),(83,2,NULL,'PROMOTION',5,'Retailer accepted campaign','A retailer accepted campaign \"Testing promotional campaings\" and selected 1 branch(es).','READ','2026-10-28 04:42:47','2026-10-28 14:46:51'),(84,2,NULL,'PROMOTION',5,'Retailer commenced campaign','Campaign \"Testing promotional campaings\" has commenced at branch Main Branch.','READ','2026-10-28 04:47:23','2026-10-28 14:46:46'),(85,5,NULL,'SUPPLY_CHANGE',17,'Manufacturer price decreased: Quencher lime','The direct manufacturer supply price for Quencher lime, version 1, decreased from KSh 240.75 to KSh 120.00, effective 2026-10-28. No prices were automatically changed in your organisation. Review your own purchasing and selling prices and adjust them if appropriate.','READ','2026-10-28 11:49:14','2026-10-30 18:18:18'),(86,5,NULL,'PRODUCT_CHANGE',10,'RRP updated: Quencher lime','A new recommendation of KSh 170.00 was published for Quencher lime (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 11:49:36','2026-10-30 18:18:18'),(87,5,NULL,'PRODUCT_CHANGE',10,'RRP updated: Quencher lime','The recommendation changed from KSh 170.00 to KSh 195.00 for Quencher lime (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 11:49:53','2026-10-30 18:18:18'),(88,5,NULL,'PRODUCT_CHANGE',20,'RRP updated: Cadburry classic','A new recommendation of KSh 120.00 was published for Cadburry classic (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 11:55:25','2026-10-30 18:18:18'),(89,29,NULL,'PRODUCT_CHANGE',20,'RRP updated: Cadburry classic','A new recommendation of KSh 120.00 was published for Cadburry classic (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-28 11:55:25',NULL),(90,3,NULL,'PRODUCT_CHANGE',20,'RSP updated: Cadburry classic','A new recommendation of KSh 85.00 was published for Cadburry classic (version 1). The supplier recommended selling price (RSP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 11:55:36','2026-10-30 17:34:15'),(91,28,NULL,'PRODUCT_CHANGE',20,'RSP updated: Cadburry classic','A new recommendation of KSh 85.00 was published for Cadburry classic (version 1). The supplier recommended selling price (RSP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-28 11:55:36',NULL),(92,3,NULL,'SUPPLY_CHANGE',12,'Manufacturer price decreased: mill mini short cake','The direct manufacturer supply price for mill mini short cake, version 2, decreased from KSh 105.53 to KSh 70.00, effective 2026-10-28. Automatic adjustment was skipped because Price Change Protection is not enabled for your organisation.','READ','2026-10-28 13:51:05','2026-10-30 17:34:15'),(93,3,NULL,'SUPPLY_CHANGE',14,'Manufacturer price decreased: Mill cookies','The direct manufacturer supply price for Mill cookies, version 1, decreased from KSh 268.26 to KSh 90.00, effective 2026-10-28. Automatic adjustment was skipped because Price Change Protection is not enabled for your organisation.','READ','2026-10-28 13:53:34','2026-10-30 17:34:15'),(94,5,NULL,'PRODUCT_CHANGE',8,'RRP updated: Mill cookies','A new recommendation of KSh 150.00 was published for Mill cookies (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 13:54:35','2026-10-30 18:18:18'),(95,29,NULL,'PRODUCT_CHANGE',8,'RRP updated: Mill cookies','A new recommendation of KSh 150.00 was published for Mill cookies (version 1). The recommended retail price (RRP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-28 13:54:35',NULL),(96,2,NULL,'SUPPLY_CHANGE',34,'Regional product appointment created','Regional appointment for Demo Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-28 with no planned end date.','READ','2026-10-28 14:30:22','2026-10-30 17:39:16'),(97,3,NULL,'SUPPLY_CHANGE',34,'New regional product appointment','Regional appointment for Demo Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-10-28 with no planned end date.','READ','2026-10-28 14:30:22','2026-10-30 17:34:15'),(98,5,NULL,'PROMOTION',7,'New manufacturer campaign available','Campaign \"Holiday Times\" is approved and runs from 2026-10-29 to 2026-11-07. Review the offer in Promotions > Manufacturer campaigns.','READ','2026-10-28 14:43:26','2026-10-28 17:43:49'),(99,29,NULL,'PROMOTION',7,'New manufacturer campaign available','Campaign \"Holiday Times\" is approved and runs from 2026-10-29 to 2026-11-07. Review the offer in Promotions > Manufacturer campaigns.','UNREAD','2026-10-28 14:43:26',NULL),(100,2,NULL,'PROMOTION',7,'Retailer accepted campaign','A retailer accepted campaign \"Holiday Times\" and selected 3 branch(es).','READ','2026-10-28 14:44:31','2026-10-30 17:39:16'),(101,3,NULL,'PRODUCT_CHANGE',8,'RSP updated: Mill cookies','A new recommendation of KSh 120.00 was published for Mill cookies (version 1). The supplier recommended selling price (RSP) is effective 2026-10-28. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-28 14:54:02','2026-10-30 17:34:15'),(102,5,NULL,'GENERAL',6,'New direct message','A new message has arrived from Chandaria Industries.','READ','2026-10-28 18:59:53','2026-10-28 22:01:42'),(103,2,NULL,'PROMOTION',7,'Retailer commenced campaign','Campaign \"Holiday Times\" has commenced at branch Main Branch.','READ','2026-10-29 09:26:44','2026-10-30 17:39:16'),(104,2,NULL,'PROMOTION',7,'Retailer commenced campaign','Campaign \"Holiday Times\" has commenced at branch Target Machakos.','READ','2026-10-29 09:26:45','2026-10-30 17:39:16'),(105,2,NULL,'PROMOTION',7,'Retailer commenced campaign','Campaign \"Holiday Times\" has commenced at branch Target Supermarket.','READ','2026-10-29 09:26:47','2026-10-30 17:39:16'),(106,5,NULL,'GENERAL',10,'New direct message','A new message has arrived from Broockside indursty.','READ','2026-10-29 14:58:46','2026-10-29 17:59:12'),(107,30,NULL,'GENERAL',10,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-10-29 15:00:10','2026-10-29 18:17:42'),(108,5,NULL,'PRODUCT_CHANGE',21,'RRP updated: brookside whole milk','A new recommendation of KSh 65.00 was published for brookside whole milk (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 15:07:09','2026-10-29 18:14:14'),(109,31,NULL,'SUPPLY_CHANGE',50,'Regional product appointment created','Regional appointment for Fresh fri in Nairobi: Fratres Limited Kitui was appointed from 2026-10-29 through 2027-01-01.','UNREAD','2026-10-29 16:33:17',NULL),(110,3,NULL,'SUPPLY_CHANGE',50,'New regional product appointment','Regional appointment for Fresh fri in Nairobi: Fratres Limited Kitui was appointed from 2026-10-29 through 2027-01-01.','READ','2026-10-29 16:33:17','2026-10-30 17:34:15'),(111,3,NULL,'GENERAL',11,'New direct message','A new message has arrived from Pwani oil products.','READ','2026-10-29 16:38:49','2026-10-29 19:38:59'),(112,31,NULL,'GENERAL',11,'New direct message','A new message has arrived from Fratres Limited Kitui.','UNREAD','2026-10-29 16:39:09',NULL),(113,5,NULL,'PRODUCT_CHANGE',22,'RRP updated: Fresh fri','A new recommendation of KSh 385.00 was published for Fresh fri (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 16:44:09','2026-10-30 18:18:18'),(114,29,NULL,'PRODUCT_CHANGE',22,'RRP updated: Fresh fri','A new recommendation of KSh 385.00 was published for Fresh fri (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-29 16:44:09',NULL),(115,3,NULL,'PRODUCT_CHANGE',22,'RSP updated: Fresh fri','A new recommendation of KSh 320.00 was published for Fresh fri (version 1). The supplier recommended selling price (RSP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 16:44:39','2026-10-30 17:34:15'),(116,5,NULL,'PRODUCT_CHANGE',22,'RRP updated: Fresh fri','The recommendation changed from KSh 385.00 to KSh 385.00 for Fresh fri (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 17:47:02','2026-10-30 18:18:18'),(117,29,NULL,'PRODUCT_CHANGE',22,'RRP updated: Fresh fri','The recommendation changed from KSh 385.00 to KSh 385.00 for Fresh fri (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-29 17:47:02',NULL),(118,3,NULL,'GENERAL',12,'New direct message','A new message has arrived from Manji food indurstries.','READ','2026-10-29 19:29:54','2026-10-29 22:30:20'),(119,33,NULL,'GENERAL',12,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-10-29 19:30:48','2026-10-30 17:33:51'),(120,33,NULL,'SUPPLY_CHANGE',51,'Regional product appointment created','Regional appointment for Digestive biscuits in Nairobi: Fratres Limited Kitui was appointed from 2026-10-29 with no planned end date.','READ','2026-10-29 19:35:01','2026-10-30 17:19:49'),(121,3,NULL,'SUPPLY_CHANGE',51,'New regional product appointment','Regional appointment for Digestive biscuits in Nairobi: Fratres Limited Kitui was appointed from 2026-10-29 with no planned end date.','READ','2026-10-29 19:35:01','2026-10-30 17:34:15'),(122,5,NULL,'PRODUCT_CHANGE',5,'RRP updated: Muffins','A new recommendation of KSh 250.00 was published for Muffins (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 19:49:39','2026-10-30 18:18:18'),(123,29,NULL,'PRODUCT_CHANGE',5,'RRP updated: Muffins','A new recommendation of KSh 250.00 was published for Muffins (version 1). The recommended retail price (RRP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-29 19:49:39',NULL),(124,3,NULL,'PRODUCT_CHANGE',5,'RSP updated: Muffins','A new recommendation of KSh 200.00 was published for Muffins (version 1). The supplier recommended selling price (RSP) is effective 2026-10-29. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-29 19:50:31','2026-10-30 17:34:15'),(125,5,NULL,'PROMOTION',8,'New manufacturer campaign available','Campaign \"back to school\" is approved and runs from 2026-10-29 to 2026-11-05. Review the offer in Promotions > Manufacturer campaigns.','READ','2026-10-29 19:53:52','2026-10-30 18:18:18'),(126,29,NULL,'PROMOTION',8,'New manufacturer campaign available','Campaign \"back to school\" is approved and runs from 2026-10-29 to 2026-11-05. Review the offer in Promotions > Manufacturer campaigns.','UNREAD','2026-10-29 19:53:52',NULL),(127,2,NULL,'PROMOTION',8,'Retailer accepted campaign','A retailer accepted campaign \"back to school\" and selected 3 branch(es).','READ','2026-10-29 19:54:56','2026-10-30 17:39:16'),(128,2,NULL,'PROMOTION',8,'Retailer commenced campaign','Campaign \"back to school\" has commenced at branch Main Branch.','READ','2026-10-29 19:54:59','2026-10-30 17:39:16'),(129,2,NULL,'PROMOTION',8,'Retailer commenced campaign','Campaign \"back to school\" has commenced at branch Target Machakos.','READ','2026-10-29 19:55:01','2026-10-30 17:39:16'),(130,2,NULL,'PROMOTION',8,'Retailer commenced campaign','Campaign \"back to school\" has commenced at branch Target Supermarket.','READ','2026-10-29 19:55:02','2026-10-30 17:39:16'),(131,5,NULL,'PRODUCT_CHANGE',23,'RRP updated: Digestive biscuits','A new recommendation of KSh 185.00 was published for Digestive biscuits (version 1). The recommended retail price (RRP) is effective 2026-10-30. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-30 03:26:55','2026-10-30 18:18:18'),(132,29,NULL,'PRODUCT_CHANGE',23,'RRP updated: Digestive biscuits','A new recommendation of KSh 185.00 was published for Digestive biscuits (version 1). The recommended retail price (RRP) is effective 2026-10-30. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-10-30 03:26:55',NULL),(133,3,NULL,'PRODUCT_CHANGE',23,'RSP updated: Digestive biscuits','A new recommendation of KSh 145.00 was published for Digestive biscuits (version 1). The supplier recommended selling price (RSP) is effective 2026-10-30. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-30 03:27:07','2026-10-30 17:34:15'),(134,3,NULL,'PRODUCT_CHANGE',23,'RSP updated: Digestive biscuits','The recommendation changed from KSh 145.00 to KSh 145.00 for Digestive biscuits (version 1). The supplier recommended selling price (RSP) is effective 2026-10-30. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-10-30 03:27:20','2026-10-30 17:34:15'),(135,3,NULL,'SUPPLY_CHANGE',33,'Price update needs review: Digestive biscuits','Your current cost or selling-price records for Digestive biscuits, version 1, need review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-30.','READ','2026-10-30 03:28:03','2026-10-30 17:34:15'),(136,3,NULL,'SUPPLY_CHANGE',33,'Price update needs review: Digestive biscuits','Your current cost or selling-price records for Digestive biscuits, version 1, need review before automatic repricing can proceed. No automatic price adjustment was made to your organisation. Effective date: 2026-10-31.','READ','2026-10-31 03:29:27','2026-11-02 20:37:51'),(137,3,NULL,'SUPPLY_CHANGE',33,'Manufacturer price decreased: Digestive biscuits','The direct manufacturer supply price for Digestive biscuits, version 1, decreased from KSh 100.00 to KSh 95.00, effective 2026-10-31. Automatic adjustment was skipped because a supplier offer is current or scheduled for this product.','READ','2026-10-31 03:33:32','2026-11-02 20:37:51'),(138,11,NULL,'SUPPLY_CHANGE',52,'Regional product appointment created','Regional appointment for Quencher lime in Kitui: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:23:42','2026-11-02 02:32:04'),(139,3,NULL,'SUPPLY_CHANGE',52,'New regional product appointment','Regional appointment for Quencher lime in Kitui: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:23:42','2026-11-02 20:37:51'),(140,2,NULL,'SUPPLY_CHANGE',53,'Regional product appointment created','Regional appointment for Mill cookies in Kitui: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:32:56','2026-11-02 11:45:30'),(141,3,NULL,'SUPPLY_CHANGE',53,'New regional product appointment','Regional appointment for Mill cookies in Kitui: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:32:56','2026-11-02 20:37:51'),(142,2,NULL,'SUPPLY_CHANGE',54,'Regional product appointment created','Regional appointment for Mill cookies in Nairobi: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:37:06','2026-11-02 11:45:22'),(143,3,NULL,'SUPPLY_CHANGE',54,'New regional product appointment','Regional appointment for Mill cookies in Nairobi: Fratres Limited Kitui was appointed from 2026-11-01 with no planned end date.','READ','2026-11-01 14:37:06','2026-11-02 20:37:51'),(144,5,NULL,'SUPPLY_CHANGE',54,'Supply arrangement awaiting your decision','Fratres Limited Kitui has been appointed to supply Mill cookies in Nairobi from 2026-11-01. Open Supply arrangements to accept or decline.','READ','2026-11-01 14:37:06','2026-11-02 02:32:41'),(145,3,NULL,'LPO_ACKNOWLEDGED',8,'LPO acknowledgement received','The receiving organisation has acknowledged LPO LPO-000001. Product availability, shortages and expected delivery details are now available in the LPO.','READ','2026-11-02 17:35:54','2026-11-02 20:37:42'),(146,3,NULL,'LPO_ACKNOWLEDGED',9,'LPO acknowledgement received','The receiving organisation has acknowledged LPO LPO-000002. Product availability, shortages and expected delivery details are now available in the LPO.','READ','2026-11-02 17:36:41','2026-11-02 20:36:57'),(147,2,NULL,'SUPPLY_CHANGE',55,'Regional product appointment created','Regional appointment for Heart Deli Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-11-03 with no planned end date.','READ','2026-11-03 03:17:31','2026-11-03 17:57:50'),(148,3,NULL,'SUPPLY_CHANGE',55,'New regional product appointment','Regional appointment for Heart Deli Bread in Nairobi: Fratres Limited Kitui was appointed from 2026-11-03 with no planned end date.','READ','2026-11-03 03:17:31','2026-11-03 12:24:58'),(149,5,NULL,'SUPPLY_CHANGE',55,'Supply arrangement awaiting your decision','Fratres Limited Kitui has been appointed to supply Heart Deli Bread in Nairobi from 2026-11-03. Open Supply arrangements to accept or decline.','READ','2026-11-03 03:17:31','2026-11-03 06:18:35'),(150,3,NULL,'SUPPLY_CHANGE',2,'Manufacturer price changed: Heart Deli Bread','Mill Bakers changed the manufacturer selling price of Heart Deli Bread, version 1, from KSh 320.00 to KSh 340.00, effective 2026-11-03. Your acquisition cost for this product has changed. Your organisation has Price Change Protection enabled, so your cost was adjusted from KSh 305.58 to KSh 340.00 and your selling price from KSh 322.69 to KSh 359.04.','READ','2026-11-03 03:48:46','2026-11-03 12:24:58'),(151,5,NULL,'SUPPLY_CHANGE',2,'Product price updated: Heart Deli Bread','Price update processed for Heart Deli Bread, version 1. Your own recorded cost changed from KSh 322.69 to KSh 359.04, and your own selling price changed from KSh 445.00 to KSh 499.99. Effective date: 2026-11-03. This notification contains only your organisation\'s own prices.','READ','2026-11-03 03:48:46','2026-11-04 12:12:41'),(152,3,NULL,'SUPPLY_CHANGE',2,'Manufacturer price changed: Heart Deli Bread','Mill Bakers changed the manufacturer selling price of Heart Deli Bread, version 1, from KSh 340.00 to KSh 360.00, effective 2026-11-03. Your acquisition cost for this product has changed. Your organisation has Price Change Protection enabled, so your cost was adjusted from KSh 340.00 to KSh 360.00 and your selling price from KSh 359.04 to KSh 380.16.','READ','2026-11-03 04:02:47','2026-11-03 12:24:58'),(153,5,NULL,'SUPPLY_CHANGE',2,'Product price updated: Heart Deli Bread','Price update processed for Heart Deli Bread, version 1. Your own recorded cost changed from KSh 359.04 to KSh 380.16, and your own selling price changed from KSh 499.99 to KSh 529.99. Effective date: 2026-11-03. This notification contains only your organisation\'s own prices.','READ','2026-11-03 04:02:47','2026-11-04 12:12:41'),(154,2,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending','Fratres Limited Kitui gave notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui. It ends on 2026-11-18, and the appointments and offers between them end on the same date. Replacements are still needed for: Heart Deli Bread (Nairobi), Mill cookies (Kitui), Mill cookies (Nairobi).','READ','2026-11-04 01:14:50','2026-11-04 14:57:44'),(155,3,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending','Fratres Limited Kitui gave notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui. It ends on 2026-11-18, and the appointments and offers between them end on the same date. Replacements are still needed for: Heart Deli Bread (Nairobi), Mill cookies (Kitui), Mill cookies (Nairobi).','READ','2026-11-04 01:14:50','2026-11-05 06:35:54'),(156,5,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending','Fratres Limited Kitui will stop supplying Heart Deli Bread (Nairobi), Mill cookies (Nairobi) on 2026-11-18. Connect with another appointed supplier to keep receiving these products.','READ','2026-11-04 01:14:50','2026-11-04 12:12:41'),(157,2,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending withdrawn','The notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui was withdrawn by Fratres Limited Kitui. Supply continues.','READ','2026-11-04 01:15:10','2026-11-04 14:57:44'),(158,3,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending withdrawn','The notice to end the supply relationship between Mill Bakers and Fratres Limited Kitui was withdrawn by Fratres Limited Kitui. Supply continues.','READ','2026-11-04 01:15:10','2026-11-05 06:35:54'),(159,5,NULL,'SUPPLY_CHANGE',35,'Supply relationship ending withdrawn','Fratres Limited Kitui will continue supplying: Heart Deli Bread (Nairobi), Mill cookies (Nairobi). The notice to stop supplying has been withdrawn.','READ','2026-11-04 01:15:10','2026-11-04 12:12:35'),(160,3,NULL,'SUPPLY_CHANGE',2,'Tax treatment changed: Heart Deli Bread','The VAT rate applying to Heart Deli Bread version 1 (400g) changes from 16.00% to 0.00% from 2026-11-04. The manufacturer adjusted its own prices to keep its net revenue. Review your own prices for this product.','READ','2026-11-04 10:54:49','2026-11-05 06:35:54'),(161,5,NULL,'SUPPLY_CHANGE',2,'Tax treatment changed: Heart Deli Bread','The VAT rate applying to Heart Deli Bread version 1 (400g) changes from 16.00% to 0.00% from 2026-11-04. The manufacturer adjusted its own prices to keep its net revenue. Review your own prices for this product.','READ','2026-11-04 10:54:49','2026-11-04 14:58:49'),(162,3,NULL,'SUPPLY_CHANGE',17,'Manufacturer price decreased: Quencher lime','The direct manufacturer supply price for Quencher lime, version 1, decreased from KSh 120.00 to KSh 110.00, effective 2026-11-04. Automatic adjustment was skipped because a supplier offer is current or scheduled for this product.','READ','2026-11-04 12:45:53','2026-11-05 06:35:54'),(163,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 02:47:34','2026-11-05 05:55:19'),(164,3,NULL,'GENERAL',5,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-11-05 02:48:24','2026-11-05 05:54:35'),(165,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 02:55:13','2026-11-05 05:55:19'),(166,3,NULL,'GENERAL',5,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-11-05 02:55:32','2026-11-05 06:23:56'),(167,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 03:24:08','2026-11-05 06:24:21'),(168,3,NULL,'GENERAL',5,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-11-05 03:24:29','2026-11-05 06:35:51'),(169,3,NULL,'GENERAL',5,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-11-05 03:35:39','2026-11-05 06:35:51'),(170,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 03:36:25','2026-11-05 06:36:33'),(171,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 04:52:29','2026-11-06 04:04:19'),(172,5,NULL,'GENERAL',5,'New direct message','A new message has arrived from Fratres Limited Kitui.','READ','2026-11-05 05:08:46','2026-11-06 04:04:19'),(173,3,NULL,'GENERAL',5,'New direct message','A new message has arrived from Target Supermarket.','READ','2026-11-05 05:09:01','2026-11-07 16:22:19'),(174,2,NULL,'PROMOTION',8,'Retailer completed campaign','Campaign \"back to school\" was marked complete at branch Main Branch.','READ','2026-11-07 11:37:42','2026-11-07 16:32:47'),(175,2,NULL,'PROMOTION',8,'Retailer completed campaign','Campaign \"back to school\" was marked complete at branch Target Machakos.','READ','2026-11-07 11:37:44','2026-11-07 16:32:47'),(176,2,NULL,'PROMOTION',8,'Retailer completed campaign','Campaign \"back to school\" was marked complete at branch Target Supermarket.','READ','2026-11-07 11:37:48','2026-11-07 16:32:47'),(177,2,NULL,'PROMOTION',5,'Retailer completed campaign','Campaign \"Testing promotional campaings\" was marked complete at branch Main Branch.','READ','2026-11-07 11:38:07','2026-11-07 16:32:47'),(178,5,NULL,'PRODUCT_CHANGE',25,'RRP updated: High Madeira','A new recommendation of KSh 395.00 was published for High Madeira (version 1). The recommended retail price (RRP) is effective 2026-11-07. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-11-07 13:30:27','2026-11-10 13:32:14'),(179,29,NULL,'PRODUCT_CHANGE',25,'RRP updated: High Madeira','A new recommendation of KSh 395.00 was published for High Madeira (version 1). The recommended retail price (RRP) is effective 2026-11-07. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','UNREAD','2026-11-07 13:30:27',NULL),(180,3,NULL,'PRODUCT_CHANGE',25,'RSP updated: High Madeira','A new recommendation of KSh 355.00 was published for High Madeira (version 1). The supplier recommended selling price (RSP) is effective 2026-11-07. This is non-binding pricing guidance; your organisation retains control over its own selling prices.','READ','2026-11-07 13:30:42','2026-11-07 16:35:43'),(181,2,NULL,'SUPPLY_CHANGE',56,'Regional product appointment created','Regional appointment for High Madeira in Nairobi: Fratres Limited Kitui was appointed from 2026-11-07 through 2026-11-08.','READ','2026-11-07 13:32:27','2026-11-07 16:32:43'),(182,3,NULL,'SUPPLY_CHANGE',56,'New regional product appointment','Regional appointment for High Madeira in Nairobi: Fratres Limited Kitui was appointed from 2026-11-07 through 2026-11-08.','READ','2026-11-07 13:32:27','2026-11-07 16:34:01'),(183,5,NULL,'SUPPLY_CHANGE',56,'Supply arrangement awaiting your decision','Fratres Limited Kitui has been appointed to supply High Madeira in Nairobi from 2026-11-07. Open Supply arrangements to accept or decline.','READ','2026-11-07 13:32:27','2026-11-10 13:32:14'),(184,2,NULL,'SUPPLY_CHANGE',58,'Regional product appointment created','Regional appointment for Muffins in Nairobi: Fratres Limited Kitui was appointed from 2026-11-08 with no planned end date.','UNREAD','2026-11-08 04:07:02',NULL),(185,3,NULL,'SUPPLY_CHANGE',58,'New regional product appointment','Regional appointment for Muffins in Nairobi: Fratres Limited Kitui was appointed from 2026-11-08 with no planned end date.','UNREAD','2026-11-08 04:07:02',NULL),(186,5,NULL,'SUPPLY_CHANGE',58,'Supply arrangement awaiting your decision','Fratres Limited Kitui has been appointed to supply Muffins in Nairobi from 2026-11-08. Open Supply arrangements to accept or decline.','READ','2026-11-08 04:07:02','2026-11-10 13:32:14'),(187,2,NULL,'SUPPLY_CHANGE',59,'Regional product appointment created','Regional appointment for Mill cookies in Mombasa: Fratres Limited Kitui was invited to supply from 2026-11-08 with no planned end date, subject to the supplier accepting.','UNREAD','2026-11-08 04:50:40',NULL),(188,3,NULL,'SUPPLY_CHANGE',59,'Appointment awaiting your acceptance','Regional appointment for Mill cookies in Mombasa: Fratres Limited Kitui was invited to supply from 2026-11-08 with no planned end date, subject to the supplier accepting. Open Supply arrangements to accept or decline it.','UNREAD','2026-11-08 04:50:40',NULL),(189,5,NULL,'SUPPLY_CHANGE',59,'Supply arrangement awaiting your decision','Fratres Limited Kitui has been appointed to supply Mill cookies in Mombasa from 2026-11-08. Open Supply arrangements to accept or decline.','READ','2026-11-08 04:52:17','2026-11-10 13:32:14'),(190,2,NULL,'SUPPLY_CHANGE',59,'Appointment accepted','Fratres Limited Kitui accepted the appointment to supply Mill cookies in Mombasa.','READ','2026-11-08 04:52:17','2026-11-08 07:52:33'),(191,34,NULL,'SUPPLY_CHANGE',60,'Regional product appointment created','Regional appointment for Gold crown in Nairobi: Fratres Limited Kitui was invited to supply from 2026-11-08 with no planned end date, subject to the supplier accepting.','UNREAD','2026-11-08 13:02:48',NULL),(192,3,NULL,'SUPPLY_CHANGE',60,'Appointment awaiting your acceptance','Regional appointment for Gold crown in Nairobi: Fratres Limited Kitui was invited to supply from 2026-11-08 with no planned end date, subject to the supplier accepting. Open Supply arrangements to accept or decline it.','UNREAD','2026-11-08 13:02:48',NULL),(193,2,NULL,'PROMOTION',7,'Retailer completed campaign','Campaign \"Holiday Times\" was marked complete at branch Main Branch.','UNREAD','2026-11-11 09:29:53',NULL),(194,2,NULL,'PROMOTION',7,'Retailer completed campaign','Campaign \"Holiday Times\" was marked complete at branch Target Machakos.','UNREAD','2026-11-11 09:29:55',NULL),(195,2,NULL,'PROMOTION',7,'Retailer completed campaign','Campaign \"Holiday Times\" was marked complete at branch Target Supermarket.','UNREAD','2026-11-11 09:29:56',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_areas`
--

DROP TABLE IF EXISTS `organization_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_areas` (
  `organization_area_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `area_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`organization_area_id`),
  UNIQUE KEY `uq_organization_area` (`organization_id`,`area_id`,`start_date`),
  KEY `fk_organization_area_area` (`area_id`),
  CONSTRAINT `fk_organization_area_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_organization_area_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_areas`
--

LOCK TABLES `organization_areas` WRITE;
/*!40000 ALTER TABLE `organization_areas` DISABLE KEYS */;
INSERT INTO `organization_areas` VALUES (1,1,1,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(2,1,2,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(3,1,3,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(4,2,1,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(5,2,2,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(6,3,4,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(8,5,1,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(9,5,2,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(10,5,3,'2026-01-01',NULL,'ACTIVE','2026-10-04 12:27:51'),(12,3,1,'2026-10-29',NULL,'ACTIVE','2026-10-29 06:51:12');
/*!40000 ALTER TABLE `organization_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_connection_requests`
--

DROP TABLE IF EXISTS `organization_connection_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_connection_requests` (
  `request_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requester_organization_id` int(10) unsigned NOT NULL,
  `recipient_organization_id` int(10) unsigned NOT NULL,
  `source_organization_id` int(10) unsigned NOT NULL,
  `target_organization_id` int(10) unsigned NOT NULL,
  `relationship_type` enum('SUPPLIES','DISTRIBUTES') NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `requested_by_user_id` int(10) unsigned NOT NULL,
  `decided_by_user_id` int(10) unsigned DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_connection_requester` (`requester_organization_id`,`status`),
  KEY `idx_connection_recipient` (`recipient_organization_id`,`status`),
  KEY `idx_connection_source` (`source_organization_id`),
  KEY `idx_connection_target` (`target_organization_id`),
  KEY `fk_connection_requested_by` (`requested_by_user_id`),
  KEY `fk_connection_decided_by` (`decided_by_user_id`),
  CONSTRAINT `fk_connection_decided_by` FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_recipient` FOREIGN KEY (`recipient_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_requested_by` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_requester` FOREIGN KEY (`requester_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_source` FOREIGN KEY (`source_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_connection_target` FOREIGN KEY (`target_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_connection_requests`
--

LOCK TABLES `organization_connection_requests` WRITE;
/*!40000 ALTER TABLE `organization_connection_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `organization_connection_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_price_protection`
--

DROP TABLE IF EXISTS `organization_price_protection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_price_protection` (
  `organization_id` int(10) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`organization_id`),
  KEY `fk_org_price_protection_user` (`updated_by`),
  CONSTRAINT `fk_org_price_protection_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_org_price_protection_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_price_protection`
--

LOCK TABLES `organization_price_protection` WRITE;
/*!40000 ALTER TABLE `organization_price_protection` DISABLE KEYS */;
INSERT INTO `organization_price_protection` VALUES (2,1,7,'2026-11-01 23:48:40','2026-11-02 05:26:15'),(3,1,28,'2026-10-26 05:46:36','2026-10-29 18:03:12'),(5,1,1,'2026-10-26 05:40:30','2026-11-11 09:42:04'),(11,1,11,'2026-11-02 00:18:47','2026-11-02 00:19:29'),(28,1,35,'2026-11-04 12:20:12','2026-11-04 12:20:12'),(31,1,40,'2026-10-29 16:43:17','2026-10-29 16:43:17'),(33,1,42,'2026-10-30 03:22:08','2026-10-30 03:22:08');
/*!40000 ALTER TABLE `organization_price_protection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_relationships`
--

DROP TABLE IF EXISTS `organization_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_relationships` (
  `relationship_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_organization_id` int(10) unsigned NOT NULL,
  `target_organization_id` int(10) unsigned NOT NULL,
  `relationship_type` enum('SUPPLIES','DISTRIBUTES') NOT NULL,
  `status` enum('PENDING','ACTIVE','INACTIVE') NOT NULL DEFAULT 'PENDING',
  `requested_by_organization_id` int(10) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`relationship_id`),
  KEY `fk_relationship_source` (`source_organization_id`),
  KEY `fk_relationship_target` (`target_organization_id`),
  KEY `fk_relationships_requested_by` (`requested_by_organization_id`),
  CONSTRAINT `fk_relationship_source` FOREIGN KEY (`source_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_relationship_target` FOREIGN KEY (`target_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_relationships_requested_by` FOREIGN KEY (`requested_by_organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_relationships`
--

LOCK TABLES `organization_relationships` WRITE;
/*!40000 ALTER TABLE `organization_relationships` DISABLE KEYS */;
INSERT INTO `organization_relationships` VALUES (1,1,5,'SUPPLIES','ACTIVE',NULL,'2026-01-01',NULL,'2026-10-04 12:29:13'),(2,2,3,'SUPPLIES','INACTIVE',NULL,'2026-01-01','2026-11-03','2026-10-04 12:29:13'),(3,3,5,'SUPPLIES','ACTIVE',NULL,'2026-01-01',NULL,'2026-10-04 12:29:13'),(6,1,3,'SUPPLIES','INACTIVE',1,'2026-10-18','2026-10-18','2026-10-18 15:56:23'),(7,1,3,'SUPPLIES','INACTIVE',1,'2026-10-19','2026-10-20','2026-10-18 22:16:22'),(8,11,5,'SUPPLIES','ACTIVE',11,'2026-10-19',NULL,'2026-10-19 11:28:17'),(9,2,5,'SUPPLIES','INACTIVE',2,'2026-10-19','2026-10-21','2026-10-19 11:44:00'),(10,1,3,'SUPPLIES','ACTIVE',3,'2026-10-20',NULL,'2026-10-20 04:50:51'),(11,26,5,'SUPPLIES','ACTIVE',26,'2026-10-20',NULL,'2026-10-20 06:42:37'),(12,27,3,'SUPPLIES','ACTIVE',27,'2026-10-20',NULL,'2026-10-20 08:12:01'),(13,9,5,'SUPPLIES','INACTIVE',9,'2026-10-20','2026-10-21','2026-10-20 12:55:21'),(14,9,10,'SUPPLIES','INACTIVE',9,'2026-10-20','2026-10-24','2026-10-20 12:55:36'),(15,27,28,'SUPPLIES','ACTIVE',28,'2026-10-20',NULL,'2026-10-20 13:15:28'),(16,9,28,'SUPPLIES','INACTIVE',28,'2026-10-20','2026-10-21','2026-10-20 13:15:34'),(17,2,28,'SUPPLIES','INACTIVE',28,'2026-10-20','2026-10-20','2026-10-20 13:15:45'),(18,28,5,'SUPPLIES','ACTIVE',28,'2026-10-20',NULL,'2026-10-20 13:15:54'),(19,10,5,'SUPPLIES','PENDING',5,'2026-10-21',NULL,'2026-10-21 10:38:00'),(20,9,28,'SUPPLIES','ACTIVE',28,'2026-10-21',NULL,'2026-10-21 11:25:43'),(21,3,29,'SUPPLIES','ACTIVE',29,'2026-10-25',NULL,'2026-10-22 14:17:26'),(22,28,29,'SUPPLIES','PENDING',29,'2026-10-22',NULL,'2026-10-22 14:17:42'),(23,10,29,'SUPPLIES','PENDING',29,'2026-10-22',NULL,'2026-10-22 14:17:52'),(24,1,29,'SUPPLIES','ACTIVE',29,'2026-10-25',NULL,'2026-10-22 14:18:00'),(25,26,29,'SUPPLIES','ACTIVE',29,'2026-10-28',NULL,'2026-10-22 14:18:07'),(26,11,29,'SUPPLIES','ACTIVE',29,'2026-10-28',NULL,'2026-10-22 14:18:12'),(27,9,3,'SUPPLIES','INACTIVE',9,'2026-10-24','2026-10-24','2026-10-24 12:38:28'),(28,2,28,'SUPPLIES','PENDING',2,'2026-10-25',NULL,'2026-10-25 05:25:20'),(29,11,3,'SUPPLIES','ACTIVE',11,'2026-10-29',NULL,'2026-10-29 06:51:12'),(30,26,3,'SUPPLIES','ACTIVE',26,'2026-10-29',NULL,'2026-10-29 06:51:12'),(31,9,3,'SUPPLIES','ACTIVE',9,'2026-10-29',NULL,'2026-10-29 06:51:12'),(32,30,5,'SUPPLIES','ACTIVE',30,'2026-10-29',NULL,'2026-10-29 14:55:55'),(33,31,3,'SUPPLIES','ACTIVE',31,'2026-10-29',NULL,'2026-10-29 16:27:51'),(34,33,3,'SUPPLIES','ACTIVE',33,'2026-10-29',NULL,'2026-10-29 19:25:41'),(35,2,3,'SUPPLIES','ACTIVE',2,'2026-11-03',NULL,'2026-11-03 09:23:56'),(36,34,3,'SUPPLIES','ACTIVE',34,'2026-11-08',NULL,'2026-11-08 12:59:05');
/*!40000 ALTER TABLE `organization_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `organization_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(150) NOT NULL,
  `organization_type` enum('MANUFACTURER','SUPPLIER','MANUFACTURER_SUPPLIER','RETAILER') NOT NULL,
  `kra_pin` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`organization_id`),
  UNIQUE KEY `kra_pin` (`kra_pin`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,'Chandaria Industries','MANUFACTURER_SUPPLIER',NULL,'info@chandaria.com',NULL,'Kenya','uploads/organization-logos/org-1-7c713bfa53bd580cc10c456d4f6fc28c.jpg','ACTIVE','2026-10-04 12:21:24'),(2,'Mill Bakers','MANUFACTURER',NULL,'millbakerskenya@gmail.co.ke',NULL,'Kenya','uploads/organization-logos/org-2-5240b78aaf79e71cac3b4e4b5f22d4f4.jpg','ACTIVE','2026-10-04 12:21:24'),(3,'Fratres Limited Kitui','SUPPLIER',NULL,NULL,NULL,'Kitui, Kenya','uploads/organization-logos/org-3-a47bafb3977208b5747d9190d8c7f3d1.jpg','ACTIVE','2026-10-04 12:21:24'),(5,'Target Supermarket','RETAILER',NULL,'targetsupermarkets@globall.co.ke','+254 700 000 000','Kenya','uploads/organization-logos/org-5-84eae099178040d74aba3181fdbb50fa.jpg','ACTIVE','2026-10-04 12:21:24'),(9,'P&G limitted','MANUFACTURER',NULL,'proctorgamble@gmail.com','+254769231217',NULL,'uploads/organization-logos/org-9-c5e84a430911a988874a9513745cc03d.jpg','ACTIVE','2026-10-07 03:46:29'),(10,'Summer Africa','SUPPLIER',NULL,'summerafrica@gmail.com','0712344321',NULL,NULL,'ACTIVE','2026-10-07 07:36:13'),(11,'Excel Chemicals','MANUFACTURER_SUPPLIER',NULL,'excelchemicals@gmail.com','011234567789',NULL,'uploads/organization-logos/org-11-e364c6f6a234aefe30a732ba6df7e165.jpg','ACTIVE','2026-10-07 07:38:37'),(12,'Test Org 043555','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-08 01:35:56'),(13,'Phase0 Test 140746','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-09 11:07:46'),(14,'Phase0 Test 20261010025546','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-09 23:55:46'),(15,'Phase0 Test 20261010025634','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-09 23:56:34'),(16,'Phase0 Test 20261010031252','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 00:12:52'),(17,'Phase0 Test 20261010033114','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 00:31:14'),(18,'Phase0 Test 20261010074601','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 04:46:01'),(19,'Rule Test 133653','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 10:36:53'),(20,'Rule Test 134101','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 10:41:01'),(21,'Rule Test 134250','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 10:42:50'),(22,'Rule Test 134256','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 10:42:56'),(23,'Phase0 Test 20261010190514','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 16:05:14'),(24,'Phase0 Test 20261010194159','RETAILER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-10 16:41:59'),(25,'testretailer','MANUFACTURER',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-14 12:42:00'),(26,'Uniliver limitted','MANUFACTURER_SUPPLIER','A123456789Z','uniliverkenya@gmail.co.ke','073462783843','Thika mombasa rd','uploads/organization-logos/org-26-10dbcce349a5db4f0458c20f8bdea427.jpg','ACTIVE','2026-10-20 06:36:09'),(27,'Cadbury limited','MANUFACTURER',NULL,'cadburylimited@gmail.co.ke','075677464785',NULL,'uploads/organization-logos/org-27-500d38f167fad1a9e846f54c0ced1f41.jpg','ACTIVE','2026-10-20 08:06:35'),(28,'Hasbah','SUPPLIER',NULL,'hasbahkenya@gmail.co.ke','0769231217',NULL,'uploads/organization-logos/org-28-b72a217793f50643e00603a2a784a887.jpg','ACTIVE','2026-10-20 13:15:11'),(29,'Magunas Supermarkets','RETAILER',NULL,'magunas@globall.co.ke','075467384565',NULL,NULL,'ACTIVE','2026-10-22 14:17:06'),(30,'Broockside indursty','MANUFACTURER_SUPPLIER',NULL,'brooksidegloball@gmail.com','0746573957694',NULL,NULL,'ACTIVE','2026-10-29 14:54:20'),(31,'Pwani oil products','MANUFACTURER',NULL,'pwanioil@globall.co.ke','+254700034336','kikambala',NULL,'ACTIVE','2026-10-29 16:18:24'),(32,'Greenvastlimited','RETAILER',NULL,'greenvastgloball@gmail.org',NULL,'Garissa Rd',NULL,'ACTIVE','2026-10-29 18:59:59'),(33,'Manji food indurstries','MANUFACTURER',NULL,'manji@globall.org',NULL,'kisauni',NULL,'ACTIVE','2026-10-29 19:24:56'),(34,'Kenya creameries cooporation','MANUFACTURER',NULL,'kccdairies@gmail.com',NULL,NULL,NULL,'ACTIVE','2026-11-08 12:54:06');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_announcement_recipients`
--

DROP TABLE IF EXISTS `product_announcement_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_announcement_recipients` (
  `recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `acknowledgement_status` enum('PENDING','ACKNOWLEDGED') NOT NULL DEFAULT 'PENDING',
  `acknowledged_by` int(10) unsigned DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recipient_id`),
  UNIQUE KEY `uq_announcement_recipient` (`announcement_id`,`organization_id`),
  KEY `idx_announcement_recipient_org` (`organization_id`),
  KEY `fk_announcement_acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `fk_announcement_acknowledged_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_recipient_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `product_announcements` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_recipient_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_announcement_recipients`
--

LOCK TABLES `product_announcement_recipients` WRITE;
/*!40000 ALTER TABLE `product_announcement_recipients` DISABLE KEYS */;
INSERT INTO `product_announcement_recipients` VALUES (1,1,5,'ACKNOWLEDGED',1,'2026-10-15 18:52:50','2026-10-15 15:52:27'),(2,2,3,'ACKNOWLEDGED',4,'2026-10-15 21:08:47','2026-10-15 16:35:10'),(3,2,5,'ACKNOWLEDGED',1,'2026-10-15 19:37:19','2026-10-15 16:35:10'),(4,3,3,'ACKNOWLEDGED',28,'2026-10-21 14:14:27','2026-10-16 02:54:03'),(5,3,5,'ACKNOWLEDGED',1,'2026-10-16 05:54:46','2026-10-16 02:54:03'),(6,4,3,'ACKNOWLEDGED',28,'2026-10-21 14:14:20','2026-10-16 09:49:07'),(7,4,5,'ACKNOWLEDGED',1,'2026-10-16 12:51:53','2026-10-16 09:49:07'),(8,5,5,'ACKNOWLEDGED',1,'2026-10-19 14:41:45','2026-10-19 11:40:40'),(9,6,5,'ACKNOWLEDGED',1,'2026-10-20 09:49:04','2026-10-20 06:48:33'),(10,7,3,'ACKNOWLEDGED',28,'2026-10-20 11:15:37','2026-10-20 08:14:14'),(11,7,5,'ACKNOWLEDGED',1,'2026-10-20 13:54:51','2026-10-20 08:14:14'),(12,8,5,'ACKNOWLEDGED',1,'2026-10-22 10:17:23','2026-10-22 07:16:47'),(13,8,28,'PENDING',NULL,NULL,'2026-10-22 07:16:47'),(14,9,3,'ACKNOWLEDGED',28,'2026-10-29 19:36:22','2026-10-29 16:35:23'),(15,9,5,'PENDING',NULL,NULL,'2026-10-29 16:35:23'),(16,9,29,'PENDING',NULL,NULL,'2026-10-29 16:35:23'),(17,10,3,'ACKNOWLEDGED',28,'2026-10-29 22:38:22','2026-10-29 19:36:14'),(18,10,5,'ACKNOWLEDGED',1,'2026-10-29 22:39:35','2026-10-29 19:36:14'),(19,10,29,'PENDING',NULL,NULL,'2026-10-29 19:36:14');
/*!40000 ALTER TABLE `product_announcement_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_announcements`
--

DROP TABLE IF EXISTS `product_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_announcements` (
  `announcement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `announcement_title` varchar(200) NOT NULL,
  `announcement_message` text DEFAULT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('PUBLISHED','WITHDRAWN') NOT NULL DEFAULT 'PUBLISHED',
  PRIMARY KEY (`announcement_id`),
  KEY `idx_announcement_product` (`product_id`),
  KEY `idx_announcement_manufacturer` (`manufacturer_id`),
  CONSTRAINT `fk_announcement_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_announcements`
--

LOCK TABLES `product_announcements` WRITE;
/*!40000 ALTER TABLE `product_announcements` DISABLE KEYS */;
INSERT INTO `product_announcements` VALUES (1,6,2,'new type of bread','hello we will be introducing a new type of bread','2026-10-15 15:52:27','PUBLISHED'),(2,7,2,'Introducing Mill Bakers Multigrain Bread 700g',NULL,'2026-10-15 16:35:10','PUBLISHED'),(3,8,2,'new product','millbakers will now be making cookies order some to check product movement','2026-10-16 02:54:03','PUBLISHED'),(4,9,2,'a new product in market dont be left out in sales',NULL,'2026-10-16 09:49:07','PUBLISHED'),(5,10,11,'Introducing a new flavour','We are introducing a new flavour juice drink availablke at our deports you can order now','2026-10-19 11:40:40','PUBLISHED'),(6,11,26,'Intoduced a new product','we have introduced a new product availble for order','2026-10-20 06:48:33','PUBLISHED'),(7,12,27,'Introducing  a new chocolate flavour','we are introducing a new chocolate flavour order now to avoid missed sales','2026-10-20 08:14:14','PUBLISHED'),(8,17,9,'update','order this product it is available','2026-10-22 07:16:47','PUBLISHED'),(9,22,31,'New product','We are introducing a new product in the market','2026-10-29 16:35:23','PUBLISHED'),(10,23,33,'Introduced a new product','WE wish to inform you we have intorduced a new product','2026-10-29 19:36:14','PUBLISHED');
/*!40000 ALTER TABLE `product_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_changes`
--

DROP TABLE IF EXISTS `product_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_changes` (
  `product_change_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `old_product_version_id` int(10) unsigned DEFAULT NULL,
  `new_product_version_id` int(10) unsigned NOT NULL,
  `change_type` enum('BARCODE_CHANGE','PACKAGING_CHANGE','PRODUCT_INFORMATION_CHANGE') NOT NULL,
  `reason` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','PUBLISHED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `published_by` int(10) unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_change_id`),
  KEY `fk_product_changes_product` (`product_id`),
  KEY `fk_product_changes_old_version` (`old_product_version_id`),
  KEY `fk_product_changes_new_version` (`new_product_version_id`),
  KEY `fk_product_changes_user` (`created_by`),
  KEY `fk_product_changes_published_by` (`published_by`),
  CONSTRAINT `fk_product_changes_new_version` FOREIGN KEY (`new_product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_changes_old_version` FOREIGN KEY (`old_product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_changes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_changes_published_by` FOREIGN KEY (`published_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_changes_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_changes`
--

LOCK TABLES `product_changes` WRITE;
/*!40000 ALTER TABLE `product_changes` DISABLE KEYS */;
INSERT INTO `product_changes` VALUES (1,1,1,2,'BARCODE_CHANGE','Packaging changed from 50ml to 45ml and a new barcode was introduced.','2026-08-01','PUBLISHED',2,NULL,NULL,'2026-10-04 12:50:51'),(2,6,7,8,'BARCODE_CHANGE','Packaging changed from 400g to 450g and a new barcode was introduced.','2026-10-10','PUBLISHED',7,NULL,NULL,'2026-10-05 02:55:02'),(5,3,3,11,'PACKAGING_CHANGE','to increase quantity','2027-01-01','PUBLISHED',7,7,'2026-10-15 04:36:42','2026-10-15 01:36:28'),(6,4,4,12,'PACKAGING_CHANGE','Product upgrade','2027-01-01','PUBLISHED',7,7,'2026-10-15 17:19:15','2026-10-15 14:19:12'),(7,9,15,16,'PRODUCT_INFORMATION_CHANGE','we are increasing the grammes','2028-06-07','PUBLISHED',27,7,'2026-10-16 12:47:05','2026-10-16 09:46:26'),(8,11,18,19,'BARCODE_CHANGE','Product upgrade','2026-10-30','PUBLISHED',29,29,'2026-10-20 09:44:07','2026-10-20 06:41:58'),(9,6,8,29,'BARCODE_CHANGE','Increase product quantity','2026-10-28','PUBLISHED',7,7,'2026-10-28 20:12:52','2026-10-28 17:12:45'),(10,21,30,31,'BARCODE_CHANGE','product modification','2026-10-30','PUBLISHED',39,39,'2026-10-29 18:13:22','2026-10-29 15:12:59'),(11,8,14,35,'PACKAGING_CHANGE','product modification','2026-11-02','PUBLISHED',7,7,'2026-11-02 10:16:20','2026-11-02 07:16:18'),(12,6,29,36,'BARCODE_CHANGE','product modification','2026-11-08','PUBLISHED',7,7,'2026-11-06 10:16:11','2026-11-06 07:16:07');
/*!40000 ALTER TABLE `product_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_price_recommendations`
--

DROP TABLE IF EXISTS `product_price_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_price_recommendations` (
  `recommendation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `recommendation_type` enum('RRP','RSP') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'KES',
  `effective_from` date NOT NULL,
  `created_by_user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recommendation_id`),
  KEY `idx_price_recommendation_current` (`product_version_id`,`recommendation_type`,`effective_from`,`created_at`),
  KEY `idx_price_recommendation_product` (`product_id`,`recommendation_type`,`effective_from`),
  KEY `idx_price_recommendation_manufacturer` (`manufacturer_id`,`recommendation_type`),
  KEY `fk_recommendation_author` (`created_by_user_id`),
  CONSTRAINT `fk_recommendation_author` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_recommendation_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_recommendation_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_recommendation_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_recommendation_amount` CHECK (`amount` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_price_recommendations`
--

LOCK TABLES `product_price_recommendations` WRITE;
/*!40000 ALTER TABLE `product_price_recommendations` DISABLE KEYS */;
INSERT INTO `product_price_recommendations` VALUES (1,2,6,8,'RRP',150.00,'KES','2026-10-28',7,'2026-10-28 03:43:31'),(2,2,6,8,'RSP',120.00,'KES','2026-10-28',7,'2026-10-28 03:43:53'),(3,11,10,17,'RRP',170.00,'KES','2026-10-28',11,'2026-10-28 11:49:36'),(4,11,10,17,'RRP',195.00,'KES','2026-10-28',11,'2026-10-28 11:49:53'),(5,11,10,17,'RSP',165.00,'KES','2026-10-28',11,'2026-10-28 11:50:30'),(6,27,20,28,'RRP',120.00,'KES','2026-10-28',31,'2026-10-28 11:55:25'),(7,27,20,28,'RSP',85.00,'KES','2026-10-28',31,'2026-10-28 11:55:36'),(8,2,8,14,'RRP',150.00,'KES','2026-10-28',7,'2026-10-28 13:54:35'),(9,2,8,14,'RSP',120.00,'KES','2026-10-28',7,'2026-10-28 14:54:02'),(10,30,21,30,'RRP',65.00,'KES','2026-10-29',39,'2026-10-29 15:07:09'),(11,31,22,32,'RRP',385.00,'KES','2026-10-29',40,'2026-10-29 16:44:09'),(12,31,22,32,'RSP',320.00,'KES','2026-10-29',40,'2026-10-29 16:44:39'),(13,31,22,32,'RRP',385.00,'KES','2026-10-29',40,'2026-10-29 17:47:02'),(14,2,5,5,'RRP',250.00,'KES','2026-10-29',7,'2026-10-29 19:49:39'),(15,2,5,5,'RSP',200.00,'KES','2026-10-29',7,'2026-10-29 19:50:31'),(16,33,23,33,'RRP',185.00,'KES','2026-10-30',42,'2026-10-30 03:26:55'),(17,33,23,33,'RSP',145.00,'KES','2026-10-30',42,'2026-10-30 03:27:07'),(18,33,23,33,'RSP',145.00,'KES','2026-10-30',42,'2026-10-30 03:27:20'),(19,1,1,6,'RSP',219.00,'KES','2026-10-31',2,'2026-10-31 15:51:27'),(20,1,1,6,'RRP',248.00,'KES','2026-10-31',2,'2026-10-31 15:51:27'),(21,2,2,2,'RSP',352.00,'KES','2026-10-31',7,'2026-10-31 15:51:27'),(22,2,2,2,'RRP',400.00,'KES','2026-10-31',7,'2026-10-31 15:51:27'),(23,2,3,11,'RSP',114.00,'KES','2027-01-01',7,'2026-10-31 15:51:27'),(24,2,3,11,'RRP',130.00,'KES','2027-01-01',7,'2026-10-31 15:51:27'),(25,2,4,12,'RSP',77.00,'KES','2027-01-01',7,'2026-10-31 15:51:27'),(26,2,4,12,'RRP',88.00,'KES','2027-01-01',7,'2026-10-31 15:51:27'),(27,2,7,13,'RSP',99.00,'KES','2026-11-12',7,'2026-10-31 15:51:27'),(28,2,7,13,'RRP',113.00,'KES','2026-11-12',7,'2026-10-31 15:51:27'),(29,2,9,16,'RSP',356.00,'KES','2028-06-07',7,'2026-10-31 15:51:27'),(30,2,9,16,'RRP',404.00,'KES','2028-06-07',7,'2026-10-31 15:51:27'),(31,26,11,19,'RSP',272.00,'KES','2026-10-31',29,'2026-10-31 15:51:27'),(32,26,11,19,'RRP',309.00,'KES','2026-10-31',29,'2026-10-31 15:51:27'),(33,27,12,20,'RSP',191.00,'KES','2026-10-31',31,'2026-10-31 15:51:27'),(34,27,12,20,'RRP',217.00,'KES','2026-10-31',31,'2026-10-31 15:51:27'),(35,9,13,21,'RSP',204.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(36,9,13,21,'RRP',231.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(37,9,14,22,'RSP',127.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(38,9,14,22,'RRP',144.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(39,9,15,23,'RSP',166.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(40,9,15,23,'RRP',188.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(41,9,16,24,'RSP',121.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(42,9,16,24,'RRP',137.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(43,9,17,25,'RSP',258.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(44,9,17,25,'RRP',293.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(45,9,18,26,'RSP',108.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(46,9,18,26,'RRP',123.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(47,9,19,27,'RSP',119.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(48,9,19,27,'RRP',135.00,'KES','2026-10-31',9,'2026-10-31 15:51:27'),(49,33,24,34,'RSP',275.00,'KES','2026-10-31',42,'2026-10-31 15:51:27'),(50,33,24,34,'RRP',313.00,'KES','2026-10-31',42,'2026-10-31 15:51:27'),(51,2,8,35,'RRP',150.00,'KES','2026-11-02',7,'2026-11-02 07:16:20'),(52,2,8,35,'RSP',120.00,'KES','2026-11-02',7,'2026-11-02 07:16:20'),(54,2,2,2,'RSP',303.45,'KES','2026-11-04',7,'2026-11-04 10:54:49'),(55,2,2,2,'RRP',344.83,'KES','2026-11-04',7,'2026-11-04 10:54:49'),(56,2,2,2,'RSP',345.00,'KES','2026-11-05',7,'2026-11-04 21:43:15'),(57,2,2,2,'RRP',390.00,'KES','2026-11-05',7,'2026-11-04 21:43:15'),(58,2,2,2,'RSP',345.00,'KES','2026-11-05',7,'2026-11-04 21:48:10'),(59,2,2,2,'RRP',390.00,'KES','2026-11-05',7,'2026-11-04 21:48:10'),(60,2,25,37,'RRP',395.00,'KES','2026-11-07',7,'2026-11-07 13:30:27'),(61,2,25,37,'RSP',355.00,'KES','2026-11-07',7,'2026-11-07 13:30:42'),(62,34,26,38,'RRP',70.00,'KES','2026-11-08',43,'2026-11-08 12:58:12'),(63,34,26,38,'RSP',60.00,'KES','2026-11-08',43,'2026-11-08 12:58:26');
/*!40000 ALTER TABLE `product_price_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_prices`
--

DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_prices` (
  `price_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `price_type` enum('COST','SELLING') NOT NULL,
  `price_amount` decimal(14,4) NOT NULL,
  `price_includes_vat` tinyint(1) NOT NULL DEFAULT 1,
  `tax_category` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `source_grn_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`price_id`),
  KEY `organization_id` (`organization_id`),
  KEY `product_id` (`product_id`),
  KEY `product_version_id` (`product_version_id`),
  KEY `idx_product_prices_source_grn` (`source_grn_id`),
  CONSTRAINT `fk_product_prices_source_grn` FOREIGN KEY (`source_grn_id`) REFERENCES `goods_received_notes` (`grn_id`) ON DELETE SET NULL,
  CONSTRAINT `product_prices_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`),
  CONSTRAINT `product_prices_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `product_prices_ibfk_3` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=503 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_prices`
--

LOCK TABLES `product_prices` WRITE;
/*!40000 ALTER TABLE `product_prices` DISABLE KEYS */;
INSERT INTO `product_prices` VALUES (208,1,1,1,'COST',278.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(209,1,1,1,'SELLING',299.6800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(210,3,1,1,'COST',299.6800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(211,3,1,1,'SELLING',316.1600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(212,5,1,1,'COST',316.1600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(213,5,1,1,'SELLING',425.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(214,1,1,6,'COST',182.0000,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(215,1,1,6,'SELLING',198.3800,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(216,3,1,6,'COST',198.3800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(217,3,1,6,'SELLING',209.0900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(218,5,1,6,'COST',209.0900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(219,5,1,6,'SELLING',295.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(220,2,2,2,'COST',284.0000,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(221,2,2,2,'SELLING',305.5800,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(222,3,2,2,'COST',305.5800,1,'STANDARD_RATED','2020-01-01','2026-11-02',NULL,'2026-10-25 10:12:53'),(223,3,2,2,'SELLING',322.6900,1,'STANDARD_RATED','2020-01-01','2026-11-02',NULL,'2026-10-25 10:12:53'),(224,5,2,2,'COST',322.6900,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(225,5,2,2,'SELLING',445.0000,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:53'),(226,2,3,3,'COST',167.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(227,2,3,3,'SELLING',182.8600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(228,3,3,3,'COST',182.8600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(229,3,3,3,'SELLING',192.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(230,5,3,3,'COST',192.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(231,5,3,3,'SELLING',265.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(232,2,3,11,'COST',98.0000,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:53'),(233,2,3,11,'SELLING',103.2900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(234,3,3,11,'COST',103.2900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(235,3,3,11,'SELLING',109.3800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(236,5,3,11,'COST',109.3800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(237,5,3,11,'SELLING',150.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(238,2,4,4,'COST',160.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(239,2,4,4,'SELLING',173.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(240,3,4,4,'COST',173.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(241,3,4,4,'SELLING',183.6700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(242,5,4,4,'COST',183.6700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(243,5,4,4,'SELLING',250.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(244,2,4,12,'COST',99.0000,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:53'),(245,2,4,12,'SELLING',105.5300,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:53'),(246,3,4,12,'COST',105.5300,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(247,3,4,12,'SELLING',111.0200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(248,5,4,12,'COST',111.0200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(249,5,4,12,'SELLING',149.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(250,2,5,5,'COST',161.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:53'),(251,2,5,5,'SELLING',171.4600,1,'STANDARD_RATED','2020-01-01','2026-11-05',NULL,'2026-10-25 10:12:53'),(252,3,5,5,'COST',171.4600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(253,3,5,5,'SELLING',180.7200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(254,5,5,5,'COST',180.7200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(255,5,5,5,'SELLING',245.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:53'),(256,2,6,7,'COST',186.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(257,2,6,7,'SELLING',200.5100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(258,3,6,7,'COST',200.5100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(259,3,6,7,'SELLING',211.9400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(260,5,6,7,'COST',211.9400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(261,5,6,7,'SELLING',299.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(262,2,6,8,'COST',75.0000,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:54'),(263,2,6,8,'SELLING',85.0000,1,'STANDARD_RATED','2020-01-01','2026-10-24',NULL,'2026-10-25 10:12:54'),(264,3,6,8,'COST',85.0000,1,'STANDARD_RATED','2020-01-01','2026-10-25',NULL,'2026-10-25 10:12:54'),(265,3,6,8,'SELLING',89.6800,1,'STANDARD_RATED','2020-01-01','2026-10-25',NULL,'2026-10-25 10:12:54'),(266,5,6,8,'COST',89.6800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(267,5,6,8,'SELLING',125.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(268,2,7,13,'COST',222.0000,1,'STANDARD_RATED','2020-01-01','2026-10-25',NULL,'2026-10-25 10:12:54'),(269,2,7,13,'SELLING',243.3100,1,'STANDARD_RATED','2020-01-01','2026-10-25',NULL,'2026-10-25 10:12:54'),(270,3,7,13,'COST',243.3100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(271,3,7,13,'SELLING',257.6700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(272,5,7,13,'COST',257.6700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(273,5,7,13,'SELLING',360.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(274,2,8,14,'COST',255.0000,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:54'),(275,2,8,14,'SELLING',268.2600,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:54'),(276,3,8,14,'COST',268.2600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(277,3,8,14,'SELLING',283.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(278,5,8,14,'COST',283.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(279,5,8,14,'SELLING',395.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(280,2,9,15,'COST',185.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(281,2,9,15,'SELLING',199.0600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(282,3,9,15,'COST',199.0600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(283,3,9,15,'SELLING',210.6100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(284,5,9,15,'COST',210.6100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(285,5,9,15,'SELLING',289.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(286,2,9,16,'COST',299.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:54'),(287,2,9,16,'SELLING',322.9200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(288,3,9,16,'COST',322.9200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(289,3,9,16,'SELLING',339.0700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(290,5,9,16,'COST',339.0700,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(291,5,9,16,'SELLING',469.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(292,9,13,21,'COST',170.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:54'),(293,9,13,21,'SELLING',184.7900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(294,3,13,21,'COST',184.7900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(295,3,13,21,'SELLING',194.9500,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(296,5,13,21,'COST',194.9500,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(297,5,13,21,'SELLING',270.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(298,9,14,22,'COST',109.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:54'),(299,9,14,22,'SELLING',114.8900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(300,3,14,22,'COST',114.8900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(301,3,14,22,'SELLING',121.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(302,5,14,22,'COST',121.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(303,5,14,22,'SELLING',169.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(304,9,15,23,'COST',138.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:54'),(305,9,15,23,'SELLING',150.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(306,3,15,23,'COST',150.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(307,3,15,23,'SELLING',157.6600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(308,5,15,23,'COST',157.6600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(309,5,15,23,'SELLING',220.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:54'),(310,9,16,24,'COST',101.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(311,9,16,24,'SELLING',109.5800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(312,3,16,24,'COST',109.5800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(313,3,16,24,'SELLING',115.2800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(314,5,16,24,'COST',115.2800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(315,5,16,24,'SELLING',159.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(316,9,17,25,'COST',221.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(317,9,17,25,'SELLING',234.0400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(318,3,17,25,'COST',234.0400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(319,3,17,25,'SELLING',246.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(320,5,17,25,'COST',246.4400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(321,5,17,25,'SELLING',340.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(322,9,18,26,'COST',91.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(323,9,18,26,'SELLING',97.9200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(324,3,18,26,'COST',97.9200,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(325,3,18,26,'SELLING',103.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(326,5,18,26,'COST',103.0100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(327,5,18,26,'SELLING',145.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(328,9,19,27,'COST',102.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(329,9,19,27,'SELLING',107.7100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(330,3,19,27,'COST',107.7100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(331,3,19,27,'SELLING',113.2000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(332,5,19,27,'COST',113.2000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(333,5,19,27,'SELLING',160.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(334,11,10,17,'COST',225.0000,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:55'),(335,11,10,17,'SELLING',240.7500,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:55'),(336,3,10,17,'COST',240.7500,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(337,3,10,17,'SELLING',254.9500,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(338,5,10,17,'COST',254.9500,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(339,5,10,17,'SELLING',359.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(340,26,11,18,'COST',160.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(341,26,11,18,'SELLING',171.3600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(342,3,11,18,'COST',171.3600,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(343,3,11,18,'SELLING',181.3000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(344,5,11,18,'COST',181.3000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(345,5,11,18,'SELLING',245.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(346,26,11,19,'COST',229.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(347,26,11,19,'SELLING',246.6300,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(348,3,11,19,'COST',246.6300,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(349,3,11,19,'SELLING',261.1800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(350,5,11,19,'COST',261.1800,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(351,5,11,19,'SELLING',355.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(352,27,12,20,'COST',161.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(353,27,12,20,'SELLING',172.9100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(354,3,12,20,'COST',172.9100,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(355,3,12,20,'SELLING',182.9400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(356,5,12,20,'COST',182.9400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(357,5,12,20,'SELLING',259.9900,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(358,27,20,28,'COST',54.0000,1,'STANDARD_RATED','2020-01-01','2026-10-30',NULL,'2026-10-25 10:12:55'),(359,27,20,28,'SELLING',58.1000,1,'STANDARD_RATED','2020-01-01','2026-10-27',NULL,'2026-10-25 10:12:55'),(360,3,20,28,'COST',58.1000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(361,3,20,28,'SELLING',61.2400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(362,5,20,28,'COST',61.2400,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(363,5,20,28,'SELLING',85.0000,1,'STANDARD_RATED','2020-01-01',NULL,NULL,'2026-10-25 10:12:55'),(364,2,2,2,'COST',284.0000,1,'STANDARD_RATED','2026-10-25','2026-10-30',NULL,'2026-10-25 15:03:48'),(365,2,2,2,'SELLING',320.0000,1,'STANDARD_RATED','2026-10-25','2026-11-02',NULL,'2026-10-25 15:03:48'),(366,2,6,8,'COST',75.0000,1,'STANDARD_RATED','2026-10-25',NULL,NULL,'2026-10-25 16:44:38'),(367,2,6,8,'SELLING',90.0000,1,'STANDARD_RATED','2026-10-25','2026-10-25',NULL,'2026-10-25 16:44:38'),(368,1,1,6,'COST',182.0000,1,'STANDARD_RATED','2026-10-25','2026-10-30',NULL,'2026-10-25 17:38:58'),(369,1,1,6,'SELLING',198.3800,1,'STANDARD_RATED','2026-10-25',NULL,NULL,'2026-10-25 17:38:58'),(370,5,2,2,'COST',322.6900,1,'STANDARD_RATED','2026-10-25','2026-11-02',NULL,'2026-10-25 17:44:09'),(371,5,2,2,'SELLING',445.0000,1,'STANDARD_RATED','2026-10-25','2026-11-02',NULL,'2026-10-25 17:44:09'),(378,2,6,8,'SELLING',90.0000,1,'STANDARD_RATED','2026-10-26','2026-10-26',NULL,'2026-10-26 05:26:38'),(379,3,6,8,'COST',90.0000,1,'STANDARD_RATED','2026-10-26',NULL,NULL,'2026-10-26 06:10:17'),(380,3,6,8,'SELLING',190.0000,1,'STANDARD_RATED','2026-10-26','2026-10-27',NULL,'2026-10-26 06:10:17'),(381,2,7,13,'COST',70.0000,1,'STANDARD_RATED','2026-10-26','2026-10-30',NULL,'2026-10-26 17:37:50'),(382,2,7,13,'SELLING',90.0000,1,'STANDARD_RATED','2026-10-26',NULL,NULL,'2026-10-26 17:37:50'),(383,2,6,8,'SELLING',91.0000,1,'STANDARD_RATED','2026-10-27',NULL,NULL,'2026-10-27 02:46:05'),(384,3,6,8,'SELLING',120.0000,1,'STANDARD_RATED','2026-10-28',NULL,NULL,'2026-10-28 06:01:50'),(385,11,10,17,'COST',70.0000,1,'STANDARD_RATED','2026-10-28','2026-10-30',NULL,'2026-10-28 11:49:13'),(386,11,10,17,'SELLING',120.0000,1,'STANDARD_RATED','2026-10-28','2026-11-03',NULL,'2026-10-28 11:49:14'),(387,27,20,28,'SELLING',65.0000,1,'STANDARD_RATED','2026-10-28',NULL,NULL,'2026-10-28 11:54:52'),(388,2,4,12,'COST',50.0000,1,'STANDARD_RATED','2026-10-28','2026-10-30',NULL,'2026-10-28 13:51:04'),(389,2,4,12,'SELLING',70.0000,1,'STANDARD_RATED','2026-10-28',NULL,NULL,'2026-10-28 13:51:05'),(390,2,8,14,'COST',70.0000,1,'STANDARD_RATED','2026-10-28','2026-10-30',NULL,'2026-10-28 13:53:34'),(391,2,8,14,'SELLING',90.0000,1,'STANDARD_RATED','2026-10-28',NULL,NULL,'2026-10-28 13:53:34'),(392,2,3,11,'COST',120.0000,1,'STANDARD_RATED','2026-10-28','2026-10-30',NULL,'2026-10-28 14:51:51'),(393,5,6,29,'SELLING',120.0000,1,'STANDARD_RATED','2026-10-29',NULL,NULL,'2026-10-29 11:34:17'),(394,30,21,30,'COST',35.0000,1,'EXEMPT','2026-10-29',NULL,NULL,'2026-10-29 15:06:33'),(395,30,21,30,'SELLING',50.0000,1,'EXEMPT','2026-10-29',NULL,NULL,'2026-10-29 15:06:33'),(396,31,22,32,'COST',250.0000,1,'STANDARD_RATED','2026-10-29','2026-10-30',NULL,'2026-10-29 16:42:12'),(397,31,22,32,'SELLING',275.0000,1,'STANDARD_RATED','2026-10-29',NULL,NULL,'2026-10-29 16:42:13'),(398,33,23,33,'COST',59.0000,1,'STANDARD_RATED','2026-10-29','2026-10-30',NULL,'2026-10-29 19:34:21'),(399,33,23,33,'SELLING',90.0000,1,'STANDARD_RATED','2026-10-29','2026-10-29',NULL,'2026-10-29 19:34:21'),(400,33,23,33,'SELLING',95.0000,1,'STANDARD_RATED','2026-10-30','2026-10-30',NULL,'2026-10-30 03:28:03'),(401,5,23,33,'SELLING',185.0000,1,'STANDARD_RATED','2026-10-30',NULL,NULL,'2026-10-30 03:29:03'),(402,33,24,34,'SELLING',250.0000,1,'STANDARD_RATED','2026-10-23',NULL,NULL,'2026-10-30 06:54:25'),(403,33,24,34,'COST',120.0000,1,'STANDARD_RATED','2026-10-30','2026-10-30',NULL,'2026-10-30 11:47:35'),(404,33,23,33,'SELLING',95.0000,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 03:29:27'),(457,2,5,5,'COST',138.7900,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(458,2,9,16,'COST',257.7600,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(459,9,13,21,'COST',146.5500,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(460,9,14,22,'COST',93.9700,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(461,9,15,23,'COST',118.9700,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(462,9,16,24,'COST',87.0700,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(463,9,17,25,'COST',190.5200,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(464,9,18,26,'COST',78.4500,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(465,9,19,27,'COST',87.9300,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(466,26,11,19,'COST',197.4100,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(467,27,12,20,'COST',138.7900,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(468,27,20,28,'COST',46.5500,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(469,2,2,2,'COST',244.8300,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(470,1,1,6,'COST',156.9000,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(471,2,7,13,'COST',60.3400,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(472,11,10,17,'COST',60.3400,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(473,2,4,12,'COST',43.1000,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(474,2,8,14,'COST',60.3400,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(475,2,3,11,'COST',103.4500,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(476,31,22,32,'COST',215.5200,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(477,33,23,33,'COST',50.8600,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(478,33,24,34,'COST',103.4500,0,'STANDARD_RATED','2026-10-31',NULL,NULL,'2026-10-31 15:59:09'),(479,3,8,35,'COST',268.2600,1,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(480,3,8,35,'SELLING',283.0100,1,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(481,5,8,35,'COST',283.0100,1,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(482,5,8,35,'SELLING',395.0000,1,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(483,2,8,35,'SELLING',90.0000,1,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(484,2,8,35,'COST',60.3400,0,'STANDARD_RATED','2026-11-02',NULL,NULL,'2026-11-02 07:16:20'),(486,2,2,2,'SELLING',360.0000,1,'STANDARD_RATED','2026-11-03','2026-11-03',NULL,'2026-11-03 03:48:46'),(487,3,2,2,'COST',360.0000,1,'STANDARD_RATED','2026-11-03',NULL,NULL,'2026-11-03 03:48:46'),(488,3,2,2,'SELLING',380.1600,1,'STANDARD_RATED','2026-11-03',NULL,NULL,'2026-11-03 03:48:46'),(489,5,2,2,'COST',380.1600,1,'STANDARD_RATED','2026-11-03',NULL,NULL,'2026-11-03 03:48:46'),(490,5,2,2,'SELLING',529.9900,1,'STANDARD_RATED','2026-11-03','2026-11-04',NULL,'2026-11-03 03:48:46'),(491,2,2,2,'SELLING',310.3400,1,'EXEMPT','2026-11-04',NULL,NULL,'2026-11-04 10:54:49'),(492,11,10,17,'SELLING',110.0000,1,'STANDARD_RATED','2026-11-04',NULL,NULL,'2026-11-04 12:45:53'),(493,5,2,2,'SELLING',390.0000,1,'EXEMPT','2026-11-05',NULL,NULL,'2026-11-04 22:00:33'),(494,5,6,36,'SELLING',120.0000,1,'STANDARD_RATED','2026-11-08',NULL,NULL,'2026-11-06 07:16:11'),(495,2,5,5,'SELLING',150.0000,1,'STANDARD_RATED','2026-11-06',NULL,NULL,'2026-11-06 07:22:09'),(497,2,25,37,'SELLING',300.0000,1,'EXEMPT','2026-11-07',NULL,NULL,'2026-11-07 13:29:51'),(498,2,25,37,'COST',250.0000,0,'EXEMPT','2026-11-07',NULL,NULL,'2026-11-07 13:31:43'),(501,34,26,38,'SELLING',55.0000,1,'EXEMPT','2026-11-08',NULL,NULL,'2026-11-08 12:56:48'),(502,34,26,38,'COST',45.0000,0,'EXEMPT','2026-11-08',NULL,NULL,'2026-11-08 13:02:13');
/*!40000 ALTER TABLE `product_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_suppliers`
--

DROP TABLE IF EXISTS `product_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_suppliers` (
  `product_supplier_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `area_id` int(10) unsigned NOT NULL,
  `is_exclusive` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('PENDING','ACTIVE','INACTIVE','DECLINED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_supplier_id`),
  KEY `fk_product_suppliers_supplier` (`supplier_id`),
  KEY `idx_product_suppliers_area_id` (`area_id`),
  KEY `idx_product_suppliers_product_id` (`product_id`),
  KEY `idx_product_supplier_area_period` (`product_id`,`supplier_id`,`area_id`,`status`,`start_date`,`end_date`),
  CONSTRAINT `fk_product_suppliers_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_suppliers_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_suppliers_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_suppliers`
--

LOCK TABLES `product_suppliers` WRITE;
/*!40000 ALTER TABLE `product_suppliers` DISABLE KEYS */;
INSERT INTO `product_suppliers` VALUES (4,3,3,14,0,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 11:36:51'),(5,6,3,29,0,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 11:58:27'),(6,6,3,31,0,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 13:16:13'),(8,6,3,31,0,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 13:30:24'),(9,6,3,1,0,'2026-10-24','2026-10-25','INACTIVE','2026-10-24 14:01:36'),(10,6,3,21,0,'2026-10-24','2026-10-25','INACTIVE','2026-10-24 14:09:51'),(11,2,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:56:55'),(12,3,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:12'),(13,7,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:19'),(14,8,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:25'),(15,4,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:31'),(16,5,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:37'),(17,9,3,1,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 00:57:52'),(18,6,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(19,2,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(20,3,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(21,7,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(22,8,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(23,4,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(24,5,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(25,9,3,16,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:41:45'),(26,6,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(27,2,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(28,3,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(29,7,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(30,8,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(31,4,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(32,5,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(33,9,3,4,0,'2026-10-25','2026-11-01','INACTIVE','2026-10-25 01:42:02'),(34,6,3,1,0,'2026-10-28','2026-11-01','INACTIVE','2026-10-28 14:30:22'),(35,1,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(36,10,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(37,11,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(38,12,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(39,13,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(40,14,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(41,15,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(42,16,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(43,17,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(44,18,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(45,19,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(46,20,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 06:51:12'),(50,22,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 16:33:17'),(51,23,3,1,0,'2026-10-29','2026-11-01','INACTIVE','2026-10-29 19:35:01'),(52,10,3,4,0,'2026-11-01',NULL,'ACTIVE','2026-11-01 14:23:42'),(53,8,3,4,0,'2026-11-01',NULL,'ACTIVE','2026-11-01 14:32:56'),(54,8,3,1,0,'2026-11-01',NULL,'ACTIVE','2026-11-01 14:37:06'),(55,2,3,1,0,'2026-11-03',NULL,'ACTIVE','2026-11-03 03:17:31'),(56,25,3,1,0,'2026-11-07','2026-11-08','ACTIVE','2026-11-07 13:32:27'),(58,5,3,1,0,'2026-11-08',NULL,'ACTIVE','2026-11-08 04:07:02'),(59,8,3,29,0,'2026-11-08',NULL,'ACTIVE','2026-11-08 04:50:40'),(60,26,3,1,0,'2026-11-08',NULL,'PENDING','2026-11-08 13:02:48');
/*!40000 ALTER TABLE `product_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_supply_acceptances`
--

DROP TABLE IF EXISTS `product_supply_acceptances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_supply_acceptances` (
  `acceptance_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_supplier_id` int(10) unsigned NOT NULL,
  `retailer_id` int(10) unsigned NOT NULL,
  `status` enum('ACCEPTED','DECLINED') NOT NULL,
  `decided_by_user_id` int(10) unsigned NOT NULL,
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`acceptance_id`),
  UNIQUE KEY `uq_supply_acceptance` (`product_supplier_id`,`retailer_id`),
  KEY `idx_supply_acceptance_retailer` (`retailer_id`,`status`),
  KEY `fk_supply_acceptance_user` (`decided_by_user_id`),
  CONSTRAINT `fk_supply_acceptance_appointment` FOREIGN KEY (`product_supplier_id`) REFERENCES `product_suppliers` (`product_supplier_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_acceptance_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_acceptance_user` FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_supply_acceptances`
--

LOCK TABLES `product_supply_acceptances` WRITE;
/*!40000 ALTER TABLE `product_supply_acceptances` DISABLE KEYS */;
INSERT INTO `product_supply_acceptances` VALUES (1,54,5,'ACCEPTED',1,'2026-11-01 14:42:01'),(2,55,5,'ACCEPTED',1,'2026-11-03 03:56:24'),(5,59,5,'ACCEPTED',1,'2026-11-10 01:47:44'),(6,58,5,'ACCEPTED',1,'2026-11-10 01:48:03');
/*!40000 ALTER TABLE `product_supply_acceptances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_tax_classifications`
--

DROP TABLE IF EXISTS `product_tax_classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_tax_classifications` (
  `product_tax_classification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_version_id` int(10) unsigned NOT NULL,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `tax_category` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `declared_by` int(10) unsigned DEFAULT NULL,
  `declaration_note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_tax_classification_id`),
  UNIQUE KEY `uq_product_tax_version_start` (`product_version_id`,`effective_from`),
  KEY `idx_product_tax_effective_period` (`product_version_id`,`effective_from`,`effective_to`),
  KEY `idx_product_tax_manufacturer` (`manufacturer_id`),
  KEY `fk_product_tax_declared_by` (`declared_by`),
  CONSTRAINT `fk_product_tax_declared_by` FOREIGN KEY (`declared_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_product_tax_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_tax_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_product_tax_period` CHECK (`effective_to` is null or `effective_to` >= `effective_from`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tax_classifications`
--

LOCK TABLES `product_tax_classifications` WRITE;
/*!40000 ALTER TABLE `product_tax_classifications` DISABLE KEYS */;
INSERT INTO `product_tax_classifications` VALUES (1,8,2,'STANDARD_RATED','2026-10-24',NULL,7,NULL,'2026-10-24 17:28:10'),(2,1,1,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(3,6,1,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(4,2,2,'STANDARD_RATED','2020-01-01','2026-11-03',NULL,NULL,'2026-10-25 08:06:13'),(5,3,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(6,11,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(7,4,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(8,12,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(9,5,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(10,7,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(11,13,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(12,14,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(13,15,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(14,16,2,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(15,21,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(16,22,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(17,23,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(18,24,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(19,25,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(20,26,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(21,27,9,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(22,17,11,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(23,18,26,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(24,19,26,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(25,20,27,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(26,28,27,'STANDARD_RATED','2020-01-01',NULL,NULL,NULL,'2026-10-25 08:06:13'),(33,34,33,'STANDARD_RATED','2026-10-23',NULL,42,NULL,'2026-10-30 06:54:25'),(34,29,2,'STANDARD_RATED','2026-10-28',NULL,NULL,'Backfilled for system testing; confirm the real category','2026-10-30 11:54:16'),(35,30,30,'EXEMPT','2026-10-29',NULL,NULL,'Milk is not charged VAT; declared exempt','2026-10-30 11:54:16'),(36,31,30,'EXEMPT','2026-10-30',NULL,NULL,'Milk is not charged VAT; declared exempt','2026-10-30 11:54:16'),(37,32,31,'STANDARD_RATED','2026-10-29',NULL,NULL,'Backfilled for system testing; confirm the real category','2026-10-30 11:54:16'),(38,33,33,'STANDARD_RATED','2026-10-29',NULL,NULL,'Backfilled for system testing; confirm the real category','2026-10-30 11:54:16'),(41,35,2,'STANDARD_RATED','2026-11-02',NULL,NULL,NULL,'2026-11-02 07:16:20'),(42,2,2,'EXEMPT','2026-11-04',NULL,7,NULL,'2026-11-04 10:54:49'),(43,36,2,'STANDARD_RATED','2026-11-08',NULL,NULL,'Backfilled for system testing; confirm the real category','2026-11-06 07:16:11'),(44,37,2,'EXEMPT','2026-11-07',NULL,7,NULL,'2026-11-07 13:29:51'),(45,38,34,'EXEMPT','2026-11-08',NULL,43,NULL,'2026-11-08 12:56:48');
/*!40000 ALTER TABLE `product_tax_classifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_versions`
--

DROP TABLE IF EXISTS `product_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_versions` (
  `product_version_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `pack_size` varchar(100) DEFAULT NULL,
  `packaging_description` varchar(255) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_version_key` int(10) unsigned GENERATED ALWAYS AS (case when `is_current` = 1 then `product_id` else NULL end) STORED,
  PRIMARY KEY (`product_version_id`),
  UNIQUE KEY `uq_product_version` (`product_id`,`version_number`),
  UNIQUE KEY `uq_product_versions_product_pair` (`product_id`,`product_version_id`),
  UNIQUE KEY `uq_product_barcode` (`barcode`),
  UNIQUE KEY `uq_one_current_version` (`current_version_key`),
  CONSTRAINT `fk_product_versions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_versions`
--

LOCK TABLES `product_versions` WRITE;
/*!40000 ALTER TABLE `product_versions` DISABLE KEYS */;
INSERT INTO `product_versions` VALUES (1,1,1,'2000000000018','50ml','Original 50ml packaging','2026-01-01','2026-07-31',0,'2026-10-04 12:37:25',NULL),(2,2,1,'2000000000025','400g','Original packaging','2026-01-01',NULL,1,'2026-10-04 12:37:25',2),(3,3,1,'2000000000032','500g','Original packaging','2026-01-01','2026-12-31',0,'2026-10-04 12:37:25',NULL),(4,4,1,'2000000000049','Pack','Original packaging','2026-01-01','2026-12-31',0,'2026-10-04 12:37:25',NULL),(5,5,1,'2000000000056','Pack','Original packaging','2026-01-01',NULL,1,'2026-10-04 12:37:25',5),(6,1,2,'9876543210123','45ml','Updated 45ml packaging','2026-08-01',NULL,1,'2026-10-04 12:49:50',1),(7,6,1,'6001234567890','400g','Standard 400g bread packaging','2026-10-05','2026-10-09',0,'2026-10-05 02:36:39',NULL),(8,6,2,'6001234567891','450g','Updated 450g bread packaging','2026-10-10','2026-10-27',0,'2026-10-05 02:55:02',NULL),(11,3,2,'60009778786876',NULL,'60g','2027-01-01',NULL,1,'2026-10-15 01:36:28',3),(12,4,2,'99000000001',NULL,NULL,'2027-01-01',NULL,1,'2026-10-15 14:19:12',4),(13,7,1,'78589494955998','700g','loaf bread','2026-11-12',NULL,1,'2026-10-15 16:31:11',7),(14,8,1,'61620034567834','400g','a container pack sealed','2026-10-17','2026-11-01',0,'2026-10-16 02:52:07',NULL),(15,9,1,'61620094556564','350g','packed in a clear plastic wrapping','2026-10-17','2028-06-06',0,'2026-10-16 09:40:05',NULL),(16,9,2,'65656565656565','400g','packed in a clear plastic wrapping','2028-06-07',NULL,1,'2026-10-16 09:46:26',9),(17,10,1,'61616161616166','500ml','bottled','2026-10-19',NULL,1,'2026-10-19 11:39:17',10),(18,11,1,'65654664757647','1.5kg','pholynolylone bag','2026-10-20','2026-10-29',0,'2026-10-20 06:40:30',NULL),(19,11,2,'67672345345626',NULL,NULL,'2026-10-30',NULL,1,'2026-10-20 06:41:58',11),(20,12,1,'68686868668868','15g','In a wrapping','2026-10-20',NULL,1,'2026-10-20 08:10:22',12),(21,13,1,'60673847276346','5kg','Packed in a polynoylone bag','2026-10-21',NULL,1,'2026-10-20 13:10:27',13),(22,14,1,'67554974887859','1l','bottled','2026-10-21',NULL,1,'2026-10-21 02:10:16',14),(23,15,1,'64646464535532','1l','bottled','2026-10-21',NULL,1,'2026-10-21 02:12:56',15),(24,16,1,'68543546453433','1kg','nylone bag','2026-10-21',NULL,1,'2026-10-21 02:22:56',16),(25,17,1,'65748359335343','single','clear plastic casing','2026-10-21',NULL,1,'2026-10-21 02:26:18',17),(26,18,1,'69475823452323','3 pack','packed in a plastic casing','2026-10-22',NULL,1,'2026-10-21 02:29:32',18),(27,19,1,'68454536456374','4 pack','packed','2026-10-21',NULL,1,'2026-10-21 02:33:04',19),(28,20,1,'64537893545322','80g','purple wrapping','2026-10-21',NULL,1,'2026-10-21 02:35:25',20),(29,6,3,'60000567383955','1000g','polythene bag','2026-10-28','2026-11-07',0,'2026-10-28 17:12:45',NULL),(30,21,1,'61623456353627','500ml','24/ct','2026-10-29','2026-10-29',0,'2026-10-29 15:04:57',NULL),(31,21,2,'67584947590687','450ml','24/ct','2026-10-30',NULL,1,'2026-10-29 15:12:59',21),(32,22,1,'20010000000005','1Litre','plastic bottle containing edible cooking oil with a screw cap','2026-10-29',NULL,1,'2026-10-29 16:25:25',22),(33,23,1,'68936476353748','85g','clear wrapper','2026-10-29',NULL,1,'2026-10-29 19:33:10',23),(34,24,1,'65748478483993','120g','Tyrne cassing','2026-10-23',NULL,1,'2026-10-30 06:54:25',24),(35,8,2,'65748658465394','90g',NULL,'2026-11-02',NULL,1,'2026-11-02 07:16:18',8),(36,6,4,'67840958048503','85g',NULL,'2026-11-08',NULL,1,'2026-11-06 07:16:07',6),(37,25,1,'67568399903748','250g','clear plastic wrapping','2026-11-07',NULL,1,'2026-11-07 13:29:51',25),(38,26,1,'67498596796959','500ml','tetrafino pack','2026-11-08',NULL,1,'2026-11-08 12:56:48',26);
/*!40000 ALTER TABLE `product_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(10) unsigned NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `availability_status` enum('COMING_SOON','AVAILABLE') NOT NULL DEFAULT 'AVAILABLE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `fk_products_manufacturer` (`manufacturer_id`),
  CONSTRAINT `fk_products_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Whitedent','Personal Care','Whitedent product supplied through the supply chain','uploads/products/4e51ab8275d1ffaa1b78a8c70259cb7c.jpg','ACTIVE','AVAILABLE','2026-10-04 12:34:32'),(2,2,'Heart Deli Bread','Bakery','Heart Deli bread product',NULL,'ACTIVE','AVAILABLE','2026-10-04 12:34:32'),(3,2,'Madeira Cake','Bakery','Madeira cake product','uploads/products/c140646741bf1606903641ee41ee974f.jpg','ACTIVE','AVAILABLE','2026-10-04 12:34:32'),(4,2,'mill mini short cake','Bakery','Cupcake product','uploads/products/f98c7e470a77c8cbaa7859302f991c9f.png','ACTIVE','AVAILABLE','2026-10-04 12:34:32'),(5,2,'Muffins','Bakery','Muffin product',NULL,'ACTIVE','AVAILABLE','2026-10-04 12:34:32'),(6,2,'Demo Bread','Bakery','Demo product created through the supply chain system',NULL,'ACTIVE','AVAILABLE','2026-10-05 02:36:39'),(7,2,'Mill bakers multi grain bread','Bakery','we will be having a new type of bread coming up...you can start to preorder','uploads/products/1b821c5b67a8c2bf4aef02e8efae1475.jpg','ACTIVE','COMING_SOON','2026-10-15 16:31:11'),(8,2,'Mill cookies','Snacks','Indulge in the classic, homemade taste of Mill Bakers\' Chunky Chocolate Chip & Oatmeal Cookies. Baked with love and packed oven-fresh, these hearty treats combine the satisfying chew of wholesome oats with generous chunks of rich chocolate. Safely sealed in a clear, resealable 400g (14.1 oz) container to lock in that fresh-from-the-bakery texture, they are the perfect ready-to-share snack for any occasion.','uploads/products/9c0ad86420515dc3fdc1044ad9e9812a.jpg','ACTIVE','AVAILABLE','2026-10-16 02:52:07'),(9,2,'Standard madeira','Bakery','something new crafted from the streets of madeira','uploads/products/1a75634830ad50758d942a332f797729.jpg','ACTIVE','AVAILABLE','2026-10-16 09:40:05'),(10,11,'Quencher lime','Juice','Refresh lime made from real lemon lime juice from 2mountains of kilimanjaro','uploads/products/501a3c9caa290d9a789411a10d1596fe.jpg','ACTIVE','AVAILABLE','2026-10-19 11:39:17'),(11,26,'Omo washing powder','Laundry care','The new refreshing omo washing powder 1kg with everlasting fragrance order now','uploads/products/49ef945443776c867dae225e36634f7e.jpg','ACTIVE','AVAILABLE','2026-10-20 06:40:30'),(12,27,'Mint chocolate','Sweets','Cadbury mint chocolate created by the finest and produced by the best','uploads/products/4498c70723c83ca667449eed93d108b1.jpg','ACTIVE','AVAILABLE','2026-10-20 08:10:22'),(13,9,'Ariel lavender 5kg','Laundry care','The new aeriel lavender 5kg  made with real lavender for lasting fragrance','uploads/products/3c7fdeacf94f9ba663676f01a4032b0a.jpg','ACTIVE','AVAILABLE','2026-10-20 13:10:27'),(14,9,'Aeriel stawberry fabric softener 1l','laundry care',NULL,'uploads/products/0b78712d2ab9091c8f0b8f3a9c524264.jpg','ACTIVE','AVAILABLE','2026-10-21 02:10:16'),(15,9,'Aerile vanilla fabric softener 1l','Laundry care',NULL,'uploads/products/28c45c5f23dd4af405dc65be6b297994.jpg','ACTIVE','AVAILABLE','2026-10-21 02:12:56'),(16,9,'Aeriel washing powder 1kg','laundry care',NULL,'uploads/products/87679461342b23bc72d37090f10e83b6.png','ACTIVE','AVAILABLE','2026-10-21 02:22:56'),(17,9,'Oral b cross toothbrush','Personal care',NULL,'uploads/products/7e8319e86d871d850ae2100b10c0218a.png','ACTIVE','AVAILABLE','2026-10-21 02:26:18'),(18,9,'gillete razor pack','Personal care',NULL,'uploads/products/e060d0413977ab398888168dc0209637.png','ACTIVE','AVAILABLE','2026-10-21 02:29:32'),(19,9,'pampers baby pants','Diapers',NULL,'uploads/products/c18864c2787c9e530d29adf3746b685b.png','ACTIVE','AVAILABLE','2026-10-21 02:33:04'),(20,27,'Cadburry classic','sweets',NULL,'uploads/products/2a34713a10eb9729a52cdb77186e0d34.png','ACTIVE','AVAILABLE','2026-10-21 02:35:25'),(21,30,'brookside whole milk','milk',NULL,'uploads/products/70e82ba8ad00f5049bb013248992bc01.jpg','ACTIVE','AVAILABLE','2026-10-29 15:04:57'),(22,31,'Fresh fri','cooking oil',NULL,'uploads/products/ebcec4716d68cdb7738701e1f2c7285f.jpg','ACTIVE','AVAILABLE','2026-10-29 16:25:25'),(23,33,'Digestive biscuits','snacks',NULL,NULL,'ACTIVE','AVAILABLE','2026-10-29 19:33:10'),(24,33,'Manji Digestive','snacks',NULL,NULL,'ACTIVE','AVAILABLE','2026-10-30 06:54:25'),(25,2,'High Madeira','Cakes',NULL,'uploads/products/dc8b40c6c3a25d7cbc15368dab2f124e.jpg','ACTIVE','AVAILABLE','2026-11-07 13:29:51'),(26,34,'Gold crown','milk',NULL,NULL,'ACTIVE','AVAILABLE','2026-11-08 12:56:48');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_branches`
--

DROP TABLE IF EXISTS `promotion_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_branches` (
  `promotion_branch_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `status` enum('PENDING','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`promotion_branch_id`),
  UNIQUE KEY `uq_promotion_branch` (`promotion_id`,`branch_id`),
  KEY `idx_promotion_branches_promotion` (`promotion_id`),
  KEY `idx_promotion_branches_branch` (`branch_id`),
  CONSTRAINT `fk_promotion_branches_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_branches_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_branches`
--

LOCK TABLES `promotion_branches` WRITE;
/*!40000 ALTER TABLE `promotion_branches` DISABLE KEYS */;
INSERT INTO `promotion_branches` VALUES (1,5,7,'COMPLETED','2026-10-28 04:42:47','2026-11-07 11:38:07'),(2,7,7,'COMPLETED','2026-10-28 14:44:31','2026-11-11 09:29:53'),(3,7,36,'COMPLETED','2026-10-28 14:44:31','2026-11-11 09:29:55'),(4,7,26,'COMPLETED','2026-10-28 14:44:31','2026-11-11 09:29:56'),(5,8,7,'COMPLETED','2026-10-29 19:54:56','2026-11-07 11:37:41'),(6,8,36,'COMPLETED','2026-10-29 19:54:56','2026-11-07 11:37:44'),(7,8,26,'COMPLETED','2026-10-29 19:54:56','2026-11-07 11:37:48');
/*!40000 ALTER TABLE `promotion_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_participants`
--

DROP TABLE IF EXISTS `promotion_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_participants` (
  `promotion_participant_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `status` enum('PENDING','ACCEPTED','DECLINED','ACTIVE','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `joined_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promotion_participant_id`),
  UNIQUE KEY `uq_promotion_participant` (`promotion_id`,`organization_id`),
  KEY `fk_promotion_participants_organization` (`organization_id`),
  CONSTRAINT `fk_promotion_participants_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_participants_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_participants`
--

LOCK TABLES `promotion_participants` WRITE;
/*!40000 ALTER TABLE `promotion_participants` DISABLE KEYS */;
INSERT INTO `promotion_participants` VALUES (1,1,5,'ACCEPTED','2026-09-01 09:00:00','2026-10-04 13:06:49'),(3,2,29,'PENDING',NULL,'2026-10-27 04:33:02'),(4,3,29,'PENDING',NULL,'2026-10-27 04:33:02'),(5,4,29,'PENDING',NULL,'2026-10-27 04:33:02'),(6,5,5,'COMPLETED','2026-10-28 07:42:47','2026-10-28 03:48:01'),(7,6,29,'PENDING',NULL,'2026-10-28 14:36:38'),(8,6,5,'PENDING',NULL,'2026-10-28 14:36:38'),(9,7,29,'PENDING',NULL,'2026-10-28 14:43:15'),(10,7,5,'COMPLETED','2026-10-28 17:44:31','2026-10-28 14:43:15'),(11,8,5,'COMPLETED','2026-10-29 22:54:56','2026-10-29 19:53:41'),(12,8,29,'PENDING',NULL,'2026-10-29 19:53:41');
/*!40000 ALTER TABLE `promotion_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_products`
--

DROP TABLE IF EXISTS `promotion_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_products` (
  `promotion_product_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned DEFAULT NULL,
  `normal_price` decimal(12,2) NOT NULL,
  `rrp_snapshot` decimal(12,2) DEFAULT NULL,
  `rrp_recommendation_id` int(10) unsigned DEFAULT NULL,
  `promotion_price` decimal(12,2) NOT NULL,
  `compensation_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promotion_product_id`),
  UNIQUE KEY `uq_promotion_product` (`promotion_id`,`product_id`),
  KEY `fk_promotion_products_product` (`product_id`),
  KEY `idx_promotion_products_version` (`product_version_id`),
  KEY `idx_promotion_products_recommendation` (`rrp_recommendation_id`),
  CONSTRAINT `fk_promotion_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_products_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_products_rrp_recommendation` FOREIGN KEY (`rrp_recommendation_id`) REFERENCES `product_price_recommendations` (`recommendation_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_products_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_products`
--

LOCK TABLES `promotion_products` WRITE;
/*!40000 ALTER TABLE `promotion_products` DISABLE KEYS */;
INSERT INTO `promotion_products` VALUES (1,1,1,NULL,150.00,NULL,NULL,125.00,20.00,'2026-10-04 13:06:27'),(3,2,6,NULL,100.00,NULL,NULL,75.00,10.00,'2026-10-27 04:33:02'),(4,3,6,NULL,100.00,NULL,NULL,80.00,15.00,'2026-10-27 04:33:02'),(5,4,6,NULL,100.00,NULL,NULL,80.00,15.00,'2026-10-27 04:33:02'),(6,5,6,8,150.00,150.00,1,130.00,20.00,'2026-10-28 03:48:01'),(7,6,6,8,150.00,150.00,1,100.00,50.00,'2026-10-28 14:36:38'),(8,6,8,14,150.00,150.00,8,99.00,51.00,'2026-10-28 14:36:38'),(9,7,8,14,150.00,150.00,8,99.00,51.00,'2026-10-28 14:43:15'),(10,8,5,5,250.00,250.00,14,199.00,51.00,'2026-10-29 19:53:41');
/*!40000 ALTER TABLE `promotion_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_sales`
--

DROP TABLE IF EXISTS `promotion_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_sales` (
  `promotion_sale_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` int(10) unsigned NOT NULL,
  `promotion_product_id` int(10) unsigned NOT NULL,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `sale_date` date NOT NULL,
  `units_sold` int(10) unsigned NOT NULL,
  `recorded_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promotion_sale_id`),
  KEY `fk_promotion_sales_promotion` (`promotion_id`),
  KEY `fk_promotion_sales_product` (`promotion_product_id`),
  KEY `fk_promotion_sales_retailer` (`retailer_id`),
  KEY `fk_promotion_sales_user` (`recorded_by`),
  KEY `idx_promotion_sales_branch` (`branch_id`),
  CONSTRAINT `fk_promotion_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_sales_product` FOREIGN KEY (`promotion_product_id`) REFERENCES `promotion_products` (`promotion_product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_sales_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_sales_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_sales_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_sales`
--

LOCK TABLES `promotion_sales` WRITE;
/*!40000 ALTER TABLE `promotion_sales` DISABLE KEYS */;
INSERT INTO `promotion_sales` VALUES (1,1,1,5,NULL,'2026-09-15',85,3,'2026-10-04 13:07:08');
/*!40000 ALTER TABLE `promotion_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `promotion_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `promotion_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('DRAFT','SUBMITTED','APPROVED','SCHEDULED','ACTIVE','EXPIRED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promotion_id`),
  KEY `fk_promotions_organization` (`organization_id`),
  KEY `fk_promotions_user` (`created_by`),
  KEY `fk_promotions_approved_by` (`approved_by`),
  CONSTRAINT `fk_promotions_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_promotions_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,1,'Whitedent September Promotion','Promotional campaign for Whitedent with retailer compensation based on eligible units sold.','2026-09-01','2026-09-30','ACTIVE',2,NULL,NULL,'2026-10-04 13:06:08'),(2,2,'TST-20261027073239-A','Automated test','2027-08-23','2027-09-02','CANCELLED',7,7,'2026-10-27 07:33:02','2026-10-27 04:33:01'),(3,2,'TST-20261027073239-B','Automated test','2027-08-28','2027-09-07','CANCELLED',7,NULL,NULL,'2026-10-27 04:33:02'),(4,2,'TST-20261027073239-C','Automated test','2026-10-26','2026-10-26','CANCELLED',7,NULL,NULL,'2026-10-27 04:33:02'),(5,2,'Testing promotional campaings','this is a test promotion campaign','2026-10-28','2026-11-01','APPROVED',7,7,'2026-10-28 06:48:10','2026-10-28 03:48:01'),(6,2,'Back from school','Back from school promotion campaign to help make more sales during this holiday times','2026-10-28','2026-11-01','CANCELLED',7,NULL,NULL,'2026-10-28 14:36:38'),(7,2,'Holiday Times','Thank you for your continued support you have accorded us throughout the years .As we gear up for this back from school period, we have planned to run some offers on the following products. Please take notes on the promotion dates.','2026-10-29','2026-11-07','APPROVED',7,7,'2026-10-28 17:43:26','2026-10-28 14:43:15'),(8,2,'back to school','back to school campain as from 29/10/2026 to 5/10/2026','2026-10-29','2026-11-05','APPROVED',7,7,'2026-10-29 22:53:52','2026-10-29 19:53:41');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `realtime_outbox`
--

DROP TABLE IF EXISTS `realtime_outbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realtime_outbox` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(80) NOT NULL,
  `recipient_organization_id` int(10) unsigned NOT NULL,
  `payload` longtext NOT NULL,
  `status` enum('PENDING','PROCESSING','AWAITING_ACK','DISPATCHED','FAILED') NOT NULL DEFAULT 'PENDING',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `worker_id` char(36) DEFAULT NULL,
  `locked_at` datetime(6) DEFAULT NULL,
  `dispatch_token` char(36) DEFAULT NULL,
  `acknowledgement_deadline` datetime(6) DEFAULT NULL,
  `available_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `last_error` varchar(1000) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `delivered_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `idx_realtime_outbox_recipient` (`recipient_organization_id`,`event_id`),
  KEY `idx_realtime_outbox_pending` (`status`,`available_at`,`event_id`),
  KEY `idx_realtime_outbox_lease` (`status`,`locked_at`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `realtime_outbox`
--

LOCK TABLES `realtime_outbox` WRITE;
/*!40000 ALTER TABLE `realtime_outbox` DISABLE KEYS */;
INSERT INTO `realtime_outbox` VALUES (1,'notification.created',5,'{\"notification_id\":163}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.922391',NULL,'2026-11-05 05:47:34.919779','2026-11-05 08:06:52.063983'),(2,'notification.created',3,'{\"notification_id\":164}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.926267',NULL,'2026-11-05 05:48:24.373820','2026-11-05 08:06:52.068565'),(3,'notification.created',5,'{\"notification_id\":165}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.929874',NULL,'2026-11-05 05:55:13.876098','2026-11-05 08:06:52.076727'),(4,'notification.created',3,'{\"notification_id\":166}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.934226',NULL,'2026-11-05 05:55:32.665025','2026-11-05 08:06:52.095295'),(5,'notification.created',5,'{\"notification_id\":167}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.939970',NULL,'2026-11-05 06:24:08.752325','2026-11-05 08:06:52.083967'),(6,'notification.created',3,'{\"notification_id\":168}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.943542',NULL,'2026-11-05 06:24:29.304837','2026-11-05 08:06:52.090774'),(7,'notification.created',3,'{\"notification_id\":169}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.947761',NULL,'2026-11-05 06:35:39.533907','2026-11-05 08:06:52.097684'),(8,'notification.created',5,'{\"notification_id\":170}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.952240',NULL,'2026-11-05 06:36:25.624972','2026-11-05 08:06:52.105188'),(9,'notification.created',5,'{\"notification_id\":171}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:06:46.958991',NULL,'2026-11-05 07:52:29.378695','2026-11-05 08:06:52.110523'),(10,'notification.created',5,'{\"notification_id\":172}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:08:46.839520',NULL,'2026-11-05 08:08:46.839520','2026-11-05 08:08:46.875823'),(11,'notification.created',3,'{\"notification_id\":173}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-05 08:09:01.457945',NULL,'2026-11-05 08:09:01.457945','2026-11-05 08:09:01.486116'),(12,'notification.created',2,'{\"notification_id\":174}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:10:09.990635',NULL,'2026-11-07 14:37:42.013767','2026-11-07 16:10:15.056820'),(13,'notification.created',2,'{\"notification_id\":175}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:10:09.993621',NULL,'2026-11-07 14:37:44.463009','2026-11-07 16:10:15.056336'),(14,'notification.created',2,'{\"notification_id\":176}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:10:09.996826',NULL,'2026-11-07 14:37:48.133944','2026-11-07 16:10:15.056492'),(15,'notification.created',2,'{\"notification_id\":177}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:10:10.000423',NULL,'2026-11-07 14:38:07.022222','2026-11-07 16:10:15.056144'),(16,'notification.created',5,'{\"notification_id\":178}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:30:27.099265',NULL,'2026-11-07 16:30:27.099265','2026-11-07 16:30:27.145550'),(17,'notification.created',29,'{\"notification_id\":179}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.071041','Recipient organization has no available WebSocket connections.','2026-11-07 16:30:27.101155',NULL),(18,'notification.created',3,'{\"notification_id\":180}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:30:42.072830',NULL,'2026-11-07 16:30:42.072830','2026-11-07 16:30:42.132347'),(19,'notification.created',2,'{\"notification_id\":181}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:32:27.149593',NULL,'2026-11-07 16:32:27.149593','2026-11-07 16:32:27.231835'),(20,'notification.created',3,'{\"notification_id\":182}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:32:27.151383',NULL,'2026-11-07 16:32:27.151383','2026-11-07 16:32:27.202411'),(21,'notification.created',5,'{\"notification_id\":183}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-07 16:32:27.156501',NULL,'2026-11-07 16:32:27.156501','2026-11-07 16:32:27.199791'),(22,'notification.created',2,'{\"notification_id\":184}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-10 17:24:54.044099',NULL,'2026-11-08 07:07:02.181542','2026-11-10 17:24:59.082600'),(23,'notification.created',3,'{\"notification_id\":185}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.075032','Recipient organization has no available WebSocket connections.','2026-11-08 07:07:02.183932',NULL),(24,'notification.created',5,'{\"notification_id\":186}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-09 09:58:31.151899',NULL,'2026-11-08 07:07:02.191181','2026-11-09 09:58:36.209623'),(25,'notification.created',2,'{\"notification_id\":187}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-10 17:24:59.044369',NULL,'2026-11-08 07:50:40.748738','2026-11-10 17:24:59.093027'),(26,'notification.created',3,'{\"notification_id\":188}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.078178','Recipient organization has no available WebSocket connections.','2026-11-08 07:50:40.756873',NULL),(27,'notification.created',5,'{\"notification_id\":189}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-09 09:58:31.163318',NULL,'2026-11-08 07:52:17.811174','2026-11-09 09:58:36.213019'),(28,'notification.created',2,'{\"notification_id\":190}','DISPATCHED',1,NULL,NULL,NULL,NULL,'2026-11-10 17:24:59.055385',NULL,'2026-11-08 07:52:17.813890','2026-11-10 17:25:04.153547'),(29,'notification.created',34,'{\"notification_id\":191}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.081453','Recipient organization has no available WebSocket connections.','2026-11-08 16:02:48.704792',NULL),(30,'notification.created',3,'{\"notification_id\":192}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.085108','Recipient organization has no available WebSocket connections.','2026-11-08 16:02:48.718595',NULL),(31,'notification.created',2,'{\"notification_id\":193}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.088779','Recipient organization has no available WebSocket connections.','2026-11-11 12:29:53.914466',NULL),(32,'notification.created',2,'{\"notification_id\":194}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.092582','Recipient organization has no available WebSocket connections.','2026-11-11 12:29:55.426772',NULL),(33,'notification.created',2,'{\"notification_id\":195}','PENDING',0,NULL,NULL,NULL,NULL,'2026-11-12 02:55:08.095489','Recipient organization has no available WebSocket connections.','2026-11-11 12:29:56.769396',NULL);
/*!40000 ALTER TABLE `realtime_outbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer_branch_prices`
--

DROP TABLE IF EXISTS `retailer_branch_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_branch_prices` (
  `retailer_branch_price_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `selling_price` decimal(12,2) NOT NULL,
  `price_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') NOT NULL DEFAULT 'VAT_INCLUSIVE',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`retailer_branch_price_id`),
  UNIQUE KEY `uq_retailer_branch_price_start` (`retailer_id`,`branch_id`,`product_id`,`product_version_id`,`effective_from`),
  KEY `idx_retailer_branch_price_period` (`retailer_id`,`branch_id`,`product_id`,`product_version_id`,`status`,`effective_from`,`effective_to`),
  KEY `fk_retailer_branch_price_product_version` (`product_id`,`product_version_id`),
  KEY `fk_retailer_branch_price_created_by` (`created_by`),
  CONSTRAINT `fk_retailer_branch_price_branch` FOREIGN KEY (`retailer_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_branch_price_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_branch_price_product_version` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product_versions` (`product_id`, `product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_retailer_branch_price_amount` CHECK (`selling_price` >= 0),
  CONSTRAINT `chk_retailer_branch_price_period` CHECK (`effective_to` is null or `effective_to` >= `effective_from`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer_branch_prices`
--

LOCK TABLES `retailer_branch_prices` WRITE;
/*!40000 ALTER TABLE `retailer_branch_prices` DISABLE KEYS */;
INSERT INTO `retailer_branch_prices` VALUES (1,5,7,6,8,200.00,'VAT_INCLUSIVE','2026-10-24','2026-10-31','ACTIVE',1,'2026-10-24 17:28:45','2026-10-25 06:14:44'),(2,5,7,14,22,1500.00,'VAT_INCLUSIVE','2026-10-24','2026-10-25','ACTIVE',1,'2026-10-24 19:12:57','2026-10-25 06:14:05'),(3,5,7,16,24,400.00,'VAT_INCLUSIVE','2026-10-24','2026-10-31','ACTIVE',1,'2026-10-24 19:13:19','2026-10-25 06:14:29'),(4,5,7,15,23,1499.87,'VAT_INCLUSIVE','2026-10-24','2026-10-31','ACTIVE',1,'2026-10-24 19:13:35','2026-10-25 06:14:33'),(5,5,7,13,21,2250.00,'VAT_INCLUSIVE','2026-10-24','2026-10-31','ACTIVE',1,'2026-10-24 19:13:51','2026-10-25 06:14:38'),(6,5,7,11,18,550.00,'VAT_INCLUSIVE','2026-10-25',NULL,'ACTIVE',1,'2026-10-25 06:17:02','2026-10-25 06:17:02');
/*!40000 ALTER TABLE `retailer_branch_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer_products`
--

DROP TABLE IF EXISTS `retailer_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_products` (
  `retailer_product_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`retailer_product_id`),
  UNIQUE KEY `uq_retailer_product_version` (`retailer_id`,`product_id`,`product_version_id`),
  KEY `fk_retailer_products_product` (`product_id`),
  KEY `fk_retailer_products_version` (`product_version_id`),
  CONSTRAINT `fk_retailer_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_products_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_products_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer_products`
--

LOCK TABLES `retailer_products` WRITE;
/*!40000 ALTER TABLE `retailer_products` DISABLE KEYS */;
INSERT INTO `retailer_products` VALUES (1,5,1,1,150.00,'INACTIVE','2026-10-04 12:42:20'),(2,5,1,2,150.00,'ACTIVE','2026-10-04 12:58:40');
/*!40000 ALTER TABLE `retailer_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer_promotion_items`
--

DROP TABLE IF EXISTS `retailer_promotion_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_promotion_items` (
  `retailer_promotion_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_promotion_id` int(10) unsigned NOT NULL,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `regular_price_snapshot` decimal(12,2) NOT NULL,
  `promotion_price` decimal(12,2) NOT NULL,
  `price_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') NOT NULL DEFAULT 'VAT_INCLUSIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`retailer_promotion_item_id`),
  UNIQUE KEY `uq_retailer_promotion_branch_product` (`retailer_promotion_id`,`branch_id`,`product_version_id`),
  KEY `idx_retailer_promotion_items_owner_branch` (`retailer_id`,`branch_id`),
  KEY `fk_retailer_promotion_item_owner` (`retailer_promotion_id`,`retailer_id`),
  KEY `fk_retailer_promotion_item_version` (`product_id`,`product_version_id`),
  CONSTRAINT `fk_retailer_promotion_item_branch` FOREIGN KEY (`retailer_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_promotion_item_owner` FOREIGN KEY (`retailer_promotion_id`, `retailer_id`) REFERENCES `retailer_promotions` (`retailer_promotion_id`, `retailer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_promotion_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_promotion_item_version` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product_versions` (`product_id`, `product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_retailer_promotion_regular_price` CHECK (`regular_price_snapshot` > 0),
  CONSTRAINT `chk_retailer_promotion_sale_price` CHECK (`promotion_price` > 0 and `promotion_price` < `regular_price_snapshot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer_promotion_items`
--

LOCK TABLES `retailer_promotion_items` WRITE;
/*!40000 ALTER TABLE `retailer_promotion_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `retailer_promotion_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer_promotions`
--

DROP TABLE IF EXISTS `retailer_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_promotions` (
  `retailer_promotion_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `promotion_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('DRAFT','SCHEDULED','ACTIVE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`retailer_promotion_id`),
  UNIQUE KEY `uq_retailer_promotion_owner` (`retailer_promotion_id`,`retailer_id`),
  KEY `idx_retailer_promotion_owner` (`retailer_id`,`status`,`created_at`),
  KEY `fk_retailer_promotion_creator` (`created_by`),
  CONSTRAINT `fk_retailer_promotion_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_promotion_owner` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_retailer_promotion_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer_promotions`
--

LOCK TABLES `retailer_promotions` WRITE;
/*!40000 ALTER TABLE `retailer_promotions` DISABLE KEYS */;
/*!40000 ALTER TABLE `retailer_promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retailer_stock`
--

DROP TABLE IF EXISTS `retailer_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_stock` (
  `retailer_stock_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `branch_scope_id` int(10) unsigned GENERATED ALWAYS AS (ifnull(`branch_id`,0)) STORED,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `quantity_on_hand` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 0,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`retailer_stock_id`),
  UNIQUE KEY `uq_retailer_stock_scope` (`retailer_id`,`branch_scope_id`,`product_id`,`product_version_id`),
  KEY `fk_retailer_stock_product` (`product_id`),
  KEY `fk_retailer_stock_version` (`product_version_id`),
  KEY `idx_retailer_stock_branch` (`branch_id`),
  CONSTRAINT `fk_retailer_stock_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retailer_stock`
--

LOCK TABLES `retailer_stock` WRITE;
/*!40000 ALTER TABLE `retailer_stock` DISABLE KEYS */;
INSERT INTO `retailer_stock` VALUES (1,5,NULL,0,1,2,18,25,'2026-10-04 16:15:56'),(2,5,7,7,6,8,-5,20,'2026-10-25 11:00:08'),(4,5,7,7,15,23,0,10,'2026-10-29 12:43:10'),(5,5,7,7,20,28,0,10,'2026-11-05 16:28:45'),(7,5,7,7,10,17,0,12,'2026-10-29 15:32:00'),(8,5,7,7,11,18,8,120,'2026-10-25 09:17:49'),(9,5,7,7,5,5,6,6,'2026-10-24 21:57:35'),(10,5,7,7,19,27,-2,5,'2026-11-07 16:50:48'),(11,5,7,7,1,1,10,20,'2026-10-29 15:32:20'),(12,5,7,7,1,6,0,20,'2026-11-05 16:29:26'),(13,5,7,7,2,2,0,20,'2026-11-05 16:30:00'),(14,5,7,7,3,3,100,20,'2026-10-25 11:13:24'),(15,5,7,7,3,11,-1,20,'2026-11-05 16:29:08'),(16,5,7,7,4,4,100,20,'2026-10-25 11:13:24'),(17,5,7,7,4,12,100,20,'2026-10-25 11:13:24'),(18,5,7,7,6,7,-1,20,'2026-10-29 15:32:54'),(19,5,7,7,7,13,1,20,'2026-10-29 15:33:20'),(20,5,7,7,8,14,5,20,'2026-11-05 16:29:51'),(21,5,7,7,9,15,10,20,'2026-10-29 15:32:35'),(22,5,7,7,9,16,0,20,'2026-10-29 17:12:19'),(23,5,7,7,11,19,0,20,'2026-11-05 16:29:40'),(24,5,7,7,12,20,100,20,'2026-10-25 11:13:24'),(25,5,7,7,13,21,-14,20,'2026-11-07 16:51:17'),(26,5,7,7,14,22,-15,20,'2026-11-10 13:29:50'),(27,5,7,7,16,24,-20,20,'2026-11-11 12:30:12'),(28,5,7,7,17,25,10,20,'2026-10-29 15:32:42'),(29,5,7,7,18,26,10,20,'2026-10-29 15:31:38');
/*!40000 ALTER TABLE `retailer_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_history`
--

DROP TABLE IF EXISTS `sales_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_history` (
  `sales_history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned DEFAULT NULL,
  `sale_date` date NOT NULL,
  `units_sold` int(10) unsigned NOT NULL,
  `sales_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sales_amount_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') DEFAULT NULL,
  `tax_category_snapshot` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') DEFAULT NULL,
  `tax_rate_snapshot` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(12,2) DEFAULT NULL,
  `net_sales_amount` decimal(12,2) DEFAULT NULL,
  `unit_cost_snapshot` decimal(12,4) DEFAULT NULL,
  `cost_of_goods_sold` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`sales_history_id`),
  UNIQUE KEY `uq_sales_history_daily_scope` (`retailer_id`,`branch_id`,`product_id`,`product_version_id`,`sale_date`),
  KEY `fk_sales_history_product` (`product_id`),
  KEY `idx_sales_history_branch` (`branch_id`),
  KEY `idx_sales_history_stock_scope` (`retailer_id`,`branch_id`,`product_id`,`product_version_id`,`sale_date`),
  KEY `fk_sales_history_version` (`product_version_id`),
  CONSTRAINT `fk_sales_history_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_history_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_history_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_history`
--

LOCK TABLES `sales_history` WRITE;
/*!40000 ALTER TABLE `sales_history` DISABLE KEYS */;
INSERT INTO `sales_history` VALUES (1,5,NULL,1,NULL,'2026-09-24',8,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,5,NULL,1,NULL,'2026-09-25',7,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,5,NULL,1,NULL,'2026-09-26',9,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,5,NULL,1,NULL,'2026-09-27',8,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,5,NULL,1,NULL,'2026-09-28',10,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,5,NULL,1,NULL,'2026-09-29',7,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,5,NULL,1,NULL,'2026-09-30',9,NULL,'2026-10-04 13:16:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,5,7,6,8,'2026-10-24',5,NULL,'2026-10-24 15:29:41',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,5,7,15,23,'2026-10-24',5,NULL,'2026-10-24 15:36:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(10,5,7,11,18,'2026-10-25',50,27500.00,'2026-10-25 06:17:49','VAT_INCLUSIVE','STANDARD_RATED',16.00,3793.10,23706.90,NULL,NULL),(13,5,7,19,27,'2026-10-25',2,1000.00,'2026-10-25 08:12:26','VAT_INCLUSIVE','STANDARD_RATED',16.00,137.93,862.07,NULL,NULL),(14,5,7,20,28,'2026-10-25',5,1250.00,'2026-10-25 08:14:50','VAT_INCLUSIVE','STANDARD_RATED',16.00,172.41,1077.59,NULL,NULL),(16,5,7,16,24,'2026-10-25',105,42000.00,'2026-10-25 08:16:30','VAT_INCLUSIVE','STANDARD_RATED',16.00,5793.10,36206.90,NULL,NULL),(17,5,7,1,1,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(18,5,7,1,6,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(19,5,7,2,2,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(20,5,7,3,3,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(21,5,7,3,11,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(22,5,7,4,4,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(23,5,7,4,12,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(24,5,7,5,5,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(25,5,7,6,7,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(26,5,7,6,8,'2026-10-25',1,200.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,27.59,172.41,NULL,NULL),(27,5,7,7,13,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(28,5,7,8,14,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(29,5,7,9,15,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(30,5,7,9,16,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(31,5,7,10,17,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(32,5,7,11,19,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(33,5,7,12,20,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(34,5,7,13,21,'2026-10-25',1,2250.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,310.34,1939.66,NULL,NULL),(35,5,7,14,22,'2026-10-25',87,130500.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,18000.00,112500.00,NULL,NULL),(36,5,7,15,23,'2026-10-25',1,1499.87,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,206.88,1292.99,NULL,NULL),(37,5,7,17,25,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(38,5,7,18,26,'2026-10-25',1,100.00,'2026-10-25 08:23:05','VAT_INCLUSIVE','STANDARD_RATED',16.00,13.79,86.21,NULL,NULL),(48,5,7,15,23,'2026-10-29',21,31497.27,'2026-10-29 09:43:10','VAT_INCLUSIVE','STANDARD_RATED',16.00,4344.45,27152.82,NULL,NULL),(49,5,7,13,21,'2026-10-29',100,225000.00,'2026-10-29 09:44:35','VAT_INCLUSIVE','STANDARD_RATED',16.00,31034.48,193965.52,NULL,NULL),(50,5,7,18,26,'2026-10-29',90,13050.00,'2026-10-29 12:31:38','VAT_INCLUSIVE','STANDARD_RATED',16.00,1800.00,11250.00,NULL,NULL),(51,5,7,2,2,'2026-10-29',10,4450.00,'2026-10-29 12:31:48','VAT_INCLUSIVE','STANDARD_RATED',16.00,613.79,3836.21,NULL,NULL),(52,5,7,10,17,'2026-10-29',16,5759.84,'2026-10-29 12:32:00','VAT_INCLUSIVE','STANDARD_RATED',16.00,794.46,4965.38,NULL,NULL),(53,5,7,19,27,'2026-10-29',4,640.00,'2026-10-29 12:32:08','VAT_INCLUSIVE','STANDARD_RATED',16.00,88.28,551.72,NULL,NULL),(54,5,7,1,1,'2026-10-29',90,38250.00,'2026-10-29 12:32:20','VAT_INCLUSIVE','STANDARD_RATED',16.00,5275.86,32974.14,NULL,NULL),(55,5,7,1,6,'2026-10-29',90,26550.00,'2026-10-29 12:32:28','VAT_INCLUSIVE','STANDARD_RATED',16.00,3662.07,22887.93,NULL,NULL),(56,5,7,9,15,'2026-10-29',90,26099.10,'2026-10-29 12:32:35','VAT_INCLUSIVE','STANDARD_RATED',16.00,3599.88,22499.22,NULL,NULL),(57,5,7,17,25,'2026-10-29',90,30600.00,'2026-10-29 12:32:42','VAT_INCLUSIVE','STANDARD_RATED',16.00,4220.69,26379.31,NULL,NULL),(58,5,7,6,7,'2026-10-29',101,30298.99,'2026-10-29 12:32:54','VAT_INCLUSIVE','STANDARD_RATED',16.00,4179.17,26119.82,NULL,NULL),(59,5,7,3,11,'2026-10-29',99,14850.00,'2026-10-29 12:33:14','VAT_INCLUSIVE','STANDARD_RATED',16.00,2048.28,12801.72,NULL,NULL),(60,5,7,7,13,'2026-10-29',99,35640.00,'2026-10-29 12:33:20','VAT_INCLUSIVE','STANDARD_RATED',16.00,4915.86,30724.14,NULL,NULL),(61,5,7,14,22,'2026-10-29',14,2379.86,'2026-10-29 14:11:55','VAT_INCLUSIVE','STANDARD_RATED',16.00,328.26,2051.60,NULL,NULL),(62,5,7,9,16,'2026-10-29',100,46999.00,'2026-10-29 14:12:19','VAT_INCLUSIVE','STANDARD_RATED',16.00,6482.62,40516.38,NULL,NULL),(63,5,7,20,28,'2026-11-05',15,1275.00,'2026-11-05 13:28:45','VAT_INCLUSIVE','STANDARD_RATED',16.00,175.86,1099.14,NULL,NULL),(64,5,7,3,11,'2026-11-05',2,300.00,'2026-11-05 13:29:08','VAT_INCLUSIVE','STANDARD_RATED',16.00,41.38,258.62,NULL,NULL),(65,5,7,1,6,'2026-11-05',10,2950.00,'2026-11-05 13:29:26','VAT_INCLUSIVE','STANDARD_RATED',16.00,406.90,2543.10,NULL,NULL),(66,5,7,11,19,'2026-11-05',100,35500.00,'2026-11-05 13:29:40','VAT_INCLUSIVE','STANDARD_RATED',16.00,4896.55,30603.45,NULL,NULL),(67,5,7,8,14,'2026-11-05',95,37525.00,'2026-11-05 13:29:51','VAT_INCLUSIVE','STANDARD_RATED',16.00,5175.86,32349.14,NULL,NULL),(68,5,7,2,2,'2026-11-05',90,35100.00,'2026-11-05 13:30:00','VAT_INCLUSIVE','EXEMPT',0.00,0.00,35100.00,NULL,NULL),(69,5,7,19,27,'2026-11-07',3,480.00,'2026-11-07 13:50:48','VAT_INCLUSIVE','STANDARD_RATED',16.00,66.21,413.79,NULL,NULL),(70,5,7,13,21,'2026-11-07',14,3780.00,'2026-11-07 13:51:17','VAT_INCLUSIVE','STANDARD_RATED',16.00,521.38,3258.62,NULL,NULL),(71,5,7,14,22,'2026-11-10',15,2549.85,'2026-11-10 10:29:50','VAT_INCLUSIVE','STANDARD_RATED',16.00,351.70,2198.15,NULL,NULL),(72,5,7,16,24,'2026-11-11',15,2399.85,'2026-11-11 09:30:12','VAT_INCLUSIVE','STANDARD_RATED',16.00,331.01,2068.84,NULL,NULL);
/*!40000 ALTER TABLE `sales_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_alerts`
--

DROP TABLE IF EXISTS `stock_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_alerts` (
  `stock_alert_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned DEFAULT NULL,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(10) unsigned NOT NULL,
  `average_daily_sales` decimal(12,2) NOT NULL,
  `lead_time_days` int(10) unsigned NOT NULL,
  `expected_demand` int(11) NOT NULL DEFAULT 0,
  `recommended_order_quantity` int(11) NOT NULL DEFAULT 0,
  `alert_type` enum('LOW_STOCK','OUT_OF_STOCK','SUPPLY_UNAVAILABLE') NOT NULL,
  `status` enum('ACTIVE','RESOLVED','DISMISSED') NOT NULL DEFAULT 'ACTIVE',
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  `active_branch_scope_id` int(10) unsigned GENERATED ALWAYS AS (case when `status` = 'ACTIVE' then ifnull(`branch_id`,0) else NULL end) STORED,
  `active_version_scope_id` int(10) unsigned GENERATED ALWAYS AS (case when `status` = 'ACTIVE' then ifnull(`product_version_id`,0) else NULL end) STORED,
  PRIMARY KEY (`stock_alert_id`),
  UNIQUE KEY `uq_stock_alerts_active_scope` (`retailer_id`,`active_branch_scope_id`,`product_id`,`active_version_scope_id`),
  KEY `fk_stock_alerts_product` (`product_id`),
  KEY `idx_stock_alerts_branch` (`branch_id`),
  KEY `idx_stock_alerts_stock_scope` (`retailer_id`,`branch_id`,`product_id`,`product_version_id`,`status`),
  KEY `fk_stock_alerts_version` (`product_version_id`),
  CONSTRAINT `fk_stock_alerts_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_alerts_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_alerts_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_alerts`
--

LOCK TABLES `stock_alerts` WRITE;
/*!40000 ALTER TABLE `stock_alerts` DISABLE KEYS */;
INSERT INTO `stock_alerts` VALUES (1,5,NULL,1,NULL,18,25,8.29,3,25,40,'LOW_STOCK','ACTIVE','2026-10-04 16:16:58',NULL,0,0),(2,5,7,11,18,8,120,3.57,3,11,112,'LOW_STOCK','ACTIVE','2026-10-24 21:52:09',NULL,7,18),(3,5,7,5,5,6,6,0.00,3,0,1,'LOW_STOCK','ACTIVE','2026-10-24 21:57:35',NULL,7,5),(4,5,7,6,8,0,20,5.71,3,17,58,'OUT_OF_STOCK','ACTIVE','2026-10-25 10:56:18',NULL,7,8),(5,5,7,19,27,-2,5,0.64,3,2,9,'OUT_OF_STOCK','ACTIVE','2026-10-25 11:12:26',NULL,7,27),(6,5,7,16,24,-20,20,1.07,3,3,40,'OUT_OF_STOCK','ACTIVE','2026-10-25 11:16:30',NULL,7,24),(7,5,7,14,22,-15,20,2.07,3,6,36,'OUT_OF_STOCK','ACTIVE','2026-10-25 19:50:24',NULL,7,22),(8,5,7,15,23,0,10,1.93,3,6,20,'OUT_OF_STOCK','ACTIVE','2026-10-29 12:43:10',NULL,7,23),(9,5,7,13,21,-14,20,8.21,3,25,97,'OUT_OF_STOCK','ACTIVE','2026-10-29 12:44:35',NULL,7,21),(10,5,7,18,26,10,20,6.50,3,20,55,'LOW_STOCK','ACTIVE','2026-10-29 15:31:38',NULL,7,26),(11,5,7,2,2,10,20,6.50,3,20,55,'LOW_STOCK','RESOLVED','2026-10-29 15:31:48','2026-10-29 15:33:07',NULL,NULL),(12,5,7,10,17,0,12,1.21,3,4,13,'OUT_OF_STOCK','ACTIVE','2026-10-29 15:32:00',NULL,7,17),(13,5,7,1,1,10,20,6.50,3,20,60,'LOW_STOCK','ACTIVE','2026-10-29 15:32:20',NULL,7,1),(14,5,7,1,6,10,20,6.50,3,20,60,'LOW_STOCK','RESOLVED','2026-10-29 15:32:28','2026-10-29 17:12:27',NULL,NULL),(15,5,7,9,15,10,20,6.50,3,20,55,'LOW_STOCK','ACTIVE','2026-10-29 15:32:35',NULL,7,15),(16,5,7,17,25,10,20,6.50,3,20,55,'LOW_STOCK','ACTIVE','2026-10-29 15:32:42',NULL,7,25),(17,5,7,6,7,-1,20,7.29,3,22,74,'OUT_OF_STOCK','ACTIVE','2026-10-29 15:32:54',NULL,7,7),(18,5,7,3,11,-1,20,7.29,3,22,74,'OUT_OF_STOCK','ACTIVE','2026-10-29 15:33:14',NULL,7,11),(19,5,7,7,13,1,20,7.14,3,21,71,'LOW_STOCK','ACTIVE','2026-10-29 15:33:20',NULL,7,13),(20,5,7,9,16,0,20,7.21,3,22,73,'OUT_OF_STOCK','ACTIVE','2026-10-29 17:12:19',NULL,7,16),(21,5,7,1,6,10,20,6.50,3,20,60,'LOW_STOCK','RESOLVED','2026-10-29 17:12:32','2026-10-29 17:12:36',NULL,NULL),(22,5,7,1,6,0,20,7.21,3,22,73,'OUT_OF_STOCK','ACTIVE','2026-10-29 17:12:40',NULL,7,6),(23,5,7,20,28,0,10,1.43,3,4,15,'OUT_OF_STOCK','ACTIVE','2026-11-05 16:28:45',NULL,7,28),(24,5,7,11,19,0,20,7.21,3,22,73,'OUT_OF_STOCK','ACTIVE','2026-11-05 16:29:40',NULL,7,19),(25,5,7,8,14,5,20,6.86,3,21,64,'LOW_STOCK','ACTIVE','2026-11-05 16:29:51',NULL,7,14),(26,5,7,2,2,0,20,7.21,3,22,73,'OUT_OF_STOCK','ACTIVE','2026-11-05 16:30:00',NULL,7,2);
/*!40000 ALTER TABLE `stock_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_distribution_areas`
--

DROP TABLE IF EXISTS `supplier_distribution_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_distribution_areas` (
  `supplier_area_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `area_id` int(10) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supplier_area_id`),
  KEY `fk_supplier_area_area` (`area_id`),
  KEY `idx_supplier_area_period` (`supplier_id`,`area_id`,`status`,`start_date`,`end_date`),
  CONSTRAINT `fk_supplier_area_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_area_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_distribution_areas`
--

LOCK TABLES `supplier_distribution_areas` WRITE;
/*!40000 ALTER TABLE `supplier_distribution_areas` DISABLE KEYS */;
INSERT INTO `supplier_distribution_areas` VALUES (1,3,29,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 12:15:28'),(2,3,1,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 12:30:07'),(7,3,1,'2026-10-24','2026-10-25','INACTIVE','2026-10-24 13:05:31'),(8,3,31,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 13:14:46'),(9,3,31,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 13:25:49'),(10,3,21,'2026-10-24','2026-10-24','INACTIVE','2026-10-24 14:08:23'),(11,3,1,'2026-10-25',NULL,'ACTIVE','2026-10-25 00:58:49'),(12,3,16,'2026-10-25',NULL,'ACTIVE','2026-10-25 01:33:33'),(13,3,4,'2026-10-25',NULL,'ACTIVE','2026-10-25 01:33:39'),(14,1,1,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:13:57'),(15,3,5,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(16,3,6,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(17,3,7,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(18,3,8,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(19,3,9,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(20,3,10,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(21,3,11,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(22,3,12,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(23,3,13,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(24,3,3,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(25,3,14,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(26,3,15,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(27,3,17,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:27'),(28,3,18,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(29,3,19,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(30,3,20,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(31,3,21,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(32,3,22,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(33,3,23,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(34,3,2,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(35,3,24,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(36,3,25,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(37,3,26,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(38,3,27,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(39,3,28,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(40,3,29,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(41,3,30,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(42,3,31,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(43,3,32,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(44,3,33,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(45,3,34,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(46,3,35,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(47,3,36,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(48,3,37,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(49,3,38,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(50,3,39,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(51,3,40,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(52,3,41,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(53,3,42,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(54,3,43,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(55,3,44,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(56,3,45,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(57,3,46,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:28'),(58,3,47,'2026-10-28',NULL,'ACTIVE','2026-10-28 14:29:29'),(59,11,5,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(60,11,6,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(61,11,7,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(62,11,8,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(63,11,9,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(64,11,10,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(65,11,11,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(66,11,12,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(67,11,13,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(68,11,3,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(69,11,14,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(70,11,15,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(71,11,16,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(72,11,17,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(73,11,18,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(74,11,19,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(75,11,20,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(76,11,4,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(77,11,21,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(78,11,22,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(79,11,23,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(80,11,2,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(81,11,24,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(82,11,25,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(83,11,26,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(84,11,27,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(85,11,28,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(86,11,29,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(87,11,30,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(88,11,1,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(89,11,31,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:54'),(90,11,32,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(91,11,33,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(92,11,34,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(93,11,35,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(94,11,36,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(95,11,37,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(96,11,38,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(97,11,39,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(98,11,40,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(99,11,41,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(100,11,42,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(101,11,43,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(102,11,44,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(103,11,45,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(104,11,46,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(105,11,47,'2026-10-28',NULL,'ACTIVE','2026-10-28 19:19:55'),(106,30,1,'2026-10-29',NULL,'ACTIVE','2026-10-29 15:19:29');
/*!40000 ALTER TABLE `supplier_distribution_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_product_offers`
--

DROP TABLE IF EXISTS `supplier_product_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_product_offers` (
  `supplier_product_offer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `area_id` int(10) unsigned NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `price_unit` enum('EACH','CTN') NOT NULL DEFAULT 'EACH',
  `units_per_carton` int(10) unsigned DEFAULT NULL,
  `price_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'KES',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `offer_note` varchar(500) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_product_offer_id`),
  UNIQUE KEY `uq_supplier_offer_start` (`supplier_id`,`product_id`,`product_version_id`,`area_id`,`effective_from`),
  KEY `idx_supplier_offer_period` (`supplier_id`,`product_id`,`product_version_id`,`area_id`,`status`,`effective_from`,`effective_to`),
  KEY `fk_supplier_offer_product_version` (`product_id`,`product_version_id`),
  KEY `fk_supplier_offer_area` (`area_id`),
  KEY `fk_supplier_offer_created_by` (`created_by`),
  CONSTRAINT `fk_supplier_offer_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_offer_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_offer_product_version` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product_versions` (`product_id`, `product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_offer_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_supplier_offer_price` CHECK (`unit_price` >= 0),
  CONSTRAINT `chk_supplier_offer_period` CHECK (`effective_to` is null or `effective_to` >= `effective_from`),
  CONSTRAINT `chk_supplier_offer_price_unit` CHECK (`price_unit` = 'CTN' and `units_per_carton` is not null and `units_per_carton` >= 1 or `price_unit` = 'EACH' and (`units_per_carton` is null or `units_per_carton` >= 1))
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_product_offers`
--

LOCK TABLES `supplier_product_offers` WRITE;
/*!40000 ALTER TABLE `supplier_product_offers` DISABLE KEYS */;
INSERT INTO `supplier_product_offers` VALUES (2,3,2,2,1,284.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(5,3,5,5,1,161.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(7,3,8,14,1,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer RSP; treated as VAT inclusive.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(9,3,10,17,1,165.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer RSP; treated as VAT inclusive.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(11,3,12,20,1,161.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(12,3,13,21,1,170.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(13,3,14,22,1,109.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(14,3,15,23,1,138.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(15,3,16,24,1,101.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(16,3,17,25,1,221.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(17,3,18,26,1,91.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(18,3,19,27,1,102.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer COST because no applicable RSP was found.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(19,3,20,28,1,85.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-10-29','2026-10-31','INACTIVE','Initial Fratres Nairobi offer sourced from manufacturer RSP; treated as VAT inclusive.',NULL,'2026-10-29 06:51:12','2026-11-01 14:13:24'),(34,3,10,17,4,165.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-01',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',11,'2026-11-01 14:23:42','2026-11-01 14:23:42'),(35,3,8,14,4,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-01',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-01 14:32:56','2026-11-04 01:15:10'),(36,3,8,14,1,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-01',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-01 14:37:06','2026-11-04 01:15:10'),(37,3,8,35,4,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-02',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-02 07:16:20','2026-11-04 01:15:10'),(38,3,8,35,1,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-02',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-02 07:16:20','2026-11-04 01:15:10'),(40,3,2,2,1,352.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-03',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-03 03:17:31','2026-11-04 01:15:10'),(41,3,25,37,1,355.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-07','2026-11-08','ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-07 13:32:27','2026-11-07 13:32:27'),(43,3,5,5,1,200.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-08',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',7,'2026-11-08 04:07:02','2026-11-08 04:07:02'),(44,3,8,35,29,120.00,'EACH',NULL,'VAT_INCLUSIVE','KES','2026-11-08',NULL,'ACTIVE','Initial supplier offer sourced from the manufacturer RSP; the price basis follows the manufacturer selling price.',28,'2026-11-08 04:52:17','2026-11-08 04:52:17');
/*!40000 ALTER TABLE `supplier_product_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_product_supply`
--

DROP TABLE IF EXISTS `supplier_product_supply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_product_supply` (
  `supplier_product_supply_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lead_time_days` int(10) unsigned NOT NULL,
  `minimum_order_quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `order_multiple` int(10) unsigned NOT NULL DEFAULT 1,
  `order_unit` enum('EACH','CTN') NOT NULL DEFAULT 'EACH',
  `availability_status` enum('AVAILABLE','LIMITED','OUT_OF_STOCK','INACTIVE') NOT NULL DEFAULT 'AVAILABLE',
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_product_supply_id`),
  UNIQUE KEY `uq_supplier_product_supply` (`supplier_id`,`product_id`),
  KEY `fk_supplier_product_supply_product` (`product_id`),
  CONSTRAINT `fk_supplier_product_supply_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_product_supply_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_product_supply`
--

LOCK TABLES `supplier_product_supply` WRITE;
/*!40000 ALTER TABLE `supplier_product_supply` DISABLE KEYS */;
INSERT INTO `supplier_product_supply` VALUES (1,3,1,3,20,10,'EACH','AVAILABLE','2026-10-04 16:15:26');
/*!40000 ALTER TABLE `supplier_product_supply` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_change_recipients`
--

DROP TABLE IF EXISTS `supply_change_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_change_recipients` (
  `supply_change_recipient_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supply_change_id` int(10) unsigned NOT NULL,
  `organization_id` int(10) unsigned NOT NULL,
  `notification_status` enum('PENDING','SENT','VIEWED','ACKNOWLEDGED','CLARIFICATION_REQUESTED','RESOLVED') NOT NULL DEFAULT 'PENDING',
  `viewed_at` datetime DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supply_change_recipient_id`),
  UNIQUE KEY `uq_supply_change_recipient` (`supply_change_id`,`organization_id`),
  KEY `fk_supply_change_recipients_organization` (`organization_id`),
  KEY `fk_supply_change_recipients_acknowledged_by` (`acknowledged_by`),
  CONSTRAINT `fk_supply_change_recipients_acknowledged_by` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_change_recipients_change` FOREIGN KEY (`supply_change_id`) REFERENCES `supply_changes` (`supply_change_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_change_recipients_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_change_recipients`
--

LOCK TABLES `supply_change_recipients` WRITE;
/*!40000 ALTER TABLE `supply_change_recipients` DISABLE KEYS */;
INSERT INTO `supply_change_recipients` VALUES (1,1,5,'SENT',NULL,NULL,NULL,'2026-10-04 12:54:07');
/*!40000 ALTER TABLE `supply_change_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_changes`
--

DROP TABLE IF EXISTS `supply_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_changes` (
  `supply_change_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `old_supplier_id` int(10) unsigned DEFAULT NULL,
  `new_supplier_id` int(10) unsigned NOT NULL,
  `changed_by` int(10) unsigned NOT NULL,
  `published_by` int(10) unsigned DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `status` enum('DRAFT','PENDING_APPROVAL','PUBLISHED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supply_change_id`),
  KEY `fk_supply_changes_product` (`product_id`),
  KEY `fk_supply_changes_old_supplier` (`old_supplier_id`),
  KEY `fk_supply_changes_new_supplier` (`new_supplier_id`),
  KEY `fk_supply_changes_user` (`changed_by`),
  KEY `fk_supply_changes_published_by` (`published_by`),
  CONSTRAINT `fk_supply_changes_new_supplier` FOREIGN KEY (`new_supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_changes_old_supplier` FOREIGN KEY (`old_supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_changes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_changes_published_by` FOREIGN KEY (`published_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_changes_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_changes`
--

LOCK TABLES `supply_changes` WRITE;
/*!40000 ALTER TABLE `supply_changes` DISABLE KEYS */;
INSERT INTO `supply_changes` VALUES (1,1,1,3,2,NULL,NULL,'Distribution responsibility for Whitedent has been transferred to the new supplier.','2026-09-01','PUBLISHED','2026-10-04 12:53:44');
/*!40000 ALTER TABLE `supply_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_order_item_acknowledgements`
--

DROP TABLE IF EXISTS `supply_order_item_acknowledgements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_order_item_acknowledgements` (
  `acknowledgement_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supply_order_item_id` int(10) unsigned NOT NULL,
  `quantity_ordered` int(10) unsigned NOT NULL,
  `quantity_available` int(10) unsigned NOT NULL DEFAULT 0,
  `quantity_short` int(10) unsigned NOT NULL DEFAULT 0,
  `quantity_out_of_stock` int(10) unsigned NOT NULL DEFAULT 0,
  `expected_delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`acknowledgement_item_id`),
  UNIQUE KEY `uq_acknowledgement_supply_order_item` (`supply_order_item_id`),
  CONSTRAINT `fk_acknowledgement_supply_order_item` FOREIGN KEY (`supply_order_item_id`) REFERENCES `supply_order_items` (`supply_order_item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_acknowledgement_quantities` CHECK (`quantity_available` + `quantity_short` + `quantity_out_of_stock` = `quantity_ordered`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_order_item_acknowledgements`
--

LOCK TABLES `supply_order_item_acknowledgements` WRITE;
/*!40000 ALTER TABLE `supply_order_item_acknowledgements` DISABLE KEYS */;
INSERT INTO `supply_order_item_acknowledgements` VALUES (1,132,300,300,0,0,NULL,'2026-11-02 17:35:54'),(2,136,300,200,100,0,NULL,'2026-11-02 17:36:41');
/*!40000 ALTER TABLE `supply_order_item_acknowledgements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_order_items`
--

DROP TABLE IF EXISTS `supply_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_order_items` (
  `supply_order_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supply_order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned DEFAULT NULL,
  `quantity_ordered` int(10) unsigned NOT NULL,
  `quantity_received` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_price` decimal(14,4) DEFAULT NULL,
  `offer_price_unit` enum('EACH','CTN') NOT NULL DEFAULT 'EACH',
  `supplier_order_unit` enum('EACH','CTN') NOT NULL DEFAULT 'EACH',
  `supplier_offer_unit_price` decimal(12,2) DEFAULT NULL,
  `supplier_units_per_carton` int(10) unsigned DEFAULT NULL,
  `price_basis` enum('VAT_INCLUSIVE','VAT_EXCLUSIVE') DEFAULT NULL,
  `tax_category` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') DEFAULT NULL,
  `supplier_product_offer_id` int(10) unsigned DEFAULT NULL,
  `source_product_price_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supply_order_item_id`),
  UNIQUE KEY `uq_supply_order_product` (`supply_order_id`,`product_id`),
  KEY `fk_supply_order_items_product_version` (`product_id`,`product_version_id`),
  KEY `fk_supply_order_items_supplier_offer` (`supplier_product_offer_id`),
  CONSTRAINT `fk_supply_order_items_order` FOREIGN KEY (`supply_order_id`) REFERENCES `supply_orders` (`supply_order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_order_items_product_version` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product_versions` (`product_id`, `product_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_order_items_supplier_offer` FOREIGN KEY (`supplier_product_offer_id`) REFERENCES `supplier_product_offers` (`supplier_product_offer_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_lpo_item_offer_unit` CHECK ((`offer_price_unit` = 'CTN' or `supplier_order_unit` = 'CTN') and `supplier_units_per_carton` is not null and `supplier_units_per_carton` >= 1 or `offer_price_unit` = 'EACH' and `supplier_order_unit` = 'EACH' and (`supplier_units_per_carton` is null or `supplier_units_per_carton` >= 1))
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_order_items`
--

LOCK TABLES `supply_order_items` WRITE;
/*!40000 ALTER TABLE `supply_order_items` DISABLE KEYS */;
INSERT INTO `supply_order_items` VALUES (119,1,14,22,14,0,109.0000,'EACH','EACH',109.00,NULL,'VAT_INCLUSIVE',NULL,13,NULL,'2026-10-29 08:12:06'),(120,1,16,24,100,0,101.0000,'EACH','EACH',101.00,NULL,'VAT_INCLUSIVE',NULL,15,NULL,'2026-10-29 08:12:06'),(121,1,15,23,14,0,138.0000,'EACH','EACH',138.00,NULL,'VAT_INCLUSIVE',NULL,14,NULL,'2026-10-29 08:12:06'),(122,1,13,21,50,0,170.0000,'EACH','EACH',170.00,NULL,'VAT_INCLUSIVE',NULL,12,NULL,'2026-10-29 08:12:06'),(123,1,20,28,32,0,85.0000,'EACH','EACH',85.00,NULL,'VAT_INCLUSIVE',NULL,19,NULL,'2026-10-29 08:12:06'),(124,1,18,26,12,0,91.0000,'EACH','EACH',91.00,NULL,'VAT_INCLUSIVE',NULL,17,NULL,'2026-10-29 08:12:06'),(125,1,2,2,14,0,284.0000,'EACH','EACH',284.00,NULL,'VAT_INCLUSIVE',NULL,2,NULL,'2026-10-29 08:12:06'),(126,1,8,14,20,0,120.0000,'EACH','EACH',120.00,NULL,'VAT_INCLUSIVE',NULL,7,NULL,'2026-10-29 08:12:06'),(127,1,12,20,32,0,161.0000,'EACH','EACH',161.00,NULL,'VAT_INCLUSIVE',NULL,11,NULL,'2026-10-29 08:12:06'),(128,1,5,5,250,0,161.0000,'EACH','EACH',161.00,NULL,'VAT_INCLUSIVE',NULL,5,NULL,'2026-10-29 08:12:06'),(129,1,17,25,14,0,221.0000,'EACH','EACH',221.00,NULL,'VAT_INCLUSIVE',NULL,16,NULL,'2026-10-29 08:12:06'),(130,1,19,27,36,0,102.0000,'EACH','EACH',102.00,NULL,'VAT_INCLUSIVE',NULL,18,NULL,'2026-10-29 08:12:06'),(131,1,10,17,14,0,165.0000,'EACH','EACH',165.00,NULL,'VAT_INCLUSIVE',NULL,9,NULL,'2026-10-29 08:12:06'),(132,8,23,33,300,0,95.0000,'EACH','EACH',95.00,NULL,'VAT_INCLUSIVE','STANDARD_RATED',NULL,400,'2026-10-30 03:49:37'),(136,9,23,33,300,0,95.0000,'EACH','EACH',95.00,NULL,'VAT_EXCLUSIVE','STANDARD_RATED',NULL,404,'2026-11-02 16:43:13'),(145,10,8,35,500,0,120.0000,'EACH','EACH',NULL,NULL,'VAT_INCLUSIVE','STANDARD_RATED',38,NULL,'2026-11-11 09:34:03'),(146,10,2,2,500,0,352.0000,'EACH','EACH',NULL,NULL,'VAT_INCLUSIVE','EXEMPT',40,NULL,'2026-11-11 09:34:03'),(147,10,5,5,500,0,200.0000,'EACH','EACH',NULL,NULL,'VAT_INCLUSIVE','STANDARD_RATED',43,NULL,'2026-11-11 09:34:03');
/*!40000 ALTER TABLE `supply_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supply_orders`
--

DROP TABLE IF EXISTS `supply_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_orders` (
  `supply_order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lpo_number` varchar(32) NOT NULL,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `order_direction` enum('RETAILER_TO_SUPPLIER','SUPPLIER_TO_MANUFACTURER') NOT NULL DEFAULT 'RETAILER_TO_SUPPLIER',
  `order_date` date NOT NULL,
  `lpo_valid_until` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','CONFIRMED','REJECTED','PARTIALLY_DELIVERED','DELIVERED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`supply_order_id`),
  UNIQUE KEY `uq_supply_orders_org_lpo` (`retailer_id`,`lpo_number`),
  KEY `fk_supply_orders_supplier` (`supplier_id`),
  KEY `fk_supply_orders_user` (`created_by`),
  KEY `idx_supply_orders_branch` (`branch_id`),
  CONSTRAINT `fk_supply_orders_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supply_orders`
--

LOCK TABLES `supply_orders` WRITE;
/*!40000 ALTER TABLE `supply_orders` DISABLE KEYS */;
INSERT INTO `supply_orders` VALUES (1,'LPO-000001',5,7,3,'RETAILER_TO_SUPPLIER','2026-10-29','2026-11-28',NULL,'CONFIRMED',1,'2026-10-29 06:51:50','2026-10-29 14:26:26',28,'2026-10-29 08:59:21',NULL),(8,'LPO-000001',3,NULL,33,'SUPPLIER_TO_MANUFACTURER','2026-10-30','2026-11-29',NULL,'CONFIRMED',28,'2026-10-30 03:49:37','2026-11-02 17:35:54',42,'2026-11-02 17:35:54',NULL),(9,'LPO-000002',3,NULL,33,'SUPPLIER_TO_MANUFACTURER','2026-11-02','2026-12-01',NULL,'CONFIRMED',28,'2026-11-02 16:42:54','2026-11-02 17:36:41',42,'2026-11-02 17:36:41',NULL),(10,'LPO-000002',5,7,3,'RETAILER_TO_SUPPLIER','2026-11-11','2026-12-10',NULL,'SUBMITTED',1,'2026-11-11 09:33:41','2026-11-11 09:34:06',NULL,NULL,NULL);
/*!40000 ALTER TABLE `supply_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rate_schedule`
--

DROP TABLE IF EXISTS `tax_rate_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_rate_schedule` (
  `tax_rate_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tax_category` enum('STANDARD_RATED','ZERO_RATED','EXEMPT') NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `taxable_supply` tinyint(1) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `source_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`tax_rate_id`),
  UNIQUE KEY `uq_tax_rate_category_start` (`tax_category`,`effective_from`),
  KEY `idx_tax_rate_effective_period` (`tax_category`,`effective_from`,`effective_to`),
  CONSTRAINT `chk_tax_rate_percent` CHECK (`rate_percent` >= 0 and `rate_percent` <= 100),
  CONSTRAINT `chk_taxable_supply_flag` CHECK (`taxable_supply` in (0,1)),
  CONSTRAINT `chk_tax_rate_period` CHECK (`effective_to` is null or `effective_to` >= `effective_from`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rate_schedule`
--

LOCK TABLES `tax_rate_schedule` WRITE;
/*!40000 ALTER TABLE `tax_rate_schedule` DISABLE KEYS */;
INSERT INTO `tax_rate_schedule` VALUES (1,'STANDARD_RATED',16.00,1,'2021-01-01',NULL,'General Kenya VAT rate; verify against applicable law for each transaction.','2026-10-24 16:20:21'),(2,'ZERO_RATED',0.00,1,'2021-01-01',NULL,'Qualifying zero-rated supplies only; verify statutory eligibility.','2026-10-24 16:20:21'),(3,'EXEMPT',0.00,0,'2021-01-01',NULL,'Exempt is distinct from zero-rated; related input VAT treatment differs.','2026-10-24 16:20:21');
/*!40000 ALTER TABLE `tax_rate_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','BRANCH_ADMIN','STAFF') NOT NULL DEFAULT 'STAFF',
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_branch` (`branch_id`),
  KEY `fk_users_branch_org` (`organization_id`,`branch_id`),
  CONSTRAINT `fk_users_branch_org` FOREIGN KEY (`organization_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,5,NULL,'Paul','Mutemi','admin@target.local','$2y$10$e.FY39uuqe6jrVbGXDER1u3zQUZEdVcME0LjyTFzJUrpD8pnTDgRm','ADMIN','ACTIVE','2026-10-04 12:48:25'),(2,1,3,'Chandaria','Manager','manager@chandaria.local','$2y$10$e.FY39uuqe6jrVbGXDER1u3zQUZEdVcME0LjyTFzJUrpD8pnTDgRm','ADMIN','ACTIVE','2026-10-04 12:48:25'),(3,5,7,'Target','Manager','manager@target.local','$2y$10$e.FY39uuqe6jrVbGXDER1u3zQUZEdVcME0LjyTFzJUrpD8pnTDgRm','STAFF','INACTIVE','2026-10-04 12:48:25'),(4,3,5,'Fratres','Staff','staff@fratres.local','$2y$10$e.FY39uuqe6jrVbGXDER1u3zQUZEdVcME0LjyTFzJUrpD8pnTDgRm','STAFF','ACTIVE','2026-10-04 12:48:25'),(7,2,4,'Barry','Kimani','manager@millbakers.local','$2y$10$n7zXbRqgU8/Rsx6XrTgQZOZDc1JO5K.SwoOf/cN9m0frKUAxxfsvO','ADMIN','ACTIVE','2026-10-05 02:44:46'),(9,9,11,'Paul','Mutemi','paulmutemi@gmail.com','$2y$10$T7MT2fdp7DCjTbQUr7h5weNpnfY63rApQjzsqG/mZIxmiIXy6FKhC','ADMIN','ACTIVE','2026-10-07 03:46:29'),(10,10,12,'kisha','Mokaya','kishamokaysummer@gmail.com','$2y$10$o/4Nw0N0l2U7nBgBUEPmy.u6yCvX4lWViqIPjmHswO/zCzdwOI9n2','ADMIN','ACTIVE','2026-10-07 07:36:13'),(11,11,13,'lola','kim','lolakimexcel@gmail.com','$2y$10$TV4F5POwV7TGl8AZmzV8k.gja074w.5/.LqG2lc3FMQ.CRCUq8uv.','ADMIN','ACTIVE','2026-10-07 07:38:37'),(12,12,14,'Test','User','test043555@example.com','$2y$10$gTV.3d4Mit5BOFONEWsgpeu3Ca5tNdDkrGIjyeLqDTAz2Ze7M8Wmq','ADMIN','ACTIVE','2026-10-08 01:35:56'),(13,13,15,'Test','User','phase0-140746@example.com','$2y$10$/ChWPEh5LIOChHEKiAVJhe3HV2Ne1JVI0ZHYBffLFxC4hJBJzTuIa','ADMIN','ACTIVE','2026-10-09 11:07:46'),(14,14,16,'Test','User','phase0-20261010025546@example.com','$2y$10$MF8X6UXA3GkDdvzeQx2kUeplvrF7iw2/gSLLjPRf9hqAaK4Lfmj5m','ADMIN','ACTIVE','2026-10-09 23:55:47'),(15,15,17,'Test','User','phase0-20261010025634@example.com','$2y$10$qSR7oMkcVQQN92hW5O48i.0e40pVRdEkstVhkGuT8TVUVyyQxtURK','ADMIN','ACTIVE','2026-10-09 23:56:34'),(16,16,18,'Test','User','phase0-20261010031252@example.com','$2y$10$ooXw5jZFL5bdQO7iT.tN9OAg3g27vgmWuAavBkXd2IrjrCDozwgce','ADMIN','ACTIVE','2026-10-10 00:12:52'),(17,5,7,'Joy','Wanjiru','joy033042@example.com','$2y$10$BXrUU2ioIyUV/TzMpVcPTO5PXlhGX6aJujEDBWWFBDrstRbePOtvO','STAFF','INACTIVE','2026-10-10 00:30:43'),(18,17,19,'Test','User','phase0-20261010033114@example.com','$2y$10$kligsZQVqhd4h2XGDcUTl.sBEQAAECiLxmK8fHQoSl7eCW3i8kB/i','ADMIN','ACTIVE','2026-10-10 00:31:14'),(19,18,20,'Test','User','phase0-20261010074601@example.com','$2y$10$AT/p24pKYiPOUkow.5R1oufz38/8dkNDeuwN7wljXmFH7/5bOnQwy','ADMIN','ACTIVE','2026-10-10 04:46:02'),(20,19,21,'Only','Admin','rule133653@example.com','$2y$10$GgsLFH5z.OLRVf9OsMpjuOglujkT2PvbSXH1ufCd5bWana2nn6iqy','ADMIN','ACTIVE','2026-10-10 10:36:53'),(21,20,22,'Only','Admin','rule134101@example.com','$2y$10$Fwc7Is7ypgwjps.24n0kzOMpivNY8VWpaQM5ZmUqHbo09XRwSAeqq','ADMIN','ACTIVE','2026-10-10 10:41:01'),(22,21,23,'Only','Renamed','rule134250@example.com','$2y$10$zVM2WzP9m/8qRpvTV9pDneQIP1212uY6yeGnzGrrgO8MQKGob9Ee2','ADMIN','ACTIVE','2026-10-10 10:42:50'),(23,22,24,'Only','Renamed','rule134256@example.com','$2y$10$WFLu6nLykJWRQjIRAMGvE.nLyiuk9lffTQOW9n3CN8f8aoOBBnKCm','ADMIN','ACTIVE','2026-10-10 10:42:56'),(24,23,27,'Test','User','phase0-20261010190514@example.com','$2y$10$tYWtrIV22U0wpMlGHdT2hu0jeqtlEFSvl4crcxtMDI2fY11H/fjvS','ADMIN','ACTIVE','2026-10-10 16:05:14'),(25,24,28,'Test','User','phase0-20261010194159@example.com','$2y$10$Ug8ryP0EIhJvI0PHsiCPQe1/PFuMlkKajSgf1ZsosmE5sYmeKjYaO','ADMIN','ACTIVE','2026-10-10 16:41:59'),(26,25,29,'carroline','muigai','caromigai@testorg.com','$2y$10$QEzjFEFQg52b2DGE22B0ou2MZ4kxh6mcthpBfhcQ4cPcMWMgJlu9K','ADMIN','ACTIVE','2026-10-14 12:42:00'),(27,2,4,'Shanice','mueni','shanicemueni@millbakers.com','$2y$10$Cb4Ssp4565FTzkUWiNRWqetcXzO32IX0DPQYOqWV6pp7afH1neGSu','STAFF','ACTIVE','2026-10-16 09:33:59'),(28,3,5,'Paul','Walker','admin@fratres.local','$2y$10$e.FY39uuqe6jrVbGXDER1u3zQUZEdVcME0LjyTFzJUrpD8pnTDgRm','ADMIN','ACTIVE','2026-10-17 18:56:32'),(29,26,30,'John','Kamau','johnkamau@uniliver.co.ke','$2y$10$cXf9F.RFVMSldETni0UIkudqHn6TCeaGHKNbTtos4VIuZ0f/i3346','ADMIN','ACTIVE','2026-10-20 06:36:10'),(30,26,30,'Jacob','Ochieng','jacobochieng@uniliver.co.ke','$2y$10$UvC4VuFGA2dzjOsvE/lWPOGzOzDbvX/4gFUz5l0pUTRoxezlz2rNy','STAFF','ACTIVE','2026-10-20 06:52:33'),(31,27,32,'Elijah','Kisoi','elijahkisoicadbury@gmail.com','$2y$10$ERPvLEkyBil95uTKjk0vqu7BDlGY66evnQFhuzfCdYgxdQyM9zWuq','ADMIN','ACTIVE','2026-10-20 08:06:35'),(32,27,32,'Abigail','Mwaniki','abimwanikicadbury@gmail.com','$2y$10$7NrXAEdd4BWR4aqaLKidU.CwBQDxtGTzisx2/PLMCkQH94Y3dlDTm','STAFF','ACTIVE','2026-10-20 08:18:39'),(33,27,33,'sussan','kihika','sussankihikacadbury@gmail.com','$2y$10$38B/IKfi65ovBtkqNe.oveeD5Nsb2glYN15/mrjWNxwPTTfVx63pu','STAFF','INACTIVE','2026-10-20 09:42:05'),(34,27,33,'Shanice','mosli','shanicemosli@gmail.co.ke','$2y$10$ayBI7HFHcEztli51bBEKOOw6AX0lmIf46UHZNDImz0Bn3ZO1XKa/m','ADMIN','INACTIVE','2026-10-20 09:43:45'),(35,28,34,'Mike','Jones','mikejoneshasbah@gmail.com','$2y$10$lCvJXP7WdS6gMT6T7Uoa8.OnqkKPqv8Di2tXrirPli.DeXNJcTCrK','ADMIN','ACTIVE','2026-10-20 13:15:11'),(36,29,35,'Fame','Mulwa','famemulwa@magunas.co.ke','$2y$10$nxH8jMDw8.xgrGKZqd4QLOKs7oxWzWF5Uub1KdhRIQGDFB76T9do2','ADMIN','ACTIVE','2026-10-22 14:17:06'),(37,5,36,'Jolly','Mosiria','jollymosiria@target.co.ke','$2y$10$82Op2cEZI4.rqorhaVv6VOFJcvXewZni.llLyowIgD0Ryuw.MAeum','BRANCH_ADMIN','ACTIVE','2026-10-23 05:09:31'),(38,5,36,'leo','nerd','leonerd@target.local','$2y$10$gJA.6rS3KYKmKliikMobsuYvcRXQb5bEYUNdUxaKIFn82dc.jyvpa','BRANCH_ADMIN','ACTIVE','2026-10-23 11:24:02'),(39,30,37,'Jack','Mwendwa','jackmwendwa@brookside.com','$2y$10$lCUXhZ6erK.C7CjHcp8/C.8zzk.ahycttelEDEDmdpsGTPyu8w8BG','ADMIN','ACTIVE','2026-10-29 14:54:20'),(40,31,38,'Daniel','Mwangi','danielmwangi@pwani.org','$2y$10$dRK8LpwqnZaTNMPw4cwAB.dh8htBWyfRX.hXsDBlO0S69dAPx2OzO','ADMIN','ACTIVE','2026-10-29 16:18:24'),(41,32,39,'Ahmed','Fadhil','ahmedfadhil@greenvast.org','$2y$10$iE9cFd0oOqs8xCDMfOA1nOMpZkWApw6UVlEpQx5nbmqs5Evymcleq','ADMIN','ACTIVE','2026-10-29 18:59:59'),(42,33,40,'khalif','kairo','khalifkairo@gmail.com','$2y$10$oTGVzE2BGWFxVz4ip9YcmuXiQ9IefzCo8HrPJSeXEHwBgDodXQldO','ADMIN','ACTIVE','2026-10-29 19:24:56'),(43,34,41,'Mercy','Masika','admin@kcc.local','$2y$10$yiXYt.L5KvO2.gKUq1peEukYw54JTfxJqeRsDBQml9JEFMS0OOCFy','ADMIN','ACTIVE','2026-11-08 12:54:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-12  2:55:06
