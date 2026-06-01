<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Template Import Dosen');

$headers = [
    'id_prodi',
    'nidn',
    'nip',
    'nama_dosen',
    'gelar_depan',
    'gelar_belakang',
    'jenis_kelamin',
    'tempat_lahir',
    'tanggal_lahir',
    'email',
    'no_hp',
    'alamat',
    'status_dosen',
    'username',
    'password'
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

$sheet->setCellValue('A2', '1');
$sheet->setCellValue('B2', '0312345678');
$sheet->setCellValue('C2', '1987654321');
$sheet->setCellValue('D2', 'Budi Santoso');
$sheet->setCellValue('E2', 'Dr.');
$sheet->setCellValue('F2', 'S.Kom., M.Kom.');
$sheet->setCellValue('G2', 'L');
$sheet->setCellValue('H2', 'Jakarta');
$sheet->setCellValue('I2', '1985-05-24');
$sheet->setCellValue('J2', 'budi@email.com');
$sheet->setCellValue('K2', '6281234567890');
$sheet->setCellValue('L2', 'Jakarta');
$sheet->setCellValue('M2', 'tetap');
$sheet->setCellValue('N2', 'budi.santoso');
$sheet->setCellValue('O2', '123456');

$sheet->getStyle('A1:O1')->getFont()->setBold(true);

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Mengunduh template import data dosen",
    "Dosen"
);

$filename = "template_import_dosen.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>