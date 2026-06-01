<?php
require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/whatsapp_gateway.php";

cek_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_POST['id_user'] ?? null;
    $nomor = $_POST['nomor'] ?? '';
    $pesan = $_POST['pesan'] ?? '';

    if ($nomor && $pesan) {
        $status = kirim_whatsapp($conn, $id_user, $nomor, $pesan);

        echo json_encode([
            'status' => $status ? 'success' : 'error',
            'message' => $status ? 'WhatsApp berhasil dikirim' : 'WhatsApp gagal dikirim'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data WhatsApp belum lengkap'
        ]);
    }
}
?>