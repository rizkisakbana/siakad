<?php
function simpan_log($conn, $id_user, $aktivitas, $modul = null)
{
    $id_user = $id_user ? intval($id_user) : "NULL";
    $aktivitas = mysqli_real_escape_string($conn, $aktivitas);
    $modul = mysqli_real_escape_string($conn, $modul);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $ip_address = mysqli_real_escape_string($conn, $ip_address);
    $user_agent = mysqli_real_escape_string($conn, $user_agent);

    $query = "
        INSERT INTO log_aktivitas 
        (id_user, aktivitas, modul, ip_address, user_agent)
        VALUES 
        ($id_user, '$aktivitas', '$modul', '$ip_address', '$user_agent')
    ";

    return mysqli_query($conn, $query);
}
?>