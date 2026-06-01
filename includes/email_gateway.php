<?php
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$last_email_gateway_error = null;

function email_gateway_last_error()
{
    global $last_email_gateway_error;
    return $last_email_gateway_error;
}

function kirim_email($conn, $id_user, $tujuan_email, $subjek, $isi_pesan)
{
    global $email_config;
    global $last_email_gateway_error;

    $last_email_gateway_error = null;

    if (empty($tujuan_email) || !filter_var($tujuan_email, FILTER_VALIDATE_EMAIL)) {
        $last_email_gateway_error = "Alamat email tidak valid.";
        return false;
    }

    $tujuan_email_db = mysqli_real_escape_string($conn, $tujuan_email);
    $subjek_db = mysqli_real_escape_string($conn, $subjek);
    $isi_pesan_db = mysqli_real_escape_string($conn, $isi_pesan);
    $id_user_sql = $id_user ? intval($id_user) : "NULL";

    $insert_log = mysqli_query($conn, "
        INSERT INTO email_log 
        (id_user, tujuan_email, subjek, isi_pesan, status)
        VALUES 
        ($id_user_sql, '$tujuan_email_db', '$subjek_db', '$isi_pesan_db', 'pending')
    ");

    if (!$insert_log) {
        $last_email_gateway_error = "Gagal mencatat email log: " . mysqli_error($conn);
        return false;
    }

    $id_email_log = mysqli_insert_id($conn);

    if (!extension_loaded('openssl')) {
        $last_email_gateway_error = "Ekstensi PHP OpenSSL belum aktif. Aktifkan extension=openssl di php.ini lalu restart Apache.";
        $error_db = mysqli_real_escape_string($conn, $last_email_gateway_error);

        mysqli_query($conn, "
            UPDATE email_log
            SET status = 'gagal',
                response = '$error_db'
            WHERE id_email_log = '$id_email_log'
        ");

        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $email_config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['smtp_username'];
        $mail->Password   = $email_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $email_config['smtp_port'];

        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($tujuan_email);

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = $isi_pesan;
        $mail->AltBody = strip_tags($isi_pesan);

        $mail->send();

        mysqli_query($conn, "
            UPDATE email_log 
            SET status = 'terkirim',
                sent_at = NOW(),
                response = 'Email berhasil dikirim melalui SMTP'
            WHERE id_email_log = '$id_email_log'
        ");

        return true;

    } catch (Exception $e) {
        $error = mysqli_real_escape_string($conn, $mail->ErrorInfo);
        $last_email_gateway_error = $mail->ErrorInfo ?: $e->getMessage();

        mysqli_query($conn, "
            UPDATE email_log 
            SET status = 'gagal',
                response = '$error'
            WHERE id_email_log = '$id_email_log'
        ");

        return false;
    }
}
?>
