<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/kelas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Kelas";
$page_subtitle = "Informasi lengkap data kelas";

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$data = kelas_query_one($conn, "
    SELECT 
        kelas.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        prodi.gelar,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun,
        tahun_akademik.status AS status_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    WHERE kelas.id_kelas = '$id_kelas'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$total_mahasiswa = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas'
");

$total_jadwal = kelas_count($conn, "
    SELECT COUNT(*) AS total 
    FROM jadwal_kuliah 
    WHERE id_kelas = '$id_kelas'
");

$total_dosen = kelas_count($conn, "
    SELECT COUNT(DISTINCT id_dosen) AS total 
    FROM jadwal_kuliah 
    WHERE id_kelas = '$id_kelas'
");

$sisa_kapasitas = intval($data['kapasitas'] ?? 0) - intval($total_mahasiswa);
if ($sisa_kapasitas < 0) {
    $sisa_kapasitas = 0;
}

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail kelas: " . $data['nama_kelas'],
    "Kelas"
);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Kelas</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap kelas akademik.</p>
        </div>

        <a href="data_kelas.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Mahasiswa</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($total_mahasiswa); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sisa Kapasitas</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($sisa_kapasitas); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Jadwal Kuliah</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($total_jadwal); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Dosen Pengampu</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2">
                <?= number_format($total_dosen); ?>
            </h2>
        </div>

    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-users text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_kelas']); ?>
                    </h3>

                    <p class="text-sm text-slate-500">
                        <?= htmlspecialchars($data['kode_kelas'] ?? '-'); ?> • 
                        <?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?> • 
                        Semester <?= htmlspecialchars($data['semester'] ?? '-'); ?>
                    </p>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Kelas</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Kelas</p>
                    <p class="font-semibold text-slate-800"><?= $data['id_kelas']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kode Kelas</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['kode_kelas'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Nama Kelas</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_kelas']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Angkatan</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['angkatan'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Semester Kelas</p>
                    <p class="font-semibold text-slate-800">Semester <?= htmlspecialchars($data['semester'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kapasitas</p>
                    <p class="font-semibold text-slate-800"><?= number_format($data['kapasitas'] ?? 0); ?> Mahasiswa</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Terisi</p>
                    <p class="font-semibold text-slate-800"><?= number_format($total_mahasiswa); ?> Mahasiswa</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Sisa Kapasitas</p>
                    <p class="font-semibold text-slate-800"><?= number_format($sisa_kapasitas); ?> Mahasiswa</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status Kelas</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold 
                        <?= $data['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold text-slate-800">
                        <?= !empty($data['created_at']) ? tanggal_jam_indonesia($data['created_at']) : '-'; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Terakhir Diperbarui</p>
                    <p class="font-semibold text-slate-800">
                        <?= !empty($data['updated_at']) ? tanggal_jam_indonesia($data['updated_at']) : '-'; ?>
                    </p>
                </div>

            </div>

        </section>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Program Studi</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Kode Prodi</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['kode_prodi'] ?? '-'); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Nama Prodi</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Jenjang</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['jenjang'] ?? '-'); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Gelar</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['gelar'] ?? '-'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Tahun Akademik</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Tahun</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['tahun'] ?? '-'); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Semester</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['semester_tahun'] ?? '-'); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Status Tahun</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= htmlspecialchars($data['status_tahun'] ?? '-'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

                <div class="space-y-3">
                    <a href="edit_kelas.php?id=<?= $data['id_kelas']; ?>"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                        <i class="fa-solid fa-pen mr-2"></i>
                        Edit Kelas
                    </a>

                    <?php if ($total_mahasiswa < 1 && $total_jadwal < 1): ?>
                        <a href="hapus_kelas.php?id=<?= $data['id_kelas']; ?>"
                           onclick="return confirm('Yakin ingin menghapus data kelas ini?')"
                           class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                            <i class="fa-solid fa-trash mr-2"></i>
                            Hapus Kelas
                        </a>
                    <?php else: ?>
                        <button type="button"
                                onclick="alert('Kelas tidak dapat dihapus karena sudah digunakan pada data mahasiswa atau jadwal kuliah.')"
                                class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                            <i class="fa-solid fa-lock mr-2"></i>
                            Tidak Bisa Dihapus
                        </button>
                    <?php endif; ?>

                    <a href="data_kelas.php"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </div>

        </aside>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
