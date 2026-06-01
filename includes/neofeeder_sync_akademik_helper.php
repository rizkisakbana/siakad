<?php

function nf_clean($value)
{
    return trim((string)($value ?? ''));
}

function nf_num($value)
{
    return (float)($value ?? 0);
}

function nf_id_from_response($response, $keys)
{
    $data = $response['data'] ?? null;
    if (!$data && isset($response['response']['data'])) {
        $data = $response['response']['data'];
    }

    if (isset($data[0]) && is_array($data[0])) {
        $data = $data[0];
    }

    if (!is_array($data)) {
        return '';
    }

    foreach ($keys as $key) {
        if (!empty($data[$key])) {
            return $data[$key];
        }
    }

    return '';
}

function nf_update_sync($conn, $table, $pk, $id, $status, $error = null, $id_column = null, $id_feeder = null, $raw = null)
{
    $table = mysqli_real_escape_string($conn, $table);
    $pk = mysqli_real_escape_string($conn, $pk);
    $id = intval($id);
    $status = mysqli_real_escape_string($conn, $status);
    $error_sql = $error !== null ? "'" . mysqli_real_escape_string($conn, $error) . "'" : "NULL";
    $sets = [
        "status_sync_feeder = '$status'",
        "last_sync_feeder = NOW()",
        "last_error_feeder = $error_sql",
    ];

    if ($id_column && $id_feeder) {
        $id_column = mysqli_real_escape_string($conn, $id_column);
        $id_feeder = mysqli_real_escape_string($conn, $id_feeder);
        $sets[] = "$id_column = '$id_feeder'";
    }

    if ($raw !== null) {
        $raw_sql = mysqli_real_escape_string($conn, json_encode($raw, JSON_UNESCAPED_UNICODE));
        $sets[] = "raw_feeder_data = '$raw_sql'";
    }

    mysqli_query($conn, "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE `$pk` = '$id'");
}

function nf_sync_result()
{
    return ['total' => 0, 'berhasil' => 0, 'gagal' => 0, 'errors' => []];
}

function nf_add_error(&$result, $label, $message)
{
    $result['gagal']++;
    $result['errors'][] = $label . ': ' . $message;
}

