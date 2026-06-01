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

mysqli_begin_transaction($conn);

try {
    mysqli_query($conn, "UPDATE tahun_akademik SET status='nonaktif'");
    mysqli_query($conn, "UPDATE tahun_akademik SET status='aktif' WHERE id_tahun='$id_tahun'");

    mysqli_commit($conn);

    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Mengaktifkan tahun akademik: " . $data['tahun'] . " - " . $data['semester'],
        "Tahun Akademik"
    );

    set_alert("success", "Tahun akademik " . $data['tahun'] . " - " . $data['semester'] . " berhasil dijadikan aktif.");
} catch (Exception $e) {
    mysqli_rollback($conn);
    set_alert("error", "Gagal mengaktifkan tahun akademik.");
}

header("Location: data_tahun.php");
exit;
?>