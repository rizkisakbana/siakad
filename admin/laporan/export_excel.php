<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        'sql' => "
            SELECT m.nim, m.nama_mahasiswa, p.nama_prodi, m.angkatan, m.semester, m.status_mahasiswa
            FROM mahasiswa m
            LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
            ORDER BY m.angkatan DESC, m.nim ASC
            LIMIT 1000
        ",
        'map' => fn($r) => [$r['nim'] ?? '-', $r['nama_mahasiswa'] ?? '-', $r['nama_prodi'] ?? '-', $r['angkatan'] ?? '-', $r['semester'] ?? '-', $r['status_mahasiswa'] ?? '-'],
    ],
    'dosen' => [
        'title' => 'Laporan Dosen',
        'columns' => ['NIDN/NIDK', 'Nama', 'Prodi', 'Jenis', 'Status'],
        'sql' => "
            SELECT d.nidn, d.nidk, d.nama_dosen, d.status_dosen, d.status, p.nama_prodi
            FROM dosen d
            LEFT JOIN prodi p ON p.id_prodi = d.id_prodi
            ORDER BY d.nama_dosen ASC
            LIMIT 1000
        ",
        'map' => fn($r) => [$r['nidn'] ?: ($r['nidk'] ?? '-'), $r['nama_dosen'] ?? '-', $r['nama_prodi'] ?? '-', $r['status_dosen'] ?? '-', $r['status'] ?? '-'],
    ],
    'krs' => [
        'title' => 'Laporan KRS',
        'columns' => ['Mahasiswa', 'Periode', 'Tanggal KRS', 'SKS', 'Status'],
        'sql' => "
            SELECT kr.*, m.nim, m.nama_mahasiswa, ta.tahun, ta.semester
            FROM krs kr
            JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
            LEFT JOIN tahun_akademik ta ON ta.id_tahun = kr.id_tahun
            ORDER BY kr.id_krs DESC
            LIMIT 1000
        ",
        'map' => fn($r) => [($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: '-', $r['tanggal_krs'] ?? '-', $r['total_sks'] ?? '0', $r['status_krs'] ?? '-'],
    ],
    'nilai' => [
        'title' => 'Laporan Nilai',
        'columns' => ['Mahasiswa', 'Mata Kuliah', 'Nilai Akhir', 'Huruf', 'Bobot', 'Status'],
        'sql' => "
            SELECT n.*, m.nim, m.nama_mahasiswa, mk.kode_mk, mk.nama_mk
            FROM nilai n
            JOIN krs_detail kd ON kd.id_krs_detail = n.id_krs_detail
            JOIN krs kr ON kr.id_krs = kd.id_krs
            JOIN mahasiswa m ON m.id_mahasiswa = kr.id_mahasiswa
            LEFT JOIN jadwal_kuliah j ON j.id_jadwal = kd.id_jadwal
            LEFT JOIN mata_kuliah mk ON mk.id_mk = COALESCE(n.id_matkul, j.id_mk)
            ORDER BY n.id_nilai DESC
            LIMIT 1000
        ",
        'map' => fn($r) => [($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), ($r['kode_mk'] ?? '-') . ' - ' . ($r['nama_mk'] ?? '-'), $r['nilai_akhir'] ?? '0', $r['nilai_huruf'] ?? '-', $r['bobot'] ?? '0', $r['status_publish'] ?? '-'],
    ],
    'presensi' => [
        'title' => 'Laporan Presensi',
        'columns' => ['Tanggal', 'Pertemuan', 'Mahasiswa', 'Status', 'Waktu'],
        'sql' => "
            SELECT pm.*, pk.tanggal_presensi, pk.pertemuan_ke, m.nim, m.nama_mahasiswa
            FROM presensi_mahasiswa pm
            JOIN presensi_kuliah pk ON pk.id_presensi_kuliah = pm.id_presensi_kuliah
            JOIN mahasiswa m ON m.id_mahasiswa = pm.id_mahasiswa
            ORDER BY pk.tanggal_presensi DESC, pm.id_presensi_mahasiswa DESC
            LIMIT 1000
        ",
        'map' => fn($r) => [$r['tanggal_presensi'] ?? '-', 'Pertemuan ' . ($r['pertemuan_ke'] ?? '-'), ($r['nim'] ?? '-') . ' - ' . ($r['nama_mahasiswa'] ?? '-'), $r['status_presensi'] ?? '-', $r['waktu_presensi'] ?? '-'],
    ],
];

$config = $laporan[$jenis];
$data = internal_fetch_all($conn, $config['sql']);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(substr($config['title'], 0, 31));
$sheet->setCellValue('A1', $config['title']);
$sheet->fromArray($config['columns'], null, 'A3');

$rowNumber = 4;
foreach ($data as $row) {
    $sheet->fromArray($config['map']($row), null, 'A' . $rowNumber);
    $rowNumber++;
}

foreach (range('A', chr(64 + count($config['columns']))) as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

simpan_log($conn, $_SESSION['id_user'], 'Export Excel ' . $config['title'], 'Laporan');

$filename = strtolower(str_replace(' ', '_', $config['title'])) . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
