<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total Dosen', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM dosen"))],
    ['label' => 'Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM dosen WHERE status = 'aktif'"))],
    ['label' => 'Dosen Tetap', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM dosen WHERE status_dosen = 'tetap'"))],
    ['label' => 'Pengajar Kelas', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas"))],
];

$rows = [];
$data_dosen = internal_fetch_all($conn, "
    SELECT d.nidn, d.nidk, d.nama_dosen, d.status_dosen, d.status, p.nama_prodi
    FROM dosen d
    LEFT JOIN prodi p ON p.id_prodi = d.id_prodi
    ORDER BY d.nama_dosen ASC
    LIMIT 50
");

foreach ($data_dosen as $r) {
    $rows[] = [
        htmlspecialchars($r['nidn'] ?: ($r['nidk'] ?? '-')),
        htmlspecialchars($r['nama_dosen'] ?? '-'),
        htmlspecialchars($r['nama_prodi'] ?? '-'),
        htmlspecialchars($r['status_dosen'] ?? '-'),
        internal_badge($r['status'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
render_internal_page('Laporan Dosen', 'Rekap dosen, homebase prodi, dan status pengajar.', $cards, ['NIDN/NIDK', 'Nama', 'Prodi', 'Jenis', 'Status'], $rows);
require_once __DIR__ . "/../../includes/footer.php";
