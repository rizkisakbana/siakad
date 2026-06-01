<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/kelas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Kelas";
$page_subtitle = "Kelola master data kelas per program studi dan tahun akademik";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_prodi = intval($_GET['prodi'] ?? 0);
$filter_tahun = intval($_GET['tahun'] ?? 0);
$filter_status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        kelas.kode_kelas LIKE '%$keyword%' OR
        kelas.nama_kelas LIKE '%$keyword%' OR
        kelas.angkatan LIKE '%$keyword%' OR
        prodi.nama_prodi LIKE '%$keyword%' OR
        tahun_akademik.tahun LIKE '%$keyword%' OR
        tahun_akademik.semester LIKE '%$keyword%'
    )";
}

if ($filter_prodi > 0) {
    $where .= " AND kelas.id_prodi = '$filter_prodi'";
}

if ($filter_tahun > 0) {
    $where .= " AND kelas.id_tahun = '$filter_tahun'";
}

if (!empty($filter_status)) {
    $where .= " AND kelas.status = '$filter_status'";
}

$total_kelas = kelas_count($conn, "
    SELECT COUNT(*) AS total FROM kelas
");

$total_aktif = kelas_count($conn, "
    SELECT COUNT(*) AS total FROM kelas WHERE status = 'aktif'
");

$total_nonaktif = kelas_count($conn, "
    SELECT COUNT(*) AS total FROM kelas WHERE status = 'nonaktif'
");

$total_kapasitas = kelas_count($conn, "
    SELECT COALESCE(SUM(kapasitas), 0) AS total FROM kelas
");

$data_prodi = kelas_fetch_all($conn, "
    SELECT * FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$data_tahun = kelas_fetch_all($conn, "
    SELECT * FROM tahun_akademik
    ORDER BY status ASC, tahun DESC, semester ASC
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = kelas_count($conn, "
    SELECT COUNT(*) AS total
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    $where
");

$total_page = ceil($total_data_filter / $limit);

$data_kelas = kelas_fetch_all($conn, "
    SELECT 
        kelas.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        tahun_akademik.tahun,
        tahun_akademik.semester AS semester_tahun,
        tahun_akademik.status AS status_tahun
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    LEFT JOIN tahun_akademik ON kelas.id_tahun = tahun_akademik.id_tahun
    $where
    ORDER BY kelas.id_kelas DESC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'prodi' => $filter_prodi,
    'tahun' => $filter_tahun,
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
            <p class="text-sm text-slate-500">Total Kelas</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_kelas); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kelas Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kelas Nonaktif</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_nonaktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Kapasitas</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_kapasitas); ?></h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Master Data Kelas</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Data kelas terhubung dengan program studi dan tahun akademik.
                </p>
            </div>
            <div class="flex flex-col md:flex-row gap-3 mb-8">
                <a href="tambah_kelas.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>

                <!-- <a href="peserta_kelas.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-sky-700 hover:bg-sky-800 text-white font-semibold">
                    <i class="fa-solid fa-users mr-2"></i>
                    Peserta Kelas
                </a> -->
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari kelas..."
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

            <select name="tahun"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Tahun Akademik</option>
                <?php foreach ($data_tahun as $tahun): ?>
                    <option value="<?= $tahun['id_tahun']; ?>" <?= $filter_tahun == $tahun['id_tahun'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($tahun['tahun']); ?> - <?= htmlspecialchars($tahun['semester']); ?>
                        <?= $tahun['status'] == 'aktif' ? '(Aktif)' : ''; ?>
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
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Program Studi</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Tahun Akademik</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Angkatan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Semester</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kapasitas</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_kelas)): ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_kelas as $row): ?>

                            <?php
                            $id_kelas = intval($row['id_kelas']);

                            $cek_mahasiswa = kelas_count($conn, "
                                SELECT COUNT(*) AS total 
                                FROM mahasiswa 
                                WHERE id_kelas = '$id_kelas'
                            ");

                            $cek_jadwal = kelas_count($conn, "
                                SELECT COUNT(*) AS total 
                                FROM jadwal_kuliah 
                                WHERE id_kelas = '$id_kelas'
                            ");
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_kelas']); ?>
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['kode_kelas'] ?? '-'); ?>
                                    </div>

                                    <div class="lg:hidden mt-2">
                                        <div class="text-xs text-blue-600">
                                            <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            Semester <?= htmlspecialchars($row['semester'] ?? '-'); ?> • Kapasitas
                                            <?= number_format($row['kapasitas'] ?? 0); ?>
                                        </div>
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
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['tahun'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['semester_tahun'] ?? '-'); ?>
                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= htmlspecialchars($row['angkatan'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    Semester <?= htmlspecialchars($row['semester'] ?? '-'); ?>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?= number_format($row['kapasitas'] ?? 0); ?> Mahasiswa
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $row['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">

                                        <a href="peserta_kelas.php?id=<?= $row['id_kelas']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 hover:bg-emerald-200"
                                            title="Peserta Kelas">
                                            <i class="fa-solid fa-users"></i>
                                        </a>
                                        <a href="detail_kelas.php?id=<?= $row['id_kelas']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_kelas.php?id=<?= $row['id_kelas']; ?>"
                                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($cek_mahasiswa < 1 && $cek_jadwal < 1): ?>
                                            <a href="hapus_kelas.php?id=<?= $row['id_kelas']; ?>"
                                                onclick="return confirm('Yakin ingin menghapus data kelas ini?')"
                                                class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200"
                                                title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button"
                                                onclick="alert('Kelas tidak dapat dihapus karena sudah digunakan pada data mahasiswa atau jadwal kuliah.')"
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
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                Data kelas tidak ditemukan.
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
