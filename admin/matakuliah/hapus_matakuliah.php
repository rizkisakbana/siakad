<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_mk = intval($_GET['id'] ?? 0);

if ($id_mk <= 0) {
    set_alert("error", "ID mata kuliah tidak valid.");
    header("Location: data_matakuliah.php");
    exit;
}

$cek = mysqli_query($conn, "
    SELECT 
        mata_kuliah.*,
        kurikulum.nama_kurikulum,
        prodi.nama_prodi
    FROM mata_kuliah
    LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE mata_kuliah.id_mk = '$id_mk'
    LIMIT 1
");

if (mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data mata kuliah tidak ditemukan.");
    header("Location: data_matakuliah.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

$cek_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    WHERE id_mk = '$id_mk'
"))['total'] ?? 0;

if ($cek_jadwal > 0) {
    set_alert("warning", "Mata kuliah tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.");
    header("Location: detail_matakuliah.php?id=" . $id_mk);
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM mata_kuliah
    WHERE id_mk = '$id_mk'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus mata kuliah: " . $data['kode_mk'] . " - " . $data['nama_mk'] . " pada kurikulum " . ($data['nama_kurikulum'] ?? '-'),
        "Mata Kuliah"
    );

    set_alert("success", "Mata kuliah berhasil dihapus.");
    header("Location: data_matakuliah.php");
    exit;
} else {
    set_alert("error", "Mata kuliah gagal dihapus.");
    header("Location: detail_matakuliah.php?id=" . $id_mk);
    exit;
}
?>