<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/notification.php";
require_once "../../includes/email_gateway.php";
require_once "../../includes/whatsapp_gateway.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_mahasiswa = intval($_GET['id'] ?? 0);

if ($id_mahasiswa <= 0) {
    set_alert("error", "ID mahasiswa tidak valid.");
    header("Location: data_mahasiswa.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        mahasiswa.*,
        users.username,
        prodi.nama_prodi,
        prodi.jenjang,
        kelas.nama_kelas
    FROM mahasiswa
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    WHERE mahasiswa.id_mahasiswa = '$id_mahasiswa'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data mahasiswa tidak ditemukan.");
    header("Location: data_mahasiswa.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$id_user = intval($data['id_user']);
$nim = $data['nim'];
$nama_mahasiswa = $data['nama_mahasiswa'];
$email = $data['email'];
$no_hp = $data['no_hp'];
$username = $data['username'];

$total_krs = 0;
$q_krs = mysqli_query($conn, "SELECT COUNT(*) AS total FROM krs WHERE id_mahasiswa = '$id_mahasiswa'");
if ($q_krs) {
    $total_krs = mysqli_fetch_assoc($q_krs)['total'] ?? 0;
}

$total_nilai = 0;
$q_nilai = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM nilai
    LEFT JOIN krs_detail ON nilai.id_krs_detail = krs_detail.id_krs_detail
    LEFT JOIN krs ON krs_detail.id_krs = krs.id_krs
    WHERE krs.id_mahasiswa = '$id_mahasiswa'
");
if ($q_nilai) {
    $total_nilai = mysqli_fetch_assoc($q_nilai)['total'] ?? 0;
}

mysqli_begin_transaction($conn);

try {
    $update_mahasiswa = mysqli_query($conn, "
        UPDATE mahasiswa SET
            status_mahasiswa = 'nonaktif',
            status = 'nonaktif',
            status_sync_feeder = 'belum',
            last_sync_feeder = NULL,
            last_error_feeder = 'Status mahasiswa dinonaktifkan dari SIAKAD dan perlu sinkron ulang.'
        WHERE id_mahasiswa = '$id_mahasiswa'
    ");

    if (!$update_mahasiswa) {
        throw new Exception("Gagal menonaktifkan data mahasiswa.");
    }

    if ($id_user > 0) {
        $update_user = mysqli_query($conn, "
            UPDATE users SET
                status = 'nonaktif',
                updated_at = NOW()
            WHERE id_user = '$id_user'
        ");

        if (!$update_user) {
            throw new Exception("Gagal menonaktifkan akun pengguna mahasiswa.");
        }
    }

    mysqli_commit($conn);

    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Menonaktifkan mahasiswa: $nim - $nama_mahasiswa",
        "Mahasiswa"
    );

    if ($id_user > 0) {
        kirim_notifikasi(
            $conn,
            $id_user,
            "Akun Mahasiswa Dinonaktifkan",
            "Akun dan status mahasiswa Anda telah dinonaktifkan oleh administrator.",
            "warning",
            "../mahasiswa/dashboard.php"
        );
    }

    if (!empty($email)) {
        $pesan_email = "
            <p>Yth. <strong>$nama_mahasiswa</strong>,</p>
            <p>Akun dan status Mahasiswa SIAKAD Anda telah dinonaktifkan oleh administrator.</p>

            <table style='border-collapse: collapse; width: 100%; max-width: 500px; border: 1px solid #ddd;'>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>NIM</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>$nim</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Nama Mahasiswa</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>$nama_mahasiswa</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Program Studi</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>" . ($data['nama_prodi'] ?? '-') . " - " . ($data['jenjang'] ?? '-') . "</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Kelas</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>" . ($data['nama_kelas'] ?? '-') . "</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Username</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>$username</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2; font-weight: bold;'>Status</td>
                    <td style='padding: 8px; border: 1px solid #ddd;'>Nonaktif</td>
                </tr>
            </table>

            <p>Jika informasi ini tidak sesuai, silakan hubungi admin akademik.</p>
            <p>Terima kasih.</p>
        ";

        kirim_email(
            $conn,
            $id_user,
            $email,
            "Informasi Penonaktifan Akun Mahasiswa SIAKAD ATITB",
            $pesan_email
        );
    }

    if (!empty($no_hp)) {
        $pesan_wa = "*PENONAKTIFAN AKUN MAHASISWA SIAKAD ATITB*\n\n" .
            "Halo $nama_mahasiswa,\n\n" .
            "Akun dan status Mahasiswa SIAKAD ATITB Anda telah dinonaktifkan oleh administrator.\n\n" .
            "Detail Data:\n" .
            "- NIM: $nim\n" .
            "- Nama: $nama_mahasiswa\n" .
            "- Prodi: " . ($data['nama_prodi'] ?? '-') . " - " . ($data['jenjang'] ?? '-') . "\n" .
            "- Kelas: " . ($data['nama_kelas'] ?? '-') . "\n" .
            "- Username: $username\n" .
            "- Status: Nonaktif\n\n" .
            "Jika informasi ini tidak sesuai, silakan hubungi admin akademik.";

        kirim_whatsapp(
            $conn,
            $id_user,
            $no_hp,
            $pesan_wa
        );
    }

    set_alert(
        "success",
        "Data mahasiswa berhasil dinonaktifkan. Riwayat akademik tetap tersimpan."
    );

    header("Location: data_mahasiswa.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);

    set_alert("error", $e->getMessage());
    header("Location: detail_mahasiswa.php?id=" . $id_mahasiswa);
    exit;
}
?>
