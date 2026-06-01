<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/log_aktivitas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$id_log = intval($_GET['id'] ?? 0);

if ($id_log <= 0) {
    set_alert("error", "ID aktivitas tidak valid.");
    header("Location: data_aktivitas.php");
    exit;
}

$data = aktivitas_query_one($conn, "
    SELECT *
    FROM log_aktivitas
    WHERE id_log = '$id_log'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data aktivitas tidak ditemukan.");
    header("Location: data_aktivitas.php");
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM log_aktivitas
    WHERE id_log = '$id_log'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus log aktivitas ID: " . $id_log . " - " . $data['aktivitas'],
        "Log Aktivitas"
    );

    set_alert("success", "Data aktivitas berhasil dihapus.");
    header("Location: data_aktivitas.php");
    exit;
} else {
    set_alert("error", "Data aktivitas gagal dihapus.");
    header("Location: data_aktivitas.php");
    exit;
}
