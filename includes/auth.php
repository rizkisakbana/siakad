<?php
require_once __DIR__ . "/session.php";

function cek_login()
{
    kirim_header_no_cache();

    if (!isset($_SESSION['id_user'])) {
        header("Location: /siakad-atitb/auth/login.php");
        exit;
    }
}

function cek_role($roles = [])
{
    if (!in_array($_SESSION['role'], $roles)) {
        echo "<script>
            alert('Akses ditolak!');
            window.location='/siakad-atitb/auth/login.php';
        </script>";
        exit;
    }
}
?>
