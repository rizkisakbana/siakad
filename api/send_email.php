<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/email_gateway.php";

cek_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_POST['id_user'] ?? null;
    $email = $_POST['email'] ?? '';
    $subjek = $_POST['subjek'] ?? '';
    $pesan = $_POST['pesan'] ?? '';

    if ($email && $subjek && $pesan) {
        $status = kirim_email($conn, $id_user, $email, $subjek, $pesan);

        echo json_encode([
            'status' => $status ? 'success' : 'error',
            'message' => $status ? 'Email berhasil dikirim' : 'Email gagal dikirim'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data email belum lengkap'
        ]);
    }
}
?>