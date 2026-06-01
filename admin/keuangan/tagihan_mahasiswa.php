<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_keuangan']);

$page_title = "Tagihan Mahasiswa";
$page_subtitle = "Monitoring tagihan, pembayaran, dan status pelunasan mahasiswa.";

$cards = [
    ['label' => 'Total Tagihan', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tagihan_mahasiswa"))],
    ['label' => 'Belum Bayar', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tagihan_mahasiswa WHERE status_tagihan = 'belum_bayar'"))],
    ['label' => 'Lunas', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tagihan_mahasiswa WHERE status_tagihan = 'lunas'"))],
    ['label' => 'Nominal Tagihan', 'value' => rupiah_internal(internal_sum($conn, "SELECT COALESCE(SUM(total_tagihan),0) total FROM tagihan_mahasiswa"))],
];

$rows = [];
$data_tagihan = internal_fetch_all($conn, "
    SELECT t.*, m.nim, m.nama_mahasiswa, jb.nama_biaya, ta.tahun, ta.semester
    FROM tagihan_mahasiswa t
    JOIN mahasiswa m ON m.id_mahasiswa = t.id_mahasiswa
    JOIN jenis_biaya jb ON jb.id_jenis_biaya = t.id_jenis_biaya
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = t.id_tahun
    ORDER BY t.id_tagihan DESC
    LIMIT 25
");

foreach ($data_tagihan as $r) {
    $rows[] = [
        htmlspecialchars($r['nomor_tagihan'] ?? '-'),
        htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
        htmlspecialchars($r['nama_biaya'] ?? '-'),
        htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-'),
        rupiah_internal($r['total_tagihan'] ?? 0),
        internal_badge($r['status_tagihan'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('Tagihan Mahasiswa', 'Monitoring tagihan, pembayaran, dan status pelunasan mahasiswa.', $cards, ['No. Tagihan', 'Mahasiswa', 'Jenis Biaya', 'Periode', 'Total', 'Status'], $rows, 'Belum ada tagihan mahasiswa.');
require_once __DIR__ . "/../../includes/footer.php";
