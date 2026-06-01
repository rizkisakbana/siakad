<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$id_email_log = intval($_GET['id'] ?? 0);

if ($id_email_log <= 0) {
    echo "<script>
        alert('ID email tidak valid.');
        window.location='data_email.php';
    </script>";
    exit;
}

$cek = mysqli_query($conn, "
    SELECT 
        email_log.*,
        users.nama_lengkap
    FROM email_log
    LEFT JOIN users ON email_log.id_user = users.id_user
    WHERE email_log.id_email_log = '$id_email_log'
    LIMIT 1
");

if (mysqli_num_rows($cek) < 1) {
    echo "<script>
        alert('Data email tidak ditemukan.');
        window.location='data_email.php';
    </script>";
    exit;
}

$data = mysqli_fetch_assoc($cek);

$hapus = mysqli_query($conn, "
    DELETE FROM email_log
    WHERE id_email_log = '$id_email_log'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus email log ID: " . $id_email_log . " - Tujuan: " . $data['tujuan_email'],
        "Email Gateway"
    );

    echo "<script>
        alert('Log email berhasil dihapus.');
        window.location='data_email.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Log email gagal dihapus.');
        window.location='data_email.php';
    </script>";
    exit;
}
?>