<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Ruangan";
$page_subtitle = "Informasi lengkap data ruangan";

$id_ruangan = intval($_GET['id'] ?? 0);

if ($id_ruangan <= 0) {
    set_alert("error", "ID ruangan tidak valid.");
    header("Location: data_ruangan.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT *
    FROM ruangan
    WHERE id_ruangan = '$id_ruangan'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data ruangan tidak ditemukan.");
    header("Location: data_ruangan.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$total_jadwal = 0;
$q_jadwal = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    WHERE id_ruangan = '$id_ruangan'
");

if ($q_jadwal) {
    $total_jadwal = mysqli_fetch_assoc($q_jadwal)['total'] ?? 0;
}

$total_jadwal_aktif = 0;
$q_jadwal_aktif = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    LEFT JOIN tahun_akademik ON jadwal_kuliah.id_tahun = tahun_akademik.id_tahun
    WHERE jadwal_kuliah.id_ruangan = '$id_ruangan'
    AND tahun_akademik.status = 'aktif'
");

if ($q_jadwal_aktif) {
    $total_jadwal_aktif = mysqli_fetch_assoc($q_jadwal_aktif)['total'] ?? 0;
}

$status_class = 'bg-slate-100 text-slate-700';
if ($data['status'] == 'aktif') {
    $status_class = 'bg-green-100 text-green-700';
} elseif ($data['status'] == 'nonaktif') {
    $status_class = 'bg-red-100 text-red-700';
} elseif ($data['status'] == 'maintenance') {
    $status_class = 'bg-orange-100 text-orange-700';
}

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail ruangan: " . $data['kode_ruangan'] . " - " . $data['nama_ruangan'],
    "Ruangan"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Ruangan</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap ruangan.</p>
        </div>

        <a href="data_ruangan.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kapasitas</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($data['kapasitas'] ?? 0); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Jadwal</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($total_jadwal); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Jadwal Tahun Aktif</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($total_jadwal_aktif); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status Ruangan</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= htmlspecialchars(ucfirst($data['status'])); ?>
                </span>
            </div>
        </div>

    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-door-open text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-xl font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_ruangan']); ?>
                    </h3>

                    <p class="text-sm text-slate-500 mt-1">
                        <?= htmlspecialchars($data['kode_ruangan']); ?> • 
                        <?= htmlspecialchars(ucfirst($data['jenis_ruangan'] ?? '-')); ?> • 
                        Kapasitas <?= number_format($data['kapasitas'] ?? 0); ?> orang
                    </p>

                    <div class="mt-3">
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class; ?>">
                            <?= htmlspecialchars(ucfirst($data['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Ruangan</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Ruangan</p>
                    <p class="font-semibold text-slate-800">
                        <?= $data['id_ruangan']; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kode Ruangan</p>
                    <p class="font-semibold text-slate-800">
                        <?= htmlspecialchars($data['kode_ruangan']); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Nama Ruangan</p>
                    <p class="font-semibold text-slate-800">
                        <?= htmlspecialchars($data['nama_ruangan']); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Jenis Ruangan</p>
                    <p class="font-semibold text-slate-800 capitalize">
                        <?= htmlspecialchars($data['jenis_ruangan'] ?? '-'); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Gedung</p>
                    <p class="font-semibold text-slate-800">
                        <?= htmlspecialchars($data['gedung'] ?? '-'); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Lantai</p>
                    <p class="font-semibold text-slate-800">
                        <?= htmlspecialchars($data['lantai'] ?? '-'); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kapasitas</p>
                    <p class="font-semibold text-slate-800">
                        <?= number_format($data['kapasitas'] ?? 0); ?> Orang
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $status_class; ?>">
                        <?= htmlspecialchars(ucfirst($data['status'])); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 sm:col-span-2">
                    <p class="text-xs text-slate-500 mb-1">Fasilitas</p>
                    <p class="font-semibold text-slate-800 whitespace-pre-line">
                        <?= !empty($data['fasilitas']) ? htmlspecialchars($data['fasilitas']) : '-'; ?>
                    </p>
                </div>

            </div>

        </section>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Penggunaan Jadwal</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Total Jadwal</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= number_format($total_jadwal); ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Tahun Akademik Aktif</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= number_format($total_jadwal_aktif); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Riwayat Sistem</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4 border-b pb-3">
                        <span class="text-slate-500">Dibuat</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['created_at']) ? tanggal_jam_indonesia($data['created_at']) : '-'; ?>
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Diperbarui</span>
                        <span class="font-semibold text-slate-800 text-right">
                            <?= !empty($data['updated_at']) ? tanggal_jam_indonesia($data['updated_at']) : '-'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

                <div class="space-y-3">
                    <a href="edit_ruangan.php?id=<?= $data['id_ruangan']; ?>"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                        <i class="fa-solid fa-pen mr-2"></i>
                        Edit Ruangan
                    </a>

                    <?php if ($total_jadwal < 1): ?>
                        <a href="hapus_ruangan.php?id=<?= $data['id_ruangan']; ?>"
                           onclick="return confirm('Yakin ingin menghapus data ruangan ini?')"
                           class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                            <i class="fa-solid fa-trash mr-2"></i>
                            Hapus Ruangan
                        </a>
                    <?php else: ?>
                        <button type="button"
                                onclick="alert('Ruangan tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                                class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                            <i class="fa-solid fa-lock mr-2"></i>
                            Tidak Bisa Dihapus
                        </button>
                    <?php endif; ?>

                    <a href="data_ruangan.php"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </div>

        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>