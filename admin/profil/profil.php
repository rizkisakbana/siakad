<?php
require_once "../../includes/auth.php";
require_once "../../config/database.php";
require_once "../../includes/helper.php";
require_once "../../includes/alert.php";
require_once "../../includes/log_aktivitas.php";

cek_login();
cek_role(['super_admin', 'admin_akademik', 'admin_keuangan']);

$page_title = "Profil Saya";
$page_subtitle = "Informasi akun pengguna yang sedang aktif";

$id_user = intval($_SESSION['id_user'] ?? 0);

$query = mysqli_query($conn, "
    SELECT users.*, roles.nama_role, roles.keterangan AS keterangan_role
    FROM users
    LEFT JOIN roles ON users.id_role = roles.id_role
    WHERE users.id_user = '$id_user'
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) < 1) {
    set_alert("error", "Data profil pengguna tidak ditemukan.");
    header("Location: ../dashboard.php");
    exit;
}

$data = mysqli_fetch_assoc($query);
$inisial = strtoupper(substr(trim($data['nama_lengkap'] ?? 'P'), 0, 1));
$foto = !empty($data['foto']) ? "../../uploads/pengguna/" . htmlspecialchars($data['foto']) : null;

simpan_log($conn, $id_user, "Melihat profil sendiri", "Profil");

require_once "../../includes/header.php";
require_once "../../includes/sidebar.php";
require_once "../../includes/navbar.php";
?>

<main class="lg:ml-[270px] p-4 sm:p-6 lg:p-8">
    <?php show_alert(); ?>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <section class="xl:col-span-2 overflow-hidden rounded-2xl bg-white shadow-lg border border-slate-100">
            <div class="bg-gradient-to-r from-blue-800 via-blue-700 to-cyan-700 p-6 sm:p-8 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <div class="shrink-0">
                        <?php if ($foto && file_exists("../../uploads/pengguna/" . $data['foto'])): ?>
                            <img src="<?= $foto; ?>" alt="Foto Profil"
                                class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover border-4 border-white/30 shadow-lg">
                        <?php else: ?>
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center text-4xl font-bold shadow-lg">
                                <?= $inisial; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0">
                        <p class="inline-flex items-center px-3 py-1 rounded-full bg-white/15 border border-white/20 text-xs font-semibold mb-3">
                            <i class="fa-solid fa-user-shield mr-2"></i>
                            <?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? 'Pengguna')); ?>
                        </p>
                        <h2 class="text-2xl sm:text-3xl font-bold leading-tight break-words">
                            <?= htmlspecialchars($data['nama_lengkap']); ?>
                        </h2>
                        <p class="mt-2 text-blue-100 break-all">
                            <?= htmlspecialchars($data['email'] ?: 'Email belum diisi'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">ID Pengguna</p>
                        <p class="font-bold text-slate-800"><?= (int) $data['id_user']; ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Username</p>
                        <p class="font-bold text-slate-800 break-all"><?= htmlspecialchars($data['username']); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Nama Lengkap</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($data['nama_lengkap']); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Role</p>
                        <p class="font-bold text-slate-800 capitalize">
                            <?= htmlspecialchars(str_replace('_', ' ', $data['nama_role'] ?? '-')); ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Email</p>
                        <p class="font-bold text-slate-800 break-all"><?= htmlspecialchars($data['email'] ?: '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">No. HP / WhatsApp</p>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($data['no_hp'] ?: '-'); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Status Akun</p>
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $data['status'] === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?= htmlspecialchars($data['status']); ?>
                        </span>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Terakhir Login</p>
                        <p class="font-bold text-slate-800">
                            <?= !empty($data['last_login']) ? tanggal_jam_indonesia($data['last_login']) : '-'; ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Dibuat Pada</p>
                        <p class="font-bold text-slate-800"><?= tanggal_jam_indonesia($data['created_at']); ?></p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-semibold text-slate-500 mb-1">Terakhir Diperbarui</p>
                        <p class="font-bold text-slate-800">
                            <?= !empty($data['updated_at']) ? tanggal_jam_indonesia($data['updated_at']) : '-'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Aksi Profil</h3>
                <div class="space-y-3">
                    <a href="edit_profil.php"
                        class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-semibold">
                        <i class="fa-solid fa-user-pen mr-2"></i>
                        Edit Profil
                    </a>

                    <a href="../dashboard.php"
                        class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Kembali ke Dashboard
                    </a>
                </div>
            </section>

            <section class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-3">Keamanan Akun</h3>
                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-green-100 text-green-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Password terenkripsi</p>
                            <p class="text-slate-500 mt-1">Password disimpan menggunakan hash, bukan teks asli.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800">Aktivitas tercatat</p>
                            <p class="text-slate-500 mt-1">Perubahan profil akan masuk ke log aktivitas sistem.</p>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</main>

<?php require_once "../../includes/footer.php"; ?>
