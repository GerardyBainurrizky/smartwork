-- ISA SmartWork Target Pre-Sync Backup
-- Date: 2026-09-08 11:02:32
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `clock_in` timestamp NULL DEFAULT NULL,
  `clock_out` timestamp NULL DEFAULT NULL,
  `clock_in_lat` decimal(10,7) DEFAULT NULL,
  `clock_in_lng` decimal(10,7) DEFAULT NULL,
  `clock_in_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_in_maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_in_selfie` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_lat` decimal(10,7) DEFAULT NULL,
  `clock_out_lng` decimal(10,7) DEFAULT NULL,
  `clock_out_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_out_selfie` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `absence_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_user_id_date_unique` (`user_id`,`date`),
  KEY `attendances_date_index` (`date`),
  KEY `attendances_status_index` (`status`),
  KEY `attendances_archived_at_index` (`archived_at`),
  KEY `attendances_data_archive_id_index` (`data_archive_id`),
  CONSTRAINT `attendances_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('isa-smartwork-cache-karyawan|182.10.131.77', 'i:1;', '1788857669');
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('isa-smartwork-cache-karyawan|182.10.131.77:timer', 'i:1788857669;', '1788857669');
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('isa-smartwork-cache-superadmin|182.10.131.77', 'i:3;', '1788857627');
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('isa-smartwork-cache-superadmin|182.10.131.77:timer', 'i:1788857627;', '1788857627');

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `data_archives`;
CREATE TABLE `data_archives` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `data_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `records_count` int unsigned NOT NULL DEFAULT '0',
  `details` json DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'archived',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `restored_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data_archives_user_id_foreign` (`user_id`),
  KEY `data_archives_data_type_status_index` (`data_type`,`status`),
  KEY `data_archives_archived_at_index` (`archived_at`),
  CONSTRAINT `data_archives_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `gps_locations`;
