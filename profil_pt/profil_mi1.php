<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Manajemen Informatika";
$page_subtitle = "Informasi program studi vokasi bidang sistem informasi";
$prodi = get_prodi_by_keyword($conn, 'Manajemen Informatika');

$config = [
    'nama' => 'Manajemen Informatika',
    'jenjang' => 'D3',
    'icon' => 'fa-laptop-code',
    'intro' => 'Program Studi Manajemen Informatika menyiapkan lulusan vokasi yang memahami pengembangan sistem informasi, pengelolaan data, proses bisnis digital, dan pemanfaatan teknologi untuk kebutuhan organisasi.',
    'akreditasi' => 'Baik',
    'lembaga' => 'LAM INFOKOM',
    'akreditasi_text' => 'Akreditasi "Baik" LAM INFOKOM Nomor: 239/SK/LAM-INFOKOM/Ak.S/D3/VIII/2024.',
    'deskripsi' => 'Manajemen Informatika berfokus pada kemampuan merancang, membangun, mengelola, dan mengevaluasi sistem informasi. Mahasiswa diarahkan untuk memahami kebutuhan pengguna, proses bisnis, basis data, pemrograman, serta tata kelola teknologi informasi.',
    'prospek' => 'Lulusan dapat berkarier sebagai programmer junior, analis sistem, administrator basis data, staf IT, pengelola aplikasi bisnis, teknisi sistem informasi, maupun wirausaha digital.',
    'kompetensi' => [
        'Analisis kebutuhan sistem dan proses bisnis.',
        'Pemrograman aplikasi dan pengelolaan basis data.',
        'Pengembangan sistem informasi berbasis web.',
        'Dokumentasi, implementasi, dan dukungan layanan IT.'
    ],
    'nilai' => [
        ['fa-code', 'Praktis', 'Mampu membangun solusi teknologi yang langsung menjawab kebutuhan kerja.'],
        ['fa-diagram-project', 'Analitis', 'Memahami alur data, proses bisnis, dan kebutuhan pengguna.'],
        ['fa-database', 'Tertib Data', 'Menjaga kualitas data sebagai dasar pengambilan keputusan.'],
        ['fa-rocket', 'Adaptif', 'Siap mengikuti perkembangan teknologi dan kebutuhan industri.'],
    ],
];

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";

render_prodi_profile($prodi, $config);

require_once "../includes/footer.php";
?>
