<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "KHS Mahasiswa";
$page_subtitle = "Monitoring KHS, IPS, IPK, dan status publish nilai.";

$cards = [
    ['label' => 'Total KHS', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM khs"))],
    ['label' => 'Publish', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM khs WHERE status_publish = 'publish'"))],
    ['label' => 'Draft', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM khs WHERE status_publish = 'draft'"))],
    ['label' => 'AKM', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM aktivitas_kuliah_mahasiswa"))],
];

$rows = [];
$data_khs = internal_fetch_all($conn, "
    SELECT kh.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester
    FROM khs kh
    JOIN mahasiswa m ON m.id_mahasiswa = kh.id_mahasiswa
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = kh.id_tahun
    ORDER BY kh.id_tahun DESC, kh.id_khs DESC
    LIMIT 50
");

foreach ($data_khs as $r) {
    $rows[] = [
        htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
        htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-'),
        htmlspecialchars((string)($r['total_sks'] ?? 0)),
        htmlspecialchars((string)($r['ips'] ?? 0)),
        htmlspecialchars((string)($r['ipk'] ?? 0)),
        internal_badge($r['status_publish'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('KHS Mahasiswa', 'Monitoring KHS, IPS, IPK, dan status publish nilai.', $cards, ['Mahasiswa', 'Periode', 'SKS', 'IPS', 'IPK', 'Status'], $rows, 'Belum ada data KHS.');
require_once __DIR__ . "/../../includes/footer.php";
