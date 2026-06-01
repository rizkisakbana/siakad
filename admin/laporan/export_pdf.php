<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$jenis = $_GET['jenis'] ?? 'mahasiswa';
$allowed = ['mahasiswa', 'dosen', 'krs', 'nilai', 'presensi'];
if (!in_array($jenis, $allowed, true)) {
    $jenis = 'mahasiswa';
}

$laporan = [
    'mahasiswa' => [
        'title' => 'Laporan Mahasiswa',
        'columns' => ['NIM', 'Nama', 'Prodi', 'Angkatan', 'Semester', 'Status'],
        'sql' => "SELECT m.nim, m.nama_mahasiswa, p.nama_prodi, m.angkatan, m.semester, m.status_mahasiswa FROM mahasiswa m LEFT JOIN prodi p ON p.id_prodi = m.id_prodi ORDER BY m.angkatan DESC, m.nim ASC LIMIT 1000",
        'map' => fn($r) => [$r['nim'] ?? '-', $r['nama_mahasiswa'] ?? '-', $r['nama_prodi'] ?? '-', $r['angkatan'] ?? '-', $r['semester'] ?? '-', $r['status_mahasiswa'] ?? '-'],
    ],
    'dosen' => [
        'title' => 'Laporan Dosen',
        'columns' => ['NIDN/NIDK', 'Nama', 'Prodi', 'Jenis', 'Status'],
        'sql' => "SELECT d.nidn, d.nidk, d.nama_dosen, d.status_dosen, d.status, p.nama_prodi FROM dosen d LEFT JOIN prodi p ON p.id_prodi = d.id_prodi ORDER BY d.nama_dosen ASC LIMIT 1000",
        'map' => fn($r) => [$r['nidn'] ?: ($r['nidk'] ?? '-'), $r['nama_dosen'] ?? '-', $r['nama_prodi'] ?? '-', $r['status_dosen'] ?? '-', $r['status'] ?? '-'],
    ],
    'krs' => [
        'title' => 'Laporan KRS',
        'columns' => ['Mahasiswa', 'Periode', 'Tanggal KRS', 'SKS', 'Status'],
        'sql' => "SELECT kr.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester FROM krs kr JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa LEFT JOIN tahun_akademik ta ON ta.id_tahun = kr.id_tahun ORDER BY kr.id_krs DESC LIMIT 1000",
        'map' => fn($r) => [($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-', $r['tanggal_krs'] ?? '-', $r['total_sks'] ?? '0', $r['status_krs'] ?? '-'],
    ],
    'nilai' => [
        'title' => 'Laporan Nilai',
        'columns' => ['Mahasiswa', 'Mata Kuliah', 'Nilai Akhir', 'Huruf', 'Bobot', 'Status'],
        'sql' => "SELECT n.*, m.nim, m.nama_mahasiswa, mk.kode_mk, mk.nama_mk FROM nilai n JOIN krs_detail kd ON kd.id_krs_detail = n.id_krs_detail JOIN krs kr ON kr.id_krs = kd.id_krs JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa LEFT JOIN jadwal_kuliah j ON j.id_jadwal = kd.id_jadwal LEFT JOIN mata_kuliah mk ON mk.id_mk = COALESCE(n.id_matkul, j.id_mk) ORDER BY n.id_nilai DESC LIMIT 1000",
        'map' => fn($r) => [($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), ($r['kode_mk'] ?? '-') . ' - ' . ($r['nama_mk'] ?? '-'), $r['nilai_akhir'] ?? '0', $r['nilai_huruf'] ?? '-', $r['bobot'] ?? '0', $r['status_publish'] ?? '-'],
    ],
    'presensi' => [
        'title' => 'Laporan Presensi',
        'columns' => ['Tanggal', 'Pertemuan', 'Mahasiswa', 'Status', 'Waktu'],
        'sql' => "SELECT pm.*, pk.tanggal_presensi, pk.pertemuan_ke, m.nim, m.nama_mahasiswa FROM presensi_mahasiswa pm JOIN presensi_kuliah pk ON pk.id_presensi_kuliah = pm.id_presensi_kuliah JOIN mahasiswa m ON m.id_mahasiswa = pm.id_mahasiswa ORDER BY pk.tanggal_presensi DESC, pm.id_presensi_mahasiswa DESC LIMIT 1000",
        'map' => fn($r) => [$r['tanggal_presensi'] ?? '-', 'Pertemuan ' . ($r['pertemuan_ke'] ?? '-'), ($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), $r['status_presensi'] ?? '-', $r['waktu_presensi'] ?? '-'],
    ],
];

$config = $laporan[$jenis];
$data = internal_fetch_all($conn, $config['sql']);

$html = '<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:10px;color:#111827}
h1{font-size:18px;margin:0 0 4px}
p{margin:0 0 14px;color:#64748b}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #cbd5e1;padding:6px;text-align:left;vertical-align:top}
th{background:#e2e8f0}
</style></head><body>';
$html .= '<h1>' . htmlspecialchars($config['title']) . '</h1>';
$html .= '<p>Dicetak pada ' . htmlspecialchars(date('Y-m-d H:i:s')) . '</p>';
$html .= '<table><thead><tr>';
foreach ($config['columns'] as $column) {
    $html .= '<th>' . htmlspecialchars($column) . '</th>';
}
$html .= '</tr></thead><tbody>';

if (!empty($data)) {
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($config['map']($row) as $cell) {
            $html .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
        }
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="' . count($config['columns']) . '">Data belum tersedia.</td></tr>';
}

$html .= '</tbody></table></body></html>';

simpan_log($conn, $_SESSION['id_user'], 'Export PDF ' . $config['title'], 'Laporan');

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', count($config['columns']) > 5 ? 'landscape' : 'portrait');
$dompdf->render();
$dompdf->stream(strtolower(str_replace(' ', '_', $config['title'])) . '_' . date('Ymd_His') . '.pdf', ['Attachment' => true]);
exit;
