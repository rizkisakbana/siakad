<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total KRS', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM krs"))],
    ['label' => 'Disetujui', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM krs WHERE status_krs = 'disetujui'"))],
    ['label' => 'Detail KRS', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM krs_detail"))],
    ['label' => 'Peserta Kelas', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT kr.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester
    FROM krs kr
    JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = kr.id_tahun
    ORDER BY kr.id_krs DESC
    LIMIT 50
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
            htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-'),
            htmlspecialchars($r['tanggal_krs'] ?? '-'),
            htmlspecialchars((string)($r['total_sks'] ?? 0)),
            internal_badge($r['status_krs'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_internal_page('Laporan KRS', 'Rekap KRS mahasiswa dan status persetujuan akademik.', $cards, ['Mahasiswa', 'Periode', 'Tanggal KRS', 'SKS', 'Status'], $rows, 'Belum ada data KRS.');
require_once "../../includes/footer.php";
