<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Mata Kuliah";
$page_subtitle = "Kelola master data mata kuliah berdasarkan kurikulum dan program studi";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_kurikulum = intval($_GET['kurikulum'] ?? 0);
$filter_prodi = intval($_GET['prodi'] ?? 0);
$filter_semester = intval($_GET['semester'] ?? 0);

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        mata_kuliah.kode_mk LIKE '%$keyword%' OR
        mata_kuliah.nama_mk LIKE '%$keyword%' OR
        mata_kuliah.jenis_mk LIKE '%$keyword%' OR
        kurikulum.nama_kurikulum LIKE '%$keyword%' OR
        prodi.nama_prodi LIKE '%$keyword%'
    )";
}

if ($filter_kurikulum > 0) {
    $where .= " AND mata_kuliah.id_kurikulum = '$filter_kurikulum'";
}

if ($filter_prodi > 0) {
    $where .= " AND kurikulum.id_prodi = '$filter_prodi'";
}

if ($filter_semester > 0) {
    $where .= " AND mata_kuliah.semester = '$filter_semester'";
}

$total_mk = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mata_kuliah
"))['total'] ?? 0;

$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mata_kuliah WHERE status = 'aktif'
"))['total'] ?? 0;

$total_nonaktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mata_kuliah WHERE status = 'nonaktif'
"))['total'] ?? 0;

$total_sks = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(total_sks), 0) AS total FROM mata_kuliah
"))['total'] ?? 0;

$data_prodi = mysqli_query($conn, "
    SELECT * FROM prodi 
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$data_kurikulum_filter = mysqli_query($conn, "
    SELECT 
        kurikulum.*,
        prodi.nama_prodi,
        prodi.jenjang
    FROM kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    WHERE kurikulum.status = 'aktif'
    ORDER BY prodi.nama_prodi ASC, kurikulum.tahun_kurikulum DESC
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM mata_kuliah
    LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_matakuliah = mysqli_query($conn, "
    SELECT 
        mata_kuliah.*,
        kurikulum.nama_kurikulum,
        kurikulum.tahun_kurikulum,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang
    FROM mata_kuliah
    LEFT JOIN kurikulum ON mata_kuliah.id_kurikulum = kurikulum.id_kurikulum
    LEFT JOIN prodi ON kurikulum.id_prodi = prodi.id_prodi
    $where
    ORDER BY prodi.nama_prodi ASC, mata_kuliah.semester ASC, mata_kuliah.kode_mk ASC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'prodi' => $filter_prodi,
    'kurikulum' => $filter_kurikulum,
    'semester' => $filter_semester
]);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Mata Kuliah</p>
                    <h2 class="text-3xl font-bold text-blue-700 mt-2">
                        <?= number_format($total_mk); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-book text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Mata Kuliah Aktif</p>
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
                    <p class="text-sm text-slate-500">Mata Kuliah Nonaktif</p>
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
                    <p class="text-sm text-slate-500">Total SKS</p>
                    <h2 class="text-3xl font-bold text-purple-700 mt-2">
                        <?= number_format($total_sks); ?>
                    </h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center">
                    <i class="fa-solid fa-layer-group text-xl"></i>
                </div>
            </div>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">
                    Master Mata Kuliah
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Data mata kuliah tersusun berdasarkan kurikulum dan program studi.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="../neofeeder/pull_matakuliah.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-download mr-2"></i>
                    Pull MK
                </a>

                <a href="../neofeeder/pull_matkul_kurikulum.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white font-semibold">
                    <i class="fa-solid fa-layer-group mr-2"></i>
                    Pull Relasi
                </a>

                <a href="tambah_matakuliah.php"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text"
                   name="keyword"
                   value="<?= htmlspecialchars($keyword); ?>"
                   placeholder="Cari kode/nama mata kuliah..."
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

            <select name="prodi"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Prodi</option>
                <?php while ($prodi = mysqli_fetch_assoc($data_prodi)): ?>
                    <option value="<?= $prodi['id_prodi']; ?>" <?= $filter_prodi == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($prodi['nama_prodi']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="kurikulum"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Kurikulum</option>
                <?php while ($kurikulum = mysqli_fetch_assoc($data_kurikulum_filter)): ?>
                    <option value="<?= $kurikulum['id_kurikulum']; ?>" <?= $filter_kurikulum == $kurikulum['id_kurikulum'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($kurikulum['nama_kurikulum']); ?> - <?= htmlspecialchars($kurikulum['nama_prodi']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="semester"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Semester</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?= $i; ?>" <?= $filter_semester == $i ? 'selected' : ''; ?>>
                        Semester <?= $i; ?>
                    </option>
                <?php endfor; ?>
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
                        <th class="px-4 py-3 text-left">Mata Kuliah</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kurikulum</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Prodi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Semester</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">SKS</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Jenis</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($data_matakuliah) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_matakuliah)): ?>

                            <?php
                                $id_mk = intval($row['id_mk']);

                                $cek_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
                                    SELECT COUNT(*) AS total 
                                    FROM jadwal_kuliah 
                                    WHERE id_mk = '$id_mk'
                                "))['total'] ?? 0;
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <?= $no++; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_mk']); ?>
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['kode_mk']); ?> • <?= number_format($row['total_sks']); ?> SKS
                                    </div>

                                    <div class="lg:hidden mt-2">
                                        <div class="text-xs text-slate-600">
                                            Semester <?= htmlspecialchars($row['semester']); ?> • <?= htmlspecialchars($row['jenis_mk']); ?>
                                        </div>

                                        <div class="text-xs text-slate-400 mt-1">
                                            <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_kurikulum'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['tahun_kurikulum'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['jenjang'] ?? '-'); ?> • <?= htmlspecialchars($row['kode_prodi'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    Semester <?= htmlspecialchars($row['semester']); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= number_format($row['total_sks']); ?> SKS
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        T: <?= number_format($row['sks_teori']); ?> • P: <?= number_format($row['sks_praktik']); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $row['jenis_mk'] == 'wajib' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'; ?>">
                                        <?= htmlspecialchars($row['jenis_mk']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_matakuliah.php?id=<?= $row['id_mk']; ?>"
                                           class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                           title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_matakuliah.php?id=<?= $row['id_mk']; ?>"
                                           class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                           title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($cek_jadwal < 1): ?>
                                            <a href="hapus_matakuliah.php?id=<?= $row['id_mk']; ?>"
                                               onclick="return confirm('Yakin ingin menghapus mata kuliah ini?')"
                                               class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200"
                                               title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button"
                                                    onclick="alert('Mata kuliah tidak dapat dihapus karena sudah digunakan pada jadwal kuliah.')"
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
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                Data mata kuliah tidak ditemukan.
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
                            <a href="?<?= $query_string; ?>&page=<?= $i; ?>"
                               class="px-4 py-2 rounded-xl text-sm font-semibold
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
