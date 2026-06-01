<?php
require_once __DIR__ . "/../includes/session.php";

kirim_header_no_cache();

$login_url = "/siakad-atitb/auth/login.php";
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$token = $_POST['csrf_token'] ?? '';

if ($request_method !== 'POST') {
    header("Location: $login_url");
    exit;
}

$session_token = $_SESSION['csrf_logout_token'] ?? '';
if (!hash_equals($session_token, $token)) {
    header("Location: $login_url?error=session");
    exit;
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_unset();
session_destroy();

header("Location: $login_url");
exit;
?>
