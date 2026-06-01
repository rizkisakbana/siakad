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
    SELECT 
        mahasiswa.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        kelas.kode_kelas,
        kelas.nama_kelas,
        users.username
    FROM mahasiswa
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    ORDER BY prodi.nama_prodi ASC, mahasiswa.angkatan DESC, mahasiswa.nama_mahasiswa ASC
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
        font-size: 9px;
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
        font-size: 9px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #1e3a8a;
        color: white;
        border: 1px solid #1e293b;
        padding: 5px 3px;
        text-align: center;
        font-size: 8px;
    }

    td {
        border: 1px solid #cbd5e1;
        padding: 4px 3px;
        vertical-align: top;
        font-size: 7.5px;
    }

    .center {
        text-align: center;
    }

    .small {
        font-size: 7px;
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

    .footer {
        margin-top: 18px;
        width: 100%;
    }

    .ttd {
        width: 220px;
        float: right;
        text-align: center;
        font-size: 9px;
    }
</style>
</head>
<body>

<div class="kop">
    <h2>Akademi Teknik Informatika Tunas Bangsa Jakarta</h2>
    <h3>Laporan Data Mahasiswa</h3>
</div>

<div class="info">
    <strong>Tanggal Cetak:</strong> '.$tanggal_cetak.'
</div>

<table>
<thead>
<tr>
    <th width="3%">No</th>
    <th width="8%">NIM</th>
    <th width="15%">Nama Mahasiswa</th>
    <th width="5%">JK</th>
    <th width="10%">TTL</th>
    <th width="12%">Prodi</th>
    <th width="9%">Kelas</th>
    <th width="6%">Angkatan</th>
    <th width="5%">Smt</th>
    <th width="11%">Email</th>
    <th width="8%">No. HP</th>
    <th width="8%">Status</th>
</tr>
</thead>
<tbody>
';

$no = 1;

if ($query && mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
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

        $status = htmlspecialchars($row['status_mahasiswa'] ?? '-');
        $status_class = ($status == 'aktif') ? 'aktif' : 'nonaktif';

        $html .= '
        <tr>
            <td class="center">'.$no++.'</td>
            <td class="center">'.htmlspecialchars($row['nim'] ?? '-').'</td>
            <td>'.htmlspecialchars($row['nama_mahasiswa'] ?? '-').'</td>
            <td class="center">'.$jk.'</td>
            <td>'.$ttl.'</td>
            <td>
                '.htmlspecialchars($row['nama_prodi'] ?? '-').'
                <div class="small">'.htmlspecialchars($row['jenjang'] ?? '-').' - '.htmlspecialchars($row['kode_prodi'] ?? '-').'</div>
            </td>
            <td>
                '.htmlspecialchars($row['nama_kelas'] ?? '-').'
                <div class="small">'.htmlspecialchars($row['kode_kelas'] ?? '-').'</div>
            </td>
            <td class="center">'.htmlspecialchars($row['angkatan'] ?? '-').'</td>
            <td class="center">'.htmlspecialchars($row['semester'] ?? '-').'</td>
            <td>'.htmlspecialchars($row['email'] ?? '-').'</td>
            <td>'.htmlspecialchars($row['no_hp'] ?? '-').'</td>
            <td class="center '.$status_class.'">'.$status.'</td>
        </tr>
        ';
    }
} else {
    $html .= '
    <tr>
        <td colspan="12" class="center">Data mahasiswa tidak tersedia.</td>
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
    "Export data mahasiswa ke PDF",
    "Mahasiswa"
);

$dompdf->stream("data_mahasiswa_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
exit;
?>