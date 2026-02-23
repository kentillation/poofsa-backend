-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Feb 23, 2026 at 12:54 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `poofsa-uat`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_01_31_155525_create_tbl_ingredients_table', 1),
(6, '2026_01_31_160305_create_tbl_stock_batches_table', 1),
(7, '2026_01_31_160440_create_tbl_stock_movements_table', 1),
(8, '2026_01_31_190454_create_tbl_products_table', 1),
(9, '2026_01_31_190831_create_tbl_product_items_table', 1),
(10, '2026_01_31_191424_create_tbl_product_variants_table', 1),
(11, '2026_01_31_191838_create_tbl_product_prices_table', 1),
(12, '2026_01_31_192321_create_tbl_sales_table', 1),
(13, '2026_01_31_192949_create_tbl_sale_items_table', 1),
(14, '2026_01_31_193209_create_tbl_orders_table', 1),
(15, '2026_01_31_194216_create_tbl_order_items_table', 1),
(16, '2026_01_31_201144_create_tbl_admin_table', 1),
(17, '2026_01_31_201531_create_tbl_shops_table', 1),
(18, '2026_01_31_201830_create_tbl_shop_branch_table', 1),
(19, '2026_01_31_202304_create_tbl_barista_table', 1),
(20, '2026_01_31_202500_create_tbl_cashier_table', 1),
(21, '2026_01_31_202830_create_tbl_kitchen_personnel_table', 1),
(22, '2026_01_31_205028_create_tbl_product_category_table', 1),
(23, '2026_01_31_205336_create_tbl_dev_table', 1),
(24, '2026_01_31_205950_create_tbl_payment_table', 1),
(25, '2026_01_31_210446_create_tbl_payment_method_table', 1),
(26, '2026_01_31_211400_create_tbl_orders_void_table', 1),
(27, '2026_02_17_231622_create_tbl_product_size_table', 2),
(28, '2026_02_17_232334_create_tbl_product_temp_table', 3),
(29, '2026_02_17_233117_create_tbl_product_availability_table', 4),
(30, '2026_02_17_233628_create_tbl_shop_station_table', 5),
(31, '2026_02_18_103117_create_tbl_ingredient_unit_table', 6),
(32, '2026_02_18_104307_create_tbl_ingredient_availability_table', 7),
(33, '2026_02_17_233117_create_tbl_availability_table', 8),
(34, '2026_02_18_110952_create_tbl_void_status_table', 8),
(35, '2026_02_18_111334_create_tbl_void_orders_table', 8),
(36, '2026_02_18_114216_create_tbl_order_status_table', 9),
(37, '2026_02_19_235517_create_tbl_movement_type_table', 9),
(38, '2026_02_20_004253_create_tbl_sales_status_table', 10),
(39, '2026_02_20_010859_create_tbl_order_type_table', 11),
(40, '2026_02_20_120508_create_tbl_station_status_table', 12),
(41, '2026_02_20_140215_create_tbl_product_history_table', 13),
(42, '2026_02_20_141142_create_tbl_products_history_table', 14);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokenable_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\DevModel', 2, 'auth-token', '9bbb865c7869269f29e091f98fca7b4c34409ad34528999ea3560d50e0f579d0', '[\"*\"]', NULL, NULL, '2026-02-17 11:31:32', '2026-02-17 11:31:32'),
(2, 'App\\Models\\AdminModel', 1, 'auth-token', '0a601077ec5c3d03cd0355257fb33f71e2d01173ed5ae74296b92677cf3ff1be', '[\"*\"]', NULL, NULL, '2026-02-17 13:01:51', '2026-02-17 13:01:51'),
(5, 'App\\Models\\AdminModel', 1, 'auth_token', 'bd20b8648564a52616a5b692376650ecc8b1047656fd76df47cc2d136c496638', '[\"*\"]', '2026-02-18 13:30:23', '2026-02-25 12:54:16', '2026-02-18 12:54:16', '2026-02-18 13:30:23'),
(13, 'App\\Models\\AdminModel', 1, 'auth_token', '1efdcd50357b2d3d85d5aced2df410f9ba0cae33724184e3e74fddbec75c75a7', '[\"*\"]', '2026-02-20 06:19:47', '2026-02-27 02:50:46', '2026-02-20 02:50:46', '2026-02-20 06:19:47'),
(14, 'App\\Models\\AdminModel', 1, 'auth_token', '737ac3fd0c52bbb873e0ccfe0f90dfee948d5946c85d02b454bdba9d9799fb5f', '[\"*\"]', '2026-02-20 14:03:30', '2026-02-27 12:33:41', '2026-02-20 12:33:41', '2026-02-20 14:03:30'),
(19, 'App\\Models\\AdminModel', 1, 'auth_token', 'bc1eb1cb03962ba1a4268e16474a97920f6cf1356b54e1fef23ee0fa48e54e55', '[\"*\"]', '2026-02-22 11:43:00', '2026-03-01 04:00:19', '2026-02-22 04:00:19', '2026-02-22 11:43:00');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

DROP TABLE IF EXISTS `tbl_admin`;
CREATE TABLE IF NOT EXISTS `tbl_admin` (
  `admin_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_mpin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `role` enum('admin','superadmin','manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `tbl_admin_admin_email_unique` (`admin_email`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`admin_id`, `admin_name`, `admin_email`, `admin_password`, `admin_mpin`, `shop_id`, `role`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Juan Dela Cruz', 'juan@test.com', '$2y$12$yE1Jzh67GT.Gtm968pRUge4Kd.0JXwRYX6jOxI8MKeCJGNwMmjeYu', '$2y$12$7njDtfS0u9IGfFxqbnj83uujmlapcfudjsA3cW74BQCnYPIAP7MyC', 1, 'admin', 1, '2026-02-17 13:01:48', '2026-02-17 13:01:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_availability`
--

DROP TABLE IF EXISTS `tbl_availability`;
CREATE TABLE IF NOT EXISTS `tbl_availability` (
  `availability_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `availability_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`availability_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_availability`
--

INSERT INTO `tbl_availability` (`availability_id`, `availability_label`, `created_at`, `updated_at`) VALUES
(1, 'Available', '2026-02-19 01:53:35', '2026-02-19 01:53:35'),
(2, 'Unavailable', '2026-02-19 01:53:35', '2026-02-19 01:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_barista`
--

DROP TABLE IF EXISTS `tbl_barista`;
CREATE TABLE IF NOT EXISTS `tbl_barista` (
  `barista_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `barista_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barista_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barista_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barista_mpin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`barista_id`),
  UNIQUE KEY `tbl_barista_barista_email_unique` (`barista_email`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_barista`
--

INSERT INTO `tbl_barista` (`barista_id`, `barista_name`, `barista_email`, `barista_password`, `barista_mpin`, `shop_id`, `branch_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ezra Bayot', 'ezra@test.com', '$2y$12$n.HTUKD2I.4PkvtGp04hNelMk7Y730jT/7USdWdKhVFsC5OmkiRs6', '$2y$12$SLcBCT0lBU.ySeqDlDmfFexJiRPZR03cwHIGeULu6cV51kkk93UMy', 1, 1, 1, '2026-02-17 13:01:51', '2026-02-17 13:01:51');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cashier`
--

DROP TABLE IF EXISTS `tbl_cashier`;
CREATE TABLE IF NOT EXISTS `tbl_cashier` (
  `cashier_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `cashier_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cashier_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cashier_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cashier_mpin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cashier_id`),
  UNIQUE KEY `tbl_cashier_cashier_email_unique` (`cashier_email`),
  UNIQUE KEY `tbl_cashier_cashier_mpin_unique` (`cashier_mpin`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_cashier`
--

INSERT INTO `tbl_cashier` (`cashier_id`, `cashier_name`, `cashier_email`, `cashier_password`, `cashier_mpin`, `shop_id`, `branch_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Mysty Bayot', 'mysty@test.com', '$2y$12$yBy3.9i1ZG2wBdlYPwwULu9wIg.C.EnTWm7aziT8vW3yf60N.NmTm', '$2y$12$jj.X8nhg/qVHDqi57XmRIOvGPxeBczPVZvGS0rtxsGDfUZpJfXJ0.', 1, 1, 1, '2026-02-17 13:01:49', '2026-02-17 13:01:49');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_dev`
--

DROP TABLE IF EXISTS `tbl_dev`;
CREATE TABLE IF NOT EXISTS `tbl_dev` (
  `dev_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `dev_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dev_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dev_password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`dev_id`),
  UNIQUE KEY `tbl_dev_dev_email_unique` (`dev_email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_dev`
--

INSERT INTO `tbl_dev` (`dev_id`, `dev_name`, `dev_email`, `dev_password`, `created_at`, `updated_at`) VALUES
(1, 'Kent Anthony Engbino', 'founder@poofsa.com', '$2y$12$7FBBhkCrTRD0eF3TgvtR2OlYfgzllpqR99Bhum7g95SSU.a7LIFDy', '2026-02-17 11:31:32', '2026-02-17 11:31:32');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ingredients`
--

DROP TABLE IF EXISTS `tbl_ingredients`;
CREATE TABLE IF NOT EXISTS `tbl_ingredients` (
  `ingredient_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_unit_id` bigint UNSIGNED NOT NULL,
  `alert_quantity` decimal(10,3) NOT NULL,
  `availability_id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ingredient_id`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`),
  KEY `availability_id` (`availability_id`),
  KEY `base_unit_id` (`base_unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_ingredients`
--

INSERT INTO `tbl_ingredients` (`ingredient_id`, `ingredient_name`, `base_unit_id`, `alert_quantity`, `availability_id`, `shop_id`, `branch_id`, `created_at`, `updated_at`) VALUES
(1, 'Coffee Beans', 1, 10.000, 1, 1, 1, '2026-02-19 00:13:39', '2026-02-19 00:13:39'),
(2, 'Brown Sugar', 2, 10.000, 1, 1, 1, '2026-02-19 02:13:39', '2026-02-19 02:13:39');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ingredient_unit`
--

DROP TABLE IF EXISTS `tbl_ingredient_unit`;
CREATE TABLE IF NOT EXISTS `tbl_ingredient_unit` (
  `ingredient_unit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_avb` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ingredient_unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_ingredient_unit`
--

INSERT INTO `tbl_ingredient_unit` (`ingredient_unit_id`, `unit_label`, `unit_avb`, `created_at`, `updated_at`) VALUES
(1, 'grams', 'g', '2026-02-18 02:35:57', '2026-02-18 02:35:57'),
(2, 'milliliter', 'ml', '2026-02-18 02:35:57', '2026-02-18 02:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kitchen_personnel`
--

DROP TABLE IF EXISTS `tbl_kitchen_personnel`;
CREATE TABLE IF NOT EXISTS `tbl_kitchen_personnel` (
  `kitchen_personnel_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `kitchen_personnel_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kitchen_personnel_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kitchen_personnel_password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kitchen_personnel_mpin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`kitchen_personnel_id`),
  UNIQUE KEY `tbl_kitchen_personnel_kitchen_personnel_email_unique` (`kitchen_personnel_email`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_kitchen_personnel`
--

INSERT INTO `tbl_kitchen_personnel` (`kitchen_personnel_id`, `kitchen_personnel_name`, `kitchen_personnel_email`, `kitchen_personnel_password`, `kitchen_personnel_mpin`, `shop_id`, `branch_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Kirt Bayot', 'kirt@test.com', '$2y$12$WP0k6.iAUvNp.JEom1ftAOxWW71iR7w7EnPTIRDb3Z5pSTqNKqYjO', '$2y$12$MZmZUjT0FFoDXDLzDYuAN.i1gE5e2NCKaZYDHaH2z4hFjTOPYKm/O', 1, 1, 1, '2026-02-17 13:01:50', '2026-02-17 13:01:50');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_movement_type`
--

DROP TABLE IF EXISTS `tbl_movement_type`;
CREATE TABLE IF NOT EXISTS `tbl_movement_type` (
  `movement_type_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `movement_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`movement_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_movement_type`
--

INSERT INTO `tbl_movement_type` (`movement_type_id`, `movement_type`, `created_at`, `updated_at`) VALUES
(1, 'In', '2026-02-19 15:57:55', '2026-02-19 15:57:55'),
(2, 'Out', '2026-02-19 15:57:55', '2026-02-19 15:57:55'),
(3, 'Waste', '2026-02-19 15:57:55', '2026-02-19 15:57:55'),
(4, 'Adjustment', '2026-02-19 15:57:55', '2026-02-19 15:57:55');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_orders`
--

DROP TABLE IF EXISTS `tbl_orders`;
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `order_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_cash` decimal(10,2) NOT NULL,
  `customer_change` decimal(10,2) NOT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_type_id` bigint UNSIGNED NOT NULL,
  `order_status_id` bigint UNSIGNED NOT NULL,
  `table_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_quantity` int NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `tbl_orders_order_number_unique` (`order_number`),
  UNIQUE KEY `tbl_orders_reference_number_unique` (`reference_number`),
  KEY `order_status_id` (`order_status_id`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`),
  KEY `user_id` (`user_id`),
  KEY `order_type_id` (`order_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_items`
--

DROP TABLE IF EXISTS `tbl_order_items`;
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `order_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `shop_station_id` bigint UNSIGNED NOT NULL,
  `station_status_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `tbl_order_items_order_id_foreign` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_status`
--

DROP TABLE IF EXISTS `tbl_order_status`;
CREATE TABLE IF NOT EXISTS `tbl_order_status` (
  `order_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_order_status`
--

INSERT INTO `tbl_order_status` (`order_status_id`, `order_status`, `created_at`, `updated_at`) VALUES
(1, 'Pending', '2026-02-19 15:56:18', '2026-02-19 15:56:18'),
(2, 'Confirmed', '2026-02-19 15:56:18', '2026-02-19 15:56:18'),
(3, 'Completed', '2026-02-19 15:56:46', '2026-02-19 15:56:46'),
(4, 'Cancelled', '2026-02-19 15:56:59', '2026-02-19 15:56:59');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_order_type`
--

DROP TABLE IF EXISTS `tbl_order_type`;
CREATE TABLE IF NOT EXISTS `tbl_order_type` (
  `order_type_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_order_type`
--

INSERT INTO `tbl_order_type` (`order_type_id`, `order_type`, `created_at`, `updated_at`) VALUES
(1, 'Dine-in', '2026-02-19 17:09:54', '2026-02-19 17:09:54'),
(2, 'Take-out', '2026-02-19 17:09:54', '2026-02-19 17:09:54'),
(3, 'Delivery', '2026-02-19 17:09:54', '2026-02-19 17:09:54');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment`
--

DROP TABLE IF EXISTS `tbl_payment`;
CREATE TABLE IF NOT EXISTS `tbl_payment` (
  `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_intent_id` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paymongo_payment_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_method`
--

DROP TABLE IF EXISTS `tbl_payment_method`;
CREATE TABLE IF NOT EXISTS `tbl_payment_method` (
  `payment_method_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_method_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payment_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_payment_method`
--

INSERT INTO `tbl_payment_method` (`payment_method_id`, `payment_method_name`, `created_at`, `updated_at`) VALUES
(1, 'Cash', '2026-02-19 16:34:14', '2026-02-19 16:34:14'),
(2, 'eWallet', '2026-02-19 16:34:14', '2026-02-19 16:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

DROP TABLE IF EXISTS `tbl_products`;
CREATE TABLE IF NOT EXISTS `tbl_products` (
  `product_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `cost_estimate` decimal(10,2) DEFAULT NULL,
  `sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_id` bigint UNSIGNED NOT NULL,
  `temp_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `availability_id` bigint UNSIGNED NOT NULL,
  `station_id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `tbl_products_category_id_index` (`category_id`),
  KEY `tbl_products_station_id_index` (`station_id`),
  KEY `tbl_products_shop_id_index` (`shop_id`),
  KEY `tbl_products_branch_id_index` (`branch_id`),
  KEY `tbl_products_user_id_index` (`user_id`),
  KEY `size_id` (`size_id`),
  KEY `temp_id` (`temp_id`),
  KEY `availability_id` (`availability_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`product_id`, `product_name`, `base_price`, `cost_estimate`, `sku`, `size_id`, `temp_id`, `category_id`, `is_active`, `availability_id`, `station_id`, `shop_id`, `branch_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Iced coffee', 35.00, 15.00, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-02-20 05:06:11', '2026-02-21 06:09:54'),
(2, 'Hot Coffee', 25.00, 0.00, NULL, 1, 2, 1, 1, 1, 1, 1, 1, 1, '2026-02-21 06:23:56', '2026-02-23 09:18:04'),
(3, 'Hot Cappuccino', 40.00, 0.00, NULL, 1, 2, 1, 1, 1, 1, 1, 1, 1, '2026-02-21 06:47:33', '2026-02-21 13:44:52'),
(4, 'Iced Caramel Macchiato', 55.00, 0.00, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, '2026-02-21 06:47:33', '2026-02-21 13:44:34'),
(5, 'Siomai (steamed)', 25.00, 0.00, NULL, 5, 3, 2, 1, 1, 4, 1, 1, 1, '2026-02-21 13:53:00', '2026-02-21 16:16:59'),
(6, 'Siomai (fried)', 25.00, 0.00, NULL, 5, 3, 2, 1, 1, 4, 1, 1, 1, '2026-02-21 13:53:00', '2026-02-21 16:30:25'),
(7, 'Siopao', 45.00, 30.00, NULL, 5, 2, 2, 1, 1, 4, 1, 1, 1, '2026-02-21 13:59:30', '2026-02-23 09:06:44');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products_history`
--

DROP TABLE IF EXISTS `tbl_products_history`;
CREATE TABLE IF NOT EXISTS `tbl_products_history` (
  `product_history_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint UNSIGNED NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `manage_id` bigint UNSIGNED NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_history_id`),
  KEY `tbl_products_history_manage_id_index` (`manage_id`),
  KEY `tbl_products_history_shop_id_index` (`shop_id`),
  KEY `tbl_products_history_branch_id_index` (`branch_id`),
  KEY `tbl_products_history_user_id_index` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_products_history`
--

INSERT INTO `tbl_products_history` (`product_history_id`, `product_id`, `description`, `manage_id`, `shop_id`, `branch_id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'No fields were updated', 2, 1, 1, 1, '2026-02-20 06:12:46', '2026-02-20 06:12:46'),
(2, 1, 'Product name: From [₱Iced Coffeeeee] To [₱Iced Coffee].', 2, 1, 1, 1, '2026-02-20 06:14:58', '2026-02-20 06:14:58'),
(3, 1, 'Quantity required: From [2.500] To [2.600]. Ingredient capital: From [₱5.000] To [₱6].', 2, 1, 1, 1, '2026-02-20 13:37:08', '2026-02-20 13:37:08'),
(4, 1, 'Quantity required: From [2.600] To [2.500]. Ingredient capital: From [₱6.000] To [₱5].', 2, 1, 1, 1, '2026-02-20 13:57:36', '2026-02-20 13:57:36'),
(5, 1, 'Product name: From [Iced Coffee] To [Iced Coffeeeee].', 2, 1, 1, 1, '2026-02-21 05:39:57', '2026-02-21 05:39:57'),
(6, 1, 'Base price: From [₱25.00] To [₱26].', 2, 1, 1, 1, '2026-02-21 05:40:32', '2026-02-21 05:40:32'),
(7, 1, 'Temperature: From [-ICED] To [-].', 2, 1, 1, 1, '2026-02-21 05:41:04', '2026-02-21 05:41:04'),
(8, 1, 'Product name: From [Iced Coffeeeee] To [Iced coffeeeee]. Size: From [-R] To [-S].', 2, 1, 1, 1, '2026-02-21 05:41:33', '2026-02-21 05:41:33'),
(9, 1, 'Station    : From [Barista] To [Kitchen].', 2, 1, 1, 1, '2026-02-21 05:42:41', '2026-02-21 05:42:41'),
(10, 1, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 05:43:15', '2026-02-21 05:43:15'),
(11, 1, 'Product name: From [Iced coffeeeee] To [Iced coffee]. Base price: From [₱26.00] To [₱25]. Temperature: From [-] To [-ICED]. Size: From [-S] To [-R]. Station    : From [Kitchen] To [Barista]. Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 05:54:21', '2026-02-21 05:54:21'),
(12, 1, 'Size: From [-R] To [-S].', 2, 1, 1, 1, '2026-02-21 05:55:01', '2026-02-21 05:55:01'),
(13, 1, 'Size: From [-S] To [-R].', 2, 1, 1, 1, '2026-02-21 05:55:15', '2026-02-21 05:55:15'),
(14, 1, 'Base price: From [₱25.00] To [₱26.00].', 2, 1, 1, 1, '2026-02-21 05:57:19', '2026-02-21 05:57:19'),
(15, 1, 'Estimated cost: From [₱15.00] To [₱16.00].', 2, 1, 1, 1, '2026-02-21 05:57:34', '2026-02-21 05:57:34'),
(16, 1, 'Estimated cost: From [₱16.00] To [₱15.00].', 2, 1, 1, 1, '2026-02-21 05:57:55', '2026-02-21 05:57:55'),
(17, 1, 'Base price: From [₱26.00] To [₱25.00].', 2, 1, 1, 1, '2026-02-21 05:58:12', '2026-02-21 05:58:12'),
(18, 1, 'Base price: From [₱25.00] To [₱26.00].', 2, 1, 1, 1, '2026-02-21 05:58:56', '2026-02-21 05:58:56'),
(19, 1, 'Estimated cost: From [₱15.00] To [₱16.00].', 2, 1, 1, 1, '2026-02-21 05:59:13', '2026-02-21 05:59:13'),
(20, 1, 'Base price: From [₱26.00] To [₱25.00]. Estimated cost: From [₱16.00] To [₱15.00].', 2, 1, 1, 1, '2026-02-21 05:59:31', '2026-02-21 05:59:31'),
(21, 1, 'Base price: From [₱25.00] To [₱35.00].', 2, 1, 1, 1, '2026-02-21 06:09:54', '2026-02-21 06:09:54'),
(22, 2, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 06:23:56', '2026-02-21 06:23:56'),
(23, 2, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 06:42:33', '2026-02-21 06:42:33'),
(24, 3, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 06:47:33', '2026-02-21 06:47:33'),
(25, 4, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 06:47:33', '2026-02-21 06:47:33'),
(26, 4, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 13:44:34', '2026-02-21 13:44:34'),
(27, 3, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 13:44:42', '2026-02-21 13:44:42'),
(28, 3, 'No fields were updated', 2, 1, 1, 1, '2026-02-21 13:44:52', '2026-02-21 13:44:52'),
(29, 2, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 13:45:04', '2026-02-21 13:45:04'),
(30, 5, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 13:53:00', '2026-02-21 13:53:00'),
(31, 6, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 13:53:00', '2026-02-21 13:53:00'),
(32, 7, 'New Product Saved', 1, 1, 1, 1, '2026-02-21 13:59:30', '2026-02-21 13:59:30'),
(33, 6, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:00:27', '2026-02-21 14:00:27'),
(34, 5, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:00:35', '2026-02-21 14:00:35'),
(35, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:00:42', '2026-02-21 14:00:42'),
(36, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 14:06:55', '2026-02-21 14:06:55'),
(37, 5, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 14:07:11', '2026-02-21 14:07:11'),
(38, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:07:41', '2026-02-21 14:07:41'),
(39, 5, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:09:06', '2026-02-21 14:09:06'),
(40, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 14:09:16', '2026-02-21 14:09:16'),
(41, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 14:32:27', '2026-02-21 14:32:27'),
(42, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 14:37:32', '2026-02-21 14:37:32'),
(43, 5, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 14:38:36', '2026-02-21 14:38:36'),
(44, 5, 'No fields were updated', 2, 1, 1, 1, '2026-02-21 14:57:25', '2026-02-21 14:57:25'),
(45, 7, 'No fields were updated', 2, 1, 1, 1, '2026-02-21 15:03:12', '2026-02-21 15:03:12'),
(46, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 15:12:36', '2026-02-21 15:12:36'),
(47, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 15:22:40', '2026-02-21 15:22:40'),
(48, 5, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 15:32:04', '2026-02-21 15:32:04'),
(49, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 15:32:29', '2026-02-21 15:32:29'),
(50, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 15:33:01', '2026-02-21 15:33:01'),
(51, 5, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 15:36:52', '2026-02-21 15:36:52'),
(52, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 15:37:04', '2026-02-21 15:37:04'),
(53, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 15:38:45', '2026-02-21 15:38:45'),
(54, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 16:15:13', '2026-02-21 16:15:13'),
(55, 5, 'Base price: From [₱25.00] To [₱28.00].', 2, 1, 1, 1, '2026-02-21 16:15:30', '2026-02-21 16:15:30'),
(56, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 16:15:38', '2026-02-21 16:15:38'),
(57, 5, 'Base price: From [₱28.00] To [₱25.00].', 2, 1, 1, 1, '2026-02-21 16:16:59', '2026-02-21 16:16:59'),
(58, 7, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 16:29:55', '2026-02-21 16:29:55'),
(59, 6, 'Availability: From [Available] To [Unavailable].', 2, 1, 1, 1, '2026-02-21 16:30:03', '2026-02-21 16:30:03'),
(60, 6, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 16:30:25', '2026-02-21 16:30:25'),
(61, 7, 'Availability: From [Unavailable] To [Available].', 2, 1, 1, 1, '2026-02-21 16:30:32', '2026-02-21 16:30:32'),
(62, 1, 'Quantity required: From [2.500] To [2.600]. Ingredient capital: From [₱5.000] To [₱6].', 2, 1, 1, 1, '2026-02-22 04:08:00', '2026-02-22 04:08:00'),
(63, 7, 'Base price: From [₱40.00] To [₱45.00]. Estimated cost: From [₱0.00] To [₱30.00].', 2, 1, 1, 1, '2026-02-22 04:41:25', '2026-02-22 04:41:25'),
(64, 1, 'Ingredient capital: From [₱6.000] To [₱5.000]. Quantity required: From [2.600] To [2.500].', 2, 1, 1, 1, '2026-02-22 05:39:02', '2026-02-22 05:39:02'),
(65, 1, 'Ingredient capital: From [₱5.000] To [₱8.000]. Quantity required: From [2.500] To [3.500].', 2, 1, 1, 1, '2026-02-22 05:44:16', '2026-02-22 05:44:16'),
(66, 1, 'Ingredient capital: From [₱8.000] To [₱5.000]. Quantity required: From [3.500] To [2.500].', 2, 1, 1, 1, '2026-02-22 05:46:35', '2026-02-22 05:46:35'),
(67, 1, 'Ingredient capital: From [₱5.000] To [₱6.000]. Quantity required: From [2.500] To [2.600].', 2, 1, 1, 1, '2026-02-22 05:50:22', '2026-02-22 05:50:22'),
(68, 1, 'Ingredient capital: From [₱6.000] To [₱5.000]. Quantity required: From [2.600] To [2.500].', 2, 1, 1, 1, '2026-02-22 05:54:30', '2026-02-22 05:54:30'),
(69, 1, 'Ingredient capital: From [₱5.000] To [₱7.000]. Quantity required: From [2.500] To [2.700].', 2, 1, 1, 1, '2026-02-22 05:59:20', '2026-02-22 05:59:20'),
(70, 1, 'Ingredient capital: From [₱7.000] To [₱5.000]. Quantity required: From [2.700] To [2.500].', 2, 1, 1, 1, '2026-02-23 00:17:51', '2026-02-23 00:17:51'),
(71, 1, 'Ingredient capital: From [₱5.000] To [₱7.000]. Quantity required: From [2.500] To [2.600].', 2, 1, 1, 1, '2026-02-23 00:25:17', '2026-02-23 00:25:17'),
(72, 1, 'Ingredient capital: From [₱7.000] To [₱5.000]. Quantity required: From [2.600] To [2.500].', 2, 1, 1, 1, '2026-02-23 00:27:19', '2026-02-23 00:27:19'),
(73, 1, 'No fields were updated', 2, 1, 1, 1, '2026-02-23 00:28:08', '2026-02-23 00:28:08'),
(74, 1, 'Ingredient capital: From [₱5.000] To [₱6.000]. Quantity required: From [2.500] To [2.600].', 2, 1, 1, 1, '2026-02-23 00:29:46', '2026-02-23 00:29:46'),
(75, 1, 'Ingredient capital: From [₱6.000] To [₱5.000]. Quantity required: From [2.600] To [2.500].', 2, 1, 1, 1, '2026-02-23 00:31:45', '2026-02-23 00:31:45'),
(76, 1, 'Ingredient capital: From [₱5.000] To [₱6.000].', 2, 1, 1, 1, '2026-02-23 00:32:34', '2026-02-23 00:32:34'),
(77, 1, 'Ingredient capital: From [₱6.000] To [₱5.000].', 2, 1, 1, 1, '2026-02-23 00:33:41', '2026-02-23 00:33:41'),
(78, 1, 'Ingredient capital: From [₱5.000] To [₱6.000]. Quantity required: From [2.500] To [2.600].', 2, 1, 1, 1, '2026-02-23 00:35:31', '2026-02-23 00:35:31'),
(79, 7, 'Product name: From [Siopao] To [Siopaooooo].', 2, 1, 1, 1, '2026-02-23 00:49:58', '2026-02-23 00:49:58'),
(80, 7, 'Product name: From [Siopaooooo] To [Siopao].', 2, 1, 1, 1, '2026-02-23 00:50:11', '2026-02-23 00:50:11'),
(81, 7, 'Product name: From [Siopao] To [Siopaoooo].', 2, 1, 1, 1, '2026-02-23 08:28:12', '2026-02-23 08:28:12'),
(82, 7, 'Product name: From [Siopaoooo] To [Siopao].', 2, 1, 1, 1, '2026-02-23 08:30:17', '2026-02-23 08:30:17'),
(83, 7, 'Product name: From [Siopao] To [Siopaooooo].', 2, 1, 1, 1, '2026-02-23 08:32:16', '2026-02-23 08:32:16'),
(84, 7, 'Product name: From [Siopaooooo] To [Siopao].', 2, 1, 1, 1, '2026-02-23 08:37:11', '2026-02-23 08:37:11'),
(85, 7, 'Product name: From [Siopao] To [Siopaooooo].', 2, 1, 1, 1, '2026-02-23 08:44:12', '2026-02-23 08:44:12'),
(86, 7, 'Product name: From [Siopaooooo] To [Siopao].', 2, 1, 1, 1, '2026-02-23 08:46:16', '2026-02-23 08:46:16'),
(87, 7, 'Product name: From [Siopao] To [Siopaoooo].', 2, 1, 1, 1, '2026-02-23 09:06:05', '2026-02-23 09:06:05'),
(88, 7, 'Product name: From [Siopaoooo] To [Siopao].', 2, 1, 1, 1, '2026-02-23 09:06:44', '2026-02-23 09:06:44'),
(89, 2, 'Product name: From [Hot Coffee] To [Hot Coffeeeee].', 2, 1, 1, 1, '2026-02-23 09:08:08', '2026-02-23 09:08:08'),
(90, 2, 'Product name: From [Hot Coffeeeee] To [Hot Coffee].', 2, 1, 1, 1, '2026-02-23 09:09:08', '2026-02-23 09:09:08'),
(91, 2, 'Product name: From [Hot Coffee] To [Hot Coffeeiiii].', 2, 1, 1, 1, '2026-02-23 09:17:01', '2026-02-23 09:17:01'),
(92, 2, 'Product name: From [Hot Coffeeiiii] To [Hot Coffee].', 2, 1, 1, 1, '2026-02-23 09:18:05', '2026-02-23 09:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_category`
--

DROP TABLE IF EXISTS `tbl_product_category`;
CREATE TABLE IF NOT EXISTS `tbl_product_category` (
  `product_category_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_category_id`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_product_category`
--

INSERT INTO `tbl_product_category` (`product_category_id`, `category_label`, `shop_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Coffee', 1, 1, '2026-02-17 15:01:25', '2026-02-17 15:01:25'),
(2, 'Finger Foods', 1, 1, '2026-02-21 13:51:25', '2026-02-21 13:51:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_items`
--

DROP TABLE IF EXISTS `tbl_product_items`;
CREATE TABLE IF NOT EXISTS `tbl_product_items` (
  `product_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint UNSIGNED NOT NULL,
  `ingredient_id` bigint UNSIGNED NOT NULL,
  `ingredient_capital` decimal(10,3) NOT NULL,
  `quantity_required` decimal(10,3) NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_item_id`),
  UNIQUE KEY `tbl_product_items_product_id_ingredient_id_unique` (`product_id`,`ingredient_id`),
  KEY `tbl_product_items_ingredient_id_foreign` (`ingredient_id`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_product_items`
--

INSERT INTO `tbl_product_items` (`product_item_id`, `product_id`, `ingredient_id`, `ingredient_capital`, `quantity_required`, `shop_id`, `branch_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 6.000, 2.600, 1, 1, '2026-02-20 12:44:55', '2026-02-23 00:35:31');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_prices`
--

DROP TABLE IF EXISTS `tbl_product_prices`;
CREATE TABLE IF NOT EXISTS `tbl_product_prices` (
  `price_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `effective_from` timestamp NOT NULL,
  `effective_to` timestamp NULL DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`price_id`),
  KEY `tbl_product_prices_variant_id_foreign` (`variant_id`),
  KEY `tbl_product_prices_product_id_variant_id_effective_to_index` (`product_id`,`variant_id`,`effective_to`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_size`
--

DROP TABLE IF EXISTS `tbl_product_size`;
CREATE TABLE IF NOT EXISTS `tbl_product_size` (
  `product_size_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `size_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_size_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_product_size`
--

INSERT INTO `tbl_product_size` (`product_size_id`, `size_label`, `created_at`, `updated_at`) VALUES
(1, '-R', '2026-02-17 15:19:41', '2026-02-17 15:19:41'),
(2, '-S', '2026-02-17 15:19:41', '2026-02-17 15:19:41'),
(3, '-M', '2026-02-17 15:19:41', '2026-02-17 15:19:41'),
(4, '-L', '2026-02-17 15:19:41', '2026-02-17 15:19:41'),
(5, '-', '2026-02-17 15:19:41', '2026-02-17 15:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_temp`
--

DROP TABLE IF EXISTS `tbl_product_temp`;
CREATE TABLE IF NOT EXISTS `tbl_product_temp` (
  `product_temp_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `temp_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`product_temp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_product_temp`
--

INSERT INTO `tbl_product_temp` (`product_temp_id`, `temp_label`, `created_at`, `updated_at`) VALUES
(1, '-ICED', '2026-02-17 15:25:49', '2026-02-17 15:25:49'),
(2, '-HOT', '2026-02-17 15:25:49', '2026-02-17 15:25:49'),
(3, '-', '2026-02-17 15:26:18', '2026-02-17 15:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_variants`
--

DROP TABLE IF EXISTS `tbl_product_variants`;
CREATE TABLE IF NOT EXISTS `tbl_product_variants` (
  `variant_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_adjustment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`variant_id`),
  KEY `tbl_product_variants_product_id_foreign` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sales`
--

DROP TABLE IF EXISTS `tbl_sales`;
CREATE TABLE IF NOT EXISTS `tbl_sales` (
  `sale_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL,
  `sales_status_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `tbl_sales_receipt_no_unique` (`receipt_no`),
  KEY `tbl_sales_shop_id_index` (`shop_id`),
  KEY `tbl_sales_branch_id_index` (`branch_id`),
  KEY `tbl_sales_user_id_index` (`user_id`),
  KEY `tbl_sales_payment_method_id_index` (`payment_method_id`),
  KEY `tbl_sales_created_at_index` (`created_at`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sales_status`
--

DROP TABLE IF EXISTS `tbl_sales_status`;
CREATE TABLE IF NOT EXISTS `tbl_sales_status` (
  `sales_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sales_status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_sales_status`
--

INSERT INTO `tbl_sales_status` (`sales_status_id`, `sales_status`, `created_at`, `updated_at`) VALUES
(1, 'Paid', '2026-02-19 16:44:15', '2026-02-19 16:44:15'),
(2, 'Void', '2026-02-19 16:44:15', '2026-02-19 16:44:15'),
(3, 'Refund', '2026-02-19 16:44:39', '2026-02-19 16:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_items`
--

DROP TABLE IF EXISTS `tbl_sale_items`;
CREATE TABLE IF NOT EXISTS `tbl_sale_items` (
  `sale_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED DEFAULT NULL,
  `product_name_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_name_snapshot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`sale_item_id`),
  KEY `tbl_sale_items_sale_id_foreign` (`sale_id`),
  KEY `tbl_sale_items_product_id_foreign` (`product_id`),
  KEY `tbl_sale_items_variant_id_foreign` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_shops`
--

DROP TABLE IF EXISTS `tbl_shops`;
CREATE TABLE IF NOT EXISTS `tbl_shops` (
  `shop_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_owner` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_contact_number` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`shop_id`),
  UNIQUE KEY `tbl_shops_shop_email_unique` (`shop_email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_shops`
--

INSERT INTO `tbl_shops` (`shop_id`, `shop_name`, `shop_owner`, `shop_address`, `shop_email`, `shop_contact_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Poofsa PH', 'Kent Anthony', 'Brgy. Paraiso, Sagay City', 'support@poofsa.com', '09453145499', 1, '2026-02-17 13:01:47', '2026-02-17 13:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_shop_branch`
--

DROP TABLE IF EXISTS `tbl_shop_branch`;
CREATE TABLE IF NOT EXISTS `tbl_shop_branch` (
  `branch_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_manager_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_contact_number` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`branch_id`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_shop_branch`
--

INSERT INTO `tbl_shop_branch` (`branch_id`, `shop_id`, `branch_name`, `branch_address`, `branch_manager_name`, `branch_contact_number`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cadiz 01', 'Brgy. Zone 1, Cadiz City', 'Chrisgen Norca', '09545886456', 1, '2026-02-17 13:01:47', '2026-02-17 13:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_shop_station`
--

DROP TABLE IF EXISTS `tbl_shop_station`;
CREATE TABLE IF NOT EXISTS `tbl_shop_station` (
  `shop_station_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `station_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`shop_station_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_shop_station`
--

INSERT INTO `tbl_shop_station` (`shop_station_id`, `station_name`, `created_at`, `updated_at`) VALUES
(1, 'Barista', '2026-02-17 15:37:30', '2026-02-17 15:37:30'),
(2, 'Kitchen', '2026-02-17 15:37:30', '2026-02-17 15:37:30'),
(3, 'Dessert', '2026-02-17 15:38:11', '2026-02-17 15:38:11'),
(4, 'Counter', '2026-02-17 15:38:25', '2026-02-17 15:38:25');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_station_status`
--

DROP TABLE IF EXISTS `tbl_station_status`;
CREATE TABLE IF NOT EXISTS `tbl_station_status` (
  `station_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `station_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`station_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_batches`
--

DROP TABLE IF EXISTS `tbl_stock_batches`;
CREATE TABLE IF NOT EXISTS `tbl_stock_batches` (
  `stock_batch_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_id` bigint UNSIGNED NOT NULL,
  `batch_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `quantity_received` decimal(10,3) NOT NULL,
  `quantity_remaining` decimal(10,3) NOT NULL,
  `shop_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`stock_batch_id`),
  KEY `tbl_stock_batches_ingredient_id_expiry_date_index` (`ingredient_id`,`expiry_date`),
  KEY `shop_id` (`shop_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_stock_batches`
--

INSERT INTO `tbl_stock_batches` (`stock_batch_id`, `ingredient_id`, `batch_code`, `expiry_date`, `unit_cost`, `quantity_received`, `quantity_remaining`, `shop_id`, `branch_id`, `created_at`, `updated_at`) VALUES
(1, 1, '0001', '2027-02-19', 2000.00, 100.000, 10.000, 1, 1, '2026-02-19 00:14:13', '2026-02-19 00:14:13'),
(2, 2, '0002', '2027-02-20', 3000.00, 100.000, 10.000, 1, 1, '2026-02-19 02:14:13', '2026-02-19 02:14:13');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_movements`
--

DROP TABLE IF EXISTS `tbl_stock_movements`;
CREATE TABLE IF NOT EXISTS `tbl_stock_movements` (
  `stock_movement_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_id` bigint UNSIGNED NOT NULL,
  `stock_batch_id` bigint UNSIGNED DEFAULT NULL,
  `movement_type_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`stock_movement_id`),
  KEY `tbl_stock_movements_ingredient_id_movement_type_index` (`ingredient_id`,`movement_type_id`),
  KEY `stock_batch_id` (`stock_batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_void_orders`
--

DROP TABLE IF EXISTS `tbl_void_orders`;
CREATE TABLE IF NOT EXISTS `tbl_void_orders` (
  `void_order_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` int NOT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `void_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `void_notes` text COLLATE utf8mb4_unicode_ci,
  `voided_by` bigint UNSIGNED NOT NULL,
  `voided_at` timestamp NOT NULL,
  `void_status_id` int NOT NULL,
  `from_quantity` int NOT NULL,
  `to_quantity` int NOT NULL,
  `shop_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`void_order_id`),
  KEY `tbl_void_orders_order_id_foreign` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_void_status`
--

DROP TABLE IF EXISTS `tbl_void_status`;
CREATE TABLE IF NOT EXISTS `tbl_void_status` (
  `void_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `void_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`void_status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_void_status`
--

INSERT INTO `tbl_void_status` (`void_status_id`, `void_status`, `created_at`, `updated_at`) VALUES
(1, 'Pending', '2026-02-18 03:19:22', '2026-02-18 03:19:22'),
(2, 'Approved', '2026-02-18 03:19:22', '2026-02-18 03:19:22'),
(3, 'Rejected', '2026-02-18 03:19:45', '2026-02-18 03:19:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD CONSTRAINT `tbl_admin_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_barista`
--
ALTER TABLE `tbl_barista`
  ADD CONSTRAINT `tbl_barista_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_barista_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_cashier`
--
ALTER TABLE `tbl_cashier`
  ADD CONSTRAINT `tbl_cashier_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_cashier_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_ingredients`
--
ALTER TABLE `tbl_ingredients`
  ADD CONSTRAINT `tbl_ingredients_ibfk_5` FOREIGN KEY (`base_unit_id`) REFERENCES `tbl_ingredient_unit` (`ingredient_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ingredients_ibfk_6` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ingredients_ibfk_7` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ingredients_ibfk_8` FOREIGN KEY (`availability_id`) REFERENCES `tbl_availability` (`availability_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_kitchen_personnel`
--
ALTER TABLE `tbl_kitchen_personnel`
  ADD CONSTRAINT `tbl_kitchen_personnel_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_kitchen_personnel_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_orders`
--
ALTER TABLE `tbl_orders`
  ADD CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`order_status_id`) REFERENCES `tbl_order_status` (`order_status_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_orders_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_orders_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_orders_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `tbl_cashier` (`cashier_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_order_items`
--
ALTER TABLE `tbl_order_items`
  ADD CONSTRAINT `tbl_order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD CONSTRAINT `tbl_products_ibfk_1` FOREIGN KEY (`size_id`) REFERENCES `tbl_product_size` (`product_size_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `tbl_product_category` (`product_category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_3` FOREIGN KEY (`temp_id`) REFERENCES `tbl_product_temp` (`product_temp_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_4` FOREIGN KEY (`availability_id`) REFERENCES `tbl_availability` (`availability_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_5` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_6` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_7` FOREIGN KEY (`user_id`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_ibfk_8` FOREIGN KEY (`station_id`) REFERENCES `tbl_shop_station` (`shop_station_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_products_history`
--
ALTER TABLE `tbl_products_history`
  ADD CONSTRAINT `tbl_products_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_history_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_history_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_products_history_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_product_category`
--
ALTER TABLE `tbl_product_category`
  ADD CONSTRAINT `tbl_product_category_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_product_items`
--
ALTER TABLE `tbl_product_items`
  ADD CONSTRAINT `tbl_product_items_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `tbl_ingredients` (`ingredient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_product_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_product_prices`
--
ALTER TABLE `tbl_product_prices`
  ADD CONSTRAINT `tbl_product_prices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_product_prices_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `tbl_product_variants` (`variant_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_product_variants`
--
ALTER TABLE `tbl_product_variants`
  ADD CONSTRAINT `tbl_product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  ADD CONSTRAINT `tbl_sales_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_sales_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_sales_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `tbl_cashier` (`cashier_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_sales_ibfk_4` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_sales_ibfk_5` FOREIGN KEY (`payment_method_id`) REFERENCES `tbl_payment_method` (`payment_method_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_sale_items`
--
ALTER TABLE `tbl_sale_items`
  ADD CONSTRAINT `tbl_sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`product_id`),
  ADD CONSTRAINT `tbl_sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `tbl_sales` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_sale_items_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `tbl_product_variants` (`variant_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_shop_branch`
--
ALTER TABLE `tbl_shop_branch`
  ADD CONSTRAINT `tbl_shop_branch_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_stock_batches`
--
ALTER TABLE `tbl_stock_batches`
  ADD CONSTRAINT `tbl_stock_batches_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `tbl_shops` (`shop_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_stock_batches_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `tbl_shop_branch` (`branch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_stock_batches_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `tbl_ingredients` (`ingredient_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_stock_movements`
--
ALTER TABLE `tbl_stock_movements`
  ADD CONSTRAINT `tbl_stock_movements_ibfk_1` FOREIGN KEY (`stock_batch_id`) REFERENCES `tbl_stock_batches` (`stock_batch_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_stock_movements_ingredient_id_foreign` FOREIGN KEY (`ingredient_id`) REFERENCES `tbl_ingredients` (`ingredient_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_void_orders`
--
ALTER TABLE `tbl_void_orders`
  ADD CONSTRAINT `tbl_void_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`order_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
