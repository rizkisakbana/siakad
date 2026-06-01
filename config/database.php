<?php
require_once __DIR__ . "/env.php";

$host = env_value("DB_HOST", "localhost");
$user = env_value("DB_USER", "root");
$pass = env_value("DB_PASS", "");
$db   = env_value("DB_NAME", "siakad_atitb");
$port = (int) env_value("DB_PORT", 3306);
$timezone = env_value("APP_TIMEZONE", "Asia/Jakarta");

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

date_default_timezone_set($timezone);

mysqli_set_charset($conn, "utf8mb4");

mysqli_query($conn, "SET time_zone = '+07:00'");

?>
