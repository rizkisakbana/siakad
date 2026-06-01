<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/dosen_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Dosen";
$page_subtitle = "Kelola master data dosen SIAKAD";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_prodi = intval($_GET['prodi'] ?? 0);
$filter_status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        dosen.nidn LIKE '%$keyword%' OR
        dosen.nidk LIKE '%$keyword%' OR
        dosen.nuptk LIKE '%$keyword%' OR
        dosen.nip LIKE '%$keyword%' OR
        dosen.id_dosen_feeder LIKE '%$keyword%' OR
        dosen.id_feeder LIKE '%$keyword%' OR
        dosen.nama_dosen LIKE '%$keyword%' OR
        dosen.email LIKE '%$keyword%' OR
        dosen.no_hp LIKE '%$keyword%' OR
        prodi.nama_prodi LIKE '%$keyword%' OR
        users.username LIKE '%$keyword%'
    )";
}

if ($filter_prodi > 0) {
    $where .= " AND dosen.id_prodi = '$filter_prodi'";
}

if (!empty($filter_status)) {
    $where .= " AND dosen.status = '$filter_status'";
}

$total_dosen = dosen_count($conn, "
    SELECT COUNT(*) AS total FROM dosen
");

$total_aktif = dosen_count($conn, "
    SELECT COUNT(*) AS total FROM dosen WHERE status = 'aktif'
");

$total_nonaktif = dosen_count($conn, "
    SELECT COUNT(*) AS total FROM dosen WHERE status = 'nonaktif'
");

$total_prodi = dosen_count($conn, "
    SELECT COUNT(DISTINCT id_prodi) AS total FROM dosen WHERE id_prodi IS NOT NULL
");

$total_feeder = dosen_count($conn, "
    SELECT COUNT(*) AS total FROM dosen WHERE COALESCE(NULLIF(id_dosen_feeder, ''), NULLIF(id_feeder, ''), '') <> ''
");

$data_prodi = dosen_fetch_all($conn, "
    SELECT * FROM prodi 
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$total_data_filter = dosen_count($conn, "
    SELECT COUNT(*) AS total
    FROM dosen
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    LEFT JOIN users ON dosen.id_user = users.id_user
    $where
");

$total_page = ceil($total_data_filter / $limit);

$data_dosen = dosen_fetch_all($conn, "
    SELECT 
        dosen.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        users.username,
        users.foto AS foto_user,
        users.status AS status_user
    FROM dosen
    LEFT JOIN prodi ON dosen.id_prodi = prodi.id_prodi
    LEFT JOIN users ON dosen.id_user = users.id_user
    $where
    ORDER BY dosen.id_dosen DESC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'prodi' => $filter_prodi,
    'status' => $filter_status
]);

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Dosen</p>
                    <h2 class="text-3xl font-bold text-blue-700 mt-2">
                        <?= number_format($total_dosen); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-chalkboard-teacher text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Dosen Aktif</p>
                    <h2 class="text-3xl font-bold text-green-700 mt-2">
                        <?= number_format($total_aktif); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Dosen Nonaktif</p>
                    <h2 class="text-3xl font-bold text-red-700 mt-2">
                        <?= number_format($total_nonaktif); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-700 flex items-center justify-center">
                    <i class="fa-solid fa-circle-xmark text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Punya ID Feeder</p>
                    <h2 class="text-3xl font-bold text-purple-700 mt-2">
                        <?= number_format($total_feeder); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-database text-xl"></i>
                </div>
            </div>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">
                    Master Data Dosen
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Data dosen terhubung dengan akun pengguna, program studi, dan identitas NeoFeeder.
                </p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 mb-8">
                <a href="../neofeeder/pull_dosen.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-cloud-arrow-down mr-2"></i>
                    Pull NeoFeeder
                </a>

                <a href="import_dosen.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold">
                    <i class="fa-solid fa-file-import mr-2"></i>
                    Import
                </a>

                <a href="tambah_dosen.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>

                <a href="export_dosen_pdf.php"
                    class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg shadow-lg text-center">
                    <i class="fa fa-file-pdf"></i>
                    PDF
                </a>

                <a href="export_dosen_excel.php"
                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg shadow-lg text-center">
                    <i class="fa fa-file-excel"></i>
                    Excel
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari dosen..."
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

            <select name="prodi"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Prodi</option>
                <?php foreach ($data_prodi as $prodi): ?>
                    <option value="<?= $prodi['id_prodi']; ?>" <?= $filter_prodi == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
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
                        <th class="px-4 py-3 text-left">Dosen</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Identitas</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Program Studi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kontak</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Akun</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_dosen)): ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_dosen as $row): ?>

                            <?php
                            $id_dosen = intval($row['id_dosen']);

                            $cek_jadwal = dosen_count($conn, "
                                    SELECT COUNT(*) AS total 
                                    FROM jadwal_kuliah 
                                    WHERE id_dosen = '$id_dosen'
                                ");

                            $foto_dosen = $row['foto'] ?? '';
                            $foto_user = $row['foto_user'] ?? '';
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">

                                        <?php if (!empty($foto_dosen)): ?>
                                            <img src="../../uploads/dosen/<?= htmlspecialchars($foto_dosen); ?>" alt="Foto Dosen"
                                                class="w-11 h-11 rounded-full object-cover border border-slate-200">
                                        <?php elseif (!empty($foto_user)): ?>
                                            <img src="../../uploads/pengguna/<?= htmlspecialchars($foto_user); ?>"
                                                alt="Foto Pengguna"
                                                class="w-11 h-11 rounded-full object-cover border border-slate-200">
                                        <?php else: ?>
                                            <div
                                                class="w-11 h-11 rounded-full bg-blue-700 text-white flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($row['nama_dosen'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="font-semibold text-slate-800">
                                                <?= htmlspecialchars($row['nama_dosen']); ?>
                                            </div>

                                            <div class="text-xs text-slate-500">
                                                <?= htmlspecialchars($row['nidn'] ?? '-'); ?>
                                            </div>

                                            <div class="lg:hidden mt-1 text-xs text-blue-600">
                                                <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                            </div>
                                        </div>

                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        NIDN: <?= htmlspecialchars($row['nidn'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        NIDK: <?= htmlspecialchars($row['nidk'] ?? '-'); ?> • NIP: <?= htmlspecialchars($row['nip'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500 break-all">
                                        Feeder: <?= htmlspecialchars($row['id_dosen_feeder'] ?: ($row['id_feeder'] ?? '-')); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['jenjang'] ?? '-'); ?> •
                                        <?= htmlspecialchars($row['kode_prodi'] ?? '-'); ?>
                                    </div>
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
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['username'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['status_user'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                    <div class="mt-2">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= ($row['status_sync_feeder'] ?? '') == 'sudah' ? 'bg-blue-100 text-blue-700' : (($row['status_sync_feeder'] ?? '') == 'gagal' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'); ?>">
                                            Feeder: <?= htmlspecialchars($row['status_sync_feeder'] ?? 'belum'); ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">

                                        <a href="detail_dosen.php?id=<?= $row['id_dosen']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_dosen.php?id=<?= $row['id_dosen']; ?>"
                                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($cek_jadwal < 1): ?>
                                            <a href="hapus_dosen.php?id=<?= $row['id_dosen']; ?>"
                                                onclick="return confirm('Yakin ingin menghapus data dosen ini?')"
                                                class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200"
                                                title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="alert('Dosen tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
                                                class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-slate-200 text-slate-500 cursor-not-allowed"
                                                title="Tidak dapat dihapus">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                Data dosen tidak ditemukan.
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

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
