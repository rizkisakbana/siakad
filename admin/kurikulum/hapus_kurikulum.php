<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/kurikulum_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_kurikulum = intval($_GET['id'] ?? 0);

if ($id_kurikulum <= 0) {
    set_alert("error", "ID kurikulum tidak valid.");
    header("Location: data_kurikulum.php");
    exit;
}

$data = kurikulum_query_one($conn, "
    SELECT 
        kurikulum.*,
        prodi.nama_prodi
    FROM kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE kurikulum.id_kurikulum = '$id_kurikulum'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data kurikulum tidak ditemukan.");
    header("Location: data_kurikulum.php");
    exit;
}

$cek_mk = kurikulum_count($conn, "
    SELECT COUNT(*) AS total
    FROM mata_kuliah
    WHERE id_kurikulum = '$id_kurikulum'
");

if ($cek_mk > 0) {
    set_alert("warning", "Kurikulum tidak dapat dihapus karena sudah digunakan pada data mata kuliah.");
    header("Location: detail_kurikulum.php?id=" . $id_kurikulum);
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM kurikulum
    WHERE id_kurikulum = '$id_kurikulum'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus kurikulum: " . $data['nama_kurikulum'] . " - " . ($data['nama_prodi'] ?? '-'),
        "Kurikulum"
    );

    set_alert("success", "Kurikulum berhasil dihapus.");
    header("Location: data_kurikulum.php");
    exit;
} else {
    set_alert("error", "Kurikulum gagal dihapus.");
    header("Location: detail_kurikulum.php?id=" . $id_kurikulum);
    exit;
}
?>
