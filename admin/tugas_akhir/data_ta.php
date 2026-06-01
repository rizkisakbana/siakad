<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

$page_title = "Data Tugas Akhir";
$page_subtitle = "Monitoring pengajuan, pembimbing, penguji, seminar, sidang, dan nilai tugas akhir.";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total TA', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tugas_akhir"))],
    ['label' => 'Diajukan', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tugas_akhir WHERE status_ta = 'diajukan'"))],
    ['label' => 'Sidang', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tugas_akhir WHERE status_ta = 'sidang'"))],
    ['label' => 'Lulus', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM tugas_akhir WHERE status_ta = 'lulus'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT ta.*, m.nim, m.nama_mahasiswa, p.nama_prodi
    FROM tugas_akhir ta
    JOIN mahasiswa m ON m.id_mahasiswa = ta.id_mahasiswa
    LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
    ORDER BY ta.id_ta DESC
    LIMIT 25
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
            htmlspecialchars($r['nama_prodi'] ?? '-'),
            htmlspecialchars($r['judul'] ?? '-'),
            htmlspecialchars($r['tanggal_pengajuan'] ?? '-'),
            internal_badge($r['status_ta'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('Tugas Akhir', 'Monitoring pengajuan, pembimbing, penguji, seminar, sidang, dan nilai tugas akhir.', $cards, ['Mahasiswa', 'Prodi', 'Judul', 'Tanggal Pengajuan', 'Status'], $rows, 'Belum ada pengajuan tugas akhir.');
require_once "../../includes/footer.php";
