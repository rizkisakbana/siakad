<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/log_aktivitas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Log Aktivitas";
$page_subtitle = "Monitoring aktivitas pengguna sistem SIAKAD";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');

$where = "";

if (!empty($keyword)) {
    $where = "WHERE 
        users.nama_lengkap LIKE '%$keyword%' OR
        users.username LIKE '%$keyword%' OR
        log_aktivitas.aktivitas LIKE '%$keyword%' OR
        log_aktivitas.modul LIKE '%$keyword%'
    ";
}

$total_log = aktivitas_count($conn, "
    SELECT COUNT(*) AS total
    FROM log_aktivitas
");

$total_hari_ini = aktivitas_count($conn, "
    SELECT COUNT(*) AS total
    FROM log_aktivitas
    WHERE DATE(created_at)=CURDATE()
");

$total_login = aktivitas_count($conn, "
    SELECT COUNT(*) AS total
    FROM log_aktivitas
    WHERE aktivitas LIKE '%login%'
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$total_data_filter = aktivitas_count($conn, "
    SELECT COUNT(*) AS total
    FROM log_aktivitas
    LEFT JOIN users 
        ON log_aktivitas.id_user = users.id_user
    $where
");

$total_page = ceil($total_data_filter / $limit);

$data_log = aktivitas_fetch_all($conn, "
    SELECT 
        log_aktivitas.*,
        users.nama_lengkap,
        users.username
    FROM log_aktivitas
    LEFT JOIN users 
        ON log_aktivitas.id_user = users.id_user
    $where
    ORDER BY log_aktivitas.id_log DESC
    LIMIT $limit OFFSET $offset
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- STATISTIK -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Aktivitas</p>
                    <h2 class="text-3xl font-bold text-blue-700 mt-2">
                        <?= number_format($total_log); ?>
                    </h2>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Aktivitas Hari Ini</p>
                    <h2 class="text-3xl font-bold text-green-700 mt-2">
                        <?= number_format($total_hari_ini); ?>
                    </h2>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-day text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Login</p>
                    <h2 class="text-3xl font-bold text-purple-700 mt-2">
                        <?= number_format($total_login); ?>
                    </h2>
                </div>

                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-right-to-bracket text-xl"></i>
                </div>
            </div>
        </div>

    </section>

    <!-- TABLE -->
    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <!-- HEADER -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">

            <div>
                <h2 class="text-xl font-bold text-slate-800">
                    Data Aktivitas Sistem
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Monitoring aktivitas seluruh pengguna SIAKAD.
                </p>
            </div>

            <form method="GET" class="w-full lg:w-auto">
                <input type="hidden" name="page" value="1">

                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>"
                        placeholder="Cari aktivitas..."
                        class="w-full sm:w-72 rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <button type="submit"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-search mr-2"></i>
                        Cari
                    </button>
                </div>
            </form>

        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto rounded-xl border border-slate-200">

            <table class="min-w-full text-sm">

                <thead class="bg-slate-100 text-slate-700">

                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>

                        <th class="px-4 py-3 text-left">
                            Pengguna
                        </th>

                        <th class="px-4 py-3 text-left hidden lg:table-cell">
                            Aktivitas
                        </th>

                        <th class="px-4 py-3 text-left hidden lg:table-cell">
                            Modul
                        </th>

                        <th class="px-4 py-3 text-left hidden lg:table-cell">
                            IP Address
                        </th>

                        <th class="px-4 py-3 text-left hidden lg:table-cell">
                            Waktu
                        </th>

                        <th class="px-4 py-3 text-center w-36">
                            Aksi
                        </th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php if (!empty($data_log)): ?>

                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_log as $row): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">

                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_lengkap'] ?? 'System'); ?>
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['username'] ?? '-'); ?>
                                    </div>

                                    <!-- MOBILE INFO -->
                                    <div class="lg:hidden mt-2">

                                        <div class="text-xs text-slate-600">
                                            <?= htmlspecialchars($row['modul'] ?? '-'); ?>
                                        </div>

                                        <div class="text-xs text-slate-400 mt-1">
                                            <?= tanggal_jam_indonesia($row['created_at']); ?>
                                        </div>

                                    </div>

                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['aktivitas']); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['modul'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['ip_address'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= tanggal_jam_indonesia($row['created_at']); ?>
                                </td>

                                <td class="px-4 py-3">

                                    <div class="flex items-center justify-center gap-2">

                                        <!-- DETAIL -->
                                        <a href="detail_aktivitas.php?id=<?= $row['id_log']; ?>"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 transition"
                                            title="Detail">

                                            <i class="fa-solid fa-eye"></i>

                                        </a>

                                        <!-- HAPUS -->
                                        <a href="hapus_aktivitas.php?id=<?= $row['id_log']; ?>"
                                            onclick="return confirm('Yakin ingin menghapus data aktivitas ini?')"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 transition"
                                            title="Hapus">

                                            <i class="fa-solid fa-trash"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                Data aktivitas tidak ditemukan.
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
                    <span class="font-semibold text-slate-700">
                        <?= min($offset + $limit, $total_data_filter); ?>
                    </span>
                    dari
                    <span class="font-semibold text-slate-700"><?= $total_data_filter; ?></span>
                    data
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <?php if ($page > 1): ?>
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page - 1; ?>"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Sebelumnya
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_page; $i++): ?>
                        <?php if ($i == 1 || $i == $total_page || ($i >= $page - 2 && $i <= $page + 2)): ?>

                            <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $i; ?>" class="px-4 py-2 rounded-xl text-sm font-semibold
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
                        <a href="?keyword=<?= urlencode($keyword); ?>&page=<?= $page + 1; ?>"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm">
                            Berikutnya
                        </a>
                    <?php endif; ?>

                </div>

            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
