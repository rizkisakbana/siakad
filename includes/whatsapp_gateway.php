<?php

require_once __DIR__ . '/../config/whatsapp.php';

$last_whatsapp_gateway_error = null;

function whatsapp_gateway_last_error()
{
    global $last_whatsapp_gateway_error;
    return $last_whatsapp_gateway_error;
}

function normalisasi_nomor_whatsapp($nomor)
{
    $nomor = preg_replace('/[^0-9]/', '', (string) $nomor);

    if (strpos($nomor, '0') === 0) {
        return '62' . substr($nomor, 1);
    }

    return $nomor;
}

function kirim_whatsapp($conn, $id_user, $nomor, $pesan)
{
    global $wa_config;
    global $last_whatsapp_gateway_error;

    $last_whatsapp_gateway_error = null;
    $nomor = normalisasi_nomor_whatsapp($nomor);

    if (empty($nomor) || strlen($nomor) < 10) {
        $last_whatsapp_gateway_error = "Nomor WhatsApp tidak valid.";
        return false;
    }

    $nomor_db = mysqli_real_escape_string($conn, $nomor);
    $pesan_db = mysqli_real_escape_string($conn, $pesan);

    $id_user_sql = $id_user ? intval($id_user) : "NULL";

    $insert_log = mysqli_query($conn, "
        INSERT INTO whatsapp_log
        (id_user, tujuan_nomor, isi_pesan, status)
        VALUES
        ($id_user_sql, '$nomor_db', '$pesan_db', 'pending')
    ");

    if (!$insert_log) {
        $last_whatsapp_gateway_error = "Gagal mencatat WhatsApp log: " . mysqli_error($conn);
        return false;
    }

    $id_whatsapp_log = mysqli_insert_id($conn);

    $curl = curl_init();

    $curl_options = array(
        CURLOPT_URL => $wa_config['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => array(
            'target' => $nomor,
            'message' => $pesan,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $wa_config['token']
        ),
    );

    if (!empty($wa_config['ca_bundle']) && file_exists($wa_config['ca_bundle'])) {
        $curl_options[CURLOPT_CAINFO] = $wa_config['ca_bundle'];
    }

    curl_setopt_array($curl, $curl_options);

    $response = curl_exec($curl);

    $error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($error) {

        $response_db = mysqli_real_escape_string($conn, $error);
        $last_whatsapp_gateway_error = $error;

        mysqli_query($conn, "
            UPDATE whatsapp_log
            SET status = 'gagal',
                response = '$response_db'
            WHERE id_whatsapp_log = '$id_whatsapp_log'
        ");

        return false;

    } else {

        $response_db = mysqli_real_escape_string($conn, $response);
        $response_data = json_decode($response, true);
        $is_success = $http_code >= 200 && $http_code < 300;

        if (is_array($response_data)) {
            if (isset($response_data['status']) && $response_data['status'] === false) {
                $is_success = false;
            }

            if (isset($response_data['detail']) && stripos((string) $response_data['detail'], 'success') === false) {
                $is_success = false;
            }
        }

        if (!$is_success) {
            $last_whatsapp_gateway_error = $response ?: "HTTP status $http_code";

            mysqli_query($conn, "
                UPDATE whatsapp_log
                SET status = 'gagal',
                    response = '$response_db'
                WHERE id_whatsapp_log = '$id_whatsapp_log'
            ");

            return false;
        }

        mysqli_query($conn, "
            UPDATE whatsapp_log
            SET status = 'terkirim',
                sent_at = NOW(),
                response = '$response_db'
            WHERE id_whatsapp_log = '$id_whatsapp_log'
        ");

        return true;
    }
}
?>
