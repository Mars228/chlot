
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
DROP TABLE IF EXISTS `bet_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bet_batches` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `stype` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SIMPLE',
  `schema_id` int unsigned DEFAULT NULL,
  `strategy_id_from` int unsigned DEFAULT NULL,
  `strategy_id_to` int unsigned DEFAULT NULL,
  `last_n` int DEFAULT NULL,
  `per_strategy` int NOT NULL DEFAULT '1',
  `include_random_baseline` tinyint(1) NOT NULL DEFAULT '1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'done',
  `total_strategies` int NOT NULL DEFAULT '0',
  `total_tickets` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `processed_strategies` int NOT NULL DEFAULT '0',
  `processed_tickets` int NOT NULL DEFAULT '0',
  `error_msg` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_batches_game` (`game_id`),
  KEY `idx_batches_schema` (`schema_id`),
  KEY `idx_batches_status` (`status`),
  KEY `idx_batches_created_at` (`created_at`),
  CONSTRAINT `fk_bb_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bb_schema` FOREIGN KEY (`schema_id`) REFERENCES `stat_schemas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bet_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bet_results` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int unsigned NOT NULL,
  `batch_id` int unsigned NOT NULL,
  `game_id` int unsigned NOT NULL,
  `strategy_id` int unsigned NOT NULL,
  `is_baseline` tinyint(1) NOT NULL DEFAULT '0',
  `next_draw_system_id` int unsigned NOT NULL,
  `evaluation_draw_system_id` int unsigned NOT NULL,
  `hits_a` int DEFAULT NULL,
  `hits_b` int DEFAULT NULL,
  `k_a` int DEFAULT NULL,
  `k_b` int DEFAULT NULL,
  `win_amount` decimal(12,2) DEFAULT NULL,
  `win_factor` decimal(8,4) DEFAULT NULL,
  `win_currency` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prize_label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_winner` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ticket_eval` (`ticket_id`,`evaluation_draw_system_id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_game` (`game_id`),
  KEY `idx_eval` (`evaluation_draw_system_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bet_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bet_tickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` int unsigned NOT NULL,
  `game_id` int unsigned NOT NULL,
  `strategy_id` int unsigned NOT NULL,
  `k_a` int DEFAULT NULL,
  `k_b` int DEFAULT NULL,
  `hot_count_a` int DEFAULT NULL,
  `cold_count_a` int DEFAULT NULL,
  `hot_count_b` int DEFAULT NULL,
  `cold_count_b` int DEFAULT NULL,
  `is_baseline` tinyint(1) NOT NULL DEFAULT '0',
  `numbers_a` text COLLATE utf8mb4_unicode_ci,
  `numbers_b` text COLLATE utf8mb4_unicode_ci,
  `next_draw_system_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tickets_batch` (`batch_id`),
  KEY `idx_tickets_strategy` (`strategy_id`),
  KEY `idx_tickets_game` (`game_id`),
  KEY `idx_tickets_next_draw` (`next_draw_system_id`),
  KEY `idx_tickets_created_at` (`created_at`),
  CONSTRAINT `fk_bt_batch` FOREIGN KEY (`batch_id`) REFERENCES `bet_batches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bt_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bt_strategy` FOREIGN KEY (`strategy_id`) REFERENCES `strategies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9493608 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `draw_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `draw_results` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `draw_system_id` bigint DEFAULT NULL,
  `external_draw_no` bigint DEFAULT NULL,
  `draw_date` date NOT NULL,
  `draw_time` time NOT NULL,
  `numbers_a` text COLLATE utf8mb4_general_ci NOT NULL,
  `numbers_b` text COLLATE utf8mb4_general_ci,
  `source` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'csv',
  `raw_json` longtext COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id_draw_system_id` (`game_id`,`draw_system_id`),
  KEY `game_id_draw_date` (`game_id`,`draw_date`),
  CONSTRAINT `draw_results_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_pick_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_pick_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `code` varchar(1) COLLATE utf8mb4_general_ci NOT NULL,
  `range_min` int NOT NULL,
  `range_max` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_pick_groups_game_id_foreign` (`game_id`),
  CONSTRAINT `game_pick_groups_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `game_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `game_variants` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `picks_a_min` int DEFAULT NULL,
  `picks_a_max` int DEFAULT NULL,
  `picks_b_min` int DEFAULT NULL,
  `picks_b_max` int DEFAULT NULL,
  `price` decimal(8,2) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_variants_game_id_foreign` (`game_id`),
  CONSTRAINT `game_variants_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `games` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `logo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `default_price` decimal(8,2) DEFAULT NULL,
  `range_a_min` int DEFAULT NULL,
  `range_a_max` int DEFAULT NULL,
  `picks_a_min` int DEFAULT NULL,
  `picks_a_max` int DEFAULT NULL,
  `range_b_min` int DEFAULT NULL,
  `range_b_max` int DEFAULT NULL,
  `picks_b_min` int DEFAULT NULL,
  `picks_b_max` int DEFAULT NULL,
  `payout_schema_json` longtext COLLATE utf8mb4_general_ci,
  `draw_no_transform_json` longtext COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prize_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prize_tiers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_variant_id` int unsigned NOT NULL,
  `matched_a` int DEFAULT NULL,
  `matched_b` int DEFAULT NULL,
  `payout_type` enum('fixed','coefficient') COLLATE utf8mb4_general_ci NOT NULL,
  `value` decimal(12,4) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prize_tiers_game_variant_id_foreign` (`game_variant_id`),
  CONSTRAINT `prize_tiers_game_variant_id_foreign` FOREIGN KEY (`game_variant_id`) REFERENCES `game_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stat_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `scheme` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `from_draw_system_id` bigint unsigned DEFAULT NULL,
  `from_draw_date` date DEFAULT NULL,
  `from_draw_time` time DEFAULT NULL,
  `params_json` longtext COLLATE utf8mb4_general_ci,
  `progress` int NOT NULL DEFAULT '0',
  `total` int NOT NULL DEFAULT '0',
  `status` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'queued',
  `message` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `result_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id_scheme` (`game_id`,`scheme`),
  CONSTRAINT `stat_jobs_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stat_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_results` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `schema_id` int unsigned DEFAULT NULL,
  `game_id` int unsigned NOT NULL,
  `scheme` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `from_draw_system_id` bigint unsigned DEFAULT NULL,
  `met_at_draw_system_id` bigint unsigned DEFAULT NULL,
  `met_at_draw_system_id_a` bigint unsigned DEFAULT NULL,
  `met_at_draw_system_id_b` bigint unsigned DEFAULT NULL,
  `window_draws` int DEFAULT NULL,
  `window_draws_a` int DEFAULT NULL,
  `window_draws_b` int DEFAULT NULL,
  `target_repeats` int DEFAULT NULL,
  `criteria_a` longtext COLLATE utf8mb4_general_ci,
  `criteria_b` longtext COLLATE utf8mb4_general_ci,
  `top_k_a` int DEFAULT NULL,
  `top_k_b` int DEFAULT NULL,
  `numbers_in_a` text COLLATE utf8mb4_general_ci,
  `numbers_in_b` text COLLATE utf8mb4_general_ci,
  `counts_a` longtext COLLATE utf8mb4_general_ci,
  `counts_b` longtext COLLATE utf8mb4_general_ci,
  `hot_a` text COLLATE utf8mb4_general_ci,
  `cold_a` text COLLATE utf8mb4_general_ci,
  `hot_b` text COLLATE utf8mb4_general_ci,
  `cold_b` text COLLATE utf8mb4_general_ci,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id_scheme` (`game_id`,`scheme`),
  CONSTRAINT `stat_results_game_id_foreign` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stat_schemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stat_schemas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `scheme` varchar(20) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `params_json` longtext NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'idle',
  `first_met_from_draw` bigint DEFAULT NULL,
  `current_from_draw` bigint DEFAULT NULL,
  `processed_since_first` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_game_scheme` (`game_id`,`scheme`),
  KEY `idx_stat_schemas_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `strategies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `strategies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int unsigned NOT NULL,
  `schema_id` int unsigned DEFAULT NULL,
  `stat_result_id` int unsigned NOT NULL,
  `from_draw_system_id` bigint NOT NULL,
  `next_draw_system_id` bigint NOT NULL,
  `stype` varchar(20) NOT NULL DEFAULT 'SIMPLE',
  `hot_count_a` tinyint DEFAULT NULL,
  `cold_count_a` tinyint DEFAULT NULL,
  `hot_even_a` tinyint DEFAULT NULL,
  `hot_odd_a` tinyint DEFAULT NULL,
  `cold_even_a` tinyint DEFAULT NULL,
  `cold_odd_a` tinyint DEFAULT NULL,
  `hits_hot_a` text,
  `hits_cold_a` text,
  `hot_count_b` tinyint DEFAULT NULL,
  `cold_count_b` tinyint DEFAULT NULL,
  `hot_even_b` tinyint DEFAULT NULL,
  `hot_odd_b` tinyint DEFAULT NULL,
  `cold_even_b` tinyint DEFAULT NULL,
  `cold_odd_b` tinyint DEFAULT NULL,
  `hits_hot_b` text,
  `hits_cold_b` text,
  `recommend_a_json` longtext,
  `recommend_b_json` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_stat_result` (`stat_result_id`),
  KEY `idx_game_schema` (`game_id`,`schema_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49026 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

