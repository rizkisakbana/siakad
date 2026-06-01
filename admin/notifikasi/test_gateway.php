<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Test Gateway";
$page_subtitle = "Pusat pengujian email gateway dan WhatsApp gateway";

$total_email = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM email_log
"))['total'] ?? 0;

$total_email_gagal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM email_log WHERE status = 'gagal'
"))['total'] ?? 0;

$total_whatsapp = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM whatsapp_log
"))['total'] ?? 0;

$total_whatsapp_gagal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total FROM whatsapp_log WHERE status = 'gagal'
"))['total'] ?? 0;

$email_terakhir = mysqli_query($conn, "
    SELECT * FROM email_log
    ORDER BY id_email_log DESC
    LIMIT 5
");

$wa_terakhir = mysqli_query($conn, "
    SELECT * FROM whatsapp_log
    ORDER BY id_whatsapp_log DESC
    LIMIT 5
");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total Email</p>
            <h2 class="text-3xl font-bold text-purple-700 mt-2"><?= number_format($total_email); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Email Gagal</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_email_gagal); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">Total WhatsApp</p>
            <h2 class="text-3xl font-bold text-green-700 mt-2"><?= number_format($total_whatsapp); ?></h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5">
            <p class="text-sm text-slate-500">WhatsApp Gagal</p>
            <h2 class="text-3xl font-bold text-red-700 mt-2"><?= number_format($total_whatsapp_gagal); ?></h2>
        </div>

    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <a href="test_email.php"
           class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:border-purple-300 hover:bg-purple-50 transition">
            <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center mb-5">
                <i class="fa-solid fa-envelope text-2xl"></i>
            </div>

            <h2 class="text-xl font-bold text-slate-800">Test Email Gateway</h2>
            <p class="text-sm text-slate-500 mt-2">
                Uji pengiriman email menggunakan konfigurasi email gateway sistem.
            </p>
        </a>

        <a href="test_whatsapp.php"
           class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 hover:border-green-300 hover:bg-green-50 transition">
            <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-700 flex items-center justify-center mb-5">
                <i class="fa-brands fa-whatsapp text-2xl"></i>
            </div>

            <h2 class="text-xl font-bold text-slate-800">Test WhatsApp Gateway</h2>
            <p class="text-sm text-slate-500 mt-2">
                Uji pengiriman WhatsApp menggunakan API gateway yang telah dikonfigurasi.
            </p>
        </a>

    </section>

    <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6 mb-6">
        <h2 class="text-xl font-bold text-slate-800 mb-5">Menu Cepat Modul Notifikasi</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

            <a href="data_notifikasi.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition">
                <i class="fa-solid fa-bell text-blue-700 text-2xl mb-3"></i>
                <h3 class="font-bold text-slate-800">Data Notifikasi</h3>
                <p class="text-sm text-slate-500 mt-1">Pantau notifikasi internal.</p>
            </a>

            <a href="data_email.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-purple-300 hover:bg-purple-50 transition">
                <i class="fa-solid fa-envelope text-purple-700 text-2xl mb-3"></i>
                <h3 class="font-bold text-slate-800">Data Email</h3>
                <p class="text-sm text-slate-500 mt-1">Riwayat email gateway.</p>
            </a>

            <a href="data_whatsapp.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-green-300 hover:bg-green-50 transition">
                <i class="fa-brands fa-whatsapp text-green-700 text-2xl mb-3"></i>
                <h3 class="font-bold text-slate-800">Data WhatsApp</h3>
                <p class="text-sm text-slate-500 mt-1">Riwayat WA gateway.</p>
            </a>

            <a href="../log_aktivitas/data_aktivitas.php"
               class="p-5 rounded-2xl border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition">
                <i class="fa-solid fa-clock-rotate-left text-slate-700 text-2xl mb-3"></i>
                <h3 class="font-bold text-slate-800">Log Aktivitas</h3>
                <p class="text-sm text-slate-500 mt-1">Pantau aktivitas sistem.</p>
            </a>

        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Email Terakhir</h2>

            <div class="space-y-3">
                <?php if (mysqli_num_rows($email_terakhir) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($email_terakhir)): ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="font-semibold text-slate-800 break-all">
                                <?= htmlspecialchars($row['tujuan_email']); ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                <?= htmlspecialchars($row['status']); ?> • <?= tanggal_jam_indonesia($row['created_at']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Belum ada data email.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-4 sm:p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">WhatsApp Terakhir</h2>

            <div class="space-y-3">
                <?php if (mysqli_num_rows($wa_terakhir) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($wa_terakhir)): ?>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="font-semibold text-slate-800 break-all">
                                <?= htmlspecialchars($row['tujuan_nomor']); ?>
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                <?= htmlspecialchars($row['status']); ?> • <?= tanggal_jam_indonesia($row['created_at']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-500">Belum ada data WhatsApp.</p>
                <?php endif; ?>
            </div>
        </div>

    </section>

</main>

<?php require_once "../../includes/footer.php"; ?>