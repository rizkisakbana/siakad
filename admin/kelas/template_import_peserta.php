<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/kelas_helper.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

/** @var mysqli $conn */

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    die("ID kelas tidak valid.");
}


$kelas = kelas_query_one($conn, "
    SELECT 
        kelas.*,
        prodi.nama_prodi,
        prodi.kode_prodi,
        prodi.jenjang,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    WHERE kelas.id_kelas = '$id_kelas'
    LIMIT 1
");

if (!$kelas) {
    die("Data kelas tidak ditemukan.");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Import Peserta Kelas");

$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'AKADEMI TEKNIK INFORMATIKA TUNAS BANGSA JAKARTA');

$sheet->mergeCells('A2:C2');
$sheet->setCellValue('A2', 'TEMPLATE IMPORT PESERTA KELAS');

$sheet->mergeCells('A3:C3');
$sheet->setCellValue('A3', 'Kelas: ' . $kelas['nama_kelas'] . ' | Prodi: ' . $kelas['nama_prodi'] . ' | Tahun Akademik: ' . $kelas['tahun'] . ' - ' . $kelas['semester_tahun']);

$sheet->setCellValue('A5', 'nim');
$sheet->setCellValue('B5', 'nama_mahasiswa_opsional');
$sheet->setCellValue('C5', 'keterangan');

$sheet->setCellValue('A6', '202610001');
$sheet->setCellValue('B6', 'Contoh Nama Mahasiswa');
$sheet->setCellValue('C6', 'Kolom wajib hanya NIM');

$sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A3')->getFont()->setSize(10);

$sheet->getStyle('A5:C5')->getFont()->setBold(true);
$sheet->getStyle('A5:C5')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF1E3A8A');

$sheet->getStyle('A5:C5')->getFont()->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A5:C6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A5:C6')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(35);
$sheet->getColumnDimension('C')->setWidth(35);

$sheet->freezePane('A6');

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Mengunduh template import peserta kelas: " . $kelas['nama_kelas'],
    "Kelas"
);

$filename = "template_import_peserta_kelas_" . $id_kelas . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
