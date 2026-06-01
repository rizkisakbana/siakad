<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Program Studi";
$page_subtitle = "Informasi lengkap program studi";

$id_prodi = intval($_GET['id'] ?? 0);

if ($id_prodi <= 0) {
    set_alert("error", "ID prodi tidak valid.");
    header("Location: data_prodi.php");
    exit;
}

$query = mysqli_query($conn, "SELECT * FROM prodi WHERE id_prodi='$id_prodi' LIMIT 1");

if (mysqli_num_rows($query) < 1) {
    set_alert("error", "Data prodi tidak ditemukan.");
    header("Location: data_prodi.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

simpan_log($conn, $_SESSION['id_user'], "Melihat detail program studi: " . $data['nama_prodi'], "Program Studi");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Program Studi</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi master program studi.</p>
        </div>

        <a href="data_prodi.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-building-columns text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($data['nama_prodi']); ?></h3>
                    <p class="text-sm text-slate-500"><?= htmlspecialchars($data['kode_prodi']); ?> • <?= htmlspecialchars($data['jenjang']); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Prodi</p>
                    <p class="font-semibold"><?= $data['id_prodi']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kode Prodi</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['kode_prodi']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Nama Prodi</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['nama_prodi']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Jenjang</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['jenjang']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Gelar</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['gelar'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $data['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold"><?= tanggal_indonesia($data['created_at']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Waktu Dibuat</p>
                    <p class="font-semibold"><?= jam_indonesia($data['created_at']); ?></p>
                </div>
            </div>
        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

            <div class="space-y-3">
                <a href="edit_prodi.php?id=<?= $data['id_prodi']; ?>" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                    <i class="fa-solid fa-pen mr-2"></i>
                    Edit Prodi
                </a>

                <a href="hapus_prodi.php?id=<?= $data['id_prodi']; ?>" onclick="return confirm('Yakin ingin menghapus data prodi ini?')" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus Prodi
                </a>

                <a href="data_prodi.php" class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>
        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>