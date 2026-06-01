<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/notifikasi_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$id_whatsapp_log = intval($_GET['id'] ?? 0);

if ($id_whatsapp_log <= 0) {
    echo "<script>
        alert('ID WhatsApp tidak valid.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
}

$data = notifikasi_one($conn, "
    SELECT 
        whatsapp_log.*,
        users.nama_lengkap
    FROM whatsapp_log
    LEFT JOIN users ON whatsapp_log.id_user = users.id_user
    WHERE whatsapp_log.id_whatsapp_log = '$id_whatsapp_log'
    LIMIT 1
");

if (!$data) {
    echo "<script>
        alert('Data WhatsApp tidak ditemukan.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
}

$hapus = notifikasi_execute($conn, "
    DELETE FROM whatsapp_log
    WHERE id_whatsapp_log = '$id_whatsapp_log'
");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus WhatsApp log ID: " . $id_whatsapp_log . " - Tujuan: " . $data['tujuan_nomor'],
        "WhatsApp Gateway"
    );

    echo "<script>
        alert('Log WhatsApp berhasil dihapus.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Log WhatsApp gagal dihapus.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
}
?>
