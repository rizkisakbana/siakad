<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Presensi Mahasiswa";
$page_subtitle = "Monitoring sesi perkuliahan dan kehadiran mahasiswa.";

$cards = [
    ['label' => 'Sesi Presensi', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_kuliah"))],
    ['label' => 'Detail Presensi', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_mahasiswa"))],
    ['label' => 'Selesai', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_kuliah WHERE status_pertemuan = 'selesai'"))],
    ['label' => 'Batal', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM presensi_kuliah WHERE status_pertemuan = 'batal'"))],
];

$rows = [];
$q = mysqli_query($conn, "
    SELECT pk.*, mk.nama_mk, k.nama_kelas, d.nama_dosen
    FROM presensi_kuliah pk
    LEFT JOIN jadwal_kuliah j ON j.id_jadwal = pk.id_jadwal
    LEFT JOIN mata_kuliah mk ON mk.id_mk = j.id_mk
    LEFT JOIN kelas k ON k.id_kelas = j.id_kelas
    LEFT JOIN dosen d ON d.id_dosen = j.id_dosen
    ORDER BY pk.tanggal_presensi DESC, pk.id_presensi_kuliah DESC
    LIMIT 25
");

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = [
            htmlspecialchars($r['tanggal_presensi'] ?? '-'),
            htmlspecialchars('Pertemuan ' . ($r['pertemuan_ke'] ?? '-')),
            htmlspecialchars($r['nama_mk'] ?? '-'),
            htmlspecialchars($r['nama_kelas'] ?? '-'),
            htmlspecialchars($r['nama_dosen'] ?? '-'),
            internal_badge($r['status_pertemuan'] ?? '-'),
        ];
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
show_alert();
render_internal_page('Presensi Mahasiswa', 'Monitoring sesi perkuliahan dan kehadiran mahasiswa.', $cards, ['Tanggal', 'Pertemuan', 'Mata Kuliah', 'Kelas', 'Dosen', 'Status'], $rows, 'Belum ada sesi presensi.');
require_once "../../includes/footer.php";
