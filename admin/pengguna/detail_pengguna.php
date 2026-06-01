<?php
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/helper.php";
require_once __DIR__ . "/../../includes/alert.php";
require_once __DIR__ . "/../../includes/log_aktivitas.php";
require_once __DIR__ . "/pengguna_helper.php";

cek_login();
cek_role(['super_admin', 'admin_akademik']);

$page_title = "Detail Pengguna";
$page_subtitle = "Informasi lengkap akun pengguna";

$id_user = intval($_GET['id'] ?? 0);

if ($id_user <= 0) {
    set_alert("error", "ID pengguna tidak valid.");
    header("Location: data_pengguna.php");
    exit;
}

$data = pengguna_one($conn, "
    SELECT users.*, roles.nama_role
    FROM users
    LEFT JOIN roles ON users.id_role = roles.id_role
    WHERE users.id_user='$id_user'
    LIMIT 1
");

if (!$data) {
    set_alert("error", "Data pengguna tidak ditemukan.");
    header("Location: data_pengguna.php");
    exit;
}

simpan_log($conn, $_SESSION['id_user'], "Melihat detail pengguna: " . $data['nama_lengkap'], "Pengguna");

require_once __DIR__ . "/../../includes/header.php";
require_once __DIR__ . "/../../includes/sidebar.php";
require_once __DIR__ . "/../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">

    <!-- <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Detail Pengguna</h2>
            <p class="text-sm text-slate-500">Menampilkan informasi akun pengguna.</p>
        </div>

        <a href="data_pengguna.php" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div> -->

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <div class="flex items-start gap-4 mb-6">
                <div
                    class="w-24 h-24 rounded-xl flex items-center justify-center text-xl font-bold">
                    <?php if (!empty($data['foto'])): ?>
                        <img src="../../uploads/pengguna/<?= htmlspecialchars($data['foto']); ?>" alt="Foto Pengguna"
                            class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                    <?php else: ?>
                        <div
                            class="w-24 h-24 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-bold">
                            <?= strtoupper(substr($data['nama_lengkap'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($data['nama_lengkap']); ?></h3>
                    <p class="text-lg text-slate-500"><?= htmlspecialchars($data['username']); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">ID User</p>
                    <p class="font-semibold"><?= $data['id_user']; ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Role</p>
                    <p class="font-semibold capitalize">
                        <?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Email</p>
                    <p class="font-semibold break-all"><?= htmlspecialchars($data['email'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">No HP</p>
                    <p class="font-semibold"><?= htmlspecialchars($data['no_hp'] ?? '-'); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Status</p>
                    <span
                        class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $data['status'] == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?= htmlspecialchars($data['status']); ?>
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Terakhir Login</p>
                    <p class="font-semibold">
                        <?= !empty($data['last_login']) ? tanggal_jam_indonesia($data['last_login']) : '-'; ?>
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Tanggal Dibuat</p>
                    <p class="font-semibold"><?= tanggal_jam_indonesia($data['created_at']); ?></p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500 mb-1">Terakhir Diubah</p>
                    <p class="font-semibold">
                        <?= !empty($data['updated_at']) ? tanggal_jam_indonesia($data['updated_at']) : '-'; ?>
                    </p>
                </div>
            </div>
        </section>

        <aside class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Data</h3>

            <div class="space-y-3">
                <a href="edit_pengguna.php?id=<?= $data['id_user']; ?>"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 text-white font-semibold">
                    <i class="fa-solid fa-pen mr-2"></i> Edit Pengguna
                </a>

                <a href="hapus_pengguna.php?id=<?= $data['id_user']; ?>"
                    onclick="return confirm('Yakin ingin menghapus pengguna ini?')"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">
                    <i class="fa-solid fa-trash mr-2"></i> Hapus Pengguna
                </a>

                <a href="data_pengguna.php"
                    class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
            </div>
        </aside>

    </div>

</main>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
