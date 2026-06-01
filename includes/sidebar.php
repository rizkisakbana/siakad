<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
$current_page = $_SERVER['REQUEST_URI'] ?? '';

$base_url = '/siakad-atitb';

$jumlah_notifikasi = 0;

if (isset($conn) && isset($_SESSION['id_user'])) {
    $id_user_sidebar = intval($_SESSION['id_user']);

    $qNotif = mysqli_query($conn, "
        SELECT COUNT(*) AS total 
        FROM notifikasi 
        WHERE id_user = '$id_user_sidebar' 
        AND status_baca = 'belum'
    ");

    if ($qNotif) {
        $dNotif = mysqli_fetch_assoc($qNotif);
        $jumlah_notifikasi = $dNotif['total'] ?? 0;
    }
}

function menuAktif($path, $current_page)
{
    return strpos($current_page, $path) !== false;
}

function menuClass($path, $current_page)
{
    if (menuAktif($path, $current_page)) {
        return "flex items-center justify-between px-4 py-3 rounded-xl bg-blue-700 text-white font-semibold shadow";
    }

    return "flex items-center justify-between px-4 py-3 rounded-xl text-slate-700 hover:bg-blue-700 hover:text-white transition";
}

function iconText($icon, $text)
{
    return '
        <span class="flex items-center">
            <i class="' . $icon . ' w-5 mr-3 text-center"></i>
            <span>' . $text . '</span>
        </span>
    ';
}
?>

<div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/40 z-40 lg:hidden"></div>

<aside id="sidebar"
    class="fixed top-0 left-0 w-[270px] h-screen flex flex-col bg-white shadow-2xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="p-4 border-b border-slate-200">
        <div class="flex justify-center items-center">
            <img src="<?= $base_url; ?>/assets/img/logo-atitb.png" alt="Logo SIAKAD" class="h-16 w-auto object-contain">
        </div>

        <h1 class="text-center text-blue-800 font-bold text-lg mt-1">
            SIAKAD ATITB
        </h1>

        <p class="text-center text-slate-400 font-medium text-xs">
            Version 1.0
        </p>
    </div>

    <nav class="p-4 overflow-y-auto flex-1">
        <ul class="space-y-2">

            <?php if (in_array($role, ['super_admin', 'admin_akademik', 'admin_keuangan'])): ?>

                <li>
                    <a href="<?= $base_url; ?>/admin/dashboard.php"
                        class="<?= menuClass('/admin/dashboard.php', $current_page); ?>">
                        <?= iconText('fas fa-home', 'Dashboard'); ?>
                    </a>
                </li>

                <div class="mt-4 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Profil PT</div>

                <li>
                    <button type="button" onclick="toggleSubMenu('menu-profil-pt')"
                        class="w-full <?= menuClass('/profil_pt', $current_page); ?>">
                        <?= iconText('fas fa-university', 'Profil PT'); ?>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <ul id="menu-profil-pt" class="hidden pl-8 mt-1 space-y-1">
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_pt.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700"> 
                                <i class="fas fa-university mr-2"></i>Profil PT</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_mi.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-laptop mr-2"></i>Prodi MI</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_ka.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-calculator mr-2"></i>Prodi KA</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/pmb/data_pendaftar.php"
                        class="<?= menuClass('/admin/pmb/', $current_page); ?>">
                        <?= iconText('fas fa-user-plus', 'PMB'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/mahasiswa/data_mahasiswa.php"
                        class="<?= menuClass('/admin/mahasiswa/', $current_page); ?>">
                        <?= iconText('fas fa-user-graduate', 'Mahasiswa'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/dosen/data_dosen.php"
                        class="<?= menuClass('/admin/dosen/', $current_page); ?>">
                        <?= iconText('fas fa-chalkboard-teacher', 'Dosen'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/perkuliahan/data_kelas_kuliah.php"
                        class="<?= menuClass('/admin/perkuliahan/', $current_page); ?>">
                        <?= iconText('fas fa-chalkboard', 'Perkuliahan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/prodi/data_prodi.php"
                        class="<?= menuClass('/admin/prodi/', $current_page); ?>">
                        <?= iconText('fas fa-building-columns', 'Program Studi'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/kurikulum/data_kurikulum.php"
                        class="<?= menuClass('/admin/kurikulum/', $current_page); ?>">
                        <?= iconText('fas fa-layer-group', 'Kurikulum'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/matakuliah/data_matakuliah.php"
                        class="<?= menuClass('/admin/matakuliah/', $current_page); ?>">
                        <?= iconText('fas fa-book', 'Mata Kuliah'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/kelas/data_kelas.php"
                        class="<?= menuClass('/admin/kelas/', $current_page); ?>">
                        <?= iconText('fas fa-users-rectangle', 'Kelas'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/ruangan/data_ruangan.php"
                        class="<?= menuClass('/admin/ruangan/', $current_page); ?>">
                        <?= iconText('fas fa-door-open', 'Ruangan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/jadwal/data_jadwal.php"
                        class="<?= menuClass('/admin/jadwal/', $current_page); ?>">
                        <?= iconText('fas fa-calendar-days', 'Jadwal Kuliah'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/akademik/krs_mahasiswa.php"
                        class="<?= menuClass('/admin/akademik/krs', $current_page); ?>">
                        <?= iconText('fas fa-clipboard-list', 'KRS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/akademik/khs_mahasiswa.php"
                        class="<?= menuClass('/admin/akademik/khs', $current_page); ?>">
                        <?= iconText('fas fa-file-lines', 'KHS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/akademik/akm_mahasiswa.php"
                        class="<?= menuClass('/admin/akademik/akm', $current_page); ?>">
                        <?= iconText('fas fa-chart-line', 'AKM'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/akademik/transkrip_mahasiswa.php"
                        class="<?= menuClass('/admin/akademik/transkrip', $current_page); ?>">
                        <?= iconText('fas fa-file-signature', 'Transkrip'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/akademik/lulus_do_mahasiswa.php"
                        class="<?= menuClass('/admin/akademik/lulus_do', $current_page); ?>">
                        <?= iconText('fas fa-user-check', 'Lulus / DO'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/keuangan/tagihan_mahasiswa.php"
                        class="<?= menuClass('/admin/keuangan/', $current_page); ?>">
                        <?= iconText('fas fa-wallet', 'Keuangan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/tugas_akhir/data_ta.php"
                        class="<?= menuClass('/admin/tugas_akhir/', $current_page); ?>">
                        <?= iconText('fas fa-graduation-cap', 'Tugas Akhir'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/yudisium/data_yudisium.php"
                        class="<?= menuClass('/admin/yudisium/', $current_page); ?>">
                        <?= iconText('fas fa-award', 'Yudisium'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/wisuda/data_wisuda.php"
                        class="<?= menuClass('/admin/wisuda/', $current_page); ?>">
                        <?= iconText('fas fa-user-check', 'Wisuda'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/laporan/data_laporan.php"
                        class="<?= menuClass('/admin/laporan/', $current_page); ?>">
                        <?= iconText('fas fa-chart-bar', 'Laporan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/tahun_akademik/data_tahun.php"
                        class="<?= menuClass('/admin/tahun_akademik/', $current_page); ?>">
                        <?= iconText('fas fa-calendar-check', 'Tahun Akademik'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/pengguna/data_pengguna.php"
                        class="<?= menuClass('/admin/pengguna/', $current_page); ?>">
                        <?= iconText('fas fa-users-cog', 'Pengguna'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/log_aktivitas/data_aktivitas.php"
                        class="<?= menuClass('/admin/log_aktivitas/', $current_page); ?>">
                        <?= iconText('fas fa-clock-rotate-left', 'Log Aktivitas'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/profil/profil.php"
                        class="<?= menuClass('/admin/profil/', $current_page); ?>">
                        <?= iconText('fas fa-user', 'Profil Saya'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/neofeeder/data_pull.php"
                        class="<?= menuClass('/admin/neofeeder/', $current_page); ?>">
                        <?= iconText('fas fa-sync', 'NeoFeeder'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/admin/sinkronisasi/sinkronisasi.php"
                        class="<?= menuClass('/admin/sinkronisasi/', $current_page); ?>">
                        <?= iconText('fas fa-sync', 'Sinkronisasi'); ?>
                    </a>
                </li>

            <?php elseif ($role === 'mahasiswa'): ?>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/dashboard.php"
                        class="<?= menuClass('/mahasiswa/dashboard.php', $current_page); ?>">
                        <?= iconText('fas fa-home', 'Dashboard'); ?>
                    </a>
                </li>

                <li>
                    <button type="button" onclick="toggleSubMenu('menu-profil-pt')"
                        class="w-full <?= menuClass('/profil_pt', $current_page); ?>">
                        <?= iconText('fas fa-university', 'Profil PT'); ?>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <ul id="menu-profil-pt" class="hidden pl-8 mt-1 space-y-1">
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_pt.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700"> 
                                <i class="fas fa-university mr-2"></i>Profil PT</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_mi.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-laptop mr-2"></i>Prodi MI</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_ka.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-calculator mr-2"></i>Prodi KA</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/krs/isi_krs.php"
                        class="<?= menuClass('/mahasiswa/krs/', $current_page); ?>">
                        <?= iconText('fas fa-clipboard-list', 'KRS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/khs/data_khs.php"
                        class="<?= menuClass('/mahasiswa/khs/', $current_page); ?>">
                        <?= iconText('fas fa-file-lines', 'KHS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/transkrip/data_transkrip.php"
                        class="<?= menuClass('/mahasiswa/transkrip/', $current_page); ?>">
                        <?= iconText('fas fa-file-signature', 'Transkrip'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/jadwal/jadwal_kuliah.php"
                        class="<?= menuClass('/mahasiswa/jadwal/', $current_page); ?>">
                        <?= iconText('fas fa-calendar-days', 'Jadwal Kuliah'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/presensi/data_presensi.php"
                        class="<?= menuClass('/mahasiswa/presensi/', $current_page); ?>">
                        <?= iconText('fas fa-qrcode', 'Presensi'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/keuangan/tagihan.php"
                        class="<?= menuClass('/mahasiswa/keuangan/', $current_page); ?>">
                        <?= iconText('fas fa-wallet', 'Keuangan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/tugas_akhir/pengajuan_judul.php"
                        class="<?= menuClass('/mahasiswa/tugas_akhir/', $current_page); ?>">
                        <?= iconText('fas fa-graduation-cap', 'Tugas Akhir'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/mahasiswa/profil.php"
                        class="<?= menuClass('/mahasiswa/profil', $current_page); ?>">
                        <?= iconText('fas fa-user', 'Profil Saya'); ?>
                    </a>
                </li>

            <?php elseif ($role === 'dosen'): ?>

                <li>
                    <a href="<?= $base_url; ?>/dosen/dashboard.php"
                        class="<?= menuClass('/dosen/dashboard.php', $current_page); ?>">
                        <?= iconText('fas fa-home', 'Dashboard'); ?>
                    </a>
                </li>

                <li>
                    <button type="button" onclick="toggleSubMenu('menu-profil-pt')"
                        class="w-full <?= menuClass('/profil_pt', $current_page); ?>">
                        <?= iconText('fas fa-university', 'Profil PT'); ?>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <ul id="menu-profil-pt" class="hidden pl-8 mt-1 space-y-1">
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_pt.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700"> 
                                <i class="fas fa-university mr-2"></i>Profil PT</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_mi.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-laptop mr-2"></i>Prodi MI</a></li>
                        <li><a href="<?= $base_url; ?>/profil_pt/profil_ka.php"
                                class="block py-2 text-sm text-slate-600 hover:text-blue-700">
                                <i class="fas fa-calculator mr-2"></i>Prodi KA</a></li>
                    </ul>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/jadwal/jadwal_mengajar.php"
                        class="<?= menuClass('/dosen/jadwal/', $current_page); ?>">
                        <?= iconText('fas fa-calendar-days', 'Jadwal Mengajar'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/kelas/kelas_diampu.php"
                        class="<?= menuClass('/dosen/kelas/', $current_page); ?>">
                        <?= iconText('fas fa-users', 'Kelas Diampu'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/presensi/input_presensi.php"
                        class="<?= menuClass('/dosen/presensi/', $current_page); ?>">
                        <?= iconText('fas fa-user-check', 'Presensi'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/nilai/input_nilai.php"
                        class="<?= menuClass('/dosen/nilai/', $current_page); ?>">
                        <?= iconText('fas fa-pen-to-square', 'Input Nilai'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/krs/persetujuan_krs.php"
                        class="<?= menuClass('/dosen/krs/', $current_page); ?>">
                        <?= iconText('fas fa-clipboard-check', 'Persetujuan KRS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/tugas_akhir/mahasiswa_bimbingan.php"
                        class="<?= menuClass('/dosen/tugas_akhir/', $current_page); ?>">
                        <?= iconText('fas fa-graduation-cap', 'Bimbingan TA'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/dosen/profil.php" class="<?= menuClass('/dosen/profil', $current_page); ?>">
                        <?= iconText('fas fa-user', 'Profil Saya'); ?>
                    </a>
                </li>

            <?php elseif ($role === 'kaprodi'): ?>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/dashboard.php"
                        class="<?= menuClass('/kaprodi/dashboard.php', $current_page); ?>">
                        <?= iconText('fas fa-home', 'Dashboard'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/profil_pt/profil_pt.php"
                        class="<?= menuClass('/profil_pt/', $current_page); ?>">
                        <?= iconText('fas fa-university', 'Profil Kampus'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/monitoring_mahasiswa.php"
                        class="<?= menuClass('/kaprodi/monitoring_mahasiswa', $current_page); ?>">
                        <?= iconText('fas fa-user-graduate', 'Monitoring Mahasiswa'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/monitoring_dosen.php"
                        class="<?= menuClass('/kaprodi/monitoring_dosen', $current_page); ?>">
                        <?= iconText('fas fa-chalkboard-teacher', 'Monitoring Dosen'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/monitoring_krs.php"
                        class="<?= menuClass('/kaprodi/monitoring_krs', $current_page); ?>">
                        <?= iconText('fas fa-clipboard-list', 'Monitoring KRS'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/monitoring_nilai.php"
                        class="<?= menuClass('/kaprodi/monitoring_nilai', $current_page); ?>">
                        <?= iconText('fas fa-chart-line', 'Monitoring Nilai'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/kaprodi/laporan_prodi.php"
                        class="<?= menuClass('/kaprodi/laporan', $current_page); ?>">
                        <?= iconText('fas fa-file-chart-column', 'Laporan Prodi'); ?>
                    </a>
                </li>

            <?php elseif ($role === 'pimpinan'): ?>

                <li>
                    <a href="<?= $base_url; ?>/pimpinan/dashboard.php"
                        class="<?= menuClass('/pimpinan/dashboard.php', $current_page); ?>">
                        <?= iconText('fas fa-home', 'Dashboard'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/profil_pt/profil_pt.php"
                        class="<?= menuClass('/profil_pt/', $current_page); ?>">
                        <?= iconText('fas fa-university', 'Profil Kampus'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/pimpinan/statistik_akademik.php"
                        class="<?= menuClass('/pimpinan/statistik', $current_page); ?>">
                        <?= iconText('fas fa-chart-pie', 'Statistik Akademik'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/pimpinan/laporan_mahasiswa.php"
                        class="<?= menuClass('/pimpinan/laporan_mahasiswa', $current_page); ?>">
                        <?= iconText('fas fa-user-graduate', 'Laporan Mahasiswa'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/pimpinan/laporan_keuangan.php"
                        class="<?= menuClass('/pimpinan/laporan_keuangan', $current_page); ?>">
                        <?= iconText('fas fa-wallet', 'Laporan Keuangan'); ?>
                    </a>
                </li>

                <li>
                    <a href="<?= $base_url; ?>/pimpinan/laporan_kelulusan.php"
                        class="<?= menuClass('/pimpinan/laporan_kelulusan', $current_page); ?>">
                        <?= iconText('fas fa-award', 'Laporan Kelulusan'); ?>
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </nav>

    <div class="p-4 border-t border-slate-200">
        <form action="<?= $base_url; ?>/auth/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_logout_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit"
                class="w-full flex items-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold transition">
                <i class="fas fa-sign-out-alt w-5 mr-3 text-center"></i>
                Logout
            </button>
        </form>
    </div>
    <script>
        function toggleSubMenu(id) {
            document.getElementById(id).classList.toggle('hidden');
        }
    </script>
</aside>
