<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total Mahasiswa', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa"))],
    ['label' => 'Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa = 'aktif'"))],
    ['label' => 'Lulus', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa = 'lulus'"))],
    ['label' => 'Nonaktif/Cuti/DO', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa IN ('nonaktif','cuti','drop out')"))],
];

$rows = [];
$data_mahasiswa = internal_fetch_all($conn, "
    SELECT m.nim, m.nama_mahasiswa, m.angkatan, m.semester, m.status_mahasiswa, p.nama_prodi
    FROM mahasiswa m
    LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
    ORDER BY m.angkatan DESC, m.nim ASC
    LIMIT 50
");

foreach ($data_mahasiswa as $r) {
    $rows[] = [
        htmlspecialchars($r['nim'] ?? '-'),
        htmlspecialchars($r['nama_mahasiswa'] ?? '-'),
        htmlspecialchars($r['nama_prodi'] ?? '-'),
        htmlspecialchars($r['angkatan'] ?? '-'),
        htmlspecialchars('Semester ' . ($r['semester'] ?? '-')),
        internal_badge($r['status_mahasiswa'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
render_internal_page('Laporan Mahasiswa', 'Rekap master data mahasiswa berdasarkan prodi, angkatan, dan status.', $cards, ['NIM', 'Nama', 'Prodi', 'Angkatan', 'Semester', 'Status'], $rows);
require_once __DIR__ . "/../../includes/footer.php";
