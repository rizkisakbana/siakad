<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_tahun = intval($_GET['id'] ?? 0);

if ($id_tahun <= 0) {
    set_alert("error", "ID tahun akademik tidak valid.");
    header("Location: data_tahun.php");
    exit;
}

$cek = mysqli_query($conn, "SELECT * FROM tahun_akademik WHERE id_tahun='$id_tahun' LIMIT 1");

if (mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data tahun akademik tidak ditemukan.");
    header("Location: data_tahun.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

if ($data['status'] == 'aktif') {
    set_alert("warning", "Tahun akademik aktif tidak dapat dihapus. Aktifkan periode lain terlebih dahulu.");
    header("Location: data_tahun.php");
    exit;
}

$cek_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM kelas WHERE id_tahun='$id_tahun'"))['total'] ?? 0;
$cek_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM jadwal_kuliah WHERE id_tahun='$id_tahun'"))['total'] ?? 0;
$cek_krs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM krs WHERE id_tahun='$id_tahun'"))['total'] ?? 0;
$cek_khs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM khs WHERE id_tahun='$id_tahun'"))['total'] ?? 0;

if ($cek_kelas > 0 || $cek_jadwal > 0 || $cek_krs > 0 || $cek_khs > 0) {
    set_alert("warning", "Tahun akademik tidak dapat dihapus karena sudah digunakan pada kelas, jadwal, KRS, atau KHS.");
    header("Location: data_tahun.php");
    exit;
}

$hapus = mysqli_query($conn, "DELETE FROM tahun_akademik WHERE id_tahun='$id_tahun'");

if ($hapus) {
    simpan_log($conn, $_SESSION['id_user'], "Menghapus tahun akademik: " . $data['tahun'] . " - " . $data['semester'], "Tahun Akademik");
    set_alert("success", "Tahun akademik berhasil dihapus.");
} else {
    set_alert("error", "Tahun akademik gagal dihapus.");
}

header("Location: data_tahun.php");
exit;
?>