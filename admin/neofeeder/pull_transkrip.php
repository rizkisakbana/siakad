<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/neofeeder_helper.php";
require_once __DIR__ . "/neofeeder_admin_helper.php";
require_once __DIR__ . "/pull_perkuliahan_inti_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Pull Transkrip NeoFeeder";
$page_subtitle = "Menarik transkrip dan riwayat nilai mahasiswa";
$summary_key = 'pull_transkrip_summary';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pull_data'])) {
    $limit = max(1, min(500, (int) ($_POST['limit'] ?? 50)));
    $offset = max(0, (int) ($_POST['offset'] ?? 0));
    $summary = pull_transkrip_pelaporan($conn, $limit, $offset);
    $_SESSION[$summary_key] = $summary;
    simpan_log($conn, $_SESSION['id_user'], "Pull Transkrip NeoFeeder. Insert: {$summary['insert']}, Update: {$summary['update']}, Gagal: {$summary['gagal']}", "Neo Feeder");
    set_alert($summary['gagal'] > 0 ? 'warning' : 'success', 'Pull transkrip selesai diproses.');
    header("Location: pull_transkrip.php");
    exit;
}

$summary = $_SESSION[$summary_key] ?? null;
unset($_SESSION[$summary_key]);

$total_lokal = nf_count($conn, "SELECT COUNT(*) total FROM transkrip_mahasiswa");
$total_mahasiswa_reg = nf_count($conn, "SELECT COUNT(*) total FROM mahasiswa WHERE id_registrasi_feeder IS NOT NULL AND id_registrasi_feeder <> ''");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Pull Transkrip NeoFeeder</h2>
            <p class="text-sm text-slate-500">Mengambil GetTranskripMahasiswa, dengan fallback GetRiwayatNilaiMahasiswa.</p>
        </div>
        <a href="data_pull.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">Kembali</a>
    </div>

    <?php if ($summary): ?>
        <section class="grid grid-cols-1 sm:grid-cols-5 gap-5 mb-6">
            <?php foreach (['total' => 'Mahasiswa Diproses', 'insert' => 'Insert', 'update' => 'Update', 'skip' => 'Kosong', 'gagal' => 'Gagal'] as $key => $label): ?>
                <div class="bg-white rounded-2xl shadow border p-5">
                    <p class="text-sm text-slate-500"><?= $label; ?></p>
                    <h2 class="text-3xl font-bold text-blue-700 mt-2"><?= number_format($summary[$key] ?? 0); ?></h2>
                </div>
            <?php endforeach; ?>
        </section>

        <?php if (!empty($summary['pesan_gagal'])): ?>
            <div class="mb-6 p-5 rounded-2xl bg-yellow-50 border border-yellow-200 text-yellow-700">
                <h3 class="font-bold mb-3">Catatan Gagal</h3>
                <ul class="list-disc pl-5 text-sm space-y-1">
                    <?php foreach ($summary['pesan_gagal'] as $pesan): ?>
                        <li><?= htmlspecialchars($pesan); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Limit Mahasiswa</label>
                    <input type="number" name="limit" value="50" min="1" max="500" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Offset Mahasiswa</label>
                    <input type="number" name="offset" value="0" min="0" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" name="pull_data" value="1" onclick="return confirm('Tarik transkrip dari NeoFeeder sekarang?')" class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-cloud-arrow-down mr-2"></i> Pull Transkrip
                    </button>
                </div>
            </form>
        </div>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Status Lokal</h3>
            <div class="space-y-3">
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                    <p class="text-blue-700 font-semibold">Total Transkrip</p>
                    <p class="text-2xl font-bold"><?= number_format($total_lokal); ?></p>
                </div>
                <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                    <p class="text-green-700 font-semibold">Mahasiswa Dengan ID Registrasi</p>
                    <p class="text-2xl font-bold"><?= number_format($total_mahasiswa_reg); ?></p>
                </div>
            </div>
        </aside>
    </section>
</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
