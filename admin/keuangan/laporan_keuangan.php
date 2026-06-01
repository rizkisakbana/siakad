<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_keuangan']);

$page_title = "Laporan Keuangan";
$page_subtitle = "Ringkasan tagihan, pembayaran valid, dan piutang mahasiswa.";

$total_tagihan = internal_sum($conn, "SELECT COALESCE(SUM(total_tagihan),0) total FROM tagihan_mahasiswa WHERE status_tagihan <> 'dibatalkan'");
$total_valid = internal_sum($conn, "SELECT COALESCE(SUM(jumlah_bayar),0) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'valid'");
$total_piutang = max(0, $total_tagihan - $total_valid);

$cards = [
    ['label' => 'Total Tagihan', 'value' => rupiah_internal($total_tagihan)],
    ['label' => 'Pembayaran Valid', 'value' => rupiah_internal($total_valid)],
    ['label' => 'Estimasi Piutang', 'value' => rupiah_internal($total_piutang)],
    ['label' => 'Pembayaran Pending', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'pending'"))],
];

$rows = [];
$data_laporan = internal_fetch_all($conn, "
    SELECT
        jb.kode_biaya,
        jb.nama_biaya,
        COUNT(DISTINCT t.id_tagihan) AS total_tagihan_data,
        COALESCE(SUM(t.total_tagihan), 0) AS nominal_tagihan,
        COALESCE(SUM(t.total_bayar), 0) AS nominal_bayar,
        SUM(CASE WHEN t.status_tagihan = 'lunas' THEN 1 ELSE 0 END) AS total_lunas,
        SUM(CASE WHEN t.status_tagihan IN ('belum_bayar', 'sebagian') THEN 1 ELSE 0 END) AS total_belum_lunas
    FROM jenis_biaya jb
    LEFT JOIN tagihan_mahasiswa t ON t.id_jenis_biaya = jb.id_jenis_biaya
    GROUP BY jb.id_jenis_biaya, jb.kode_biaya, jb.nama_biaya
    ORDER BY jb.kode_biaya ASC
");

foreach ($data_laporan as $r) {
    $rows[] = [
        htmlspecialchars($r['kode_biaya'] ?? '-'),
        htmlspecialchars($r['nama_biaya'] ?? '-'),
        number_format((int)($r['total_tagihan_data'] ?? 0)),
        rupiah_internal($r['nominal_tagihan'] ?? 0),
        rupiah_internal($r['nominal_bayar'] ?? 0),
        number_format((int)($r['total_lunas'] ?? 0)),
        number_format((int)($r['total_belum_lunas'] ?? 0)),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('Laporan Keuangan', 'Ringkasan tagihan, pembayaran valid, dan piutang mahasiswa.', $cards, ['Kode', 'Jenis Biaya', 'Tagihan', 'Nominal Tagihan', 'Terbayar', 'Lunas', 'Belum Lunas'], $rows, 'Belum ada data laporan keuangan.');
require_once __DIR__ . "/../../includes/footer.php";
