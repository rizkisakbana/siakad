<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$query = mysqli_query($conn, "
    SELECT *
    FROM ruangan
    ORDER BY kode_ruangan ASC, nama_ruangan ASC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Data Ruangan");

$tanggal_export = tanggal_indonesia(date('Y-m-d')) . " " . date('H:i') . " WIB";

$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', 'AKADEMI TEKNIK INFORMATIKA TUNAS BANGSA JAKARTA');

$sheet->mergeCells('A2:K2');
$sheet->setCellValue('A2', 'LAPORAN DATA RUANGAN');

$sheet->mergeCells('A3:K3');
$sheet->setCellValue('A3', 'Tanggal Export : ' . $tanggal_export);

$headers = [
    'No',
    'ID Ruangan',
    'Kode Ruangan',
    'Nama Ruangan',
    'Gedung',
    'Lantai',
    'Kapasitas',
    'Jenis Ruangan',
    'Fasilitas',
    'Status',
    'Tanggal Dibuat',
    'Terakhir Diperbarui'
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '5', $header);
    $col++;
}

$rowNumber = 6;
$no = 1;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $sheet->setCellValue('A' . $rowNumber, $no++);
        $sheet->setCellValue('B' . $rowNumber, $row['id_ruangan']);
        $sheet->setCellValue('C' . $rowNumber, $row['kode_ruangan'] ?? '');
        $sheet->setCellValue('D' . $rowNumber, $row['nama_ruangan'] ?? '');
        $sheet->setCellValue('E' . $rowNumber, $row['gedung'] ?? '');
        $sheet->setCellValue('F' . $rowNumber, $row['lantai'] ?? '');
        $sheet->setCellValue('G' . $rowNumber, $row['kapasitas'] ?? 0);
        $sheet->setCellValue('H' . $rowNumber, $row['jenis_ruangan'] ?? '');
        $sheet->setCellValue('I' . $rowNumber, $row['fasilitas'] ?? '');
        $sheet->setCellValue('J' . $rowNumber, $row['status'] ?? '');
        $sheet->setCellValue('K' . $rowNumber, !empty($row['created_at']) ? tanggal_jam_indonesia($row['created_at']) : '');
        $sheet->setCellValue('L' . $rowNumber, !empty($row['updated_at']) ? tanggal_jam_indonesia($row['updated_at']) : '');

        $rowNumber++;
    }
}

$lastRow = $rowNumber - 1;

$sheet->mergeCells('A1:L1');
$sheet->mergeCells('A2:L2');
$sheet->mergeCells('A3:L3');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A3')->getFont()->setSize(10);

$sheet->getStyle('A1:A3')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A5:L5')->getFont()->setBold(true);
$sheet->getStyle('A5:L5')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF1E3A8A');

$sheet->getStyle('A5:L5')->getFont()->getColor()->setARGB('FFFFFFFF');

$sheet->getStyle('A5:L' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet->getStyle('A5:L' . $lastRow)->getAlignment()
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet->getStyle('A5:L5')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (range('A', 'L') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

$sheet->freezePane('A6');
$sheet->setAutoFilter('A5:L5');

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Export data ruangan ke Excel",
    "Ruangan"
);

$filename = "data_ruangan_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>