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
    SELECT 
        mahasiswa.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        kelas.kode_kelas,
        kelas.nama_kelas,
        users.username,
        users.status AS status_user
    FROM mahasiswa
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    ORDER BY prodi.nama_prodi ASC, mahasiswa.angkatan DESC, mahasiswa.nama_mahasiswa ASC
");

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Data Mahasiswa");

$tanggal_export = tanggal_indonesia(date('Y-m-d')) . " " . date('H:i') . " WIB";

$sheet->mergeCells('A1:AJ1');
$sheet->setCellValue('A1', 'AKADEMI TEKNIK INFORMATIKA TUNAS BANGSA JAKARTA');

$sheet->mergeCells('A2:AJ2');
$sheet->setCellValue('A2', 'LAPORAN DATA MAHASISWA');

$sheet->mergeCells('A3:AJ3');
$sheet->setCellValue('A3', 'Tanggal Export : ' . $tanggal_export);

$headers = [
    'No',
    'ID Mahasiswa',
    'ID User',
    'NIM',
    'Nama Mahasiswa',
    'Jenis Kelamin',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Agama',
    'NIK',
    'NISN',
    'NPWP',
    'Kewarganegaraan',
    'Alamat',
    'Kode Pos',
    'Email',
    'No HP',
    'Asal Sekolah',
    'Tahun Lulus',
    'Nama Ayah',
    'Nama Ibu',
    'Pekerjaan Ayah',
    'Pekerjaan Ibu',
    'Penghasilan Orang Tua',
    'Status Mahasiswa',
    'Angkatan',
    'Semester',
    'Jalur Masuk',
    'Tanggal Masuk',
    'Tanggal Keluar',
    'Status Akun',
    'Username',
    'ID Prodi',
    'Kode Prodi',
    'Nama Prodi',
    'ID Kelas',
    'Kode Kelas',
    'Nama Kelas',
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

while ($row = mysqli_fetch_assoc($query)) {
    $jenis_kelamin = '-';

    if (($row['jenis_kelamin'] ?? '') == 'L') {
        $jenis_kelamin = 'Laki-laki';
    } elseif (($row['jenis_kelamin'] ?? '') == 'P') {
        $jenis_kelamin = 'Perempuan';
    }

    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, $row['id_mahasiswa']);
    $sheet->setCellValue('C' . $rowNumber, $row['id_user']);

    $sheet->setCellValueExplicit('D' . $rowNumber, $row['nim'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('E' . $rowNumber, $row['nama_mahasiswa'] ?? '');
    $sheet->setCellValue('F' . $rowNumber, $jenis_kelamin);
    $sheet->setCellValue('G' . $rowNumber, $row['tempat_lahir'] ?? '');
    $sheet->setCellValue('H' . $rowNumber, !empty($row['tanggal_lahir']) ? tanggal_indonesia($row['tanggal_lahir']) : '');
    $sheet->setCellValue('I' . $rowNumber, $row['agama'] ?? '');

    $sheet->setCellValueExplicit('J' . $rowNumber, $row['nik'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('K' . $rowNumber, $row['nisn'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('L' . $rowNumber, $row['npwp'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    $sheet->setCellValue('M' . $rowNumber, $row['kewarganegaraan'] ?? '');
    $sheet->setCellValue('N' . $rowNumber, $row['alamat'] ?? '');
    $sheet->setCellValueExplicit('O' . $rowNumber, $row['kode_pos'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('P' . $rowNumber, $row['email'] ?? '');
    $sheet->setCellValueExplicit('Q' . $rowNumber, $row['no_hp'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    $sheet->setCellValue('R' . $rowNumber, $row['asal_sekolah'] ?? '');
    $sheet->setCellValue('S' . $rowNumber, $row['tahun_lulus'] ?? '');
    $sheet->setCellValue('T' . $rowNumber, $row['nama_ayah'] ?? '');
    $sheet->setCellValue('U' . $rowNumber, $row['nama_ibu'] ?? '');
    $sheet->setCellValue('V' . $rowNumber, $row['pekerjaan_ayah'] ?? '');
    $sheet->setCellValue('W' . $rowNumber, $row['pekerjaan_ibu'] ?? '');
    $sheet->setCellValue('X' . $rowNumber, $row['penghasilan_ortu'] ?? '');

    $sheet->setCellValue('Y' . $rowNumber, $row['status_mahasiswa'] ?? '');
    $sheet->setCellValue('Z' . $rowNumber, $row['angkatan'] ?? '');
    $sheet->setCellValue('AA' . $rowNumber, $row['semester'] ?? '');
    $sheet->setCellValue('AB' . $rowNumber, $row['jalur_masuk'] ?? '');
    $sheet->setCellValue('AC' . $rowNumber, !empty($row['tanggal_masuk']) ? tanggal_indonesia($row['tanggal_masuk']) : '');
    $sheet->setCellValue('AD' . $rowNumber, !empty($row['tanggal_keluar']) ? tanggal_indonesia($row['tanggal_keluar']) : '');

    $sheet->setCellValue('AE' . $rowNumber, $row['status'] ?? '');
    $sheet->setCellValue('AF' . $rowNumber, $row['username'] ?? '');
    $sheet->setCellValue('AG' . $rowNumber, $row['id_prodi'] ?? '');
    $sheet->setCellValue('AH' . $rowNumber, $row['kode_prodi'] ?? '');
    $sheet->setCellValue('AI' . $rowNumber, $row['nama_prodi'] ?? '');
    $sheet->setCellValue('AJ' . $rowNumber, $row['id_kelas'] ?? '');

    $sheet->setCellValue('AK' . $rowNumber, $row['kode_kelas'] ?? '');
    $sheet->setCellValue('AL' . $rowNumber, $row['nama_kelas'] ?? '');
    $sheet->setCellValue('AM' . $rowNumber, !empty($row['created_at']) ? tanggal_jam_indonesia($row['created_at']) : '');
    $sheet->setCellValue('AN' . $rowNumber, !empty($row['updated_at']) ? tanggal_jam_indonesia($row['updated_at']) : '');

    $rowNumber++;
}

$lastRow = $rowNumber - 1;

$sheet->mergeCells('A1:AN1');
$sheet->mergeCells('A2:AN2');
$sheet->mergeCells('A3:AN3');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
$sheet->getStyle('A3')->getFont()->setSize(10);

$sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A5:AN5')->getFont()->setBold(true);
$sheet->getStyle('A5:AN5')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FF1E3A8A');

$sheet->getStyle('A5:AN5')->getFont()->getColor()->setARGB('FFFFFFFF');

$sheet->getStyle('A5:AN' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet->getStyle('A5:AN' . $lastRow)->getAlignment()
    ->setVertical(Alignment::VERTICAL_TOP);

$sheet->getStyle('A5:AN5')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (range('A', 'Z') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

foreach (['AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN'] as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

$sheet->freezePane('A6');
$sheet->setAutoFilter('A5:AN5');

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Export data mahasiswa ke Excel",
    "Mahasiswa"
);

$filename = "data_mahasiswa_" . date('Ymd_His') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>