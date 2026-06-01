<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_keuangan']);

$page_title = "Pembayaran Mahasiswa";
$page_subtitle = "Monitoring pembayaran mahasiswa dan status verifikasi.";

$cards = [
    ['label' => 'Total Pembayaran', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa"))],
    ['label' => 'Pending', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'pending'"))],
    ['label' => 'Valid', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'valid'"))],
    ['label' => 'Nominal Valid', 'value' => rupiah_internal(internal_sum($conn, "SELECT COALESCE(SUM(jumlah_bayar),0) total FROM pembayaran_mahasiswa WHERE status_pembayaran = 'valid'"))],
];

$rows = [];
$data_pembayaran = internal_fetch_all($conn, "
    SELECT p.*, t.nomor_tagihan, m.nim, m.nama_mahasiswa, u.nama_lengkap AS nama_verifikator
    FROM pembayaran_mahasiswa p
    JOIN tagihan_mahasiswa t ON t.id_tagihan = p.id_tagihan
    JOIN mahasiswa m ON m.id_mahasiswa = p.id_mahasiswa
    LEFT JOIN users u ON u.id_user = p.diverifikasi_oleh
    ORDER BY p.tanggal_bayar DESC, p.id_pembayaran DESC
    LIMIT 50
");

foreach ($data_pembayaran as $r) {
    $rows[] = [
        htmlspecialchars($r['nomor_pembayaran'] ?? '-'),
        htmlspecialchars($r['nomor_tagihan'] ?? '-'),
        htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
        htmlspecialchars(!empty($r['tanggal_bayar']) ? tanggal_jam_indonesia($r['tanggal_bayar']) : '-'),
        rupiah_internal($r['jumlah_bayar'] ?? 0),
        htmlspecialchars($r['metode_bayar'] ?? '-'),
        internal_badge($r['status_pembayaran'] ?? '-'),
        htmlspecialchars($r['nama_verifikator'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('Pembayaran Mahasiswa', 'Monitoring pembayaran mahasiswa dan status verifikasi.', $cards, ['No. Bayar', 'No. Tagihan', 'Mahasiswa', 'Tanggal', 'Jumlah', 'Metode', 'Status', 'Verifikator'], $rows, 'Belum ada pembayaran mahasiswa.');
require_once __DIR__ . "/../../includes/footer.php";
