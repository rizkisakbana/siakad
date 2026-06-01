<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Dashboard Admin";
$page_subtitle = "Ringkasan aktivitas akademik SIAKAD ATITB";

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM mahasiswa"))['total'] ?? 0;
$total_dosen = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM dosen"))['total'] ?? 0;
$total_prodi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM prodi"))['total'] ?? 0;
$total_mk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM mata_kuliah"))['total'] ?? 0;
$total_krs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM krs"))['total'] ?? 0;
$total_notifikasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifikasi"))['total'] ?? 0;

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <section class="mb-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
            <p class="text-sm text-blue-100 mb-2">Selamat Datang</p>
            <h1 class="text-2xl sm:text-3xl font-bold">
                <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Administrator'); ?>
            </h1>
            <p class="mt-3 text-blue-100 max-w-3xl">
                Kelola aktivitas akademik, data mahasiswa, dosen, kurikulum, KRS, nilai, dan layanan akademik secara terintegrasi.
            </p>
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Mahasiswa</p>
                    <h2 class="text-3xl font-bold text-blue-600 mt-2"><?= $total_mahasiswa; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-user-graduate text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Dosen</p>
                    <h2 class="text-3xl font-bold text-green-600 mt-2"><?= $total_dosen; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-chalkboard-teacher text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Program Studi</p>
                    <h2 class="text-3xl font-bold text-purple-600 mt-2"><?= $total_prodi; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-building-columns text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Mata Kuliah</p>
                    <h2 class="text-3xl font-bold text-orange-600 mt-2"><?= $total_mk; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center">
                    <i class="fa-solid fa-book text-xl"></i>
                </div>
            </div>
        </div>

    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

        <div class="xl:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Menu Cepat Akademik</h2>
                    <p class="text-sm text-slate-500">Akses modul utama SIAKAD.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <a href="mahasiswa/data_mahasiswa.php" class="p-5 rounded-2xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition">
                    <i class="fa-solid fa-user-graduate text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Data Mahasiswa</h3>
                    <p class="text-sm text-slate-500 mt-1">Kelola biodata dan status mahasiswa.</p>
                </a>

                <a href="dosen/data_dosen.php" class="p-5 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition">
                    <i class="fa-solid fa-chalkboard-teacher text-green-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Data Dosen</h3>
                    <p class="text-sm text-slate-500 mt-1">Kelola data dosen dan pengampu.</p>
                </a>

                <a href="prodi/data_prodi.php" class="p-5 rounded-2xl border border-slate-100 hover:border-purple-300 hover:bg-purple-50 transition">
                    <i class="fa-solid fa-building-columns text-purple-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Program Studi</h3>
                    <p class="text-sm text-slate-500 mt-1">Kelola prodi dan jenjang pendidikan.</p>
                </a>

                <a href="kurikulum/data_kurikulum.php" class="p-5 rounded-2xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-layer-group text-indigo-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Kurikulum</h3>
                    <p class="text-sm text-slate-500 mt-1">Kelola kurikulum dan struktur SKS.</p>
                </a>

                <a href="jadwal/data_jadwal.php" class="p-5 rounded-2xl border border-slate-100 hover:border-yellow-300 hover:bg-yellow-50 transition">
                    <i class="fa-solid fa-calendar-days text-yellow-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Jadwal Kuliah</h3>
                    <p class="text-sm text-slate-500 mt-1">Atur jadwal, ruangan, kelas, dan dosen.</p>
                </a>

                <a href="akademik/krs_mahasiswa.php" class="p-5 rounded-2xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50 transition">
                    <i class="fa-solid fa-clipboard-list text-sky-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">KRS Mahasiswa</h3>
                    <p class="text-sm text-slate-500 mt-1">Monitoring pengisian KRS mahasiswa.</p>
                </a>

            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Status Sistem</h2>

            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">KRS</span>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-green-100 text-green-700">Aktif</span>
                </div>

                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">Input Nilai</span>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-yellow-100 text-yellow-700">Berjalan</span>
                </div>

                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">Notifikasi</span>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-blue-100 text-blue-700"><?= $total_notifikasi; ?> Data</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Total KRS</span>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-100 text-indigo-700"><?= $total_krs; ?> Data</span>
                </div>
            </div>
        </div>

    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Integrasi Sistem</h2>

            <div class="space-y-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">Email Gateway</h3>
                        <p class="text-sm text-slate-500">Digunakan untuk notifikasi akun, akademik, dan pembayaran.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-green-100 text-green-700 flex items-center justify-center">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">WA Gateway</h3>
                        <p class="text-sm text-slate-500">Digunakan untuk reminder KRS, tagihan, nilai, dan jadwal.</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                        <i class="fa-solid fa-database"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">PDDIKTI / Neo Feeder</h3>
                        <p class="text-sm text-slate-500">Struktur data disiapkan untuk kebutuhan pelaporan akademik.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Informasi Aplikasi</h2>

            <div class="space-y-3 text-sm text-slate-600">
                <p><strong>Nama Sistem:</strong> SIAKAD ATITB Jakarta</p>
                <p><strong>Versi:</strong> 1.0.0</p>
                <p><strong>Teknologi:</strong> PHP Native, MySQL, Tailwind CSS, JavaScript</p>
                <p><strong>Status:</strong> Pengembangan Awal</p>
                <p><strong>Role Aktif:</strong> <?= htmlspecialchars(str_replace('_', ' ', $_SESSION['role'] ?? '-')); ?></p>
            </div>
        </div>

    </section>

</main>

<?php require_once "../includes/footer.php"; ?>