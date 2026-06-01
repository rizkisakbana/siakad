<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/log_aktivitas_helper.php";

/** @var mysqli $conn */

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Detail Aktivitas";
$page_subtitle = "Informasi detail aktivitas pengguna sistem";

$id_log = intval($_GET['id'] ?? 0);

if ($id_log <= 0) {
    set_alert("error", "ID aktivitas tidak valid.");
    header("Location: data_aktivitas.php");
    exit;
}

$data = aktivitas_query_one($conn, "
    SELECT 
        log_aktivitas.*,
        users.nama_lengkap,
        users.username,
        users.email,
        users.no_hp,
        roles.nama_role
    FROM log_aktivitas
    LEFT JOIN users ON log_aktivitas.id_user = users.id_user
    LEFT JOIN roles ON users.id_role = roles.id_role
    WHERE log_aktivitas.id_log = '$id_log'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data aktivitas tidak ditemukan.");
    header("Location: data_aktivitas.php");
    exit;
}

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Aktivitas</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi lengkap aktivitas sistem.</p>
        </div>

        <a href="data_aktivitas.php"
           class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i>
            Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <div class="flex items-start gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-clock-rotate-left text-2xl"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-800">
                        <?= htmlspecialchars($data['aktivitas']); ?>
                    </h3>
                    <p class="text-sm text-slate-500">
                        Modul: <?= htmlspecialchars($data['modul'] ?? '-'); ?>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID Log</p>
                    <p class="font-semibold text-slate-800">
                        <?= $data['id_log']; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Waktu Aktivitas</p>
                    <p class="font-semibold text-slate-800">
                        <?= date('d-m-Y H:i:s', strtotime($data['created_at'])); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">IP Address</p>
                    <p class="font-semibold text-slate-800 break-all">
                        <?= htmlspecialchars($data['ip_address'] ?? '-'); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Modul</p>
                    <p class="font-semibold text-slate-800">
                        <?= htmlspecialchars($data['modul'] ?? '-'); ?>
                    </p>
                </div>

            </div>

            <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <p class="text-xs text-slate-500 mb-2">User Agent / Perangkat</p>
                <p class="text-sm text-slate-700 break-words leading-relaxed">
                    <?= htmlspecialchars($data['user_agent'] ?? '-'); ?>
                </p>
            </div>

        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">

            <h3 class="text-lg font-bold text-slate-800 mb-4">
                Informasi Pengguna
            </h3>

            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
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
                    <span class="font-semibold text-slate-800 text-right">
                        <?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">Email</span>
                    <span class="font-semibold text-slate-800 text-right break-all">
                        <?= htmlspecialchars($data['email'] ?? '-'); ?>
                    </span>
                </div>

                <div class="flex justify-between gap-4 border-b pb-3">
                    <span class="text-slate-500">No. HP</span>
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
                <a href="data_aktivitas.php"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-600 hover:bg-slate-700 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Kembali
                </a>

                <a href="hapus_aktivitas.php?id=<?= $data['id_log']; ?>"
                   onclick="return confirm('Yakin ingin menghapus data aktivitas ini?')"
                   class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i>
                    Hapus Aktivitas
                </a>
            </div>

        </aside>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
