-- Laravel's session table.
--
-- This application ships no framework migrations, and the vendor's
-- database.sql contains only their own 47 tables. With
-- SESSION_DRIVER=database, every request that starts a session fails
-- with "Base table or view not found: sessions" — the health endpoint
-- /up still passes because it does not touch the session, so the stack
-- looks healthy while every real page returns 500.
--
-- Import once, after the vendor dump:
--   docker compose exec mysql sh -c \
--     'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' \
--     < database/schema/laravel-sessions.sql

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
