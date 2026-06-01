<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/helper.php";

cek_login();
cek_role(['mahasiswa']);

$page_title = "Dashboard Mahasiswa";
$page_subtitle = "Ringkasan aktivitas akademik dan layanan mahasiswa";

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

    if (in_array($status, ['aktif', 'disetujui', 'publish', 'hadir'])) {
        $class = "bg-green-100 text-green-700";
    } elseif (in_array($status, ['draft', 'diajukan', 'belum'])) {
        $class = "bg-yellow-100 text-yellow-700";
    } elseif (in_array($status, ['ditolak', 'gagal', 'nonaktif', 'alpha'])) {
        $class = "bg-red-100 text-red-700";
    } elseif (in_array($status, ['cuti', 'izin', 'sakit'])) {
        $class = "bg-blue-100 text-blue-700";
    }

    return '<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold ' . $class . '">' . htmlspecialchars($status ?: '-') . '</span>';
}

$q_mahasiswa = mysqli_query($conn, "
    SELECT mahasiswa.*, prodi.nama_prodi, prodi.jenjang, kelas.nama_kelas
    FROM mahasiswa
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    WHERE mahasiswa.id_user = '$id_user'
    LIMIT 1
");

$mahasiswa = ($q_mahasiswa && mysqli_num_rows($q_mahasiswa) > 0) ? mysqli_fetch_assoc($q_mahasiswa) : null;
$id_mahasiswa = intval($mahasiswa['id_mahasiswa'] ?? 0);

$total_krs = $id_mahasiswa > 0 ? dashboard_count($conn, "SELECT COUNT(*) AS total FROM krs WHERE id_mahasiswa = '$id_mahasiswa'") : 0;
$total_kelas = $id_mahasiswa > 0 ? dashboard_count($conn, "SELECT COUNT(*) AS total FROM peserta_kelas_kuliah WHERE id_mahasiswa = '$id_mahasiswa' AND status_peserta = 'aktif'") : 0;
$total_nilai = $id_mahasiswa > 0 ? dashboard_count($conn, "SELECT COUNT(*) AS total FROM nilai WHERE id_mahasiswa = '$id_mahasiswa' AND status_publish = 'publish'") : 0;
$total_notifikasi = dashboard_count($conn, "SELECT COUNT(*) AS total FROM notifikasi WHERE id_user = '$id_user' AND status_baca = 'belum'");

$krs_terakhir = null;
if ($id_mahasiswa > 0) {
    $q_krs = mysqli_query($conn, "
        SELECT krs.*, tahun_akademik.tahun, tahun_akademik.semester, tahun_akademik.nama_semester
        FROM krs
        LEFT JOIN tahun_akademik ON krs.id_tahun = tahun_akademik.id_tahun
        WHERE krs.id_mahasiswa = '$id_mahasiswa'
        ORDER BY krs.id_krs DESC
        LIMIT 1
    ");
    $krs_terakhir = ($q_krs && mysqli_num_rows($q_krs) > 0) ? mysqli_fetch_assoc($q_krs) : null;
}

$jadwal_hari_ini = false;
if ($id_mahasiswa > 0) {
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
    $jadwal_hari_ini = mysqli_query($conn, "
        SELECT jadwal_kuliah.*, mata_kuliah.kode_mk, mata_kuliah.nama_mk, ruangan.nama_ruangan, dosen.nama_dosen
        FROM peserta_kelas_kuliah
        INNER JOIN kelas_kuliah ON peserta_kelas_kuliah.id_kelas_kuliah = kelas_kuliah.id_kelas_kuliah
        INNER JOIN jadwal_kuliah ON kelas_kuliah.id_jadwal = jadwal_kuliah.id_jadwal
        INNER JOIN mata_kuliah ON jadwal_kuliah.id_mk = mata_kuliah.id_mk
        LEFT JOIN ruangan ON jadwal_kuliah.id_ruangan = ruangan.id_ruangan
        LEFT JOIN dosen ON jadwal_kuliah.id_dosen = dosen.id_dosen
        WHERE peserta_kelas_kuliah.id_mahasiswa = '$id_mahasiswa'
        AND peserta_kelas_kuliah.status_peserta = 'aktif'
        AND jadwal_kuliah.hari = '$hari_ini'
        ORDER BY jadwal_kuliah.jam_mulai ASC
        LIMIT 5
    ");
}

$nilai_terbaru = false;
if ($id_mahasiswa > 0) {
    $nilai_terbaru = mysqli_query($conn, "
        SELECT nilai.*, mata_kuliah.kode_mk, mata_kuliah.nama_mk
        FROM nilai
        LEFT JOIN mata_kuliah ON nilai.id_matkul = mata_kuliah.id_mk
        WHERE nilai.id_mahasiswa = '$id_mahasiswa'
        ORDER BY nilai.updated_at DESC, nilai.id_nilai DESC
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
                <?= htmlspecialchars($mahasiswa['nama_mahasiswa'] ?? ($_SESSION['nama_lengkap'] ?? 'Mahasiswa')); ?>
            </h1>
            <p class="mt-3 text-blue-100 max-w-3xl">
                Pantau KRS, jadwal kuliah, nilai, presensi, dan informasi akademik pribadi melalui satu dashboard.
            </p>
        </div>
    </section>

    <?php if (!$mahasiswa): ?>
        <section class="mb-8 bg-yellow-50 border border-yellow-200 rounded-2xl p-5 text-yellow-800">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                <div>
                    <h2 class="font-bold">Profil mahasiswa belum terhubung</h2>
                    <p class="text-sm mt-1">Akun Anda belum memiliki data mahasiswa pada database. Silakan hubungi admin akademik.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Semester</p>
                    <h2 class="text-3xl font-bold text-blue-600 mt-2"><?= htmlspecialchars($mahasiswa['semester'] ?? '-'); ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-layer-group text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Kelas Diambil</p>
                    <h2 class="text-3xl font-bold text-green-600 mt-2"><?= $total_kelas; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-chalkboard text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:shadow-xl transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Riwayat KRS</p>
                    <h2 class="text-3xl font-bold text-purple-600 mt-2"><?= $total_krs; ?></h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-list text-xl"></i>
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
                <h2 class="text-xl font-bold text-slate-800">Menu Cepat Mahasiswa</h2>
                <p class="text-sm text-slate-500">Akses layanan akademik utama.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="krs/isi_krs.php" class="p-5 rounded-2xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition">
                    <i class="fa-solid fa-clipboard-list text-blue-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">KRS</h3>
                    <p class="text-sm text-slate-500 mt-1">Isi dan pantau rencana studi.</p>
                </a>
                <a href="khs/data_khs.php" class="p-5 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition">
                    <i class="fa-solid fa-file-lines text-green-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">KHS</h3>
                    <p class="text-sm text-slate-500 mt-1">Lihat hasil studi semester.</p>
                </a>
                <a href="jadwal/jadwal_kuliah.php" class="p-5 rounded-2xl border border-slate-100 hover:border-purple-300 hover:bg-purple-50 transition">
                    <i class="fa-solid fa-calendar-days text-purple-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Jadwal</h3>
                    <p class="text-sm text-slate-500 mt-1">Pantau jadwal kuliah aktif.</p>
                </a>
                <a href="presensi/data_presensi.php" class="p-5 rounded-2xl border border-slate-100 hover:border-indigo-300 hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-qrcode text-indigo-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Presensi</h3>
                    <p class="text-sm text-slate-500 mt-1">Lihat riwayat kehadiran.</p>
                </a>
                <a href="transkrip/data_transkrip.php" class="p-5 rounded-2xl border border-slate-100 hover:border-yellow-300 hover:bg-yellow-50 transition">
                    <i class="fa-solid fa-file-signature text-yellow-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Transkrip</h3>
                    <p class="text-sm text-slate-500 mt-1">Ringkasan nilai akademik.</p>
                </a>
                <a href="notifikasi/data_notifikasi.php" class="p-5 rounded-2xl border border-slate-100 hover:border-sky-300 hover:bg-sky-50 transition">
                    <i class="fa-solid fa-bell text-sky-600 text-2xl mb-3"></i>
                    <h3 class="font-bold text-slate-800">Notifikasi</h3>
                    <p class="text-sm text-slate-500 mt-1">Informasi terbaru dari sistem.</p>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Profil Akademik</h2>
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">NIM</span>
                    <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($mahasiswa['nim'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b gap-4">
                    <span class="text-sm text-slate-600">Program Studi</span>
                    <span class="text-sm font-bold text-slate-800 text-right"><?= htmlspecialchars($mahasiswa['nama_prodi'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b">
                    <span class="text-sm text-slate-600">Angkatan</span>
                    <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($mahasiswa['angkatan'] ?? '-'); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-600">Status</span>
                    <?= dashboard_badge($mahasiswa['status_mahasiswa'] ?? '-'); ?>
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
                                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($jadwal['nama_dosen'] ?? '-'); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Tidak ada jadwal kuliah hari ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">Nilai Terbaru</h2>
            <div class="space-y-3">
                <?php if ($nilai_terbaru && mysqli_num_rows($nilai_terbaru) > 0): ?>
                    <?php while ($nilai = mysqli_fetch_assoc($nilai_terbaru)): ?>
                        <div class="flex items-center justify-between gap-4 pb-3 border-b last:border-b-0">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 truncate"><?= htmlspecialchars($nilai['nama_mk'] ?? '-'); ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($nilai['kode_mk'] ?? '-'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-blue-700"><?= htmlspecialchars($nilai['nilai_huruf'] ?? '-'); ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($nilai['status_publish'] ?? '-'); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Belum ada nilai yang ditampilkan.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php require_once "../includes/footer.php"; ?>
