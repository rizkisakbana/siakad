<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$cek = mysqli_query($conn, "
    SELECT 
        kelas.*,
        prodi.nama_prodi,
        prodi.jenjang,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    WHERE kelas.id_kelas = '$id_kelas'
    LIMIT 1
");

if (!$cek || mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

$total_mahasiswa = 0;
$query_mahasiswa = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas'
");

if ($query_mahasiswa) {
    $total_mahasiswa = mysqli_fetch_assoc($query_mahasiswa)['total'] ?? 0;
}

$total_jadwal = 0;
$query_jadwal = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_kelas = '$id_kelas'
");

if ($query_jadwal) {
    $total_jadwal = mysqli_fetch_assoc($query_jadwal)['total'] ?? 0;
}

if ($total_mahasiswa > 0 || $total_jadwal > 0) {
    set_alert(
        "warning",
        "Kelas tidak dapat dihapus karena sudah digunakan pada data mahasiswa atau jadwal kuliah."
    );

    header("Location: detail_kelas.php?id=" . $id_kelas);
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM kelas
    WHERE id_kelas = '$id_kelas'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus kelas: " . ($data['kode_kelas'] ?? '-') . " - " . $data['nama_kelas'],
        "Kelas"
    );

    set_alert("success", "Data kelas berhasil dihapus.");
    header("Location: data_kelas.php");
    exit;
} else {
    set_alert("error", "Data kelas gagal dihapus.");
    header("Location: detail_kelas.php?id=" . $id_kelas);
    exit;
}
?>