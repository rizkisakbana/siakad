<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/neofeeder_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Pengaturan Neo Feeder";
$page_subtitle = "Konfigurasi koneksi Web Service Neo Feeder PDDikti";

$config = get_neofeeder_config($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pt = mysqli_real_escape_string($conn, trim($_POST['nama_pt'] ?? ''));
    $kode_pt = mysqli_real_escape_string($conn, trim($_POST['kode_pt'] ?? ''));
    $url_ws = mysqli_real_escape_string($conn, trim($_POST['url_ws'] ?? ''));
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $password = mysqli_real_escape_string($conn, trim($_POST['password'] ?? ''));
    $environment = mysqli_real_escape_string($conn, $_POST['environment'] ?? 'development');

    if (empty($url_ws) || empty($username)) {
        set_alert("error", "URL Web Service dan username wajib diisi.");
    } else {
        if ($config) {
            $password_sql = "";

            if (!empty($password)) {
                $password_sql = ", password = '$password'";
            }

            $update = mysqli_query($conn, "
                UPDATE neofeeder_config SET
                    nama_pt = '$nama_pt',
                    kode_pt = '$kode_pt',
                    url_ws = '$url_ws',
                    username = '$username',
                    environment = '$environment',
                    status = 'disconnected',
                    token = NULL
                    $password_sql
                WHERE id_config = '{$config['id_config']}'
            ");

            if ($update) {
                simpan_log(
                    $conn,
                    $_SESSION['id_user'],
                    "Memperbarui pengaturan Neo Feeder",
                    "Neo Feeder"
                );

                set_alert("success", "Pengaturan Neo Feeder berhasil diperbarui.");
                header("Location: pengaturan.php");
                exit;
            } else {
                set_alert("error", "Pengaturan Neo Feeder gagal diperbarui.");
            }
        } else {
            if (empty($password)) {
                set_alert("error", "Password wajib diisi untuk konfigurasi baru.");
            } else {
                $insert = mysqli_query($conn, "
                    INSERT INTO neofeeder_config
                    (
                        nama_pt,
                        kode_pt,
                        url_ws,
                        username,
                        password,
                        environment,
                        status
                    )
                    VALUES
                    (
                        '$nama_pt',
                        '$kode_pt',
                        '$url_ws',
                        '$username',
                        '$password',
                        '$environment',
                        'disconnected'
                    )
                ");

                if ($insert) {
                    simpan_log(
                        $conn,
                        $_SESSION['id_user'],
                        "Menambahkan pengaturan Neo Feeder",
                        "Neo Feeder"
                    );

                    set_alert("success", "Pengaturan Neo Feeder berhasil disimpan.");
                    header("Location: pengaturan.php");
                    exit;
                } else {
                    set_alert("error", "Pengaturan Neo Feeder gagal disimpan.");
                }
            }
        }
    }
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

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Pengaturan Neo Feeder</h2>
            <p class="text-sm text-slate-500">
                Konfigurasi koneksi Web Service Neo Feeder/PDDikti.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="test_koneksi.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                <i class="fa-solid fa-plug mr-2"></i>
                Test Koneksi
            </a>

            <a href="log_sinkronisasi.php"
               class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                Log Sinkronisasi
            </a>
        </div>
    </div>

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
            <p class="text-sm text-slate-500">Kode PT</p>
            <h2 class="text-2xl font-bold text-purple-700 mt-2">
                <?= htmlspecialchars($config['kode_pt'] ?? '-'); ?>
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

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Form Konfigurasi Neo Feeder
            </h3>

            <form method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nama Perguruan Tinggi
                    </label>
                    <input type="text"
                           name="nama_pt"
                           value="<?= htmlspecialchars($config['nama_pt'] ?? ''); ?>"
                           placeholder="Akademi Teknik Informatika Tunas Bangsa"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Kode PT
                    </label>
                    <input type="text"
                           name="kode_pt"
                           value="<?= htmlspecialchars($config['kode_pt'] ?? ''); ?>"
                           placeholder="Contoh: 041xxx"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        URL Web Service Neo Feeder
                    </label>
                    <input type="text"
                           name="url_ws"
                           required
                           value="<?= htmlspecialchars($config['url_ws'] ?? ''); ?>"
                           placeholder="http://localhost:8100/ws/live2.php"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Contoh lokal: http://localhost:8100/ws/live2.php
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Username Neo Feeder
                    </label>
                    <input type="text"
                           name="username"
                           required
                           value="<?= htmlspecialchars($config['username'] ?? ''); ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Password Neo Feeder
                    </label>
                    <input type="password"
                           name="password"
                           placeholder="<?= $config ? 'Kosongkan jika tidak diubah' : 'Masukkan password'; ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Jika konfigurasi sudah ada, kosongkan password apabila tidak ingin mengubahnya.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Environment
                    </label>
                    <select name="environment"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                        <option value="development" <?= ($config['environment'] ?? '') == 'development' ? 'selected' : ''; ?>>
                            Development
                        </option>
                        <option value="production" <?= ($config['environment'] ?? '') == 'production' ? 'selected' : ''; ?>>
                            Production
                        </option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-save mr-2"></i>
                        Simpan Pengaturan
                    </button>

                    <a href="test_koneksi.php"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                        <i class="fa-solid fa-plug mr-2"></i>
                        Test Koneksi
                    </a>
                </div>

            </form>

        </div>

        <aside class="space-y-6">

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Informasi Koneksi
                </h3>

                <div class="space-y-3 text-sm">
                    <div class="border-b pb-3">
                        <p class="text-slate-500">URL WS</p>
                        <p class="font-semibold text-slate-800 break-all mt-1">
                            <?= htmlspecialchars($config['url_ws'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-slate-500">Username</p>
                        <p class="font-semibold text-slate-800 mt-1">
                            <?= htmlspecialchars($config['username'] ?? '-'); ?>
                        </p>
                    </div>

                    <div class="border-b pb-3">
                        <p class="text-slate-500">Token</p>
                        <p class="font-semibold text-slate-800 mt-1">
                            <?= !empty($config['token']) ? 'Tersimpan' : 'Belum tersedia'; ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-500">Updated At</p>
                        <p class="font-semibold text-slate-800 mt-1">
                            <?= !empty($config['updated_at']) ? tanggal_jam_indonesia($config['updated_at']) : '-'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">
                    Catatan Penting
                </h3>

                <div class="space-y-4 text-sm text-slate-600">
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                        <p class="font-semibold text-blue-700 mb-1">URL Web Service</p>
                        <p>Pastikan URL mengarah ke file live Neo Feeder, misalnya live2.php.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                        <p class="font-semibold text-green-700 mb-1">Token</p>
                        <p>Token akan otomatis diperbarui saat tombol Test Koneksi dijalankan.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-orange-50 border border-orange-100">
                        <p class="font-semibold text-orange-700 mb-1">Keamanan</p>
                        <p>Simpan akses Neo Feeder hanya untuk role admin yang berwenang.</p>
                    </div>
                </div>
            </div>

        </aside>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>