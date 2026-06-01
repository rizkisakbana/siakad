<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Detail WhatsApp Gateway";
$page_subtitle = "Informasi lengkap riwayat pengiriman WhatsApp";

$id_whatsapp_log = intval($_GET['id'] ?? 0);

if ($id_whatsapp_log <= 0) {
    echo "<script>
        alert('ID WhatsApp tidak valid.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        whatsapp_log.*,
        users.nama_lengkap,
        users.username,
        users.email,
        users.no_hp,
        roles.nama_role
    FROM whatsapp_log
    LEFT JOIN users ON whatsapp_log.id_user = users.id_user
    LEFT JOIN roles ON users.id_role = roles.id_role
    WHERE whatsapp_log.id_whatsapp_log = '$id_whatsapp_log'
    LIMIT 1
");

if (mysqli_num_rows($query) < 1) {
    echo "<script>
        alert('Data WhatsApp tidak ditemukan.');
        window.location='data_whatsapp.php';
    </script>";
    exit;
}

$data = mysqli_fetch_assoc($query);

simpan_log(
    $conn,
    $_SESSION['id_user'],
    "Melihat detail WhatsApp log ID: " . $id_whatsapp_log,
    "WhatsApp Gateway"
);

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail WhatsApp Gateway</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap pengiriman WhatsApp.</p>
        </div>

        <a href="data_whatsapp.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl 
                    <?php
                        if ($data['status'] == 'terkirim') echo 'bg-green-100 text-green-700';
                        elseif ($data['status'] == 'pending') echo 'bg-orange-100 text-orange-700';
                        else echo 'bg-red-100 text-red-700';
                    ?> flex items-center justify-center">

                    <?php if ($data['status'] == 'terkirim'): ?>
                        <i class="fa-solid fa-circle-check text-2xl"></i>
                    <?php elseif ($data['status'] == 'pending'): ?>
                        <i class="fa-solid fa-clock text-2xl"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-xmark text-2xl"></i>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        WhatsApp Gateway
                    </h3>
                    <p class="text-sm text-slate-500 break-all">
                        Nomor Tujuan: <?= htmlspecialchars($data['tujuan_nomor']); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID WhatsApp Log</p>
                    <p class="font-semibold text-slate-800">
                        <?= $data['id_whatsapp_log']; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status Pengiriman</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold
                        <?php
                            if ($data['status'] == 'terkirim') echo 'bg-green-100 text-green-700';
                            elseif ($data['status'] == 'pending') echo 'bg-orange-100 text-orange-700';
                            else echo 'bg-red-100 text-red-700';
                        ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold text-slate-800">
                        <?= tanggal_indonesia($data['created_at']); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Waktu Dibuat</p>
                    <p class="font-semibold text-slate-800">
                        <?= jam_indonesia($data['created_at']); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Terkirim</p>
                    <p class="font-semibold text-slate-800">
                        <?= !empty($data['sent_at']) ? tanggal_indonesia($data['sent_at']) : '-'; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Waktu Terkirim</p>
                    <p class="font-semibold text-slate-800">
                        <?= !empty($data['sent_at']) ? jam_indonesia($data['sent_at']) : '-'; ?>
                    </p>
                </div>

            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 mb-5">
                <p class="text-xs text-slate-500 mb-2">Isi Pesan WhatsApp</p>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                    <?= htmlspecialchars($data['isi_pesan']); ?>
                </p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs text-slate-500 mb-2">Response Gateway</p>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line break-words">
                    <?= htmlspecialchars($data['response'] ?? '-'); ?>
                </p>
            </div>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Pengguna
            </h3>

            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-green-700 text-white flex items-center justify-center text-xl font-bold">
                    <?= strtoupper(substr($data['nama_lengkap'] ?? 'S', 0, 1)); ?>
                </div>

                <div>
                    <p class="font-bold text-slate-800">
                        <?= htmlspecialchars($data['nama_lengkap'] ?? 'System'); ?>
                    </p>
                    <p class="text-sm text-slate-500">
                        <?= htmlspecialchars($data['username'] ?? '-'); ?>
                    </p>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">Role</span>
                    <span class="font-semibold text-slate-800 text-right capitalize">
                        <?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">Email User</span>
                    <span class="font-semibold text-slate-800 text-right break-all">
                        <?= htmlspecialchars($data['email'] ?? '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">No. HP User</span>
                    <span class="font-semibold text-slate-800 text-right">
                        <?= htmlspecialchars($data['no_hp'] ?? '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4">
                    <span class="text-slate-500">ID User</span>
                    <span class="font-semibold text-slate-800 text-right">
                        <?= htmlspecialchars($data['id_user'] ?? '-'); ?>
                    </span>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <a href="data_whatsapp.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>

                <a href="hapus_whatsapp.php?id=<?= $data['id_whatsapp_log']; ?>"
                   onclick="return confirm('Yakin ingin menghapus log WhatsApp ini?')"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus WhatsApp
                </a>
            </div>

        </aside>

    </div>

</main>

<?php require_once "../../includes/footer.php"; ?>