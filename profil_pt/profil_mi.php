<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Manajemen Informatika";
$page_subtitle = "Program Studi D3 Manajemen Informatika";
$prodi = get_prodi_by_keyword($conn, 'Manajemen Informatika');

$config = [
    'nama' => 'Manajemen Informatika',
    'jenjang' => 'Diploma 3',
    'icon' => 'fa-laptop-code',
    'intro' => 'Program Studi Manajemen Informatika merupakan program pendidikan vokasi bidang teknologi informasi yang menyiapkan mahasiswa agar mampu memahami, merancang, membangun, dan mengelola sistem informasi sesuai kebutuhan dunia usaha, dunia industri, dan masyarakat global.',
    'akreditasi' => 'Baik',
    'lembaga' => 'LAM INFOKOM',
    'akreditasi_text' => 'Akreditasi "Baik" LAM INFOKOM Nomor: 239/SK/LAM-INFOKOM/Ak.S/D3/VIII/2024.',
    'deskripsi' => 'Manajemen Informatika berfokus pada penguasaan sistem informasi, basis data, pemrograman, analisis proses bisnis, pengembangan aplikasi, serta pemanfaatan teknologi informasi untuk menciptakan solusi digital yang aplikatif. Program studi ini mendukung visi ATITB dalam menghasilkan lulusan bidang teknologi informasi yang mampu menciptakan solusi atas kebutuhan masyarakat global dan berguna bagi kemajuan ilmu pengetahuan dan teknologi komputer.',
    'prospek' => 'Lulusan Program Studi Manajemen Informatika memiliki peluang karier sebagai programmer junior, web developer, analis sistem, administrator basis data, staf IT, pengelola aplikasi bisnis, IT support, operator sistem informasi, technopreneur, dan wirausaha digital.',
    'kompetensi' => [
        'Menganalisis kebutuhan sistem informasi dan proses bisnis organisasi.',
        'Merancang dan membangun aplikasi berbasis web serta sistem informasi sederhana.',
        'Mengelola basis data untuk kebutuhan operasional dan pengambilan keputusan.',
        'Menerapkan pemrograman, dokumentasi sistem, dan pengujian aplikasi.',
        'Memanfaatkan teknologi informasi untuk mendukung kewirausahaan digital.'
    ],
    'nilai' => [
        ['fa-briefcase', 'Profesional', 'Mampu bekerja secara disiplin, bertanggung jawab, dan berorientasi pada kebutuhan industri.'],
        ['fa-code', 'Praktis', 'Mampu membangun solusi teknologi yang dapat langsung digunakan.'],
        ['fa-database', 'Tertib Data', 'Memahami pentingnya pengelolaan data yang akurat dan terstruktur.'],
        ['fa-rocket', 'Adaptif', 'Siap mengikuti perkembangan teknologi dan kebutuhan dunia kerja.'],
    ],
];

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";
?>

<main class="lg:ml-[270px] min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8 font-sans">

    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700 shadow-xl">
        <div class="p-6 sm:p-8 lg:p-10 text-white">
            <div class="max-w-5xl">
                <span class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-semibold">
                    Program Studi <?= profil_h($config['jenjang']); ?>
                </span>

                <div class="mt-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-800 shadow-lg">
                    <i class="fa-solid <?= profil_h($config['icon']); ?> text-3xl"></i>
                </div>

                <h1 class="mt-6 text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                    <?= profil_h($config['nama']); ?>
                </h1>

                <p class="mt-5 max-w-4xl text-sm sm:text-base leading-7 text-blue-50">
                    <?= profil_h($config['intro']); ?>
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-green-100 px-4 py-2 text-xs font-bold text-green-700">
                        Akreditasi <?= profil_h($config['akreditasi']); ?>
                    </span>
                    <span class="rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-white">
                        <?= profil_h($config['lembaga']); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 xl:grid-cols-[1.1fr_0.9fr] gap-6">
        <div class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Deskripsi Program Studi</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Profil Keilmuan Manajemen Informatika</h2>
            <p class="mt-4 text-sm sm:text-base leading-7 text-slate-600">
                <?= profil_h($config['deskripsi']); ?>
            </p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Akreditasi</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Status Mutu Program Studi</h2>
            <div class="mt-5 rounded-2xl bg-blue-50 border border-blue-100 p-5">
                <p class="text-3xl font-extrabold text-blue-800"><?= profil_h($config['akreditasi']); ?></p>
                <p class="mt-2 text-sm leading-6 text-blue-700">
                    <?= profil_h($config['akreditasi_text']); ?>
                </p>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Kompetensi Lulusan</p>
        <h2 class="mt-2 text-xl font-bold text-slate-900">Kemampuan Utama yang Dikembangkan</h2>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($config['kompetensi'] as $item): ?>
                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="text-sm leading-6 font-semibold text-slate-700">
                        <?= profil_h($item); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 xl:grid-cols-[0.9fr_1.1fr] gap-6">
        <div class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Prospek Karier</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Peluang Kerja Lulusan</h2>
            <p class="mt-4 text-sm leading-7 text-slate-600">
                <?= profil_h($config['prospek']); ?>
            </p>
        </div>

        <div class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Nilai Pembelajaran</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Karakter Lulusan</h2>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($config['nilai'] as $nilai): ?>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                            <i class="fa-solid <?= profil_h($nilai[0]); ?>"></i>
                        </div>
                        <h3 class="font-bold text-slate-900"><?= profil_h($nilai[1]); ?></h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500"><?= profil_h($nilai[2]); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($prodi): ?>
        <section class="mt-6 rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Data Program Studi</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Data Lokal SIAKAD / NeoFeeder</h2>

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-400 uppercase">Kode Prodi</p>
                    <p class="mt-2 font-bold text-slate-800"><?= profil_h(profil_text($prodi['kode_prodi'] ?? '')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-400 uppercase">Jenjang</p>
                    <p class="mt-2 font-bold text-slate-800"><?= profil_h(profil_text($prodi['jenjang'] ?? 'D3')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-400 uppercase">Status</p>
                    <p class="mt-2 font-bold text-slate-800"><?= profil_h(profil_text($prodi['status'] ?? 'aktif')); ?></p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-400 uppercase">ID Feeder</p>
                    <p class="mt-2 break-all font-bold text-slate-800"><?= profil_h(profil_text($prodi['id_feeder'] ?? '')); ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php require_once "../includes/footer.php"; ?>