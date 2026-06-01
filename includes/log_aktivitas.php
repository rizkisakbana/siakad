<?php

/*
|--------------------------------------------------------------------------
| FILE : includes/log_aktivitas.php
|--------------------------------------------------------------------------
| Fungsi:
| - Menyimpan aktivitas pengguna ke tabel log_aktivitas
| - Digunakan pada seluruh modul sistem
| - Mendukung audit trail sistem SIAKAD
|--------------------------------------------------------------------------
*/

if (!function_exists('simpan_log')) {

    function simpan_log($conn, $id_user, $aktivitas, $modul = null)
    {
        try {

            $id_user = $id_user ? intval($id_user) : "NULL";

            $aktivitas = mysqli_real_escape_string($conn, trim($aktivitas));

            $modul = $modul 
                ? mysqli_real_escape_string($conn, trim($modul))
                : NULL;

            $ip_address = mysqli_real_escape_string(
                $conn,
                $_SERVER['REMOTE_ADDR'] ?? ''
            );

            $user_agent = mysqli_real_escape_string(
                $conn,
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $query = "
                INSERT INTO log_aktivitas
                (
                    id_user,
                    aktivitas,
                    modul,
                    ip_address,
                    user_agent
                )
                VALUES
                (
                    $id_user,
                    '$aktivitas',
                    " . ($modul ? "'$modul'" : "NULL") . ",
                    '$ip_address',
                    '$user_agent'
                )
            ";

            return mysqli_query($conn, $query);

        } catch (Exception $e) {

            error_log(
                "[" . date('Y-m-d H:i:s') . "] LOG ERROR : " . 
                $e->getMessage() . PHP_EOL,
                3,
                __DIR__ . '/../logs/error.log'
            );

            return false;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fungsi mengambil aktivitas terbaru
|--------------------------------------------------------------------------
*/

if (!function_exists('get_log_aktivitas')) {

    function get_log_aktivitas($conn, $limit = 10)
    {
        $limit = intval($limit);

        $query = mysqli_query($conn, "
            SELECT 
                log_aktivitas.*,
                users.nama_lengkap
            FROM log_aktivitas
            LEFT JOIN users 
                ON log_aktivitas.id_user = users.id_user
            ORDER BY log_aktivitas.id_log DESC
            LIMIT $limit
        ");

        return $query;
    }
}

/*
|--------------------------------------------------------------------------
| Fungsi mengambil log berdasarkan user
|--------------------------------------------------------------------------
*/

if (!function_exists('get_log_by_user')) {

    function get_log_by_user($conn, $id_user, $limit = 20)
    {
        $id_user = intval($id_user);
        $limit = intval($limit);

        $query = mysqli_query($conn, "
            SELECT *
            FROM log_aktivitas
            WHERE id_user = '$id_user'
            ORDER BY id_log DESC
            LIMIT $limit
        ");

        return $query;
    }
}

/*
|--------------------------------------------------------------------------
| Fungsi hapus log lama
|--------------------------------------------------------------------------
| Digunakan untuk maintenance sistem
|--------------------------------------------------------------------------
*/

if (!function_exists('hapus_log_lama')) {

    function hapus_log_lama($conn, $hari = 365)
    {
        $hari = intval($hari);

        $query = "
            DELETE FROM log_aktivitas
            WHERE created_at < NOW() - INTERVAL $hari DAY
        ";

        return mysqli_query($conn, $query);
    }
}
?>