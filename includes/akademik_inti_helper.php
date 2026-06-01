<?php

function akademik_inti_query($conn, $sql, $label)
{
    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException($label . ': ' . mysqli_error($conn));
    }

    return mysqli_affected_rows($conn);
}

function hitung_tabel($conn, $table)
{
    $table = mysqli_real_escape_string($conn, $table);
    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");
    if (!$q) {
        throw new RuntimeException('Hitung tabel ' . $table . ': ' . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function rebuild_matkul_kurikulum($conn)
{
    $sql = "
        INSERT INTO matkul_kurikulum (
            id_kurikulum,
            id_mk,
            id_kurikulum_feeder,
            id_matkul_feeder,
            semester,
            sifat_mata_kuliah,
            sks_mata_kuliah,
            sks_tatap_muka,
            sks_praktik,
            sks_praktik_lapangan,
            sks_simulasi,
            status_sync_feeder
        )
        SELECT
            mk.id_kurikulum,
            mk.id_mk,
            k.id_kurikulum_feeder,
            mk.id_matkul_feeder,
            mk.semester,
            COALESCE(mk.jenis_mk, 'wajib'),
            COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0),
            COALESCE(NULLIF(mk.sks_tatap_muka, 0), mk.sks_teori, 0),
            COALESCE(mk.sks_praktik, 0),
            COALESCE(mk.sks_praktik_lapangan, 0),
            COALESCE(mk.sks_simulasi, 0),
            CASE
                WHEN mk.id_matkul_feeder IS NOT NULL AND mk.id_matkul_feeder <> ''
                 AND k.id_kurikulum_feeder IS NOT NULL AND k.id_kurikulum_feeder <> ''
                THEN 'sudah'
                ELSE 'belum'
            END
        FROM mata_kuliah mk
        JOIN kurikulum k ON k.id_kurikulum = mk.id_kurikulum
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
            updated_at = NOW()
    ";

    return akademik_inti_query($conn, $sql, 'Rebuild matkul_kurikulum');
}

function rebuild_kelas_kuliah($conn)
{
    $sql = "
        INSERT INTO kelas_kuliah (
            id_jadwal,
            id_tahun,
            id_semester_feeder,
            id_prodi,
            id_prodi_feeder,
            id_mk,
            id_matkul_feeder,
            id_kelas_internal,
            nama_kelas_kuliah,
            kode_kelas,
            mode_kuliah,
            tanggal_mulai,
            tanggal_selesai,
            kapasitas,
            status,
            status_sync_feeder
        )
        SELECT
            j.id_jadwal,
            j.id_tahun,
            COALESCE(NULLIF(ta.id_semester_feeder, ''), NULLIF(ta.id_feeder, '')),
            k.id_prodi,
            COALESCE(NULLIF(p.id_prodi_feeder, ''), NULLIF(p.id_feeder, '')),
            j.id_mk,
            NULLIF(mk.id_matkul_feeder, ''),
            j.id_kelas,
            COALESCE(NULLIF(k.nama_kelas, ''), CONCAT('Kelas ', j.id_jadwal)),
            NULLIF(k.kode_kelas, ''),
            COALESCE(j.metode, 'tatap muka'),
            ta.tanggal_mulai,
            ta.tanggal_selesai,
            COALESCE(k.kapasitas, 40),
            COALESCE(j.status, 'aktif'),
            CASE
                WHEN NULLIF(j.id_kelas_kuliah_feeder, '') IS NOT NULL THEN 'sudah'
                ELSE 'belum'
            END
        FROM jadwal_kuliah j
        JOIN kelas k ON k.id_kelas = j.id_kelas
        LEFT JOIN prodi p ON p.id_prodi = k.id_prodi
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = j.id_tahun
        LEFT JOIN mata_kuliah mk ON mk.id_mk = j.id_mk
        ON DUPLICATE KEY UPDATE
            id_tahun = VALUES(id_tahun),
            id_semester_feeder = VALUES(id_semester_feeder),
            id_prodi = VALUES(id_prodi),
            id_prodi_feeder = VALUES(id_prodi_feeder),
            id_mk = VALUES(id_mk),
            id_matkul_feeder = VALUES(id_matkul_feeder),
            id_kelas_internal = VALUES(id_kelas_internal),
            nama_kelas_kuliah = VALUES(nama_kelas_kuliah),
            kode_kelas = VALUES(kode_kelas),
            mode_kuliah = VALUES(mode_kuliah),
            tanggal_mulai = VALUES(tanggal_mulai),
            tanggal_selesai = VALUES(tanggal_selesai),
            kapasitas = VALUES(kapasitas),
            status = VALUES(status),
            updated_at = NOW()
    ";

    $affected = akademik_inti_query($conn, $sql, 'Rebuild kelas_kuliah');

    akademik_inti_query($conn, "
        UPDATE jadwal_kuliah j
        JOIN kelas_kuliah kk ON kk.id_jadwal = j.id_jadwal
        SET
            j.id_kelas_kuliah = kk.id_kelas_kuliah,
            j.id_kelas_kuliah_feeder = kk.id_kelas_kuliah_feeder,
            j.updated_at = NOW()
    ", 'Update relasi jadwal_kuliah');

    return $affected;
}

function rebuild_dosen_pengajar_kelas($conn)
{
    $sql = "
        INSERT INTO dosen_pengajar_kelas (
            id_kelas_kuliah,
            id_kelas_kuliah_feeder,
            id_dosen,
            id_dosen_feeder,
            urutan_pengajar,
            sks_substansi_total,
            rencana_tatap_muka,
            status_sync_feeder
        )
        SELECT
            kk.id_kelas_kuliah,
            kk.id_kelas_kuliah_feeder,
            j.id_dosen,
            COALESCE(NULLIF(d.id_dosen_feeder, ''), NULLIF(d.id_feeder, '')),
            1,
            COALESCE(NULLIF(mk.sks_tatap_muka, 0), mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0),
            16,
            CASE
                WHEN NULLIF(kk.id_kelas_kuliah_feeder, '') IS NOT NULL
                 AND COALESCE(NULLIF(d.id_dosen_feeder, ''), NULLIF(d.id_feeder, '')) IS NOT NULL
                THEN 'sudah'
                ELSE 'belum'
            END
        FROM jadwal_kuliah j
        JOIN kelas_kuliah kk ON kk.id_jadwal = j.id_jadwal
        JOIN dosen d ON d.id_dosen = j.id_dosen
        LEFT JOIN mata_kuliah mk ON mk.id_mk = j.id_mk
        ON DUPLICATE KEY UPDATE
            id_kelas_kuliah_feeder = VALUES(id_kelas_kuliah_feeder),
            id_dosen_feeder = VALUES(id_dosen_feeder),
            sks_substansi_total = VALUES(sks_substansi_total),
            updated_at = NOW()
    ";

    return akademik_inti_query($conn, $sql, 'Rebuild dosen_pengajar_kelas');
}

function rebuild_peserta_kelas_kuliah($conn)
{
    akademik_inti_query($conn, "
        UPDATE krs_detail kd
        JOIN kelas_kuliah kk ON kk.id_jadwal = kd.id_jadwal
        SET
            kd.id_kelas_kuliah = kk.id_kelas_kuliah,
            kd.id_kelas_kuliah_feeder = kk.id_kelas_kuliah_feeder,
            kd.updated_at = NOW()
    ", 'Update relasi krs_detail');

    $sql = "
        INSERT INTO peserta_kelas_kuliah (
            id_kelas_kuliah,
            id_kelas_kuliah_feeder,
            id_krs_detail,
            id_krs,
            id_mahasiswa,
            id_registrasi_mahasiswa_feeder,
            nim,
            status_peserta,
            status_sync_feeder
        )
        SELECT
            kk.id_kelas_kuliah,
            kk.id_kelas_kuliah_feeder,
            kd.id_krs_detail,
            kr.id_krs,
            kr.id_mahasiswa,
            NULLIF(m.id_registrasi_feeder, ''),
            m.nim,
            CASE WHEN kd.status_mk = 'batal' THEN 'batal' ELSE 'aktif' END,
            CASE
                WHEN NULLIF(kk.id_kelas_kuliah_feeder, '') IS NOT NULL
                 AND NULLIF(m.id_registrasi_feeder, '') IS NOT NULL
                THEN 'sudah'
                ELSE 'belum'
            END
        FROM krs_detail kd
        JOIN krs kr ON kr.id_krs = kd.id_krs
        JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
        JOIN kelas_kuliah kk ON kk.id_jadwal = kd.id_jadwal
        ON DUPLICATE KEY UPDATE
            id_kelas_kuliah_feeder = VALUES(id_kelas_kuliah_feeder),
            id_krs_detail = VALUES(id_krs_detail),
            id_krs = VALUES(id_krs),
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            nim = VALUES(nim),
            status_peserta = VALUES(status_peserta),
            updated_at = NOW()
    ";

    return akademik_inti_query($conn, $sql, 'Rebuild peserta_kelas_kuliah');
}

function rebuild_akademik_inti($conn, $use_transaction = true)
{
    if ($use_transaction) {
        mysqli_begin_transaction($conn);
    }

    try {
        $affected = [
            'matkul_kurikulum' => rebuild_matkul_kurikulum($conn),
            'kelas_kuliah' => rebuild_kelas_kuliah($conn),
            'dosen_pengajar_kelas' => rebuild_dosen_pengajar_kelas($conn),
            'peserta_kelas_kuliah' => rebuild_peserta_kelas_kuliah($conn),
        ];

        if ($use_transaction) {
            mysqli_commit($conn);
        }
    } catch (Throwable $e) {
        if ($use_transaction) {
            mysqli_rollback($conn);
        }
        throw $e;
    }

    return [
        'affected' => $affected,
        'total' => [
            'kurikulum' => hitung_tabel($conn, 'kurikulum'),
            'mata_kuliah' => hitung_tabel($conn, 'mata_kuliah'),
            'matkul_kurikulum' => hitung_tabel($conn, 'matkul_kurikulum'),
            'kelas_kuliah' => hitung_tabel($conn, 'kelas_kuliah'),
            'dosen_pengajar_kelas' => hitung_tabel($conn, 'dosen_pengajar_kelas'),
            'peserta_kelas_kuliah' => hitung_tabel($conn, 'peserta_kelas_kuliah'),
        ],
    ];
}
