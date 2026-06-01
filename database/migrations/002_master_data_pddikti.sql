-- Tahap 2: Rapikan master data PDDikti/NeoFeeder.
-- Migration ini menambah kolom eksplisit PDDikti dan melakukan backfill dari kolom lama.

ALTER TABLE `profil_pt`
  ADD COLUMN IF NOT EXISTS `id_perguruan_tinggi_feeder` varchar(100) DEFAULT NULL AFTER `id_feeder`,
  ADD COLUMN IF NOT EXISTS `id_wilayah_feeder` varchar(50) DEFAULT NULL AFTER `id_wilayah`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `status_sinkron`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

UPDATE `profil_pt`
SET
  `id_perguruan_tinggi_feeder` = COALESCE(NULLIF(`id_perguruan_tinggi_feeder`, ''), `id_feeder`),
  `id_wilayah_feeder` = COALESCE(NULLIF(`id_wilayah_feeder`, ''), `id_wilayah`),
  `status_sync_feeder` = CASE WHEN `status_sinkron` = 'sudah' THEN 'sudah' ELSE `status_sync_feeder` END
WHERE `id_feeder` IS NOT NULL OR `id_wilayah` IS NOT NULL;

ALTER TABLE `profil_pt`
  ADD KEY IF NOT EXISTS `idx_profil_pt_id_pt_feeder` (`id_perguruan_tinggi_feeder`),
  ADD KEY IF NOT EXISTS `idx_profil_pt_status_sync` (`status_sync_feeder`);

ALTER TABLE `prodi`
  ADD COLUMN IF NOT EXISTS `id_prodi_feeder` varchar(100) DEFAULT NULL AFTER `id_feeder`,
  ADD COLUMN IF NOT EXISTS `id_jenjang_pendidikan_feeder` varchar(50) DEFAULT NULL AFTER `jenjang`,
  ADD COLUMN IF NOT EXISTS `nama_jenjang_pendidikan` varchar(100) DEFAULT NULL AFTER `id_jenjang_pendidikan_feeder`,
  ADD COLUMN IF NOT EXISTS `status_prodi_feeder` varchar(20) DEFAULT NULL AFTER `nama_jenjang_pendidikan`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `status_prodi_feeder`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

UPDATE `prodi`
SET
  `id_prodi_feeder` = COALESCE(NULLIF(`id_prodi_feeder`, ''), `id_feeder`),
  `nama_jenjang_pendidikan` = COALESCE(NULLIF(`nama_jenjang_pendidikan`, ''), `jenjang`),
  `status_sync_feeder` = CASE WHEN `id_feeder` IS NOT NULL AND `id_feeder` != '' THEN 'sudah' ELSE `status_sync_feeder` END;

ALTER TABLE `prodi`
  ADD KEY IF NOT EXISTS `idx_prodi_id_prodi_feeder` (`id_prodi_feeder`),
  ADD KEY IF NOT EXISTS `idx_prodi_status_sync` (`status_sync_feeder`);

ALTER TABLE `tahun_akademik`
  ADD COLUMN IF NOT EXISTS `id_semester_feeder` varchar(20) DEFAULT NULL AFTER `id_feeder`,
  ADD COLUMN IF NOT EXISTS `periode_pelaporan` varchar(20) DEFAULT NULL AFTER `id_semester_feeder`,
  ADD COLUMN IF NOT EXISTS `tahun_ajaran` varchar(20) DEFAULT NULL AFTER `periode_pelaporan`,
  ADD COLUMN IF NOT EXISTS `kode_semester` varchar(5) DEFAULT NULL AFTER `tahun_ajaran`,
  ADD COLUMN IF NOT EXISTS `nama_semester` varchar(50) DEFAULT NULL AFTER `kode_semester`,
  ADD COLUMN IF NOT EXISTS `tipe_periode` varchar(20) DEFAULT NULL AFTER `nama_semester`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `tipe_periode`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

UPDATE `tahun_akademik`
SET
  `id_semester_feeder` = COALESCE(NULLIF(`id_semester_feeder`, ''), `id_feeder`),
  `periode_pelaporan` = COALESCE(NULLIF(`periode_pelaporan`, ''), `id_feeder`),
  `tahun_ajaran` = COALESCE(NULLIF(`tahun_ajaran`, ''), `tahun`),
  `kode_semester` = COALESCE(
    NULLIF(`kode_semester`, ''),
    CASE
      WHEN `semester` = 'Ganjil' THEN '1'
      WHEN `semester` = 'Genap' THEN '2'
      WHEN `semester` = 'Pendek' THEN '3'
      ELSE NULL
    END
  ),
  `nama_semester` = COALESCE(NULLIF(`nama_semester`, ''), `semester`),
  `status_sync_feeder` = CASE WHEN `id_feeder` IS NOT NULL AND `id_feeder` != '' THEN 'sudah' ELSE `status_sync_feeder` END;

ALTER TABLE `tahun_akademik`
  ADD KEY IF NOT EXISTS `idx_tahun_id_semester_feeder` (`id_semester_feeder`),
  ADD KEY IF NOT EXISTS `idx_tahun_periode_pelaporan` (`periode_pelaporan`),
  ADD KEY IF NOT EXISTS `idx_tahun_status_sync` (`status_sync_feeder`);

ALTER TABLE `ref_pddikti`
  ADD COLUMN IF NOT EXISTS `source_act` varchar(100) DEFAULT NULL AFTER `jenis_ref`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

