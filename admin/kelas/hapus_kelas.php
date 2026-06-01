<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/kelas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$data = kelas_query_one($conn, "
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

if (!$data) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$total_mahasiswa = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas'
");

$total_jadwal = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_kelas = '$id_kelas'
");

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
