<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

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
$q = mysqli_query($conn, "
    SELECT kh.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester
    FROM khs kh
    JOIN mahasiswa m ON m.id_mahasiswa = kh.id_mahasiswa
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = kh.id_tahun
    ORDER BY kh.id_tahun DESC, kh.id_khs DESC
    LIMIT 50
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
            htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-'),
            htmlspecialchars((string)($r['total_sks'] ?? 0)),
            htmlspecialchars((string)($r['ips'] ?? 0)),
            htmlspecialchars((string)($r['ipk'] ?? 0)),
            internal_badge($r['status_publish'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('KHS Mahasiswa', 'Monitoring KHS, IPS, IPK, dan status publish nilai.', $cards, ['Mahasiswa', 'Periode', 'SKS', 'IPS', 'IPK', 'Status'], $rows, 'Belum ada data KHS.');
require_once "../../includes/footer.php";
