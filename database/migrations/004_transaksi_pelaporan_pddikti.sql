-- Tahap 4: Transaksi pelaporan NeoFeeder/PDDikti.
-- Fokus: KRS, nilai, KHS, AKM, status mahasiswa, lulus/DO, prestasi, dan MBKM.

ALTER TABLE `krs`
  ADD COLUMN IF NOT EXISTS `id_semester_feeder` varchar(20) NULL AFTER `id_tahun`,
  ADD COLUMN IF NOT EXISTS `id_registrasi_mahasiswa_feeder` varchar(100) NULL AFTER `id_mahasiswa`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext NULL AFTER `tanggal_disetujui`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `krs` kr
LEFT JOIN `mahasiswa` m ON m.`id_mahasiswa` = kr.`id_mahasiswa`
LEFT JOIN `tahun_akademik` ta ON ta.`id_tahun` = kr.`id_tahun`
SET
  kr.`id_registrasi_mahasiswa_feeder` = COALESCE(NULLIF(kr.`id_registrasi_mahasiswa_feeder`, ''), NULLIF(m.`id_registrasi_feeder`, '')),
  kr.`id_semester_feeder` = COALESCE(NULLIF(kr.`id_semester_feeder`, ''), NULLIF(ta.`id_semester_feeder`, ''), NULLIF(ta.`id_feeder`, ''));

ALTER TABLE `krs`
  ADD INDEX IF NOT EXISTS `idx_krs_id_semester_feeder` (`id_semester_feeder`),
  ADD INDEX IF NOT EXISTS `idx_krs_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  ADD INDEX IF NOT EXISTS `idx_krs_status_sync_feeder` (`status_sync_feeder`);

ALTER TABLE `nilai`
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah` int NULL AFTER `id_krs_detail`,
  ADD COLUMN IF NOT EXISTS `id_kelas_kuliah_feeder` varchar(100) NULL AFTER `id_kelas_kuliah`,
  ADD COLUMN IF NOT EXISTS `id_mahasiswa` int NULL AFTER `id_kelas_kuliah_feeder`,
  ADD COLUMN IF NOT EXISTS `id_registrasi_mahasiswa_feeder` varchar(100) NULL AFTER `id_mahasiswa`,
  ADD COLUMN IF NOT EXISTS `id_matkul` int NULL AFTER `id_registrasi_mahasiswa_feeder`,
  ADD COLUMN IF NOT EXISTS `id_matkul_feeder` varchar(100) NULL AFTER `id_matkul`,
  ADD COLUMN IF NOT EXISTS `nilai_indeks` decimal(4,2) NULL AFTER `bobot`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext NULL AFTER `status_publish`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text NULL AFTER `last_sync_feeder`;

UPDATE `nilai` n
JOIN `krs_detail` kd ON kd.`id_krs_detail` = n.`id_krs_detail`
JOIN `krs` kr ON kr.`id_krs` = kd.`id_krs`
LEFT JOIN `mahasiswa` m ON m.`id_mahasiswa` = kr.`id_mahasiswa`
LEFT JOIN `kelas_kuliah` kk ON kk.`id_kelas_kuliah` = kd.`id_kelas_kuliah`
LEFT JOIN `jadwal_kuliah` j ON j.`id_jadwal` = kd.`id_jadwal`
LEFT JOIN `mata_kuliah` mk ON mk.`id_mk` = COALESCE(kk.`id_mk`, j.`id_mk`)
SET
  n.`id_kelas_kuliah` = COALESCE(n.`id_kelas_kuliah`, kd.`id_kelas_kuliah`, kk.`id_kelas_kuliah`),
  n.`id_kelas_kuliah_feeder` = COALESCE(NULLIF(n.`id_kelas_kuliah_feeder`, ''), NULLIF(kd.`id_kelas_kuliah_feeder`, ''), NULLIF(kk.`id_kelas_kuliah_feeder`, '')),
  n.`id_mahasiswa` = COALESCE(n.`id_mahasiswa`, kr.`id_mahasiswa`),
  n.`id_registrasi_mahasiswa_feeder` = COALESCE(NULLIF(n.`id_registrasi_mahasiswa_feeder`, ''), NULLIF(m.`id_registrasi_feeder`, '')),
  n.`id_matkul` = COALESCE(n.`id_matkul`, mk.`id_mk`),
  n.`id_matkul_feeder` = COALESCE(NULLIF(n.`id_matkul_feeder`, ''), NULLIF(mk.`id_matkul_feeder`, '')),
  n.`nilai_indeks` = COALESCE(n.`nilai_indeks`, n.`bobot`);

