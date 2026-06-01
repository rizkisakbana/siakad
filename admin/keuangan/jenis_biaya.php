<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_keuangan']);

$page_title = "Jenis Biaya";
$page_subtitle = "Monitoring master komponen biaya mahasiswa.";

$cards = [
    ['label' => 'Total Jenis Biaya', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM jenis_biaya"))],
    ['label' => 'Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM jenis_biaya WHERE status = 'aktif'"))],
    ['label' => 'Wajib', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM jenis_biaya WHERE wajib = 'ya'"))],
    ['label' => 'Nominal Default', 'value' => rupiah_internal(internal_sum($conn, "SELECT COALESCE(SUM(nominal_default),0) total FROM jenis_biaya WHERE status = 'aktif'"))],
];

$rows = [];
$data_biaya = internal_fetch_all($conn, "
    SELECT *
    FROM jenis_biaya
    ORDER BY FIELD(status, 'aktif', 'nonaktif'), kategori ASC, kode_biaya ASC
");

foreach ($data_biaya as $r) {
    $rows[] = [
        htmlspecialchars($r['kode_biaya'] ?? '-'),
        htmlspecialchars($r['nama_biaya'] ?? '-'),
        htmlspecialchars($r['kategori'] ?? '-'),
        rupiah_internal($r['nominal_default'] ?? 0),
        internal_badge($r['wajib'] ?? '-'),
        internal_badge($r['status'] ?? '-'),
    ];
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
show_alert();
render_internal_page('Jenis Biaya', 'Monitoring master komponen biaya mahasiswa.', $cards, ['Kode', 'Nama Biaya', 'Kategori', 'Nominal Default', 'Wajib', 'Status'], $rows, 'Belum ada jenis biaya.');
require_once __DIR__ . "/../../includes/footer.php";
