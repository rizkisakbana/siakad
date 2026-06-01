<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function set_alert($tipe, $pesan)
{
    $_SESSION['alert'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

function show_alert()
{
    if (isset($_SESSION['alert'])) {
        $tipe = $_SESSION['alert']['tipe'];
        $pesan = $_SESSION['alert']['pesan'];

        $class = "bg-blue-100 text-blue-700 border-blue-200";
        $icon = "fa-circle-info";

        if ($tipe == "success") {
            $class = "bg-green-100 text-green-700 border-green-200";
            $icon = "fa-circle-check";
        } elseif ($tipe == "error") {
            $class = "bg-red-100 text-red-700 border-red-200";
            $icon = "fa-circle-xmark";
        } elseif ($tipe == "warning") {
            $class = "bg-yellow-100 text-yellow-700 border-yellow-200";
            $icon = "fa-triangle-exclamation";
        }

        echo '
        <div class="mb-5 rounded-xl border px-4 py-3 text-sm font-semibold ' . $class . '">
            <div class="flex items-start gap-3">
                <i class="fa-solid ' . $icon . ' mt-0.5"></i>
                <span>' . htmlspecialchars($pesan) . '</span>
            </div>
        </div>
        ';

        unset($_SESSION['alert']);
    }
}
?>