ALTER TABLE `nilai`
  ADD INDEX IF NOT EXISTS `idx_nilai_kelas_kuliah` (`id_kelas_kuliah`),
  ADD INDEX IF NOT EXISTS `idx_nilai_kelas_feeder` (`id_kelas_kuliah_feeder`),
  ADD INDEX IF NOT EXISTS `idx_nilai_mahasiswa` (`id_mahasiswa`),
  ADD INDEX IF NOT EXISTS `idx_nilai_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  ADD INDEX IF NOT EXISTS `idx_nilai_status_sync_feeder` (`status_sync_feeder`);

ALTER TABLE `khs`
  ADD COLUMN IF NOT EXISTS `id_semester_feeder` varchar(20) NULL AFTER `id_tahun`,
  ADD COLUMN IF NOT EXISTS `id_registrasi_mahasiswa_feeder` varchar(100) NULL AFTER `id_mahasiswa`,
  ADD COLUMN IF NOT EXISTS `sks_semester` int NULL DEFAULT 0 AFTER `total_sks`,
  ADD COLUMN IF NOT EXISTS `sks_total` int NULL DEFAULT 0 AFTER `sks_semester`,
  ADD COLUMN IF NOT EXISTS `biaya_kuliah_smt` decimal(15,2) NULL DEFAULT 0 AFTER `ipk`,
  ADD COLUMN IF NOT EXISTS `id_status_mahasiswa_feeder` varchar(50) NULL AFTER `biaya_kuliah_smt`,
  ADD COLUMN IF NOT EXISTS `nama_status_mahasiswa` varchar(100) NULL AFTER `id_status_mahasiswa_feeder`,
  ADD COLUMN IF NOT EXISTS `raw_feeder_data` longtext NULL AFTER `tanggal_publish`,
  ADD COLUMN IF NOT EXISTS `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum' AFTER `raw_feeder_data`,
  ADD COLUMN IF NOT EXISTS `last_sync_feeder` datetime NULL AFTER `status_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `last_error_feeder` text NULL AFTER `last_sync_feeder`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`;

UPDATE `khs` kh
LEFT JOIN `mahasiswa` m ON m.`id_mahasiswa` = kh.`id_mahasiswa`
LEFT JOIN `tahun_akademik` ta ON ta.`id_tahun` = kh.`id_tahun`
SET
  kh.`id_registrasi_mahasiswa_feeder` = COALESCE(NULLIF(kh.`id_registrasi_mahasiswa_feeder`, ''), NULLIF(m.`id_registrasi_feeder`, '')),
  kh.`id_semester_feeder` = COALESCE(NULLIF(kh.`id_semester_feeder`, ''), NULLIF(ta.`id_semester_feeder`, ''), NULLIF(ta.`id_feeder`, '')),
  kh.`sks_semester` = CASE WHEN COALESCE(kh.`sks_semester`, 0) = 0 THEN COALESCE(kh.`total_sks`, 0) ELSE kh.`sks_semester` END,
  kh.`sks_total` = CASE WHEN COALESCE(kh.`sks_total`, 0) = 0 THEN COALESCE(kh.`total_sks`, 0) ELSE kh.`sks_total` END,
  kh.`id_status_mahasiswa_feeder` = COALESCE(NULLIF(kh.`id_status_mahasiswa_feeder`, ''), NULLIF(m.`id_status_mahasiswa_feeder`, '')),
  kh.`nama_status_mahasiswa` = COALESCE(NULLIF(kh.`nama_status_mahasiswa`, ''), m.`status_mahasiswa`);

