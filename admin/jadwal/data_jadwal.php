<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/jadwal_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Data Jadwal";
$page_subtitle = "Monitoring jadwal perkuliahan, kelas kuliah, dosen pengajar, dan ruangan.";

$keyword = mysqli_real_escape_string($conn, $_GET['keyword'] ?? '');
$filter_tahun = intval($_GET['tahun'] ?? 0);
$filter_status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

$where = "WHERE 1=1";
if ($keyword !== '') {
    $where .= " AND (
        mk.kode_mk LIKE '%$keyword%' OR
        mk.nama_mk LIKE '%$keyword%' OR
        k.nama_kelas LIKE '%$keyword%' OR
        d.nama_dosen LIKE '%$keyword%' OR
        r.nama_ruangan LIKE '%$keyword%'
    )";
}
if ($filter_tahun > 0) {
    $where .= " AND j.id_tahun = '$filter_tahun'";
}
if ($filter_status !== '') {
    $where .= " AND j.status = '$filter_status'";
}

$total_jadwal = jadwal_count($conn, "SELECT COUNT(*) total FROM jadwal_kuliah");
$total_aktif = jadwal_count($conn, "SELECT COUNT(*) total FROM jadwal_kuliah WHERE status='aktif'");
$total_kelas_kuliah = jadwal_count($conn, "SELECT COUNT(*) total FROM kelas_kuliah");
$total_pengajar = jadwal_count($conn, "SELECT COUNT(*) total FROM dosen_pengajar_kelas");

$data_tahun = jadwal_fetch_all($conn, "SELECT * FROM tahun_akademik ORDER BY status ASC, tahun DESC, semester ASC");

$data_jadwal = jadwal_fetch_all($conn, "
    SELECT
        j.*,
        ta.tahun,
        ta.semester AS semester_tahun,
        k.nama_kelas,
        k.kode_kelas,
        mk.kode_mk,
        mk.nama_mk,
        mk.total_sks,
        d.nama_dosen,
        r.nama_ruangan
    FROM jadwal_kuliah j
    JOIN tahun_akademik ta ON ta.id_tahun = j.id_tahun
    JOIN kelas k ON k.id_kelas = j.id_kelas
    JOIN mata_kuliah mk ON mk.id_mk = j.id_mk
    JOIN dosen d ON d.id_dosen = j.id_dosen
    JOIN ruangan r ON r.id_ruangan = j.id_ruangan
    $where
    ORDER BY ta.tahun DESC, FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), j.jam_mulai ASC
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Jadwal</p>
            <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($total_jadwal); ?></h2>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Jadwal Aktif</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_aktif); ?></h2>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Kelas Kuliah</p>
            <h2 class="text-3xl font-bold text-indigo-700 mt-2"><?= number_format($total_kelas_kuliah); ?></h2>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Dosen Pengajar</p>
            <h2 class="text-3xl font-bold text-amber-700 mt-2"><?= number_format($total_pengajar); ?></h2>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Jadwal Kuliah</h2>
                <p class="text-sm text-slate-500 mt-1">Jadwal menjadi sumber kelas kuliah dan dosen pengajar untuk NeoFeeder.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="../sinkronisasi/rebuild_akademik_inti.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white font-semibold">
                    <i class="fa-solid fa-arrows-rotate mr-2"></i> Rebuild
                </a>
                <a href="tambah_jadwal.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-solid fa-plus mr-2"></i> Tambah
                </a>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-6">
            <input type="text" name="keyword" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari jadwal..." class="w-full rounded-xl border border-slate-300 px-4 py-3">
            <select name="tahun" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <option value="0">Semua Tahun</option>
                <?php foreach ($data_tahun as $ta): ?>
                    <option value="<?= $ta['id_tahun']; ?>" <?= $filter_tahun == $ta['id_tahun'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($ta['tahun'] . ' - ' . $ta['semester']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $filter_status === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="nonaktif" <?= $filter_status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
            </select>
            <button class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Jadwal</th>
                        <th class="px-4 py-3 text-left">Mata Kuliah</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-left">Dosen</th>
                        <th class="px-4 py-3 text-left">Ruangan</th>
                        <th class="px-4 py-3 text-left">Sync</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_jadwal)): ?>
                        <?php foreach ($data_jadwal as $row): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold"><?= htmlspecialchars($row['hari']); ?>, <?= htmlspecialchars(substr($row['jam_mulai'], 0, 5)); ?>-<?= htmlspecialchars(substr($row['jam_selesai'], 0, 5)); ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['tahun'] . ' ' . $row['semester_tahun']); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold"><?= htmlspecialchars($row['kode_mk']); ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nama_mk']); ?></div>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_kelas']); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_dosen']); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($row['nama_ruangan']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= ($row['status_sync_feeder'] ?? 'belum') === 'sudah' ? 'bg-green-100 text-green-700' : (($row['status_sync_feeder'] ?? '') === 'gagal' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'); ?>">
                                        <?= htmlspecialchars($row['status_sync_feeder'] ?? 'belum'); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <a href="detail_jadwal.php?id=<?= $row['id_jadwal']; ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-blue-100 text-blue-700"><i class="fa-solid fa-eye"></i></a>
                                        <a href="edit_jadwal.php?id=<?= $row['id_jadwal']; ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-yellow-100 text-yellow-700"><i class="fa-solid fa-pen"></i></a>
                                        <a href="hapus_jadwal.php?id=<?= $row['id_jadwal']; ?>" onclick="return confirm('Yakin menghapus jadwal ini?')" class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-red-100 text-red-700"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Data jadwal belum tersedia.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
