<?php

require __DIR__ . '/../config/database.php';

$queries = [
    'users_role_missing' => "
        SELECT COUNT(*) AS total
        FROM users u
        LEFT JOIN roles r ON r.id_role = u.id_role
        WHERE r.id_role IS NULL
    ",
    'dosen_user_missing' => "
        SELECT COUNT(*) AS total
        FROM dosen d
        LEFT JOIN users u ON u.id_user = d.id_user
        WHERE d.id_user IS NOT NULL AND u.id_user IS NULL
    ",
    'dosen_prodi_missing' => "
        SELECT COUNT(*) AS total
        FROM dosen d
        LEFT JOIN prodi p ON p.id_prodi = d.id_prodi
        WHERE d.id_prodi IS NOT NULL AND p.id_prodi IS NULL
    ",
    'mahasiswa_user_missing' => "
        SELECT COUNT(*) AS total
        FROM mahasiswa m
        LEFT JOIN users u ON u.id_user = m.id_user
        WHERE m.id_user IS NOT NULL AND u.id_user IS NULL
    ",
    'mahasiswa_prodi_missing' => "
        SELECT COUNT(*) AS total
        FROM mahasiswa m
        LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
        WHERE m.id_prodi IS NOT NULL AND p.id_prodi IS NULL
    ",
    'kurikulum_prodi_missing' => "
        SELECT COUNT(*) AS total
        FROM kurikulum k
        LEFT JOIN prodi p ON p.id_prodi = k.id_prodi
        WHERE k.id_prodi IS NOT NULL AND p.id_prodi IS NULL
    ",
    'mata_kuliah_kurikulum_missing' => "
        SELECT COUNT(*) AS total
        FROM mata_kuliah mk
        LEFT JOIN kurikulum k ON k.id_kurikulum = mk.id_kurikulum
        WHERE k.id_kurikulum IS NULL
    ",
    'matkul_kurikulum_kurikulum_missing' => "
        SELECT COUNT(*) AS total
        FROM matkul_kurikulum mk
        LEFT JOIN kurikulum k ON k.id_kurikulum = mk.id_kurikulum
        WHERE k.id_kurikulum IS NULL
    ",
    'matkul_kurikulum_matkul_missing' => "
        SELECT COUNT(*) AS total
        FROM matkul_kurikulum mk
        LEFT JOIN mata_kuliah m ON m.id_mk = mk.id_mk
        WHERE m.id_mk IS NULL
    ",
    'kelas_kuliah_prodi_missing' => "
        SELECT COUNT(*) AS total
        FROM kelas_kuliah kk
        LEFT JOIN prodi p ON p.id_prodi = kk.id_prodi
        WHERE kk.id_prodi IS NOT NULL AND p.id_prodi IS NULL
    ",
    'kelas_kuliah_tahun_missing' => "
        SELECT COUNT(*) AS total
        FROM kelas_kuliah kk
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = kk.id_tahun
        WHERE kk.id_tahun IS NOT NULL AND ta.id_tahun IS NULL
    ",
    'kelas_kuliah_matkul_missing' => "
        SELECT COUNT(*) AS total
        FROM kelas_kuliah kk
        LEFT JOIN mata_kuliah mk ON mk.id_mk = kk.id_mk
        WHERE kk.id_mk IS NOT NULL AND mk.id_mk IS NULL
    ",
    'dosen_pengajar_kelas_missing' => "
        SELECT COUNT(*) AS total
        FROM dosen_pengajar_kelas dpk
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = dpk.id_kelas_kuliah
        WHERE kk.id_kelas_kuliah IS NULL
    ",
    'dosen_pengajar_dosen_missing' => "
        SELECT COUNT(*) AS total
        FROM dosen_pengajar_kelas dpk
        LEFT JOIN dosen d ON d.id_dosen = dpk.id_dosen
        WHERE d.id_dosen IS NULL
    ",
    'peserta_kelas_missing' => "
        SELECT COUNT(*) AS total
        FROM peserta_kelas_kuliah pkk
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = pkk.id_kelas_kuliah
        WHERE kk.id_kelas_kuliah IS NULL
    ",
    'peserta_mahasiswa_missing' => "
        SELECT COUNT(*) AS total
        FROM peserta_kelas_kuliah pkk
        LEFT JOIN mahasiswa m ON m.id_mahasiswa = pkk.id_mahasiswa
        WHERE m.id_mahasiswa IS NULL
    ",
    'krs_mahasiswa_missing' => "
        SELECT COUNT(*) AS total
        FROM krs k
        LEFT JOIN mahasiswa m ON m.id_mahasiswa = k.id_mahasiswa
        WHERE m.id_mahasiswa IS NULL
    ",
    'krs_tahun_missing' => "
        SELECT COUNT(*) AS total
        FROM krs k
        LEFT JOIN tahun_akademik ta ON ta.id_tahun = k.id_tahun
        WHERE ta.id_tahun IS NULL
    ",
    'krs_detail_krs_missing' => "
        SELECT COUNT(*) AS total
        FROM krs_detail kd
        LEFT JOIN krs k ON k.id_krs = kd.id_krs
        WHERE k.id_krs IS NULL
    ",
    'krs_detail_kelas_missing' => "
        SELECT COUNT(*) AS total
        FROM krs_detail kd
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = kd.id_kelas_kuliah
        WHERE kd.id_kelas_kuliah IS NOT NULL AND kk.id_kelas_kuliah IS NULL
    ",
    'nilai_kelas_missing' => "
        SELECT COUNT(*) AS total
        FROM nilai n
        LEFT JOIN kelas_kuliah kk ON kk.id_kelas_kuliah = n.id_kelas_kuliah
        WHERE kk.id_kelas_kuliah IS NULL
    ",
    'nilai_mahasiswa_missing' => "
        SELECT COUNT(*) AS total
        FROM nilai n
        LEFT JOIN mahasiswa m ON m.id_mahasiswa = n.id_mahasiswa
        WHERE m.id_mahasiswa IS NULL
    ",
    'notifikasi_user_missing' => "
        SELECT COUNT(*) AS total
        FROM notifikasi n
        LEFT JOIN users u ON u.id_user = n.id_user
        WHERE u.id_user IS NULL
    ",
];

foreach ($queries as $name => $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        echo "{$name}: ERROR " . mysqli_error($conn) . "\n";
        continue;
    }

    $row = mysqli_fetch_assoc($result);
    echo "{$name}: {$row['total']}\n";
}
