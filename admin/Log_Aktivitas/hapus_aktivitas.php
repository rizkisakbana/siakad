<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$id_log = intval($_GET['id'] ?? 0);

if ($id_log <= 0) {
    echo "<script>
        alert('ID aktivitas tidak valid.');
        window.location='data_aktivitas.php';
    </script>";
    exit;
}

$cek = mysqli_query($conn, "
    SELECT *
    FROM log_aktivitas
    WHERE id_log = '$id_log'
    LIMIT 1
");

if (mysqli_num_rows($cek) < 1) {
    echo "<script>
        alert('Data aktivitas tidak ditemukan.');
        window.location='data_aktivitas.php';
    </script>";
    exit;
}

$data = mysqli_fetch_assoc($cek);

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

    echo "<script>
        alert('Data aktivitas berhasil dihapus.');
        window.location='data_aktivitas.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Data aktivitas gagal dihapus.');
        window.location='data_aktivitas.php';
    </script>";
    exit;
}
?>