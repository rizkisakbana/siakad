<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/alert.php";
require_once "../../includes/internal_module_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Pusat Laporan";
$page_subtitle = "Halaman utama untuk mengakses laporan mahasiswa, dosen, KRS, nilai, dan presensi.";

$cards = [
    ['label' => 'Mahasiswa', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa"))],
    ['label' => 'Dosen', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM dosen"))],
    ['label' => 'KRS', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM krs"))],
    ['label' => 'Nilai', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM nilai"))],
];

$laporan = [
    [
        'judul' => 'Laporan Mahasiswa',
        'deskripsi' => 'Master data mahasiswa berdasarkan prodi, angkatan, semester, dan status.',
        'ikon' => 'fa-user-graduate',
        'warna' => 'bg-blue-100 text-blue-700',
        'url' => 'laporan_mahasiswa.php',
        'total' => internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa"),
    ],
    [
        'judul' => 'Laporan Dosen',
        'deskripsi' => 'Rekap dosen, homebase prodi, status dosen, dan pengajar kelas.',
        'ikon' => 'fa-chalkboard-user',
        'warna' => 'bg-indigo-100 text-indigo-700',
        'url' => 'laporan_dosen.php',
        'total' => internal_count($conn, "SELECT COUNT(*) total FROM dosen"),
    ],
    [
        'judul' => 'Laporan KRS',
        'deskripsi' => 'Rekap KRS mahasiswa, persetujuan, dan peserta kelas kuliah.',
        'ikon' => 'fa-clipboard-list',
        'warna' => 'bg-emerald-100 text-emerald-700',
        'url' => 'laporan_krs.php',
        'total' => internal_count($conn, "SELECT COUNT(*) total FROM krs"),
    ],
    [
        'judul' => 'Laporan Nilai',
        'deskripsi' => 'Rekap nilai perkuliahan, KHS, IPS, IPK, dan status publish.',
        'ikon' => 'fa-square-poll-vertical',
        'warna' => 'bg-amber-100 text-amber-700',
        'url' => 'laporan_nilai.php',
        'total' => internal_count($conn, "SELECT COUNT(*) total FROM nilai"),
    ],
    [
        'judul' => 'Laporan Presensi',
        'deskripsi' => 'Rekap sesi perkuliahan dan kehadiran mahasiswa.',
        'ikon' => 'fa-calendar-check',
        'warna' => 'bg-rose-100 text-rose-700',
        'url' => 'laporan_presensi.php',
        'total' => internal_count($conn, "SELECT COUNT(*) total FROM presensi_mahasiswa"),
    ],
];

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <!-- <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-800">Pusat Laporan</h2>
        <p class="text-sm text-slate-500 mt-1">
            Halaman utama untuk mengakses laporan mahasiswa, dosen, KRS, nilai, dan presensi.
        </p>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <?php foreach ($cards as $card): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500"><?= htmlspecialchars($card['label']); ?></p>
                <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars($card['value']); ?></h3>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($laporan as $item): ?>
            <a href="<?= htmlspecialchars($item['url']); ?>"
               class="block bg-white rounded-2xl shadow-lg border border-slate-100 p-5 hover:shadow-xl transition">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl <?= htmlspecialchars($item['warna']); ?> flex items-center justify-center">
                        <i class="fa-solid <?= htmlspecialchars($item['ikon']); ?>"></i>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold text-slate-800"><?= htmlspecialchars($item['judul']); ?></h3>
                            <span class="text-xs font-bold text-slate-500"><?= number_format((int)$item['total']); ?> data</span>
                        </div>

                        <p class="text-sm text-slate-500 mt-2">
                            <?= htmlspecialchars($item['deskripsi']); ?>
                        </p>

                        <div class="mt-4 text-sm font-semibold text-blue-700">
                            Buka laporan
                            <i class="fa-solid fa-arrow-right ml-1"></i>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
