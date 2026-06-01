<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Import Mahasiswa");

$headers = [
    'id_prodi',
    'id_kelas',
    'nim',
    'nama_mahasiswa',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'id_agama_feeder',
    'nik',
    'nisn',
    'npwp',
    'id_negara_feeder',
    'alamat',
    'id_wilayah_feeder',
    'kode_pos',
    'email',
    'no_hp',
    'id_alat_transportasi_feeder',
    'asal_sekolah',
    'tahun_lulus',
    'nama_ayah',
    'nama_ibu',
    'id_pekerjaan_ayah_feeder',
    'id_pekerjaan_ibu_feeder',
    'id_penghasilan_ayah_feeder',
    'id_penghasilan_ibu_feeder',
    'id_status_mahasiswa_feeder',
    'angkatan',
    'semester',
    'id_jalur_masuk_feeder',
    'id_jenis_pendaftaran_feeder',
    'tanggal_masuk',
    'tanggal_keluar',
    'username',
    'password'
];

$lastCol = 'AI';

$sheet->mergeCells("A1:{$lastCol}1");
$sheet->setCellValue('A1', 'AKADEMI TEKNIK INFORMATIKA TUNAS BANGSA JAKARTA');

$sheet->mergeCells("A2:{$lastCol}2");
$sheet->setCellValue('A2', 'TEMPLATE IMPORT DATA MAHASISWA - TERINTEGRASI NEOFEEDER/PDDIKTI');

$sheet->mergeCells("A3:{$lastCol}3");
$sheet->setCellValue('A3', 'Isi data mahasiswa mulai dari baris ke-6. Jangan menambahkan catatan di bawah tabel agar proses import tidak membaca baris kosong/catatan.');

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '5', $header);
    $col++;
}

$contoh = [
    '1',
    '0',
    '202610001',
    'Ahmad Rizki Saputra',
    'L',
    'Jakarta',
    '2006-05-24',
    '1',
    '317xxxxxxxxxxxxx',
    '006xxxxxxxxx',
    '',
    'ID',
    'Jl. Contoh Alamat Mahasiswa',
    '016407',
    '13430',
    'ahmad@email.com',
    '6281234567890',
    '',
    'SMK Contoh Jakarta',
    '2025',
    'Bapak Ahmad',
    'Ibu Siti',
    '',
    '',
    '',
    '',
    'A',
    '2026',
    '1',
    '1',
    '1',
    '2026-09-01',
    '',
    '202610001',
    '123456'
];

$col = 'A';
foreach ($contoh as $value) {
    $sheet->setCellValueExplicit($col . '6', $value, DataType::TYPE_STRING);
    $col++;
}

$sheet->getStyle("A1:A3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A3')->getFont()->setSize(10);

$sheet->getStyle("A5:{$lastCol}5")->getFont()->setBold(true);
$sheet->getStyle("A5:{$lastCol}5")->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF1E3A8A');
$sheet->getStyle("A5:{$lastCol}5")->getFont()->getColor()->setARGB('FFFFFFFF');

$sheet->getStyle("A5:{$lastCol}6")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet->getStyle("A5:{$lastCol}6")->getAlignment()
    ->setVertical(Alignment::VERTICAL_TOP);

foreach (range('A', 'Z') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI'] as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

$sheet->freezePane('A6');
$sheet->setAutoFilter("A5:{$lastCol}5");

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Mengunduh template import data mahasiswa NeoFeeder",
    "Mahasiswa"
);

$filename = "template_import_mahasiswa_neofeeder.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
