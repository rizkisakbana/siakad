<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Pendaftar";
$page_subtitle = "Monitoring awal pendaftar, gelombang, dan status verifikasi PMB.";

$cards = [
    ['label' => 'Total Pendaftar', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pendaftar_pmb"))],
    ['label' => 'Diterima', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pendaftar_pmb WHERE status_pendaftaran = 'diterima'"))],
    ['label' => 'Perlu Verifikasi', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pendaftar_pmb WHERE status_pendaftaran IN ('daftar','verifikasi')"))],
    ['label' => 'Gelombang Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pmb_gelombang WHERE status = 'aktif'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT p.*, pr.nama_prodi, g.nama_gelombang
    FROM pendaftar_pmb p
    LEFT JOIN prodi pr ON pr.id_prodi = p.id_prodi_pilihan
    LEFT JOIN pmb_gelombang g ON g.id_gelombang = p.id_gelombang
    ORDER BY p.id_pendaftar DESC
    LIMIT 25
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars($r['nomor_pendaftaran'] ?? '-'),
            htmlspecialchars($r['nama_pendaftar'] ?? '-'),
            htmlspecialchars($r['nama_prodi'] ?? '-'),
            htmlspecialchars($r['nama_gelombang'] ?? '-'),
            internal_badge($r['status_pendaftaran'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('PMB', 'Monitoring awal pendaftar, gelombang, dan status verifikasi PMB.', $cards, ['No. Pendaftaran', 'Nama', 'Prodi Pilihan', 'Gelombang', 'Status'], $rows, 'Belum ada pendaftar PMB.');
require_once "../../includes/footer.php";
