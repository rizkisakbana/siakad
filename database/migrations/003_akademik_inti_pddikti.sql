-- Tahap 3: Struktur akademik inti PDDikti/NeoFeeder.
-- Fokus: kurikulum, mata kuliah, matkul kurikulum, kelas kuliah, dosen pengajar, peserta kelas.

ALTER TABLE `kurikulum`
  ADD COLUMN IF NOT EXISTS `id_kurikulum_feeder` varchar(100) DEFAULT NULL AFTER `id_kurikulum`,
  ADD COLUMN IF NOT EXISTS `id_prodi_feeder` varchar(100) DEFAULT NULL AFTER `id_prodi`,
  ADD COLUMN IF NOT EXISTS `id_semester_mulai_feeder` varchar(20) DEFAULT NULL AFTER `tahun_kurikulum`,
  ADD COLUMN IF NOT EXISTS `jumlah_sks_lulus` int DEFAULT 0 AFTER `total_sks`,
  ADD COLUMN IF NOT EXISTS `jumlah_sks_wajib` int DEFAULT 0 AFTER `jumlah_sks_lulus`,
  ADD COLUMN IF NOT EXISTS `jumlah_sks_pilihan` int DEFAULT 0 AFTER `jumlah_sks_wajib`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `jumlah_sks_pilihan`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `kurikulum` k
LEFT JOIN `prodi` p ON p.id_prodi = k.id_prodi
SET
  k.`id_prodi_feeder` = COALESCE(NULLIF(k.`id_prodi_feeder`, ''), p.`id_prodi_feeder`, p.`id_feeder`),
  k.`jumlah_sks_lulus` = CASE WHEN k.`jumlah_sks_lulus` = 0 THEN COALESCE(k.`total_sks`, 0) ELSE k.`jumlah_sks_lulus` END,
  k.`jumlah_sks_wajib` = CASE WHEN k.`jumlah_sks_wajib` = 0 THEN COALESCE(k.`total_sks`, 0) ELSE k.`jumlah_sks_wajib` END;

ALTER TABLE `kurikulum`
  ADD KEY IF NOT EXISTS `idx_kurikulum_id_feeder` (`id_kurikulum_feeder`),
  ADD KEY IF NOT EXISTS `idx_kurikulum_id_prodi_feeder` (`id_prodi_feeder`),
  ADD KEY IF NOT EXISTS `idx_kurikulum_status_sync` (`status_sync_feeder`);

ALTER TABLE `mata_kuliah`
  ADD COLUMN IF NOT EXISTS `id_matkul_feeder` varchar(100) DEFAULT NULL AFTER `id_mk`,
  ADD COLUMN IF NOT EXISTS `id_prodi_feeder` varchar(100) DEFAULT NULL AFTER `id_kurikulum`,
  ADD COLUMN IF NOT EXISTS `id_jenis_mata_kuliah_feeder` varchar(50) DEFAULT NULL AFTER `jenis_mk`,
  ADD COLUMN IF NOT EXISTS `sks_tatap_muka` decimal(5,2) DEFAULT 0.00 AFTER `sks_praktik`,
  ADD COLUMN IF NOT EXISTS `sks_praktik_lapangan` decimal(5,2) DEFAULT 0.00 AFTER `sks_tatap_muka`,
  ADD COLUMN IF NOT EXISTS `sks_simulasi` decimal(5,2) DEFAULT 0.00 AFTER `sks_praktik_lapangan`,
  ADD COLUMN IF NOT EXISTS `ada_sap` enum('0','1') DEFAULT '0' AFTER `sks_simulasi`,
  ADD COLUMN IF NOT EXISTS `ada_silabus` enum('0','1') DEFAULT '0' AFTER `ada_sap`,
  ADD COLUMN IF NOT EXISTS `ada_bahan_ajar` enum('0','1') DEFAULT '0' AFTER `ada_silabus`,
  ADD COLUMN IF NOT EXISTS `ada_acara_praktik` enum('0','1') DEFAULT '0' AFTER `ada_bahan_ajar`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext DEFAULT NULL AFTER `ada_acara_praktik`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `mata_kuliah` mk
