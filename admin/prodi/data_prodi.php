<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Program Studi";
$page_subtitle = "Kelola master data program studi";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');

$where = "";
if (!empty($keyword)) {
    $where = "WHERE 
        kode_prodi LIKE '%$keyword%' OR
        nama_prodi LIKE '%$keyword%' OR
        jenjang LIKE '%$keyword%' OR
        gelar LIKE '%$keyword%' OR
        status LIKE '%$keyword%'
    ";
}

$total_prodi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM prodi"))['total'] ?? 0;
$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM prodi WHERE status='aktif'"))['total'] ?? 0;
$total_nonaktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM prodi WHERE status='nonaktif'"))['total'] ?? 0;

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM prodi $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_prodi = mysqli_query($conn, "
    SELECT * FROM prodi
    $where
    ORDER BY id_prodi DESC
    LIMIT $limit OFFSET $offset
");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Prodi</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_prodi); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Prodi Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Prodi Nonaktif</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_nonaktif); ?></h2>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Master Program Studi</h2>
                <p class="text-sm text-slate-500 mt-1">Data program studi pada Akademi Teknik Informatika Tunas Bangsa.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="page" value="1">

                    <input type="text"
                           name="keyword"
                           value="<?= htmlspecialchars($keyword); ?>"
                           placeholder="Cari prodi..."
                           class="w-full sm:w-72 rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-search mr-2"></i>
                        Cari
                    </button>
                </form>

                <a href="tambah_prodi.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Program Studi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kode</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Jenjang</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Gelar</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-40">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($data_prodi) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_prodi)): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_prodi']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['kode_prodi']); ?> • <?= htmlspecialchars($row['jenjang']); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars($row['kode_prodi']); ?></td>
                                <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars($row['jenjang']); ?></td>
                                <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars($row['gelar'] ?? '-'); ?></td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_prodi.php?id=<?= $row['id_prodi']; ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="edit_prodi.php?id=<?= $row['id_prodi']; ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="hapus_prodi.php?id=<?= $row['id_prodi']; ?>" onclick="return confirm('Yakin ingin menghapus data prodi ini?')" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Data prodi tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_page > 1): ?>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">
                <div class="text-sm text-slate-500">
                    Menampilkan <span class="font-semibold"><?= $offset + 1; ?></span>
                    -
                    <span class="font-semibold"><?= min($offset + $limit, $total_data_filter); ?></span>
                    dari <span class="font-semibold"><?= $total_data_filter; ?></span> data
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page - 1; ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-semibold">Sebelumnya</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_page; $i++): ?>
                        <?php if ($i == 1 || $i == $total_page || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $i; ?>"
                               class="px-4 py-2 rounded-xl text-sm font-semibold <?= $i == $page ? 'bg-blue-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>">
                                <?= $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="px-2 text-slate-400">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_page): ?>
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page + 1; ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-semibold">Berikutnya</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>