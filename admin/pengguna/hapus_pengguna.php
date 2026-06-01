<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/notification.php";
require_once __DIR__ . "/../../includes/email_gateway.php";
require_once __DIR__ . "/../../includes/whatsapp_gateway.php";
require_once __DIR__ . "/pengguna_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_user = intval($_GET['id'] ?? 0);

if ($id_user <= 0) {
    set_alert("error", "ID pengguna tidak valid.");
    header("Location: data_pengguna.php");
    exit;
}

if ($id_user == $_SESSION['id_user']) {
    set_alert("warning", "Anda tidak dapat menghapus akun yang sedang digunakan.");
    header("Location: data_pengguna.php");
    exit;
}

$data = pengguna_one($conn, "SELECT * FROM users WHERE id_user='$id_user' LIMIT 1");

if (!$data) {
    set_alert("error", "Data pengguna tidak ditemukan.");
    header("Location: data_pengguna.php");
    exit;
}

$cek_dosen = pengguna_count($conn, "SELECT COUNT(*) AS total FROM dosen WHERE id_user='$id_user'");
$cek_mahasiswa = pengguna_count($conn, "SELECT COUNT(*) AS total FROM mahasiswa WHERE id_user='$id_user'");

if ($cek_dosen > 0 || $cek_mahasiswa > 0) {
    set_alert("warning", "Pengguna tidak dapat dihapus karena sudah terhubung dengan data dosen atau mahasiswa.");
    header("Location: data_pengguna.php");
    exit;
}

$nama_lengkap = $data['nama_lengkap'];
$email = $data['email'];
$no_hp = $data['no_hp'];
$username = $data['username'];

if (!empty($email)) {
    kirim_email(
        $conn,
        $id_user,
        $email,
        "Akun SIAKAD ATITB Dihapus",
        "
        <p>Yth. <strong>$nama_lengkap</strong>,</p>
        <p>Akun SIAKAD Anda dengan username <strong>$username</strong> telah dihapus oleh administrator.</p>
        <p>Jika informasi ini tidak sesuai, silakan hubungi admin akademik.</p>
        <br>
        <p>Hormat kami,<br><strong>SIAKAD ATITB Jakarta</strong></p>
        "
    );
}

if (!empty($no_hp)) {
    kirim_whatsapp(
        $conn,
        $id_user,
        $no_hp,
        "*Akun SIAKAD ATITB Dihapus*\n\n" .
        "Halo $nama_lengkap Akun Anda dengan username $username telah dihapus oleh administrator\n" .
        "Jika informasi ini tidak sesuai, silakan hubungi admin akademik.\n\n" .
        "Terima kasih\n\n" .
        "SIAKAD ATITB Jakarta"
    ); 
}

$hapus = pengguna_execute($conn, "DELETE FROM users WHERE id_user='$id_user'");

if ($hapus) {
    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus pengguna: " . $nama_lengkap,
        "Pengguna"
    );

    set_alert("success", "Pengguna berhasil dihapus. Email dan WhatsApp pemberitahuan telah diproses.");
} else {
    set_alert("error", "Pengguna gagal dihapus.");
}

header("Location: data_pengguna.php");
exit;
?>
