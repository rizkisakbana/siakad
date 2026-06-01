<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$cards = [
    ['label' => 'Total Nilai', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM nilai"))],
    ['label' => 'Publish', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM nilai WHERE status_publish = 'publish'"))],
    ['label' => 'KHS', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM khs"))],
    ['label' => 'AKM', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM aktivitas_kuliah_mahasiswa"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT n.*, m.nim, m.nama_mahasiswa, mk.kode_mk, mk.nama_mk
    FROM nilai n
    JOIN krs_detail kd ON kd.id_krs_detail = n.id_krs_detail
    JOIN krs kr ON kr.id_krs = kd.id_krs
    JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
    LEFT JOIN jadwal_kuliah j ON j.id_jadwal = kd.id_jadwal
    LEFT JOIN mata_kuliah mk ON mk.id_mk = COALESCE(n.id_matkul, j.id_mk)
    ORDER BY n.id_nilai DESC
    LIMIT 50
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars(($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-')),
            htmlspecialchars(($r['kode_mk'] ?? '-') . ' - ' . ($r['nama_mk'] ?? '-')),
            htmlspecialchars((string)($r['nilai_akhir'] ?? 0)),
            htmlspecialchars($r['nilai_huruf'] ?? '-'),
            htmlspecialchars((string)($r['bobot'] ?? 0)),
            internal_badge($r['status_publish'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
render_internal_page('Laporan Nilai', 'Rekap nilai perkuliahan, KHS, dan AKM.', $cards, ['Mahasiswa', 'Mata Kuliah', 'Nilai Akhir', 'Huruf', 'Bobot', 'Status'], $rows, 'Belum ada data nilai.');
require_once "../../includes/footer.php";
