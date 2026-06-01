ALTER TABLE `krs_detail`
  MODIFY `id_jadwal` int(11) NULL;

ALTER TABLE `krs_detail`
  DROP INDEX `unique_krs_jadwal`,
  ADD UNIQUE KEY `unique_krs_kelas_kuliah` (`id_krs`, `id_kelas_kuliah`),
  ADD KEY `idx_krs_detail_kelas_feeder` (`id_kelas_kuliah_feeder`);

