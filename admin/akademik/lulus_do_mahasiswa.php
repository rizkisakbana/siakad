<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Lulus / DO Mahasiswa";
$page_subtitle = "Monitoring data mahasiswa lulus, keluar, pindah, dan drop out.";

$cards = [
    ['label' => 'Total Lulus/DO', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do"))],
    ['label' => 'Lulus', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do WHERE jenis_keluar LIKE '%lulus%'"))],
    ['label' => 'Sudah Sync', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do WHERE status_sync_feeder IN ('belum','gagal')"))],
];

$rows = [];
$data_lulus_do = internal_fetch_all($conn, "
    SELECT ld.*, m.nim, m.nama_mahasiswa, p.nama_prodi
    FROM mahasiswa_lulus_do ld
    JOIN mahasiswa m ON m.id_mahasiswa = ld.id_mahasiswa
    LEFT JOIN prodi p ON p.id_prodi = ld.id_prodi
    ORDER BY ld.tanggal_keluar DESC, m.nim ASC
    LIMIT 100
");

foreach ($data_lulus_do as $r) {
    $rows[] = [
        htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
        htmlspecialchars($r['nama_prodi'] ?? '-'),
        htmlspecialchars($r['jenis_keluar'] ?? '-'),
        htmlspecialchars(!empty($r['tanggal_keluar']) ? format_tanggal($r['tanggal_keluar']) : '-'),
        htmlspecialchars($r['nomor_sk_yudisium'] ?? '-'),
        htmlspecialchars((string)($r['ipk'] ?? 0)),
        htmlspecialchars($r['nomor_ijazah'] ?? '-'),
        internal_badge($r['status_sync_feeder'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('Lulus / DO Mahasiswa', 'Monitoring data mahasiswa lulus, keluar, pindah, dan drop out.', $cards, ['Mahasiswa', 'Prodi', 'Jenis Keluar', 'Tanggal', 'SK Yudisium', 'IPK', 'Ijazah', 'Sync'], $rows, 'Belum ada data lulus/DO.');
require_once __DIR__ . "/../../includes/footer.php";
