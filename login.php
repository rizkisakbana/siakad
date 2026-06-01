<?php
require_once "includes/session.php";

kirim_header_no_cache();

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = "auth/login.php" . ($query !== '' ? '?' . $query : '');

header("Location: $target", true, 302);
exit;
?>
