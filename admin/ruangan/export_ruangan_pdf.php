<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$query = mysqli_query($conn, "
    SELECT *
    FROM ruangan
    ORDER BY kode_ruangan ASC, nama_ruangan ASC
");

$tanggal_cetak = tanggal_indonesia(date('Y-m-d')) . " " . date('H:i') . " WIB";

$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #111827;
    }

    .kop {
        text-align: center;
        border-bottom: 2px solid #111827;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    .kop h2 {
        margin: 0;
        font-size: 16px;
        text-transform: uppercase;
    }

    .kop h3 {
        margin: 4px 0 0 0;
        font-size: 13px;
        text-transform: uppercase;
    }

    .kop p {
        margin: 3px 0;
        font-size: 10px;
    }

    .judul {
        text-align: center;
        margin-bottom: 12px;
    }

    .judul h3 {
        margin: 0;
        font-size: 13px;
        text-transform: uppercase;
    }

    .info {
        margin-bottom: 10px;
        font-size: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #1e3a8a;
        color: white;
        border: 1px solid #1e293b;
        padding: 6px 4px;
        text-align: center;
        font-size: 9px;
    }

    td {
        border: 1px solid #cbd5e1;
        padding: 5px 4px;
        vertical-align: top;
        font-size: 8.5px;
    }

    .center {
        text-align: center;
    }

    .small {
        font-size: 8px;
        color: #475569;
    }

    .aktif {
        color: #166534;
        font-weight: bold;
    }

    .nonaktif {
        color: #991b1b;
        font-weight: bold;
    }

    .maintenance {
        color: #c2410c;
        font-weight: bold;
    }

    .footer {
        margin-top: 18px;
        width: 100%;
    }

    .ttd {
        width: 220px;
        float: right;
        text-align: center;
        font-size: 10px;
    }
</style>
</head>
<body>

<div class="kop">
    <h2>Akademi Teknik Informatika Tunas Bangsa Jakarta</h2>
    <h3>Laporan Data Ruangan</h3>
</div>

<div class="info">
    <strong>Tanggal Cetak:</strong> '.$tanggal_cetak.'
</div>

<table>
<thead>
<tr>
    <th width="4%">No</th>
    <th width="10%">Kode</th>
    <th width="18%">Nama Ruangan</th>
    <th width="14%">Lokasi</th>
    <th width="12%">Jenis</th>
    <th width="9%">Kapasitas</th>
    <th width="23%">Fasilitas</th>
    <th width="10%">Status</th>
</tr>
</thead>
<tbody>
';

$no = 1;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $status = htmlspecialchars($row['status'] ?? '-');
        $status_class = '';

        if ($status == 'aktif') {
            $status_class = 'aktif';
        } elseif ($status == 'nonaktif') {
            $status_class = 'nonaktif';
        } elseif ($status == 'maintenance') {
            $status_class = 'maintenance';
        }

        $lokasi = htmlspecialchars($row['gedung'] ?? '-');
        $lokasi .= '<div class="small">Lantai ' . htmlspecialchars($row['lantai'] ?? '-') . '</div>';

        $html .= '
        <tr>
            <td class="center">'.$no++.'</td>
            <td class="center">'.htmlspecialchars($row['kode_ruangan'] ?? '-').'</td>
            <td>'.htmlspecialchars($row['nama_ruangan'] ?? '-').'</td>
            <td>'.$lokasi.'</td>
            <td class="center">'.htmlspecialchars(ucfirst($row['jenis_ruangan'] ?? '-')).'</td>
            <td class="center">'.number_format($row['kapasitas'] ?? 0).' Orang</td>
            <td>'.(!empty($row['fasilitas']) ? htmlspecialchars($row['fasilitas']) : '-').'</td>
            <td class="center '.$status_class.'">'.htmlspecialchars(ucfirst($status)).'</td>
        </tr>
        ';
    }
} else {
    $html .= '
    <tr>
        <td colspan="8" class="center">Data ruangan tidak tersedia.</td>
    </tr>
    ';
}

$html .= '
</tbody>
</table>

<div class="footer">
    <div class="ttd">
        <p>Jakarta, '.tanggal_indonesia(date('Y-m-d')).'</p>
        <p>Admin Akademik</p>
        <br><br><br>
        <p><strong>________________________</strong></p>
    </div>
</div>

</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Export data ruangan ke PDF",
    "Ruangan"
);

$dompdf->stream("data_ruangan_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
exit;
?>