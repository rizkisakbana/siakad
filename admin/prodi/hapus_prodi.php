<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_prodi = intval($_GET['id'] ?? 0);

if ($id_prodi <= 0) {
    set_alert("error", "ID prodi tidak valid.");
    header("Location: data_prodi.php");
    exit;
}

$cek = mysqli_query($conn, "SELECT * FROM prodi WHERE id_prodi='$id_prodi' LIMIT 1");

if (mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data prodi tidak ditemukan.");
    header("Location: data_prodi.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

$cek_relasi_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mahasiswa WHERE id_prodi='$id_prodi'
"))['total'] ?? 0;

$cek_relasi_kurikulum = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM kurikulum WHERE id_prodi='$id_prodi'
"))['total'] ?? 0;

$cek_relasi_kelas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM kelas WHERE id_prodi='$id_prodi'
"))['total'] ?? 0;

if ($cek_relasi_mahasiswa > 0 || $cek_relasi_kurikulum > 0 || $cek_relasi_kelas > 0) {
    set_alert("warning", "Program studi tidak dapat dihapus karena sudah digunakan pada data mahasiswa, kurikulum, atau kelas.");
    header("Location: data_prodi.php");
    exit;
}

$hapus = mysqli_query($conn, "DELETE FROM prodi WHERE id_prodi='$id_prodi'");

if ($hapus) {
    simpan_log($conn, $_SESSION['id_user'], "Menghapus program studi: " . $data['nama_prodi'], "Program Studi");
    set_alert("success", "Program studi berhasil dihapus.");
} else {
    set_alert("error", "Program studi gagal dihapus.");
}

header("Location: data_prodi.php");
exit;
?>