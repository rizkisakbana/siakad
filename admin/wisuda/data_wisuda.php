<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Wisuda";
$page_subtitle = "Monitoring periode wisuda, peserta, kuota, dan kelengkapan ijazah/transkrip.";

$cards = [
    ['label' => 'Periode Wisuda', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM wisuda"))],
    ['label' => 'Peserta', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_wisuda"))],
    ['label' => 'Valid', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_wisuda WHERE status_pendaftaran = 'valid'"))],
    ['label' => 'Hadir', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_wisuda WHERE status_pendaftaran = 'hadir'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT w.*, COUNT(pw.id_peserta_wisuda) AS total_peserta
    FROM wisuda w
    LEFT JOIN peserta_wisuda pw ON pw.id_wisuda = w.id_wisuda
    GROUP BY w.id_wisuda
    ORDER BY w.tanggal_wisuda DESC, w.id_wisuda DESC
    LIMIT 25
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars($r['nama_periode'] ?? '-'),
            htmlspecialchars($r['tanggal_wisuda'] ?? '-'),
            htmlspecialchars($r['lokasi'] ?? '-'),
            number_format((int)($r['total_peserta'] ?? 0)) . ' / ' . number_format((int)($r['kuota'] ?? 0)),
            internal_badge($r['status_wisuda'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('Wisuda', 'Monitoring periode wisuda, peserta, kuota, dan kelengkapan ijazah/transkrip.', $cards, ['Periode', 'Tanggal', 'Lokasi', 'Peserta/Kuota', 'Status'], $rows, 'Belum ada periode wisuda.');
require_once "../../includes/footer.php";
