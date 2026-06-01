<?php
/*
|--------------------------------------------------------------------------
| Neo Feeder Helper
|--------------------------------------------------------------------------
| Helper ini menjadi jembatan komunikasi antara SIAKAD dan Web Service
| Neo Feeder / PDDikti.
|--------------------------------------------------------------------------
*/

if (!function_exists('get_neofeeder_config')) {
    function get_neofeeder_config($conn)
    {
        $query = mysqli_query($conn, "
            SELECT *
            FROM neofeeder_config
            ORDER BY id_config DESC
            LIMIT 1
        ");

        if (!$query || mysqli_num_rows($query) < 1) {
            return null;
        }

        return mysqli_fetch_assoc($query);
    }
}

if (!function_exists('neofeeder_mask_text')) {
    function neofeeder_mask_text($value)
    {
        $value = (string) $value;

        $value = preg_replace('/("?(?:token|password)"?\s*:\s*")([^"]+)(")/i', '$1***MASKED***$3', $value);
        $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '***EMAIL_MASKED***', $value);
        $value = preg_replace('/\b\d{15,16}\b/', '***NIK_MASKED***', $value);
        $value = preg_replace('/\b(?:\+?62|0)8\d{7,12}\b/', '***PHONE_MASKED***', $value);

        return $value;
    }
}

if (!function_exists('neofeeder_mask_payload')) {
    function neofeeder_mask_payload($payload)
    {
        $sensitive_keys = [
            'password',
            'token',
            'nik',
            'email',
            'handphone',
            'no_hp',
            'nomor_hp',
            'telepon'
        ];

        if (is_array($payload)) {
            $masked = [];

            foreach ($payload as $key => $value) {
                $key_string = strtolower((string) $key);

                if (in_array($key_string, $sensitive_keys, true)) {
                    $masked[$key] = '***MASKED***';
                    continue;
                }

                $masked[$key] = neofeeder_mask_payload($value);
            }

            return $masked;
        }

        if (is_string($payload)) {
            return neofeeder_mask_text($payload);
        }

        return $payload;
    }
}

if (!function_exists('simpan_log_neofeeder')) {
    function simpan_log_neofeeder($conn, $modul, $aksi, $request_payload, $response_payload, $status, $pesan_error = null)
    {
        $http_code_value = null;
        $duration_ms_value = null;

        if (is_array($response_payload)) {
            $http_code_value = $response_payload['http_code'] ?? null;
            $duration_ms_value = $response_payload['duration_ms'] ?? null;
        }

        $modul = mysqli_real_escape_string($conn, $modul);
        $aksi = mysqli_real_escape_string($conn, $aksi);
        $request_payload = mysqli_real_escape_string($conn, json_encode(neofeeder_mask_payload($request_payload), JSON_UNESCAPED_UNICODE));
        $response_payload = mysqli_real_escape_string($conn, json_encode(neofeeder_mask_payload($response_payload), JSON_UNESCAPED_UNICODE));
        $status = mysqli_real_escape_string($conn, $status);
        $pesan_error = mysqli_real_escape_string($conn, neofeeder_mask_text($pesan_error ?? ''));
        $http_code = $http_code_value !== null ? (int) $http_code_value : null;
        $duration_ms = $duration_ms_value !== null ? (int) $duration_ms_value : null;

        $cek_tabel = mysqli_query($conn, "SHOW TABLES LIKE 'neofeeder_log'");

        if (!$cek_tabel || mysqli_num_rows($cek_tabel) < 1) {
            return false;
        }

        $columns = ['modul', 'aksi', 'request_payload', 'response_payload', 'status', 'pesan_error'];
        $values = ["'$modul'", "'$aksi'", "'$request_payload'", "'$response_payload'", "'$status'", "'$pesan_error'"];

        $cek_http_code = mysqli_query($conn, "SHOW COLUMNS FROM neofeeder_log LIKE 'http_code'");
        if ($cek_http_code && mysqli_num_rows($cek_http_code) > 0) {
            $columns[] = 'http_code';
            $values[] = $http_code !== null ? (string) $http_code : 'NULL';
        }

        $cek_duration = mysqli_query($conn, "SHOW COLUMNS FROM neofeeder_log LIKE 'duration_ms'");
        if ($cek_duration && mysqli_num_rows($cek_duration) > 0) {
            $columns[] = 'duration_ms';
            $values[] = $duration_ms !== null ? (string) $duration_ms : 'NULL';
        }

        return mysqli_query($conn, "
            INSERT INTO neofeeder_log (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $values) . ")
        ");
    }
}

if (!function_exists('neofeeder_call')) {
    function neofeeder_call($url_ws, $payload)
    {
        $started_at = microtime(true);
        $ch = curl_init($url_ws);
        $ssl_verify = function_exists('env_value') ? env_value('NEOFEEDER_SSL_VERIFY', false) : false;
        $ssl_verify = filter_var($ssl_verify, FILTER_VALIDATE_BOOLEAN);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => $ssl_verify,
            CURLOPT_SSL_VERIFYHOST => $ssl_verify ? 2 : 0
        ]);

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration_ms = (int) round((microtime(true) - $started_at) * 1000);

        curl_close($ch);

        if ($curl_error) {
            return [
                'success' => false,
                'http_code' => $http_code,
                'duration_ms' => $duration_ms,
                'error' => $curl_error,
                'raw' => null,
                'data' => null
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'success' => true,
            'http_code' => $http_code,
            'duration_ms' => $duration_ms,
            'error' => null,
            'raw' => $response,
            'data' => $decoded
        ];
    }
}

