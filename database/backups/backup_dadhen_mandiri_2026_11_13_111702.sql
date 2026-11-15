-- Full Database Backup: dadhen_mandiri
-- Date: 2026-11-13 11:17:02

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `company_activity_logs`;
CREATE TABLE `company_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `properties` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_activity_logs_user_id_index` (`user_id`),
  KEY `company_activity_logs_action_index` (`action`),
  KEY `company_activity_logs_resource_type_index` (`resource_type`),
  KEY `company_activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `company_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('1', NULL, 'Crissencio Dheni S.', 'year_created', 'OperationalYear', '4', 'Tahun operasional 2027 berhasil dibuat dengan 12 workspace bulanan.', '{\"year\": 2027, \"created_by\": \"Crissencio Dheni S.\"}', '2026-11-08 17:40:46');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('2', NULL, 'Crissencio Dheni S.', 'logout', 'User', '14', 'Pengguna Crissencio Dheni S. keluar dari sistem.', NULL, '2026-11-08 18:05:39');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('3', NULL, 'Michael Adrian Made Yudi Sunarke', 'login', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 18:05:45');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('4', NULL, 'Michael Adrian Made Yudi Sunarke', 'logout', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke keluar dari sistem.', NULL, '2026-11-08 18:06:00');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('5', NULL, 'Michael Adrian Made Yudi Sunarke', 'login', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 18:06:56');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('6', NULL, 'Michael Adrian Made Yudi Sunarke', 'logout', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke keluar dari sistem.', NULL, '2026-11-08 18:37:38');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('7', NULL, 'Michael Adrian Made Yudi Sunarke', 'login', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 18:38:48');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('8', NULL, 'Michael Adrian Made Yudi Sunarke', 'logout', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke keluar dari sistem.', NULL, '2026-11-08 18:38:52');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('9', NULL, 'Dadhen Mandiri', 'login', 'User', '26', 'Pengguna Dadhen Mandiri berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 18:38:59');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('10', NULL, 'Dadhen Mandiri', 'logout', 'User', '26', 'Pengguna Dadhen Mandiri keluar dari sistem.', NULL, '2026-11-08 18:39:30');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('11', NULL, 'ANIS BASWEDAN', 'year_created', 'OperationalYear', '5', 'Tahun operasional 2028 berhasil dibuat dengan 12 workspace bulanan.', '{\"year\": 2028, \"created_by\": \"ANIS BASWEDAN\"}', '2026-11-08 19:13:02');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('12', NULL, 'ANIS BASWEDAN', 'year_password_updated', 'OperationalYear', '4', 'Password workspace tahun 2027 berhasil diperbarui.', '{\"year\": 2027, \"configured_by\": \"ANIS BASWEDAN\"}', '2026-11-08 19:24:22');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('13', NULL, 'ANIS BASWEDAN', 'logout', 'User', '32', 'Pengguna ANIS BASWEDAN keluar dari sistem.', NULL, '2026-11-08 19:25:59');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('14', NULL, 'Michael Adrian Made Yudi Sunarke', 'login', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 19:26:04');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('15', NULL, 'Michael Adrian Made Yudi Sunarke', 'logout', 'User', '25', 'Pengguna Michael Adrian Made Yudi Sunarke keluar dari sistem.', NULL, '2026-11-08 19:28:54');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('16', NULL, 'ANIS BASWEDAN', 'login', 'User', '32', 'Pengguna ANIS BASWEDAN berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-08 19:29:35');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('17', NULL, 'ANIS BASWEDAN', 'year_restored', 'OperationalYear', '2', 'Tahun operasional 2025 dipulihkan menjadi aktif.', '{\"year\": 2025, \"restored_by\": \"ANIS BASWEDAN\"}', '2026-11-08 19:29:49');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('18', NULL, 'ANIS BASWEDAN', 'year_password_updated', 'OperationalYear', '5', 'Password workspace tahun 2028 berhasil diperbarui.', '{\"year\": 2028, \"configured_by\": \"ANIS BASWEDAN\"}', '2026-11-08 20:05:17');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('19', NULL, 'ANIS BASWEDAN', 'year_archived', 'OperationalYear', '2', 'Tahun operasional 2025 berhasil diarsipkan menjadi read-only.', '{\"year\": 2025, \"archived_by\": \"ANIS BASWEDAN\"}', '2026-11-08 20:05:29');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('20', NULL, 'ANIS BASWEDAN', 'year_restored', 'OperationalYear', '2', 'Tahun operasional 2025 dipulihkan menjadi aktif.', '{\"year\": 2025, \"restored_by\": \"ANIS BASWEDAN\"}', '2026-11-08 20:12:40');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('21', NULL, 'ANIS BASWEDAN', 'year_archived', 'OperationalYear', '2', 'Tahun operasional 2025 berhasil diarsipkan menjadi read-only.', '{\"year\": 2025, \"archived_by\": \"ANIS BASWEDAN\"}', '2026-11-08 20:21:08');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('22', NULL, 'ANIS BASWEDAN', 'login', 'User', '32', 'Pengguna ANIS BASWEDAN berhasil masuk ke sistem.', '{\"ip\": \"127.0.0.1\", \"user_agent\": \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36\"}', '2026-11-09 11:41:15');
INSERT INTO `company_activity_logs` (`id`, `user_id`, `user_name`, `action`, `resource_type`, `resource_id`, `description`, `properties`, `created_at`) VALUES ('23', NULL, 'Sistem', 'register', 'User', '33', 'Pengguna baru Crissencio Dheni S. mendaftar sebagai Admin.', NULL, '2026-11-11 07:37:38');

DROP TABLE IF EXISTS `fisheries_groups`;
CREATE TABLE `fisheries_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Budidaya Lele',
  `invite_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Utama',
  `pond_count` int NOT NULL DEFAULT '1',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `is_legacy` tinyint(1) NOT NULL DEFAULT '0',
  `operational_year` smallint unsigned DEFAULT NULL,
  `operational_month` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fisheries_groups_invite_code_unique` (`invite_code`),
  KEY `fisheries_groups_created_by_foreign` (`created_by`),
  CONSTRAINT `fisheries_groups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `friendships`;
CREATE TABLE `friendships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `friend_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friendships_user_id_friend_id_unique` (`user_id`,`friend_id`),
  KEY `friendships_friend_id_foreign` (`friend_id`),
  CONSTRAINT `friendships_friend_id_foreign` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `friendships_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `group_members`;
CREATE TABLE `group_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Anggota',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_members_group_id_user_id_unique` (`group_id`,`user_id`),
  KEY `group_members_user_id_foreign` (`user_id`),
  CONSTRAINT `group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `fisheries_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=615 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '2024_01_01_000001_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '2024_01_01_000002_create_fisheries_groups_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '2024_01_01_000003_create_group_members_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2024_01_01_000004_create_spreadsheet_sheets_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2024_01_01_000005_create_spreadsheet_columns_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2024_01_01_000006_create_spreadsheet_rows_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2024_01_01_000007_create_spreadsheet_cells_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2024_01_01_000008_create_friendships_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2024_01_01_000009_create_notifications_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('10', '2024_01_01_000010_create_password_reset_tokens_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('11', '2024_01_01_000011_create_sessions_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('12', '2024_01_02_000001_upgrade_spreadsheet_and_roles_schema', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('13', '2024_01_03_000001_add_password_to_fisheries_groups_table', '3');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('14', '2026_10_20_000001_add_xlsx_layout_metadata_to_spreadsheets', '4');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('15', '2026_10_20_000002_add_version_to_spreadsheet_cells', '4');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('16', '2026_10_21_000003_create_spreadsheet_activities_table', '5');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('17', '2026_10_26_000001_add_formula_index_to_spreadsheet_cells', '6');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('18', '2026_10_30_000001_normalize_group_member_roles', '6');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('19', '2026_10_30_000002_migrate_karyawan_to_admin_multi_leader', '6');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('20', '2026_11_08_000001_create_company_years_and_monthly_workspaces_tables', '7');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('21', '2026_11_08_000002_migrate_to_owner_and_admin_roles', '8');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('22', '2026_11_08_000003_add_status_and_activity_to_users_and_create_logs_table', '9');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('23', '2026_11_08_000004_ensure_user_role_default_and_sync', '10');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('24', '2026_11_08_000005_add_password_to_operational_years_table', '11');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('25', '2026_11_11_000001_make_workspace_creator_nullable_on_user_delete', '12');

DROP TABLE IF EXISTS `monthly_workspaces`;
CREATE TABLE `monthly_workspaces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year_id` bigint unsigned NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` bigint unsigned NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_year_month` (`year_id`,`month`),
  UNIQUE KEY `monthly_workspaces_group_id_unique` (`group_id`),
  CONSTRAINT `monthly_workspaces_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `fisheries_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_workspaces_year_id_foreign` FOREIGN KEY (`year_id`) REFERENCES `operational_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `description`, `is_read`, `created_at`, `updated_at`) VALUES ('52', '33', 'success', 'Selamat Datang di Dadhen Mandiri', 'Akun kolaborasi spreadsheet Anda berhasil dibuat.', '0', '2026-11-11 00:37:38', '2026-11-11 00:37:38');

DROP TABLE IF EXISTS `operational_years`;
CREATE TABLE `operational_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_version` int unsigned NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operational_years_year_unique` (`year`),
  KEY `operational_years_created_by_foreign` (`created_by`),
  CONSTRAINT `operational_years_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `operational_years` (`id`, `year`, `is_active`, `is_archived`, `password`, `password_version`, `created_by`, `created_at`, `updated_at`) VALUES ('1', '2024', '1', '0', NULL, '1', NULL, '2026-11-08 08:24:22', '2026-11-08 08:24:22');
INSERT INTO `operational_years` (`id`, `year`, `is_active`, `is_archived`, `password`, `password_version`, `created_by`, `created_at`, `updated_at`) VALUES ('2', '2025', '0', '1', NULL, '1', NULL, '2026-11-08 08:24:29', '2026-11-08 13:21:08');
INSERT INTO `operational_years` (`id`, `year`, `is_active`, `is_archived`, `password`, `password_version`, `created_by`, `created_at`, `updated_at`) VALUES ('3', '2026', '1', '0', NULL, '1', NULL, '2026-11-08 08:24:37', '2026-11-08 08:24:37');
INSERT INTO `operational_years` (`id`, `year`, `is_active`, `is_archived`, `password`, `password_version`, `created_by`, `created_at`, `updated_at`) VALUES ('4', '2027', '1', '0', '$2y$12$Zi7qwqMPVqLMwdgpUpP.eeax20eTzE8Q0ZM0CG85JIreRhqnAk/Fi', '2', NULL, '2026-11-08 10:40:30', '2026-11-08 12:24:22');
INSERT INTO `operational_years` (`id`, `year`, `is_active`, `is_archived`, `password`, `password_version`, `created_by`, `created_at`, `updated_at`) VALUES ('5', '2028', '1', '0', '$2y$12$1LhmRtO1MVSe5fWhRpIT5efhIT0G8oncWhr.3G07WwOi.aLGHKlhG', '2', NULL, '2026-11-08 12:12:40', '2026-11-08 13:05:17');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
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

DROP TABLE IF EXISTS `spreadsheet_activities`;
CREATE TABLE `spreadsheet_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` bigint unsigned NOT NULL,
  `sheet_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cell_range` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `details` json DEFAULT NULL,
  `committed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spreadsheet_activities_event_uuid_unique` (`event_uuid`),
  KEY `spreadsheet_activities_sheet_id_foreign` (`sheet_id`),
  KEY `spreadsheet_activities_user_id_foreign` (`user_id`),
  KEY `spreadsheet_activities_group_id_sheet_id_committed_at_index` (`group_id`,`sheet_id`,`committed_at`),
  CONSTRAINT `spreadsheet_activities_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `fisheries_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spreadsheet_activities_sheet_id_foreign` FOREIGN KEY (`sheet_id`) REFERENCES `spreadsheet_sheets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `spreadsheet_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spreadsheet_cells`;
CREATE TABLE `spreadsheet_cells` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `row_id` bigint unsigned NOT NULL,
  `column_id` bigint unsigned NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `formula` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `spreadsheet_cells_row_id_column_id_unique` (`row_id`,`column_id`),
  KEY `spreadsheet_cells_column_id_index` (`column_id`),
  KEY `spreadsheet_cells_row_id_formula_index` (`row_id`,`formula`),
  CONSTRAINT `spreadsheet_cells_column_id_foreign` FOREIGN KEY (`column_id`) REFERENCES `spreadsheet_columns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spreadsheet_cells_row_id_foreign` FOREIGN KEY (`row_id`) REFERENCES `spreadsheet_rows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=122635 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spreadsheet_columns`;
CREATE TABLE `spreadsheet_columns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` bigint unsigned NOT NULL,
  `letter` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `width` int NOT NULL DEFAULT '160',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spreadsheet_columns_sheet_id_sort_order_index` (`sheet_id`,`sort_order`),
  CONSTRAINT `spreadsheet_columns_sheet_id_foreign` FOREIGN KEY (`sheet_id`) REFERENCES `spreadsheet_sheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7353 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spreadsheet_rows`;
CREATE TABLE `spreadsheet_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` bigint unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spreadsheet_rows_sheet_id_sort_order_index` (`sheet_id`,`sort_order`),
  CONSTRAINT `spreadsheet_rows_sheet_id_foreign` FOREIGN KEY (`sheet_id`) REFERENCES `spreadsheet_sheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9921 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `spreadsheet_sheets`;
CREATE TABLE `spreadsheet_sheets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `monthly_workspace_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'produksi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_sheet_name` (`group_id`,`name`),
  KEY `spreadsheet_sheets_monthly_workspace_id_foreign` (`monthly_workspace_id`),
  CONSTRAINT `spreadsheet_sheets_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `fisheries_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spreadsheet_sheets_monthly_workspace_id_foreign` FOREIGN KEY (`monthly_workspace_id`) REFERENCES `monthly_workspaces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=456 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pemilik/Pengelola Tambak',
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_active_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `password`, `whatsapp`, `role_title`, `role`, `is_active`, `last_active_at`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES ('33', 'Crissencio Dheni S.', 'yudimade979@gmail.com', '$2y$12$C4GUozXN1YEhJdiHNmLfTeJjfpu7yf.P50fm1kKrrWseCo42uGeAu', '0853 3100 3356', 'Admin', 'admin', '1', NULL, NULL, NULL, '2026-11-11 00:37:38', '2026-11-11 00:37:38');

SET FOREIGN_KEY_CHECKS=1;
