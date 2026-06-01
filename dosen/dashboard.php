<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";

cek_login();
cek_role(['dosen']);

$page_title = "Dashboard Dosen";
$page_subtitle = "Ringkasan aktivitas mengajar dan layanan dosen";

$id_user = intval($_SESSION['id_user'] ?? 0);

function dashboard_count($conn, $sql)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

function dashboard_badge($status)
{
    $status = strtolower(trim((string) $status));
    $class = "bg-slate-100 text-slate-700";

    if (in_array($status, ['aktif', 'tetap', 'publish', 'sudah'])) {
        $class = "bg-green-100 text-green-700";
    } elseif (in_array($status, ['belum', 'draft', 'honorer'])) {
        $class = "bg-yellow-100 text-yellow-700";
    } elseif (in_array($status, ['gagal', 'nonaktif'])) {
        $class = "bg-red-100 text-red-700";
    } elseif (in_array($status, ['tidak tetap'])) {
        $class = "bg-blue-100 text-blue-700";
    }

    return '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold ' . $class . '">' . htmlspecialchars($status ?: '-') . '</span>';
}

$q_dosen = mysqli_query($conn, "
    SELECT dosen.*, prodi.nama_prodi, prodi.jenjang
    FROM dosen
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    WHERE dosen.id_user = '$id_user'
    LIMIT 1
");

$dosen = ($q_dosen && mysqli_num_rows($q_dosen) > 0) ? mysqli_fetch_assoc($q_dosen) : null;
$id_dosen = intval($dosen['id_dosen'] ?? 0);

$total_kelas = $id_dosen > 0 ? dashboard_count($conn, "SELECT COUNT(DISTINCT id_kelas_kuliah) AS total FROM dosen_pengajar_kelas WHERE id_dosen = '$id_dosen'") : 0;
$total_jadwal = $id_dosen > 0 ? dashboard_count($conn, "SELECT COUNT(*) AS total FROM jadwal_kuliah WHERE id_dosen = '$id_dosen' AND status = 'aktif'") : 0;
$total_mahasiswa = $id_dosen > 0 ? dashboard_count($conn, "
    SELECT COUNT(DISTINCT peserta_kelas_kuliah.id_mahasiswa) AS total
    FROM dosen_pengajar_kelas
    INNER JOIN peserta_kelas_kuliah ON dosen_pengajar_kelas.id_kelas_kuliah = peserta_kelas_kuliah.id_kelas_kuliah
    WHERE dosen_pengajar_kelas.id_dosen = '$id_dosen'
    AND peserta_kelas_kuliah.status_peserta = 'aktif'
") : 0;
$total_notifikasi = dashboard_count($conn, "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user = '$id_user' AND status_baca = 'belum'");

$hari_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu',
];
$hari_ini = $hari_map[date('l')] ?? 'Senin';

$jadwal_hari_ini = false;
if ($id_dosen > 0) {
    $jadwal_hari_ini = mysqli_query($conn, "
        SELECT jadwal_kuliah.*, mata_kuliah.kode_mk, mata_kuliah.nama_mk, ruangan.nama_ruangan, kelas.nama_kelas
        FROM jadwal_kuliah
        INNER JOIN mata_kuliah ON jadwal_kuliah.id_mk = mata_kuliah.id_mk
        LEFT JOIN ruangan ON jadwal_kuliah.id_ruangan = ruangan.id_ruangan
        LEFT JOIN kelas ON jadwal_kuliah.id_kelas = kelas.id_kelas
        WHERE jadwal_kuliah.id_dosen = '$id_dosen'
        AND jadwal_kuliah.hari = '$hari_ini'
        AND jadwal_kuliah.status = 'aktif'
        ORDER BY jadwal_kuliah.jam_mulai ASC
        LIMIT 5
    ");
}

$kelas_diampu = false;
if ($id_dosen > 0) {
    $kelas_diampu = mysqli_query($conn, "
        SELECT kelas_kuliah.*, mata_kuliah.kode_mk, mata_kuliah.nama_mk, tahun_akademik.nama_semester,
               COUNT(peserta_kelas_kuliah.id_peserta_kelas) AS jumlah_peserta
        FROM dosen_pengajar_kelas
        INNER JOIN kelas_kuliah ON dosen_pengajar_kelas.id_kelas_kuliah = kelas_kuliah.id_kelas_kuliah
        LEFT JOIN mata_kuliah ON kelas_kuliah.id_mk = mata_kuliah.id_mk
        LEFT JOIN tahun_akademik ON kelas_kuliah.id_tahun = tahun_akademik.id_tahun
        LEFT JOIN peserta_kelas_kuliah ON kelas_kuliah.id_kelas_kuliah = peserta_kelas_kuliah.id_kelas_kuliah
        WHERE dosen_pengajar_kelas.id_dosen = '$id_dosen'
        GROUP BY kelas_kuliah.id_kelas_kuliah
        ORDER BY kelas_kuliah.id_kelas_kuliah DESC
        LIMIT 5
    ");
}

require_once "../includes/header.php";
require_once "../includes/sidebar.php";
require_once "../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <section class="mb-8">
        <div class="bg-gradient-to-r from-blue-700 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
            <p class="text-sm text-blue-100 mb-2">Selamat Datang</p>
            <h1 class="text-2xl sm:text-3xl font-bold">
                <?= htmlspecialchars($dosen['nama_dosen'] ?? ($_SESSION['nama_lengkap'] ?? 'Dosen')); ?>
            </h1>
            <p class="mt-3 text-blue-100 max-w-3xl">
                Kelola jadwal mengajar, kelas yang diampu, presensi, nilai, dan informasi akademik dosen.
            </p>
        </div>
    </section>

    <?php if (!$dosen): ?>
        <section class="mb-8 bg-yellow-50 border border-yellow-200 rounded-2xl p-5 text-yellow-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div>
                    <h2 class="font-bold">Profil dosen belum terhubung</h2>
                    <p class="text-sm mt-1">Akun Anda belum memiliki data dosen pada database. Silakan hubungi admin akademik.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Kelas Diampu</p>
                    <h2 class="text-3xl font-bold text-blue-600 mt-2"><?= $total_kelas; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Jadwal Aktif</p>
                    <h2 class="text-3xl font-bold text-green-600 mt-2"><?= $total_jadwal; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-days text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Mahasiswa</p>
                    <h2 class="text-3xl font-bold text-purple-600 mt-2"><?= $total_mahasiswa; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-user-graduate text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Notifikasi Baru</p>
                    <h2 class="text-3xl font-bold text-orange-600 mt-2"><?= $total_notifikasi; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center">
                    <i class="fa-solid fa-bell text-xl"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-slate-800">Menu Cepat Dosen</h2>
                <p class="text-sm text-slate-500">Akses aktivitas mengajar utama.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="jadwal/jadwal_mengajar.php" class="p-5 rounded-2xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition">
                    <i class="fa-solid fa-calendar-days text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Jadwal Mengajar</h3>
                    <p class="text-sm text-slate-500 mt-1">Lihat jadwal dan ruangan.</p>
                </a>
                <a href="kelas/kelas_diampu.php" class="p-5 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition">
                    <i class="fa-solid fa-users text-green-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Kelas Diampu</h3>
                    <p class="text-sm text-slate-500 mt-1">Pantau peserta kelas.</p>
                </a>
                <a href="presensi/input_presensi.php" class="p-5 rounded-2xl border border-slate-100 hover:border-purple-300 hover:bg-purple-50 transition">
                    <i class="fa-solid fa-user-check text-purple-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Presensi</h3>
                    <p class="text-sm text-slate-500 mt-1">Input kehadiran mahasiswa.</p>
                </a>
                <a href="nilai/input_nilai.php" class="p-5 rounded-2xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-pen-to-square text-indigo-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Input Nilai</h3>
                    <p class="text-sm text-slate-500 mt-1">Kelola nilai perkuliahan.</p>
                </a>
                <a href="krs/persetujuan_krs.php" class="p-5 rounded-2xl border border-slate-100 hover:border-yellow-300 hover:bg-yellow-50 transition">
                    <i class="fa-solid fa-clipboard-check text-yellow-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Persetujuan KRS</h3>
                    <p class="text-sm text-slate-500 mt-1">Validasi rencana studi.</p>
                </a>
                <a href="notifikasi/data_notifikasi.php" class="p-5 rounded-2xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50 transition">
                    <i class="fa-solid fa-bell text-sky-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Notifikasi</h3>
                    <p class="text-sm text-slate-500 mt-1">Informasi terbaru sistem.</p>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Profil Dosen</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">NIDN</span>
                    <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($dosen['nidn'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b gap-4">
                    <span class="text-sm text-slate-600">Program Studi</span>
                    <span class="text-sm font-bold text-slate-800 text-right"><?= htmlspecialchars($dosen['nama_prodi'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">Ikatan Kerja</span>
                    <span class="text-sm font-bold text-slate-800 text-right"><?= htmlspecialchars($dosen['nama_ikatan_kerja'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Status</span>
                    <?= dashboard_badge($dosen['status_dosen'] ?? '-'); ?>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Jadwal Hari Ini</h2>
            <div class="space-y-4">
                <?php if ($jadwal_hari_ini && mysqli_num_rows($jadwal_hari_ini) > 0): ?>
                    <?php while ($jadwal = mysqli_fetch_assoc($jadwal_hari_ini)): ?>
                        <div class="flex items-start gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-800"><?= htmlspecialchars($jadwal['nama_mk'] ?? '-'); ?></h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    <?= htmlspecialchars(substr($jadwal['jam_mulai'] ?? '', 0, 5)); ?> - <?= htmlspecialchars(substr($jadwal['jam_selesai'] ?? '', 0, 5)); ?>
                                    · <?= htmlspecialchars($jadwal['nama_ruangan'] ?? '-'); ?>
                                </p>
                                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($jadwal['nama_kelas'] ?? '-'); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Tidak ada jadwal mengajar hari ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Kelas Terbaru</h2>
            <div class="space-y-3">
                <?php if ($kelas_diampu && mysqli_num_rows($kelas_diampu) > 0): ?>
                    <?php while ($kelas = mysqli_fetch_assoc($kelas_diampu)): ?>
                        <div class="flex items-center justify-between gap-4 pb-3 border-b last:border-b-0">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 truncate"><?= htmlspecialchars($kelas['nama_mk'] ?? '-'); ?></p>
                                <p class="text-xs text-slate-500">
                                    <?= htmlspecialchars($kelas['nama_kelas_kuliah'] ?? '-'); ?> · <?= htmlspecialchars($kelas['nama_semester'] ?? '-'); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-blue-700"><?= (int) ($kelas['jumlah_peserta'] ?? 0); ?></p>
                                <p class="text-xs text-slate-500">peserta</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Belum ada kelas yang terhubung.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
