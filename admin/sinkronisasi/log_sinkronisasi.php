<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Log Sinkronisasi Neo Feeder";
$page_subtitle = "Riwayat request dan response integrasi Neo Feeder PDDikti";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_modul = mysqli_real_escape_string($conn, $_GET['modul'] ?? '');
$filter_status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        aksi LIKE '%$keyword%' OR
        modul LIKE '%$keyword%' OR
        pesan_error LIKE '%$keyword%' OR
        request_payload LIKE '%$keyword%' OR
        response_payload LIKE '%$keyword%'
    )";
}

if (!empty($filter_modul)) {
    $where .= " AND modul = '$filter_modul'";
}

if (!empty($filter_status)) {
    $where .= " AND status = '$filter_status'";
}

$total_log = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM neofeeder_log
"))['total'] ?? 0;

$total_success = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM neofeeder_log WHERE status = 'success'
"))['total'] ?? 0;

$total_failed = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM neofeeder_log WHERE status = 'failed'
"))['total'] ?? 0;

$data_modul = mysqli_query($conn, "
    SELECT DISTINCT modul
    FROM neofeeder_log
    WHERE modul IS NOT NULL AND modul != ''
    ORDER BY modul ASC
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM neofeeder_log
    $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_log = mysqli_query($conn, "
    SELECT *
    FROM neofeeder_log
    $where
    ORDER BY 
        CASE 
            WHEN status = 'failed' THEN 1
            WHEN status = 'success' THEN 2
            ELSE 3
        END,
        id_log DESC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'modul' => $filter_modul,
    'status' => $filter_status
]);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Log Sinkronisasi Neo Feeder</h2>
            <p class="text-sm text-slate-500">
                Monitoring request, response, status, dan error integrasi Neo Feeder/PDDikti.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="sinkronisasi.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-rotate mr-2"></i>
                Sinkronisasi
            </a>

            <a href="pengaturan.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-gear mr-2"></i>
                Pengaturan
            </a>
        </div>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Log</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_log); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Berhasil</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_success); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Gagal</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_failed); ?></h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Log Sinkronisasi Neo Feeder</h2>
                <p class="text-sm text-slate-500">
                    Monitoring request, response, status, dan error integrasi Neo Feeder/PDDikti.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="sinkronisasi.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-rotate mr-2"></i>
                    Sinkronisasi
                </a>

                <a href="pengaturan.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-gear mr-2"></i>
                    Pengaturan
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari log..."
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

            <select name="modul"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Modul</option>
                <?php if ($data_modul && mysqli_num_rows($data_modul) > 0): ?>
                    <?php while ($modul = mysqli_fetch_assoc($data_modul)): ?>
                        <option value="<?= htmlspecialchars($modul['modul']); ?>" <?= $filter_modul == $modul['modul'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($modul['modul']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <select name="status"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Status</option>
                <option value="success" <?= $filter_status == 'success' ? 'selected' : ''; ?>>Success</option>
                <option value="failed" <?= $filter_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>

            <button type="submit"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-filter mr-2"></i>
                Filter
            </button>

            <!-- <a href="?status=failed"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 font-semibold">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                Lihat Log Gagal
            </a>

            <a href="?status=success"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-100 hover:bg-green-200 text-green-700 font-semibold">
                <i class="fa-solid fa-circle-check mr-2"></i>
                Lihat Log Berhasil
            </a>

            <a href="log_sinkronisasi.php"
                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">
                <i class="fa-solid fa-list mr-2"></i>
                Semua Log
            </a> -->
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Log</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Aksi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Pesan Error</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Waktu</th>
                        <th class="px-4 py-3 text-center w-32">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if ($data_log && mysqli_num_rows($data_log) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_log)): ?>

                            <?php
                            $status_class = $row['status'] == 'success'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700';
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['modul']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['aksi']); ?>
                                    </div>
                                    <div class="lg:hidden mt-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class; ?>">
                                            <?= htmlspecialchars($row['status']); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['aksi']); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell max-w-xs">
                                    <div class="truncate text-slate-600">
                                        <?= !empty($row['pesan_error']) ? htmlspecialchars($row['pesan_error']) : '-'; ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= !empty($row['created_at']) ? tanggal_jam_indonesia($row['created_at']) : '-'; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_log.php?id=<?= $row['id_log']; ?>"
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
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                Data log sinkronisasi belum tersedia.
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