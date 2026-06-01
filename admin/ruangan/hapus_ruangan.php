<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_ruangan = intval($_GET['id'] ?? 0);

if ($id_ruangan <= 0) {
    set_alert("error", "ID ruangan tidak valid.");
    header("Location: data_ruangan.php");
    exit;
}

$cek = mysqli_query($conn, "
    SELECT *
    FROM ruangan
    WHERE id_ruangan = '$id_ruangan'
    LIMIT 1
");

if (!$cek || mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data ruangan tidak ditemukan.");
    header("Location: data_ruangan.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

$total_jadwal = 0;
$q_jadwal = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    WHERE id_ruangan = '$id_ruangan'
");

if ($q_jadwal) {
    $total_jadwal = mysqli_fetch_assoc($q_jadwal)['total'] ?? 0;
}

if ($total_jadwal > 0) {
    set_alert(
        "warning",
        "Ruangan tidak dapat dihapus karena sudah digunakan pada jadwal kuliah."
    );

    header("Location: detail_ruangan.php?id=" . $id_ruangan);
    exit;
}

$hapus = mysqli_query($conn, "
    DELETE FROM ruangan
    WHERE id_ruangan = '$id_ruangan'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus ruangan: " . ($data['kode_ruangan'] ?? '-') . " - " . ($data['nama_ruangan'] ?? '-'),
        "Ruangan"
    );

    set_alert("success", "Data ruangan berhasil dihapus.");
    header("Location: data_ruangan.php");
    exit;
} else {
    set_alert("error", "Data ruangan gagal dihapus.");
    header("Location: detail_ruangan.php?id=" . $id_ruangan);
    exit;
}
?>