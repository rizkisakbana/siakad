<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/jadwal_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

header('Content-Type: application/json');

$id_jadwal = intval($_GET['id_jadwal'] ?? 0);
$id_tahun = intval($_GET['id_tahun'] ?? 0);
$id_kelas = intval($_GET['id_kelas'] ?? 0);
$id_dosen = intval($_GET['id_dosen'] ?? 0);
$id_ruangan = intval($_GET['id_ruangan'] ?? 0);
$hari = mysqli_real_escape_string($conn, $_GET['hari'] ?? '');
$jam_mulai = mysqli_real_escape_string($conn, $_GET['jam_mulai'] ?? '');
$jam_selesai = mysqli_real_escape_string($conn, $_GET['jam_selesai'] ?? '');

$errors = [];

if ($id_tahun <= 0 || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
    echo json_encode(['bentrok' => false, 'errors' => ['Parameter belum lengkap.']]);
    exit;
}

if ($jam_mulai >= $jam_selesai) {
    echo json_encode(['bentrok' => true, 'errors' => ['Jam mulai harus lebih kecil dari jam selesai.']]);
    exit;
}

$exclude = $id_jadwal > 0 ? "AND id_jadwal != '$id_jadwal'" : "";
$time_clause = "
    id_tahun = '$id_tahun'
    AND hari = '$hari'
    AND status = 'aktif'
    AND ('$jam_mulai' < jam_selesai AND '$jam_selesai' > jam_mulai)
    $exclude
";

if ($id_kelas > 0) {
    $bentrok_kelas = jadwal_query_exists($conn, "SELECT id_jadwal FROM jadwal_kuliah WHERE id_kelas = '$id_kelas' AND $time_clause LIMIT 1");
    if ($bentrok_kelas === null) {
        $errors[] = 'Validasi jadwal kelas gagal diproses.';
    } elseif ($bentrok_kelas) {
        $errors[] = 'Kelas sudah memiliki jadwal pada waktu tersebut.';
    }
}

if ($id_dosen > 0) {
    $bentrok_dosen = jadwal_query_exists($conn, "SELECT id_jadwal FROM jadwal_kuliah WHERE id_dosen = '$id_dosen' AND $time_clause LIMIT 1");
    if ($bentrok_dosen === null) {
        $errors[] = 'Validasi jadwal dosen gagal diproses.';
    } elseif ($bentrok_dosen) {
        $errors[] = 'Dosen sudah mengajar pada waktu tersebut.';
    }
}

if ($id_ruangan > 0) {
    $bentrok_ruangan = jadwal_query_exists($conn, "SELECT id_jadwal FROM jadwal_kuliah WHERE id_ruangan = '$id_ruangan' AND $time_clause LIMIT 1");
    if ($bentrok_ruangan === null) {
        $errors[] = 'Validasi jadwal ruangan gagal diproses.';
    } elseif ($bentrok_ruangan) {
        $errors[] = 'Ruangan sudah digunakan pada waktu tersebut.';
    }
}

echo json_encode([
    'bentrok' => !empty($errors),
    'errors' => $errors,
]);