LEFT JOIN `kurikulum` k ON k.id_kurikulum = mk.id_kurikulum
LEFT JOIN `prodi` p ON p.id_prodi = k.id_prodi
SET
  mk.`id_prodi_feeder` = COALESCE(NULLIF(mk.`id_prodi_feeder`, ''), k.`id_prodi_feeder`, p.`id_prodi_feeder`, p.`id_feeder`),
  mk.`sks_tatap_muka` = CASE WHEN mk.`sks_tatap_muka` = 0 THEN COALESCE(mk.`sks_teori`, 0) ELSE mk.`sks_tatap_muka` END;

ALTER TABLE `mata_kuliah`
  ADD KEY IF NOT EXISTS `idx_mk_id_matkul_feeder` (`id_matkul_feeder`),
  ADD KEY IF NOT EXISTS `idx_mk_id_prodi_feeder` (`id_prodi_feeder`),
  ADD KEY IF NOT EXISTS `idx_mk_status_sync` (`status_sync_feeder`);

CREATE TABLE IF NOT EXISTS `matkul_kurikulum` (
  `id_matkul_kurikulum` int(11) NOT NULL AUTO_INCREMENT,
  `id_kurikulum` int(11) NOT NULL,
  `id_mk` int(11) NOT NULL,
  `id_kurikulum_feeder` varchar(100) DEFAULT NULL,
  `id_matkul_feeder` varchar(100) DEFAULT NULL,
  `semester` int(11) NOT NULL,
  `sifat_mata_kuliah` enum('wajib','pilihan') DEFAULT 'wajib',
  `sks_mata_kuliah` decimal(5,2) DEFAULT 0.00,
  `sks_tatap_muka` decimal(5,2) DEFAULT 0.00,
  `sks_praktik` decimal(5,2) DEFAULT 0.00,
  `sks_praktik_lapangan` decimal(5,2) DEFAULT 0.00,
  `sks_simulasi` decimal(5,2) DEFAULT 0.00,
  `raw_feeder_data` longtext DEFAULT NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum',
  `last_sync_feeder` datetime DEFAULT NULL,
  `last_error_feeder` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_matkul_kurikulum`),
  UNIQUE KEY `uniq_matkul_kurikulum` (`id_kurikulum`,`id_mk`),
  KEY `fk_mk_kurikulum_kurikulum` (`id_kurikulum`),
  KEY `fk_mk_kurikulum_mk` (`id_mk`),
  KEY `idx_matkul_kurikulum_feeder` (`id_kurikulum_feeder`,`id_matkul_feeder`),
  KEY `idx_matkul_kurikulum_status_sync` (`status_sync_feeder`),
  CONSTRAINT `fk_matkul_kurikulum_kurikulum` FOREIGN KEY (`id_kurikulum`) REFERENCES `kurikulum` (`id_kurikulum`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matkul_kurikulum_mk` FOREIGN KEY (`id_mk`) REFERENCES `mata_kuliah` (`id_mk`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `matkul_kurikulum`
(
  `id_kurikulum`,
  `id_mk`,
  `id_kurikulum_feeder`,
  `id_matkul_feeder`,
  `semester`,
  `sifat_mata_kuliah`,
  `sks_mata_kuliah`,
  `sks_tatap_muka`,
  `sks_praktik`,
  `sks_praktik_lapangan`,
  `sks_simulasi`
)
SELECT
  mk.`id_kurikulum`,
  mk.`id_mk`,
  k.`id_kurikulum_feeder`,
  mk.`id_matkul_feeder`,
  mk.`semester`,
  mk.`jenis_mk`,
  COALESCE(mk.`total_sks`, 0),
  COALESCE(mk.`sks_tatap_muka`, mk.`sks_teori`, 0),
  COALESCE(mk.`sks_praktik`, 0),
  COALESCE(mk.`sks_praktik_lapangan`, 0),
  COALESCE(mk.`sks_simulasi`, 0)
FROM `mata_kuliah` mk
LEFT JOIN `kurikulum` k ON k.`id_kurikulum` = mk.`id_kurikulum`;

CREATE TABLE IF NOT EXISTS `kelas_kuliah` (
  `id_kelas_kuliah` int(11) NOT NULL AUTO_INCREMENT,
  `id_kelas_kuliah_feeder` varchar(100) DEFAULT NULL,
  `id_jadwal` int(11) DEFAULT NULL,
  `id_tahun` int(11) NOT NULL,
  `id_semester_feeder` varchar(20) DEFAULT NULL,
  `id_prodi` int(11) NOT NULL,
  `id_prodi_feeder` varchar(100) DEFAULT NULL,
  `id_mk` int(11) NOT NULL,
  `id_matkul_feeder` varchar(100) DEFAULT NULL,
  `id_kelas_internal` int(11) DEFAULT NULL,
  `nama_kelas_kuliah` varchar(100) NOT NULL,
  `kode_kelas` varchar(50) DEFAULT NULL,
  `bahasan` text DEFAULT NULL,
  `lingkup` enum('internal','eksternal','campuran') DEFAULT 'internal',
  `mode_kuliah` enum('tatap muka','online','hybrid') DEFAULT 'tatap muka',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `kapasitas` int(11) DEFAULT 40,
  `raw_feeder_data` longtext DEFAULT NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum',
  `last_sync_feeder` datetime DEFAULT NULL,
  `last_error_feeder` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_kelas_kuliah`),
  UNIQUE KEY `uniq_kelas_kuliah_jadwal` (`id_jadwal`),
  KEY `idx_kelas_kuliah_feeder` (`id_kelas_kuliah_feeder`),
  KEY `idx_kelas_kuliah_semester` (`id_semester_feeder`),
  KEY `idx_kelas_kuliah_prodi` (`id_prodi`,`id_tahun`),
  KEY `idx_kelas_kuliah_mk` (`id_mk`),
  KEY `idx_kelas_kuliah_status_sync` (`status_sync_feeder`),
  CONSTRAINT `fk_kelas_kuliah_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_kuliah_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_kuliah_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_kuliah_mk` FOREIGN KEY (`id_mk`) REFERENCES `mata_kuliah` (`id_mk`) ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_kuliah_internal` FOREIGN KEY (`id_kelas_internal`) REFERENCES `kelas` (`id_kelas`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `kelas_kuliah`
(
  `id_jadwal`,
  `id_tahun`,
  `id_semester_feeder`,
  `id_prodi`,
  `id_prodi_feeder`,
  `id_mk`,
  `id_matkul_feeder`,
  `id_kelas_internal`,
  `nama_kelas_kuliah`,
  `kode_kelas`,
  `mode_kuliah`,
  `kapasitas`,
  `status`
)
SELECT
  j.`id_jadwal`,
  j.`id_tahun`,
  ta.`id_semester_feeder`,
  kls.`id_prodi`,
  p.`id_prodi_feeder`,
  j.`id_mk`,
  mk.`id_matkul_feeder`,
  j.`id_kelas`,
  COALESCE(NULLIF(kls.`nama_kelas`, ''), CONCAT(mk.`kode_mk`, '-', j.`id_jadwal`)),
  kls.`kode_kelas`,
  j.`metode`,
  COALESCE(kls.`kapasitas`, 40),
  j.`status`
FROM `jadwal_kuliah` j
LEFT JOIN `kelas` kls ON kls.`id_kelas` = j.`id_kelas`
LEFT JOIN `prodi` p ON p.`id_prodi` = kls.`id_prodi`
LEFT JOIN `mata_kuliah` mk ON mk.`id_mk` = j.`id_mk`
LEFT JOIN `tahun_akademik` ta ON ta.`id_tahun` = j.`id_tahun`;

ALTER TABLE `jadwal_kuliah`
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah` int(11) DEFAULT NULL AFTER `id_jadwal`,
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah_feeder` varchar(100) DEFAULT NULL AFTER `id_kelas_kuliah`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `status`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `jadwal_kuliah` j
LEFT JOIN `kelas_kuliah` kk ON kk.`id_jadwal` = j.`id_jadwal`
SET
  j.`id_kelas_kuliah` = COALESCE(j.`id_kelas_kuliah`, kk.`id_kelas_kuliah`),
  j.`id_kelas_kuliah_feeder` = COALESCE(NULLIF(j.`id_kelas_kuliah_feeder`, ''), kk.`id_kelas_kuliah_feeder`);

ALTER TABLE `jadwal_kuliah`
  ADD KEY IF NOT EXISTS `idx_jadwal_kelas_kuliah` (`id_kelas_kuliah`),
  ADD KEY IF NOT EXISTS `idx_jadwal_kelas_kuliah_feeder` (`id_kelas_kuliah_feeder`),
  ADD KEY IF NOT EXISTS `idx_jadwal_status_sync` (`status_sync_feeder`);

CREATE TABLE IF NOT EXISTS `dosen_pengajar_kelas` (
  `id_dosen_pengajar` int(11) NOT NULL AUTO_INCREMENT,
  `id_kelas_kuliah` int(11) NOT NULL,
  `id_kelas_kuliah_feeder` varchar(100) DEFAULT NULL,
  `id_dosen` int(11) NOT NULL,
  `id_dosen_feeder` varchar(100) DEFAULT NULL,
  `urutan_pengajar` int(11) DEFAULT 1,
  `sks_substansi_total` decimal(5,2) DEFAULT 0.00,
  `rencana_tatap_muka` int(11) DEFAULT 16,
  `realisasi_tatap_muka` int(11) DEFAULT 0,
  `jenis_evaluasi` varchar(50) DEFAULT NULL,
  `raw_feeder_data` longtext DEFAULT NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum',
  `last_sync_feeder` datetime DEFAULT NULL,
  `last_error_feeder` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_dosen_pengajar`),
  UNIQUE KEY `uniq_dosen_pengajar_kelas` (`id_kelas_kuliah`,`id_dosen`),
  KEY `idx_dosen_pengajar_kelas_feeder` (`id_kelas_kuliah_feeder`),
  KEY `idx_dosen_pengajar_dosen_feeder` (`id_dosen_feeder`),
  KEY `idx_dosen_pengajar_status_sync` (`status_sync_feeder`),
  CONSTRAINT `fk_dosen_pengajar_kelas_kuliah` FOREIGN KEY (`id_kelas_kuliah`) REFERENCES `kelas_kuliah` (`id_kelas_kuliah`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_dosen_pengajar_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `dosen_pengajar_kelas`
(
  `id_kelas_kuliah`,
  `id_kelas_kuliah_feeder`,
  `id_dosen`,
  `id_dosen_feeder`,
  `sks_substansi_total`
)
SELECT
  kk.`id_kelas_kuliah`,
  kk.`id_kelas_kuliah_feeder`,
  j.`id_dosen`,
  d.`id_dosen_feeder`,
  COALESCE(mk.`total_sks`, 0)
FROM `jadwal_kuliah` j
INNER JOIN `kelas_kuliah` kk ON kk.`id_jadwal` = j.`id_jadwal`
LEFT JOIN `dosen` d ON d.`id_dosen` = j.`id_dosen`
LEFT JOIN `mata_kuliah` mk ON mk.`id_mk` = j.`id_mk`;

CREATE TABLE IF NOT EXISTS `peserta_kelas_kuliah` (
  `id_peserta_kelas` int(11) NOT NULL AUTO_INCREMENT,
  `id_kelas_kuliah` int(11) NOT NULL,
  `id_kelas_kuliah_feeder` varchar(100) DEFAULT NULL,
  `id_krs_detail` int(11) DEFAULT NULL,
  `id_krs` int(11) DEFAULT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) DEFAULT NULL,
  `nim` varchar(30) DEFAULT NULL,
  `status_peserta` enum('aktif','batal') DEFAULT 'aktif',
  `raw_feeder_data` longtext DEFAULT NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum',
  `last_sync_feeder` datetime DEFAULT NULL,
  `last_error_feeder` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_peserta_kelas`),
  UNIQUE KEY `uniq_peserta_kelas` (`id_kelas_kuliah`,`id_mahasiswa`),
  KEY `idx_peserta_kelas_feeder` (`id_kelas_kuliah_feeder`),
  KEY `idx_peserta_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_peserta_krs_detail` (`id_krs_detail`),
  KEY `idx_peserta_status_sync` (`status_sync_feeder`),
  CONSTRAINT `fk_peserta_kelas_kuliah` FOREIGN KEY (`id_kelas_kuliah`) REFERENCES `kelas_kuliah` (`id_kelas_kuliah`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_krs` FOREIGN KEY (`id_krs`) REFERENCES `krs` (`id_krs`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_krs_detail` FOREIGN KEY (`id_krs_detail`) REFERENCES `krs_detail` (`id_krs_detail`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `krs_detail`
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah` int(11) DEFAULT NULL AFTER `id_jadwal`,
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah_feeder` varchar(100) DEFAULT NULL AFTER `id_kelas_kuliah`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') DEFAULT 'belum' AFTER `status_mk`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime DEFAULT NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text DEFAULT NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `krs_detail` kd
LEFT JOIN `kelas_kuliah` kk ON kk.`id_jadwal` = kd.`id_jadwal`
SET
  kd.`id_kelas_kuliah` = COALESCE(kd.`id_kelas_kuliah`, kk.`id_kelas_kuliah`),
  kd.`id_kelas_kuliah_feeder` = COALESCE(NULLIF(kd.`id_kelas_kuliah_feeder`, ''), kk.`id_kelas_kuliah_feeder`);

ALTER TABLE `krs_detail`
  ADD KEY IF NOT EXISTS `idx_krs_detail_kelas_kuliah` (`id_kelas_kuliah`),
  ADD KEY IF NOT EXISTS `idx_krs_detail_status_sync` (`status_sync_feeder`);

INSERT IGNORE INTO `peserta_kelas_kuliah`
(
  `id_kelas_kuliah`,
  `id_kelas_kuliah_feeder`,
  `id_krs_detail`,
  `id_krs`,
  `id_mahasiswa`,
  `id_registrasi_mahasiswa_feeder`,
  `nim`,
  `status_peserta`
)
SELECT
  kk.`id_kelas_kuliah`,
  kk.`id_kelas_kuliah_feeder`,
  kd.`id_krs_detail`,
  krs.`id_krs`,
  krs.`id_mahasiswa`,
  m.`id_registrasi_feeder`,
  m.`nim`,
  CASE WHEN kd.`status_mk` = 'batal' THEN 'batal' ELSE 'aktif' END
FROM `krs_detail` kd
INNER JOIN `krs` krs ON krs.`id_krs` = kd.`id_krs`
INNER JOIN `mahasiswa` m ON m.`id_mahasiswa` = krs.`id_mahasiswa`
INNER JOIN `kelas_kuliah` kk ON kk.`id_jadwal` = kd.`id_jadwal`;

INSERT IGNORE INTO `schema_migrations` (`version`, `description`)
VALUES ('003_akademik_inti_pddikti', 'Bangun struktur kurikulum, mata kuliah, matkul kurikulum, kelas kuliah, dosen pengajar, dan peserta kelas');
