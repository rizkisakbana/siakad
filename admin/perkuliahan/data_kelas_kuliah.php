<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Kelas Kuliah";
$page_subtitle = "Kelas kuliah resmi untuk pelaporan NeoFeeder/PDDikti";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_tahun = intval($_GET['tahun'] ?? 0);
$filter_prodi = intval($_GET['prodi'] ?? 0);

$where = "WHERE 1=1";
if ($keyword !== '') {
    $where .= " AND (
        kk.nama_kelas_kuliah LIKE '%$keyword%' OR
        kk.id_kelas_kuliah_feeder LIKE '%$keyword%' OR
        mk.kode_mk LIKE '%$keyword%' OR
        mk.nama_mk LIKE '%$keyword%' OR
        p.nama_prodi LIKE '%$keyword%'
    )";
}
if ($filter_tahun > 0) {
    $where .= " AND kk.id_tahun = '$filter_tahun'";
}
if ($filter_prodi > 0) {
    $where .= " AND kk.id_prodi = '$filter_prodi'";
}

$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM kelas_kuliah"))['total'] ?? 0;
$total_pengajar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas"))['total'] ?? 0;
$total_peserta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM peserta_kelas_kuliah"))['total'] ?? 0;
$total_nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM nilai"))['total'] ?? 0;

$data_tahun = mysqli_query($conn, "SELECT id_tahun, tahun, semester, id_semester_feeder FROM tahun_akademik ORDER BY tahun DESC, semester ASC");
$data_prodi = mysqli_query($conn, "SELECT id_prodi, nama_prodi, jenjang FROM prodi WHERE status='aktif' ORDER BY nama_prodi ASC");

$limit = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$total_filter = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) total
    FROM kelas_kuliah kk
    LEFT JOIN mata_kuliah mk ON kk.id_mk = mk.id_mk
    LEFT JOIN prodi p ON kk.id_prodi = p.id_prodi
    $where
"))['total'] ?? 0;
$total_page = max(1, ceil($total_filter / $limit));

$data_kelas = mysqli_query($conn, "
    SELECT
        kk.*, mk.kode_mk, mk.nama_mk, mk.total_sks,
        p.nama_prodi, p.jenjang, ta.tahun, ta.semester AS semester_tahun,
        (SELECT COUNT(*) FROM dosen_pengajar_kelas dpk WHERE dpk.id_kelas_kuliah = kk.id_kelas_kuliah) total_dosen,
        (SELECT COUNT(*) FROM peserta_kelas_kuliah pkk WHERE pkk.id_kelas_kuliah = kk.id_kelas_kuliah) total_peserta,
        (SELECT COUNT(*) FROM nilai n WHERE n.id_kelas_kuliah = kk.id_kelas_kuliah) total_nilai
    FROM kelas_kuliah kk
    LEFT JOIN mata_kuliah mk ON kk.id_mk = mk.id_mk
    LEFT JOIN prodi p ON kk.id_prodi = p.id_prodi
    LEFT JOIN tahun_akademik ta ON kk.id_tahun = ta.id_tahun
    $where
    ORDER BY ta.tahun DESC, ta.semester ASC, p.nama_prodi ASC, mk.kode_mk ASC, kk.nama_kelas_kuliah ASC
    LIMIT $limit OFFSET $offset
");

$query_string = http_build_query(['keyword' => $keyword, 'tahun' => $filter_tahun, 'prodi' => $filter_prodi]);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Kelas Kuliah</p><h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_kelas); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Dosen Pengajar</p><h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_pengajar); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Peserta KRS</p><h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_peserta); ?></h2></div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5"><p class="text-sm text-slate-500">Nilai</p><h2 class="text-3xl font-bold text-orange-700 mt-2"><?= number_format($total_nilai); ?></h2></div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Kelas Kuliah PDDikti</h2>
                <p class="text-sm text-slate-500 mt-1">Data ini menjadi pusat relasi dosen pengajar, peserta KRS, dan nilai.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="../neofeeder/pull_kelas_kuliah.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold"><i class="fa-solid fa-download mr-2"></i> Pull Kelas</a>
                <a href="../neofeeder/pull_dosen_pengajar.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold"><i class="fa-solid fa-chalkboard-user mr-2"></i> Pull Pengajar</a>
                <a href="../neofeeder/pull_peserta_kelas.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold"><i class="fa-solid fa-users mr-2"></i> Pull Peserta</a>
                <a href="../neofeeder/pull_nilai.php" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-orange-700 hover:bg-orange-800 text-white font-semibold"><i class="fa-solid fa-square-poll-vertical mr-2"></i> Pull Nilai</a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
            <input type="hidden" name="page" value="1">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari kelas/mata kuliah..." class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            <select name="tahun" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Periode</option>
                <?php while ($tahun = mysqli_fetch_assoc($data_tahun)): ?>
                    <option value="<?= $tahun['id_tahun']; ?>" <?= $filter_tahun == $tahun['id_tahun'] ? 'selected' : ''; ?>><?= htmlspecialchars($tahun['tahun'] . ' - ' . $tahun['semester'] . ' (' . ($tahun['id_semester_feeder'] ?? '-') . ')'); ?></option>
                <?php endwhile; ?>
            </select>
            <select name="prodi" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                <option value="0">Semua Prodi</option>
                <?php while ($prodi = mysqli_fetch_assoc($data_prodi)): ?>
                    <option value="<?= $prodi['id_prodi']; ?>" <?= $filter_prodi == $prodi['id_prodi'] ? 'selected' : ''; ?>><?= htmlspecialchars($prodi['jenjang'] . ' ' . $prodi['nama_prodi']); ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold"><i class="fa-solid fa-filter mr-2"></i> Filter</button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left w-16">No</th>
                        <th class="px-4 py-3 text-left">Kelas Kuliah</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Periode</th>
                        <th class="px-4 py-3 text-left hidden lg:table-cell">Prodi</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Pengajar</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Peserta</th>
                        <th class="px-4 py-3 text-center hidden lg:table-cell">Nilai</th>
                        <th class="px-4 py-3 text-center w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($data_kelas && mysqli_num_rows($data_kelas) > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($data_kelas)): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= $no++; ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($row['kode_mk'] . ' - ' . $row['nama_mk']); ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nama_kelas_kuliah']); ?> &bull; <?= number_format($row['total_sks'] ?? 0); ?> SKS</div>
                                    <div class="lg:hidden text-xs text-blue-600 mt-1"><?= htmlspecialchars(($row['tahun'] ?? '-') . ' ' . ($row['semester_tahun'] ?? '-')); ?></div>
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars(($row['tahun'] ?? '-') . ' - ' . ($row['semester_tahun'] ?? '-')); ?></td>
                                <td class="px-4 py-3 hidden lg:table-cell"><?= htmlspecialchars(($row['jenjang'] ?? '-') . ' ' . ($row['nama_prodi'] ?? '-')); ?></td>
                                <td class="px-4 py-3 text-center hidden lg:table-cell"><?= number_format($row['total_dosen']); ?></td>
                                <td class="px-4 py-3 text-center hidden lg:table-cell"><?= number_format($row['total_peserta']); ?></td>
                                <td class="px-4 py-3 text-center hidden lg:table-cell"><?= number_format($row['total_nilai']); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="detail_kelas_kuliah.php?id=<?= $row['id_kelas_kuliah']; ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200" title="Detail"><i class="fa-solid fa-eye"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Data kelas kuliah belum tersedia.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_page > 1): ?>
            <div class="flex flex-wrap items-center gap-2 mt-6">
                <?php for ($i = 1; $i <= $total_page; $i++): ?>
                    <?php if ($i == 1 || $i == $total_page || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?<?= $query_string; ?>&page=<?= $i; ?>" class="px-4 py-2 rounded-xl text-sm font-semibold <?= $i == $page ? 'bg-blue-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'; ?>"><?= $i; ?></a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="px-2 text-slate-400">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once "../../includes/footer.php"; ?>
