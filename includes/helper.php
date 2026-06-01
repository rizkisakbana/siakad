<?php
function bersihkan_input($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

function format_tanggal($tanggal)
{
    return date('d-m-Y', strtotime($tanggal));
}

function format_rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

function tanggal_indonesia($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($datetime);

    $tanggal = date('d', $timestamp);
    $bulan_index = (int) date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    return $tanggal . ' ' . $bulan[$bulan_index] . ' ' . $tahun;
}

function jam_indonesia($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    return date('H:i', strtotime($datetime));
}

function tanggal_jam_indonesia($datetime)
{
    if (empty($datetime)) {
        return '-';
    }

    return tanggal_indonesia($datetime) . ' • ' . jam_indonesia($datetime);
}

?>