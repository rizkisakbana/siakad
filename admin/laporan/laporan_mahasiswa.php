<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total Mahasiswa', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa"))],
    ['label' => 'Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa = 'aktif'"))],
    ['label' => 'Lulus', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa = 'lulus'"))],
    ['label' => 'Nonaktif/Cuti/DO', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa IN ('nonaktif','cuti','drop out')"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT m.nim, m.nama_mahasiswa, m.angkatan, m.semester, m.status_mahasiswa, p.nama_prodi
    FROM mahasiswa m
    LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
    ORDER BY m.angkatan DESC, m.nim ASC
    LIMIT 50
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars($r['nim'] ?? '-'),
            htmlspecialchars($r['nama_mahasiswa'] ?? '-'),
            htmlspecialchars($r['nama_prodi'] ?? '-'),
            htmlspecialchars($r['angkatan'] ?? '-'),
            htmlspecialchars('Semester ' . ($r['semester'] ?? '-')),
            internal_badge($r['status_mahasiswa'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_internal_page('Laporan Mahasiswa', 'Rekap master data mahasiswa berdasarkan prodi, angkatan, dan status.', $cards, ['NIM', 'Nama', 'Prodi', 'Angkatan', 'Semester', 'Status'], $rows);
require_once "../../includes/footer.php";
