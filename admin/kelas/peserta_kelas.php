<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Peserta Kelas";
$page_subtitle = "Daftar mahasiswa dalam kelas";

$id_kelas = intval($_GET['id'] ?? 0);

if ($id_kelas <= 0) {
    set_alert("error", "ID kelas tidak valid.");
    header("Location: data_kelas.php");
    exit;
}

$q_kelas = mysqli_query($conn, "
    SELECT 
        kelas.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    WHERE kelas.id_kelas = '$id_kelas'
    LIMIT 1
");

if (!$q_kelas || mysqli_num_rows($q_kelas) < 1) {
    set_alert("error", "Data kelas tidak ditemukan.");
    header("Location: data_kelas.php");
    exit;
}

$kelas = mysqli_fetch_assoc($q_kelas);

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');

$where = "WHERE mahasiswa.id_kelas = '$id_kelas'";

if (!empty($keyword)) {
    $where .= " AND (
        mahasiswa.nim LIKE '%$keyword%' OR
        mahasiswa.nama_mahasiswa LIKE '%$keyword%' OR
        mahasiswa.email LIKE '%$keyword%' OR
        mahasiswa.no_hp LIKE '%$keyword%' OR
        users.username LIKE '%$keyword%'
    )";
}

$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas'
"))['total'] ?? 0;

$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE id_kelas = '$id_kelas' 
    AND status_mahasiswa = 'aktif'
"))['total'] ?? 0;

$kapasitas = intval($kelas['kapasitas'] ?? 0);
$sisa_kapasitas = $kapasitas - intval($total_peserta);
if ($sisa_kapasitas < 0)
    $sisa_kapasitas = 0;

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM mahasiswa
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_mahasiswa = mysqli_query($conn, "
    SELECT 
        mahasiswa.*,
        users.username,
        users.foto AS foto_user
    FROM mahasiswa
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    $where
    ORDER BY mahasiswa.nama_mahasiswa ASC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'id' => $id_kelas,
    'keyword' => $keyword
]);

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat peserta kelas: " . $kelas['nama_kelas'],
    "Kelas"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Peserta Kelas</h2>
            <p class="text-sm text-slate-500">
                <?= htmlspecialchars($kelas['nama_kelas']); ?> •
                <?= htmlspecialchars($kelas['nama_prodi'] ?? '-'); ?> •
                <?= htmlspecialchars($kelas['tahun'] ?? '-'); ?> -
                <?= htmlspecialchars($kelas['semester_tahun'] ?? '-'); ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="detail_kelas.php?id=<?= $id_kelas; ?>"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                <i class="fa-solid fa-eye mr-2"></i>
                Detail Kelas
            </a>

            <a href="data_kelas.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Peserta</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_peserta); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Mahasiswa Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kapasitas</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($kapasitas); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Sisa Kapasitas</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($sisa_kapasitas); ?></h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Peserta Kelas</h2>
                <p class="text-sm text-slate-500">
                    <?= htmlspecialchars($kelas['nama_kelas']); ?> •
                    <?= htmlspecialchars($kelas['nama_prodi'] ?? '-'); ?> •
                    <?= htmlspecialchars($kelas['tahun'] ?? '-'); ?> -
                    <?= htmlspecialchars($kelas['semester_tahun'] ?? '-'); ?>
                </p>
            </div>

            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="id" value="<?= $id_kelas; ?>">
                <input type="hidden" name="page" value="1">

                <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>"
                    placeholder="Cari mahasiswa..."
                    class="w-full sm:w-72 rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-search mr-2"></i>
                    Cari
                </button>

                <a href="tambah_peserta_kelas.php?id=<?= $id_kelas; ?>"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-user-plus mr-2"></i>
                    Tambah Peserta
                </a>

                <a href="detail_kelas.php?id=<?= $id_kelas; ?>"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                    <i class="fa-solid fa-eye mr-2"></i>
                    Detail Kelas
                </a>

                <!-- <a href="data_kelas.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a> -->
            </form>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Mahasiswa</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">NIM</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Angkatan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Semester</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kontak</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-32">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if ($data_mahasiswa && mysqli_num_rows($data_mahasiswa) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_mahasiswa)): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="../../uploads/mahasiswa/<?= htmlspecialchars($row['foto']); ?>"
                                                alt="Foto Mahasiswa"
                                                class="w-11 h-11 rounded-full object-cover border border-slate-200">
                                        <?php elseif (!empty($row['foto_user'])): ?>
                                            <img src="../../uploads/pengguna/<?= htmlspecialchars($row['foto_user']); ?>"
                                                alt="Foto Pengguna"
                                                class="w-11 h-11 rounded-full object-cover border border-slate-200">
                                        <?php else: ?>
                                            <div
                                                class="w-11 h-11 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($row['nama_mahasiswa'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($row['nama_mahasiswa']); ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <?= htmlspecialchars($row['nim'] ?? '-'); ?>
                                            </div>
                                            <div class="lg:hidden text-xs text-blue-600 mt-1">
                                                <?= htmlspecialchars($row['status_mahasiswa'] ?? '-'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['nim'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['angkatan'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    Semester <?= htmlspecialchars($row['semester'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800 break-all">
                                        <?= htmlspecialchars($row['email'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['no_hp'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= ($row['status_mahasiswa'] ?? '') == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status_mahasiswa'] ?? '-'); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="../mahasiswa/detail_mahasiswa.php?id=<?= $row['id_mahasiswa']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                Belum ada mahasiswa pada kelas ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>

        <?php if ($total_page > 1): ?>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">
                <div class="text-sm text-slate-500">
                    Menampilkan
                    <span class="font-semibold text-slate-700"><?= $offset + 1; ?></span>
                    -
                    <span class="font-semibold text-slate-700"><?= min($offset + $limit, $total_data_filter); ?></span>
                    dari
                    <span class="font-semibold text-slate-700"><?= $total_data_filter; ?></span>
                    data
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?= $query_string; ?>&page=<?= $page - 1; ?>"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Sebelumnya
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_page; $i++): ?>
                        <?php if ($i == 1 || $i == $total_page || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?<?= $query_string; ?>&page=<?= $i; ?>"
                                class="px-4 py-2 rounded-xl text-sm font-semibold
                               <?= $i == $page ? 'bg-blue-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>">
                                <?= $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="px-2 text-slate-400">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_page): ?>
                        <a href="?<?= $query_string; ?>&page=<?= $page + 1; ?>"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Berikutnya
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>