ALTER TABLE `khs`
  ADD INDEX IF NOT EXISTS `idx_khs_id_semester_feeder` (`id_semester_feeder`),
  ADD INDEX IF NOT EXISTS `idx_khs_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  ADD INDEX IF NOT EXISTS `idx_khs_status_sync_feeder` (`status_sync_feeder`);

CREATE TABLE IF NOT EXISTS `aktivitas_kuliah_mahasiswa` (
  `id_akm` int NOT NULL AUTO_INCREMENT,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `id_tahun` int NOT NULL,
  `id_semester_feeder` varchar(20) NULL,
  `id_status_mahasiswa_feeder` varchar(50) NULL,
  `nama_status_mahasiswa` varchar(100) NULL,
  `ips` decimal(4,2) NULL DEFAULT 0.00,
  `ipk` decimal(4,2) NULL DEFAULT 0.00,
  `sks_semester` int NULL DEFAULT 0,
  `sks_total` int NULL DEFAULT 0,
  `biaya_kuliah_smt` decimal(15,2) NULL DEFAULT 0.00,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_akm`),
  UNIQUE KEY `uniq_akm_mahasiswa_tahun` (`id_mahasiswa`, `id_tahun`),
  KEY `idx_akm_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_akm_semester_feeder` (`id_semester_feeder`),
  KEY `idx_akm_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_akm_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_akm_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `aktivitas_kuliah_mahasiswa` (
  `id_mahasiswa`, `id_registrasi_mahasiswa_feeder`, `id_tahun`, `id_semester_feeder`,
  `id_status_mahasiswa_feeder`, `nama_status_mahasiswa`, `ips`, `ipk`,
  `sks_semester`, `sks_total`, `biaya_kuliah_smt`, `status_sync_feeder`
)
SELECT
  kh.`id_mahasiswa`,
  NULLIF(m.`id_registrasi_feeder`, ''),
  kh.`id_tahun`,
  COALESCE(NULLIF(kh.`id_semester_feeder`, ''), NULLIF(ta.`id_semester_feeder`, ''), NULLIF(ta.`id_feeder`, '')),
  COALESCE(NULLIF(kh.`id_status_mahasiswa_feeder`, ''), NULLIF(m.`id_status_mahasiswa_feeder`, '')),
  COALESCE(NULLIF(kh.`nama_status_mahasiswa`, ''), m.`status_mahasiswa`),
  kh.`ips`,
  kh.`ipk`,
  COALESCE(NULLIF(kh.`sks_semester`, 0), kh.`total_sks`, 0),
  COALESCE(NULLIF(kh.`sks_total`, 0), kh.`total_sks`, 0),
  COALESCE(kh.`biaya_kuliah_smt`, 0),
  'belum'
FROM `khs` kh
JOIN `mahasiswa` m ON m.`id_mahasiswa` = kh.`id_mahasiswa`
LEFT JOIN `tahun_akademik` ta ON ta.`id_tahun` = kh.`id_tahun`
ON DUPLICATE KEY UPDATE
  `id_registrasi_mahasiswa_feeder` = VALUES(`id_registrasi_mahasiswa_feeder`),
  `id_semester_feeder` = VALUES(`id_semester_feeder`),
  `id_status_mahasiswa_feeder` = VALUES(`id_status_mahasiswa_feeder`),
  `nama_status_mahasiswa` = VALUES(`nama_status_mahasiswa`),
  `ips` = VALUES(`ips`),
  `ipk` = VALUES(`ipk`),
  `sks_semester` = VALUES(`sks_semester`),
  `sks_total` = VALUES(`sks_total`),
  `biaya_kuliah_smt` = VALUES(`biaya_kuliah_smt`),
  `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `riwayat_status_mahasiswa` (
  `id_riwayat_status` int NOT NULL AUTO_INCREMENT,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `id_tahun` int NULL,
  `id_semester_feeder` varchar(20) NULL,
  `status_mahasiswa` varchar(100) NOT NULL,
  `id_status_mahasiswa_feeder` varchar(50) NULL,
  `tanggal_status` date NULL,
  `keterangan` text NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_riwayat_status`),
  UNIQUE KEY `uniq_riwayat_status_mhs_tahun_status` (`id_mahasiswa`, `id_tahun`, `status_mahasiswa`),
  KEY `idx_riwayat_status_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_riwayat_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_riwayat_status_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_status_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `riwayat_status_mahasiswa` (
  `id_mahasiswa`, `id_registrasi_mahasiswa_feeder`, `id_tahun`, `id_semester_feeder`,
  `status_mahasiswa`, `id_status_mahasiswa_feeder`, `tanggal_status`, `keterangan`
)
SELECT
  m.`id_mahasiswa`,
  NULLIF(m.`id_registrasi_feeder`, ''),
  COALESCE(ta.`id_tahun`, ta_active.`id_tahun`),
  COALESCE(NULLIF(ta.`id_semester_feeder`, ''), NULLIF(ta.`id_feeder`, ''), NULLIF(ta_active.`id_semester_feeder`, ''), NULLIF(ta_active.`id_feeder`, '')),
  m.`status_mahasiswa`,
  NULLIF(m.`id_status_mahasiswa_feeder`, ''),
  COALESCE(m.`tanggal_keluar`, m.`tanggal_masuk`, CURDATE()),
  'Backfill status terakhir mahasiswa'
FROM `mahasiswa` m
LEFT JOIN `tahun_akademik` ta
  ON ta.`id_tahun` = (
    SELECT ta2.`id_tahun`
    FROM `tahun_akademik` ta2
    WHERE ta2.`tahun` LIKE CONCAT(m.`angkatan`, '/%')
    ORDER BY
      CASE WHEN ta2.`semester` = 'Ganjil' THEN 0 ELSE 1 END,
      ta2.`id_tahun` ASC
    LIMIT 1
  )
LEFT JOIN `tahun_akademik` ta_active ON ta_active.`status` = 'aktif'
ON DUPLICATE KEY UPDATE
  `id_registrasi_mahasiswa_feeder` = VALUES(`id_registrasi_mahasiswa_feeder`),
  `id_tahun` = COALESCE(VALUES(`id_tahun`), `riwayat_status_mahasiswa`.`id_tahun`),
  `id_semester_feeder` = VALUES(`id_semester_feeder`),
  `id_status_mahasiswa_feeder` = VALUES(`id_status_mahasiswa_feeder`),
  `tanggal_status` = VALUES(`tanggal_status`),
  `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `mahasiswa_lulus_do` (
  `id_lulus_do` int NOT NULL AUTO_INCREMENT,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `id_prodi` int NULL,
  `id_prodi_feeder` varchar(100) NULL,
  `id_periode_keluar_feeder` varchar(20) NULL,
  `id_jenis_keluar_feeder` varchar(50) NULL,
  `jenis_keluar` varchar(100) NULL,
  `tanggal_keluar` date NULL,
  `keterangan` text NULL,
  `nomor_sk_yudisium` varchar(100) NULL,
  `tanggal_sk_yudisium` date NULL,
  `ipk` decimal(4,2) NULL DEFAULT 0.00,
  `nomor_ijazah` varchar(100) NULL,
  `judul_tugas_akhir` text NULL,
  `bulan_awal_bimbingan` varchar(6) NULL,
  `bulan_akhir_bimbingan` varchar(6) NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_lulus_do`),
  UNIQUE KEY `uniq_lulus_do_mahasiswa` (`id_mahasiswa`),
  KEY `idx_lulus_do_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_lulus_do_periode_keluar` (`id_periode_keluar_feeder`),
  KEY `idx_lulus_do_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_lulus_do_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_lulus_do_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mahasiswa_lulus_do` (
  `id_mahasiswa`, `id_registrasi_mahasiswa_feeder`, `id_prodi`, `id_prodi_feeder`,
  `id_periode_keluar_feeder`, `jenis_keluar`, `tanggal_keluar`, `keterangan`, `ipk`, `status_sync_feeder`
)
SELECT
  m.`id_mahasiswa`,
  NULLIF(m.`id_registrasi_feeder`, ''),
  m.`id_prodi`,
  COALESCE(NULLIF(m.`id_prodi_feeder`, ''), NULLIF(p.`id_prodi_feeder`, ''), NULLIF(p.`id_feeder`, '')),
  NULLIF(ta.`id_semester_feeder`, ''),
  m.`status_mahasiswa`,
  m.`tanggal_keluar`,
  CONCAT('Backfill status ', m.`status_mahasiswa`),
  COALESCE(kh.`ipk`, 0),
  'belum'