if (!function_exists('get_token')) {
    function get_token($conn, $force_refresh = false)
    {
        $config = get_neofeeder_config($conn);

        if (!$config) {
            return [
                'success' => false,
                'message' => 'Konfigurasi Neo Feeder belum tersedia.',
                'token' => null,
                'response' => null
            ];
        }

        if (!$force_refresh && !empty($config['token'])) {
            return [
                'success' => true,
                'message' => 'Token tersedia dari database.',
                'token' => $config['token'],
                'response' => null
            ];
        }

        $payload = [
            'act' => 'GetToken',
            'username' => $config['username'],
            'password' => $config['password']
        ];

        $result = neofeeder_call($config['url_ws'], $payload);

        if (!$result['success']) {
            simpan_log_neofeeder(
                $conn,
                'Token',
                'GetToken',
                $payload,
                $result,
                'failed',
                $result['error']
            );

            return [
                'success' => false,
                'message' => 'Gagal menghubungi Web Service Neo Feeder.',
                'token' => null,
                'response' => $result
            ];
        }

        $response = $result['data'];

        $error_code = $response['error_code'] ?? null;
        $error_desc = $response['error_desc'] ?? '';

        if ($error_code !== 0 && $error_code !== '0') {
            mysqli_query($conn, "
                UPDATE neofeeder_config SET
                    status = 'disconnected'
                WHERE id_config = '{$config['id_config']}'
            ");

            simpan_log_neofeeder(
                $conn,
                'Token',
                'GetToken',
                $payload,
                $response,
                'failed',
                $error_desc
            );

            return [
                'success' => false,
                'message' => $error_desc ?: 'Gagal mendapatkan token.',
                'token' => null,
                'response' => $response
            ];
        }

        $token = $response['data']['token'] ?? null;

        if (!$token) {
            simpan_log_neofeeder(
                $conn,
                'Token',
                'GetToken',
                $payload,
                $response,
                'failed',
                'Token tidak ditemukan pada response.'
            );

            return [
                'success' => false,
                'message' => 'Token tidak ditemukan pada response Neo Feeder.',
                'token' => null,
                'response' => $response
            ];
        }

        $token_db = mysqli_real_escape_string($conn, $token);

        mysqli_query($conn, "
            UPDATE neofeeder_config SET
                token = '$token_db',
                status = 'connected',
                last_connected_at = NOW()
            WHERE id_config = '{$config['id_config']}'
        ");

        simpan_log_neofeeder(
            $conn,
            'Token',
            'GetToken',
            $payload,
            $response,
            'success',
            null
        );

        return [
            'success' => true,
            'message' => 'Token berhasil didapatkan.',
            'token' => $token,
            'response' => $response
        ];
    }
}

if (!function_exists('neofeeder_request')) {
    function neofeeder_request($conn, $act, $filter = '', $order = '', $limit = '', $offset = '', $data = null, $modul = 'NeoFeeder')
    {
        $config = get_neofeeder_config($conn);

        if (!$config) {
            return [
                'success' => false,
                'message' => 'Konfigurasi Neo Feeder belum tersedia.',
                'response' => null
            ];
        }

        $token_result = get_token($conn);

        if (!$token_result['success']) {
            return [
                'success' => false,
                'message' => $token_result['message'],
                'response' => $token_result['response']
            ];
        }

        $payload = [
            'act' => $act,
            'token' => $token_result['token']
        ];

        if ($filter !== '') {
            $payload['filter'] = $filter;
        }

        if ($order !== '') {
            $payload['order'] = $order;
        }

        if ($limit !== '') {
            $payload['limit'] = $limit;
        }

        if ($offset !== '') {
            $payload['offset'] = $offset;
        }

        if ($data !== null) {
            $payload['record'] = $data;
        }

        $result = neofeeder_call($config['url_ws'], $payload);

        if (!$result['success']) {
            simpan_log_neofeeder(
                $conn,
                $modul,
                $act,
                $payload,
                $result,
                'failed',
                $result['error']
            );

            return [
                'success' => false,
                'message' => 'Gagal menghubungi Neo Feeder.',
                'response' => $result
            ];
        }

        $response = $result['data'];
        $error_code = $response['error_code'] ?? null;
        $error_desc = $response['error_desc'] ?? '';

        if ($error_code === '100' || $error_code === 100 || stripos($error_desc, 'token') !== false) {
            $refresh = get_token($conn, true);

            if ($refresh['success']) {
                $payload['token'] = $refresh['token'];
                $result = neofeeder_call($config['url_ws'], $payload);
                $response = $result['data'];
                $error_code = $response['error_code'] ?? null;
                $error_desc = $response['error_desc'] ?? '';
            }
        }

        if ($error_code !== 0 && $error_code !== '0') {
            simpan_log_neofeeder(
                $conn,
                $modul,
                $act,
                $payload,
                $response,
                'failed',
                $error_desc
            );

            return [
                'success' => false,
                'message' => $error_desc ?: 'Request Neo Feeder gagal.',
                'response' => $response
            ];
        }

        simpan_log_neofeeder(
            $conn,
            $modul,
            $act,
            $payload,
            $response,
            'success',
            null
        );

        return [
            'success' => true,
            'message' => 'Request Neo Feeder berhasil.',
            'response' => $response,
            'data' => $response['data'] ?? null
        ];
    }
}

if (!function_exists('cek_koneksi_feeder')) {
    function cek_koneksi_feeder($conn)
    {
        $token = get_token($conn, true);

        if (!$token['success']) {
            return [
                'success' => false,
                'message' => $token['message'],
                'response' => $token['response']
            ];
        }

        $profil = neofeeder_request(
            $conn,
            'GetProfilPT',
            '',
            '',
            1,
            0,
            null,
            'Profil PT'
        );

        if (!$profil['success']) {
            return [
                'success' => false,
                'message' => $profil['message'],
                'response' => $profil['response']
            ];
        }

        return [
            'success' => true,
            'message' => 'Koneksi Neo Feeder berhasil.',
            'token' => $token['token'],
            'profil' => $profil['data'],
            'response' => $profil['response']
        ];
    }
}

if (!function_exists('ambil_dictionary')) {
    function ambil_dictionary($conn)
    {
        return neofeeder_request(
            $conn,
            'GetDictionary',
            '',
            '',
            '',
            '',
            null,
            'Dictionary'
        );
    }
}

function feeder_date_to_mysql($date)
{
    $date = trim((string)$date);

    if (empty($date)) {
        return '';
    }

    $d = DateTime::createFromFormat('d-m-Y', $date);
    if ($d) {
        return $d->format('Y-m-d');
    }

    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d) {
        return $d->format('Y-m-d');
    }

    return '';
}
?>
