<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/log_aktivitas.php";
require_once "../../includes/email_gateway.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Test Email Gateway";
$page_subtitle = "Uji pengiriman email dari sistem SIAKAD";

$pesan_status = "";
$tipe_alert = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $subjek = mysqli_real_escape_string($conn, $_POST['subjek'] ?? '');
    $pesan = $_POST['pesan'] ?? '';

    if (empty($email) || empty($subjek) || empty($pesan)) {
        $pesan_status = "Email tujuan, subjek, dan pesan wajib diisi.";
        $tipe_alert = "error";
    } else {
        $isi_email = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2 style='color:#1d4ed8;'>Test Email Gateway SIAKAD ATITB</h2>
                <p>" . nl2br(htmlspecialchars($pesan)) . "</p>
                <hr>
                <p style='font-size:12px;color:#666;'>Email ini dikirim dari sistem SIAKAD ATITB.</p>
            </div>
        ";

        $kirim = kirim_email(
            $conn,
            $_SESSION['id_user'],
            $email,
            $subjek,
            $isi_email
        );

        simpan_log(
            $conn,
            $_SESSION['id_user'],
            "Melakukan test email gateway ke: " . $email,
            "Email Gateway"
        );

        if ($kirim) {
            $pesan_status = "Email berhasil diproses. Silakan cek Data Email.";
            $tipe_alert = "success";
        } else {
            $pesan_status = "Email gagal diproses. Periksa konfigurasi email gateway.";
            $tipe_alert = "error";
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Test Email Gateway</h2>
            <p class="text-sm text-slate-500">Gunakan form ini untuk menguji pengiriman email.</p>
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
                        Email Tujuan
                    </label>
                    <input type="email" name="email" required placeholder="contoh@email.com"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Subjek Email
                    </label>
                    <input type="text" name="subjek" required value="Test Email Gateway SIAKAD ATITB"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Pesan
                    </label>
                    <textarea name="pesan" rows="6" required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">Ini adalah pesan percobaan email gateway dari SIAKAD ATITB.</textarea>
                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-3 rounded-xl bg-purple-700 hover:bg-purple-800 text-white font-semibold">
                    <i class="fa-solid fa-paper-plane mr-2"></i>
                    Kirim Test Email
                </button>

            </form>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Menu Email</h3>

            <div class="space-y-3">
                <a href="data_email.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-purple-50 text-slate-700 font-semibold">
                    Data Email
                </a>

                <a href="data_notifikasi.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 font-semibold">
                    Data Notifikasi
                </a>

                <a href="test_whatsapp.php"
                    class="block px-4 py-3 rounded-xl bg-slate-100 hover:bg-green-50 text-slate-700 font-semibold">
                    Test WhatsApp
                </a>


                <a href="test_email.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>

            </div>
        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>