FROM `mahasiswa` m
LEFT JOIN `prodi` p ON p.`id_prodi` = m.`id_prodi`
LEFT JOIN `tahun_akademik` ta ON m.`tanggal_keluar` BETWEEN ta.`tanggal_mulai` AND ta.`tanggal_selesai`
LEFT JOIN (
  SELECT k1.`id_mahasiswa`, k1.`ipk`
  FROM `khs` k1
  JOIN (
    SELECT `id_mahasiswa`, MAX(`id_tahun`) AS `id_tahun`
    FROM `khs`
    GROUP BY `id_mahasiswa`
  ) last_khs ON last_khs.`id_mahasiswa` = k1.`id_mahasiswa` AND last_khs.`id_tahun` = k1.`id_tahun`
) kh ON kh.`id_mahasiswa` = m.`id_mahasiswa`
WHERE m.`status_mahasiswa` IN ('lulus', 'drop out', 'mengundurkan diri', 'pindah')
ON DUPLICATE KEY UPDATE
  `id_registrasi_mahasiswa_feeder` = VALUES(`id_registrasi_mahasiswa_feeder`),
  `id_prodi_feeder` = VALUES(`id_prodi_feeder`),
  `id_periode_keluar_feeder` = VALUES(`id_periode_keluar_feeder`),
  `jenis_keluar` = VALUES(`jenis_keluar`),
  `tanggal_keluar` = VALUES(`tanggal_keluar`),
  `ipk` = VALUES(`ipk`),
  `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `prestasi_mahasiswa` (
  `id_prestasi` int NOT NULL AUTO_INCREMENT,
  `id_prestasi_feeder` varchar(100) NULL,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `id_jenis_prestasi_feeder` varchar(50) NULL,
  `jenis_prestasi` varchar(100) NULL,
  `id_tingkat_prestasi_feeder` varchar(50) NULL,
  `tingkat_prestasi` varchar(100) NULL,
  `nama_prestasi` varchar(255) NOT NULL,
  `tahun_prestasi` year NULL,
  `penyelenggara` varchar(255) NULL,
  `peringkat` varchar(100) NULL,
  `nomor_sk` varchar(100) NULL,
  `tanggal_sk` date NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_prestasi`),
  KEY `idx_prestasi_feeder` (`id_prestasi_feeder`),
  KEY `idx_prestasi_mahasiswa` (`id_mahasiswa`),
  KEY `idx_prestasi_registrasi_feeder` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_prestasi_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_prestasi_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aktivitas_mahasiswa` (
  `id_aktivitas_mahasiswa` int NOT NULL AUTO_INCREMENT,
  `id_aktivitas_feeder` varchar(100) NULL,
  `id_prodi` int NULL,
  `id_prodi_feeder` varchar(100) NULL,
  `id_tahun` int NULL,
  `id_semester_feeder` varchar(20) NULL,
  `id_jenis_aktivitas_feeder` varchar(50) NULL,
  `jenis_aktivitas` varchar(100) NULL,
  `judul` varchar(255) NOT NULL,
  `lokasi` varchar(255) NULL,
  `sk_tugas` varchar(100) NULL,
  `tanggal_sk_tugas` date NULL,
  `jenis_anggota` enum('personal','kelompok') NULL DEFAULT 'personal',
  `keterangan` text NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_aktivitas_mahasiswa`),
  KEY `idx_aktivitas_mhs_feeder` (`id_aktivitas_feeder`),
  KEY `idx_aktivitas_mhs_prodi` (`id_prodi`),
  KEY `idx_aktivitas_mhs_semester` (`id_semester_feeder`),
  KEY `idx_aktivitas_mhs_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_aktivitas_mhs_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_aktivitas_mhs_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aktivitas_mahasiswa_anggota` (
  `id_aktivitas_anggota` int NOT NULL AUTO_INCREMENT,
  `id_aktivitas_mahasiswa` int NOT NULL,
  `id_aktivitas_feeder` varchar(100) NULL,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `nim` varchar(30) NULL,
  `peran` varchar(100) NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_aktivitas_anggota`),
  UNIQUE KEY `uniq_aktivitas_anggota_mhs` (`id_aktivitas_mahasiswa`, `id_mahasiswa`),
  KEY `idx_aktivitas_anggota_registrasi` (`id_registrasi_mahasiswa_feeder`),
  CONSTRAINT `fk_aktivitas_anggota_aktivitas` FOREIGN KEY (`id_aktivitas_mahasiswa`) REFERENCES `aktivitas_mahasiswa` (`id_aktivitas_mahasiswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aktivitas_anggota_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aktivitas_mahasiswa_pembimbing` (
  `id_aktivitas_pembimbing` int NOT NULL AUTO_INCREMENT,
  `id_aktivitas_mahasiswa` int NOT NULL,
  `id_aktivitas_feeder` varchar(100) NULL,
  `id_dosen` int NOT NULL,
  `id_dosen_feeder` varchar(100) NULL,
  `pembimbing_ke` int NULL DEFAULT 1,
  `kategori_kegiatan` varchar(100) NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_aktivitas_pembimbing`),
  UNIQUE KEY `uniq_aktivitas_pembimbing_dosen` (`id_aktivitas_mahasiswa`, `id_dosen`, `pembimbing_ke`),
  KEY `idx_aktivitas_pembimbing_dosen_feeder` (`id_dosen_feeder`),
  CONSTRAINT `fk_aktivitas_pembimbing_aktivitas` FOREIGN KEY (`id_aktivitas_mahasiswa`) REFERENCES `aktivitas_mahasiswa` (`id_aktivitas_mahasiswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aktivitas_pembimbing_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `konversi_mbkm` (
  `id_konversi_mbkm` int NOT NULL AUTO_INCREMENT,
  `id_aktivitas_mahasiswa` int NOT NULL,
  `id_aktivitas_feeder` varchar(100) NULL,
  `id_mahasiswa` int NOT NULL,
  `id_registrasi_mahasiswa_feeder` varchar(100) NULL,
  `id_mk` int NOT NULL,
  `id_matkul_feeder` varchar(100) NULL,
  `sks` decimal(5,2) NULL DEFAULT 0.00,
  `nilai_angka` decimal(5,2) NULL,
  `nilai_huruf` varchar(5) NULL,
  `nilai_indeks` decimal(4,2) NULL,
  `raw_feeder_data` longtext NULL,
  `status_sync_feeder` enum('belum','sudah','gagal') NULL DEFAULT 'belum',
  `last_sync_feeder` datetime NULL,
  `last_error_feeder` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_konversi_mbkm`),
  UNIQUE KEY `uniq_konversi_mbkm` (`id_aktivitas_mahasiswa`, `id_mahasiswa`, `id_mk`),
  KEY `idx_konversi_mbkm_registrasi` (`id_registrasi_mahasiswa_feeder`),
  KEY `idx_konversi_mbkm_matkul_feeder` (`id_matkul_feeder`),
  KEY `idx_konversi_mbkm_status_sync_feeder` (`status_sync_feeder`),
  CONSTRAINT `fk_konversi_mbkm_aktivitas` FOREIGN KEY (`id_aktivitas_mahasiswa`) REFERENCES `aktivitas_mahasiswa` (`id_aktivitas_mahasiswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_konversi_mbkm_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konversi_mbkm_mk` FOREIGN KEY (`id_mk`) REFERENCES `mata_kuliah` (`id_mk`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `schema_migrations` (`version`, `description`)
VALUES ('004_transaksi_pelaporan_pddikti', 'Tahap 4 transaksi pelaporan PDDikti: KRS, nilai, KHS, AKM, status, lulus DO, prestasi, MBKM');
