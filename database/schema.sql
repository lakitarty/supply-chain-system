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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `is_main_branch` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `main_branch_key` int(10) unsigned GENERATED ALWAYS AS (case when `is_main_branch` = 1 then `organization_id` else NULL end) STORED,
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `uq_branches_org_branch` (`organization_id`,`branch_id`),
  UNIQUE KEY `uq_branches_one_main` (`main_branch_key`),
  CONSTRAINT `fk_branches_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `conversation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(200) NOT NULL,
  `context_type` enum('PRODUCT_CHANGE','SUPPLY_CHANGE','PROMOTION','CLAIM','RELATIONSHIP') DEFAULT NULL,
  `context_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  KEY `fk_conversations_user` (`created_by`),
  KEY `idx_conversations_context` (`context_type`,`context_id`),
  CONSTRAINT `fk_conversations_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `notification_type` enum('PRODUCT_CHANGE','SUPPLY_CHANGE','PROMOTION','STOCK_ALERT','GENERAL') NOT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('UNREAD','READ') NOT NULL DEFAULT 'UNREAD',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `fk_notifications_organization` (`organization_id`),
  KEY `fk_notifications_user` (`user_id`),
  CONSTRAINT `fk_notifications_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`relationship_id`),
  KEY `fk_relationship_source` (`source_organization_id`),
  KEY `fk_relationship_target` (`target_organization_id`),
  CONSTRAINT `fk_relationship_source` FOREIGN KEY (`source_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_relationship_target` FOREIGN KEY (`target_organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`organization_id`),
  UNIQUE KEY `kra_pin` (`kra_pin`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_supplier_id`),
  UNIQUE KEY `uq_product_supplier_start` (`product_id`,`supplier_id`,`start_date`),
  KEY `fk_product_suppliers_supplier` (`supplier_id`),
  CONSTRAINT `fk_product_suppliers_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_product_suppliers_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  UNIQUE KEY `uq_product_barcode` (`barcode`),
  UNIQUE KEY `uq_one_current_version` (`current_version_key`),
  CONSTRAINT `fk_product_versions_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `normal_price` decimal(12,2) NOT NULL,
  `promotion_price` decimal(12,2) NOT NULL,
  `compensation_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promotion_product_id`),
  UNIQUE KEY `uq_promotion_product` (`promotion_id`,`product_id`),
  KEY `fk_promotion_products_product` (`product_id`),
  CONSTRAINT `fk_promotion_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_promotion_products_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
-- Table structure for table `retailer_stock`
--

DROP TABLE IF EXISTS `retailer_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `retailer_stock` (
  `retailer_stock_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_version_id` int(10) unsigned NOT NULL,
  `quantity_on_hand` int(10) unsigned NOT NULL DEFAULT 0,
  `reorder_level` int(10) unsigned NOT NULL DEFAULT 0,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`retailer_stock_id`),
  UNIQUE KEY `uq_retailer_stock` (`retailer_id`,`product_id`,`product_version_id`),
  KEY `fk_retailer_stock_product` (`product_id`),
  KEY `fk_retailer_stock_version` (`product_version_id`),
  KEY `idx_retailer_stock_branch` (`branch_id`),
  CONSTRAINT `fk_retailer_stock_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_retailer_stock_version` FOREIGN KEY (`product_version_id`) REFERENCES `product_versions` (`product_version_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `sale_date` date NOT NULL,
  `units_sold` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sales_history_id`),
  KEY `fk_sales_history_retailer` (`retailer_id`),
  KEY `fk_sales_history_product` (`product_id`),
  KEY `idx_sales_history_branch` (`branch_id`),
  CONSTRAINT `fk_sales_history_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_history_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `current_stock` int(10) unsigned NOT NULL,
  `reorder_level` int(10) unsigned NOT NULL,
  `average_daily_sales` decimal(12,2) NOT NULL,
  `lead_time_days` int(10) unsigned NOT NULL,
  `expected_demand` decimal(12,2) NOT NULL,
  `recommended_order_quantity` int(10) unsigned NOT NULL,
  `alert_type` enum('LOW_STOCK','OUT_OF_STOCK','SUPPLY_UNAVAILABLE') NOT NULL,
  `status` enum('ACTIVE','RESOLVED','DISMISSED') NOT NULL DEFAULT 'ACTIVE',
  `generated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`stock_alert_id`),
  KEY `fk_stock_alerts_retailer` (`retailer_id`),
  KEY `fk_stock_alerts_product` (`product_id`),
  KEY `idx_stock_alerts_branch` (`branch_id`),
  CONSTRAINT `fk_stock_alerts_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_alerts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_alerts_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  UNIQUE KEY `uq_supplier_area` (`supplier_id`,`area_id`,`start_date`),
  KEY `fk_supplier_area_area` (`area_id`),
  CONSTRAINT `fk_supplier_area_area` FOREIGN KEY (`area_id`) REFERENCES `distribution_areas` (`area_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supplier_area_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
-- Table structure for table `supply_order_items`
--

DROP TABLE IF EXISTS `supply_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_order_items` (
  `supply_order_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supply_order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity_ordered` int(10) unsigned NOT NULL,
  `quantity_received` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supply_order_item_id`),
  UNIQUE KEY `uq_supply_order_product` (`supply_order_id`,`product_id`),
  KEY `fk_supply_order_items_product` (`product_id`),
  CONSTRAINT `fk_supply_order_items_order` FOREIGN KEY (`supply_order_id`) REFERENCES `supply_orders` (`supply_order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supply_orders`
--

DROP TABLE IF EXISTS `supply_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supply_orders` (
  `supply_order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `retailer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','CONFIRMED','PARTIALLY_DELIVERED','DELIVERED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supply_order_id`),
  KEY `fk_supply_orders_retailer` (`retailer_id`),
  KEY `fk_supply_orders_supplier` (`supplier_id`),
  KEY `fk_supply_orders_user` (`created_by`),
  KEY `idx_supply_orders_branch` (`branch_id`),
  CONSTRAINT `fk_supply_orders_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_retailer` FOREIGN KEY (`retailer_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_supply_orders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `role` enum('ADMIN','STAFF') NOT NULL DEFAULT 'STAFF',
  `status` enum('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_branch` (`branch_id`),
  KEY `fk_users_branch_org` (`organization_id`,`branch_id`),
  CONSTRAINT `fk_users_branch_org` FOREIGN KEY (`organization_id`, `branch_id`) REFERENCES `branches` (`organization_id`, `branch_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`organization_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-17 11:14:27
