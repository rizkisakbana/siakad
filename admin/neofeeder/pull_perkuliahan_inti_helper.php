<?php
function pi_value($row, $keys, $default = '')
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return trim((string) $row[$key]);
        }
    }

    return $default;
}

function pi_sql($conn, $value)
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return "NULL";
    }

    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

function pi_num($value)
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return 0;
    }

    return (float) str_replace(',', '.', $value);
}

function pi_int($value)
{
    return (int) round(pi_num($value));
}

function pi_date($value)
{
    if (function_exists('feeder_date_to_mysql')) {
        return feeder_date_to_mysql($value);
    }

    return '';
}

function pi_result()
{
    return [
        'total' => 0,
        'insert' => 0,
        'update' => 0,
        'skip' => 0,
        'gagal' => 0,
        'pesan_gagal' => [],
        'next_offset' => 0
    ];
}

function pi_add_error(&$result, $label, $message)
{
    $result['gagal']++;
    $result['pesan_gagal'][] = $label . ': ' . $message;
}

function pi_first_id($conn, $sql, $field)
{
    $row = nf_query_one($conn, $sql);
    if ($row) {
        return (int) $row[$field];
    }

    return null;
}

function pi_tahun_lokal($conn, $id_semester)
{
    $id_semester = trim((string) $id_semester);
    if ($id_semester === '') {
        return null;
    }

    $id = mysqli_real_escape_string($conn, $id_semester);
    return pi_first_id($conn, "
        SELECT id_tahun
        FROM tahun_akademik
        WHERE id_semester_feeder = '$id'
           OR id_feeder = '$id'
           OR periode_pelaporan = '$id'
        ORDER BY id_tahun DESC
        LIMIT 1
    ", 'id_tahun');
}

function pi_semester_label($id_semester, $nama_semester = '')
{
    $nama = strtolower((string) $nama_semester);
    if (strpos($nama, 'genap') !== false) {
        return 'Genap';
    }
    if (strpos($nama, 'pendek') !== false || strpos($nama, 'antara') !== false) {
        return 'Pendek';
    }

    $last = substr((string) $id_semester, -1);
    if ($last === '2') {
        return 'Genap';
    }
    if ($last === '3') {
        return 'Pendek';
    }

    return 'Ganjil';
}

function pi_tahun_ajaran_label($id_semester, $nama_semester = '')
{
    if (preg_match('/\d{4}\s*\/\s*\d{4}/', (string) $nama_semester, $m)) {
        return str_replace(' ', '', $m[0]);
    }

    $tahun_awal = (int) substr((string) $id_semester, 0, 4);
    if ($tahun_awal < 1900) {
        $tahun_awal = (int) date('Y');
    }

    return $tahun_awal . '/' . ($tahun_awal + 1);
}

function pi_tahun_lokal_atau_buat($conn, $row)
{
    $id_semester = pi_value($row, ['id_semester', 'id_smt']);
    $found = pi_tahun_lokal($conn, $id_semester);
    if ($found) {
        return $found;
    }

    if ($id_semester === '') {
        return null;
    }

    $nama_semester = pi_value($row, ['nama_semester', 'nm_smt']);
    $tahun_ajaran = pi_tahun_ajaran_label($id_semester, $nama_semester);
    $semester = pi_semester_label($id_semester, $nama_semester);
    $kode_semester = substr($id_semester, -1);
    $raw = json_encode([
        'id_semester' => $id_semester,
        'nama_semester' => $nama_semester,
        'sumber' => 'fallback_pull_perkuliahan'
    ], JSON_UNESCAPED_UNICODE);

    $ok = mysqli_query($conn, "
        INSERT INTO tahun_akademik (
            id_feeder, id_semester_feeder, periode_pelaporan, tahun_ajaran,
            kode_semester, nama_semester, tipe_periode, raw_feeder_data,
            status_sync_feeder, last_sync_feeder, tahun, semester, status
        ) VALUES (
            " . pi_sql($conn, $id_semester) . ", " . pi_sql($conn, $id_semester) . ", " . pi_sql($conn, $id_semester) . ",
            " . pi_sql($conn, $tahun_ajaran) . ", " . pi_sql($conn, $kode_semester) . ",
            " . pi_sql($conn, $nama_semester ?: ($tahun_ajaran . ' ' . $semester)) . ",
            'semester', " . pi_sql($conn, $raw) . ", 'sudah', NOW(),
            " . pi_sql($conn, $tahun_ajaran) . ", " . pi_sql($conn, $semester) . ", 'nonaktif'
        )
    ");

    return $ok ? (int) mysqli_insert_id($conn) : null;
}

function pi_prodi_lokal($conn, $id_prodi_feeder)
{
    $id_prodi_feeder = trim((string) $id_prodi_feeder);
    if ($id_prodi_feeder === '') {
        return null;
    }

    $id = mysqli_real_escape_string($conn, $id_prodi_feeder);
    return pi_first_id($conn, "
        SELECT id_prodi
        FROM prodi
        WHERE id_prodi_feeder = '$id' OR id_feeder = '$id'
        LIMIT 1
    ", 'id_prodi');
}

function pi_matkul_lokal($conn, $id_matkul_feeder, $kode_mk = '', $id_prodi_feeder = '')
{
    $id_matkul_feeder = trim((string) $id_matkul_feeder);
    if ($id_matkul_feeder !== '') {
        $id = mysqli_real_escape_string($conn, $id_matkul_feeder);
        $found = pi_first_id($conn, "
            SELECT id_mk
            FROM mata_kuliah
            WHERE id_matkul_feeder = '$id'
            LIMIT 1
        ", 'id_mk');

        if ($found) {
            return $found;
        }
    }

    $kode_mk = trim((string) $kode_mk);
    if ($kode_mk === '') {
        return null;
    }

    $kode = mysqli_real_escape_string($conn, $kode_mk);
    $where = "mk.kode_mk = '$kode'";

    if (trim((string) $id_prodi_feeder) !== '') {
        $id_prodi = mysqli_real_escape_string($conn, trim((string) $id_prodi_feeder));
        $where .= " AND (mk.id_prodi_feeder = '$id_prodi' OR k.id_prodi_feeder = '$id_prodi' OR p.id_prodi_feeder = '$id_prodi' OR p.id_feeder = '$id_prodi')";
    }

    return pi_first_id($conn, "
        SELECT mk.id_mk
        FROM mata_kuliah mk
        LEFT JOIN kurikulum k ON mk.id_kurikulum = k.id_kurikulum
        LEFT JOIN prodi p ON k.id_prodi = p.id_prodi
        WHERE $where
        ORDER BY mk.id_mk ASC
        LIMIT 1
    ", 'id_mk');
}

function pi_kelas_lokal($conn, $id_kelas_kuliah_feeder)
{
    $id_kelas_kuliah_feeder = trim((string) $id_kelas_kuliah_feeder);
    if ($id_kelas_kuliah_feeder === '') {
        return null;
    }

    $id = mysqli_real_escape_string($conn, $id_kelas_kuliah_feeder);
    return pi_first_id($conn, "
        SELECT id_kelas_kuliah
        FROM kelas_kuliah
        WHERE id_kelas_kuliah_feeder = '$id'
        LIMIT 1
    ", 'id_kelas_kuliah');
}

function pi_dosen_lokal($conn, $row)
{
    $id_dosen_feeder = pi_value($row, ['id_dosen']);
    $nidn = pi_value($row, ['nidn']);

    $conditions = [];
    if ($id_dosen_feeder !== '') {
        $id = mysqli_real_escape_string($conn, $id_dosen_feeder);
        $conditions[] = "id_dosen_feeder = '$id'";
        $conditions[] = "id_feeder = '$id'";
    }

    if ($nidn !== '') {
        $safe = mysqli_real_escape_string($conn, $nidn);
        $conditions[] = "nidn = '$safe'";
    }

    if (empty($conditions)) {
        return null;
    }

    return pi_first_id($conn, "
        SELECT id_dosen
        FROM dosen
        WHERE " . implode(' OR ', $conditions) . "
        LIMIT 1
    ", 'id_dosen');
}

function pi_seed_dosen_dari_pengajar($conn, $row)
{
    $existing = pi_dosen_lokal($conn, $row);
    if ($existing) {
        return $existing;
    }

    $nama_dosen = pi_value($row, ['nama_dosen']);
    if ($nama_dosen === '') {
        return null;
    }

    $id_prodi = pi_prodi_lokal($conn, pi_value($row, ['id_prodi', 'id_sms']));
    $id_dosen_feeder = pi_value($row, ['id_dosen']);
    $nidn = pi_value($row, ['nidn']);
    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);

    $ok = mysqli_query($conn, "
        INSERT INTO dosen (
            id_prodi, id_prodi_feeder, nidn, nidk, nuptk, nama_dosen,
            raw_feeder_data, status_sync_feeder, last_sync_feeder, status,
            id_feeder, id_dosen_feeder
        ) VALUES (
            " . ($id_prodi ? "'$id_prodi'" : "NULL") . ",
            " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'])) . ",
            " . pi_sql($conn, $nidn) . ", NULL, " . pi_sql($conn, pi_value($row, ['nuptk'])) . ",
            " . pi_sql($conn, $nama_dosen) . ",
            " . pi_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif',
            " . pi_sql($conn, $id_dosen_feeder) . ", " . pi_sql($conn, $id_dosen_feeder) . "
        )
        ON DUPLICATE KEY UPDATE
            id_prodi = COALESCE(id_prodi, VALUES(id_prodi)),
            id_prodi_feeder = COALESCE(NULLIF(id_prodi_feeder, ''), VALUES(id_prodi_feeder)),
            nama_dosen = VALUES(nama_dosen),
            nuptk = COALESCE(NULLIF(nuptk, ''), VALUES(nuptk)),
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL,
            status = 'aktif',
            id_feeder = COALESCE(NULLIF(id_feeder, ''), VALUES(id_feeder)),
            id_dosen_feeder = COALESCE(NULLIF(id_dosen_feeder, ''), VALUES(id_dosen_feeder))
    ");

    if (!$ok) {
        return null;
    }

    return pi_dosen_lokal($conn, $row);
}

function pi_seed_kelas_kuliah_by_feeder($conn, $id_kelas_feeder)
{
    $id_kelas_feeder = trim((string) $id_kelas_feeder);
    if ($id_kelas_feeder === '') {
        return null;
    }

    $existing = pi_kelas_lokal($conn, $id_kelas_feeder);
    if ($existing) {
        return $existing;
    }

    $safe = mysqli_real_escape_string($conn, $id_kelas_feeder);
    $resp = neofeeder_request($conn, 'GetDetailKelasKuliah', "id_kelas_kuliah = '$safe'", '', 1, 0, null, 'Pull Detail Kelas Pengajar');
    if (!$resp['success'] || empty($resp['data'])) {
        return null;
    }

    $row = isset($resp['data'][0]) ? $resp['data'][0] : $resp['data'];
    if (!is_array($row)) {
        return null;
    }

    $id_tahun = pi_tahun_lokal_atau_buat($conn, $row);
    $id_prodi = pi_prodi_lokal($conn, pi_value($row, ['id_prodi', 'id_sms']));
    $id_mk = pi_matkul_lokal($conn, pi_value($row, ['id_matkul']), pi_value($row, ['kode_mata_kuliah']), pi_value($row, ['id_prodi', 'id_sms']));

    if (!$id_tahun || !$id_prodi || !$id_mk) {
        return null;
    }

    $label = pi_value($row, ['nama_kelas_kuliah'], $id_kelas_feeder);
    $kapasitas = pi_int(pi_value($row, ['kapasitas', 'kuota_pditt'], 40));
    if ($kapasitas < 1) {
        $kapasitas = 40;
    }

    $lingkup = strtolower(pi_value($row, ['lingkup_kelas'], 'internal'));
    if (!in_array($lingkup, ['internal', 'eksternal', 'campuran'], true)) {
        $lingkup = 'internal';
    }

    $mode = strtolower(pi_value($row, ['mode_kuliah'], 'tatap muka'));
    if (!in_array($mode, ['tatap muka', 'online', 'hybrid'], true)) {
        $mode = 'tatap muka';
    }

    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
    $ok = mysqli_query($conn, "
        INSERT INTO kelas_kuliah (
            id_kelas_kuliah_feeder, id_tahun, id_semester_feeder, id_prodi, id_prodi_feeder,
            id_mk, id_matkul_feeder, nama_kelas_kuliah, kode_kelas, bahasan,
            lingkup, mode_kuliah, tanggal_mulai, tanggal_selesai, kapasitas,
            raw_feeder_data, status_sync_feeder, last_sync_feeder, status
        ) VALUES (
            " . pi_sql($conn, $id_kelas_feeder) . ", '$id_tahun', " . pi_sql($conn, pi_value($row, ['id_semester', 'id_smt'])) . ",
            '$id_prodi', " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'])) . ",
            '$id_mk', " . pi_sql($conn, pi_value($row, ['id_matkul'])) . ",
            " . pi_sql($conn, $label) . ", " . pi_sql($conn, $label) . ", " . pi_sql($conn, pi_value($row, ['bahasan', 'bahasan_case'])) . ",
            '$lingkup', '$mode', " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_mulai_efektif', 'tgl_mulai_koas']))) . ",
            " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_akhir_efektif', 'tgl_selesai_koas']))) . ", '$kapasitas',
            " . pi_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif'
        )
    ");

    return $ok ? pi_kelas_lokal($conn, $id_kelas_feeder) : null;
}

function pi_mahasiswa_lokal($conn, $row)
{
    $id_reg = pi_value($row, ['id_registrasi_mahasiswa']);
    $id_mhs = pi_value($row, ['id_mahasiswa']);
    $nim = pi_value($row, ['nim', 'nipd']);

    $conditions = [];
    if ($id_reg !== '') {
        $safe = mysqli_real_escape_string($conn, $id_reg);
        $conditions[] = "id_registrasi_feeder = '$safe'";
    }

    if ($id_mhs !== '') {
        $safe = mysqli_real_escape_string($conn, $id_mhs);
        $conditions[] = "id_biodata_feeder = '$safe'";
        $conditions[] = "id_feeder = '$safe'";
    }

    if ($nim !== '') {
        $safe = mysqli_real_escape_string($conn, $nim);
        $conditions[] = "nim = '$safe'";
    }

    if (empty($conditions)) {
        return null;
    }

    return pi_first_id($conn, "
        SELECT id_mahasiswa
        FROM mahasiswa
        WHERE " . implode(' OR ', $conditions) . "
        LIMIT 1
    ", 'id_mahasiswa');
}

function pi_seed_mahasiswa_dari_peserta($conn, $row)
{
    $existing = pi_mahasiswa_lokal($conn, $row);
    if ($existing) {
        return $existing;
    }

    $nim = pi_value($row, ['nim', 'nipd']);
    $nama = pi_value($row, ['nama_mahasiswa']);
    $id_prodi_feeder = pi_value($row, ['id_prodi', 'id_sms']);
    $id_prodi = pi_prodi_lokal($conn, $id_prodi_feeder);

    if ($nim === '' || $nama === '' || !$id_prodi) {
        return null;
    }

    $angkatan = pi_int(pi_value($row, ['angkatan'], substr($nim, 0, 4)));
    if ($angkatan < 1900) {
        $angkatan = (int) date('Y');
    }

    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
    $ok = mysqli_query($conn, "
        INSERT INTO mahasiswa (
            id_feeder, id_biodata_feeder, id_registrasi_feeder, id_prodi, id_prodi_feeder,
            nim, nama_mahasiswa, angkatan, id_periode_masuk_feeder, semester,
            status_mahasiswa, status, status_sync_feeder, last_sync_feeder, raw_feeder_data
        ) VALUES (
            " . pi_sql($conn, pi_value($row, ['id_mahasiswa'])) . ",
            " . pi_sql($conn, pi_value($row, ['id_mahasiswa'])) . ",
            " . pi_sql($conn, pi_value($row, ['id_registrasi_mahasiswa'])) . ",
            '$id_prodi', " . pi_sql($conn, $id_prodi_feeder) . ",
            " . pi_sql($conn, $nim) . ", " . pi_sql($conn, $nama) . ", '$angkatan',
            " . pi_sql($conn, pi_value($row, ['id_periode_masuk', 'id_semester_masuk'])) . ",
            1, 'aktif', 'aktif', 'sudah', NOW(), " . pi_sql($conn, $raw) . "
        )
        ON DUPLICATE KEY UPDATE
            id_feeder = COALESCE(NULLIF(id_feeder, ''), VALUES(id_feeder)),
            id_biodata_feeder = COALESCE(NULLIF(id_biodata_feeder, ''), VALUES(id_biodata_feeder)),
            id_registrasi_feeder = COALESCE(NULLIF(id_registrasi_feeder, ''), VALUES(id_registrasi_feeder)),
            id_prodi = VALUES(id_prodi),
            id_prodi_feeder = VALUES(id_prodi_feeder),
            nama_mahasiswa = VALUES(nama_mahasiswa),
            angkatan = VALUES(angkatan),
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL
    ");

    if (!$ok) {
        return null;
    }

    return pi_mahasiswa_lokal($conn, $row);
}

function pi_krs_get_or_create($conn, $id_mahasiswa, $id_tahun, $id_semester_feeder, $id_registrasi_feeder, $raw)
{
    $id_mahasiswa = (int) $id_mahasiswa;
    $id_tahun = (int) $id_tahun;

    if ($id_mahasiswa < 1 || $id_tahun < 1) {
        return null;
    }

    $existing = pi_first_id($conn, "
        SELECT id_krs
        FROM krs
        WHERE id_mahasiswa = '$id_mahasiswa' AND id_tahun = '$id_tahun'
        LIMIT 1
    ", 'id_krs');

    if ($existing) {
        mysqli_query($conn, "
            UPDATE krs SET
                id_registrasi_mahasiswa_feeder = COALESCE(id_registrasi_mahasiswa_feeder, " . pi_sql($conn, $id_registrasi_feeder) . "),
                id_semester_feeder = " . pi_sql($conn, $id_semester_feeder) . ",
                raw_feeder_data = " . pi_sql($conn, $raw) . ",
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL,
                status_krs = IF(status_krs = 'draft', 'disetujui', status_krs)
            WHERE id_krs = '$existing'
        ");
        return $existing;
    }

    $ok = mysqli_query($conn, "
        INSERT INTO krs (
            id_mahasiswa, id_registrasi_mahasiswa_feeder, id_tahun, id_semester_feeder,
            tanggal_krs, total_sks, status_krs, raw_feeder_data, status_sync_feeder, last_sync_feeder
        ) VALUES (
            '$id_mahasiswa', " . pi_sql($conn, $id_registrasi_feeder) . ", '$id_tahun', " . pi_sql($conn, $id_semester_feeder) . ",
            NOW(), 0, 'disetujui', " . pi_sql($conn, $raw) . ", 'sudah', NOW()
        )
    ");

    return $ok ? (int) mysqli_insert_id($conn) : null;
}

function pi_krs_detail_get_or_create($conn, $id_krs, $id_kelas_kuliah, $id_kelas_kuliah_feeder)
{
    $id_krs = (int) $id_krs;
    $id_kelas_kuliah = (int) $id_kelas_kuliah;

    if ($id_krs < 1 || $id_kelas_kuliah < 1) {
        return null;
    }

    $existing = pi_first_id($conn, "
        SELECT id_krs_detail
        FROM krs_detail
        WHERE id_krs = '$id_krs' AND id_kelas_kuliah = '$id_kelas_kuliah'
        LIMIT 1
    ", 'id_krs_detail');

    if ($existing) {
        mysqli_query($conn, "
            UPDATE krs_detail SET
                id_kelas_kuliah_feeder = " . pi_sql($conn, $id_kelas_kuliah_feeder) . ",
                status_mk = 'diambil',
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL
            WHERE id_krs_detail = '$existing'
        ");
        return $existing;
    }

    $ok = mysqli_query($conn, "
        INSERT INTO krs_detail (
            id_krs, id_jadwal, id_kelas_kuliah, id_kelas_kuliah_feeder,
            status_mk, status_sync_feeder, last_sync_feeder
        ) VALUES (
            '$id_krs', NULL, '$id_kelas_kuliah', " . pi_sql($conn, $id_kelas_kuliah_feeder) . ",
            'diambil', 'sudah', NOW()
        )
    ");

    return $ok ? (int) mysqli_insert_id($conn) : null;
}

function pi_update_total_sks_krs($conn, $id_krs)
{
    $id_krs = (int) $id_krs;
    mysqli_query($conn, "
        UPDATE krs SET total_sks = (
            SELECT COALESCE(SUM(mk.total_sks), 0)
            FROM krs_detail kd
            LEFT JOIN kelas_kuliah kk ON kd.id_kelas_kuliah = kk.id_kelas_kuliah
            LEFT JOIN mata_kuliah mk ON kk.id_mk = mk.id_mk
            WHERE kd.id_krs = '$id_krs' AND kd.status_mk = 'diambil'
        )
        WHERE id_krs = '$id_krs'
    ");
}

function pull_kelas_kuliah_inti($conn, $limit, $offset, $ambil_detail = true)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListKelasKuliah', '', '', $limit, $offset, null, 'Pull Kelas Kuliah');
    if (!$resp['success']) {
        pi_add_error($result, 'GetListKelasKuliah', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_feeder = pi_value($row, ['id_kelas_kuliah']);
        $label = pi_value($row, ['nama_kelas_kuliah'], $id_feeder ?: 'Kelas Kuliah');

        if ($ambil_detail && $id_feeder !== '') {
            $safe = mysqli_real_escape_string($conn, $id_feeder);
            $detail = neofeeder_request($conn, 'GetDetailKelasKuliah', "id_kelas_kuliah = '$safe'", '', 1, 0, null, 'Pull Detail Kelas Kuliah');
            if ($detail['success'] && !empty($detail['data'])) {
                $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                if (is_array($detail_row)) {
                    $row = array_merge($row, array_filter($detail_row, fn($value) => trim((string) $value) !== ''));
                }
            }
        }

        $id_tahun = pi_tahun_lokal_atau_buat($conn, $row);
        $id_prodi = pi_prodi_lokal($conn, pi_value($row, ['id_prodi', 'id_sms']));
        $id_mk = pi_matkul_lokal($conn, pi_value($row, ['id_matkul']), pi_value($row, ['kode_mata_kuliah']), pi_value($row, ['id_prodi', 'id_sms']));

        if (!$id_tahun || !$id_prodi || !$id_mk) {
            $missing = [];
            if (!$id_tahun) {
                $missing[] = 'periode ' . pi_value($row, ['id_semester', 'id_smt'], '-');
            }
            if (!$id_prodi) {
                $missing[] = 'prodi ' . pi_value($row, ['id_prodi', 'id_sms'], '-');
            }
            if (!$id_mk) {
                $missing[] = 'mata kuliah ' . pi_value($row, ['kode_mata_kuliah'], pi_value($row, ['id_matkul'], '-'));
            }
            pi_add_error($result, $label, 'Data lokal belum ditemukan: ' . implode(', ', $missing) . '.');
            continue;
        }

        $existing = pi_kelas_lokal($conn, $id_feeder);
        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
        $kapasitas = pi_int(pi_value($row, ['kapasitas', 'kuota_pditt'], 40));
        if ($kapasitas < 1) {
            $kapasitas = 40;
        }

        $lingkup = strtolower(pi_value($row, ['lingkup_kelas'], 'internal'));
        if (!in_array($lingkup, ['internal', 'eksternal', 'campuran'], true)) {
            $lingkup = 'internal';
        }

        $mode = strtolower(pi_value($row, ['mode_kuliah'], 'tatap muka'));
        if (!in_array($mode, ['tatap muka', 'online', 'hybrid'], true)) {
            $mode = 'tatap muka';
        }

        if ($existing) {
            $ok = mysqli_query($conn, "
                UPDATE kelas_kuliah SET
                    id_tahun = '$id_tahun',
                    id_semester_feeder = " . pi_sql($conn, pi_value($row, ['id_semester', 'id_smt'])) . ",
                    id_prodi = '$id_prodi',
                    id_prodi_feeder = " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'])) . ",
                    id_mk = '$id_mk',
                    id_matkul_feeder = " . pi_sql($conn, pi_value($row, ['id_matkul'])) . ",
                    nama_kelas_kuliah = " . pi_sql($conn, $label) . ",
                    kode_kelas = " . pi_sql($conn, $label) . ",
                    bahasan = " . pi_sql($conn, pi_value($row, ['bahasan', 'bahasan_case'])) . ",
                    lingkup = '$lingkup',
                    mode_kuliah = '$mode',
                    tanggal_mulai = " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_mulai_efektif', 'tgl_mulai_koas']))) . ",
                    tanggal_selesai = " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_akhir_efektif', 'tgl_selesai_koas']))) . ",
                    kapasitas = '$kapasitas',
                    raw_feeder_data = " . pi_sql($conn, $raw) . ",
                    status_sync_feeder = 'sudah',
                    last_sync_feeder = NOW(),
                    last_error_feeder = NULL,
                    status = 'aktif'
                WHERE id_kelas_kuliah = '$existing'
            ");
            $ok ? $result['update']++ : pi_add_error($result, $label, mysqli_error($conn));
        } else {
            $ok = mysqli_query($conn, "
                INSERT INTO kelas_kuliah (
                    id_kelas_kuliah_feeder, id_tahun, id_semester_feeder, id_prodi, id_prodi_feeder,
                    id_mk, id_matkul_feeder, nama_kelas_kuliah, kode_kelas, bahasan,
                    lingkup, mode_kuliah, tanggal_mulai, tanggal_selesai, kapasitas,
                    raw_feeder_data, status_sync_feeder, last_sync_feeder, status
                ) VALUES (
                    " . pi_sql($conn, $id_feeder) . ", '$id_tahun', " . pi_sql($conn, pi_value($row, ['id_semester', 'id_smt'])) . ",
                    '$id_prodi', " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'])) . ",
                    '$id_mk', " . pi_sql($conn, pi_value($row, ['id_matkul'])) . ",
                    " . pi_sql($conn, $label) . ", " . pi_sql($conn, $label) . ", " . pi_sql($conn, pi_value($row, ['bahasan', 'bahasan_case'])) . ",
                    '$lingkup', '$mode', " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_mulai_efektif', 'tgl_mulai_koas']))) . ",
                    " . pi_sql($conn, pi_date(pi_value($row, ['tanggal_akhir_efektif', 'tgl_selesai_koas']))) . ", '$kapasitas',
                    " . pi_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif'
                )
            ");
            $ok ? $result['insert']++ : pi_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pull_dosen_pengajar_inti($conn, $limit, $offset)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetDosenPengajarKelasKuliah', '', '', $limit, $offset, null, 'Pull Dosen Pengajar');
    if (!$resp['success']) {
        pi_add_error($result, 'GetDosenPengajarKelasKuliah', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);
    $urutan_cache = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $label = pi_value($row, ['nama_dosen'], 'Dosen') . ' - ' . pi_value($row, ['nama_kelas_kuliah']);
        $id_kelas_feeder = pi_value($row, ['id_kelas_kuliah']);
        $id_kelas = pi_kelas_lokal($conn, $id_kelas_feeder);
        if (!$id_kelas) {
            $id_kelas = pi_seed_kelas_kuliah_by_feeder($conn, $id_kelas_feeder);
        }

        $id_dosen = pi_dosen_lokal($conn, $row);
        if (!$id_dosen) {
            $id_dosen = pi_seed_dosen_dari_pengajar($conn, $row);
        }

        if (!$id_kelas || !$id_dosen) {
            $missing = [];
            if (!$id_kelas) {
                $missing[] = 'kelas kuliah ' . ($id_kelas_feeder ?: '-');
            }
            if (!$id_dosen) {
                $missing[] = 'dosen ' . (pi_value($row, ['nidn'], pi_value($row, ['id_dosen'], '-')));
            }
            pi_add_error($result, $label, 'Data lokal belum ditemukan: ' . implode(', ', $missing) . '.');
            continue;
        }

        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
        $cache_key = $id_kelas;
        $urutan_cache[$cache_key] = ($urutan_cache[$cache_key] ?? 0) + 1;

        $ok = mysqli_query($conn, "
            INSERT INTO dosen_pengajar_kelas (
                id_kelas_kuliah, id_kelas_kuliah_feeder, id_dosen, id_dosen_feeder, urutan_pengajar,
                sks_substansi_total, rencana_tatap_muka, realisasi_tatap_muka, jenis_evaluasi,
                raw_feeder_data, status_sync_feeder, last_sync_feeder
            ) VALUES (
                '$id_kelas', " . pi_sql($conn, $id_kelas_feeder) . ", '$id_dosen', " . pi_sql($conn, pi_value($row, ['id_dosen'])) . ",
                '" . $urutan_cache[$cache_key] . "', '" . pi_num(pi_value($row, ['sks_substansi_total'])) . "',
                '" . pi_int(pi_value($row, ['rencana_minggu_pertemuan'], 16)) . "', '" . pi_int(pi_value($row, ['realisasi_minggu_pertemuan'])) . "',
                " . pi_sql($conn, pi_value($row, ['nama_jenis_evaluasi', 'id_jenis_evaluasi'])) . ",
                " . pi_sql($conn, $raw) . ", 'sudah', NOW()
            )
            ON DUPLICATE KEY UPDATE
                id_kelas_kuliah_feeder = VALUES(id_kelas_kuliah_feeder),
                id_dosen_feeder = VALUES(id_dosen_feeder),
                sks_substansi_total = VALUES(sks_substansi_total),
                rencana_tatap_muka = VALUES(rencana_tatap_muka),
                realisasi_tatap_muka = VALUES(realisasi_tatap_muka),
                jenis_evaluasi = VALUES(jenis_evaluasi),
                raw_feeder_data = VALUES(raw_feeder_data),
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL
        ");

        if ($ok) {
            mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        } else {
            pi_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pi_store_peserta($conn, $row, &$result)
{
    $label = pi_value($row, ['nim']) . ' - ' . pi_value($row, ['nama_mahasiswa']);
    $id_kelas_feeder = pi_value($row, ['id_kelas_kuliah']);
    $id_kelas = pi_kelas_lokal($conn, $id_kelas_feeder);
    if (!$id_kelas) {
        $id_kelas = pi_seed_kelas_kuliah_by_feeder($conn, $id_kelas_feeder);
    }

    $id_mahasiswa = pi_mahasiswa_lokal($conn, $row);
    if (!$id_mahasiswa) {
        $id_mahasiswa = pi_seed_mahasiswa_dari_peserta($conn, $row);
    }

    if (!$id_kelas || !$id_mahasiswa) {
        $missing = [];
        if (!$id_kelas) {
            $missing[] = 'kelas kuliah ' . ($id_kelas_feeder ?: '-');
        }
        if (!$id_mahasiswa) {
            $missing[] = 'mahasiswa ' . (pi_value($row, ['nim'], pi_value($row, ['id_mahasiswa'], '-')));
        }
        pi_add_error($result, $label, 'Data lokal belum ditemukan: ' . implode(', ', $missing) . '.');
        return false;
    }

    $id_tahun = pi_first_id($conn, "SELECT id_tahun FROM kelas_kuliah WHERE id_kelas_kuliah = '$id_kelas' LIMIT 1", 'id_tahun');
    $id_semester = pi_first_id($conn, "SELECT id_semester_feeder FROM kelas_kuliah WHERE id_kelas_kuliah = '$id_kelas' LIMIT 1", 'id_semester_feeder');
    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
    $id_reg = pi_value($row, ['id_registrasi_mahasiswa']);
    $id_krs = pi_krs_get_or_create($conn, $id_mahasiswa, $id_tahun, $id_semester, $id_reg, $raw);
    $id_krs_detail = pi_krs_detail_get_or_create($conn, $id_krs, $id_kelas, $id_kelas_feeder);

    if (!$id_krs || !$id_krs_detail) {
        pi_add_error($result, $label, 'Gagal membentuk KRS atau detail KRS lokal.');
        return false;
    }

    $ok = mysqli_query($conn, "
        INSERT INTO peserta_kelas_kuliah (
            id_kelas_kuliah, id_kelas_kuliah_feeder, id_krs_detail, id_krs, id_mahasiswa,
            id_registrasi_mahasiswa_feeder, nim, status_peserta, raw_feeder_data,
            status_sync_feeder, last_sync_feeder
        ) VALUES (
            '$id_kelas', " . pi_sql($conn, $id_kelas_feeder) . ", '$id_krs_detail', '$id_krs', '$id_mahasiswa',
            " . pi_sql($conn, $id_reg) . ", " . pi_sql($conn, pi_value($row, ['nim'])) . ", 'aktif',
            " . pi_sql($conn, $raw) . ", 'sudah', NOW()
        )
        ON DUPLICATE KEY UPDATE
            id_kelas_kuliah_feeder = VALUES(id_kelas_kuliah_feeder),
            id_krs_detail = VALUES(id_krs_detail),
            id_krs = VALUES(id_krs),
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            nim = VALUES(nim),
            status_peserta = 'aktif',
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL
    ");

    if ($ok) {
        pi_update_total_sks_krs($conn, $id_krs);
        mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        return $id_krs_detail;
    }

    pi_add_error($result, $label, mysqli_error($conn));
    return false;
}

function pull_peserta_krs_inti($conn, $limit, $offset)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetPesertaKelasKuliah', '', '', $limit, $offset, null, 'Pull Peserta Kelas');
    if (!$resp['success']) {
        pi_add_error($result, 'GetPesertaKelasKuliah', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (is_array($row)) {
            pi_store_peserta($conn, $row, $result);
        }
    }

    return $result;
}

function pi_store_nilai($conn, $row, &$result)
{
    $label = pi_value($row, ['nim']) . ' - ' . pi_value($row, ['kode_mata_kuliah']);

    $dummy_result = pi_result();
    $id_krs_detail = pi_store_peserta($conn, $row, $dummy_result);
    if (!$id_krs_detail) {
        $detail_error = !empty($dummy_result['pesan_gagal'])
            ? implode('; ', $dummy_result['pesan_gagal'])
            : 'Detail KRS belum bisa dibentuk untuk nilai.';
        pi_add_error($result, $label, $detail_error);
        return false;
    }

    $id_kelas = pi_kelas_lokal($conn, pi_value($row, ['id_kelas_kuliah']));
    $id_mahasiswa = pi_mahasiswa_lokal($conn, $row);

    if (!$id_kelas || !$id_mahasiswa) {
        $missing = [];
        if (!$id_kelas) {
            $missing[] = 'kelas kuliah ' . pi_value($row, ['id_kelas_kuliah'], '-');
        }
        if (!$id_mahasiswa) {
            $missing[] = 'mahasiswa ' . pi_value($row, ['nim'], '-');
        }
        pi_add_error($result, $label, 'Data lokal belum tersedia setelah pembentukan peserta: ' . implode(', ', $missing) . '.');
        return false;
    }

    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
    $nilai_angka = pi_num(pi_value($row, ['nilai_angka', 'nilai_akhir']));
    $nilai_indeks = pi_num(pi_value($row, ['nilai_indeks', 'bobot']));
    $nilai_huruf = pi_value($row, ['nilai_huruf']);

    $ok = mysqli_query($conn, "
        INSERT INTO nilai (
            id_krs_detail, id_kelas_kuliah, id_kelas_kuliah_feeder, id_mahasiswa,
            id_registrasi_mahasiswa_feeder, id_matkul, id_matkul_feeder,
            nilai_akhir, nilai_huruf, bobot, nilai_indeks, status_publish,
            raw_feeder_data, status_sync_feeder, last_sync_feeder
        ) VALUES (
            '$id_krs_detail', '$id_kelas', " . pi_sql($conn, pi_value($row, ['id_kelas_kuliah'])) . ", '$id_mahasiswa',
            " . pi_sql($conn, pi_value($row, ['id_registrasi_mahasiswa'])) . ",
            " . pi_sql($conn, pi_matkul_lokal($conn, pi_value($row, ['id_matkul']), pi_value($row, ['kode_mata_kuliah']), pi_value($row, ['id_prodi']))) . ",
            " . pi_sql($conn, pi_value($row, ['id_matkul'])) . ",
            '$nilai_angka', " . pi_sql($conn, $nilai_huruf) . ", '$nilai_indeks', '$nilai_indeks', 'publish',
            " . pi_sql($conn, $raw) . ", 'sudah', NOW()
        )
        ON DUPLICATE KEY UPDATE
            id_kelas_kuliah = VALUES(id_kelas_kuliah),
            id_kelas_kuliah_feeder = VALUES(id_kelas_kuliah_feeder),
            id_mahasiswa = VALUES(id_mahasiswa),
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_matkul = VALUES(id_matkul),
            id_matkul_feeder = VALUES(id_matkul_feeder),
            nilai_akhir = VALUES(nilai_akhir),
            nilai_huruf = VALUES(nilai_huruf),
            bobot = VALUES(bobot),
            nilai_indeks = VALUES(nilai_indeks),
            status_publish = 'publish',
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL
    ");

    if ($ok) {
        mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        return true;
    }

    pi_add_error($result, $label, mysqli_error($conn));
    return false;
}

function pull_nilai_inti($conn, $limit, $offset, $detail_limit = 500)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListNilaiPerkuliahanKelas', '', '', $limit, $offset, null, 'Pull Nilai Kelas');
    if (!$resp['success']) {
        pi_add_error($result, 'GetListNilaiPerkuliahanKelas', $resp['message']);
        return $result;
    }

    $classes = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($classes);

    foreach ($classes as $class_row) {
        if (!is_array($class_row)) {
            continue;
        }

        $id_kelas_feeder = pi_value($class_row, ['id_kelas_kuliah']);
        if (pi_kelas_lokal($conn, $id_kelas_feeder) < 1) {
            pi_seed_kelas_kuliah_by_feeder($conn, $id_kelas_feeder);
        }

        if (pi_kelas_lokal($conn, $id_kelas_feeder) < 1) {
            pi_add_error($result, pi_value($class_row, ['nama_kelas_kuliah'], $id_kelas_feeder), 'Kelas kuliah lokal belum ditemukan. Pull kelas kuliah terlebih dahulu.');
            continue;
        }

        $safe = mysqli_real_escape_string($conn, $id_kelas_feeder);
        $detail = neofeeder_request($conn, 'GetDetailNilaiPerkuliahanKelas', "id_kelas_kuliah = '$safe'", '', $detail_limit, 0, null, 'Pull Detail Nilai Kelas');
        if (!$detail['success']) {
            pi_add_error($result, pi_value($class_row, ['nama_kelas_kuliah'], $id_kelas_feeder), $detail['message']);
            continue;
        }

        $rows = is_array($detail['data'] ?? null) ? $detail['data'] : [];
        if (empty($rows)) {
            $result['skip']++;
            continue;
        }

        foreach ($rows as $row) {
            if (is_array($row)) {
                pi_store_nilai($conn, $row, $result);
            }
        }
    }

    return $result;
}

function pull_akm_pelaporan($conn, $limit, $offset)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListPerkuliahanMahasiswa', '', '', $limit, $offset, null, 'Pull AKM');
    if (!$resp['success']) {
        pi_add_error($result, 'GetListPerkuliahanMahasiswa', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_reg_detail = pi_value($row, ['id_registrasi_mahasiswa']);
        $id_semester_detail = pi_value($row, ['id_semester', 'id_smt']);

        if ($id_reg_detail !== '' && $id_semester_detail !== '') {
            $safe_reg = mysqli_real_escape_string($conn, $id_reg_detail);
            $safe_semester = mysqli_real_escape_string($conn, $id_semester_detail);
            $detail = neofeeder_request(
                $conn,
                'GetDetailPerkuliahanMahasiswa',
                "id_registrasi_mahasiswa = '$safe_reg' AND id_semester = '$safe_semester'",
                '',
                1,
                0,
                null,
                'Pull Detail AKM'
            );

            if ($detail['success'] && !empty($detail['data'])) {
                $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                if (is_array($detail_row)) {
                    $row = array_merge($row, $detail_row);
                }
            }
        }

        $label = pi_value($row, ['nim']) . ' - ' . pi_value($row, ['id_semester', 'id_smt']);
        $id_mahasiswa = pi_mahasiswa_lokal($conn, $row);
        if (!$id_mahasiswa) {
            $id_mahasiswa = pi_seed_mahasiswa_dari_peserta($conn, $row);
        }
        $id_semester = pi_value($row, ['id_semester', 'id_smt']);
        $id_tahun = pi_tahun_lokal_atau_buat($conn, $row);
        $id_reg = pi_value($row, ['id_registrasi_mahasiswa']);

        if (!$id_mahasiswa || !$id_tahun) {
            $missing = [];
            if (!$id_mahasiswa) {
                $missing[] = 'mahasiswa lokal';
            }
            if (!$id_tahun) {
                $missing[] = 'periode lokal';
            }
            pi_add_error($result, $label, implode(' dan ', $missing) . ' belum ditemukan.');
            continue;
        }

        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
        $ok = mysqli_query($conn, "
            INSERT INTO aktivitas_kuliah_mahasiswa (
                id_mahasiswa, id_registrasi_mahasiswa_feeder, id_tahun, id_semester_feeder,
                id_status_mahasiswa_feeder, nama_status_mahasiswa, ips, ipk,
                sks_semester, sks_total, biaya_kuliah_smt, raw_feeder_data,
                status_sync_feeder, last_sync_feeder
            ) VALUES (
                '$id_mahasiswa', " . pi_sql($conn, $id_reg) . ", '$id_tahun', " . pi_sql($conn, $id_semester) . ",
                " . pi_sql($conn, pi_value($row, ['id_status_mahasiswa'])) . ",
                " . pi_sql($conn, pi_value($row, ['nama_status_mahasiswa', 'status_mahasiswa'])) . ",
                '" . pi_num(pi_value($row, ['ips'])) . "', '" . pi_num(pi_value($row, ['ipk'])) . "',
                '" . pi_int(pi_value($row, ['sks_semester', 'sks_smt'])) . "', '" . pi_int(pi_value($row, ['sks_total', 'total_sks'])) . "',
                '" . pi_num(pi_value($row, ['biaya_kuliah_smt', 'biaya'])) . "', " . pi_sql($conn, $raw) . ",
                'sudah', NOW()
            )
            ON DUPLICATE KEY UPDATE
                id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
                id_status_mahasiswa_feeder = VALUES(id_status_mahasiswa_feeder),
                nama_status_mahasiswa = VALUES(nama_status_mahasiswa),
                ips = VALUES(ips),
                ipk = VALUES(ipk),
                sks_semester = VALUES(sks_semester),
                sks_total = VALUES(sks_total),
                biaya_kuliah_smt = VALUES(biaya_kuliah_smt),
                raw_feeder_data = VALUES(raw_feeder_data),
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL
        ");

        if ($ok) {
            mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        } else {
            pi_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pull_lulus_do_pelaporan($conn, $limit, $offset)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListMahasiswaLulusDO', '', '', $limit, $offset, null, 'Pull Lulus DO');
    if (!$resp['success']) {
        pi_add_error($result, 'GetListMahasiswaLulusDO', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_reg_detail = pi_value($row, ['id_registrasi_mahasiswa']);
        if ($id_reg_detail !== '') {
            $safe_reg = mysqli_real_escape_string($conn, $id_reg_detail);
            $detail = neofeeder_request(
                $conn,
                'GetDetailMahasiswaLulusDO',
                "id_registrasi_mahasiswa = '$safe_reg'",
                '',
                1,
                0,
                null,
                'Pull Detail Lulus DO'
            );

            if ($detail['success'] && !empty($detail['data'])) {
                $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                if (is_array($detail_row)) {
                    $row = array_merge($row, $detail_row);
                }
            }
        }

        $label = pi_value($row, ['nim']) . ' - ' . pi_value($row, ['nama_mahasiswa']);
        $id_mahasiswa = pi_mahasiswa_lokal($conn, $row);
        if (!$id_mahasiswa) {
            $id_mahasiswa = pi_seed_mahasiswa_dari_peserta($conn, $row);
        }
        $id_prodi = pi_prodi_lokal($conn, pi_value($row, ['id_prodi', 'id_sms']));

        if (!$id_mahasiswa) {
            pi_add_error($result, $label, 'Mahasiswa lokal belum ditemukan.');
            continue;
        }

        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
        $tanggal_keluar = pi_date(pi_value($row, ['tanggal_keluar', 'tgl_keluar']));
        $tanggal_sk = pi_date(pi_value($row, ['tanggal_sk_yudisium', 'tgl_sk_yudisium']));

        $ok = mysqli_query($conn, "
            INSERT INTO mahasiswa_lulus_do (
                id_mahasiswa, id_registrasi_mahasiswa_feeder, id_prodi, id_prodi_feeder,
                id_periode_keluar_feeder, id_jenis_keluar_feeder, jenis_keluar,
                tanggal_keluar, keterangan, nomor_sk_yudisium, tanggal_sk_yudisium,
                ipk, nomor_ijazah, judul_tugas_akhir, bulan_awal_bimbingan,
                bulan_akhir_bimbingan, raw_feeder_data, status_sync_feeder, last_sync_feeder
            ) VALUES (
                '$id_mahasiswa', " . pi_sql($conn, pi_value($row, ['id_registrasi_mahasiswa'])) . ",
                " . ($id_prodi ? "'$id_prodi'" : "NULL") . ", " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'])) . ",
                " . pi_sql($conn, pi_value($row, ['id_periode_keluar', 'id_semester_keluar'])) . ",
                " . pi_sql($conn, pi_value($row, ['id_jenis_keluar'])) . ",
                " . pi_sql($conn, pi_value($row, ['nama_jenis_keluar', 'jenis_keluar'])) . ",
                " . pi_sql($conn, $tanggal_keluar) . ", " . pi_sql($conn, pi_value($row, ['keterangan'])) . ",
                " . pi_sql($conn, pi_value($row, ['nomor_sk_yudisium', 'sk_yudisium'])) . ",
                " . pi_sql($conn, $tanggal_sk) . ", '" . pi_num(pi_value($row, ['ipk'])) . "',
                " . pi_sql($conn, pi_value($row, ['nomor_ijazah', 'no_seri_ijazah'])) . ",
                " . pi_sql($conn, pi_value($row, ['judul_tugas_akhir', 'judul_skripsi'])) . ",
                " . pi_sql($conn, pi_value($row, ['bulan_awal_bimbingan'])) . ",
                " . pi_sql($conn, pi_value($row, ['bulan_akhir_bimbingan'])) . ",
                " . pi_sql($conn, $raw) . ", 'sudah', NOW()
            )
            ON DUPLICATE KEY UPDATE
                id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
                id_prodi = VALUES(id_prodi),
                id_prodi_feeder = VALUES(id_prodi_feeder),
                id_periode_keluar_feeder = VALUES(id_periode_keluar_feeder),
                id_jenis_keluar_feeder = VALUES(id_jenis_keluar_feeder),
                jenis_keluar = VALUES(jenis_keluar),
                tanggal_keluar = VALUES(tanggal_keluar),
                keterangan = VALUES(keterangan),
                nomor_sk_yudisium = VALUES(nomor_sk_yudisium),
                tanggal_sk_yudisium = VALUES(tanggal_sk_yudisium),
                ipk = VALUES(ipk),
                nomor_ijazah = VALUES(nomor_ijazah),
                judul_tugas_akhir = VALUES(judul_tugas_akhir),
                bulan_awal_bimbingan = VALUES(bulan_awal_bimbingan),
                bulan_akhir_bimbingan = VALUES(bulan_akhir_bimbingan),
                raw_feeder_data = VALUES(raw_feeder_data),
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL
        ");

        if ($ok) {
            mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        } else {
            pi_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pi_store_transkrip_row($conn, $mahasiswa, $row, &$result)
{
    $id_mahasiswa = (int)($mahasiswa['id_mahasiswa'] ?? 0);
    if ($id_mahasiswa < 1 || !is_array($row)) {
        return false;
    }

    $kode_mk = pi_value($row, ['kode_mata_kuliah', 'kode_mk']);
    $nama_mk = pi_value($row, ['nama_mata_kuliah', 'nama_mk']);
    $id_matkul_feeder = pi_value($row, ['id_matkul', 'id_mata_kuliah']);
    $id_semester = pi_value($row, ['id_semester', 'id_smt']);
    $label = ($mahasiswa['nim'] ?? '-') . ' - ' . ($kode_mk ?: $nama_mk ?: $id_matkul_feeder);

    if ($kode_mk === '' && $nama_mk === '' && $id_matkul_feeder === '') {
        pi_add_error($result, $label, 'Kode/nama/id mata kuliah dari Feeder kosong.');
        return false;
    }

    $id_tahun = pi_tahun_lokal($conn, $id_semester);
    $id_matkul = pi_matkul_lokal(
        $conn,
        $id_matkul_feeder,
        $kode_mk,
        pi_value($row, ['id_prodi', 'id_sms'], $mahasiswa['id_prodi_feeder'] ?? '')
    );
    $id_prodi = pi_prodi_lokal($conn, pi_value($row, ['id_prodi', 'id_sms'], $mahasiswa['id_prodi_feeder'] ?? ''));
    if (!$id_prodi && !empty($mahasiswa['id_prodi'])) {
        $id_prodi = (int)$mahasiswa['id_prodi'];
    }

    $id_reg = pi_value($row, ['id_registrasi_mahasiswa'], $mahasiswa['id_registrasi_feeder'] ?? '');
    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);
    $sks = pi_int(pi_value($row, ['sks_mata_kuliah', 'sks_mk', 'sks']));
    $nilai_angka = pi_num(pi_value($row, ['nilai_angka', 'nilai_akhir']));
    $nilai_indeks = pi_num(pi_value($row, ['nilai_indeks', 'bobot']));
    $nilai_huruf = pi_value($row, ['nilai_huruf']);

    $ok = mysqli_query($conn, "
        INSERT INTO transkrip_mahasiswa (
            id_mahasiswa, id_registrasi_mahasiswa_feeder, id_prodi, id_prodi_feeder,
            id_tahun, id_semester_feeder, id_matkul, id_matkul_feeder,
            kode_mk, nama_mk, sks_mk, nilai_angka, nilai_huruf, nilai_indeks,
            raw_feeder_data, status_sync_feeder, last_sync_feeder
        ) VALUES (
            '$id_mahasiswa', " . pi_sql($conn, $id_reg) . ",
            " . ($id_prodi ? "'$id_prodi'" : "NULL") . ",
            " . pi_sql($conn, pi_value($row, ['id_prodi', 'id_sms'], $mahasiswa['id_prodi_feeder'] ?? '')) . ",
            " . ($id_tahun ? "'$id_tahun'" : "NULL") . ",
            " . pi_sql($conn, $id_semester) . ",
            " . ($id_matkul ? "'$id_matkul'" : "NULL") . ",
            " . pi_sql($conn, $id_matkul_feeder) . ",
            " . pi_sql($conn, $kode_mk) . ",
            " . pi_sql($conn, $nama_mk) . ",
            '$sks', '$nilai_angka', " . pi_sql($conn, $nilai_huruf) . ", '$nilai_indeks',
            " . pi_sql($conn, $raw) . ", 'sudah', NOW()
        )
        ON DUPLICATE KEY UPDATE
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_prodi = VALUES(id_prodi),
            id_prodi_feeder = VALUES(id_prodi_feeder),
            id_tahun = VALUES(id_tahun),
            id_semester_feeder = VALUES(id_semester_feeder),
            id_matkul = VALUES(id_matkul),
            id_matkul_feeder = VALUES(id_matkul_feeder),
            kode_mk = VALUES(kode_mk),
            nama_mk = VALUES(nama_mk),
            sks_mk = VALUES(sks_mk),
            nilai_angka = VALUES(nilai_angka),
            nilai_huruf = VALUES(nilai_huruf),
            nilai_indeks = VALUES(nilai_indeks),
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL
    ");

    if ($ok) {
        mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        return true;
    }

    pi_add_error($result, $label, mysqli_error($conn));
    return false;
}

function pull_transkrip_pelaporan($conn, $limit, $offset)
{
    $result = pi_result();
    $result['next_offset'] = $offset + $limit;
    $limit = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $data_mahasiswa = nf_fetch_all($conn, "
        SELECT m.id_mahasiswa, m.nim, m.nama_mahasiswa, m.id_registrasi_feeder, m.id_prodi, m.id_prodi_feeder
        FROM mahasiswa m
        WHERE m.id_registrasi_feeder IS NOT NULL
          AND m.id_registrasi_feeder <> ''
        ORDER BY m.nim ASC
        LIMIT $limit OFFSET $offset
    ");

    foreach ($data_mahasiswa as $mahasiswa) {
        $result['total']++;
        $id_reg = trim((string)($mahasiswa['id_registrasi_feeder'] ?? ''));
        $safe_reg = mysqli_real_escape_string($conn, $id_reg);
        $label = ($mahasiswa['nim'] ?? '-') . ' - ' . ($mahasiswa['nama_mahasiswa'] ?? '-');

        $resp = neofeeder_request(
            $conn,
            'GetTranskripMahasiswa',
            "id_registrasi_mahasiswa = '$safe_reg'",
            '',
            500,
            0,
            null,
            'Pull Transkrip Mahasiswa'
        );

        if (!$resp['success']) {
            $resp = neofeeder_request(
                $conn,
                'GetRiwayatNilaiMahasiswa',
                "id_registrasi_mahasiswa = '$safe_reg'",
                '',
                500,
                0,
                null,
                'Pull Riwayat Nilai Mahasiswa'
            );
        }

        if (!$resp['success']) {
            pi_add_error($result, $label, $resp['message']);
            continue;
        }

        $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
        if (empty($rows)) {
            $result['skip']++;
            continue;
        }

        foreach ($rows as $row) {
            pi_store_transkrip_row($conn, $mahasiswa, $row, $result);
        }
    }

    return $result;
}
?>