function sync_kurikulum_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT k.*, COALESCE(NULLIF(k.id_prodi_feeder,''), NULLIF(p.id_prodi_feeder,''), NULLIF(p.id_feeder,'')) AS feeder_prodi
        FROM kurikulum k
        JOIN prodi p ON p.id_prodi = k.id_prodi
        WHERE k.status = 'aktif'
          AND (k.status_sync_feeder IS NULL OR k.status_sync_feeder IN ('belum','gagal'))
        ORDER BY k.id_kurikulum ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = $r['nama_kurikulum'];

        if (empty($r['feeder_prodi']) || empty($r['id_semester_mulai_feeder'])) {
            nf_update_sync($conn, 'kurikulum', 'id_kurikulum', $r['id_kurikulum'], 'gagal', 'ID prodi Feeder atau semester mulai kosong.');
            nf_add_error($result, $label, 'ID prodi Feeder atau semester mulai kosong.');
            continue;
        }

        $record = [
            'nama_kurikulum' => nf_clean($r['nama_kurikulum']),
            'id_prodi' => nf_clean($r['feeder_prodi']),
            'id_semester' => nf_clean($r['id_semester_mulai_feeder']),
            'jumlah_sks_lulus' => (int)$r['jumlah_sks_lulus'],
            'jumlah_sks_wajib' => (int)$r['jumlah_sks_wajib'],
            'jumlah_sks_pilihan' => (int)$r['jumlah_sks_pilihan'],
        ];

        $resp = neofeeder_request($conn, 'InsertKurikulum', '', '', '', '', $record, 'Kurikulum');
        if ($resp['success']) {
            $id_feeder = nf_id_from_response($resp, ['id_kurikulum', 'id_kurikulum_sp']);
            nf_update_sync($conn, 'kurikulum', 'id_kurikulum', $r['id_kurikulum'], 'sudah', null, 'id_kurikulum_feeder', $id_feeder, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'kurikulum', 'id_kurikulum', $r['id_kurikulum'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_matakuliah_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT mk.*, k.id_kurikulum_feeder, COALESCE(NULLIF(mk.id_prodi_feeder,''), NULLIF(k.id_prodi_feeder,''), NULLIF(p.id_prodi_feeder,''), NULLIF(p.id_feeder,'')) AS feeder_prodi
        FROM mata_kuliah mk
        JOIN kurikulum k ON k.id_kurikulum = mk.id_kurikulum
        JOIN prodi p ON p.id_prodi = k.id_prodi
        WHERE mk.status = 'aktif'
          AND (mk.status_sync_feeder IS NULL OR mk.status_sync_feeder IN ('belum','gagal'))
        ORDER BY mk.id_mk ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = $r['kode_mk'] . ' - ' . $r['nama_mk'];

        if (empty($r['feeder_prodi'])) {
            nf_update_sync($conn, 'mata_kuliah', 'id_mk', $r['id_mk'], 'gagal', 'ID prodi Feeder kosong.');
            nf_add_error($result, $label, 'ID prodi Feeder kosong.');
            continue;
        }

        $record = [
            'id_prodi' => nf_clean($r['feeder_prodi']),
            'kode_mata_kuliah' => nf_clean($r['kode_mk']),
            'nama_mata_kuliah' => nf_clean($r['nama_mk']),
            'id_jenis_mata_kuliah' => nf_clean($r['id_jenis_mata_kuliah_feeder'] ?: 'A'),
            'sks_mata_kuliah' => nf_num($r['sks_teori']) + nf_num($r['sks_praktik']),
            'sks_tatap_muka' => nf_num($r['sks_tatap_muka'] ?: $r['sks_teori']),
            'sks_praktek' => nf_num($r['sks_praktik']),
            'sks_praktek_lapangan' => nf_num($r['sks_praktik_lapangan']),
            'sks_simulasi' => nf_num($r['sks_simulasi']),
            'ada_sap' => nf_clean($r['ada_sap']),
            'ada_silabus' => nf_clean($r['ada_silabus']),
            'ada_bahan_ajar' => nf_clean($r['ada_bahan_ajar']),
            'ada_acara_praktek' => nf_clean($r['ada_acara_praktik']),
        ];

        $resp = neofeeder_request($conn, 'InsertMataKuliah', '', '', '', '', $record, 'Mata Kuliah');
        if ($resp['success']) {
            $id_feeder = nf_id_from_response($resp, ['id_matkul', 'id_mata_kuliah']);
            nf_update_sync($conn, 'mata_kuliah', 'id_mk', $r['id_mk'], 'sudah', null, 'id_matkul_feeder', $id_feeder, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'mata_kuliah', 'id_mk', $r['id_mk'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_kelas_kuliah_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM kelas_kuliah
        WHERE status = 'aktif'
          AND (status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal'))
        ORDER BY id_kelas_kuliah ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = $r['nama_kelas_kuliah'];

        if (empty($r['id_semester_feeder']) || empty($r['id_prodi_feeder']) || empty($r['id_matkul_feeder'])) {
            nf_update_sync($conn, 'kelas_kuliah', 'id_kelas_kuliah', $r['id_kelas_kuliah'], 'gagal', 'Semester/prodi/matkul Feeder belum lengkap.');
            nf_add_error($result, $label, 'Semester/prodi/matkul Feeder belum lengkap.');
            continue;
        }

        $record = [
            'id_semester' => nf_clean($r['id_semester_feeder']),
            'id_prodi' => nf_clean($r['id_prodi_feeder']),
            'id_matkul' => nf_clean($r['id_matkul_feeder']),
            'nama_kelas_kuliah' => nf_clean($r['nama_kelas_kuliah']),
            'bahasan' => nf_clean($r['bahasan']),
            'tanggal_mulai_efektif' => nf_clean($r['tanggal_mulai']),
            'tanggal_akhir_efektif' => nf_clean($r['tanggal_selesai']),
        ];

        $resp = neofeeder_request($conn, 'InsertKelasKuliah', '', '', '', '', $record, 'Kelas Kuliah');
        if ($resp['success']) {
            $id_feeder = nf_id_from_response($resp, ['id_kelas_kuliah']);
            nf_update_sync($conn, 'kelas_kuliah', 'id_kelas_kuliah', $r['id_kelas_kuliah'], 'sudah', null, 'id_kelas_kuliah_feeder', $id_feeder, $resp['data'] ?? $resp['response']);
            if (!empty($r['id_jadwal']) && $id_feeder) {
                $id_feeder_db = mysqli_real_escape_string($conn, $id_feeder);
                mysqli_query($conn, "UPDATE jadwal_kuliah SET id_kelas_kuliah_feeder='$id_feeder_db', status_sync_feeder='sudah', last_sync_feeder=NOW(), last_error_feeder=NULL WHERE id_jadwal='{$r['id_jadwal']}'");
            }
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'kelas_kuliah', 'id_kelas_kuliah', $r['id_kelas_kuliah'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_dosen_pengajar_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM dosen_pengajar_kelas
        WHERE status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal')
        ORDER BY id_dosen_pengajar ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = 'Pengajar #' . $r['id_dosen_pengajar'];

        if (empty($r['id_kelas_kuliah_feeder']) || empty($r['id_dosen_feeder'])) {
            nf_update_sync($conn, 'dosen_pengajar_kelas', 'id_dosen_pengajar', $r['id_dosen_pengajar'], 'gagal', 'ID kelas kuliah atau dosen Feeder kosong.');
            nf_add_error($result, $label, 'ID kelas kuliah atau dosen Feeder kosong.');
            continue;
        }

        $record = [
            'id_kelas_kuliah' => nf_clean($r['id_kelas_kuliah_feeder']),
            'id_dosen' => nf_clean($r['id_dosen_feeder']),
            'jumlah_pertemuan_direncanakan' => (int)$r['rencana_tatap_muka'],
            'jumlah_pertemuan_realisasi' => (int)$r['realisasi_tatap_muka'],
            'sks_substansi_total' => nf_num($r['sks_substansi_total']),
        ];

        $resp = neofeeder_request($conn, 'InsertDosenPengajarKelasKuliah', '', '', '', '', $record, 'Dosen Pengajar Kelas');
        if ($resp['success']) {
            nf_update_sync($conn, 'dosen_pengajar_kelas', 'id_dosen_pengajar', $r['id_dosen_pengajar'], 'sudah', null, null, null, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'dosen_pengajar_kelas', 'id_dosen_pengajar', $r['id_dosen_pengajar'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_peserta_kelas_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM peserta_kelas_kuliah
        WHERE status_peserta = 'aktif'
          AND (status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal'))
        ORDER BY id_peserta_kelas ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = $r['nim'] ?: ('Peserta #' . $r['id_peserta_kelas']);

        if (empty($r['id_kelas_kuliah_feeder']) || empty($r['id_registrasi_mahasiswa_feeder'])) {
            nf_update_sync($conn, 'peserta_kelas_kuliah', 'id_peserta_kelas', $r['id_peserta_kelas'], 'gagal', 'ID kelas kuliah atau registrasi mahasiswa Feeder kosong.');
            nf_add_error($result, $label, 'ID kelas kuliah atau registrasi mahasiswa Feeder kosong.');
            continue;
        }

        $record = [
            'id_kelas_kuliah' => nf_clean($r['id_kelas_kuliah_feeder']),
            'id_registrasi_mahasiswa' => nf_clean($r['id_registrasi_mahasiswa_feeder']),
        ];

        $resp = neofeeder_request($conn, 'InsertPesertaKelasKuliah', '', '', '', '', $record, 'Peserta Kelas');
        if ($resp['success']) {
            nf_update_sync($conn, 'peserta_kelas_kuliah', 'id_peserta_kelas', $r['id_peserta_kelas'], 'sudah', null, null, null, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'peserta_kelas_kuliah', 'id_peserta_kelas', $r['id_peserta_kelas'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_nilai_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM nilai
        WHERE status_publish = 'publish'
          AND (status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal'))
        ORDER BY id_nilai ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = 'Nilai #' . $r['id_nilai'];

        if (empty($r['id_kelas_kuliah_feeder']) || empty($r['id_registrasi_mahasiswa_feeder'])) {
            nf_update_sync($conn, 'nilai', 'id_nilai', $r['id_nilai'], 'gagal', 'ID kelas kuliah atau registrasi mahasiswa Feeder kosong.');
            nf_add_error($result, $label, 'ID kelas kuliah atau registrasi mahasiswa Feeder kosong.');
            continue;
        }

        $record = [
            'id_kelas_kuliah' => nf_clean($r['id_kelas_kuliah_feeder']),
            'id_registrasi_mahasiswa' => nf_clean($r['id_registrasi_mahasiswa_feeder']),
            'nilai_angka' => nf_num($r['nilai_akhir']),
            'nilai_huruf' => nf_clean($r['nilai_huruf']),
            'nilai_indeks' => nf_num($r['nilai_indeks'] ?: $r['bobot']),
        ];

        $resp = neofeeder_request($conn, 'UpdateNilaiPerkuliahanKelas', '', '', '', '', $record, 'Nilai');
        if ($resp['success']) {
            nf_update_sync($conn, 'nilai', 'id_nilai', $r['id_nilai'], 'sudah', null, null, null, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'nilai', 'id_nilai', $r['id_nilai'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_akm_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM aktivitas_kuliah_mahasiswa
        WHERE status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal')
        ORDER BY id_akm ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = 'AKM #' . $r['id_akm'];

        if (empty($r['id_registrasi_mahasiswa_feeder']) || empty($r['id_semester_feeder']) || empty($r['id_status_mahasiswa_feeder'])) {
            nf_update_sync($conn, 'aktivitas_kuliah_mahasiswa', 'id_akm', $r['id_akm'], 'gagal', 'Registrasi, semester, atau status mahasiswa Feeder kosong.');
            nf_add_error($result, $label, 'Registrasi, semester, atau status mahasiswa Feeder kosong.');
            continue;
        }

        $record = [
            'id_registrasi_mahasiswa' => nf_clean($r['id_registrasi_mahasiswa_feeder']),
            'id_semester' => nf_clean($r['id_semester_feeder']),
            'id_status_mahasiswa' => nf_clean($r['id_status_mahasiswa_feeder']),
            'ips' => nf_num($r['ips']),
            'ipk' => nf_num($r['ipk']),
            'sks_semester' => (int)$r['sks_semester'],
            'total_sks' => (int)$r['sks_total'],
            'biaya_kuliah_smt' => nf_num($r['biaya_kuliah_smt']),
        ];

        $resp = neofeeder_request($conn, 'InsertPerkuliahanMahasiswa', '', '', '', '', $record, 'AKM');
        if ($resp['success']) {
            nf_update_sync($conn, 'aktivitas_kuliah_mahasiswa', 'id_akm', $r['id_akm'], 'sudah', null, null, null, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'aktivitas_kuliah_mahasiswa', 'id_akm', $r['id_akm'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}

function sync_lulus_do_to_feeder($conn, $limit = 50)
{
    $result = nf_sync_result();
    $limit = max(1, (int)$limit);
    $q = mysqli_query($conn, "
        SELECT *
        FROM mahasiswa_lulus_do
        WHERE status_sync_feeder IS NULL OR status_sync_feeder IN ('belum','gagal')
        ORDER BY id_lulus_do ASC
        LIMIT $limit
    ");

    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $result['total']++;
        $label = 'Lulus/DO #' . $r['id_lulus_do'];

        if (empty($r['id_registrasi_mahasiswa_feeder']) || empty($r['id_jenis_keluar_feeder']) || empty($r['tanggal_keluar'])) {
            nf_update_sync($conn, 'mahasiswa_lulus_do', 'id_lulus_do', $r['id_lulus_do'], 'gagal', 'Registrasi, jenis keluar, atau tanggal keluar Feeder kosong.');
            nf_add_error($result, $label, 'Registrasi, jenis keluar, atau tanggal keluar Feeder kosong.');
            continue;
        }

        $record = [
            'id_registrasi_mahasiswa' => nf_clean($r['id_registrasi_mahasiswa_feeder']),
            'id_jenis_keluar' => nf_clean($r['id_jenis_keluar_feeder']),
            'tanggal_keluar' => nf_clean($r['tanggal_keluar']),
            'keterangan' => nf_clean($r['keterangan']),
            'nomor_sk_yudisium' => nf_clean($r['nomor_sk_yudisium']),
            'tanggal_sk_yudisium' => nf_clean($r['tanggal_sk_yudisium']),
            'ipk' => nf_num($r['ipk']),
            'nomor_ijazah' => nf_clean($r['nomor_ijazah']),
            'judul_skripsi' => nf_clean($r['judul_tugas_akhir']),
        ];

        $resp = neofeeder_request($conn, 'InsertMahasiswaLulusDO', '', '', '', '', $record, 'Lulus DO');
        if ($resp['success']) {
            nf_update_sync($conn, 'mahasiswa_lulus_do', 'id_lulus_do', $r['id_lulus_do'], 'sudah', null, null, null, $resp['data'] ?? $resp['response']);
            $result['berhasil']++;
        } else {
            nf_update_sync($conn, 'mahasiswa_lulus_do', 'id_lulus_do', $r['id_lulus_do'], 'gagal', $resp['message']);
            nf_add_error($result, $label, $resp['message']);
        }
    }

    return $result;
}
