<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";
require_once "profil_helper.php";

cek_login();

$page_title = "Profil Komputerisasi Akuntansi";
$page_subtitle = "Program Studi D3 Komputerisasi Akuntansi";
$prodi = get_prodi_by_keyword($conn, 'Komputerisasi Akuntansi');

$config = [
    'nama' => 'Komputerisasi Akuntansi',
    'jenjang' => 'Diploma 3',
    'icon' => 'fa-calculator',
    'akreditasi' => 'Baik',
    'lembaga' => 'BAN-PT',
    'akreditasi_text' => 'Akreditasi "Baik" BAN-PT No. 1626/SK/BAN-PT/Akred/Dipl-III/III/2021.',
    'intro' => 'Program Studi Komputerisasi Akuntansi merupakan program pendidikan vokasi yang mengintegrasikan konsep akuntansi dengan teknologi informasi modern.',
    'deskripsi' => 'Komputerisasi Akuntansi adalah program studi yang mengintegrasikan konsep akuntansi tradisional dengan teknologi informasi modern. Mahasiswa dibekali pemahaman tentang sistem informasi akuntansi, penggunaan perangkat lunak akuntansi terkini, serta keterampilan analisis data keuangan untuk mendukung kebutuhan dunia kerja dan bisnis digital.',
    'prospek' => 'Lulusan Program Studi Komputerisasi Akuntansi memiliki peluang karier sebagai staf akuntansi, operator aplikasi akuntansi, finance administration, tax assistant, payroll staff, analis data keuangan junior, admin keuangan, pengelola sistem informasi akuntansi, serta wirausaha berbasis layanan akuntansi digital.',
    'kompetensi' => [
        'Memahami konsep akuntansi dan siklus pencatatan transaksi keuangan.',
        'Menggunakan perangkat lunak akuntansi dan aplikasi spreadsheet bisnis.',
        'Mengelola data transaksi keuangan secara teliti, akurat, dan terdokumentasi.',
        'Menyusun laporan keuangan untuk kebutuhan administrasi dan pengambilan keputusan.',
        'Menganalisis data keuangan menggunakan teknologi informasi modern.',
        'Menerapkan etika, ketelitian, dan integritas dalam pekerjaan akuntansi.'
    ],
    'nilai' => [
        ['fa-file-invoice-dollar', 'Teliti', 'Mampu mengelola transaksi dan laporan keuangan secara akurat.'],
        ['fa-chart-line', 'Analitis', 'Mampu membaca data keuangan untuk mendukung keputusan bisnis.'],
        ['fa-computer', 'Digital', 'Menguasai aplikasi dan sistem komputerisasi akuntansi.'],
        ['fa-shield-halved', 'Berintegritas', 'Menjaga kepercayaan, ketertiban dokumen, dan etika profesi.'],
    ],
];

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";
?>

<main class="lg:ml-[270px] min-h-screen bg-slate-50 p-4 sm:p-6 lg:p-8 font-sans">

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Profil Komputerisasi Akuntansi</h2>
            <p class="mt-1 text-sm text-slate-500">
                Informasi program studi vokasi bidang akuntansi digital dan sistem informasi akuntansi.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-2 text-xs font-semibold text-blue-700">
                <i class="fa-solid fa-graduation-cap mr-2"></i>
                Program Studi D3
            </span>
            <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-2 text-xs font-semibold text-green-700">
                <i class="fa-solid fa-circle-check mr-2"></i>
                Akreditasi <?= profil_h($config['akreditasi']); ?>
            </span>
        </div>
    </div>

    <section class="overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-xl">
        <div class="relative bg-gradient-to-br from-blue-950 via-blue-800 to-cyan-700 p-6 sm:p-8 lg:p-10 text-white">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white"></div>
                <div class="absolute -bottom-24 left-16 h-72 w-72 rounded-full bg-cyan-300"></div>
            </div>

            <div class="relative z-10 grid grid-cols-1 gap-8 xl:grid-cols-[1.35fr_0.75fr] xl:items-end">
                <div>
                    <div class="mb-5 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold text-white">
                            Akademi Teknik Informatika Tunas Bangsa Jakarta
                        </span>
                        <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-400/15 px-4 py-2 text-xs font-semibold text-emerald-100">
                            Siap Kerja - Siap Wirausaha
                        </span>
                    </div>

                    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-blue-800 shadow-lg">
                        <i class="fa-solid <?= profil_h($config['icon']); ?> text-3xl"></i>
                    </div>

                    <h1 class="max-w-4xl text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
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

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Jenjang</p>
                        <p class="mt-2 text-2xl font-extrabold"><?= profil_h($config['jenjang']); ?></p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Bidang</p>
                        <p class="mt-2 text-2xl font-extrabold">Akuntansi Digital</p>
                    </div>

                    <div class="rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Orientasi</p>
                        <p class="mt-2 text-2xl font-extrabold">Vokasi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 xl:grid-cols-[1.1fr_0.9fr] gap-6">
        <div class="rounded-2xl bg-white border border-slate-100 p-5 sm:p-6 shadow-lg">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Deskripsi Program Studi</p>
            <h2 class="mt-2 text-xl font-bold text-slate-900">Akuntansi Terapan Berbasis Teknologi</h2>
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