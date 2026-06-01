<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/../../includes/whatsapp_gateway.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Test WhatsApp Gateway";
$page_subtitle = "Uji pengiriman WhatsApp dari sistem SIAKAD";

$pesan_status = "";
$tipe_alert = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = mysqli_real_escape_string($conn, $_POST['nomor'] ?? '');
    $pesan = $_POST['pesan'] ?? '';

    if (empty($nomor) || empty($pesan)) {
        $pesan_status = "Nomor WhatsApp dan pesan wajib diisi.";
        $tipe_alert = "error";
    } else {
        $kirim = kirim_whatsapp(
            $conn,
            $_SESSION['id_user'],
            $nomor,
            $pesan
        );

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Melakukan test WhatsApp gateway ke: " . $nomor,
            "WhatsApp Gateway"
        );

        if ($kirim) {
            $pesan_status = "WhatsApp berhasil diproses. Silakan cek Data WhatsApp.";
            $tipe_alert = "success";
        } else {
            $pesan_status = "WhatsApp gagal diproses. Periksa konfigurasi WA gateway.";
            $tipe_alert = "error";
        }
    }
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Test WhatsApp Gateway</h2>
            <p class="text-sm text-slate-500">Gunakan form ini untuk menguji pengiriman WhatsApp.</p>
        </div>

        <a href="test_gateway.php"
            class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <?php if (!empty($pesan_status)): ?>
                <div class="mb-5 rounded-xl px-4 py-3 text-sm font-semibold
                    <?= $tipe_alert == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                    <?= $pesan_status; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Nomor WhatsApp Tujuan
                    </label>
                    <input type="text" name="nomor" required placeholder="628xxxxxxxxxx"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">

                    <p class="text-xs text-slate-500 mt-2">
                        Gunakan format internasional, contoh: 6281234567890.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Pesan WhatsApp
                    </label>
                    <textarea name="pesan" rows="6" required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">Test WA Gateway SIAKAD ATITB berhasil diproses.</textarea>
                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-green-700 hover:bg-green-800 text-white font-semibold">
                    <i class="fa-brands fa-whatsapp mr-2"></i>
                    Kirim Test WhatsApp
                </button>

            </form>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Menu WhatsApp</h3>

            <div class="space-y-3">
                <a href="data_whatsapp.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-green-50 text-slate-700 font-semibold">
                    Data WhatsApp
                </a>

                <a href="data_notifikasi.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 font-semibold">
                    Data Notifikasi
                </a>

                <a href="test_email.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-purple-50 text-slate-700 font-semibold">
                    Test Email
                </a>

                <a href="test_gateway.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>

            </div>
        </aside>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