CREATE TABLE `gps_locations` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_stop_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `altitude` decimal(8,2) DEFAULT NULL,
  `accuracy` decimal(6,2) DEFAULT NULL,
  `speed` decimal(6,2) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gps_locations_route_stop_id_foreign` (`route_stop_id`),
  KEY `gps_locations_route_id_recorded_at_index` (`route_id`,`recorded_at`),
  KEY `gps_locations_recorded_at_index` (`recorded_at`),
  CONSTRAINT `gps_locations_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gps_locations_route_stop_id_foreign` FOREIGN KEY (`route_stop_id`) REFERENCES `route_stops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
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

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_08_04_000001_add_username_to_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_08_04_000002_create_attendances_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_08_04_000003_create_stores_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_08_04_000004_create_routes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2026_08_04_000005_create_gps_locations_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2026_08_04_000006_create_visits_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('10', '2026_08_04_031521_create_permission_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('11', '2026_08_05_000001_add_city_province_to_stores_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('12', '2026_08_06_000001_add_estimated_time_to_route_stops_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('13', '2026_08_06_000002_add_estimated_duration_minutes_to_route_stops_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('14', '2026_08_06_000003_rename_karyawan_role_to_sales_add_staff', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('15', '2026_08_07_000001_add_owner_kecamatan_maps_to_stores_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('16', '2026_08_07_000002_add_created_by_to_routes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('17', '2026_08_10_000001_add_payment_method_to_visits_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('18', '2026_08_10_000002_add_transaction_status_to_visits_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('19', '2026_08_21_000001_create_data_archives_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('20', '2026_08_24_095003_add_data_archive_id_to_operational_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('21', '2026_08_26_110420_create_notifications_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('22', '2026_08_31_000001_add_sales_and_delivery_to_stores_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('23', '2026_09_01_001533_add_izin_sakit_support_to_attendances_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('24', '2026_09_01_020138_create_visit_photos_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('25', '2026_09_02_000001_create_store_receivables_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('26', '2026_09_03_000001_create_store_transactions_and_payments_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('27', '2026_09_08_000001_add_data_archive_id_to_transaction_and_receivable_tables', '1');

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=404 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('1', 'staff', 'web', '2026-09-08 13:19:15', '2026-09-08 13:19:15');

DROP TABLE IF EXISTS `route_stops`;
CREATE TABLE `route_stops` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` smallint unsigned NOT NULL,
  `estimated_duration_minutes` smallint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_stops_route_id_sequence_unique` (`route_id`,`sequence`),
  KEY `route_stops_store_id_foreign` (`store_id`),
  KEY `route_stops_route_id_store_id_index` (`route_id`,`store_id`),
  CONSTRAINT `route_stops_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_stops_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `routes`;
CREATE TABLE `routes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `distance_planned` decimal(10,2) DEFAULT NULL,
  `distance_actual` decimal(10,2) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `routes_date_index` (`date`),
  KEY `routes_status_index` (`status`),
  KEY `routes_user_id_date_index` (`user_id`,`date`),
  KEY `routes_created_by_foreign` (`created_by`),
  KEY `routes_archived_at_index` (`archived_at`),
  KEY `routes_data_archive_id_index` (`data_archive_id`),
  CONSTRAINT `routes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routes_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `store_receivables`;
CREATE TABLE `store_receivables` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocation` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_receivables_store_id_index` (`store_id`),
  KEY `store_receivables_type_index` (`type`),
  KEY `store_receivables_transaction_date_index` (`transaction_date`),
  KEY `store_receivables_created_by_index` (`created_by`),
  KEY `store_receivables_data_archive_id_index` (`data_archive_id`),
  KEY `store_receivables_archived_at_index` (`archived_at`),
  CONSTRAINT `store_receivables_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_receivables_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_receivables_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `store_submissions`;
CREATE TABLE `store_submissions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `store_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_submissions_reviewed_by_foreign` (`reviewed_by`),
  KEY `store_submissions_store_id_foreign` (`store_id`),
  KEY `store_submissions_user_id_index` (`user_id`),
  KEY `store_submissions_status_index` (`status`),
  CONSTRAINT `store_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_submissions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `store_transaction_payments`;
CREATE TABLE `store_transaction_payments` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_transaction_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_transaction_payments_store_transaction_id_index` (`store_transaction_id`),
  KEY `store_transaction_payments_payment_date_index` (`payment_date`),
  KEY `store_transaction_payments_recorded_by_index` (`recorded_by`),
  KEY `store_transaction_payments_data_archive_id_index` (`data_archive_id`),
  KEY `store_transaction_payments_archived_at_index` (`archived_at`),
  CONSTRAINT `store_transaction_payments_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_transaction_payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_transaction_payments_store_transaction_id_foreign` FOREIGN KEY (`store_transaction_id`) REFERENCES `store_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `store_transactions`;
CREATE TABLE `store_transactions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `store_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `transaction_amount` decimal(15,2) NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BELUM_LUNAS',
  `reference_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_transactions_transaction_code_unique` (`transaction_code`),
  KEY `store_transactions_store_id_index` (`store_id`),
  KEY `store_transactions_transaction_code_index` (`transaction_code`),
  KEY `store_transactions_transaction_date_index` (`transaction_date`),
  KEY `store_transactions_status_index` (`status`),
  KEY `store_transactions_created_by_index` (`created_by`),
  KEY `store_transactions_data_archive_id_index` (`data_archive_id`),
  KEY `store_transactions_archived_at_index` (`archived_at`),
  CONSTRAINT `store_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_transactions_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `store_transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stores`;
CREATE TABLE `stores` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_penanggung_jawab_id` bigint unsigned DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outlet',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kecamatan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_delivery_destination` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_code_unique` (`code`),
  KEY `stores_code_index` (`code`),
  KEY `stores_type_index` (`type`),
  KEY `stores_status_index` (`status`),
  KEY `stores_sales_penanggung_jawab_id_index` (`sales_penanggung_jawab_id`),
  KEY `stores_is_delivery_destination_index` (`is_delivery_destination`),
  CONSTRAINT `stores_sales_penanggung_jawab_id_foreign` FOREIGN KEY (`sales_penanggung_jawab_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `visit_photos`;
CREATE TABLE `visit_photos` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'checkout_documentation',
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visit_photos_visit_id_type_index` (`visit_id`,`type`),
  CONSTRAINT `visit_photos_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `route_stop_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_progress',
  `archived_at` timestamp NULL DEFAULT NULL,
  `data_archive_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_at` timestamp NULL DEFAULT NULL,
  `check_in_lat` decimal(10,7) DEFAULT NULL,
  `check_in_lng` decimal(10,7) DEFAULT NULL,
  `check_in_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storefront_photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_selfie` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivered_goods` text COLLATE utf8mb4_unicode_ci,
  `cash_received` decimal(15,2) DEFAULT NULL,
  `initial_notes` text COLLATE utf8mb4_unicode_ci,
  `check_out_at` timestamp NULL DEFAULT NULL,
  `check_out_lat` decimal(10,7) DEFAULT NULL,
  `check_out_lng` decimal(10,7) DEFAULT NULL,
  `check_out_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_maps_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visit_result` text COLLATE utf8mb4_unicode_ci,
  `delivered_goods_summary` text COLLATE utf8mb4_unicode_ci,
  `returned_goods` text COLLATE utf8mb4_unicode_ci,
  `transaction_amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `final_notes` text COLLATE utf8mb4_unicode_ci,
  `final_store_photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_selfie` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visits_route_stop_id_foreign` (`route_stop_id`),
  KEY `visits_route_id_foreign` (`route_id`),
  KEY `visits_user_id_check_in_at_index` (`user_id`,`check_in_at`),
  KEY `visits_status_index` (`status`),
  KEY `visits_store_id_check_in_at_index` (`store_id`,`check_in_at`),
  KEY `visits_archived_at_index` (`archived_at`),
  KEY `visits_data_archive_id_index` (`data_archive_id`),
  CONSTRAINT `visits_data_archive_id_foreign` FOREIGN KEY (`data_archive_id`) REFERENCES `data_archives` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_route_stop_id_foreign` FOREIGN KEY (`route_stop_id`) REFERENCES `route_stops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visits_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
