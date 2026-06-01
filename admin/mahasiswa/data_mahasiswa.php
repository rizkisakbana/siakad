<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Mahasiswa";
$page_subtitle = "Kelola master data mahasiswa SIAKAD";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_prodi = intval($_GET['prodi'] ?? 0);
$filter_kelas = intval($_GET['kelas'] ?? 0);
$filter_angkatan = mysqli_real_escape_string($conn, $_GET['angkatan'] ?? '');
$filter_status = mysqli_real_escape_string($conn, $_GET['status_mahasiswa'] ?? '');

$where = "WHERE 1=1";

if (!empty($keyword)) {
    $where .= " AND (
        mahasiswa.nim LIKE '%$keyword%' OR
        mahasiswa.nama_mahasiswa LIKE '%$keyword%' OR
        mahasiswa.email LIKE '%$keyword%' OR
        mahasiswa.no_hp LIKE '%$keyword%' OR
        mahasiswa.nik LIKE '%$keyword%' OR
        mahasiswa.nisn LIKE '%$keyword%' OR
        prodi.nama_prodi LIKE '%$keyword%' OR
        kelas.nama_kelas LIKE '%$keyword%' OR
        users.username LIKE '%$keyword%'
    )";
}

if ($filter_prodi > 0) {
    $where .= " AND mahasiswa.id_prodi = '$filter_prodi'";
}

if ($filter_kelas > 0) {
    $where .= " AND mahasiswa.id_kelas = '$filter_kelas'";
}

if (!empty($filter_angkatan)) {
    $where .= " AND mahasiswa.angkatan = '$filter_angkatan'";
}

if (!empty($filter_status)) {
    $where .= " AND mahasiswa.status_mahasiswa = '$filter_status'";
}

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mahasiswa
"))['total'] ?? 0;

$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mahasiswa WHERE status_mahasiswa = 'aktif'
"))['total'] ?? 0;

$total_lulus = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM mahasiswa WHERE status_mahasiswa = 'lulus'
"))['total'] ?? 0;

$total_nonaktif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM mahasiswa 
    WHERE status_mahasiswa IN ('nonaktif','cuti','drop out','mengundurkan diri','pindah')
"))['total'] ?? 0;

