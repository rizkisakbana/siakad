ALTER TABLE `notifikasi`
  ADD COLUMN IF NOT EXISTS `dibaca_pada` datetime NULL AFTER `status_baca`;

ALTER TABLE `notifikasi`
  ADD KEY IF NOT EXISTS `idx_notifikasi_user_status` (`id_user`, `status_baca`);
