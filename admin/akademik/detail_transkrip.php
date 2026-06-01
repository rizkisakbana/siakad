<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/internal_module_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$id_mahasiswa = intval($_GET['id'] ?? 0);
if ($id_mahasiswa <= 0) {
    set_alert("error", "ID mahasiswa tidak valid.");
    header("Location: transkrip_mahasiswa.php");
    exit;
}

$mahasiswa = internal_query_one($conn, "
    SELECT m.*, p.nama_prodi, p.jenjang
    FROM mahasiswa m
    LEFT JOIN prodi p ON p.id_prodi = m.id_prodi
    WHERE m.id_mahasiswa = '$id_mahasiswa'
    LIMIT 1
");

if (!$mahasiswa) {
    set_alert("error", "Data mahasiswa tidak ditemukan.");
    header("Location: transkrip_mahasiswa.php");
    exit;
}

$page_title = "Detail Transkrip";
$page_subtitle = "Rincian transkrip mahasiswa";

$data_transkrip = internal_fetch_all($conn, "
    SELECT t.*, ta.tahun, ta.semester
    FROM transkrip_mahasiswa t
    LEFT JOIN tahun_akademik ta ON ta.id_tahun = t.id_tahun
    WHERE t.id_mahasiswa = '$id_mahasiswa'
    ORDER BY COALESCE(t.id_semester_feeder, ''), t.kode_mk, t.nama_mk
");

$total_sks = 0;
$total_mutu = 0;
$total_mk = 0;
$rows = [];

foreach ($data_transkrip as $r) {
    $sks = (int)($r['sks_mk'] ?? 0);
    $indeks = (float)($r['nilai_indeks'] ?? 0);
    $total_sks += $sks;
    $total_mutu += $sks * $indeks;
    $total_mk++;
    $rows[] = $r;
}

$ipk = $total_sks > 0 ? round($total_mutu / $total_sks, 2) : 0;

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($mahasiswa['nim'] . ' - ' . $mahasiswa['nama_mahasiswa']); ?></h2>
            <p class="text-sm text-slate-500 mt-1"><?= htmlspecialchars(($mahasiswa['nama_prodi'] ?? '-') . ' ' . ($mahasiswa['jenjang'] ?? '')); ?></p>
        </div>
        <a href="transkrip_mahasiswa.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Mata Kuliah</p>
            <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= number_format($total_mk); ?></h3>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total SKS</p>
            <h3 class="text-2xl font-bold text-slate-800 mt-2"><?= number_format($total_sks); ?></h3>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">IPK</p>
            <h3 class="text-2xl font-bold text-blue-700 mt-2"><?= number_format($ipk, 2); ?></h3>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-left">Kode</th>
                        <th class="px-4 py-3 text-left">Mata Kuliah</th>
                        <th class="px-4 py-3 text-center">SKS</th>
                        <th class="px-4 py-3 text-center">Angka</th>
                        <th class="px-4 py-3 text-center">Huruf</th>
                        <th class="px-4 py-3 text-center">Indeks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3"><?= htmlspecialchars(trim(($r['tahun'] ?? '') . ' ' . ($r['semester'] ?? '')) ?: ($r['id_semester_feeder'] ?? '-')); ?></td>
                                <td class="px-4 py-3 font-semibold text-slate-800"><?= htmlspecialchars($r['kode_mk'] ?? '-'); ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($r['nama_mk'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-center"><?= number_format((int)($r['sks_mk'] ?? 0)); ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars((string)($r['nilai_angka'] ?? '0.00')); ?></td>
                                <td class="px-4 py-3 text-center font-bold"><?= htmlspecialchars($r['nilai_huruf'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars((string)($r['nilai_indeks'] ?? '0.00')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">Data transkrip mahasiswa belum tersedia.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
