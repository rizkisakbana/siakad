<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Log Neo Feeder";
$page_subtitle = "Detail request dan response integrasi Neo Feeder PDDikti";

$id_log = intval($_GET['id'] ?? 0);

if ($id_log <= 0) {
    set_alert("error", "ID log tidak valid.");
    header("Location: log_sinkronisasi.php");
    exit;
}

$query = mysqli_query($conn, "
    SELECT *
    FROM neofeeder_log
    WHERE id_log = '$id_log'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data log tidak ditemukan.");
    header("Location: log_sinkronisasi.php");
    exit;
}

$data = mysqli_fetch_assoc($query);

$status_class = $data['status'] == 'success'
    ? 'bg-green-100 text-green-700'
    : 'bg-red-100 text-red-700';

$request_json = json_decode($data['request_payload'] ?? '', true);
$response_json = json_decode($data['response_payload'] ?? '', true);

$request_pretty = json_last_error() === JSON_ERROR_NONE
    ? json_encode($request_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    : ($data['request_payload'] ?? '-');

$response_pretty = json_last_error() === JSON_ERROR_NONE
    ? json_encode($response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    : ($data['response_payload'] ?? '-');

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Log Neo Feeder</h2>
            <p class="text-sm text-slate-500">
                Menampilkan request dan response lengkap dari Neo Feeder/PDDikti.
            </p>
        </div>

        <a href="log_sinkronisasi.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Modul</p>
            <h2 class="text-2xl font-bold text-blue-700 mt-2">
                <?= htmlspecialchars($data['modul'] ?? '-'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Aksi</p>
            <h2 class="text-2xl font-bold text-purple-700 mt-2">
                <?= htmlspecialchars($data['aksi'] ?? '-'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= htmlspecialchars($data['status'] ?? '-'); ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Waktu</p>
            <h2 class="text-sm font-bold text-slate-700 mt-3">
                <?= !empty($data['created_at']) ? tanggal_jam_indonesia($data['created_at']) : '-'; ?>
            </h2>
        </div>

    </section>

    <?php if (!empty($data['pesan_error'])): ?>
        <div class="mb-6 p-5 rounded-2xl bg-red-50 border border-red-200 text-red-700">
            <h3 class="font-bold mb-2">
                <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                Pesan Error
            </h3>
            <p class="text-sm">
                <?= htmlspecialchars($data['pesan_error']); ?>
            </p>
        </div>
    <?php endif; ?>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Request Payload</h3>
                    <p class="text-sm text-slate-500">
                        Data yang dikirim dari SIAKAD ke Neo Feeder.
                    </p>
                </div>
            </div>

            <pre class="p-4 rounded-xl bg-slate-900 text-green-300 text-xs overflow-x-auto whitespace-pre-wrap max-h-[600px]"><?= htmlspecialchars($request_pretty); ?></pre>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Response Payload</h3>
                    <p class="text-sm text-slate-500">
                        Data balasan dari Neo Feeder/PDDikti.
                    </p>
                </div>
            </div>

            <pre class="p-4 rounded-xl bg-slate-900 text-green-300 text-xs overflow-x-auto whitespace-pre-wrap max-h-[600px]"><?= htmlspecialchars($response_pretty); ?></pre>
        </div>

    </section>

    <section class="mt-6 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

        <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Cepat</h3>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="sinkronisasi.php"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold">
                <i class="fa-solid fa-rotate mr-2"></i>
                Pusat Sinkronisasi
            </a>

            <a href="test_koneksi.php"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-100 hover:bg-green-200 text-green-700 font-semibold">
                <i class="fa-solid fa-plug mr-2"></i>
                Test Koneksi
            </a>

            <a href="log_sinkronisasi.php"
               class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-slate-100 font-semibold">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </div>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>