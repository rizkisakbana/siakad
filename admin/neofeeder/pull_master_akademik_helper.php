<?php
function ma_value($row, $keys, $default = '')
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return trim((string) $row[$key]);
        }
    }

    return $default;
}

function ma_sql($conn, $value)
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return "NULL";
    }

    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

function ma_num($value)
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return 0;
    }

    return (float) str_replace(',', '.', $value);
}

function ma_int($value)
{
    return (int) round(ma_num($value));
}

function ma_result()
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

function ma_add_error(&$result, $label, $message)
{
    $result['gagal']++;
    $result['pesan_gagal'][] = $label . ': ' . $message;
}

function ma_prodi_lokal($conn, $id_prodi_feeder)
{
    $id_prodi_feeder = trim((string) $id_prodi_feeder);

    if ($id_prodi_feeder === '') {
        return [null, null];
    }

    $id = mysqli_real_escape_string($conn, $id_prodi_feeder);
    $q = mysqli_query($conn, "
        SELECT id_prodi, COALESCE(NULLIF(id_prodi_feeder, ''), NULLIF(id_feeder, '')) AS id_feeder
        FROM prodi
        WHERE id_prodi_feeder = '$id' OR id_feeder = '$id'
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return [(int) $row['id_prodi'], $row['id_feeder']];
    }

    return [null, $id_prodi_feeder];
}

function ma_kurikulum_lokal($conn, $id_kurikulum_feeder, $id_prodi = 0)
{
    $conditions = [];

    if (trim((string) $id_kurikulum_feeder) !== '') {
        $id = mysqli_real_escape_string($conn, trim((string) $id_kurikulum_feeder));
        $conditions[] = "id_kurikulum_feeder = '$id'";
    }

    if ((int) $id_prodi > 0) {
        $conditions[] = "id_prodi = '" . (int) $id_prodi . "'";
    }

    if (empty($conditions)) {
        return null;
    }

    $where = trim((string) $id_kurikulum_feeder) !== ''
        ? $conditions[0]
        : implode(' AND ', $conditions);

    $q = mysqli_query($conn, "
        SELECT id_kurikulum
        FROM kurikulum
        WHERE $where
        ORDER BY tahun_kurikulum DESC, id_kurikulum DESC
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return (int) $row['id_kurikulum'];
    }

    return null;
}

function ma_matkul_lokal($conn, $id_matkul_feeder, $kode_mk = '', $id_prodi_feeder = '')
{
    if (trim((string) $id_matkul_feeder) !== '') {
        $id = mysqli_real_escape_string($conn, trim((string) $id_matkul_feeder));
        $q = mysqli_query($conn, "
            SELECT id_mk
            FROM mata_kuliah
            WHERE id_matkul_feeder = '$id'
            ORDER BY id_mk ASC
            LIMIT 1
        ");

        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            return (int) $row['id_mk'];
        }
    }

    if (trim((string) $kode_mk) === '') {
        return null;
    }

    $kode = mysqli_real_escape_string($conn, trim((string) $kode_mk));
    $where = "mata_kuliah.kode_mk = '$kode'";

    if (trim((string) $id_prodi_feeder) !== '') {
        $id_prodi = mysqli_real_escape_string($conn, trim((string) $id_prodi_feeder));
        $where .= " AND (
            mata_kuliah.id_prodi_feeder = '$id_prodi'
            OR kurikulum.id_prodi_feeder = '$id_prodi'
            OR prodi.id_prodi_feeder = '$id_prodi'
            OR prodi.id_feeder = '$id_prodi'
        )";
    }

    $q = mysqli_query($conn, "
        SELECT mata_kuliah.id_mk
        FROM mata_kuliah
        LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
        LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
        WHERE $where
        ORDER BY mata_kuliah.id_mk ASC
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return (int) $row['id_mk'];
    }

    return null;
}

function ma_kurikulum_meta($conn, $id_kurikulum)
{
    $id_kurikulum = (int) $id_kurikulum;
    if ($id_kurikulum < 1) {
        return null;
    }

    $q = mysqli_query($conn, "
        SELECT
            kurikulum.id_kurikulum,
            kurikulum.id_prodi,
            COALESCE(NULLIF(kurikulum.id_prodi_feeder, ''), NULLIF(prodi.id_prodi_feeder, ''), NULLIF(prodi.id_feeder, '')) AS id_prodi_feeder
        FROM kurikulum
        LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
        WHERE kurikulum.id_kurikulum = '$id_kurikulum'
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }

    return null;
}

