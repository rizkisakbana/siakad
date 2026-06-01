<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/matakuliah_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

/** @var mysqli $conn */

$page_title = "Detail Mata Kuliah";
$page_subtitle = "Informasi lengkap mata kuliah";

$id_mk = intval($_GET['id'] ?? 0);

if ($id_mk <= 0) {
    set_alert("error", "ID mata kuliah tidak valid.");
    header("Location: data_matakuliah.php");
    exit;
}

$data = matakuliah_query_one($conn, "
    SELECT 
        mata_kuliah.*,
        kurikulum.nama_kurikulum,
        kurikulum.tahun_kurikulum,
        kurikulum.total_sks AS total_sks_kurikulum,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        prodi.gelar
    FROM mata_kuliah
    LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE mata_kuliah.id_mk = '$id_mk'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data mata kuliah tidak ditemukan.");
    header("Location: data_matakuliah.php");
    exit;
}

$total_jadwal = matakuliah_count($conn, "
    SELECT COUNT(*) AS total
    FROM jadwal_kuliah
    WHERE id_mk = '$id_mk'
");

$total_krs = matakuliah_count($conn, "
    SELECT COUNT(*) AS total
    FROM krs_detail
    LEFT JOIN jadwal_kuliah ON krs_detail.id_jadwal = jadwal_kuliah.id_jadwal
    WHERE jadwal_kuliah.id_mk = '$id_mk'
");

$total_nilai = matakuliah_count($conn, "
    SELECT COUNT(*) AS total
    FROM nilai
    LEFT JOIN krs_detail ON nilai.id_krs_detail = krs_detail.id_krs_detail
    LEFT JOIN jadwal_kuliah ON krs_detail.id_jadwal = jadwal_kuliah.id_jadwal
    WHERE jadwal_kuliah.id_mk = '$id_mk'
");

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail mata kuliah: " . $data['kode_mk'] . " - " . $data['nama_mk'],
    "Mata Kuliah"
);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Mata Kuliah</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap mata kuliah.</p>
        </div>

        <a href="data_matakuliah.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total SKS</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($data['total_sks']); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Digunakan Jadwal</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($total_jadwal); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">KRS Terkait</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($total_krs); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Nilai Terkait</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2">
                <?= number_format($total_nilai); ?>
            </h2>
        </div>

    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-book text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_mk']); ?>
                    </h3>
                    <p class="text-sm text-slate-500">
                        <?= htmlspecialchars($data['kode_mk']); ?> • Semester <?= htmlspecialchars($data['semester']); ?> • <?= number_format($data['total_sks']); ?> SKS
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Mata Kuliah</p>
                    <p class="font-semibold text-slate-800"><?= $data['id_mk']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Kode Mata Kuliah</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['kode_mk']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Nama Mata Kuliah</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($data['nama_mk']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Semester</p>
                    <p class="font-semibold text-slate-800">Semester <?= htmlspecialchars($data['semester']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">SKS Teori</p>
                    <p class="font-semibold text-slate-800"><?= number_format($data['sks_teori']); ?> SKS</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">SKS Praktik</p>
                    <p class="font-semibold text-slate-800"><?= number_format($data['sks_praktik']); ?> SKS</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Total SKS</p>
                    <p class="font-semibold text-slate-800"><?= number_format($data['total_sks']); ?> SKS</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Jenis Mata Kuliah</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold 
                        <?= $data['jenis_mk'] == 'wajib' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                        <?= htmlspecialchars($data['jenis_mk']); ?>
                    </span>
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

            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Kurikulum</h3>

            <div class="space-y-3 text-sm mb-6">
                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">Kurikulum</span>
                    <span class="font-semibold text-slate-800 text-right">
                        <?= htmlspecialchars($data['nama_kurikulum'] ?? '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">Tahun</span>
                    <span class="font-semibold text-slate-800 text-right">
                        <?= htmlspecialchars($data['tahun_kurikulum'] ?? '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">Total SKS Kurikulum</span>
                    <span class="font-semibold text-slate-800 text-right">
                        <?= number_format($data['total_sks_kurikulum'] ?? 0); ?> SKS
                    </span>
                </div>
            </div>

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
                <a href="edit_matakuliah.php?id=<?= $data['id_mk']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                    <i class="fa-solid fa-pen mr-2"></i>
                    Edit Mata Kuliah
                </a>

                <?php if ($total_jadwal < 1): ?>
                    <a href="hapus_matakuliah.php?id=<?= $data['id_mk']; ?>"
                       onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')"
                       class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Hapus Mata Kuliah
                    </a>
                <?php else: ?>
                    <button type="button"
                            onclick="alert('Mata kuliah tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                            class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-300 text-slate-600 font-semibold cursor-not-allowed">
                        <i class="fa-solid fa-lock mr-2"></i>
                        Tidak Bisa Dihapus
                    </button>
                <?php endif; ?>

                <a href="../kurikulum/detail_kurikulum.php?id=<?= $data['id_kurikulum']; ?>"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-layer-group mr-2"></i>
                    Detail Kurikulum
                </a>

                <a href="data_matakuliah.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

        </aside>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
