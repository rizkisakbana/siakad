<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/dosen_helper.php";

/** @var mysqli $conn */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$data_dosen = dosen_fetch_all($conn, "
    SELECT 
        dosen.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        users.username,
        users.status AS status_user
    FROM dosen
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    LEFT JOIN users ON dosen.id_user = users.id_user
    ORDER BY prodi.nama_prodi ASC, dosen.nama_dosen ASC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Data Dosen");

$tanggal_export = tanggal_indonesia(date('Y-m-d')) . " " . date('H:i') . " WIB";

/*
|--------------------------------------------------------------------------
| HEADER LAPORAN
|--------------------------------------------------------------------------
*/

$sheet->mergeCells('A1:U1');
$sheet->setCellValue('A1', 'AKADEMI TEKNIK INFORMATIKA TUNAS BANGSA JAKARTA');

$sheet->mergeCells('A2:U2');
$sheet->setCellValue('A2', 'LAPORAN DATA DOSEN');

$sheet->mergeCells('A3:U3');
$sheet->setCellValue('A3', 'Tanggal Export : ' . $tanggal_export);

$headers = [
    'No',
    'ID Dosen',
    'ID User',
    'NIDN',
    'NIP',
    'Gelar Depan',
    'Nama Dosen',
    'Gelar Belakang',
    'Jenis Kelamin',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Email',
    'No HP',
    'Alamat',
    'Status Dosen',
    'Status Akun',
    'Username',
    'ID Prodi',
    'Kode Prodi',
    'Nama Prodi',
    'Jenjang'
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '5', $header);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

$rowNumber = 6;
$no = 1;

foreach ($data_dosen as $row) {
    $jenis_kelamin = '-';

    if (($row['jenis_kelamin'] ?? '') == 'L') {
        $jenis_kelamin = 'Laki-laki';
    } elseif (($row['jenis_kelamin'] ?? '') == 'P') {
        $jenis_kelamin = 'Perempuan';
    }

    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, $row['id_dosen']);
    $sheet->setCellValue('C' . $rowNumber, $row['id_user']);
    $sheet->setCellValueExplicit('D' . $rowNumber, $row['nidn'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('E' . $rowNumber, $row['nip'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('F' . $rowNumber, $row['gelar_depan'] ?? '');
    $sheet->setCellValue('G' . $rowNumber, $row['nama_dosen'] ?? '');
    $sheet->setCellValue('H' . $rowNumber, $row['gelar_belakang'] ?? '');
    $sheet->setCellValue('I' . $rowNumber, $jenis_kelamin);
    $sheet->setCellValue('J' . $rowNumber, $row['tempat_lahir'] ?? '');
    $sheet->setCellValue('K' . $rowNumber, !empty($row['tanggal_lahir']) ? tanggal_indonesia($row['tanggal_lahir']) : '');
    $sheet->setCellValue('L' . $rowNumber, $row['email'] ?? '');
    $sheet->setCellValueExplicit('M' . $rowNumber, $row['no_hp'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('N' . $rowNumber, $row['alamat'] ?? '');
    $sheet->setCellValue('O' . $rowNumber, $row['status_dosen'] ?? '');
    $sheet->setCellValue('P' . $rowNumber, $row['status'] ?? '');
    $sheet->setCellValue('Q' . $rowNumber, $row['username'] ?? '');
    $sheet->setCellValue('R' . $rowNumber, $row['id_prodi'] ?? '');
    $sheet->setCellValue('S' . $rowNumber, $row['kode_prodi'] ?? '');
    $sheet->setCellValue('T' . $rowNumber, $row['nama_prodi'] ?? '');
    $sheet->setCellValue('U' . $rowNumber, $row['jenjang'] ?? '');

    $rowNumber++;
}

/*
|--------------------------------------------------------------------------
| STYLE HEADER
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A3')->getFont()->setSize(10);

$sheet->getStyle('A1:A3')->getAlignment()
    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

$sheet->getRowDimension(1)->setRowHeight(28);
$sheet->getRowDimension(2)->setRowHeight(22);
$sheet->getRowDimension(3)->setRowHeight(18);

$lastRow = $rowNumber - 1;

$sheet->getStyle('A5:U5')->getFill()
    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF1E3A8A');

$sheet->getStyle('A5:U5')->getFont()->getColor()->setARGB('FFFFFFFF');

$sheet->getStyle('A1:U' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

$sheet->getStyle('A5:U5' . $lastRow)->getAlignment()
    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

$sheet->getStyle('A5:U5')->getAlignment()
    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

$sheet->freezePane('A6');
$sheet->setAutoFilter('A5:U5');

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Export data dosen ke Excel",
    "Dosen"
);

$filename = "data_dosen_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