function ma_seed_matkul_dari_relasi($conn, $id_kurikulum, $row)
{
    $kode_mk = ma_value($row, ['kode_mata_kuliah', 'kode_mk']);
    $nama_mk = ma_value($row, ['nama_mata_kuliah', 'nama_mk'], $kode_mk);

    if ($kode_mk === '' || $nama_mk === '') {
        return null;
    }

    $meta = ma_kurikulum_meta($conn, $id_kurikulum);
    if (!$meta) {
        return null;
    }

    $id_matkul_feeder = ma_value($row, ['id_matkul']);
    $id_prodi_feeder = $meta['id_prodi_feeder'] ?? ma_value($row, ['id_prodi']);
    $semester = max(1, ma_int(ma_value($row, ['semester'], 1)));
    $sks_tm = ma_num(ma_value($row, ['sks_tatap_muka', 'sks_mata_kuliah']));
    $sks_praktik = ma_num(ma_value($row, ['sks_praktek', 'sks_praktik']));
    $sks_lap = ma_num(ma_value($row, ['sks_praktek_lapangan', 'sks_praktik_lapangan']));
    $sks_sim = ma_num(ma_value($row, ['sks_simulasi']));
    $jenis_mk = ma_value($row, ['apakah_wajib'], '1') === '1' ? 'wajib' : 'pilihan';
    $raw = json_encode($row, JSON_UNESCAPED_UNICODE);

    $ok = mysqli_query($conn, "
        INSERT INTO mata_kuliah (
            id_matkul_feeder, id_kurikulum, id_prodi_feeder, kode_mk, nama_mk, semester,
            sks_teori, sks_praktik, sks_tatap_muka, sks_praktik_lapangan, sks_simulasi,
            jenis_mk, raw_feeder_data, status_sync_feeder, last_sync_feeder, status
        ) VALUES (
            " . ma_sql($conn, $id_matkul_feeder) . ", '" . (int) $id_kurikulum . "', " . ma_sql($conn, $id_prodi_feeder) . ",
            " . ma_sql($conn, $kode_mk) . ", " . ma_sql($conn, $nama_mk) . ", '$semester',
            '" . ma_int($sks_tm) . "', '" . ma_int($sks_praktik) . "', '$sks_tm', '$sks_lap', '$sks_sim',
            '$jenis_mk', " . ma_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif'
        )
        ON DUPLICATE KEY UPDATE
            id_matkul_feeder = VALUES(id_matkul_feeder),
            id_prodi_feeder = VALUES(id_prodi_feeder),
            nama_mk = VALUES(nama_mk),
            semester = VALUES(semester),
            sks_teori = VALUES(sks_teori),
            sks_praktik = VALUES(sks_praktik),
            sks_tatap_muka = VALUES(sks_tatap_muka),
            sks_praktik_lapangan = VALUES(sks_praktik_lapangan),
            sks_simulasi = VALUES(sks_simulasi),
            jenis_mk = VALUES(jenis_mk),
            raw_feeder_data = VALUES(raw_feeder_data),
            status_sync_feeder = 'sudah',
            last_sync_feeder = NOW(),
            last_error_feeder = NULL,
            status = 'aktif'
    ");

    if (!$ok) {
        return null;
    }

    return ma_matkul_lokal($conn, $id_matkul_feeder, $kode_mk, $id_prodi_feeder);
}

function pull_kurikulum_akademik($conn, $limit, $offset, $ambil_detail = true)
{
    $result = ma_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListKurikulum', '', '', $limit, $offset, null, 'Pull Kurikulum');
    if (!$resp['success']) {
        ma_add_error($result, 'GetListKurikulum', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_feeder = ma_value($row, ['id_kurikulum']);
        $label = ma_value($row, ['nama_kurikulum'], $id_feeder ?: 'Kurikulum');

        if ($ambil_detail && $id_feeder !== '') {
            $safe = mysqli_real_escape_string($conn, $id_feeder);
            $detail = neofeeder_request($conn, 'GetDetailKurikulum', "id_kurikulum = '$safe'", '', 1, 0, null, 'Pull Detail Kurikulum');
            if ($detail['success'] && !empty($detail['data'])) {
                $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                if (is_array($detail_row)) {
                    $row = array_merge($row, array_filter($detail_row, fn($value) => trim((string) $value) !== ''));
                }
            }
        }

        [$id_prodi, $id_prodi_feeder] = ma_prodi_lokal($conn, ma_value($row, ['id_prodi']));
        if (!$id_prodi) {
            ma_add_error($result, $label, 'Program studi lokal tidak ditemukan untuk ID prodi Feeder ' . ma_value($row, ['id_prodi']));
            continue;
        }

        $id_semester = ma_value($row, ['id_semester', 'id_semester_mulai_berlaku']);
        $tahun = substr($id_semester, 0, 4);
        if ($tahun === '') {
            $tahun = date('Y');
        }

        $total_sks = ma_int(ma_value($row, ['jumlah_sks_lulus']));
        $wajib = ma_int(ma_value($row, ['jumlah_sks_wajib', 'jumlah_sks_mata_kuliah_wajib']));
        $pilihan = ma_int(ma_value($row, ['jumlah_sks_pilihan', 'jumlah_sks_mata_kuliah_pilihan']));

        $existing = ma_kurikulum_lokal($conn, $id_feeder);
        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $ok = mysqli_query($conn, "
                UPDATE kurikulum SET
                    id_prodi = '$id_prodi',
                    id_prodi_feeder = " . ma_sql($conn, $id_prodi_feeder) . ",
                    nama_kurikulum = " . ma_sql($conn, ma_value($row, ['nama_kurikulum'])) . ",
                    tahun_kurikulum = '$tahun',
                    id_semester_mulai_feeder = " . ma_sql($conn, $id_semester) . ",
                    total_sks = '$total_sks',
                    jumlah_sks_lulus = '$total_sks',
                    jumlah_sks_wajib = '$wajib',
                    jumlah_sks_pilihan = '$pilihan',
                    raw_feeder_data = " . ma_sql($conn, $raw) . ",
                    status_sync_feeder = 'sudah',
                    last_sync_feeder = NOW(),
                    last_error_feeder = NULL,
                    status = 'aktif'
                WHERE id_kurikulum = '$existing'
            ");
            $ok ? $result['update']++ : ma_add_error($result, $label, mysqli_error($conn));
        } else {
            $ok = mysqli_query($conn, "
                INSERT INTO kurikulum (
                    id_kurikulum_feeder, id_prodi, id_prodi_feeder, nama_kurikulum, tahun_kurikulum,
                    id_semester_mulai_feeder, total_sks, jumlah_sks_lulus, jumlah_sks_wajib, jumlah_sks_pilihan,
                    raw_feeder_data, status_sync_feeder, last_sync_feeder, status
                ) VALUES (
                    " . ma_sql($conn, $id_feeder) . ", '$id_prodi', " . ma_sql($conn, $id_prodi_feeder) . ",
                    " . ma_sql($conn, ma_value($row, ['nama_kurikulum'])) . ", '$tahun',
                    " . ma_sql($conn, $id_semester) . ", '$total_sks', '$total_sks', '$wajib', '$pilihan',
                    " . ma_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif'
                )
            ");
            $ok ? $result['insert']++ : ma_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pull_matakuliah_akademik($conn, $limit, $offset, $ambil_detail = true)
{
    $result = ma_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetListMataKuliah', '', '', $limit, $offset, null, 'Pull Mata Kuliah');
    if (!$resp['success']) {
        ma_add_error($result, 'GetListMataKuliah', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_feeder = ma_value($row, ['id_matkul']);
        $kode_mk = ma_value($row, ['kode_mata_kuliah', 'kode_mk']);
        $label = $kode_mk . ' - ' . ma_value($row, ['nama_mata_kuliah', 'nama_mk']);

        if ($ambil_detail && $id_feeder !== '') {
            $safe = mysqli_real_escape_string($conn, $id_feeder);
            $detail = neofeeder_request($conn, 'GetDetailMataKuliah', "id_matkul = '$safe'", '', 1, 0, null, 'Pull Detail Mata Kuliah');
            if ($detail['success'] && !empty($detail['data'])) {
                $detail_row = isset($detail['data'][0]) ? $detail['data'][0] : $detail['data'];
                if (is_array($detail_row)) {
                    $row = array_merge($row, array_filter($detail_row, fn($value) => trim((string) $value) !== ''));
                }
            }
        }

        [$id_prodi, $id_prodi_feeder] = ma_prodi_lokal($conn, ma_value($row, ['id_prodi']));
        if (!$id_prodi) {
            ma_add_error($result, $label, 'Program studi lokal tidak ditemukan untuk ID prodi Feeder ' . ma_value($row, ['id_prodi']));
            continue;
        }

        $id_kurikulum = ma_kurikulum_lokal($conn, '', $id_prodi);
        if (!$id_kurikulum) {
            ma_add_error($result, $label, 'Kurikulum lokal untuk prodi ini belum tersedia. Pull kurikulum terlebih dahulu.');
            continue;
        }

        $sks_tm = ma_num(ma_value($row, ['sks_tatap_muka']));
        $sks_praktik = ma_num(ma_value($row, ['sks_praktek', 'sks_praktik']));
        $sks_lap = ma_num(ma_value($row, ['sks_praktek_lapangan', 'sks_praktik_lapangan']));
        $sks_sim = ma_num(ma_value($row, ['sks_simulasi']));
        $semester = max(1, ma_int(ma_value($row, ['semester'], 1)));
        $jenis_mk = strtolower(ma_value($row, ['nama_jenis_mata_kuliah'])) === 'pilihan' ? 'pilihan' : 'wajib';
        $existing = ma_matkul_lokal($conn, $id_feeder, $kode_mk, $id_prodi_feeder);
        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $ok = mysqli_query($conn, "
                UPDATE mata_kuliah SET
                    id_matkul_feeder = " . ma_sql($conn, $id_feeder) . ",
                    id_kurikulum = '$id_kurikulum',
                    id_prodi_feeder = " . ma_sql($conn, $id_prodi_feeder) . ",
                    kode_mk = " . ma_sql($conn, $kode_mk) . ",
                    nama_mk = " . ma_sql($conn, ma_value($row, ['nama_mata_kuliah', 'nama_mk'])) . ",
                    semester = '$semester',
                    sks_teori = '" . ma_int($sks_tm) . "',
                    sks_praktik = '" . ma_int($sks_praktik) . "',
                    sks_tatap_muka = '$sks_tm',
                    sks_praktik_lapangan = '$sks_lap',
                    sks_simulasi = '$sks_sim',
                    ada_sap = " . ma_sql($conn, ma_value($row, ['ada_sap'], '0')) . ",
                    ada_silabus = " . ma_sql($conn, ma_value($row, ['ada_silabus'], '0')) . ",
                    ada_bahan_ajar = " . ma_sql($conn, ma_value($row, ['ada_bahan_ajar'], '0')) . ",
                    ada_acara_praktik = " . ma_sql($conn, ma_value($row, ['ada_acara_praktek', 'ada_acara_praktik'], '0')) . ",
                    jenis_mk = '$jenis_mk',
                    id_jenis_mata_kuliah_feeder = " . ma_sql($conn, ma_value($row, ['id_jenis_mata_kuliah', 'jns_mk'])) . ",
                    raw_feeder_data = " . ma_sql($conn, $raw) . ",
                    status_sync_feeder = 'sudah',
                    last_sync_feeder = NOW(),
                    last_error_feeder = NULL,
                    status = 'aktif'
                WHERE id_mk = '$existing'
            ");
            $ok ? $result['update']++ : ma_add_error($result, $label, mysqli_error($conn));
        } else {
            $ok = mysqli_query($conn, "
                INSERT INTO mata_kuliah (
                    id_matkul_feeder, id_kurikulum, id_prodi_feeder, kode_mk, nama_mk, semester,
                    sks_teori, sks_praktik, sks_tatap_muka, sks_praktik_lapangan, sks_simulasi,
                    ada_sap, ada_silabus, ada_bahan_ajar, ada_acara_praktik,
                    jenis_mk, id_jenis_mata_kuliah_feeder, raw_feeder_data, status_sync_feeder, last_sync_feeder, status
                ) VALUES (
                    " . ma_sql($conn, $id_feeder) . ", '$id_kurikulum', " . ma_sql($conn, $id_prodi_feeder) . ",
                    " . ma_sql($conn, $kode_mk) . ", " . ma_sql($conn, ma_value($row, ['nama_mata_kuliah', 'nama_mk'])) . ", '$semester',
                    '" . ma_int($sks_tm) . "', '" . ma_int($sks_praktik) . "', '$sks_tm', '$sks_lap', '$sks_sim',
                    " . ma_sql($conn, ma_value($row, ['ada_sap'], '0')) . ", " . ma_sql($conn, ma_value($row, ['ada_silabus'], '0')) . ",
                    " . ma_sql($conn, ma_value($row, ['ada_bahan_ajar'], '0')) . ", " . ma_sql($conn, ma_value($row, ['ada_acara_praktek', 'ada_acara_praktik'], '0')) . ",
                    '$jenis_mk', " . ma_sql($conn, ma_value($row, ['id_jenis_mata_kuliah', 'jns_mk'])) . ",
                    " . ma_sql($conn, $raw) . ", 'sudah', NOW(), 'aktif'
                )
            ");
            $ok ? $result['insert']++ : ma_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}

function pull_matkul_kurikulum_akademik($conn, $limit, $offset)
{
    $result = ma_result();
    $result['next_offset'] = $offset + $limit;

    $resp = neofeeder_request($conn, 'GetMatkulKurikulum', '', '', $limit, $offset, null, 'Pull Matkul Kurikulum');
    if (!$resp['success']) {
        ma_add_error($result, 'GetMatkulKurikulum', $resp['message']);
        return $result;
    }

    $rows = is_array($resp['data'] ?? null) ? $resp['data'] : [];
    $result['total'] = count($rows);

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id_kurikulum_feeder = ma_value($row, ['id_kurikulum']);
        $id_matkul_feeder = ma_value($row, ['id_matkul']);
        $label = ma_value($row, ['kode_mata_kuliah']) . ' - ' . ma_value($row, ['nama_mata_kuliah']);
        $id_kurikulum = ma_kurikulum_lokal($conn, $id_kurikulum_feeder);
        $id_prodi_feeder = ma_value($row, ['id_prodi']);
        $id_mk = ma_matkul_lokal($conn, $id_matkul_feeder, ma_value($row, ['kode_mata_kuliah']), $id_prodi_feeder);

        if (!$id_mk && $id_kurikulum) {
            $id_mk = ma_seed_matkul_dari_relasi($conn, $id_kurikulum, $row);
        }

        if (!$id_kurikulum || !$id_mk) {
            ma_add_error($result, $label, 'Kurikulum atau mata kuliah lokal belum ditemukan. Pull kurikulum dan mata kuliah terlebih dahulu.');
            continue;
        }

        $semester = max(1, ma_int(ma_value($row, ['semester'], 1)));
        $sifat = ma_value($row, ['apakah_wajib']) === '1' ? 'wajib' : 'pilihan';
        $sks = ma_num(ma_value($row, ['sks_mata_kuliah']));
        $sks_tm = ma_num(ma_value($row, ['sks_tatap_muka']));
        $sks_praktik = ma_num(ma_value($row, ['sks_praktek', 'sks_praktik']));
        $sks_lap = ma_num(ma_value($row, ['sks_praktek_lapangan', 'sks_praktik_lapangan']));
        $sks_sim = ma_num(ma_value($row, ['sks_simulasi']));
        $raw = json_encode($row, JSON_UNESCAPED_UNICODE);

        $ok = mysqli_query($conn, "
            INSERT INTO matkul_kurikulum (
                id_kurikulum, id_mk, id_kurikulum_feeder, id_matkul_feeder, semester, sifat_mata_kuliah,
                sks_mata_kuliah, sks_tatap_muka, sks_praktik, sks_praktik_lapangan, sks_simulasi,
                raw_feeder_data, status_sync_feeder, last_sync_feeder
            ) VALUES (
                '$id_kurikulum', '$id_mk', " . ma_sql($conn, $id_kurikulum_feeder) . ", " . ma_sql($conn, $id_matkul_feeder) . ",
                '$semester', '$sifat', '$sks', '$sks_tm', '$sks_praktik', '$sks_lap', '$sks_sim',
                " . ma_sql($conn, $raw) . ", 'sudah', NOW()
            )
            ON DUPLICATE KEY UPDATE
                id_kurikulum_feeder = VALUES(id_kurikulum_feeder),
                id_matkul_feeder = VALUES(id_matkul_feeder),
                semester = VALUES(semester),
                sifat_mata_kuliah = VALUES(sifat_mata_kuliah),
                sks_mata_kuliah = VALUES(sks_mata_kuliah),
                sks_tatap_muka = VALUES(sks_tatap_muka),
                sks_praktik = VALUES(sks_praktik),
                sks_praktik_lapangan = VALUES(sks_praktik_lapangan),
                sks_simulasi = VALUES(sks_simulasi),
                raw_feeder_data = VALUES(raw_feeder_data),
                status_sync_feeder = 'sudah',
                last_sync_feeder = NOW(),
                last_error_feeder = NULL
        ");

        if ($ok) {
            mysqli_affected_rows($conn) === 1 ? $result['insert']++ : $result['update']++;
        } else {
            ma_add_error($result, $label, mysqli_error($conn));
        }
    }

    return $result;
}
?>
