<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

header('Content-Type: application/json; charset=utf-8');

$keyword = trim($_GET['q'] ?? '');

if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

$keyword = mysqli_real_escape_string($conn, $keyword);

$query = mysqli_query($conn, "
    SELECT 
        kec.id_feeder AS id_kecamatan,
        kec.nama_ref AS nama_kecamatan,

        kota.id_feeder AS id_kota,
        kota.nama_ref AS nama_kota,

        prov.id_feeder AS id_provinsi,
        prov.nama_ref AS nama_provinsi

    FROM ref_pddikti kec

    LEFT JOIN ref_pddikti kota 
        ON kec.id_induk_feeder = kota.id_feeder
        AND kota.jenis_ref = 'wilayah'

    LEFT JOIN ref_pddikti prov 
        ON kota.id_induk_feeder = prov.id_feeder
        AND prov.jenis_ref = 'wilayah'

    WHERE kec.jenis_ref = 'wilayah'
    AND kec.status = 'aktif'
    AND (
        kec.nama_ref LIKE '%$keyword%'
        OR kota.nama_ref LIKE '%$keyword%'
        OR prov.nama_ref LIKE '%$keyword%'
        OR kec.id_feeder LIKE '%$keyword%'
    )

    ORDER BY 
        prov.nama_ref ASC,
        kota.nama_ref ASC,
        kec.nama_ref ASC

    LIMIT 30
");

$data = [];

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {

        $nama_kecamatan = $row['nama_kecamatan'] ?? '';
        $nama_kota = $row['nama_kota'] ?? '';
        $nama_provinsi = $row['nama_provinsi'] ?? '';

        $label_parts = [];

        if (!empty($nama_kecamatan)) {
            $label_parts[] = $nama_kecamatan;
        }

        if (!empty($nama_kota)) {
            $label_parts[] = $nama_kota;
        }

        if (!empty($nama_provinsi)) {
            $label_parts[] = $nama_provinsi;
        }

        $label = implode(' - ', $label_parts);

        $data[] = [
            'id' => $row['id_kecamatan'],
            'text' => $label . ' (' . $row['id_kecamatan'] . ')',
            'kecamatan' => $nama_kecamatan,
            'kota' => $nama_kota,
            'provinsi' => $nama_provinsi
        ];
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit;