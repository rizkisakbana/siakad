<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Test Koneksi Neo Feeder";
$page_subtitle = "Menguji koneksi Web Service Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

$hasil_test = null;
$token_result = null;
$profil_result = null;

if (isset($_POST['test_koneksi'])) {
    $hasil_test = cek_koneksi_feeder($conn);

    simpan_log(
        $conn,
        $_SESSION['id_user'],
        "Melakukan test koneksi Neo Feeder",
        "Neo Feeder"
    );

    if ($hasil_test['success']) {
        set_alert("success", "Koneksi Neo Feeder berhasil.");
    } else {
        set_alert("error", "Koneksi Neo Feeder gagal: " . $hasil_test['message']);
    }

    $token_result = $hasil_test['token'] ?? null;
    $profil_result = $hasil_test['profil'] ?? null;
}

$config = get_neofeeder_config($conn);

$status_class = "bg-slate-100 text-slate-700";
$status_label = "Belum Terhubung";

if ($config) {
    if (($config['status'] ?? '') == 'connected') {
        $status_class = "bg-green-100 text-green-700";
        $status_label = "Connected";
    } else {
        $status_class = "bg-red-100 text-red-700";
        $status_label = "Disconnected";
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <?php show_alert(); ?>

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Test Koneksi Neo Feeder</h2>
            <p class="text-sm text-slate-500">
                Menguji koneksi ke Web Service Neo Feeder/PDDikti menggunakan GetToken dan GetProfilPT.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="pengaturan.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-gear mr-2"></i>
                Pengaturan
            </a>

            <a href="log_sinkronisasi.php"
                class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                Log Sinkronisasi
            </a>
        </div>
    </div> -->

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Status Koneksi</p>
            <div class="mt-3">
                <span class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $status_class; ?>">
                    <?= $status_label; ?>
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Environment</p>
            <h2 class="text-2xl font-bold text-blue-700 mt-2 capitalize">
                <?= htmlspecialchars($config['environment'] ?? '-'); ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Token</p>
            <h2 class="text-lg font-bold text-purple-700 mt-3">
                <?= !empty($config['token']) ? 'Tersimpan' : 'Belum Ada'; ?>
            </h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Terakhir Terhubung</p>
            <h2 class="text-sm font-bold text-slate-700 mt-3">
                <?= !empty($config['last_connected_at']) ? tanggal_jam_indonesia($config['last_connected_at']) : '-'; ?>
            </h2>
        </div>

    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Uji Koneksi Web Service
                </h3>

                <?php if (!$config): ?>
                    <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 mb-5">
                        Konfigurasi Neo Feeder belum tersedia. Silakan isi pengaturan terlebih dahulu.
                    </div>

                    <a href="pengaturan.php"
                        class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-gear mr-2"></i>
                        Buka Pengaturan
                    </a>
                <?php else: ?>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 mb-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-slate-500">Nama PT</p>
                                <p class="font-semibold text-slate-800">
                                    <?= htmlspecialchars($config['nama_pt'] ?? '-'); ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-slate-500">Kode PT</p>
                                <p class="font-semibold text-slate-800">
                                    <?= htmlspecialchars($config['kode_pt'] ?? '-'); ?>
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-slate-500">URL Web Service</p>
                                <p class="font-semibold text-slate-800 break-all">
                                    <?= htmlspecialchars($config['url_ws'] ?? '-'); ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-slate-500">Username</p>
                                <p class="font-semibold text-slate-800">
                                    <?= htmlspecialchars($config['username'] ?? '-'); ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-slate-500">Environment</p>
                                <p class="font-semibold text-slate-800 capitalize">
                                    <?= htmlspecialchars($config['environment'] ?? '-'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="flex flex-col sm:flex-row gap-3">

                        <form method="POST">
                            <button type="submit" name="test_koneksi" value="1"
                                onclick="return confirm('Jalankan test koneksi Neo Feeder sekarang?')"
                                class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                                <i class="fa-solid fa-plug mr-2"></i>
                                Jalankan Test Koneksi
                            </button>
                        </form>

                        <a href="pengaturan.php"
                            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                            <i class="fa-solid fa-gear mr-2"></i>
                            Pengaturan
                        </a>

                        <a href="log_sinkronisasi.php"
                            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                            <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                            Log Sinkronisasi
                        </a>
                        
                    </div> -->

                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="POST" class="w-full sm:w-auto">
                            <button type="submit" name="test_koneksi" value="1"
                                onclick="return confirm('Jalankan test koneksi Neo Feeder sekarang?')"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                                <i class="fa-solid fa-plug mr-2"></i>
                                Jalankan Test Koneksi
                            </button>
                        </form>

                        <a href="pengaturan.php"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                            <i class="fa-solid fa-gear mr-2"></i>
                            Pengaturan
                        </a>

                        <a href="log_sinkronisasi.php"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                            <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                            Log Sinkronisasi
                        </a>
                    </div>


                <?php endif; ?>

            </div>

            <?php if ($hasil_test): ?>

                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

                    <h3 class="text-lg font-bold text-slate-800 mb-4">
                        Hasil Test Koneksi
                    </h3>

                    <?php if ($hasil_test['success']): ?>
                        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 mb-5">
                            <div class="font-bold mb-1">Koneksi Berhasil</div>
                            <div><?= htmlspecialchars($hasil_test['message']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 mb-5">
                            <div class="font-bold mb-1">Koneksi Gagal</div>
                            <div><?= htmlspecialchars($hasil_test['message']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($token_result)): ?>
                        <div class="mb-5">
                            <p class="text-sm text-slate-500 mb-2">Token Neo Feeder</p>
                            <div class="p-4 rounded-xl bg-slate-900 text-green-300 text-xs overflow-x-auto">
                                <?= htmlspecialchars(substr($token_result, 0, 80)); ?>
                                <?= strlen($token_result) > 80 ? '...' : ''; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profil_result)): ?>
                        <div>
                            <p class="text-sm text-slate-500 mb-2">Profil Perguruan Tinggi</p>

                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full text-sm">
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ((array) $profil_result as $key => $value): ?>
                                            <tr>
                                                <td class="px-4 py-3 font-semibold text-slate-700 bg-slate-50 w-56">
                                                    <?= htmlspecialchars($key); ?>
                                                </td>
                                                <td class="px-4 py-3 text-slate-700">
                                                    <?php
                                                    if (is_array($value) || is_object($value)) {
                                                        echo '<pre class="text-xs whitespace-pre-wrap">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                                    } else {
                                                        echo htmlspecialchars((string) $value);
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

                    <h3 class="text-lg font-bold text-slate-800 mb-4">
                        Response Lengkap Neo Feeder
                    </h3>

                    <pre
                        class="p-4 rounded-xl bg-slate-900 text-green-300 text-xs overflow-x-auto whitespace-pre-wrap"><?= htmlspecialchars(json_encode($hasil_test['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

                </div>

            <?php endif; ?>

        </div>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Tahapan Test
                </h3>

                <div class="space-y-4 text-sm text-slate-600">
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                        <p class="font-semibold text-blue-700 mb-1">1. GetToken</p>
                        <p>Sistem meminta token ke Neo Feeder menggunakan username dan password.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                        <p class="font-semibold text-green-700 mb-1">2. GetProfilPT</p>
                        <p>Jika token berhasil, sistem mengambil profil perguruan tinggi.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-purple-50 border border-purple-100">
                        <p class="font-semibold text-purple-700 mb-1">3. Simpan Log</p>
                        <p>Request dan response dicatat pada tabel neofeeder_log.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Catatan Penting
                </h3>

                <div class="space-y-3 text-sm text-slate-600">
                    <p>
                        Pastikan aplikasi Neo Feeder aktif dan URL Web Service benar.
                    </p>

                    <p>
                        Berdasarkan daftar web service yang Anda berikan, fungsi <strong>GetToken</strong> dan
                        <strong>GetProfilPT</strong> tersedia untuk proses test koneksi awal.
                        :contentReference[oaicite:0]{index=0}
                    </p>

                    <p>
                        Jika gagal, cek kembali username, password, URL WS, port aplikasi, dan firewall lokal.
                    </p>
                </div>
            </div>

        </aside>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>