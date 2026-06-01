<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Yudisium";
$page_subtitle = "Monitoring periode yudisium, peserta, validasi, dan SK yudisium.";

$cards = [
    ['label' => 'Periode Yudisium', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM yudisium"))],
    ['label' => 'Peserta', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_yudisium"))],
    ['label' => 'Valid', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_yudisium WHERE status_validasi = 'valid'"))],
    ['label' => 'Belum Validasi', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_yudisium WHERE status_validasi = 'belum'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT y.*, ta.tahun, ta.semester, COUNT(py.id_peserta_yudisium) AS total_peserta
    FROM yudisium y
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = y.id_tahun
    LEFT JOIN peserta_yudisium py ON py.id_yudisium = y.id_yudisium
    GROUP BY y.id_yudisium
    ORDER BY y.tanggal_yudisium DESC, y.id_yudisium DESC
    LIMIT 25
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars($r['nomor_yudisium'] ?? '-'),
            htmlspecialchars($r['tanggal_yudisium'] ?? '-'),
            htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-'),
            number_format((int)($r['total_peserta'] ?? 0)),
            internal_badge($r['status_yudisium'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('Yudisium', 'Monitoring periode yudisium, peserta, validasi, dan SK yudisium.', $cards, ['Nomor', 'Tanggal', 'Periode', 'Peserta', 'Status'], $rows, 'Belum ada periode yudisium.');
require_once "../../includes/footer.php";
