<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Ruangan";
$page_subtitle = "Kelola master data ruangan untuk jadwal kuliah";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_jenis = mysqli_real_escape_string($conn, $_GET['jenis_ruangan'] ?? '');
$filter_status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        kode_ruangan LIKE '%$keyword%' OR
        nama_ruangan LIKE '%$keyword%' OR
        gedung LIKE '%$keyword%' OR
        lantai LIKE '%$keyword%' OR
        fasilitas LIKE '%$keyword%'
    )";
}

if (!empty($filter_jenis)) {
    $where .= " AND jenis_ruangan = '$filter_jenis'";
}

if (!empty($filter_status)) {
    $where .= " AND status = '$filter_status'";
}

$total_ruangan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM ruangan
"))['total'] ?? 0;

$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM ruangan WHERE status = 'aktif'
"))['total'] ?? 0;

$total_maintenance = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM ruangan WHERE status = 'maintenance'
"))['total'] ?? 0;

$total_kapasitas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(kapasitas), 0) AS total FROM ruangan
"))['total'] ?? 0;

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM ruangan
    $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_ruangan = mysqli_query($conn, "
    SELECT *
    FROM ruangan
    $where
    ORDER BY id_ruangan DESC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'jenis_ruangan' => $filter_jenis,
    'status' => $filter_status
]);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Ruangan</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2">
                <?= number_format($total_ruangan); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Ruangan Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2">
                <?= number_format($total_aktif); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Maintenance</p>
            <h2 class="text-3xl font-bold text-orange-700 mt-2">
                <?= number_format($total_maintenance); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Kapasitas</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2">
                <?= number_format($total_kapasitas); ?>
            </h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Master Data Ruangan</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Data ruangan digunakan untuk penyusunan jadwal kuliah dan pencegahan bentrok ruangan.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="tambah_ruangan.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>

                <a href="export_ruangan_excel.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">
                    <i class="fa-solid fa-file-excel mr-2"></i>
                    Excel
                </a>

                <a href="export_ruangan_pdf.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-file-pdf mr-2"></i>
                    PDF
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari ruangan..."
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

            <select name="jenis_ruangan"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Jenis</option>
                <option value="kelas" <?= $filter_jenis == 'kelas' ? 'selected' : ''; ?>>Kelas</option>
                <option value="laboratorium" <?= $filter_jenis == 'laboratorium' ? 'selected' : ''; ?>>Laboratorium
                </option>
                <option value="aula" <?= $filter_jenis == 'aula' ? 'selected' : ''; ?>>Aula</option>
                <option value="online" <?= $filter_jenis == 'online' ? 'selected' : ''; ?>>Online</option>
                <option value="lainnya" <?= $filter_jenis == 'lainnya' ? 'selected' : ''; ?>>Lainnya</option>
            </select>

            <select name="status"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                <option value="maintenance" <?= $filter_status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
            </select>

            <button type="submit"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-filter mr-2"></i>
                Filter
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Ruangan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Lokasi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Jenis</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kapasitas</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Fasilitas</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if ($data_ruangan && mysqli_num_rows($data_ruangan) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_ruangan)): ?>

                            <?php
                            $id_ruangan = intval($row['id_ruangan']);

                            $cek_jadwal = 0;
                            $query_jadwal = mysqli_query($conn, "
                                    SELECT COUNT(*) AS total
                                    FROM jadwal_kuliah
                                    WHERE id_ruangan = '$id_ruangan'
                                ");

                            if ($query_jadwal) {
                                $cek_jadwal = mysqli_fetch_assoc($query_jadwal)['total'] ?? 0;
                            }

                            $status_class = 'bg-slate-100 text-slate-700';
                            if ($row['status'] == 'aktif') {
                                $status_class = 'bg-green-100 text-green-700';
                            } elseif ($row['status'] == 'nonaktif') {
                                $status_class = 'bg-red-100 text-red-700';
                            } elseif ($row['status'] == 'maintenance') {
                                $status_class = 'bg-orange-100 text-orange-700';
                            }
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                                            <i class="fa-solid fa-door-open"></i>
                                        </div>

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($row['nama_ruangan']); ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <?= htmlspecialchars($row['kode_ruangan']); ?>
                                            </div>

                                            <div class="lg:hidden mt-1 text-xs text-blue-600">
                                                <?= htmlspecialchars(ucfirst($row['jenis_ruangan'] ?? '-')); ?> •
                                                Kapasitas <?= number_format($row['kapasitas'] ?? 0); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['gedung'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        Lantai <?= htmlspecialchars($row['lantai'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell capitalize">
                                    <?= htmlspecialchars($row['jenis_ruangan'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= number_format($row['kapasitas'] ?? 0); ?> Orang
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= !empty($row['fasilitas']) ? htmlspecialchars($row['fasilitas']) : '-'; ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">

                                        <a href="detail_ruangan.php?id=<?= $row['id_ruangan']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_ruangan.php?id=<?= $row['id_ruangan']; ?>"
                                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($cek_jadwal < 1): ?>
                                            <a href="hapus_ruangan.php?id=<?= $row['id_ruangan']; ?>"
                                                onclick="return confirm('Yakin ingin menghapus data ruangan ini?')"
                                                class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200"
                                                title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="alert('Ruangan tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                                                class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-slate-200 text-slate-500 cursor-not-allowed"
                                                title="Tidak dapat dihapus">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                Data ruangan tidak ditemukan.
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
                            <a href="?<?= $query_string; ?>&page=<?= $i; ?>" class="px-4 py-2 rounded-xl text-sm font-semibold
                               <?= $i == $page
                                   ? 'bg-blue-700 text-white'
                                   : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>">
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