<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Sesi Presensi', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_kuliah"))],
    ['label' => 'Hadir', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_mahasiswa WHERE status_presensi = 'hadir'"))],
    ['label' => 'Izin/Sakit', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_mahasiswa WHERE status_presensi IN ('izin','sakit')"))],
    ['label' => 'Alpha', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_mahasiswa WHERE status_presensi = 'alpha'"))],
];

$rows = [];
$data_presensi = internal_fetch_all($conn, "
    SELECT pm.*, pk.tanggal_presensi, pk.pertemuan_ke, m.nim, m.nama_mahasiswa
    FROM presensi_mahasiswa pm
    JOIN presensi_kuliah pk ON pk.id_presensi_kuliah = pm.id_presensi_kuliah
    JOIN mahasiswa m ON m.id_mahasiswa = pm.id_mahasiswa
    ORDER BY pk.tanggal_presensi DESC, pm.id_presensi_mahasiswa DESC
    LIMIT 50
");

foreach ($data_presensi as $r) {
    $rows[] = [
        htmlspecialchars($r['tanggal_presensi'] ?? '-'),
        htmlspecialchars('Pertemuan ' . ($r['pertemuan_ke'] ?? '-')),
        htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
        internal_badge($r['status_presensi'] ?? '-'),
        htmlspecialchars($r['waktu_presensi'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
render_internal_page('Laporan Presensi', 'Rekap kehadiran mahasiswa per sesi perkuliahan.', $cards, ['Tanggal', 'Pertemuan', 'Mahasiswa', 'Status', 'Waktu'], $rows, 'Belum ada data presensi.');
require_once __DIR__ . "/../../includes/footer.php";
