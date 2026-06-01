CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id_reset` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime NULL,
  `request_ip` varchar(45) NULL,
  `user_agent` varchar(255) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reset`),
  UNIQUE KEY `uniq_password_reset_token_hash` (`token_hash`),
  KEY `idx_password_reset_user_status` (`id_user`, `used_at`, `expires_at`),
  CONSTRAINT `fk_password_reset_user`
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
