<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Komputerisasi Akuntansi";
$page_subtitle = "Informasi program studi vokasi bidang akuntansi digital";
$prodi = get_prodi_by_keyword($conn, 'Komputerisasi Akuntansi');

$config = [
    'nama' => 'Komputerisasi Akuntansi',
    'jenjang' => 'D3',
    'icon' => 'fa-calculator',
    'intro' => 'Program Studi Komputerisasi Akuntansi menyiapkan lulusan vokasi yang menguasai akuntansi terapan, teknologi informasi, dan sistem pengelolaan keuangan berbasis komputer.',
    'akreditasi' => 'Baik',
    'lembaga' => 'BAN-PT',
    'akreditasi_text' => 'Akreditasi "Baik" BAN-PT No. 1626/SK/BAN-PT/Akred/Dipl-III/III/2021.',
    'deskripsi' => 'Komputerisasi Akuntansi menggabungkan kompetensi akuntansi, administrasi bisnis, aplikasi komputer, dan pengolahan data keuangan. Mahasiswa diarahkan agar mampu menyusun laporan keuangan, memahami siklus akuntansi, serta menggunakan teknologi untuk meningkatkan akurasi dan efisiensi kerja.',
    'prospek' => 'Lulusan dapat berkarier sebagai staf akuntansi, operator aplikasi akuntansi, finance administration, tax assistant, payroll staff, analis data keuangan junior, maupun wirausaha berbasis layanan akuntansi digital.',
    'kompetensi' => [
        'Penyusunan laporan keuangan dan administrasi akuntansi.',
        'Penggunaan aplikasi akuntansi dan spreadsheet bisnis.',
        'Pengolahan data transaksi secara akurat dan efisien.',
        'Pemahaman kontrol internal dan pelaporan keuangan.'
    ],
    'nilai' => [
        ['fa-file-invoice-dollar', 'Teliti', 'Mampu mengelola transaksi dan laporan keuangan secara akurat.'],
        ['fa-chart-line', 'Terukur', 'Menggunakan data untuk membaca kondisi dan kinerja keuangan.'],
        ['fa-computer', 'Digital', 'Memanfaatkan aplikasi dan teknologi untuk efisiensi pekerjaan akuntansi.'],
        ['fa-shield-halved', 'Berintegritas', 'Menjaga kepercayaan, ketertiban dokumen, dan etika profesi.'],
    ],
];

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";

render_prodi_profile($prodi, $config);

require_once "../includes/footer.php";
?>