ALTER TABLE `ref_pddikti`
  ADD KEY IF NOT EXISTS `idx_ref_source_act` (`source_act`),
  ADD KEY IF NOT EXISTS `idx_ref_last_sync` (`last_sync_feeder`);

ALTER TABLE `dosen`
  ADD COLUMN IF NOT EXISTS `id_dosen_feeder` varchar(100) DEFAULT NULL AFTER `id_feeder`,
  ADD COLUMN IF NOT EXISTS `nidk` varchar(30) DEFAULT NULL AFTER `nidn`,
  ADD COLUMN IF NOT EXISTS `nuptk` varchar(30) DEFAULT NULL AFTER `nidk`,
  ADD COLUMN IF NOT EXISTS `id_agama_feeder` varchar(50) DEFAULT NULL AFTER `tanggal_lahir`,
  ADD COLUMN IF NOT EXISTS `agama` varchar(50) DEFAULT NULL AFTER `id_agama_feeder`,
  ADD COLUMN IF NOT EXISTS `id_status_aktif_feeder` varchar(50) DEFAULT NULL AFTER `status_dosen`,
  ADD COLUMN IF NOT EXISTS `nama_status_aktif` varchar(100) DEFAULT NULL AFTER `id_status_aktif_feeder`,
  ADD COLUMN IF NOT EXISTS `id_ikatan_kerja_feeder` varchar(50) DEFAULT NULL AFTER `nama_status_aktif`,
  ADD COLUMN IF NOT EXISTS `nama_ikatan_kerja` varchar(100) DEFAULT NULL AFTER `id_ikatan_kerja_feeder`,
  ADD COLUMN IF NOT EXISTS `id_prodi_feeder` varchar(100) DEFAULT NULL AFTER `id_prodi`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `nama_ikatan_kerja`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

UPDATE `dosen`
SET
  `id_dosen_feeder` = COALESCE(NULLIF(`id_dosen_feeder`, ''), `id_feeder`),
  `status_sync_feeder` = CASE WHEN `id_feeder` IS NOT NULL AND `id_feeder` != '' THEN 'sudah' ELSE `status_sync_feeder` END;

ALTER TABLE `dosen`
  ADD KEY IF NOT EXISTS `idx_dosen_id_dosen_feeder` (`id_dosen_feeder`),
  ADD KEY IF NOT EXISTS `idx_dosen_id_prodi_feeder` (`id_prodi_feeder`),
  ADD KEY IF NOT EXISTS `idx_dosen_status_sync` (`status_sync_feeder`);

ALTER TABLE `mahasiswa`
  ADD COLUMN IF NOT EXISTS `id_prodi_feeder` varchar(100) DEFAULT NULL AFTER `id_prodi`,
  ADD COLUMN IF NOT EXISTS `id_periode_masuk_feeder` varchar(20) DEFAULT NULL AFTER `angkatan`,
  ADD COLUMN IF NOT EXISTS `id_pembiayaan_feeder` varchar(50) DEFAULT NULL AFTER `id_jenis_pendaftaran_feeder`,
  ADD COLUMN IF NOT EXISTS `biaya_masuk` decimal(15,2) DEFAULT NULL AFTER `id_pembiayaan_feeder`,
  ADD COLUMN IF NOT EXISTS `id_penghasilan_ayah_feeder` varchar(50) DEFAULT NULL AFTER `id_penghasilan_ortu_feeder`,
  ADD COLUMN IF NOT EXISTS `id_penghasilan_ibu_feeder` varchar(50) DEFAULT NULL AFTER `id_penghasilan_ayah_feeder`,
  ADD COLUMN IF NOT EXISTS `id_kebutuhan_khusus_mahasiswa_feeder` varchar(50) DEFAULT NULL AFTER `kebutuhan_khusus`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `id_kebutuhan_khusus_mahasiswa_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`;

UPDATE `mahasiswa` m
LEFT JOIN `prodi` p ON p.id_prodi = m.id_prodi
LEFT JOIN `tahun_akademik` ta ON ta.tahun LIKE CONCAT(m.angkatan, '/%') AND ta.semester = 'Ganjil'
SET
  m.`id_prodi_feeder` = COALESCE(NULLIF(m.`id_prodi_feeder`, ''), p.`id_prodi_feeder`, p.`id_feeder`),
  m.`id_periode_masuk_feeder` = COALESCE(NULLIF(m.`id_periode_masuk_feeder`, ''), ta.`id_semester_feeder`, ta.`id_feeder`),
  m.`id_penghasilan_ayah_feeder` = COALESCE(NULLIF(m.`id_penghasilan_ayah_feeder`, ''), m.`id_penghasilan_ortu_feeder`),
  m.`id_penghasilan_ibu_feeder` = COALESCE(NULLIF(m.`id_penghasilan_ibu_feeder`, ''), m.`id_penghasilan_ortu_feeder`);

ALTER TABLE `mahasiswa`
  ADD KEY IF NOT EXISTS `idx_mahasiswa_id_biodata_feeder` (`id_biodata_feeder`),
  ADD KEY IF NOT EXISTS `idx_mahasiswa_id_registrasi_feeder` (`id_registrasi_feeder`),
  ADD KEY IF NOT EXISTS `idx_mahasiswa_id_prodi_feeder` (`id_prodi_feeder`),
  ADD KEY IF NOT EXISTS `idx_mahasiswa_periode_masuk_feeder` (`id_periode_masuk_feeder`);

INSERT IGNORE INTO `schema_migrations` (`version`, `description`)
VALUES ('002_master_data_pddikti', 'Rapikan profil PT, prodi, periode, referensi, dosen, dan mahasiswa agar NeoFeeder-ready');
