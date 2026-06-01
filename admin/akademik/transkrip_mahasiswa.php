<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Transkrip Mahasiswa";
$page_subtitle = "Ringkasan kesiapan transkrip berdasarkan KHS dan nilai yang sudah tersedia.";

$keyword = mysqli_real_escape_string($conn, trim($_GET['q'] ?? ''));
$where = "";
if ($keyword !== '') {
    $where = "WHERE m.nim LIKE '%$keyword%' OR m.nama_mahasiswa LIKE '%$keyword%' OR p.nama_prodi LIKE '%$keyword%'";
}

$cards = [
    ['label' => 'Mahasiswa Aktif', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE status_mahasiswa = 'aktif'"))],
    ['label' => 'Nilai Publish', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM nilai WHERE status_publish = 'publish'"))],
    ['label' => 'Transkrip Feeder', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM transkrip_mahasiswa"))],
    ['label' => 'Lulus/DO', 'value' => number_format(internal_count($conn, "SELECT COUNT(*) total FROM mahasiswa_lulus_do"))],
];

$data_transkrip = internal_fetch_all($conn, "
    SELECT
        m.id_mahasiswa,
        m.nim,
        m.nama_mahasiswa,
        p.nama_prodi,
        COALESCE(t.total_sks_feeder, kh.total_sks_internal, 0) AS total_sks,
        COALESCE(t.ipk_feeder, kh.ipk_internal, 0) AS ipk,
        COALESCE(t.total_mk_feeder, n.total_mk_internal, 0) AS total_mk,
        COALESCE(t.last_sync_feeder, kh.last_update_internal) AS last_update
    FROM mahasiswa m
    LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
    LEFT JOIN (
        SELECT
            id_mahasiswa,
            COUNT(*) AS total_mk_feeder,
            SUM(COALESCE(sks_mk, 0)) AS total_sks_feeder,
            CASE
                WHEN SUM(COALESCE(sks_mk, 0)) > 0
                THEN ROUND(SUM(COALESCE(sks_mk, 0) * COALESCE(nilai_indeks, 0)) / SUM(COALESCE(sks_mk, 0)), 2)
                ELSE 0
            END AS ipk_feeder,
            MAX(last_sync_feeder) AS last_sync_feeder
        FROM transkrip_mahasiswa
        GROUP BY id_mahasiswa
    ) t ON t.id_mahasiswa = m.id_mahasiswa
    LEFT JOIN (
        SELECT id_mahasiswa, SUM(sks_semester) AS total_sks_internal, MAX(ipk) AS ipk_internal, MAX(updated_at) AS last_update_internal
        FROM khs
        GROUP BY id_mahasiswa
    ) kh ON kh.id_mahasiswa = m.id_mahasiswa
    LEFT JOIN (
        SELECT id_mahasiswa, COUNT(*) AS total_mk_internal
        FROM nilai
        WHERE status_publish = 'publish'
        GROUP BY id_mahasiswa
    ) n ON n.id_mahasiswa = m.id_mahasiswa
    $where
    ORDER BY m.nim ASC
    LIMIT 100
");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Transkrip Mahasiswa</h2>
            <p class="text-sm text-slate-500 mt-1">Ringkasan transkrip dari nilai internal dan snapshot NeoFeeder.</p>
        </div>
        <a href="../neofeeder/pull_transkrip.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
            <i class="fa-solid fa-cloud-arrow-down mr-2"></i> Pull Transkrip
        </a>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
        <?php foreach ($cards as $card): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
                <p class="text-sm text-slate-500"><?= htmlspecialchars($card['label']); ?></p>
                <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= htmlspecialchars((string)$card['value']); ?></h3>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
        <form method="GET" class="mb-5 flex flex-col sm:flex-row gap-3">
            <input type="text" name="q" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari NIM, nama, atau prodi"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            <button class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold">
                <i class="fa-solid fa-search mr-2"></i> Cari
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left">NIM</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Prodi</th>
                        <th class="px-4 py-3 text-center">MK</th>
                        <th class="px-4 py-3 text-center">SKS</th>
                        <th class="px-4 py-3 text-center">IPK</th>
                        <th class="px-4 py-3 text-left">Update</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($data_transkrip)): ?>
                        <?php foreach ($data_transkrip as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= htmlspecialchars($r['nim'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($r['nama_mahasiswa'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($r['nama_prodi'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-center"><?= number_format((int)($r['total_mk'] ?? 0)); ?></td>
                                <td class="px-4 py-3 text-center"><?= number_format((int)($r['total_sks'] ?? 0)); ?></td>
                                <td class="px-4 py-3 text-center font-bold text-blue-700"><?= htmlspecialchars((string)($r['ipk'] ?? '0.00')); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars(tanggal_jam_indonesia($r['last_update'] ?? null)); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="detail_transkrip.php?id=<?= (int)$r['id_mahasiswa']; ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold text-xs">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">Belum ada data transkrip.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
