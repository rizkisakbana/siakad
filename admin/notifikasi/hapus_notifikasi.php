<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/notifikasi_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$id_notifikasi = intval($_GET['id'] ?? 0);

if ($id_notifikasi <= 0) {
    echo "<script>
        alert('ID notifikasi tidak valid.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
}

$data = notifikasi_one($conn, "
    SELECT 
        notifikasi.*,
        users.nama_lengkap
    FROM notifikasi
    LEFT JOIN users ON notifikasi.id_user = users.id_user
    WHERE notifikasi.id_notifikasi = '$id_notifikasi'
    LIMIT 1
");

if (!$data) {
    echo "<script>
        alert('Data notifikasi tidak ditemukan.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
}

$hapus = notifikasi_execute($conn, "
    DELETE FROM notifikasi
    WHERE id_notifikasi = '$id_notifikasi'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus notifikasi ID: " . $id_notifikasi . " - " . $data['judul'],
        "Notifikasi"
    );

    echo "<script>
        alert('Notifikasi berhasil dihapus.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Notifikasi gagal dihapus.');
        window.location='data_notifikasi.php';
    </script>";
    exit;
}
?>
