-- Tahap 1: Fondasi integrasi NeoFeeder/PDDikti.
-- Jalankan setelah backup database. Migration ini non-destruktif.

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_queue` (
  `id_queue` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` enum('neofeeder','internal') NOT NULL DEFAULT 'neofeeder',
  `direction` enum('push','pull') NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id_local` varchar(100) DEFAULT NULL,
  `entity_id_feeder` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `payload` longtext DEFAULT NULL,
  `status` enum('pending','processing','success','failed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` tinyint unsigned NOT NULL DEFAULT 5,
  `attempt_count` int unsigned NOT NULL DEFAULT 0,
  `max_attempts` int unsigned NOT NULL DEFAULT 3,
  `last_error` text DEFAULT NULL,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `locked_at` datetime DEFAULT NULL,
  `locked_by` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_queue`),
  KEY `idx_sync_queue_status` (`status`),
  KEY `idx_sync_queue_entity` (`entity_type`,`entity_id_local`),
  KEY `idx_sync_queue_available` (`available_at`,`priority`),
  KEY `fk_sync_queue_user` (`created_by`),
  CONSTRAINT `fk_sync_queue_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_attempts` (
  `id_attempt` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_queue` bigint unsigned DEFAULT NULL,
  `provider` enum('neofeeder','internal') NOT NULL DEFAULT 'neofeeder',
  `entity_type` varchar(100) NOT NULL,
  `entity_id_local` varchar(100) DEFAULT NULL,
  `entity_id_feeder` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `request_payload` longtext DEFAULT NULL,
  `response_payload` longtext DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `http_code` int DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_attempt`),
  KEY `idx_sync_attempts_queue` (`id_queue`),
  KEY `idx_sync_attempts_entity` (`entity_type`,`entity_id_local`),
  KEY `idx_sync_attempts_status` (`status`),
  KEY `idx_sync_attempts_created` (`created_at`),
  CONSTRAINT `fk_sync_attempts_queue` FOREIGN KEY (`id_queue`) REFERENCES `sync_queue` (`id_queue`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_conflicts` (
  `id_conflict` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` enum('neofeeder','internal') NOT NULL DEFAULT 'neofeeder',
  `entity_type` varchar(100) NOT NULL,
  `entity_id_local` varchar(100) DEFAULT NULL,
  `entity_id_feeder` varchar(100) DEFAULT NULL,
  `conflict_type` varchar(100) NOT NULL,
  `local_snapshot` longtext DEFAULT NULL,
  `remote_snapshot` longtext DEFAULT NULL,
  `resolution` enum('unresolved','use_local','use_remote','manual','ignored') NOT NULL DEFAULT 'unresolved',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_conflict`),
  KEY `idx_sync_conflicts_entity` (`entity_type`,`entity_id_local`),
  KEY `idx_sync_conflicts_resolution` (`resolution`),
  KEY `fk_sync_conflicts_user` (`resolved_by`),
  CONSTRAINT `fk_sync_conflicts_user` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `neofeeder_log`
  ADD COLUMN IF NOT EXISTS `entity_type` varchar(100) DEFAULT NULL AFTER `aksi`,
  ADD COLUMN IF NOT EXISTS `entity_id_local` varchar(100) DEFAULT NULL AFTER `entity_type`,
  ADD COLUMN IF NOT EXISTS `entity_id_feeder` varchar(100) DEFAULT NULL AFTER `entity_id_local`,
  ADD COLUMN IF NOT EXISTS `http_code` int DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `duration_ms` int unsigned DEFAULT NULL AFTER `http_code`;

ALTER TABLE `neofeeder_log`
  ADD KEY IF NOT EXISTS `idx_neofeeder_log_entity` (`entity_type`,`entity_id_local`),
  ADD KEY IF NOT EXISTS `idx_neofeeder_log_http_code` (`http_code`);

INSERT IGNORE INTO `schema_migrations` (`version`, `description`)
VALUES ('001_neofeeder_foundation', 'Fondasi env, audit log aman, queue, attempts, dan conflict sync NeoFeeder');
