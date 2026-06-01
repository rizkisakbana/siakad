<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Tahun Akademik";
$page_subtitle = "Informasi lengkap periode tahun akademik";

$id_tahun = intval($_GET['id'] ?? 0);

if ($id_tahun <= 0) {
    set_alert("error", "ID tahun akademik tidak valid.");
    header("Location: data_tahun.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM tahun_akademik WHERE id_tahun='$id_tahun' LIMIT 1");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data tahun akademik tidak ditemukan.");
    header("Location: data_tahun.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM kelas WHERE id_tahun='$id_tahun'"))['total'] ?? 0;
$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM jadwal_kuliah WHERE id_tahun='$id_tahun'"))['total'] ?? 0;
$total_krs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM krs WHERE id_tahun='$id_tahun'"))['total'] ?? 0;

simpan_log($conn, $_SESSION['id_user'], "Melihat detail tahun akademik: " . $data['tahun'] . " - " . $data['semester'], "Tahun Akademik");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Tahun Akademik</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi periode akademik.</p>
        </div>

        <a href="data_tahun.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-days text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['tahun']); ?> - <?= htmlspecialchars($data['semester']); ?>
                    </h3>
                    <p class="text-sm text-slate-500">
                        <?= tanggal_indonesia($data['tanggal_mulai']); ?> s.d <?= tanggal_indonesia($data['tanggal_selesai']); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Tahun</p>
                    <p class="font-semibold"><?= $data['id_tahun']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $data['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tahun Akademik</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['tahun']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Semester</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['semester']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Mulai</p>
                    <p class="font-semibold"><?= tanggal_indonesia($data['tanggal_mulai']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Selesai</p>
                    <p class="font-semibold"><?= tanggal_indonesia($data['tanggal_selesai']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold"><?= tanggal_jam_indonesia($data['created_at']); ?></p>
                </div>
            </div>
        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Ringkasan Relasi</h3>

            <div class="space-y-3 mb-6">
                <div class="flex justify-between border-b pb-3">
                    <span class="text-slate-500">Kelas</span>
                    <span class="font-bold"><?= number_format($total_kelas); ?></span>
                </div>

                <div class="flex justify-between border-b pb-3">
                    <span class="text-slate-500">Jadwal</span>
                    <span class="font-bold"><?= number_format($total_jadwal); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">KRS</span>
                    <span class="font-bold"><?= number_format($total_krs); ?></span>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

            <div class="space-y-3">
                <a href="edit_tahun.php?id=<?= $data['id_tahun']; ?>" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                    <i class="fa-solid fa-pen mr-2"></i>
                    Edit Tahun
                </a>

                <?php if ($data['status'] != 'aktif'): ?>
                    <a href="set_aktif.php?id=<?= $data['id_tahun']; ?>"
                       onclick="return confirm('Aktifkan tahun akademik ini?')"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">
                        <i class="fa-solid fa-check mr-2"></i>
                        Jadikan Aktif
                    </a>
                <?php endif; ?>

                <a href="hapus_tahun.php?id=<?= $data['id_tahun']; ?>" onclick="return confirm('Yakin ingin menghapus tahun akademik ini?')" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus Tahun
                </a>

                <a href="data_tahun.php" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i> 
                    Kembali
                </a>
            </div>
        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>