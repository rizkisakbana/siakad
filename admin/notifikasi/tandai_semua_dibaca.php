<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/notification.php";
require_once __DIR__ . "/notifikasi_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: data_notifikasi.php");
    exit;
}

$scope = $_POST['scope'] ?? 'saya';
$paksa = ($scope === 'semua');
$id_user = $_SESSION['id_user'] ?? null;

$query_count = $paksa
    ? "SELECT COUNT(*) AS total FROM notifikasi WHERE status_baca = 'belum'"
    : "SELECT COUNT(*) AS total FROM notifikasi WHERE status_baca = 'belum' AND id_user = '" . intval($id_user) . "'";

$total_ditandai = notifikasi_count($conn, $query_count);
$berhasil = tandai_semua_notifikasi_dibaca($conn, $id_user, $paksa);

if ($berhasil) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menandai " . intval($total_ditandai) . " notifikasi sebagai dibaca",
        "Notifikasi"
    );

    header("Location: data_notifikasi.php?status=read_all&jumlah=" . intval($total_ditandai));
    exit;
}

header("Location: data_notifikasi.php?status=read_all_failed");
exit;
?>