$data_prodi = mysqli_query($conn, "
    SELECT * FROM prodi
    WHERE status = 'aktif'
    ORDER BY nama_prodi ASC
");

$data_kelas = mysqli_query($conn, "
    SELECT 
        kelas.*,
        prodi.nama_prodi,
        prodi.jenjang
    FROM kelas
    LEFT JOIN prodi ON kelas.id_prodi = prodi.id_prodi
    WHERE kelas.status = 'aktif'
    ORDER BY prodi.nama_prodi ASC, kelas.nama_kelas ASC
");

$data_angkatan = mysqli_query($conn, "
    SELECT DISTINCT angkatan 
    FROM mahasiswa 
    WHERE angkatan IS NOT NULL AND angkatan != ''
    ORDER BY angkatan DESC
");

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1)
    $page = 1;

$offset = ($page - 1) * $limit;

$total_data_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM mahasiswa
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    $where
"))['total'] ?? 0;

$total_page = ceil($total_data_filter / $limit);

$data_mahasiswa = mysqli_query($conn, "
    SELECT 
        mahasiswa.*,
        prodi.kode_prodi,
        prodi.nama_prodi,
        prodi.jenjang,
        kelas.kode_kelas,
        kelas.nama_kelas,
        users.username,
        users.foto AS foto_user,
        users.status AS status_user
    FROM mahasiswa
    LEFT JOIN prodi ON mahasiswa.id_prodi = prodi.id_prodi
    LEFT JOIN kelas ON mahasiswa.id_kelas = kelas.id_kelas
    LEFT JOIN users ON mahasiswa.id_user = users.id_user
    $where
    ORDER BY mahasiswa.id_mahasiswa DESC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query([
    'keyword' => $keyword,
    'prodi' => $filter_prodi,
    'kelas' => $filter_kelas,
    'angkatan' => $filter_angkatan,
    'status_mahasiswa' => $filter_status
]);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Mahasiswa</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_mahasiswa); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Mahasiswa Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Mahasiswa Lulus</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_lulus); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Nonaktif/Cuti/DO</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_nonaktif); ?></h2>
        </div>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Master Data Mahasiswa</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Data mahasiswa terhubung dengan akun pengguna, program studi, dan kelas.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="../neofeeder/pull_mahasiswa.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                    <i class="fa-solid fa-cloud-arrow-down mr-2"></i>
                    Pull NeoFeeder
                </a>

                <a href="import_mahasiswa.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold">
                    <i class="fa-solid fa-file-import mr-2"></i>
                    Import
                </a>

                <a href="tambah_mahasiswa.php"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Tambah
                </a>

                <a href="export_mahasiswa_pdf.php"
                    class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg shadow-lg text-center">
                    <i class="fa fa-file-pdf"></i>
                </a>

                <a href="export_mahasiswa_excel.php"
                    class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg shadow-lg text-center">
                    <i class="fa fa-file-excel"></i>
                </a>

                <a href="../neofeeder/sync_mahasiswa.php"
                    class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>
                    Push NeoFeeder
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3 mb-6">
            <input type="hidden" name="page" value="1">

            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari mahasiswa..."
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

            <select name="prodi"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Prodi</option>
                <?php while ($prodi = mysqli_fetch_assoc($data_prodi)): ?>
                    <option value="<?= $prodi['id_prodi']; ?>" <?= $filter_prodi == $prodi['id_prodi'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($prodi['nama_prodi']); ?> - <?= htmlspecialchars($prodi['jenjang']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="kelas"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Kelas</option>
                <?php while ($kelas = mysqli_fetch_assoc($data_kelas)): ?>
                    <option value="<?= $kelas['id_kelas']; ?>" <?= $filter_kelas == $kelas['id_kelas'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($kelas['nama_kelas']); ?> - <?= htmlspecialchars($kelas['nama_prodi']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="angkatan"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Angkatan</option>
                <?php while ($angkatan = mysqli_fetch_assoc($data_angkatan)): ?>
                    <option value="<?= htmlspecialchars($angkatan['angkatan']); ?>"
                        <?= $filter_angkatan == $angkatan['angkatan'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($angkatan['angkatan']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="status_mahasiswa"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filter_status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="cuti" <?= $filter_status == 'cuti' ? 'selected' : ''; ?>>Cuti</option>
                <option value="nonaktif" <?= $filter_status == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                <option value="lulus" <?= $filter_status == 'lulus' ? 'selected' : ''; ?>>Lulus</option>
                <option value="drop out" <?= $filter_status == 'drop out' ? 'selected' : ''; ?>>Drop Out</option>
                <option value="mengundurkan diri" <?= $filter_status == 'mengundurkan diri' ? 'selected' : ''; ?>>
                    Mengundurkan Diri</option>
                <option value="pindah" <?= $filter_status == 'pindah' ? 'selected' : ''; ?>>Pindah</option>
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
                        <th class="px-4 py-3 text-left">Mahasiswa</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Prodi</th>
                        <!-- <th class="px-4 py-3 text-left hidden lg:table-cell">Kelas</th> -->
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Angkatan</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Semester</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Kontak</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Status</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Sync</th>
                        <th class="px-4 py-3 text-center w-44">Aksi</th>
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
                                                class="w-11 h-11 rounded-xl object-cover border border-slate-200">
                                        <?php elseif (!empty($row['foto_user'])): ?>
                                            <img src="../../uploads/pengguna/<?= htmlspecialchars($row['foto_user']); ?>"
                                                alt="Foto Pengguna"
                                                class="w-11 h-11 rounded-xl object-cover border border-slate-200">
                                        <?php else: ?>
                                            <div
                                                class="w-11 h-11 rounded-xl bg-blue-700 text-white flex items-center justify-center font-bold">
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

                                            <div class="lg:hidden mt-1 text-xs text-blue-600">
                                                <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                            </div>
                                        </div>

                                    </div>
                                </td>

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_prodi'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['jenjang'] ?? '-'); ?> -
                                        <?= htmlspecialchars($row['kode_prodi'] ?? '-'); ?>
                                    </div>
                                </td>

                                <!-- <td class="px-4 py-3 hidden lg:table-cell">
                                    <div class="font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_kelas'] ?? '-'); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($row['kode_kelas'] ?? '-'); ?>
                                    </div>
                                </td> -->

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

                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <?php if (($row['status_sync_feeder'] ?? 'belum') == 'sudah'): ?>
                                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
                                            Sudah
                                        </span>
                                    <?php elseif (($row['status_sync_feeder'] ?? 'belum') == 'gagal'): ?>
                                        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                                            Gagal
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">
                                            Belum
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($row['last_sync_feeder'])): ?>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?= tanggal_jam_indonesia($row['last_sync_feeder']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">

                                        <a href="detail_mahasiswa.php?id=<?= $row['id_mahasiswa']; ?>"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                                            title="Detail">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="edit_mahasiswa.php?id=<?= $row['id_mahasiswa']; ?>"
                                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-yellow-100 text-yellow-700 hover:bg-yellow-200"
                                            title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <a href="hapus_mahasiswa.php?id=<?= $row['id_mahasiswa']; ?>"
                                            onclick="return confirm('Yakin ingin menonaktifkan/menghapus data mahasiswa ini?')"
                                            class="hidden lg:inline-flex w-10 h-10 items-center justify-center rounded-xl bg-red-100 text-red-700 hover:bg-red-200"
                                            title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                Data mahasiswa tidak ditemukan.
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
