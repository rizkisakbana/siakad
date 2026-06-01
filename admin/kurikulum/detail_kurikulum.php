<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Kurikulum";
$page_subtitle = "Informasi lengkap kurikulum program studi";

$id_kurikulum = intval($_GET['id'] ?? 0);

if ($id_kurikulum <= 0) {
    set_alert("error", "ID kurikulum tidak valid.");
    header("Location: data_kurikulum.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        kurikulum.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        prodi.gelar
    FROM kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE kurikulum.id_kurikulum = '$id_kurikulum'
    LIMIT 1
");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data kurikulum tidak ditemukan.");
    header("Location: data_kurikulum.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$total_mk = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mata_kuliah 
    WHERE id_kurikulum = '$id_kurikulum'
"))['total'] ?? 0;

$total_sks_mk = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_sks), 0) AS total 
    FROM mata_kuliah 
    WHERE id_kurikulum = '$id_kurikulum'
"))['total'] ?? 0;

$total_wajib = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mata_kuliah 
    WHERE id_kurikulum = '$id_kurikulum' 
    AND jenis_mk = 'wajib'
"))['total'] ?? 0;

$total_pilihan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mata_kuliah 
    WHERE id_kurikulum = '$id_kurikulum' 
    AND jenis_mk = 'pilihan'
"))['total'] ?? 0;

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail kurikulum: " . $data['nama_kurikulum'],
    "Kurikulum"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Kurikulum</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap kurikulum.</p>
        </div>

        <a href="data_kurikulum.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Mata Kuliah</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_mk); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total SKS MK</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_sks_mk); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">MK Wajib</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_wajib); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">MK Pilihan</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($total_pilihan); ?></h2>
        </div>

    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-layer-group text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_kurikulum']); ?>
                    </h3>
                    <p class="text-sm text-slate-500">
                        <?= htmlspecialchars($data['nama_prodi'] ?? '-'); ?> • Tahun Kurikulum <?= htmlspecialchars($data['tahun_kurikulum']); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Kurikulum</p>
                    <p class="font-semibold text-slate-800"><?= $data['id_kurikulum']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Nama Kurikulum</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_kurikulum']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tahun Kurikulum</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['tahun_kurikulum']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Total SKS Target</p>
                    <p class="font-semibold text-slate-800"><?= number_format($data['total_sks']); ?> SKS</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold 
                        <?= $data['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold text-slate-800"><?= tanggal_jam_indonesia($data['created_at']); ?></p>
                </div>

            </div>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Program Studi</h3>

            <div class="space-y-3 text-sm mb-6">
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

            <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

            <div class="space-y-3">
                <a href="edit_kurikulum.php?id=<?= $data['id_kurikulum']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                    <i class="fa-solid fa-pen mr-2"></i>
                    Edit Kurikulum
                </a>

                <?php if ($total_mk < 1): ?>
                    <a href="hapus_kurikulum.php?id=<?= $data['id_kurikulum']; ?>"
                       onclick="return confirm('Yakin ingin menghapus kurikulum ini?')"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Hapus Kurikulum
                    </a>
                <?php else: ?>
                    <button type="button"
                            onclick="alert('Kurikulum tidak dapat dihapus karena sudah digunakan pada data mata kuliah.')"
                            class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                        <i class="fa-solid fa-lock mr-2"></i>
                        Tidak Bisa Dihapus
                    </button>
                <?php endif; ?>

                <a href="../matakuliah/data_matakuliah.php?kurikulum=<?= $data['id_kurikulum']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                    <i class="fa-solid fa-book mr-2"></i>
                    Lihat Mata Kuliah
                </a>

                <a href="data_kurikulum.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>