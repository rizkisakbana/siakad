-- Tahap 5: Fondasi modul internal SIAKAD.
-- PMB, keuangan, presensi, tugas akhir, yudisium, wisuda, notifikasi, gateway, dan laporan.

CREATE TABLE IF NOT EXISTS `pmb_gelombang` (
  `id_gelombang` int NOT NULL AUTO_INCREMENT,
  `nama_gelombang` varchar(100) NOT NULL,
  `tahun_akademik` varchar(20) NOT NULL,
  `tanggal_mulai` date NULL,
  `tanggal_selesai` date NULL,
  `biaya_pendaftaran` decimal(15,2) NULL DEFAULT 0.00,
  `status` enum('aktif','nonaktif') NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_gelombang`),
  KEY `idx_pmb_gelombang_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pendaftar_pmb` (
  `id_pendaftar` int NOT NULL AUTO_INCREMENT,
  `id_gelombang` int NULL,
  `nomor_pendaftaran` varchar(50) NOT NULL,
  `id_prodi_pilihan` int NULL,
  `nama_pendaftar` varchar(150) NOT NULL,
  `jenis_kelamin` enum('L','P') NULL,
  `tempat_lahir` varchar(100) NULL,
  `tanggal_lahir` date NULL,
  `nik` varchar(30) NULL,
  `nisn` varchar(30) NULL,
  `email` varchar(150) NULL,
  `no_hp` varchar(30) NULL,
  `alamat` text NULL,
  `asal_sekolah` varchar(150) NULL,
  `tahun_lulus` year NULL,
  `status_pendaftaran` enum('draft','daftar','verifikasi','diterima','ditolak','mengundurkan diri','konversi') NULL DEFAULT 'daftar',
  `tanggal_daftar` datetime NULL DEFAULT current_timestamp(),
  `tanggal_verifikasi` datetime NULL,
  `diverifikasi_oleh` int NULL,
  `catatan_verifikasi` text NULL,
  `id_mahasiswa` int NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pendaftar`),
  UNIQUE KEY `uniq_pmb_nomor` (`nomor_pendaftaran`),
  KEY `idx_pmb_status` (`status_pendaftaran`),
  KEY `idx_pmb_prodi` (`id_prodi_pilihan`),
  KEY `idx_pmb_mahasiswa` (`id_mahasiswa`),
  CONSTRAINT `fk_pmb_gelombang` FOREIGN KEY (`id_gelombang`) REFERENCES `pmb_gelombang` (`id_gelombang`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pmb_prodi` FOREIGN KEY (`id_prodi_pilihan`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pmb_verifikator` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pmb_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jenis_biaya` (
  `id_jenis_biaya` int NOT NULL AUTO_INCREMENT,
  `kode_biaya` varchar(50) NOT NULL,
  `nama_biaya` varchar(150) NOT NULL,
  `kategori` enum('pendaftaran','kuliah','praktikum','ujian','wisuda','lainnya') NULL DEFAULT 'kuliah',
  `nominal_default` decimal(15,2) NULL DEFAULT 0.00,
  `wajib` enum('ya','tidak') NULL DEFAULT 'ya',
  `status` enum('aktif','nonaktif') NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_jenis_biaya`),
  UNIQUE KEY `uniq_jenis_biaya_kode` (`kode_biaya`),
  KEY `idx_jenis_biaya_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `jenis_biaya` (`kode_biaya`, `nama_biaya`, `kategori`, `nominal_default`, `wajib`)
VALUES
  ('PMB', 'Biaya Pendaftaran PMB', 'pendaftaran', 0, 'ya'),
  ('SPP', 'SPP/UKT Semester', 'kuliah', 0, 'ya'),
  ('PRAKTIKUM', 'Biaya Praktikum', 'praktikum', 0, 'tidak'),
  ('TA', 'Biaya Tugas Akhir', 'ujian', 0, 'tidak'),
  ('WISUDA', 'Biaya Wisuda', 'wisuda', 0, 'tidak');

CREATE TABLE IF NOT EXISTS `tagihan_mahasiswa` (
  `id_tagihan` int NOT NULL AUTO_INCREMENT,
  `nomor_tagihan` varchar(50) NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_tahun` int NULL,
  `id_jenis_biaya` int NOT NULL,
  `tanggal_tagihan` date NULL,
  `tanggal_jatuh_tempo` date NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `potongan` decimal(15,2) NULL DEFAULT 0.00,
  `denda` decimal(15,2) NULL DEFAULT 0.00,
  `total_tagihan` decimal(15,2) GENERATED ALWAYS AS (`nominal` - `potongan` + `denda`) STORED,
  `total_bayar` decimal(15,2) NULL DEFAULT 0.00,
  `status_tagihan` enum('belum_bayar','sebagian','lunas','dibatalkan') NULL DEFAULT 'belum_bayar',
  `keterangan` text NULL,
  `created_by` int NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_tagihan`),
  UNIQUE KEY `uniq_nomor_tagihan` (`nomor_tagihan`),
  KEY `idx_tagihan_mahasiswa` (`id_mahasiswa`),
  KEY `idx_tagihan_status` (`status_tagihan`),
  CONSTRAINT `fk_tagihan_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tagihan_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tagihan_jenis_biaya` FOREIGN KEY (`id_jenis_biaya`) REFERENCES `jenis_biaya` (`id_jenis_biaya`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tagihan_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pembayaran_mahasiswa` (
  `id_pembayaran` int NOT NULL AUTO_INCREMENT,
  `nomor_pembayaran` varchar(50) NOT NULL,
  `id_tagihan` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `tanggal_bayar` datetime NULL DEFAULT current_timestamp(),
  `jumlah_bayar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metode_bayar` enum('tunai','transfer','virtual_account','qris','lainnya') NULL DEFAULT 'tunai',
  `bukti_bayar` varchar(255) NULL,
  `status_pembayaran` enum('pending','valid','ditolak','dibatalkan') NULL DEFAULT 'pending',
  `diverifikasi_oleh` int NULL,
  `tanggal_verifikasi` datetime NULL,
  `catatan` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pembayaran`),
  UNIQUE KEY `uniq_nomor_pembayaran` (`nomor_pembayaran`),
  KEY `idx_pembayaran_tagihan` (`id_tagihan`),
  KEY `idx_pembayaran_mahasiswa` (`id_mahasiswa`),
  KEY `idx_pembayaran_status` (`status_pembayaran`),
  CONSTRAINT `fk_pembayaran_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan_mahasiswa` (`id_tagihan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pembayaran_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pembayaran_verifikator` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presensi_kuliah` (
  `id_presensi_kuliah` int NOT NULL AUTO_INCREMENT,
  `id_jadwal` int NULL,
  `id_kelas_kuliah` int NULL,
  `pertemuan_ke` int NOT NULL,
  `tanggal_presensi` date NOT NULL,
  `jam_mulai` time NULL,
  `jam_selesai` time NULL,
  `materi` text NULL,
  `status_pertemuan` enum('terjadwal','selesai','batal') NULL DEFAULT 'terjadwal',
  `dibuat_oleh` int NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_presensi_kuliah`),
  UNIQUE KEY `uniq_presensi_pertemuan` (`id_jadwal`, `pertemuan_ke`, `tanggal_presensi`),
  KEY `idx_presensi_kelas_kuliah` (`id_kelas_kuliah`),
  CONSTRAINT `fk_presensi_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kuliah` (`id_jadwal`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_presensi_kelas_kuliah` FOREIGN KEY (`id_kelas_kuliah`) REFERENCES `kelas_kuliah` (`id_kelas_kuliah`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_presensi_user` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presensi_mahasiswa` (
  `id_presensi_mahasiswa` int NOT NULL AUTO_INCREMENT,
  `id_presensi_kuliah` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_peserta_kelas` int NULL,
  `status_presensi` enum('hadir','izin','sakit','alpha') NULL DEFAULT 'hadir',
  `waktu_presensi` datetime NULL,
  `catatan` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_presensi_mahasiswa`),
  UNIQUE KEY `uniq_presensi_mahasiswa` (`id_presensi_kuliah`, `id_mahasiswa`),
  KEY `idx_presensi_mahasiswa_mhs` (`id_mahasiswa`),
  CONSTRAINT `fk_presensi_mhs_header` FOREIGN KEY (`id_presensi_kuliah`) REFERENCES `presensi_kuliah` (`id_presensi_kuliah`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_presensi_mhs_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_presensi_mhs_peserta` FOREIGN KEY (`id_peserta_kelas`) REFERENCES `peserta_kelas_kuliah` (`id_peserta_kelas`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tugas_akhir` (
  `id_ta` int NOT NULL AUTO_INCREMENT,
  `id_mahasiswa` int NOT NULL,
  `judul` text NOT NULL,
  `abstrak` text NULL,
  `tanggal_pengajuan` date NULL,
  `status_ta` enum('draft','diajukan','disetujui','ditolak','seminar','sidang','lulus','revisi') NULL DEFAULT 'diajukan',
  `catatan` text NULL,
  `file_proposal` varchar(255) NULL,
  `file_laporan` varchar(255) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ta`),
  KEY `idx_ta_mahasiswa` (`id_mahasiswa`),
  KEY `idx_ta_status` (`status_ta`),
  CONSTRAINT `fk_ta_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ta_pembimbing` (
  `id_ta_pembimbing` int NOT NULL AUTO_INCREMENT,
  `id_ta` int NOT NULL,
  `id_dosen` int NOT NULL,
  `pembimbing_ke` int NULL DEFAULT 1,
  `status` enum('aktif','nonaktif') NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ta_pembimbing`),
  UNIQUE KEY `uniq_ta_pembimbing` (`id_ta`, `id_dosen`, `pembimbing_ke`),
  CONSTRAINT `fk_ta_pembimbing_ta` FOREIGN KEY (`id_ta`) REFERENCES `tugas_akhir` (`id_ta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_pembimbing_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ta_penguji` (
  `id_ta_penguji` int NOT NULL AUTO_INCREMENT,
  `id_ta` int NOT NULL,
  `id_dosen` int NOT NULL,
  `jenis_ujian` enum('seminar','sidang') NOT NULL,
  `penguji_ke` int NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ta_penguji`),
  UNIQUE KEY `uniq_ta_penguji` (`id_ta`, `id_dosen`, `jenis_ujian`, `penguji_ke`),
  CONSTRAINT `fk_ta_penguji_ta` FOREIGN KEY (`id_ta`) REFERENCES `tugas_akhir` (`id_ta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_penguji_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ta_jadwal` (
  `id_ta_jadwal` int NOT NULL AUTO_INCREMENT,
  `id_ta` int NOT NULL,
  `jenis_ujian` enum('seminar','sidang') NOT NULL,
  `tanggal_ujian` date NOT NULL,
  `jam_mulai` time NULL,
  `jam_selesai` time NULL,
  `id_ruangan` int NULL,
  `status_jadwal` enum('terjadwal','selesai','batal') NULL DEFAULT 'terjadwal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ta_jadwal`),
  KEY `idx_ta_jadwal_ta` (`id_ta`),
  CONSTRAINT `fk_ta_jadwal_ta` FOREIGN KEY (`id_ta`) REFERENCES `tugas_akhir` (`id_ta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_jadwal_ruangan` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ta_nilai` (
  `id_ta_nilai` int NOT NULL AUTO_INCREMENT,
  `id_ta` int NOT NULL,
  `jenis_ujian` enum('seminar','sidang') NOT NULL,
  `id_dosen` int NULL,
  `nilai_angka` decimal(5,2) NULL DEFAULT 0.00,
  `nilai_huruf` varchar(5) NULL,
  `catatan` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ta_nilai`),
  KEY `idx_ta_nilai_ta` (`id_ta`),
  CONSTRAINT `fk_ta_nilai_ta` FOREIGN KEY (`id_ta`) REFERENCES `tugas_akhir` (`id_ta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_nilai_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `yudisium` (
  `id_yudisium` int NOT NULL AUTO_INCREMENT,
  `nomor_yudisium` varchar(50) NOT NULL,
  `id_tahun` int NULL,
  `tanggal_yudisium` date NOT NULL,
  `nomor_sk` varchar(100) NULL,
  `tanggal_sk` date NULL,
  `status_yudisium` enum('draft','validasi','selesai','batal') NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_yudisium`),
  UNIQUE KEY `uniq_yudisium_nomor` (`nomor_yudisium`),
  CONSTRAINT `fk_yudisium_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_akademik` (`id_tahun`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `peserta_yudisium` (
  `id_peserta_yudisium` int NOT NULL AUTO_INCREMENT,
  `id_yudisium` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_ta` int NULL,
  `ipk` decimal(4,2) NULL DEFAULT 0.00,
  `total_sks` int NULL DEFAULT 0,
  `predikat` varchar(100) NULL,
  `status_validasi` enum('belum','valid','ditolak') NULL DEFAULT 'belum',
  `catatan` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_peserta_yudisium`),
  UNIQUE KEY `uniq_peserta_yudisium` (`id_yudisium`, `id_mahasiswa`),
  KEY `idx_peserta_yudisium_mahasiswa` (`id_mahasiswa`),
  CONSTRAINT `fk_peserta_yudisium_header` FOREIGN KEY (`id_yudisium`) REFERENCES `yudisium` (`id_yudisium`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_yudisium_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_yudisium_ta` FOREIGN KEY (`id_ta`) REFERENCES `tugas_akhir` (`id_ta`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wisuda` (
  `id_wisuda` int NOT NULL AUTO_INCREMENT,
  `nama_periode` varchar(100) NOT NULL,
  `tanggal_wisuda` date NOT NULL,
  `lokasi` varchar(255) NULL,
  `kuota` int NULL DEFAULT 0,
  `status_wisuda` enum('draft','buka','tutup','selesai','batal') NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_wisuda`),
  KEY `idx_wisuda_status` (`status_wisuda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `peserta_wisuda` (
  `id_peserta_wisuda` int NOT NULL AUTO_INCREMENT,
  `id_wisuda` int NOT NULL,
  `id_mahasiswa` int NOT NULL,
  `id_yudisium` int NULL,
  `nomor_ijazah` varchar(100) NULL,
  `nomor_transkrip` varchar(100) NULL,
  `status_pendaftaran` enum('daftar','valid','ditolak','hadir','tidak_hadir') NULL DEFAULT 'daftar',
  `tanggal_daftar` datetime NULL DEFAULT current_timestamp(),
  `catatan` text NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_peserta_wisuda`),
  UNIQUE KEY `uniq_peserta_wisuda` (`id_wisuda`, `id_mahasiswa`),
  KEY `idx_peserta_wisuda_mahasiswa` (`id_mahasiswa`),
  CONSTRAINT `fk_peserta_wisuda_header` FOREIGN KEY (`id_wisuda`) REFERENCES `wisuda` (`id_wisuda`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_wisuda_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_wisuda_yudisium` FOREIGN KEY (`id_yudisium`) REFERENCES `yudisium` (`id_yudisium`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `notifikasi`
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() AFTER `created_at`,
  ADD INDEX IF NOT EXISTS `idx_notifikasi_user_status` (`id_user`, `status_baca`);

ALTER TABLE `email_queue`
  ADD INDEX IF NOT EXISTS `idx_email_queue_status` (`status`, `scheduled_at`);

ALTER TABLE `whatsapp_queue`
  ADD INDEX IF NOT EXISTS `idx_whatsapp_queue_status` (`status`, `scheduled_at`);

INSERT IGNORE INTO `schema_migrations` (`version`, `description`)
VALUES ('005_modul_internal_siakad', 'Tahap 5 fondasi modul internal: PMB, keuangan, presensi, TA, yudisium, wisuda, notifikasi, laporan');
