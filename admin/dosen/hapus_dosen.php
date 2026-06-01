<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/email_gateway.php";
require_once "../../includes/whatsapp_gateway.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_dosen = intval($_GET['id'] ?? 0);

if ($id_dosen <= 0) {
    set_alert("error", "ID dosen tidak valid.");
    header("Location: data_dosen.php");
    exit;
}

$cek = mysqli_query($conn, "
    SELECT 
        dosen.*,
        users.username,
        prodi.nama_prodi,
        prodi.jenjang
    FROM dosen
    LEFT JOIN users ON dosen.id_user = users.id_user
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    WHERE dosen.id_dosen = '$id_dosen'
    LIMIT 1
");

if (mysqli_num_rows($cek) < 1) {
    set_alert("error", "Data dosen tidak ditemukan.");
    header("Location: data_dosen.php");
    exit;
}

$data = mysqli_fetch_assoc($cek);

$cek_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    WHERE id_dosen = '$id_dosen'
"))['total'] ?? 0;

if ($cek_jadwal > 0) {
    set_alert("warning", "Dosen tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.");
    header("Location: detail_dosen.php?id=" . $id_dosen);
    exit;
}

$id_user = intval($data['id_user']);
$nama_dosen = $data['nama_dosen'];
$username = $data['username'];
$email = $data['email'];
$no_hp = $data['no_hp'];
$foto = $data['foto'];

$nama_lengkap = trim(($data['gelar_depan'] ?? '') . ' ' . $nama_dosen . ' ' . ($data['gelar_belakang'] ?? ''));

if (!empty($email)) {
    $pesan_email = "
        <p>Yth. <strong>$nama_lengkap</strong>,</p>
        <p>Akun dan data dosen SIAKAD Anda telah dihapus oleh administrator.</p>

        <table style='border-collapse: collapse; width: 100%; max-width: 500px; border: 1px solid #ddd;'>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Nama Dosen</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>$nama_lengkap</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>NIDN</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['nidn']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>NIP</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['nip']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Program Studi</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['nama_prodi']} - {$data['jenjang']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Username</td>
                <td style='padding: 8px; border: 1px solid #ddd;'>$username</td>
            </tr>
        </table>

        <p>Jika informasi ini tidak sesuai, silakan hubungi admin akademik.</p>
        <p>Terima kasih.</p>
    ";

    kirim_email(
        $conn,
        $id_user,
        $email,
        "Informasi Penghapusan Akun Dosen SIAKAD ATITB",
        $pesan_email
    );
}

if (!empty($no_hp)) {
    $pesan_wa = "*PENGHAPUSAN AKUN DOSEN SIAKAD ATITB*\n\n" .
        "Halo $nama_lengkap,\n\n" .
        "Akun dan data dosen SIAKAD ATITB Anda telah dihapus oleh administrator.\n\n" .
        "Detail Data:\n" .
        "- Nama: $nama_lengkap\n" .
        "- NIDN: " . ($data['nidn'] ?? '-') . "\n" .
        "- NIP: " . ($data['nip'] ?? '-') . "\n" .
        "- Prodi: " . ($data['nama_prodi'] ?? '-') . " - " . ($data['jenjang'] ?? '-') . "\n" .
        "- Username: $username\n\n" .
        "Jika informasi ini tidak sesuai, silakan hubungi admin akademik.";

    kirim_whatsapp(
        $conn,
        $id_user,
        $no_hp,
        $pesan_wa
    );
}

mysqli_begin_transaction($conn);

try {
    $hapus_dosen = mysqli_query($conn, "
        DELETE FROM dosen
        WHERE id_dosen = '$id_dosen'
    ");

    if (!$hapus_dosen) {
        throw new Exception("Gagal menghapus data dosen.");
    }

    if ($id_user > 0) {
        mysqli_query($conn, "
            DELETE FROM users
            WHERE id_user = '$id_user'
        ");
    }

    mysqli_commit($conn);

    if (!empty($foto) && file_exists("../../uploads/dosen/" . $foto)) {
        unlink("../../uploads/dosen/" . $foto);
    }

    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menghapus data dosen: " . $nama_lengkap,
        "Dosen"
    );

    set_alert("success", "Data dosen berhasil dihapus. Email dan WhatsApp pemberitahuan telah diproses.");
    header("Location: data_dosen.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);

    set_alert("error", $e->getMessage());
    header("Location: detail_dosen.php?id=" . $id_dosen);
    exit;
}
?>