<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Tahun Akademik";
$page_subtitle = "Kelola periode tahun akademik SIAKAD";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');

$where = "";
if (!empty($keyword)) {
    $where = "WHERE 
        tahun LIKE '%$keyword%' OR
        semester LIKE '%$keyword%' OR
        status LIKE '%$keyword%'
    ";
}

$total_tahun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM tahun_akademik"))['total'] ?? 0;
$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM tahun_akademik WHERE status='aktif'"))['total'] ?? 0;
$total_nonaktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM tahun_akademik WHERE status='nonaktif'"))['total'] ?? 0;

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM tahun_akademik $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_tahun = mysqli_query($conn, "
    SELECT * FROM tahun_akademik
    $where
    ORDER BY id_tahun DESC
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
            <p class="text-sm text-slate-500">Total Tahun Akademik</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_tahun); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Periode Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Periode Nonaktif</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_nonaktif); ?></h2>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Master Tahun Akademik</h2>
                <p class="text-sm text-slate-500 mt-1">Hanya satu tahun akademik yang boleh aktif.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <form method="GET" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="page" value="1">

                    <input type="text"
                           name="keyword"
                           value="<?= htmlspecialchars($keyword); ?>"
                           placeholder="Cari tahun akademik..."
                           class="w-full sm:w-72 rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-search mr-2"></i>
                        Cari
                    </button>
                </form>

                <a href="tambah_tahun.php"
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
                        <th class="px-4 py-3 text-left">Tahun Akademik</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Periode</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Dibuat</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($data_tahun) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_tahun)): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['tahun']); ?> - <?= htmlspecialchars($row['semester']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= tanggal_indonesia($row['tanggal_mulai']); ?> s.d <?= tanggal_indonesia($row['tanggal_selesai']); ?>
                                    </div>

                                    <div class="lg:hidden mt-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div><?= tanggal_indonesia($row['tanggal_mulai']); ?></div>
                                    <div class="text-xs text-slate-500">s.d <?= tanggal_indonesia($row['tanggal_selesai']); ?></div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= tanggal_jam_indonesia($row['created_at']); ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_tahun.php?id=<?= $row['id_tahun']; ?>"
                                           class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_tahun.php?id=<?= $row['id_tahun']; ?>"
                                           class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($row['status'] != 'aktif'): ?>
                                            <a href="set_aktif.php?id=<?= $row['id_tahun']; ?>"
                                               onclick="return confirm('Aktifkan tahun akademik ini? Tahun akademik aktif sebelumnya akan dinonaktifkan.')"
                                               class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-green-100 text-green-700 hover:bg-green-200">
                                                <i class="fa-solid fa-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="hapus_tahun.php?id=<?= $row['id_tahun']; ?>"
                                           onclick="return confirm('Yakin ingin menghapus tahun akademik ini?')"
                                           class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                Data tahun akademik tidak ditemukan.
                            </td>
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
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page - 1; ?>"
                           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-semibold">
                            Sebelumnya
                        </a>
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
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page + 1; ?>"
                           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-sm font-semibold">
                            Berikutnya
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>