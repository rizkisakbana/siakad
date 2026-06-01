<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/helper.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "AKM Mahasiswa";
$page_subtitle = "Monitoring aktivitas kuliah mahasiswa per semester.";

$cards = [
    ['label' => 'Total AKM', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM aktivitas_kuliah_mahasiswa"))],
    ['label' => 'Sudah Sync', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM aktivitas_kuliah_mahasiswa WHERE status_sync_feeder='sudah'"))],
    ['label' => 'Belum/Gagal', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM aktivitas_kuliah_mahasiswa WHERE status_sync_feeder IN ('belum','gagal')"))],
    ['label' => 'KHS Publish', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM khs WHERE status_publish='publish'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT akm.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester
    FROM aktivitas_kuliah_mahasiswa akm
    JOIN mahasiswa m ON m.id_mahasiswa = akm.id_mahasiswa
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = akm.id_tahun
    ORDER BY akm.id_tahun DESC, m.nim ASC
    LIMIT 100
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
            htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: ($r['id_semester_feeder'] ?? '-')),
            htmlspecialchars($r['nama_status_mahasiswa'] ?? '-'),
            htmlspecialchars((string)($r['sks_semester'] ?? 0)),
            htmlspecialchars((string)($r['sks_total'] ?? 0)),
            htmlspecialchars((string)($r['ips'] ?? 0)),
            htmlspecialchars((string)($r['ipk'] ?? 0)),
            internal_badge($r['status_sync_feeder'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('AKM Mahasiswa', 'Monitoring aktivitas kuliah mahasiswa per semester.', $cards, ['Mahasiswa', 'Periode', 'Status', 'SKS Smt', 'SKS Total', 'IPS', 'IPK', 'Sync'], $rows, 'Belum ada data AKM.');
require_once "../../includes/footer.php";
