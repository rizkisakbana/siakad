<?php
require_once __DIR__ . "/session.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $page_title ?? 'SIAKAD ATITB'; ?></title>

    <link rel="icon" type="image/x-icon" href="/siakad-atitb/assets/img/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            overflow-x: hidden;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen">