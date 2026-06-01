<?php

function transaksi_pelaporan_query($conn, $sql, $label)
{
    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException($label . ': ' . mysqli_error($conn));
    }

    return mysqli_affected_rows($conn);
}

function transaksi_pelaporan_count($conn, $table)
{
    $table = mysqli_real_escape_string($conn, $table);
    $q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");
    if (!$q) {
        throw new RuntimeException('Hitung tabel ' . $table . ': ' . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($q);
    return (int)($row['total'] ?? 0);
}

function rebuild_krs_pelaporan($conn)
{
    return transaksi_pelaporan_query($conn, "
        UPDATE krs kr
        LEFT JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = kr.id_tahun
        SET
            kr.id_registrasi_mahasiswa_feeder = COALESCE(NULLIF(kr.id_registrasi_mahasiswa_feeder, ''), NULLIF(m.id_registrasi_feeder, '')),
            kr.id_semester_feeder = COALESCE(NULLIF(kr.id_semester_feeder, ''), NULLIF(ta.id_semester_feeder, ''), NULLIF(ta.id_feeder, '')),
            kr.updated_at = NOW()
    ", 'Rebuild KRS pelaporan');
}

function rebuild_nilai_pelaporan($conn)
{
    return transaksi_pelaporan_query($conn, "
        UPDATE nilai n
        JOIN krs_detail kd ON kd.id_krs_detail = n.id_krs_detail
        JOIN krs kr ON kr.id_krs = kd.id_krs
        LEFT JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = kd.id_kelas_kuliah
        LEFT JOIN jadwal_kuliah j ON j.id_jadwal = kd.id_jadwal
        LEFT JOIN mata_kuliah mk ON mk.id_mk = COALESCE(kk.id_mk, j.id_mk)
        SET
            n.id_kelas_kuliah = COALESCE(n.id_kelas_kuliah, kd.id_kelas_kuliah, kk.id_kelas_kuliah),
            n.id_kelas_kuliah_feeder = COALESCE(NULLIF(n.id_kelas_kuliah_feeder, ''), NULLIF(kd.id_kelas_kuliah_feeder, ''), NULLIF(kk.id_kelas_kuliah_feeder, '')),
            n.id_mahasiswa = COALESCE(n.id_mahasiswa, kr.id_mahasiswa),
            n.id_registrasi_mahasiswa_feeder = COALESCE(NULLIF(n.id_registrasi_mahasiswa_feeder, ''), NULLIF(m.id_registrasi_feeder, '')),
            n.id_matkul = COALESCE(n.id_matkul, mk.id_mk),
            n.id_matkul_feeder = COALESCE(NULLIF(n.id_matkul_feeder, ''), NULLIF(mk.id_matkul_feeder, '')),
            n.nilai_indeks = COALESCE(n.nilai_indeks, n.bobot),
            n.updated_at = NOW()
    ", 'Rebuild nilai pelaporan');
}

function rebuild_khs_dari_nilai($conn)
{
    $affected = transaksi_pelaporan_query($conn, "
        INSERT INTO khs (
            id_mahasiswa,
            id_registrasi_mahasiswa_feeder,
            id_tahun,
            id_semester_feeder,
            total_sks,
            sks_semester,
            sks_total,
            total_mutu,
            ips,
            ipk,
            id_status_mahasiswa_feeder,
            nama_status_mahasiswa,
            status_publish
        )
        SELECT
            kr.id_mahasiswa,
            NULLIF(m.id_registrasi_feeder, ''),
            kr.id_tahun,
            COALESCE(NULLIF(ta.id_semester_feeder, ''), NULLIF(ta.id_feeder, '')),
            SUM(COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) AS total_sks,
            SUM(COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) AS sks_semester,
            SUM(COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) AS sks_total,
            SUM((COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) * COALESCE(n.bobot, n.nilai_indeks, 0)) AS total_mutu,
            CASE
                WHEN SUM(COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) > 0
                THEN ROUND(SUM((COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)) * COALESCE(n.bobot, n.nilai_indeks, 0)) / SUM(COALESCE(mk.sks_teori, 0) + COALESCE(mk.sks_praktik, 0)), 2)
                ELSE 0
            END AS ips,
            0 AS ipk,
            NULLIF(m.id_status_mahasiswa_feeder, ''),
            m.status_mahasiswa,
            CASE WHEN SUM(CASE WHEN n.status_publish = 'publish' THEN 1 ELSE 0 END) > 0 THEN 'publish' ELSE 'draft' END
        FROM krs_detail kd
        JOIN krs kr ON kr.id_krs = kd.id_krs
        JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
        JOIN nilai n ON n.id_krs_detail = kd.id_krs_detail
        LEFT JOIN jadwal_kuliah j ON j.id_jadwal = kd.id_jadwal
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = kd.id_kelas_kuliah
        LEFT JOIN mata_kuliah mk ON mk.id_mk = COALESCE(kk.id_mk, j.id_mk)
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = kr.id_tahun
        WHERE kd.status_mk = 'diambil'
        GROUP BY kr.id_mahasiswa, kr.id_tahun
        ON DUPLICATE KEY UPDATE
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_semester_feeder = VALUES(id_semester_feeder),
            total_sks = VALUES(total_sks),
            sks_semester = VALUES(sks_semester),
            sks_total = VALUES(sks_total),
            total_mutu = VALUES(total_mutu),
            ips = VALUES(ips),
            id_status_mahasiswa_feeder = VALUES(id_status_mahasiswa_feeder),
            nama_status_mahasiswa = VALUES(nama_status_mahasiswa),
            status_publish = VALUES(status_publish),
            updated_at = NOW()
    ", 'Rebuild KHS dari nilai');

    transaksi_pelaporan_query($conn, "
        UPDATE khs kh
        SET
            kh.sks_total = (
                SELECT COALESCE(SUM(kh2.sks_semester), 0)
                FROM khs kh2
                WHERE kh2.id_mahasiswa = kh.id_mahasiswa
                  AND kh2.id_tahun <= kh.id_tahun
            ),
            kh.ipk = (
                SELECT CASE
                    WHEN COALESCE(SUM(kh2.sks_semester), 0) > 0
                    THEN ROUND(SUM(kh2.total_mutu) / SUM(kh2.sks_semester), 2)
                    ELSE 0
                END
                FROM khs kh2
                WHERE kh2.id_mahasiswa = kh.id_mahasiswa
                  AND kh2.id_tahun <= kh.id_tahun
            ),
            kh.updated_at = NOW()
    ", 'Update IPK kumulatif KHS');

    return $affected;
}

function rebuild_akm_pelaporan($conn)
{
    return transaksi_pelaporan_query($conn, "
        INSERT INTO aktivitas_kuliah_mahasiswa (
            id_mahasiswa,
            id_registrasi_mahasiswa_feeder,
            id_tahun,
            id_semester_feeder,
            id_status_mahasiswa_feeder,
            nama_status_mahasiswa,
            ips,
            ipk,
            sks_semester,
            sks_total,
            biaya_kuliah_smt,
            status_sync_feeder
        )
        SELECT
            kh.id_mahasiswa,
            COALESCE(NULLIF(kh.id_registrasi_mahasiswa_feeder, ''), NULLIF(m.id_registrasi_feeder, '')),
            kh.id_tahun,
            COALESCE(NULLIF(kh.id_semester_feeder, ''), NULLIF(ta.id_semester_feeder, ''), NULLIF(ta.id_feeder, '')),
            COALESCE(NULLIF(kh.id_status_mahasiswa_feeder, ''), NULLIF(m.id_status_mahasiswa_feeder, '')),
            COALESCE(NULLIF(kh.nama_status_mahasiswa, ''), m.status_mahasiswa),
            kh.ips,
            kh.ipk,
            COALESCE(kh.sks_semester, kh.total_sks, 0),
            COALESCE(kh.sks_total, kh.total_sks, 0),
            COALESCE(kh.biaya_kuliah_smt, 0),
            'belum'
        FROM khs kh
        JOIN mahasiswa m ON m.id_mahasiswa = kh.id_mahasiswa
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = kh.id_tahun
        ON DUPLICATE KEY UPDATE
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_semester_feeder = VALUES(id_semester_feeder),
            id_status_mahasiswa_feeder = VALUES(id_status_mahasiswa_feeder),
            nama_status_mahasiswa = VALUES(nama_status_mahasiswa),
            ips = VALUES(ips),
            ipk = VALUES(ipk),
            sks_semester = VALUES(sks_semester),
            sks_total = VALUES(sks_total),
            biaya_kuliah_smt = VALUES(biaya_kuliah_smt),
            updated_at = NOW()
    ", 'Rebuild AKM pelaporan');
}

function rebuild_riwayat_status_pelaporan($conn)
{
    return transaksi_pelaporan_query($conn, "
        INSERT INTO riwayat_status_mahasiswa (
            id_mahasiswa,
            id_registrasi_mahasiswa_feeder,
            id_tahun,
            id_semester_feeder,
            status_mahasiswa,
            id_status_mahasiswa_feeder,
            tanggal_status,
            keterangan
        )
        SELECT
            m.id_mahasiswa,
            NULLIF(m.id_registrasi_feeder, ''),
            COALESCE(ta.id_tahun, ta_active.id_tahun),
            COALESCE(NULLIF(ta.id_semester_feeder, ''), NULLIF(ta.id_feeder, ''), NULLIF(ta_active.id_semester_feeder, ''), NULLIF(ta_active.id_feeder, '')),
            m.status_mahasiswa,
            NULLIF(m.id_status_mahasiswa_feeder, ''),
            COALESCE(m.tanggal_keluar, m.tanggal_masuk, CURDATE()),
            'Rebuild status terakhir mahasiswa'
        FROM mahasiswa m
        LEFT JOIN tahun_akademik ta
          ON ta.id_tahun = (
              SELECT ta2.id_tahun
              FROM tahun_akademik ta2
              WHERE ta2.tahun LIKE CONCAT(m.angkatan, '/%')
              ORDER BY
                  CASE WHEN ta2.semester = 'Ganjil' THEN 0 ELSE 1 END,
                  ta2.id_tahun ASC
              LIMIT 1
          )
        LEFT JOIN tahun_akademik ta_active ON ta_active.status = 'aktif'
        ON DUPLICATE KEY UPDATE
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_tahun = COALESCE(VALUES(id_tahun), riwayat_status_mahasiswa.id_tahun),
            id_semester_feeder = VALUES(id_semester_feeder),
            id_status_mahasiswa_feeder = VALUES(id_status_mahasiswa_feeder),
            tanggal_status = VALUES(tanggal_status),
            updated_at = NOW()
    ", 'Rebuild riwayat status mahasiswa');
}

function rebuild_lulus_do_pelaporan($conn)
{
    return transaksi_pelaporan_query($conn, "
        INSERT INTO mahasiswa_lulus_do (
            id_mahasiswa,
            id_registrasi_mahasiswa_feeder,
            id_prodi,
            id_prodi_feeder,
            id_periode_keluar_feeder,
            jenis_keluar,
            tanggal_keluar,
            keterangan,
            ipk,
            status_sync_feeder
        )
        SELECT
            m.id_mahasiswa,
            NULLIF(m.id_registrasi_feeder, ''),
            m.id_prodi,
            COALESCE(NULLIF(m.id_prodi_feeder, ''), NULLIF(p.id_prodi_feeder, ''), NULLIF(p.id_feeder, '')),
            NULLIF(ta.id_semester_feeder, ''),
            m.status_mahasiswa,
            m.tanggal_keluar,
            CONCAT('Rebuild status ', m.status_mahasiswa),
            COALESCE(kh.ipk, 0),
            'belum'
        FROM mahasiswa m
        LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
        LEFT JOIN tahun_akademik ta ON m.tanggal_keluar BETWEEN ta.tanggal_mulai AND ta.tanggal_selesai
        LEFT JOIN (
            SELECT k1.id_mahasiswa, k1.ipk
            FROM khs k1
            JOIN (
                SELECT id_mahasiswa, MAX(id_tahun) AS id_tahun
                FROM khs
                GROUP BY id_mahasiswa
            ) last_khs ON last_khs.id_mahasiswa = k1.id_mahasiswa AND last_khs.id_tahun = k1.id_tahun
        ) kh ON kh.id_mahasiswa = m.id_mahasiswa
        WHERE m.status_mahasiswa IN ('lulus', 'drop out', 'mengundurkan diri', 'pindah')
        ON DUPLICATE KEY UPDATE
            id_registrasi_mahasiswa_feeder = VALUES(id_registrasi_mahasiswa_feeder),
            id_prodi_feeder = VALUES(id_prodi_feeder),
            id_periode_keluar_feeder = VALUES(id_periode_keluar_feeder),
            jenis_keluar = VALUES(jenis_keluar),
            tanggal_keluar = VALUES(tanggal_keluar),
            ipk = VALUES(ipk),
            updated_at = NOW()
    ", 'Rebuild mahasiswa lulus DO');
}

function rebuild_transaksi_pelaporan($conn)
{
    mysqli_begin_transaction($conn);

    try {
        $affected = [
            'krs' => rebuild_krs_pelaporan($conn),
            'nilai' => rebuild_nilai_pelaporan($conn),
            'khs' => rebuild_khs_dari_nilai($conn),
            'aktivitas_kuliah_mahasiswa' => rebuild_akm_pelaporan($conn),
            'riwayat_status_mahasiswa' => rebuild_riwayat_status_pelaporan($conn),
            'mahasiswa_lulus_do' => rebuild_lulus_do_pelaporan($conn),
        ];

        mysqli_commit($conn);
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        throw $e;
    }

    return [
        'affected' => $affected,
        'total' => [
            'krs' => transaksi_pelaporan_count($conn, 'krs'),
            'krs_detail' => transaksi_pelaporan_count($conn, 'krs_detail'),
            'nilai' => transaksi_pelaporan_count($conn, 'nilai'),
            'khs' => transaksi_pelaporan_count($conn, 'khs'),
            'aktivitas_kuliah_mahasiswa' => transaksi_pelaporan_count($conn, 'aktivitas_kuliah_mahasiswa'),
            'riwayat_status_mahasiswa' => transaksi_pelaporan_count($conn, 'riwayat_status_mahasiswa'),
            'mahasiswa_lulus_do' => transaksi_pelaporan_count($conn, 'mahasiswa_lulus_do'),
            'prestasi_mahasiswa' => transaksi_pelaporan_count($conn, 'prestasi_mahasiswa'),
            'aktivitas_mahasiswa' => transaksi_pelaporan_count($conn, 'aktivitas_mahasiswa'),
            'konversi_mbkm' => transaksi_pelaporan_count($conn, 'konversi_mbkm'),
        ],
    ];
}
