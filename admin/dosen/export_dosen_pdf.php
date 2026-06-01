<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/dosen_helper.php";

/** @var mysqli $conn */

use Dompdf\Dompdf;
use Dompdf\Options;

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$data_dosen = dosen_fetch_all($conn, "
    SELECT 
        dosen.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        users.username
    FROM dosen
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    LEFT JOIN users ON dosen.id_user = users.id_user
    ORDER BY prodi.nama_prodi ASC, dosen.nama_dosen ASC
");

$tanggal_cetak = tanggal_indonesia(date('Y-m-d')) . " " . date('H:i');

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
            margin-bottom: 16px;
        }

        .kop h2 {
            margin: 0;
            font-size: 17px;
            text-transform: uppercase;
        }

        .kop h3 {
            margin: 4px 0 0 0;
            font-size: 14px;
            text-transform: uppercase;
        }

        .kop p {
            margin: 3px 0;
            font-size: 10px;
        }

        .judul {
            text-align: center;
            margin-bottom: 14px;
        }

        .judul h3 {
            margin: 0;
            font-size: 14px;
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

        .badge-aktif {
            color: #166534;
            font-weight: bold;
        }

        .badge-nonaktif {
            color: #991b1b;
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

        .small {
            font-size: 8px;
            color: #475569;
        }
    </style>
</head>
<body>

<div class="kop">
    <h2>Akademi Teknik Informatika Tunas Bangsa Jakarta</h2>
    <h3>Laporan Data Dosen</h3>
</div>

<div class="info">
    <strong>Tanggal Cetak:</strong> '.$tanggal_cetak.' WIB
</div>

<table>
    <thead>
        <tr>
            <th width="3%">No</th>
            <th width="15%">Nama Dosen</th>
            <th width="8%">NIDN</th>
            <th width="8%">NIP</th>
            <th width="5%">JK</th>
            <th width="11%">TTL</th>
            <th width="12%">Prodi</th>
            <th width="11%">Email</th>
            <th width="9%">No. HP</th>
            <th width="8%">Status</th>
            <th width="10%">Username</th>
        </tr>
    </thead>
    <tbody>
';

$no = 1;

if (!empty($data_dosen)) {
    foreach ($data_dosen as $row) {
        $nama_lengkap = trim(($row['gelar_depan'] ?? '') . ' ' . $row['nama_dosen'] . ' ' . ($row['gelar_belakang'] ?? ''));

        $jk = '-';
        if (($row['jenis_kelamin'] ?? '') == 'L') {
            $jk = 'L';
        } elseif (($row['jenis_kelamin'] ?? '') == 'P') {
            $jk = 'P';
        }

        $ttl = htmlspecialchars($row['tempat_lahir'] ?? '-');
        if (!empty($row['tanggal_lahir'])) {
            $ttl .= ', ' . tanggal_indonesia($row['tanggal_lahir']);
        }

        $status_class = ($row['status'] == 'aktif') ? 'badge-aktif' : 'badge-nonaktif';

        $html .= '
            <tr>
                <td class="center">'.$no++.'</td>
                <td>'.htmlspecialchars($nama_lengkap).'</td>
                <td class="center">'.htmlspecialchars($row['nidn'] ?? '-').'</td>
                <td class="center">'.htmlspecialchars($row['nip'] ?? '-').'</td>
                <td class="center">'.$jk.'</td>
                <td>'.$ttl.'</td>
                <td>
                    '.htmlspecialchars($row['nama_prodi'] ?? '-').'
                    <div class="small">'.htmlspecialchars($row['jenjang'] ?? '-').' - '.htmlspecialchars($row['kode_prodi'] ?? '-').'</div>
                </td>
                <td>'.htmlspecialchars($row['email'] ?? '-').'</td>
                <td>'.htmlspecialchars($row['no_hp'] ?? '-').'</td>
                <td class="center '.$status_class.'">'.htmlspecialchars($row['status'] ?? '-').'</td>
                <td>'.htmlspecialchars($row['username'] ?? '-').'</td>
            </tr>
        ';
    }
} else {
    $html .= '
        <tr>
            <td colspan="11" class="center">Data dosen tidak tersedia.</td>
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
    "Export data dosen ke PDF",
    "Dosen"
);

$dompdf->stream("data_dosen_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
exit;
?>
